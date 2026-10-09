# PIXVA: Staging checklist for the security release

Use this list on a Staging copy that matches production in PHP version, web server, database and reverse-proxy layout. Record the ZIP SHA-256 and the commit before you start. Do not reuse results from older builds.

## 0. Identify the build

- ZIP SHA-256 matches the report (`sha256sum pixva.zip`).
- Install the ZIP. Check that `wp-content/themes/pixva/inc/security.php` contains `pixva_create_once` and `pixva_normalize_submission_id`.

## 1. Deployment requirements (must be confirmed, not assumed)

| Item | Check | Why |
|---|---|---|
| Private photos | `GET /wp-content/uploads/pixva-private/<any>.jpg` returns 403 or 404 on Nginx and Apache | The directory must not be served directly |
| Client IP | Behind a proxy or CDN: the `pixva_client_ip` filter returns the real client from a header the proxy overwrites. Without it, all visitors share one scope | Otherwise per-client limits are shared by everyone behind the proxy |
| IPv6 | A request from another address in the same /64 hits the same lookup limit | Confirms the /64 scope |
| WP-Cron | `wp cron event list` shows `pixva_prune_rows` hourly. The site uses a real cron or runs WP-Cron on traffic | Without it, `wp_options` rows are not pruned |
| Database | `SHOW INDEX FROM wp_options` lists `option_name` as UNIQUE (MySQL/MariaDB). On SQLite, confirm the same uniqueness | The create-once primitive depends on it |

## 2. Automated checks

- `PHP=8.3 php-wasm-cli tests/unit/php/order-security.test.php`: expect 66 passed, 0 failed.
- `NODE_PATH=<node_modules> node tests/unit/js/safe-html.test.cjs` (22) and `...safe-html-fixtures.test.cjs` (20).
- `LD_LIBRARY_PATH=<libs> NODE_PATH=<node_modules> node tests/unit/js/safe-html.chromium.cjs` (13) in each browser you can run (set `CHROMIUM_PATH`).

These are simulations. They do not replace the steps below.

## 3. Runtime checks (WordPress on Staging)

### 3a. Submission duplicates

1. Log in as a customer. Load the booking form and note `_pixva_sid`.
2. Submit the same form 10 times in parallel with the same `_pixva_sid` and the same payload (for example a script with `Promise.all` and the form's nonce).
3. Expect: exactly one new `pixva_orders` post with this payload; the others get the stored result or a 409 pending response.
4. Count orders for the test phone before and after. The count rises by one.
5. Force a validation error after claim (for example an invalid time slot). Resubmit the same `_pixva_sid` with valid data. Expect exactly one order.

### 3b. Order status concurrency

1. As a manager, open one order.
2. Send 10 status changes in parallel with different notes (the admin form or the REST route).
3. Expect: no 500 errors; the history (`_pixva_order_steps`) contains one entry per accepted change. Changes queue on the lock and each one is quick, so all should succeed. A 409 "busy" is expected only if a holder keeps the lock longer than 10 seconds; then it must write nothing.
4. Check `wp_options` for any `pixva_olock_<id>` row after the run. It must not exist.

### 3c. Lookup limits

1. From client A, send 6 wrong phone numbers for one order code. Expect the 6th to return `locked`.
2. From client B, send the correct code and phone. Expect the order. This confirms that A cannot lock B.
3. From client A (still locked), send the correct phone. Expect `locked`.
4. Monitoring: after 50 wrong attempts from distinct clients for one code, check that the `pixva_suspicious_lookup` action fired (add a temporary logging snippet on Staging only).

### 3d. Browser and XSS

1. Open the tracking and warranty pages with a valid order. Confirm the order view appears and is correctly styled (RTL, timeline, history).
2. Submit the calculator with each service. Confirm the result appears.
3. Open the error-code archive and filter it. Confirm the results appear.
4. In the browser console, run `pixva.setSafeHTML(document.body, '<img src=x onerror=alert(1)>')`. Expect no dialog.

### 3e. Housekeeping

1. Note the number of `wp_options` rows with prefixes `pixva_cf_`, `pixva_cg_`, `pixva_sub_`, `pixva_olock_`.
2. Run `wp cron event run pixva_prune_rows`.
3. Expect rows older than the TTLs to disappear and no others to change.

## 4. Regression

Repeat the existing suites on the same build (REST contract, redirect/404 matrix, layout, SEO, keyboard, contrast, the sec2/sec3 suites). Do not carry over results from earlier builds.

## 5. Cleanup

- Delete all test orders, test users, test photos under `pixva-private/`, and `pixva_*` option rows created by the tests.
- Confirm the cleanup in the report.

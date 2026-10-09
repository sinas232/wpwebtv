# PIXVA: Staging checklist and release gate

Use this on a Staging copy that matches production in PHP version, web server, database engine and reverse-proxy/CDN layout. Record the ZIP SHA-256 and the source commit before you start. Do not reuse results from an earlier build: every run below must be repeated on the build you are testing.

**Release gate:** operational release stays NO-GO until every item marked **[GATE]** passes on Staging and the decisions in section 1 are recorded.

---

## 0. Identify the build

- `sha256sum pixva.zip` equals the SHA-256 in the release report.
- After installing, `wp-content/themes/pixva/inc/repairs.php` contains `pixva_place_order_once` and `pixva_orders_by_submission`, and `security.php` contains `pixva_client_ip` and `pixva_trusted_proxies`.
- `PIXVA_VERSION` is unchanged (2.2.0). The change adds no schema, no option name visible to users, and no form, REST, notification or dashboard contract change, so no version bump or cache flush is needed.

---

## 1. Deployment decisions (client IP and proxies)

The lookup counters and the general budget are keyed on the client address. The address is taken **only** from `REMOTE_ADDR` unless the site owner declares a proxy as trusted. Client-sent forwarded headers are never trusted from an undeclared peer.

Choose exactly one case and record it in the release notes.

| Case | Who is in front of PHP | Configuration | Effect |
|---|---|---|---|
| **A. Direct** | Nothing, or only a firewall that does not rewrite addresses | None | `REMOTE_ADDR` is the client. Correct behaviour. |
| **B. Proxy you control** | Your load balancer, nginx, or CDN whose IP range you know | Restore the real address in the web server (B1 or B2) **and** optionally declare the proxy (section 1.2). | Each visitor has its own scope. |
| **C. Proxy you do not control** | A third-party CDN or shared proxy | Do **not** declare any trusted proxy | All visitors behind that proxy share one scope. See 1.3. |

### 1.1 Case B: restoring the real address

**B1. nginx as the edge (recommended).** nginx sets `REMOTE_ADDR` for PHP. Declare only the proxies you own:

```nginx
set_real_ip_from 10.0.0.0/8;      # your load balancer ONLY, replace with the real range
real_ip_header X-Forwarded-For;
real_ip_recursive on;
```

With B1, `REMOTE_ADDR` is already the client, so no PHP trust setting is needed. Setting one is harmless, because a client address is never in the trusted list.

**B2. Apache with mod_remoteip:**

```apache
RemoteIPHeader X-Forwarded-For
RemoteIPTrustedProxy 10.0.0.0/8   # your load balancer ONLY
```

**B3. Cloudflare in front of an origin you control.** Use the current published Cloudflare ranges (fetch them from Cloudflare; do not copy them from this document). Restrict the origin firewall so that only Cloudflare can reach it. **Without that firewall, any client can send `CF-Connecting-IP` directly to the origin and this setting is spoofable.**

```php
// wp-config.php (or a mu-plugin)
define( 'PIXVA_TRUSTED_PROXIES', array( /* current Cloudflare IPv4 and IPv6 ranges */ ) );
define( 'PIXVA_CLIENT_IP_HEADER', 'HTTP_CF_CONNECTING_IP' );
```

**B4. Generic load balancer that appends to X-Forwarded-For:**

```php
define( 'PIXVA_TRUSTED_PROXIES', array( '10.0.0.0/8' ) ); // the balancer's addresses
define( 'PIXVA_CLIENT_IP_HEADER', 'HTTP_X_FORWARDED_FOR' ); // default
```

The balancer must **overwrite** or **append** to the header. It must not pass the client's own header through unchanged. The chain is walked from the right, so a client-supplied leftmost value is ignored, but only if the balancer appends.

The same settings can be given as filters, `pixva_trusted_proxies` and `pixva_client_ip_header`, which is useful when the list is computed at runtime.

### 1.2 Verify case B on Staging [GATE for case B]

1. From a direct connection (not through the proxy), send 6 wrong phone numbers for one order code with `X-Forwarded-For: 203.0.113.9`. The 6th request must return the lockout. Repeat with a different spoofed header value: it must still return the lockout (the header is ignored from an untrusted peer).
2. Through the proxy, from two different real clients (two networks), make 5 wrong attempts from client A for one code. Client A is then locked. Client B, on the same code, must still receive `not_found` (not `locked`) for a wrong phone.
3. A correct code and phone from client A while A is locked returns `locked` (by design: no oracle). The same correct code and phone from client B succeeds.

If step 2 fails (both clients locked), the proxy trust is not configured. Return to section 1.

### 1.3 Case C: uncontrolled shared proxy [decision required]

Without a trusted-proxy declaration, the site cannot tell visitors apart behind such a proxy, and it does **not** guess. The site then behaves as follows, and this must be accepted in writing before launch:

- **Successful lookups are never refused by the general budget.** A customer with a correct code and phone is not affected by other visitors' volume.
- **Failed lookups of one code** from the shared scope count against that scope. After 5 wrong phone numbers for a code, the code is locked for that scope for up to one hour (the hour boundary is described in `tests/unit/README.md`). Customers behind the same proxy who try that code within the hour can be locked out. This is a real availability cost, and it is accepted only for case C.
- **General budget (20 failed lookups per 10 minutes per scope)** can refuse other visitors' **failed** lookups behind the same proxy. It does not refuse successful ones.
- **Monitoring** (`pixva_suspicious_lookup`) counts across all clients for a code and never blocks.

Also record: the lockout limits volume against one address. It is **not** brute-force resistance. A distributed attacker is not stopped by it (see section 5).

---

## 2. Automated checks (unit and simulation, not WordPress runtime)

Run each command on the release build and record the exact counts.

| Suite | Command | Expected |
|---|---|---|
| PHP order and claims | `PHP=8.3 php-wasm-cli tests/unit/php/order-security.test.php` | `112 passed, 0 failed` |
| PHP mutation controls | `PHP_CLI=php-wasm-cli python3 tests/unit/php/mutation-controls.py` | `controls=15 survivors=0` |
| SQLite race (real engine, ported algorithm) | `python3 tests/unit/db/reservation_sqlite_race.py 300 24` | `violations=0`; orders and announcements `1` per round |
| Sanitizer, jsdom | `NODE_PATH=<node_modules> node tests/unit/js/safe-html.test.cjs` | `22 passed` |
| Sanitizer, fixtures, jsdom | `NODE_PATH=<node_modules> node tests/unit/js/safe-html-fixtures.test.cjs` | `20 passed` |
| Sanitizer, Chromium | `LD_LIBRARY_PATH=<libs> NODE_PATH=<node_modules> node tests/unit/js/safe-html.chromium.cjs` | `13 passed` |
| Chromium naive control | `NAIVE_CONTROL=1` with the same variables | `8 failed` (must fail) |

These are simulations or ports. They do not prove the WordPress runtime, and the SQLite run does not prove MySQL behaviour.

---

## 3. Runtime checks on WordPress (Staging)

### 3a. [GATE] One order per submission id

1. Load the booking form and note the hidden `_pixva_sid` value.
2. Send 10 identical submissions in parallel with that same `_pixva_sid` (script with `Promise.all`, or the form's AJAX endpoint with the same nonce and fields).
3. Expect exactly one new order (`wp post list --post_type=pixva_orders --format=count` before and after), and exactly one staff notification email.
4. Expect the other responses to be the same result or a pending (409) response.

### 3b. [GATE] Crash after insert, before the link: recovery returns the same order

This test uses a temporary mu-plugin on Staging only. Remove it afterwards.

1. Install `wp-content/mu-plugins/pixva-fatal-test.php`:

   ```php
   <?php
   add_action( 'pixva_order_before_link', function () {
       if ( ! empty( $_COOKIE['pixva_fatal'] ) ) {
           wp_die( 'simulated fatal after insert' );
       }
   } );
   ```

2. In a browser with cookie `pixva_fatal=1`, submit the booking form once. Expect an error page. Note the `_pixva_sid` value (form source or network log).
3. Check the database: one new order exists with `post_name` equal to that sid (`wp db query "SELECT ID, post_name FROM wp_posts WHERE post_type='pixva_orders' AND post_name LIKE 'SID%'"`). The reservation is `creating` (`wp option get pixva_ord_SID`).
4. Remove the cookie. Wait at least 301 seconds (the claim TTL), then resubmit with the same `_pixva_sid` (same form values).
5. Expect: success, the **same** order code as the order in step 3, still exactly one order with that sid, and the reservation now `linked` to it.
6. Expect exactly one staff notification email for this submission. The failed attempt in step 2 must not have sent one.
7. Remove `pixva-fatal-test.php`.

### 3c. [GATE] Crash before insert, and a failed insert

Repeat 3b with the hook on `pixva_order_before_insert` (crash before insert), and with a temporary failure of the insert (for example an invalid meta value in a staging-only filter). Expect one order after the retry and no notification for the failed attempt.

### 3d. [GATE] Status updates under concurrency

1. As a manager, open one order.
2. Send 10 status changes in parallel with different notes.
3. Expect no 500 errors. The history (`_pixva_order_steps`) holds one entry per accepted change. A 409 "busy" is expected only if a holder keeps the lock longer than 10 seconds, and then nothing is written for that request.
4. After the run, no `pixva_olock_<id>` option remains.

### 3e. [GATE] Notes and claim under concurrency

1. Ten parallel internal notes on one order: all ten appear in `_pixva_order_notes`, with none lost.
2. Two customer accounts claim the same unowned order (code and phone) at the same moment: exactly one account becomes the owner. The other gets the not-found message or 409.

### 3f. [GATE] Lookup limits

1. From client A, send 6 wrong phone numbers for one code. The 6th returns `locked`.
2. From client B, the correct code and phone succeed.
3. From client A (locked), the correct phone returns `locked`.
4. Failed lookups after the general budget is used up (20 in 10 minutes for the scope) return the rate error. Successful lookups from the same scope still succeed.
5. Monitoring: after 50 wrong attempts for one code from distinct clients, `pixva_suspicious_lookup` fired once (temporary logging snippet on Staging only).

### 3g. Browser and XSS

1. Tracking and warranty pages with a valid order: the order view appears, styled, in RTL, with the timeline and history.
2. Calculator: each service returns a result.
3. Error-code archive: filtering shows results.
4. In the browser console: `pixva.setSafeHTML(document.body, '<img src=x onerror=alert(1)>')` produces no dialog.
5. Run the same in each browser you support. Only Chromium has been tested in this release.

### 3h. Housekeeping

1. Count `wp_options` rows with prefixes `pixva_cf_`, `pixva_cg_`, `pixva_sub_`, `pixva_olock_`, `pixva_ord_`, `pixva_rl_`.
2. Run `wp cron event run pixva_prune_rows`.
3. Expect expired rows removed. Linked `pixva_ord_` rows younger than 30 days remain. Fresh rows do not change.
4. Check the WP-Cron schedule: `wp cron event list` shows `pixva_prune_rows` hourly. If the site does not run WP-Cron, install a system cron (`wp cron event run --due-now` every 5 minutes) or the prune never runs.

---

## 4. Real database (MySQL or MariaDB) [GATE]

The checks above are only meaningful on the production engine. Record the engine and version.

1. `SELECT ENGINE FROM information_schema.TABLES WHERE TABLE_NAME = 'wp_options'` returns `InnoDB`.
2. `SHOW INDEX FROM wp_options WHERE Column_name = 'option_name'` shows `Non_unique = 0`. This is the create-once guarantee.
3. `SHOW INDEX FROM wp_posts WHERE Column_name = 'post_name'` shows an index. The reservation lookup (`post_name LIKE 'sid%'`) depends on it. Run `EXPLAIN SELECT ID FROM wp_posts WHERE post_type = 'pixva_orders' AND post_name LIKE 'x%' ORDER BY ID` and confirm it uses that index.
4. Run 3a with at least 20 parallel requests. Expect one order.
5. Run 3b. Expect one order, and the reservation row linked to it.
6. Run 3d and 3e.
7. Confirm the atomicity assumption on the server: the compare-and-replace and create-once statements are single statements. Under InnoDB's default REPEATABLE READ with autocommit, each returns its affected-row count, and the UNIQUE index rejects the second insert. This is the expected behaviour, and it is NOT PROVEN on your server until steps 2 and 4 pass.

Record the result of each step, the engine, the server version, and the exact date. Do not reuse the SQLite result for this section.

---

## 5. Residual risks (state these in the release report)

- **Distributed brute force is not resisted.** Per-client counters limit one address. An attacker using many addresses or many IPv6 /64 ranges is not stopped. No brute-force resistance claim is made.
- **Hour boundary.** The failure window is fixed, so up to 10 failures can happen around the boundary (for example 5 just before and 5 just after). Documented, not fixed.
- **Stalled attempt longer than the claim TTL (300 s).** Such an attempt is fenced at the link step. An unlinked order it inserted is removed by the next attempt that links the reservation. Photos it uploaded remain in the private folder (pre-existing behaviour for failed submissions; clean up manually if needed).
- **Before the upgrade.** A submission whose claim was pending at the moment of upgrade has no reservation row, and its earlier order (if any) has no submission id. A retry of that exact submission can create a second order. Check for pending `pixva_sub_` rows before upgrading, and avoid upgrading during peak booking hours.
- **Contact messages** (`pixva_inbox`) use the claim, but not the order reservation. A crash after the message insert can still leave a duplicate message. This is not an order.
- **Submission id as a bearer for the order code.** A submission id, once used, returns the same order code for 30 days (it previously returned it for 1 day, and then created a new order). The id is sent only to the submitter's form. The code alone does not open order details: the phone number is also required.
- **Admin meta fields** (name, brand, notes saved from the admin screen) are last-writer-wins. Status and history are protected by the lock. Admin-only data is not.
- **Engine.** The MySQL/MariaDB behaviour is NOT TESTED in this release unless section 4 has been run.

---

## 6. Regression

Repeat the existing suites on the same build: REST contract, redirect and 404 matrix, layout, SEO, keyboard, contrast, and the earlier security suites. Do not carry results over from earlier builds.

---

## 7. Cleanup

- Delete test orders, test users, test photos under `pixva-private/`, and `pixva_*` option rows created by the tests.
- Remove `pixva-fatal-test.php` and any logging snippets.
- Record the cleanup in the report.

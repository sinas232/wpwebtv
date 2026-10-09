# PIXVA unit and simulation tests

These tests run outside WordPress. They load the real theme code (`pixva/inc/*.php`, `pixva/assets/js/app.js`) with small stubs. **They do not replace a WordPress runtime test on Staging.** Results from this folder say what the code does under the stated model. They say nothing about a real database, a real proxy, or a real browser installation beyond the one named below.

## Suites

| Suite | Command | What it proves | What it does not prove |
|---|---|---|---|
| `php/order-security.test.php` (66 checks) | `PHP=8.3 php-wasm-cli tests/unit/php/order-security.test.php` | Claim ownership, fencing, lease loss, owner-only release, status-lock logic, per-(code, client) attempt limits, IPv6 /64 scope, hour-boundary behaviour, monitoring that never blocks, pruning of expired rows | Real row locking and concurrency in MySQL or SQLite; object cache; WP-Cron; real IP headers |
| `php/render-fixtures.php` | `php-wasm-cli tests/unit/php/render-fixtures.php` | Writes `js/fixtures/render.json`: the real order, warranty and calculator fragments rendered with hostile values | WordPress's own `esc_*`/`wp_kses` (stubbed to equivalent behaviour) |
| `js/safe-html.test.cjs` (22) | `NODE_PATH=<node_modules> node tests/unit/js/safe-html.test.cjs` | Sanitizer on hand-written attack payloads, in jsdom | Browser rendering (jsdom is a DOM model) |
| `js/safe-html-fixtures.test.cjs` (20) | `NODE_PATH=<node_modules> node tests/unit/js/safe-html-fixtures.test.cjs` | Sanitizer on the real PHP fixtures, mutation-XSS, DOM clobbering, SVG, `setSafeFromNode` | Browser rendering (jsdom) |
| `js/safe-html.chromium.cjs` (13) | `LD_LIBRARY_PATH=<libs> NODE_PATH=<node_modules> node tests/unit/js/safe-html.chromium.cjs` | The same attack and fixture cases executed in headless Chromium 153 | Firefox, Safari, Edge, or mobile engines |

`php-wasm-cli` can return exit code 0 even after a PHP fatal error. Read the `N passed, M failed` line and check for `Fatal`.

Negative controls (the suites must fail on a broken implementation) are in the QA report. Each was run once against a temporary copy.

## Chromium prerequisites

The sandbox Chromium needs `libnspr4` and related libraries. On this machine they came from the `al2023.tar.br` bundle in `@sparticuz/chromium`, decompressed to `/tmp/al2023`. Set `CHROMIUM_PATH` to use another binary.

## Not covered by any suite here (NOT TESTED)

- Real concurrent requests against MySQL/MariaDB and against the SQLite drop-in. The UNIQUE and compare-and-replace assumptions are correct for MySQL InnoDB and should be confirmed on the target.
- Behaviour behind a reverse proxy or CDN (real `REMOTE_ADDR`, real forwarded addresses).
- WP-Cron execution of `pixva_prune_rows`.
- Clock skew between PHP workers.
- Distributed brute-force from many IPs (see the QA report for the residual risk).
- Real browsers other than Chromium.

# PIXVA unit, simulation and port tests

These tests run outside WordPress. They load the real theme code (`pixva/inc/*.php`, `pixva/assets/js/app.js`) with small stubs, or port its algorithm onto a real database engine. **They do not replace a WordPress runtime test on Staging** (see `docs/pixva-staging-checklist.md`). Each result below states which kind of evidence it is.

Evidence kinds:

- **Unit (stubbed):** the real PHP/JS runs against an in-memory WordPress stub. The stub enforces the same conditions as the database primitives (UNIQUE option names; UPDATE/DELETE only when the old value matches). Concurrency is not real here.
- **Port on a real engine:** the algorithm is ported to Python and runs its SQL against a real database engine (SQLite) with several OS processes racing. The PHP code is not executed.
- **Browser:** the real JS runs in headless Chromium.
- **Real WordPress / real MySQL:** NOT TESTED in this environment.

## Suites

| Suite | Evidence | Command | Result |
|---|---|---|---|
| `php/order-security.test.php` (233 checks, sections A–Q) | Unit (stubbed WordPress and wpdb; SIMULATED, not MySQL/MariaDB) | `php-wasm-cli tests/unit/php/order-security.test.php` | `233 passed, 0 failed` (Round 4 final run; see the Round 4 report for the run date and runner) |
| `php/mutation-controls.py` (30 controls: 15 order/IP/lock, 4 audit, 11 Round 4: F1 inbox, F2 notes, F3 role, F6 report, F7 migration) | Unit, negative controls | `PHP_CLI=php-wasm-cli python3 tests/unit/php/mutation-controls.py` | `controls=30 survivors=0` (see the final line of the run) |
| `php/render-fixtures.php` | Unit (writes `js/fixtures/render.json`) | `php-wasm-cli tests/unit/php/render-fixtures.php` | 3 fixtures; output identical to the committed file |
| `db/reservation_sqlite_race.py` | Port on SQLite 3.40 (NOT MySQL/MariaDB) | `python3 tests/unit/db/reservation_sqlite_race.py 300 24` | `violations=0` over 300 rounds of 24 racing requests with random crashes (crashes are counted in the run output; recovery created exactly one order and one announcement each time) |
| `php/audit` (section H of the PHP suite, read-only audit) | Unit (stubbed store, temp directory) | included in `order-security.test.php` | H1–H11 pass; H11 is a static check that `audit.php` has no write, delete or private-directory call |
| Static analysis: PHPCS 4.0.4 with WordPress Coding Standards (develop), PHPCSUtils, PHPCSExtra; ruleset `pixva/phpcs.xml` | Static, run under php-wasm | `php-wasm-cli <phpcs>/bin/phpcs -p --report=summary --standard=phpcs.xml .` in `pixva/` | `27 ERRORS and 82 WARNINGS in 17 files`: identical to the pre-change baseline. `pixva/inc/audit.php` adds none. The 27 errors are pre-existing and listed in `docs/pixva-write-path-review.md`. Not fixed in this round. |
| `js/safe-html.test.cjs` (22) | Unit (jsdom) | `NODE_PATH=<node_modules> node tests/unit/js/safe-html.test.cjs` | `22 passed` |
| `js/safe-html-fixtures.test.cjs` (20) | Unit (jsdom, real PHP fixtures) | `NODE_PATH=<node_modules> node tests/unit/js/safe-html-fixtures.test.cjs` | `20 passed` |
| `js/safe-html.chromium.cjs` (13) | Browser (Chromium 153, headless) | `LD_LIBRARY_PATH=<libs> NODE_PATH=<node_modules> node tests/unit/js/safe-html.chromium.cjs` | `13 passed` |
| `js/safe-html.chromium.cjs` with `NAIVE_CONTROL=1` | Browser, negative control | same, plus `NAIVE_CONTROL=1` | `5 passed, 8 failed` (the naive `innerHTML` control must fail) |

The `PHP=8.3` variable in the commands above is not a version selector. The runner used for the Round 3 results is `@php-wasm/cli` 3.1.57, which runs PHP 8.5.10 (check with `php-wasm-cli -r 'echo PHP_VERSION;'`). Record the version actually run.

`php-wasm-cli` can return exit code 0 even after a PHP fatal error. Read the `N passed, M failed` line and check for `Fatal`.

## Round 4 sections (simulated; not MySQL/MariaDB, not WordPress)

- **M. Contact inbox (F1):** Option A reservation `pixva_msg_<sid>` (UNIQUE option row, compare-and-replace). Covers same-SID concurrency (M7), crash after insert (M3), crash before/after the mail (M4–M6), retry, a live `creating` row returning `busy` (M12), a stalled attempt fenced after takeover (M13), and pruning of inbox reservations (M10). The email windows are simulated: a duplicate when the mail is sent before the marker and a crash follows (M5); no loss in the other order.
- **N. Internal notes (F2):** compare-and-write under the order lock with a keyed fingerprint of the value the form was loaded with. Covers a note added after load (N1), a concurrent two-save race (N3), a missing or wrong fingerprint (N4, N5), and a notice that shows no note text (N7).
- **O. Authorisation (F3):** the six-role matrix from the plugin's own role table, with `user_can()` and core `edit_post` simulated (`O` and `O-save`). Core meta-cap mapping in real WordPress is NOT TESTED.
- **P. Migration (F7):** the run lock (acquire, held, wrong-token release, stale takeover), per-order migration idempotence, and no reversion of status or history.
- **Q. Private-photo report (F6):** name, size, mtime and reason on the unreferenced-file finding; no absolute path in the output; the file still exists after the audit. No deletion code exists.

## What the PHP suite covers

- **Claims** (`pixva_claim_submission`, `pixva_finish_submission`, `pixva_release_submission`): exclusive creation, replay of the stored outcome, pending recovery after the TTL, lease loss when the owner runs past the TTL, error release, expired outcomes.
- **Order placement** (`pixva_place_order_once`, section H): one order per submission id; adoption of an order inserted by a crashed attempt; a crash before insert; a fatal inside the link step; a stalled attempt that is fenced at the link (both orderings: the other attempt inserted first, and the other attempt had not inserted yet); a failed insert; a suffixed slug (`sid-2`) left by an older duplicate; a link to a missing order; the claim and reservation together end to end; a malformed id refused before any write.
- **Status lock** and **order-level lock helper** (sections B and K): acquisition, release, expiry, takeover, fencing, release after an exception, lost-lock signal.
- **Client address** (section I): forwarded headers are read only from a declared trusted proxy; the chain is walked from the right; malformed chains fall back to `REMOTE_ADDR`; CIDR and IPv6 matching; a non-`HTTP_` header name is refused.
- **Lookup budget** (section J): correct lookups are never refused by the general budget; only failures count against it.
- **Lookup attempts** (section D): per (code, client scope) lockout, IPv6 /64 scope, success clears failures, hour boundary.
- **Monitoring** (section E): counts across clients, fires once, never blocks.
- **Pruning** (section G): expired rows only, including order reservations (30-day link TTL, stale attempts), unreadable rows kept, unrelated options untouched.

## Mutation controls

The mutation set runs as a background process: it takes several minutes.

`mutation-controls.py` applies one mutation at a time to a temporary copy and requires the suite to fail. Current controls (all caught):

| ID | Mutation | Caught by |
|---|---|---|
| C1 | claim create always succeeds | 12 checks |
| C2 | claim finish without the ownership check | 3 |
| C3 | IPv6 scoped by full address | 2 |
| L1 | status write without the fencing check | 1 |
| O1 | no adoption of an order a crashed attempt inserted | 5 |
| O2 | link is read-then-write | 3 |
| O3 | order announced at insert, before the link | 12 |
| O4 | order not named by the submission id | 15 |
| O5 | no removal of unlinked duplicates on the link path | 1 |
| O6 | linked reservation does not short-circuit (re-announces) | 4 |
| O7 | fence ignored: stalled attempt announces | 5 |
| O8 | linked reservations pruned after the claim TTL | 2 |
| B1 | general budget refuses correct lookups | 1 |
| I1 | forwarded header trusted from any peer | 3 |
| I2 | chain walked from the left | 2 |

The Round 4 controls (F1a–F1e inbox, F2a notes, F3a role path, F6a report, F7a–F7c migration) are in `mutation-controls.py` with the same rule: each must make the suite fail.

`reservation_sqlite_race.py` has its own negative controls, run over 300 rounds each: `RACE_MUTANT=no_adopt` (recovery inserts again instead of adopting) gives violations in 104 rounds; `RACE_MUTANT=no_cas` (read-then-write replacement) gives violations in 146 rounds, most with two or more announcements.

## Why the SQLite port is not MySQL evidence

The port uses the same SQL primitives as the PHP code: `INSERT` into a UNIQUE `option_name`, and `UPDATE ... WHERE option_name = ? AND option_value = ?` for compare-and-replace. SQLite serialises writers, which makes the test a valid check of the algorithm's logic under real interleavings. It is not a check of InnoDB's locking, the REPEATABLE READ snapshot, or deadlock behaviour. Those need the MySQL/MariaDB steps in section 4 of the checklist.

## Hour boundary (documented behaviour)

Lookup failure counters use fixed clock hours. Five failures just before an hour boundary and five just after it are both accepted, so ten failures can happen within about 11 seconds across the boundary. Section D of the PHP suite checks this exactly, so the limit is documented rather than hidden.

## Chromium prerequisites

The sandbox Chromium needs `libnspr4` and related libraries. They came from the `al2023.tar.br` bundle in `@sparticuz/chromium`, decompressed to `/tmp/al2023` (outside the repository). Set `CHROMIUM_PATH` to use another binary.

## NOT TESTED in this environment

- MySQL/MariaDB (no server binary is reachable from the sandbox: the registries allowed here only provide client libraries). The UNIQUE and compare-and-replace behaviour is therefore unproven on the production engine.
- WordPress runtime: the forms handlers in `pixva/inc/forms.php` (booking, customer claim, staff notes) run only in WordPress. They are covered by code review and by the checklist, not by a unit test. The save-box initialisation in `repairs.php` is the same.
- Behaviour behind a real reverse proxy or CDN (real `REMOTE_ADDR`, real forwarded addresses).
- WP-Cron execution of `pixva_prune_rows`.
- Clock skew between PHP workers.
- Distributed brute force from many addresses (see the checklist, section 5).
- Private folder deny rules on Nginx and Apache (checklist 3i). The theme writes only the Apache `.htaccess`; the real server response is NOT TESTED.
- Upgrade on a real site: `pixva_run_migrations` and `wp pixva audit` on a real database (checklist 3j). NOT TESTED.
- Inbox duplicates after a crash (finding F1): the behaviour is reasoned, not run; see `docs/pixva-write-path-review.md`.
- Browsers other than Chromium.

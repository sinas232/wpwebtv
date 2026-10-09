# PIXVA Round 4 report (F1, F2, F3, F5, F6, F7, item 6, packaging)

**Operational release: NO-GO.** The duplicate-order fix (order path, see O-4 below), the proxy/IP decision, and the Staging tests are still open. Nothing in this report is a claim of "no vulnerability", "fully secure" or "release ready".

Evidence labels used below:
- **SIMULATED**: the real PHP/JS code runs against a stubbed WordPress and `wpdb` (in-memory, enforcing UNIQUE and compare-and-set). Concurrency is simulated by ordering the steps inside one process.
- **SQLite port**: the algorithm is ported to Python and runs its SQL on SQLite 3.40.1 with 24 OS processes. This is **not** MySQL or MariaDB.
- **Browser**: real JS in headless Chromium.
- **NOT TESTED**: not run in this environment. Real MySQL/MariaDB, a WordPress runtime, Staging, and real proxy headers are all NOT TESTED.

## 1. Pre-state and local changes (item 6)

- Branch `arena/689d3d79-wpwebtv`. HEAD at the start of this round `1255daa` (equal to `origin/arena/689d3d79-wpwebtv`). Old HEAD recorded in `/home/user/round4-prestate-head.txt` as `df81f49`.
- `git stash list` is **empty** in this environment. `stash@{0}` and the earlier local tarball from previous rounds are **not present** here. This round did not create, drop or rewrite any stash.
- Pre-change backups made this round (outside the repository): `/home/user/round4-prestate-worktree-1791571179.tar.gz` (SHA-256 `e28ea61d…c6d2`, excludes `.git`) and `/home/user/round4-prestate-local-dist-pixva-410e1c.zip` (SHA-256 `410e1c18…330c1`, an earlier unverified local ZIP, not shipped).
- `frontend/` (classification: **UNCLEAR, owner decision**). It is a separate Next.js headless app (`tvdoctor-frontend` 3.0.0), committed by the owner in `547d0a2` and `d33e35f` (2026-10-08). It is not in the ZIP (`export-ignore` / `pixva/` only). It is not PIXVA WordPress code and was **not** imported into the WordPress branch.
- Redesigned CSS (`pixva/assets/css/app.css`, 1560 lines at `df81f49`, 9140 at HEAD; commits `156643e`…`597b25e`). Classification: **PIXVA product, shipped in the ZIP**. It has no visual or Chromium tests for these pages, so its visual correctness is **NOT TESTED**.

## 2. Baseline and final results

Baseline = clean export of `1255daa` (`/tmp/r4-base`), run this round. Final = this commit, run after all code changes.

| Check | Baseline `1255daa` | Final (this commit) | Evidence |
|---|---|---|---|
| `order-security.test.php` | 135 passed, 0 failed | **233 passed, 0 failed** | SIMULATED |
| `mutation-controls.py` | 19 controls, 0 survivors | **30 controls, 0 survivors** (11 new: F1a–F1e, F2a, F3a, F6a, F7a–F7c) | SIMULATED negative controls |
| `reservation_sqlite_race.py 300 24` | 0 violations | **0 violations** (300 rounds, 24 processes, SQLite 3.40.1) | SQLite port, NOT MySQL |
| `safe-html.test.cjs` (jsdom) | 22 passed | **22 passed** | Unit (jsdom) |
| `safe-html-fixtures.test.cjs` (jsdom) | 20 passed | **20 passed** | Unit (jsdom, real PHP fixtures) |
| `safe-html.chromium.cjs` | 13 passed | **13 passed** | Browser (Chromium) |
| `safe-html.chromium.cjs` `NAIVE_CONTROL=1` | 5 pass / 8 fail | **5 pass / 8 fail** (expected: the naive control must fail) | Browser, negative control |
| `render-fixtures.php` | identical to committed | **identical** (git reports no change) | Unit |
| PHPCS 4.0.4 + WPCS (`pixva/phpcs.xml`) | 27 errors, 82 warnings | **27 errors, 82 warnings** (no new issue; `inbox.php` has none) | Static |
| `php -l` on all `pixva/inc/*.php`, `functions.php`, test file | clean | **clean** | Static |

A failure or SKIP is counted as NOT PASS. The earlier run of section M had 4 failures (M5 ×3, M10). Those were fixed: M5 was a test bug (an arrow function captured the mail list by value), and M10 was a real gap (the prune rule for `pixva_msg_` was missing from `pixva/inc/security.php`). Also, F7a initially survived because the mutant crashed with a fatal instead of a FAIL. The test now stubs the install recorders and checks that no install step runs while the lock is held (P6a).

## 3. Item status

### F1: Inbox duplicate (Option A, `_pixva_sid`). Status: **PARTIALLY MITIGATED**
- Implemented in `pixva/inc/inbox.php` (new) and wired into `pixva/inc/forms.php` (contact handler). The public form contract is unchanged: same fields, same `_pixva_sid`, same response shape. Design: `docs/pixva-write-path-review.md` §3.
- Reservation `pixva_msg_<sid>` is an option row. `add_option()` (UNIQUE `option_name`) creates it. Replacement is `$wpdb->update` with the old value in the WHERE clause (compare-and-set). The mail marker is the field `m` in the same row.
- A live `creating` row returns `busy` (HTTP 409, an existing code) and is not taken over. An expired row is taken over, and the existing post is adopted by SID. A stalled attempt fences itself before inserting (M13).
- Tests (SIMULATED): same-SID interleaving (M7), crash after insert (M3), crash before/after mail (M4–M6), retry without duplication (M2, M4), live-row busy (M12), stalled-attempt fence (M13), prune (M10).
- **Email windows** (never claimed as exactly-once):
  - Mail sent, then crash before the marker: the retry sends a **second** email. The message is not lost. Simulated in M5.
  - Marker written before the mail, then crash: the email is **lost**. The code writes the marker after the mail. Control F1d checks this order.
- **Residual R-F1-1**: a stalled attempt whose row expired can, in the window between the fence check and the insert, insert a second post. This is a duplicate, not a loss, and it is not atomic. It needs a real MySQL/MariaDB test (**NOT TESTED**).
- **Residual R-F1-2**: no real database was used. UNIQUE and compare-and-set were exercised only on the stub, which models their semantics. SQLite and the stub are not MySQL/MariaDB proof.

### F2: Internal-notes overwrite. Status: **PARTIALLY MITIGATED**
- Both paths now use compare-and-write under the order lock: the meta-box save (`pixva_save_order_notes_cas()`), and the public note append (`pixva_handle_order_update`, unchanged, already locked).
- The meta-box form carries `pixva_o[notes_base]`: a keyed `wp_hash` of the value the form was loaded with. It does not carry the note text. Save happens only if the current value still matches. Otherwise nothing is written, and the user sees a notice (no note text in the notice).
- A form without a fingerprint (a page loaded before this version) is refused and asks the user to reload. This is deliberate: a silent overwrite is not allowed.
- Tests (SIMULATED): N1 (a note added after load survives), N2, N3 (two writers from the same base: the second is refused), N4/N5 (missing or wrong fingerprint), N6, N7 (notice, no note text), N8/N9 (no capability or bad nonce changes nothing).
- **Residuals**: the compare and the write are two statements under the advisory lock, not one DB transaction. A stale lock owner (after the TTL) can still write (see F5). The `busy` branch of the lock is **NOT TESTED** (the stub waits for the real deadline). A refused save loses the typed text; the user must re-enter it.

### F3: Meta-box authorisation (six roles). Status: **DOCUMENTED / OPEN**
- Checked against `pixva/inc/capabilities.php`. Only `administrator` and `pixva_manager` hold `edit_others_pixva_orders` and `pixva_view_order_pii` and `pixva_manage_orders`. `pixva_technician` and `pixva_customer` do not. A responsible technician gets `pixva_work_order` only through `_pixva_technician_id` (`pixva_map_meta_cap`). A customer gets `pixva_view_order` only as the owner.
- Matrix tested (SIMULATED, section O): admin, manager, `pixva_work_orders`-only user, responsible technician, non-responsible technician, customer owner and customer other. The save path tests show that unauthorised roles change nothing (O-save, N8).
- **No vulnerability was demonstrated in the model. No capability was changed.** The core `edit_post` → `edit_others_*` mapping is simulated from the plugin's role table; real WordPress mapping is **NOT TESTED**.
- Defence-in-depth gap (documented, not changed): brand, model, estimate, notes, technician and warranty fields are gated only by the single `edit_post` entry check, while PII fields add `pixva_view_order_pii`. If a future role gets `edit_post` without that PII capability, those fields become writable.

### F4: Unlocked technician and warranty writes (round 2 residual). Status: **DOCUMENTED / OPEN**
- Unchanged in Round 4. The writes in the meta-box save are not under the order lock. They are limited by the `edit_post` gate (F3).

### F5: Fencing versus meta writes. Status: **DOCUMENTED / OPEN** (assessment only)
- `pixva_set_order_status_locked()` checks the fence (`pixva_order_lock_held`) and then writes `_pixva_order_status` and `_pixva_order_steps` as two separate `update_post_meta()` calls. The history list was read before the fence. A stale owner that passes the check can still write a history missing a newer entry. The window is a few milliseconds, but it is not closed.
- WordPress API: `update_post_meta($id, $key, $new, $prev)` issues a conditional UPDATE (`... AND meta_value = prev`). It would narrow the window, but the return value is ambiguous when the new value equals the old one, and it does not cover status and history together. **NOT TESTED** on a real database.
- Options (not implemented; a structural or transactional change needs a decision first):
  1. Compare-and-set on status and history with `$prev_value`, verified on MySQL. Smallest change; it narrows but does not close the window.
  2. Store status and history in one meta row and update it with one conditional UPDATE. Closes the status/history split; a data-format change.
  3. A DB transaction with `SELECT ... FOR UPDATE` on the order's postmeta rows. Closes it on InnoDB; it needs a real MySQL/MariaDB test.

### F6: Private photos (no cleanup). Status: **PARTIALLY MITIGATED**
- The read-only audit report (`pixva/inc/audit.php`) now includes, for each unreferenced file: relative name, size in bytes, modified time (UTC, ISO 8601), `reason` (`no_order_reference`) and `action: none`. Missing and shared references carry a `reason` too.
- No absolute path appears in the output. The collector reads `filesize`/`filemtime` only on readable files, and writes nothing.
- Automatic cleanup: **not implemented, and not planned for this round**. Any future cleanup needs a dry run, a reviewable report, human confirmation, and tests.
- Tests (SIMULATED, temp directory): Q1–Q4. Control F6a. Real filesystem metadata was read from a temp directory and the file still existed afterwards. Real WordPress uploads path resolution: **NOT TESTED**.

### F7: Migration under two simultaneous requests. Status: **PARTIALLY MITIGATED**
- Whole-run lock: `pixva_run_migrations()` takes `pixva_migration_lock` (created by `add_option()`, which is UNIQUE on `option_name`). A second request returns at once. A lock older than `PIXVA_MIGRATION_LOCK_TTL` (600 s) is taken over by a conditional delete. Release deletes only the row whose value matches this run's token.
- Per-order step: the v1 history conversion and the status fallback now run inside `pixva_with_order_lock()` (`pixva_migrate_order_v2()`), re-reading the row under the lock. This stops a legacy write from erasing a status change made during the run.
- Tests (SIMULATED): P1–P6 (lock acquire, held, wrong-token release, stale takeover, no install while held), P7–P9 (idempotent conversion, no status or history reversion, invalid status still set to `new`), P10 static checks.
- **NOT TESTED**: real MySQL concurrency; a lock-busy order is skipped in that run (it keeps its legacy format, which the read path accepts).
- Migration creates no duplicate posts: `pixva_migrate_v1` only updates existing posts (`wp_update_post`).

### O-4: Order placement takeover of a live `creating` row (found in Round 4). Status: **DOCUMENTED / OPEN**
- `pixva_place_order_once()` (`pixva/inc/repairs.php`, around line 554) takes over a `creating` reservation without checking its age (the comment says "expired", the code does not check). The inbox path was fixed in Round 4; the order path was **not** changed (out of Round 4 scope, and it changes order behaviour). From reading the code, a duplicate order is possible when a second attempt takes over a live reservation. This is **not demonstrated by a test** in this round. It is listed here because it blocks the operational release.
- Proposed fix (for the owner to approve): apply the same live-row check as `inbox.php`, with a test in section H and a control. Then run the SQLite race and a MySQL test.

### Operational items
- Proxy and IP decision: unchanged in this round. Forwarded headers are read only from declared trusted proxies (section I). No proxy header was trusted from the client. The real reverse-proxy configuration is **NOT TESTED** and has to be decided in the real environment.
- Private-folder deny rules (Nginx and Apache): a **mandatory operational requirement**. Code emits only Apache `.htaccess` rules (`security.php` around line 779–800). Nginx rules must be set by the server admin. **NOT TESTED** here.
- `PIXVA_VERSION` stays `2.2.0`. No version bump was requested in Round 4, and a bump is a release decision for the owner.

## 4. Packaging

- Source commit: see `git log` for the commit that contains this report (the commit after `1255daa`). The ZIP was built from that commit with `git archive`, which honours `export-ignore` (`pixva/phpcs.xml` is excluded).
- ZIP path: `dist/pixva.zip`. Its SHA-256 and the source commit it was built from are recorded in the commit message of the ZIP commit, and in section 5 below.
- The ZIP contains only `pixva/`, and it includes `pixva/inc/inbox.php`.

## 5. Final status

- Operational release: **NO-GO**.
- Real MySQL/MariaDB: **NOT TESTED**. WordPress runtime: **NOT TESTED**. Staging: **not accessible, NOT TESTED**.
- The results in section 2 are SIMULATED or SQLite-port evidence and are labelled as such. None of them is a WordPress runtime result.

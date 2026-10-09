# PIXVA Round 5 report (F1-order first; F1 inbox, F5, F2, F3, F4, F6, F7, packaging)

**Operational release: NO-GO.** The duplicate-order fix is only partly done (residual window, F1-order below). The IP/proxy decision (O-4) is still open. Staging has not been run and is not accessible. Nothing in this report is a claim of "no vulnerability", "fully secure" or "final release ready".

## Evidence labels

- **SIMULATED**: the real PHP runs against a stubbed WordPress and `wpdb` (in memory, enforcing UNIQUE and compare-and-set). Concurrency is simulated by ordering steps inside one process. This is **not** MySQL, MariaDB or WordPress.
- **SQLite port**: the placement algorithm is ported to Python and runs its SQL on SQLite 3.40.1 with 24 OS processes and random crashes. The PHP is **not** executed. This is **not** MySQL or MariaDB.
- **Static**: a check of the source text. It is not a runtime test.
- **Browser**: real JS in headless Chromium 153 (`@sparticuz/chromium`).
- **NOT TESTED**: not run here. Real MySQL/MariaDB, a WordPress runtime, Staging, real proxy headers, and the Nginx/Apache deny rules on a real server.

## 1. Pre-state (item 1)

- Branch `arena/689d3d79-wpwebtv`. At the start of Round 5, HEAD was `34ebd2ca01d686268668427e5ce1a5240dff1bfc` and equal to `origin/arena/689d3d79-wpwebtv`. The HEAD and ZIP SHA-256 are recorded in `/home/user/round5-prestate-head.txt`; the status is in `/home/user/round5-prestate-status.txt`. The working tree was **clean** at the start.
- `git stash list` was **empty** (count 0). This round did not create, drop, apply or rewrite any stash or local change.
- ZIP before the round: `dist/pixva.zip` at `65cc20e`/`34ebd2c`, SHA-256 `66cc7a799dce9839358380132a648d7b4a17e36b273e5d78745ab321d27c9933`, 80 files and 9 directories.
- Real database binaries checked: `mysqld`, `mariadbd`, `mysql`, `mariadb`, `docker` and `podman` are all **absent**. Real MySQL/MariaDB runs are **NOT TESTED**.

Commits made this round: `abe6207` (code, tests, README), then a commit with this report, the rebuilt ZIP and the write-path review update. The Round 4 ZIP is still in git history at `65cc20e`.

## 2. Results (item 10)

Baseline = `34ebd2c` (clean `git archive` extracted to `/tmp`, run this round). Final = `abe6207` (code), with the ZIP built from it.

| Check | Baseline `34ebd2c` | Final `abe6207` | Evidence |
|---|---|---|---|
| `order-security.test.php` | 233 passed, 0 failed | **253 passed, 0 failed** | SIMULATED |
| Same suite against code extracted from the final ZIP | not run | **253 passed, 0 failed** | SIMULATED |
| `mutation-controls.py` | 30 controls, 0 survivors (Round 4) | **39 controls, 39 CAUGHT, 0 survivors** (run twice on the final code) | SIMULATED negative controls |
| `reservation_sqlite_race.py 300 24` | Round 4 port: 0 violations | **0 violations** (port updated for Round 5; one announcement per round) | SQLite port, NOT MySQL |
| race `RACE_MUTANT=no_adopt` | 104 rounds (Round 4 port) | **110 rounds** with violations | SQLite port |
| race `RACE_MUTANT=no_cas` | 146 rounds (Round 4 port) | **140 rounds** with violations | SQLite port |
| race `RACE_MUTANT=live_takeover` (new) | not present | **162 rounds** with violations | SQLite port |
| `safe-html.test.cjs` (jsdom) | 22 passed | **22 passed, 0 failed** | Unit (jsdom) |
| `safe-html-fixtures.test.cjs` (jsdom) | 20 passed | **20 passed, 0 failed** | Unit (jsdom, real PHP fixtures) |
| `safe-html.chromium.cjs` | 13 passed | **13 passed, 0 failed** | Browser (Chromium) |
| `safe-html.chromium.cjs` with `NAIVE_CONTROL=1` | 5 passed, 8 failed | **5 passed, 8 failed** (the naive control must fail; exit 1 is expected) | Browser, negative control |
| `render-fixtures.php` | identical to committed | **identical** (`git status` clean for fixtures) | Unit |
| PHPCS 4.0.4 (WPCS develop), `pixva/phpcs.xml` | 27 errors, 82 warnings | **27 errors, 82 warnings; same message set, no new messages** | Static |
| `php -l` on `pixva/functions.php`, `pixva/inc/*.php`, PHP test files | clean | **clean (28 files)** | Static |
| Python `py_compile` on both Python harnesses | not applicable | clean | Static |

Notes on the PHPCS result: the 27 errors and 82 warnings are the pre-existing Round 4 set. Comparing the JSON reports, the message set is identical to `34ebd2c`.

Round 5 changes the PHP suite count from 233 to 253 (new H14–H16, M14, N10, N11, P11, plus the existing H-section expectations rewritten for the new behaviour). The mutation set grew from 30 to 39 (R1–R9).

## 3. F1-order: duplicate-order prevention (item 2) — PARTIALLY MITIGATED

### What changed (`pixva/inc/repairs.php`, `pixva_place_order_once` and helpers)

- **No takeover of a live reservation.** A `creating` row written less than `PIXVA_CLAIM_PENDING_TTL` ago returns `WP_Error('busy', …, 409)` and is never replaced. Only an expired `creating` row, or a `linked` row whose order was deleted, is replaced (compare on the exact old value).
- **Fence before the adopt write and before the insert.** After the lookup, and again after `pixva_order_before_insert`, the code checks `pixva_option_value($name) === $mine` and stops (`continue`) if the reservation is no longer ours. A `pixva_order_before_fence` seam was added so the adopt-path fence can be tested.
- **Duplicates are never deleted.** `pixva_remove_duplicate_orders()` no longer calls `wp_delete_post`. It sets `_pixva_superseded_by` on other orders with the same submission id, once each. The audit (read-only, `audit.php`, tests A1–A4 and H-section audit checks) reports `submission_duplicate_orders` and `order_not_linked`.
- **Failed insert releases its own reservation.** If `pixva_insert_order()` returns `WP_Error`, no post exists, so the attempt deletes its own reservation (compare on its token). Without this, a retry would be refused as busy for the whole TTL. The inbox path (`inbox.php`) got the same release (test M14).
- **Form contract unchanged.** The form handler still maps any placement error to its existing 500 `save` response. A `busy` result is therefore shown to the customer as "ثبت درخواست ممکن نشد. لطفاً دوباره تلاش کنید." This is a UX point, not a behaviour change. Changing it would change the form contract, so it is not done.

### Tests

| Case | Test | Evidence |
|---|---|---|
| Stall (live `creating`, another request arrives) | H14: busy (409); no insert; reservation keeps its token | SIMULATED |
| Expiry (claim expired, reservation still live) | H15: busy inside the reservation TTL; succeeds only after it | SIMULATED |
| Takeover of an expired attempt, A stalls after linking | H6: B takes over during A's pause; A returns B's order; one order | SIMULATED |
| Stale continuation after takeover (before insert) | H7: A inserts nothing; one order; announced once by B; nothing deleted | SIMULATED |
| Stale continuation on the adopt path | H16: A loses its reservation after adopting; B's data is kept (name unchanged); no overwrite | SIMULATED |
| Duplicate handling | H10b: duplicate kept and marked `_pixva_superseded_by`; `deleted_log` empty | SIMULATED |
| Retry after a failed insert | H8, M14: retry succeeds at once | SIMULATED |
| Crash after insert, before link; recovery | H3–H5 (Round 4, rerun) | SIMULATED |
| Same-SID concurrency | race port: 300 rounds × 24 processes, 0 violations; `live_takeover` mutant: 162 violating rounds; `no_adopt`: 110; `no_cas`: 140 | SQLite port, NOT MySQL |

The race port was updated to the Round 5 rules (busy on a live reservation, fence before insert, superseded not deleted). A new phase 3 was added so the takeover is raced separately from the live-reservation refusal. A phase-2 violation is recorded only when a live reservation exists when phase 2 starts. Without that restriction the check would fire on correct behaviour, because a claimant that crashed before writing the reservation leaves nothing to be live.

Mutation controls for this item: R1 (busy guard removed), R2 (fence before insert removed), R3 (fence before the adopt write removed), R4 (superseded replaced by delete), R5 (no release after a failed insert), R6 (the same for the inbox). All CAUGHT.

### Residual window (not removable with current WordPress APIs)

- **Where:** between the fence read (`pixva_option_value($name) === $mine`) and the write (`pixva_insert_order` or `pixva_write_order_data`). These are separate statements. There is no transaction and no conditional insert.
- **Who can reach it:** an attempt that stalls for longer than `PIXVA_CLAIM_PENDING_TTL` (the reservation TTL) and is taken over exactly between its fence and its write.
- **Effects:** (a) if it adopted an order, it writes the same submission data to that order; (b) if it inserts an order after the winner has marked its duplicates, that post stays unlinked and unmarked. It is kept, never deleted, and the audit lists it as a duplicate submission id. Staff resolve it by hand. No outside order is linked, and no customer tracking code is taken over.
- **Length:** the time between the fence read and the write, typically a few milliseconds. It is not measured on MySQL.
- **Not closed by:** the option-based reservation, `wp_insert_post`, post meta or `WP_Query`. None of these gives a conditional insert that depends on the reservation row.

### Alternatives (not implemented; architecture change)

1. **Custom table with a UNIQUE key on the submission id**, holding the order id, written with `INSERT … ON DUPLICATE KEY` (MySQL) or the equivalent in one statement before `wp_insert_post`. This closes the duplicate race, because only one request can own the key. Consequences: a schema change (`dbDelta`), a migration, a new table to prune and back up, and a read path change for the audit. It needs a real MySQL run before any claim.
2. **InnoDB row lock with `SELECT … FOR UPDATE` inside `START TRANSACTION`/`COMMIT` through `$wpdb`.** Consequences: WordPress has no supported transaction API; the engine must be InnoDB (MyISAM hosts break the guarantee); the object cache and other plugins are not covered; there is a deadlock risk with `update_post_meta` on the same connection. Not verified here.
3. **Keep the current design** and accept the window, with the audit as detection. This is the state of Round 5.

**Decision:** option 3 for Round 5. Options 1 and 2 need a real MySQL/MariaDB run and an owner decision. No architecture change was made without proof.

## 4. F1 inbox (item 3) — PARTIALLY MITIGATED

Round 4 implementation kept (Option A, `pixva_msg_<sid>` with `_pixva_sid`). Review against the requested cases:

| Case | Result | Evidence |
|---|---|---|
| Crash after message creation, before linking | Recovered by adoption; no second message | M3 |
| Same-SID concurrent sends | One reservation; a live `creating` row gives busy | M7 |
| Crash between mail send and marker | Mail may be sent again on retry. **Duplicate email is possible.** | M5 (documented) |
| Retry after claim expiry | Recovered; takeover only after the TTL | M3, M4 |
| Failed insert then immediate retry | Reservation released; retry places the message | M14 (new) |
| Pruning of `pixva_msg_` rows | Pruned only after the message TTL; a linked row cannot be pruned on the order TTL | M10, F1e mutant CAUGHT |

**Exactly-once email is not claimed.** Mail sending is not transactional. The marker is written after the mail, so a crash in between can send twice. Writing the marker before the mail would lose mail on a crash.

**Outbox (not implemented; design and consequences).** A real fix is an outbox: write a `pending` message row in the same reservation step, have a worker claim it with a compare-and-set, send, and mark `sent` with an idempotency key. Consequences: a new table or option namespace, a cron or worker dependency, and a retry policy. The result is at-least-once delivery, and effectively-once only if the mail provider deduplicates on the key. This changes the email path, so it needs owner approval before any implementation.

## 5. F5 state and history integrity (item 4) — DOCUMENTED / OPEN

### Every read and write path

| # | Path | Reads | Writes | Lock | Notes |
|---|---|---|---|---|---|
| R/W-1 | `pixva_set_order_status()` → `pixva_set_order_status_locked()` (`repairs.php` ~656–815) | status, steps (`pixva_order_history`) | `_pixva_order_status`, `_pixva_order_steps`, warranty on `delivered` | Order lock (`pixva_olock_<id>`), fenced before writes | Status and steps are two statements. |
| R/W-2 | Placement creates the order (`pixva_write_order_data`, `repairs.php` ~402–430) | none | status `new`, steps list | Under the reservation (F1) | New order only. |
| R/W-3 | Meta-box creates the code (`pixva_save_order_box`, ~1181–1210) | code | code, post title, steps `[new]`, status `new` | Order lock | Initialises history once. |
| R/W-4 | Meta-box status change (`pixva_save_order_box`, ~1288) | — | via R/W-1 | Order lock (inside R/W-1) | Refusal now recorded (R7). |
| R/W-5 | Order-form status update (`pixva_handle_order_update`, `forms.php` ~666–700) | — | via R/W-1, then internal note append | Order lock | Technician and manager path. |
| R/W-6 | Migration step 7 (`migration.php` ~436–443, `pixva_migrate_order_v2`) | steps, status | steps (list conversion), status only if invalid (→ `new`), warranty source, phone hash, customer id | Order lock (static check R9) | Repeatable (P7b, P11). |
| R-7 | Timeline and admin display (`pixva_order_history`, ~626; `repairs.php` ~857–860, ~1065) | steps | none | none | Read only. |
| R-8 | Dashboard queries (`dashboard.php` 58, 83–111) | `_pixva_order_status` via `meta_query` | none | none | Filters on status. Changing the storage would change this contract. |
| R-9 | Audit (`audit.php` 61) | status | none | none | Read only. |
| R-10 | Technician check (`capabilities.php` 147) | technician id | none | none | Read only. |

### Options compared

| Option | Compatibility with WordPress | Limits | Migration risk | Test type | Verdict |
|---|---|---|---|---|---|
| **A. Compare-and-swap on the previous meta value** (`UPDATE wp_postmeta … WHERE meta_value = old`) | Compatible in principle (direct SQL, cache invalidation needed). Not a core API. | Status and steps are two rows, so CAS on one does not protect the other. Does not stop writers that do not use CAS. | Low (no schema change). | SIMULATED plus SQLite port. Not MySQL. | Not adopted. Needs proof on MySQL. |
| **B. One combined structure** (status + history in one meta row, CAS on that value) | Compatible in principle | Dashboard `meta_query` filters on `_pixva_order_status`; a combined row would need mirroring or a query change, which changes the dashboard contract. | High. | SIMULATED plus port. | Not adopted: architecture change without proof. |
| **C. DB transaction or row lock** (`SELECT … FOR UPDATE`) | Not a WordPress API. InnoDB only. | MyISAM hosts; object cache; deadlock risk; not verified. | Medium (engine dependent). | NOT TESTED (needs MySQL). | Not adopted. |

**Current state (kept):** the per-order lock (`pixva_olock_<id>`, compare-and-delete, 30 s stale threshold) and the fence before writes. Status and steps are read and written under the lock, so no history entry is lost while the lock is held.

### What is not guaranteed (why F5 stays OPEN)

- **Status and steps are two statements.** A crash between them leaves a status without its history entry, or the reverse.
- **Stale-lock holder.** After a takeover at 30 s, the old holder can still pass its fence and write before the takeover is visible. This is the same residual window as F1-order.
- **No transition table.** `pixva_set_order_status_locked` accepts any known status from any known status. The only refusal is the closed-state guard (`delivered`, `cancelled`) for non-managers. An "invalid transition" is therefore accepted. A transition table is a product rule, not a code fix, so it is not added here.
- **Migration silent status change (P9).** An invalid status is reset to `new` without a history entry. No history is lost, but the status changes without a record. Adding an entry would change migration output; it is left open for the owner.

## 6. F2 internal notes (item 5) — PARTIALLY MITIGATED

Paths reviewed, all with the same lock pattern:

| Path | Pattern | Silent loss? | Tests |
|---|---|---|---|
| Meta-box notes save (`pixva_save_order_notes_cas`) | Fingerprint compare and write under the order lock | No. A conflict records a notice; the text is not shown in the notice | N1–N7 |
| Order-form internal note (`forms.php` ~683) | Read, append, write under the order lock | No. A busy lock returns an error after the status is saved; the message says so | Code path, Round 4 tests |
| Public note on status change (`pixva_handle_order_update` via R/W-1) | Under the order lock | No | K-section, Round 4 |
| Technician status change path (R/W-5) | Under the order lock | No | — |
| Meta-box status refused by a lock (R/W-4) | **Round 5 fix:** a notice is recorded (`status` reason) instead of a silent drop | No longer silent | N10, R7 (CAUGHT) |
| Meta-box technician and warranty saves (R8) | **Round 5 fix:** wrapped in the order lock; a refused save writes nothing and records a `busy` notice | No longer silent | N11, R8 (CAUGHT) |

Residual: the stale-lock window described in F5 applies to notes as well.

## 7. F3 authorisation (item 6) — DOCUMENTED / OPEN

Role matrix from `pixva_role_caps()` (`capabilities.php` ~75). No capability was changed this round.

| Role | Order primitives (`edit_pixva_orders` etc.) | Other | Can reach the meta-box save (`edit_post`) |
|---|---|---|---|
| administrator | all | messages, settings, redirects, content health | yes |
| pixva_manager | all | messages, settings, `read` | yes |
| pixva_technician | none | `read`, dashboard, `pixva_work_orders` | **no** (no `edit_pixva_orders`) |
| pixva_customer | none | `read` | no |
| editor | none | dashboard, redirects, content health | **no** |
| guest | none | — | no |

`pixva_map_meta_cap` maps `pixva_work_order` to `pixva_work_orders` for the assigned technician and to `pixva_manage_orders` for managers, and maps `pixva_view_order` to `read` for the owning customer.

Evidence: section O of the PHP suite (SIMULATED: `user_can()` and the core `edit_post` mapping are simulated from the plugin's own role table). **Real WordPress core meta-cap mapping is NOT TESTED.** Status: DOCUMENTED / OPEN until a WordPress runtime test confirms the matrix.

## 8. F4 technician and warranty fields (item 7) — PARTIALLY MITIGATED

| Path | Reachable by | Lock (before → now) | Writes | Can change status? | Sensitive? | Overwrite risk |
|---|---|---|---|---|---|---|
| Meta-box technician (`_pixva_technician_id`) | `edit_post` holders: admin, manager | none → **order lock (R8)** | technician id, validated (`pixva_work_orders` or `pixva_manage_orders`) | No | Yes: decides who can work and see the order | Two saves: last writer wins before; now serialised |
| Meta-box warranty dates (`_pixva_warranty_start`/`_until`, source `manual`) | `edit_post` holders: admin, manager | none → **order lock (R8)** | dates (format validated), source `manual` | No | Yes: customer-visible warranty state, no history entry | As above |
| Warranty policy on `delivered` | Automatic, inside R/W-1 | order lock | start, until, source `policy` only when empty | Through the status change | Customer-visible | Not overwritten (only when empty) |
| Assigned technician form (`pixva_handle_order_update`) | `pixva_work_order`: assigned technician or manager | order lock | status (via R/W-1), internal note | **Yes** (status) | Notes internal | Serialised |
| Migration (R/W-6) | Admin init, one run at a time | order lock | warranty source `legacy` only when empty; phone hash; customer id | Only invalid status → `new` (see F5 P9) | Yes | Repeatable |

Reachability: the technician and warranty field writes can only be reached from the meta-box, which requires `edit_post`. A technician cannot reach them, because the technician role has no `edit_pixva_orders`. The technician can change status and internal notes through the form path only when assigned. No path lets a customer change these fields.

Status: PARTIALLY MITIGATED. The overwrite race is closed in code and tested with simulated locks (N11, R8). Authorisation is unchanged and is DOCUMENTED / OPEN as in F3. The lock path is NOT TESTED on MySQL.

## 9. F6 private photo report (item 8) — PARTIALLY MITIGATED (unchanged in Round 5)

- `pixva/inc/audit.php` is still read-only. The Round 4 test set Q was rerun on the final code: name, size, mtime and reason are reported; no absolute path appears in the output; the file still exists after the audit. Mutation A4 (an audit that writes or deletes) is CAUGHT.
- **No automatic deletion** exists anywhere in the audit path. Any cleanup must be a separate, reviewed tool with a dry-run and tests, and it is not part of this round.
- The `.htaccess` deny rule for the private directory (`security.php` ~779–800) is written by the theme. **Nginx has no `.htaccess`; the Nginx deny rule is a mandatory operational requirement that is NOT TESTED and not provided by the theme.** Apache behaviour is NOT TESTED on a real server.

## 10. F7 migration lock (item 9) — PARTIALLY MITIGATED

Review of expiry, takeover, release and concurrent runs:

| Aspect | Evidence |
|---|---|
| Acquire, held (second run refused), wrong-token release keeps the lock, right-token release frees it | P1–P4, SIMULATED |
| Stale (crashed) lock taken over after `PIXVA_MIGRATION_LOCK_TTL` | P5, SIMULATED |
| Second run while another holds the lock: no install step, DB version untouched, other owner's lock intact | P6, P6a, P6b, SIMULATED |
| Per-order migration repeatable; second run leaves history unchanged | P7, P7b, SIMULATED |
| A valid status and an existing list are not reverted | P8, SIMULATED |
| A status change between two runs keeps its history entry and its status | **P11 (new)**, SIMULATED |
| Per-order migration runs under the order lock | P10 (static), R9 (CAUGHT) |
| Concurrent runs across real processes | **NOT TESTED** (the SQLite port does not cover the migration) |

Status: PARTIALLY MITIGATED. The migration lock has no test with real concurrent processes, and the 30-second order lock applies only to the per-order step.

## 11. Packaging (item 10)

- **ZIP:** `dist/pixva.zip`, built with `git archive --format=zip -o dist/pixva.zip HEAD pixva` from the final code commit `abe6207`. The `pixva/` prefix comes from the archive paths.
- **SHA-256:** `b09789ab694504d51c0ec40733f7b2e75fe635d437ffcadf52d94cf664c3616d` (471989 bytes).
- **Contents:** 80 files and 9 directories (same counts as Round 4). The only differences from the Round 4 ZIP (`65cc20e`, extracted and compared) are `pixva/inc/inbox.php` and `pixva/inc/repairs.php`.
- **Excluded:** `pixva/phpcs.xml` (`export-ignore`). `frontend/`, `tests/`, `docs/`, `dist/`, `.git` are not under `pixva/`, so they are not in the ZIP. Checked by listing the entries.
- **Compression:** the Round 4 ZIP was stored uncompressed. The `git archive` ZIP is deflated (smaller). The file contents are the same.
- **Extracted-package check:** the PHP suite was run against code extracted from this ZIP: **253 passed, 0 failed** (SIMULATED).
- **`PIXVA_VERSION` left at `2.2.0`.** Reason: this round changes behaviour, but a version number is a release decision for the owner. No bump is made without that decision.
- **`frontend/` and redesigned CSS:** not imported, not changed in this round.

## 12. Not tested (explicit)

- Real MySQL/MariaDB: NOT TESTED (no server binary in this environment).
- WordPress runtime (core `edit_post` mapping, `wp_insert_post`, capability checks in a real site): NOT TESTED.
- Staging: NOT TESTED (not accessible).
- Nginx and Apache deny rules for the private folder on a real server: NOT TESTED.
- Real proxy headers and the IP decision (O-4): NOT TESTED; the decision remains open.
- Concurrent migration runs across real processes: NOT TESTED.
- Mail: no exactly-once claim is made. Email duplicates are possible (section 4).

## 13. Status by item

| Item | Status |
|---|---|
| F1-order (duplicate order, live reservation, stale continuation) | **PARTIALLY MITIGATED**: fixed and simulated/port-tested; residual window documented; MySQL NOT TESTED |
| F1 inbox | **PARTIALLY MITIGATED**: Round 4 kept; M14 added; exactly-once not claimed; outbox designed, not implemented |
| F5 state and history integrity | **DOCUMENTED / OPEN**: options compared; no architecture change; no transition table; status and steps not atomic |
| F2 internal notes | **PARTIALLY MITIGATED**: silent status and technician/warranty refusals now reported; lock on technician and warranty saves |
| F3 authorisation | **DOCUMENTED / OPEN**: matrix verified in simulation; runtime NOT TESTED; no capability change |
| F4 technician and warranty fields | **PARTIALLY MITIGATED**: lock added and tested (simulated); authorisation unchanged; reachability documented |
| F6 private photo report | **PARTIALLY MITIGATED**: read-only, no automatic deletion, tests rerun; Nginx/Apache deny NOT TESTED |
| F7 migration lock | **PARTIALLY MITIGATED**: P11 added; concurrent processes NOT TESTED |
| O-4 IP and proxy decision | **DOCUMENTED / OPEN** (unchanged) |
| Operational release | **NO-GO** |

## 14. Files changed this round

- `pixva/inc/repairs.php`: F1-order, F2/F4 locks and notices, docblock.
- `pixva/inc/inbox.php`: failed insert releases its reservation.
- `tests/unit/php/order-security.test.php`: H6, H7, H8, H10b rewritten; H14–H16, M14, N10, N11, P11 added. 253 checks.
- `tests/unit/php/mutation-controls.py`: R1–R9 added; controls run in parallel; CAUGHT requires a summary line and at least one FAIL.
- `tests/unit/db/reservation_sqlite_race.py`: port updated to the Round 5 rules; phase 3; `live_takeover` mutant.
- `tests/unit/README.md`: counts and sections updated.
- `docs/pixva-write-path-review.md`: W15, F1 residual and the live-takeover note corrected.
- `docs/pixva-round5-report.md`: this report.
- `dist/pixva.zip`: rebuilt from `abe6207`.

# PIXVA Phase A audit (baseline, state, contradictions)

Scope: a checkpoint of the state of the project, verified in this sandbox on the session branch. It is documentation only. No runtime code changed in this step.

## 1. Repository state (verified)

- Working copy for further work (this step adds only `docs/pixva-phase-a-audit.md`): `/home/user/pixva-full`, branch `arena/689d3d79-wpwebtv`, clean, HEAD `f3b0c9c` (equal to origin at the time of the clone; this audit file is the only uncommitted change).
- Recent history (verified with `git log`): `f3b0c9c` Round 5 report <- `abe6207` Round 5 code (F1-order, F2/F4 locks, F7 history test) <- `34ebd2c` / `65cc20e` Round 4 report and ZIP rebuild <- `256d033` Round 4 code <- `5f30468` Round 3 audit <- earlier rounds. Round 4 and 5 work is an ancestor of `f3b0c9c`.
- Older local clone `/home/user/wpwebtv`: HEAD `df81f49`. Earlier byte-level comparison found 43 tracked and 93 untracked paths identical to origin; only the local `dist/pixva.zip` (old base version) differs. Git still refuses fast-forward on its dirty tree. It was **not** modified in this step. A complete backup exists at `/home/user/backup-state-20261009-205750` (tar of the working tree without `.git`, untracked archive, tracked patch, status, SHA-256 manifest; all verified OK).
- Stashes: none were seen in the earlier read of the original repo.
- `PIXVA_VERSION` = `2.2.0` (`pixva/functions.php`), `Version: 2.2.0` in `pixva/style.css`. Not changed in this step.

## 2. Release artefact

- `dist/pixva.zip` at `f3b0c9c`: SHA-256 `b09789ab694504d51c0ec40733f7b2e75fe635d437ffcadf52d94cf664c3616d`.
- Its `pixva/` tree matches `git archive` of `pixva/` at `f3b0c9c` (verified by extraction and `diff -r` in an earlier step of this session).
- The commit message of `f3b0c9c` says the ZIP was rebuilt from `abe6207` with this SHA-256. This step did not rebuild or re-verify the build; it verified only the ZIP's content against the `pixva/` tree.
- Any change in this step invalidates this ZIP; a new ZIP must be built from the new commit.

## 3. Toolchain available in this sandbox

| Tool | Status |
|---|---|
| Node.js | v22.22.3 |
| npm | 10.9.8 |
| PHP (WASM runner, `@php-wasm/cli` 3.1.57) | PHP 8.5.10. This is the WASM runtime, **not** a server runtime. |
| Python 3 | present |
| nginx, Apache httpd | **absent** |
| MySQL, MariaDB | **absent** |
| WordPress, Staging | **not available** |
| Chromium, PHPCS, mutation/race harness | **not re-established** in this sandbox (the previous `qa-env` and `/tmp` artefacts were not present). Results from earlier sessions are not reported here as current. |

## 4. Baseline run in this step (clone at `f3b0c9c`)

| Check | Result | Evidence type |
|---|---|---|
| `tests/unit/php/order-security.test.php` | 253 passed, 0 failed, no FATAL | SIMULATED (stubbed WordPress and `wpdb`), PHP 8.5.10 WASM |
| `php -l` on 63 PHP files (`pixva/*.php`, `pixva/inc/*.php`, `pixva/page-templates/*.php`, `pixva/functions.php`) | all clean | static |

Not run in this step: mutation controls, SQLite race port, jsdom, Chromium, PHPCS, JavaScript syntax check, ZIP-extracted rerun. Their last results are from earlier sessions and are NOT TESTED on this exact state.

## 5. Contradictions to resolve

1. **Gap ledger (`docs/pixva-gap-ledger.json`)** marks 75 requirements `COMPLETE`, 1 `INTENTIONALLY_NOT_APPLICABLE` and 1 `COMPLETE — RUNTIME/BROWSER VERIFICATION BLOCKED`. The Round 4 and Round 5 reports list several items as OPEN or PARTIALLY MITIGATED (F1-order residual window, F3 runtime authorisation, F5 atomicity and transitions, F7 concurrent migration, O-4 IP decision). The ledger does not reflect these. **Until reconciled, the ledger must not be cited as evidence of completion.**
2. **`docs/pixva-architecture.md` (around line 167)** states that browser and server checks "were executed on a real staging stack" with nginx 1.31.6, Apache 2.4.68, PHP 8.3, WordPress 7.1.3 and SQLite. This sandbox has no such stack, and the claim has no artefact in this repository that this step could verify. It is recorded here as an **owner-reported claim, not verified in this step**. It must not be cited as a result from this work.
3. The Round 4 and Round 5 reports refer to a SQLite race port. That is evidence for the SQLite engine only, not for MySQL or MariaDB.

## 6. Security review points checked in this step (static, read-only)

- **Private photo download** (`pixva/inc/repairs.php`, `pixva_stream_order_photo`): requires a valid nonce bound to the order id, `current_user_can('pixva_view_order', $order_id)`, and a filename that is in the order's own photo list (allowlist from `_pixva_order_photos`, matched by a strict regex). The MIME comes from an allowlist, the response has `X-Content-Type-Options: nosniff` and `Cache-Control: private, no-store`. Status: **PASS by static review**; the runtime path through WordPress `admin-post` is NOT TESTED.
- **Partial photo rollback** (`pixva/inc/forms.php` ~328): deletes only files stored by the same request. Status: static review only.
- **Private directory protection**: the theme writes `uploads/pixva-private/.htaccess` (Apache, with `mod_authz_core` and fallback rules). Nginx requires an operator rule; it is documented in `docs/pixva-staging-checklist.md` §3i and `docs/pixva-architecture.md`. **Nginx and Apache behaviour is NOT TESTED** in this sandbox.
- **Client IP**: forwarded headers are read only from a declared trusted proxy (`pixva_trusted_proxies` filter or `PIXVA_TRUSTED_PROXIES` constant). The production proxy decision (O-4) is still owned by the operator and is **OPEN**.

## 7. Open items, in priority order (carried from Round 4 and Round 5 reports)

1. **F1-order residual window.** A stalled attempt can pass the fence and then write. Closing it needs a schema change (UNIQUE key on the submission id in a custom table) or a verified InnoDB transaction. Requires an owner decision and a MySQL/MariaDB run. **OPEN.**
2. **F5 state and history.** Status and history are two statements, and there is no transition table, so invalid transitions are accepted. Product rule needed. **OPEN.**
3. **F3 authorisation.** The role matrix was verified only by simulation. Real WordPress capability mapping is NOT TESTED.
4. **F1 inbox.** Email may be duplicated after a crash between send and marker. Outbox design needs owner approval. **OPEN** (exactly-once is not claimed).
5. **F7 migration.** Concurrent runs across real processes are NOT TESTED.
6. **O-4 IP and proxy decision.** Owner decision. **OPEN.**
7. **UI/UX, accessibility, performance, SEO, visual QA.** Not started in this step. Requires Chromium and real pages.
8. **Business content and legal text.** Must be marked NEEDS BUSINESS INPUT and NEEDS LEGAL REVIEW; no content was invented.

## 8. Verdicts at this checkpoint

- **Technical readiness:** PARTIAL. One simulated suite and lint pass on this exact state; the open items above remain.
- **ZIP and install readiness:** NOT TESTED. Not installed on WordPress.
- **Operational production readiness:** **NO-GO.**

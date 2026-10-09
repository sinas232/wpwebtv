# PIXVA: Write-path review (Round 3, pre-Staging)

Scope: every code path that writes order status, history, ownership, notes, photos or sensitive order meta, plus the contact inbox, migration and activation. This is a code review, not a runtime test. Each row states what the code does on the branch at the time of writing. Results of runs are in the release report, not here.

Legend. **Lock**: the per-order advisory lock `pixva_olock_<id>` (`pixva_with_order_lock()`). **Idempotent**: a retry does not duplicate effects. **Overwrite risk**: whether a concurrent writer can silently lose data.

## 1. Order write paths

| # | Path (file) | Who can call it | Authorisation | Lock | Concurrency and overwrite risk |
| --- | --- | --- | --- | --- | --- |
| W1 | Status change from the account form `pixva_order_update` (`forms.php` `pixva_handle_order_update`) | Logged-in users only (anonymous requests are redirected to the account page); the handler checks `current_user_can( 'pixva_work_order', $id )` | Per-order capability, checked before any write | Status: `pixva_set_order_status()` holds the lock. Internal note: a separate lock call. | Status and note are two lock acquisitions, so a failure between them is reported as "status saved, note not saved". Lost-update risk is limited to a concurrent status change, which is serialised. |
| W2 | Status change from the admin meta box `pixva_save_order_box` (`repairs.php` ~1064) | Users with `edit_post` on the order; nonce checked | `edit_post` only. The per-order capability is not checked here (see finding F3) | `pixva_set_order_status()` holds the lock | Status is serialised. Fencing check runs before the two writes but is not atomic with them (F5). |
| W3 | Status history and warranty dates, inside `pixva_set_order_status_locked()` | Only through W1, W2 or the lock wrapper | Closed orders (delivered, cancelled) need `pixva_manage_orders` (checked) | Yes, owner fenced before writes | Stale owner is fenced at the check, not in the write (F5). History is read inside the lock, so no lost history from a concurrent status change. |
| W4 | Internal notes from the admin box (`_pixva_order_notes`, `save_order_box`) | `edit_post` | `edit_post` | **No lock** | Whole-field overwrite. A note appended concurrently by W1 can be lost (F2). |
| W5 | Internal notes from the public form (`pixva_handle_order_update`, `_pixva_order_notes` append) | `pixva_work_order` | Per-order capability | Yes | Append under the lock, so no loss between two appends. |
| W6 | Ownership claim `pixva_handle_claim` (`_pixva_customer_id`) | Any logged-in user with a valid code and phone | Code and phone verified (`pixva_verify_order_access`), rate-limited | Yes: read-check-write under the lock | Two accounts cannot both take an unowned order. An owned order returns 404, the same as not found. |
| W7 | Technician assignment (`_pixva_technician_id`, meta box) | `edit_post` | Target user must have `pixva_work_orders` or `pixva_manage_orders` | **No lock** | Last writer wins. Low impact, admin-only (F4). |
| W8 | Warranty dates set by hand (`_pixva_warranty_start/until`, meta box) | `edit_post` | `edit_post` | **No lock** | Last writer wins; source marked `manual`. Admin-only (F4). |
| W9 | Order creation from booking (`pixva_place_order_once` / `pixva_insert_order`) | Public form, protected by the submission claim | Submission claim, nonce, honeypot, rate limit | Reservation row `pixva_ord_<sid>` (create-once and compare-and-replace), not the order lock | Exactly one order per submission id (tested in the harness and on SQLite). Photo files written before this call (see F6). |
| W10 | Photo list (`_pixva_order_photos`) | Set at creation (W9) and rewritten on adoption | Same as W9 | Rewritten on adoption, unlocked within the claim | On adoption the list is replaced by the new attempt's files, so earlier files are orphaned (F6). |
| W11 | Order code (`_pixva_order_code`) at creation or repair | W9, and the meta box when the code is missing | Same as W9 / `edit_post` | Meta box path is under the lock | Duplicate codes are possible only if the random generator collides; the audit reports `order_code_duplicate`. |
| W12 | Customer id (`_pixva_customer_id`) by migration | `switch_themes`, first admin request after upgrade | Admin only | **No lock** | Written only if empty; a claim running at the same moment could be overwritten (F7). |
| W13 | Order steps migration (`migration.php` ~330–346) | Same as W12 | Admin only | No lock | Rewrites the history list in place; safe only if no status change runs at the same moment (F7). |
| W14 | Demo order trash (`PXV-DEMO-2401`, migration) | Same as W12 | Admin only | No lock | Trash, not delete. |
| W15 | Order deletion in duplicate removal (`pixva_remove_duplicate_orders`) | Called by W9, after the link or when the reservation is already linked | Internal | Under the claim | Deletes unlinked duplicates. Their photo files are **not** deleted (F6). |
| W16 | Prune of reservation and claim rows (`pixva_prune_expiring_rows`) | WP-Cron (`pixva_prune_rows`) | Internal | Uses compare-and-delete | Linked reservations are kept for 30 days (tested, O8). |

## 2. Findings from this review

- **F1 (inbox duplicates).** `pixva_handle_contact` inserts a `pixva_inbox` post on every valid submission that passes the claim. The claim stops double-clicks. It does **not** stop this case: PHP dies after `wp_insert_post` and before `pixva_finish_submission`; the pending claim expires after 300 s; the visitor resubmits; a second message is inserted and a second staff email is sent. The risk is real but narrow. Decision needed (section 3).
- **F2 (lost internal note).** W4 overwrites `_pixva_order_notes` from the textarea without the lock. A note appended by W5 between the page load and the save is lost. Fix: append through the same lock path as W5, or compare the loaded value (compare-and-replace). Not changed in this round.
- **F3 (meta-box capability).** W2 uses `edit_post`, not the per-order `pixva_work_order` capability used by W1. In the current role model these may be the same users; this needs confirmation on Staging (see prerequisites).
- **F4 (unlocked admin fields).** W7 and W8 are last-writer-wins. Low impact.
- **F5 (fence not atomic with the write).** In `pixva_set_order_status_locked()` the owner check runs before the two `update_post_meta` calls. A takeover in that gap is possible only after the 30 s stale threshold. Residual, documented; a compare-and-replace on the status row would close it and is not done here.
- **F6 (orphaned private photos).** Photos are stored before W9. Orphans arise from: (a) a placement error after storage (`pixva_place_order_once` returns `WP_Error` and the booking handler does not delete the stored files); (b) adoption replacing the photo list (W10); (c) duplicate removal (W15). A file cannot be assigned to a deleted order afterwards, so cleanup is manual (checklist 3k). Fixing (a) safely needs the rule that the `busy` error path can leave an unlinked order that references the files, so deletion there is **not** safe without a ledger.
- **F7 (migration without a guard).** W12 and W13 run on the first admin request after upgrade, with no lock and no "already running" flag. Two admin requests at the same moment can race. Mitigation: upgrade with a single admin session and no booking traffic (checklist 3j).

## 3. Inbox idempotency: proposal (NOT implemented; decision required)

Current contract (must not change): the contact form posts the same fields, and `_pixva_sid` is already a hidden field rendered for every form. The claim (`pixva_claim_submission`) runs before the handler. The handler returns the original outcome for a finished claim and 409 for a pending one.

Option A (recommended, no form change): give the inbox post a stable slug derived from the submission id (`post_name = sid`), and adopt an existing post with that slug before inserting, the same pattern as orders.
- Adoption rewrites the meta with the same data (idempotent).
- The staff email is sent only when the post is first inserted **or** when the post carries no `_pixva_msg_notified` marker. The marker is written after the mail call.
- Migration effects: none for existing posts (they have no slug and no marker). New posts get a slug. Admin URLs use the post ID, not the slug, so links in existing notification emails keep working.
- Backward compatibility: the form, REST/AJAX response, notification text and dashboard do not change. Old posts without the marker behave as before.
- Residual: a crash between the mail call and the marker write sends a second email. That is an accepted trade-off (duplicate email is safer than a lost message), and it must be stated to the user.
- Tests required before merge: fatal after insert, fatal after mail, retry after TTL, duplicate request during pending, legacy post, and negative controls for each guard.

Option B (form change): add a client-generated message id. Rejected for this round because it changes the form contract.

Until a decision is made, F1 is an open finding. Operational release stays NO-GO for this item.

## 4. Migration and activation (reviewed separately)

- `pixva_maybe_migrate` (`admin_init`) and `pixva_run_migrations` run on the first admin request after the theme version changes. They are guarded only by `pixva_db_version`. There is no lock and no "in progress" flag (F7).
- `pixva_on_switch_theme` (`after_switch_theme`) runs the same install code. It is not a second writer for orders, but it can run during bookings if the theme is switched on a live site.
- Activation writes options and roles (`pixva_install_roles`, `pixva_install`). These are idempotent writes of fixed values.
- Recommendation: upgrade and switch themes in a maintenance window, and check `pixva_db_version` after the first request.

## 5. Status of these items

- Code review: done (this document).
- Runtime checks of W1–W16 on real MySQL/MariaDB: NOT TESTED. Staging not accessible.
- F1, F2, F3, F6, F7: open. F4, F5: documented residuals.

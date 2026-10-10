# Content recovery record — /el-test/ Elementor document (2026-10-10)

## Incident
During save-API diagnosis a diagnostic `save_builder` request with `elements:[]`
was POSTed to page id=24 (`/el-test/`). WordPress revisions captured the event:
rev 29/30 stored `_elementor_data = []` (empty). The frontend briefly rendered
the Elementor wrapper with **zero widgets** (verified: 0 `data-elementor-type`,
0 `pixva-steps` markers).

## Backups taken BEFORE any further save
- `docs/evidence/content-backup/page-24-el-test.json` — current data + 4 revisions (incl. healthy rev 28)
- `docs/evidence/content-backup/page-4-home.json` — home data + rev 27
- `docs/evidence/content-backup/page-25-el-booking.json`
- `docs/evidence/content-backup/page-26-el-track.json`
- Source of truth for classic home markup: server-rendered snapshot taken at recovery time.

## Recovery
Healthy pre-wipe document found in **revision 28** (2026-10-10 08:36:02,
`pixva-steps` widget, heading «مراحل تعمیر — ویرایش Elementor», 328 bytes).
Restored through the real Elementor pipeline (`elementor_ajax` → `save_builder`,
authenticated admin session, fresh nonce) — NOT by writing meta directly.

## Verification (after restore)
- HTTP 200 on `/el-test/`
- PASS: healthy rev-28 heading rendered server-side
- PASS: diagnostic save-string absent
- PASS: `<ol class="steps">` widget output present
- PASS: `data-elementor-id` wrapper present

## Scope check
Only diagnostic test pages were affected (`el-test`, plus test-created
`el-booking`/`el-track` containers). The real home page (`id=4`) classic content
in `post_content` was never touched; its leftover 267-byte test `_elementor_data`
is inventoried in the backup above and is replaced by the real Elementor build
(not left as test data).

## Classic pre-conversion snapshots (archived 2026-10-10)
Full server-rendered HTML of every classic page, captured BEFORE the Elementor
conversion, is preserved in `docs/evidence/content-backup/classic-html-snapshots/`:
about, contact, faq, problems, repair, repair_book, tools, tracking, warranty
(+ `markers.json`). These are the reference of record for parity checks and for
recovering any classic markup.
Page id=15 (`/about/`): its `post_content` was rewritten during conversion; the
classic rendering is fully preserved in `about_.html` above and the about page
renders from `about.php` + Elementor site panels regardless of `post_content`.
No admin-authored content, orders, messages, options or business data were
deleted at any point (verified by suites: T12 order read-back, T38 fake-data
scan, checklist state).

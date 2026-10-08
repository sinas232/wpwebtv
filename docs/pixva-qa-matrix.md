# PIXVA 2.0.0 — QA matrix

Environment: WordPress 7.1.3, SQLite integration 2.2.23, PHP 8.3 (php-wasm CLI), request harness that boots WordPress per request (status, headers via filters, body). Fixtures: 1 brand, 1 model, 1 service, 1 error code, 1 FAQ, 1 article, users for every role. No headless browser is available, so browser-only checks are listed separately and are **not** claimed as passed.

| ID | Area | Check | Method | Result |
|---|---|---|---|---|
| T01 | Static | PHP syntax of all theme files | php -l (PHP 8.3) on 61 files | PASS — 0 errors |
| T02 | Static | WordPress Coding Standards (theme phpcs.xml, WPCS 3.4.1 / PHPCS 3.13.6) | phpcs full theme | PASS — 0 errors; 14 warnings, all reviewed performance advisories on bounded queries (posts_per_page ≤ 500 with no_found_rows, meta/tax queries) |
| T03 | Static | phpcbf formatting pass is behaviour-neutral | PHP token-stream comparison before/after (whitespace + trailing commas ignored) | PASS — 0 token-different files |
| T04 | Static | JS syntax | node --check pixva/assets/js/*.js (7 files) | PASS |
| T05 | Static | Duplicate functions / undefined pixva_* calls | tools/php_lint.py + call/definition scan | PASS — none (remaining matches are capability/hook names) |
| T06 | Static | CSS class coverage | tools/class_audit.py | PASS — 0 used-but-unstyled, 0 unused |
| T07 | Static | theme.json validity | json.load | PASS |
| T08 | Static | Design-token integrity (§25) | theme.json palette + hex scan of CSS/PHP/JS | PASS — palette has exactly the 12 §25 colours; no off-palette hex in CSS/PHP/JS except the pixel-test colours (the tool itself) |
| T09 | Static | Fake-data purge (§34) | grep for v1 phones, 180-day warranty, demo order, ratings/reviews | PASS — only in the migration denylist; preview/ v1.6 mock with fake phone/warranty removed |
| T10 | Static | REST permission callbacks (§51) | review of inc/rest.php | PASS — 5 register calls / 6 routes, every route has permission_callback (rate limiter or code+phone proof) |
| T11 | Runtime | Fresh install activation | WP 7.1.3 + SQLite, switch_theme + after_switch_theme | PASS — db 2.0.0, /blog/%postname%/, 20 pages, front/blog set |
| T12 | Runtime | 49-route crawl (§59) | /tmp harness crawl: status, h1, robots, canonical, title, PHP errors | PASS — 39×200, 6×301 (single hop), 2×404, 1×410, 0 PHP-error pages (fresh install, final HEAD) |
| T13 | Runtime | Booking validation | AJAX POST invalid data | PASS — 422 + per-field errors |
| T14 | Runtime | Booking success + duplicate submit | AJAX POST valid, then same _pixva_sid | PASS — PXV code; duplicate returns same code, no second analytics event |
| T15 | Runtime | Booking no-JS PRG | admin-post POST | PASS — 303 to one-time result page with code |
| T16 | Runtime | Booking prefill from diagnosis | GET array + CSV symptoms; without from=diagnosis | PASS — array and CSV accepted, unknown keys dropped, no prefill without from=diagnosis |
| T17 | Runtime | Contact rate limit | 8 rapid POSTs | PASS — 429 after limit |
| T18 | Runtime | Tracking without PII | REST track with code+phone | PASS — 200, no name/phone/internal note |
| T19 | Runtime | Tracking oracle / lock | wrong phone; repeated failures | PASS — 404 identical to unknown code; per-code 429 lock |
| T20 | Runtime | Account claim + IDOR | cust1 claims, cust2 tries same order | PASS — 200 / 404; cust2 cannot see order |
| T21 | Runtime | Technician authorisation | unassigned update; assigned update; customer update | PASS — 6/6: 403 unassigned; assigned sees order with masked phone (۰۹۳۵•••۲۳۳), update 200; customer update 403/404 |
| T22 | Runtime | Public vs internal notes | update with quotes/backslash, then track | PASS — public note intact, internal note never in tracking |
| T23 | Runtime | Dashboard roles | GET /dashboard/ as each role | PASS — customer 403; editor/manager/technician/admin 200; editor sees no PII |
| T24 | Runtime | REST endpoints | models, error-codes, estimate, diagnosis (valid/invalid) | PASS — 200 ×4, 400 on invalid problem |
| T25 | Runtime | REST exposure | wp/v2/users anon; orders in REST | PASS — both 404 |
| T26 | Runtime | Sitemap exclusion cache | noindex meta toggled; cache keyed by theme version and flushed on post/meta/redirect/gone/theme changes | PASS — excluded immediately, restored after clearing; stale pre-update cache ignored |
| T27 | Runtime | Thin and untouched sample content | hello-world post, Sample Page before/after an owner edit | PASS — both noindex,follow and out of the sitemap while untouched; Sample Page indexable and listed after edit |
| T28 | Runtime | Front-page meta description | crawl / | PASS — description from pixva_front_lead() |
| T29 | Runtime | v1.7 → v2 migration | v1.7 activated (20 pages, 32 posts) then v2 migration, run twice | PASS — 24 log entries, fake settings/claims/tagline parked, menus retargeted, second run byte-identical |
| T30 | Runtime | Legacy URLs after migration | crawl v1 paths | PASS — 301 single hop (/calculator/, /diagnosis/, /category/…, /{post}/), 410 for /b2b/, /parts-stock/ |
| T31 | Runtime | robots.txt | GET /robots.txt | PASS — only /wp-admin/ disallowed (admin-ajax allowed), sitemap listed, CSS/JS crawlable |
| T32 | Runtime | Escaping / schema honesty | crawl with quotes/HTML in fixtures; JSON-LD review | PASS — escaped output; no LocalBusiness/rating/review without real data |
| T33 | Runtime | Admin overview checklist | render via harness | PASS — 7 items incl. core sample content detection |
| T34 | Runtime | Private image validation, storage and streaming access | pixva_store_private_image() with the is_uploaded/move test seams; admin-post pixva_order_photo as anon/other customer/editor | PASS — real PNG stored with random name in uploads/pixva-private (+ .htaccess, index.php); PHP disguised as .png rejected (upload_type); 6 MB rejected (upload_size); streaming denied: anon 400, non-owner 403, editor 403 |
| T35 | Runtime | Sitemap integrity (both directions) | every URL of every sub-sitemap fetched: status, robots, canonical | PASS — fresh install 24/24 and migrated v1.7 site 47/47 URLs are 200, indexable and self-canonical; users sitemap 404 |
| T36 | Runtime | One-hop guarantee on URL variants | redirect chains followed (≤6) for 34 variants on fresh install + 20 legacy paths on migrated site: no trailing slash, /index.php/, ?p=, ?page_id=, ?cat=, ?tv_problem=, CPT query vars, ?post_type=, legacy v1 paths | PASS — 0 chains; every redirect is a single 301 to a 200 (or a direct 410/404). Fixed in this pass: CPT query-var and bare ?post_type= URLs were 200 duplicates, now one-hop 301 |
| T37 | Runtime | Final acceptance suite (53 checks) | photo streaming (owner/manager/tech/other customer/editor/bad nonce/traversal), warranty privacy, no-JS tracking/warranty forms, PRG one-time token, profile role escalation, anon form actions, wp-admin restriction per role, redirect manager per role, REST exposure incl. users and oEmbed, no-store headers via real REST dispatch | PASS — 53/53. Fixed in this pass: logged-in customers/technicians could list users via /wp/v2/users (admin login slug) and oEmbed exposed author_url; now 404 / removed, /users/me returns only own record, editors keep the list |
| T38 | Runtime | Rendered fake-data scan | tel:/WhatsApp links, phone numbers, prices, ratings/reviews, warranty durations, statistics, demo order across all crawled + indexable pages | PASS — fresh install 46 crawled + 28 indexable pages, migrated v1.7 site 51 pages: 0 hits |
| T39 | Runtime | Distribution ZIP | dist/pixva.zip extracted and compared with pixva/ (diff + SHA-256 manifest); theme installed from the ZIP on a clean database and 12 routes crawled | PASS — ZIP == tree (88 files, identical manifest hash), only pixva/ paths, Version 2.0.0; activation from ZIP OK, 0 PHP errors, 301/410 behaviour intact |
| B01 | Browser | Keyboard-only walkthrough of diagnosis wizard, booking, pixel test | manual / Playwright | BLOCKED — Needs a real browser (rendering, focus/screen reader, viewport, JS execution); the sandbox has PHP + WordPress CLI harness only, no headless browser. |
| B02 | Browser | Screen-reader announcements (aria-live, error summary focus) | NVDA/VoiceOver | BLOCKED — Needs a real browser (rendering, focus/screen reader, viewport, JS execution); the sandbox has PHP + WordPress CLI harness only, no headless browser. |
| B03 | Browser | Responsive layout 320–1440 px, RTL rendering, Vazirmatn shaping | visual | BLOCKED — Needs a real browser (rendering, focus/screen reader, viewport, JS execution); the sandbox has PHP + WordPress CLI harness only, no headless browser. |
| B04 | Browser | Core Web Vitals / Lighthouse | Lighthouse | BLOCKED — Needs a real browser (rendering, focus/screen reader, viewport, JS execution); the sandbox has PHP + WordPress CLI harness only, no headless browser. |
| B05 | Browser | Pixel test fullscreen API, reduced-motion, colour cycling | manual | BLOCKED — Needs a real browser (rendering, focus/screen reader, viewport, JS execution); the sandbox has PHP + WordPress CLI harness only, no headless browser. |
| B06 | Browser | JS enhancement flows (AJAX submit, busy states, model loading) and analytics dispatch to dataLayer/gtag | manual / Playwright | BLOCKED — Needs a real browser (rendering, focus/screen reader, viewport, JS execution); the sandbox has PHP + WordPress CLI harness only, no headless browser. |
| B07 | Infrastructure | Real multipart upload through a web server, Apache .htaccess / Nginx deny enforcement for uploads/pixva-private, outbound mail delivery | deployed server | BLOCKED — Needs a deployed web server and mail transport; the sandbox runs WordPress through a CLI request harness. |

Requirement coverage: see [pixva-traceability.md](pixva-traceability.md).

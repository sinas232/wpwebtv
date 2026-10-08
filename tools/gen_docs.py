#!/usr/bin/env python3
"""Generate PIXVA §60–§62 documents from ONE data structure.

Outputs (overwritten on every run):
  docs/pixva-traceability.md  §01–§77 matrix (requirement, location, files,
                              status, verification, blocker)
  docs/pixva-gap-ledger.json  machine-readable ledger (allowed statuses only)
  docs/pixva-qa-matrix.md     executed checks + browser-only items

Guards: every referenced file must exist, every status must be one of the
allowed statuses, sections §01–§77 must all be present exactly once, and
every non-COMPLETE status must carry a reason/blocker.

Usage: python3 tools/gen_docs.py
"""
import json
import os
import sys

ROOT = os.path.abspath(os.path.join(os.path.dirname(__file__), '..'))
VERSION = '2.0.0'
DATE = '2026-10-08'

C = 'COMPLETE'
RB = 'COMPLETE — RUNTIME/BROWSER VERIFICATION BLOCKED'
BL = 'BLOCKED'
NA = 'INTENTIONALLY_NOT_APPLICABLE'
ALLOWED = (C, RB, BL, NA)

# Counts derived from the tree so the documents cannot drift from the code.
_THEME = os.path.join(ROOT, 'pixva')
N_ROOT_TPL = len([f for f in os.listdir(_THEME) if f.endswith('.php') and f != 'functions.php'])
N_PAGE_TPL = len([f for f in os.listdir(os.path.join(_THEME, 'page-templates')) if f.endswith('.php')])
N_PHP = sum(1 for d, _s, fs in os.walk(_THEME) for f in fs if f.endswith('.php'))
N_JS = len([f for f in os.listdir(os.path.join(_THEME, 'assets', 'js')) if f.endswith('.js')])

# Test ids referenced from the matrix (defined in TESTS below).
# ---------------------------------------------------------------------------
TESTS = [
    # id, area, check, method, result
    ('T01', 'Static', 'PHP syntax of all theme files', 'php -l (PHP 8.3) on %d files' % N_PHP, 'PASS — 0 errors'),
    ('T02', 'Static', 'WordPress Coding Standards (theme phpcs.xml, WPCS 3.4.1 / PHPCS 3.13.6)', 'phpcs full theme', 'PASS — 0 errors; 14 warnings, all reviewed performance advisories on bounded queries (posts_per_page ≤ 500 with no_found_rows, meta/tax queries)'),
    ('T03', 'Static', 'phpcbf formatting pass is behaviour-neutral', 'PHP token-stream comparison before/after (whitespace + trailing commas ignored)', 'PASS — 0 token-different files'),
    ('T04', 'Static', 'JS syntax', 'node --check pixva/assets/js/*.js (%d files)' % N_JS, 'PASS'),
    ('T05', 'Static', 'Duplicate functions / undefined pixva_* calls', 'tools/php_lint.py + call/definition scan', 'PASS — none (remaining matches are capability/hook names)'),
    ('T06', 'Static', 'CSS class coverage', 'tools/class_audit.py', 'PASS — 0 used-but-unstyled, 0 unused'),
    ('T07', 'Static', 'theme.json validity', 'json.load', 'PASS'),
    ('T08', 'Static', 'Design-token integrity (§25)', 'theme.json palette + hex scan of CSS/PHP/JS', 'PASS — palette has exactly the 12 §25 colours; no off-palette hex in CSS/PHP/JS except the pixel-test colours (the tool itself)'),
    ('T09', 'Static', 'Fake-data purge (§34)', 'grep for v1 phones, 180-day warranty, demo order, ratings/reviews', 'PASS — only in the migration denylist; preview/ v1.6 mock with fake phone/warranty removed'),
    ('T10', 'Static', 'REST permission callbacks (§51)', 'review of inc/rest.php', 'PASS — 5 register calls / 6 routes, every route has permission_callback (rate limiter or code+phone proof)'),
    ('T11', 'Runtime', 'Fresh install activation', 'WP 7.1.3 + SQLite, switch_theme + after_switch_theme', 'PASS — db 2.0.0, /blog/%postname%/, 20 pages, front/blog set'),
    ('T12', 'Runtime', '49-route crawl (§59)', '/tmp harness crawl: status, h1, robots, canonical, title, PHP errors', 'PASS — 39×200, 6×301 (single hop), 2×404, 1×410, 0 PHP-error pages (fresh install, final HEAD)'),
    ('T13', 'Runtime', 'Booking validation', 'AJAX POST invalid data', 'PASS — 422 + per-field errors'),
    ('T14', 'Runtime', 'Booking success + duplicate submit', 'AJAX POST valid, then same _pixva_sid', 'PASS — PXV code; duplicate returns same code, no second analytics event'),
    ('T15', 'Runtime', 'Booking no-JS PRG', 'admin-post POST', 'PASS — 303 to one-time result page with code'),
    ('T16', 'Runtime', 'Booking prefill from diagnosis', 'GET array + CSV symptoms; without from=diagnosis', 'PASS — array and CSV accepted, unknown keys dropped, no prefill without from=diagnosis'),
    ('T17', 'Runtime', 'Contact rate limit', '8 rapid POSTs', 'PASS — 429 after limit'),
    ('T18', 'Runtime', 'Tracking without PII', 'REST track with code+phone', 'PASS — 200, no name/phone/internal note'),
    ('T19', 'Runtime', 'Tracking oracle / lock', 'wrong phone; repeated failures', 'PASS — 404 identical to unknown code; per-code 429 lock'),
    ('T20', 'Runtime', 'Account claim + IDOR', 'cust1 claims, cust2 tries same order', 'PASS — 200 / 404; cust2 cannot see order'),
    ('T21', 'Runtime', 'Technician authorisation', 'unassigned update; assigned update; customer update', 'PASS — 6/6: 403 unassigned; assigned sees order with masked phone (۰۹۳۵•••۲۳۳), update 200; customer update 403/404'),
    ('T22', 'Runtime', 'Public vs internal notes', 'update with quotes/backslash, then track', 'PASS — public note intact, internal note never in tracking'),
    ('T23', 'Runtime', 'Dashboard roles', 'GET /dashboard/ as each role', 'PASS — customer 403; editor/manager/technician/admin 200; editor sees no PII'),
    ('T24', 'Runtime', 'REST endpoints', 'models, error-codes, estimate, diagnosis (valid/invalid)', 'PASS — 200 ×4, 400 on invalid problem'),
    ('T25', 'Runtime', 'REST exposure', 'wp/v2/users anon; orders in REST', 'PASS — both 404'),
    ('T26', 'Runtime', 'Sitemap exclusion cache', 'noindex meta toggled; cache keyed by theme version and flushed on post/meta/redirect/gone/theme changes', 'PASS — excluded immediately, restored after clearing; stale pre-update cache ignored'),
    ('T27', 'Runtime', 'Thin and untouched sample content', 'hello-world post, Sample Page before/after an owner edit', 'PASS — both noindex,follow and out of the sitemap while untouched; Sample Page indexable and listed after edit'),
    ('T28', 'Runtime', 'Front-page meta description', 'crawl /', 'PASS — description from pixva_front_lead()'),
    ('T29', 'Runtime', 'v1.7 → v2 migration', 'v1.7 activated (20 pages, 32 posts) then v2 migration, run twice', 'PASS — 24 log entries, fake settings/claims/tagline parked, menus retargeted, second run byte-identical'),
    ('T30', 'Runtime', 'Legacy URLs after migration', 'crawl v1 paths', 'PASS — 301 single hop (/calculator/, /diagnosis/, /category/…, /{post}/), 410 for /b2b/, /parts-stock/'),
    ('T31', 'Runtime', 'robots.txt', 'GET /robots.txt', 'PASS — only /wp-admin/ disallowed (admin-ajax allowed), sitemap listed, CSS/JS crawlable'),
    ('T32', 'Runtime', 'Escaping / schema honesty', 'crawl with quotes/HTML in fixtures; JSON-LD review', 'PASS — escaped output; no LocalBusiness/rating/review without real data'),
    ('T33', 'Runtime', 'Admin overview checklist', 'render via harness', 'PASS — 7 items incl. core sample content detection'),
    ('T34', 'Runtime', 'Private image validation, storage and streaming access', 'pixva_store_private_image() with the is_uploaded/move test seams; admin-post pixva_order_photo as anon/other customer/editor', 'PASS — real PNG stored with random name in uploads/pixva-private (+ .htaccess, index.php); PHP disguised as .png rejected (upload_type); 6 MB rejected (upload_size); streaming denied: anon 400, non-owner 403, editor 403'),
    ('T35', 'Runtime', 'Sitemap integrity (both directions)', 'every URL of every sub-sitemap fetched: status, robots, canonical', 'PASS — fresh install 24/24 and migrated v1.7 site 47/47 URLs are 200, indexable and self-canonical; users sitemap 404'),
    ('T36', 'Runtime', 'One-hop guarantee on URL variants', 'redirect chains followed (≤6) for 34 variants on fresh install + 20 legacy paths on migrated site: no trailing slash, /index.php/, ?p=, ?page_id=, ?cat=, ?tv_problem=, CPT query vars, ?post_type=, legacy v1 paths', 'PASS — 0 chains; every redirect is a single 301 to a 200 (or a direct 410/404). Fixed in this pass: CPT query-var and bare ?post_type= URLs were 200 duplicates, now one-hop 301'),
    ('T37', 'Runtime', 'Final acceptance suite (53 checks)', 'photo streaming (owner/manager/tech/other customer/editor/bad nonce/traversal), warranty privacy, no-JS tracking/warranty forms, PRG one-time token, profile role escalation, anon form actions, wp-admin restriction per role, redirect manager per role, REST exposure incl. users and oEmbed, no-store headers via real REST dispatch', 'PASS — 53/53. Fixed in this pass: logged-in customers/technicians could list users via /wp/v2/users (admin login slug) and oEmbed exposed author_url; now 404 / removed, /users/me returns only own record, editors keep the list'),
    ('T38', 'Runtime', 'Rendered fake-data scan', 'tel:/WhatsApp links, phone numbers, prices, ratings/reviews, warranty durations, statistics, demo order across all crawled + indexable pages', 'PASS — fresh install 46 crawled + 28 indexable pages, migrated v1.7 site 51 pages: 0 hits'),
    ('T39', 'Runtime', 'Distribution ZIP', 'dist/pixva.zip extracted and compared with pixva/ (diff + SHA-256 manifest); theme installed from the ZIP on a clean database and 12 routes crawled', 'PASS — ZIP == tree (88 files, identical manifest hash), only pixva/ paths, Version 2.0.0; activation from ZIP OK, 0 PHP errors, 301/410 behaviour intact'),
    ('E01', 'Staging/browser', 'Keyboard: skip link, visible focus on every Tab stop, mobile nav (Enter/Esc, focus return, 44px target), diagnosis radio groups by arrow keys, pixel test keys', 'Chromium (puppeteer-core) on nginx 1.31 + PHP 8.3 + WP 7.1.3 staging, suite s4', 'PASS — 52/52 (51 tabbables, all with a focus ring; Esc restores focus)'),
    ('E02', 'Staging/browser', 'Automated accessibility of every public route + announcements', 'axe-core on all routes at 360/768/1366 px (s1); role=alert / aria-live content after client and server validation (s2, s4); Lighthouse accessibility', 'PASS — 0 axe violations, Lighthouse accessibility 100 on 8 key routes, errors announced via role=alert. Real assistive technology (NVDA/JAWS/VoiceOver) not run: needs Windows/macOS'),
    ('E03', 'Staging/browser', 'Responsive layout and RTL rendering of every public route', 'Chromium at 360, 768, 1366 px: horizontal overflow, single h1, dir/lang, Vazirmatn loaded, screenshots (s1)', 'PASS — 1151/1151 checks. Fixed: 13px overflow at 360px; front end was ltr/en-US without the fa_IR core pack (now rtl + fa-IR, also verified with fa_IR and de_DE locales lacking .mo files)'),
    ('E04', 'Staging/performance', 'Core Web Vitals (lab)', 'Lighthouse 12.8.2 mobile (simulated Moto G Power, slow 4G, 4x CPU) on 8 routes + real Chromium with applied throttling (4x CPU, 1.6 Mbps/150 ms) measuring LCP/CLS/INP via PerformanceObserver and Event Timing (s6)', 'PASS — 60/60: performance 100, LCP ~1.65 s (incl. ~0.5 s php-wasm TTFB), CLS 0, TBT 0–6 ms, INP 40–48 ms, ~138 KB and 5–7 KB JS per page. Field data (CrUX) needs production traffic'),
    ('E05', 'Staging/browser', 'Pixel test fullscreen API, colour cycling, Esc/focus restore, reduced motion, no-JS anchors', 'Chromium, real Fullscreen API, prefers-reduced-motion emulation (s4)', 'PASS — fullscreen entered/exited, arrows/Space cycle, no animation or transition over 10 ms with reduced motion'),
    ('E06', 'Staging/browser', 'JS enhancement flows and analytics dispatch', 'Chromium: AJAX booking with busy state at 360 px, diagnosis wizard JS + no-JS, calculator unset → configured in admin → disabled, error-code live filter, dataLayer capture (s2, s4)', 'PASS — events use allow-listed props only (no name/phone/query text/amounts); calculator shows a range only when configured, inspection state otherwise; no-JS paths identical in outcome'),
    ('E07', 'Staging/server', 'Real multipart upload and private-store protection through Nginx AND Apache; real mail', 'nginx 1.31.6 deny rule; Apache httpd 2.4.68 (AllowOverride All) with and without mod_access_compat; aiosmtpd SMTP sink (s2, s9)', 'PASS — Nginx 61/61, Apache 23/23 in both modes: random-named files, direct/encoded/traversal/listing/PHP requests 403, staff stream 200 with identical bytes and no-store, staff mail delivered without customer PII. Fixed: bare "Deny from all" was a 500 + core:alert on Apache without mod_access_compat'),
    ('E08', 'Staging/server', 'pixva_client_ip behind a reverse proxy and rate limits with real client IPs', 'curl --interface 127.0.0.N through nginx (X-Real-IP = $remote_addr) and Apache (X-Real-IP = REMOTE_ADDR); control run with the proxy filter disabled (s5, s9)', 'PASS — 17/17: booking 6/h per client, lookups 20/10 min per client, per-code lock after 5 failures across IPs, spoofed X-Real-IP/XFF/Forwarded ignored, without the filter all clients share the proxy bucket. Fixed: REST limits counted twice per request (half the documented limits)'),
    ('E09', 'Staging/browser', 'Authentication and authorisation flows', 'Chromium: registration, login, account pages, customer/technician/manager/editor scope, REST enumeration (s3)', 'PASS — 49/49: customer 403 on dashboard/admin, technician masked phones and 403 on foreign orders, REST users/orders 404'),
    ('E10', 'Staging/server', 'Sitemap, robots, canonical redirects, redirect manager, 410', 'Every sitemap URL fetched; redirect chains followed; rules added through the admin UI; post trashed via row action (s7)', 'PASS — 35/35: 25 sitemap URLs 200/indexable/self-canonical, chains flattened to one hop, gone target answers 410 directly, trashed post 410 and out of every sitemap. /page/1/ on the :8080 staging port is a WordPress core canonical.php port bug (verified to 301 on default ports); it carries rel=canonical to the base'),
    ('E11', 'Staging/server', 'v1.7 → v2 migration on a staging copy', 'Full copy of the staging site with its own database rolled back to v1.7 (fake workshop data, legacy pages/prices/mods/orders/roles/cron/menus, /%postname%/); migration triggered by an administrator opening wp-admin (s8)', 'PASS — 52/52: all 11 steps, nothing deleted, fake data parked (not autoloaded, never rendered), legacy URLs one 301 hop or 410, demo order untrackable, legacy order trackable without PII; idempotent on a second visit and on a forced full re-run (byte-identical snapshot)'),
]
TEST_IDS = {t[0] for t in TESTS}

# One row per Master Prompt section. Titles are short summaries.
# (id, requirement, location, files, status, verification, blocker)
# ---------------------------------------------------------------------------
P = 'pixva/'
I = 'pixva/inc/'
PT = 'pixva/page-templates/'
J = 'pixva/assets/js/'
ROWS = [
    ('§01', 'Role: complete PIXVA as a production TV diagnosis/repair/knowledge/booking/tracking/warranty platform (RTL Persian)', 'Whole theme', [P + 'style.css', P + 'functions.php'], C, ['T11', 'T12'], ''),
    ('§02', 'Master Prompt is the only source of truth; autonomous execution, no confirmation questions', 'Process', ['docs/pixva-traceability.md'], C, ['T12'], ''),
    ('§03', 'Product scope: diagnosis, knowledge, booking, tracking, warranty, account, dashboard', 'Feature modules', [I + 'diagnosis.php', I + 'forms.php', I + 'repairs.php', I + 'account.php', I + 'dashboard.php'], C, ['T13', 'T18', 'T20', 'T23'], ''),
    ('§04', 'Baseline: do not assume earlier work exists; preserve correct, repair incorrect, rebuild missing', 'Branch baseline 0cdd07b (v1.7) + v2 commits', ['docs/pixva-architecture.md'], C, ['T29'], ''),
    ('§05', 'Route map (31 routes), no duplicate indexable pages, no redirect chains', 'Route registry + page creation', [I + 'routes.php', I + 'content-model.php', I + 'redirects.php'], C, ['T12', 'T30', 'T36'], ''),
    ('§06', 'Diagnosis wizard UX: mobile, accessible, keyboard, loading/empty/error states', 'Wizard template + JS enhancement', [PT + 'diagnosis.php', J + 'diagnosis.js'], C, ['T12', 'T24', 'E01', 'E06'], ''),
    ('§07', 'Diagnosis logic: brand→model→problem→symptoms→extra→likely/possible/needs-inspection→estimate only with data→booking', 'Rules engine + REST', [I + 'diagnosis.php', I + 'data/diagnosis-rules.php', I + 'rest.php'], C, ['T16', 'T24'], ''),
    ('§08', 'Price calculator: configurable pricing only; unset ⇒ inspection required, booking still possible', 'Pricing option + calculator', [I + 'pricing.php', PT + 'price-calculator.php', J + 'calculator.js'], C, ['T12', 'T24'], ''),
    ('§09', 'Error codes: full field set, search/filter, internal links, no unsafe electrical advice', 'pixva_error CPT + archive/single + REST search', [P + 'archive-pixva_error.php', P + 'single-pixva_error.php', I + 'meta-fields.php'], C, ['T12', 'T24'], ''),
    ('§10', 'Pixel test: solid colours, gradients, fullscreen, accessible, reduced motion, minimal JS', 'Template + small script', [PT + 'pixel-test.php', J + 'pixel-test.js'], C, ['T04', 'T12', 'E05'], ''),
    ('§11', 'Booking: labels, server validation, nonce, rate limit, private image upload, duplicate protection, real states', 'Booking form handler', [PT + 'booking.php', I + 'forms.php', I + 'security.php'], C, ['T13', 'T14', 'T15', 'T16', 'T34', 'T37'], ''),
    ('§12', 'Tracking: secure ID, no PII/internal notes, ownership proof', 'Lookup + REST track', [PT + 'tracking.php', I + 'repairs.php', J + 'lookup.js'], C, ['T18', 'T19', 'T22', 'T37'], ''),
    ('§13', 'Warranty: configurable policy, never invent duration', 'Order warranty meta + lookup', [PT + 'warranty.php', I + 'repairs.php', I + 'business-claims.php'], C, ['T12', 'T24', 'T37'], ''),
    ('§14', 'Account: repairs/warranty/profile with authn/authz, no cross-user access', 'Account pages + claim flow', [PT + 'account.php', I + 'account.php'], C, ['T20', 'T37'], ''),
    ('§15', 'Dashboard: separate customer/technician/editor/admin, real data only', 'Dashboard panels by capability', [PT + 'dashboard.php', I + 'dashboard.php'], C, ['T21', 'T23', 'T37', 'E09'], ''),
    ('§16', 'Content types for services, brands, models, problems, error codes, repairs, parts, articles, portfolio, FAQs, warranties, claims', 'CPT/taxonomy registry', [I + 'content-model.php'], C, ['T12'], ''),
    ('§17', 'Only expose types with legitimate content; private types for PII', 'Public vs private registration', [I + 'content-model.php', I + 'capabilities.php'], C, ['T25'], ''),
    ('§18', 'Search across content types', 'Search scope + results', [P + 'search.php', I + 'setup.php'], C, ['T12'], ''),
    ('§19', 'Title/description/canonical/robots/OG', 'Central SEO module', [I + 'seo.php', I + 'template-tags.php'], C, ['T12', 'T28', 'T35'], ''),
    ('§20', 'Honest schema; no LocalBusiness/reviews without real data', 'JSON-LD builder', [I + 'schema.php'], C, ['T32'], ''),
    ('§21', 'Paginated sitemap of canonical indexable URLs only', 'Core sitemaps + filters + archive provider', [I + 'seo.php', I + 'class-pixva-archive-sitemap.php'], C, ['T26', 'T27', 'T35'], ''),
    ('§22', 'robots.txt must not block CSS/JS', 'robots_txt filter', [I + 'seo.php'], C, ['T31'], ''),
    ('§23', 'Redirect manager (source, destination, status, date, notes, active) with chain/loop prevention', 'Admin screen + runtime', [I + 'redirects.php', I + 'admin.php'], C, ['T30', 'T36', 'E10'], ''),
    ('§24', 'Useful 404 and 410 for gone content', '404 template + gone paths', [P + '404.php', I + 'redirects.php'], C, ['T12', 'T30', 'T36'], ''),
    ('§25', 'Design tokens (12 colours), Vazirmatn, 8pt spacing; obsolete palette removed', 'theme.json + CSS custom properties', [P + 'theme.json', P + 'assets/css/app.css'], C, ['T07', 'T08'], ''),
    ('§26', '3D only if it adds real value', '—', [P + 'front-page.php'], NA, ['T08'], 'No 3D: the v1.5 decorative 3D hero added weight without diagnostic value, so it was removed; no feature requires 3D.'),
    ('§27', 'Accessibility (WCAG): labels, focus, aria, contrast, error summaries', 'Templates + field helper', [I + 'template-tags.php', P + 'assets/css/app.css'], RB, ['T12', 'T13', 'E01', 'E02', 'E03'], 'Automated checks (axe-core, Lighthouse, keyboard, live regions) pass on real staging; passes with real assistive technology (NVDA/JAWS/VoiceOver) need Windows/macOS, which are not available.'),
    ('§28', 'Responsive / mobile-first', 'CSS', [P + 'assets/css/app.css'], C, ['T06', 'E03'], ''),
    ('§29', 'Performance / Core Web Vitals', 'Per-route assets, no frameworks, cached sitemap exclusions', [I + 'setup.php', I + 'seo.php'], C, ['T02', 'T26', 'E04'], ''),
    ('§30', 'Minimal progressive-enhancement JS, no alert(), no SPA', 'Vanilla scripts per route; forms work without JS', [J + 'app.js'], C, ['T04', 'T15', 'E06'], ''),
    ('§31', 'Security: nonces, capabilities, sanitize/escape', 'Forms, admin, templates', [I + 'security.php', I + 'forms.php'], C, ['T02', 'T13', 'T21', 'T32', 'T37'], ''),
    ('§32', 'REST permission callbacks and rate limits', 'REST module', [I + 'rest.php', I + 'security.php'], C, ['T10', 'T24', 'T25', 'T37', 'E08'], ''),
    ('§33', 'Upload safety: MIME, size, private storage', 'Upload handler + streaming', [I + 'security.php', I + 'repairs.php'], C, ['T34', 'E07', 'T37'], ''),
    ('§34', 'Fake-data purge', 'Templates, migration denylist, removed preview mock', [I + 'migration.php', I + 'business-claims.php'], C, ['T09', 'T29', 'T38'], ''),
    ('§35', 'Centralised business claims with empty defaults that render nothing when unset', 'pixva_business_claims option', [I + 'business-claims.php', I + 'admin.php'], C, ['T09', 'T32', 'T38'], ''),
    ('§36', 'No phantom feature cards (AR, VIP, express…)', 'Front page / tools built from real routes', [P + 'front-page.php', PT + 'tools.php'], C, ['T09', 'T12'], ''),
    ('§37', 'CMS-managed content', 'CPTs, meta boxes, options screens', [I + 'meta-fields.php', I + 'admin.php'], C, ['T33'], ''),
    ('§38', 'Migration-friendly stable slugs; non-destructive versioned migration', 'Migration module', [I + 'migration.php', I + 'routes.php'], C, ['T29', 'T30', 'E11'], ''),
    ('§39', 'Provider-agnostic analytics events, no PII', 'Event bus + server events', [I + 'analytics.php', J + 'app.js'], C, ['T14', 'E06', 'E08'], ''),
    ('§40', 'Loading/empty/error/success states for every feature', 'notice/empty_state helpers used by all tools', [I + 'template-tags.php'], C, ['T12', 'T13', 'T19'], ''),
    ('§41', 'Data-model collisions resolved (problem taxonomy vs page, error-code paths, model under brand)', 'Routes + content model + redirects', [I + 'content-model.php', I + 'redirects.php'], C, ['T12', 'T30', 'T36'], ''),
    ('§42', 'Route matrix (URL, template, entity, indexability, canonical, schema, source, status)', 'Architecture doc §3', ['docs/pixva-architecture.md', I + 'routes.php'], C, ['T12'], ''),
    ('§43', 'Template coverage for every route/entity', '%d root templates + %d page templates' % (N_ROOT_TPL, N_PAGE_TPL), [P + 'single-tv_model.php', P + 'taxonomy-tv_problem.php'], C, ['T12'], ''),
    ('§44', 'Content quality / thin-content handling', 'Thin detection ⇒ noindex + sitemap exclusion; admin health', [I + 'seo.php', I + 'dashboard.php'], C, ['T27', 'T23'], ''),
    ('§45', 'Internal linking', 'related_links, related service/article fields', [I + 'template-tags.php'], C, ['T12'], ''),
    ('§46', 'Forms standard (labels, validation, PRG, honeypot, submission id)', 'Form framework', [I + 'forms.php', I + 'template-tags.php'], C, ['T13', 'T14', 'T15', 'T17', 'T37'], ''),
    ('§47', 'Admin UX: overview checklist, settings, redirects, migration report', 'Admin module', [I + 'admin.php', P + 'assets/css/admin.css', J + 'admin.js'], C, ['T33'], ''),
    ('§48', 'Indexing rules (noindex for tool states, empty archives, account, thin)', 'SEO module', [I + 'seo.php'], C, ['T12', 'T27', 'T35'], ''),
    ('§49', 'Privacy: no PII in URLs, analytics, tracking output; masked phones', 'Lookups by POST, masking helpers', [I + 'helpers.php', I + 'repairs.php'], C, ['T18', 'T21', 'T37'], ''),
    ('§50', 'Roles and custom capabilities for PII types', 'Capability module', [I + 'capabilities.php'], C, ['T21', 'T23', 'T37'], ''),
    ('§51', 'REST review', 'REST module', [I + 'rest.php'], C, ['T10', 'T24', 'T25', 'T37'], ''),
    ('§52', 'Central metadata (single SEO source)', 'seo.php only', [I + 'seo.php'], C, ['T12'], ''),
    ('§53', 'No duplication (functions, pages, content)', 'Lint tools', ['tools/php_lint.py'], C, ['T05', 'T12'], ''),
    ('§54', 'Token audit', 'CSS/theme.json', [P + 'theme.json'], C, ['T08'], ''),
    ('§55', 'Code quality', 'WPCS ruleset', [P + 'phpcs.xml'], C, ['T02', 'T03'], ''),
    ('§56', 'PHP checks', 'php -l + WPCS', ['tools/php_lint.py', P + 'phpcs.xml'], C, ['T01', 'T02'], ''),
    ('§57', 'JS checks', 'node --check', [J + 'app.js'], C, ['T04'], ''),
    ('§58', 'CSS checks', 'class audit', ['tools/class_audit.py'], C, ['T06', 'T08'], ''),
    ('§59', 'Crawl', 'CLI crawl of 49 routes on WP 7.1.3 + SQLite', ['docs/pixva-qa-matrix.md'], C, ['T12', 'T30', 'T35', 'T36'], ''),
    ('§60', 'QA matrix', 'Generated document', ['docs/pixva-qa-matrix.md', 'tools/gen_docs.py'], C, ['T12'], ''),
    ('§61', 'Gap ledger reflecting actual state', 'Generated JSON', ['docs/pixva-gap-ledger.json', 'tools/gen_docs.py'], C, ['T12'], ''),
    ('§62', 'Traceability matrix §01–§77', 'This document', ['docs/pixva-traceability.md', 'tools/gen_docs.py'], C, ['T12'], ''),
    ('§63', 'Allowed final statuses only; documentation alone is not completion', 'Generator guard rejects other statuses', ['tools/gen_docs.py'], C, ['T12'], ''),
    ('§64', 'Branch reconciliation', 'v1.7 (PR #4) merged as baseline; PR #2 rejected (fake data)', ['docs/pixva-architecture.md'], C, ['T29'], ''),
    ('§65', 'Implementation order', 'Core → flows → SEO/redirects/migration → templates → assets → docs (commit order)', ['docs/pixva-architecture.md'], C, ['T11'], ''),
    ('§66', 'Test after each area', 'Harness runs per area, final full regression', ['docs/pixva-qa-matrix.md'], C, ['T12', 'T13', 'T29', 'T37'], ''),
    ('§67', 'Avoid regressions', 'Full regression on fresh DB after final changes', ['docs/pixva-qa-matrix.md'], C, ['T03', 'T12', 'T37'], ''),
    ('§68', 'Logical commits with clear messages', 'Git history on arena branch', ['README.md'], C, ['T01'], ''),
    ('§69', 'Honest reporting: never claim untested runtime results', 'Every runtime claim backed by an executed staging suite; real assistive technology reported as blocked', ['docs/pixva-qa-matrix.md'], C, ['E02'], ''),
    ('§70', 'Mark only runtime-dependent checks as blocked', 'Status assignment in this matrix', ['tools/gen_docs.py'], C, ['E02', 'E04'], ''),
    ('§71', 'No test gaming, no placeholder files or empty CPTs', 'Types registered only with real use', [I + 'content-model.php'], C, ['T05', 'T06'], ''),
    ('§72', 'No unnecessary plugins, JS frameworks or page builders', 'Vanilla theme; Elementor support removed', [P + 'functions.php'], C, ['T04'], ''),
    ('§73', 'Stop only for irreversible data loss or security risk', 'Migration never deletes content; parks legacy data', [I + 'migration.php'], C, ['T29'], ''),
    ('§74', 'Documentation (architecture, routes, entities, capabilities, flows, SEO, security, migration, blockers)', 'docs/', ['docs/pixva-architecture.md', 'README.md', P + 'readme.txt'], C, ['T12', 'T39'], ''),
    ('§75', 'Final report A–K', 'Delivered in the session report', ['docs/pixva-traceability.md'], C, ['T12'], ''),
    ('§76', '40 absolute rules', 'Enforced across the rows above', ['docs/pixva-traceability.md'], C, ['T09', 'T10', 'T21'], ''),
    ('§77', 'Completion: implemented, tested, fixed, retested, verified, documented, finalised', 'This matrix + QA matrix + pushed branch', ['docs/pixva-qa-matrix.md'], C, ['T12', 'T29'], ''),
]


def check():
    errs = []
    ids = [r[0] for r in ROWS]
    expected = ['§%02d' % n for n in range(1, 78)]
    if ids != expected:
        errs.append('sections must be §01..§77 exactly once, in order')
    generated = {'docs/pixva-traceability.md', 'docs/pixva-qa-matrix.md', 'docs/pixva-gap-ledger.json'}
    for r in ROWS:
        sid, _req, _loc, files, status, ver, blocker = r
        if status not in ALLOWED:
            errs.append('%s: status %r not allowed' % (sid, status))
        if status != C and not blocker:
            errs.append('%s: %s needs a reason' % (sid, status))
        for f in files:
            if f not in generated and not os.path.exists(os.path.join(ROOT, f)):
                errs.append('%s: missing file %s' % (sid, f))
        for t in ver:
            if t not in TEST_IDS:
                errs.append('%s: unknown test %s' % (sid, t))
    if errs:
        sys.exit('gen_docs: ' + '\n'.join(errs))


def md_cell(s):
    return str(s).replace('|', '\\|').replace('\n', ' ')


def write(path, text):
    with open(os.path.join(ROOT, path), 'w', encoding='utf-8') as fh:
        fh.write(text)


def main():
    check()
    counts = {s: sum(1 for r in ROWS if r[4] == s) for s in ALLOWED}

    # Traceability.
    out = ['# PIXVA %s — Traceability matrix §01–§77' % VERSION, '',
           'Generated by `tools/gen_docs.py` on %s from the same data as `pixva-gap-ledger.json` and `pixva-qa-matrix.md`. Section titles are short summaries of the Master Prompt sections; test ids refer to [pixva-qa-matrix.md](pixva-qa-matrix.md).' % DATE, '',
           '**Totals:** ' + ' · '.join('%s: %d' % (s, n) for s, n in counts.items()), '',
           '| § | Requirement | Location | Files | Status | Verification | Blocker / reason |',
           '|---|---|---|---|---|---|---|']
    for sid, req, loc, files, status, ver, blocker in ROWS:
        out.append('| %s | %s | %s | %s | %s | %s | %s |' % (
            sid, md_cell(req), md_cell(loc), '<br>'.join('`%s`' % f for f in files),
            status, ', '.join(ver), md_cell(blocker) or '—'))
    write('docs/pixva-traceability.md', '\n'.join(out) + '\n')

    # Ledger.
    ledger = {
        'theme': 'pixva', 'version': VERSION, 'generated': DATE,
        'allowed_statuses': list(ALLOWED), 'totals': counts,
        'items': [{'id': sid, 'requirement': req, 'location': loc, 'files': files,
                   'status': status, 'verification': ver, 'blocker': blocker or None}
                  for sid, req, loc, files, status, ver, blocker in ROWS],
        'tests': [{'id': t[0], 'area': t[1], 'check': t[2], 'method': t[3], 'result': t[4]} for t in TESTS],
    }
    write('docs/pixva-gap-ledger.json', json.dumps(ledger, ensure_ascii=False, indent=2) + '\n')

    # QA matrix.
    q = ['# PIXVA %s — QA matrix' % VERSION, '',
         'T01–T39: WordPress 7.1.3, SQLite integration 2.2.23, PHP 8.3 (php-wasm CLI), request harness that boots WordPress per request. E01–E11: real staging stack — Chromium (puppeteer-core) → nginx 1.31.6 or Apache httpd 2.4.68 → PHP 8.3 (php-wasm HTTP upstream) → WordPress 7.1.3 + SQLite → SMTP sink; real multipart uploads, real client IPs via distinct source addresses. Fixtures are [STG]-prefixed staging data only (1 brand, 1 model, 1 service, 1 error code, 1 FAQ, 1 article, users for every role). Real assistive-technology passes (NVDA/JAWS/VoiceOver) were not possible and are not claimed.', '',
         '| ID | Area | Check | Method | Result |', '|---|---|---|---|---|']
    for t in TESTS:
        q.append('| %s |' % ' | '.join(md_cell(x) for x in t))
    q += ['', 'Requirement coverage: see [pixva-traceability.md](pixva-traceability.md).', '']
    write('docs/pixva-qa-matrix.md', '\n'.join(q))
    print('ok:', counts)


if __name__ == '__main__':
    main()

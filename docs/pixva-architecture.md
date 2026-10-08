# PIXVA v2.0.0 — Architecture

Theme: `pixva/` (WordPress ≥ 6.5, PHP ≥ 7.4, tested on WordPress 7.1.3 / PHP 8.3 + SQLite).
No plugins, no jQuery, no JS framework, no page builder. RTL Persian, Vazirmatn (OFL, self-hosted).

Product loop: **problem → identify → diagnose → understand → estimate (only with configured data) → book → repair → track → warranty → return to knowledge/tools.**

## 1. Baseline and reconciliation

| Item | Decision |
| --- | --- |
| Baseline | `main` + merge of `origin/pr4` (v1.7, the most complete product loop) → commit `3570ad2`. |
| Rejected | `origin/pr2` (older, superseded by pr4, conflicting architecture). |
| v1.x modules | Removed after review: AI bot, B2B hub, parts-stock "inventory", Elementor support, hard-coded pricing engine, demo activation (fake order `PXV-DEMO-2401`, fake phones, fake 180-day warranty), customizer sections with reviews/statistics. Data they created is migrated, never deleted (§7). |
| Earlier-claimed work | Business-claims layer, gap ledger, gone paths, sitemap pagination, REST rate limiting did not exist on any branch; rebuilt from the specification. |

## 2. Modules (`pixva/inc/`)

| File | Responsibility |
| --- | --- |
| `helpers.php` | Request accessors (unslash + sanitize), Persian digits, mobile normalisation/masking, Jalali dates, icons, kses, TOC, `pixva_json_meta()` (slash-safe JSON meta). |
| `routes.php` | **Single route registry** (`pixva_routes()`): path, source (front/home/page/archive/redirect), template, indexability, schema. Pages are resolved by `_pixva_route` meta, never by ID. `pixva_route_url()` everywhere. |
| `content-model.php` | CPTs/taxonomies, rewrite rules (`/brands/{brand}/{model}/`), problem-term meta. |
| `meta-fields.php` | Declarative meta schema → `register_post_meta` + meta boxes + nonce/cap-checked save. |
| `capabilities.php` | Roles and custom capabilities (§5). |
| `security.php` | Headers, REST user hiding, author-archive block, rate limiter, honeypot, submission IDs, private uploads, mail header hardening. |
| `business-claims.php` | Central claims store (`pixva_business_claims`), empty defaults, `pixva_claim()` / `pixva_has_claim()`, known-fabricated blocklist. |
| `pricing.php` | Configurable estimate table (`pixva_pricing`); `available:false` when unset. |
| `diagnosis.php` + `data/diagnosis-rules.php` | Rule data (problems → symptoms → weighted causes, safe checks, danger flags) and scoring. |
| `forms.php` | Booking, contact, register, profile, claim-order, order-update: PRG + AJAX via one dispatcher. |
| `repairs.php` | Order CPT logic: codes, status history, warranty application, public view, admin box, private photo streaming. |
| `account.php` / `dashboard.php` | Account sub-pages, login events, dashboard panels by capability. |
| `rest.php` | `/pixva/v1/*` endpoints (§8). |
| `seo.php` / `schema.php` / `class-pixva-archive-sitemap.php` | Metadata, robots, canonical, OG/Twitter, JSON-LD, sitemaps. |
| `redirects.php` | Redirect manager, system legacy redirects, 410 gone paths. |
| `analytics.php` | Event allow-list, server-side events, `PIXVA.events/props`. |
| `admin.php` | Settings (business, pricing), redirects UI, setup checklist, migration report. |
| `migration.php` | Idempotent v1.x → v2 upgrade (`pixva_db_version`). |

## 3. Routes (§05, §42)

| URL | Source | Template | Entity | Index | Schema |
| --- | --- | --- | --- | --- | --- |
| `/` | front page | `front-page.php` | page `home` | yes | WebSite (+SearchAction), Organization (LocalBusiness only with real claims) |
| `/services/`, `/services/{service}/` | CPT archive/single | `archive-tv_services.php`, `single-tv_services.php` | `tv_services` | yes when non-empty | CollectionPage / Service |
| `/brands/`, `/brands/{brand}/` | CPT archive/single | `archive-tv_brands.php`, `single-tv_brands.php` | `tv_brands` | yes when non-empty | CollectionPage / WebPage |
| `/brands/{brand}/{model}/` | custom rewrite | `single-tv_model.php` | `tv_model` (`_pixva_brand_id`) | yes | WebPage + Breadcrumb |
| `/problems/` | page | `page-templates/problems.php` | route page | yes | CollectionPage |
| `/problems/{problem}/` | taxonomy | `taxonomy-tv_problem.php` | `tv_problem` | yes unless thin | CollectionPage |
| `/tools/` | page | `page-templates/tools.php` | route page | yes | CollectionPage |
| `/tools/diagnosis/` | page | `page-templates/diagnosis.php` | rules | yes (step/result URLs noindex) | WebApplication |
| `/tools/price-calculator/` | page | `page-templates/price-calculator.php` | pricing | yes (query states noindex) | WebApplication |
| `/tools/pixel-test/` | page | `page-templates/pixel-test.php` | — | yes | WebApplication |
| `/tools/error-codes/` | **301 → `/error-codes/`** | — | — | no | — |
| `/error-codes/`, `/error-codes/{code}/` | CPT archive/single | `archive-pixva_error.php`, `single-pixva_error.php` | `pixva_error` | yes when non-empty; search states noindex | CollectionPage / TechArticle-style WebPage |
| `/repair/`, `/repair/book/` | pages | `repair.php`, `booking.php` | route pages | yes (prefilled `?from=` noindex) | WebPage |
| `/tracking/`, `/warranty/` | pages | `tracking.php`, `warranty.php` | lookups | yes | WebPage |
| `/portfolio/` | CPT archive | `archive-repair_cases.php`, `single-repair_cases.php` | `repair_cases` | yes when non-empty | CollectionPage |
| `/blog/`, `/blog/{article}/`, `/blog/category/{category}/` | posts page / posts / category | `home.php`, `single.php`, `archive.php` | `post`, `category` | yes (empty categories noindex) | Blog / Article |
| `/about/`, `/contact/`, `/faq/` | pages | `about.php`, `contact.php`, `faq.php` | route pages | yes | AboutPage / ContactPage / FAQPage (only with FAQ entries) |
| `/account/`, `/account/repairs/`, `/account/warranty/`, `/account/profile/` | pages | `page-templates/account.php` | user | **noindex** | — |
| `/dashboard/` | page | `page-templates/dashboard.php` | role panels | **noindex**, 403 for customers | — |

**Collision decisions**

- `/tools/error-codes/` and `/error-codes/` have the same intent → one canonical (`/error-codes/`), the other is a single-hop 301 (no duplicate indexable page).
- v1.x pages that collided with archives (`/error-codes/` page, `/diagnosis/`, `/calculator/`, `/rates/`, `/technician/`, `/client-hub/`) were drafted and get single-hop 301s to their v2 routes; `/b2b/`, `/parts-stock/`, `/tech/*` answer 410.
- Old `/%postname%/` post URLs 301 to `/blog/{slug}/`; old `/category/{slug}/` and `/tag/{slug}/` 301 to the `/blog/` bases (only when the term exists and the old base is no longer live).
- URLs never depend on page IDs: pages carry `_pixva_route`, models resolve by brand slug + model slug, a model requested under the wrong brand 301s to its canonical brand path.

## 4. Entities (§16–17, §41)

| Entity | Type | Public | Notes |
| --- | --- | --- | --- |
| Service | `tv_services` | yes | `/services/` |
| Brand | `tv_brands` | yes | `/brands/` |
| TV model | `tv_model` | yes | code, size, panel, year, common issues, brand relation |
| Problem | taxonomy `tv_problem` | yes | symptoms, safe checks, related diagnosis rule, related service |
| Error code | `pixva_error` | yes | §09 fields: code, brand, models, meaning, symptoms, causes, severity, safe checks, professional action, related service, related article |
| Portfolio | `repair_cases` | yes | before/after (no fabricated cases; v1 demo case drafted) |
| Article | `post` + `category` | yes | under `/blog/` |
| FAQ | `pixva_faq` | rendered on `/faq/` only | FAQPage schema only when entries exist |
| Repair order | `pixva_orders` | **private** | custom capability type, not in REST, PII |
| Inbox message | `pixva_inbox` | private | contact form |
| Part | `pixva_part` | private (staff) | no public inventory claims |
| Warranty | order meta + policy claim | via lookup/account | duration only from configured policy or manual entry |
| Business claims | option | rendered only when set | §35 |
| Branches | — | INTENTIONALLY_NOT_APPLICABLE | no real branch data exists; single address is a claim |

Order meta: `_pixva_order_code/name/phone/brand/brand_id/model/problem/description/mode/address/time/photos/diagnosis/status/steps/notes/estimate`, `_pixva_phone_hash`, `_pixva_customer_id`, `_pixva_technician_id`, `_pixva_warranty_start/until/source`.
Statuses: new, received, diagnosed, waiting, parts, repairing, testing, ready, delivered, cancelled.

## 5. Roles and capabilities (§15, §50)

| Role | Capabilities |
| --- | --- |
| `pixva_customer` | `read` only; sees own orders via `_pixva_customer_id` (attached only through claim with code + phone). |
| `pixva_technician` | `pixva_view_dashboard`, `pixva_work_orders`; sees **assigned** orders only, phone masked, can update status + public/internal notes (meta cap `pixva_work_order`). |
| `editor` | `pixva_view_dashboard`, `pixva_manage_redirects`, `pixva_view_content_health`; **no** order/PII access. |
| `pixva_manager` | full `pixva_orders` capability set, `pixva_manage_orders`, `pixva_view_order_pii`, `pixva_export_orders`, `pixva_manage_settings`. |
| `administrator` | manager set + redirects + content health. |

Meta caps `pixva_view_order` / `pixva_work_order` map per order (owner, assigned technician, manager). Orders use `capability_type = pixva_order` so editors do not inherit access.

## 6. Flows

**Diagnosis (§07)** — Steps: device (brand, model with datalist) → problem → symptoms → extra info (age, events) → result. Works as GET pages without JS (noindexed states); `diagnosis.js` enhances with step validation and model loading. Result lists causes with *likely / possible / needs inspection*, safe checks, danger stop-notice, matching error codes, an estimate **only** when pricing is configured, then a booking CTA prefilled with brand/model/problem/symptoms. Events: `diagnosis_started/completed/abandoned`.

**Price calculator (§08)** — Reads `pixva_pricing`; with no table it states that pricing requires inspection and links to booking. No hard-coded floor.

**Booking (§11)** — Labelled fields, server validation (Iranian mobile, required consent), nonce `_pixva_nonce`, honeypot `pixva_hp`, per-IP rate limit (6/h), submission id `_pixva_sid` (a duplicate returns the original result, a concurrent one returns 409), optional ≤3 images (JPG/PNG/WebP, ≤5 MB, magic-byte sniffing, random names, `uploads/pixva-private/` with deny rules, streamed only after capability + nonce check). Without JS: PRG (303 → `?pixva_r=` one-time token). With JS: AJAX, JSON `{message, code, tracking_url, errors}`, 422 on validation.

**Tracking / warranty (§12–13)** — POST only (phone never in URL). Code + phone proof, same 404 for unknown code or wrong phone, per-IP limiter (20/10 min) and per-code lock after 5 failures. Output: status, label, history with public notes, device label, warranty state. Never name, phone, address, internal notes, photos.

**Account (§14)** — Login / registration (if enabled), claim order by code + phone, repairs, warranties, profile (email uniqueness enforced). All checks server-side.

**Dashboard (§15)** — Panels by capability: manager (order counts and recent orders), technician (assigned orders + update form), editor (content health). Customers receive HTTP 403.

## 7. Migration (§38, §41)

`pixva_maybe_migrate()` (admin, `switch_themes`) and `after_switch_theme`; idempotent (`pixva_db_version = 2.0.0`), never deletes:
permalink `/blog/%postname%/`, route pages created or retargeted, duplicates drafted with reason, v1 fake workshop settings not imported (kept in `pixva_legacy_*`), menu items retargeted, demo order excluded, v1 status timestamps converted to history, `pixva_b2b_client` users → `pixva_customer`, the site tagline auto-written by v1 activation (unverified “specialist” claim) cleared and parked in `pixva_legacy_blogdescription`, fabricated warranty/time claims stripped from sample content (revisions keep the original). Log: **Pixva → گزارش ارتقا**.

## 8. REST (`/pixva/v1`, §51)

| Route | Method | Access | Limit | PII |
| --- | --- | --- | --- | --- |
| `/diagnosis` | POST | public | 30 / 10 min / IP | no |
| `/estimate` | POST | public | 60 / 10 min | no |
| `/track` | POST | code + phone proof | 20 / 10 min + per-code lock | no (masked public view) |
| `/warranty` | POST | code + phone proof | same | no |
| `/error-codes` | GET | public | 120 / 10 min | no |
| `/models?brand=` | GET | public | 120 / 10 min | no |

All responses, including errors, are `Cache-Control: no-store, private`. `/wp/v2/users` is available only to staff with `edit_posts`/`list_users` (block editor); other logged-in users keep only `/wp/v2/users/me` (own record), guests get none. oEmbed responses carry no `author_url` (it contains the login slug); `pixva_orders/inbox/part` not in REST.

## 9. SEO (§19–24, §48, §52)

- One decision function for robots: noindex for account/dashboard/search/404/410, query states (diagnosis steps, calculator inputs, error-code search, booking prefill), empty archives, thin posts/terms, per-post `_pixva_seo_noindex`.
- Canonical only on indexable pages; titles/descriptions per route/entity; OG + Twitter tags. The front-page description comes from `pixva_front_lead()` (the front page excerpt, or a factual sentence about what the site does — never a claim). Without a tagline the title uses a factual descriptor until the owner sets one.
- Thin content: WordPress’ own sample content while untouched (“Hello world!”, “Sample Page”; indexable again once the owner edits it), posts with < 300 characters of text, and thin services/brands/models/problems, are `noindex, follow` and kept out of the sitemap. The admin overview checklist flags untouched core sample content (`pixva_core_sample_content()`); the theme never deletes it.
- Schema only from real data: WebSite + SearchAction and Organization (name/url/logo/sameAs) on every page, upgraded to LocalBusiness only when real address/phone claims exist, BreadcrumbList, Article, Service, FAQPage only with entries. Never AggregateRating, Review, Offer/price.
- Sitemaps: core `wp-sitemap.xml` (paginated by core at 2 000 URLs), excludes noindex/thin posts, redirect sources and gone paths (exclusion list computed in batches of 100 without an upper cap, cached in the `pixva_sitemap_excluded` transient keyed by theme version and flushed on any post/meta/redirect/gone-path change, theme switch or update); custom provider for CPT archive URLs (only non-empty).
- robots.txt: only `/wp-admin/` disallowed (`admin-ajax.php` allowed); CSS/JS/images crawlable.
- Redirect manager: source, destination, status (301/302/307/308/410), date, notes, active; flattens chains on save (A→B + B→C becomes A→C), rejects loops, self-redirects and protected route paths; a redirect whose target is gone answers 410 directly.
- Query-var URLs of public custom types (`/?tv_services=slug`, bare `/?post_type=tv_services`) answer one 301 to the pretty permalink (other parameters kept); core already handles `?p=`, `?page_id=`, `?cat=` and taxonomy query vars.
- 404: explanation, search, key routes, suggestions. 410 for intentionally removed content (tracked on trash/unpublish of public types).

## 10. Analytics (§39)

`app.js` exposes `pixva.track()`; events are allow-listed server-side (`search, diagnosis_started, diagnosis_completed, diagnosis_abandoned, price_calculator_started, price_calculator_completed, pixel_test_started, error_code_search, booking_started, booking_submitted, booking_failed, tracking_viewed, warranty_lookup, account_login, account_registration, cta_click`), properties are allow-listed and coerced to short slugs; pushed to `window.dataLayer` as `pixva_*` and dispatched as `pixva:track` DOM event. Server-side events (search, login, registration) are queued and emitted on the next page. No PII is ever sent.

## 11. Design system (§25)

Tokens in `style.css` and `theme.json` (exactly the 12 approved colours; derived tints via `color-mix`), Vazirmatn variable font, 8-pt spacing scale (`--s-half` 4px, `--s-1`…`--s-10`), logical properties for RTL. Documented exceptions: pixel-test colours (pure RGB is the function of the tool), `theme-color` meta and `admin.css` (no access to front-end CSS variables, uses token hex values), accent buttons use deep text because white on `#FF5C35` fails WCAG AA.

## 12. Security summary (§31–33)

Nonces on all forms and admin actions, capability or ownership checks on all writes, `wp_unslash` + sanitisation on all input, escaping on all output, rate limits on public writes and lookups, honeypot, duplicate-submit protection, private uploads, security headers (`X-Content-Type-Options`, `Referrer-Policy`, `X-Frame-Options`, `Permissions-Policy`), generic login errors, author archives blocked, user enumeration closed (REST users, oEmbed author URL, users sitemap), mail header injection stripped. Rate limits key on `REMOTE_ADDR` (not spoofable via headers); behind a reverse proxy/CDN restore the real client IP in the web server or use the `pixva_client_ip` filter, otherwise all visitors share one bucket.

## 13. Code quality

PHP 8.3 `php -l` on all files, WordPress Coding Standards 3.4.1 with the theme’s `phpcs.xml` (0 errors; 14 reviewed performance warnings on bounded queries), `node --check` on all scripts, `tools/class_audit.py` for CSS coverage. The v1.6 static `preview/` mock was removed because it contained the fabricated phone number and 180-day warranty. Requirement-level status: [`pixva-traceability.md`](pixva-traceability.md), [`pixva-gap-ledger.json`](pixva-gap-ledger.json), [`pixva-qa-matrix.md`](pixva-qa-matrix.md), all generated by `tools/gen_docs.py`.

## 14. Staging verification and deployment notes

Browser and server checks were executed on a real staging stack (Chromium → nginx 1.31.6 or Apache httpd 2.4.68 → PHP 8.3 → WordPress 7.1.3 + SQLite → SMTP), suites E01–E11 in [`pixva-qa-matrix.md`](pixva-qa-matrix.md): responsive/RTL on every route, keyboard, axe-core, Lighthouse/CWV (lab), pixel-test fullscreen and reduced motion, JS flows and analytics payloads, real multipart uploads, private-store protection, staff mail, reverse-proxy client IPs and rate limits, sitemap/redirects/410, and the v1.7 → v2 migration on a staging copy.

**Remaining blocker:** passes with real assistive technology (NVDA/JAWS/VoiceOver) need Windows/macOS and were not run; automated accessibility checks pass.

**Deployment requirements**

- **Nginx** must add: `location ~* /wp-content/uploads/pixva-private/ { deny all; }`.
- **Apache** needs `AllowOverride` to include `AuthConfig` (or `All`) for `wp-content/uploads/`; the theme writes `uploads/pixva-private/.htaccess` with `<IfModule mod_authz_core.c>` rules that work with and without `mod_access_compat`. With `AllowOverride None`, add the same deny in the server config.
- **Reverse proxy / CDN:** set the real client address in a header the proxy overwrites (for example `X-Real-IP $remote_addr`) and return it from the `pixva_client_ip` filter; never trust the left-most `X-Forwarded-For`. Without it all visitors share the proxy's rate-limit bucket.
- Sites served on a non-default port are affected by a WordPress core canonical bug (`/page/1/` is not redirected); those pages still carry `rel=canonical` to the base URL.

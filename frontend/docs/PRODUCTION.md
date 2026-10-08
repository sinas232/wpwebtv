# Production guide: تی‌وی دکتر frontend

Status legend used throughout: **Completed** (implemented and tested here), **Partial** (implemented with a stated gap), **Not implemented**, **Needs business input**, **Needs production credentials**, **Needs legal review**.

## 1. Architecture

```
Browser ──► Next.js (App Router, Node runtime)
              ├─ Pages: SSG/ISR-friendly server components; client islands for interactive parts
              ├─ /api/leads     → validation → MediaStorage.put → LeadRepository.save → after(): CRM webhook + SMS
              ├─ /api/tracking  → LeadRepository.findByCode (code + mobile required)
              ├─ /api/events    → allowlisted analytics events → appendEvent (dev sink)
              └─ lib/cms.ts     → WordPress headless REST (WP_API_URL) or seed fallback
WordPress (pixva theme, headless CMS/API)
```

Server boundaries (all under `src/lib/server/`):

| Module | Responsibility | Production adapter |
|---|---|---|
| `config.ts` | Only place that reads secrets/storage settings; production safety checks | — |
| `lead-repository.ts` (`LeadRepository`) | Save / find leads | Postgres adapter **Not implemented** (declared as `LEAD_STORE=postgres`, returns a clear error) |
| `media-storage.ts` (`MediaStorage`) | Customer photos/videos | `S3MediaStorage` (any S3-compatible store) **Partial**: written and type-checked; not yet exercised against a live bucket |
| `crm-webhook.ts` | Signed lead delivery, timeout, retry, redacted logs | Generic HMAC webhook **Completed** (tested with an injected transport); CRM vendor mapping **Needs production credentials** |
| `sms.ts` (`SmsProvider`) | Lead received / status changed / visit scheduled | `NullSmsProvider` only **Not implemented** for any real gateway; **Needs production credentials and a provider contract** |
| `file-signature.ts` | Magic-byte validation of every upload | **Completed** |
| `rate-limit.ts` | Sliding-window limiter | In-memory **Partial** (single process only; use shared store for multi-instance) |
| `log.ts` | Structured logs with redaction | **Completed** |

Routes depend only on these interfaces, so swapping an adapter does not change HTTP behaviour.

## 2. Environment variables

See `.env.example` (names only). No secret value is in the repository.

| Variable | Required in production | Purpose |
|---|---|---|
| `NEXT_PUBLIC_SITE_URL` | Yes | Canonical URLs, sitemap, notification links |
| `LEAD_STORE` | Yes | `file` today; `postgres` once the adapter exists |
| `ALLOW_LOCAL_STORAGE_IN_PRODUCTION` | Only for single-server installs | `1` allows file leads and local uploads in production. Without it, production **refuses** to accept leads and returns 503 instead of writing to an ephemeral disk |
| `MEDIA_STORAGE` | Yes | `s3` for production |
| `S3_BUCKET`, `S3_REGION`, `S3_ACCESS_KEY_ID`, `S3_SECRET_ACCESS_KEY` | With `s3` | Credentials from the secret manager. `S3_ENDPOINT` for non-AWS providers, `S3_PREFIX` optional |
| `CRM_WEBHOOK_URL` | Optional | HTTPS endpoint. Leads save without it |
| `CRM_WEBHOOK_SECRET` | With the URL | HMAC key. Required; the webhook refuses to send without it |
| `CRM_WEBHOOK_TIMEOUT_MS`, `CRM_WEBHOOK_MAX_ATTEMPTS` | No | Defaults 5000 ms and 3 attempts (clamped) |
| `TRUST_PROXY` | Yes behind a proxy | Default `1`. The proxy must overwrite `X-Forwarded-For`. Set `0` only when directly exposed |
| `NEXT_PUBLIC_GTM_ID`, `NEXT_PUBLIC_GA_ID` | Optional | Analytics. Loaded only when set |
| `NEXT_PUBLIC_PHONE`, `NEXT_PUBLIC_WHATSAPP`, `NEXT_PUBLIC_TELEGRAM`, `NEXT_PUBLIC_EMAIL` | Business input | Contact channels render only when set |
| `WP_API_URL` | Yes once WordPress is live | Headless content source; seed fallback when unset |

**Rule:** `NEXT_PUBLIC_*` values are visible to every visitor. Secrets never use that prefix.

## 3. Lead data contract

`Lead` (`src/lib/leads.ts`): `id`, `trackingCode`, `createdAt`, `status`, `name`, `mobile`, `city`, `area`, `address`, `brand`, `model`, `size`, `problem`, `description`, `standbyLight`, `preferredTime`, `source`, `utm.*`, `landingPage`, `media[]` (`kind`, sanitised `name`, `size`, `mime`, `storedAs` key), **`diagnosis`** (symptom, cause, answers, or `null`), `technician` (`assignedTo`, `visitAt`), `repair` (`report`, `parts`, `invoiceId`).

The `technician` and `repair` fields are written only by a future technician workflow. They are empty today and nothing is fabricated.

Webhook body: `{ "event": "lead.created", "sentAt": ISO, "data": <Lead> }`. Headers: `x-tv-timestamp` (unix seconds), `x-tv-signature: sha256=HMAC_SHA256(secret, "<timestamp>.<body>")`, `idempotency-key: <lead id>`.

Receiver checklist: verify the signature with a constant-time compare, reject timestamps older than 5 minutes, de-duplicate on `idempotency-key`, return 2xx only after the lead is durably stored.

## 4. Technician data model (architecture, no UI yet)

Target relational model for the Postgres adapter. **Not implemented.** Shown so the contract is reviewable now.

```
Technician 1─* Appointment *─1 Lead
Lead 1─* Attachment            (object key, kind, mime, size, uploaded_by)
Lead 1─* Note                  (author_id → Technician or staff, body, created_at)
Lead 1─0..1 Repair             (report, status, completed_at)
Repair 1─* RepairPart          (part_id, qty, unit_cost, source)
Repair 0..1─1 Invoice          (number, issued_at, total, currency=IRR, status)
Part (catalogue) 1─* RepairPart
Lead.status ∈ received | reviewing | technician_assigned | visit_scheduled | dispatched | in_repair | completed
Appointment: scheduled_at, window, technician_id, lead_id, status ∈ proposed | confirmed | cancelled | done
```

Notes: `Invoice` amounts are never shown to customers before technician confirmation. Attachments reference object-storage keys, never public URLs.

## 5. Content (WordPress headless CMS)

| Content | Source in production | Status |
|---|---|---|
| Services, Brands, Problems, Cities, Areas, FAQs, Blog, SEO metadata, Homepage sections | WordPress REST via `WP_API_URL` (`lib/cms.ts`) | Partial: adapter present; field-level mapping against the live WP schema needs a staging check |
| Testimonials / reviews | CMS, only when a real review exists | **Needs business input**. Empty state shown; none fabricated |
| Pricing | CMS, always `needs_review` until approved | **Needs business input** |
| Service availability per city | CMS `coverage` field. `pending` cities are `noindex` and left out of the sitemap | **Needs business input** |
| Seed content | `src/lib/content/seed.ts`. **Development fallback only** | Completed |

Fallback rule: seed content is used only when `WP_API_URL` is unset or the request fails. Production should alert on fallback use rather than silently serving seed content. **Not implemented** (alerting).

## 6. Security checklist

| Control | Status | Notes |
|---|---|---|
| Input validation (fields) | Completed | Server-side whitelists; Persian per-field messages |
| Honeypot | Completed | Bot submissions get a fake success and store nothing |
| Rate limiting: leads 6/10 min, tracking 20/10 min | Partial | In-memory only; move to shared store before scaling out |
| File type by magic bytes | Completed | MIME and extension are not trusted |
| Upload size limits | Completed | 8 MB per photo, 50 MB per video, 80 MB total, 6 files |
| Path traversal in storage keys | Completed | Lead id must be a UUID; names are sanitised |
| XSS | Completed | React escapes output; no `dangerouslySetInnerHTML` except the static JSON-LD scripts |
| CSRF | Partial | The lead, tracking and event POST endpoints do not require a session cookie, so there is no ambient credential to forge. They still accept cross-site POSTs. Add an `Origin` / `Sec-Fetch-Site` allowlist check before launch (**not implemented**) |
| Webhook authentication | Completed | HMAC-SHA256 with timestamp; HTTPS required in production |
| Secret management | Completed for code | No secret in source. Storage of secrets in production (secret manager) is **Needs production credentials** |
| API error sanitisation | Completed | Internal errors return a generic Persian message. Only error class names are logged |
| No PII in logs | Completed | `redact()` masks name, mobile, address, description, media, secrets |
| No PII in public tracking response | Completed | Only code, created time, status timeline and visit time |
| Tracking enumeration | Completed | Same 404 message for unknown code and wrong mobile |
| Retention and deletion of customer media | **Not implemented** | Needs a lifecycle policy and a legal decision. **Needs legal review** |
| Encryption at rest for bucket | **Needs production credentials** | Enable bucket default encryption |
| Security headers | Completed | `next.config.ts` |

## 7. 3D asset pipeline

- **Current:** procedural Three.js/React Three Fiber geometry, no external model.
- **Architecture for a real model:** a single optimised `.glb` (Draco or Meshopt compressed, target < 1.5 MB, one material atlas), loaded with `useGLTF` inside the existing lazy `Canvas3D` boundary. The part names must match the `PartId` values so highlighting keeps working.
- **Licence rule:** no model is added without a written licence that permits this use. Each model's source, licence and author are recorded in `public/models/LICENSES.md` before it is committed. **Needs business input** (choosing or commissioning a model).
- **Status:** **Not implemented.** No model was added, so the TV is procedural.

## 8. Accessibility and reduced motion

- All 3D information has a text equivalent: the TV stage has an `aria-label`, and the exploded view has a keyboard-accessible part list with descriptions.
- `prefers-reduced-motion` disables 3D rendering in favour of a static CSS illustration (`TvFallback`).
- WebGL unavailable or context lost: the same CSS illustration is shown.
- Low-power devices render with reduced DPR and antialiasing.
- Smooth-scroll libraries are not used, so native scrolling is kept.

## 9. Performance (measured with headless Chromium, production build)

Raw (uncompressed) JavaScript requested on first load:

| Page | JS files | Raw KB |
|---|---|---|
| `/` (home) | 17 | ~1,592 |
| `/services/tv-repair` | 12 | ~573 |
| `/booking` | 11 | ~545 |

The largest chunk (~884 KB raw) is three.js with React Three Fiber. The home page loads it because the hero TV is in the first viewport. The three.js import is gated on visibility, so off-screen stages do not download it.

**Known gaps:** the homepage has no lazy split of the hero 3D from the initial bundle, and Lighthouse/Core Web Vitals field data have **not** been collected (no lab tool is available in this environment). Measure LCP, INP and CLS on staging before launch.

## 10. Testing

- Unit tests: `npm test` runs 44 tests. Coverage covers diagnosis flows for every symptom, handoff validation and tampering, lead and media validation, file signatures, storage configuration rules, webhook signing and retry, the repository, rate limiting, and log redaction.
- Typecheck: `npm run typecheck`. Build: `npm run build`.
- Browser end-to-end (headless Chromium): diagnosis → booking handoff → full wizard with a real photo upload → tracking code → tracking by code and mobile → wrong-mobile check → mobile overflow checks → no uncaught errors. 20 checks passing.
- Route smoke: 20 main routes at desktop and mobile widths return the expected status with `lang="fa" dir="rtl"`, one `h1`, and no horizontal overflow.

## 11. Known gaps and placeholders (summary)

| Item | Status |
|---|---|
| 3D: procedural TV, camera focus transition, part selection, boot/screen/backlight states | Completed (procedural) |
| 3D: photoreal GLB model | **Needs business input** and a licence |
| 3D: hover tooltips on parts | Not implemented (click selection and the accessible list exist) |
| TV Detective: per-symptom flows, safe advice, hedged results, handoff to booking | Completed |
| Booking: all steps, validation, uploads, progress, tracking code | Completed |
| Postgres lead repository | **Not implemented** |
| S3 media adapter | Partial (not exercised against a live bucket) |
| CRM webhook | Completed generically; vendor mapping **Needs production credentials** |
| SMS | **Not implemented** (interface and templates only; **Needs production credentials**) |
| Durable retries for webhook and SMS | **Not implemented** (in-process retries only, lost on restart; needs an outbox/queue) |
| Technician panel | **Not implemented** (data model documented in §4) |
| Reviews, certifications, prices, customer counts, service areas | **Needs business input**. Empty or `needs_review` states shown; none fabricated |
| Privacy and terms text | Drafts. **Needs legal review** |
| Customer media retention | **Not implemented**. **Needs legal review** |
| Analytics backend for server events | Dev log only |
| Lighthouse / Core Web Vitals field measurement | **Not implemented** in this environment |
| Pre-hydration `js` class script | Not implemented (minor; the class is added on mount) |

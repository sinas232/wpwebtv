# PIXVA test harness

Runtime verification for the PIXVA theme against a **real WordPress install**
(Playground: WordPress 7.1.3 + SQLite + PHP 8.3 via php-wasm).

## What is in here

| File | Purpose |
|------|---------|
| `mu-plugin.php` | In-process test suite mounted as a mu-plugin **only on the test server** (never packaged into `dist/pixva.zip`). Exposes `pixva-test/v1/run` and `pixva-test/v1/probe`. |
| `http-tests.sh` | End-to-end HTTP tests (forms, PRG, idempotent replay, rate limits, photo stream, roles, REST hardening). |
| `run.js` | One-shot runner: unit suite + HTTP suite (`npm test`). |

## Start the test server

```bash
NODE_EXTRA_CA_CERTS=/etc/ssl/certs/ca-certificates.crt \
node_modules/.bin/wp-playground-cli server \
  --port 9412 \
  --wp https://github.com/WordPress/WordPress/archive/refs/tags/7.1.3.zip \
  --mount-before-install "$PWD/pixva:/wordpress/wp-content/themes/pixva" \
  --mount "$PWD/tools/harness/mu-plugin.php:/wordpress/wp-content/mu-plugins/pixva-test.php" \
  --define PIXVA_TEST_TOKEN "pixva-test-token" \
  --blueprint /tmp/bp-test.json
```

`/tmp/bp-test.json` = working blueprint (`preferredVersions` pinning + `activateTheme`
step, **without** the `login` step so anonymous requests stay anonymous):

```json
{"preferredVersions":{"php":"8.3","wp":"https://github.com/WordPress/WordPress/archive/refs/tags/7.1.3.zip"},"landingPage":"/","steps":[{"step":"activateTheme","themeFolderName":"pixva"}]}
```

The WordPress source zip is pre-seeded in `~/.wordpress-playground/custom-7f345958.zip`
(cache key = `custom-` + first 8 hex of sha1(releaseUrl)) so the server boots offline.

## Run the suites

```bash
npm test                # lint + unit + HTTP suites
# or individually:
npm run lint:php
node tools/harness/run.js
PIXVA_BASE=http://127.0.0.1:9412 PIXVA_TEST_TOKEN=pixva-test-token node tools/harness/run.js
```

Recorded evidence from the 2026-10-10 run: `docs/evidence/unit-run-1.json`,
`docs/evidence/http-run-1.log`.

**Restart the server before a recorded run** — Playground persists state only
for the process lifetime, so a fresh boot = clean install (fresh rate-limit
buckets, no leftover orders).

## Honest limitations

- WordPress 7.1.3 + **SQLite** + PHP-wasm — **not** MySQL/MariaDB.
- **1 worker**: race paths are exercised at the compare-and-swap primitive
  level and via sequential replays; there is no true parallel writer.
- Mail is short-circuited (`pre_wp_mail` counter); no real SMTP delivery.
- Static files are served by Playground's router, which ignores `.htaccess`
  and has no Nginx config — the private-photo directory therefore returns
  200 on a direct request **in this environment only**. Production web-server
  deny rules remain NOT TESTED here.
- The mounted `mu-plugin.php` exists only for the test server; it is outside
  `pixva/` and never ships in the ZIP.

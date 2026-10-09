#!/usr/bin/env python3
"""
PIXVA REST contract check (read-only, repeatable).

Usage:
    python3 tools/rest_contract_check.py http://127.0.0.1:9400

What it asserts (the recorded contract, see docs/pixva-qa-matrix.md T25/T37):
  1. PIXVA custom post types do NOT expose a `meta` property in REST (their
     schema omits it, so no `_pixva_*` post meta is ever serialised). Any
     change that adds `custom-fields` or `show_in_rest` to these types fails
     here and must be a deliberate, documented contract change.
  2. Anonymous collection responses for PIXVA types contain no `meta` object and
     no `_pixva*` key (internal order, phone, case and SEO meta stay server-side). Anonymous single-item access to a
     non-public (draft/private) record is refused (401/403/404).
  3. /wp/v2/users is not listed anonymously (404/401/403).
  4. The PIXVA namespace exposes exactly the recorded routes; the order/
     tracking data endpoints accept only the documented methods (GET of
     /track and /warranty returns 404/405, not data).

Exit code 0 = all assertions passed, 1 = at least one failed, 2 = endpoint
unreachable. Nothing is written to the site; the script only issues GET and
OPTIONS requests.
"""
import json
import sys
import urllib.error
import urllib.request

PIXVA_TYPES = ['tv_services', 'tv_brands', 'tv_model', 'pixva_error', 'repair_cases', 'pixva_faq']
# Recorded contract: the PIXVA namespace routes (path => allowed methods).
PIXVA_ROUTES = {
    '/pixva/v1/diagnosis': ['POST'],
    '/pixva/v1/estimate': ['POST'],
    '/pixva/v1/error-codes': ['GET'],
    '/pixva/v1/models': ['GET'],
    '/pixva/v1/track': ['POST'],
    '/pixva/v1/warranty': ['POST'],
}
# Any `_pixva*` key is internal post meta; none may appear in REST output.
PRIVATE_PREFIXES = ('_pixva',)

failures = []
passes = []


def check(name, ok, detail=''):
    (passes if ok else failures).append(name + (f' — {detail}' if detail else ''))


def request(base, path, method='GET', body=None):
    data = None
    headers = {'Accept': 'application/json'}
    if body is not None:
        data = json.dumps(body).encode()
        headers['Content-Type'] = 'application/json'
    req = urllib.request.Request(base + path, data=data, method=method, headers=headers)
    try:
        with urllib.request.urlopen(req, timeout=30) as r:
            raw = r.read()
            return r.status, (json.loads(raw) if raw else None)
    except urllib.error.HTTPError as e:
        raw = e.read()
        try:
            return e.code, json.loads(raw)
        except Exception:
            return e.code, None
    except urllib.error.URLError as e:
        print(f'unreachable: {base} ({e})')
        sys.exit(2)


def main():
    base = (sys.argv[1] if len(sys.argv) > 1 else 'http://127.0.0.1:9400').rstrip('/')
    api = base + '/wp-json'

    # 1) Schema: no meta property on PIXVA types.
    for t in PIXVA_TYPES:
        st, sch = request(base, f'/wp-json/wp/v2/{t}', 'OPTIONS')
        props = ((sch or {}).get('schema') or {}).get('properties', {}) if isinstance(sch, dict) else {}
        check(f'schema {t}: no meta property', st == 200 and 'meta' not in props, f'status={st}')

    # 2) Anonymous collections: no meta, no private keys.
    for t in PIXVA_TYPES:
        st, items = request(base, f'/wp-json/wp/v2/{t}?per_page=100&context=view')
        ok = st == 200 and isinstance(items, list)
        leaked = []
        if ok:
            for it in items:
                if 'meta' in it:
                    leaked.append(f"item {it.get('id')} has meta")
                for k in it:
                    if k.startswith(PRIVATE_PREFIXES):
                        leaked.append(f"item {it.get('id')} key {k}")
        check(f'anon list {t}: no private/meta fields', ok and not leaked,
              f'status={st} items={len(items) if ok else "?"} {leaked[:3]}')

    # 3) Anonymous single non-public record is refused (use an id that cannot be public).
    for t in ['repair_cases']:
        st, _ = request(base, f'/wp-json/wp/v2/{t}/1?context=edit')
        check(f'anon edit-context {t}/1 refused', st in (401, 403, 404), f'status={st}')

    # 4) Users are not enumerable anonymously.
    st, users = request(base, '/wp-json/wp/v2/users')
    check('anon /wp/v2/users not listed', st in (401, 403, 404) or not isinstance(users, list) or users == [],
          f'status={st}')

    # 5) PIXVA namespace routes match the recorded contract.
    st, idx = request(base, '/wp-json/')
    routes = (idx or {}).get('routes', {}) if isinstance(idx, dict) else {}
    for path, methods in PIXVA_ROUTES.items():
        r = routes.get(path)
        got = sorted(r.get('methods', [])) if r else []
        check(f'route {path} present with {methods}', r is not None and set(methods) <= set(got), f'got={got}')
    extra = sorted(p for p in routes if p.startswith('/pixva/v1') and p not in PIXVA_ROUTES and p != '/pixva/v1')
    check('no undocumented /pixva/v1 routes', not extra, f'extra={extra}')

    # 6) Data endpoints do not answer GET with data.
    for path in ['/pixva/v1/track', '/pixva/v1/warranty', '/pixva/v1/orders']:
        st, body = request(base, path)
        check(f'GET {path} returns no data', st in (404, 405) and not (isinstance(body, dict) and 'order' in json.dumps(body).lower()),
              f'status={st}')

    print(f'PASS {len(passes)}  FAIL {len(failures)}  ({base})')
    for p in passes:
        print('  ok   ', p)
    for f in failures:
        print('  FAIL ', f)
    sys.exit(1 if failures else 0)


if __name__ == '__main__':
    main()

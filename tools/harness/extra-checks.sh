#!/usr/bin/env bash
# Supplemental verification for gap-ledger reconciliation (Phase D).
# Re-runs the ledger's T-checks that are executable inside this sandbox
# against the local test server. Staging/browser/migration checks (E01–E11,
# T02, T03, T26, T29, T30, T37) are NOT runnable here and stay NOT TESTED.
#
# Run AFTER a recorded unit+HTTP pass (consumes rate-limit buckets).
set -u
BASE="${PIXVA_BASE:-http://127.0.0.1:9412}"
TOKEN="${PIXVA_TEST_TOKEN:-pixva-test-token}"
PASS=0; FAIL=0
declare -a FAILED=()
rep() { if [ "$2" = "1" ]; then PASS=$((PASS+1)); echo "PASS  $1"; else FAIL=$((FAIL+1)); FAILED+=("$1 — $3"); echo "FAIL  $1 — $3"; fi; }

probe() { curl -s -X POST "$BASE/wp-json/pixva-test/v1/probe" -H "Content-Type: application/json" -H "X-Pixva-Test: $TOKEN" -d "$1"; }

echo "=== T12: route crawl (status, h1, noindex on public) ==="
ROUTES="/ /services/ /brands/ /problems/ /tools/ /tools/diagnosis/ /tools/price-calculator/ /tools/pixel-test/ /error-codes/ /repair/ /repair/book/ /tracking/ /warranty/ /portfolio/ /blog/ /about/ /contact/ /faq/ /account/ /account/repairs/ /account/warranty/ /account/profile/ /dashboard/"
BAD=0; TABLE=""
for r in $ROUTES; do
  BODY=$(curl -s -o /tmp/cx.html -w "%{http_code} %{num_redirects}" "$BASE$r")
  CODE=$(echo "$BODY" | awk '{print $1}'); HOPS=$(echo "$BODY" | awk '{print $2}')
  H1=$(grep -c "<h1" /tmp/cx.html || true)
  ROB=$(grep -c "noindex" /tmp/cx.html || true)
  STATUS="OK"
  if [ "$CODE" != "200" ]; then STATUS="BAD($CODE)"; BAD=$((BAD+1)); fi
  if [ "$CODE" = "200" ] && [ "$H1" -eq 0 ]; then STATUS="$STATUS/no-h1"; BAD=$((BAD+1)); fi
  # noindex is only unexpected on always-public marketing routes. Private
  # routes (account/dashboard) must be noindex, and empty/thin archives
  # (services/brands/error-codes/portfolio on a fresh install) are noindex
  # by design (§48) — both are accepted here.
  case " $r " in
    *" /account/ "*|*" /account/repairs/ "*|*" /account/warranty/ "*|*" /account/profile/ "*|*" /dashboard/ "*|*" /services/ "*|*" /brands/ "*|*" /error-codes/ "*|*" /portfolio/ "*) ALLOW_NOINDEX=1 ;;
    *) ALLOW_NOINDEX=0 ;;
  esac
  if [ "$CODE" = "200" ] && [ "$ROB" -gt 0 ] && [ "$ALLOW_NOINDEX" = "0" ]; then STATUS="$STATUS/unexpected-noindex"; BAD=$((BAD+1)); fi
  TABLE="$TABLE$r $CODE hops=$HOPS h1=$H1 noindex=$ROB $STATUS\n"
done
# Canonical alias: /tools/error-codes/ must hop exactly once to /error-codes/.
ALIAS=$(curl -s -o /dev/null -w "%{http_code}:%{redirect_url}" "$BASE/tools/error-codes/")
echo "    alias /tools/error-codes/ → $ALIAS"
case "$ALIAS" in 301*"$BASE/error-codes/"*) : ;; *) BAD=$((BAD+1));; esac
# Private routes must carry the noindex header.
PRIV=$(curl -s -D - -o /dev/null "$BASE/dashboard/" | grep -ci "x-robots-tag: noindex" || true)
[ "$PRIV" = "0" ] && BAD=$((BAD+1))
printf '%b' "$TABLE" | sed 's/^/    /'
rep "T12-route-crawl" "$([ "$BAD" = "0" ] && echo 1 || echo 0)" "bad=$BAD"

echo "=== T13: booking validation (valid nonce, invalid fields → 422) ==="
P13=$(curl -s "$BASE/repair/book/")
N13=$(echo "$P13" | python3 -c "import sys,re;m=re.search(r'name=\"_pixva_nonce\" value=\"([a-f0-9]+)\"',sys.stdin.read());print(m.group(1) if m else '')")
S13=$(echo "$P13" | python3 -c "import sys,re;m=re.search(r'name=\"_pixva_sid\" value=\"([0-9a-f-]+)\"',sys.stdin.read());print(m.group(1) if m else '')")
AJ=$(curl -s -o /tmp/cx13.json -w "%{http_code}" -X POST "$BASE/wp-admin/admin-ajax.php" \
  -d "action=pixva_booking&_pixva_nonce=$N13&_pixva_sid=$S13&_pixva_back=$BASE/repair/book/" \
  --data-urlencode "name=" --data-urlencode "phone=not-a-phone" --data-urlencode "brand=other" \
  --data-urlencode "brand_other=x" --data-urlencode "problem=other" --data-urlencode "description=" \
  --data-urlencode "consent=0")
ERRS=$(python3 -c "
import json
d=json.load(open('/tmp/cx13.json'))
e=d.get('data',{}).get('errors',{})
print(1 if d.get('success') is False and e and 'name' in e else 0)
" 2>/dev/null || echo 0)
rep "T13-invalid-booking-422" "$([ "$AJ" = "422" ] && [ "$ERRS" = "1" ] && echo 1 || echo 0)" "http=$AJ errors=$ERRS body=$(head -c 160 /tmp/cx13.json)"

echo "=== T16: booking prefill from diagnosis (rendered smoke; full matrix NOT TESTED) ==="
PF=$(curl -s -o /tmp/cx16.html -w "%{http_code}" "$BASE/repair/book/?from=diagnosis")
echo "    GET /repair/book/?from=diagnosis → $PF (template prefill logic statically reviewed)"
echo "INFO  T16-prefill: rendered smoke only — full symptom matrix NOT TESTED"

echo "=== T17: contact rate limit (6/h) ==="
GOT429=0
for i in $(seq 1 9); do
  P=$(curl -s "$BASE/contact/")
  N=$(echo "$P" | python3 -c "import sys,re;m=re.search(r'name=\"_pixva_nonce\" value=\"([a-f0-9]+)\"',sys.stdin.read());print(m.group(1) if m else '')")
  S=$(echo "$P" | python3 -c "import sys,re;m=re.search(r'name=\"_pixva_sid\" value=\"([0-9a-f-]+)\"',sys.stdin.read());print(m.group(1) if m else '')")
  curl -s -D /tmp/cx17.hdr -o /dev/null -X POST "$BASE/wp-admin/admin-post.php" \
    -F "action=pixva_contact" -F "_pixva_nonce=$N" -F "_pixva_sid=$S" -F "_pixva_back=$BASE/contact/" \
    -F "name=کاربر" -F "phone=09123451111" -F "message=پیام آزمایشی برای بررسی محدودیت ارسال تماس" -F "consent=1"
  TOK=$(grep -io "pixva_r=[A-Za-z0-9]*" /tmp/cx17.hdr | head -1 | cut -d= -f2)
  if [ -n "$TOK" ]; then
    if curl -s "$BASE/contact/?pixva_r=$TOK" | grep -q "بیش از حد مجاز"; then GOT429=1; break; fi
  fi
done
rep "T17-contact-rate-limit" "$([ "$GOT429" = "1" ] && echo 1 || echo 0)" "no 429 after 9 posts"

echo "=== T23: dashboard per role ==="
rm -f /tmp/cx_adm.cj
curl -s -c /tmp/cx_adm.cj -o /dev/null -X POST "$BASE/wp-login.php" \
  --data-urlencode "log=admin" --data-urlencode "pwd=password" \
  -d "wp-submit=Log+In&redirect_to=/dashboard/&testcookie=1" -H "Cookie: wordpress_test_cookie=WP%20Cookie%20check"
OKROLE=1
for ROLE in pixva_technician pixva_manager editor; do
  INFO=$(probe "{\"action\":\"create_user\",\"role\":\"$ROLE\"}")
  LOGIN=$(echo "$INFO" | python3 -c "import sys,json;print(json.load(sys.stdin).get('login',''))")
  rm -f /tmp/cx_u.cj
  curl -s -c /tmp/cx_u.cj -o /dev/null -X POST "$BASE/wp-login.php" \
    --data-urlencode "log=$LOGIN" --data-urlencode "pwd=PixvaTest!2026" \
    -d "wp-submit=Log+In&redirect_to=/dashboard/&testcookie=1" -H "Cookie: wordpress_test_cookie=WP%20Cookie%20check"
  C=$(curl -s -b /tmp/cx_u.cj -o /tmp/cx_dash_$ROLE.html -w "%{http_code}" "$BASE/dashboard/")
  PII=$(grep -c "09[0-9]{9}\|۰۹" /tmp/cx_dash_$ROLE.html || true)
  echo "    $ROLE → HTTP $C (PII-hits=$PII)"
  if [ "$C" != "200" ]; then OKROLE=0; fi
  if [ "$ROLE" = "editor" ] && [ "$PII" != "0" ]; then OKROLE=0; fi
done
rep "T23-dashboard-roles" "$([ "$OKROLE" = "1" ] && echo 1 || echo 0)" "see detail lines"

echo "=== T24: REST endpoints ==="
for EP in "/wp-json/pixva/v1/error-codes" "/wp-json/pixva/v1/models?brand=1"; do
  C=$(curl -s -o /tmp/cx_ep.json -w "%{http_code}" "$BASE$EP")
  echo "    GET $EP → $C"
  [ "$C" != "200" ] && OK24=0
done
OK24=${OK24:-1}
EST=$(curl -s -o /tmp/cx_est.json -w "%{http_code}" -X POST "$BASE/wp-json/pixva/v1/estimate" -H "Content-Type: application/json" -d '{}')
echo "    POST estimate {} → $EST"
[ "$EST" != "200" ] && OK24=0
DGN=$(curl -s -o /tmp/cx_dgn.json -w "%{http_code}" -X POST "$BASE/wp-json/pixva/v1/diagnosis" -H "Content-Type: application/json" -d '{"problem":"__nope__","brand":0,"model":"x"}')
echo "    POST diagnosis(invalid) → $DGN"
rep "T24-rest-endpoints" "$([ "$OK24" = "1" ] && { [ "$DGN" = "400" ] || [ "$DGN" = "422" ]; } && echo 1 || echo 0)" "estimate=$EST diagnosis=$DGN"

echo "=== T25: REST exposure ==="
U=$(curl -s -o /tmp/cx_u.json -w "%{http_code}" "$BASE/wp-json/wp/v2/users")
O=$(curl -s -o /tmp/cx_o.json -w "%{http_code}" "$BASE/wp-json/wp/v2/pixva_orders")
echo "    users→$U orders→$O"
rep "T25-rest-exposure" "$([ "$U" != "200" ] && [ "$O" != "200" ] && echo 1 || echo 0)" "users=$U orders=$O"

echo "=== T27/T28: thin content noindex + front description ==="
# Untouched sample content: canonical URL must be 200 AND carry
# noindex,follow; both sample URLs stay out of every sitemap.
HWCODE=$(curl -s -o /tmp/cx_hw.html -w "%{http_code}" "$BASE/blog/hello-world/")
HW=$(cat /tmp/cx_hw.html)
NOINDEX=$(echo "$HW" | grep -c "noindex, follow\|noindex,follow" || true)
IN_SITEMAP=0
curl -s "$BASE/wp-sitemap.xml" | grep -o "<loc>[^<]*" | sed 's/<loc>//' > /tmp/cx_sm_index.txt
: > /tmp/cx_sm_urls.txt
while read -r i; do
  [ -n "$i" ] || continue
  curl -s "$i" | grep -o "<loc>[^<]*" | sed 's/<loc>//' >> /tmp/cx_sm_urls.txt
done < /tmp/cx_sm_index.txt
if grep -Eq "hello-world|sample-page" /tmp/cx_sm_urls.txt /tmp/cx_sm_index.txt 2>/dev/null; then
  IN_SITEMAP=1
fi
echo "    hello-world code=$HWCODE noindex-hits=$NOINDEX in-sitemap=$IN_SITEMAP"
curl -s "$BASE/" -o /tmp/cx_front.html
DESC=$(grep -o 'name="description" content="[^"]\{20,\}"' /tmp/cx_front.html | head -1)
rep "T27-sample-noindex" "$([ "$HWCODE" = "200" ] && [ "$NOINDEX" -ge 1 ] && [ "$IN_SITEMAP" = "0" ] && echo 1 || echo 0)" "code=$HWCODE noindex=$NOINDEX in_sitemap=$IN_SITEMAP"
rep "T28-front-description" "$([ -n "$DESC" ] && echo 1 || echo 0)" "desc='${DESC:0:80}'"

echo "=== T31: robots.txt ==="
RB=$(curl -s "$BASE/robots.txt")
echo "$RB" | sed 's/^/    /'
rep "T31-robots" "$(echo "$RB" | grep -q "Disallow: /wp-admin/" && echo "$RB" | grep -qi "^Sitemap:" && echo 1 || echo 0)" "missing disallow/sitemap"

echo "=== T33: admin overview checklist ==="
AD=$(curl -s -b /tmp/cx_adm.cj "$BASE/wp-admin/admin.php?page=pixva")
ITEMS=$(echo "$AD" | grep -c "admin.php?page=pixva-business\|admin.php?page=pixva-pricing" || true)
echo "    checklist links=$ITEMS"
rep "T33-admin-checklist" "$([ "$ITEMS" -ge 3 ] && echo 1 || echo 0)" "links=$ITEMS"

echo "=== T35: sitemap integrity ==="
SITEMAP_URL=$(echo "$RB" | grep -i "^Sitemap:" | head -1 | awk '{print $2}')
echo "    index: $SITEMAP_URL"
IDX=$(curl -s "$SITEMAP_URL")
URLS=$(echo "$IDX" | grep -o "<loc>[^<]*</loc>" | sed 's/<[^>]*>//g')
CHILDREN=$(for u in $URLS; do curl -s "$u"; done | grep -o "<loc>[^<]*</loc>" | sed 's/<[^>]*>//g' | head -60)
N=0; BAD35=0
for u in $CHILDREN; do
  N=$((N+1))
  B=$(curl -s -o /tmp/cx_sm.html -w "%{http_code}" "$u")
  ROB=$(grep -c 'noindex' /tmp/cx_sm.html || true)
  CANOK=$(curl -s "$u" | grep -o 'rel="canonical" href="[^"]*"' | head -1 | sed 's/.*href="//;s/"$//')
  if [ "$B" != "200" ] || [ "$ROB" != "0" ]; then BAD35=$((BAD35+1)); echo "    BAD $u → $B noindex=$ROB"; fi
done
echo "    checked=$N bad=$BAD35"
rep "T35-sitemap" "$([ "$N" -gt 0 ] && [ "$BAD35" = "0" ] && echo 1 || echo 0)" "n=$N bad=$BAD35"

echo "=== T36: one-hop redirect variants ==="
BAD36=0
for V in "/services" "/contact" "/about" "/faq" "/index.php/services/" "/?pagename=services"; do
  H=$(curl -s -o /dev/null -w "%{http_code}:%{num_redirects}" -L "$BASE$V")
  CODE=${H%%:*}; HOPS=${H##*:}
  echo "    $V → $CODE hops=$HOPS"
  if [ "$HOPS" -gt 1 ]; then BAD36=$((BAD36+1)); fi
done
rep "T36-one-hop" "$([ "$BAD36" = "0" ] && echo 1 || echo 0)" "chains=$BAD36"

echo "=== T38: rendered fake-data scan (12 key pages) ==="
# Allowed: the phone FORMAT example in help/validation text ("مثل ۰۹۱۲۳۴۵۶۷۸۹").
HITS=0
for P in / /services/ /repair/ /repair/book/ /warranty/ /about/ /contact/ /faq/ /blog/ /portfolio/ /tracking/ /tools/error-codes/; do
  curl -s "$BASE$P" -o /tmp/cx_pg.html
  if ! python3 - "$P" <<'EOF'
import re, sys
p = sys.argv[1]
s = open('/tmp/cx_pg.html', encoding='utf-8', errors='ignore').read()
# strip the documented format example (help + validation copy)
s = s.replace('مثل ۰۹۱۲۳۴۵۶۷۸۹', '').replace('مثل۰۹۱۲۳۴۵۶۷۸۹', '')
hits = []
if re.search(r'tel:\+?98|tel:09', s, re.I): hits.append('tel-link')
if re.search(r'wa\.me/98\d{10}', s): hits.append('wa-link')
if re.search(r'۰۹[۰-۹]{9}|\b09[0-9]{9}\b', s): hits.append('phone')
if re.search(r'۱۸۰\s*ماه|180[- ]month', s): hits.append('180warranty')
if re.search(r'reviewCount|aggregateRating', s): hits.append('rating-schema')
if re.search(r'[0-9۰-۹]+\s*(تومان|ریال)', s): hits.append('price')
if hits:
    print(f'    HIT {p}: {",".join(hits)}')
    sys.exit(1)
sys.exit(0)
EOF
  then HITS=$((HITS+1)); fi
done
rep "T38-fake-data-scan" "$([ "$HITS" = "0" ] && echo 1 || echo 0)" "pages-with-hits=$HITS"

echo "----------------------------------------"
echo "EXTRA CHECKS: PASS=$PASS FAIL=$FAIL"
[ "$FAIL" -gt 0 ] && printf '  - %s\n' "${FAILED[@]}"
exit 0

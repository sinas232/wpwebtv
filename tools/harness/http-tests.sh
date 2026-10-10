#!/usr/bin/env bash
# PIXVA end-to-end HTTP tests against a local WordPress Playground test server.
#
# Prerequisites (see tools/harness/README.md): a server started with the
# mu-plugin mounted and PIXVA_TEST_TOKEN defined, e.g. port 9412.
#
# Honest scope: WordPress 7.1.3 + SQLite + PHP-wasm, single worker.
# "Concurrent" cases are sequential replays (true parallelism is not
# available in this harness). No real SMTP, no Nginx/Apache rules.
set -u

BASE="${PIXVA_BASE:-http://127.0.0.1:9412}"
TOKEN="${PIXVA_TEST_TOKEN:-pixva-test-token}"
UA="PixvaHarness/1.0"

PASS=0
FAIL=0
declare -a FAILED=()

report() { # report <name> <cond> <detail>
  if [ "$2" = "1" ]; then
    PASS=$((PASS+1)); echo "PASS  $1"
  else
    FAIL=$((FAIL+1)); FAILED+=("$1: $3"); echo "FAIL  $1 — $3"
  fi
}

jget() { # jget <json> <python-expr on obj d>
  python3 -c "
import json,sys
d=json.loads(sys.argv[1])
try:
  v=eval(sys.argv[2])
  print(v if v is not None else '')
except Exception as e:
  print('')
" "$1" "$2"
}

probe() { # probe <action> <sid>
  curl -s -X POST "$BASE/wp-json/pixva-test/v1/probe" \
    -H "Content-Type: application/json" -H "X-Pixva-Test: $TOKEN" \
    -d "{\"action\":\"$1\",\"sid\":\"$2\"}"
}

# ---------------------------------------------------------------------------
# 1) In-process suite (via the mounted mu-plugin)
# ---------------------------------------------------------------------------
RUN=$(curl -s -X POST "$BASE/wp-json/pixva-test/v1/run" \
  -H "Content-Type: application/json" -H "X-Pixva-Test: $TOKEN" -d '{}')
FAILED_TOTAL=$(jget "$RUN" "d.get('failed')")
SUITE_ERR=$(jget "$RUN" "'x' if 'tests' in d else 'err'")
if [ "$SUITE_ERR" = "err" ]; then
  report "unit-suite-reachable" 0 "endpoint unreachable: ${RUN:0:120}"
else
  report "unit-suite-reachable" 1 ""
  report "unit-suite-all-pass" "$([ "$FAILED_TOTAL" = "0" ] && echo 1 || echo 0)" "failed=$FAILED_TOTAL"
  python3 -c "
import json,sys
d=json.loads(sys.argv[1])
for t in d.get('tests',[]):
  if t['status']!='PASS':
    print('  FAIL-DETAIL', t['test'], t['assertions'], t['failures'])
" "$RUN"
fi

# ---------------------------------------------------------------------------
# 2) Booking: full no-JS PRG flow, duplicate replay, ajax, bad nonce, honeypot
# ---------------------------------------------------------------------------
PAGE=$(curl -s -c /tmp/px.cj -b /tmp/px.cj "$BASE/repair/book/")
NONCE=$(echo "$PAGE" | python3 -c "import sys,re;m=re.search(r'name=\"_pixva_nonce\" value=\"([a-f0-9]+)\"',sys.stdin.read());print(m.group(1) if m else '')")
SID=$(echo "$PAGE" | python3 -c "import sys,re;m=re.search(r'name=\"_pixva_sid\" value=\"([0-9a-f-]+)\"',sys.stdin.read());print(m.group(1) if m else '')")
report "booking-page-renders" "$([ -n "$NONCE" ] && [ -n "$SID" ] && echo 1 || echo 0)" "nonce='$NONCE' sid='$SID'"

post_booking() { # post_booking <sid> <nonce> [extra args...]
  local sid="$1" nonce="$2"; shift 2
  curl -s -D /tmp/px_book.hdr -o /tmp/px_book.body -X POST "$BASE/wp-admin/admin-post.php" \
    -F "action=pixva_booking" -F "_pixva_nonce=$nonce" -F "_pixva_sid=$sid" \
    -F "_pixva_back=$BASE/repair/book/" \
    -F "name=کاربر تست HTTP" -F "phone=09123456789" \
    -F "brand=other" -F "brand_other=برند تست" -F "model=مدل تست" \
    -F "problem=other" -F "description=توضیح مشکل آزمایشی از اسکریپت تست" \
    -F "consent=1" "$@"
  grep -i "^HTTP/" /tmp/px_book.hdr | tail -1
}

LOC=$(post_booking "$SID" "$NONCE" | tail -1)
CODE_HDR=$(grep -i "^location:" /tmp/px_book.hdr | tr -d '\r')
report "booking-prg-redirect" "$([ -n "$CODE_HDR" ] && echo 1 || echo 0)" "no location header"
TOKEN_R=$(echo "$CODE_HDR" | python3 -c "import sys,re;m=re.search(r'pixva_r=([A-Za-z0-9]+)',sys.stdin.read());print(m.group(1) if m else '')")
C1=$(probe orders_by_sid "$SID")
report "booking-creates-order" "$( [ "$(jget "$C1" "d.get('count')" 2>/dev/null || echo x)" = "1" ] && echo 1 || echo 0)" "count=$(echo $C1 | head -c 200)"

# Replay the exact same submission (double submit / crash-retry).
post_booking "$SID" "$NONCE" >/dev/null
C2=$(probe orders_by_sid "$SID")
report "booking-replay-idempotent" "$( [ "$(jget "$C2" "d.get('count')" 2>/dev/null || echo x)" = "1" ] && echo 1 || echo 0)" "count=$(echo $C2 | head -c 200)"

# Result page shows the tracking code.
if [ -n "$TOKEN_R" ]; then
  RESULT_PAGE=$(curl -s -c /tmp/px.cj -b /tmp/px.cj "$BASE/repair/book/?pixva_r=$TOKEN_R")
  ORDER_CODE=$(jget "$(probe order_code "$SID")" "d.get('code')")
  report "booking-result-shows-code" "$(echo "$RESULT_PAGE" | grep -q "$ORDER_CODE" && echo 1 || echo 0)" "code '$ORDER_CODE' not on result page"
fi

# AJAX path (admin-ajax JSON) with a fresh sid.
PAGE2=$(curl -s -c /tmp/px.cj -b /tmp/px.cj "$BASE/repair/book/")
NONCE2=$(echo "$PAGE2" | python3 -c "import sys,re;m=re.search(r'name=\"_pixva_nonce\" value=\"([a-f0-9]+)\"',sys.stdin.read());print(m.group(1) if m else '')")
SID2=$(echo "$PAGE2" | python3 -c "import sys,re;m=re.search(r'name=\"_pixva_sid\" value=\"([0-9a-f-]+)\"',sys.stdin.read());print(m.group(1) if m else '')")
AJAX=$(curl -s -X POST "$BASE/wp-admin/admin-ajax.php" \
  -F "action=pixva_booking" -F "_pixva_nonce=$NONCE2" -F "_pixva_sid=$SID2" \
  -F "_pixva_back=$BASE/repair/book/" \
  -F "name=کاربر تست AJAX" -F "phone=09123456788" \
  -F "brand=other" -F "brand_other=برند تست" -F "model=مدل" \
  -F "problem=other" -F "description=توضیح مشکل آزمایشی مسیر ajax" \
  -F "consent=1")
report "booking-ajax-success" "$(echo "$AJAX" | python3 -c "import sys,json;d=json.load(sys.stdin);import sys as s;print(1 if d.get('success') and d.get('data',{}).get('code','').startswith('PXV-') else 0)" 2>/dev/null || echo 0)" "resp=${AJAX:0:160}"

# Invalid nonce → rejected, no order.
PAGE3=$(curl -s "$BASE/repair/book/")
SID3=$(echo "$PAGE3" | python3 -c "import sys,re;m=re.search(r'name=\"_pixva_sid\" value=\"([0-9a-f-]+)\"',sys.stdin.read());print(m.group(1) if m else '')")
post_booking "$SID3" "bogusnonce" >/dev/null
C3=$(probe orders_by_sid "$SID3")
report "booking-bad-nonce-rejected" "$( [ "$(jget "$C3" "d.get('count')" 2>/dev/null || echo x)" = "0" ] && echo 1 || echo 0)" "count=$(echo $C3 | head -c 120)"

# Honeypot filled → pretend success, no order.
PAGE4=$(curl -s "$BASE/repair/book/")
NONCE4=$(echo "$PAGE4" | python3 -c "import sys,re;m=re.search(r'name=\"_pixva_nonce\" value=\"([a-f0-9]+)\"',sys.stdin.read());print(m.group(1) if m else '')")
SID4=$(echo "$PAGE4" | python3 -c "import sys,re;m=re.search(r'name=\"_pixva_sid\" value=\"([0-9a-f-]+)\"',sys.stdin.read());print(m.group(1) if m else '')")
post_booking "$SID4" "$NONCE4" -F "pixva_hp=filled-by-bot" >/dev/null
C4=$(probe orders_by_sid "$SID4")
report "booking-honeypot-no-order" "$( [ "$(jget "$C4" "d.get('count')" 2>/dev/null || echo x)" = "0" ] && echo 1 || echo 0)" "count=$(echo $C4 | head -c 120)"

# ---------------------------------------------------------------------------
# 3) Contact: store + replay idempotency
# ---------------------------------------------------------------------------
CPAGE=$(curl -s "$BASE/contact/")
CNONCE=$(echo "$CPAGE" | python3 -c "import sys,re;m=re.search(r'name=\"_pixva_nonce\" value=\"([a-f0-9]+)\"',sys.stdin.read());print(m.group(1) if m else '')")
CSID=$(echo "$CPAGE" | python3 -c "import sys,re;m=re.search(r'name=\"_pixva_sid\" value=\"([0-9a-f-]+)\"',sys.stdin.read());print(m.group(1) if m else '')")
post_contact() {
  curl -s -o /tmp/px_c.out -D /tmp/px_c.hdr -X POST "$BASE/wp-admin/admin-post.php" \
    -F "action=pixva_contact" -F "_pixva_nonce=$CNONCE" -F "_pixva_sid=$CSID" \
    -F "_pixva_back=$BASE/contact/" \
    -F "name=کاربر تست" -F "phone=09123450000" -F "message=پیام آزمایشی برای مسیر تماس از اسکریپت تست" \
    -F "consent=1"
  grep -i "^HTTP/" /tmp/px_c.hdr | tail -1
}
post_contact >/dev/null
I1=$(probe inbox_by_sid "$CSID")
report "contact-stores-message" "$( [ "$(jget "$I1" "d.get('count')" 2>/dev/null || echo x)" = "1" ] && echo 1 || echo 0)" "count=$(echo $I1 | head -c 200)"
post_contact >/dev/null
I2=$(probe inbox_by_sid "$CSID")
report "contact-replay-idempotent" "$( [ "$(jget "$I2" "d.get('count')" 2>/dev/null || echo x)" = "1" ] && echo 1 || echo 0)" "count=$(echo $I2 | head -c 200)"

# ---------------------------------------------------------------------------
# 4) Lookup (track): proof, no PII, per-code lockout
# ---------------------------------------------------------------------------
TR=$(curl -s -X POST "$BASE/wp-json/pixva/v1/track" -H "Content-Type: application/json" \
  -d '{"code":"PXV-ZZZZ-ZZZZ","phone":"09123456789"}')
report "track-unknown-code-404" "$(echo "$TR" | python3 -c "import sys,json;d=json.load(sys.stdin);print(1 if d.get('code')=='pixva_rest_404' or d.get('code')=='not_found' or d.get('status')==404 else 0)" 2>/dev/null || echo 0)" "resp=${TR:0:160}"

# Real order from earlier booking (order created with phone 09123456789).
ORDERS_JSON=$(probe orders_by_sid "$SID")
REAL_CODE=$(jget "$(probe order_code "$SID")" "d.get('code')")
TR2=$(curl -s -X POST "$BASE/wp-json/pixva/v1/track" -H "Content-Type: application/json" \
  -d "{\"code\":\"$REAL_CODE\",\"phone\":\"09123456789\"}")
OK2=$(echo "$TR2" | python3 -c "
import sys,json
d=json.load(sys.stdin)
v=d.get('code') or ''
print(1 if v=='PXV' or (isinstance(d,dict) and 'history' in d and 'status' in d) else 0)
" 2>/dev/null || echo 0)
report "track-valid-proof-ok" "$OK2" "resp=${TR2:0:160}"
report "track-no-pii" "$(echo "$TR2" | grep -q "09123456789\|کاربر تست HTTP" && echo 0 || echo 1)" "phone/name leaked in track response"

# Per-code failure lock: 5 wrong phones → locked.
LOCKED=0
for i in 1 2 3 4 5 6; do
  R=$(curl -s -o /tmp/px_lock.json -w "%{http_code}" -X POST "$BASE/wp-json/pixva/v1/track" \
    -H "Content-Type: application/json" -d "{\"code\":\"$REAL_CODE\",\"phone\":\"0912000000$i\"}")
  if [ "$R" = "429" ]; then LOCKED=1; fi
done
report "track-per-code-lockout" "$([ "$LOCKED" = "1" ] && echo 1 || echo 0)" "no 429 after repeated wrong phones (last=$R)"

# ---------------------------------------------------------------------------
# 5) Private photo: upload via booking, stream access control
# ---------------------------------------------------------------------------
# Customer session (needed for the foreign-access denial below).
CUST_INFO=$(curl -s -X POST "$BASE/wp-json/pixva-test/v1/probe" \
  -H "Content-Type: application/json" -H "X-Pixva-Test: $TOKEN" \
  -d '{"action":"create_customer"}')
CUST_LOGIN=$(jget "$CUST_INFO" "d.get('login')")
rm -f /tmp/px_cust.cj
curl -s -c /tmp/px_cust.cj -o /dev/null -X POST "$BASE/wp-login.php" \
  --data-urlencode "log=$CUST_LOGIN" --data-urlencode "pwd=PixvaTest!2026" \
  -d "wp-submit=Log+In&redirect_to=/dashboard/&testcookie=1" \
  -H "Cookie: wordpress_test_cookie=WP%20Cookie%20check"

# 1x1 PNG
python3 - <<'EOF'
import struct,zlib
def chunk(t,d):
    return struct.pack('>I',len(d))+t+d+struct.pack('>I',zlib.crc32(t+d)&0xffffffff)
sig=b'\x89PNG\r\n\x1a\n'
ihdr=chunk(b'IHDR',struct.pack('>IIBBBBB',1,1,8,2,0,0,0))
idat=chunk(b'IDAT',zlib.compress(b'\x00\xff\x00\x00'))
iend=chunk(b'IEND',b'')
open('/tmp/px.png','wb').write(sig+ihdr+idat+iend)
EOF
PAGE5=$(curl -s "$BASE/repair/book/")
NONCE5=$(echo "$PAGE5" | python3 -c "import sys,re;m=re.search(r'name=\"_pixva_nonce\" value=\"([a-f0-9]+)\"',sys.stdin.read());print(m.group(1) if m else '')")
SID5=$(echo "$PAGE5" | python3 -c "import sys,re;m=re.search(r'name=\"_pixva_sid\" value=\"([0-9a-f-]+)\"',sys.stdin.read());print(m.group(1) if m else '')")
curl -s -D /tmp/px_up.hdr -o /tmp/px_up.body -X POST "$BASE/wp-admin/admin-post.php" \
  -F "action=pixva_booking" -F "_pixva_nonce=$NONCE5" -F "_pixva_sid=$SID5" \
  -F "_pixva_back=$BASE/repair/book/" \
  -F "name=کاربر تست عکس" -F "phone=09123456787" \
  -F "brand=other" -F "brand_other=برند تست" -F "model=مدل" \
  -F "problem=other" -F "description=توضیح مشکل برای تست آپلود تصویر" -F "consent=1" \
  -F "photos[]=@/tmp/px.png;type=image/png"
report "photo-booking-ok" "$(grep -qi '^location:.*pixva_r' /tmp/px_up.hdr && echo 1 || echo 0)" "no redirect after photo upload"
PHOTO=$(curl -s -X POST "$BASE/wp-json/pixva-test/v1/probe" -H "Content-Type: application/json" -H "X-Pixva-Test: $TOKEN" \
  -d "{\"action\":\"order_photo\",\"sid\":\"$SID5\"}" 2>/dev/null)
# probe action order_photo may not exist — fetch the stored file name via probe list instead:
ORDER_IDS=$(jget "$(probe orders_by_sid "$SID5")" "d.get('ids')")
if [ -n "$ORDER_IDS" ]; then
  OID=$(echo "$ORDER_IDS" | python3 -c "import sys,json;print(json.load(sys.stdin)[0])" 2>/dev/null || echo "")
else
  OID=""
fi
report "photo-order-created" "$([ -n "$OID" ] && echo 1 || echo 0)" "no order for photo booking"

if [ -n "$OID" ]; then
  # Manager (admin) fetches the photo through the authorised stream.
  rm -f /tmp/px_admin.cj
  LOGIN=$(curl -s -c /tmp/px_admin.cj -o /dev/null -w "%{http_code}" -X POST "$BASE/wp-login.php" \
    -d "log=admin&pwd=password&wp-submit=Log+In&redirect_to=/wp-admin/&testcookie=1" -H "Cookie: wordpress_test_cookie=WP%20Cookie%20check")
  # Fetch the order edit page to grab a signed photo URL.
  EDIT=$(curl -s -b /tmp/px_admin.cj "$BASE/wp-admin/post.php?post=$OID&action=edit")
  PHOTO_URL=$(echo "$EDIT" | python3 -c "import sys,re;m=re.search(r'href=\"([^\"]*admin-post\.php\?action=pixva_order_photo[^\"]*)\"',sys.stdin.read());u=m.group(1) if m else '';print(u.replace('&#038;','&').replace('&amp;','&'))")
  PHOTO_FILE=$(echo "$PHOTO_URL" | python3 -c "import sys,re,urllib.parse;m=re.search(r'[?&]file=([^&]+)',sys.stdin.read());print(urllib.parse.unquote(m.group(1)) if m else '')")
  report "photo-url-present-for-staff" "$([ -n "$PHOTO_URL" ] && [ -n "$PHOTO_FILE" ] && echo 1 || echo 0)" "url='${PHOTO_URL:0:120}' file='$PHOTO_FILE'"
  if [ -n "$PHOTO_URL" ]; then
    # Correct access (admin).
    case "$PHOTO_URL" in http*) FULL="$PHOTO_URL";; *) FULL="$BASE$PHOTO_URL";; esac
    curl -s -D /tmp/px_ph.hdr -o /tmp/px_ph.bin -b /tmp/px_admin.cj "$FULL"
    PH_CODE=$(head -1 /tmp/px_ph.hdr | awk '{print $2}')
    PH_LEN=$(wc -c < /tmp/px_ph.bin)
    PNG_LEN=$(wc -c < /tmp/px.png)
    report "photo-stream-200" "$([ "$PH_CODE" = "200" ] && echo 1 || echo 0)" "code=$PH_CODE"
    report "photo-stream-bytes" "$([ "$PH_LEN" -eq "$PNG_LEN" ] && echo 1 || echo 0)" "len=$PH_LEN expected=$PNG_LEN"
    report "photo-stream-headers" "$(grep -qi 'X-Content-Type-Options: nosniff' /tmp/px_ph.hdr && grep -qi 'no-store' /tmp/px_ph.hdr && echo 1 || echo 0)" "missing nosniff/no-store"
    # Stripped nonce → 403.
    STRIPPED=$(echo "$FULL" | sed 's/_wpnonce=[a-f0-9]*//')
    C=$(curl -s -o /dev/null -w "%{http_code}" -b /tmp/px_admin.cj "$STRIPPED")
    report "photo-no-nonce-403" "$([ "$C" = "403" ] && echo 1 || echo 0)" "code=$C"
    # Wrong order id with the same file → 403 (file not on that order).
    # Anonymous → not 200.
    CA=$(curl -s -o /dev/null -w "%{http_code}" "$FULL")
    report "photo-anonymous-denied" "$([ "$CA" != "200" ] && echo 1 || echo 0)" "code=$CA"
    # Customer (no view cap on this order) → denied.
    CC=$(curl -s -o /dev/null -w "%{http_code}" -b /tmp/px_cust.cj "$FULL")
    report "photo-foreign-customer-denied" "$([ "$CC" = "403" ] && echo 1 || echo 0)" "code=$CC"
    # Direct static URL (private dir) — environment-dependent: recorded, not asserted as PASS of web-server rules.
    PRIV=$(curl -s -o /dev/null -w "%{http_code}" "$BASE/wp-content/uploads/pixva-private/$PHOTO_FILE")
    echo "INFO  direct-private-file GET → HTTP $PRIV (Playground serves static files without .htaccess; real Nginx/Apache rules are NOT verified here)"
  fi
fi

# ---------------------------------------------------------------------------
# 6) Roles: dashboard access + admin area lockout for customers
# ---------------------------------------------------------------------------
# Create a customer via the registration form (users_can_register may be off → set probe can't; use REST users? ) — use the unit suite's user instead:
# Log in as the test customer created in-process (same run): credentials are deterministic.
# NOTE: users were created in the unit-suite request with login t_pixva_customer_<suffix>; suffix is random,
# so we instead register through the site when open, else skip gracefully.
REG=$(curl -s -X POST "$BASE/wp-json/pixva-test/v1/probe" -H "Content-Type: application/json" -H "X-Pixva-Test: $TOKEN" -d '{"action":"enable_registration"}' >/dev/null; \
  APAGE=$(curl -s "$BASE/account/"); \
  ANONCE=$(echo "$APAGE" | python3 -c "import sys,re;m=re.search(r'name=\"_pixva_nonce\" value=\"([a-f0-9]+)\"',sys.stdin.read());print(m.group(1) if m else '')"); \
  ASID=$(echo "$APAGE" | python3 -c "import sys,re;m=re.search(r'name=\"_pixva_sid\" value=\"([0-9a-f-]+)\"',sys.stdin.read());print(m.group(1) if m else '')"); \
  curl -s -X POST "$BASE/wp-admin/admin-post.php" \
    -F "action=pixva_register" -F "_pixva_nonce=$ANONCE" -F "_pixva_sid=$ASID" \
    -F "_pixva_back=$BASE/account/" \
    -F "name=کاربر نو" -F "email=customer-$(date +%s)@example.test" -F "phone=09123459999" \
    -F "password=CustomerPass!23" -F "consent=1" -o /tmp/px_reg.out -D /tmp/px_reg.hdr -w '{"redir":"%{http_code}"}')
REG_CODE=$(echo "$REG" | python3 -c "import sys,json;d=json.load(sys.stdin);print(d.get('redir',''))" 2>/dev/null || echo "")
report "registration-redirect" "$([ -n "$REG_CODE" ] && echo 1 || echo 0)" "resp=$REG:${REG:0:120}"

# Dashboard as anonymous → 200 with login form.
DASH_CODE=$(curl -s -o /tmp/px_dash.html -w "%{http_code}" "$BASE/dashboard/")
report "dashboard-anon-200" "$([ "$DASH_CODE" = "200" ] && echo 1 || echo 0)" "code=$DASH_CODE"
report "dashboard-anon-login-form" "$(grep -q 'ورود کارکنان' /tmp/px_dash.html && echo 1 || echo 0)" "login panel missing"

# Login as admin → dashboard renders staff content.
curl -s -c /tmp/px_admin.cj -o /dev/null -X POST "$BASE/wp-login.php" \
  -d "log=admin&pwd=password&wp-submit=Log+In&redirect_to=/dashboard/&testcookie=1" -H "Cookie: wordpress_test_cookie=WP%20Cookie%20check"
DASH2=$(curl -s -b /tmp/px_admin.cj -o /tmp/px_dash2.html -w "%{http_code}" "$BASE/dashboard/")
report "dashboard-admin-200" "$([ "$DASH2" = "200" ] && echo 1 || echo 0)" "code=$DASH2"
report "dashboard-admin-content" "$(grep -q 'درخواست‌ها بر اساس وضعیت\|هنوز درخواستی ثبت نشده' /tmp/px_dash2.html && echo 1 || echo 0)" "staff panel missing"

# Customer session (created before the photo tests) → dashboard 403
# (pixva_private_route_headers).
DASH3=$(curl -s -b /tmp/px_cust.cj -o /dev/null -w "%{http_code}" "$BASE/dashboard/")
report "dashboard-customer-403" "$([ "$DASH3" = "403" ] && echo 1 || echo 0)" "code=$DASH3"

# ---------------------------------------------------------------------------
# 7) REST & hardening
# ---------------------------------------------------------------------------
USERS=$(curl -s -o /tmp/px_users.json -w "%{http_code}" "$BASE/wp-json/wp/v2/users")
report "rest-users-listing-restricted" "$([ "$USERS" != "200" ] && echo 1 || echo 0)" "code=$USERS body=$(head -c 120 /tmp/px_users.json)"

HDRS=$(curl -s -D - -o /dev/null "$BASE/")
report "security-headers-present" "$(echo "$HDRS" | grep -qi 'X-Content-Type-Options: nosniff' && echo "$HDRS" | grep -qi 'X-Frame-Options' && echo 1 || echo 0)" "missing baseline headers"

AUTH=$(curl -s -o /dev/null -w "%{http_code}" "$BASE/author/admin/")
report "author-archive-404" "$([ "$AUTH" = "404" ] && echo 1 || echo 0)" "code=$AUTH"

# error-codes REST with no-store
EC_CODE=$(curl -s -D /tmp/px_ec.hdr -o /tmp/px_ec.json -w "%{http_code}" "$BASE/wp-json/pixva/v1/error-codes")
report "error-codes-rest-200" "$([ "$EC_CODE" = "200" ] && echo 1 || echo 0)" "code=$EC_CODE"
report "rest-no-store" "$(grep -qi 'no-store' /tmp/px_ec.hdr && echo 1 || echo 0)" "missing no-store"

# ---------------------------------------------------------------------------
# 8) Booking rate limit (same client) — runs LAST, consumes the bucket
# ---------------------------------------------------------------------------
GOT429=0
for i in $(seq 1 12); do
  P=$(curl -s "$BASE/repair/book/")
  N=$(echo "$P" | python3 -c "import sys,re;m=re.search(r'name=\"_pixva_nonce\" value=\"([a-f0-9]+)\"',sys.stdin.read());print(m.group(1) if m else '')")
  S=$(echo "$P" | python3 -c "import sys,re;m=re.search(r'name=\"_pixva_sid\" value=\"([0-9a-f-]+)\"',sys.stdin.read());print(m.group(1) if m else '')")
  H=$(curl -s -D - -o /dev/null -X POST "$BASE/wp-admin/admin-post.php" \
    -F "action=pixva_booking" -F "_pixva_nonce=$N" -F "_pixva_sid=$S" \
    -F "_pixva_back=$BASE/repair/book/" \
    -F "name=کاربر تست" -F "phone=09123456786" \
    -F "brand=other" -F "brand_other=ب" -F "model=م" \
    -F "problem=other" -F "description=توضیح آزمایشی برای تست محدودیت ارسال" \
    -F "consent=1")
  if echo "$H" | grep -qi "pixva_r"; then
    TOK=$(echo "$H" | python3 -c "import sys,re;m=re.search(r'pixva_r=([A-Za-z0-9]+)',sys.stdin.read());print(m.group(1) if m else '')")
    if [ -n "$TOK" ]; then
      # Fetch the redirect body's error state: rate-limited results are errors.
      BODY=$(curl -s "$BASE/repair/book/?pixva_r=$TOK")
      if echo "$BODY" | grep -q "بیش از حد مجاز"; then GOT429=1; break; fi
    fi
  fi
done
report "booking-rate-limit" "$([ "$GOT429" = "1" ] && echo 1 || echo 0)" "no rate-limit message after 12 submissions"

# ---------------------------------------------------------------------------
echo "----------------------------------------"
echo "HTTP SUITE: PASS=$PASS FAIL=$FAIL"
if [ "$FAIL" -gt 0 ]; then
  printf '  - %s\n' "${FAILED[@]}"
  exit 1
fi
exit 0

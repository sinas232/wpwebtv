#!/usr/bin/env bash
# PIXVA + Elementor end-to-end verification (frontend markers, editor loads,
# orders workflow, admin surfaces, shortcodes). Safe against rate-limit state:
# caller should rl_reset before running.
set -u
B="${PIXVA_BASE:-http://127.0.0.1:9414}"
J=/tmp/ve.cookies
rm -f "$J"
curl -s -c "$J" -b "$J" -o /dev/null -X POST "$B/wp-login.php" \
  --data-urlencode "log=admin" --data-urlencode "pwd=password" \
  -d "wp-submit=Log+In&redirect_to=%2Fwp-admin%2F&testcookie=1"
P=0; F=0
ok(){ P=$((P+1)); echo "PASS  $1"; }
no(){ F=$((F+1)); echo "FAIL  $1"; }
chk(){ [ "$2" = "1" ] && ok "$1" || no "$1"; }

echo "# PIXVA + Elementor verification"
echo "# WP 7.1.3 / PHP 8.3.33 (Playground SQLite), Elementor 4.4.0"
echo
echo "=== A. Registration ==="
curl -s -X POST "$B/wp-json/pixva-test/v1/probe" -H "Content-Type: application/json" \
  -H "X-Pixva-Test: pixva-test-token" -d '{"action":"el_state"}' -o /tmp/ve_el.json
python3 - <<'EOF'
import json
d = json.load(open('/tmp/ve_el.json', encoding='utf-8'))
st = d.get('el', d)
w = st.get('pixva_widgets', [])
print(f"widget_count={st.get('widget_count')} pixva_widgets={len(w)} hooked={st.get('hooked')} pri={st.get('hooked_pri')}")
print('list:', ', '.join(w))
EOF
echo
echo "=== B. Frontend (10 pages, HTTP + markers) ==="
fetch(){ curl -s -o "/tmp/ve_$1.html" -w '%{http_code}' --max-time 60 "$B$2"; }
for pair in "home:/" "problems:/problems/" "tools:/tools/" "repair:/repair/" "booking:/repair/book/" "tracking:/tracking/" "warranty:/warranty/" "faq:/faq/" "contact:/contact/" "about:/about/"; do
  k="${pair%%:*}"; u="${pair#*:}"
  c=$(fetch "$k" "$u")
  [ "$c" = "200" ] && ok "$k HTTP 200" || no "$k HTTP $c"
done
python3 - <<'EOF'
checks = {
 'home': ['<h1 class="hero__title"', 'مشکل رایج خود را انتخاب کنید', 'ابزارهای رایگان عیب‌یابی',
          'روند کار چطور است؟', 'از مجله پیکسوا', '<ol class="steps"', 'class="cta"', 'data-elementor-type'],
 'problems': ['<h1', 'class="empty"', 'ابزارهای عیب‌یابی', 'class="cta"', 'data-elementor-type'],
 'tools': ['<h1', 'notice notice--info', 'grid--tools', 'class="cta"', 'data-elementor-type'],
 'repair': ['<h1', 'روند تعمیر', '<ol class="steps"', 'data-track-location="repair_hub"', 'data-elementor-type'],
 'booking': ['<h1', 'admin-post.php', '_pixva_nonce', 'data-elementor-type'],
 'tracking': ['<h1', 'data-lookup-result', 'data-endpoint="track"', 'data-elementor-type'],
 'warranty': ['<h1', 'سیاست گارانتی', 'استعلام گارانتی تعمیر', 'data-elementor-type'],
 'faq': ['<h1', 'class="empty"', 'هنوز پرسشی منتشر نشده', 'class="cta"', 'data-elementor-type'],
 'contact': ['<h1', 'ارسال پیام', 'admin-post.php', 'راه‌های ارتباط', 'data-elementor-type', 'ct-consent'],
 'about': ['<h1', 'data-elementor-type', 'btn--ghost'],
}
p = f = 0
fails = []
for page, marks in checks.items():
    h = open(f'/tmp/ve_{page}.html', encoding='utf-8').read()
    for m in marks:
        if m in h:
            p += 1
        else:
            f += 1
            fails.append(f'{page}:{m[:38]}')
h = open('/tmp/v_home.html', encoding='utf-8').read() if False else open('/tmp/ve_home.html', encoding='utf-8').read()
dup_ok = 'aria-labelledby="home-problems"' not in h and 'aria-labelledby="home-tools"' not in h
import glob
nf = all('There has been a critical error' not in open(g, encoding='utf-8').read() for g in glob.glob('/tmp/ve_*.html'))
print(('PASS' if dup_ok else 'FAIL'), 'home: no duplicated classic sections')
print(('PASS' if nf else 'FAIL'), 'all pages: no PHP fatal')
print(f"markers: {p} pass / {f} fail")
if fails:
    print('failed:', fails)
EOF
echo
echo "=== C. Elementor editor loads (10 pages) ==="
for pid in 4 6 7 11 12 13 14 15 16 17; do
  c=$(curl -s -b "$J" -o "/tmp/ve_edl_$pid.html" -w '%{http_code}' --max-time 60 "$B/wp-admin/post.php?post=$pid&action=elementor")
  cfg=$(grep -c 'ElementorConfig' "/tmp/ve_edl_$pid.html" 2>/dev/null || true)
  if [ "$c" = "200" ] && [ "${cfg:-0}" -gt 0 ]; then ok "editor pid=$pid (200, config)"; else no "editor pid=$pid ($c, config=$cfg)"; fi
done
echo
echo "=== D. Orders workflow (badge + filter + search) ==="
curl -s -b "$J" -o /tmp/ve_orders.html "$B/wp-admin/edit.php?post_type=pixva_orders"
set -- $(python3 -c "h=open('/tmp/ve_orders.html',encoding='utf-8').read(); print(' '.join('1' if m in h else '0' for m in ['name=\"pixva_status\"','pixva-badge','pixva-badge--']))")
chk "status filter dropdown" "$1"
chk "status badges in list" "$2"
chk "badge modifier classes" "$3"
CODE=$(python3 -c "import re;h=open('/tmp/ve_orders.html',encoding='utf-8').read();m=re.search(r'PXV-[A-Z0-9-]+',h);print(m.group(0) if m else '')")
if [ -n "$CODE" ]; then
  curl -s -b "$J" -o /tmp/ve_search.html --get "$B/wp-admin/edit.php" \
    --data-urlencode "post_type=pixva_orders" --data-urlencode "s=$CODE"
  python3 -c "h=open('/tmp/ve_search.html',encoding='utf-8').read(); print('1' if '$CODE' in h else '0')" > /tmp/ve_s1
  chk "search by tracking code ($CODE)" "$(cat /tmp/ve_s1)"
else
  no "tracking code found for search test"
fi
echo
echo "=== E. Admin menu + overview surfaces ==="
curl -s -b "$J" -o /tmp/ve_ov.html "$B/wp-admin/admin.php?page=pixva"
python3 -c "
h = open('/tmp/ve_ov.html', encoding='utf-8').read()
i1 = h.find('page=pixva-hub'); i2 = h.find('page=pixva-business')
ok2 = all(m in h for m in ['pixva-quick-actions', 'pixva-panel', 'pixva-checklist', 'pixva-stat-cards'])
print('1' if (0 < i1 < i2 and ok2) else '0')" > /tmp/ve_e1
chk "hub second + quick actions + panels + stat cards + checklist" "$(cat /tmp/ve_e1)"
echo
echo "=== F. Shortcodes page ==="
curl -s -o /tmp/ve_sc.html -w 'HTTP %{http_code}' "$B/sc-test/" >/tmp/ve_sc_code
set -- $(python3 -c "h=open('/tmp/ve_sc.html',encoding='utf-8').read(); print(' '.join('1' if m in h else '0' for m in ['<ol class=\"steps\"','data-track-location=\"sc_cta\"','[pixva_']))")
chk "shortcodes page 200" "$([ "$(cat /tmp/ve_sc_code)" = "HTTP 200" ] && echo 1 || echo 0)"
chk "[pixva_steps] renders" "$1"
chk "[pixva_cta] renders" "$2"
# $3 = 0 means NO raw shortcode present, which is the pass condition.
chk "raw shortcodes never leak" "$([ "$3" = "0" ] && echo 1 || echo 0)"
echo
echo "TOTAL: PASS=$P FAIL=$F"
[ "$F" = "0" ] && exit 0 || exit 1

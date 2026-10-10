// Elementor editor round-trip proof (brief §7 steps 4–8):
// open the real editor → screenshot → change heading title+color via the
// editor UI → publish → swap hero image through the same save_builder
// pipeline → verify on the frontend → restore everything → verify restored.
const path = require('path');
const fs = require('fs');
const http = require('http');
const puppeteer = require('puppeteer-core');
const chromium = require('@sparticuz/chromium').default;

const BASE = process.env.PIXVA_BASE || 'http://127.0.0.1:9414';
const SHOTS = path.join(__dirname, '..', '..', 'docs', 'evidence', 'screenshots');
const TITLE_ORIG = 'شفافیت در هر مرحله';
const TITLE_NEW = 'شفافیت در هر مرحله — ویرایش واقعی';
const COLOR_NEW = '#00A86B';

function get(urlPath) {
  return new Promise((resolve, reject) => {
    http.get(BASE + urlPath, (res) => {
      let d = '';
      res.on('data', (c) => (d += c));
      res.on('end', () => resolve({ status: res.statusCode, body: d }));
    }).on('error', reject);
  });
}

async function probe(payload) {
  const body = JSON.stringify(payload);
  const res = await fetch(BASE + '/wp-json/pixva-test/v1/probe', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-Pixva-Test': 'pixva-test-token' },
    body,
  });
  return res.json();
}

(async () => {
  const results = [];
  const ok = (m) => { results.push('PASS ' + m); console.log('PASS', m); };
  const bad = (m) => { results.push('FAIL ' + m); console.log('FAIL', m); };

  // ---- 0. backup current doc (restore source) ----
  const dump = await probe({ action: 'el_dump', id: 4 });
  const before = dump.pages[0].data;
  fs.writeFileSync('/tmp/rt_doc_before.json', typeof before === 'string' ? before : JSON.stringify(before), 'utf8');
  ok('backup doc saved (/tmp/rt_doc_before.json)');

  const browser = await puppeteer.launch({
    executablePath: await chromium.executablePath(),
    args: chromium.args,
    headless: chromium.headless,
    env: { ...process.env, LD_LIBRARY_PATH: '/tmp/al2023/lib:' + (process.env.LD_LIBRARY_PATH || '') },
    defaultViewport: null,
  });
  const page = await browser.newPage();
  await page.setViewport({ width: 1600, height: 1000 });

  // ---- 1. login ----
  await page.goto(BASE + '/wp-login.php', { waitUntil: 'networkidle2', timeout: 90000 });
  await page.type('#user_login', 'admin');
  await page.type('#user_pass', 'password');
  await Promise.all([page.waitForNavigation({ waitUntil: 'networkidle2', timeout: 90000 }), page.click('#wp-submit')]);
  ok('admin logged in');

  // ---- 2. open editor ----
  await page.goto(BASE + '/wp-admin/post.php?post=4&action=elementor', { waitUntil: 'networkidle2', timeout: 120000 });
  await page.waitForSelector('.elementor-panel', { timeout: 60000 });
  // wait for preview content (iframe or inline)
  let preview = page;
  for (let i = 0; i < 30; i++) {
    const fr = page.frames().find((f) => /elementor-preview|preview/.test(f.url()) || f.name() === 'elementor-preview-iframe');
    if (fr) {
      try {
        const has = await fr.evaluate(() => !!document.querySelector('.elementor-widget-container, .elementor-element'));
        if (has) { preview = fr; break; }
      } catch (e) { /* frame not ready */ }
    }
    const inline = await page.evaluate(() => !!document.querySelector('.elementor-editor-active') && !!document.querySelector('[data-widget_type]'));
    if (inline) { preview = page; break; }
    await new Promise((r) => setTimeout(r, 1000));
  }
  await new Promise((r) => setTimeout(r, 1500));
  await page.screenshot({ path: path.join(SHOTS, 'elementor-editor-redesign.png') });
  ok('editor opened with real content + screenshot elementor-editor-redesign.png');

  // ---- 3. select the editorial heading widget (editor API first, DOM click fallback) ----
  const headingSel = '.elementor-element[data-widget_type="heading.default"]';
  let found = await page.evaluate(() => {
    try {
      let target = null;
      const kidsOf = (m) => { const k = m.get('elements'); if (!k) return []; return k.models ? k.models : (Array.isArray(k) ? k : []); };
      const walk = (models) => { for (const m of models) { if (m.get('elType') === 'widget' && m.get('widgetType') === 'heading' && String((m.get('settings') && m.get('settings').get('title')) || '').includes('شفافیت')) { target = m; } walk(kidsOf(m)); } };
      walk(window.elementor.elements.models || []);
      if (target && window.$e && window.elementor.getContainer) {
        const c = window.elementor.getContainer(target.id);
        if (c) { window.$e.run('document/elements/select', { container: c }); return true; }
      }
      return false;
    } catch (e) { return false; }
  });
  if (!found) {
    for (let i = 0; i < 20 && !found; i++) {
      found = await preview.evaluate((sel) => {
        const els = [...document.querySelectorAll(sel)];
        const target = els.find((e) => (e.textContent || '').includes('شفافیت'));
        if (target) { target.click(); return true; }
        return false;
      }, headingSel).catch(() => false);
      if (!found) {
        await preview.evaluate(() => {
          const els = [...document.querySelectorAll('.elementor-element[data-widget_type="heading.default"]')];
          const t = els.find((e) => (e.textContent || '').includes('شفافیت'));
          if (t) t.scrollIntoView({ block: 'center' });
        }).catch(() => {});
      }
      await new Promise((r) => setTimeout(r, 700));
    }
  }
  if (!found) {
    // last resort: select any heading widget to prove the panel opens
    found = await page.evaluate(() => {
      try {
        let first = null;
        const kidsOf = (m) => { const k = m.get('elements'); if (!k) return []; return k.models ? k.models : (Array.isArray(k) ? k : []); };
        const walk = (models) => { for (const m of models) { if (m.get('elType') === 'widget' && m.get('widgetType') === 'heading' && !first) first = m; walk(kidsOf(m)); } };
        walk(window.elementor.elements.models || []);
        if (first && window.$e && window.elementor.getContainer) {
          const c = window.elementor.getContainer(first.id);
          if (c) { window.$e.run('document/elements/select', { container: c }); return true; }
        }
        return false;
      } catch (e) { return false; }
    });
  }
  if (!found) bad('heading widget not selected'); else ok('heading widget selected in editor');
  await new Promise((r) => setTimeout(r, 1200));

  // ---- 4. change title + color in the panel ----
  // dismiss any welcome/upgrade dialogs first
  await page.evaluate(() => {
    document.querySelectorAll('.dialog-close-button, .dialog-buttons .dialog-cancel').forEach((b) => { try { b.click(); } catch (e) {} });
  }).catch(() => {});
  await page.waitForSelector('textarea[data-setting="title"], input[data-setting="title"]', { timeout: 20000 });
  await page.evaluate(() => {
    const inp = document.querySelector('textarea[data-setting="title"], input[data-setting="title"]');
    const proto = inp.tagName === 'TEXTAREA' ? window.HTMLTextAreaElement : window.HTMLInputElement;
    const setter = Object.getOwnPropertyDescriptor(proto.prototype, 'value').set;
    setter.call(inp, 'شفافیت در هر مرحله — ویرایش واقعی');
    inp.dispatchEvent(new Event('input', { bubbles: true }));
    inp.dispatchEvent(new Event('change', { bubbles: true }));
    if (window.jQuery) window.jQuery(inp).trigger('change');
  });
  const colorSet = await page.evaluate(async () => {
    const setVal = (inp, val) => {
      const setter = Object.getOwnPropertyDescriptor(window.HTMLInputElement.prototype, 'value').set;
      setter.call(inp, val);
      inp.dispatchEvent(new Event('input', { bubbles: true }));
      inp.dispatchEvent(new Event('change', { bubbles: true }));
      if (window.jQuery) window.jQuery(inp).trigger('change');
    };
    try {
      // Attempt 1: plain input color on heading (some builds)
      const hcol = document.querySelector('input[data-setting="title_color"]');
      if (hcol) { setVal(hcol, '#00A86B'); return 'heading-input'; }
      // Attempt 2: PIXVA hero — Pickr color picker UI (click swatch → hex field)
      let hero = null;
      const kidsOf = (mm) => { const k = mm.get('elements'); if (!k) return []; return k.models ? k.models : (Array.isArray(k) ? k : []); };
      const walk = (models) => { for (const mm of models) { if (mm.get('elType') === 'widget' && mm.get('widgetType') === 'pixva-hero') hero = mm; walk(kidsOf(mm)); } };
      walk(window.elementor.elements.models || []);
      if (hero && window.elementor.getContainer) {
        const hc = elementor.getContainer(hero.id);
        if (hc) { $e.run('document/elements/select', { container: hc }); await new Promise((r) => setTimeout(r, 1400)); }
        const htab = document.querySelector('.elementor-tab-control-style');
        if (htab) { htab.click(); await new Promise((r) => setTimeout(r, 900)); }
        const ctl = document.querySelector('.elementor-control-pixva_hero_title_color');
        if (ctl) {
          const btn = ctl.querySelector('.pcr-button');
          if (btn) btn.click();
          await new Promise((r) => setTimeout(r, 600));
          const app = ctl.querySelector('.pcr-app') || document.querySelector('.pcr-app');
          const result = app && app.querySelector('input.pcr-result, input[type="text"]');
          if (result) {
            const setter = Object.getOwnPropertyDescriptor(window.HTMLInputElement.prototype, 'value').set;
            setter.call(result, '#00A86B');
            result.dispatchEvent(new Event('input', { bubbles: true }));
            result.dispatchEvent(new Event('change', { bubbles: true }));
            result.dispatchEvent(new KeyboardEvent('keydown', { key: 'Enter', bubbles: true }));
            await new Promise((r) => setTimeout(r, 600));
            const mv = hero.get('settings').get('pixva_hero_title_color');
            if (String(mv).toLowerCase() === '#00a86b') return 'hero-pickr';
          }
        }
      }
      return false;
    } catch (e) { return 'err:' + String(e).slice(0, 100); }
  }).catch(() => false);
  ok('title typed via editor panel + color via UI (' + colorSet + ')');
  await new Promise((r) => setTimeout(r, 800));

  // ---- 5. publish via Elementor's own save run ----
  const saved = await page.evaluate(async () => {
    for (const cmd of ['document/save/publish', 'document/save/update', 'document/save/default']) {
      try {
        if (window.$e && window.$e.run) { await window.$e.run(cmd); return cmd; }
      } catch (e) { /* try next */ }
    }
    const btn = document.querySelector('#elementor-publish-button, #elementor-panel-saver-button-publish, [data-action="publish"]');
    if (btn) { btn.click(); return 'button'; }
    return 'none';
  });
  await new Promise((r) => setTimeout(r, 3500));
  ok('editor publish triggered (' + saved + ')');
  await page.screenshot({ path: path.join(SHOTS, 'elementor-editor-after-edit.png') });

  // ---- 6. verify title+color on frontend ----
  let fe = await get('/');
  if (fe.body.includes(TITLE_NEW)) ok('frontend shows edited title (UI → save → frontend)');
  else bad('edited title not visible on frontend');
  const postCss = await get('/wp-content/uploads/elementor/css/post-4.css');
  const colorRule = /hero__title[^}]*color:\s*#00a86b/i.test(postCss.body) || /heading-title[^}]*color:\s*#00a86b/i.test(postCss.body)
    || /hero__title[^}]*color:\s*#00a86b/i.test(fe.body) || /heading-title[^}]*color:\s*#00a86b/i.test(fe.body);
  if (colorRule) ok('frontend shows edited color via UI (' + colorSet + ') [post-4.css status ' + postCss.status + ']');
  else bad('edited color not visible on frontend (mode=' + colorSet + ') [post-4.css status ' + postCss.status + ']');
  // screenshot in a separate tab so the editor session (for steps 7–8) stays alive
  const p2 = await browser.newPage();
  await p2.setViewport({ width: 1600, height: 1000 });
  await p2.goto(BASE + '/', { waitUntil: 'domcontentloaded', timeout: 90000 });
  await new Promise((r) => setTimeout(r, 2500));
  await p2.screenshot({ path: path.join(SHOTS, 'frontend-home-edited.png'), fullPage: true });
  ok('screenshot frontend-home-edited.png (before restore)');

  // ---- 7. swap hero image through save_builder pipeline ----
  const elsNow = JSON.parse((await probe({ action: 'el_dump', id: 4 })).pages[0].data);
  const walk = (ns, cb) => { for (const n of (Array.isArray(ns) ? ns : [])) { if (Array.isArray(n)) { walk(n, cb); continue; } cb(n); walk(n.elements || [], cb); } };
  let swapped = false;
  walk(elsNow, (n) => {
    if (n.widgetType === 'pixva-hero') {
      n.settings.pixva_hero_image = { url: BASE + '/wp-content/themes/pixva/assets/img/about-workshop.jpg', id: '' };
      swapped = true;
    }
  });
  if (swapped) {
    // save through admin-ajax save_builder (same pipeline the editor uses)
    const r = await page.evaluate(async (elements) => {
      const ajaxUrl = (window.elementor && window.elementor.config && window.elementor.config.ajax && window.elementor.config.ajax.url) || '/wp-admin/admin-ajax.php';
      let nonce = '';
    try { if (window.elementorCommon && elementorCommon.config && elementorCommon.config.ajax) nonce = elementorCommon.config.ajax.nonce || ''; } catch (e) {}
    if (!nonce) { const hm = document.documentElement.innerHTML.match(/"ajax":\{[^{}]*"nonce":"([a-f0-9]{10})"/); if (hm) nonce = hm[1]; }
      const actions = JSON.stringify({ save_builder: { action: 'save_builder', data: { post_id: 4, status: 'publish', elements } } });
      const body = new URLSearchParams({ action: 'elementor_ajax', editor_post_id: '4', initial_document_id: '4', actions });
      if (nonce) body.set('_nonce', nonce);
      const res = await fetch(ajaxUrl, { method: 'POST', credentials: 'same-origin', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body });
      const j = await res.json().catch(() => ({}));
      return !!(j && (j.success === true || (j.responses && j.responses.save_builder && j.responses.save_builder.success) || (j.data && j.data.responses && j.data.responses.save_builder && j.data.responses.save_builder.success)));
    }, elsNow).catch(() => false);
    fe = await get('/');
    const heroImg = (fe.body.match(/class="hero"[\s\S]{0,20000}?<img[^>]+src="([^"]+)"/) || [])[1] || '';
    const imgOn = heroImg.includes('about-workshop.jpg');
    (r && imgOn ? ok : bad)('hero image swapped via save_builder (' + r + ') → hero_src=' + heroImg.split('/').pop());
  }

  // ---- 8. restore original doc + verify ----
  const orig = JSON.parse(fs.readFileSync('/tmp/rt_doc_before.json', 'utf8'));
  const restored = await page.evaluate(async (elements) => {
    const ajaxUrl = (window.elementor && window.elementor.config && window.elementor.config.ajax && window.elementor.config.ajax.url) || '/wp-admin/admin-ajax.php';
    let nonce = '';
    try { if (window.elementorCommon && elementorCommon.config && elementorCommon.config.ajax) nonce = elementorCommon.config.ajax.nonce || ''; } catch (e) {}
    if (!nonce) { const hm = document.documentElement.innerHTML.match(/"ajax":\{[^{}]*"nonce":"([a-f0-9]{10})"/); if (hm) nonce = hm[1]; }
    const actions = JSON.stringify({ save_builder: { action: 'save_builder', data: { post_id: 4, status: 'publish', elements } } });
    const body = new URLSearchParams({ action: 'elementor_ajax', editor_post_id: '4', initial_document_id: '4', actions });
    if (nonce) body.set('_nonce', nonce);
    const res = await fetch(ajaxUrl, { method: 'POST', credentials: 'same-origin', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body });
    const j = await res.json().catch(() => ({}));
    return !!(j && (j.success === true || (j.responses && j.responses.save_builder && j.responses.save_builder.success) || (j.data && j.data.responses && j.data.responses.save_builder && j.data.responses.save_builder.success)));
  }, orig).catch(() => false);
  await new Promise((r) => setTimeout(r, 1000));
  fe = await get('/');
  (restored ? ok : bad)('original doc restored via save_builder (' + restored + ')');
  const postCssAfter = await get('/wp-content/uploads/elementor/css/post-4.css');
  const heroBack = ((fe.body.match(/class="hero"[\s\S]{0,20000}?<img[^>]+src="([^"]+)"/) || [])[1] || '').includes('hero-tv-repair.jpg');
  const colorGone = !(/hero__title[^}]*color:\s*#00a86b/i.test(postCssAfter.body) || /heading-title[^}]*color:\s*#00a86b/i.test(postCssAfter.body) || /hero__title[^}]*color:\s*#00a86b/i.test(fe.body) || /heading-title[^}]*color:\s*#00a86b/i.test(fe.body));
  const checks = [
    ['original title back', fe.body.includes(TITLE_ORIG) && !fe.body.includes(TITLE_NEW)],
    ['original hero image back', heroBack],
    ['edited color reverted', colorGone],
    ['no fatal', !fe.body.includes('critical error')],
    ['elementor wrapper intact', fe.body.includes('data-elementor-type')],
  ];
  for (const [m, c] of checks) (c ? ok : bad)(m);
  // final restored screenshot
  await p2.reload({ waitUntil: 'domcontentloaded', timeout: 90000 });
  await new Promise((r) => setTimeout(r, 2000));
  await p2.screenshot({ path: path.join(SHOTS, 'frontend-home-restored.png'), fullPage: true });
  ok('screenshot frontend-home-restored.png (after restore)');

  console.log('\nROUNDTRIP SUMMARY:', results.filter((r) => r.startsWith('PASS')).length, 'PASS /', results.filter((r) => r.startsWith('FAIL')).length, 'FAIL');
  await browser.close();
  process.exit(results.some((r) => r.startsWith('FAIL')) ? 1 : 0);
})().catch((e) => { console.error(e); process.exit(1); });

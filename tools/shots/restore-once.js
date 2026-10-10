const fs = require('fs');
const puppeteer = require('puppeteer-core');
const chromium = require('@sparticuz/chromium').default;
const BASE = process.env.PIXVA_BASE || 'http://127.0.0.1:9414';
(async () => {
  const browser = await puppeteer.launch({
    executablePath: await chromium.executablePath(),
    args: chromium.args,
    headless: chromium.headless,
    env: { ...process.env, LD_LIBRARY_PATH: '/tmp/al2023/lib:' + (process.env.LD_LIBRARY_PATH || '') },
    defaultViewport: null,
  });
  const page = await browser.newPage();
  await page.goto(BASE + '/wp-login.php', { waitUntil: 'networkidle2', timeout: 90000 });
  await page.type('#user_login', 'admin');
  await page.type('#user_pass', 'password');
  await Promise.all([page.waitForNavigation({ waitUntil: 'networkidle2', timeout: 90000 }), page.click('#wp-submit')]);
  await page.goto(BASE + '/wp-admin/post.php?post=4&action=elementor', { waitUntil: 'networkidle2', timeout: 120000 });
  await page.waitForSelector('.elementor-panel', { timeout: 60000 });
  await new Promise((r) => setTimeout(r, 3000));
  const orig = JSON.parse(fs.readFileSync('/tmp/rt_doc_before.json', 'utf8'));
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
    return { ok: !!(j && (j.success === true || (j.data && j.data.responses && j.data.responses.save_builder && j.data.responses.save_builder.success) || (j.responses && j.responses.save_builder && j.responses.save_builder.success))), tail: JSON.stringify(j).slice(0, 200) };
  }, orig);
  console.log('restore:', JSON.stringify(r));
  await browser.close();
})().catch((e) => { console.error(e); process.exit(1); });

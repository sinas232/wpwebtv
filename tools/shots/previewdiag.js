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
  await page.setViewport({ width: 1600, height: 1000 });
  const fails = [];
  page.on('requestfailed', (r) => fails.push(r.url().slice(0, 140) + ' :: ' + (r.failure() && r.failure().errorText)));
  page.on('pageerror', (e) => fails.push('pageerror: ' + String(e).slice(0, 300)));
  await page.goto(BASE + '/wp-login.php', { waitUntil: 'networkidle2', timeout: 90000 });
  await page.type('#user_login', 'admin');
  await page.type('#user_pass', 'password');
  await Promise.all([page.waitForNavigation({ waitUntil: 'networkidle2', timeout: 90000 }), page.click('#wp-submit')]);
  await page.goto(BASE + '/wp-admin/post.php?post=4&action=elementor', { waitUntil: 'networkidle2', timeout: 120000 });
  await page.waitForSelector('.elementor-panel', { timeout: 60000 });
  await new Promise((r) => setTimeout(r, 12000));
  const info = await page.evaluate(() => {
    const frames = [];
    document.querySelectorAll('iframe').forEach((f) => frames.push({ id: f.id, src: (f.src || '').slice(0, 160), cls: f.className.slice(0, 60) }));
    const load = document.querySelector('#elementor-preview-loading, .elementor-preview-loading');
    return {
      frames,
      loadEl: load ? { id: load.id, cls: load.className, disp: getComputedStyle(load).display, vis: getComputedStyle(load).visibility, op: getComputedStyle(load).opacity } : null,
      dialog: !!document.querySelector('.dialog-widget'),
      editorReady: document.documentElement.classList.contains('elementor-editor-active'),
      topbar: !!document.querySelector('#elementor-mode-switcher'),
    };
  });
  console.log(JSON.stringify(info, null, 1));
  // try loading the preview URL directly for status
  const frameUrl = (info.frames[0] || {}).src;
  if (frameUrl) {
    const r = await page.evaluate(async (u) => {
      try { const res = await fetch(u, { credentials: 'same-origin' }); return { status: res.status, len: (await res.text()).length }; } catch (e) { return { err: String(e).slice(0, 150) }; }
    }, frameUrl);
    console.log('preview fetch:', JSON.stringify(r));
  }
  console.log('failures:', fails.filter(f => !/gravatar|googleapis|gstatic|jsdelivr|google/.test(f)).slice(0, 10));
  await browser.close();
})().catch((e) => { console.error(e); process.exit(1); });

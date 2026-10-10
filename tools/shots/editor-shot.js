'use strict';
const m = require('@sparticuz/chromium');
const chromium = m.default;
const fs = require('fs'); const path = require('path');
const puppeteer = require('puppeteer-core');
const BASE = process.argv[2] || 'http://127.0.0.1:9414';
const POST = process.argv[3] || '4';
const OUT = path.resolve(__dirname, '..', '..', 'docs', 'evidence', 'screenshots');
function visible(sel) {
  const el = document.querySelector(sel);
  return !!el && !!(el.offsetWidth || el.offsetHeight || el.getClientRects().length);
}
(async () => {
  const pkgRoot = path.dirname(path.dirname(require.resolve('@sparticuz/chromium')));
  if (!fs.existsSync('/tmp/al2023')) await m.inflate(path.join(pkgRoot, 'bin', 'al2023.tar.br'));
  const browser = await puppeteer.launch({
    executablePath: await chromium.executablePath(),
    args: chromium.args, headless: chromium.headless,
    env: { ...process.env, LD_LIBRARY_PATH: '/tmp/al2023/lib' },
    defaultViewport: { width: 1600, height: 1000 },
  });
  const page = await browser.newPage();
  page.setDefaultTimeout(120000);
  await page.goto(BASE + '/wp-login.php', { waitUntil: 'domcontentloaded' });
  await page.waitForSelector('#user_login', { timeout: 30000 });
  await page.type('#user_login', 'admin');
  await page.type('#user_pass', 'password');
  await Promise.all([page.waitForNavigation({ waitUntil: 'networkidle2' }), page.click('#wp-submit')]);
  // warm preview
  await page.goto(BASE + `/?elementor-preview=${POST}`, { waitUntil: 'networkidle2' }).catch(() => {});
  await page.goto(BASE + `/wp-admin/post.php?post=${POST}&action=elementor`, { waitUntil: 'networkidle2' });
  // robust readiness: #elementor-loading hidden + panel visible + iframe widgets
  const ready = await page.waitForFunction(() => {
    const loader = document.querySelector('#elementor-loading');
    const loaderHidden = !loader || getComputedStyle(loader).display === 'none';
    const panel = document.querySelector('#elementor-panel');
    const panelVisible = !!panel && !!(panel.offsetWidth || panel.offsetHeight || panel.getClientRects().length);
    let widgets = -1;
    try {
      const f = document.querySelector('#elementor-preview iframe');
      if (f && f.contentDocument) widgets = f.contentDocument.querySelectorAll('.elementor-widget').length;
    } catch (e) { widgets = -2; }
    return loaderHidden && panelVisible && widgets > 3;
  }, { timeout: 120000, polling: 2000 }).then(() => true).catch(() => false);
  console.log('editor ready:', ready);
  // dismiss safe-mode toast if present
  await page.evaluate(() => {
    const t = [...document.querySelectorAll('.dialog-notification, .elementor-toast, [class*=toast], [class*=notification]')]
      .find((n) => /Can't Edit|Safe Mode/i.test(n.textContent || ''));
    if (t) { const x = t.querySelector('button, .dialog-dismiss-button, .eicon-close'); if (x) x.click(); else t.remove(); }
  });
  await new Promise((r) => setTimeout(r, 2000));
  const st = await page.evaluate(() => {
    const vis = (el) => !!el && !!(el.offsetWidth || el.offsetHeight || el.getClientRects().length);
    let widgets = 0; let frameTitle = '';
    try { const f = document.querySelector('#elementor-preview iframe'); widgets = f.contentDocument.querySelectorAll('.elementor-widget').length; frameTitle = f.contentDocument.title; } catch (e) {}
    return { panel: vis(document.querySelector('#elementor-panel')), widgets, frameTitle,
      spinner: [...document.querySelectorAll('.elementor-loading')].some(vis) };
  });
  console.log('state', JSON.stringify(st));
  const file = path.join(OUT, `elementor-editor-post${POST}.png`);
  await page.screenshot({ path: file });
  console.log('SHOT', file, fs.statSync(file).size, 'bytes');
  await browser.close();
})().catch((e) => { console.error('ERR', e); process.exit(1); });

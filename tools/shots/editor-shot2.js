'use strict';
const m = require('@sparticuz/chromium');
const chromium = m.default;
const fs = require('fs'); const path = require('path');
const puppeteer = require('puppeteer-core');
const BASE = process.argv[2] || 'http://127.0.0.1:9414';
const OUT = path.resolve(__dirname, '..', '..', 'docs', 'evidence', 'screenshots');
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
  await page.waitForSelector('#user_login');
  await page.type('#user_login', 'admin'); await page.type('#user_pass', 'password');
  await Promise.all([page.waitForNavigation({ waitUntil: 'networkidle2' }), page.click('#wp-submit')]);
  await page.goto(BASE + '/?elementor-preview=4', { waitUntil: 'networkidle2' }).catch(() => {});
  await page.goto(BASE + '/wp-admin/post.php?post=4&action=elementor', { waitUntil: 'networkidle2' });
  await page.waitForFunction(() => {
    const l = document.querySelector('#elementor-loading');
    return (!l || getComputedStyle(l).display === 'none') && !!document.querySelector('#elementor-panel');
  }, { timeout: 120000, polling: 2000 });
  await new Promise((r) => setTimeout(r, 2500));
  // 1) search PIXVA widgets
  const search = await page.$('#elementor-panel__search-input, .elementor-search input, #elementor-panel input[type=search]');
  if (search) {
    await search.click({ clickCount: 3 });
    await search.type('pixva');
    await new Promise((r) => setTimeout(r, 1500));
    const f1 = path.join(OUT, 'elementor-editor-pixva-widgets.png');
    await page.screenshot({ path: f1 });
    console.log('SHOT elementor-editor-pixva-widgets', fs.statSync(f1).size);
    await search.click({ clickCount: 3 });
    await page.keyboard.press('Backspace');
    await new Promise((r) => setTimeout(r, 1200));
  } else {
    console.log('search input not found');
  }
  // 2) click the hero widget in canvas -> settings panel
  const frame = await page.frames().find((fr) => fr.url().includes('elementor-preview'));
  if (frame) {
    await frame.evaluate(() => {
      const el = document.querySelector('.elementor-widget, .e-con');
      if (el) el.dispatchEvent(new MouseEvent('click', { bubbles: true }));
    }).catch((e) => console.log('click err', e.message));
    await new Promise((r) => setTimeout(r, 2500));
    const f2 = path.join(OUT, 'elementor-editor-widget-settings.png');
    await page.screenshot({ path: f2 });
    console.log('SHOT elementor-editor-widget-settings', fs.statSync(f2).size);
  }
  await browser.close();
})().catch((e) => { console.error('ERR', e); process.exit(1); });

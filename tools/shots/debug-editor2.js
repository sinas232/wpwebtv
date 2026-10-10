'use strict';
const m = require('@sparticuz/chromium');
const chromium = m.default;
const fs = require('fs'); const path = require('path');
const puppeteer = require('puppeteer-core');
const BASE = 'http://127.0.0.1:9414';
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
  page.setDefaultTimeout(60000);
  const failed = new Map();
  page.on('requestfailed', (req) => {
    const u = req.url();
    if (u.startsWith(BASE)) failed.set(u, req.failure()?.errorText);
  });
  await page.goto(BASE + '/wp-login.php', { waitUntil: 'domcontentloaded' });
  await page.waitForSelector('#user_login', { timeout: 30000 });
  await page.type('#user_login', 'admin'); await page.type('#user_pass', 'password');
  await Promise.all([page.waitForNavigation({ waitUntil: 'networkidle2' }), page.click('#wp-submit')]);
  // warm up: load preview standalone first
  await page.goto(BASE + '/?elementor-preview=4', { waitUntil: 'networkidle2' }).catch((e) => console.log('warm failed', e.message));
  console.log('warmed preview standalone, title:', await page.title());
  await page.goto(BASE + '/wp-admin/post.php?post=4&action=elementor', { waitUntil: 'networkidle2' });
  for (let i = 0; i < 6; i++) {
    await new Promise((r) => setTimeout(r, 8000));
    const st = await page.evaluate(() => ({
      loading: !!document.querySelector('.elementor-loading'),
      frame: !!document.querySelector('#elementor-preview iframe'),
      inFrame: (() => { const f = document.querySelector('#elementor-preview iframe'); try { return f.contentDocument ? !!f.contentDocument.querySelector('.elementor-widget') : 'xdom'; } catch (e) { return 'cors'; } })(),
    }));
    console.log(`t+${(i + 1) * 8}s`, JSON.stringify(st), 'failed:', failed.size);
    if (!st.loading && st.inFrame !== false) break;
  }
  console.log('FAILED URLS:');
  for (const [u, e] of [...failed.entries()].slice(0, 25)) console.log(' ', e, u.slice(0, 150));
  await page.screenshot({ path: '/tmp/editor_debug2.png' });
  await browser.close();
})().catch((e) => { console.error('ERR', e); process.exit(1); });

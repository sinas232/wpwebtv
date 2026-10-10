/**
 * PIXVA visual evidence capture — real Chromium (bundled via @sparticuz/chromium)
 * against the live Playground test server. Produces docs/evidence/screenshots/*.
 * Usage: node tools/shots/run.js [baseUrl]
 */
'use strict';
const m = require('@sparticuz/chromium');
const chromium = m.default;
const fs = require('fs');
const path = require('path');
const puppeteer = require('puppeteer-core');

const BASE = process.argv[2] || 'http://127.0.0.1:9414';
const OUT = path.resolve(__dirname, '..', '..', 'docs', 'evidence', 'screenshots');

async function shot(page, name, opts = {}) {
  const file = path.join(OUT, name + '.png');
  await page.screenshot({ path: file, fullPage: !!opts.fullPage });
  const st = fs.statSync(file);
  console.log(`SHOT ${name} ${st.size} bytes`);
}

(async () => {
  if (!fs.existsSync('/tmp/al2023')) {
    const pkgRoot = path.dirname(path.dirname(require.resolve('@sparticuz/chromium')));
    await m.inflate(path.join(pkgRoot, 'bin', 'al2023.tar.br'));
  }
  const browser = await puppeteer.launch({
    executablePath: await chromium.executablePath(),
    args: chromium.args,
    headless: chromium.headless,
    env: { ...process.env, LD_LIBRARY_PATH: '/tmp/al2023/lib:' + (process.env.LD_LIBRARY_PATH || '') },
    defaultViewport: { width: 1440, height: 2600 },
  });

  // ---------- visitor context: public pages ----------
  let page = await browser.newPage();
  page.setDefaultTimeout(60000);
  const pages = [
    ['home', '/'],
    ['problems', '/problems/'],
    ['tools', '/tools/'],
    ['faq', '/faq/'],
    ['contact', '/contact/'],
    ['booking', '/repair/book/'],
    ['tracking', '/tracking/'],
    ['warranty', '/warranty/'],
    ['repair', '/repair/'],
    ['about', '/about/'],
  ];
  for (const [name, url] of pages) {
    await page.goto(BASE + url, { waitUntil: 'networkidle2' });
    await new Promise((r) => setTimeout(r, 700));
    await shot(page, 'frontend-' + name, { fullPage: name === 'home' });
  }
  await page.close();

  // ---------- editor context: login ----------
  page = await browser.newPage();
  page.setDefaultTimeout(90000);
  await page.goto(BASE + '/wp-login.php', { waitUntil: 'domcontentloaded' });
  await page.type('#user_login', 'admin');
  await page.type('#user_pass', 'password');
  await Promise.all([
    page.waitForNavigation({ waitUntil: 'networkidle2' }),
    page.click('#wp-submit'),
  ]);
  console.log('logged in, url =', page.url());

  // admin: overview, hub, orders, business, dashboard
  const adminPages = [
    ['admin-overview', '/wp-admin/admin.php?page=pixva'],
    ['admin-hub', '/wp-admin/admin.php?page=pixva-hub'],
    ['admin-orders', '/wp-admin/edit.php?post_type=pixva_orders'],
    ['admin-business', '/wp-admin/admin.php?page=pixva-business'],
    ['admin-dashboard', '/wp-admin/index.php'],
  ];
  for (const [name, url] of adminPages) {
    await page.goto(BASE + url, { waitUntil: 'networkidle2' });
    await new Promise((r) => setTimeout(r, 700));
    await shot(page, name, { fullPage: name === 'admin-overview' || name === 'admin-hub' });
  }

  // Editor shots live in editor-shot.js / editor-shot2.js (they need a
  // longer warm-up on the single-worker server).
  await browser.close();
  console.log('done ->', OUT);
})().catch((e) => { console.error('ERR', e); process.exit(1); });

// Authenticated admin screenshotter: logs in, then shoots each PIXVA admin screen.
const path = require('path');
const puppeteer = require('puppeteer-core');
const chromium = require('@sparticuz/chromium').default;

const BASE = process.env.PIXVA_BASE || 'http://127.0.0.1:9414';
const SHOTS = path.join(__dirname, '..', '..', 'docs', 'evidence', 'screenshots');

const PAGES = [
  ['/wp-admin/admin.php?page=pixva', 'a-overview'],
  ['/wp-admin/admin.php?page=pixva-hub', 'a-hub'],
  ['/wp-admin/admin.php?page=pixva-business', 'a-business'],
  ['/wp-admin/admin.php?page=pixva-pricing', 'a-pricing'],
  ['/wp-admin/admin.php?page=pixva-redirects', 'a-redirects'],
  ['/wp-admin/admin.php?page=pixva-migration', 'a-migration'],
];

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
  const errs = [];
  page.on('pageerror', (e) => errs.push('pageerror: ' + String(e).slice(0, 200)));
  page.on('console', (m) => { if (m.type() === 'error') errs.push('console: ' + m.text().slice(0, 200)); });

  await page.goto(BASE + '/wp-login.php', { waitUntil: 'networkidle2', timeout: 90000 });
  await page.type('#user_login', 'admin');
  await page.type('#user_pass', 'password');
  await Promise.all([page.waitForNavigation({ waitUntil: 'networkidle2', timeout: 90000 }), page.click('#wp-submit')]);

  for (const [url, name] of PAGES) {
    try {
      await page.goto(BASE + url, { waitUntil: 'networkidle2', timeout: 90000 });
      await new Promise((r) => setTimeout(r, 900));
      const isLogin = await page.evaluate(() => !!document.querySelector('#loginform'));
      await page.screenshot({ path: path.join(SHOTS, name + '.png'), fullPage: true });
      console.log((isLogin ? 'LOGIN-PAGE! ' : 'OK ') + name);
    } catch (e) {
      console.log('FAIL ' + name + ' ' + String(e).slice(0, 120));
    }
  }
  console.log('errors:', errs.length ? errs.slice(0, 6).join(' | ') : 'NONE');
  await browser.close();
})().catch((e) => { console.error(e); process.exit(1); });

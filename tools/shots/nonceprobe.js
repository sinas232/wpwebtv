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
  const dbg = await page.evaluate(() => ({
    cfgAjax: JSON.stringify((window.elementor && window.elementor.config && window.elementor.config.ajax) || null),
    common: JSON.stringify((window.elementor && elementor.config && elementor.config.elements ? 1 : 0)),
    restNonce: (document.querySelector('meta[name="elementor-ajax-nonce"]') || {}).content || null,
    wpApi: !!window.wpApiSettings,
  }));
  const html = await page.content();
  const m = html.match(/"nonce":"([a-f0-9]{10})"/);
  console.log(JSON.stringify({ dbg, htmlNonce: m && m[1] }));
  await browser.close();
})().catch((e) => { console.error(e); process.exit(1); });

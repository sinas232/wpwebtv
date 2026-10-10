const m = require('@sparticuz/chromium');
const chromium = m.default;
const fs = require('fs');
const path = require('path');
const pkgRoot = path.dirname(path.dirname(require.resolve('@sparticuz/chromium')));
(async () => {
  if (!fs.existsSync('/tmp/al2023')) {
    const out = await m.inflate(path.join(pkgRoot, 'bin', 'al2023.tar.br'));
    console.log('al2023 ->', out);
  }
  const execPath = await chromium.executablePath();
  const puppeteer = require('puppeteer-core');
  const browser = await puppeteer.launch({
    executablePath: execPath,
    args: chromium.args,
    headless: chromium.headless,
    env: { ...process.env, LD_LIBRARY_PATH: '/tmp/al2023/lib:' + (process.env.LD_LIBRARY_PATH || '') },
    defaultViewport: { width: 1440, height: 2400 },
  });
  const page = await browser.newPage();
  page.setDefaultTimeout(45000);
  await page.goto('http://127.0.0.1:9414/', { waitUntil: 'networkidle2' });
  await new Promise(r => setTimeout(r, 1200));
  console.log('TITLE:', await page.title());
  await page.screenshot({ path: '/tmp/pp_test_home.png' });
  console.log('saved');
  await browser.close();
})().catch(e => { console.error('ERR', e.message); process.exit(1); });

// Quick single-page screenshot for design iteration.
// Usage: node tools/shots/quick.js <path> [outName] [width] [height] [full=1]
const path = require('path');
const fs = require('fs');
const puppeteer = require('puppeteer-core');
const chromium = require('@sparticuz/chromium').default;
const { inflate } = require('@sparticuz/chromium');

(async () => {
  const base = process.env.PIXVA_BASE || 'http://127.0.0.1:9414';
  const urlPath = process.argv[2] || '/';
  const name = process.argv[3] || 'quick';
  const width = parseInt(process.argv[4] || '1440', 10);
  const height = parseInt(process.argv[5] || '900', 10);
  const full = (process.argv[6] || '1') === '1';

  const pkgRoot = path.dirname(path.dirname(require.resolve('@sparticuz/chromium')));
  const alb = path.join(pkgRoot, 'bin', 'al2023.tar.br');
  if (!fs.existsSync('/tmp/al2023')) await inflate(alb);

  const browser = await puppeteer.launch({
    executablePath: await chromium.executablePath(),
    args: chromium.args,
    headless: chromium.headless,
    env: { ...process.env, LD_LIBRARY_PATH: '/tmp/al2023/lib:' + (process.env.LD_LIBRARY_PATH || '') },
    defaultViewport: null,
  });
  const page = await browser.newPage();
  await page.setViewport({ width, height, deviceScaleFactor: 1 });
  const problems = [];
  page.on('console', (m) => { if (m.type() === 'error') problems.push('console: ' + m.text().slice(0, 300)); });
  page.on('pageerror', (e) => problems.push('pageerror: ' + String(e).slice(0, 300)));
  const resp = await page.goto(base + urlPath, { waitUntil: 'networkidle2', timeout: 90000 });
  await new Promise((r) => setTimeout(r, 1200));
  const out = path.join(__dirname, '..', '..', 'docs', 'evidence', 'screenshots', `${name}.png`);
  await page.screenshot({ path: out, fullPage: full });
  console.log('SHOT', name, resp.status(), fs.statSync(out).size, 'bytes');
  if (problems.length) console.log('ISSUES:\n' + problems.join('\n'));
  else console.log('NO console/page errors');
  await browser.close();
})().catch((e) => { console.error(e); process.exit(1); });

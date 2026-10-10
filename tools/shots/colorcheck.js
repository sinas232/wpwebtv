const puppeteer = require('puppeteer-core');
const chromium = require('@sparticuz/chromium').default;
(async () => {
  const b = await puppeteer.launch({ executablePath: await chromium.executablePath(), args: chromium.args, headless: chromium.headless,
    env: { ...process.env, LD_LIBRARY_PATH: '/tmp/al2023/lib:' + (process.env.LD_LIBRARY_PATH || '') }, defaultViewport: null });
  const p = await b.newPage();
  await p.goto('http://127.0.0.1:9414/', { waitUntil: 'networkidle2', timeout: 90000 });
  const r = await p.evaluate(() => {
    const band = document.querySelector('.band--dark');
    const h = band && band.querySelector('.elementor-heading-title');
    return h ? { color: getComputedStyle(h).color, text: h.textContent.slice(0, 40) } : null;
  });
  console.log(JSON.stringify(r));
  await b.close();
})().catch(e => { console.error(e); process.exit(1); });

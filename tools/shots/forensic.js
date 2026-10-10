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
  page.setDefaultTimeout(120000);
  await page.goto(BASE + '/wp-login.php', { waitUntil: 'domcontentloaded' });
  await page.waitForSelector('#user_login');
  await page.type('#user_login', 'admin'); await page.type('#user_pass', 'password');
  await Promise.all([page.waitForNavigation({ waitUntil: 'networkidle2' }), page.click('#wp-submit')]);
  await page.goto(BASE + '/?elementor-preview=4', { waitUntil: 'networkidle2' }).catch(() => {});
  await page.goto(BASE + '/wp-admin/post.php?post=4&action=elementor', { waitUntil: 'networkidle2' });
  await new Promise((r) => setTimeout(r, 15000));
  const forensics = await page.evaluate(() => {
    const out = { topSpinners: [], overlayEls: [], iframeInfo: null, panelRect: null };
    const vis = (el) => !!el && !!(el.offsetWidth || el.offsetHeight || el.getClientRects().length);
    document.querySelectorAll('.elementor-loading, [class*=loading]').forEach((el) => {
      if (vis(el)) out.topSpinners.push(el.tagName + '.' + el.className + ' z=' + getComputedStyle(el).zIndex);
    });
    document.querySelectorAll('div').forEach((el) => {
      const cs = getComputedStyle(el);
      if (vis(el) && cs.position === 'fixed' && parseFloat(cs.opacity) > 0.5 && el.offsetWidth > 800 && el.offsetHeight > 500) {
        out.overlayEls.push(el.tagName + '.' + String(el.className).slice(0, 80));
      }
    });
    const f = document.querySelector('#elementor-preview iframe');
    if (f) {
      const r = f.getBoundingClientRect();
      out.iframeInfo = { w: r.width, h: r.height, x: r.x, y: r.y, src: f.src.slice(0, 90) };
      try {
        const d = f.contentDocument;
        out.iframeInfo.doc = { ready: d.readyState, bodyClass: d.body.className.slice(0, 120),
          widgets: d.querySelectorAll('.elementor-widget').length,
          spinners: [...d.querySelectorAll('[class*=loading]')].filter(vis).map((el) => String(el.className).slice(0, 60)) };
      } catch (e) { out.iframeInfo.err = String(e).slice(0, 80); }
    }
    const p = document.querySelector('#elementor-panel');
    if (p) { const r = p.getBoundingClientRect(); out.panelRect = { x: r.x, y: r.y, w: r.width, h: r.height }; }
    return out;
  });
  console.log(JSON.stringify(forensics, null, 1));
  await page.screenshot({ path: '/tmp/forensic_top.png' });
  const f = await page.$('#elementor-preview iframe');
  if (f) await f.screenshot({ path: '/tmp/forensic_frame.png' }).catch((e) => console.log('frame shot err', e.message));
  const p = await page.$('#elementor-panel');
  if (p) await p.screenshot({ path: '/tmp/forensic_panel.png' }).catch((e) => console.log('panel shot err', e.message));
  for (const n of ['forensic_top', 'forensic_frame', 'forensic_panel']) {
    const fp = `/tmp/${n}.png`;
    if (fs.existsSync(fp)) console.log(n, fs.statSync(fp).size);
  }
  await browser.close();
})().catch((e) => { console.error('ERR', e); process.exit(1); });

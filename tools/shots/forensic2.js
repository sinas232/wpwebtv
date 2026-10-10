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
  const logs = [];
  page.on('console', (msg) => logs.push(msg.type() + ': ' + msg.text().slice(0, 220)));
  page.on('pageerror', (e) => logs.push('PAGEERROR: ' + String(e).slice(0, 300)));
  await page.goto(BASE + '/wp-login.php', { waitUntil: 'domcontentloaded' });
  await page.waitForSelector('#user_login');
  await page.type('#user_login', 'admin'); await page.type('#user_pass', 'password');
  await Promise.all([page.waitForNavigation({ waitUntil: 'networkidle2' }), page.click('#wp-submit')]);
  await page.goto(BASE + '/?elementor-preview=4', { waitUntil: 'networkidle2' }).catch(() => {});
  logs.length = 0;
  await page.goto(BASE + '/wp-admin/post.php?post=4&action=elementor', { waitUntil: 'networkidle2' });
  for (let i = 0; i < 8; i++) {
    await new Promise((r) => setTimeout(r, 6000));
    const st = await page.evaluate(() => {
      const f = document.querySelector('#elementor-preview iframe');
      const out = {
        loaderDisplay: (() => { const l = document.querySelector('#elementor-loading'); return l ? getComputedStyle(l).display : 'gone'; })(),
        hasElementor: typeof window.elementor !== 'undefined' && !!window.elementor,
        elementorLoaded: (() => { try { return !!(window.elementor && window.elementor.isPreview !== undefined && document.querySelector('#elementor-editor-wrapper').childElementCount > 0); } catch (e) { return false; } })(),
        wrapperV2: document.querySelector('#elementor-editor-wrapper-v2')?.childElementCount ?? -1,
        wrapper: document.querySelector('#elementor-editor-wrapper')?.childElementCount ?? -1,
        iframeFE: null,
      };
      try {
        const w = f.contentWindow;
        out.iframeFE = { frontend: !!(w && w.elementorFrontend), elementor: !!(w && w.elementor), jq: !!(w && w.jQuery) };
      } catch (e) { out.iframeFE = String(e).slice(0, 60); }
      return out;
    });
    console.log(`t+${(i + 1) * 6}s`, JSON.stringify(st));
    if (st.loaderDisplay === 'gone') break;
  }
  console.log('--- console logs (' + logs.length + ') ---');
  logs.slice(-30).forEach((l) => console.log(l));
  await browser.close();
})().catch((e) => { console.error('ERR', e); process.exit(1); });

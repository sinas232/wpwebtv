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
  page.on('console', (msg) => { const t = msg.type(); if (t === 'error' || t === 'warning') console.log('CONSOLE', t, msg.text().slice(0, 300)); });
  page.on('pageerror', (e) => console.log('PAGEERROR', String(e).slice(0, 400)));
  page.on('response', (r) => { if (r.status() >= 400) console.log('HTTP', r.status(), r.url().slice(0, 160)); });
  await page.goto(BASE + '/wp-login.php', { waitUntil: 'domcontentloaded' });
  await page.type('#user_login', 'admin'); await page.type('#user_pass', 'password');
  await Promise.all([page.waitForNavigation({ waitUntil: 'networkidle2' }), page.click('#wp-submit')]);
  console.log('--- entering editor ---');
  await page.goto(BASE + '/wp-admin/post.php?post=4&action=elementor', { waitUntil: 'networkidle2' });
  await new Promise((r) => setTimeout(r, 20000));
  const state = await page.evaluate(() => ({
    bodyClass: document.body.className.slice(0, 200),
    hasIframe: !!document.querySelector('#elementor-preview iframe'),
    iframeSrc: document.querySelector('#elementor-preview iframe')?.getAttribute('src') || '',
    panel: !!document.querySelector('#elementor-panel'),
    loading: !!document.querySelector('.elementor-loading'),
    editorActive: !!document.querySelector('.elementor-editor-active'),
  }));
  console.log('STATE', JSON.stringify(state, null, 1));
  await page.screenshot({ path: '/tmp/editor_debug.png' });
  await browser.close();
})().catch((e) => { console.error('ERR', e); process.exit(1); });

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
  await page.setViewport({ width: 1600, height: 1000 });
  await page.goto(BASE + '/wp-login.php', { waitUntil: 'networkidle2', timeout: 90000 });
  await page.type('#user_login', 'admin');
  await page.type('#user_pass', 'password');
  await Promise.all([page.waitForNavigation({ waitUntil: 'networkidle2', timeout: 90000 }), page.click('#wp-submit')]);
  await page.goto(BASE + '/wp-admin/post.php?post=4&action=elementor', { waitUntil: 'networkidle2', timeout: 120000 });
  await page.waitForSelector('.elementor-panel', { timeout: 60000 });
  await new Promise((r) => setTimeout(r, 4000));
  const sel = await page.evaluate(() => {
    let target = null;
    const kidsOf = (m) => { const k = m.get('elements'); if (!k) return []; return k.models ? k.models : (Array.isArray(k) ? k : []); };
    const walk = (models) => { for (const m of models) { if (m.get('elType') === 'widget' && m.get('widgetType') === 'heading' && String((m.get('settings') && m.get('settings').get('title')) || '').includes('شفافیت')) target = m; walk(kidsOf(m)); } };
    walk(window.elementor.elements.models || []);
    const out = { found: !!target, hasE: !!window.$e };
    if (target && window.$e && window.elementor.getContainer) {
      const c = window.elementor.getContainer(target.id);
      if (c) { window.$e.run('document/elements/select', { container: c }); out.ran = true; }
      else out.nocontainer = true;
    }
    return out;
  });
  await new Promise((r) => setTimeout(r, 3000));
  const state = await page.evaluate((sel) => {
    const pv = window.elementor && elementor.getPanelView && elementor.getPanelView();
    const views = [];
    try { let v = pv; let g = 0; while (v && g++ < 6) { views.push(v.name || v.className || '?'); v = v.getCurrentView && v.getCurrentView(); } } catch (e) {}
    return {
      sel,
      views,
      inputs: [...document.querySelectorAll('input[data-setting]')].slice(0, 12).map((i) => i.getAttribute('data-setting')),
      panelActive: [...document.querySelectorAll('.elementor-tab-wrapper.active, .elementor-panel-section.active')].map((e) => e.id || e.className).slice(0, 5),
      panelHTML: (document.querySelector('.elementor-panel') || {}).innerHTML ? document.querySelector('.elementor-panel').innerHTML.slice(0, 0) : null,
      titleInput: !!document.querySelector('input[data-setting="title"]'),
      headingControls: [...document.querySelectorAll('.elementor-control')].length,
      activeEl: (document.activeElement && document.activeElement.className || '').slice(0, 80),
    };
  }, sel);
  console.log(JSON.stringify(state, null, 1));
  await browser.close();
})().catch((e) => { console.error(e); process.exit(1); });

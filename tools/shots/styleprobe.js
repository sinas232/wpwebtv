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
  const errs = [];
  page.on('pageerror', (e) => errs.push('pageerror: ' + String(e).slice(0, 300)));
  page.on('console', (m) => { if (m.type() === 'error') errs.push('console: ' + m.text().slice(0, 300)); });
  await page.setViewport({ width: 1600, height: 1000 });
  await page.goto(BASE + '/wp-login.php', { waitUntil: 'networkidle2', timeout: 90000 });
  await page.type('#user_login', 'admin');
  await page.type('#user_pass', 'password');
  await Promise.all([page.waitForNavigation({ waitUntil: 'networkidle2', timeout: 90000 }), page.click('#wp-submit')]);
  await page.goto(BASE + '/wp-admin/post.php?post=4&action=elementor', { waitUntil: 'networkidle2', timeout: 120000 });
  await page.waitForSelector('.elementor-panel', { timeout: 60000 });
  await new Promise((r) => setTimeout(r, 4000));
  const out = await page.evaluate(async () => {
    let target = null;
    const kidsOf = (m) => { const k = m.get('elements'); if (!k) return []; return k.models ? k.models : (Array.isArray(k) ? k : []); };
    const walk = (models) => { for (const m of models) { if (m.get('elType') === 'widget' && m.get('widgetType') === 'heading' && String((m.get('settings') && m.get('settings').get('title')) || '').includes('شفافیت')) target = m; walk(kidsOf(m)); } };
    walk(window.elementor.elements.models || []);
    const c = elementor.getContainer(target.id);
    $e.run('document/elements/select', { container: c });
    await new Promise((r) => setTimeout(r, 1500));
    const tabs = [...document.querySelectorAll('.elementor-tab-title')].map((t) => ({ tab: t.getAttribute('data-tab'), cls: t.className.slice(0, 60) }));
    // click style tabs inside the widget edit panel
    // click Normal subtab if present (after style tab switch)
    const clickNormal = async () => {
      const n = document.querySelector('.elementor-control-title_colors_normal .elementor-panel-tab-heading') || document.querySelector('.elementor-control-title_colors_normal');
      if (n) { n.click(); await new Promise((r) => setTimeout(r, 900)); }
    };
    const st = document.querySelector('.elementor-tab-control-style');
    const tabInfo = st ? { cls: st.className, tag: st.tagName, dataTab: st.getAttribute('data-tab'), disp: getComputedStyle(st).display } : null;
    if (st) st.click();
    await new Promise((r) => setTimeout(r, 1200));
    await clickNormal();
    // also expand all collapsed sections in style tab
    for (const s of [...document.querySelectorAll('.elementor-control-section .elementor-tab-title')]) { if (s.closest('.elementor-tab-wrapper.active, [style*="block"]') !== null) {} }
    await new Promise((r) => setTimeout(r, 800));
    const panel = document.querySelector('.elementor-panel');
    const classes = {};
    (panel ? panel.querySelectorAll('[class*="tab"], [class*="section"], [class*="style"]') : []).forEach((e) => { classes[e.className.split(' ').filter(c=>/tab|section|style/.test(c)).join(',')] = (classes[e.className.split(' ').filter(c=>/tab|section|style/.test(c)).join(',')]||0)+1; });
    return {
      tabs,
      tabInfo,
      colorAfterClick: !!document.querySelector('input[data-setting="title_color"]'),
      inputsAfter: [...document.querySelectorAll('[data-setting]')].map((i) => i.tagName + ':' + i.getAttribute('data-setting')).slice(0, 120),
      tc: !!document.querySelector('[data-setting="title_color"]'),
      tcHtml: (document.querySelector('[data-setting="title_color"]') || {}).outerHTML ? document.querySelector('[data-setting="title_color"]').outerHTML.slice(0, 300) : null,
      hasBlend: [...document.querySelectorAll('[data-setting]')].some(e => e.getAttribute('data-setting')==='blend_mode'),
      subTabs: [...document.querySelectorAll('.elementor-control-type-tab')].map(e => ({cls: e.className.slice(0,60), txt: (e.textContent||'').slice(0,20)})),
      normalPanel: (document.querySelector('.elementor-control-title_colors_normal') || {outerHTML:''}).outerHTML.slice(0, 900),
      styleInputs: [...document.querySelectorAll('input[data-setting], textarea[data-setting]')].map((i) => i.getAttribute('data-setting')),
      color: !!document.querySelector('input[data-setting="title_color"]'),
      sections: [...document.querySelectorAll('.elementor-control-section')].map((s) => s.getAttribute('data-section') || s.className).slice(0, 12),
      classMap: classes,
      panelLen: panel ? panel.innerHTML.length : 0,
      headHTML: panel ? [...panel.querySelectorAll('.elementor-control')].slice(0,3).map(e=>e.outerHTML.slice(0,220)) : [],
    };
  });
  out.errs = errs.slice(0, 12);
  console.log(JSON.stringify(out, null, 1));
  await browser.close();
})().catch((e) => { console.error(e); process.exit(1); });

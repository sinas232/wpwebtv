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
  const out = await page.evaluate(async () => {
    let hero = null;
    const kidsOf = (mm) => { const k = mm.get('elements'); if (!k) return []; return k.models ? k.models : (Array.isArray(k) ? k : []); };
    const walk = (models) => { for (const mm of models) { if (mm.get('elType') === 'widget' && mm.get('widgetType') === 'pixva-hero') hero = mm; walk(kidsOf(mm)); } };
    walk(window.elementor.elements.models || []);
    const res = { found: !!hero };
    if (!hero) return res;
    const hc = elementor.getContainer(hero.id);
    $e.run('document/elements/select', { container: hc });
    await new Promise((r) => setTimeout(r, 1500));
    res.hasTitleInput = !!document.querySelector('textarea[data-setting="pixva_hero_title"], input[data-setting="pixva_hero_title"]');
    const tabs = [...document.querySelectorAll('.elementor-tab-control-style')];
    res.tabs = tabs.length;
    if (tabs.length) { tabs[0].click(); await new Promise((r) => setTimeout(r, 1200)); }
    res.allSettings = [...document.querySelectorAll('[data-setting]')].map((e) => e.tagName + ':' + e.getAttribute('data-setting')).filter((x) => /color/i.test(x));
    res.anyColor = [...document.querySelectorAll('[data-setting]')].map((e) => e.getAttribute('data-setting')).slice(0, 60);
    // open the Pickr dialog for hero title color and set hex
    const ctl = document.querySelector('.elementor-control-pixva_hero_title_color');
    res.ctlFound = !!ctl;
    if (ctl) {
      const btn = ctl.querySelector('.pcr-button');
      if (btn) btn.click();
      await new Promise((r) => setTimeout(r, 600));
      const app = document.querySelector('.pcr-app') || ctl.querySelector('.pcr-app');
      res.appVisible = app ? getComputedStyle(app).display !== 'none' && getComputedStyle(app).visibility !== 'hidden' : false;
      const result = (app || ctl).querySelector('input.pcr-result, input[type="text"]');
      if (result) {
        const setter = Object.getOwnPropertyDescriptor(window.HTMLInputElement.prototype, 'value').set;
        setter.call(result, '#00A86B');
        result.dispatchEvent(new Event('input', { bubbles: true }));
        result.dispatchEvent(new Event('change', { bubbles: true }));
        result.dispatchEvent(new KeyboardEvent('keydown', { key: 'Enter', bubbles: true }));
        await new Promise((r) => setTimeout(r, 600));
        res.resultVal = result.value;
      } else res.resultVal = 'NO-INPUT';
      // read bound model value
      let hero2 = null;
      const kids2 = (mm) => { const k = mm.get('elements'); if (!k) return []; return k.models ? k.models : (Array.isArray(k) ? k : []); };
      const walk2 = (models) => { for (const mm of models) { if (mm.get('elType') === 'widget' && mm.get('widgetType') === 'pixva-hero') hero2 = mm; walk2(kids2(mm)); } };
      walk2(window.elementor.elements.models || []);
      if (hero2) res.modelVal = hero2.get('settings').get('pixva_hero_title_color');
    }
    const panel = document.querySelector('.elementor-panel');
    const h = panel ? panel.innerHTML : '';
    res.hasLabel = h.includes('رنگ عنوان');
    return res;
  });
  console.log(JSON.stringify(out, null, 1));
  await browser.close();
})().catch((e) => { console.error(e); process.exit(1); });

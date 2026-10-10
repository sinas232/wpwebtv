// Evidence shot of the REAL Elementor editor with the redesigned home loaded.
const path = require('path');
const puppeteer = require('puppeteer-core');
const chromium = require('@sparticuz/chromium').default;

const BASE = process.env.PIXVA_BASE || 'http://127.0.0.1:9414';
const SHOTS = path.join(__dirname, '..', '..', 'docs', 'evidence', 'screenshots');

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

  // wait until the preview actually renders widgets (heading text visible)
  let ready = false;
  for (let i = 0; i < 60 && !ready; i++) {
    ready = await page.evaluate(() => {
      const t = document.body ? '' : '';
      const html = document.body.innerHTML;
      return html.includes('تشخیص تعمیر تلویزیون') || html.includes('elementor-widget-heading');
    }).catch(() => false);
    if (!ready) await new Promise((r) => setTimeout(r, 1000));
  }
  // dismiss safe-mode / welcome dialogs
  await page.evaluate(() => {
    document.querySelectorAll('.dialog-close-button, .dialog-buttons .dialog-cancel, .elementor-popup-close').forEach((b) => { try { b.click(); } catch (e) {} });
  });
  await new Promise((r) => setTimeout(r, 1500));

  // select the editorial heading so the panel shows real controls
  await page.evaluate(() => {
    try {
      let target = null;
      const kidsOf = (m) => { const k = m.get('elements'); if (!k) return []; return k.models ? k.models : (Array.isArray(k) ? k : []); };
      const walk = (models) => { for (const m of models) { if (m.get('elType') === 'widget' && m.get('widgetType') === 'heading' && String((m.get('settings') && m.get('settings').get('title')) || '').includes('شفافیت')) target = m; walk(kidsOf(m)); } };
      walk(window.elementor.elements.models || []);
      if (target && window.elementor.getContainer) {
        const c = elementor.getContainer(target.id);
        if (c) $e.run('document/elements/select', { container: c });
      }
    } catch (e) {}
  });
  await new Promise((r) => setTimeout(r, 2000));
  // scroll the preview to the editorial band
  for (const fr of page.frames()) {
    await fr.evaluate(() => {
      const els = [...document.querySelectorAll('.elementor-element[data-widget_type="heading.default"]')];
      const t = els.find((e) => (e.textContent || '').includes('شفافیت'));
      if (t) t.scrollIntoView({ block: 'center' });
    }).catch(() => {});
  }

  const state = await page.evaluate(() => ({
    ready: document.body.innerHTML.includes('elementor-widget-heading'),
    panelControls: document.querySelectorAll('.elementor-control').length,
    titleInput: !!document.querySelector('textarea[data-setting="title"], input[data-setting="title"]'),
    loading: !!document.querySelector('#elementor-preview-loading:not(.elementor-loaded)'),
  }));
  console.log(JSON.stringify(state));

  await page.screenshot({ path: path.join(SHOTS, 'elementor-editor-redesign.png') });
  console.log('saved elementor-editor-redesign.png');
  await browser.close();
})().catch((e) => { console.error(e); process.exit(1); });

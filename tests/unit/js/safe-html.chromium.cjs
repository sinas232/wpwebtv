/**
 * Real-engine test: pixva.setSafeHTML in headless Chromium (puppeteer-core).
 *
 * Loads the real app.js into a page, inserts the real PHP fixtures and hostile
 * payloads, then waits so that any image error/load handler or script would
 * have time to run. Passes only if nothing executes and no dialog opens.
 *
 * Requirements: puppeteer-core and @sparticuz/chromium in NODE_PATH. On
 * systems without the bundled libraries, set LD_LIBRARY_PATH to a directory
 * containing libnspr4.so and related libraries, or set CHROMIUM_PATH.
 *   NODE_PATH=/path/to/node_modules node tests/unit/js/safe-html.chromium.cjs
 */
'use strict';

const fs = require('fs');
const path = require('path');
const assert = require('assert');

const root = path.join(__dirname, '..', '..', '..');
const appSource = fs.readFileSync(path.join(root, 'pixva', 'assets', 'js', 'app.js'), 'utf8');
const fixtures = JSON.parse(fs.readFileSync(path.join(__dirname, 'fixtures', 'render.json'), 'utf8'));

const payloads = {
	'img onerror': '<img src="https://127.0.0.1:9/none.png" onerror="window.__xss=1">',
	'img onerror relative': '<img src=x onerror="window.__xss=1">',
	'svg onload': '<svg onload="window.__xss=1"><circle r="3"></circle></svg>',
	'script': '<script>window.__xss=1</script>',
	'form/math/style': '<form><math><mtext></form><form><mglyph><style></math><img src=x onerror=window.__xss=1>',
	'svg animate': '<svg><a><animate attributeName="href" values="javascript:window.__xss=1"/><text>x</text></a></svg>',
	'details toggle': '<details open ontoggle="window.__xss=1"><summary>s</summary></details>',
	'dom clobbering id': '<div id="pixva"><span id="document">a</span></div>',
	'javascript href': '<a href="javascript:window.__xss=1">x</a>',
};

(async () => {
	let puppeteer;
	let chromium;
	try {
		puppeteer = require('puppeteer-core');
		const mod = require('@sparticuz/chromium');
		chromium = mod.default || mod;
	} catch (e) {
		console.log('SKIP - puppeteer-core / @sparticuz/chromium not found: ' + e.message);
		process.exit(2);
	}
	const exe = process.env.CHROMIUM_PATH || (await chromium.executablePath());
	const browser = await puppeteer.launch({ executablePath: exe, args: chromium.args, headless: true });
	let passed = 0;
	let failed = 0;
	const report = (ok, name, detail) => {
		if (ok) { passed++; console.log('ok   - ' + name); } else { failed++; console.log('FAIL - ' + name + (detail ? '\n       ' + detail : '')); }
	};
	try {
		const page = await browser.newPage();
		let dialogs = 0;
		page.on('dialog', async (d) => { dialogs++; await d.dismiss(); });
		await page.setContent('<!doctype html><html><body></body></html>', { waitUntil: 'load' });
		await page.evaluate(() => { window.PIXVA = { rest: '/x/', events: [], props: [] }; window.fetch = () => new Promise(() => {}); });
		await page.addScriptTag({ content: appSource });
		if (process.env.NAIVE_CONTROL === '1') {
			// Negative control: replace the sanitizer with raw innerHTML. The suite must FAIL.
			await page.evaluate(() => { window.pixva.setSafeHTML = (t, h) => { t.innerHTML = h; }; });
		}

		const check = async (name, html) => {
			await page.evaluate(() => { window.__xss = undefined; });
			const result = await page.evaluate((h) => {
				const host = document.createElement('div');
				document.body.appendChild(host);
				window.pixva.setSafeHTML(host, h);
				const bad = [];
				for (const el of [host].concat(Array.from(host.querySelectorAll('*')))) {
					for (const a of Array.from(el.attributes)) {
						const n = a.name.toLowerCase();
						const v = a.value.replace(/[\u0000-\u0020\u007f]/g, '');
						if (n.indexOf('on') === 0) bad.push('handler ' + n);
						if (/^(javascript|vbscript|data):/i.test(v)) bad.push('url ' + n);
					}
				}
				if (host.querySelector('script,style,iframe,object,embed,form,button,input,noscript,template,animate,set,foreignobject,use')) bad.push('banned element');
				return { bad, text: host.textContent.slice(0, 120) };
			}, html);
			await new Promise((r) => setTimeout(r, 150)); // let any image/load handler fire
			const executed = await page.evaluate(() => window.__xss);
			report(result.bad.length === 0 && executed === undefined && dialogs === 0, 'chromium: ' + name, result.bad.concat(executed !== undefined ? ['executed'] : [], dialogs ? ['dialog opened'] : []).join('; '));
		};

		for (const [name, html] of Object.entries(payloads)) {
			await check(name, html);
		}
		for (const key of Object.keys(fixtures)) {
			await check('real fixture ' + key, fixtures[key]);
		}

		// Visible text of hostile order data must be text, not markup.
		await page.evaluate(() => { window.__xss = undefined; });
		const shown = await page.evaluate((h) => {
			const host = document.createElement('div');
			document.body.appendChild(host);
			window.pixva.setSafeHTML(host, h);
			return host.querySelectorAll('img,script').length === 0;
		}, fixtures.order_view);
		report(shown, 'chromium: hostile order text has no img/script elements');
	} finally {
		await browser.close();
	}
	console.log('\n' + passed + ' passed, ' + failed + ' failed');
	process.exit(failed ? 1 : 0);
})().catch((e) => {
	console.error('ERROR', e);
	process.exit(1);
});

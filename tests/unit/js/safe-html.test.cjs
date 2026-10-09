/**
 * Unit test: pixva.setSafeHTML (allow-list sanitizer in assets/js/app.js).
 *
 * Runs the real app.js inside jsdom and feeds hostile and legitimate fragments
 * through the sanitizer. Requires jsdom; run with:
 *   NODE_PATH=/path/to/node_modules node tests/unit/js/safe-html.test.cjs
 * Exit code 0 = all assertions passed.
 */
'use strict';

const fs = require('fs');
const path = require('path');
const assert = require('assert');
const { JSDOM } = require('jsdom');

const appSource = fs.readFileSync(
	path.join(__dirname, '..', '..', '..', 'pixva', 'assets', 'js', 'app.js'),
	'utf8'
);

const dom = new JSDOM('<!doctype html><html><body></body></html>', {
	runScripts: 'outside-only',
	url: 'https://example.test/',
});
const { window } = dom;
window.PIXVA = { rest: '/wp-json/pixva/v1/', events: [], props: [] };
window.fetch = () => new Promise(() => {});
window.eval(appSource);
const setSafeHTML = window.pixva && window.pixva.setSafeHTML;
assert.strictEqual(typeof setSafeHTML, 'function', 'pixva.setSafeHTML must be exported by app.js');

let passed = 0;
let failed = 0;

function render(html) {
	const host = window.document.createElement('div');
	window.document.body.appendChild(host);
	setSafeHTML(host, html);
	return host;
}

function test(name, fn) {
	try {
		fn();
		passed += 1;
		console.log('ok   - ' + name);
	} catch (e) {
		failed += 1;
		console.log('FAIL - ' + name + '\n       ' + e.message);
	}
}

/** True if any element in the subtree has an event-handler or executable attribute. */
function hasExecutableMarkup(root) {
	const all = [root].concat(Array.from(root.querySelectorAll('*')));
	for (const el of all) {
		for (const attr of Array.from(el.attributes)) {
			const n = attr.name.toLowerCase();
			if (n.startsWith('on')) return 'event handler ' + n + ' on ' + el.localName;
			if ((n === 'href' || n === 'src' || n === 'xlink:href' || n === 'action') && /^\s*(javascript|vbscript|data):/i.test(attr.value.replace(/[\u0000-\u0020]/g, ''))) {
				return 'unsafe URL in ' + n + ' on ' + el.localName;
			}
		}
	}
	const banned = root.querySelectorAll('script,style,iframe,object,embed,form,button,input,noscript,template,base,link,meta');
	if (banned.length) return 'banned element ' + banned[0].localName;
	return null;
}

const attacks = {
	'img onerror': '<img src=x onerror="window.__xss=1">',
	'svg onload': '<svg onload="window.__xss=1"><circle r="3"></circle></svg>',
	'a javascript href': '<a href="javascript:window.__xss=1">x</a>',
	'a entity-obfuscated javascript href': '<a href="jav&#x09;ascript:window.__xss=1">x</a>',
	'script element': '<script>window.__xss=1</script><p>t</p>',
	'iframe': '<iframe src="https://evil.example/"></iframe>',
	'style element': '<style>body{display:none}</style><p>t</p>',
	'noscript breakout': '<noscript><p title="</noscript><img src=x onerror=window.__xss=1>"></noscript>',
	'details ontoggle': '<details open ontoggle="window.__xss=1"><summary>s</summary></details>',
	'form action': '<form action="javascript:window.__xss=1"><button>go</button></form>',
	'data image src': '<img src="data:image/svg+xml,%3Csvg onload=window.__xss=1%3E">',
	'svg xlink href': '<svg><a xlink:href="javascript:window.__xss=1"><text>x</text></a></svg>',
	'mXSS math/style': '<math><mtext><table><mglyph><style><img src=x onerror=window.__xss=1>',
	'id clobbering': '<div id="document.cookie"><span>a</span></div>',
	'formaction on button': '<button formaction="javascript:window.__xss=1">b</button>',
	'object data': '<object data="javascript:window.__xss=1"></object>',
};

for (const [name, payload] of Object.entries(attacks)) {
	test('blocks ' + name, () => {
		const host = render(payload);
		const problem = hasExecutableMarkup(host);
		assert.strictEqual(problem, null, problem || '');
		assert.strictEqual(window.__xss, undefined, 'payload executed');
	});
}

test('removes javascript href but keeps the link text', () => {
	const host = render('<a href="javascript:void(0)">texto</a>');
	const a = host.querySelector('a');
	assert.ok(a, 'anchor text should survive as text');
	assert.strictEqual(a.hasAttribute('href'), false);
	assert.strictEqual(host.textContent, 'texto');
});

test('keeps https links and drops target', () => {
	const host = render('<a href="https://ok.example/x" target="_blank" rel="opener">ok</a>');
	const a = host.querySelector('a');
	assert.strictEqual(a.getAttribute('href'), 'https://ok.example/x');
	assert.strictEqual(a.hasAttribute('target'), false);
	assert.strictEqual(a.hasAttribute('rel'), false);
});

test('keeps legitimate order-view markup structure', () => {
	const html =
		'<article class="order" aria-labelledby="order-PXV-1234"><header class="order__head">' +
		'<h2 class="order__title" id="order-PXV-1234"><span dir="ltr">PXV-1234</span></h2>' +
		'<span class="badge badge--status-new">جدید</span></header>' +
		'<ol class="timeline" aria-label="مراحل"><li class="timeline__item is-current" aria-current="step"><span>دریافت</span></li></ol>' +
		'<p class="order__warranty"><svg class="icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M1 1L2 2"></path></svg> گارانتی</p>' +
		'<details class="order__history"><summary>سابقه</summary><ul><li><time datetime="2026-10-09">2026-10-09</time> — ثبت</li></ul></details>' +
		'</article>';
	const host = render(html);
	assert.strictEqual(hasExecutableMarkup(host), null);
	assert.ok(host.querySelector('article.order h2#order-PXV-1234'));
	assert.ok(host.querySelector('li[aria-current="step"]'));
	assert.ok(host.querySelector('svg path'));
	assert.ok(host.querySelector('time[datetime="2026-10-09"]'));
	assert.ok(host.textContent.indexOf('دریافت') !== -1);
});

test('keeps text that looks like markup as text (no double parsing)', () => {
	const host = render('<p>&lt;img src=x onerror=1&gt;</p>');
	assert.strictEqual(host.querySelector('img'), null);
	assert.strictEqual(host.textContent, '<img src=x onerror=1>');
});

test('null and undefined input clear the target without throwing', () => {
	const host = render('<p>old</p>');
	setSafeHTML(host, null);
	assert.strictEqual(host.childNodes.length, 0);
	setSafeHTML(host, undefined);
	assert.strictEqual(host.childNodes.length, 0);
});

test('replaces previous content rather than appending', () => {
	const host = render('<p>one</p>');
	setSafeHTML(host, '<p>two</p>');
	assert.strictEqual(host.textContent, 'two');
});

console.log('\n' + passed + ' passed, ' + failed + ' failed');
process.exit(failed ? 1 : 0);

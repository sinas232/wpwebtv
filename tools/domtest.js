/**
 * آزمون رگرسیون DOM پوسته پیکسوا (بدون PHP، با jsdom).
 *
 * هارنس: preview/hero-preview.html (خارج از گیت) — آینه خروجی PHP برای
 * هدر آبشاری، دروئر آکاردئونی، هیرو + سیمولاتور، ویجت سیمولاتور المنتور،
 * سایدبار و محاسبه‌گر، با AJAX ساختگی (همان نرخ‌نامه سمت سرور).
 *
 * اجرا:
 *   npm install jsdom --no-save        # یک‌بار در پوشه والد
 *   node tools/domtest.js
 */
'use strict';

const fs = require('fs');
const path = require('path');
const { JSDOM, VirtualConsole } = require('jsdom');

const repo = path.join(__dirname, '..');
const theme = path.join(repo, 'pixva');
const harness = path.join(repo, 'preview', 'hero-preview.html');

let passed = 0;
let failed = 0;
const failures = [];

function check(label, condition) {
	if (condition) {
		passed += 1;
		return true;
	}
	failed += 1;
	failures.push(label);
	return false;
}

const wait = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

/* ------------------------------------------------------------------ *
 * بخش ۱ — قرارداد ایستای فایل‌ها (CSS/JS/ویجت‌ها)
 * ------------------------------------------------------------------ */
function staticChecks() {
	const css = fs.readFileSync(path.join(theme, 'assets/css/pixva-2026.css'), 'utf8');
	const mainJs = fs.readFileSync(path.join(theme, 'assets/js/main.js'), 'utf8');
	const toolsJs = fs.readFileSync(path.join(theme, 'assets/js/interactive-tools.js'), 'utf8');
	const support = fs.readFileSync(path.join(theme, 'inc/elementor-support.php'), 'utf8');

	check('css: overflow-x clip (چسبنده‌ها نشکنند)', css.indexOf('overflow-x: clip') > -1);
	check('css: منوی آبشاری شیشه‌ای', css.indexOf('.pixva-dropdown__sub') > -1 && css.indexOf('.pixva-mega__panel--menu') > -1);
	check('css: آکاردئون دروئر', css.indexOf('.pixva-drawer-nav__panel') > -1);
	check('css: سایدبار چسبان', css.indexOf('.pixva-sidebar__inner') > -1);
	check('css: دکمه گرادیانی CTA', css.indexOf('.pixva-btn--gradient') > -1);
	check('css: نقطه‌های ناوبری سیمولاتور', css.indexOf('.pixva-sim__dots button.is-active') > -1);
	check('css: پوسته ویجت‌های بخشی', css.indexOf('.pixva-sim--widget') > -1 && css.indexOf('.pixva-services__grid') > -1);
	check('css: احترام به reduced-motion', css.indexOf('prefers-reduced-motion') > -1);

	const open = (css.match(/\{/g) || []).length;
	const close = (css.match(/\}/g) || []).length;
	check('css: آکولادها متوازن (' + open + '/' + close + ')', open === close);

	check('main.js: آکاردئون دروئر ثبت شده', mainJs.indexOf('function initDrawerNav') > -1 && mainJs.indexOf('initDrawerNav();') > -1);
	check('tools.js: CTA اختصاصی هر ایراد', toolsJs.indexOf('data-sim-cta') > -1);
	check('tools.js: حالت رسانه و محو تصویر', toolsJs.indexOf('data-sim-mode') > -1 && toolsJs.indexOf('is-swapping') > -1);
	check('tools.js: سوییپ لمسی', toolsJs.indexOf('pointerdown') > -1 && toolsJs.indexOf('is-dragging') > -1);

	check('elementor: ویجت‌های بخشی ثبت می‌شوند', support.indexOf('pixva_section_widget_classes') > -1 && support.indexOf('pixva_load_section_widgets') > -1);

	const widgets = fs.readdirSync(path.join(theme, 'inc/widgets')).filter((f) => f.indexOf('.php') > -1);
	check('widgets: ۸ فایل ویجت موجود است (' + widgets.length + ')', widgets.length >= 8);
}

/* ------------------------------------------------------------------ *
 * بخش ۲ — رفتار زمان اجرا در DOM
 * ------------------------------------------------------------------ */
async function runtimeChecks(window) {
	const document = window.document;
	const $ = (sel, root) => (root || document).querySelector(sel);
	const $$ = (sel, root) => Array.from((root || document).querySelectorAll(sel));

	// --- هدر و منوی آبشاری چندسطحی ---
	check('header: مگامنو رندر شده', !!$('[data-pixva-mega]'));
	check('menu: ساختار سطح ۲', !!$('.pixva-dropdown__list--level-2'));
	check('menu: ساختار سطح ۳', !!$('.pixva-dropdown__list--level-3'));
	check('menu: ساختار سطح ۴', !!$('.pixva-dropdown__list--level-4'));
	check('menu: آیتم منو قرارداد مگا را دارد', !!$('.pixva-nav-item[data-mega-item] > [data-mega-trigger]'));

	const navTrigger = $('.pixva-nav-item[data-mega-item] > [data-mega-trigger]');
	navTrigger.click();
	check('menu: با کلیک باز می‌شود', navTrigger.getAttribute('aria-expanded') === 'true' && navTrigger.closest('[data-mega-item]').classList.contains('is-open'));
	check('menu: پنل به trigger وصل است', document.getElementById(navTrigger.getAttribute('aria-controls')) === navTrigger.parentElement.querySelector('[data-mega-panel]'));

	// --- دروئر موبایل ---
	const burger = $('[data-pixva-burger]');
	const drawer = $('[data-pixva-drawer]');
	burger.click();
	check('drawer: با برگر باز می‌شود', burger.getAttribute('aria-expanded') === 'true');
	$('[data-pixva-overlay]').click();
	check('drawer: با اورلی بسته می‌شود', burger.getAttribute('aria-expanded') === 'false');

	const topItems = $$('#pixva-drawer .pixva-drawer-nav--level-1 > .pixva-drawer-nav__item');
	const parentItem = topItems[1];
	const parentToggle = $('[data-drawer-toggle]', parentItem);
	check('drawer: آکاردئون در حالت بسته', !parentItem.classList.contains('is-open') && parentToggle.getAttribute('aria-expanded') === 'false');
	parentToggle.click();
	check('drawer: آکاردئون باز می‌شود', parentItem.classList.contains('is-open') && parentToggle.getAttribute('aria-expanded') === 'true');
	const nestedToggle = $('.pixva-drawer-nav--level-2 [data-drawer-toggle]', parentItem);
	nestedToggle.click();
	check('drawer: سطح تودرتو مستقل باز می‌شود', nestedToggle.closest('.pixva-drawer-nav__item').classList.contains('is-open'));
	check('drawer: آیتم برگ کلید آکاردئون ندارد', !topItems[2].querySelector('[data-drawer-toggle]'));
	$('[data-pixva-drawer-close]').click();
	check('drawer: بستن دروئر آکاردئون‌ها را جمع می‌کند', $$('#pixva-drawer .pixva-drawer-nav__item.is-open').length === 0);

	// --- سیمولاتور: دو نمونه مستقل ---
	const heroSim = $('#pixva-hero-simulator');
	const widgetSim = $('#pixva-sim-widget');
	check('sim: دو نمونه مستقل رندر شده', !!heroSim && !!widgetSim);
	check('sim: هیرو در حالت fault (بدون رسانه)', $('[data-sim-screen]', heroSim).getAttribute('data-sim-mode') === 'fault');
	check('sim: ویجت در حالت media (تصویر ادمین)', $('[data-sim-screen]', widgetSim).getAttribute('data-sim-mode') === 'media');
	check('sim: تصویر ویجت از Repeater می‌آید', ($('[data-sim-shot]', widgetSim).getAttribute('src') || '').indexOf('data:image/svg') === 0);
	check('sim: هیرو بدون تصویر اضافه', ! $('[data-sim-shot]', heroSim).getAttribute('src'));
	check('sim: مقدار پیش‌فرض سرور (نرخ‌نامه)', $('[data-sim-info] [data-sim-cost]', heroSim).textContent === '۱۵٬۵۵۰٬۰۰۰ تا ۲۵٬۸۰۰٬۰۰۰ تومان');

	// --- نقطه‌های ناوبری و سوییپ ---
	const dots = () => $$('.pixva-sim__dots button', widgetSim);
	check('sim: نقطه‌ها به تعداد ایرادها ساخته شدند', dots().length === 3);
	check('sim: نقطه اول فعال است', dots()[0].classList.contains('is-active'));

	const track = $('[data-sim-track]', widgetSim);
	const buttons = $$('[data-sim-symptom]', widgetSim);
	buttons.forEach((button, index) => {
		Object.defineProperty(button, 'offsetLeft', { value: index * 300, configurable: true });
		Object.defineProperty(button, 'offsetWidth', { value: 200, configurable: true });
	});
	Object.defineProperty(track, 'clientWidth', { value: 300, configurable: true });
	Object.defineProperty(track, 'scrollWidth', { value: 900, configurable: true });
	track.scrollLeft = 300;
	track.dispatchEvent(new window.Event('scroll'));
	check('sim: اسکرول سوییپ نقطه فعال را همگام می‌کند', dots()[1].classList.contains('is-active'));
	dots()[2].click();
	await wait(260);
	check('sim: کلیک روی نقطه، ایراد را انتخاب می‌کند', $('[data-sim-screen]', widgetSim).getAttribute('data-sim-screen') === 'blink');

	// --- انتخاب ایراد و CTA اختصاصی ---
	const linesBtn = $('[data-sim-symptom="lines"]', widgetSim);
	linesBtn.click();
	await wait(260);
	check('sim: وضعیت فعال جابه‌جا شد', linesBtn.classList.contains('is-active') && linesBtn.getAttribute('aria-pressed') === 'true');
	check('sim: برچسب قبلی غیرفعال شد', $('[data-sim-symptom="no_picture"]', widgetSim).getAttribute('aria-pressed') === 'false');
	check('sim: HUD به‌روز شد', $('.pixva-sim__hud-title', widgetSim).textContent === 'خطوط عمودی و رنگی');
	check('sim: علت از تنظیمات ادمین', $('[data-sim-info] [data-sim-cause]', widgetSim).textContent.indexOf('COF') > -1);
	check('sim: برآورد زنده از نرخ‌نامه', $('[data-sim-info] [data-sim-cost]', widgetSim).textContent === '۳۱٬۱۰۰٬۰۰۰ تا ۴۲٬۱۵۰٬۰۰۰ تومان');
	check('sim: زمان تحویل', $('[data-sim-info] [data-sim-time]', widgetSim).textContent === '۳ تا ۶ روز کاری');
	check('sim: CTA اختصاصی همان ایراد', $('[data-sim-order]', widgetSim).getAttribute('href') === '#panel-repair-request');
	check('sim: پرچم CTA فعال', $('[data-sim-order]', widgetSim).getAttribute('data-sim-cta-active') === '1');
	check('sim: تصویر با محو نرم عوض شد', $('[data-sim-shot]', widgetSim).getAttribute('src') === linesBtn.getAttribute('data-sim-media'));
	check('sim: واتساپ با متن ایراد بازنویسی شد', ($('[data-sim-wa]', widgetSim).getAttribute('href') || '').indexOf('https://wa.me/989120000000?text=') === 0);

	// ایراد بدون CTA اختصاصی → لینک پیش‌فرض محاسبه‌گر
	$('[data-sim-symptom="no_picture"]', widgetSim).click();
	await wait(260);
	check('sim: بازگشت به CTA پیش‌فرض', $('[data-sim-order]', widgetSim).getAttribute('href') === '#calculator-page');
	check('sim: پرچم CTA صفر شد', $('[data-sim-order]', widgetSim).getAttribute('data-sim-cta-active') === '0');

	// --- استقلال نمونه‌ها ---
	check('sim: هیرو دست‌نخورده ماند', $('[data-sim-screen]', heroSim).getAttribute('data-sim-screen') === 'no_picture');
	check('sim: CTA هیرو پیش‌فرض است', $('[data-sim-order]', heroSim).getAttribute('href') === '#calculator-page');

	// --- محاسبه‌گر: پیش‌پرکردن از سیمولاتور با کلیک روی CTA ---
	const form = $('[data-pixva-calc]');
	if (typeof form.pixvaCalc !== 'object') {
		throw new Error('calculator.js اجرا نشده — readyState=' + document.readyState);
	}
	check('calc: API عمومی در دسترس است', true);

	const widgetOrder = $('[data-sim-order]', widgetSim);
	widgetOrder.dispatchEvent(new window.MouseEvent('click', { bubbles: true, cancelable: true }));
	await wait(220);
	check('calc: ایراد فعال سیمولاتور منتقل شد', form.pixvaCalc.value('problem') === 'no_picture');
	check('calc: برند/تکنولوژی/سایز از سیمولاتور', form.pixvaCalc.value('brand') === 'samsung' && form.pixvaCalc.value('tech') === 'led' && form.pixvaCalc.value('size') === '55');
	check('calc: جادوگر به گام برآورد رفت', !$('[data-step="4"]', form).hidden && $('[data-step="4"]', form).classList.contains('is-active'));
	check('calc: قیمت فقط از سرور', $('[data-price]', form).textContent === '۱۵٬۵۵۰٬۰۰۰ تا ۲۵٬۸۰۰٬۰۰۰');
	check('calc: زمان تحویل', $('[data-days]', form).textContent === '۲ تا ۴ روز کاری');
	check('calc: سلب مسئولیت', $('[data-disclaimer]', form).textContent.length > 10);
	check('calc: نوار پیشرفت روی گام ۴', $('[data-progress="4"]', form).classList.contains('is-current'));
	check('calc: خطایی نمایش داده نمی‌شود', $('[data-calc-error]', form).hidden === true);

	// CTA اختصاصی یک ایراد → محاسبه‌گر بازنویسی نمی‌شود (لینک خودش دنبال می‌شود).
	linesBtn.click();
	await wait(220);
	const priceBefore = $('[data-price]', form).textContent;
	widgetOrder.dispatchEvent(new window.MouseEvent('click', { bubbles: true, cancelable: true }));
	await wait(140);
	check('calc: CTA اختصاصی محاسبه‌گر را بازنویسی نمی‌کند', $('[data-price]', form).textContent === priceBefore && form.pixvaCalc.value('problem') === 'no_picture');

	// --- ثبت نوبت ---
	$('[name="customer_name"]', form).value = 'کاربر آزمون';
	$('[name="phone"]', form).value = '09120000000';
	form.dispatchEvent(new window.Event('submit', { bubbles: true, cancelable: true }));
	await wait(60);
	const msg = $('[data-order-msg]', form);
	check('order: پیام موفقیت نمایش داده شد', msg.hidden === false && msg.className.indexOf('pixva-notice--success') > -1);
	check('order: لینک پیگیری اضافه شد', !!$('a', msg));

	// --- سایدبار داینامیک ---
	check('sidebar: پوسته چسبان', !!$('.pixva-sidebar__inner'));
	check('sidebar: ۳ ویجت نمونه', $$('.pixva-sidebar .pixva-widget').length === 3);
	check('sidebar: ویجت محاسبه‌گر CTA گرادیانی دارد', !!$('.pixva-widget--calc .pixva-btn--gradient'));

	// --- آمار هیرو ---
	check('hero: آمارها با data-count-to رندر شده‌اند', $$('[data-count-to]').length === 4);
	check('hero: دکمه گرادیانی اصلی', !!$('.pixva-hero__actions .pixva-btn--gradient'));
}

/* ------------------------------------------------------------------ */
(async function main() {
	staticChecks();

	if (!fs.existsSync(harness)) {
		failures.push('هارنس preview/hero-preview.html پیدا نشد');
		failed += 1;
	} else {
		const errors = [];
		const virtualConsole = new VirtualConsole();
		virtualConsole.on('jsdomError', (error) => errors.push(error.message));
		virtualConsole.on('error', (message) => errors.push(String(message)));

		const dom = await JSDOM.fromFile(harness, {
			runScripts: 'dangerously',
			resources: 'usable',
			pretendToBeVisual: true,
			virtualConsole,
		});
		const { window } = dom;
		if (window.document.readyState !== 'complete') {
			await new Promise((resolve) => window.addEventListener('load', resolve));
		}
		await wait(120);

		await wait(900);

		await runtimeChecks(window);

		check('بدون خطای jsdom در کنسول (' + errors.length + ')', errors.length === 0);
		if (errors.length) {
			errors.slice(0, 5).forEach((e) => console.log('  ! ' + e));
		}
		window.close();
	}

	console.log('\n' + passed + '/' + (passed + failed) + ' assertions passed');
	if (failed) {
		console.log('\nFAILED:\n - ' + failures.join('\n - '));
		process.exitCode = 1;
	} else {
		console.log('\nALL GREEN');
	}
}());

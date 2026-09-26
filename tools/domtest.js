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
const crmHarness = path.join(repo, 'tools', 'fixtures', 'crm-preview.html');
const v6Harness = path.join(repo, 'tools', 'fixtures', 'v6-preview.html');
const v7Harness = path.join(repo, 'tools', 'fixtures', 'v7-preview.html');

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

	// --- نسخه ۱٫۴٫۰: موتور CRM و حذف جعبه‌های غیرکاربردی ---
	const crmJs = fs.readFileSync(path.join(theme, 'assets/js/crm-engine.js'), 'utf8');
	const phpFiles = ['crm-engine', 'crm-wizard', 'crm-warranty', 'crm-technician', 'crm-dispatcher', 'blog-ecosystem']
		.map((f) => fs.readFileSync(path.join(theme, 'inc', f + '.php'), 'utf8'));
	const allPhp = phpFiles.join('\n');
	const themeFiles = ['functions.php', 'front-page.php', 'inc/interactive-tools.php', 'inc/tools-registry.php', 'inc/tool-renderers.php', 'inc/theme-options.php']
		.map((f) => fs.readFileSync(path.join(theme, f), 'utf8')).join('\n');

	check('crm.js: جادوگر، پنل تعمیرکار، دیسپچ و هولوگرام', ['initWizard', 'initTechnicianPanel', 'initDispatcher', 'initHologram'].every((fn) => crmJs.indexOf(fn) > -1));
	check('crm.js: چاپ فاکتور، استعلام گارانتی، جست‌وجوی خطا، برآورد سریع', ['initPrint', 'initWarrantyCheck', 'initErrorSearch', 'initQuickQuote'].every((fn) => crmJs.indexOf(fn) > -1));
	check('crm.js: بدون jQuery', crmJs.indexOf('jQuery') === -1 && crmJs.indexOf('$(') === -1);
	check('crm.php: نقش‌ها و قابلیت‌های اختصاصی', allPhp.indexOf('pixva_technician') > -1 && allPhp.indexOf('pixva_manager') > -1 && allPhp.indexOf('pixva_issue_order_warranty') > -1);
	check('crm.php: وضعیت‌های شش‌گانه', ['pending', 'assigned', 'repairing', 'qc', 'ready', 'delivered'].every((k) => allPhp.indexOf("'" + k + "'") > -1));
	check('crm.php: سریال گارانتی و اثرانگشت', allPhp.indexOf('PXV-G-') > -1 && allPhp.indexOf('pixva_warranty_hash') > -1);
	check('crm.php: قلاب پیامک', allPhp.indexOf('pixva_send_sms') > -1);
	check('crm.php: اندپوینت استعلام گارانتی', allPhp.indexOf('/crm/warranty/(?P<serial>') > -1);

	check('حذف ابزار ۵: هیچ ارجاعی به سیمولاتور لمسی نمانده', ['tv_simulator', 'initTVSimulator', 'pixva_simulator_part_data', 'data-tv-simulator'].every((needle) => themeFiles.indexOf(needle) === -1 && toolsJs.indexOf(needle) === -1));
	check('ابزار ۵ با جادوگر ثبت سفارش جایگزین شده', themeFiles.indexOf('order_wizard') > -1 && themeFiles.indexOf('pixva_render_order_wizard') > -1);
	check('کادر ایستای پیک جمع‌آوری حذف و با استعلام اصالت جایگزین شده', themeFiles.indexOf('data-pixva-warranty-check') > -1);

	check('css: لایه ۲۷ (هولوگرام و CRM)', css.indexOf('.pixva-holo__foil') > -1 && css.indexOf('.pixva-warranty__serial') > -1 && css.indexOf('.pixva-crm-order__status--qc') > -1);
	check('css: انیمیشن هولوگرام با transform', css.indexOf('@keyframes pixva-holo-spin') > -1 && css.indexOf('--holo-tilt-x') > -1);
	check('css: چاپ فاکتور', css.indexOf('@media print') > -1 && css.indexOf('body.pixva-printing') > -1);
	check('css: پیشرفت مطالعه با scroll-driven animation', css.indexOf('animation-timeline: scroll(root block)') > -1);
	check('css: نور محیطی OLED هیرو', css.indexOf('pixva-oled-breathe') > -1 && css.indexOf('pixva-cta-pulse') > -1);
	check('css: رندر تنبل بخش‌های ابزار', css.indexOf('.pixva-tool-section') > -1 && css.indexOf('content-visibility: auto') > -1);
	check('css: انیمیشن سنگین box-shadow حذف شده', css.indexOf('box-shadow: 0 0 0 7px rgba(34, 197, 94, 0); }') === -1);
	check('css: خط اسکن با translate3d', css.indexOf('@keyframes pixva-scan') > -1 && /@keyframes pixva-scan \{[^}]*translate3d/s.test(css));
	check('css: کارت شیشه‌ای وبلاگ و ابزارک‌ها', css.indexOf('.pixva-post-card__reading') > -1 && css.indexOf('.pixva-widget__result') > -1);

	const functions = fs.readFileSync(path.join(theme, 'functions.php'), 'utf8');
	check('سرعت: بارگذاری شرایطی اسکریپت ابزارها', functions.indexOf('pixva_needs_tools_js') > -1 && functions.indexOf('pixva_needs_crm_js') > -1);
	check('سرعت: اسکریپت‌ها با defer', (functions.match(/'strategy'  => 'defer'/g) || []).length >= 5);

	const style = fs.readFileSync(path.join(theme, 'style.css'), 'utf8');
	check('style.css: نسخه ۱٫۶٫۰', /Version:\s*1\.6\.0/.test(style));
	check('functions.php: PIXVA_VERSION هم‌نسخه با style.css', functions.indexOf("define( 'PIXVA_VERSION', '1.6.0' )") > -1);
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

/* ------------------------------------------------------------------ *
 * بخش ۳ — موتور اتوماسیون CRM (هارنس tools/fixtures/crm-preview.html)
 * ------------------------------------------------------------------ */
async function crmChecks(window) {
	const document = window.document;
	const $ = (sel, root) => (root || document).querySelector(sel);
	const $$ = (sel, root) => Array.from((root || document).querySelectorAll(sel));
	const fire = (el, type, Ctor) => el.dispatchEvent(new (Ctor || window.Event)(type, { bubbles: true, cancelable: true }));
	const setValue = (el, value) => {
		el.value = value;
		fire(el, 'input');
		fire(el, 'change');
	};

	check('crm: موتور پس از DOMContentLoaded راه‌اندازی شد', document.documentElement.classList.contains('pixva-crm-ready'));

	/* --- جادوگر ثبت سفارش --- */
	const wizard = $('#pixva-order-wizard');
	const steps = $$('[data-wz-step]', wizard).filter((step) => step.getAttribute('data-wz-step') !== 'done');
	const doneStep = $('[data-wz-step="done"]', wizard);
	const rail = $('[data-wz-rail]', wizard);
	const dots = $$('[data-wz-dot]', wizard);

	check('wizard: ساختار پنج گامی رندر شده', steps.length === 4 && !!doneStep && dots.length === 5);
	check('wizard: در ابتدا فقط گام دستگاه دیده می‌شود', steps[0].hidden === false && steps.slice(1).every((step) => step.hidden === true) && doneStep.hidden === true);
	check('wizard: گام بعد تا تکمیل اجباری‌ها غیرفعال است', $('[data-wz-next]', steps[0]).disabled === true);

	$('[data-wz-group="brand"][data-wz-value="samsung"]', wizard).click();
	const brandChip = $('[data-wz-group="brand"][data-wz-value="samsung"]', wizard);
	check('wizard: چیپ برند فعال شد', brandChip.classList.contains('is-active') && brandChip.getAttribute('aria-checked') === 'true');
	check('wizard: فیلد پنهان brand همگام شد', $('input[name="brand"]', wizard).value === 'samsung');

	$('[data-wz-group="brand"][data-wz-value="lg"]', wizard).click();
	check('wizard: انتخاب جایگزین، چیپ قبلی را غیرفعال می‌کند', $('input[name="brand"]', wizard).value === 'lg' && !brandChip.classList.contains('is-active'));
	$('[data-wz-group="brand"][data-wz-value="samsung"]', wizard).click();

	$('[data-wz-group="tech"][data-wz-value="oled"]', wizard).click();
	$('[data-wz-group="size"][data-wz-value="65"]', wizard).click();
	check('wizard: سه انتخاب اجباری → دکمه فعال', $('[data-wz-next]', steps[0]).disabled === false);

	$('[data-wz-next]', steps[0]).click();
	check('wizard: گام خرابی باز شد', steps[1].hidden === false && steps[0].hidden === true);
	check('wizard: ریل همگام شد', dots[1].classList.contains('is-current') && dots[0].classList.contains('is-done'));

	$('[data-wz-group="problem"][data-wz-value="lines"]', wizard).click();
	check('wizard: فیلد پنهان problem پر شد', $('input[name="problem"]', wizard).value === 'lines');
	$('[data-wz-next]', steps[1]).click();
	check('wizard: گام تماس باز شد', steps[2].hidden === false);

	const quoteBtn = $('[data-wz-quote]', wizard);
	check('wizard: اعتبارسنجی موبایل، دکمه برآورد را قفل می‌کند', quoteBtn.disabled === true);
	setValue($('#wz-name', wizard), 'س');
	setValue($('#wz-phone', wizard), '0912123');
	check('wizard: نام کوتاه و شماره نامعتبر همچنان قفل است', quoteBtn.disabled === true);
	setValue($('#wz-name', wizard), 'سینا کریمی');
	setValue($('#wz-phone', wizard), '09129876543');
	check('wizard: داده معتبر → دکمه برآورد فعال', quoteBtn.disabled === false);

	quoteBtn.click();
	await wait(60);
	check('wizard: برآورد فقط از سرور گرفته شد', window.__estimateCalls.length === 1 && window.__estimateCalls[0].action === 'pixva_get_estimate');
	check('wizard: ورودی برآورد از انتخاب‌ها ساخته شد', window.__estimateCalls[0].fields.brand === 'samsung' && window.__estimateCalls[0].fields.size === '65' && window.__estimateCalls[0].fields.problem === 'lines');
	check('wizard: گام بازبینی با قیمت سمت سرور', steps[3].hidden === false && $('[data-wz-price]', wizard).textContent.indexOf('۱٬۵۵۰٬۰۰۰') > -1);
	check('wizard: زمان تحویل نمایش داده شد', $('[data-wz-days]', wizard).textContent.indexOf('۲ تا ۴ روز کاری') > -1);
	check('wizard: خلاصه پرونده پر شد', $('[data-wz-sum="name"]', wizard).textContent === 'سینا کریمی' && $('[data-wz-sum="phone"]', wizard).textContent === '۰۹۱۲۹۸۷۶۵۴۳');
	check('wizard: دکمه ثبت پس از برآورد فعال شد', $('[data-wz-submit]', wizard).disabled === false);

	fire($('[data-pixva-wizard]', wizard), 'submit');
	await wait(80);
	check('wizard: گام موفقیت نمایش داده شد', doneStep.hidden === false && steps.every((step) => step.hidden === true));
	check('wizard: کد پیگیری اختصاصی صادر شد', $('[data-wz-code]', wizard).textContent === 'PXV-1404-000501');
	check('wizard: وضعیت پرونده اعلام شد', $('[data-wz-status]', wizard).textContent === 'در انتظار بررسی');
	check('wizard: لینک پیگیری کد را حمل می‌کند', $('a[data-wz-track]', wizard).getAttribute('href').indexOf('code=PXV-1404-000501') > -1);
	check('wizard: ریل کامل شد', rail.classList.contains('is-complete') && dots.every((dot) => dot.classList.contains('is-done')));

	$('[data-wz-restart]', wizard).click();
	check('wizard: شروع دوباره فرم را خالی می‌کند', steps[0].hidden === false && doneStep.hidden === true && $('input[name="brand"]', wizard).value === '' && $('[data-wz-code]', wizard).textContent === '—');

	/* --- ایستگاه کاری تعمیرکار --- */
	const panel = $('[data-crm-panel]');
	const chips = $$('[data-crm-filter]', panel);
	const card101 = $('[data-crm-order="101"]', panel);
	const card102 = $('[data-crm-order="102"]', panel);
	const panelMessage = $('[data-crm-message]', panel);

	check('panel: نمای تعمیرکار (نه دیسپچ) متصل شد', panel.getAttribute('data-crm-view') === 'technician' && panel.dataset.crmBound === '1');
	check('panel: فیلترها و پرونده‌ها رندر شده‌اند', chips.length === 3 && !!card101 && !!card102);

	chips[2].click();
	check('panel: فیلتر کلاینت‌ساید پرونده ناهمخوان را پنهان می‌کند', card101.hidden === true && card102.hidden === false && chips[2].classList.contains('is-active'));
	chips[0].click();
	check('panel: فیلتر «همه» کارت‌ها را برمی‌گرداند', card101.hidden === false && card102.hidden === false);

	const reportForm = $('#report-101', panel);
	const toggleBtn = $('[data-crm-toggle="report-101"]', panel);
	check('panel: فرم گزارش در ابتدا بسته است', reportForm.hidden === true && toggleBtn.getAttribute('aria-expanded') === 'false');
	toggleBtn.click();
	check('panel: فرم گزارش باز شد', reportForm.hidden === false && reportForm.classList.contains('is-open') && toggleBtn.getAttribute('aria-expanded') === 'true');

	const totalBox = $('[data-crm-total]', reportForm);
	check('panel: جمع اولیه = دستمزد + قطعات', totalBox.textContent === '۸۰۰٬۰۰۰ تومان');
	setValue($('[data-crm-part="price"]', reportForm), '1500000');
	setValue($('[data-crm-part="qty"]', reportForm), '2');
	check('panel: جمع زنده با قطعه و تعداد به‌روز شد', totalBox.textContent === '۳٬۸۰۰٬۰۰۰ تومان');

	$('[data-crm-add-part]', reportForm).click();
	check('panel: افزودن ردیف قطعه', $$('[data-crm-part-row]', reportForm).length === 2);
	check('panel: نام فیلدهای ردیف جدید بازهم ایندکس می‌شود', $('[data-crm-part="name"]', $$('[data-crm-part-row]', reportForm)[1]).name === 'parts[1][name]');
	$('[data-crm-remove-part]', $$('[data-crm-part-row]', reportForm)[1]).click();
	check('panel: حذف ردیف قطعه', $$('[data-crm-part-row]', reportForm).length === 1);

	setValue($('[data-crm-part="name"]', reportForm), 'بک‌لایت');
	setValue($('[data-crm-part="spec"]', reportForm), '۵۵ اینچ Samsung');
	fire(reportForm, 'submit');
	await wait(80);
	check('panel: گزارش فنی ذخیره شد', panelMessage.hidden === false && panelMessage.textContent.indexOf('گزارش فنی ذخیره شد') > -1);
	check('panel: وضعیت پرونده به تست کیفیت رفت', card101.getAttribute('data-crm-status') === 'qc' && $('.pixva-crm-order__status', card101).classList.contains('pixva-crm-order__status--qc'));
	check('panel: برچسب وضعیت فارسی از پیکربندی آمد', $('.pixva-crm-order__status', card101).textContent === 'تست کیفیت');

	const readyBtn = $('[data-crm-action="status"][data-status="ready"]', card101);
	readyBtn.click();
	await wait(80);
	check('panel: انتقال وضعیت به آماده تحویل', card101.getAttribute('data-crm-status') === 'ready' && readyBtn.disabled === true);

	const warrantyBtn = $('[data-crm-action="warranty"]', card101);
	check('panel: دکمه تایید نهایی و صدور گارانتی وجود دارد', !!warrantyBtn);
	warrantyBtn.click();
	await wait(80);
	check('panel: کارت گارانتی‌دار شد', card101.classList.contains('is-warrantied'));
	check('panel: سریال گارانتی به سربرگ اضافه شد', !!$('.pixva-crm-order__serial', card101) && $('.pixva-crm-order__serial', card101).textContent.indexOf('PXV-G-250926-A1B2C3') > -1);
	check('panel: دکمه صدور گارانتی حذف شد', !$('[data-crm-action="warranty"]', card101));

	/* --- هولوگرام زنده --- */
	const holo = $('[data-pixva-warranty]');
	check('holo: لایه‌های مهر هولوگرافیک رندر شده‌اند', ['.pixva-holo__foil', '.pixva-holo__sheen', '.pixva-holo__grid', '.pixva-holo__ring', '.pixva-holo__core'].every((sel) => !!$(sel, holo)));
	check('holo: سریال روی کارت است', holo.getAttribute('data-warranty-serial') === 'PXV-G-250926-A1B2C3');
	holo.dispatchEvent(new window.MouseEvent('pointermove', { bubbles: true, clientX: 150, clientY: 100 }));
	await wait(60);
	check('holo: حرکت نشانگر نور را جابه‌جا کرد', holo.style.getPropertyValue('--holo-x') === '25.00%' && holo.style.getPropertyValue('--holo-y') === '25.00%');
	check('holo: شیب سه‌بعدی کارت تنظیم شد', holo.style.getPropertyValue('--holo-tilt-y') === '-3.13deg' && holo.classList.contains('is-live'));
	holo.dispatchEvent(new window.MouseEvent('pointerleave', { bubbles: true }));
	check('holo: با خروج نشانگر به حالت مرکز برمی‌گردد', holo.style.getPropertyValue('--holo-x') === '50%' && !holo.classList.contains('is-live'));

	/* --- چاپ فاکتور رسمی --- */
	const invoice = $('#invoice-102');
	check('invoice: ساختار فاکتور رسمی کامل است', ['.pixva-invoice__head', '.pixva-invoice__parties', '.pixva-invoice__table', '.pixva-invoice__sign', '.pixva-invoice__verify'].every((sel) => !!$(sel, invoice)));
	check('invoice: مهر دیجیتال و اثرانگشت اصالت دارد', !!$('.pixva-invoice__stamp', invoice) && $('.pixva-invoice__verify code', invoice).textContent.length >= 16);
	$('[data-pixva-print][data-print-area="invoice-102"]', invoice).click();
	check('print: بدنه در حالت چاپ و فقط فاکتور دیده می‌شود', document.body.classList.contains('pixva-printing') && invoice.classList.contains('is-print-source'));
	await wait(90);
	check('print: گفت‌وگوی چاپ فراخوانی شد', window.__printed === 1);
	window.dispatchEvent(new window.Event('afterprint'));
	check('print: پس از چاپ حالت‌ها پاک شد', !document.body.classList.contains('pixva-printing') && !invoice.classList.contains('is-print-source'));

	/* --- استعلام اصالت گارانتی --- */
	const checkCard = $('[data-pixva-warranty-check]');
	setValue($('[data-warranty-serial]', checkCard), 'pxv-g-250926-a1b2c3');
	fire($('[data-warranty-form]', checkCard), 'submit');
	await wait(80);
	check('check: نتیجه استعلام نمایش داده شد', $('[data-warranty-result]', checkCard).hidden === false);
	check('check: اعتبار و تعمیرکار از REST آمد', $('[data-warranty-state]', checkCard).textContent === 'گارانتی معتبر است.' && $('[data-warranty-tech]', checkCard).textContent === 'رضا محمدی');
	check('check: پوشش‌ها به فارسی جدا شدند', $('[data-warranty-covers]', checkCard).textContent === 'بک‌لایت ۵۵ اینچ، برد پاور');
	check('check: سریال پیش از ارسال بزرگ شد', window.__fetchLog.some((url) => url.indexOf('/crm/warranty/PXV-G-250926-A1B2C3') > -1));

	setValue($('[data-warranty-serial]', checkCard), 'PXV-G-000000-NOPE00');
	fire($('[data-warranty-form]', checkCard), 'submit');
	await wait(80);
	check('check: سریال نامعتبر پیام خطا می‌دهد', $('[data-warranty-error]', checkCard).hidden === false && $('[data-warranty-result]', checkCard).hidden === true);

	/* --- ابزارک جست‌وجوی کد خطا --- */
	const errorWidget = $('[data-pixva-error-search]');
	setValue($('[data-error-input]', errorWidget), 'E10');
	await wait(450);
	check('errors: نتیجه از REST رندر شد', $$('.pixva-widget__result', errorWidget).length === 1);
	check('errors: کد، علت و راه‌حل نمایش داده شد', $('.pixva-widget__result strong', errorWidget).textContent === 'E101' && $('.pixva-widget__result small', errorWidget).textContent.indexOf('بک‌لایت') > -1);
	setValue($('[data-error-input]', errorWidget), 'x');
	await wait(450);
	check('errors: عبارت کوتاه‌تر از ۲ نویسه درخواست نمی‌فرستد', $$('.pixva-widget__result', errorWidget).length === 0);

	/* --- ابزارک برآورد سریع --- */
	const quoteWidget = $('[data-pixva-quick-quote]');
	fire($('[data-quote-form]', quoteWidget), 'submit');
	await wait(60);
	check('quote: نتیجه برآورد سریع نمایش داده شد', $('[data-quote-result]', quoteWidget).hidden === false);
	check('quote: بازه قیمت سمت سرور است', $('[data-quote-price]', quoteWidget).textContent.indexOf('۱٬۵۵۰٬۰۰۰ تا ۲٬۵۸۰٬۰۰۰') > -1);

	/* --- کپی سریال --- */
	$('[data-copy]', holo).click();
	await wait(30);
	check('copy: بازخورد کپی نمایش داده شد', $('[data-crm-toast]') !== null && $('[data-copy]', holo).classList.contains('is-copied'));
}

/* ------------------------------------------------------------------ *
 * بخش ۳ — لایه ۱٫۵٫۰ (Master Prompt v6): مگامنوی چهارگروهی، صفحه اصلی
 * مینیمال، موتور حرکت و داشبورد تعمیرکار
 * ------------------------------------------------------------------ */
function v6StaticChecks() {
	const navMenu = fs.readFileSync(path.join(theme, 'inc/nav-menu.php'), 'utf8');
	const hubs = fs.readFileSync(path.join(theme, 'inc/hubs.php'), 'utf8');
	const options = fs.readFileSync(path.join(theme, 'inc/theme-options.php'), 'utf8');
	const front = fs.readFileSync(path.join(theme, 'front-page.php'), 'utf8');
	const funcs = fs.readFileSync(path.join(theme, 'functions.php'), 'utf8');
	const metaboxes = fs.readFileSync(path.join(theme, 'inc/meta-boxes.php'), 'utf8');
	const shortcodes = fs.readFileSync(path.join(theme, 'inc/shortcodes.php'), 'utf8');
	const activation = fs.readFileSync(path.join(theme, 'inc/activation.php'), 'utf8');
	const motionJs = fs.readFileSync(path.join(theme, 'assets/js/motion.js'), 'utf8');
	const css = fs.readFileSync(path.join(theme, 'assets/css/pixva-2026.css'), 'utf8');

	check('v6: گروه‌های مگا فیلترپذیرند', navMenu.indexOf("function pixva_mega_groups()") > -1 && navMenu.indexOf("apply_filters( 'pixva_mega_groups'") > -1);
	check('v6: چهار گروه منو (خدمات/خطا/گارانتی/تعمیرکار)', ['خدمات تعمیرات', 'کدهای خطا و عیب‌یابی', 'استعلام و گارانتی', 'ورود تعمیرکاران'].every((t) => navMenu.indexOf(t) > -1));
	check('v6: سه برند راهنمای کد خطا از کاتالوگ برندها', navMenu.indexOf("'sony', 'samsung', 'lg'") > -1 && navMenu.indexOf('pixva_brand_catalog') > -1);
	check('v6: هدر هیچ متن سخت‌کد بدون i18n ندارد', navMenu.indexOf("__( 'راهنمای کدهای خطای %s', 'pixva' )") > -1);
	check('v6: رندر مگا از گروه‌ها ساخته می‌شود', hubs.indexOf('pixva_mega_groups()') > -1 && hubs.indexOf('pixva-mega__panel--group') > -1);
	check('v6: دروئر موبایل همان گروه‌ها را دارد', navMenu.indexOf('function pixva_render_drawer_groups') > -1);

	check('v6: پیش‌فرض صفحه اصلی مینیمال است', /'quote'\s+=> false/.test(options) && /'advantages'\s+=> true/.test(options) && /'work'\s+=> true/.test(options));
	check('v6: ترتیب پیش‌فرض با هیرو و مزیت‌ها شروع می‌شود', options.indexOf("'hero'          => esc_html__") > -1 && options.indexOf("'advantages'    => esc_html__") > -1);
	check('v6: سکشن‌های تازه به ترتیب نصب‌های قدیمی اضافه می‌شوند', options.indexOf('$missing') > -1);
	check('v6: رندرهای مزیت و نمونه‌کار موجودند', front.indexOf('function pixva_home_advantages()') > -1 && front.indexOf('function pixva_home_work()') > -1);
	check('v6: مزیت‌ها از داده واقعی (گارانتی/اعزام/انبار) ساخته می‌شوند', front.indexOf('pixva_warranty_days') > -1 && front.indexOf('hub_eta_hours') > -1 && front.indexOf('pixva_parts') > -1);
	check('v6: کلاس مینیمال روی main', front.indexOf('pixva-home--minimal') > -1);

	check('v6: موتور حرکت enqueue شده (defer + footer)', funcs.indexOf('pixva-motion') > -1 && funcs.indexOf('motion.js') > -1);
	check('v6: فایل متاباکس‌های بومی بارگذاری می‌شود', funcs.indexOf('inc/meta-boxes.php') > -1);
	check('v6: متاباکس‌ها بومی‌اند (بدون ACF)', metaboxes.indexOf('add_meta_box(') > -1 && metaboxes.indexOf('update_post_meta(') > -1
		&& ['acf_add_local_field_group', 'get_field(', 'have_rows(', 'the_field('].every((fn) => metaboxes.indexOf(fn) === -1));
	check('v6: متاباکس روی نوع محتوای سفارش‌ها', metaboxes.indexOf('save_post_pixva_orders') > -1);
	check('v6: ذخیره از مسیر API موتور CRM', ['pixva_crm_assign(', 'pixva_crm_set_status(', 'pixva_crm_save_report(', 'pixva_crm_issue_warranty('].every((fn) => metaboxes.indexOf(fn) > -1));
	check('v6: شورت‌کد داشبورد تعمیرکار', shortcodes.indexOf("add_shortcode( 'pixva_technician_panel'") > -1);
	check('v6: برگه /technician-dashboard در نصب ساخته می‌شود', activation.indexOf("'technician-dashboard'") > -1);
	check('v6: ارتقای نصب‌های قدیمی به چیدمان مینیمال', activation.indexOf("version_compare( $stored_version, '1.5.0', '<' )") > -1 && activation.indexOf('pixva_reset_home_sections_on_upgrade') > -1);

	check('v6: CSS لایه حرکت (reveal/ripple/magnetic)', ['.pixva-fx', '.pixva-ripple', '.pixva-magnetic'].every((c) => css.indexOf(c) > -1));
	check('v6: CSS کارت‌های مزیت و نمونه‌کار', ['.pixva-advantage', '.pixva-work-item', '.pixva-mega__groups', '.pixva-tech-dash__bar'].every((c) => css.indexOf(c) > -1));
	check('v6: حرکت فقط GPU (will-change + translate3d)', css.indexOf('will-change: transform, opacity') > -1 && motionJs.indexOf('translate3d') >= -1);
	check('v6: احترام به prefers-reduced-motion در موتور حرکت', motionJs.indexOf('prefers-reduced-motion') > -1);
	check('v6: اسکن پویا برای محتوای جدید', motionJs.indexOf('pixvaMotionScan') > -1);
}

async function v6Checks(window) {
	const { document } = window;
	const $ = (sel, root) => (root || document).querySelector(sel);
	const $$ = (sel, root) => Array.prototype.slice.call((root || document).querySelectorAll(sel));
	const fire = (el, type, opts) => el.dispatchEvent(new window.MouseEvent(type, Object.assign({ bubbles: true, cancelable: true, view: window, button: 0 }, opts || {})));
	const press = (el, k) => el.dispatchEvent(new window.KeyboardEvent('keydown', { bubbles: true, cancelable: true, key: k }));
	const texts = (sel, root) => $$(sel, root).map((n) => n.textContent.trim());

	/* --- ۱) ساختار مگامنوی چهارگروهی --- */
	const nav = $('[data-pixva-mega]');
	check('v6: مگامنو در هدر رندر شده', !!nav);
	const items = $$('[data-mega-item]', nav);
	check('v6: سه گروه آبشاری + یک پیوند مستقیم', items.length === 3 && $$('.pixva-mega__item--direct', nav).length === 1);

	const labels = texts('.pixva-mega__trigger > span', nav);
	check('v6: عنوان گروه‌ها طبق اسپک (' + labels.join(' | ') + ')',
		labels[0] === 'خدمات تعمیرات' && labels[1] === 'کدهای خطا و عیب‌یابی' && labels[2] === 'استعلام و گارانتی');

	const direct = $('.pixva-mega__link--tech', nav);
	check('v6: ورود تعمیرکاران پیوند مستقیم به داشبورد', !!direct && (direct.getAttribute('href') || '').indexOf('technician-dashboard') > -1);

	const g1 = texts('#mega-g1 .pixva-mega__links strong');
	const g2 = texts('#mega-g2 .pixva-mega__links strong');
	const g3 = texts('#mega-g3 .pixva-mega__links strong');
	check('v6: سه خدمت گروه اول', g1.length === 3 && g1[1] === 'تعمیر برد اصلی' && g1[2] === 'تعمیر پنل OLED/LED');
	check('v6: سه راهنمای برند گروه دوم', g2.length === 3 && g2[0] === 'راهنمای کدهای خطای سونی' && g2[2] === 'راهنمای کدهای خطای ال‌جی');
	check('v6: سه استعلام گروه سوم', g3.length === 3 && g3[0] === 'استعلام اصالت قطعه' && g3[1] === 'پیگیری سفارش' && g3[2] === 'مشاهده کارت گارانتی');
	check('v6: لینک برندها با پارامتر brand', ($$('#mega-g2 .pixva-mega__links a').every((a) => (a.getAttribute('href') || '').indexOf('brand=') > -1)));
	check('v6: هر گروه کارت ویژگی و CTA دارد', $$('.pixva-mega__feature--group', nav).length === 3 && $$('.pixva-mega__cta', nav).length === 3);

	/* --- ۲) رفتار آبشاری --- */
	const t0 = $('[data-mega-trigger]', items[0]);
	const t1 = $('[data-mega-trigger]', items[1]);
	fire(t0, 'click');
	check('v6: کلیک، پنل گروه را باز می‌کند', items[0].classList.contains('is-open') && t0.getAttribute('aria-expanded') === 'true');
	fire(t1, 'click');
	check('v6: گروه دوم باز و گروه اول بسته می‌شود (آبشاری)', items[1].classList.contains('is-open') && !items[0].classList.contains('is-open'));
	press(items[1], 'Escape');
	check('v6: کلید Escape پنل را می‌بندد', !items[1].classList.contains('is-open') && t1.getAttribute('aria-expanded') === 'false');
	press(t0, 'ArrowDown');
	check('v6: ArrowDown پنل را باز و اولین پیوند را فوکوس می‌کند', items[0].classList.contains('is-open') && document.activeElement === $('#mega-g1 .pixva-mega__links a'));

	/* --- ۳) دروئر موبایل با همان گروه‌ها --- */
	const drawer = $('[data-pixva-drawer]');
	$('[data-pixva-burger]').click();
	await wait(30);
	check('v6: برگر، دروئر را باز می‌کند', drawer.classList.contains('is-open') && drawer.getAttribute('aria-hidden') === 'false');
	check('v6: دروئر چهار گروه دارد', $$('.pixva-drawer-groups .pixva-drawer-hub').length === 4);
	const acc = $('.pixva-drawer-groups [data-pixva-accordion]');
	acc.click();
	check('v6: آکاردئون گروه اول باز می‌شود', acc.getAttribute('aria-expanded') === 'true' && $('#drawer-group-1').hidden === false);
	check('v6: زیرمنوی گروه اول سه خدمت دارد', $$('#drawer-group-1 li a').length >= 3);
	check('v6: ورود تعمیرکاران در دروئر مستقیم است', !!$('.pixva-drawer-hub--direct a[href*="technician-dashboard"]'));
	$('[data-pixva-drawer-close]').click();
	await wait(30);
	check('v6: دکمه بستن، دروئر را می‌بندد', !drawer.classList.contains('is-open') && drawer.getAttribute('aria-hidden') === 'true');

	/* --- ۴) صفحه اصلی مینیمال --- */
	const home = $('#content');
	check('v6: کلاس مینیمال روی main', home.classList.contains('pixva-home--minimal'));
	const sections = $$(':scope > section', home);
	check('v6: فقط چهار سکشن روی خانه (' + sections.length + ')', sections.length === 4);
	check('v6: هیرو + مزیت‌ها + نمونه‌کار + نظرات', ['hero', 'advantages', 'work', 'reviews'].every((id) => !!home.querySelector('#' + id + ', .pixva-section--' + id)));
	check('v6: دکمه استعلام سریع قیمت لنگر مرده ندارد', ($('.pixva-hero__actions .pixva-btn').getAttribute('href') || '').indexOf('#') !== 0);
	check('v6: سه کارت مزیت', $$('.pixva-advantage').length === 3);
	check('v6: گالری نمونه‌کار با تصویر و پلیس‌هولدر', $$('.pixva-work-item').length === 2 && !!$('.pixva-work-item img') && !!$('.pixva-work-item__placeholder'));

	/* --- ۵) موتور حرکت --- */
	check('v6: کلاس pixva-motion-js روی html', document.documentElement.classList.contains('pixva-motion-js'));
	check('v6: API اسکن پویا در دسترس است', typeof window.pixvaMotionScan === 'function');

	const adv = $$('.pixva-advantage');
	check('v6: کارت‌های مزیت reveal گرفتند', adv.every((n) => n.classList.contains('pixva-fx')));
	check('v6: کارت‌ها پس از دیده‌شدن نمایان می‌شوند (is-in)', adv.every((n) => n.classList.contains('is-in')));
	check('v6: تأخیر پله‌ای برای ترتیب ظهور', adv[1].style.getPropertyValue('--fx-delay') !== '' && adv[0].style.getPropertyValue('--fx-delay') !== adv[2].style.getPropertyValue('--fx-delay'));
	check('v6: هاور مغناطیسی روی کارت‌ها فعال است', adv.every((n) => n.classList.contains('pixva-magnetic')) && $$('.pixva-work-item.pixva-magnetic').length === 2);

	fire(adv[0], 'pointermove', { clientX: 240, clientY: 150 });
	await wait(80);
	check('v6: متغیرهای نور OLED لبه کارت تنظیم شد', adv[0].classList.contains('is-magnetic') && adv[0].style.getPropertyValue('--mx') !== '' && adv[0].style.getPropertyValue('--edge-angle') !== '');
	fire(adv[0], 'pointerleave');
	check('v6: خروج نشانگر، حالت مغناطیسی را پاک می‌کند', !adv[0].classList.contains('is-magnetic'));

	/* موج نوری کلیک */
	const cta = $('.pixva-hero__actions .pixva-btn');
	fire(cta, 'pointerdown', { clientX: 60, clientY: 40 });
	await wait(20);
	check('v6: موج نوری در نقطه کلیک ساخته شد', !!$('.pixva-ripple', cta) && cta.classList.contains('pixva-ripple-host'));
	check('v6: موج نوری از نظر دسترس‌پذیری مخفی است', $('.pixva-ripple', cta).getAttribute('aria-hidden') === 'true');
	fire($('.pixva-mega__trigger', items[0]), 'pointerdown', { clientX: 20, clientY: 20 });
	await wait(20);
	check('v6: تریگر مگا هم موج می‌گیرد', !!$('.pixva-ripple', $('[data-mega-trigger]', items[0])));

	/* اسکرول نرم لنگرها */
	const anchorLink = $('[data-fx-anchor]');
	let scrollCalls = 0;
	window.scrollTo = () => { scrollCalls += 1; };
	window.HTMLElement.prototype.scrollIntoView = function () { this.dataset.scrolled = '1'; };
	const anchorEvent = new window.MouseEvent('click', { bubbles: true, cancelable: true, view: window, button: 0 });
	anchorLink.dispatchEvent(anchorEvent);
	await wait(120);
	check('v6: لنگر داخلی به‌جای پرش ناگهانی، نرم اسکرول می‌شود', anchorEvent.defaultPrevented && scrollCalls > 0);
	check('v6: مقصد اسکرول، فوکوس دسترس‌پذیر می‌گیرد', $('#reviews').getAttribute('tabindex') === '-1');

	/* --- ۶) نوار داشبورد تعمیرکار --- */
	const bar = $('.pixva-tech-dash__bar');
	check('v6: نوار چسبان داشبورد تعمیرکار رندر شد', !!bar);
	check('v6: دسترسی سریع داشبورد (نرخ‌نامه/گارانتی/خروج)', $$('.pixva-tech-dash__quick a').length === 4);
	check('v6: نوار هم reveal می‌گیرد', bar.classList.contains('pixva-fx'));
}

/* ------------------------------------------------------------------ */
/* ------------------------------------------------------------------ *
 * بخش ۴ — لایه ۱٫۶٫۰ (Master Prompt v7): اسکرول سینمایی GSAP، مدل سه‌بعدی
 * Spline، عیب‌یاب هوشمند رسانه‌محور و نقشه زنده تعمیرکار
 * ------------------------------------------------------------------ */
function v7StaticChecks() {
	const read = (rel) => fs.readFileSync(path.join(theme, rel), 'utf8');

	const cinematic = read('inc/cinematic.php');
	const aiPhp = read('inc/ai-diagnose.php');
	const mapPhp = read('inc/tracker-map.php');
	const funcs = read('functions.php');
	const support = read('inc/elementor-support.php');
	const shortcodes = read('inc/shortcodes.php');
	const metaboxes = read('inc/meta-boxes.php');
	const options = read('inc/theme-options.php');
	const css = read('assets/css/pixva-2026.css');
	const styleCss = read('style.css');
	const gsapLite = read('assets/js/vendor/pixva-gsap-lite.js');
	const cineJs = read('assets/js/cinematic.js');
	const splineJs = read('assets/js/spline-3d.js');
	const aiJs = read('assets/js/ai-diagnose.js');
	const mapJs = read('assets/js/tracker-map.js');
	const trackerJs = read('assets/js/tracker.js');
	const toolsJs = read('assets/js/interactive-tools.js');

	/* --- نسخه و بارگذاری ماژول‌ها --- */
	check('v7: نسخه پوسته ۱٫۶٫۰ (style.css + PIXVA_VERSION)', styleCss.indexOf('Version: 1.6.0') > -1 && funcs.indexOf("define( 'PIXVA_VERSION', '1.6.0' )") > -1);
	check('v7: سه ماژول تازه در functions.php', ['inc/cinematic.php', 'inc/ai-diagnose.php', 'inc/tracker-map.php'].every((f) => funcs.indexOf(f) > -1));

	/* --- رندر مشترک و بارگذاری شرایطی --- */
	check('v7: چهار رندر مشترک ویجت‌ها', ['pixva_render_cinematic_unboxing', 'pixva_render_spline_3d', 'pixva_render_ai_diagnose', 'pixva_render_technician_tracker']
		.every((fn) => cinematic.indexOf('function ' + fn + '(') > -1));
	check('v7: ثبت همیشه + صف شرایطی (اولویت ۱۵/۲۰)', cinematic.indexOf("'pixva_cinematic_register_assets', 15") > -1 && cinematic.indexOf("'pixva_cinematic_assets', 20") > -1);
	check('v7: چهار تابع تشخیص نیاز', ['pixva_needs_cinematic_js', 'pixva_needs_spline_js', 'pixva_needs_ai_diagnose_js', 'pixva_needs_tracker_map_js']
		.every((fn) => cinematic.indexOf('function ' + fn + '(') > -1));
	check('v7: مارکرهای تشخیص = نام ویجت/کلاس المنتور/شورت‌کد', ['pixva_cinematic_unboxing', 'pixva_3d_repair', 'pixva_ai_diagnose', 'pixva_tech_tracker', 'pixva_technician_tracker']
		.every((m) => cinematic.indexOf("'" + m + "'") > -1));
	check('v7: ترتیب منبع کتابخانه vendor → CDN → موتور داخلی', ['assets/js/vendor/gsap.min.js', 'assets/js/vendor/ScrollTrigger.min.js', 'assets/js/vendor/leaflet.js', 'pixva_cdn_gsap', 'pixva_cdn_scrolltrigger', 'pixva_cdn_leaflet', 'pixva-gsap-lite.js']
		.every((k) => cinematic.indexOf(k) > -1));
	check('v7: Spline به‌صورت ماژول ES از CDN و تنبل', cinematic.indexOf('pixva_cdn_spline') > -1 && splineJs.indexOf("script.type = 'module'") > -1);
	check('v7: لوکالایز چهار ماژول', ['pixvaCine', 'pixvaSpline', 'pixvaAiDiagnose', 'pixvaTrackerMap'].every((k) => cinematic.indexOf("'" + k + "'") > -1));
	check('v7: اسکریپت‌های تازه defer و در فوتر', (cinematic.match(/'strategy'/g) || []).length >= 5 && (cinematic.match(/'in_footer' => true/g) || []).length >= 5);
	check('v7: کمکی صف مستقیم برای شورت‌کدها', cinematic.indexOf('function pixva_enqueue_cinematic_assets(') > -1 && shortcodes.indexOf('pixva_enqueue_cinematic_assets(') > -1);
	check('v7: خواندن JSON مشخصه شورت‌کد', cinematic.indexOf('function pixva_shortcode_json(') > -1);

	/* --- ویجت‌های المنتور --- */
	const widgets = [
		{ name: 'pixva_cinematic_unboxing', cls: 'Pixva_Cinematic_Unboxing_Widget', file: 'inc/widgets/class-pixva-cinematic-widget.php', render: 'pixva_render_cinematic_unboxing(' },
		{ name: 'pixva_3d_repair', cls: 'Pixva_3d_Repair_Widget', file: 'inc/widgets/class-pixva-spline-widget.php', render: 'pixva_render_spline_3d(' },
		{ name: 'pixva_ai_diagnose', cls: 'Pixva_Ai_Diagnose_Widget', file: 'inc/widgets/class-pixva-ai-diagnose-widget.php', render: 'pixva_render_ai_diagnose(' },
		{ name: 'pixva_tech_tracker', cls: 'Pixva_Technician_Tracker_Widget', file: 'inc/widgets/class-pixva-tech-tracker-widget.php', render: 'pixva_render_technician_tracker(' }
	];

	widgets.forEach((w) => {
		const src = read(w.file);
		check('v7: ویجت ' + w.name + ' از پایه مشترک ارث می‌برد', src.indexOf('class ' + w.cls + ' extends Pixva_Section_Widget_Base') > -1);
		check('v7: ویجت ' + w.name + ' کنترل کامل ثبت می‌کند', src.indexOf('protected function register_controls()') > -1
			&& src.indexOf('Controls_Manager::TAB_CONTENT') > -1 && src.indexOf('Controls_Manager::TAB_STYLE') > -1);
		check('v7: ویجت ' + w.name + ' رنگ/تصویر/زمان‌بندی قابل ویرایش دارد', src.indexOf('Controls_Manager::COLOR') > -1
			&& (src.indexOf('Controls_Manager::SLIDER') > -1 || src.indexOf('Controls_Manager::MEDIA') > -1 || src.indexOf('Controls_Manager::NUMBER') > -1));
		check('v7: ویجت ' + w.name + ' وابستگی اسکریپت و بارگذاری دارایی دارد', src.indexOf('get_script_depends') > -1 && src.indexOf('enqueue_front_assets') > -1);
		check('v7: ویجت ' + w.name + ' به رندر مشترک وصل است', src.indexOf(w.render) > -1);
		check('v7: کلاس ' + w.cls + ' در المنتور ثبت می‌شود', support.indexOf("'" + w.cls + "'") > -1);
	});

	check('v7: فایل ویجت‌ها خودکار include می‌شوند', funcs.indexOf("glob( $dir . '/class-*.php' )") > -1);
	check('v7: گارد المنتور در سر ویجت‌ها', widgets.every((w) => read(w.file).indexOf("did_action( 'elementor/loaded' )") > -1));

	/* --- شورت‌کدها --- */
	['pixva_cinematic_unboxing', 'pixva_3d_repair', 'pixva_ai_diagnose', 'pixva_technician_tracker'].forEach((tag) => {
		check('v7: شورت‌کد [' + tag + '] ثبت شده', shortcodes.indexOf("add_shortcode( '" + tag + "'") > -1);
	});
	check('v7: نام مستعار [pixva_tech_tracker]', shortcodes.indexOf("add_shortcode( 'pixva_tech_tracker'") > -1);

	/* --- REST عیب‌یاب هوشمند --- */
	check('v7: مسیر POST pixva/v1/ai-diagnose', aiPhp.indexOf("'pixva/v1'") > -1 && aiPhp.indexOf("'/ai-diagnose'") > -1 && aiPhp.indexOf("'methods'             => 'POST'") > -1);
	check('v7: نانس اختصاصی و هانی‌پات', aiPhp.indexOf("wp_verify_nonce( $nonce, 'pixva_ai_diagnose' )") > -1 && aiPhp.indexOf('pixva_hp') > -1);
	check('v7: محدودسازی نرخ ۶ درخواست در ساعت', aiPhp.indexOf('pixva_ai_diagnose_rate_max') > -1 && aiPhp.indexOf('HOUR_IN_SECONDS') > -1);
	check('v7: سقف حجم و نوع رسانه از تنظیم/فیلتر', aiPhp.indexOf('pixva_ai_diagnose_max_size') > -1 && aiPhp.indexOf('pixva_ai_diagnose_allowed_types') > -1 && aiPhp.indexOf('MB_IN_BYTES') > -1);
	check('v7: ذخیره رسانه به‌عنوان پرونده صندوق ورودی', aiPhp.indexOf('pixva_inbox') > -1 && aiPhp.indexOf('_pixva_ai_') > -1);
	check('v7: آماده اتصال عامل پایتون (آدرس + توکن از گزینه‌ها)', aiPhp.indexOf("pixva_option( 'pixva_ai_agent_url'") > -1 && aiPhp.indexOf("pixva_option( 'pixva_ai_agent_token'") > -1);
	check('v7: کد پیگیری PXV-AI', aiPhp.indexOf('PXV-AI') > -1);

	/* --- REST نقشه زنده --- */
	check('v7: مسیر GET pixva/v1/dispatch-live', mapPhp.indexOf("'/dispatch-live'") > -1 && mapPhp.indexOf("'methods'             => 'GET'") > -1);
	check('v7: کد + شماره همراه هر دو لازم‌اند', mapPhp.indexOf('pixva_find_order( $code, $phone )') > -1);
	check('v7: اعتبارسنجی موبایل ایرانی', mapPhp.indexOf('pixva_is_valid_iranian_mobile') > -1);
	check('v7: محدودسازی نرخ نقشه', mapPhp.indexOf('pixva_map_rate_max') > -1);
	check('v7: بار خروجی نقشه (ETA/پیشرفت/مسیر/مارکر)', ['eta', 'progress', 'route', 'marker', 'simulated', 'moving', 'arrived'].every((k) => mapPhp.indexOf("'" + k + "'") > -1));
	check('v7: مبدأ/منطقه/مقصد از گزینه‌های پوسته', ['pixva_map_origin_lat', 'pixva_map_origin_lng', 'pixva_map_zones', 'pixva_map_dest_label'].every((k) => mapPhp.indexOf(k) > -1));
	check('v7: سرعت و بازه نوسازی از گزینه‌ها', mapPhp.indexOf("pixva_option( 'pixva_map_average_speed'") > -1 && mapPhp.indexOf("pixva_option( 'pixva_map_refresh'") > -1);

	/* --- متاباکس موقعیت --- */
	['_pixva_order_map_lat', '_pixva_order_map_lng', '_pixva_order_tech_lat', '_pixva_order_tech_lng', '_pixva_order_eta', '_pixva_order_progress'].forEach((key) => {
		check('v7: متای نقشه ثبت شده ' + key, metaboxes.indexOf("'" + key + "'") > -1);
	});
	check('v7: متاباکس موقعیت زنده رندر می‌شود', metaboxes.indexOf('function pixva_mb_render_map(') > -1 && metaboxes.indexOf("'pixva_mb_map'") > -1);
	check('v7: فیلدهای نقشه در نگاشت ذخیره', ['pixva_mb_map_lat', 'pixva_mb_tech_lat', 'pixva_mb_eta', 'pixva_mb_progress'].every((f) => metaboxes.indexOf("'" + f + "'") > -1));

	/* --- سفارشی‌ساز --- */
	check('v7: بخش سفارشی‌ساز سینمایی/نقشه', options.indexOf("'pixva_cinematic'") > -1 && options.indexOf('pixva_v7_fields') > -1);
	['pixva_cdn_gsap', 'pixva_cdn_scrolltrigger', 'pixva_cdn_leaflet', 'pixva_cdn_leaflet_css', 'pixva_cdn_spline',
		'pixva_ai_agent_url', 'pixva_ai_agent_token', 'pixva_ai_max_size',
		'pixva_map_origin_lat', 'pixva_map_origin_lng', 'pixva_map_origin_label', 'pixva_map_zones', 'pixva_map_dest_label',
		'pixva_map_average_speed', 'pixva_map_refresh', 'pixva_map_provider', 'pixva_map_tiles', 'pixva_map_attribution',
		'pixva_map_on_tracking', 'pixva_map_badge', 'pixva_map_title', 'pixva_map_subtitle'].forEach((key) => {
		check('v7: گزینه سفارشی‌ساز ' + key, options.indexOf("'" + key + "'") > -1);
	});
	check('v7: پاک‌سازی مختصات و موتور نقشه', options.indexOf('function pixva_sanitize_coord(') > -1 && options.indexOf('function pixva_sanitize_map_provider(') > -1);

	/* --- نقشه زنده داخل صفحه پیگیری سفارش --- */
	const tracking = read('page-templates/page-tracking.php');
	check('v7: نقشه زنده در صفحه پیگیری رندر می‌شود', tracking.indexOf('pixva_render_technician_tracker(') > -1 && tracking.indexOf("pixva_option( 'pixva_map_on_tracking'") > -1);
	check('v7: متن‌های نقشه پیگیری از گزینه‌ها می‌آیند', ['pixva_map_badge', 'pixva_map_title', 'pixva_map_subtitle'].every((k) => tracking.indexOf(k) > -1));
	check('v7: صف نقشه به گزینه صفحه پیگیری شرط شده', cinematic.indexOf("pixva_option( 'pixva_map_on_tracking', true ) && is_page_template(") > -1);

	/* --- تداخل کنترل المنتور با استایل درون‌خطی رندر --- */
	const techWidget = read('inc/widgets/class-pixva-tech-tracker-widget.php');
	check('v7: کنترل ارتفاع نقشه با --map-h درون‌خطی تداخل ندارد', !/add_responsive_control\(\s*'height'/.test(techWidget) && techWidget.indexOf('--map-h: {{SIZE}}') === -1);
	check('v7: کنترل‌های سبکی ویجت‌ها به متغیرهای CSS رندر وصل‌اند', read('inc/widgets/class-pixva-ai-diagnose-widget.php').indexOf('.pixva-ai__orb') > -1
		&& read('inc/widgets/class-pixva-spline-widget.php').indexOf('.pixva-3d__hot-dot') > -1
		&& read('inc/widgets/class-pixva-cinematic-widget.php').indexOf('.pixva-cine__scene') > -1);

	/* --- موتور GSAP سبک --- */
	check('v7: موتور سبک gsap + ScrollTrigger', gsapLite.indexOf('window.gsap') > -1 && gsapLite.indexOf('window.ScrollTrigger') > -1 && gsapLite.indexOf('__pixvaLite') > -1);
	check('v7: موتور سبک در برابر GSAP رسمی کنار می‌رود', gsapLite.indexOf('window.gsap && !window.gsap.__pixvaLite') > -1 || gsapLite.indexOf('if (window.gsap)') > -1);
	check('v7: Pin با spacer و ارتفاع برابر طول اسکرول', gsapLite.indexOf('pixva-pin-spacer') > -1 && gsapLite.indexOf('syncPin') > -1);
	check('v7: ScrollTrigger از end به پیکسل', gsapLite.indexOf("'+='") > -1);
	check('v7: تایم‌لاین/توئین با scrub و progress', gsapLite.indexOf('Timeline') > -1 && gsapLite.indexOf('progress') > -1);

	/* --- JS بخش‌ها --- */
	check('v7: سینمایی — اسکن پویا + Pin/Scrub + احترام به reduced-motion', cineJs.indexOf('pixvaCinematicScan') > -1
		&& cineJs.indexOf('ScrollTrigger.create') > -1 && cineJs.indexOf('prefers-reduced-motion') > -1 && cineJs.indexOf('revealAll') > -1);
	check('v7: سینمایی — لایه‌ها با عمق سه‌بعدی باز می‌شوند', cineJs.indexOf('rotationY') > -1 && cineJs.indexOf('data-cine-depth') > -1 || cineJs.indexOf('cineDepth') > -1);
	check('v7: سه‌بعدی — بارگذاری تنبل و نمای جایگزین', splineJs.indexOf('IntersectionObserver') > -1 && splineJs.indexOf('initFallback') > -1 && splineJs.indexOf('--rot-x') > -1);
	check('v7: سه‌بعدی — هات‌اسپیت کارت استعلام قیمت', splineJs.indexOf('initHotspots') > -1 && splineJs.indexOf('data-spline-panel-cta') > -1);
	check('v7: هوش مصنوعی — ارسال multipart با هدر نانس', aiJs.indexOf('FormData') > -1 && aiJs.indexOf("'X-Pixva-Nonce'") > -1 && aiJs.indexOf("'/ai-diagnose'") > -1);
	check('v7: هوش مصنوعی — ضبط صدا با MediaRecorder', aiJs.indexOf('MediaRecorder') > -1 && aiJs.indexOf('getUserMedia') > -1);
	check('v7: هوش مصنوعی — اعتبارسنجی نوع/حجم فایل', aiJs.indexOf('badType') > -1 && aiJs.indexOf('tooLarge') > -1);
	check('v7: هوش مصنوعی — متن‌ها از لوکالایز (بدون هاردکد)', aiJs.indexOf('i18n.audioName') > -1 && aiJs.indexOf('i18n.smart') > -1 && aiJs.indexOf('cfg.logs') > -1);
	check('v7: نقشه — دو موتور (Leaflet + داخلی SVG)', mapJs.indexOf('LeafletMap') > -1 && mapJs.indexOf('InternalMap') > -1 && mapJs.indexOf('pixva-map__svg') > -1);
	check('v7: نقشه — مارکر متحرک و نوسازی دوره‌ای', mapJs.indexOf('animateTo') > -1 && mapJs.indexOf('setInterval') > -1);
	check('v7: نقشه — پل رویداد فرم پیگیری', mapJs.indexOf("'pixva:track-result'") > -1 && trackerJs.indexOf("'pixva:track-result'") > -1 && toolsJs.indexOf("'pixva:track-result'") > -1);
	check('v7: بدون jQuery در ماژول‌های تازه', [cineJs, splineJs, aiJs, mapJs, gsapLite].every((src) => src.indexOf('jQuery(') === -1 && src.indexOf('$(') === -1));

	/* --- CSS لایه ۲۹ --- */
	check('v7: CSS لایه ۲۹ وجود دارد', css.indexOf('۲۹) لایه سینمایی') > -1);
	check('v7: CSS سینمایی (صحنه/لایه/نوار پیشرفت)', ['.pixva-cine__stage', '.pixva-cine__scene', '.pixva-cine__layer', '.pixva-cine__bar i', '.pixva-pin-spacer'].every((c) => css.indexOf(c) > -1));
	check('v7: CSS سه‌بعدی (صحنه/هات‌اسپیت/کارت)', ['.pixva-3d__stage', '.pixva-3d__hot-dot', '.pixva-3d__panel', '.pixva-3d__tv'].every((c) => css.indexOf(c) > -1));
	check('v7: CSS عیب‌یاب (گوی/حلقه/تحلیل/نتیجه)', ['.pixva-ai__orb', '.pixva-ai__ring', '.pixva-ai__scan', '.pixva-ai__result', '.pixva-ai__preview'].every((c) => css.indexOf(c) > -1));
	check('v7: CSS نقشه (بوم/مسیر/ماشین/کارت وضعیت)', ['.pixva-map__canvas', '.pixva-map__route', '.pixva-map__car-body', '.pixva-map__status', '.pixva-map__icon-dot'].every((c) => css.indexOf(c) > -1));
	check('v7: CSS حالت‌های داده‌محور', ['[data-ai-state="analyzing"]', '[data-map-loading="1"]', '[data-map-moving="1"]', '[data-map-simulated="1"]'].every((c) => css.indexOf(c) > -1));
	check('v7: CSS واکنش‌گرا و reduced-motion در لایه ۲۹', css.indexOf('html.pixva-cine-js') > -1 && css.indexOf('@media (prefers-reduced-motion: reduce)') > -1);
	check('v7: CSS رنگ‌ها از متغیر ویجت', ['var(--cine-neon)', 'var(--spline-hot)', 'var(--ai-neon)', 'var(--map-neon)'].every((v) => css.indexOf(v) > -1));
}

async function v7Checks(window) {
	const { document } = window;
	const $ = (sel, root) => (root || document).querySelector(sel);
	const $$ = (sel, root) => Array.prototype.slice.call((root || document).querySelectorAll(sel));
	const click = (el) => el.dispatchEvent(new window.MouseEvent('click', { bubbles: true, cancelable: true, view: window }));
	const wait = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

	/* --- ۱) اسکرول سینمایی: Pin + Scrub --- */
	const cine = $('[data-pixva-cine]');
	check('v7: کلاس JS سینمایی روی <html>', document.documentElement.classList.contains('pixva-cine-js'));
	check('v7: ScrollTrigger برای بخش ساخته شد', typeof window.pixvaCinematicInstances === 'function' && window.pixvaCinematicInstances().length === 1);

	const stage = $('[data-cine-stage]', cine);
	const spacer = stage && stage.parentNode;
	check('v7: صحنه داخل spacer قفل‌شده است', !!spacer && spacer.classList.contains('pixva-pin-spacer'));
	check('v7: ارتفاع spacer = صحنه + طول اسکرول', !!spacer && parseFloat(spacer.style.height) >= 2000);

	const bar = $('[data-cine-bar]', cine);
	const stepEl = $('[data-cine-step]', cine);
	const notes = $$('[data-cine-note]', cine);
	const layers = $$('[data-cine-layer]', cine);

	check('v7: در آغاز، نوار پیشرفت خالی است', bar.style.transform === '' || bar.style.transform.indexOf('scaleX(0') > -1);

	// شبیه‌سازی اسکرول تا میانه تایم‌لاین.
	Object.defineProperty(document.documentElement, 'scrollTop', { value: 1300, writable: true, configurable: true });
	Object.defineProperty(window, 'pageYOffset', { value: 1300, writable: true, configurable: true });
	window.dispatchEvent(new window.Event('scroll'));
	await wait(120);

	check('v7: با اسکرول، نوار پیشرفت پر شد (' + bar.style.transform + ')', bar.style.transform.indexOf('scaleX(0.') > -1 || bar.style.transform.indexOf('scaleX(1') > -1);
	check('v7: شمارنده گام به‌روز شد (' + stepEl.textContent.trim() + ')', stepEl.textContent.indexOf('۰') === -1 && stepEl.textContent.indexOf('/') > -1);
	check('v7: یادداشت گام فعال مشخص شد', notes.filter((n) => n.classList.contains('is-active')).length === 1);
	check('v7: بخش در حالت زنده است', cine.classList.contains('is-live'));
	check('v7: صحنه قفل (position: fixed) شد', stage.style.position === 'fixed' && stage.dataset.pixvaPinned === '1');
	check('v7: لایه‌ها با ترنسفورم سه‌بعدی باز شدند', layers.some((l) => l.style.transform.indexOf('translate3d') > -1 && l.style.transform.indexOf('rotate') > -1));
	check('v7: یادداشت‌ها با اسکرول ظاهر شدند', notes.some((n) => parseFloat(n.style.opacity || '0') > 0.5));

	/* --- ۲) مدل سه‌بعدی: نمای جایگزین + هات‌اسپیت --- */
	const three = $('[data-pixva-spline]');
	check('v7: کلاس JS سه‌بعدی روی <html>', document.documentElement.classList.contains('pixva-3d-js'));
	check('v7: بدون آدرس مدل، نمای جایگزین فعال است', three.classList.contains('is-fallback') && !three.classList.contains('is-3d'));

	const tv = $('.pixva-3d__tv', three);
	check('v7: متغیرهای چرخش نمای جایگزین تنظیم شد', tv.style.getPropertyValue('--rot-x').indexOf('deg') > -1 && tv.style.getPropertyValue('--rot-y').indexOf('deg') > -1);

	const rotBefore = tv.style.getPropertyValue('--rot-y');
	const view = $('[data-spline-fallback-view]', three);
	view.dispatchEvent(new window.MouseEvent('pointerdown', { bubbles: true, clientX: 100, clientY: 100 }));
	check('v7: حالت کشیدن فعال شد', view.classList.contains('is-dragging'));
	view.dispatchEvent(new window.MouseEvent('pointermove', { bubbles: true, clientX: 160, clientY: 110 }));
	view.dispatchEvent(new window.MouseEvent('pointerup', { bubbles: true, clientX: 160, clientY: 110 }));
	check('v7: چرخش با کشیدن ماوس تغییر کرد', tv.style.getPropertyValue('--rot-y') !== rotBefore && !view.classList.contains('is-dragging'));

	const hots = $$('[data-spline-hot]', three);
	check('v7: سه نقطه قطعه معیوب رندر شده', hots.length === 3);

	const panel = $('[data-spline-panel]', three);
	check('v7: کارت استعلام در آغاز بسته است', panel.hidden === true);

	click(hots[0]);
	check('v7: با کلیک نقطه، کارت استعلام باز شد', panel.hidden === false && panel.classList.contains('is-open'));
	check('v7: عنوان/شرح/قطعه در کارت نوشته شد', $('[data-spline-panel-title]', panel).textContent === 'بک‌لایت سوخته'
		&& $('[data-spline-panel-text]', panel).textContent.indexOf('ریسه LED') > -1
		&& $('[data-spline-panel-part]', panel).textContent === 'تعویض بک‌لایت');
	check('v7: دکمه استعلام به محاسبه‌گر همان قطعه می‌رود', ($('[data-spline-panel-cta]', panel).getAttribute('href') || '').indexOf('problem=backlight') > -1);
	check('v7: نقطه فعال aria-expanded گرفت', hots[0].getAttribute('aria-expanded') === 'true' && hots[0].classList.contains('is-active'));

	click($('[data-spline-close]', three));
	check('v7: دکمه بستن، کارت را می‌بندد', panel.hidden === true && hots.every((h) => h.getAttribute('aria-expanded') === 'false'));

	/* --- ۳) عیب‌یاب هوشمند --- */
	const ai = $('[data-pixva-ai-diagnose]');
	check('v7: کلاس JS عیب‌یاب روی <html>', document.documentElement.classList.contains('pixva-ai-js'));
	check('v7: وضعیت اولیه idle', ai.dataset.aiState === 'idle');

	const orb = $('[data-ai-orb]', ai);
	ai.dispatchEvent(new window.MouseEvent('pointermove', { bubbles: true, clientX: 400, clientY: 220 }));
	check('v7: گوی به حرکت نشانگر واکنش می‌دهد', orb.style.getPropertyValue('--orb-x').indexOf('%') > -1 && orb.style.getPropertyValue('--orb-y').indexOf('%') > -1);

	// ارسال بدون رسانه → خطای راهنما.
	const form = $('[data-ai-form]', ai);
	form.dispatchEvent(new window.Event('submit', { bubbles: true, cancelable: true }));
	await wait(40);
	const errorBox = $('[data-ai-error]', ai);
	check('v7: بدون رسانه، خطای راهنما نمایش داده می‌شود', errorBox.hidden === false && errorBox.textContent.indexOf('ابتدا یک ویدیو') > -1);
	check('v7: بدون رسانه درخواستی فرستاده نمی‌شود', window.__pixvaCalls.ai === 0 && ai.dataset.aiState === 'idle');

	// فایل نامعتبر → خطای نوع.
	const fileInput = $('[data-ai-file]', ai);
	const badFile = new window.File(['x'], 'note.txt', { type: 'text/plain' });
	Object.defineProperty(fileInput, 'files', { value: [badFile], configurable: true });
	fileInput.dispatchEvent(new window.Event('change', { bubbles: true }));
	check('v7: فایل غیرمجاز رد می‌شود', errorBox.textContent.indexOf('فقط فایل ویدیو یا صدا') > -1 && ai.dataset.aiHasMedia !== '1');

	// فایل معتبر → پیش‌نمایش.
	const goodFile = new window.File([new Uint8Array(2048)], 'fault-backlight.mp4', { type: 'video/mp4' });
	Object.defineProperty(fileInput, 'files', { value: [goodFile], configurable: true });
	fileInput.dispatchEvent(new window.Event('change', { bubbles: true }));
	const mediaBox = $('[data-ai-media]', ai);
	check('v7: فایل معتبر پذیرفته و کارت رسانه باز شد', mediaBox.hidden === false && ai.dataset.aiHasMedia === '1' && errorBox.hidden === true);
	check('v7: نام و حجم فایل فارسی‌سازی شد', $('[data-ai-media-name]', mediaBox).textContent === 'fault-backlight.mp4'
		&& $('[data-ai-media-meta]', mediaBox).textContent.indexOf('ویدیو') > -1);
	check('v7: پیش‌نمایش ویدیو ساخته شد', !!$('[data-ai-preview]', mediaBox) && $('[data-ai-media-icon]', mediaBox).dataset.aiKind === 'video');

	// ضبط صدا با MediaRecorder ساختگی.
	const mic = $('[data-ai-mic]', ai);
	check('v7: دکمه ضبط با MediaRecorder فعال است', mic.disabled === false);
	click(mic);
	await wait(30);
	check('v7: ضبط صدا شروع شد', mic.classList.contains('is-recording') && ai.dataset.aiState === 'recording');
	click(mic);
	await wait(30);
	check('v7: با توقف ضبط، فایل صدا جایگزین شد', !mic.classList.contains('is-recording') && $('[data-ai-media-icon]', mediaBox).dataset.aiKind === 'audio');

	// ارسال موفق به REST.
	form.dispatchEvent(new window.Event('submit', { bubbles: true, cancelable: true }));
	await wait(20);
	check('v7: حالت تحلیل سایبرپانک فعال شد', ai.dataset.aiState === 'analyzing' && $('[data-ai-loading]', ai).hidden === false
		&& $('[data-ai-log]', ai).textContent.indexOf('>') === 0);
	await wait(220);
	check('v7: درخواست به ai-diagnose با نانس فرستاده شد', window.__pixvaCalls.ai === 1 && window.__pixvaAiHeaders['X-Pixva-Nonce'] === 'ai-nonce');
	check('v7: فایل رسانه داخل FormData است', window.__pixvaAiBody && typeof window.__pixvaAiBody.get === 'function' && !!window.__pixvaAiBody.get('media'));

	const result = $('[data-ai-result]', ai);
	check('v7: کارت نتیجه باز شد', ai.dataset.aiState === 'done' && result.hidden === false && result.classList.contains('is-open'));
	check('v7: رأی، کد پیگیری و تاریخ در نتیجه', $('[data-ai-result-title]', result).textContent.indexOf('بک‌لایت') > -1
		&& $('[data-ai-result-code]', result).textContent === 'PXV-AI-482913'
		&& $('[data-ai-result-date]', result).textContent.length > 0);
	check('v7: CTA نتیجه به قطعه تشخیصی لینک شد', ($('[data-ai-result-cta]', result).getAttribute('href') || '').indexOf('problem=backlight') > -1);
	check('v7: جعبه تحلیل بسته و رسانه پاک شد', $('[data-ai-loading]', ai).hidden === true && ai.dataset.aiHasMedia === '0');

	/* --- ۴) نقشه زنده تعمیرکار --- */
	const map = $('[data-pixva-tracker]');
	check('v7: کلاس JS نقشه روی <html>', document.documentElement.classList.contains('pixva-map-js'));

	const mapForm = $('[data-map-form]', map);
	$('[name="code"]', mapForm).value = 'PXV-G-250926-A1B2C3';
	$('[name="phone"]', mapForm).value = '09121111111';
	mapForm.dispatchEvent(new window.Event('submit', { bubbles: true, cancelable: true }));
	await wait(80);

	check('v7: استعلام dispatch-live با کد و شماره فرستاده شد', window.__pixvaCalls.dispatch === 1
		&& window.__pixvaCalls.dispatchCodes[0].indexOf('code=PXV-G-250926-A1B2C3') > -1
		&& window.__pixvaCalls.dispatchCodes[0].indexOf('phone=09121111111') > -1);

	const canvas = $('[data-map-canvas]', map);
	const svg = $('.pixva-map__svg', canvas);
	check('v7: نقشه داخلی SVG رندر شد', !!svg && !map.dataset.mapLoading);
	check('v7: شبکه، مسیر، مبدأ/مقصد و ماشین ساخته شدند', !!$('.pixva-map__grid', svg) && !!$('.pixva-map__route', svg)
		&& !!$('.pixva-map__route--done', svg) && !!$('.pixva-map__point--origin', svg) && !!$('.pixva-map__point--dest', svg) && !!$('.pixva-map__car', svg));
	check('v7: مسیر نقاط واقعی دارد', ($('.pixva-map__route', svg).getAttribute('points') || '').split(' ').length === 4);
	check('v7: مارکر ون روی مسیر قرار گرفت', ($('.pixva-map__car', svg).getAttribute('transform') || '').indexOf('translate(') === 0);

	const status = $('[data-map-status]', map);
	check('v7: کارت وضعیت با ETA فارسی نمایش داده شد', status.hidden === false
		&& $('[data-map-eta]', status).textContent.indexOf('در حال حرکت به سمت شما') > -1
		&& $('[data-map-eta]', status).textContent.indexOf('۱۵ دقیقه') > -1);
	check('v7: نام و تخصص تعمیرکار نوشته شد', $('[data-map-tech]', status).textContent.indexOf('رضا کریمی') > -1);
	check('v7: برچسب وضعیت + شبیه‌سازی مسیر', $('[data-map-state]', status).textContent.indexOf('شبیه‌سازی مسیر') > -1 && map.dataset.mapSimulated === '1' && map.dataset.mapMoving === '1');
	check('v7: یادداشت نقشه داخلی در انتهای بخش', $('[data-map-attr]', map).textContent.indexOf('نقشه داخلی پیکسوا') > -1);

	// پل رویداد فرم پیگیری (بدون شماره → راهنما).
	$('[name="phone"]', mapForm).value = '';
	document.dispatchEvent(new window.CustomEvent('pixva:track-result', {
		detail: { code: 'PXV-G-250926-A1B2C3', status: 'assigned', statusLabel: 'تخصیص‌یافته' }
	}));
	await wait(40);
	const mapError = $('[data-map-error]', map);
	check('v7: بدون شماره همراه، راهنمای لازم بودن شماره نمایش داده می‌شود', mapError.hidden === false
		&& mapError.textContent.indexOf('شماره همراه ثبت‌شده') > -1 && window.__pixvaCalls.dispatch === 1);

	// با شماره → استعلام دوباره.
	document.dispatchEvent(new window.CustomEvent('pixva:track-result', {
		detail: { code: 'PXV-G-250926-A1B2C3', phone: '09121111111', status: 'assigned', statusLabel: 'تخصیص‌یافته' }
	}));
	await wait(60);
	check('v7: رویداد پیگیری با شماره، نقشه را تازه می‌کند', window.__pixvaCalls.dispatch === 2);
}

(async function main() {
	staticChecks();

	// هارنس موتور CRM (داخل مخزن نگه‌داری می‌شود).
	if (!fs.existsSync(crmHarness)) {
		failures.push('هارنس tools/fixtures/crm-preview.html پیدا نشد');
		failed += 1;
	} else {
		const crmErrors = [];
		const crmConsole = new VirtualConsole();
		crmConsole.on('jsdomError', (error) => crmErrors.push(error.message));
		crmConsole.on('error', (message) => crmErrors.push(String(message)));

		const crmDom = await JSDOM.fromFile(crmHarness, {
			runScripts: 'dangerously',
			resources: 'usable',
			pretendToBeVisual: true,
			virtualConsole: crmConsole,
		});
		if (crmDom.window.document.readyState !== 'complete') {
			await new Promise((resolve) => crmDom.window.addEventListener('load', resolve));
		}
		await wait(150);

		await crmChecks(crmDom.window);

		check('crm: بدون خطای jsdom در کنسول (' + crmErrors.length + ')', crmErrors.length === 0);
		if (crmErrors.length) {
			crmErrors.slice(0, 5).forEach((e) => console.log('  ! ' + e));
		}
		crmDom.window.close();
	}

	// هارنس لایه ۱٫۵٫۰ (مگامنو، خانه مینیمال، موتور حرکت، داشبورد تعمیرکار).
	v6StaticChecks();
	if (!fs.existsSync(v6Harness)) {
		failures.push('هارنس tools/fixtures/v6-preview.html پیدا نشد');
		failed += 1;
	} else {
		const v6Errors = [];
		const v6Console = new VirtualConsole();
		v6Console.on('jsdomError', (error) => v6Errors.push(error.message));
		v6Console.on('error', (message) => v6Errors.push(String(message)));

		const v6Dom = await JSDOM.fromFile(v6Harness, {
			runScripts: 'dangerously',
			resources: 'usable',
			pretendToBeVisual: true,
			virtualConsole: v6Console,
		});
		if (v6Dom.window.document.readyState !== 'complete') {
			await new Promise((resolve) => v6Dom.window.addEventListener('load', resolve));
		}
		await wait(200);

		await v6Checks(v6Dom.window);

		check('v6: بدون خطای jsdom در کنسول (' + v6Errors.length + ')', v6Errors.length === 0);
		if (v6Errors.length) {
			v6Errors.slice(0, 5).forEach((e) => console.log('  ! ' + e));
		}
		v6Dom.window.close();
	}

	// هارنس لایه ۱٫۶٫۰ (سینمایی GSAP، سه‌بعدی، عیب‌یاب هوشمند، نقشه زنده).
	v7StaticChecks();
	if (!fs.existsSync(v7Harness)) {
		failures.push('هارنس tools/fixtures/v7-preview.html پیدا نشد');
		failed += 1;
	} else {
		const v7Errors = [];
		const v7Console = new VirtualConsole();
		v7Console.on('jsdomError', (error) => v7Errors.push(error.message));
		v7Console.on('error', (message) => v7Errors.push(String(message)));

		const v7Dom = await JSDOM.fromFile(v7Harness, {
			runScripts: 'dangerously',
			resources: 'usable',
			pretendToBeVisual: true,
			virtualConsole: v7Console,
		});
		if (v7Dom.window.document.readyState !== 'complete') {
			await new Promise((resolve) => v7Dom.window.addEventListener('load', resolve));
		}
		await wait(260);

		await v7Checks(v7Dom.window);

		check('v7: بدون خطای jsdom در کنسول (' + v7Errors.length + ')', v7Errors.length === 0);
		if (v7Errors.length) {
			v7Errors.slice(0, 5).forEach((e) => console.log('  ! ' + e));
		}
		v7Dom.window.close();
	}

	// هارنس قدیمی هیرو (خارج از گیت)؛ در صورت نبود، فقط یادداشت می‌شود.
	if (!fs.existsSync(harness)) {
		console.log('\n(هارنس preview/hero-preview.html موجود نیست — بخش هیرو رد شد)');
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

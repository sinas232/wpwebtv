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
	check('style.css: نسخه ۱٫۴٫۰', /Version:\s*1\.4\.0/.test(style));
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

/* ------------------------------------------------------------------ */
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

#!/usr/bin/env node
/*!
 *_PIXVA QA SUITE — نسخه ۴٫۰٫۰ (Master Prompt v14 / Bento & SaaS)
 *
 * بازطراحی کامل سوئیت طبق مانده v14: همه assertionهای لایه‌های قدیمی
 * (px-/pixva-hero/دو ستونی) حذف شده‌اند و سوئیت جدید علیه معماری بنتو
 * سنجیده می‌شود: کلاس‌های bx-*، شناسه‌های جدید، موتور رزرو تب‌دار،
 * payloadهای تازه REST و توکن‌های ابسیدین/ایندیگو/زمرد.
 *
 * اجرا: NODE_PATH=/home/user/node_modules node tools/domtest.js
 */
'use strict';

const fs = require('fs');
const path = require('path');
const { JSDOM, VirtualConsole } = require('jsdom');

const ROOT = path.resolve(__dirname, '..');
const THEME = path.join(ROOT, 'pixva');
const FIXTURE = path.join(__dirname, 'fixtures', 'v4-preview.html');

let pass = 0;
let fail = 0;
const failures = [];

function check(name, cond) {
	if (cond) {
		pass += 1;
	} else {
		fail += 1;
		failures.push(name);
		console.log('  ✗ ' + name);
	}
}

function read(rel) {
	return fs.readFileSync(path.join(THEME, rel), 'utf8');
}

function wait(ms) {
	return new Promise((resolve) => setTimeout(resolve, ms));
}

/* ==========================================================================
 * ممیزی‌های سراسری (مقیدات دائمی پروژه)
 * ======================================================================== */

function stripJsComments(src) {
	return src.replace(/\/\*[\s\S]*?\*\//g, '').replace(/(^|[^:'"\\])\/\/[^\n]*/g, '$1');
}

function jqueryAudit() {
	const dir = path.join(THEME, 'assets', 'js');
	const offenders = [];
	for (const file of fs.readdirSync(dir)) {
		if (!file.endsWith('.js')) continue;
		const src = stripJsComments(fs.readFileSync(path.join(dir, file), 'utf8'));
		if (/window\.jQuery|require\(\s*['"]jquery|\$\(\s*['"]|\$\(\s*document\s*\)|\$\(\s*function/i.test(src)) {
			offenders.push(file);
		}
	}
	check('ممیزی jQuery: همه اسکریپت‌ها Vanilla هستند (' + offenders.join(',') + ')', offenders.length === 0);
}

function classFilterAudit() {
	// هر فیلتر body_class باید آرایه‌امن باشد: is_array + push + return.
	const phpFiles = [];
	(function walk(dir) {
		for (const entry of fs.readdirSync(dir, { withFileTypes: true })) {
			const full = path.join(dir, entry.name);
			if (entry.isDirectory()) walk(full);
			else if (entry.name.endsWith('.php')) phpFiles.push(full);
		}
	}(THEME));

	const accepted = ['array_values', 'array_unique', 'array_merge', 'array_filter', 'array_map'];
	let audited = 0;
	const bad = [];

	for (const file of phpFiles) {
		const src = fs.readFileSync(file, 'utf8');
		const noComments = src.replace(/\/\*[\s\S]*?\*\//g, '').replace(/\/\/[^\n]*/g, '');
		const re = /add_filter\(\s*'body_class'\s*,\s*'([a-zA-Z0-9_]+)'/g;
		let m;
		while ((m = re.exec(noComments)) !== null) {
			const fn = m[1];
			const fnRe = new RegExp('function\\s+' + fn + '\\s*\\([^)]*\\)\\s*\\{');
			const fm = fnRe.exec(noComments);
			if (!fm) {
				bad.push(fn + ' (تعریف نشد)');
				continue;
			}
			let depth = 0;
			let end = -1;
			for (let i = fm.index + fm[0].length - 1; i < noComments.length; i += 1) {
				if (noComments[i] === '{') depth += 1;
				else if (noComments[i] === '}') {
					depth -= 1;
					if (depth === 0) { end = i; break; }
				}
			}
			const body = noComments.slice(fm.index, end);
			audited += 1;
			const usesConcat = /\$classes\s*\.=/.test(body);
			const safeArray = /is_array\(\s*\$classes\s*\)/.test(body);
			const pushes = /\$classes\[\]\s*=/.test(body);
			const returns = /return\s+\$classes|return\s+array_values/.test(body);
			const allowedCalls = (body.match(/array_[a-z]+\(/g) || []).every((c) => accepted.includes(c.replace('(', '')));
			if (usesConcat || !safeArray || !pushes || !returns || !allowedCalls) {
				bad.push(fn);
			}
		}
	}
	check('ممیزی body_class: ' + audited + ' فیلتر آرایه‌امن (' + bad.join(',') + ')', bad.length === 0 && audited >= 2);
}

function abspathGuard() {
	const templates = ['header.php', 'footer.php', 'front-page.php', 'single.php', 'page.php', 'archive.php', 'index.php', 'search.php', '404.php', 'searchform.php', 'functions.php', 'inc/bento-ui.php'];
	const missing = templates.filter((t) => read(t).indexOf("defined( 'ABSPATH' )") === -1);
	check('گیت ABSPATH در همه قالب‌های بازنویسی‌شده (' + missing.join(',') + ')', missing.length === 0);
}

function inlineStyleAudit() {
	const files = ['front-page.php', 'header.php', 'footer.php', 'single.php', 'page.php', 'archive.php', 'index.php', 'search.php', '404.php', 'searchform.php', 'inc/bento-ui.php', 'template-parts/card-post.php'];
	const dirty = [];
	for (const f of files) {
		const src = read(f);
		// style=" فقط در خروجی HTML ممنوع (نه در رشته‌های PHP مانند input_attrs).
		if (/style\s*=\s*"/.test(src.replace(/<\?php[\s\S]*?\?>/g, ''))) {
			dirty.push(f);
		}
	}
	check('صفر style= درون‌خطی در قالب‌ها و رندررهای بنتو (' + dirty.join(',') + ')', dirty.length === 0);
}

/* ==========================================================================
 * بررسی‌های ایستایی سامانه طراحی بنتو
 * ======================================================================== */

function v4CssChecks() {
	const css = read('assets/css/seo-cro.css');
	const bytes = Buffer.byteLength(css);

	check('v4 CSS: زیر ۵۰ کیلوبایت (' + bytes + 'B)', bytes < 50 * 1024);
	check('v4 CSS: ابسیدین #090D16', css.indexOf('#090D16') > -1);
	check('v4 CSS: بوم #F1F5F9', css.indexOf('#F1F5F9') > -1);
	check('v4 CSS: سطح سفید + مرز #E2E8F0', css.indexOf('--bx-surface: #FFFFFF') > -1 && css.indexOf('#E2E8F0') > -1);
	check('v4 CSS: ایندیگوی الکتریک #4F46E5 + #4338CA', css.indexOf('#4F46E5') > -1 && css.indexOf('#4338CA') > -1);
	check('v4 CSS: زمرد #10B981', css.indexOf('#10B981') > -1);
	check('v4 CSS: شیشه blur(16px)', css.indexOf('--bx-blur: blur(16px)') > -1 && (css.match(/backdrop-filter/g) || []).length >= 6);
	check('v4 CSS: سایه سه‌بعدی 0 20px 40px -15px rgba(0,0,0,0.07)', css.indexOf('--bx-shadow: 0 20px 40px -15px rgba(0, 0, 0, 0.07)') > -1);
	check('v4 CSS: منحنی حرکت cubic-bezier(0.16, 1, 0.3, 1)', css.indexOf('--bx-ease: cubic-bezier(0.16, 1, 0.3, 1)') > -1);
	check('v4 CSS: گرید بنتو repeat(12, 1fr) با gap 20px', /grid-template-columns: repeat\(12, 1fr\);\s*gap: 20px/.test(css.replace(/\n/g, ' ').replace(/\s+/g, ' ')) || (css.indexOf('repeat(12, 1fr)') > -1 && css.indexOf('gap: 20px') > -1));
	check('v4 CSS: ستون‌های بنتو bx-col-4/8/12', css.indexOf('.bx-col-4') > -1 && css.indexOf('.bx-col-8') > -1 && css.indexOf('.bx-col-12') > -1);
	check('v4 CSS: ناوبری قرصی max-width 1000px', css.indexOf('--bx-nav-max: 1000px') > -1 && css.indexOf('.bx-navpill') > -1);
	check('v4 CSS: دروئر تمام‌صفحه fixed inset 0', /\.bx-drawer \{[^}]*position: fixed/.test(css) && css.indexOf('inset: 0') > -1 && css.indexOf('--bx-z-drawer: 1200') > -1);
	check('v4 CSS: کنترل لمسی ≥۴۴/۴۸px', css.indexOf('min-height: 44px') > -1 && css.indexOf('min-height: 48px') > -1);
	check('v4 CSS: دروئر اکشن ۴۸px (ورودی/دکمه)', css.indexOf('min-height: 48px') > -1 && /\.bx-drawer__link \{[^}]*min-height: 48px/.test(css.replace(/\n/g, ' ')));
	check('v4 CSS: داک z-index 999', css.indexOf('--bx-z-dock: 999') > -1 && /\.bx-dock \{[^}]*z-index: var\(--bx-z-dock\)/.test(css.replace(/\n/g, ' ')));
	check('v4 CSS: ورودی فرم ۴۸px', /\.bx-input,[\s\S]{0,120}min-height: 48px/.test(css));
	check('v4 CSS: overflow-x سراسری بسته', css.indexOf('overflow-x: hidden') > -1 && css.indexOf('overflow-x: clip') > -1);
	check('v4 CSS: گرید JetEngine auto-fit minmax(280px,1fr) + stretch', css.indexOf('minmax(280px, 1fr)') > -1 && css.indexOf('.bx-jet-grid') > -1 && css.indexOf('align-items: stretch') > -1);
	check('v4 CSS: وارینت‌های کارت JetEngine', ['--service', '--symptom', '--price', '--testimonial'].every((v) => css.indexOf('.bx-jet-card' + v) > -1));
	check('v4 CSS: prefers-reduced-motion', css.indexOf('prefers-reduced-motion: reduce') > -1);
	check('v4 CSS: هیچ کلاس قدیمی px-/pixva-hero/pixva-mobile-dock نمانده', !/\.px-[a-z]/.test(css) && css.indexOf('.pixva-hero') === -1 && css.indexOf('.pixva-mobile-dock') === -1 && css.indexOf('.pixva-header') === -1 && css.indexOf('.pixva-topbar') === -1);
	check('v4 CSS: هیرو مرکزی (text-align: center) + بج متحرک', /\.bx-hero \{[^}]*text-align: center/.test(css.replace(/\n/g, ' ')) && css.indexOf('bx-pulse-ring') > -1 && css.indexOf('bx-badge-in') > -1);
	check('v4 CSS: پنل رزرو شیشه‌ای radius 28', css.indexOf('--bx-radius-xl: 28px') > -1 && /\.bx-booking \{[\s\S]{0,400}backdrop-filter/.test(css));
	check('v4 CSS: سلول‌های بنتو fault/dispatch/warranty/pricing', ['bx-cell--fault', 'bx-cell--dispatch', 'bx-cell--warranty', 'bx-cell--pricing'].every((c) => css.indexOf('.' + c) > -1));
	check('v4 CSS: واکنش‌گرایی ۱۱۰۰/۱۰۲۴/۷۶۸/۴۸۰', ['max-width: 1100px', 'max-width: 1024px', 'max-width: 768px', 'max-width: 480px'].every((q) => css.indexOf(q) > -1));
	check('v4 CSS: padding بدن برای داک در موبایل', /body \{\s*padding-bottom: calc\(76px \+ env\(safe-area-inset-bottom\)\)/.test(css));

	const mainCss = read('assets/css/main.css');
	check('v4 پاک‌سازی: داک قدیمی از main.css حذف شد', mainCss.indexOf('.pixva-mobile-dock') === -1 && mainCss.indexOf('calc(72px') === -1);
}

function v4TemplateChecks() {
	const header = read('header.php');
	check('v4 هدر: ناوبری قرصی شناور + چسبان', header.indexOf('bx-navpill') > -1 && header.indexOf('data-bx-header') > -1);
	check('v4 هدر: دروئر تمام‌صفحه با data-attribute تازه', header.indexOf('data-bx-burger') > -1 && header.indexOf('data-bx-drawer') > -1 && header.indexOf('data-bx-drawer-close') > -1);
	check('v4 هدر: منوی سفارشی با fallback هاب‌محور', header.indexOf('wp_nav_menu') > -1 && header.indexOf('pixva_bento_nav_fallback') > -1);
	check('v4 هدر: جایگاه Elementor header گیت شده', header.indexOf("pixva_elementor_location( 'header' )") > -1);
	check('v4 هدر: wp_head/body_class/wp_body_open/skip-link سالم', header.indexOf('wp_head()') > -1 && header.indexOf('body_class()') > -1 && header.indexOf('wp_body_open()') > -1 && header.indexOf('skip-link') > -1);
	check('v4 هدر: theme-color پویا از رنگ برند (#4F46E5)', header.indexOf("pixva_option( 'pixva_brand_color', '#4F46E5' )") > -1);
	check('v4 هدر: کلید CTA + لوگوی موبایل + ساعات کاری', header.indexOf('pixva_header_cta_enabled') > -1 && header.indexOf('pixva_mobile_logo_html') > -1 && header.indexOf('pixva_working_hours') > -1);
	check('v4 هدر: هیچ ساختار قدیمی pixva-* نمانده', header.indexOf('pixva-topbar') === -1 && header.indexOf('pixva-header') === -1 && header.indexOf('pixva-drawer') === -1 && header.indexOf('pixva_render_mega_menu') === -1);

	const footer = read('footer.php');
	check('v4 فوتر: ساختار ابسیدین bx-footer', footer.indexOf('bx-footer') > -1 && footer.indexOf('bx-footer__grid') > -1);
	check('v4 فوتر: جایگاه Elementor footer گیت شده', footer.indexOf("pixva_elementor_location( 'footer' )") > -1);
	check('v4 فوتر: داک bx-dock بیرون جایگاه (پس از endif)', footer.indexOf('bx-dock') > -1 && footer.indexOf('endif; /* پایان جایگاه footer') < footer.indexOf('<nav class="bx-dock"'));
	check('v4 فوتر: QR گارانتی + چت‌بات + wp_footer حفظ شده', footer.indexOf('data-pixva-qr') > -1 && footer.indexOf('data-qr-target') > -1 && footer.indexOf('pixva_render_ai_chatbot_widget') > -1 && footer.indexOf('wp_footer()') > -1);
	check('v4 فوتر: نسخه از PIXVA_VERSION', footer.indexOf('pixva_fa_num( PIXVA_VERSION )') > -1);
	check('v4 فوتر: مقصد داک از گزینه CTA (لنگر سالم با pixva_bento_url)', footer.indexOf('pixva_hero_cta_link') > -1 && footer.indexOf('pixva_bento_url') > -1);

	const front = read('front-page.php');
	check('v4 صفحه اصلی: فقط رندررهای بنتو', ['pixva_bento_hero', 'pixva_bento_explorer', 'pixva_bento_brands', 'pixva_bento_closer'].every((f) => front.indexOf(f) > -1));
	check('v4 صفحه اصلی: گیت Elementor (front-page/page/home layout)', front.indexOf("pixva_elementor_location( 'front-page' )") > -1 && front.indexOf('pixva_home_elementor_layout') > -1);
	check('v4 صفحه اصلی: بدون ساختار قدیمی', front.indexOf('px-hero') === -1 && front.indexOf('pixva-hero') === -1 && front.indexOf('pixva_render_hero_booking') === -1 && front.indexOf('pixva-section') === -1);
	check('v4 صفحه اصلی: id=content برای skip-link', front.indexOf('id="content"') > -1);

	const gates = [
		['single.php', "'single'"],
		['page.php', "'page'"],
		['archive.php', "'archive'"],
		['index.php', "'archive'"],
		['search.php', "'search-results'"],
		['404.php', "'404'"],
	];
	for (const [file, loc] of gates) {
		const src = read(file);
		check('v4 گیت جایگاه ' + loc + ' در ' + file, src.indexOf('pixva_elementor_location( ' + loc + ' )') > -1);
		check('v4 پوسته بنتو در ' + file, src.indexOf('bx-') > -1 && src.indexOf('px-') === -1 && src.indexOf('pixva-container') === -1);
	}

	check('v4 searchform: فرم جستجوی بنتو', read('searchform.php').indexOf('bx-search') > -1);
	check('v4 template part: کارت پست بنتو', read('template-parts/card-post.php').indexOf('bx-card') > -1);
}

function v4BackendChecks() {
	const bento = read('inc/bento-ui.php');
	const funcs = ['pixva_bento_icons', 'pixva_bento_services', 'pixva_bento_initial_estimate', 'pixva_bento_micro_trust', 'pixva_bento_nav_fallback', 'pixva_bento_booking', 'pixva_bento_hero', 'pixva_bento_fault_cell', 'pixva_bento_dispatch_cell', 'pixva_bento_warranty_cell', 'pixva_bento_pricing_cell', 'pixva_bento_explorer', 'pixva_bento_brands', 'pixva_bento_closer', 'pixva_bento_localize', 'pixva_bento_url', 'pixva_bento_first_key'];
	check('v4 بک‌اند: همه رندررهای بنتو تعریف شده‌اند', funcs.every((f) => bento.indexOf('function ' + f + '(') > -1));
	check('v4 بک‌اند: موتور رزرو تب‌دار (book/price/track)', bento.indexOf('data-bx-tab="book"') > -1 && bento.indexOf('data-bx-tab="price"') > -1 && bento.indexOf('data-bx-tab="track"') > -1);
	check('v4 بک‌اند: نانس + هانی‌پات + منبع bento-hero', bento.indexOf("wp_create_nonce( 'pixva_express_booking' )") > -1 && bento.indexOf('pixva_hp') > -1 && bento.indexOf('bento-hero') > -1);
	check('v4 بک‌اند: برآورد اولیه سمت سرور از pixva_calculate_estimate', bento.indexOf('pixva_calculate_estimate') > -1 && bento.indexOf('pixva_bento_initial_estimate') > -1);
	check('v4 بک‌اند: داده‌ها از کاتالوگ/گزینه‌ها (بدون سخت‌کد)', bento.indexOf('pixva_brand_catalog') > -1 && bento.indexOf('pixva_price_table_sizes') > -1 && bento.indexOf('pixva_symptom_guide_items') > -1 && bento.indexOf('pixva_option') > -1);
	check('v4 بک‌اند: سلول‌ها با span درست (۸/۴/۴/۸)', bento.indexOf('bx-col-8 bx-reveal" id="fault-explorer') > -1 && bento.indexOf('bx-col-4 bx-reveal" id="dispatch-tracker') > -1 && bento.indexOf('bx-col-4 bx-reveal" id="warranty-lookup') > -1 && bento.indexOf('bx-col-8 bx-reveal" id="pricing-compare') > -1);

	const seo = read('inc/seo-cro.php');
	check('v4 ماژول‌ها: رندرر express با پوسته بنتو', seo.indexOf('data-bx-express') > -1 && seo.indexOf('bx-express__title') > -1 && seo.indexOf('data-express-form') === -1);
	check('v4 ماژول‌ها: رندرر قیمت با پوسته بنتو', seo.indexOf('data-bx-price-pill') > -1 && seo.indexOf('data-bx-price-rows') > -1 && seo.indexOf('pixva-pricetable') === -1);
	check('v4 ماژول‌ها: راهنمای علائم با details بومی', seo.indexOf('bx-sym__item') > -1 && seo.indexOf('<details') > -1 && seo.indexOf('pixva-symptom') === -1);
	check('v4 ماژول‌ها: اعتماد با پوسته بنتو', seo.indexOf('bx-trust__item') > -1 && seo.indexOf('pixva-trust__grid') === -1);
	check('v4 REST: پارامترهای صریح service/size با اولویت بر تشخیص متنی', seo.indexOf("get_param( 'service' )") > -1 && seo.indexOf("get_param( 'size' )") > -1 && seo.indexOf('pixva_price_table_services()') > -1);
	check('v4 REST: پاسخ express شامل service/size', seo.indexOf("'service'  => $problem") > -1 && seo.indexOf("'size'     => $size") > -1);
	check('v4 REST: مسیرهای price-table/express-booking حفظ شده', seo.indexOf("'/price-table'") > -1 && seo.indexOf("'/express-booking'") > -1);
	check('v4 REST: هانی‌پات + نانس + محدودیت نرخ دست‌نخورده', seo.indexOf('pixva_express_spam') > -1 && seo.indexOf('pixva_express_nonce') > -1 && seo.indexOf('pixva_express_booking_limited') > -1);

	const corp = read('inc/corporate-ui.php');
	check('v4 برند: خروجی توکن‌های --bx- از سفارشی‌ساز', corp.indexOf('--bx-brand:') > -1 && corp.indexOf('--bx-accent:') > -1 && corp.indexOf('--bx-brand-strong:') > -1);
	check('v4 برند: پیش‌فرض‌های تازه ایندیگو/زمرد', corp.indexOf('#4F46E5') > -1 && corp.indexOf('#4338CA') > -1 && corp.indexOf('#10B981') > -1);
	check('v4 برند: fallback امن sanitize_hex_color در فرانت‌اند', corp.indexOf("function_exists( 'sanitize_hex_color' )") > -1);
	check('v4 برند: تزریق با wp_add_inline_style روی pixva-seo-cro', corp.indexOf("wp_add_inline_style( 'pixva-seo-cro'") > -1);
	check('v4 برند: لوگوی موبایل + کلید حالت رابط', corp.indexOf('pixva_mobile_logo_html') > -1 && corp.indexOf('pixva_corporate_ui_mode') > -1);

	const fn = read('functions.php');
	check('v4 functions: نسخه ۴٫۰٫۰ + اسپک ۲۶', fn.indexOf("define( 'PIXVA_VERSION', '4.0.0' )") > -1 && fn.indexOf("define( 'PIXVA_SPEC_VERSION', '26.0' )") > -1);
	check('v4 functions: معماری ماژولار آرایه‌محور', fn.indexOf('function pixva_module_map()') > -1 && fn.indexOf('function pixva_load_modules()') > -1 && fn.indexOf("'bento-ui',") > -1);
	check('v4 functions: صف سراسری pixva-bento + localize', fn.indexOf("'pixva-bento'") > -1 && fn.indexOf('pixvaBento') > -1 && fn.indexOf('pixva_bento_localize') > -1);
	check('v4 functions: سامانه طراحی بنتو سراسری (گیت حالت رابط)', fn.indexOf("wp_enqueue_style( 'pixva-seo-cro'") > -1 && fn.indexOf('pixva_corporate_ui_mode()') > -1);
	check('v4 functions: همه اسکریپت‌ها defer و بدون jQuery', fn.indexOf("'strategy'  => 'defer'") > -1 && fn.indexOf('jquery') === -1);
	check('v4 functions: بloat قدیمی حذف شد (pixva_build_toc)', fn.indexOf('pixva_build_toc') === -1);
	check('v4 functions: بارگذاری تنبل المنتور با گیت did_action', fn.indexOf("did_action( 'elementor/loaded' )") > -1);

	const opts = read('inc/theme-options.php');
	check('v4 سفارشی‌ساز: رنگ برند/لهجه با پیش‌فرض تازه', opts.indexOf("'pixva_brand_color'") > -1 && opts.indexOf("'pixva_accent_color'") > -1 && opts.indexOf("'default'           => '#4F46E5'") > -1 && opts.indexOf("'default'           => '#10B981'") > -1);
	check('v4 سفارشی‌ساز: لینک و متن CTA اصلی', opts.indexOf("'pixva_hero_cta_link'") > -1 && opts.indexOf("'pixva_hero_cta_label'") > -1);
	check('v4 سفارشی‌ساز: اعتبارسنج لنگر pixva_sanitize_cta_link', opts.indexOf('function pixva_sanitize_cta_link(') > -1 && opts.indexOf("preg_match( '/^#[A-Za-z0-9_-]+$/'") > -1);
	check('v4 سفارشی‌ساز: سه لوگو + کلید CTA هدر + ساعات کاری', opts.indexOf("'pixva_logo_mobile'") > -1 && opts.indexOf("'pixva_header_cta_enabled'") > -1 && opts.indexOf("'pixva_working_hours'") > -1);

	const el = read('inc/elementor-support.php');
	check('v4 المنتور: ثبت همه جایگاه‌های Theme Builder', el.indexOf('register_all_core_location') > -1 && el.indexOf('elementor/theme/register_locations') > -1);
	check('v4 المنتور: کمکی جایگاه با گیت دوگانه', el.indexOf('function pixva_elementor_location(') > -1 && el.indexOf("did_action( 'elementor/loaded' )") > -1 && el.indexOf("function_exists( 'elementor_theme_do_location' )") > -1);
	check('v4 JetEngine: تشخیص فعال + کلاس body', el.indexOf('function pixva_jetengine_active(') > -1 && el.indexOf('pixva-jetengine-active') > -1);

	const style = read('style.css');
	check('v4 style.css: Version: 4.0.0', style.indexOf('Version: 4.0.0') > -1);
	check('v4 style.css: شرح Master Prompt v14 + Bento', style.indexOf('Master Prompt v14') > -1 && style.indexOf('Bento') > -1);
}

function v4JsChecks() {
	const bento = read('assets/js/bento.js');
	check('v4 bento.js: تب‌ها + موتور رزرو + استعلام + پیگیری', ['data-bx-tab', 'data-bx-booking-form', 'data-bx-quote-rows', 'data-bx-lookup-form'].every((k) => bento.indexOf(k) > -1));
	check('v4 bento.js: چهار اندپوینت REST', ['/price-table', '/express-booking', '/dispatch-live?demo=1', '/track?code='].every((k) => bento.indexOf(k) > -1));
	check('v4 bento.js: payload تازه (brand/service/size/source/nonce/pixva_hp)', ['brand:', 'service:', 'size:', 'source:', 'nonce:', 'pixva_hp:'].every((k) => bento.indexOf(k) > -1));
	check('v4 bento.js: انتخابگر عیب → پیش‌پرکردن رزرو', bento.indexOf('data-bx-fault-book') > -1 && bento.indexOf("__bxActivateTab('book')") > -1);
	check('v4 bento.js: دروئر با Escape + قفل اسکرول', bento.indexOf("event.key === 'Escape'") > -1 && bento.indexOf("document.documentElement.style.overflow") > -1);
	check('v4 bento.js: رهگیر اعزام با refresh پویا', bento.indexOf('payload.refresh') > -1 && bento.indexOf('setTimeout(poll') > -1);
	check('v4 bento.js: reduced-motion + fallback بدون IntersectionObserver', bento.indexOf('prefers-reduced-motion') > -1 && bento.indexOf("'IntersectionObserver' in window") > -1);
	check('v4 bento.js: نرمال‌سازی شماره فارسی/عربی', bento.indexOf('normalizePhone') > -1 && bento.indexOf('۰۱۲۳۴۵۶۷۸۹') > -1);
	check('v4 bento.js: Vanilla + اسکن مجدد المنتور', !/\$\(/.test(bento) && bento.indexOf('window.jQuery') === -1 && bento.indexOf('elementor/frontend/init') > -1);

	const cro = read('assets/js/seo-cro.js');
	check('v4 seo-cro.js: پیوند ماژول‌های تازه (data-bx-price/data-bx-express)', cro.indexOf('[data-bx-price]') > -1 && cro.indexOf('[data-bx-express]') > -1);
	check('v4 seo-cro.js: قرارداد قدیمی حذف شده', cro.indexOf('data-express-form') === -1 && cro.indexOf('data-price-pill') === -1 && cro.indexOf('pixva-pricetable') === -1);
	check('v4 seo-cro.js: REST + المنتور + Vanilla', cro.indexOf('/express-booking') > -1 && cro.indexOf('/price-table') > -1 && cro.indexOf('elementor/frontend/init') > -1 && !/\$\(/.test(cro) && cro.indexOf('window.jQuery') === -1);
}

/* ==========================================================================
 * هارنس مرورگری (jsdom) — fixture لایه ۴٫۰٫۰
 * ======================================================================== */

async function v4Harness() {
	const vc = new VirtualConsole();
	const jsErrors = [];
	vc.on('jsdomError', (e) => jsErrors.push(e.message));

	const dom = await JSDOM.fromFile(FIXTURE, {
		runScripts: 'dangerously',
		resources: 'usable',
		pretendToBeVisual: true,
		virtualConsole: vc,
	});
	const { window } = dom;
	await new Promise((resolve) => {
		if (window.document.readyState === 'complete') resolve();
		else window.addEventListener('load', resolve);
	});
	await wait(260);

	const $ = (sel) => window.document.querySelector(sel);
	const $$ = (sel) => Array.prototype.slice.call(window.document.querySelectorAll(sel));
	const click = (el) => el.dispatchEvent(new window.MouseEvent('click', { bubbles: true, cancelable: true }));
	const change = (el, value) => {
		el.value = value;
		el.dispatchEvent(new window.Event('change', { bubbles: true }));
	};
	const submit = (form) => form.dispatchEvent(new window.Event('submit', { bubbles: true, cancelable: true }));
	const calls = () => window.__pixvaCalls;

	/* --- ساختار ایستایی DOM --- */
	check('v4 DOM: هدر قرصی + دروئر + هیرو + بنتو + فوتر + داک', !!$('.bx-navpill') && !!$('[data-bx-drawer]') && !!$('[data-bx-hero]') && !!$('[data-bx-bento]') && !!$('.bx-footer') && !!$('.bx-dock'));
	check('v4 DOM: بج متحرک هیرو + تیتر + سه نشان اعتماد', !!$('.bx-hero__badge .bx-badge-dot') && !!$('.bx-hero__title') && $$('.bx-microtrust__item').length === 3);
	check('v4 DOM: گرید بنتو با چهار سلول و span درست', $('.bx-cell--fault').className.indexOf('bx-col-8') > -1 && $('.bx-cell--dispatch').className.indexOf('bx-col-4') > -1 && $('.bx-cell--warranty').className.indexOf('bx-col-4') > -1 && $('.bx-cell--pricing').className.indexOf('bx-col-8') > -1);
	check('v4 DOM: داک ۴ اقدام با CTA به #booking', $$('.bx-dock__item').length === 4 && $('.bx-dock__item--cta').getAttribute('href') === '#booking');
	check('v4 DOM: نسخه ۴٫۰٫۰ در فوتر', $('.bx-footer__base').textContent.indexOf('۴٫۰٫۰') > -1);

	/* --- reveal بدون IntersectionObserver --- */
	check('v4 DOM: fallback ظهور، همه bx-reveal نمایان شدند', $$('.bx-reveal').every((el) => el.classList.contains('is-in')));

	/* --- هدر و دروئر --- */
	const burger = $('[data-bx-burger]');
	const drawer = $('[data-bx-drawer]');
	click(burger);
	check('v4 دروئر: با کلیک باز می‌شود (is-open + aria)', drawer.classList.contains('is-open') && drawer.getAttribute('aria-hidden') === 'false' && burger.getAttribute('aria-expanded') === 'true');
	check('v4 دروئر: قفل اسکرول بدن', window.document.documentElement.style.overflow === 'hidden');
	click($('[data-bx-drawer-close]'));
	check('v4 دروئر: با دکمه بسته می‌شود', !drawer.classList.contains('is-open') && drawer.getAttribute('aria-hidden') === 'true' && window.document.documentElement.style.overflow === '');
	click(burger);
	window.document.dispatchEvent(new window.KeyboardEvent('keydown', { key: 'Escape', bubbles: true }));
	check('v4 دروئر: با Escape بسته می‌شود', !drawer.classList.contains('is-open') && burger.getAttribute('aria-expanded') === 'false');
	check('v4 هدر: وضعیت چسبان با اسکرول', (window.dispatchEvent(new window.Event('scroll')), $('.bx-header').classList.contains('is-stuck') === (window.scrollY > 8)));

	/* --- تب‌ها --- */
	const panels = { book: $('[data-bx-panel="book"]'), price: $('[data-bx-panel="price"]'), track: $('[data-bx-panel="track"]') };
	click($('[data-bx-tab="price"]'));
	check('v4 تب: قیمت فوری فعال و رزرو پنهان', panels.price.classList.contains('is-active') && !panels.book.classList.contains('is-active') && panels.book.hasAttribute('hidden') && $('[data-bx-tab="price"]').getAttribute('aria-selected') === 'true');
	check('v4 تب قیمت: ردیف‌های نرخ‌نامه رندر شد (۳ ردیف)', $$('[data-bx-quote-rows] tr').length === 3);
	check('v4 تب قیمت: ردیف پنل برچسب «پس از بازدید» گرفت', $('[data-bx-quote-rows] tr:last-child').textContent.indexOf('پس از بازدید') > -1);

	/* --- پیگیری/گارانتی در تب track --- */
	click($('[data-bx-tab="track"]'));
	const heroLookup = $('[data-bx-lookup-form="hero"]');
	submit(heroLookup);
	check('v4 پیگیری: اعتبارسنجی بدون کد/شماره (پیام خطا، بدون fetch)', $('[data-bx-lookup-msg]', heroLookup) !== null && heroLookup.querySelector('[data-bx-lookup-msg]').classList.contains('bx-msg--err') && calls().track === 0);
	heroLookup.querySelector('[data-bx-track-code]').value = 'PX-123';
	heroLookup.querySelector('[data-bx-track-phone]').value = '09121234567';
	submit(heroLookup);
	await wait(80);
	const heroResult = heroLookup.querySelector('[data-bx-lookup-result]');
	check('v4 پیگیری: نتیجه از REST /track نمایش داده شد', calls().track === 1 && heroResult.classList.contains('is-visible') && heroResult.textContent.indexOf('تحویل شد') > -1);
	check('v4 پیگیری: دستگاه + گارانتی در نتیجه', heroResult.textContent.indexOf('سامسونگ UE55') > -1 && heroResult.textContent.indexOf('گارانتی کتبی ۱۸۰ روزه') > -1);

	/* --- برگشت به تب رزرو و برآورد زنده --- */
	click($('[data-bx-tab="book"]'));
	check('v4 تب: بازگشت به رزرو', panels.book.classList.contains('is-active') && !panels.book.hasAttribute('hidden'));
	const priceCallsBefore = calls().price;
	const serviceSel = $('[data-bx-service]');
	change(serviceSel, 'panel');
	await wait(60);
	check('v4 برآورد زنده: خدمت پنل → متن «پس از بازدید»', $('[data-bx-estimate-value]').textContent.indexOf('پس از بازدید') > -1 || $('[data-bx-estimate-value]').textContent.indexOf('خارج از نرخ‌نامه') > -1);
	change(serviceSel, 'backlight');
	await wait(60);
	check('v4 برآورد زنده: بازگشت به بک‌لایت با بازه تومان', $('[data-bx-estimate-value]').textContent.indexOf('۱٬۲۰۰٬۰۰۰ — ۱٬۸۰۰٬۰۰۰') > -1 && calls().price === priceCallsBefore);
	check('v4 برآورد زنده: حافظه کش نرخ‌نامه (بدون fetch تکراری)', calls().price === priceCallsBefore);

	/* --- انتخابگر عیب --- */
	const chip2 = $('[data-bx-fault-chip="vertical-horizontal-lines"]');
	click(chip2);
	check('v4 عیب: چیپ دوم فعال و نمای دوم باز شد', chip2.classList.contains('is-active') && chip2.getAttribute('aria-selected') === 'true' && !$('[data-bx-fault-view="vertical-horizontal-lines"]').hasAttribute('hidden') && $('[data-bx-fault-view="no-picture-backlight"]').hasAttribute('hidden'));
	check('v4 عیب: محتوای تشخیص (علت + تست + هشدار)', $('[data-bx-fault-view="vertical-horizontal-lines"]').textContent.indexOf('فلت‌های COF') > -1);
	const priceCallsBeforeFault = calls().price;
	click($('[data-bx-fault-view="vertical-horizontal-lines"] [data-bx-fault-book]'));
	check('v4 عیب → رزرو: شرح پیش‌پر شد و تب رزرو فعال است', $('[data-bx-details]').value === 'خطوط عمودی یا افقی' && panels.book.classList.contains('is-active'));
	check('v4 عیب → رزرو: بدون fetch اضافی', calls().price === priceCallsBeforeFault);

	/* --- رهگیر اعزام --- */
	check('v4 اعزام: REST دمو فراخوانی شد', calls().dispatch >= 1);
	check('v4 اعزام: ETA فارسی + وضعیت + تکنسین', $('[data-bx-dispatch-eta]').textContent === '۱۸' && $('[data-bx-dispatch-status]').textContent === 'در مسیر مشتری' && $('[data-bx-dispatch-tech]').textContent === 'تعمیرکار شیفت امروز');
	check('v4 اعزام: نوار پیشرفت ۴۲٪ + مهارت + به‌روزرسانی', $('[data-bx-dispatch-progress]').style.width === '42%' && $('[data-bx-dispatch-skill]').textContent.indexOf('بک‌لایت') > -1 && $('[data-bx-dispatch-updated]').textContent === '۱۲:۳۰:۰۰');

	/* --- استعلام گارانتی (سلول بنتو) --- */
	const cellLookup = $('[data-bx-lookup-form="cell"]');
	cellLookup.querySelector('[data-bx-track-code]').value = 'PX-999';
	cellLookup.querySelector('[data-bx-track-phone]').value = '09121234567';
	submit(cellLookup);
	await wait(80);
	check('v4 گارانتی: پرونده پیدا نشد → پیام خطا از REST', calls().track === 2 && cellLookup.querySelector('[data-bx-lookup-msg]').classList.contains('bx-msg--err') && cellLookup.querySelector('[data-bx-lookup-msg]').textContent.indexOf('یافت نشد') > -1);

	/* --- مقایسه قیمت برند --- */
	const pricing = $('[data-bx-pricing]');
	const priceCallsBeforePill = calls().price;
	click($('[data-bx-price-pill="lg"]'));
	await wait(80);
	check('v4 قیمت: قرص ال‌جی → fetch + ردیف‌های تازه', calls().price === priceCallsBeforePill + 1 && pricing.getAttribute('data-brand') === 'lg');
	check('v4 قیمت: کپشن و وضعیت قرص‌ها همگام', $('[data-bx-price-caption]').textContent.indexOf('ال‌جی') > -1 && $('[data-bx-price-pill="lg"]').classList.contains('is-active') && $('[data-bx-price-pill="lg"]').getAttribute('aria-pressed') === 'true' && !$('[data-bx-price-pill="samsung"]').classList.contains('is-active'));
	check('v4 قیمت: ردیف‌ها با برچسب نرخ مصوب/پس از بازدید', $$('[data-bx-price-rows] tr').length === 3 && $('[data-bx-price-rows] .bx-price-tag--warn') !== null);
	change($('[data-bx-price-size]'), '65');
	await wait(80);
	check('v4 قیمت: تغییر سایز → fetch تازه', calls().price === priceCallsBeforePill + 2);

	/* --- اعتبارسنجی رزرو --- */
	const bookingForm = $('[data-bx-booking-form]');
	submit(bookingForm);
	check('v4 رزرو: موبایل خالی → خطا و بدون POST', calls().express === 0 && $('[data-bx-booking-msg]').classList.contains('bx-msg--err') && $('[data-bx-phone]').classList.contains('is-invalid'));
	$('[data-bx-phone]').value = '۰۹۱۲۱۲۳';
	submit(bookingForm);
	check('v4 رزرو: شماره نامعتبر → خطا (نرمال‌سازی ارقام فارسی)', calls().express === 0 && $('[data-bx-booking-msg]').textContent.indexOf('۰۹۱۲۱۲۳۴۵۶۷') > -1);

	/* --- ثبت موفق رزرو با payload تازه --- */
	$('[data-bx-phone]').value = '۰۹۱۲۱۲۳۴۵۶۷';
	change($('[data-bx-brand]'), 'sony');
	change($('[data-bx-size]'), '65');
	await wait(60);
	submit(bookingForm);
	await wait(120);
	check('v4 رزرو: POST به express-booking ارسال شد', calls().express === 1);
	const sent = window.__pixvaLastExpress || {};
	check('v4 رزرو: payload تازه (phone نرمال + brand/service/size/source/nonce/hp)', sent.phone === '09121234567' && sent.brand === 'sony' && sent.service === 'backlight' && sent.size === '65' && sent.source === 'bento-hero' && sent.nonce === 'bento-nonce-1' && sent.pixva_hp === '');
	check('v4 رزرو: حالت موفقیت با کد پیگیری', $('.bx-success') !== null && $('.bx-success__code').textContent === 'PX-40001' && $('.bx-success').textContent.indexOf('ثبت شد') > -1);
	check('v4 رزرو: فرم پس از موفقیت جایگزین شد', $('[data-bx-booking-form]') === null);

	check('v4 هارنس: بدون خطای jsdom (' + jsErrors.join(' | ') + ')', jsErrors.length === 0);

	window.close();
}

/* ==========================================================================
 * اجرا
 * ======================================================================== */

(async function main() {
	console.log('Pixva QA — لایه ۴٫۰٫۰ (Bento & SaaS)');

	jqueryAudit();
	classFilterAudit();
	abspathGuard();
	inlineStyleAudit();

	v4CssChecks();
	v4TemplateChecks();
	v4BackendChecks();
	v4JsChecks();

	if (!fs.existsSync(FIXTURE)) {
		console.log('fixture v4-preview.html موجود نیست — هارنس رد شد');
	} else {
		await v4Harness();
	}

	console.log('');
	console.log(pass + '/' + (pass + fail) + ' assertions passed');
	if (fail > 0) {
		console.log('FAILURES:');
		failures.forEach((f) => console.log(' - ' + f));
		process.exitCode = 1;
	} else {
		console.log('');
		console.log('ALL GREEN');
	}
}());

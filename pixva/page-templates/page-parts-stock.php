<?php
/**
 * Template Name: استعلام انبار قطعات فابریک
 * Template Post Type: page
 *
 * @package Pixva
 * @since   1.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>
<main id="content">
	<?php pixva_page_hero( __( 'انبار مرکزی قطعات فابریک و اصالت کالا', 'pixva' ), __( 'استعلام زنده موجودی انواع بردهای تغذیه، مین‌برد و دست کامل بک‌لایت‌های اورجینال کره‌ای و ژاپنی.', 'pixva' ) ); ?>
	<div class="pixva-container pixva-content">
		<div class="pixva-card" style="margin-bottom:1.5rem;">
			<div style="display:flex;gap:12px;flex-wrap:wrap;">
				<input type="text" placeholder="جستجوی مدل دستگاه یا پارت نامبر (مثلاً 55AU7000 یا BN44...)" class="pixva-input" style="flex:2;min-width:240px;">
				<select class="pixva-input" style="flex:1;min-width:140px;">
					<option>همه قطعات</option>
					<option>دست کامل بک‌لایت</option>
					<option>برد تغذیه (Power)</option>
					<option>برد اصلی (Mainboard)</option>
					<option>تیکان و فلت COF</option>
				</select>
				<button type="button" class="pixva-btn pixva-btn--primary">استعلام موجودی کارگاه</button>
			</div>
		</div>

		<div class="pixva-grid pixva-grid--3" style="gap:1.2rem;">
			<div class="pixva-card">
				<span class="pixva-badge pixva-badge--success">موجود در کارگاه علاءالدین</span>
				<h4 style="margin:0.6rem 0;color:#0F172A;">بک‌لایت دست کامل سامسونگ 55RU/NU</h4>
				<p class="pixva-muted" style="font-size:13px;">جنس هیت‌سینک آلومینیوم تقویت‌شده با لنز اورجینال ضدحرارت.</p>
				<div style="margin-top:10px;font-size:12px;color:#64748B;">شناسه پارت: BN96-45913A</div>
			</div>
			<div class="pixva-card">
				<span class="pixva-badge pixva-badge--success">موجود در کارگاه علاءالدین</span>
				<h4 style="margin:0.6rem 0;color:#0F172A;">برد پاور تلویزیون سونی 55X8500G</h4>
				<p class="pixva-muted" style="font-size:13px;">تست‌شده زیر بار ۲۴ ساعته با خازن‌های ژاپنی Rubycon اورجینال.</p>
				<div style="margin-top:10px;font-size:12px;color:#64748B;">شناسه پارت: 1-984-255-11</div>
			</div>
			<div class="pixva-card">
				<span class="pixva-badge pixva-badge--brand">آماده تحویل ۱ ساعته</span>
				<h4 style="margin:0.6rem 0;color:#0F172A;">مین‌برد ال‌جی 49LJ520V</h4>
				<p class="pixva-muted" style="font-size:13px;">پروگرام‌شده با فریمور فارسی و تیونر دیجیتال DVB-T2 فعال.</p>
				<div style="margin-top:10px;font-size:12px;color:#64748B;">شناسه پارت: EAX67166104</div>
			</div>
		</div>
	</div>
</main>
<?php
get_footer();

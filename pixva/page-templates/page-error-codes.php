<?php
/**
 * Template Name: پایگاه کدهای خطا
 * Template Post Type: page
 *
 * @package Pixva
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>
<main id="content">
	<?php pixva_page_hero( __( 'چشمک چراغ پاور یعنی چه؟ راهنمای کدهای خطا', 'pixva' ), __( 'چراغ پاور وقتی پشت سر هم چشمک می‌زند دارد آدرس خرابی را می‌دهد. برند و تعداد چشمک را انتخاب کن تا بفهمی مشکل از کجاست — و مهم‌تر، چه کاری را نباید بکنی.', 'pixva' ) ); ?>
	<div class="pixva-container pixva-content">
		<div class="pixva-grid pixva-grid--3" style="margin-bottom:1.4rem">
			<article class="pixva-card pixva-reveal">
				<h3><?php esc_html_e( '۱. درست بشمار', 'pixva' ); ?></h3>
				<p><?php esc_html_e( 'برق را بکش، دو دقیقه صبر کن، وصل کن و فقط الگویی که تکرار می‌شود را بشمار. چشمک اولِ روشن شدن حساب نیست.', 'pixva' ); ?></p>
			</article>
			<article class="pixva-card pixva-reveal">
				<h3><?php esc_html_e( '۲. تطبیق بده', 'pixva' ); ?></h3>
				<p><?php esc_html_e( 'برند و تعداد چشمک را در فیلتر پایین بزن. مثلاً سه چشمک سونی معمولاً برد تغذیه است؛ چهار چشمک معمولاً بک‌لایت.', 'pixva' ); ?></p>
			</article>
			<article class="pixva-card pixva-reveal">
				<h3><?php esc_html_e( '۳. عجله نکن', 'pixva' ); ?></h3>
				<p><?php esc_html_e( 'دانستن الگو به معنی باز کردن دستگاه نیست. برد پاور حتی بعد از کشیدن دوشاخه بار دارد و کشنده است.', 'pixva' ); ?></p>
			</article>
		</div>
		<?php pixva_render_error_database(); ?>
		<aside class="pixva-notice pixva-notice--info" style="margin-top:1rem">
			<?php esc_html_e( 'این فهرست راهنمای کارگاهی پیکسوا است، نه سرویس‌منوال رسمی سازنده. اگر الگوی تو این‌جا نیست، همان تعداد چشمک و مدل دستگاه را در فرم تماس بفرست تا راهنمایی‌ات کنیم.', 'pixva' ); ?>
		</aside>
		<div style="margin-top:1.2rem">
			<?php pixva_cta_box(); ?>
		</div>
	</div>
</main>
<?php
get_footer();

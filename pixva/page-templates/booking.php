<?php
/**
 * Template Name: PIXVA — ثبت درخواست تعمیر
 *
 * Booking (§11). POST to admin-post (no-JS, PRG) or admin-ajax (app.js).
 * Server validation, nonce, honeypot, rate limit, idempotency and private
 * photo storage live in forms.php / security.php. Prefill from diagnosis
 * (problem, brand, model, symptoms, age, from=diagnosis) and from service /
 * brand pages.
 *
 * @package Pixva
 * @since   2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Elementor takeover: when this page is saved with Elementor the document
// below the header IS the page (classic sections are skipped). Classic
// behavior is untouched whenever the flag/data is absent.
if ( pixva_elementor_takeover() ) {
	pixva_elementor_render_page( __( 'مشخصات دستگاه و ایراد را بنویسید. پس از ثبت، کد پیگیری دریافت می‌کنید و کارشناس برای هماهنگی با شما تماس می‌گیرد.', 'pixva' ) );
	return;
}
get_header();
?>
<main id="main" class="site-main">
	<?php pixva_page_header( get_the_title(), __( 'مشخصات دستگاه و ایراد را بنویسید. پس از ثبت، کد پیگیری دریافت می‌کنید و کارشناس برای هماهنگی با شما تماس می‌گیرد.', 'pixva' ) ); ?>
	<div class="container container--narrow section">
		<?php pixva_page_intro(); ?>
		<?php pixva_booking_form_block(); ?>
				<?php
		$pixva_faq = pixva_faq_items( 'booking' );
		if ( $pixva_faq ) :
			?>
			<section class="section--tight" aria-labelledby="bk-faq"><h2 id="bk-faq"><?php esc_html_e( 'پرسش‌های رایج', 'pixva' ); ?></h2><?php pixva_faq_list( $pixva_faq, true ); ?></section>
	</div>
</main>
		<?php endif; ?>
	</div>
</main>
<?php
get_footer();

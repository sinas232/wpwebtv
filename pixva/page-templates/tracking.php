<?php
/**
 * Template Name: PIXVA — پیگیری تعمیر
 *
 * Tracking (§12). Code + registered phone (POST, never in the URL),
 * rate-limited with per-code lockout; output has no PII or internal notes.
 * No-JS: same-page POST. lookup.js: REST /pixva/v1/track.
 *
 * @package Pixva
 * @since   2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
nocache_headers();

// Elementor takeover: when this page is saved with Elementor the document
// below the header IS the page (classic sections are skipped). Classic
// behavior is untouched whenever the flag/data is absent.
if ( pixva_elementor_takeover() ) {
	pixva_elementor_render_page( __( 'کد پیگیری و شماره همراهی را که هنگام ثبت درخواست وارد کرده‌اید بنویسید.', 'pixva' ) );
	return;
}
$pixva_res = pixva_handle_page_lookup( 'pixva_track' );
get_header();
?>
<main id="main" class="site-main">
	<?php pixva_page_header( get_the_title(), __( 'کد پیگیری و شماره همراهی را که هنگام ثبت درخواست وارد کرده‌اید بنویسید.', 'pixva' ) ); ?>
	<div class="container container--narrow section">
		<?php pixva_page_intro(); ?>
		<?php pixva_lookup_form( 'pixva_track', 'track', __( 'نمایش وضعیت', 'pixva' ), 'tracking_viewed' ); ?>
		<div class="lookup__result" data-lookup-result aria-live="polite" tabindex="-1">
			<?php if ( is_wp_error( $pixva_res ) ) : ?>
				<?php pixva_notice( 'error', $pixva_res->get_error_message(), '', true ); ?>
			<?php elseif ( is_array( $pixva_res ) ) : ?>
				<div data-track-view="tracking_viewed"><?php pixva_order_view( $pixva_res ); ?></div>
			<?php endif; ?>
		</div>
		<?php if ( is_user_logged_in() ) : ?>
			<p><a class="link-more" href="<?php echo esc_url( pixva_route_url( 'account_repairs' ) ); ?>"><?php esc_html_e( 'همه درخواست‌های من', 'pixva' ); ?></a></p>
		<?php else : ?>
			<p class="field__help"><?php esc_html_e( 'کد را گم کرده‌اید؟ اگر با حساب کاربری درخواست ثبت کرده‌اید، وارد حساب شوید.', 'pixva' ); ?> <a href="<?php echo esc_url( pixva_route_url( 'account' ) ); ?>"><?php esc_html_e( 'ورود', 'pixva' ); ?></a></p>
		<?php endif; ?>
		<?php
		$pixva_faq = pixva_faq_items( 'tracking' );
		if ( $pixva_faq ) :
			?>
			<section class="section--tight" aria-labelledby="tr-faq"><h2 id="tr-faq"><?php esc_html_e( 'پرسش‌های رایج', 'pixva' ); ?></h2><?php pixva_faq_list( $pixva_faq, true ); ?></section>
		<?php endif; ?>
	</div>
</main>
<?php
get_footer();

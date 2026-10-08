<?php
/**
 * Template Name: PIXVA — گارانتی
 *
 * Warranty (§13). Policy text, duration and exclusions come ONLY from
 * business claims; nothing is invented when unset. Per-repair warranty
 * status is looked up by code + phone (dates set by staff per order).
 *
 * @package Pixva
 * @since   2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
nocache_headers();
$pixva_res  = pixva_handle_page_lookup( 'pixva_warranty' );
$pixva_days = pixva_warranty_policy_days();
$pixva_on   = (bool) pixva_claim( 'warranty_enabled' );
get_header();
?>
<main id="main" class="site-main">
	<?php pixva_page_header( get_the_title() ); ?>
	<div class="container container--narrow section">
		<?php pixva_page_intro(); ?>
		<section aria-labelledby="wr-policy">
			<h2 id="wr-policy"><?php esc_html_e( 'سیاست گارانتی', 'pixva' ); ?></h2>
			<?php if ( $pixva_days > 0 ) : ?>
				<p class="price"><?php echo esc_html( sprintf( /* translators: %s: days. */ __( '%s روز گارانتی تعمیر', 'pixva' ), pixva_fa_num( $pixva_days ) ) ); ?></p>
			<?php endif; ?>
			<?php if ( $pixva_on && pixva_has_claim( 'warranty_terms' ) ) : ?>
				<div class="entry-content"><?php echo wp_kses_post( wpautop( (string) pixva_claim( 'warranty_terms' ) ) ); ?></div>
			<?php endif; ?>
			<?php if ( $pixva_on && pixva_has_claim( 'warranty_excluded' ) ) : ?>
				<h3><?php esc_html_e( 'موارد خارج از پوشش', 'pixva' ); ?></h3>
				<div class="entry-content"><?php echo wp_kses_post( wpautop( (string) pixva_claim( 'warranty_excluded' ) ) ); ?></div>
			<?php endif; ?>
			<?php if ( ! $pixva_on || ( $pixva_days <= 0 && ! pixva_has_claim( 'warranty_terms' ) ) ) : ?>
				<?php pixva_notice( 'info', __( 'سیاست گارانتی عمومی هنوز منتشر نشده است. شرایط و مدت گارانتی هر تعمیر را پیش از شروع کار از کارشناس بپرسید؛ پس از ثبت، وضعیت گارانتی تعمیر شما در این صفحه قابل استعلام است.', 'pixva' ) ); ?>
			<?php endif; ?>
		</section>
		<section class="section--tight" aria-labelledby="wr-lookup">
			<h2 id="wr-lookup"><?php esc_html_e( 'استعلام گارانتی تعمیر', 'pixva' ); ?></h2>
			<?php pixva_lookup_form( 'pixva_warranty', 'warranty', __( 'استعلام', 'pixva' ), 'warranty_lookup' ); ?>
			<div class="lookup__result" data-lookup-result aria-live="polite" tabindex="-1">
				<?php if ( is_wp_error( $pixva_res ) ) : ?>
					<?php pixva_notice( 'error', $pixva_res->get_error_message(), '', true ); ?>
				<?php elseif ( is_array( $pixva_res ) ) : ?>
					<div data-track-view="warranty_lookup"><?php pixva_warranty_view( $pixva_res ); ?></div>
				<?php endif; ?>
			</div>
		</section>
		<?php
		$pixva_faq = pixva_faq_items( 'warranty' );
		if ( $pixva_faq ) :
			?>
			<section class="section--tight" aria-labelledby="wr-faq"><h2 id="wr-faq"><?php esc_html_e( 'پرسش‌های رایج', 'pixva' ); ?></h2><?php pixva_faq_list( $pixva_faq, true ); ?></section>
		<?php endif; ?>
	</div>
</main>
<?php
get_footer();

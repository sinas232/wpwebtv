<?php
/**
 * Template Name: PIXVA — تعمیر
 *
 * /repair/ hub: how the repair process works (statuses the system really
 * tracks), configured service modes only, services, booking/tracking CTAs.
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
	pixva_elementor_render_page( pixva_route_description( 'repair' ) );
	return;
}
get_header();
$pixva_modes = pixva_service_modes();
?>
<main id="main" class="site-main">
	<?php pixva_page_header( get_the_title(), pixva_route_description( 'repair' ) ); ?>
	<div class="container section">
		<?php pixva_page_intro(); ?>
		<p class="hero__actions">
			<a class="btn btn--primary" href="<?php echo esc_url( pixva_route_url( 'booking' ) ); ?>" data-track="cta_click" data-track-label="booking" data-track-location="repair_hub"><?php esc_html_e( 'ثبت درخواست تعمیر', 'pixva' ); ?></a>
			<a class="btn btn--ghost" href="<?php echo esc_url( pixva_route_url( 'diagnosis' ) ); ?>" data-track="cta_click" data-track-label="diagnosis" data-track-location="repair_hub"><?php esc_html_e( 'اول تشخیص آنلاین', 'pixva' ); ?></a>
			<a class="btn btn--ghost" href="<?php echo esc_url( pixva_route_url( 'tracking' ) ); ?>"><?php esc_html_e( 'پیگیری درخواست', 'pixva' ); ?></a>
		</p>
	</div>
	<?php pixva_section_open( 'repair-process', __( 'روند تعمیر', 'pixva' ) ); ?>
		<ol class="steps">
			<li><strong><?php esc_html_e( 'ثبت درخواست', 'pixva' ); ?></strong><span><?php esc_html_e( 'مشخصات دستگاه و ایراد را ثبت می‌کنید و کد پیگیری می‌گیرید.', 'pixva' ); ?></span></li>
			<li><strong><?php esc_html_e( 'عیب‌یابی', 'pixva' ); ?></strong><span><?php esc_html_e( 'کارشناس دستگاه را بررسی و علت و هزینه را اعلام می‌کند.', 'pixva' ); ?></span></li>
			<li><strong><?php esc_html_e( 'تأیید شما', 'pixva' ); ?></strong><span><?php esc_html_e( 'تعمیر فقط پس از تأیید هزینه توسط شما شروع می‌شود.', 'pixva' ); ?></span></li>
			<li><strong><?php esc_html_e( 'تعمیر، تست و تحویل', 'pixva' ); ?></strong><span><?php esc_html_e( 'هر مرحله در صفحه پیگیری با همان کد قابل مشاهده است.', 'pixva' ); ?></span></li>
		</ol>
	<?php pixva_section_close(); ?>
	<?php if ( $pixva_modes ) : ?>
		<?php pixva_section_open( 'repair-modes', __( 'شیوه‌های دریافت خدمت', 'pixva' ) ); ?>
			<?php pixva_list( array_values( $pixva_modes ), 'checks' ); ?>
			<?php if ( pixva_has_claim( 'service_area' ) ) : ?>
				<p><?php echo esc_html( sprintf( /* translators: %s: area. */ __( 'محدوده خدمت: %s', 'pixva' ), (string) pixva_claim( 'service_area' ) ) ); ?></p>
			<?php endif; ?>
		<?php pixva_section_close(); ?>
	<?php endif; ?>
	<?php
	$pixva_services = get_posts(
		array(
			'post_type'      => 'tv_services',
			'post_status'    => 'publish',
			'posts_per_page' => 12,
			'orderby'        => array(
				'menu_order' => 'ASC',
				'title'      => 'ASC',
			),
			'no_found_rows'  => true,
		)
	);
	if ( $pixva_services ) :
		pixva_section_open( 'repair-services', __( 'خدمات تعمیر', 'pixva' ), pixva_route_url( 'services' ) );
		pixva_card_grid( $pixva_services );
		pixva_section_close();
	endif;
	$pixva_faq = pixva_faq_items( 'booking' );
	if ( $pixva_faq ) :
		pixva_section_open( 'repair-faq', __( 'پرسش‌های رایج', 'pixva' ) );
		pixva_faq_list( $pixva_faq, true );
		pixva_section_close();
	endif;
	?>
</main>
<?php
get_footer();

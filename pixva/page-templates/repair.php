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
	<?php
	pixva_journey_section(
		'repair-process',
		__( 'روند تعمیر، قدم به قدم', 'pixva' ),
		array(
			array(
				'icon'  => 'calendar',
				'title' => __( 'ثبت درخواست', 'pixva' ),
				'text'  => __( 'مشخصات دستگاه و ایراد را ثبت می‌کنید و کد پیگیری می‌گیرید.', 'pixva' ),
			),
			array(
				'icon'  => 'search',
				'title' => __( 'عیب‌یابی', 'pixva' ),
				'text'  => __( 'کارشناس دستگاه را بررسی و علت و هزینه را اعلام می‌کند.', 'pixva' ),
			),
			array(
				'icon'  => 'check',
				'title' => __( 'تأیید شما', 'pixva' ),
				'text'  => __( 'تعمیر فقط پس از تأیید هزینه توسط شما شروع می‌شود.', 'pixva' ),
			),
			array(
				'icon'  => 'shield',
				'title' => __( 'تعمیر، تست و تحویل', 'pixva' ),
				'text'  => __( 'هر مرحله در صفحه پیگیری با همان کد قابل مشاهده است.', 'pixva' ),
			),
		),
		__( 'هیچ کاری بدون تأیید شما انجام نمی‌شود.', 'pixva' )
	);
	?>
	<?php if ( $pixva_modes ) : ?>
		<?php
		pixva_section_open(
			'repair-modes',
			__( 'شیوه‌های دریافت خدمت', 'pixva' ),
			'',
			__( 'بسته به شرایط خود، یکی از شیوه‌های زیر را انتخاب می‌کنید.', 'pixva' ),
			__( 'شیوه‌های خدمت', 'pixva' )
		);
		?>
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
		pixva_section_open(
			'repair-services',
			__( 'خدمات تعمیر', 'pixva' ),
			pixva_route_url( 'services' ),
			__( 'یکی را انتخاب کنید تا صفحه سرویس آن باز شود.', 'pixva' ),
			__( 'خدمات', 'pixva' )
		);
		pixva_service_feature( $pixva_services[0], 1 );
		pixva_service_rows( array_slice( $pixva_services, 1 ), 2 );
		pixva_section_close();
	endif;
	$pixva_faq = pixva_faq_items( 'booking' );
	if ( $pixva_faq ) :
		pixva_section_open(
			'repair-faq',
			__( 'پرسش‌های رایج', 'pixva' ),
			pixva_route_url( 'faq' ),
			'',
			__( 'سؤالات متداول', 'pixva' )
		);
		pixva_faq_list( $pixva_faq, true );
		pixva_section_close();
	endif;
	?>
</main>
<?php
get_footer();

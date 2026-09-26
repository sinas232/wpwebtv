<?php
/**
 * Template Name: پنل تعمیرکاران (ایستگاه کاری)
 *
 * محیط اختصاصی تعمیرکاران: پرونده‌های تخصیص‌یافته، گزارش فنی،
 * تأیید نهایی و صدور گارانتی دیجیتال با هولوگرام.
 *
 * @package Pixva
 * @since   1.4.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$pixva_panel_title = (string) pixva_option( 'pixva_technician_title', __( 'داشبورد تعمیرکار', 'pixva' ) );
$pixva_panel_lead  = (string) pixva_option(
	'pixva_technician_subtitle',
	__( 'دستگاه‌های تخصیص‌یافته به شما، گزارش فنی قطعات، قیمت نهایی و صدور کارت گارانتی در یک محیط کاری تمیز.', 'pixva' )
);
?>
<main id="content" class="pixva-main pixva-main--crm">
	<div class="pixva-tech-dash">
		<?php pixva_page_hero( $pixva_panel_title, $pixva_panel_lead ); ?>

		<div class="pixva-container pixva-content">
			<?php
			if ( function_exists( 'pixva_technician_dashboard_bar' ) ) {
				pixva_technician_dashboard_bar();
			}
			pixva_render_technician_panel();
			?>
		</div>
	</div>
</main>
<?php
get_footer();

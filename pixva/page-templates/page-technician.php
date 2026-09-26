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

$pixva_panel_title = (string) pixva_option( 'pixva_technician_title', __( 'ایستگاه کاری تعمیرکار', 'pixva' ) );
$pixva_panel_lead  = (string) pixva_option(
	'pixva_technician_subtitle',
	__( 'پرونده‌های تخصیص‌یافته به شما، گزارش فنی قطعات و صدور گارانتی دیجیتال در یک محیط کاری تمیز.', 'pixva' )
);
?>
<main id="content" class="pixva-main pixva-main--crm">
	<?php pixva_page_hero( $pixva_panel_title, $pixva_panel_lead ); ?>

	<div class="pixva-container pixva-content">
		<?php pixva_render_technician_panel(); ?>
	</div>
</main>
<?php
get_footer();

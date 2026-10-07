<?php
/**
 * Template Name: گارانتی تعمیر
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
	<?php pixva_page_hero( __( 'گارانتی تعمیر', 'pixva' ), __( 'اعتبار فقط از روی پرونده ثبت‌شده خوانده می‌شود.', 'pixva' ) ); ?>
	<div class="pixva-container pixva-content">
		<?php pixva_render_warranty_form(); ?>
		<p class="pixva-muted"><?php esc_html_e( 'گارانتی ۱۸۰ روزه برای برد و بک‌لایت، بعد از آماده‌شدن دستگاه در پرونده نوشته می‌شود.', 'pixva' ); ?></p>
	</div>
</main>
<?php
get_footer();

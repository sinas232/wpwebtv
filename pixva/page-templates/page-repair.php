<?php
/**
 * Template Name: درخواست تعمیر
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
	<?php pixva_page_hero( __( 'درخواست تعمیر', 'pixva' ), __( 'دستگاه، زمان و شرح مشکل را بفرستید. قیمت نهایی بعد از بررسی نوشته می‌شود.', 'pixva' ) ); ?>
	<div class="pixva-container pixva-content">
		<?php pixva_render_booking(); ?>
	</div>
</main>
<?php
get_footer();

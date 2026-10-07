<?php
/**
 * Template Name: پنل مشتریان و گارانتی دیجیتال
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
	<?php pixva_page_hero( __( 'حساب و گارانتی', 'pixva' ), __( 'کارت نمونه نشان داده نمی‌شود. فقط پرونده واقعی.', 'pixva' ) ); ?>
	<div class="pixva-container pixva-content">
		<?php pixva_render_account(); ?>
		<?php pixva_render_warranty_form(); ?>
	</div>
</main>
<?php
get_footer();

<?php
/**
 * Template Name: حساب مشتری
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
	<?php pixva_page_hero( __( 'حساب من', 'pixva' ), __( 'پرونده‌ها، دستگاه‌های ذخیره‌شده و گارانتی.', 'pixva' ) ); ?>
	<div class="pixva-container pixva-content">
		<?php pixva_render_account(); ?>
	</div>
</main>
<?php
get_footer();

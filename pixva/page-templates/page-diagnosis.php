<?php
/**
 * Template Name: تشخیص مشکل
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
	<?php pixva_page_hero( __( 'تشخیص اولیه', 'pixva' ), __( 'برند و علامت را بگویید. نتیجه، حدس کارگاه است نه قطعیت.', 'pixva' ) ); ?>
	<div class="pixva-container pixva-content">
		<?php pixva_render_diagnosis(); ?>
	</div>
</main>
<?php
get_footer();

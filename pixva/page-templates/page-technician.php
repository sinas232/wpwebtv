<?php
/**
 * Template Name: میز تکنسین
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
	<?php pixva_page_hero( __( 'میز تکنسین', 'pixva' ), __( 'وضعیت پرونده را فقط تکنسین یا مدیر عوض می‌کند.', 'pixva' ) ); ?>
	<div class="pixva-container pixva-content">
		<?php pixva_render_technician_desk(); ?>
	</div>
</main>
<?php
get_footer();

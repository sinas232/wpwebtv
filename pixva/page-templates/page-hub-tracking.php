<?php
/**
 * Template Name: هاب ۳: پیگیری آنلاین و گارانتی دیجیتال
 * Template Post Type: page
 *
 * @package Pixva
 * @since   1.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>
<main id="content">
	<?php pixva_page_hero( __( 'هاب ۳: سامانه رهگیری زنده پذیرش و گارانتی دیجیتال', 'pixva' ), __( 'استعلام وضعیت سفارش با تایم‌لاین ۶ مرحله‌ای، صدور کارت گارانتی دیجیتال با هش SHA256 و اعزام اورژانسی تکنسین.', 'pixva' ) ); ?>
	<div class="pixva-container pixva-content">
		<?php
		if ( function_exists( 'pixva_render_dispatch_and_warranty_hub' ) ) {
			pixva_render_dispatch_and_warranty_hub();
		}
		?>
	</div>
</main>
<?php
get_footer();

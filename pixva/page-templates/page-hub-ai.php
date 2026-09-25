<?php
/**
 * Template Name: هاب ۱: مرکز عیب‌یابی هوشمند و AI
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
	<?php pixva_page_hero( __( 'هاب ۱: مرکز عیب‌یابی هوشمند تصویری و صوتی (AI Diagnostics)', 'pixva' ), __( 'مجموعه ابزارهای هوش مصنوعی Gemini Vision، شبیه‌ساز لمسی تلویزیون مجازی، تست پیکسل‌های RGB و احیاکننده OLED.', 'pixva' ) ); ?>
	<div class="pixva-container pixva-content">
		<?php
		if ( function_exists( 'pixva_render_tv_canvas_simulator' ) ) {
			pixva_render_tv_canvas_simulator();
		}
		if ( function_exists( 'pixva_render_screen_rgb_tester' ) ) {
			pixva_render_screen_rgb_tester();
		}
		?>
	</div>
</main>
<?php
get_footer();

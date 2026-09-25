<?php
/**
 * Template Name: هاب ۲: محاسبه‌گر آنلاین و نرخ‌نامه شفاف
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
	<?php pixva_page_hero( __( 'هاب ۲: محاسبه‌گر هوشمند آنلاین و نرخ‌نامه مصوب بازار ۱۴۰۵', 'pixva' ), __( 'محاسبه دقیق هزینه قطعات فابریک و اجرت تعمیر با کف ۸ میلیون تومان و فرمول غیرخطی سایزهای ۳۲ تا ۹۸ اینچ.', 'pixva' ) ); ?>
	<div class="pixva-container pixva-content">
		<div class="pixva-card" style="margin-bottom:2rem;">
			<h2 style="color:#0F172A;margin-top:0;"><?php esc_html_e( 'محاسبه فوری بر اساس برند و سایز', 'pixva' ); ?></h2>
			<?php pixva_render_calculator(); ?>
		</div>
	</div>
</main>
<?php
get_footer();

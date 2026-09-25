<?php
/**
 * Template Name: هاب ۲: محاسبه‌گر و نرخ‌نامه
 * Template Post Type: page
 *
 * ابزارهای ۱۱ تا ۲۰: تحلیل تعمیر یا خرید، اسلایدر سایز، تفکیک شفاف هزینه،
 * استعلام انبار، تایم‌لاین پرونده، کارت گارانتی، اعزام اورژانسی، تحلیل‌گر نویز،
 * رهگیری اعزام و استعلام سریع تصویری.
 *
 * @package Pixva
 * @since   1.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$pixva_hub = 'pricing-calculator';
?>
<main id="content">
	<?php
	pixva_page_hero(
		__( 'هاب ۲ — محاسبه‌گر و نرخ‌نامه ۱۴۰۵', 'pixva' ),
		__( 'برآورد زنده هزینه تعمیر با فرمول نرخ‌نامه مصوب بازار، کف ۸ میلیون تومان برای ۳۲ اینچ و تفکیک شفاف قطعه، اجرت، کارشناسی و گارانتی.', 'pixva' ),
		'pixva-hub-hero'
	);
	?>

	<section class="pixva-section" id="hub-calculator">
		<div class="pixva-container">
			<div class="pixva-quote-widget__card pixva-glass">
				<div class="pixva-quote-widget__head">
					<div>
						<span class="pixva-badge pixva-badge--brand"><?php esc_html_e( 'محاسبه‌گر آنلاین', 'pixva' ); ?></span>
						<h2><?php esc_html_e( 'هزینه تعمیر دستگاه من چقدر می‌شود؟', 'pixva' ); ?></h2>
						<p><?php esc_html_e( 'همه محاسبه‌ها سمت سرور و بر پایه نرخ‌نامه مصوب انجام می‌شود؛ هیچ قیمتی در مرورگر ساخته نمی‌شود.', 'pixva' ); ?></p>
					</div>
					<a class="pixva-btn pixva-btn--ghost-dark" href="<?php echo esc_url( pixva_page_url( 'rates' ) ); ?>"><?php esc_html_e( 'نرخ‌نامه کامل', 'pixva' ); ?></a>
				</div>
				<?php pixva_render_calculator(); ?>
			</div>
		</div>
	</section>

	<section class="pixva-section pixva-section--alt">
		<div class="pixva-container">
			<?php pixva_render_rates_reference(); ?>
		</div>
	</section>

	<?php
	pixva_render_hub_index( $pixva_hub );

	if ( function_exists( 'pixva_render_dispatch_and_warranty_hub' ) ) {
		pixva_render_dispatch_and_warranty_hub();
	}

	pixva_render_hub_tools( $pixva_hub );

	while ( have_posts() ) :
		the_post();
		if ( get_the_content() ) {
			echo '<div class="pixva-container pixva-content">';
			the_content();
			echo '</div>';
		}
	endwhile;
	?>
</main>
<?php
get_footer();

<?php
/**
 * Template Name: هاب ۱: عیب‌یابی هوشمند (AI Diagnostics)
 * Template Post Type: page
 *
 * ابزارهای ۱ تا ۱۰: دستیار متنی، تحلیل تصویر، آنالیز فریم‌های پیاپی، ورودی صوتی،
 * شبیه‌ساز لمسی تلویزیون، تستر RGB، احیای OLED، تستر چشمک چراغ، اسلایدر قبل/بعد
 * و تستر صدا.
 *
 * @package Pixva
 * @since   1.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$pixva_hub = 'ai-diagnostics';
?>
<main id="content">
	<?php
	pixva_page_hero(
		__( 'هاب ۱ — عیب‌یابی هوشمند تلویزیون', 'pixva' ),
		__( 'دستیار هوش مصنوعی با تحلیل متن، تصویر و صدا، شبیه‌ساز لمسی خرابی، تستر پیکسل‌سوختگی RGB و بازسازی الگوی چشمک چراغ استندبای.', 'pixva' ),
		'pixva-hub-hero'
	);

	pixva_render_hub_index( $pixva_hub );

	if ( function_exists( 'pixva_render_before_after' ) ) {
		$pixva_case = get_posts(
			array(
				'post_type'      => 'repair_cases',
				'posts_per_page' => 1,
				'no_found_rows'  => true,
			)
		);
		$pixva_images = array(
			'before' => PIXVA_URI . '/assets/images/panel-before.jpg',
			'after'  => PIXVA_URI . '/assets/images/panel-after.jpg',
		);
		$pixva_title  = __( 'قبل و بعد از ترمیم بندینگ پنل', 'pixva' );
		if ( ! empty( $pixva_case ) ) {
			$pixva_images = pixva_case_images( $pixva_case[0]->ID );
			$pixva_title  = get_the_title( $pixva_case[0] );
		}
		echo '<section class="pixva-section"><div class="pixva-container">';
		echo '<div class="pixva-section-head"><span class="pixva-badge">' . esc_html__( 'ابزار ۹', 'pixva' ) . '</span><h2>' . esc_html__( 'اسلایدر قبل و بعد از تعمیر', 'pixva' ) . '</h2><p>' . esc_html__( 'خط جداکننده را بکشید؛ سمت راست وضعیت پذیرش و سمت چپ خروجی تست نهایی کارگاه است.', 'pixva' ) . '</p></div>';
		pixva_render_before_after( $pixva_images['before'], $pixva_images['after'], $pixva_title );
		echo '</div></section>';
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

<?php
/**
 * صفحه اصلی پیکسوا — نسخه ۴٫۰٫۰ (Bento & SaaS Luxury)
 *
 * معماری تازه از صفر:
 * ۱) هیروی مرکزی تعاملی با بج متحرک، تیتر غول‌پیکر، سه نشان اعتماد شناور
 *    و موتور رزرو تب‌دار شیشه‌ای با برآورد زنده قیمت.
 * ۲) کاوشگر بنتو ۱۲ ستونه: انتخابگر عیب (۸)، رهگیر اعزام زنده (۴)،
 *    استعلام گارانتی (۴) و مقایسه قیمت برند (۸).
 * ۳) نوار برندهای تحت پوشش و جمع‌بندی CTA ابسیدین.
 *
 * اگر صفحه اصلی با Elementor ساخته شده باشد (جایگاه front-page/page یا
 * چیدمان خانگی المنتور)، همان رندر می‌شود و این چیدمان کنار می‌رود.
 *
 * @package Pixva
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$pixva_builder_active = ( function_exists( 'pixva_elementor_location' ) && ( pixva_elementor_location( 'front-page' ) || pixva_elementor_location( 'page' ) ) )
	|| ( function_exists( 'pixva_home_elementor_layout' ) && pixva_home_elementor_layout() );

if ( ! $pixva_builder_active ) :
?>

<main id="content" class="bx-main" data-bx-front>

	<?php
	if ( function_exists( 'pixva_bento_hero' ) ) {
		pixva_bento_hero();
	}
	if ( function_exists( 'pixva_bento_explorer' ) ) {
		pixva_bento_explorer();
	}
	if ( function_exists( 'pixva_bento_brands' ) ) {
		pixva_bento_brands();
	}
	if ( function_exists( 'pixva_bento_closer' ) ) {
		pixva_bento_closer();
	}
	?>

</main>

<?php
endif;

get_footer();

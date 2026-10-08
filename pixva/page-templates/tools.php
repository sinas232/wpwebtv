<?php
/**
 * Template Name: PIXVA — ابزارها
 *
 * /tools/ hub linking the diagnosis wizard, price calculator, pixel test
 * and the error-code database (/error-codes/, canonical for /tools/error-codes/).
 *
 * @package Pixva
 * @since   2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
get_header();
?>
<main id="main" class="site-main">
	<?php pixva_page_header( get_the_title(), pixva_route_description( 'tools' ) ); ?>
	<div class="container section">
		<?php pixva_page_intro(); ?>
		<div class="section__head">
			<div class="section__head-main">
				<p class="eyebrow"><?php esc_html_e( 'چهار ابزار', 'pixva' ); ?></p>
				<h2 class="section__title"><?php esc_html_e( 'چه کاری می‌خواهید انجام دهید؟', 'pixva' ); ?></h2>
				<p class="section__lead"><?php esc_html_e( 'تشخیص، برآورد، تست صفحه و دانشنامه کدهای خطا — همگی رایگان و بدون نیاز به ورود.', 'pixva' ); ?></p>
			</div>
		</div>
		<?php pixva_tool_cards(); ?>
		<?php pixva_notice( 'info', __( 'نتیجه ابزارها راهنمای اولیه است و جای بررسی حضوری کارشناس را نمی‌گیرد. اگر بوی سوختگی، دود یا صدای جرقه دارید، دوشاخه را از برق بکشید و دستگاه را باز نکنید.', 'pixva' ) ); ?>
	</div>
	<div class="container section--tight"><?php pixva_cta_box( '', '', 'tools_hub' ); ?></div>
</main>
<?php
get_footer();

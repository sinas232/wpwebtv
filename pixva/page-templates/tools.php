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

// Elementor takeover: when this page is saved with Elementor the document
// below the header IS the page (classic sections are skipped). Classic
// behavior is untouched whenever the flag/data is absent.
if ( pixva_elementor_takeover() ) {
	pixva_elementor_render_page( pixva_route_description( 'tools' ) );
	return;
}
get_header();
?>
<main id="main" class="site-main">
	<?php pixva_page_header( get_the_title(), pixva_route_description( 'tools' ) ); ?>
	<div class="container section">
		<?php pixva_page_intro(); ?>
		<?php pixva_tool_cards(); ?>
		<?php pixva_notice( 'info', __( 'نتیجه ابزارها راهنمای اولیه است و جای بررسی حضوری کارشناس را نمی‌گیرد. اگر بوی سوختگی، دود یا صدای جرقه دارید، دوشاخه را از برق بکشید و دستگاه را باز نکنید.', 'pixva' ) ); ?>
	</div>
	<div class="container section--tight"><?php pixva_cta_box( '', '', 'tools_hub' ); ?></div>
</main>
<?php
get_footer();

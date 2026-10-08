<?php
/**
 * Template Name: PIXVA — مشکلات رایج
 *
 * /problems/ hub: tv_problem terms that have real content (thin terms are
 * hidden here and noindexed on their own URL until completed).
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
	<?php pixva_page_header( get_the_title(), pixva_route_description( 'problems' ) ); ?>
	<div class="container section">
		<?php pixva_page_intro(); ?>
		<?php if ( ! pixva_problem_tiles() ) : ?>
			<?php
			pixva_empty_state(
				__( 'راهنمای مشکلات هنوز منتشر نشده است', 'pixva' ),
				__( 'تا آن زمان می‌توانید با تشخیص آنلاین، علت‌های محتمل ایراد تلویزیون خود را ببینید.', 'pixva' ),
				array(
					__( 'شروع تشخیص آنلاین', 'pixva' ) => pixva_route_url( 'diagnosis' ),
					__( 'ثبت درخواست تعمیر', 'pixva' ) => pixva_route_url( 'booking' ),
				)
			);
			?>
		<?php endif; ?>
	</div>
	<?php pixva_section_open( 'problems-tools', __( 'ابزارهای عیب‌یابی', 'pixva' ) ); ?>
		<?php pixva_tool_cards(); ?>
	<?php pixva_section_close(); ?>
	<div class="container section--tight"><?php pixva_cta_box( '', '', 'problems_hub' ); ?></div>
</main>
<?php
get_footer();

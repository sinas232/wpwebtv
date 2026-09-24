<?php
/**
 * Template Name: سوالات متداول
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
	<?php pixva_page_hero( __( 'سوالات متداول تعمیر تلویزیون', 'pixva' ), __( 'پاسخ‌های کارگاهی، نه شعار. اگر سؤال شما این‌جا نیست، فرم تماس را پر کنید.', 'pixva' ) ); ?>
	<div class="pixva-container pixva-content" style="max-width:860px">
		<?php pixva_render_faq( pixva_default_faqs(), 'page-faq' ); ?>
		<div style="margin-top:1.4rem">
			<?php pixva_cta_box(); ?>
		</div>
	</div>
</main>
<?php
get_footer();

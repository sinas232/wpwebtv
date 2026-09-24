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
	<?php pixva_page_hero( __( 'سؤال داری؟ این‌جا صریح جواب می‌گیری', 'pixva' ), __( 'از «می‌صرفد تعمیرش کنم؟» تا «گارانتی دقیقاً چی را پوشش می‌دهد؟» — پاسخ کارگاهی، نه شعار تبلیغاتی. اگر سؤالت این‌جا نیست، فرم تماس یک دقیقه وقت می‌گیرد.', 'pixva' ) ); ?>
	<div class="pixva-container pixva-content" style="max-width:860px">
		<p class="pixva-muted"><?php echo esc_html( sprintf( __( '%s پرسش پرتکرار، با جواب کامل', 'pixva' ), pixva_fa_num( count( pixva_default_faqs() ) ) ) ); ?></p>
		<?php pixva_render_faq( pixva_default_faqs(), 'page-faq' ); ?>
		<div style="margin-top:1.4rem">
			<?php pixva_cta_box(); ?>
		</div>
	</div>
</main>
<?php
get_footer();

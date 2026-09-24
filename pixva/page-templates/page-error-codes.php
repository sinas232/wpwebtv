<?php
/**
 * Template Name: پایگاه کدهای خطا
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
	<?php pixva_page_hero( __( 'کدهای خطا و چشمک چراغ پاور', 'pixva' ), __( 'برند و تعداد چشمک را انتخاب کنید. برای مثال سه چشمک سونی در این راهنما به برد تغذیه اشاره دارد. این فهرست جایگزین سرویس‌منوال رسمی نیست.', 'pixva' ) ); ?>
	<div class="pixva-container pixva-content">
		<?php pixva_render_error_database(); ?>
		<aside class="pixva-notice pixva-notice--info" style="margin-top:1rem">
			<?php esc_html_e( 'برد پاور ولتاژ خطرناک دارد. اگر با مولتی‌متر کار نکرده‌اید، دستگاه را باز نکنید و همان الگو را برای کارگاه بفرستید.', 'pixva' ); ?>
		</aside>
	</div>
</main>
<?php
get_footer();

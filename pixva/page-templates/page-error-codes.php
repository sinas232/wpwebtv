<?php
/**
 * Template Name: هاب ۴: کدهای خطا و آموزش
 * Template Post Type: page
 *
 * پایگاه کدهای خطا و چشمک چراغ + ابزارهای ۳۱ تا ۴۵ (حمل و بسته‌بندی، راهنمای
 * کالیبراسیون، مصرف برق، استهلاک بک‌لایت، تست پورت‌ها، ارزیابی داغی و …).
 *
 * @package Pixva
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$pixva_hub = 'error-codes';
?>
<main id="content">
	<?php
	pixva_page_hero(
		__( 'هاب ۴ — کدهای خطا، چشمک چراغ و آموزش کارگاهی', 'pixva' ),
		__( 'پایگاه کدهای خطا به تفکیک برند و تعداد چشمک چراغ استندبای، به‌همراه پانزده ابزار آموزشی از بسته‌بندی و حمل تا کالیبراسیون تصویر و مدیریت مصرف برق.', 'pixva' ),
		'pixva-hub-hero'
	);
	?>
	<div class="pixva-container pixva-content">
		<?php pixva_render_error_database(); ?>
		<aside class="pixva-notice pixva-notice--warning">
			<strong><?php esc_html_e( 'هشدار ایمنی:', 'pixva' ); ?></strong>
			<?php esc_html_e( 'برد تغذیه حتی پس از قطع برق، ولتاژ خطرناک ذخیره دارد. اگر با مولتی‌متر و تخلیه خازن کار نکرده‌اید، دستگاه را باز نکنید؛ همان الگوی چشمک را برای کارگاه بفرستید.', 'pixva' ); ?>
		</aside>
	</div>
	<?php
	pixva_render_hub_index( $pixva_hub );
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

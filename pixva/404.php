<?php
/**
 * 404 / 410 page (§23–§24). Status codes are set by redirects.php;
 * this template only adapts the wording and offers useful exits.
 *
 * @package Pixva
 * @since   2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
get_header();
$pixva_gone = ! empty( $GLOBALS['pixva_is_gone'] );
?>
<main id="main" class="site-main">
	<?php
	pixva_page_header(
		$pixva_gone ? __( 'این صفحه حذف شده است', 'pixva' ) : __( 'صفحه پیدا نشد', 'pixva' ),
		$pixva_gone ? __( 'محتوای این نشانی به‌طور دائمی برداشته شده است. از گزینه‌های زیر برای پیدا کردن مطلب مشابه استفاده کنید.', 'pixva' ) : __( 'نشانی ممکن است اشتباه تایپ شده یا صفحه جابه‌جا شده باشد.', 'pixva' )
	);
	?>
	<div class="container section">
		<?php get_search_form(); ?>
		<h2 class="section__title"><?php esc_html_e( 'شاید این‌ها کمک کند', 'pixva' ); ?></h2>
		<?php pixva_tool_cards(); ?>
		<ul class="link-list link-list--inline">
			<?php foreach ( array( 'services', 'problems', 'error_codes', 'booking', 'tracking', 'blog', 'contact' ) as $pixva_key ) : ?>
				<li><a href="<?php echo esc_url( pixva_route_url( $pixva_key ) ); ?>"><?php echo esc_html( pixva_routes()[ $pixva_key ]['title'] ); ?></a></li>
			<?php endforeach; ?>
		</ul>
	</div>
</main>
<?php
get_footer();

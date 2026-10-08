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
	<div class="container section nf">
		<div class="nf-search reveal">
			<p class="nf-search__label"><?php esc_html_e( 'جستجو در سایت', 'pixva' ); ?></p>
			<?php get_search_form(); ?>
		</div>

		<section class="nf-paths" aria-labelledby="nf-paths-title">
			<h2 id="nf-paths-title" class="nf-paths__title"><?php esc_html_e( 'از کجا ادامه دهیم؟', 'pixva' ); ?></h2>
			<ol class="nf-paths__list">
				<li class="nf-path nf-path--primary">
					<a href="<?php echo esc_url( pixva_route_url( 'diagnosis' ) ); ?>">
						<span class="nf-path__no" aria-hidden="true">۱</span>
						<span class="nf-path__title"><?php esc_html_e( 'تشخیص آنلاین ایراد', 'pixva' ); ?></span>
						<span class="nf-path__text"><?php esc_html_e( 'با چند پرسش ساده، علت احتمالی خرابی را پیدا کنید.', 'pixva' ); ?></span>
					</a>
				</li>
				<li class="nf-path">
					<a href="<?php echo esc_url( pixva_route_url( 'error_codes' ) ); ?>">
						<span class="nf-path__no" aria-hidden="true">۲</span>
						<span class="nf-path__title"><?php esc_html_e( 'دانشنامه کد خطا', 'pixva' ); ?></span>
						<span class="nf-path__text"><?php esc_html_e( 'کد خطای روی صفحه را جست‌وجو کنید.', 'pixva' ); ?></span>
					</a>
				</li>
				<li class="nf-path">
					<a href="<?php echo esc_url( pixva_route_url( 'booking' ) ); ?>">
						<span class="nf-path__no" aria-hidden="true">۳</span>
						<span class="nf-path__title"><?php esc_html_e( 'ثبت درخواست تعمیر', 'pixva' ); ?></span>
						<span class="nf-path__text"><?php esc_html_e( 'اگر ایراد را می‌دانید، مستقیم درخواست ثبت کنید.', 'pixva' ); ?></span>
					</a>
				</li>
			</ol>
		</section>

		<nav class="nf-index" aria-label="<?php esc_attr_e( 'بخش‌های سایت', 'pixva' ); ?>">
			<h2 class="nf-index__title"><?php esc_html_e( 'بخش‌های سایت', 'pixva' ); ?></h2>
			<ul class="nf-index__list">
				<?php foreach ( array( 'services', 'problems', 'tracking', 'blog', 'faq', 'contact' ) as $pixva_key ) : ?>
					<li><a href="<?php echo esc_url( pixva_route_url( $pixva_key ) ); ?>"><?php echo esc_html( pixva_routes()[ $pixva_key ]['title'] ); ?></a></li>
				<?php endforeach; ?>
			</ul>
		</nav>
	</div>
</main>
<?php
get_footer();

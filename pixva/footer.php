<?php
/**
 * فوتر قالب پیکسوا
 *
 * @package Pixva
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$pixva_phone = pixva_support_phone();
$pixva_calc  = pixva_page_url( 'calculator' );
?>
<div class="px-close">
	<div class="pixva-container px-close__inner">
		<div>
			<p class="px-kicker"><?php esc_html_e( 'پیکسوا · علاءالدین', 'pixva' ); ?></p>
			<h2><?php esc_html_e( 'تلویزیونت هنوز مشکل داره؟ بگذار متخصص‌ها بررسی‌اش کنند.', 'pixva' ); ?></h2>
		</div>
		<div class="px-close__actions">
			<a class="pixva-btn pixva-btn--cta" href="<?php echo esc_url( pixva_page_url( 'repair' ) ); ?>"><?php esc_html_e( 'درخواست تعمیر', 'pixva' ); ?></a>
			<a class="pixva-btn px-btn--ghost" href="<?php echo esc_url( pixva_tel_href( $pixva_phone ) ); ?>"><?php esc_html_e( 'تماس فوری', 'pixva' ); ?></a>
		</div>
	</div>
</div>

<footer class="pixva-footer" aria-label="<?php esc_attr_e( 'فوتر', 'pixva' ); ?>">
	<div class="pixva-container px-map">
		<iframe
			title="<?php esc_attr_e( 'نقشه کارگاه پیکسوا در پاساژ علاءالدین', 'pixva' ); ?>"
			loading="lazy"
			referrerpolicy="no-referrer-when-downgrade"
			src="https://www.openstreetmap.org/export/embed.html?bbox=51.412%2C35.690%2C51.430%2C35.700&amp;layer=mapnik&amp;marker=35.6948%2C51.4212"
		></iframe>
		<div class="px-map__card">
			<p class="px-kicker"><?php esc_html_e( 'مراجعه حضوری', 'pixva' ); ?></p>
			<p><?php echo esc_html( (string) pixva_option( 'pixva_workshop_address', __( 'تهران، خیابان جمهوری، خیابان ناصرخسرو، پاساژ علاءالدین، طبقه ۴، واحد ۴۱۲', 'pixva' ) ) ); ?></p>
			<a class="pixva-btn pixva-btn--cta" href="<?php echo esc_url( 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode( 'پاساژ علاءالدین تهران' ) ); ?>"><?php esc_html_e( 'مسیر تا کارگاه', 'pixva' ); ?></a>
		</div>
	</div>
	<div class="pixva-footer__dock">
		<div class="pixva-container pixva-footer__grid">
			<section>
				<?php pixva_the_logo( 'light' ); ?>
				<p><?php echo esc_html( get_bloginfo( 'description' ) ? get_bloginfo( 'description' ) : __( 'مرکز تخصصی تعمیر تلویزیون و نمایشگر؛ پنل، بک‌لایت و برد.', 'pixva' ) ); ?></p>
				<div class="pixva-socials">
					<?php foreach ( pixva_social_links() as $item ) : ?>
						<a href="<?php echo esc_url( $item['url'] ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $item['label'] ); ?></a>
					<?php endforeach; ?>
					<?php if ( ! pixva_social_links() ) : ?>
						<span class="pixva-muted"><?php esc_html_e( 'لینک شبکه‌ها را از سفارشی‌ساز وارد کنید.', 'pixva' ); ?></span>
					<?php endif; ?>
				</div>
			</section>

			<section>
				<h2><?php esc_html_e( 'دسترسی سریع', 'pixva' ); ?></h2>
				<?php
				wp_nav_menu(
					array(
						'theme_location' => 'footer',
						'container'      => false,
						'menu_class'     => 'menu',
						'fallback_cb'    => static function () {
							echo '<ul class="menu">';
							$links = array(
								pixva_page_url( 'calculator' ) => __( 'محاسبه هزینه', 'pixva' ),
								pixva_page_url( 'tracking' )   => __( 'پیگیری تعمیر', 'pixva' ),
								pixva_page_url( 'error-codes' ) => __( 'کدهای خطا', 'pixva' ),
								pixva_page_url( 'faq' )        => __( 'سوالات متداول', 'pixva' ),
								pixva_page_url( 'about' )      => __( 'درباره پیکسوا', 'pixva' ),
								pixva_page_url( 'contact' )    => __( 'تماس با ما', 'pixva' ),
							);
							foreach ( $links as $url => $label ) {
								echo '<li><a href="' . esc_url( $url ) . '">' . esc_html( $label ) . '</a></li>';
							}
							echo '</ul>';
						},
						'depth'          => 1,
					)
				);
				?>
			</section>

			<section>
				<h2><?php esc_html_e( 'خدمات', 'pixva' ); ?></h2>
				<ul>
					<?php foreach ( pixva_service_fallbacks() as $service ) : ?>
						<li><a href="<?php echo esc_url( add_query_arg( 'problem', $service['key'], pixva_page_url( 'calculator' ) ) ); ?>"><?php echo esc_html( $service['title'] ); ?></a></li>
					<?php endforeach; ?>
				</ul>
			</section>

			<section>
				<h2><?php esc_html_e( 'کارگاه', 'pixva' ); ?></h2>
				<ul class="pixva-info-list">
					<li><?php echo pixva_icon( 'pin' ); ?><span><?php echo esc_html( (string) pixva_option( 'pixva_workshop_address', __( 'تهران، خیابان جمهوری، خیابان ناصرخسرو، پاساژ علاءالدین، طبقه ۴، واحد ۴۱۲', 'pixva' ) ) ); ?></span></li>
					<?php foreach ( pixva_footer_phones() as $phone ) : ?>
						<li><?php echo pixva_icon( 'phone' ); ?><a href="<?php echo esc_url( pixva_tel_href( $phone ) ); ?>"><?php echo esc_html( pixva_fa_num( $phone ) ); ?></a></li>
					<?php endforeach; ?>
				</ul>
				<div class="pixva-trust-row"><?php pixva_trust_badges(); ?></div>
			</section>
		</div>
		<div class="pixva-container pixva-footer__base">
			<p class="pixva-copyright"><?php echo esc_html( (string) pixva_option( 'pixva_copyright', __( '© تمامی حقوق برای مرکز تخصصی پیکسوا محفوظ است.', 'pixva' ) ) ); ?></p>
			<p><?php echo esc_html( pixva_fa_num( wp_date( 'Y' ) ) ); ?></p>
		</div>
	</div>
</footer>

<nav class="pixva-mobile-dock" aria-label="<?php esc_attr_e( 'نوار دسترسی سریع موبایل', 'pixva' ); ?>">
	<a href="<?php echo esc_url( pixva_tel_href( $pixva_phone ) ); ?>" class="pixva-mobile-dock__item">
		<?php echo pixva_icon( 'phone' ); ?>
		<span><?php esc_html_e( 'تماس فوری', 'pixva' ); ?></span>
	</a>
	<a href="<?php echo esc_url( pixva_page_url( 'repair' ) ); ?>" class="pixva-mobile-dock__item is-cta">
		<?php echo pixva_icon( 'bolt' ); ?>
		<span><?php esc_html_e( 'درخواست تعمیر', 'pixva' ); ?></span>
	</a>
</nav>

<?php
if ( function_exists( 'pixva_render_ai_chatbot_widget' ) ) {
	pixva_render_ai_chatbot_widget();
}
?>
<?php wp_footer(); ?>
</body>
</html>

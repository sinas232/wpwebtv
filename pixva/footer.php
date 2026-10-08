<?php
/**
 * Site footer: tools/route links, configured contact details and social
 * links only (nothing renders for unset claims), legal links.
 *
 * @package Pixva
 * @since   2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$pixva_social = pixva_social_links();
?>
<footer class="site-footer">
	<div class="site-footer__top">
		<div class="container site-footer__top-inner">
			<p class="site-footer__statement"><?php esc_html_e( 'تشخیص را از تعمیر جدا نکن؛ اول ببین، بعد تصمیم بگیر.', 'pixva' ); ?></p>
			<div class="site-footer__top-actions">
				<a class="btn btn--accent" data-track="cta_click" data-track-label="booking" data-track-location="footer" href="<?php echo esc_url( pixva_route_url( 'booking' ) ); ?>"><?php esc_html_e( 'ثبت درخواست تعمیر', 'pixva' ); ?></a>
				<a class="btn btn--ghost-light" data-track="cta_click" data-track-label="diagnosis" data-track-location="footer" href="<?php echo esc_url( pixva_route_url( 'diagnosis' ) ); ?>"><?php esc_html_e( 'شروع تشخیص', 'pixva' ); ?></a>
			</div>
		</div>
	</div>
	<div class="container site-footer__grid">
		<div class="site-footer__brand">
			<?php pixva_logo(); ?>
			<?php if ( pixva_has_claim( 'tagline' ) ) : ?>
				<p class="site-footer__tagline"><?php echo esc_html( (string) pixva_claim( 'tagline' ) ); ?></p>
			<?php else : ?>
				<p class="site-footer__tagline"><?php echo esc_html( get_bloginfo( 'description' ) ); ?></p>
			<?php endif; ?>
			<?php if ( $pixva_social ) : ?>
				<ul class="social" aria-label="<?php esc_attr_e( 'شبکه‌های اجتماعی', 'pixva' ); ?>">
					<?php foreach ( $pixva_social as $pixva_s ) : ?>
						<li><a href="<?php echo esc_url( $pixva_s[1] ); ?>" rel="noopener me" target="_blank"><?php echo esc_html( $pixva_s[0] ); ?></a></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
		<nav class="site-footer__col" aria-labelledby="ft-tools">
			<h2 class="site-footer__title" id="ft-tools"><?php esc_html_e( 'ابزارها', 'pixva' ); ?></h2>
			<ul>
				<?php foreach ( pixva_tools() as $pixva_tool ) : ?>
					<li><a href="<?php echo esc_url( $pixva_tool['url'] ); ?>"><?php echo esc_html( $pixva_tool['title'] ); ?></a></li>
				<?php endforeach; ?>
			</ul>
		</nav>
		<nav class="site-footer__col" aria-labelledby="ft-links">
			<h2 class="site-footer__title" id="ft-links"><?php esc_html_e( 'پیکسوا', 'pixva' ); ?></h2>
			<?php if ( has_nav_menu( 'footer' ) ) : ?>
				<?php
				wp_nav_menu(
					array(
						'theme_location' => 'footer',
						'container'      => false,
						'depth'          => 1,
						'fallback_cb'    => false,
					)
				);
				?>
			<?php else : ?>
				<ul>
					<?php foreach ( array( 'services', 'brands', 'problems', 'portfolio', 'blog', 'faq', 'about', 'contact' ) as $pixva_key ) : ?>
						<li><a href="<?php echo esc_url( pixva_route_url( $pixva_key ) ); ?>"><?php echo esc_html( pixva_routes()[ $pixva_key ]['title'] ); ?></a></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</nav>
		<nav class="site-footer__col" aria-labelledby="ft-service">
			<h2 class="site-footer__title" id="ft-service"><?php esc_html_e( 'خدمات مشتری', 'pixva' ); ?></h2>
			<ul>
				<?php foreach ( array( 'booking', 'tracking', 'warranty', 'account' ) as $pixva_key ) : ?>
					<li><a href="<?php echo esc_url( pixva_route_url( $pixva_key ) ); ?>"><?php echo esc_html( pixva_routes()[ $pixva_key ]['title'] ); ?></a></li>
				<?php endforeach; ?>
			</ul>
		</nav>
		<?php
		ob_start();
		$pixva_has_contact = pixva_contact_details();
		$pixva_contact     = ob_get_clean();
		if ( $pixva_has_contact ) :
			?>
			<div class="site-footer__col site-footer__contact">
				<h2 class="site-footer__title"><?php esc_html_e( 'ارتباط با ما', 'pixva' ); ?></h2>
				<?php echo $pixva_contact; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in pixva_contact_details(). ?>
			</div>
		<?php endif; ?>
	</div>
	<div class="container site-footer__legal">
		<p>&copy; <?php echo esc_html( pixva_fa_num( wp_date( 'Y' ) ) ); ?> <?php echo esc_html( pixva_business_name() ); ?></p>
		<ul>
			<?php if ( get_privacy_policy_url() ) : ?>
				<li><a href="<?php echo esc_url( get_privacy_policy_url() ); ?>"><?php esc_html_e( 'حریم خصوصی', 'pixva' ); ?></a></li>
			<?php endif; ?>
			<?php if ( pixva_has_claim( 'trust_seal_url' ) && pixva_has_claim( 'trust_seal_image' ) ) : ?>
				<li><a href="<?php echo esc_url( (string) pixva_claim( 'trust_seal_url' ) ); ?>" rel="noopener" target="_blank"><img src="<?php echo esc_url( (string) pixva_claim( 'trust_seal_image' ) ); ?>" alt="<?php esc_attr_e( 'نماد اعتماد', 'pixva' ); ?>" width="96" height="40" loading="lazy"></a></li>
			<?php endif; ?>
		</ul>
	</div>
</footer>
<?php /* Mobile sticky CTA: two real actions, visible under 768px only. */ ?>
<nav class="m-cta" aria-label="<?php esc_attr_e( 'دسترسی سریع', 'pixva' ); ?>">
	<a class="m-cta__ghost" data-track="cta_click" data-track-label="diagnosis" data-track-location="sticky_mobile" href="<?php echo esc_url( pixva_route_url( 'diagnosis' ) ); ?>"><?php echo wp_kses( pixva_icon( 'pulse' ), pixva_svg_allowed() ); ?><span><?php esc_html_e( 'تشخیص', 'pixva' ); ?></span></a>
	<a class="m-cta__main" data-track="cta_click" data-track-label="booking" data-track-location="sticky_mobile" href="<?php echo esc_url( pixva_route_url( 'booking' ) ); ?>"><?php esc_html_e( 'ثبت درخواست تعمیر', 'pixva' ); ?></a>
</nav>
<?php wp_footer(); ?>
</body>
</html>

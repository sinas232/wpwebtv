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
get_header();
?>
<main id="main" class="site-main tl-page">
	<?php pixva_page_header( get_the_title(), pixva_route_description( 'tools' ) ); ?>
	<div class="container section tl">
		<?php pixva_page_intro(); ?>
		<?php $pixva_tools = pixva_tools(); ?>

		<section class="tl-tier tl-tier--primary" aria-labelledby="tl-primary">
			<h2 id="tl-primary" class="sr-only"><?php esc_html_e( 'ابزار اصلی', 'pixva' ); ?></h2>
			<?php $pixva_d = $pixva_tools['diagnosis']; ?>
			<article class="hx-flagship">
				<p class="hx-flagship__kicker"><?php esc_html_e( 'ابزار اصلی', 'pixva' ); ?></p>
				<h3 class="hx-flagship__title"><a href="<?php echo esc_url( $pixva_d['url'] ); ?>"><?php echo esc_html( $pixva_d['title'] ); ?></a></h3>
				<p class="hx-flagship__text"><?php echo esc_html( $pixva_d['desc'] ); ?></p>
				<ol class="hx-flagship__steps" aria-label="<?php esc_attr_e( 'مراحل تشخیص', 'pixva' ); ?>">
					<li><span><?php esc_html_e( 'دستگاه', 'pixva' ); ?></span></li>
					<li><span><?php esc_html_e( 'نشانه‌ها', 'pixva' ); ?></span></li>
					<li><span><?php esc_html_e( 'نتیجه با درجه اطمینان', 'pixva' ); ?></span></li>
				</ol>
				<a class="btn btn--accent btn--lg hx-flagship__cta" href="<?php echo esc_url( $pixva_d['url'] ); ?>" data-track="cta_click" data-track-label="diagnosis" data-track-location="tools_hub"><?php esc_html_e( 'شروع تشخیص رایگان', 'pixva' ); ?><?php echo wp_kses( pixva_icon( 'arrow' ), pixva_svg_allowed() ); ?></a>
				<svg class="hx-flagship__art" viewBox="0 0 320 120" fill="none" aria-hidden="true" focusable="false"><path d="M0 84 H70 L86 84 102 24 124 102 142 52 156 84 H320" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/></svg>
			</article>
		</section>

		<section class="tl-tier tl-tier--secondary" aria-labelledby="tl-secondary">
			<h2 id="tl-secondary" class="tl-tier__title"><?php esc_html_e( 'ابزارهای کمکی', 'pixva' ); ?></h2>
			<ul class="hx-tools__side tl-tier__list">
				<?php foreach ( array( 'price_calculator', 'pixel_test' ) as $pixva_k ) : ?>
					<?php $pixva_t = $pixva_tools[ $pixva_k ]; ?>
					<li class="hx-tool">
						<a href="<?php echo esc_url( $pixva_t['url'] ); ?>">
							<span class="hx-tool__icon" aria-hidden="true"><?php echo wp_kses( pixva_icon( $pixva_t['icon'] ), pixva_svg_allowed() ); ?></span>
							<span class="hx-tool__body">
								<span class="hx-tool__title"><?php echo esc_html( $pixva_t['title'] ); ?></span>
								<span class="hx-tool__text"><?php echo esc_html( $pixva_t['desc'] ); ?></span>
							</span>
							<span class="hx-tool__go" aria-hidden="true"><?php echo wp_kses( pixva_icon( 'arrow' ), pixva_svg_allowed() ); ?></span>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		</section>

		<section class="tl-tier tl-tier--tertiary" aria-labelledby="tl-tertiary">
			<h2 id="tl-tertiary" class="tl-tier__title"><?php esc_html_e( 'دانشنامه', 'pixva' ); ?></h2>
			<?php $pixva_t = $pixva_tools['error_codes']; ?>
			<a class="hx-tool hx-tool--codes" href="<?php echo esc_url( $pixva_t['url'] ); ?>">
				<span class="hx-tool__icon" aria-hidden="true"><?php echo wp_kses( pixva_icon( $pixva_t['icon'] ), pixva_svg_allowed() ); ?></span>
				<span class="hx-tool__body">
					<span class="hx-tool__title"><?php echo esc_html( $pixva_t['title'] ); ?></span>
					<span class="hx-tool__text"><?php echo esc_html( $pixva_t['desc'] ); ?></span>
				</span>
				<span class="hx-tool__go" aria-hidden="true"><?php echo wp_kses( pixva_icon( 'arrow' ), pixva_svg_allowed() ); ?></span>
			</a>
		</section>

		<?php pixva_notice( 'info', __( 'نتیجه ابزارها راهنمای اولیه است و جای بررسی حضوری کارشناس را نمی‌گیرد. اگر بوی سوختگی، دود یا جرقه دیدید، دستگاه را از برق بکشید.', 'pixva' ) ); ?>
	</div>
	<div class="container section--tight"><?php pixva_cta_box( '', '', 'tools_hub' ); ?></div>
</main>
<?php
get_footer();

<?php
/**
 * قالب ۴۰۴ — نسخه ۴٫۰٫۰ (Bento)
 *
 * جایگاه 404 با Theme Builder المنتور قابل بازنویسی است.
 *
 * @package Pixva
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

if ( ! function_exists( 'pixva_elementor_location' ) || ! pixva_elementor_location( '404' ) ) :
?>

<main id="content" class="bx-page">
	<div class="bx-wrap">
		<div class="bx-surface bx-empty bx-reveal">
			<span class="bx-empty__icon"><?php echo pixva_bento_icons( 'search' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
			<h1 class="bx-page__title"><?php esc_html_e( 'صفحه‌ای که دنبالش بودید پیدا نشد', 'pixva' ); ?></h1>
			<p><?php esc_html_e( 'نشانی را اشتباه وارد کرده‌اید یا صفحه جابه‌جا شده است. از جستجو یا میان‌برهای زیر به مسیر درست برگردید.', 'pixva' ); ?></p>
			<?php get_search_form(); ?>
			<div class="bx-brands bx-u-gap">
				<?php
				if ( function_exists( 'pixva_hubs' ) ) :
					foreach ( pixva_hubs() as $pixva_hub ) :
						?>
						<a class="bx-brand-chip" href="<?php echo esc_url( pixva_hub_url( $pixva_hub['slug'] ) ); ?>">
							<?php echo esc_html( $pixva_hub['short'] ); ?>
						</a>
					<?php endforeach; ?>
				<?php endif; ?>
			</div>
			<a class="bx-btn bx-btn--primary" href="<?php echo esc_url( home_url( '/' ) ); ?>">
				<?php echo pixva_bento_icons( 'sparkle' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<span><?php esc_html_e( 'بازگشت به صفحه اصلی', 'pixva' ); ?></span>
			</a>
		</div>
	</div>
</main>

<?php
endif;

get_footer();

<?php
/**
 * قالب نوشته یگانه — نسخه ۴٫۰٫۰ (Bento)
 *
 * پوسته تازه: سربرگ متمرکز با تراشه دسته و متادیتا، مقاله روی سطح سفید
 * سایه‌دار و باند CTA پایانی. جایگاه single با Theme Builder المنتور
 * قابل بازنویسی است.
 *
 * @package Pixva
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

if ( ! function_exists( 'pixva_elementor_location' ) || ! pixva_elementor_location( 'single' ) ) :
?>

<main id="content" class="bx-page">
	<?php
	while ( have_posts() ) :
		the_post();
		$pixva_cats = get_the_category();
		?>
		<div class="bx-wrap">
			<header class="bx-page__head">
				<?php if ( ! empty( $pixva_cats ) ) : ?>
					<a class="bx-chip" href="<?php echo esc_url( get_category_link( $pixva_cats[0] ) ); ?>"><?php echo esc_html( $pixva_cats[0]->name ); ?></a>
				<?php endif; ?>
				<h1 class="bx-page__title"><?php the_title(); ?></h1>
				<p class="bx-page__meta">
					<span><?php echo pixva_bento_icons( 'clock' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php echo esc_html( get_the_date() ); ?></span>
					<span><?php echo pixva_bento_icons( 'tech' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php the_author(); ?></span>
					<?php if ( function_exists( 'pixva_reading_time_label' ) && pixva_reading_time_label() ) : ?>
						<span><?php echo pixva_bento_icons( 'book' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php echo esc_html( pixva_reading_time_label() ); ?></span>
					<?php endif; ?>
				</p>
			</header>

			<article <?php post_class( 'bx-article' ); ?>>
				<?php
				if ( has_post_thumbnail() ) {
					the_post_thumbnail( 'pixva-wide' );
				}
				the_content();
				wp_link_pages();
				?>
			</article>

			<aside class="bx-obsidian bx-article-cta">
				<p><?php echo esc_html( (string) pixva_option( 'pixva_single_cta_text', __( 'نیاز به تشخیص فوری دارید؟ تکنسین امروز اعزام می‌شود.', 'pixva' ) ) ); ?></p>
				<span class="bx-closer__actions">
					<a class="bx-btn bx-btn--accent bx-btn--sm" href="<?php echo esc_url( pixva_page_url( 'calculator' ) ); ?>">
						<?php echo pixva_bento_icons( 'bolt' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<span><?php esc_html_e( 'ثبت درخواست تعمیر', 'pixva' ); ?></span>
					</a>
					<?php $pixva_phone = function_exists( 'pixva_support_phone' ) ? pixva_support_phone() : ''; ?>
					<?php if ( '' !== $pixva_phone ) : ?>
						<a class="bx-btn bx-btn--on-dark bx-btn--sm" href="<?php echo esc_url( pixva_tel_href( $pixva_phone ) ); ?>">
							<?php echo pixva_bento_icons( 'phone' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							<span><?php echo esc_html( pixva_fa_num( $pixva_phone ) ); ?></span>
						</a>
					<?php endif; ?>
				</span>
			</aside>

			<?php
			if ( comments_open() || get_comments_number() ) {
				comments_template();
			}
			?>
		</div>
	<?php endwhile; ?>
</main>

<?php
endif;

get_footer();

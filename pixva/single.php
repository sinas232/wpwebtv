<?php
/**
 * مقاله وبلاگ: مشخصات عیب‌یابی، فهرست، CTA، FAQ و مطالب مرتبط
 *
 * @package Pixva
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>
<main id="content">
	<?php
	while ( have_posts() ) :
		the_post();
		pixva_page_hero( get_the_title(), has_excerpt() ? get_the_excerpt() : '' );
		?>
		<div class="pixva-container pixva-content pixva-layout pixva-layout--post">
		<article <?php post_class( 'pixva-entry' ); ?>>
			<?php pixva_entry_meta( get_the_ID() ); ?>
			<?php pixva_share_bar( get_the_ID() ); ?>
			<?php pixva_diagnostics_box( get_the_ID() ); ?>
			<?php
			$content = apply_filters( 'the_content', get_the_content() );
			echo pixva_build_toc( $content ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo pixva_inject_cta( $content ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			wp_link_pages(
				array(
					'before' => '<nav class="pixva-pagination" aria-label="' . esc_attr__( 'صفحات مطلب', 'pixva' ) . '">',
					'after'  => '</nav>',
				)
			);
			?>
			<footer class="pixva-entry-footer">
				<?php the_tags( '', '', '' ); ?>
				<?php pixva_share_bar( get_the_ID() ); ?>
			</footer>
			<?php
			$faq = pixva_current_faq_items();
			if ( ! empty( $faq ) ) :
				?>
				<section class="pixva-related">
					<h2><?php esc_html_e( 'سوالات متداول همین خرابی', 'pixva' ); ?></h2>
					<?php pixva_render_faq( $faq, 'post-faq' ); ?>
				</section>
			<?php endif; ?>
			<?php pixva_author_box(); ?>
			<?php pixva_related_posts( get_the_ID() ); ?>
			<?php
			if ( comments_open() || get_comments_number() ) {
				comments_template();
			}
			?>
		</article>
		<?php get_sidebar(); ?>
		</div>
	<?php endwhile; ?>
</main>
<?php
get_footer();

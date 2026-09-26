<?php
/**
 * برگه عمومی
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
		<div class="pixva-container pixva-content pixva-layout pixva-layout--page">
		<article <?php post_class( 'pixva-entry entry-content' ); ?>>
			<?php the_content(); ?>
			<?php
			wp_link_pages(
				array(
					'before' => '<nav class="pixva-pagination">',
					'after'  => '</nav>',
				)
			);
			?>
		</article>
		<?php get_sidebar(); ?>
		</div>
	<?php endwhile; ?>
</main>
<?php
get_footer();

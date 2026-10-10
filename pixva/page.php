<?php
/**
 * Default page.
 *
 * @package Pixva
 * @since   2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
get_header();
while ( have_posts() ) :
	the_post();
	?>
	<main id="main" class="site-main">
		<?php pixva_page_header( get_the_title(), has_excerpt() ? get_the_excerpt() : '' ); ?>
		<?php
		// Elementor documents own their layout/width (containers + widget sections);
		// the classic narrow wrapper (container--narrow + entry-content 75ch) would
		// clip them to reading width.
		$pixva_is_el_page = get_post_meta( get_the_ID(), '_elementor_edit_mode', true ) === 'builder';
		if ( $pixva_is_el_page ) {
			the_content();
			wp_link_pages();
			if ( comments_open() || get_comments_number() ) {
				comments_template();
			}
		} else {
			?>
			<div class="container container--narrow section">
				<div class="entry-content"><?php the_content(); ?></div>
				<?php wp_link_pages(); ?>
				<?php if ( comments_open() || get_comments_number() ) : ?>
					<?php comments_template(); ?>
				<?php endif; ?>
			</div>
			<?php
		}
		?>
	</main>
	<?php
endwhile;
get_footer();

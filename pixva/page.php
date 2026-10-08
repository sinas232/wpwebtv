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
		<div class="container container--narrow section">
			<div class="entry-content"><?php the_content(); ?></div>
			<?php wp_link_pages(); ?>
			<?php if ( comments_open() || get_comments_number() ) : ?>
				<?php comments_template(); ?>
			<?php endif; ?>
		</div>
	</main>
	<?php
endwhile;
get_footer();

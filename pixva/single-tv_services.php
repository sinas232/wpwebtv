<?php
/**
 * تک‌خدمت تعمیر
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
		$problem = sanitize_title( get_post_field( 'post_name' ) );
		pixva_page_hero( get_the_title(), has_excerpt() ? get_the_excerpt() : '' );
		?>
		<div class="pixva-container pixva-content">
			<div class="pixva-grid pixva-grid--2 pixva-split">
				<article <?php post_class( 'entry-content' ); ?>>
					<?php
					if ( has_post_thumbnail() ) {
						the_post_thumbnail( 'pixva-wide' );
					}
					the_content();
					?>
				</article>
				<aside class="pixva-aside">
					<?php
					pixva_render_calculator(
						array(
							'preset_problem' => $problem,
							'compact'        => true,
						)
					);
					?>
					<div class="pixva-sidebar pixva-sidebar--inline">
						<?php pixva_sidebar_widgets( 'services-sidebar' ); ?>
					</div>
				</aside>
			</div>
		</div>
	<?php endwhile; ?>
</main>
<?php
get_footer();

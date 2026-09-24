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
			<div class="pixva-grid pixva-grid--2" style="align-items:start">
				<article <?php post_class( 'entry-content' ); ?>>
					<?php
					if ( has_post_thumbnail() ) {
						the_post_thumbnail( 'pixva-wide' );
					}
					the_content();
					?>
				</article>
				<aside>
					<?php
					pixva_render_calculator(
						array(
							'preset_problem' => $problem,
							'compact'        => true,
						)
					);
					?>
				</aside>
			</div>
		</div>
	<?php endwhile; ?>
</main>
<?php
get_footer();

<?php
/**
 * نمونه‌کار تعمیر با مقایسه قبل و بعد
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
		$images   = pixva_case_images( get_the_ID() );
		$parts    = (string) get_post_meta( get_the_ID(), '_pixva_case_parts', true );
		$model    = (string) get_post_meta( get_the_ID(), '_pixva_case_model', true );
		$duration = (string) get_post_meta( get_the_ID(), '_pixva_case_duration', true );
		pixva_page_hero( get_the_title(), has_excerpt() ? get_the_excerpt() : '' );
		?>
		<div class="pixva-container pixva-content pixva-layout pixva-layout--case">
		<article <?php post_class( 'pixva-entry' ); ?>>
			<?php pixva_render_before_after( $images['before'], $images['after'], get_the_title() ); ?>
			<dl class="pixva-track-card pixva-case-meta">
				<?php if ( $model ) : ?>
					<dt><?php esc_html_e( 'مدل دستگاه', 'pixva' ); ?></dt><dd><?php echo esc_html( $model ); ?></dd>
				<?php endif; ?>
				<?php if ( $parts ) : ?>
					<dt><?php esc_html_e( 'قطعات تعویض‌شده', 'pixva' ); ?></dt><dd><?php echo esc_html( $parts ); ?></dd>
				<?php endif; ?>
				<?php if ( $duration ) : ?>
					<dt><?php esc_html_e( 'زمان صرف‌شده', 'pixva' ); ?></dt><dd><?php echo esc_html( $duration ); ?></dd>
				<?php endif; ?>
			</dl>
			<div class="entry-content"><?php the_content(); ?></div>
			<?php pixva_cta_box(); ?>
		</article>
		<?php get_sidebar(); ?>
		</div>
	<?php endwhile; ?>
</main>
<?php
get_footer();

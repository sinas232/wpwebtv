<?php
/**
 * فهرست مجله و fallback قالب
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
	// لایه ۳٫۰٫۰: جایگاه archive با Theme Builder المنتور قابل بازنویسی است.
	if ( ! function_exists( 'pixva_elementor_location' ) || ! pixva_elementor_location( 'archive' ) ) :
	?>
	<?php pixva_page_hero( wp_strip_all_tags( get_the_archive_title() ), wp_strip_all_tags( (string) get_the_archive_description() ) ); ?>
	<div class="pixva-container pixva-content">
		<div class="pixva-layout">
			<div class="pixva-layout__main">
				<?php if ( have_posts() ) : ?>
					<div class="pixva-grid pixva-grid--2">
						<?php
						while ( have_posts() ) :
							the_post();
							pixva_post_card();
						endwhile;
						?>
					</div>
					<?php pixva_pagination(); ?>
				<?php else : ?>
					<p class="pixva-notice pixva-notice--info"><?php esc_html_e( 'هنوز مطلبی در این فهرست نیست.', 'pixva' ); ?></p>
				<?php endif; ?>
			</div>
			<?php get_sidebar(); ?>
		</div>
	</div>
	<?php endif; /* پایان جایگاه archive — لایه ۳٫۰٫۰ */ ?>
</main>
<?php
get_footer();

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
	<?php pixva_page_hero( wp_strip_all_tags( get_the_archive_title() ), wp_strip_all_tags( (string) get_the_archive_description() ) ); ?>
	<div class="pixva-container pixva-content">
		<div class="pixva-layout">
			<div>
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
			<aside>
				<?php if ( is_active_sidebar( 'blog-sidebar' ) ) : ?>
					<?php dynamic_sidebar( 'blog-sidebar' ); ?>
				<?php else : ?>
					<section class="pixva-card pixva-widget">
						<h3 class="pixva-widget__title"><?php esc_html_e( 'جستجو در مجله', 'pixva' ); ?></h3>
						<?php get_search_form(); ?>
					</section>
					<section class="pixva-card pixva-widget">
						<h3 class="pixva-widget__title"><?php esc_html_e( 'عیب را خودتان قیمت بگیرید', 'pixva' ); ?></h3>
						<p><?php esc_html_e( 'بازه هزینه و زمان تعمیر را در چند مرحله ببینید.', 'pixva' ); ?></p>
						<a class="pixva-btn pixva-btn--cta pixva-btn--sm" href="<?php echo esc_url( pixva_page_url( 'calculator' ) ); ?>"><?php esc_html_e( 'محاسبه‌گر', 'pixva' ); ?></a>
					</section>
				<?php endif; ?>
			</aside>
		</div>
	</div>
</main>
<?php
get_footer();

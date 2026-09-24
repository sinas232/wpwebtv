<?php
/**
 * نتایج جستجو
 *
 * @package Pixva
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
$query = get_search_query();
?>
<main id="content">
	<?php
	pixva_page_hero(
		sprintf(
			/* translators: %s: عبارت جستجو */
			__( 'نتایج «%s»', 'pixva' ),
			$query
		),
		sprintf(
			/* translators: %s: تعداد نتیجه */
			__( '%s مورد پیدا شد.', 'pixva' ),
			pixva_fa_num( (string) $GLOBALS['wp_query']->found_posts )
		)
	);
	?>
	<div class="pixva-container pixva-content">
		<div class="pixva-search" style="margin-bottom:1.2rem"><?php get_search_form(); ?></div>
		<?php if ( have_posts() ) : ?>
			<?php
			while ( have_posts() ) :
				the_post();
				$pixva_type_obj = get_post_type_object( get_post_type() );
				?>
				<article <?php post_class( 'pixva-card pixva-result' ); ?>>
					<a href="<?php the_permalink(); ?>">
						<?php
						if ( has_post_thumbnail() ) {
							the_post_thumbnail( 'pixva-card' );
						} else {
							echo '<span class="pixva-result__ph"></span>';
						}
						?>
					</a>
					<div>
						<span class="pixva-kicker"><?php echo esc_html( $post_type_object ? $post_type_object->labels->singular_name : '' ); ?></span>
						<h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
						<p><?php echo esc_html( wp_trim_words( get_the_excerpt(), 28 ) ); ?></p>
					</div>
				</article>
			<?php endwhile; ?>
			<?php pixva_pagination(); ?>
		<?php else : ?>
			<div class="pixva-notice pixva-notice--info">
				<p><?php esc_html_e( 'چیزی پیدا نشد. عبارت کوتاه‌تری امتحان کنید یا مستقیم هزینه را برآورد کنید.', 'pixva' ); ?></p>
			</div>
			<a class="pixva-btn pixva-btn--primary" href="<?php echo esc_url( pixva_page_url( 'error-codes' ) ); ?>"><?php esc_html_e( 'جستجو در کدهای خطا', 'pixva' ); ?></a>
		<?php endif; ?>
	</div>
</main>
<?php
get_footer();

<?php
/**
 * Generic archive (blog categories and any other archive).
 *
 * @package Pixva
 * @since   2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
get_header();
$pixva_desc = wp_strip_all_tags( (string) get_the_archive_description() );
?>
<main id="main" class="site-main">
	<?php pixva_page_header( is_category() ? single_cat_title( '', false ) : wp_strip_all_tags( get_the_archive_title() ), $pixva_desc ); ?>
	<div class="container section ed">
		<?php if ( have_posts() ) : ?>
			<?php
			$pixva_posts = array();
			while ( have_posts() ) :
				the_post();
				$pixva_posts[] = get_post();
			endwhile;
			pixva_editorial_list( $pixva_posts );
			?>
			<?php pixva_pagination(); ?>
		<?php else : ?>
			<?php pixva_empty_state( __( 'در این بخش هنوز مطلبی منتشر نشده است', 'pixva' ), __( 'به‌محض انتشار مطالب، در این صفحه فهرست می‌شوند.', 'pixva' ), array( __( 'همه مقالات', 'pixva' ) => pixva_route_url( 'blog' ) ) ); ?>
		<?php endif; ?>
	</div>
</main>
<?php
get_footer();

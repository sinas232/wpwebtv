<?php
/**
 * Brands archive (/brands/).
 *
 * @package Pixva
 * @since   2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
get_header();
?>
<main id="main" class="site-main">
	<?php pixva_page_header( pixva_routes()['brands']['title'], pixva_route_description( 'brands' ) ); ?>
	<div class="container section">
		<?php if ( have_posts() ) : ?>
			<?php pixva_brand_wall( $GLOBALS['wp_query']->posts ); ?>
			<?php pixva_pagination(); ?>
		<?php else : ?>
			<?php pixva_empty_state( __( 'هنوز برندی ثبت نشده است', 'pixva' ), '', array( __( 'ثبت درخواست تعمیر', 'pixva' ) => pixva_route_url( 'booking' ) ) ); ?>
		<?php endif; ?>
	</div>
</main>
<?php
get_footer();

<?php
/**
 * Portfolio archive (/portfolio/).
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
	<?php pixva_page_header( pixva_routes()['portfolio']['title'], pixva_route_description( 'portfolio' ) ); ?>
	<div class="container section">
		<?php if ( have_posts() ) : ?>
			<?php
			$pixva_items = array();
			while ( have_posts() ) :
				the_post();
				$pixva_items[] = get_post();
			endwhile;
			$pixva_label = static function ( $p ) {
				$b = pixva_linked_post( $p->ID, '_pixva_case_brand_id', 'tv_brands' );
				return trim( ( $b ? get_the_title( $b ) : '' ) . ' ' . get_post_meta( $p->ID, '_pixva_case_model', true ) );
			};
			pixva_feature_card( $pixva_items[0], __( 'نمونه‌کار', 'pixva' ), pixva_format_date( get_post_time( 'U', true, $pixva_items[0] ) ), array_filter( array( $pixva_label( $pixva_items[0] ) ) ) );
			if ( count( $pixva_items ) > 1 ) :
				pixva_rows( array_slice( $pixva_items, 1 ), $pixva_label );
			endif;
			?>
			<?php pixva_pagination(); ?>
		<?php else : ?>
			<?php pixva_empty_state( __( 'هنوز نمونه‌کاری منتشر نشده است', 'pixva' ), '', array( __( 'خدمات', 'pixva' ) => pixva_route_url( 'services' ) ) ); ?>
		<?php endif; ?>
	</div>
</main>
<?php
get_footer();

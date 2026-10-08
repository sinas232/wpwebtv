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
			<div class="grid grid--cards">
				<?php
				while ( have_posts() ) :
					the_post();
					$pixva_b = pixva_linked_post( get_the_ID(), '_pixva_case_brand_id', 'tv_brands' );
					pixva_card( get_post(), trim( ( $pixva_b ? get_the_title( $pixva_b ) : '' ) . ' ' . get_post_meta( get_the_ID(), '_pixva_case_model', true ) ), 'h2' );
				endwhile;
				?>
			</div>
			<?php pixva_pagination(); ?>
		<?php else : ?>
			<?php pixva_empty_state( __( 'هنوز نمونه‌کاری منتشر نشده است', 'pixva' ), '', array( __( 'خدمات', 'pixva' ) => pixva_route_url( 'services' ) ) ); ?>
		<?php endif; ?>
	</div>
</main>
<?php
get_footer();

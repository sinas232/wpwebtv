<?php
/**
 * Services archive (/services/).
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
	<?php pixva_page_header( pixva_routes()['services']['title'], pixva_route_description( 'services' ) ); ?>
	<div class="container section">
		<?php if ( have_posts() ) : ?>
			<?php
			$pixva_items = array();
			while ( have_posts() ) :
				the_post();
				$pixva_items[] = get_post();
			endwhile;
			pixva_service_feature( $pixva_items[0] );
			if ( count( $pixva_items ) > 1 ) :
				pixva_service_rows( array_slice( $pixva_items, 1 ) );
			endif;
			?>
			<?php pixva_pagination(); ?>
		<?php else : ?>
			<?php
			pixva_empty_state(
				__( 'فهرست خدمات هنوز تکمیل نشده است', 'pixva' ),
				__( 'می‌توانید ایراد دستگاه را شرح دهید تا پس از بررسی راهنمایی شوید.', 'pixva' ),
				array(
					__( 'ثبت درخواست تعمیر', 'pixva' ) => pixva_route_url( 'booking' ),
					__( 'تشخیص آنلاین', 'pixva' )      => pixva_route_url( 'diagnosis' ),
				)
			);
			?>
		<?php endif; ?>
		<?php pixva_cta_box( '', '', 'services_archive' ); ?>
	</div>
</main>
<?php
get_footer();

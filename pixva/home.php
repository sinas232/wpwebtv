<?php
/**
 * Blog index (/blog/) with category navigation.
 *
 * @package Pixva
 * @since   2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
get_header();
$pixva_blog = get_post( (int) get_option( 'page_for_posts' ) );
?>
<main id="main" class="site-main">
	<?php pixva_page_header( $pixva_blog ? get_the_title( $pixva_blog ) : __( 'مجله', 'pixva' ), $pixva_blog && has_excerpt( $pixva_blog ) ? get_the_excerpt( $pixva_blog ) : __( 'راهنماهای عیب‌یابی، نگهداری و تعمیر تلویزیون.', 'pixva' ) ); ?>
	<div class="container section">
		<?php
		$pixva_cats = get_categories( array( 'hide_empty' => true ) );
		if ( count( $pixva_cats ) > 1 ) :
			?>
			<nav class="filter-nav" aria-label="<?php esc_attr_e( 'دسته‌بندی مقالات', 'pixva' ); ?>">
				<ul class="chips">
					<li><a class="chip" aria-current="page" href="<?php echo esc_url( pixva_route_url( 'blog' ) ); ?>"><?php esc_html_e( 'همه', 'pixva' ); ?></a></li>
					<?php foreach ( $pixva_cats as $pixva_c ) : ?>
						<li><a class="chip" href="<?php echo esc_url( get_category_link( $pixva_c ) ); ?>"><?php echo esc_html( $pixva_c->name ); ?></a></li>
					<?php endforeach; ?>
				</ul>
			</nav>
		<?php endif; ?>
		<?php if ( have_posts() ) : ?>
			<?php
			$pixva_items = array();
			while ( have_posts() ) :
				the_post();
				$pixva_items[] = get_post();
			endwhile;
			$pixva_cat = static function ( $p ) {
				$c = get_the_category( $p->ID );
				return $c ? $c[0]->name : '';
			};
			pixva_feature_card( $pixva_items[0], $pixva_cat( $pixva_items[0] ) ?: __( 'مقاله', 'pixva' ), pixva_format_date( get_post_time( 'U', true, $pixva_items[0] ) ), array_filter( array( $pixva_cat( $pixva_items[0] ) ) ) );
			if ( count( $pixva_items ) > 1 ) :
				pixva_rows( array_slice( $pixva_items, 1 ), static function ( $p ) use ( $pixva_cat ) {
					return implode( ' · ', array_filter( array( $pixva_cat( $p ), pixva_format_date( get_post_time( 'U', true, $p ) ) ) ) );
				} );
			endif;
			?>
			<?php pixva_pagination(); ?>
		<?php else : ?>
			<?php pixva_empty_state( __( 'هنوز مقاله‌ای منتشر نشده است', 'pixva' ), __( 'تا آن زمان می‌توانید از ابزارهای عیب‌یابی استفاده کنید.', 'pixva' ), array( __( 'ابزارها', 'pixva' ) => pixva_route_url( 'tools' ) ) ); ?>
		<?php endif; ?>
	</div>
</main>
<?php
get_footer();

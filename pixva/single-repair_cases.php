<?php
/**
 * Single portfolio case (/portfolio/{case}/) with before/after comparison
 * (before-after.js; both images visible without JS).
 *
 * @package Pixva
 * @since   2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
get_header();
while ( have_posts() ) :
	the_post();
	$pixva_id      = get_the_ID();
	$pixva_before  = absint( get_post_meta( $pixva_id, '_pixva_case_before', true ) );
	$pixva_after   = absint( get_post_meta( $pixva_id, '_pixva_case_after', true ) );
	$pixva_brand   = pixva_linked_post( $pixva_id, '_pixva_case_brand_id', 'tv_brands' );
	$pixva_service = pixva_linked_post( $pixva_id, '_pixva_case_service', 'tv_services' );
	$pixva_facts   = array_filter(
		array(
			__( 'برند', 'pixva' )          => $pixva_brand ? get_the_title( $pixva_brand ) : '',
			__( 'مدل', 'pixva' )           => (string) get_post_meta( $pixva_id, '_pixva_case_model', true ),
			__( 'خدمت', 'pixva' )          => $pixva_service ? get_the_title( $pixva_service ) : '',
			__( 'قطعات', 'pixva' )         => (string) get_post_meta( $pixva_id, '_pixva_case_parts', true ),
			__( 'مدت انجام کار', 'pixva' ) => (string) get_post_meta( $pixva_id, '_pixva_case_duration', true ),
		)
	);
	?>
	<main id="main" class="site-main">
		<?php pixva_page_header( get_the_title(), has_excerpt() ? get_the_excerpt() : '', __( 'نمونه‌کار', 'pixva' ) ); ?>
		<div class="container section container--narrow">
			<?php if ( $pixva_before && $pixva_after ) : ?>
				<figure class="compare" data-compare data-compare-label="<?php esc_attr_e( 'جابه‌جایی مرز تصویر قبل و بعد', 'pixva' ); ?>">
					<div class="compare__item">
					<?php
					echo wp_get_attachment_image(
						$pixva_before,
						'large',
						false,
						array(
							'alt'     => __( 'قبل از تعمیر', 'pixva' ),
							'loading' => 'eager',
						)
					);
					?>
												<span class="compare__label"><?php esc_html_e( 'قبل', 'pixva' ); ?></span></div>
					<div class="compare__item">
					<?php
					echo wp_get_attachment_image(
						$pixva_after,
						'large',
						false,
						array(
							'alt'     => __( 'بعد از تعمیر', 'pixva' ),
							'loading' => 'lazy',
						)
					);
					?>
					<span class="compare__label"><?php esc_html_e( 'بعد', 'pixva' ); ?></span></div>
					<figcaption class="screen-reader-text"><?php esc_html_e( 'مقایسه تصویر قبل و بعد از تعمیر', 'pixva' ); ?></figcaption>
				</figure>
			<?php elseif ( has_post_thumbnail() ) : ?>
				<figure class="article__media"><?php the_post_thumbnail( 'large' ); ?></figure>
			<?php endif; ?>
			<?php if ( $pixva_facts ) : ?>
				<dl class="details details--specs">
					<?php foreach ( $pixva_facts as $pixva_l => $pixva_v ) : ?>
						<dt><?php echo esc_html( $pixva_l ); ?></dt><dd dir="auto"><?php echo esc_html( $pixva_v ); ?></dd>
					<?php endforeach; ?>
				</dl>
			<?php endif; ?>
			<div class="entry-content"><?php the_content(); ?></div>
			<?php pixva_problem_chips( $pixva_id ); ?>
			<?php pixva_related_links( __( 'صفحه‌های مرتبط', 'pixva' ), array_filter( array( $pixva_service, $pixva_brand ) ) ); ?>
			<?php pixva_cta_box( '', '', 'case' ); ?>
		</div>
	</main>
	<?php
endwhile;
get_footer();

<?php
/**
 * Single model (/brands/{brand}/{model}/). Wrong-brand URLs 301 to the
 * canonical path (content-model.php).
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
	$pixva_id     = get_the_ID();
	$pixva_brand  = pixva_model_brand( $pixva_id );
	$pixva_specs  = array_filter(
		array(
			__( 'کد مدل', 'pixva' )     => (string) get_post_meta( $pixva_id, '_pixva_model_code', true ),
			__( 'اندازه', 'pixva' )     => ( (int) get_post_meta( $pixva_id, '_pixva_model_size', true ) ) ? sprintf( /* translators: %s: inches. */ __( '%s اینچ', 'pixva' ), pixva_fa_num( (int) get_post_meta( $pixva_id, '_pixva_model_size', true ) ) ) : '',
			__( 'فناوری پنل', 'pixva' ) => pixva_panel_types()[ (string) get_post_meta( $pixva_id, '_pixva_model_panel', true ) ] ?? '',
			__( 'سال عرضه', 'pixva' )   => ( (int) get_post_meta( $pixva_id, '_pixva_model_year', true ) ) ? pixva_fa_num( (int) get_post_meta( $pixva_id, '_pixva_model_year', true ) ) : '',
		)
	);
	$pixva_issues = pixva_meta_lines( $pixva_id, '_pixva_model_issues' );
	$pixva_errors = get_posts(
		array(
			'post_type'      => 'pixva_error',
			'post_status'    => 'publish',
			'posts_per_page' => 12,
			'no_found_rows'  => true,
			'meta_query'     => array(
				array(
					'key'     => '_pixva_err_models',
					'value'   => '(^|,)' . $pixva_id . '(,|$)',
					'compare' => 'REGEXP',
				),
			), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
		)
	);
	?>
	<main id="main" class="site-main">
		<?php pixva_page_header( get_the_title(), has_excerpt() ? get_the_excerpt() : '', $pixva_brand ? get_the_title( $pixva_brand ) : '' ); ?>
		<div class="container section layout-aside">
			<div class="layout-aside__main">
				<?php if ( $pixva_specs ) : ?>
					<dl class="details details--specs">
						<?php foreach ( $pixva_specs as $pixva_label => $pixva_value ) : ?>
							<dt><?php echo esc_html( $pixva_label ); ?></dt><dd dir="auto"><?php echo esc_html( $pixva_value ); ?></dd>
						<?php endforeach; ?>
					</dl>
				<?php endif; ?>
				<div class="entry-content"><?php the_content(); ?></div>
				<?php if ( $pixva_issues ) : ?>
					<section aria-labelledby="model-issues"><h2 id="model-issues"><?php esc_html_e( 'ایرادهای مستند این مدل', 'pixva' ); ?></h2><?php pixva_list( $pixva_issues ); ?></section>
				<?php endif; ?>
				<?php pixva_related_links( __( 'کدهای خطای این مدل', 'pixva' ), $pixva_errors ); ?>
				<?php if ( $pixva_brand ) : ?>
					<?php pixva_related_links( __( 'صفحه برند', 'pixva' ), array( $pixva_brand ) ); ?>
				<?php endif; ?>
			</div>
			<aside class="layout-aside__side">
				<div class="panel">
					<h2 class="panel__title"><?php esc_html_e( 'ایراد دارد؟', 'pixva' ); ?></h2>
					<a class="btn btn--primary btn--block" href="
					<?php
					echo esc_url(
						pixva_route_url(
							'diagnosis',
							array_filter(
								array(
									'brand' => $pixva_brand ? (string) $pixva_brand->ID : '',
									'model' => (string) get_post_meta( $pixva_id, '_pixva_model_code', true ),
									'step'  => '1',
								)
							)
						)
					);
					?>
																	"><?php esc_html_e( 'تشخیص آنلاین', 'pixva' ); ?></a>
					<a class="btn btn--accent btn--block" data-track="cta_click" data-track-label="booking" data-track-location="model" href="
					<?php
					echo esc_url(
						pixva_route_url(
							'booking',
							array_filter(
								array(
									'brand' => $pixva_brand ? (string) $pixva_brand->ID : '',
									'model' => (string) get_post_meta( $pixva_id, '_pixva_model_code', true ),
								)
							)
						)
					);
					?>
																																				"><?php esc_html_e( 'ثبت درخواست تعمیر', 'pixva' ); ?></a>
				</div>
			</aside>
		</div>
	</main>
	<?php
endwhile;
get_footer();

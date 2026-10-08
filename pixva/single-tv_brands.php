<?php
/**
 * Single brand (/brands/{brand}/): models, error codes, cases, articles.
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
	$pixva_models = get_posts(
		array(
			'post_type'      => 'tv_model',
			'post_status'    => 'publish',
			'posts_per_page' => 100,
			'orderby'        => 'title',
			'order'          => 'ASC',
			'meta_key'       => '_pixva_brand_id',
			'meta_value'     => $pixva_id,
			'no_found_rows'  => true,
		)
	); // phpcs:ignore WordPress.DB.SlowDBQuery
	$pixva_errors = pixva_posts_by_meta(
		'pixva_error',
		'_pixva_err_brand_id',
		$pixva_id,
		12,
		array(
			'orderby' => 'title',
			'order'   => 'ASC',
		)
	);
	$pixva_en     = (string) get_post_meta( $pixva_id, '_pixva_brand_en', true );
	?>
	<main id="main" class="site-main">
		<?php pixva_page_header( get_the_title(), has_excerpt() ? get_the_excerpt() : '', '' !== $pixva_en ? $pixva_en : __( 'برند', 'pixva' ) ); ?>
		<div class="container section">
			<div class="entry-content container--narrow"><?php the_content(); ?></div>

			<?php if ( $pixva_models ) : ?>
				<section class="section--tight" aria-labelledby="brand-models">
					<h2 id="brand-models"><?php esc_html_e( 'مدل‌ها', 'pixva' ); ?></h2>
					<ul class="chips">
						<?php foreach ( $pixva_models as $pixva_m ) : ?>
							<li><a class="chip" href="<?php echo esc_url( get_permalink( $pixva_m ) ); ?>" dir="auto"><?php echo esc_html( get_the_title( $pixva_m ) ); ?></a></li>
						<?php endforeach; ?>
					</ul>
				</section>
			<?php endif; ?>

			<?php if ( $pixva_errors ) : ?>
				<section class="section--tight" aria-labelledby="brand-errors">
					<h2 id="brand-errors"><?php echo esc_html( sprintf( /* translators: %s: brand. */ __( 'کدهای خطای %s', 'pixva' ), get_the_title() ) ); ?></h2>
					<ul class="link-list">
						<?php foreach ( $pixva_errors as $pixva_e ) : ?>
							<li><a href="<?php echo esc_url( get_permalink( $pixva_e ) ); ?>"><?php echo esc_html( get_the_title( $pixva_e ) ); ?></a></li>
						<?php endforeach; ?>
					</ul>
					<p><a class="link-more" href="<?php echo esc_url( pixva_route_url( 'error_codes', array( 'brand' => (string) $pixva_id ) ) ); ?>"><?php esc_html_e( 'جست‌وجو در کدهای خطای این برند', 'pixva' ); ?></a></p>
				</section>
			<?php endif; ?>

			<?php
			pixva_related_links( __( 'نمونه‌کارها', 'pixva' ), pixva_posts_by_meta( 'repair_cases', '_pixva_case_brand_id', $pixva_id, 4 ) );
			pixva_related_links( __( 'مقاله‌های مرتبط', 'pixva' ), pixva_posts_by_meta( 'post', '_pixva_post_brand', $pixva_id, 6 ) );
			?>
			<div class="panel panel--inline">
				<p><?php echo esc_html( sprintf( /* translators: %s: brand. */ __( 'تلویزیون %s شما ایراد دارد؟', 'pixva' ), trim( preg_replace( '/^تعمیر\s+(تلویزیون\s+)?/u', '', get_the_title() ) ) ) ); ?></p>
				<a class="btn btn--primary" href="
				<?php
				echo esc_url(
					pixva_route_url(
						'diagnosis',
						array(
							'brand' => (string) $pixva_id,
							'step'  => '1',
						)
					)
				);
				?>
													"><?php esc_html_e( 'تشخیص آنلاین', 'pixva' ); ?></a>
				<a class="btn btn--accent" data-track="cta_click" data-track-label="booking" data-track-location="brand" href="<?php echo esc_url( pixva_route_url( 'booking', array( 'brand' => (string) $pixva_id ) ) ); ?>"><?php esc_html_e( 'ثبت درخواست تعمیر', 'pixva' ); ?></a>
			</div>
		</div>
	</main>
	<?php
endwhile;
get_footer();

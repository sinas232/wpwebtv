<?php
/**
 * صفحه اختصاصی برند
 *
 * الگوی چشمک همان برند و مقاله‌های مرتبط را هم نشان می‌دهد.
 *
 * @package Pixva
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>
<main id="content">
	<?php
	while ( have_posts() ) :
		the_post();
		$slug      = sanitize_title( get_post_field( 'post_name' ) );
		$catalog   = function_exists( 'pixva_brand_catalog' ) ? pixva_brand_catalog() : array();
		$brand_fa  = isset( $catalog[ $slug ] ) ? $brand_fa : get_the_title();
		$brand_en  = isset( $catalog[ $slug ] ) ? $catalog[ $slug ]['en'] : '';
		$err_rows  = array();
		if ( function_exists( 'pixva_error_code_catalog' ) ) {
			foreach ( pixva_error_code_catalog() as $row ) {
				if ( isset( $row['brand'] ) && $slug === $row['brand'] ) {
					$err_rows[] = $row;
				}
			}
		}
		$brand_posts = get_posts(
			array(
				'post_type'      => 'post',
				'posts_per_page' => 3,
				'no_found_rows'  => true,
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- سه مطلب برای صفحه برند.
				'meta_query'     => array(
					array(
						'key'     => '_pixva_brand',
						'value'   => $brand_fa,
						'compare' => 'LIKE',
					),
				),
			)
		);
		pixva_page_hero( get_the_title(), has_excerpt() ? get_the_excerpt() : __( 'عیب‌های شایع، الگوی چشمک و مسیر تعمیر این برند در کارگاه پیکسوا.', 'pixva' ) );
		?>
		<div class="pixva-container pixva-content">
			<div class="pixva-grid pixva-grid--2" style="align-items:start">
				<article <?php post_class( 'entry-content' ); ?>>
					<?php
					if ( has_post_thumbnail() ) {
						the_post_thumbnail( 'pixva-wide' );
					}
					the_content();
					?>
					<?php if ( ! empty( $err_rows ) ) : ?>
						<section class="pixva-brand-errors" aria-label="<?php esc_attr_e( 'الگوی چشمک این برند', 'pixva' ); ?>">
							<div class="pixva-section-head">
								<h2><?php printf( esc_html__( 'الگوی چشمک %s', 'pixva' ), esc_html( $brand_fa ) ); ?></h2>
								<p><?php esc_html_e( 'تعداد چشمک چراغ پاور را بشمار و ردیف همان الگو را بخوان؛ راهنمای کارگاهی است نه سرویس‌منوال رسمی.', 'pixva' ); ?></p>
							</div>
							<div class="pixva-errors__grid">
								<?php foreach ( $err_rows as $row ) : ?>
									<article class="pixva-card pixva-error-card">
										<header>
											<span class="pixva-badge"><?php echo esc_html( $brand_fa ); ?></span>
											<strong class="pixva-latin pixva-error-code__code"><?php echo esc_html( $row['code'] ); ?></strong>
										</header>
										<h3><?php echo esc_html( $row['title'] ); ?></h3>
										<p><?php echo esc_html( $row['symptom'] ); ?></p>
										<p class="pixva-error-card__action"><span><?php esc_html_e( 'اقدام کارگاه', 'pixva' ); ?></span> <?php echo esc_html( $row['action'] ); ?></p>
										<?php if ( $brand_en ) : ?>
											<p class="pixva-latin pixva-muted"><?php echo esc_html( $brand_en ); ?></p>
										<?php endif; ?>
									</article>
								<?php endforeach; ?>
							</div>
						</section>
					<?php endif; ?>
					<?php if ( ! empty( $brand_posts ) ) : ?>
						<section class="pixva-brand-articles" aria-label="<?php esc_attr_e( 'مقاله‌های این برند', 'pixva' ); ?>">
							<div class="pixva-section-head">
								<h2><?php printf( esc_html__( 'از مجله: تجربه‌های %s', 'pixva' ), esc_html( $brand_fa ) ); ?></h2>
							</div>
							<div class="pixva-grid pixva-grid--3">
								<?php
								foreach ( $brand_posts as $brand_post ) {
									pixva_post_card( $brand_post->ID );
								}
								wp_reset_postdata();
								?>
							</div>
						</section>
					<?php endif; ?>
					<p><a class="pixva-btn pixva-btn--ghost pixva-btn--sm" href="<?php echo esc_url( pixva_page_url( 'error-codes' ) ); ?>"><?php esc_html_e( 'دیدن کدهای خطای همه برندها', 'pixva' ); ?></a></p>
				</article>
				<aside>
					<?php
					pixva_render_calculator(
						array(
							'preset_brand' => $slug,
							'compact'      => true,
						)
					);
					?>
				</aside>
			</div>
		</div>
	<?php endwhile; ?>
</main>
<?php
get_footer();

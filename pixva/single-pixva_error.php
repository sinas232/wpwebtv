<?php
/**
 * Single error code (/error-codes/{code}/), §09 fields. Safe checks are
 * editor-entered and framed with a fixed safety notice.
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
	$pixva_id       = get_the_ID();
	$pixva_code     = (string) get_post_meta( $pixva_id, '_pixva_err_code', true );
	$pixva_brand    = pixva_linked_post( $pixva_id, '_pixva_err_brand_id', 'tv_brands' );
	$pixva_models   = pixva_meta_ids( $pixva_id, '_pixva_err_models' );
	$pixva_meaning  = (string) get_post_meta( $pixva_id, '_pixva_err_meaning', true );
	$pixva_symptoms = pixva_meta_lines( $pixva_id, '_pixva_err_symptoms' );
	$pixva_causes   = pixva_meta_lines( $pixva_id, '_pixva_err_causes' );
	$pixva_sev      = (string) get_post_meta( $pixva_id, '_pixva_err_severity', true );
	$pixva_safe     = pixva_meta_lines( $pixva_id, '_pixva_err_safe' );
	$pixva_pro      = (string) get_post_meta( $pixva_id, '_pixva_err_pro', true );
	$pixva_service  = pixva_linked_post( $pixva_id, '_pixva_err_service', 'tv_services' );
	$pixva_article  = pixva_linked_post( $pixva_id, '_pixva_err_article', 'post' );
	?>
	<main id="main" class="site-main">
		<?php pixva_page_header( get_the_title(), '', $pixva_brand ? get_the_title( $pixva_brand ) : __( 'کد خطا', 'pixva' ) ); ?>
		<div class="container section layout-aside">
			<article class="layout-aside__main">
				<div class="error-head">
					<?php if ( '' !== $pixva_code ) : ?>
						<p class="code code--lg" dir="auto"><?php echo esc_html( $pixva_code ); ?></p>
					<?php endif; ?>
					<?php pixva_severity_badge( $pixva_sev ); ?>
				</div>
				<?php if ( '' !== $pixva_meaning ) : ?>
					<section aria-labelledby="err-meaning"><h2 id="err-meaning"><?php esc_html_e( 'معنی', 'pixva' ); ?></h2><p><?php echo esc_html( $pixva_meaning ); ?></p></section>
				<?php endif; ?>
				<?php if ( $pixva_symptoms ) : ?>
					<section aria-labelledby="err-symptoms"><h2 id="err-symptoms"><?php esc_html_e( 'نشانه‌ها', 'pixva' ); ?></h2><?php pixva_list( $pixva_symptoms ); ?></section>
				<?php endif; ?>
				<?php if ( $pixva_causes ) : ?>
					<section aria-labelledby="err-causes"><h2 id="err-causes"><?php esc_html_e( 'علت‌های محتمل', 'pixva' ); ?></h2><?php pixva_list( $pixva_causes ); ?></section>
				<?php endif; ?>
				<section aria-labelledby="err-safe">
					<h2 id="err-safe"><?php esc_html_e( 'بررسی‌های ایمن', 'pixva' ); ?></h2>
					<?php pixva_notice( 'warning', __( 'فقط کارهای زیر را انجام دهید. قاب دستگاه را باز نکنید و به قطعات داخلی دست نزنید؛ برد تغذیه حتی پس از کشیدن دوشاخه برق‌دار می‌ماند.', 'pixva' ) ); ?>
					<?php if ( $pixva_safe ) : ?>
						<?php pixva_list( $pixva_safe, 'checklist' ); ?>
					<?php else : ?>
						<?php pixva_list( array( __( 'دوشاخه را یک دقیقه از برق بکشید و دوباره وصل کنید.', 'pixva' ), __( 'تعداد چشمک یا متن خطا را دقیق یادداشت کنید.', 'pixva' ), __( 'اگر بوی سوختگی، دود یا صدای جرقه حس کردید، دستگاه را از برق بکشید و روشن نکنید.', 'pixva' ) ), 'checklist' ); ?>
					<?php endif; ?>
				</section>
				<?php if ( '' !== $pixva_pro ) : ?>
					<section aria-labelledby="err-pro"><h2 id="err-pro"><?php esc_html_e( 'اقدام تخصصی', 'pixva' ); ?></h2><p><?php echo esc_html( $pixva_pro ); ?></p></section>
				<?php endif; ?>
				<div class="entry-content"><?php the_content(); ?></div>
				<?php
				pixva_related_links( __( 'مدل‌های مرتبط', 'pixva' ), $pixva_models );
				pixva_related_links( __( 'خدمت و مقاله مرتبط', 'pixva' ), array_filter( array( $pixva_service, $pixva_article ) ) );
				if ( $pixva_brand ) {
					pixva_related_links(
						sprintf( /* translators: %s: brand. */ __( 'کدهای دیگر %s', 'pixva' ), get_the_title( $pixva_brand ) ),
						pixva_posts_by_meta(
							'pixva_error',
							'_pixva_err_brand_id',
							$pixva_brand->ID,
							6,
							array(
								'post__not_in' => array( $pixva_id ),
								'orderby'      => 'title',
								'order'        => 'ASC',
							)
						)
					);
				}
				?>
			</article>
			<aside class="layout-aside__side">
				<div class="panel">
					<h2 class="panel__title"><?php esc_html_e( 'قدم بعدی', 'pixva' ); ?></h2>
					<a class="btn btn--accent btn--block" data-track="cta_click" data-track-label="booking" data-track-location="error_code" href="
					<?php
					echo esc_url(
						pixva_route_url(
							'booking',
							array_filter(
								array(
									'problem' => 'blink',
									'brand'   => $pixva_brand ? (string) $pixva_brand->ID : '',
								)
							)
						)
					);
					?>
																																					"><?php esc_html_e( 'ثبت درخواست بررسی', 'pixva' ); ?></a>
					<a class="btn btn--ghost btn--block" href="<?php echo esc_url( pixva_route_url( 'error_codes', $pixva_brand ? array( 'brand' => (string) $pixva_brand->ID ) : array() ) ); ?>"><?php esc_html_e( 'بازگشت به کدهای خطا', 'pixva' ); ?></a>
				</div>
			</aside>
		</div>
	</main>
	<?php
endwhile;
get_footer();

<?php
/**
 * Problem hub (/problems/{problem}/): description, symptoms, safe checks,
 * diagnosis entry, related services/error codes/cases/articles.
 *
 * @package Pixva
 * @since   2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
get_header();
$pixva_term     = get_queried_object();
$pixva_symptoms = pixva_term_lines( $pixva_term->term_id, '_pixva_problem_symptoms' );
$pixva_safe     = pixva_term_lines( $pixva_term->term_id, '_pixva_problem_safe' );
$pixva_diag     = (string) get_term_meta( $pixva_term->term_id, '_pixva_problem_diag', true );
$pixva_svc_id   = absint( get_term_meta( $pixva_term->term_id, '_pixva_problem_service', true ) );
$pixva_service  = ( $pixva_svc_id && 'publish' === get_post_status( $pixva_svc_id ) ) ? get_post( $pixva_svc_id ) : null;
$pixva_tax      = array(
	array(
		'taxonomy' => 'tv_problem',
		'terms'    => $pixva_term->term_id,
	),
);
$pixva_q        = static function ( $type, $n ) use ( $pixva_tax ) {
	return get_posts(
		array(
			'post_type'      => $type,
			'post_status'    => 'publish',
			'posts_per_page' => $n,
			'no_found_rows'  => true,
			'tax_query'      => $pixva_tax,
		)
	); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
};
$pixva_diag_url = pixva_route_url(
	'diagnosis',
	array_filter(
		array(
			'problem' => isset( pixva_diagnosis_problems()[ $pixva_diag ] ) ? $pixva_diag : '',
			'step'    => '1',
		)
	)
);
?>
<main id="main" class="site-main">
	<?php pixva_page_header( single_term_title( '', false ), '', __( 'مشکل تلویزیون', 'pixva' ) ); ?>
	<div class="container section layout-aside">
		<div class="layout-aside__main">
			<?php if ( '' !== trim( $pixva_term->description ) ) : ?>
				<div class="entry-content"><?php echo wp_kses_post( wpautop( $pixva_term->description ) ); ?></div>
			<?php endif; ?>
			<?php if ( $pixva_symptoms ) : ?>
				<section aria-labelledby="pb-symptoms"><h2 id="pb-symptoms"><?php esc_html_e( 'نشانه‌ها', 'pixva' ); ?></h2><?php pixva_list( $pixva_symptoms ); ?></section>
			<?php endif; ?>
			<?php if ( $pixva_safe ) : ?>
				<section aria-labelledby="pb-safe"><h2 id="pb-safe"><?php esc_html_e( 'بررسی‌های ایمن پیش از تماس', 'pixva' ); ?></h2>
					<?php pixva_notice( 'warning', __( 'قاب دستگاه را باز نکنید؛ برد تغذیه حتی پس از کشیدن دوشاخه برق‌دار می‌ماند.', 'pixva' ) ); ?>
					<?php pixva_list( $pixva_safe, 'checklist' ); ?>
				</section>
			<?php endif; ?>
			<?php
			pixva_related_links(
				__( 'خدمات مرتبط', 'pixva' ),
				array_filter(
					array_merge( $pixva_service ? array( $pixva_service ) : array(), $pixva_q( 'tv_services', 6 ) ),
					static function ( $p ) {
						static $seen = array();
						if ( isset( $seen[ $p->ID ] ) ) {
							return false;
						}
						$seen[ $p->ID ] = true;
						return true;
					}
				)
			);
			pixva_related_links( __( 'کدهای خطای مرتبط', 'pixva' ), $pixva_q( 'pixva_error', 8 ) );
			pixva_related_links( __( 'نمونه‌کارها', 'pixva' ), $pixva_q( 'repair_cases', 4 ) );
			?>
			<?php if ( have_posts() ) : ?>
				<section class="section--tight" aria-labelledby="pb-posts"><h2 id="pb-posts"><?php esc_html_e( 'مطالب این موضوع', 'pixva' ); ?></h2>
					<div class="grid grid--cards">
						<?php
						while ( have_posts() ) :
							the_post();
							pixva_card( get_post() );
						endwhile;
						?>
					</div>
					<?php pixva_pagination(); ?>
				</section>
			<?php endif; ?>
		</div>
		<aside class="layout-aside__side">
			<div class="panel">
				<h2 class="panel__title"><?php esc_html_e( 'علت را پیدا کنید', 'pixva' ); ?></h2>
				<p><?php esc_html_e( 'با چند پرسش درباره نشانه‌ها، علت‌های محتمل را ببینید.', 'pixva' ); ?></p>
				<a class="btn btn--primary btn--block" href="<?php echo esc_url( $pixva_diag_url ); ?>"><?php esc_html_e( 'شروع تشخیص', 'pixva' ); ?></a>
				<a class="btn btn--accent btn--block" data-track="cta_click" data-track-label="booking" data-track-location="problem" href="<?php echo esc_url( pixva_route_url( 'booking', isset( pixva_diagnosis_problems()[ $pixva_diag ] ) ? array( 'problem' => $pixva_diag ) : array() ) ); ?>"><?php esc_html_e( 'ثبت درخواست تعمیر', 'pixva' ); ?></a>
			</div>
		</aside>
	</div>
</main>
<?php
get_footer();

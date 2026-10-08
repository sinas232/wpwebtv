<?php
/**
 * Single service (/services/{service}/): symptoms, process, honest price
 * state (configured range or "after inspection"), FAQ, internal links.
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
	$pixva_symptoms = pixva_meta_lines( $pixva_id, '_pixva_service_symptoms' );
	$pixva_process  = pixva_meta_lines( $pixva_id, '_pixva_service_process' );
	$pixva_time     = (string) get_post_meta( $pixva_id, '_pixva_service_time', true );
	$pixva_faq      = pixva_meta_faq_pairs( $pixva_id, '_pixva_service_faq' );
	$pixva_pkey     = (string) get_post_meta( $pixva_id, '_pixva_service_pricing', true );
	$pixva_est      = '' !== $pixva_pkey ? pixva_estimate( $pixva_pkey ) : null;
	$pixva_problems = pixva_post_problem_ids( $pixva_id );
	?>
	<main id="main" class="site-main">
		<?php pixva_page_header( get_the_title(), has_excerpt() ? get_the_excerpt() : '', __( 'خدمت تعمیر', 'pixva' ) ); ?>
		<div class="container section layout-aside">
			<div class="layout-aside__main">
				<?php if ( has_post_thumbnail() ) : ?>
					<figure class="article__media">
					<?php
					the_post_thumbnail(
						'large',
						array(
							'loading'  => 'eager',
							'decoding' => 'async',
						)
					);
					?>
													</figure>
				<?php endif; ?>
				<?php if ( $pixva_symptoms ) : ?>
					<section aria-labelledby="svc-symptoms"><h2 id="svc-symptoms"><?php esc_html_e( 'چه زمانی به این خدمت نیاز دارید؟', 'pixva' ); ?></h2><?php pixva_list( $pixva_symptoms, 'checklist' ); ?></section>
				<?php endif; ?>
				<div class="entry-content"><?php the_content(); ?></div>
				<?php if ( $pixva_process ) : ?>
					<section aria-labelledby="svc-process"><h2 id="svc-process"><?php esc_html_e( 'مراحل انجام کار', 'pixva' ); ?></h2>
						<ol class="steps steps--compact">
							<?php foreach ( $pixva_process as $pixva_step ) : ?>
								<li class="steps__item"><p><?php echo esc_html( $pixva_step ); ?></p></li>
							<?php endforeach; ?>
						</ol>
					</section>
				<?php endif; ?>
				<?php if ( $pixva_faq ) : ?>
					<section aria-labelledby="svc-faq"><h2 id="svc-faq"><?php esc_html_e( 'پرسش‌های متداول', 'pixva' ); ?></h2><?php pixva_faq_list( $pixva_faq ); ?></section>
				<?php endif; ?>
				<?php pixva_problem_chips( $pixva_id ); ?>
				<?php
				pixva_related_links( __( 'کدهای خطای مرتبط', 'pixva' ), pixva_posts_by_meta( 'pixva_error', '_pixva_err_service', $pixva_id, 8 ) );
				pixva_related_links( __( 'نمونه‌کارهای این خدمت', 'pixva' ), pixva_posts_by_meta( 'repair_cases', '_pixva_case_service', $pixva_id, 4 ) );
				pixva_related_links( __( 'مقاله‌های مرتبط', 'pixva' ), pixva_posts_by_meta( 'post', '_pixva_post_service', $pixva_id, 4 ) );
				?>
			</div>
			<aside class="layout-aside__side" aria-label="<?php esc_attr_e( 'هزینه و ثبت درخواست', 'pixva' ); ?>">
				<div class="panel">
					<h2 class="panel__title"><?php esc_html_e( 'هزینه', 'pixva' ); ?></h2>
					<?php if ( $pixva_est && $pixva_est['available'] ) : ?>
						<p class="price"><?php echo esc_html( pixva_format_range( $pixva_est['min'], $pixva_est['max'], $pixva_est['currency'] ) ); ?></p>
						<p class="field__help"><?php echo esc_html( '' !== $pixva_est['disclaimer'] ? $pixva_est['disclaimer'] : __( 'بازه تقریبی است؛ مبلغ نهایی پس از بررسی دستگاه و پیش از شروع کار اعلام می‌شود.', 'pixva' ) ); ?></p>
					<?php else : ?>
						<p><?php esc_html_e( 'هزینه این خدمت به مدل و میزان خرابی بستگی دارد و پس از کارشناسی، پیش از شروع کار اعلام می‌شود.', 'pixva' ); ?></p>
					<?php endif; ?>
					<?php if ( pixva_has_claim( 'inspection_fee' ) ) : ?>
						<p class="field__help"><?php echo esc_html( sprintf( /* translators: %s: fee note. */ __( 'هزینه کارشناسی: %s', 'pixva' ), (string) pixva_claim( 'inspection_fee' ) ) ); ?></p>
					<?php endif; ?>
					<?php if ( '' !== $pixva_time ) : ?>
						<p><?php echo wp_kses( pixva_icon( 'clock' ), pixva_svg_allowed() ); ?> <?php echo esc_html( $pixva_time ); ?></p>
					<?php endif; ?>
					<a class="btn btn--accent btn--block" data-track="cta_click" data-track-label="booking" data-track-location="service" href="<?php echo esc_url( pixva_route_url( 'booking', array( 'service' => get_post_field( 'post_name' ) ) ) ); ?>"><?php esc_html_e( 'ثبت درخواست این خدمت', 'pixva' ); ?></a>
					<a class="btn btn--ghost btn--block" href="<?php echo esc_url( pixva_route_url( 'diagnosis' ) ); ?>"><?php esc_html_e( 'مطمئن نیستم؛ تشخیص آنلاین', 'pixva' ); ?></a>
				</div>
			</aside>
		</div>
	</main>
	<?php
endwhile;
get_footer();

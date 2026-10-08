<?php
/**
 * Template Name: PIXVA — برآورد هزینه
 *
 * Price calculator (§08). Uses ONLY the configured price table (Admin →
 * پیکسوا → قیمت‌گذاری). When pricing is not configured it says so, explains
 * that cost is set after inspection, and still offers booking.
 * No-JS: GET form → server result. calculator.js: live result via REST.
 *
 * @package Pixva
 * @since   2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
get_header();
$pixva_p        = pixva_pricing();
$pixva_active   = pixva_pricing_active();
$pixva_services = array();
$pixva_sized    = false;
foreach ( (array) $pixva_p['services'] as $pixva_k => $pixva_s ) {
	if ( (int) $pixva_s['max'] > 0 ) {
		$pixva_services[ $pixva_k ] = $pixva_s['label'];
		$pixva_sized                = $pixva_sized || ! empty( $pixva_s['sized'] );
	}
}
$pixva_brands  = array_intersect_key( pixva_brand_choices(), (array) $pixva_p['brands'] );
$pixva_sel     = array(
	'service' => sanitize_key( pixva_get_request_var( 'service' ) ),
	'size'    => sanitize_key( pixva_get_request_var( 'size' ) ),
	'brand'   => absint( pixva_get_request_var( 'brand', '0' ) ),
);
$pixva_est     = ( $pixva_active && '' !== $pixva_sel['service'] ) ? pixva_estimate( $pixva_sel['service'], $pixva_sel['size'], $pixva_sel['brand'] ) : null;
$pixva_insp    = (array) $pixva_p['inspection'];
$pixva_insp_tx = ( (int) ( $pixva_insp['max'] ?? 0 ) > 0 ) ? pixva_format_range( (int) $pixva_insp['min'], (int) $pixva_insp['max'], (string) $pixva_p['currency'] ) : '';
?>
<main id="main" class="site-main">
	<?php pixva_page_header( get_the_title(), __( 'بازه تقریبی هزینه تعمیر را بر اساس نرخ‌های اعلام‌شده ببینید. مبلغ نهایی همیشه پس از بررسی دستگاه و پیش از شروع کار اعلام می‌شود.', 'pixva' ), __( 'ابزار رایگان', 'pixva' ) ); ?>
	<div class="container section">
		<div class="container--narrow">
			<?php pixva_page_intro(); ?>
		</div>
		<?php if ( ! $pixva_active ) : ?>
			<div class="panel" id="pixva-calculator" data-calculator-state="inspection">
				<h2 class="panel__title"><?php esc_html_e( 'هزینه پس از کارشناسی اعلام می‌شود', 'pixva' ); ?></h2>
				<p><?php esc_html_e( 'در حال حاضر نرخ‌نامه آنلاین منتشر نشده است. هزینه هر تعمیر به مدل دستگاه، قطعه موردنیاز و میزان خرابی بستگی دارد و پس از بررسی، پیش از شروع کار به شما اعلام می‌شود. تا تأیید شما کاری انجام نمی‌شود.', 'pixva' ); ?></p>
				<?php if ( '' !== $pixva_insp_tx ) : ?>
					<p><strong><?php esc_html_e( 'هزینه کارشناسی:', 'pixva' ); ?></strong> <?php echo esc_html( $pixva_insp_tx ); ?></p>
				<?php endif; ?>
				<?php if ( pixva_has_claim( 'inspection_fee' ) ) : ?>
					<p class="field__help"><?php echo esc_html( (string) pixva_claim( 'inspection_fee' ) ); ?></p>
				<?php endif; ?>
				<div class="wizard__actions">
					<a class="btn btn--accent" data-track="cta_click" data-track-label="booking" data-track-location="calculator_inspection" href="<?php echo esc_url( pixva_route_url( 'booking' ) ); ?>"><?php esc_html_e( 'ثبت درخواست بررسی', 'pixva' ); ?></a>
					<a class="btn btn--ghost" href="<?php echo esc_url( pixva_route_url( 'diagnosis' ) ); ?>"><?php esc_html_e( 'تشخیص آنلاین ایراد', 'pixva' ); ?></a>
				</div>
			</div>
		<?php else : ?>
			<form class="calc" id="pixva-calculator" method="get" action="<?php echo esc_url( pixva_route_url( 'price_calculator' ) ); ?>" data-calculator>
				<div class="calc-grid">
					<div class="calc__main panel">
						<div class="form-grid">
							<div class="field">
								<label for="calc-service"><?php esc_html_e( 'نوع تعمیر', 'pixva' ); ?> <span class="req" aria-hidden="true">*</span></label>
								<select id="calc-service" name="service" required aria-required="true">
									<option value=""><?php esc_html_e( 'انتخاب کنید…', 'pixva' ); ?></option>
									<?php foreach ( $pixva_services as $pixva_k => $pixva_l ) : ?>
										<option value="<?php echo esc_attr( $pixva_k ); ?>" data-sized="<?php echo ! empty( $pixva_p['services'][ $pixva_k ]['sized'] ) ? '1' : '0'; ?>" <?php selected( $pixva_sel['service'], $pixva_k ); ?>><?php echo esc_html( $pixva_l ); ?></option>
									<?php endforeach; ?>
								</select>
							</div>
							<?php if ( $pixva_sized && $pixva_p['sizes'] ) : ?>
								<div class="field" data-size-field>
									<label for="calc-size"><?php esc_html_e( 'اندازه صفحه', 'pixva' ); ?></label>
									<select id="calc-size" name="size">
										<option value=""><?php esc_html_e( 'نمی‌دانم', 'pixva' ); ?></option>
										<?php foreach ( $pixva_p['sizes'] as $pixva_k => $pixva_s ) : ?>
											<option value="<?php echo esc_attr( $pixva_k ); ?>" <?php selected( $pixva_sel['size'], $pixva_k ); ?>><?php echo esc_html( $pixva_s['label'] ); ?></option>
										<?php endforeach; ?>
									</select>
								</div>
							<?php endif; ?>
							<?php if ( $pixva_brands ) : ?>
								<div class="field">
									<label for="calc-brand"><?php esc_html_e( 'برند', 'pixva' ); ?></label>
									<select id="calc-brand" name="brand">
										<option value=""><?php esc_html_e( 'سایر / نمی‌دانم', 'pixva' ); ?></option>
										<?php foreach ( $pixva_brands as $pixva_k => $pixva_l ) : ?>
											<option value="<?php echo esc_attr( (string) $pixva_k ); ?>" <?php selected( $pixva_sel['brand'], $pixva_k ); ?>><?php echo esc_html( $pixva_l ); ?></option>
										<?php endforeach; ?>
									</select>
								</div>
							<?php endif; ?>
						</div>
						<div class="wizard__actions"><button class="btn btn--primary" type="submit" data-calc-submit><?php esc_html_e( 'محاسبه بازه هزینه', 'pixva' ); ?></button></div>
					</div>
					<aside class="calc__aside">
						<div class="calc__panel">
							<p class="calc__label"><?php esc_html_e( 'برآورد بازه هزینه', 'pixva' ); ?></p>
							<div class="calc__result" data-calc-result aria-live="polite" tabindex="-1">
								<?php pixva_calc_result( $pixva_est ); ?>
							</div>
							<p class="field__help"><?php echo esc_html( '' !== $pixva_p['disclaimer'] ? $pixva_p['disclaimer'] : __( 'این بازه تقریبی است و بر اساس نرخ‌های اعلام‌شده محاسبه می‌شود. مبلغ نهایی پس از بررسی دستگاه و پیش از شروع کار اعلام می‌شود.', 'pixva' ) ); ?></p>
							<?php if ( $pixva_p['updated'] ) : ?>
								<p class="field__help"><?php echo esc_html( sprintf( /* translators: %s: date. */ __( 'آخرین به‌روزرسانی نرخ‌ها: %s', 'pixva' ), pixva_format_date( (int) $pixva_p['updated'] ) ) ); ?></p>
							<?php endif; ?>
							<?php if ( '' !== $pixva_insp_tx ) : ?>
								<p class="field__help"><?php echo esc_html( sprintf( /* translators: %s: range. */ __( 'هزینه کارشناسی: %s', 'pixva' ), $pixva_insp_tx ) ); ?></p>
							<?php endif; ?>
							<div class="wizard__actions">
								<a class="btn btn--accent" data-track="cta_click" data-track-label="booking" data-track-location="calculator" href="<?php echo esc_url( pixva_route_url( 'booking' ) ); ?>"><?php esc_html_e( 'ثبت درخواست تعمیر', 'pixva' ); ?></a>
							</div>
						</div>
					</aside>
				</div>
			</form>
		<?php endif; ?>
		<section class="section--tight" aria-labelledby="calc-tools"><h2 id="calc-tools"><?php esc_html_e( 'ابزارهای دیگر', 'pixva' ); ?></h2><?php pixva_tool_cards( 'price_calculator' ); ?></section>
	</div>
</main>
<?php
get_footer();

<?php
/**
 * Template Name: PIXVA — تشخیص آنلاین
 *
 * Diagnosis wizard (§06–§07). Works fully without JavaScript as a GET
 * multi-step form (step 1: device + problem, step 2: symptoms + extra info,
 * result). diagnosis.js enhances it in place via REST (no page reload) and
 * reports diagnosis_started / completed / abandoned. Scoring is server-side.
 *
 * @package Pixva
 * @since   2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
get_header();
$pixva_problems = pixva_diagnosis_problems();
$pixva_brands   = pixva_brand_choices();
$pixva_sizes    = pixva_pricing()['sizes'];
$pixva_raw      = array(
	'brand'    => pixva_get_request_var( 'brand' ),
	'model'    => pixva_get_request_var( 'model' ),
	'problem'  => pixva_get_request_var( 'problem' ),
	'symptoms' => pixva_get_request_keys( 'symptoms' ),
	'age'      => pixva_get_request_var( 'age' ),
	'size'     => pixva_get_request_var( 'size' ),
);
$pixva_step     = sanitize_key( pixva_get_request_var( 'step' ) );
$pixva_in       = pixva_diagnosis_input( $pixva_raw );
$pixva_error    = '';
if ( in_array( $pixva_step, array( '2', 'result' ), true ) && is_wp_error( $pixva_in ) ) {
	$pixva_error = $pixva_in->get_error_message();
	$pixva_step  = '1';
}
if ( ! in_array( $pixva_step, array( '1', '2', 'result' ), true ) ) {
	$pixva_step = '1';
}
$pixva_sel_problem = is_wp_error( $pixva_in ) ? sanitize_key( $pixva_raw['problem'] ) : $pixva_in['problem'];
$pixva_sel_brand   = absint( $pixva_raw['brand'] );
$pixva_action      = pixva_route_url( 'diagnosis' );
$pixva_steps       = array(
	'1'      => __( 'دستگاه و مشکل', 'pixva' ),
	'2'      => __( 'نشانه‌ها', 'pixva' ),
	'result' => __( 'نتیجه', 'pixva' ),
);
?>
<main id="main" class="site-main">
	<?php pixva_page_header( get_the_title(), __( 'به چند پرسش پاسخ دهید تا علت‌های محتمل، بررسی‌های ایمن و قدم بعدی را ببینید. نتیجه قطعی نیست و جای بررسی تکنسین را نمی‌گیرد.', 'pixva' ), __( 'ابزار رایگان', 'pixva' ) ); ?>
	<div class="container container--narrow section">
		<?php pixva_page_intro(); ?>
		<div class="wizard" id="pixva-diagnosis" data-diagnosis data-step="<?php echo esc_attr( $pixva_step ); ?>">
			<ol class="progress" aria-label="<?php esc_attr_e( 'مراحل تشخیص', 'pixva' ); ?>">
				<?php
				$pixva_reached = true;
				foreach ( $pixva_steps as $pixva_k => $pixva_l ) :
					$pixva_cur = (string) $pixva_k === $pixva_step;
					?>
					<li class="progress__item<?php echo $pixva_cur ? ' is-current' : ( $pixva_reached ? ' is-done' : '' ); ?>" <?php echo $pixva_cur ? 'aria-current="step"' : ''; ?>><?php echo esc_html( $pixva_l ); ?></li>
					<?php
					if ( $pixva_cur ) {
						$pixva_reached = false;
					}
				endforeach;
				?>
			</ol>

			<div class="wizard__status" data-wizard-status aria-live="polite" tabindex="-1">
				<?php if ( '' !== $pixva_error ) : ?>
					<?php pixva_notice( 'error', $pixva_error, '', true ); ?>
				<?php endif; ?>
			</div>

			<?php if ( '1' === $pixva_step ) : ?>
				<form class="wizard__panel" method="get" action="<?php echo esc_url( $pixva_action ); ?>" data-wizard-step="1" novalidate>
					<input type="hidden" name="step" value="2">
					<h2 class="wizard__title"><?php esc_html_e( 'مرحله ۱: دستگاه و مشکل اصلی', 'pixva' ); ?></h2>
					<div class="form-grid">
						<div class="field">
							<label for="dg-brand"><?php esc_html_e( 'برند تلویزیون', 'pixva' ); ?> <span class="opt"><?php esc_html_e( '(اختیاری)', 'pixva' ); ?></span></label>
							<select id="dg-brand" name="brand" data-models-for="dg-model-list">
								<option value=""><?php esc_html_e( 'نمی‌دانم / در فهرست نیست', 'pixva' ); ?></option>
								<?php foreach ( $pixva_brands as $pixva_id => $pixva_t ) : ?>
									<option value="<?php echo esc_attr( (string) $pixva_id ); ?>" <?php selected( $pixva_sel_brand, $pixva_id ); ?>><?php echo esc_html( $pixva_t ); ?></option>
								<?php endforeach; ?>
							</select>
						</div>
						<div class="field">
							<label for="dg-model"><?php esc_html_e( 'مدل', 'pixva' ); ?> <span class="opt"><?php esc_html_e( '(اختیاری)', 'pixva' ); ?></span></label>
							<input id="dg-model" name="model" dir="auto" maxlength="60" list="dg-model-list" value="<?php echo esc_attr( $pixva_raw['model'] ); ?>" aria-describedby="dg-model-help">
							<datalist id="dg-model-list">
								<?php foreach ( pixva_brand_models( $pixva_sel_brand ) as $pixva_m ) : ?>
									<option value="<?php echo esc_attr( $pixva_m ); ?>"></option>
								<?php endforeach; ?>
							</datalist>
							<p class="field__help" id="dg-model-help"><?php esc_html_e( 'کد مدل روی برچسب پشت دستگاه نوشته شده است.', 'pixva' ); ?></p>
						</div>
					</div>
					<fieldset class="field choice-grid" aria-describedby="dg-problem-err">
						<legend><?php esc_html_e( 'مشکل اصلی چیست؟', 'pixva' ); ?> <span class="req" aria-hidden="true">*</span></legend>
						<?php foreach ( $pixva_problems as $pixva_k => $pixva_p ) : ?>
							<label class="choice">
								<input type="radio" name="problem" value="<?php echo esc_attr( $pixva_k ); ?>" <?php checked( $pixva_sel_problem, $pixva_k ); ?> required>
								<span class="choice__body"><span class="choice__title"><?php echo esc_html( $pixva_p['label'] ); ?></span><span class="choice__text"><?php echo esc_html( $pixva_p['desc'] ); ?></span></span>
							</label>
						<?php endforeach; ?>
						<p class="field__error" id="dg-problem-err" hidden></p>
					</fieldset>
					<div class="wizard__actions">
						<button class="btn btn--primary" type="submit"><?php esc_html_e( 'مرحله بعد', 'pixva' ); ?></button>
					</div>
				</form>

			<?php elseif ( '2' === $pixva_step ) : ?>
				<?php $pixva_rule = $pixva_problems[ $pixva_in['problem'] ]; ?>
				<form class="wizard__panel" method="get" action="<?php echo esc_url( $pixva_action ); ?>" data-wizard-step="2">
					<input type="hidden" name="step" value="result">
					<input type="hidden" name="problem" value="<?php echo esc_attr( $pixva_in['problem'] ); ?>">
					<input type="hidden" name="brand" value="<?php echo esc_attr( $pixva_in['brand'] ? (string) $pixva_in['brand'] : '' ); ?>">
					<input type="hidden" name="model" value="<?php echo esc_attr( $pixva_in['model'] ); ?>">
					<h2 class="wizard__title"><?php esc_html_e( 'مرحله ۲: نشانه‌ها و اطلاعات تکمیلی', 'pixva' ); ?></h2>
					<p class="wizard__summary">
						<?php echo esc_html( $pixva_rule['label'] ); ?>
						<?php if ( $pixva_in['brand'] || '' !== $pixva_in['model'] ) : ?>
							— <span dir="auto"><?php echo esc_html( trim( ( $pixva_in['brand'] ? pixva_brand_label( $pixva_in['brand'] ) : '' ) . ' ' . $pixva_in['model'] ) ); ?></span>
						<?php endif; ?>
						<a href="
						<?php
						echo esc_url(
							add_query_arg(
								array_filter(
									array(
										'step'    => '1',
										'problem' => $pixva_in['problem'],
										'brand'   => $pixva_in['brand'] ? (string) $pixva_in['brand'] : '',
										'model'   => $pixva_in['model'],
									)
								),
								$pixva_action
							)
						);
						?>
									"><?php esc_html_e( 'تغییر', 'pixva' ); ?></a>
					</p>
					<fieldset class="field">
						<legend><?php esc_html_e( 'کدام موارد را می‌بینید؟ (هر تعداد)', 'pixva' ); ?></legend>
						<?php foreach ( $pixva_rule['symptoms'] as $pixva_k => $pixva_l ) : ?>
							<label class="check"><input type="checkbox" name="symptoms[]" value="<?php echo esc_attr( $pixva_k ); ?>" <?php checked( in_array( $pixva_k, $pixva_in['symptoms'], true ) ); ?>> <span><?php echo esc_html( $pixva_l ); ?></span></label>
						<?php endforeach; ?>
					</fieldset>
					<fieldset class="field">
						<legend><?php esc_html_e( 'عمر دستگاه', 'pixva' ); ?></legend>
						<div class="radio-row">
							<?php foreach ( pixva_diagnosis_ages() as $pixva_k => $pixva_l ) : ?>
								<label class="check"><input type="radio" name="age" value="<?php echo esc_attr( $pixva_k ); ?>" <?php checked( $pixva_in['age'], $pixva_k ); ?>> <span><?php echo esc_html( $pixva_l ); ?></span></label>
							<?php endforeach; ?>
						</div>
					</fieldset>
					<?php if ( $pixva_sizes ) : ?>
						<div class="field">
							<label for="dg-size"><?php esc_html_e( 'اندازه صفحه', 'pixva' ); ?> <span class="opt"><?php esc_html_e( '(برای برآورد هزینه)', 'pixva' ); ?></span></label>
							<select id="dg-size" name="size">
								<option value=""><?php esc_html_e( 'نمی‌دانم', 'pixva' ); ?></option>
								<?php foreach ( $pixva_sizes as $pixva_k => $pixva_s ) : ?>
									<option value="<?php echo esc_attr( $pixva_k ); ?>" <?php selected( $pixva_in['size'], $pixva_k ); ?>><?php echo esc_html( $pixva_s['label'] ); ?></option>
								<?php endforeach; ?>
							</select>
						</div>
					<?php endif; ?>
					<div class="wizard__actions">
						<a class="btn btn--ghost" href="
						<?php
						echo esc_url(
							add_query_arg(
								array_filter(
									array(
										'step'    => '1',
										'problem' => $pixva_in['problem'],
										'brand'   => $pixva_in['brand'] ? (string) $pixva_in['brand'] : '',
										'model'   => $pixva_in['model'],
									)
								),
								$pixva_action
							)
						);
						?>
														"><?php esc_html_e( 'مرحله قبل', 'pixva' ); ?></a>
						<button class="btn btn--primary" type="submit"><?php esc_html_e( 'دیدن نتیجه', 'pixva' ); ?></button>
					</div>
				</form>

			<?php else : ?>
				<?php $pixva_r = pixva_diagnose( $pixva_in ); ?>
				<section class="wizard__panel result" data-wizard-step="result" data-track-view="diagnosis_completed" data-track-problem="<?php echo esc_attr( $pixva_in['problem'] ); ?>" aria-labelledby="dg-result-title">
					<h2 class="wizard__title" id="dg-result-title"><?php esc_html_e( 'نتیجه تشخیص', 'pixva' ); ?></h2>
					<p class="wizard__summary"><?php echo esc_html( $pixva_r['problem']['label'] ); ?><?php echo '' !== $pixva_r['device'] ? ' — <span dir="auto">' . esc_html( $pixva_r['device'] ) . '</span>' : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inline. ?></p>
					<?php if ( $pixva_r['danger'] ) : ?>
						<?php pixva_notice( 'error', __( 'دستگاه را از برق بکشید و تا بررسی تکنسین روشن نکنید. نشانه‌هایی که انتخاب کردید می‌تواند خطر برق‌گرفتگی یا آتش‌سوزی داشته باشد.', 'pixva' ), __( 'هشدار ایمنی', 'pixva' ) ); ?>
					<?php endif; ?>
					<h3><?php esc_html_e( 'علت‌های محتمل', 'pixva' ); ?></h3>
					<ol class="causes">
						<?php foreach ( $pixva_r['causes'] as $pixva_c ) : ?>
							<li class="cause cause--<?php echo esc_attr( $pixva_c['level'] ); ?>">
								<div class="cause__head"><strong><?php echo esc_html( $pixva_c['label'] ); ?></strong> <span class="badge badge--<?php echo esc_attr( $pixva_c['level'] ); ?>"><?php echo esc_html( $pixva_c['level_label'] ); ?></span></div>
								<p><?php echo esc_html( $pixva_c['desc'] ); ?></p>
								<?php if ( $pixva_c['service'] ) : ?>
									<a href="<?php echo esc_url( $pixva_c['service']['url'] ); ?>"><?php echo esc_html( sprintf( /* translators: %s: service. */ __( 'درباره «%s»', 'pixva' ), $pixva_c['service']['title'] ) ); ?></a>
								<?php endif; ?>
							</li>
						<?php endforeach; ?>
					</ol>
					<h3><?php esc_html_e( 'بررسی‌های ایمن که خودتان می‌توانید انجام دهید', 'pixva' ); ?></h3>
					<?php pixva_list( $pixva_r['safe'], 'checklist' ); ?>
					<p class="field__help"><?php esc_html_e( 'قاب دستگاه را باز نکنید؛ برد تغذیه حتی پس از کشیدن دوشاخه برق‌دار می‌ماند.', 'pixva' ); ?></p>
					<h3><?php esc_html_e( 'هزینه', 'pixva' ); ?></h3>
					<?php if ( $pixva_r['estimate'] ) : ?>
						<p class="price"><?php echo esc_html( $pixva_r['estimate']['range'] ); ?></p>
						<p class="field__help"><?php echo esc_html( sprintf( /* translators: %s: service label. */ __( 'بازه تقریبی برای «%s»؛ مبلغ نهایی پس از بررسی دستگاه و پیش از شروع کار اعلام می‌شود.', 'pixva' ), $pixva_r['estimate']['label'] ) ); ?> <?php echo esc_html( $pixva_r['estimate']['disclaimer'] ); ?></p>
					<?php else : ?>
						<p><?php esc_html_e( 'برای این مورد برآورد آنلاین در دسترس نیست؛ هزینه پس از کارشناسی و پیش از شروع کار اعلام می‌شود.', 'pixva' ); ?></p>
					<?php endif; ?>
					<?php if ( '' !== $pixva_r['inspection'] ) : ?>
						<p class="field__help"><?php echo esc_html( sprintf( /* translators: %s: fee note. */ __( 'هزینه کارشناسی: %s', 'pixva' ), $pixva_r['inspection'] ) ); ?></p>
					<?php endif; ?>
					<?php if ( '' !== $pixva_r['error_codes'] ) : ?>
						<p><a href="<?php echo esc_url( $pixva_r['error_codes'] ); ?>"><?php esc_html_e( 'معنی الگوی چشمک را در پایگاه کدهای خطا ببینید', 'pixva' ); ?></a></p>
					<?php endif; ?>
					<p class="field__help result__disclaimer"><?php echo esc_html( $pixva_r['disclaimer'] ); ?></p>
					<div class="wizard__actions">
						<a class="btn btn--accent" data-track="cta_click" data-track-label="booking" data-track-location="diagnosis_result" href="<?php echo esc_url( $pixva_r['booking_url'] ); ?>"><?php esc_html_e( 'ثبت درخواست تعمیر با همین اطلاعات', 'pixva' ); ?></a>
						<a class="btn btn--ghost" href="<?php echo esc_url( $pixva_action ); ?>"><?php esc_html_e( 'شروع دوباره', 'pixva' ); ?></a>
					</div>
				</section>
			<?php endif; ?>
		</div>
		<section class="section--tight" aria-labelledby="dg-tools"><h2 id="dg-tools"><?php esc_html_e( 'ابزارهای دیگر', 'pixva' ); ?></h2><?php pixva_tool_cards( 'diagnosis' ); ?></section>
	</div>
</main>
<?php
get_footer();

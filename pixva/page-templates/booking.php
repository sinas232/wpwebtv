<?php
/**
 * Template Name: PIXVA — ثبت درخواست تعمیر
 *
 * Booking (§11). POST to admin-post (no-JS, PRG) or admin-ajax (app.js).
 * Server validation, nonce, honeypot, rate limit, idempotency and private
 * photo storage live in forms.php / security.php. Prefill from diagnosis
 * (problem, brand, model, symptoms, age, from=diagnosis) and from service /
 * brand pages.
 *
 * @package Pixva
 * @since   2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
get_header();
$pixva_r        = pixva_form_result( 'pixva_booking' );
$pixva_problems = pixva_diagnosis_problems();
$pixva_brands   = pixva_brand_choices();
$pixva_modes    = pixva_service_modes();
$pixva_user     = wp_get_current_user();
$pixva_from     = 'diagnosis' === pixva_get_request_var( 'from' ) ? 'diagnosis' : '';
$pixva_pre      = array(
	'problem' => sanitize_key( pixva_get_request_var( 'problem' ) ),
	'brand'   => (string) absint( pixva_get_request_var( 'brand', '0' ) ),
	'model'   => pixva_substr( pixva_get_request_var( 'model' ), 0, 60 ),
);
$pixva_service  = pixva_service_by_slug( sanitize_title( pixva_get_request_var( 'service' ) ) );
$pixva_desc     = $pixva_service ? sprintf( /* translators: %s: service. */ __( 'خدمت موردنظر: %s', 'pixva' ), get_the_title( $pixva_service ) ) . "\n" : '';
$pixva_symptoms = array();
if ( 'diagnosis' === $pixva_from && isset( $pixva_problems[ $pixva_pre['problem'] ] ) ) {
	$pixva_symptoms = array_values( array_intersect( pixva_get_request_keys( 'symptoms' ), array_keys( $pixva_problems[ $pixva_pre['problem'] ]['symptoms'] ) ) );
}
$pixva_problem_opts = array( '' => __( 'انتخاب کنید…', 'pixva' ) ) + wp_list_pluck( $pixva_problems, 'label' ) + array( 'other' => __( 'سایر / مطمئن نیستم', 'pixva' ) );
$pixva_brand_opts   = array( '' => __( 'انتخاب کنید…', 'pixva' ) ) + array_map( 'strval', $pixva_brands ) + array( 'other' => __( 'سایر برندها', 'pixva' ) );
?>
<main id="main" class="site-main">
	<?php pixva_page_header( get_the_title(), __( 'مشخصات دستگاه و ایراد را بنویسید. پس از ثبت، کد پیگیری دریافت می‌کنید و کارشناس برای هماهنگی با شما تماس می‌گیرد.', 'pixva' ) ); ?>
	<div class="container container--narrow section">
		<?php pixva_page_intro(); ?>
		<div class="layout-aside booking">
		<div class="layout-aside__main">
		<div id="pixva-booking" class="form-wrap">
			<?php if ( is_array( $pixva_r ) && $pixva_r['ok'] && ! empty( $pixva_r['payload']['code'] ) ) : ?>
				<div class="success" data-track-view="booking_submitted" tabindex="-1">
					<h2 class="success__title"><?php echo wp_kses( pixva_icon( 'check' ), pixva_svg_allowed() ); ?> <?php echo esc_html( $pixva_r['payload']['message'] ); ?></h2>
					<p><?php esc_html_e( 'کد پیگیری شما:', 'pixva' ); ?></p>
					<p class="code code--lg" dir="ltr"><?php echo esc_html( $pixva_r['payload']['code'] ); ?></p>
					<p><?php esc_html_e( 'این کد را نگه دارید. با کد و شماره همراهی که وارد کردید، وضعیت درخواست را در صفحه پیگیری می‌بینید.', 'pixva' ); ?></p>
					<p class="wizard__actions">
						<a class="btn btn--primary" href="<?php echo esc_url( $pixva_r['payload']['tracking_url'] ); ?>"><?php esc_html_e( 'صفحه پیگیری', 'pixva' ); ?></a>
						<?php if ( ! is_user_logged_in() ) : ?>
							<a class="btn btn--ghost" href="<?php echo esc_url( pixva_route_url( 'account' ) ); ?>"><?php esc_html_e( 'ساخت حساب برای دیدن همه درخواست‌ها', 'pixva' ); ?></a>
						<?php endif; ?>
					</p>
				</div>
			<?php else : ?>
				<?php if ( is_array( $pixva_r ) && ! $pixva_r['ok'] ) : ?>
					<span hidden data-track-view="booking_failed"></span>
				<?php endif; ?>
				<?php if ( 'diagnosis' === $pixva_from && isset( $pixva_problems[ $pixva_pre['problem'] ] ) ) : ?>
					<?php pixva_notice( 'info', __( 'اطلاعات ابزار تشخیص به فرم اضافه شد. نتیجه تشخیص همراه درخواست برای کارشناس ارسال می‌شود.', 'pixva' ) ); ?>
				<?php endif; ?>
				<form class="form" method="post" enctype="multipart/form-data" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" data-pixva-form data-code-help="<?php esc_attr_e( 'این کد را نگه دارید. با کد و شماره همراهی که وارد کردید، وضعیت درخواست را در صفحه پیگیری می‌بینید.', 'pixva' ); ?>" data-tracking-label="<?php esc_attr_e( 'صفحه پیگیری', 'pixva' ); ?>" data-track-start="booking_started" data-track-success="booking_submitted" data-track-fail="booking_failed" novalidate>
					<?php pixva_form_fields( 'pixva_booking' ); ?>
					<input type="hidden" name="from" value="<?php echo esc_attr( $pixva_from ); ?>">
					<input type="hidden" name="symptoms" value="<?php echo esc_attr( implode( ',', $pixva_symptoms ) ); ?>">
					<input type="hidden" name="age" value="<?php echo esc_attr( 'diagnosis' === $pixva_from ? sanitize_key( pixva_get_request_var( 'age' ) ) : '' ); ?>">
					<?php pixva_form_status( $pixva_r ); ?>

					<fieldset class="form__section">
						<legend><?php esc_html_e( 'اطلاعات تماس', 'pixva' ); ?></legend>
						<div class="form-grid">
							<?php
							pixva_field(
								array(
									'name'     => 'name',
									'label'    => __( 'نام و نام خانوادگی', 'pixva' ),
									'required' => true,
									'value'    => $pixva_user->exists() ? $pixva_user->display_name : '',
									'attrs'    => array(
										'autocomplete' => 'name',
										'maxlength'    => '60',
									),
								),
								$pixva_r
							);
							pixva_field(
								array(
									'name'     => 'phone',
									'label'    => __( 'شماره همراه', 'pixva' ),
									'type'     => 'tel',
									'required' => true,
									'value'    => $pixva_user->exists() ? (string) get_user_meta( $pixva_user->ID, 'pixva_phone', true ) : '',
									'help'     => __( 'برای هماهنگی و پیگیری استفاده می‌شود؛ مثل ۰۹۱۲۳۴۵۶۷۸۹.', 'pixva' ),
									'attrs'    => array(
										'autocomplete' => 'tel',
										'inputmode'    => 'tel',
										'dir'          => 'ltr',
										'maxlength'    => '14',
									),
								),
								$pixva_r
							);
							?>
						</div>
					</fieldset>

					<fieldset class="form__section">
						<legend><?php esc_html_e( 'دستگاه و ایراد', 'pixva' ); ?></legend>
						<div class="form-grid">
							<?php
							if ( $pixva_brands ) {
								pixva_field(
									array(
										'name'     => 'brand',
										'label'    => __( 'برند', 'pixva' ),
										'type'     => 'select',
										'required' => true,
										'options'  => $pixva_brand_opts,
										'value'    => '0' !== $pixva_pre['brand'] ? $pixva_pre['brand'] : '',
									),
									$pixva_r
								);
								pixva_field(
									array(
										'name'  => 'brand_other',
										'label' => __( 'نام برند (اگر در فهرست نیست)', 'pixva' ),
										'attrs' => array(
											'maxlength' => '40',
											'data-show-when' => 'brand=other',
										),
									),
									$pixva_r
								);
							} else {
								echo '<input type="hidden" name="brand" value="other">';
								pixva_field(
									array(
										'name'     => 'brand_other',
										'label'    => __( 'برند', 'pixva' ),
										'required' => true,
										'attrs'    => array( 'maxlength' => '40' ),
									),
									$pixva_r
								);
							}
							pixva_field(
								array(
									'name'  => 'model',
									'label' => __( 'مدل', 'pixva' ),
									'value' => $pixva_pre['model'],
									'help'  => __( 'روی برچسب پشت دستگاه.', 'pixva' ),
									'attrs' => array(
										'maxlength' => '60',
										'dir'       => 'auto',
									),
								),
								$pixva_r
							);
							pixva_field(
								array(
									'name'     => 'problem',
									'label'    => __( 'مشکل اصلی', 'pixva' ),
									'type'     => 'select',
									'required' => true,
									'options'  => $pixva_problem_opts,
									'value'    => isset( $pixva_problem_opts[ $pixva_pre['problem'] ] ) ? $pixva_pre['problem'] : '',
								),
								$pixva_r
							);
							?>
						</div>
						<?php
						if ( $pixva_symptoms ) {
							echo '<p class="field__help">' . esc_html__( 'نشانه‌های انتخاب‌شده در تشخیص:', 'pixva' ) . ' ' . esc_html( implode( '، ', array_intersect_key( $pixva_problems[ $pixva_pre['problem'] ]['symptoms'], array_flip( $pixva_symptoms ) ) ) ) . '</p>';
						}
						pixva_field(
							array(
								'name'  => 'description',
								'label' => __( 'توضیح ایراد', 'pixva' ),
								'type'  => 'textarea',
								'rows'  => 4,
								'value' => $pixva_desc,
								'help'  => __( 'از کی شروع شد؟ بعد از چه اتفاقی؟ (برای «سایر» لازم است)', 'pixva' ),
								'attrs' => array( 'maxlength' => '1500' ),
							),
							$pixva_r
						);
						pixva_field(
							array(
								'name'  => 'photos[]',
								'id'    => 'f-photos',
								'label' => __( 'تصویر صفحه یا برچسب دستگاه', 'pixva' ),
								'type'  => 'file',
								'help'  => __( 'حداکثر ۳ تصویر JPG، PNG یا WebP، هر کدام تا ۵ مگابایت. تصاویر فقط برای کارشناسان قابل مشاهده است.', 'pixva' ),
								'attrs' => array(
									'accept'   => 'image/jpeg,image/png,image/webp',
									'multiple' => 'multiple',
								),
							),
							$pixva_r
						);
						?>
					</fieldset>

					<?php if ( $pixva_modes ) : ?>
						<fieldset class="form__section field<?php echo '' !== pixva_field_error( $pixva_r, 'mode' ) ? ' field--error' : ''; ?>" data-field="mode" aria-describedby="f-mode-err">
							<legend><?php esc_html_e( 'شیوه تحویل دستگاه', 'pixva' ); ?> <span class="req" aria-hidden="true">*</span></legend>
							<?php foreach ( $pixva_modes as $pixva_k => $pixva_l ) : ?>
								<label class="check"><input type="radio" name="mode" value="<?php echo esc_attr( $pixva_k ); ?>" required <?php checked( pixva_old( $pixva_r, 'mode', count( $pixva_modes ) === 1 ? $pixva_k : '' ), $pixva_k ); ?>> <span><?php echo esc_html( $pixva_l ); ?></span></label>
							<?php endforeach; ?>
							<p class="field__error" id="f-mode-err"<?php echo '' === pixva_field_error( $pixva_r, 'mode' ) ? ' hidden' : ''; ?>><?php echo esc_html( pixva_field_error( $pixva_r, 'mode' ) ); ?></p>
							<?php if ( isset( $pixva_modes['pickup'] ) || isset( $pixva_modes['onsite'] ) ) : ?>
								<?php
								pixva_field(
									array(
										'name'  => 'address',
										'label' => __( 'نشانی', 'pixva' ),
										'type'  => 'textarea',
										'rows'  => 2,
										'help'  => __( 'فقط برای دریافت از محل یا بازدید در محل لازم است.', 'pixva' ),
										'attrs' => array(
											'autocomplete' => 'street-address',
											'maxlength'    => '400',
											'data-show-when' => 'mode=pickup|onsite',
										),
									),
									$pixva_r
								);
								?>
							<?php endif; ?>
						</fieldset>
					<?php endif; ?>

					<?php
					pixva_field(
						array(
							'name'        => 'time',
							'label'       => __( 'زمان مناسب برای تماس', 'pixva' ),
							'placeholder' => __( 'مثلاً عصرها بعد از ساعت ۱۷', 'pixva' ),
							'attrs'       => array( 'maxlength' => '100' ),
						),
						$pixva_r
					);
					?>
					<?php
					pixva_field(
						array(
							'name'     => 'consent',
							'type'     => 'checkbox',
							'required' => true,
							'label'    => pixva_consent_label( __( 'موافقم اطلاعات تماس و دستگاه برای رسیدگی به این درخواست ذخیره و استفاده شود.', 'pixva' ) ),
						),
						$pixva_r
					);
					?>

					<div class="form__actions">
						<button class="btn btn--accent btn--lg" type="submit" data-submit><?php esc_html_e( 'ثبت درخواست', 'pixva' ); ?></button>
					</div>
				</form>
			<?php endif; ?>
		</div>
		</div>
		<aside class="booking__side" aria-labelledby="bk-side-title">
			<h2 class="booking__side-title" id="bk-side-title"><?php esc_html_e( 'بعد از ثبت درخواست چه می‌شود؟', 'pixva' ); ?></h2>
			<ol class="booking__steps">
				<li><strong><?php esc_html_e( 'دریافت کد پیگیری', 'pixva' ); ?></strong><span><?php esc_html_e( 'بلافاصله پس از ثبت، کد مخصوص درخواست را می‌بینید.', 'pixva' ); ?></span></li>
				<li><strong><?php esc_html_e( 'بررسی کارشناس', 'pixva' ); ?></strong><span><?php esc_html_e( 'کارشناس اطلاعات و تصاویر را بررسی و برای هماهنگی تماس می‌گیرد.', 'pixva' ); ?></span></li>
				<li><strong><?php esc_html_e( 'تأیید هزینه', 'pixva' ); ?></strong><span><?php esc_html_e( 'تعمیر فقط پس از اعلام هزینه و تأیید شما شروع می‌شود.', 'pixva' ); ?></span></li>
			</ol>
			<ul class="booking__trust">
				<li><?php echo wp_kses( pixva_icon( 'track' ), pixva_svg_allowed() ); ?><span><?php esc_html_e( 'پیگیری با کد و شماره همراه، بدون نمایش اطلاعات شخصی در صفحه.', 'pixva' ); ?></span></li>
				<li><?php echo wp_kses( pixva_icon( 'shield' ), pixva_svg_allowed() ); ?><span><?php esc_html_e( 'تصاویر فقط برای کارشناسان قابل مشاهده است و عمومی نمی‌شود.', 'pixva' ); ?></span></li>
				<li><?php echo wp_kses( pixva_icon( 'pulse' ), pixva_svg_allowed() ); ?><span><?php esc_html_e( 'اگر بوی سوختگی یا دود دارد، دستگاه را از برق بکشید و باز نکنید.', 'pixva' ); ?></span></li>
			</ul>
		</aside>
		</div>
		<?php
		$pixva_faq = pixva_faq_items( 'booking' );
		if ( $pixva_faq ) :
			?>
			<section class="section--tight" aria-labelledby="bk-faq"><h2 id="bk-faq"><?php esc_html_e( 'پرسش‌های رایج', 'pixva' ); ?></h2><?php pixva_faq_list( $pixva_faq, true ); ?></section>
		<?php endif; ?>
	</div>
</main>
<?php
get_footer();

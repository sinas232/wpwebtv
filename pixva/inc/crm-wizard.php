<?php
/**
 * جادوگر چندمرحله‌ای ثبت سفارش تعمیر (inc/crm-wizard.php)
 *
 * جایگزین کادرهای ایستای قدیمی (فرم پیک جمع‌آوری و شبیه‌ساز لمسی غیرواقعی):
 * یک جریان واقعی پذیرش سفارش که برآورد هزینه را از موتور نرخ‌نامه سمت سرور
 * می‌گیرد، پرونده `pixva_orders` می‌سازد، کد پیگیری آنی صادر می‌کند و
 * هوک پیامک اطلاع‌رسانی (pixva_send_sms) را فعال می‌کند.
 *
 * خروجی این پرونده در سه جا مصرف می‌شود:
 *  - شورت‌کد [pixva_order_wizard]
 *  - ابزار ۵ رجیستری (renderer: order_wizard)
 *  - ویجت المنتور Pixva_Order_Wizard_Widget
 *
 * @package Pixva
 * @since   1.4.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'pixva_order_wizard_steps' ) ) {
	/**
	 * گام‌های جادوگر (کلید => برچسب).
	 *
	 * @return array<string, string>
	 */
	function pixva_order_wizard_steps() {
		$steps = array(
			'device'  => __( 'دستگاه', 'pixva' ),
			'fault'   => __( 'خرابی', 'pixva' ),
			'contact' => __( 'مشخصات شما', 'pixva' ),
			'review'  => __( 'برآورد و ثبت', 'pixva' ),
		);

		/**
		 * فیلتر گام‌های جادوگر سفارش.
		 *
		 * @param array<string, string> $steps گام‌ها.
		 */
		return apply_filters( 'pixva_order_wizard_steps', $steps );
	}
}

if ( ! function_exists( 'pixva_order_wizard_urgency' ) ) {
	/**
	 * گزینه‌های زمان مراجعه/جمع‌آوری.
	 *
	 * @return array<string, string>
	 */
	function pixva_order_wizard_urgency() {
		$control = function_exists( 'pixva_control_options' ) ? pixva_control_options() : array();
		$eta     = isset( $control['hub_eta_hours'] ) ? $control['hub_eta_hours'] : '';

		$options = array(
			'asap'    => $eta
				? sprintf(
					/* translators: %s: بازه اعزام */
					__( 'اعزام فوری (زیر %s)', 'pixva' ),
					$eta
				)
				: __( 'اعزام فوری', 'pixva' ),
			'today'   => __( 'امروز', 'pixva' ),
			'tomorrow_am' => __( 'فردا (صبح)', 'pixva' ),
			'tomorrow_pm' => __( 'فردا (عصر)', 'pixva' ),
			'call'    => __( 'هماهنگی تلفنی', 'pixva' ),
		);

		/**
		 * فیلتر گزینه‌های زمان مراجعه.
		 *
		 * @param array<string, string> $options گزینه‌ها.
		 */
		return apply_filters( 'pixva_order_wizard_urgency', $options );
	}
}

if ( ! function_exists( 'pixva_order_wizard_choice_grid' ) ) {
	/**
	 * ساخت یک شبکه انتخاب (chip) از فهرست کلید => برچسب.
	 *
	 * @param string $group    نام گروه (brand/tech/size/problem).
	 * @param array  $options  گزینه‌ها.
	 * @param int    $limit    سقف تعداد گزینه نمایشی.
	 * @param string $modifier کلاس اندازه شبکه.
	 * @return void
	 */
	function pixva_order_wizard_choice_grid( $group, $options, $limit = 0, $modifier = '' ) {
		$options = is_array( $options ) ? $options : array();
		if ( $limit > 0 ) {
			$options = array_slice( $options, 0, $limit, true );
		}
		$class = 'pixva-wz__choices' . ( $modifier ? ' ' . $modifier : '' );
		?>
		<div class="<?php echo esc_attr( $class ); ?>" data-wz-choices="<?php echo esc_attr( $group ); ?>" role="radiogroup" aria-label="<?php echo esc_attr( $group ); ?>">
			<?php foreach ( $options as $key => $label ) : ?>
				<button type="button" class="pixva-choice" data-wz-group="<?php echo esc_attr( $group ); ?>" data-wz-value="<?php echo esc_attr( (string) $key ); ?>" role="radio" aria-checked="false">
					<span><?php echo esc_html( is_array( $label ) && isset( $label['fa'] ) ? $label['fa'] : $label ); ?></span>
					<?php if ( is_array( $label ) && ! empty( $label['en'] ) ) : ?>
						<small class="pixva-latin"><?php echo esc_html( $label['en'] ); ?></small>
					<?php endif; ?>
				</button>
			<?php endforeach; ?>
		</div>
		<?php
	}
}

if ( ! function_exists( 'pixva_render_order_wizard' ) ) {
	/**
	 * رندر جادوگر ثبت سفارش تعمیر.
	 *
	 * @param array $args گزینه‌ها: id, title, subtitle, badge, compact, source, note, variant.
	 * @return void
	 */
	function pixva_render_order_wizard( $args = array() ) {
		$args = wp_parse_args(
			$args,
			array(
				'id'       => 'pixva-order-wizard',
				'title'    => '',
				'subtitle' => '',
				'badge'    => '',
				'note'     => '',
				'compact'  => false,
				'variant'  => 'glass',
				'wrap'     => true,
				'source'   => 'wizard',
			)
		);

		$title    = '' !== $args['title'] ? $args['title'] : (string) pixva_option( 'pixva_wizard_title', __( 'ثبت سفارش تعمیر تلویزیون', 'pixva' ) );
		$subtitle = '' !== $args['subtitle'] ? $args['subtitle'] : (string) pixva_option(
			'pixva_wizard_subtitle',
			__( 'در چهار گام دستگاه، خرابی و مشخصات تماس را ثبت کنید؛ برآورد هزینه از نرخ‌نامه سمت سرور محاسبه و کد پیگیری آنی صادر می‌شود.', 'pixva' )
		);
		$badge    = '' !== $args['badge'] ? $args['badge'] : (string) pixva_option( 'pixva_wizard_badge', __( 'پذیرش و تخصیص خودکار به تعمیرکار', 'pixva' ) );
		$note     = '' !== $args['note'] ? $args['note'] : (string) pixva_option(
			'pixva_wizard_note',
			__( 'ثبت سفارش به معنی تأیید نهایی هزینه نیست؛ پس از عیب‌یابی رایگان، مبلغ قطعی برای تأیید شما ارسال می‌شود.', 'pixva' )
		);

		$labels = function_exists( 'pixva_calculator_labels' ) ? pixva_calculator_labels() : array();
		$brands = isset( $labels['brand'] ) ? $labels['brand'] : array();
		$techs  = isset( $labels['tech'] ) ? $labels['tech'] : array();
		$sizes  = isset( $labels['size'] ) ? $labels['size'] : array();
		$issues = isset( $labels['problem'] ) ? $labels['problem'] : array();
		$zones  = function_exists( 'pixva_zone_catalog' ) ? pixva_zone_catalog() : array();
		$steps  = pixva_order_wizard_steps();
		$urgent = pixva_order_wizard_urgency();

		$block_class = 'pixva-wz pixva-block pixva-block--' . sanitize_html_class( $args['variant'] ) . ( $args['compact'] ? ' pixva-wz--compact' : '' );
		?>
		<?php if ( $args['wrap'] ) : ?>
		<section class="pixva-section pixva-section--wizard" id="<?php echo esc_attr( $args['id'] ); ?>-section">
			<div class="pixva-container">
				<div class="pixva-section-head">
					<span class="pixva-badge pixva-badge--brand"><?php echo esc_html( $badge ); ?></span>
					<h2><?php echo esc_html( $title ); ?></h2>
					<p class="pixva-muted"><?php echo esc_html( $subtitle ); ?></p>
				</div>
		<?php endif; ?>

			<div class="<?php echo esc_attr( $block_class ); ?>" id="<?php echo esc_attr( $args['id'] ); ?>">
				<form class="pixva-wz__form" data-pixva-wizard data-wz-source="<?php echo esc_attr( $args['source'] ); ?>" novalidate>
					<?php wp_nonce_field( 'pixva_crm_nonce', 'pixva_crm_nonce_field', false ); ?>
					<input type="hidden" name="pixva_hp" value="" tabindex="-1" autocomplete="off" aria-hidden="true">
					<input type="hidden" name="brand" value="">
					<input type="hidden" name="tech" value="">
					<input type="hidden" name="size" value="">
					<input type="hidden" name="problem" value="">

					<ol class="pixva-wz__rail" data-wz-rail aria-label="<?php esc_attr_e( 'گام‌های ثبت سفارش', 'pixva' ); ?>">
						<?php $index = 0; foreach ( $steps as $key => $label ) : $index++; ?>
							<li class="pixva-wz__dot<?php echo 1 === $index ? ' is-current' : ''; ?>" data-wz-dot="<?php echo esc_attr( (string) $key ); ?>">
								<span class="pixva-wz__dot-no" aria-hidden="true"><?php echo esc_html( function_exists( 'pixva_fa_num' ) ? pixva_fa_num( (string) $index ) : $index ); ?></span>
								<span class="pixva-wz__dot-label"><?php echo esc_html( $label ); ?></span>
							</li>
						<?php endforeach; ?>
					</ol>

					<div class="pixva-wz__viewport">
						<div class="pixva-wz__track">

							<section class="pixva-wz__step is-active" data-wz-step="device" aria-labelledby="<?php echo esc_attr( $args['id'] ); ?>-device">
								<h3 id="<?php echo esc_attr( $args['id'] ); ?>-device"><?php esc_html_e( 'مشخصات دستگاه', 'pixva' ); ?></h3>
								<p class="pixva-wz__hint"><?php esc_html_e( 'برند، تکنولوژی پنل و سایز را انتخاب کنید؛ ضریب قیمت از همین مقادیر ساخته می‌شود.', 'pixva' ); ?></p>

								<h4><?php esc_html_e( 'برند', 'pixva' ); ?></h4>
								<?php pixva_order_wizard_choice_grid( 'brand', $brands, 8, 'pixva-wz__choices--brand' ); ?>

								<h4><?php esc_html_e( 'تکنولوژی پنل', 'pixva' ); ?></h4>
								<?php pixva_order_wizard_choice_grid( 'tech', $techs, 6 ); ?>

								<h4><?php esc_html_e( 'سایز صفحه', 'pixva' ); ?></h4>
								<?php pixva_order_wizard_choice_grid( 'size', $sizes, 10, 'pixva-wz__choices--size' ); ?>

								<div class="pixva-field">
									<label for="<?php echo esc_attr( $args['id'] ); ?>-model"><?php esc_html_e( 'مدل دستگاه (اختیاری)', 'pixva' ); ?></label>
									<input type="text" id="<?php echo esc_attr( $args['id'] ); ?>-model" name="model" maxlength="80" placeholder="UA55AU7000" dir="ltr">
								</div>

								<div class="pixva-wz__nav">
									<button type="button" class="pixva-btn pixva-btn--gradient" data-wz-next disabled><?php esc_html_e( 'گام بعد', 'pixva' ); ?></button>
								</div>
							</section>

							<section class="pixva-wz__step" data-wz-step="fault" hidden>
								<h3><?php esc_html_e( 'خرابی دستگاه', 'pixva' ); ?></h3>
								<p class="pixva-wz__hint"><?php esc_html_e( 'علامت اصلی خرابی را انتخاب کنید و در صورت نیاز شرح بیشتری بنویسید.', 'pixva' ); ?></p>

								<h4><?php esc_html_e( 'علامت خرابی', 'pixva' ); ?></h4>
								<?php pixva_order_wizard_choice_grid( 'problem', $issues, 8, 'pixva-wz__choices--problem' ); ?>

								<div class="pixva-field">
									<label for="<?php echo esc_attr( $args['id'] ); ?>-notes"><?php esc_html_e( 'شرح خرابی (اختیاری)', 'pixva' ); ?></label>
									<textarea id="<?php echo esc_attr( $args['id'] ); ?>-notes" name="notes" rows="3" maxlength="600" placeholder="<?php esc_attr_e( 'مثلاً تصویر هست اما نور پس‌زمینه خاموش می‌شود و چراغ پاور سه بار چشمک می‌زند.', 'pixva' ); ?>"></textarea>
								</div>

								<div class="pixva-wz__pair">
									<div class="pixva-field">
										<label for="<?php echo esc_attr( $args['id'] ); ?>-preferred"><?php esc_html_e( 'زمان مراجعه', 'pixva' ); ?></label>
										<select id="<?php echo esc_attr( $args['id'] ); ?>-preferred" name="preferred">
											<?php foreach ( $urgent as $key => $label ) : ?>
												<option value="<?php echo esc_attr( (string) $key ); ?>"><?php echo esc_html( $label ); ?></option>
											<?php endforeach; ?>
										</select>
									</div>
									<div class="pixva-field">
										<label for="<?php echo esc_attr( $args['id'] ); ?>-zone"><?php esc_html_e( 'منطقه', 'pixva' ); ?></label>
										<select id="<?php echo esc_attr( $args['id'] ); ?>-zone" name="zone">
											<?php foreach ( $zones as $zone ) : ?>
												<option value="<?php echo esc_attr( isset( $zone['label'] ) ? $zone['label'] : '' ); ?>"><?php echo esc_html( isset( $zone['label'] ) ? $zone['label'] : '' ); ?></option>
											<?php endforeach; ?>
										</select>
									</div>
								</div>

								<div class="pixva-wz__nav">
									<button type="button" class="pixva-btn pixva-btn--ghost" data-wz-prev><?php esc_html_e( 'بازگشت', 'pixva' ); ?></button>
									<button type="button" class="pixva-btn pixva-btn--gradient" data-wz-next disabled><?php esc_html_e( 'گام بعد', 'pixva' ); ?></button>
								</div>
							</section>

							<section class="pixva-wz__step" data-wz-step="contact" hidden>
								<h3><?php esc_html_e( 'مشخصات تماس', 'pixva' ); ?></h3>
								<p class="pixva-wz__hint"><?php esc_html_e( 'کد پیگیری و اطلاع‌رسانی وضعیت به همین شماره ارسال می‌شود.', 'pixva' ); ?></p>

								<div class="pixva-field">
									<label for="<?php echo esc_attr( $args['id'] ); ?>-name"><?php esc_html_e( 'نام و نام خانوادگی', 'pixva' ); ?></label>
									<input type="text" id="<?php echo esc_attr( $args['id'] ); ?>-name" name="name" autocomplete="name" maxlength="80" required>
								</div>
								<div class="pixva-field">
									<label for="<?php echo esc_attr( $args['id'] ); ?>-phone"><?php esc_html_e( 'شماره همراه', 'pixva' ); ?></label>
									<input type="tel" id="<?php echo esc_attr( $args['id'] ); ?>-phone" name="phone" inputmode="numeric" autocomplete="tel" placeholder="0912xxxxxxx" dir="ltr" required>
								</div>
								<div class="pixva-field">
									<label for="<?php echo esc_attr( $args['id'] ); ?>-address"><?php esc_html_e( 'آدرس (اختیاری)', 'pixva' ); ?></label>
									<textarea id="<?php echo esc_attr( $args['id'] ); ?>-address" name="address" rows="2" maxlength="300"></textarea>
								</div>

								<div class="pixva-wz__nav">
									<button type="button" class="pixva-btn pixva-btn--ghost" data-wz-prev><?php esc_html_e( 'بازگشت', 'pixva' ); ?></button>
									<button type="button" class="pixva-btn pixva-btn--gradient" data-wz-next data-wz-quote><?php esc_html_e( 'محاسبه برآورد', 'pixva' ); ?></button>
								</div>
							</section>

							<section class="pixva-wz__step" data-wz-step="review" hidden>
								<h3><?php esc_html_e( 'برآورد و تأیید نهایی', 'pixva' ); ?></h3>

								<dl class="pixva-wz__summary" data-wz-summary>
									<div><dt><?php esc_html_e( 'دستگاه', 'pixva' ); ?></dt><dd data-wz-sum="device">—</dd></div>
									<div><dt><?php esc_html_e( 'خرابی', 'pixva' ); ?></dt><dd data-wz-sum="problem">—</dd></div>
									<div><dt><?php esc_html_e( 'زمان مراجعه', 'pixva' ); ?></dt><dd data-wz-sum="preferred">—</dd></div>
									<div><dt><?php esc_html_e( 'منطقه', 'pixva' ); ?></dt><dd data-wz-sum="zone">—</dd></div>
									<div><dt><?php esc_html_e( 'نام', 'pixva' ); ?></dt><dd data-wz-sum="name">—</dd></div>
									<div><dt><?php esc_html_e( 'شماره همراه', 'pixva' ); ?></dt><dd data-wz-sum="phone">—</dd></div>
								</dl>

								<div class="pixva-wz__quote" data-wz-quote-box>
									<p class="pixva-wz__quote-label"><?php esc_html_e( 'بازه برآورد (نرخ‌نامه مصوب)', 'pixva' ); ?></p>
									<p class="pixva-wz__quote-price" data-wz-price>—</p>
									<p class="pixva-wz__quote-days" data-wz-days></p>
									<p class="pixva-wz__quote-note"><?php echo esc_html( $note ); ?></p>
								</div>

								<p class="pixva-notice pixva-notice--warning" data-wz-warning hidden></p>
								<p class="pixva-notice pixva-notice--error" data-wz-error hidden></p>

								<div class="pixva-wz__nav">
									<button type="button" class="pixva-btn pixva-btn--ghost" data-wz-prev><?php esc_html_e( 'اصلاح اطلاعات', 'pixva' ); ?></button>
									<button type="submit" class="pixva-btn pixva-btn--cta pixva-btn--pulse" data-wz-submit disabled><?php esc_html_e( 'ثبت سفارش و دریافت کد پیگیری', 'pixva' ); ?></button>
								</div>
							</section>

							<section class="pixva-wz__step pixva-wz__step--done" data-wz-step="done" hidden>
								<div class="pixva-wz__done" data-wz-success>
									<span class="pixva-wz__done-ring" aria-hidden="true"><?php echo pixva_icon( 'check' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
									<h3><?php esc_html_e( 'سفارش شما ثبت شد', 'pixva' ); ?></h3>
									<p class="pixva-wz__done-msg" data-wz-message></p>
									<p class="pixva-wz__code"><span><?php esc_html_e( 'کد پیگیری', 'pixva' ); ?></span><strong data-wz-code dir="ltr">—</strong></p>
									<p class="pixva-wz__done-status"><?php esc_html_e( 'وضعیت فعلی:', 'pixva' ); ?> <strong data-wz-status></strong></p>
									<div class="pixva-wz__done-actions">
										<a class="pixva-btn pixva-btn--gradient" href="<?php echo esc_url( function_exists( 'pixva_page_url' ) ? pixva_page_url( 'tracking' ) : home_url( '/' ) ); ?>" data-wz-track><?php esc_html_e( 'پیگیری وضعیت پرونده', 'pixva' ); ?></a>
										<a class="pixva-btn pixva-btn--ghost-dark" href="<?php echo esc_url( function_exists( 'pixva_whatsapp_url' ) ? pixva_whatsapp_url( __( 'سلام، سفارش تعمیر تلویزیون ثبت کردم و کد پیگیری دارم.', 'pixva' ) ) : '#' ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'مشاوره در واتساپ', 'pixva' ); ?></a>
									</div>
									<button type="button" class="pixva-wz__restart" data-wz-restart><?php esc_html_e( 'ثبت سفارش دیگر', 'pixva' ); ?></button>
								</div>
							</section>

						</div>
					</div>
				</form>
			</div>

		<?php if ( $args['wrap'] ) : ?>
			</div>
		</section>
		<?php endif; ?>
		<?php
	}
}

if ( ! function_exists( 'pixva_tool_render_order_wizard' ) ) {
	/**
	 * رندر جادوگر به‌عنوان ابزار رجیستری (جایگزین شبیه‌ساز لمسی قدیمی).
	 *
	 * @param int   $id   شناسه ابزار.
	 * @param array $tool مشخصات ابزار.
	 * @return void
	 */
	function pixva_tool_render_order_wizard( $id, $tool ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		pixva_render_order_wizard(
			array(
				'id'      => 'pixva-wizard-tool',
				'wrap'    => false,
				'compact' => true,
				'source'  => 'tool',
			)
		);
	}
}

if ( ! function_exists( 'pixva_order_wizard_shortcode' ) ) {
	/**
	 * شورت‌کد [pixva_order_wizard title="" subtitle="" badge="" compact="1" variant="glass"].
	 *
	 * @param array $atts ویژگی‌های شورت‌کد.
	 * @return string
	 */
	function pixva_order_wizard_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'id'       => 'pixva-order-wizard',
				'title'    => '',
				'subtitle' => '',
				'badge'    => '',
				'note'     => '',
				'compact'  => '',
				'variant'  => 'glass',
				'source'   => 'shortcode',
			),
			$atts,
			'pixva_order_wizard'
		);

		ob_start();
		pixva_render_order_wizard(
			array(
				'id'       => $atts['id'],
				'title'    => $atts['title'],
				'subtitle' => $atts['subtitle'],
				'badge'    => $atts['badge'],
				'note'     => $atts['note'],
				'compact'  => ! empty( $atts['compact'] ) && '0' !== $atts['compact'],
				'variant'  => $atts['variant'],
				'source'   => $atts['source'],
			)
		);
		return (string) ob_get_clean();
	}
}
add_shortcode( 'pixva_order_wizard', 'pixva_order_wizard_shortcode' );

if ( ! function_exists( 'pixva_needs_crm_js' ) ) {
	/**
	 * آیا صفحه جاری به اسکریپت موتور CRM نیاز دارد؟
	 *
	 * اسکریپت با strategy=defer و فقط در صفحه‌های دارای جادوگر، پنل،
	 * کارت گارانتی یا ابزارهای وبلاگ (اشتراک و جست‌وجوی کد خطا) بارگذاری می‌شود.
	 *
	 * @return bool
	 */
	function pixva_needs_crm_js() {
		// ابزارک‌های سایدبار (جست‌وجوی کد خطا و برآورد سریع) در آرشیو و جست‌وجو هم فعال‌اند.
		$needed = is_admin() || is_front_page() || is_singular() || is_page() || is_archive() || is_search() || is_user_logged_in();

		/**
		 * فیلتر بارگذاری اسکریپت موتور CRM در صفحه جاری.
		 *
		 * @param bool $needed نیاز به اسکریپت.
		 */
		return (bool) apply_filters( 'pixva_needs_crm_js', $needed );
	}
}

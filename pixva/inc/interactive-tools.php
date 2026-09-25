<?php
/**
 * رندر اجزای تعاملی سنگین پیکسوا (inc/interactive-tools.php)
 *
 * این پرونده فقط «اجزای بصری/تعاملی» مشترک را رندر می‌کند:
 * - شبیه‌ساز لمسی خرابی روی تلویزیون مجازی (ابزار ۵)
 * - تستر زنده پیکسل‌سوختگی RGB و چرخه احیای OLED (ابزار ۶ و ۷)
 * - هاب اعزام اورژانسی و پیگیری آنلاین (ابزار ۱۷ و ۱۹)
 *
 * رابط دستیار هوش مصنوعی در inc/ai-bot.php و بقیه ۶۰ ابزار در
 * inc/tool-renderers.php پیاده‌سازی شده‌اند.
 *
 * @package Pixva
 * @since   1.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'pixva_simulator_part_data' ) ) {
	/**
	 * داده واقعی هر بخش تلویزیون برای شبیه‌ساز لمسی (از موتور نرخ‌نامه).
	 *
	 * @return array<string, array<string, mixed>>
	 */
	function pixva_simulator_part_data() {
		$parts = array(
			'backlight'  => array(
				'title'   => __( 'دست بک‌لایت LED', 'pixva' ),
				'desc'    => __( 'سوختگی یا نیم‌سوز شدن شاخه‌های LED؛ نشانه رایج آن «صدا دارد ولی تصویر سیاه است» و دیده شدن تصویر کمرنگ با نور چراغ‌قوه است.', 'pixva' ),
				'service' => 'backlight',
			),
			'water'      => array(
				'title'   => __( 'فلت COF و برد T-Con', 'pixva' ),
				'desc'    => __( 'نفوذ رطوبت به فلت‌های بندینگ باعث خطوط عمودی، تصویر منفی یا سیاه شدن نیمی از صفحه می‌شود؛ ترمیم با دستگاه بندینگ صنعتی انجام می‌گیرد.', 'pixva' ),
				'service' => 'water',
			),
			'mainboard'  => array(
				'title'   => __( 'برد اصلی و پردازنده', 'pixva' ),
				'desc'    => __( 'ماندن روی لوگو، ریستارت مکرر، کار نکردن وای‌فای و خرابی پورت HDMI از نشانه‌های خرابی مین‌برد یا حافظه eMMC است.', 'pixva' ),
				'service' => 'mainboard',
			),
			'powerboard' => array(
				'title'   => __( 'برد تغذیه (Power)', 'pixva' ),
				'desc'    => __( 'خاموشی کامل، بوی سوختگی، چشمک زدن چراغ استندبای و روشن نشدن مجدد؛ خازن‌ها و ماس‌فت‌های طبقه سوئیچینگ درگیر می‌شوند.', 'pixva' ),
				'service' => 'powerboard',
			),
		);

		foreach ( $parts as $key => $part ) {
			$estimate = pixva_calculate_estimate( 'samsung', 'led', '55', $part['service'] );
			$parts[ $key ]['floor'] = is_array( $estimate ) ? (int) $estimate['min'] : 0;
			$parts[ $key ]['max']   = is_array( $estimate ) ? (int) $estimate['max'] : 0;
			$parts[ $key ]['days']  = is_array( $estimate ) ? (string) $estimate['days'] : '';
		}

		return $parts;
	}
}

if ( ! function_exists( 'pixva_render_tv_canvas_simulator' ) ) {
	/**
	 * ابزار ۵: شبیه‌ساز لمسی خرابی روی تلویزیون مجازی.
	 *
	 * @param array $args گزینه‌ها: wrap (پوشش سکشن) و size (سایز مرجع برآورد).
	 * @return void
	 */
	function pixva_render_tv_canvas_simulator( $args = array() ) {
		$args  = wp_parse_args( $args, array( 'wrap' => true, 'size' => '55' ) );
		$parts = pixva_simulator_part_data();
		$size  = sanitize_key( (string) $args['size'] );
		?>
		<?php if ( $args['wrap'] ) : ?>
		<section class="pixva-section" id="tv-simulator">
			<div class="pixva-container">
				<div class="pixva-section-head">
					<span class="pixva-badge pixva-badge--brand"><?php esc_html_e( 'شبیه‌ساز تعاملی', 'pixva' ); ?></span>
					<h2><?php esc_html_e( 'شبیه‌ساز لمسی عیب‌یابی تلویزیون مجازی', 'pixva' ); ?></h2>
					<p><?php esc_html_e( 'روی هر بخش از تلویزیون لمس یا کلیک کنید تا عیب، قطعه معیوب و برآورد نرخ‌نامه ۱۴۰۵ نمایش داده شود.', 'pixva' ); ?></p>
				</div>
		<?php endif; ?>

				<div class="pixva-card pixva-simulator-card" data-tv-simulator data-simulator-data="<?php echo esc_attr( wp_json_encode( $parts ) ); ?>" data-simulator-size="<?php echo esc_attr( $size ); ?>">
					<div class="pixva-sim-tv">
						<div class="pixva-sim-screen">
							<?php foreach ( $parts as $key => $part ) : ?>
								<button type="button" class="pixva-sim-hotspot" data-part="<?php echo esc_attr( $key ); ?>" aria-label="<?php echo esc_attr( $part['title'] ); ?>">
									<span><?php echo esc_html( $part['title'] ); ?></span>
								</button>
							<?php endforeach; ?>
							<div class="pixva-sim-display-msg" data-sim-screen-msg>
								<p><?php esc_html_e( 'یکی از بخش‌های تلویزیون را انتخاب کنید', 'pixva' ); ?></p>
							</div>
						</div>
					</div>

					<div class="pixva-sim-info" data-sim-info>
						<span class="pixva-badge pixva-badge--brand" data-sim-tag><?php esc_html_e( 'آماده بررسی', 'pixva' ); ?></span>
						<h3 data-sim-title><?php esc_html_e( 'بخش موردنظر را روی تلویزیون انتخاب کنید', 'pixva' ); ?></h3>
						<p data-sim-desc><?php esc_html_e( 'با انتخاب هر بخش، علت رایج خرابی، قطعه درگیر و برآورد واقعی آن خدمت از موتور نرخ‌نامه نمایش داده می‌شود.', 'pixva' ); ?></p>
						<div class="pixva-sim-price-box" data-sim-price-box hidden>
							<span class="pixva-muted"><?php esc_html_e( 'برآورد نرخ‌نامه ۱۴۰۵ (سامسونگ، LED، ۵۵ اینچ):', 'pixva' ); ?></span>
							<strong data-sim-price></strong>
							<span class="pixva-muted" data-sim-days></span>
						</div>
						<a class="pixva-btn pixva-btn--cta" href="<?php echo esc_url( pixva_page_url( 'calculator' ) ); ?>" data-sim-quote hidden><?php esc_html_e( 'محاسبه دقیق برای برند و سایز من', 'pixva' ); ?></a>
					</div>
				</div>

		<?php if ( $args['wrap'] ) : ?>
			</div>
		</section>
		<?php endif; ?>
		<?php
	}
}

if ( ! function_exists( 'pixva_render_screen_rgb_tester' ) ) {
	/**
	 * ابزار ۶ و ۷: تستر RGB پنل و چرخه احیای OLED.
	 *
	 * @param array $args گزینه‌ها: wrap و mode (rgb|oled).
	 * @return void
	 */
	function pixva_render_screen_rgb_tester( $args = array() ) {
		$args = wp_parse_args( $args, array( 'wrap' => true, 'mode' => 'rgb' ) );
		$mode = 'oled' === $args['mode'] ? 'oled' : 'rgb';

		$patterns = array(
			'#FF0000' => __( 'قرمز خالص (تست پیکسل سوخته قرمز)', 'pixva' ),
			'#00FF00' => __( 'سبز خالص (تست پیکسل سوخته سبز)', 'pixva' ),
			'#0000FF' => __( 'آبی خالص (تست پیکسل سوخته آبی)', 'pixva' ),
			'#FFFFFF' => __( 'سفید کالیبراسیون (لکه‌های نوری و هاله)', 'pixva' ),
			'#000000' => __( 'سیاه عمیق (نشتی نور بک‌لایت)', 'pixva' ),
			'#808080' => __( 'خاکستری میانه (باندینگ و یکنواختی)', 'pixva' ),
		);
		?>
		<?php if ( $args['wrap'] ) : ?>
		<section class="pixva-section pixva-section--alt" id="screen-tester">
			<div class="pixva-container">
				<div class="pixva-section-head">
					<span class="pixva-badge pixva-badge--brand"><?php esc_html_e( 'تست و احیای پنل', 'pixva' ); ?></span>
					<h2><?php esc_html_e( 'تستر پیکسل‌سوختگی RGB و احیاکننده OLED', 'pixva' ); ?></h2>
					<p><?php esc_html_e( 'الگوهای رنگی استاندارد را تمام‌صفحه روی همان تلویزیون اجرا کنید تا پیکسل‌های سوخته، گیرکرده و لکه‌های نوری مشخص شوند.', 'pixva' ); ?></p>
				</div>
		<?php endif; ?>

				<div class="pixva-card pixva-rgb-tester" data-rgb-tester data-tester-mode="<?php echo esc_attr( $mode ); ?>">
					<div class="pixva-rgb-controls">
						<?php foreach ( $patterns as $color => $label ) : ?>
							<button type="button" class="pixva-btn pixva-btn--ghost-dark" data-color="<?php echo esc_attr( $color ); ?>"><?php echo esc_html( $label ); ?></button>
						<?php endforeach; ?>
						<button type="button" class="pixva-btn pixva-btn--primary" data-fullscreen><?php esc_html_e( 'تمام‌صفحه', 'pixva' ); ?></button>
						<?php if ( 'oled' === $mode ) : ?>
							<button type="button" class="pixva-btn pixva-btn--cta" data-oled-cleaner><?php esc_html_e( 'اجرای چرخه احیای OLED', 'pixva' ); ?></button>
						<?php endif; ?>
					</div>
					<div class="pixva-rgb-canvas" data-rgb-canvas style="background:#0E2C38;">
						<span data-rgb-hint><?php esc_html_e( 'برای تست، یکی از الگوهای بالا را انتخاب کنید. در پنل‌های OLED اجرای طولانی الگوی ثابت باعث ماندگاری تصویر می‌شود؛ چرخه احیا را حداکثر ۱۰ دقیقه اجرا کنید.', 'pixva' ); ?></span>
					</div>
				</div>

		<?php if ( $args['wrap'] ) : ?>
			</div>
		</section>
		<?php endif; ?>
		<?php
	}
}

if ( ! function_exists( 'pixva_render_dispatch_and_warranty_hub' ) ) {
	/**
	 * ابزار ۱۷ و ۱۹: هاب پیگیری زنده و اعزام فوری.
	 *
	 * @param array $args گزینه‌ها: wrap.
	 * @return void
	 */
	function pixva_render_dispatch_and_warranty_hub( $args = array() ) {
		$args  = wp_parse_args( $args, array( 'wrap' => true ) );
		$phone = pixva_support_phone();
		$zones = pixva_zone_catalog();
		?>
		<?php if ( $args['wrap'] ) : ?>
		<section class="pixva-section" id="dispatch-hub">
			<div class="pixva-container">
		<?php endif; ?>
				<div class="pixva-grid pixva-grid--2">
					<div class="pixva-card">
						<span class="pixva-badge pixva-badge--brand"><?php esc_html_e( 'سامانه رهگیری لحظه‌ای', 'pixva' ); ?></span>
						<h3><?php esc_html_e( 'پیگیری زنده وضعیت تعمیر دستگاه', 'pixva' ); ?></h3>
						<p class="pixva-muted"><?php esc_html_e( 'کد پیگیری و شماره همراه ثبت‌شده هنگام پذیرش را وارد کنید تا تایم‌لاین شش مرحله‌ای پرونده و وضعیت گارانتی نمایش داده شود.', 'pixva' ); ?></p>
						<form action="<?php echo esc_url( pixva_page_url( 'tracking' ) ); ?>" method="get" class="pixva-tool-row">
							<input type="text" name="code" placeholder="<?php esc_attr_e( 'کد پیگیری (PXV-…)', 'pixva' ); ?>" class="pixva-input" aria-label="<?php esc_attr_e( 'کد پیگیری', 'pixva' ); ?>" required>
							<input type="tel" name="phone" placeholder="<?php esc_attr_e( 'شماره همراه', 'pixva' ); ?>" class="pixva-input" inputmode="numeric" aria-label="<?php esc_attr_e( 'شماره همراه', 'pixva' ); ?>" required>
							<button type="submit" class="pixva-btn pixva-btn--primary"><?php esc_html_e( 'مشاهده تایم‌لاین', 'pixva' ); ?></button>
						</form>
					</div>

					<div class="pixva-card">
						<span class="pixva-badge pixva-badge--cta"><?php esc_html_e( 'اعزام فوری', 'pixva' ); ?></span>
						<h3><?php esc_html_e( 'پیک جمع‌آوری ضدضربه و تکنسین سیار', 'pixva' ); ?></h3>
						<p class="pixva-muted"><?php esc_html_e( 'حمل تلویزیون‌های ۵۵ تا ۸۵ اینچ با جعبه پددار استاندارد انجام می‌شود و رسید کتبی با مهر کارگاه علاءالدین تقدیم می‌گردد.', 'pixva' ); ?></p>
						<form class="pixva-tool-row" data-request-form="dispatch" novalidate>
							<?php
							pixva_tool_field( 'dh-phone', __( 'شماره همراه', 'pixva' ), 'tel', array( 'name' => 'phone', 'inputmode' => 'numeric', 'placeholder' => '0912xxxxxxx', 'required' => true ) );
							pixva_tool_field( 'dh-zone', __( 'منطقه', 'pixva' ), 'select', array( 'name' => 'zone', 'options' => wp_list_pluck( $zones, 'label' ) ) );
							?>
							<button type="submit" class="pixva-btn pixva-btn--cta"><?php esc_html_e( 'ثبت درخواست اعزام', 'pixva' ); ?></button>
						</form>
						<div class="pixva-tool-row">
							<a href="<?php echo esc_url( pixva_tel_href( $phone ) ); ?>" class="pixva-btn pixva-btn--ghost-dark"><?php echo pixva_icon( 'phone' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php echo esc_html( pixva_fa_num( $phone ) ); ?></a>
							<a href="<?php echo esc_url( pixva_whatsapp_url( __( 'سلام، درخواست اعزام پیک برای تعمیر تلویزیون دارم.', 'pixva' ) ) ); ?>" target="_blank" rel="noopener noreferrer" class="pixva-btn pixva-btn--ghost-dark"><?php esc_html_e( 'واتساپ کارگاه', 'pixva' ); ?></a>
						</div>
					</div>
				</div>
		<?php if ( $args['wrap'] ) : ?>
			</div>
		</section>
		<?php endif; ?>
		<?php
	}
}

<?php
/**
 * پنل مدیریت نرخ‌نامه در پیشخوان (inc/admin-settings.php)
 *
 * مسیر اختصاصی: پیشخوان ← پیگیری تعمیرات ← نرخ‌نامه
 *
 * با WordPress Settings API ساخته شده و شامل:
 * - ضریب کلی قیمت (۰٫۵ تا ۳)
 * - هزینه کارشناسی (پیش‌فرض ۱۸۰٬۰۰۰ تا ۳۵۰٬۰۰۰ تومان)
 * - حداقل و حداکثر قیمت پایه هر خدمت (تعویض بک‌لایت، برد پاور، برد اصلی، رفع آب‌خوردگی و…)
 * - ضریب اختصاصی هر یک از ۲۴ برند
 * - دکمه Reset to Defaults برای بازگردانی نرخ‌ها به مقادیر پایه سال ۱۴۰۵ با POST ایمن
 *
 * @package Pixva
 * @since   1.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // خروج مستقیم غیرمجاز.
}

if ( ! function_exists( 'pixva_pricing_menu_slug' ) ) {
	/**
	 * نامک زیرمنوی نرخ‌نامه.
	 *
	 * @return string
	 */
	function pixva_pricing_menu_slug() {
		return 'pixva-pricing';
	}
}

/*
 * ---------------------------------------------------------------------------
 * ۱) منوی پیشخوان: پیگیری تعمیرات ← نرخ‌نامه
 * ---------------------------------------------------------------------------
 */

/**
 * افزودن زیرمنوی «نرخ‌نامه» زیر منوی «پیگیری تعمیرات» (پست‌تایپ pixva_orders).
 *
 * @return void
 */
function pixva_register_pricing_menu() {
	add_submenu_page(
		'edit.php?post_type=pixva_orders',
		esc_html__( 'نرخ‌نامه تعمیرات ۱۴۰۵', 'pixva' ),
		esc_html__( 'نرخ‌نامه', 'pixva' ),
		'manage_options',
		pixva_pricing_menu_slug(),
		'pixva_render_pricing_page'
	);
}
add_action( 'admin_menu', 'pixva_register_pricing_menu' );

/*
 * ---------------------------------------------------------------------------
 * ۲) ثبت تنظیمات با WordPress Settings API
 * ---------------------------------------------------------------------------
 */

/**
 * ثبت گزینه نرخ‌نامه در Settings API.
 *
 * @return void
 */
function pixva_register_pricing_settings() {
	register_setting(
		'pixva_pricing',
		'pixva_pricing_settings',
		array(
			'type'              => 'array',
			'sanitize_callback' => 'pixva_pricing_sanitize_settings',
			'default'           => pixva_pricing_defaults(),
		)
	);

	// بخش اول: تنظیمات کلی.
	add_settings_section(
		'pixva_pricing_general',
		esc_html__( 'تنظیمات کلی نرخ', 'pixva' ),
		static function () {
			echo '<p>' . esc_html__( 'ضریب کلی روی قیمت پایه همه خدمات اعمال می‌شود. هزینه کارشناسی، مبلغ ثابتی است که به بازه هر خدمت افزوده می‌شود.', 'pixva' ) . '</p>';
		},
		pixva_pricing_menu_slug()
	);

	add_settings_field(
		'pixva_global_multiplier',
		esc_html__( 'ضریب کلی قیمت', 'pixva' ),
		'pixva_field_global_multiplier',
		pixva_pricing_menu_slug(),
		'pixva_pricing_general'
	);

	add_settings_field(
		'pixva_diagnostic_fee',
		esc_html__( 'هزینه کارشناسی (تومان)', 'pixva' ),
		'pixva_field_diagnostic_fee',
		pixva_pricing_menu_slug(),
		'pixva_pricing_general'
	);

	// بخش دوم: قیمت پایه خدمات.
	add_settings_section(
		'pixva_pricing_services',
		esc_html__( 'قیمت پایه خدمات (تومان)', 'pixva' ),
		static function () {
			echo '<p>' . esc_html__( 'حداقل و حداکثر قیمت پایه هر خدمت را پیش از اعمال ضرایب برند و سایز تعیین کنید. تعویض کامل پنل خارج از این جدول و با هشدار اختصاصی محاسبه می‌شود.', 'pixva' ) . '</p>';
		},
		pixva_pricing_menu_slug()
	);

	foreach ( pixva_pricing_service_defaults() as $key => $row ) {
		add_settings_field(
			'pixva_service_' . $key,
			pixva_pricing_service_label( $key ),
			'pixva_field_service_base',
			pixva_pricing_menu_slug(),
			'pixva_pricing_services',
			array( 'key' => $key )
		);
	}

	// بخش سوم: ضرایب ۲۴ برند.
	add_settings_section(
		'pixva_pricing_brands',
		esc_html__( 'ضریب اختصاصی برندها', 'pixva' ),
		static function () {
			echo '<p>' . esc_html__( 'ضریب هر یک از ۲۴ برند روی قیمت پایه ضرب می‌شود. بازه مجاز ۰٫۱ تا ۳ است.', 'pixva' ) . '</p>';
		},
		pixva_pricing_menu_slug()
	);

	foreach ( pixva_pricing_brand_defaults() as $key => $value ) {
		add_settings_field(
			'pixva_brand_' . $key,
			pixva_pricing_brand_label( $key ),
			'pixva_field_brand_multiplier',
			pixva_pricing_menu_slug(),
			'pixva_pricing_brands',
			array( 'key' => $key )
		);
	}
}
add_action( 'admin_init', 'pixva_register_pricing_settings' );

if ( ! function_exists( 'pixva_pricing_service_label' ) ) {
	/**
	 * برچسب فارسی خدمت.
	 *
	 * @param string $key کلید خدمت.
	 * @return string
	 */
	function pixva_pricing_service_label( $key ) {
		$labels = array(
			'backlight'  => esc_html__( 'تعویض بک‌لایت', 'pixva' ),
			'powerboard' => esc_html__( 'تعمیر برد پاور', 'pixva' ),
			'mainboard'  => esc_html__( 'تعمیر برد اصلی', 'pixva' ),
			'water'      => esc_html__( 'رفع آب‌خوردگی', 'pixva' ),
			'no_sound'   => esc_html__( 'تعمیر صدا', 'pixva' ),
			'no_power'   => esc_html__( 'رفع خاموشی کامل', 'pixva' ),
			'blink'      => esc_html__( 'رفع چشمک چراغ پاور', 'pixva' ),
			'no_picture' => esc_html__( 'رفع بی‌تصویری', 'pixva' ),
			'lines'      => esc_html__( 'رفع خطوط عمودی/افقی', 'pixva' ),
			'panel'      => esc_html__( 'تعمیر پنل با بندینگ', 'pixva' ),
		);
		$key = (string) $key;
		return isset( $labels[ $key ] ) ? $labels[ $key ] : $key;
	}
}

if ( ! function_exists( 'pixva_pricing_brand_label' ) ) {
	/**
	 * برچسب فارسی برند.
	 *
	 * @param string $key کلید برند.
	 * @return string
	 */
	function pixva_pricing_brand_label( $key ) {
		if ( function_exists( 'pixva_brand_catalog' ) && isset( pixva_brand_catalog()[ $key ]['fa'] ) ) {
			return esc_html( pixva_brand_catalog()[ $key ]['fa'] );
		}
		return esc_html( (string) $key );
	}
}

/*
 * ---------------------------------------------------------------------------
 * ۳) رندر فیلدها
 * ---------------------------------------------------------------------------
 */

/**
 * فیلد ضریب کلی قیمت.
 *
 * @return void
 */
function pixva_field_global_multiplier() {
	$settings = pixva_pricing_settings();
	printf(
		'<input type="number" step="0.05" min="0.5" max="3" class="small-text" id="pixva_global_multiplier" name="pixva_pricing_settings[global_multiplier]" value="%1$s"> <span class="description">%2$s</span>',
		esc_attr( (string) $settings['global_multiplier'] ),
		esc_html__( 'بازه مجاز: ۰٫۵ تا ۳ (پیش‌فرض ۱).', 'pixva' )
	);
}

/**
 * فیلد بازه هزینه کارشناسی.
 *
 * @return void
 */
function pixva_field_diagnostic_fee() {
	$settings = pixva_pricing_settings();
	printf(
		'<label>%1$s <input type="number" step="10000" min="0" class="regular-text" style="max-width:9em" name="pixva_pricing_settings[diagnostic_min]" value="%2$s"></label> — <label>%3$s <input type="number" step="10000" min="0" class="regular-text" style="max-width:9em" name="pixva_pricing_settings[diagnostic_max]" value="%4$s"></label> <span class="description">%5$s</span>',
		esc_html__( 'حداقل', 'pixva' ),
		esc_attr( (string) $settings['diagnostic_min'] ),
		esc_html__( 'حداکثر', 'pixva' ),
		esc_attr( (string) $settings['diagnostic_max'] ),
		esc_html__( 'پیش‌فرض نرخ ۱۴۰۵: ۱۸۰٬۰۰۰ تا ۳۵۰٬۰۰۰ تومان.', 'pixva' )
	);
}

/**
 * فیلد قیمت پایه یک خدمت (min/max).
 *
 * @param array $args آرگومان فیلد (key).
 * @return void
 */
function pixva_field_service_base( $args ) {
	$key      = isset( $args['key'] ) ? sanitize_key( $args['key'] ) : '';
	$settings = pixva_pricing_settings();
	if ( ! isset( $settings['services'][ $key ] ) ) {
		return;
	}
	$row = $settings['services'][ $key ];
	printf(
		'<label>%1$s <input type="number" step="50000" min="0" class="regular-text" style="max-width:9em" name="pixva_pricing_settings[services][%2$s][min]" value="%3$s"></label> — <label>%4$s <input type="number" step="50000" min="0" class="regular-text" style="max-width:9em" name="pixva_pricing_settings[services][%2$s][max]" value="%5$s"></label>',
		esc_html__( 'حداقل', 'pixva' ),
		esc_attr( $key ),
		esc_attr( (string) $row['min'] ),
		esc_html__( 'حداکثر', 'pixva' ),
		esc_attr( (string) $row['max'] )
	);
}

/**
 * فیلد ضریب یک برند.
 *
 * @param array $args آرگومان فیلد (key).
 * @return void
 */
function pixva_field_brand_multiplier( $args ) {
	$key      = isset( $args['key'] ) ? sanitize_key( $args['key'] ) : '';
	$settings = pixva_pricing_settings();
	if ( ! isset( $settings['brands'][ $key ] ) ) {
		return;
	}
	printf(
		'<input type="number" step="0.05" min="0.1" max="3" class="small-text" name="pixva_pricing_settings[brands][%1$s]" value="%2$s">',
		esc_attr( $key ),
		esc_attr( (string) $settings['brands'][ $key ] )
	);
}

/*
 * ---------------------------------------------------------------------------
 * ۴) دکمه Reset to Defaults (POST ایمن)
 * ---------------------------------------------------------------------------
 */

/**
 * پردازش بازگردانی نرخ‌ها به مقادیر پایه سال ۱۴۰۵.
 *
 * @return void
 */
function pixva_handle_pricing_reset() {
	if ( empty( $_POST['pixva_reset_pricing'] ) ) {
		return;
	}
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'دسترسی غیرمجاز.', 'pixva' ) );
	}
	check_admin_referer( 'pixva_pricing_reset' );

	delete_option( 'pixva_pricing_settings' );

	$url = add_query_arg(
		array(
			'post_type'       => 'pixva_orders',
			'page'            => pixva_pricing_menu_slug(),
			'pixva_rates_reset' => '1',
		),
		admin_url( 'edit.php' )
	);
	wp_safe_redirect( $url );
	exit;
}
add_action( 'admin_init', 'pixva_handle_pricing_reset' );

/*
 * ---------------------------------------------------------------------------
 * ۵) رندر صفحه نرخ‌نامه
 * ---------------------------------------------------------------------------
 */

/**
 * نمای صفحه نرخ‌نامه.
 *
 * @return void
 */
function pixva_render_pricing_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'نرخ‌نامه تعمیرات ۱۴۰۵', 'pixva' ); ?></h1>

		<?php if ( isset( $_GET['pixva_rates_reset'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- فقط پیام وضعیت. ?>
			<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'نرخ‌نامه به مقادیر پایه سال ۱۴۰۵ بازگردانده شد.', 'pixva' ); ?></p></div>
		<?php endif; ?>

		<p class="description">
			<?php esc_html_e( 'قیمت نهایی مشتری با فرمول زیر و فقط در سرور محاسبه می‌شود:', 'pixva' ); ?>
			<code>Price = (Base × BrandMultiplier × SizeFactor) + DiagnosticFee</code>
		</p>

		<form method="post" action="options.php">
			<?php
			settings_fields( 'pixva_pricing' );
			do_settings_sections( pixva_pricing_menu_slug() );
			submit_button( esc_html__( 'ذخیره نرخ‌ها', 'pixva' ) );
			?>
		</form>

		<hr>

		<form method="post" action="">
			<?php wp_nonce_field( 'pixva_pricing_reset' ); ?>
			<p>
				<button type="submit" name="pixva_reset_pricing" value="1" class="button button-secondary" onclick="return confirm('<?php echo esc_js( __( 'همه نرخ‌ها به مقادیر پایه سال ۱۴۰۵ بازگردانده شود؟', 'pixva' ) ); ?>');">
					<?php esc_html_e( 'Reset to Real Market Standard (بازگردانی به نرخ‌های واقعی بازار)', 'pixva' ); ?>
				</button>
			</p>
			<p class="description"><?php esc_html_e( 'با این دکمه، ضریب کلی، هزینه کارشناسی، قیمت پایه خدمات و ضریب ۲۴ برند به حالت اولیه برمی‌گردد.', 'pixva' ); ?></p>
		</form>
	</div>
	<?php
}

<?php
/**
 * پنل مدیریت نرخ‌نامه ۱۴۰۵ با Settings API وردپرس
 *
 * مسیر: پیشخوان ← پیگیری تعمیرات ← نرخ‌نامه
 * قابل تنظیم: ضریب کلی ۰٫۵ تا ۳، هزینه کارشناسی، کف و سقف هر خدمت، ضریب هر برند.
 * ذخیره‌سازی با register_setting و sanitize اختصاصی؛ بازگشت به پیش‌فرض با POST ایمن.
 *
 * @package Pixva
 * @since   1.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ثبت زیرمنوی نرخ‌نامه زیر «پیگیری تعمیرات».
 *
 * @return void
 */
function pixva_register_rates_menu() {
	add_submenu_page(
		'edit.php?post_type=pixva_orders',
		esc_html__( 'نرخ‌نامه ۱۴۰۵', 'pixva' ),
		esc_html__( 'نرخ‌نامه', 'pixva' ),
		'manage_options',
		'pixva-rates',
		'pixva_render_rates_page'
	);
}
add_action( 'admin_menu', 'pixva_register_rates_menu' );

/**
 * ثبت تنظیم نرخ‌نامه و سکشن‌های فرم با Settings API.
 *
 * @return void
 */
function pixva_register_rates_settings() {
	register_setting(
		'pixva_rates_group',
		'pixva_rates_1405',
		array(
			'type'              => 'array',
			'sanitize_callback' => 'pixva_sanitize_rates_1405',
			'default'           => array(),
		)
	);
	add_settings_section( 'pixva_rates_general', esc_html__( 'تنظیمات کلی', 'pixva' ), 'pixva_rates_section_general', 'pixva-rates' );
	add_settings_section( 'pixva_rates_base', esc_html__( 'کف و سقف پایه هر خدمت (تومان، مبنای ۳۲ اینچ)', 'pixva' ), 'pixva_rates_section_base', 'pixva-rates' );
	add_settings_section( 'pixva_rates_brand', esc_html__( 'ضریب هر برند (۰٫۵ تا ۳)', 'pixva' ), 'pixva_rates_section_brand', 'pixva-rates' );
}
add_action( 'admin_init', 'pixva_register_rates_settings' );

/**
 * پاکسازی و اعتبارسنجی نرخ‌های ارسالی قبل از ذخیره.
 *
 * ضریب کلی و ضریب برندها بین ۰٫۵ تا ۳ نگه داشته می‌شوند؛
 * مبالغ نامنفی می‌شوند و سقف هر خدمت کمتر از کف آن نمی‌ماند.
 *
 * @param mixed $input ورودی خام فرم.
 * @return array نرخ‌های تمیز برای ذخیره در wp_options.
 */
function pixva_sanitize_rates_1405( $input ) {
	$defaults = pixva_default_rates_1405();
	$src      = is_array( $input ) ? $input : array();
	$stored   = get_option( 'pixva_rates_1405', array() );
	if ( ! is_array( $stored ) ) {
		$stored = array();
	}
	$clean = array();

	$global = isset( $src['global'] ) ? (float) $src['global'] : (float) $defaults['global'];
	if ( isset( $stored['global'] ) && ! isset( $src['global'] ) ) {
		$global = (float) $stored['global'];
	}
	if ( $global < 0.5 ) {
		$global = 0.5;
	}
	if ( $global > 3 ) {
		$global = 3;
	}
	$clean['global'] = round( $global, 2 );

	$expert_min = isset( $src['expert_min'] ) ? absint( $src['expert_min'] ) : (int) $defaults['expert_min'];
	$expert_max = isset( $src['expert_max'] ) ? absint( $src['expert_max'] ) : (int) $defaults['expert_max'];
	if ( $expert_max < $expert_min ) {
		$expert_max = $expert_min;
	}
	$clean['expert_min'] = $expert_min;
	$clean['expert_max'] = $expert_max;

	$clean['base'] = array();
	foreach ( $defaults['base'] as $key => $pair ) {
		$min = (int) $pair[0];
		$max = (int) $pair[1];
		if ( isset( $src['base'][ $key ] ) && is_array( $src['base'][ $key ] ) ) {
			$min = isset( $src['base'][ $key ][0] ) ? absint( $src['base'][ $key ][0] ) : $min;
			$max = isset( $src['base'][ $key ][1] ) ? absint( $src['base'][ $key ][1] ) : $max;
		} elseif ( isset( $stored['base'][ $key ] ) && is_array( $stored['base'][ $key ] ) ) {
			$min = isset( $stored['base'][ $key ][0] ) ? absint( $stored['base'][ $key ][0] ) : $min;
			$max = isset( $stored['base'][ $key ][1] ) ? absint( $stored['base'][ $key ][1] ) : $max;
		}
		if ( $max < $min ) {
			$max = $min;
		}
		$clean['base'][ $key ] = array( $min, $max );
	}

	$clean['brand'] = array();
	foreach ( $defaults['brand'] as $key => $coef ) {
		$value = (float) $coef;
		if ( isset( $src['brand'][ $key ] ) ) {
			$value = (float) $src['brand'][ $key ];
		} elseif ( isset( $stored['brand'][ $key ] ) ) {
			$value = (float) $stored['brand'][ $key ];
		}
		if ( $value < 0.5 ) {
			$value = 0.5;
		}
		if ( $value > 3 ) {
			$value = 3;
		}
		$clean['brand'][ $key ] = round( $value, 2 );
	}

	return $clean;
}

/**
 * سکشن تنظیمات کلی: ضریب کلی و هزینه کارشناسی.
 *
 * @return void
 */
function pixva_rates_section_general() {
	$rates = pixva_get_rates();
	?>
	<table class="form-table" role="presentation">
		<tbody>
			<tr>
				<th scope="row"><label for="pixva_global"><?php esc_html_e( 'ضریب کلی (۰٫۵ تا ۳)', 'pixva' ); ?></label></th>
				<td><input type="number" step="0.05" min="0.5" max="3" id="pixva_global" name="pixva_rates_1405[global]" value="<?php echo esc_attr( $rates['global'] ); ?>" class="regular-text"></td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'هزینه کارشناسی (تومان)', 'pixva' ); ?></th>
				<td>
					<label><?php esc_html_e( 'کف', 'pixva' ); ?> <input type="number" min="0" step="10000" name="pixva_rates_1405[expert_min]" value="<?php echo esc_attr( $rates['expert_min'] ); ?>"></label>
					<label><?php esc_html_e( 'سقف', 'pixva' ); ?> <input type="number" min="0" step="10000" name="pixva_rates_1405[expert_max]" value="<?php echo esc_attr( $rates['expert_max'] ); ?>"></label>
					<p class="description"><?php esc_html_e( 'پیش‌فرض ۱۸۰ تا ۳۵۰ هزار تومان، جداگانه از بازه تعمیر.', 'pixva' ); ?> <?php echo esc_html( pixva_panel_replace_warning() ); ?>.</p>
				</td>
			</tr>
		</tbody>
	</table>
	<?php
}

/**
 * سکشن کف و سقف پایه هر خدمت.
 *
 * @return void
 */
function pixva_rates_section_base() {
	$rates    = pixva_get_rates();
	$problems = function_exists( 'pixva_problem_catalog' ) ? pixva_problem_catalog() : array();
	?>
	<table class="widefat striped">
		<thead>
			<tr>
				<th><?php esc_html_e( 'خدمت', 'pixva' ); ?></th>
				<th><?php esc_html_e( 'کف', 'pixva' ); ?></th>
				<th><?php esc_html_e( 'سقف', 'pixva' ); ?></th>
				<th><?php esc_html_e( 'اثر سایز', 'pixva' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( $rates['base'] as $key => $pair ) : ?>
				<tr>
					<td><?php echo esc_html( isset( $problems[ $key ] ) ? $problems[ $key ] : $key ); ?></td>
					<td><input type="number" min="0" step="50000" name="<?php echo esc_attr( 'pixva_rates_1405[base][' . $key . '][0]' ); ?>" value="<?php echo esc_attr( $pair[0] ); ?>"></td>
					<td><input type="number" min="0" step="50000" name="<?php echo esc_attr( 'pixva_rates_1405[base][' . $key . '][1]' ); ?>" value="<?php echo esc_attr( $pair[1] ); ?>"></td>
					<td><?php echo esc_html( isset( $rates['size_mode'][ $key ] ) && 'low' === $rates['size_mode'][ $key ] ? __( 'ملایم (برد و صدا)', 'pixva' ) : __( 'کامل (بک‌لایت و پنل)', 'pixva' ) ); ?></td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
	<?php
}

/**
 * سکشن ضریب هر برند.
 *
 * @return void
 */
function pixva_rates_section_brand() {
	$rates  = pixva_get_rates();
	$brands = function_exists( 'pixva_brand_catalog' ) ? pixva_brand_catalog() : array();
	?>
	<table class="widefat striped">
		<thead>
			<tr>
				<th><?php esc_html_e( 'برند', 'pixva' ); ?></th>
				<th><?php esc_html_e( 'ضریب', 'pixva' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( $rates['brand'] as $key => $coef ) : ?>
				<tr>
					<td><?php echo esc_html( isset( $brands[ $key ] ) ? $brands[ $key ]['fa'] : $key ); ?></td>
					<td><input type="number" step="0.01" min="0.5" max="3" name="<?php echo esc_attr( 'pixva_rates_1405[brand][' . $key . ']' ); ?>" value="<?php echo esc_attr( $coef ); ?>"></td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
	<?php
}

/**
 * بازگشت نرخ‌ها به پیش‌فرض ۱۴۰۵ با POST ایمن.
 *
 * @return string پیام نتیجه یا رشته خالی.
 */
function pixva_handle_rates_reset() {
	if ( ! isset( $_POST['pixva_rates_reset'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- زیر بررسی می‌شود.
		return '';
	}
	if ( ! current_user_can( 'manage_options' ) ) {
		return esc_html__( 'دسترسی غیرمجاز.', 'pixva' );
	}
	check_admin_referer( 'pixva_rates_reset', 'pixva_rates_reset_nonce' );
	delete_option( 'pixva_rates_1405' );
	return esc_html__( 'نرخ‌ها به پیش‌فرض سال ۱۴۰۵ برگشت.', 'pixva' );
}

/**
 * نمایش صفحه نرخ‌نامه.
 *
 * @return void
 */
function pixva_render_rates_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'دسترسی غیرمجاز.', 'pixva' ) );
	}
	$reset_notice = pixva_handle_rates_reset();
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'نرخ‌نامه تعمیرات — سطح سال ۱۴۰۵', 'pixva' ); ?></h1>
		<p><?php esc_html_e( 'این جدول تنها منبع حقیقت قیمت محاسبه‌گر است. قیمت فقط سمت سرور محاسبه می‌شود و در جاوااسکریپت تکرار نشده است.', 'pixva' ); ?></p>
		<?php settings_errors(); ?>
		<?php if ( '' !== $reset_notice ) : ?>
			<div class="notice notice-success"><p><?php echo esc_html( $reset_notice ); ?></p></div>
		<?php endif; ?>
		<form method="post" action="options.php">
			<?php
			settings_fields( 'pixva_rates_group' );
			do_settings_sections( 'pixva-rates' );
			submit_button( esc_html__( 'ذخیره نرخ‌نامه', 'pixva' ) );
			?>
		</form>
		<form method="post" action="">
			<?php wp_nonce_field( 'pixva_rates_reset', 'pixva_rates_reset_nonce' ); ?>
			<p class="submit">
				<input type="submit" name="pixva_rates_reset" class="button button-secondary" value="<?php esc_attr_e( 'بازگشت به پیش‌فرض ۱۴۰۵', 'pixva' ); ?>" onclick="return confirm('<?php echo esc_js( __( 'همه نرخ‌ها به پیش‌فرض ۱۴۰۵ برگردد؟', 'pixva' ) ); ?>');">
			</p>
		</form>
		<h2><?php esc_html_e( 'نمونه صحت', 'pixva' ); ?></h2>
		<p><?php esc_html_e( 'سامسونگ ۵۵ اینچ LED، تعویض بک‌لایت: حدود ۶٫۸ تا ۱۱٫۶ میلیون تومان. برد پاور همان سایز: حدود ۲٫۳ تا ۵٫۲ میلیون تومان.', 'pixva' ); ?></p>
	</div>
	<?php
}

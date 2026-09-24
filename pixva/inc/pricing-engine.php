<?php
/**
 * موتور قیمت سمت سرور محاسبه‌گر ۱۴۰۵
 *
 * تنها منبع حقیقت قیمت. هیچ فرمولی در جاوااسکریپت تکرار نشده است؛
 * خروجی همیشه از همین توابع PHP می‌آید و به‌صورت JSON برگردانده می‌شود.
 *
 * فرمول:
 *   Min = round(Base_Min × Global × Brand × Tech × SizeFactor / 50000) × 50000
 *   Max = round(Base_Max × Global × Brand × Tech × SizeFactor / 50000) × 50000
 * هزینه کارشناسی (۱۸۰ تا ۳۵۰ هزار تومان) جداگانه اعلام می‌شود و به بازه اضافه نمی‌گردد.
 * تعویض کامل پنل خارج از جدول است و هشدار جداگانه دارد.
 *
 * @package Pixva
 * @since   1.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'pixva_default_rates_1405' ) ) {
	/**
	 * نرخ‌های پیش‌فرض سال ۱۴۰۵ — تنها منبع حقیقت قیمت.
	 *
	 * پایه‌ها برای سایز ۳۲ اینچ LED با ضریب برند ۱٫۰ تعریف شده‌اند.
	 * نمونه صحت: سامسونگ ۵۵ LED تعویض بک‌لایت حدود ۶٫۸ تا ۱۱٫۶ میلیون،
	 * و برد پاور همان سایز حدود ۲٫۳ تا ۵٫۲ میلیون تومان می‌شود.
	 * تعویض کامل پنل خارج از جدول است و جداگانه اطلاع‌رسانی می‌شود.
	 *
	 * @return array
	 */
	function pixva_default_rates_1405() {
		return array(
			// ضریب کلی قابل تنظیم از ۰٫۵ تا ۳.
			'global'     => 1.0,
			// هزینه کارشناسی و عیب‌یابی (تومان).
			'expert_min' => 180000,
			'expert_max' => 350000,
			// کف و سقف پایه هر خدمت برای ۳۲ اینچ (تومان).
			'base'       => array(
				'no_picture' => array( 3200000, 6500000 ), // بی‌تصویری (صدا دارد).
				'lines'      => array( 3800000, 7500000 ), // خطوط عمودی/افقی.
				'no_power'   => array( 1600000, 3800000 ), // خاموشی کامل.
				'no_sound'   => array( 1500000, 3200000 ), // قطع صدا.
				'blink'      => array( 1700000, 4000000 ), // چشمک چراغ پاور.
				'water'      => array( 2500000, 6000000 ), // آب‌خوردگی.
				'backlight'  => array( 4200000, 7200000 ), // تعویض بک‌لایت.
				'panel'      => array( 5000000, 9000000 ), // تعمیر پنل با بندینگ.
				'mainboard'  => array( 2200000, 5000000 ), // برد اصلی.
				'powerboard' => array( 1800000, 4100000 ), // برد پاور.
			),
			// ضریب هر برند.
			'brand'      => array(
				'samsung'    => 1.15,
				'lg'         => 1.12,
				'sony'       => 1.25,
				'panasonic'  => 1.20,
				'sharp'      => 1.18,
				'toshiba'    => 1.15,
				'philips'    => 1.18,
				'hitachi'    => 1.10,
				'tcl'        => 1.05,
				'hisense'    => 1.05,
				'xiaomi'     => 1.08,
				'haier'      => 1.02,
				'skyworth'   => 1.02,
				'blaupunkt'  => 1.10,
				'snowa'      => 1.00,
				'xvision'    => 1.00,
				'gplus'      => 1.00,
				'emersan'    => 1.00,
				'pakshoma'   => 1.00,
				'daewoo'     => 1.08,
				'tuv'        => 1.00,
				'pars'       => 1.00,
				'beko'       => 1.08,
				'marshal'    => 1.00,
			),
			// ضریب تکنولوژی صفحه.
			'tech'       => array(
				'led'      => 1.00,
				'qled'     => 1.22,
				'oled'     => 1.55,
				'plasma'   => 1.18,
				'microled' => 1.80,
			),
			// ضریب کامل سایز (برای بک‌لایت و پنل).
			'size'       => array(
				'32' => 1.00,
				'40' => 1.12,
				'43' => 1.18,
				'50' => 1.30,
				'55' => 1.40,
				'65' => 1.62,
				'75' => 1.90,
				'85' => 2.20,
			),
			// حالت اثر سایز هر خدمت: full یعنی کامل، low یعنی ملایم‌شده برای برد و صدا.
			'size_mode'  => array(
				'no_picture' => 'full',
				'lines'      => 'full',
				'no_power'   => 'low',
				'no_sound'   => 'low',
				'blink'      => 'low',
				'water'      => 'low',
				'backlight'  => 'full',
				'panel'      => 'full',
				'mainboard'  => 'low',
				'powerboard' => 'low',
			),
			'days'       => array(
				'no_picture' => '2 تا 4 روز کاری',
				'lines'      => '3 تا 6 روز کاری',
				'no_power'   => '1 تا 3 روز کاری',
				'no_sound'   => '1 تا 2 روز کاری',
				'blink'      => '1 تا 3 روز کاری',
				'water'      => '3 تا 7 روز کاری',
				'backlight'  => '1 تا 2 روز کاری',
				'panel'      => '4 تا 8 روز کاری',
				'mainboard'  => '2 تا 5 روز کاری',
				'powerboard' => '1 تا 3 روز کاری',
			),
		);
	}
}

if ( ! function_exists( 'pixva_get_rates' ) ) {
	/**
	 * خواندن نرخ‌های ذخیره‌شده در پیشخوان، ادغام با پیش‌فرض ۱۴۰۵.
	 *
	 * @return array
	 */
	function pixva_get_rates() {
		$defaults = pixva_default_rates_1405();
		$stored   = get_option( 'pixva_rates_1405', array() );
		if ( ! is_array( $stored ) ) {
			$stored = array();
		}
		// ضریب کلی بین ۰٫۵ تا ۳ نگه داشته می‌شود.
		$global = isset( $stored['global'] ) ? (float) $stored['global'] : $defaults['global'];
		if ( $global < 0.5 ) {
			$global = 0.5;
		}
		if ( $global > 3 ) {
			$global = 3;
		}
		$rates             = $defaults;
		$rates['global']   = round( $global, 2 );
		$rates['expert_min'] = isset( $stored['expert_min'] ) ? max( 0, (int) $stored['expert_min'] ) : $defaults['expert_min'];
		$rates['expert_max'] = isset( $stored['expert_max'] ) ? max( 0, (int) $stored['expert_max'] ) : $defaults['expert_max'];
		if ( $rates['expert_max'] < $rates['expert_min'] ) {
			$rates['expert_max'] = $rates['expert_min'];
		}
		if ( isset( $stored['base'] ) && is_array( $stored['base'] ) ) {
			foreach ( $defaults['base'] as $key => $pair ) {
				if ( isset( $stored['base'][ $key ] ) && is_array( $stored['base'][ $key ] ) ) {
					$min = isset( $stored['base'][ $key ][0] ) ? max( 0, (int) $stored['base'][ $key ][0] ) : (int) $pair[0];
					$max = isset( $stored['base'][ $key ][1] ) ? max( 0, (int) $stored['base'][ $key ][1] ) : (int) $pair[1];
					if ( $max < $min ) {
						$max = $min;
					}
					$rates['base'][ $key ] = array( $min, $max );
				}
			}
		}
		if ( isset( $stored['brand'] ) && is_array( $stored['brand'] ) ) {
			foreach ( $defaults['brand'] as $key => $coef ) {
				if ( isset( $stored['brand'][ $key ] ) ) {
					$value = (float) $stored['brand'][ $key ];
					if ( $value < 0.5 ) {
						$value = 0.5;
					}
					if ( $value > 3 ) {
						$value = 3;
					}
					$rates['brand'][ $key ] = round( $value, 2 );
				}
			}
		}
		return $rates;
	}
}

if ( ! function_exists( 'pixva_size_factor' ) ) {
	/**
	 * ضریب سایز با توجه به حالت خدمت.
	 *
	 * برای برد پاور، برد اصلی و صدا اثر سایز ملایم می‌شود تا
	 * تعمیر برد روی تلویزیون بزرگ بی‌دلیل نجومی نشود.
	 *
	 * @param array  $rates   نرخ‌های جاری.
	 * @param string $problem کلید خدمت.
	 * @param string $size    سایز اینچ.
	 * @return float
	 */
	function pixva_size_factor( $rates, $problem, $size ) {
		$full = isset( $rates['size'][ $size ] ) ? (float) $rates['size'][ $size ] : 1.0;
		$mode = isset( $rates['size_mode'][ $problem ] ) ? $rates['size_mode'][ $problem ] : 'full';
		if ( 'low' === $mode ) {
			// فقط ۲۵ درصد اختلاف سایز اعمال می‌شود.
			return 1.0 + ( $full - 1.0 ) * 0.25;
		}
		return $full;
	}
}

if ( ! function_exists( 'pixva_pricing_matrix' ) ) {
	/**
	 * ماتریس ضرایب و پایه‌های قیمت محاسبه‌گر (منبع حقیقت سمت سرور).
	 *
	 * این تابع نرخ ذخیره‌شده در پیشخوان را برمی‌گرداند و
	 * سازگاری با فراخوان‌های قبلی قالب را حفظ می‌کند.
	 *
	 * @return array
	 */
	function pixva_pricing_matrix() {
		return pixva_get_rates();
	}
}

/**
 * محاسبه تخمین قیمت بر اساس ماتریس سمت سرور.
 *
 * @param string $brand   کلید برند.
 * @param string $tech    کلید تکنولوژی.
 * @param string $size    سایز اینچ.
 * @param string $problem کلید مشکل.
 * @return array{min:int,max:int,days:string}|null
 */
function pixva_calculate_estimate( $brand, $tech, $size, $problem ) {
	$rates = pixva_get_rates();

	if ( ! isset( $rates['base'][ $problem ], $rates['brand'][ $brand ], $rates['tech'][ $tech ], $rates['size'][ $size ] ) ) {
		return null;
	}

	// ضریب سایز برای برد و صدا ملایم می‌شود تا روی سایز بزرگ نجومی نشود.
	$size_factor = pixva_size_factor( $rates, $problem, $size );
	$coeff       = (float) $rates['global'] * (float) $rates['brand'][ $brand ] * (float) $rates['tech'][ $tech ] * (float) $size_factor;
	$min         = (int) ( round( ( $rates['base'][ $problem ][0] * $coeff ) / 50000 ) * 50000 );
	$max         = (int) ( round( ( $rates['base'][ $problem ][1] * $coeff ) / 50000 ) * 50000 );

	if ( $max < $min ) {
		$max = $min;
	}

	return array(
		'min'  => $min,
		'max'  => $max,
		'days' => isset( $rates['days'][ $problem ] ) ? (string) $rates['days'][ $problem ] : '',
	);
}

if ( ! function_exists( 'pixva_panel_replace_warning' ) ) {
	/**
	 * متن هشدار تعویض کامل پنل (خارج از جدول محاسبه).
	 *
	 * @return string
	 */
	function pixva_panel_replace_warning() {
		return __( 'تعویض کامل پنل خارج از جدول محاسبه بوده و اغلب از ۱۰ میلیون تومان شروع می‌شود', 'pixva' );
	}
}

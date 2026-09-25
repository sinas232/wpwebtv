<?php
/**
 * موتور سمت سرور محاسبه‌گر قیمت ۱۴۰۵ (inc/pricing-engine.php)
 *
 * منبع حقیقت قیمت‌گذاری پیکسوا. هیچ فرمول قیمتی در جاوااسکریپت نیست؛
 * همه محاسبات در همین پرونده PHP انجام و به‌صورت JSON خروجی داده می‌شود.
 *
 * فرمول محاسباتی (نرخ پایه بازار ۱۴۰۵ — Master Specification v25.0):
 *
 *   SizeFactor  = 1.0 + ((Size − 32) / 32)^1.35      (برای سایز ۳۲ ضریب ۱٫۰)
 *   Price_Min   = (Base_Min × BrandMultiplier × TechFactor × SizeFactor_Min) + DiagnosticFee_Min
 *   Price_Max   = (Base_Max × BrandMultiplier × TechFactor × SizeFactor_Max) + DiagnosticFee_Max
 *   Price_Min   = max(Price_Min , Floor_Min)          (کف قیمت نرخ‌نامه رعایت می‌شود)
 *
 * کف قیمت چهار خدمت اصلی نرخ‌نامه ۱۴۰۵ (۳۲ اینچ):
 *   تعویض کامل بک‌لایت ................ ۸٬۰۰۰٬۰۰۰ تومان
 *   تعمیر/تعویض برد تغذیه (Power) ..... ۹٬۵۰۰٬۰۰۰ تومان
 *   تعمیر/تعویض برد اصلی (Mainboard) .. ۱۲٬۵۰۰٬۰۰۰ تومان
 *   ترمیم لیزری پنل / آب‌خوردگی (T-Con) ۱۶٬۰۰۰٬۰۰۰ تومان
 *
 * - «Base» قیمت پایه هر خدمت است که ضریب کلی نرخ (۰٫۵ تا ۳) و ضریب تکنولوژی صفحه
 *   روی آن اعمال می‌شود و سپس وارد فرمول بالا می‌شود.
 * - «SizeFactor» از جدول سایز خوانده می‌شود و کنترل می‌شود:
 *   برای تعویض بک‌لایت به‌صورت خطی/کامل (strength=1) و برای تعمیر برد پاور، برد اصلی
 *   و صدا با نرخ ضعیف‌تر (strength=0.45) تا قیمت برد روی تلویزیون‌های ۷۵ و ۸۵ اینچ
 *   بی‌دلیل نجومی نشود.
 * - «DiagnosticFee» هزینه کارشناسی/عیب‌یابی است (پیش‌فرض ۱۸۰٬۰۰۰ تا ۳۵۰٬۰۰۰ تومان).
 * - انتخاب «تعویض کامل پنل» خارج از جدول محاسبه است و فقط پیام هشدار برمی‌گرداند.
 *
 * @package Pixva
 * @since   1.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // خروج مستقیم غیرمجاز.
}

/*
 * ---------------------------------------------------------------------------
 * ۱) مقادیر پایه سال ۱۴۰۵
 * ---------------------------------------------------------------------------
 */

if ( ! function_exists( 'pixva_pricing_service_defaults' ) ) {
	/**
	 * قیمت پایه (حداقل/حداکثر) و کلاس ضریب سایز هر خدمت.
	 *
	 * کلاس‌های ضریب سایز:
	 * - full : اعمال خطی/کامل ضریب سایز (تعویض بک‌لایت).
	 * - weak : اعمال نرخ ضعیف‌تر ضریب سایز (برد پاور، برد اصلی، صدا).
	 * - mid  : نرخ متعادل برای سایر خدمات.
	 *
	 * @return array<string, array{min:int, max:int, size_class:string}>
	 */
	function pixva_pricing_service_defaults() {
		return array(
			/*
			 * چهار خدمت اصلی نرخ‌نامه ۱۴۰۵ مطابق مستر اسپک v25.0.
			 * «min» همان کف قیمت ۳۲ اینچ است و «max» طوری کالیبره شده که برای
			 * برندهای پرفروش بازار (ضریب ۱٫۱۸ مانند سامسونگ و ال‌جی) بازه برآورد
			 * دقیقاً روی جدول مرجع بازار ۱۴۰۵ بنشیند:
			 *   بک‌لایت   ۵۵ اینچ: ۱۵٫۵ تا ۲۱ میلیون   | ۶۵ تا ۸۵ اینچ: ۲۶ تا ۴۲ میلیون
			 *   برد پاور  ۵۵ اینچ: ۱۸ تا ۲۶ میلیون     | ۶۵ تا ۸۵ اینچ: ۳۲ تا ۴۸ میلیون
			 *   برد اصلی  ۵۵ اینچ: ۲۵ تا ۳۶ میلیون     | ۶۵ تا ۸۵ اینچ: ۴۵ تا ۶۸ میلیون
			 *   T-Con     ۵۵ اینچ: ۳۲ تا ۴۸ میلیون     | ۶۵ تا ۸۵ اینچ: ۵۸ تا ۸۵ میلیون
			 *
			 * part_share = سهم قطعه فابریک در قیمت (باقی اجرت تخصصی و ذخیره گارانتی).
			 */
			'backlight'  => array(
				'min'        => 8000000,
				'max'        => 10000000,
				'size_class' => 'full',
				'part_share' => 0.62,
			),
			'powerboard' => array(
				'min'        => 9500000,
				'max'        => 12500000,
				'size_class' => 'full',
				'part_share' => 0.48,
			),
			'mainboard'  => array(
				'min'        => 12500000,
				'max'        => 17200000,
				'size_class' => 'full',
				'part_share' => 0.44,
			),
			'water'      => array(
				'min'        => 16000000,
				'max'        => 23000000,
				'size_class' => 'full',
				'part_share' => 0.55,
			),
			'lines'      => array(
				'min'        => 16000000,
				'max'        => 20000000,
				'size_class' => 'full',
				'part_share' => 0.55,
			),
			'panel'      => array(
				'min'        => 16000000,
				'max'        => 20000000,
				'size_class' => 'full',
				'part_share' => 0.55,
			),
			'no_power'   => array(
				'min'        => 9500000,
				'max'        => 12500000,
				'size_class' => 'full',
				'part_share' => 0.48,
			),
			'no_picture' => array(
				'min'        => 9000000,
				'max'        => 14000000,
				'size_class' => 'mid',
				'part_share' => 0.46,
			),
			'blink'      => array(
				'min'        => 8500000,
				'max'        => 12000000,
				'size_class' => 'mid',
				'part_share' => 0.45,
			),
			'no_sound'   => array(
				'min'        => 4500000,
				'max'        => 8000000,
				'size_class' => 'weak',
				'part_share' => 0.40,
			),
		);
	}
}

if ( ! function_exists( 'pixva_pricing_brand_defaults' ) ) {
	/**
	 * ضریب اختصاصی ۲۴ برند پشتیبانی‌شده (مقادیر پایه ۱۴۰۵).
	 *
	 * @return array<string, float>
	 */
	function pixva_pricing_brand_defaults() {
		return array(
			'sony'       => 1.35,
			'samsung'    => 1.18,
			'lg'         => 1.18,
			'snowa'      => 0.95,
			'xvision'    => 0.95,
			'gplus'      => 0.95,
			'tcl'        => 0.95,
			'hisense'    => 0.95,
			'xiaomi'     => 1.00,
			'panasonic'  => 1.15,
			'philips'    => 1.15,
			'sharp'      => 1.15,
			'toshiba'    => 1.05,
			'haier'      => 0.95,
			'daewoo'     => 0.95,
			'jvc'        => 1.05,
			'hitachi'    => 1.10,
			'sanyo'      => 0.95,
			'grundig'    => 1.00,
			'vestel'     => 0.95,
			'skyworth'   => 0.95,
			'konka'      => 0.95,
			'changhong'  => 0.95,
			'marshal'    => 0.95,
		);
	}
}

if ( ! function_exists( 'pixva_pricing_size_factor_calc' ) ) {
	/**
	 * محاسبه ضریب سایز غیرخطی طبق فرمول مستر اسپک v21.0:
	 * SizeFactor = 1.0 + ((Size - 32) / 32)^1.35
	 *
	 * @param int|float $size سایز به اینچ.
	 * @return float
	 */
	function pixva_pricing_size_factor_calc( $size ) {
		$size = (float) $size;
		if ( $size <= 32 ) {
			return 1.0;
		}
		$ratio = ( $size - 32 ) / 32;
		return 1.0 + pow( $ratio, 1.35 );
	}
}

if ( ! function_exists( 'pixva_pricing_size_factors' ) ) {
	/**
	 * جدول ضریب سایز: هر اینچ یک بازه [حداقل، حداکثر] دارد (مبتنی بر فرمول توانی v21.0).
	 *
	 * @return array<string, array{0:float, 1:float}>
	 */
	function pixva_pricing_size_factors() {
		$sizes = array( '32', '43', '50', '55', '65', '75', '85', '98' );
		$out   = array();
		foreach ( $sizes as $s ) {
			$base_factor = pixva_pricing_size_factor_calc( (float) $s );
			$out[ $s ]   = array(
				round( $base_factor, 3 ),
				round( $base_factor * 1.08, 3 ),
			);
		}
		return $out;
	}
}

if ( ! function_exists( 'pixva_pricing_tech_factors' ) ) {
	/**
	 * ضریب تکنولوژی صفحه. این ضریب روی «پایه» اعمال می‌شود (جزءِ Base فرمول).
	 *
	 * @return array<string, float>
	 */
	function pixva_pricing_tech_factors() {
		return array(
			'led'      => 1.00,
			'qled'     => 1.15,
			'oled'     => 1.35,
			'plasma'   => 1.10,
			'microled' => 1.60,
		);
	}
}

if ( ! function_exists( 'pixva_pricing_size_strength' ) ) {
	/**
	 * نرخ اعمال ضریب سایز بر اساس کلاس خدمت.
	 *
	 * full = اعمال خطی/کامل (بک‌لایت) — weak = نرخ ضعیف‌تر (برد پاور، برد اصلی، صدا).
	 *
	 * @param string $size_class کلاس (full|weak|mid).
	 * @return float
	 */
	function pixva_pricing_size_strength( $size_class ) {
		$map = array(
			'full' => 1.00,
			'mid'  => 0.70,
			'weak' => 0.45,
		);
		$size_class = (string) $size_class;
		return isset( $map[ $size_class ] ) ? (float) $map[ $size_class ] : 0.70;
	}
}

if ( ! function_exists( 'pixva_pricing_defaults' ) ) {
	/**
	 * مقدارهای پایه نرخ‌نامه سال ۱۴۰۵ (بازگشتی دکمه Reset to Defaults).
	 *
	 * @return array
	 */
	function pixva_pricing_defaults() {
		return array(
			'global_multiplier' => 1.0,
			'diagnostic_min'    => 180000,
			'diagnostic_max'    => 350000,
			'services'          => array_map(
				static function ( $row ) {
					return array(
						'min' => (int) $row['min'],
						'max' => (int) $row['max'],
					);
				},
				pixva_pricing_service_defaults()
			),
			'brands'            => pixva_pricing_brand_defaults(),
		);
	}
}

if ( ! function_exists( 'pixva_panel_replacement_warning' ) ) {
	/**
	 * متن هشدار تعویض کامل پنل (خارج از جدول محاسبه).
	 *
	 * @return string
	 */
	function pixva_panel_replacement_warning() {
		return __( 'تعویض کامل پنل خارج از جدول محاسبه بوده و اغلب از ۱۰ میلیون تومان شروع می‌شود.', 'pixva' );
	}
}

if ( ! function_exists( 'pixva_pricing_days_map' ) ) {
	/**
	 * زمان تقریبی هر خدمت (روز کاری).
	 *
	 * @return array<string, string>
	 */
	function pixva_pricing_days_map() {
		return array(
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
		);
	}
}

/*
 * ---------------------------------------------------------------------------
 * ۲) تنظیمات ذخیره‌شده نرخ‌نامه (wp_options)
 * ---------------------------------------------------------------------------
 */

if ( ! function_exists( 'pixva_pricing_sanitize_settings' ) ) {
	/**
	 * پاکسازی و اعتبارسنجی ورودی‌های نرخ‌نامه (مورد استفاده Settings API).
	 *
	 * @param mixed $input ورودی خام.
	 * @return array
	 */
	function pixva_pricing_sanitize_settings( $input ) {
		$out     = pixva_pricing_defaults();
		$input   = is_array( $input ) ? $input : array();
		$float_  = static function ( $value, $lo, $hi, $fallback ) {
			$value = is_numeric( $value ) ? (float) $value : (float) $fallback;
			return max( $lo, min( $hi, $value ) );
		};
		$int_    = static function ( $value, $lo, $hi, $fallback ) {
			$value = is_numeric( $value ) ? (int) $value : (int) $fallback;
			return max( $lo, min( $hi, $value ) );
		};

		// ضریب کلی قیمت: ۰٫۵ تا ۳.
		$out['global_multiplier'] = $float_( $input['global_multiplier'] ?? '', 0.5, 3.0, $out['global_multiplier'] );

		// هزینه کارشناسی (بازه پیش‌فرض ۱۸۰ تا ۳۵۰ هزار تومان).
		$out['diagnostic_min'] = $int_( $input['diagnostic_min'] ?? '', 50000, 10000000, 180000 );
		$out['diagnostic_max'] = $int_( $input['diagnostic_max'] ?? '', 50000, 10000000, 350000 );
		if ( $out['diagnostic_max'] < $out['diagnostic_min'] ) {
			$out['diagnostic_max'] = $out['diagnostic_min'];
		}

		// قیمت پایه خدمات.
		$services_in = isset( $input['services'] ) && is_array( $input['services'] ) ? $input['services'] : array();
		foreach ( $out['services'] as $key => $row ) {
			$in_min = $services_in[ $key ]['min'] ?? '';
			$in_max = $services_in[ $key ]['max'] ?? '';
			$min    = $int_( $in_min, 0, 50000000, $row['min'] );
			$max    = $int_( $in_max, 0, 50000000, $row['max'] );
			if ( $max < $min ) {
				$max = $min;
			}
			$out['services'][ $key ] = array(
				'min' => $min,
				'max' => $max,
			);
		}

		// ضریب ۲۴ برند.
		$brands_in = isset( $input['brands'] ) && is_array( $input['brands'] ) ? $input['brands'] : array();
		foreach ( $out['brands'] as $key => $value ) {
			$out['brands'][ $key ] = $float_( $brands_in[ $key ] ?? '', 0.1, 3.0, $value );
		}

		return $out;
	}
}

if ( ! function_exists( 'pixva_pricing_settings' ) ) {
	/**
	 * تنظیمات مؤثر نرخ‌نامه (ذخیره‌شده روی مقادیر پایه ادغام می‌شود).
	 *
	 * @return array
	 */
	function pixva_pricing_settings() {
		$defaults = pixva_pricing_defaults();
		$saved    = get_option( 'pixva_pricing_settings', array() );
		if ( ! is_array( $saved ) ) {
			$saved = array();
		}
		$settings = wp_parse_args( $saved, $defaults );

		// تضمین کامل بودن زیرمجموعه‌ها حتی اگر گزینه قدیمی ناقص باشد.
		$settings['services'] = wp_parse_args( is_array( $settings['services'] ?? null ) ? $settings['services'] : array(), $defaults['services'] );
		$settings['brands']   = wp_parse_args( is_array( $settings['brands'] ?? null ) ? $settings['brands'] : array(), $defaults['brands'] );

		// مهار خروج از محدوده حتی برای داده دست‌کاری‌شده در دیتابیس.
		return pixva_pricing_sanitize_settings( $settings );
	}
}

/*
 * ---------------------------------------------------------------------------
 * ۳) موتور محاسبه قیمت (بدون هیچ منطق قیمتی در جاوااسکریپت)
 * ---------------------------------------------------------------------------
 */

if ( ! function_exists( 'pixva_calculate_estimate' ) ) {
	/**
	 * محاسبه برآورد هزینه تعمیر کاملاً در سمت سرور.
	 *
	 * Price_Min = (Base_Min × BrandMultiplier × SizeFactor_Min) + DiagnosticFee_Min
	 * Price_Max = (Base_Max × BrandMultiplier × SizeFactor_Max) + DiagnosticFee_Max
	 *
	 * ضریب کلی نرخ و ضریب تکنولوژی روی Base اعمال می‌شوند؛ ضریب سایز بسته به
	 * کلاس خدمت (full برای بک‌لایت، weak برای برد پاور/برد اصلی/صدا) کنترل می‌شود.
	 *
	 * @param string $brand   کلید برند.
	 * @param string $tech    کلید تکنولوژی.
	 * @param string $size    سایز اینچ.
	 * @param string $problem کلید مشکل/خدمت.
	 * @return array|null
	 */
	function pixva_calculate_estimate( $brand, $tech, $size, $problem ) {
		$brand   = sanitize_key( $brand );
		$tech    = sanitize_key( $tech );
		$size    = sanitize_key( $size );
		$problem = sanitize_key( $problem );

		// تعویض کامل پنل خارج از جدول محاسبه است.
		if ( 'panel_replace' === $problem ) {
			return array(
				'min'               => 0,
				'max'               => 0,
				'days'              => '',
				'panel_replacement' => true,
				'warning'           => pixva_panel_replacement_warning(),
			);
		}

		$settings = pixva_pricing_settings();
		$services = pixva_pricing_service_defaults();
		$sizes    = pixva_pricing_size_factors();
		$techs    = pixva_pricing_tech_factors();
		$days     = pixva_pricing_days_map();

		if ( ! isset( $settings['services'][ $problem ], $services[ $problem ], $settings['brands'][ $brand ], $techs[ $tech ], $sizes[ $size ] ) ) {
			return null;
		}

		// پایه مؤثر = قیمت پایه خدمت × ضریب کلی نرخ × ضریب تکنولوژی (جزءِ Base فرمول).
		$base_min = (float) $settings['services'][ $problem ]['min'] * (float) $settings['global_multiplier'] * (float) $techs[ $tech ];
		$base_max = (float) $settings['services'][ $problem ]['max'] * (float) $settings['global_multiplier'] * (float) $techs[ $tech ];

		// کنترل ضریب سایز: خطی/کامل برای بک‌لایت، ضعیف برای برد پاور، برد اصلی و صدا.
		$strength = pixva_pricing_size_strength( $services[ $problem ]['size_class'] );
		$sf_min   = 1 + ( (float) $sizes[ $size ][0] - 1 ) * $strength;
		$sf_max   = 1 + ( (float) $sizes[ $size ][1] - 1 ) * $strength;

		$brand_mult = (float) $settings['brands'][ $brand ];

		// فرمول رسمی نرخ‌نامه ۱۴۰۵.
		$price_min = ( $base_min * $brand_mult * $sf_min ) + (float) $settings['diagnostic_min'];
		$price_max = ( $base_max * $brand_mult * $sf_max ) + (float) $settings['diagnostic_max'];

		// گرد کردن به پله ۵۰ هزار تومان (حداقل رو به پایین، حداکثر رو به بالا).
		$price_min = (int) ( floor( $price_min / 50000 ) * 50000 );
		$price_max = (int) ( ceil( $price_max / 50000 ) * 50000 );

		// کف قیمت نرخ‌نامه: برآورد هیچ‌گاه از پایه ۳۲ اینچ خدمت کمتر نمی‌شود
		// (مثلاً تعویض کامل بک‌لایت هرگز زیر ۸٬۰۰۰٬۰۰۰ تومان اعلام نمی‌گردد).
		$floor_min = (int) $settings['services'][ $problem ]['min'];
		if ( $price_min < $floor_min ) {
			$price_min = $floor_min;
		}

		if ( $price_max < $price_min ) {
			$price_max = $price_min;
		}

		$estimate = array(
			'min'               => $price_min,
			'max'               => $price_max,
			'days'              => isset( $days[ $problem ] ) ? (string) $days[ $problem ] : '',
			'panel_replacement' => false,
			'warning'           => '',
			'service'           => $problem,
			'brand'             => $brand,
			'tech'              => $tech,
			'size'              => $size,
			'size_factor'       => round( $sf_min, 3 ),
			'brand_multiplier'  => round( $brand_mult, 2 ),
			'tech_factor'       => (float) $techs[ $tech ],
		);

		$estimate['breakdown'] = pixva_estimate_breakdown( $estimate, $settings, $services[ $problem ] );

		return $estimate;
	}
}

if ( ! function_exists( 'pixva_estimate_breakdown' ) ) {
	/**
	 * تفکیک شفاف برآورد به قطعه، اجرت، ذخیره گارانتی و هزینه کارشناسی.
	 *
	 * همه درصدها از ساختار هزینه واقعی کارگاه می‌آید (part_share هر خدمت) و
	 * محاسبه کاملاً قطعی است.
	 *
	 * @param array $estimate نتیجه pixva_calculate_estimate().
	 * @param array $settings تنظیمات نرخ‌نامه.
	 * @param array $service  تعریف خدمت از مقادیر پایه.
	 * @return array<string, array<string, mixed>>
	 */
	function pixva_estimate_breakdown( $estimate, $settings, $service ) {
		$part_share = isset( $service['part_share'] ) ? (float) $service['part_share'] : 0.5;
		$warranty   = 0.025; // ذخیره گارانتی ۱۸۰ روزه: ۲٫۵٪ از هسته قیمت.

		$rows = array();
		foreach ( array( 'min', 'max' ) as $bound ) {
			$diagnostic = (float) ( 'min' === $bound ? $settings['diagnostic_min'] : $settings['diagnostic_max'] );
			$core       = max( 0, (float) $estimate[ $bound ] - $diagnostic );
			$part       = (int) round( ( $core * $part_share ) / 50000 ) * 50000;
			$warranty_v = (int) round( ( $core * $warranty ) / 50000 ) * 50000;
			$labor      = (int) max( 0, $core - $part - $warranty_v );

			$rows[ $bound ] = array(
				'part'       => $part,
				'labor'      => $labor,
				'warranty'   => $warranty_v,
				'diagnostic' => (int) $diagnostic,
				'total'      => (int) $estimate[ $bound ],
			);
		}

		return array(
			'labels' => array(
				'part'       => __( 'قطعه فابریک', 'pixva' ),
				'labor'      => __( 'اجرت تخصصی کارگاه', 'pixva' ),
				'warranty'   => __( 'ذخیره گارانتی ۱۸۰ روزه', 'pixva' ),
				'diagnostic' => __( 'هزینه کارشناسی و عیب‌یابی', 'pixva' ),
			),
			'values' => $rows,
		);
	}
}

if ( ! function_exists( 'pixva_pricing_market_bands' ) ) {
	/**
	 * جدول بازه‌های مرجع بازار ۱۴۰۵ (مستر اسپک v25.0) برای نرخ‌نامه عمومی.
	 *
	 * این جدول داده مرجع اعلام‌شده کارگاه است و مستقیماً در صفحه نرخ‌نامه و
	 * ابزارهای محاسبه‌گر نمایش داده می‌شود.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	function pixva_pricing_market_bands() {
		$bands = array(
			array(
				'service' => 'backlight',
				'title'   => __( 'تعویض کامل بک‌لایت', 'pixva' ),
				'floor'   => 8000000,
				'size_55' => array( 15500000, 21000000 ),
				'size_65' => array( 26000000, 42000000 ),
				'note'    => __( 'دست کامل فابریک با هیت‌سینک آلومینیومی و اصلاحیه ولتاژ برد پاور.', 'pixva' ),
			),
			array(
				'service' => 'powerboard',
				'title'   => __( 'تعمیر / تعویض برد تغذیه (Power)', 'pixva' ),
				'floor'   => 9500000,
				'size_55' => array( 18000000, 26000000 ),
				'size_65' => array( 32000000, 48000000 ),
				'note'    => __( 'تست ولتاژ استندبای، تعویض ماس‌فت و آی‌سی سوئیچینگ با قطعه اورجینال.', 'pixva' ),
			),
			array(
				'service' => 'mainboard',
				'title'   => __( 'تعمیر / تعویض برد اصلی (Mainboard)', 'pixva' ),
				'floor'   => 12500000,
				'size_55' => array( 25000000, 36000000 ),
				'size_65' => array( 45000000, 68000000 ),
				'note'    => __( 'پروگرام مجدد حافظه eMMC/NAND یا ریبال آی‌سی با دستگاه BGA.', 'pixva' ),
			),
			array(
				'service' => 'water',
				'title'   => __( 'ترمیم لیزری پنل / آب‌خوردگی (T-Con)', 'pixva' ),
				'floor'   => 16000000,
				'size_55' => array( 32000000, 48000000 ),
				'size_65' => array( 58000000, 85000000 ),
				'note'    => __( 'بندینگ مجدد فلت‌های COF زیر میکروسکوپ صنعتی؛ فقط در صورت سالم بودن گلس.', 'pixva' ),
			),
		);

		return apply_filters( 'pixva_pricing_market_bands', $bands );
	}
}

if ( ! function_exists( 'pixva_pricing_reference_table' ) ) {
	/**
	 * جدول مرجع قیمت برای نمایش عمومی: برای هر خدمت و سایز، بازه محاسبه‌شده
	 * با پیکربندی مرجع (برند میانگین بازار و تکنولوژی LED).
	 *
	 * @return array<int, array<string, mixed>>
	 */
	function pixva_pricing_reference_table() {
		$settings  = pixva_pricing_settings();
		$brands    = $settings['brands'];
		$avg_brand = 1.0;
		if ( ! empty( $brands ) ) {
			$avg_brand = round( array_sum( $brands ) / count( $brands ), 3 );
		}

		// برند مرجع: نزدیک‌ترین برند به میانگین ضریب بازار.
		$reference_brand = 'xiaomi';
		$best_delta      = null;
		foreach ( $brands as $key => $multiplier ) {
			$delta = abs( (float) $multiplier - $avg_brand );
			if ( null === $best_delta || $delta < $best_delta ) {
				$best_delta      = $delta;
				$reference_brand = $key;
			}
		}

		$rows = array();
		foreach ( pixva_pricing_market_bands() as $band ) {
			$row = array(
				'title'    => $band['title'],
				'floor'    => $band['floor'],
				'size_55'  => $band['size_55'],
				'size_65'  => $band['size_65'],
				'note'     => $band['note'],
				'computed' => array(),
			);
			foreach ( array( '55', '65', '75', '85' ) as $size ) {
				$estimate = pixva_calculate_estimate( $reference_brand, 'led', $size, $band['service'] );
				$row['computed'][ $size ] = is_array( $estimate )
					? array( (int) $estimate['min'], (int) $estimate['max'] )
					: array( 0, 0 );
			}
			$rows[] = $row;
		}

		return array(
			'reference_brand' => $reference_brand,
			'rows'            => $rows,
		);
	}
}

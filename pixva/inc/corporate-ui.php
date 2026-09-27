<?php
/**
 * لایه رابط کاربری سازمانی پیکسوا — نسخه ۲٫۱٫۰ (Master Prompt v12)
 *
 * این پرونده «سامانه طراحی سازمانی» را به پوسته وصل می‌کند:
 *  - کلیدهای حالت سازمانی (قابل خاموش‌کردن از سفارشی‌ساز، پیش‌فرض روشن)؛
 *  - داده واقعی فهرست اعتماد هیرو (گارانتی/اعزام/قطعه فابریک) بدون متن سخت‌کد؛
 *  - کارت فرم اعزام فوری برای ستون کناری هیروی دوستونی (وارینت card ماژول v11).
 *
 * هیچ اسکریپت تازه‌ای بارگذاری نمی‌شود؛ رفتار فرم از همان seo-cro.js است.
 *
 * @package Pixva
 * @since   2.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // خروج مستقیم غیرمجاز.
}

if ( ! function_exists( 'pixva_corporate_ui_mode' ) ) {
	/**
	 * حالت رابط سازمانی (لایه ۲٫۱٫۰) روشن است؟
	 *
	 * @return bool
	 */
	function pixva_corporate_ui_mode() {
		$enabled = (bool) pixva_option( 'pixva_corporate_ui', true );

		/**
		 * فیلتر حالت رابط کاربری سازمانی.
		 *
		 * @param bool $enabled روشن بودن لایه سازمانی.
		 */
		return (bool) apply_filters( 'pixva_corporate_ui_mode', $enabled );
	}
}

if ( ! function_exists( 'pixva_corporate_hero_enabled' ) ) {
	/**
	 * آیا هیروی دوستونی سازمانی (به‌جای هیروی قدیمی) رندر شود؟
	 *
	 * @return bool
	 */
	function pixva_corporate_hero_enabled() {
		if ( ! pixva_corporate_ui_mode() ) {
			return false;
		}

		$enabled = (bool) pixva_option( 'pixva_corporate_hero', true );

		/**
		 * فیلتر هیروی سازمانی.
		 *
		 * @param bool $enabled روشن بودن هیروی دوستونی.
		 */
		return (bool) apply_filters( 'pixva_corporate_hero_enabled', $enabled );
	}
}

if ( ! function_exists( 'pixva_hero_trust_items' ) ) {
	/**
	 * سه مورد فهرست اعتماد هیرو — همه از داده واقعی سایت ساخته می‌شوند.
	 *
	 * @return array<int, array<string, string>>
	 */
	function pixva_hero_trust_items() {
		$control  = function_exists( 'pixva_control_options' ) ? (array) pixva_control_options() : array();
		$hours    = isset( $control['hub_eta_hours'] ) && '' !== $control['hub_eta_hours'] ? (string) $control['hub_eta_hours'] : __( '۲ ساعت', 'pixva' );
		$warranty = function_exists( 'pixva_warranty_days' ) ? pixva_warranty_days() : 180;

		$items = array(
			array(
				'icon' => 'truck',
				'text' => sprintf(
					/* translators: %s: زمان اعزام */
					__( 'اعزام تکنسین به منزل یا محل شما در کمتر از %s — بدون حمل دستگاه', 'pixva' ),
					$hours
				),
			),
			array(
				'icon' => 'shield',
				'text' => sprintf(
					/* translators: %s: روزهای گارانتی */
					__( 'گارانتی کتبی %s روزه قطعات و اجرت تعمیر روی فاکتور رسمی', 'pixva' ),
					function_exists( 'pixva_fa_num' ) ? pixva_fa_num( (string) $warranty ) : $warranty
				),
			),
			array(
				'icon' => 'cert',
				'text' => __( 'قطعات فابریک با هولوگرام اصالت؛ برآورد شفاف و بدون هزینه پنهان پیش از شروع تعمیر', 'pixva' ),
			),
		);

		/**
		 * فیلتر فهرست اعتماد هیروی سازمانی.
		 *
		 * @param array $items سه مورد اعتماد.
		 */
		return apply_filters( 'pixva_hero_trust_items', $items );
	}
}

if ( ! function_exists( 'pixva_render_hero_booking' ) ) {
	/**
	 * کارت فرم اعزام فوری برای ستون کناری هیرو (وارینت card ماژول v11).
	 *
	 * همان رندر مشترک pixva_render_express_booking با شناسه، منبع و عنوان
	 * جداگانه؛ بنابراین اعتبارسنجی، اندپوینت REST و پیامک بدون تغییر است.
	 *
	 * @return void
	 */
	function pixva_render_hero_booking() {
		if ( ! function_exists( 'pixva_render_express_booking' ) ) {
			return;
		}

		pixva_render_express_booking(
			array(
				'variant'    => 'card',
				'section_id' => 'hero-booking',
				'source'     => 'hero',
				'title'      => (string) pixva_option( 'pixva_hero_booking_title', __( 'اعزام فوری تکنسین تعمیر تلویزیون', 'pixva' ) ),
				'subtitle'   => (string) pixva_option(
					'pixva_hero_booking_subtitle',
					__( 'شماره موبایل و شرح کوتاه مشکل را بنویسید؛ کارشناس برای هماهنگی و اعلام برآورد هزینه تماس می‌گیرد.', 'pixva' )
				),
			)
		);
	}
}

if ( ! function_exists( 'pixva_mobile_logo_html' ) ) {
	/**
	 * لوگوی موبایل (لایه ۳٫۰٫۰) — فقط وقتی از سفارشی‌ساز تنظیم شده باشد.
	 *
	 * در عرض ≤768px جای لوگوی اصلی را در هدر می‌گیرد (CSS).
	 *
	 * @return string
	 */
	function pixva_mobile_logo_html() {
		$url = (string) pixva_option( 'pixva_logo_mobile', '' );
		if ( '' === $url ) {
			return '';
		}

		return sprintf(
			'<img class="pixva-logo__img pixva-logo__img--mobile" src="%1$s" alt="%2$s" width="140" height="40">',
			esc_url( $url ),
			esc_attr( get_bloginfo( 'name' ) )
		);
	}
}

if ( ! function_exists( 'pixva_hex_to_rgb' ) ) {
	/**
	 * تبدیل hex به اجزای RGB (بدون وابستگی).
	 *
	 * @param string $hex رنگ hex.
	 * @return array<int, int>
	 */
	function pixva_hex_to_rgb( $hex ) {
		$hex = ltrim( (string) $hex, '#' );
		if ( 3 === strlen( $hex ) ) {
			$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
		}
		if ( 6 !== strlen( $hex ) ) {
			return array( 37, 99, 235 );
		}
		return array(
			(int) hexdec( substr( $hex, 0, 2 ) ),
			(int) hexdec( substr( $hex, 2, 2 ) ),
			(int) hexdec( substr( $hex, 4, 2 ) ),
		);
	}
}

if ( ! function_exists( 'pixva_corporate_colors_css' ) ) {
	/**
	 * خروجی رنگ برند پویا از سفارشی‌ساز روی توکن‌های :root (لایه ۳٫۰٫۰).
	 *
	 * فقط وقتی رنگ‌ها از پیش‌فرض تغییر کرده باشند CSS درون‌خطی چاپ می‌شود
	 * تا هیچ بایت اضافی به صفحه تحمیل نشود.
	 *
	 * @return void
	 */
	function pixva_corporate_colors_css() {
		if ( ! pixva_corporate_ui_mode() || ( ! wp_style_is( 'pixva-seo-cro', 'enqueued' ) && ! wp_style_is( 'pixva-seo-cro', 'done' ) ) ) {
			return;
		}

		$brand_default = '#2563EB';
		$hover_default = '#1D4ED8';

		$brand = (string) pixva_option( 'pixva_brand_color', $brand_default );
		$hover = (string) pixva_option( 'pixva_brand_color_hover', $hover_default );

		/*
		 * sanitize_hex_color ممکن است در فرانت‌اند بارگذاری نشده باشد
		 * (همراه Customizer است)؛ با fallback امن اعتبارسنجی می‌شود.
		 */
		$pixva_hex_ok = static function ( $color ) {
			if ( function_exists( 'sanitize_hex_color' ) ) {
				return null !== sanitize_hex_color( $color );
			}
			return (bool) preg_match( '/^#([A-Fa-f0-9]{3}){1,2}$/', (string) $color );
		};

		if ( '' === $brand || ! $pixva_hex_ok( $brand ) ) {
			$brand = $brand_default;
		}
		if ( '' === $hover || ! $pixva_hex_ok( $hover ) ) {
			$hover = $hover_default;
		}

		$brand = strtoupper( $brand );
		$hover = strtoupper( $hover );

		if ( strtoupper( $brand_default ) === $brand && strtoupper( $hover_default ) === $hover ) {
			return; // پیش‌فرض است؛ توکن‌های seo-cro.css کافی‌اند.
		}

		list( $r, $g, $b ) = pixva_hex_to_rgb( $brand );

		$css = ':root{'
			. '--px-brand:' . $brand . ';'
			. '--px-brand-dark:' . $hover . ';'
			. '--px-brand-ring:rgba(' . $r . ',' . $g . ',' . $b . ',0.35);'
			. '--px-brand-soft:color-mix(in srgb,' . $brand . ' 8%,#FFFFFF);'
			. '}';

		/**
		 * فیلتر CSS درون‌خطی رنگ برند.
		 *
		 * @param string $css   قواعد :root.
		 * @param string $brand رنگ برند.
		 * @param string $hover رنگ حالت hover.
		 */
		wp_add_inline_style( 'pixva-seo-cro', apply_filters( 'pixva_corporate_colors_css', $css, $brand, $hover ) );
	}
	add_action( 'wp_enqueue_scripts', 'pixva_corporate_colors_css', 30 );
}

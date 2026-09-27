<?php
/**
 * پیکسوا — موتور هویت برند پویا (لایه ۴٫۰٫۰ / Bento)
 *
 * مسئولیت‌ها (کوچک، ماژولار و بدون مارک‌آپ قدیمی):
 * - کلید حالت رابط یکپارچه (گیت صف‌گذاری سامانه طراحی).
 * - لوگوی موبایل از سفارشی‌ساز.
 * - تزریق رنگ‌های برند/لهجه انتخاب‌شده در سفارشی‌ساز به توکن‌های --bx-*
 *   با wp_add_inline_style روی سامانه طراحی بنتو.
 *
 * @package Pixva
 * @since   2.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'pixva_corporate_ui_mode' ) ) {
	/**
	 * آیا سامانه طراحی یکپارچه (seo-cro.css / بنتو) فعال است؟
	 *
	 * کلید حذف‌شدنی برای سازگاری میزبان‌های بسیار محدود؛ پیش‌فرض روشن است.
	 *
	 * @return bool
	 */
	function pixva_corporate_ui_mode() {
		/**
		 * فیلتر حالت رابط یکپارچه.
		 *
		 * @param bool $enabled فعال بودن.
		 */
		return (bool) apply_filters( 'pixva_corporate_ui_mode', (bool) pixva_option( 'pixva_corporate_ui', true ) );
	}
}

if ( ! function_exists( 'pixva_mobile_logo_html' ) ) {
	/**
	 * لوگوی موبایل از سفارشی‌ساز (خالی = چیزی چاپ نمی‌شود).
	 *
	 * @return string
	 */
	function pixva_mobile_logo_html() {
		$url = (string) pixva_option( 'pixva_logo_mobile', '' );
		if ( '' === $url ) {
			return '';
		}

		return sprintf(
			'<img class="bx-logo-img bx-logo-img--mobile" src="%1$s" alt="%2$s" loading="eager" decoding="async">',
			esc_url( $url ),
			esc_attr( get_bloginfo( 'name' ) )
		);
	}
}

if ( ! function_exists( 'pixva_hex_to_rgb' ) ) {
	/**
	 * تبدیل HEX سه/شش‌رقمی به triplet RGB.
	 *
	 * @param string $hex رنگ HEX.
	 * @return string|null مثل «79 70 229» یا null.
	 */
	function pixva_hex_to_rgb( $hex ) {
		$hex = ltrim( (string) $hex, '#' );
		if ( 3 === strlen( $hex ) ) {
			$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
		}
		if ( ! preg_match( '/^[A-Fa-f0-9]{6}$/', $hex ) ) {
			return null;
		}
		return implode(
			' ',
			array(
				hexdec( substr( $hex, 0, 2 ) ),
				hexdec( substr( $hex, 2, 2 ) ),
				hexdec( substr( $hex, 4, 2 ) ),
			)
		);
	}
}

if ( ! function_exists( 'pixva_corporate_colors_css' ) ) {
	/**
	 * تزریق رنگ‌های سفارشی‌ساز به توکن‌های بنتو (--bx-brand و دوستان).
	 *
	 * فقط وقتی خروجی می‌دهد که دست‌کم یکی از رنگ‌ها با پیش‌فرض سامانه
	 * (#4F46E5 ایندیگو / #4338CA / #10B981 زمرد) فرق داشته باشد.
	 *
	 * @return void
	 */
	function pixva_corporate_colors_css() {
		if ( is_admin() || ! pixva_corporate_ui_mode() ) {
			return;
		}

		$brand_default = '#4F46E5';
		$hover_default = '#4338CA';
		$accent_default = '#10B981';

		$brand  = (string) pixva_option( 'pixva_brand_color', $brand_default );
		$hover  = (string) pixva_option( 'pixva_brand_color_hover', $hover_default );
		$accent = (string) pixva_option( 'pixva_accent_color', $accent_default );

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
		if ( '' === $accent || ! $pixva_hex_ok( $accent ) ) {
			$accent = $accent_default;
		}

		$is_default = ( 0 === strcasecmp( $brand, $brand_default ) )
			&& ( 0 === strcasecmp( $hover, $hover_default ) )
			&& ( 0 === strcasecmp( $accent, $accent_default ) );
		if ( $is_default ) {
			return;
		}

		$brand_rgb  = pixva_hex_to_rgb( $brand );
		$accent_rgb = pixva_hex_to_rgb( $accent );

		$css = ':root{'
			. '--bx-brand:' . $brand . ';'
			. '--bx-brand-strong:' . $hover . ';'
			. '--bx-accent:' . $accent . ';'
			. ( $brand_rgb ? '--bx-brand-ring:rgba(' . $brand_rgb . ',0.35);--bx-brand-soft:rgba(' . $brand_rgb . ',0.08);--bx-shadow-brand:0 16px 32px -12px rgba(' . $brand_rgb . ',0.4);' : '' )
			. ( $accent_rgb ? '--bx-accent-soft:rgba(' . $accent_rgb . ',0.1);' : '' )
			. '}';

		/**
		 * فیلتر CSS رنگ‌های پویای برند.
		 *
		 * @param string $css    قوانین :root.
		 * @param string $brand  رنگ برند.
		 * @param string $hover  رنگ hover.
		 * @param string $accent رنگ لهجه تبدیل.
		 */
		$css = (string) apply_filters( 'pixva_corporate_colors_css', $css, $brand, $hover, $accent );

		if ( '' !== $css && wp_style_is( 'pixva-seo-cro', 'enqueued' ) ) {
			wp_add_inline_style( 'pixva-seo-cro', $css );
		}
	}
}
add_action( 'wp_enqueue_scripts', 'pixva_corporate_colors_css', 30 );

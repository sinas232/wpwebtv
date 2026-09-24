<?php
/**
 * لایه ایمن‌سازی قالب پیکسوا
 *
 * شامل: سخت‌سازی سربرگ‌ها، محدودسازی نرخ درخواست (Rate Limit)،
 * توابع کمکی sanitize/escape، honeypot و اعتبارسنجی nonce.
 * هیچ کد مبهم، بک‌دور یا تابع خطرناکی (eval/exec/base64-decode اجرایی) در این قالب وجود ندارد.
 *
 * @package Pixva
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
 * پلی‌فیل توابع رشته‌ای PHP 8 برای میزبان‌هایی که هنوز PHP 7.4 دارند.
 * قالب در style.css حداقل PHP 7.4 را اعلام کرده است.
 */
if ( ! function_exists( 'str_starts_with' ) ) {
	/**
	 * بررسی شروع رشته.
	 *
	 * @param string $haystack رشته اصلی.
	 * @param string $needle   پیشوند.
	 * @return bool
	 */
	function str_starts_with( $haystack, $needle ) {
		if ( '' === $needle ) {
			return true;
		}
		return 0 === strncmp( (string) $haystack, (string) $needle, strlen( (string) $needle ) );
	}
}

if ( ! function_exists( 'str_ends_with' ) ) {
	/**
	 * بررسی پایان رشته.
	 *
	 * @param string $haystack رشته اصلی.
	 * @param string $needle   پسوند.
	 * @return bool
	 */
	function str_ends_with( $haystack, $needle ) {
		if ( '' === $needle ) {
			return true;
		}
		$needle   = (string) $needle;
		$haystack = (string) $haystack;
		$len      = strlen( $needle );
		if ( strlen( $haystack ) < $len ) {
			return false;
		}
		return 0 === substr_compare( $haystack, $needle, -$len );
	}
}

/*
 * ---------------------------------------------------------------------------
 * ۱) سخت‌سازی عمومی وردپرس
 * ---------------------------------------------------------------------------
 */

/**
 * حذف نسخه وردپرس از سربرگ و خوراک‌ها (کاهش افشای اطلاعات).
 */
add_action(
	'init',
	static function () {
		remove_action( 'wp_head', 'wp_generator' );
	}
);

/**
 * غیرفعال کردن pingback/trackback (سطح حمله کمتر).
 *
 * @return void
 */
function pixva_disable_pingback() {
	add_filter( 'pings_open', '__return_false' );
	add_filter( 'pre_option_use_pingbacks', '__return_zero' );
}
add_action( 'init', 'pixva_disable_pingback' );

/**
 * حذف مسیر لاگین از سربرگ‌های RSD.
 *
 * @return void
 */
function pixva_harden_headers() {
	remove_action( 'wp_head', 'rsd_link' );
	remove_action( 'wp_head', 'wlwmanifest_link' );
}
add_action( 'init', 'pixva_harden_headers' );

/**
 * پنهان‌سازی خطاهای لاگین (جلوگیری از شمارش نام کاربری).
 *
 * @return string
 */
function pixva_generic_login_error() {
	return esc_html__( 'نام کاربری یا رمز عبور نامعتبر است.', 'pixva' );
}
add_filter( 'login_errors', 'pixva_generic_login_error' );

/**
 * غیرفعال کردن XML-RPC (سطح حمله کمتر، بدون تأثیر روی پیشخوان).
 *
 * @return bool
 */
function pixva_disable_xmlrpc() {
	return false;
}
add_filter( 'xmlrpc_enabled', 'pixva_disable_xmlrpc' );

/*
 * ---------------------------------------------------------------------------
 * ۲) توابع کمکی ورودی/خروجی
 * ---------------------------------------------------------------------------
 */

if ( ! function_exists( 'pixva_get_post_var' ) ) {
	/**
	 * خواندن امن یک متغیر POST رشته‌ای.
	 *
	 * @param string $key     نام فیلد.
	 * @param string $default مقدار پیش‌فرض.
	 * @return string
	 */
	function pixva_get_post_var( $key, $default = '' ) {
		if ( ! isset( $_POST[ $key ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce در لایه فراخوان اعتبارسنجی می‌شود.
			return $default;
		}
		$value = sanitize_text_field( wp_unslash( $_POST[ $key ] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		return is_string( $value ) ? $value : $default;
	}
}

if ( ! function_exists( 'pixva_get_request_var' ) ) {
	/**
	 * خواندن امن یک متغیر GET رشته‌ای.
	 *
	 * @param string $key     نام فیلد.
	 * @param string $default مقدار پیش‌فرض.
	 * @return string
	 */
	function pixva_get_request_var( $key, $default = '' ) {
		if ( ! isset( $_GET[ $key ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- فقط خواندنی است.
			return $default;
		}
		$value = sanitize_text_field( wp_unslash( $_GET[ $key ] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return is_string( $value ) ? $value : $default;
	}
}

if ( ! function_exists( 'pixva_is_valid_iranian_mobile' ) ) {
	/**
	 * اعتبارسنجی شماره همراه ایران.
	 *
	 * @param string $mobile شماره ورودی.
	 * @return bool
	 */
	function pixva_is_valid_iranian_mobile( $mobile ) {
		$mobile = preg_replace( '/[^0-9]/', '', (string) $mobile );
		if ( 11 === strlen( $mobile ) && str_starts_with( $mobile, '09' ) ) {
			return true;
		}
		// پذیرش قالب 989xxxxxxxxx یا +989xxxxxxxxx.
		if ( 12 === strlen( $mobile ) && str_starts_with( $mobile, '989' ) ) {
			return true;
		}
		return false;
	}
}

if ( ! function_exists( 'pixva_normalize_mobile' ) ) {
	/**
	 * نرمال‌سازی شماره همراه به قالب 09xxxxxxxxx.
	 *
	 * @param string $mobile شماره ورودی.
	 * @return string
	 */
	function pixva_normalize_mobile( $mobile ) {
		$mobile = preg_replace( '/[^0-9]/', '', (string) $mobile );
		if ( 12 === strlen( $mobile ) && str_starts_with( $mobile, '989' ) ) {
			$mobile = '0' . substr( $mobile, 2 );
		}
		return $mobile;
	}
}

if ( ! function_exists( 'pixva_kses_content' ) ) {
	/**
	 * مجوزهای_kses برای محتوای غنی مدیریت‌شده (بدون اسکریپت و iframe ناشناس).
	 *
	 * @return array
	 */
	function pixva_kses_content() {
		$allowed = wp_kses_allowed_html( 'post' );
		unset( $allowed['script'], $allowed['iframe'], $allowed['form'], $allowed['input'] );
		return $allowed;
	}
}

/*
 * ---------------------------------------------------------------------------
 * ۳) محدودسازی نرخ درخواست (Rate Limit) مبتنی بر Transient
 * ---------------------------------------------------------------------------
 */

if ( ! function_exists( 'pixva_rate_limit' ) ) {
	/**
	 * محدودسازی تعداد درخواست بر اساس IP برای هر عملیات.
	 *
	 * @param string $action    نام عملیات (مثلاً order).
	 * @param int    $max       حداکثر تعداد درخواست.
	 * @param int    $window    بازه زمانی به ثانیه.
	 * @return bool true یعنی مجاز.
	 */
	function pixva_rate_limit( $action, $max = 10, $window = HOUR_IN_SECONDS ) {
		$ip   = pixva_client_ip();
		$key  = 'pixva_rl_' . $action . '_' . md5( $ip );
		$hits = (int) get_transient( $key );
		if ( $hits >= $max ) {
			return false;
		}
		set_transient( $key, $hits + 1, $window );
		return true;
	}
}

if ( ! function_exists( 'pixva_client_ip' ) ) {
	/**
	 * دریافت ایمن آدرس IP کاربر.
	 *
	 * @return string
	 */
	function pixva_client_ip() {
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '0.0.0.0';
		return (string) wp_privacy_anonymize_ip( $ip ); // ذخیره ناشناس‌سازی‌شده مطابق حریم خصوصی.
	}
}

/*
 * ---------------------------------------------------------------------------
 * ۴) Honeypot فرم‌ها
 * ---------------------------------------------------------------------------
 */

if ( ! function_exists( 'pixva_honeypot_field' ) ) {
	/**
	 * چاپ فیلد honeypot (باید خالی بماند).
	 *
	 * @return void
	 */
	function pixva_honeypot_field() {
		echo '<div class="pixva-hp-field" aria-hidden="true"><label for="pixva-hp-website">' . esc_html__( 'این فیلد را خالی بگذارید', 'pixva' ) . '</label><input type="text" id="pixva-hp-website" name="pixva_hp" tabindex="-1" autocomplete="off"></div>';
	}
}

if ( ! function_exists( 'pixva_honeypot_passed' ) ) {
	/**
	 * بررسی خالی بودن honeypot.
	 *
	 * @return bool
	 */
	function pixva_honeypot_passed() {
		$value = isset( $_POST['pixva_hp'] ) ? sanitize_text_field( wp_unslash( $_POST['pixva_hp'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		return '' === $value;
	}
}

/*
 * ---------------------------------------------------------------------------
 * ۵) پاسخ یکپارچه AJAX
 * ---------------------------------------------------------------------------
 */

if ( ! function_exists( 'pixva_strlen' ) ) {
	/**
	 * طول رشته با پشتیبانی از نویسه‌های چندبایتی.
	 *
	 * @param string $text متن.
	 * @return int
	 */
	function pixva_strlen( $text ) {
		$text = (string) $text;
		return function_exists( 'mb_strlen' ) ? (int) mb_strlen( $text ) : strlen( $text );
	}
}

if ( ! function_exists( 'pixva_mask_phone' ) ) {
	/**
	 * پوشاندن میانه شماره همراه برای نمایش عمومی.
	 *
	 * @param string $phone شماره.
	 * @return string
	 */
	function pixva_mask_phone( $phone ) {
		$phone = pixva_normalize_mobile( $phone );
		if ( strlen( $phone ) < 8 ) {
			return '';
		}
		return pixva_fa_num( substr( $phone, 0, 4 ) . '***' . substr( $phone, -4 ) );
	}
}

if ( ! function_exists( 'pixva_ajax_success' ) ) {
	/**
	 * پاسخ موفق JSON و پایان درخواست.
	 *
	 * @param array $data داده پاسخ.
	 * @return void
	 */
	function pixva_ajax_success( $data = array() ) {
		wp_send_json_success( $data );
	}
}

if ( ! function_exists( 'pixva_ajax_error' ) ) {
	/**
	 * پاسخ خطای JSON و پایان درخواست.
	 *
	 * @param string $message پیام خطای قابل نمایش به کاربر.
	 * @param int    $status  کد وضعیت HTTP.
	 * @return void
	 */
	function pixva_ajax_error( $message, $status = 400 ) {
		wp_send_json_error( array( 'message' => $message ), $status );
	}
}

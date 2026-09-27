<?php
/**
 * رفع خودکار محدودیت‌های رایج هاست (به‌ویژه هاست‌های اشتراکی ایرانی) — لایه ۱٫۹٫۰.
 *
 * پردازش عیب‌یابی هوش مصنوعی شامل آپلود فایل سنگین، فراخوانی API گوگل و
 * نگهداری پاسخ در حافظه است؛ بسیاری از هاست‌ها به‌صورت پیش‌فرض
 * max_execution_time و memory_limit پایینی دارند که باعث خطای ۵۰۰ یا
 * «Fatal error: Allowed memory size» می‌شود. این پرونده:
 *
 *  ۱) تابع pixva_host_raise_limits() را فراهم می‌کند (set_time_limit(300) و
 *     تلاش برای افزایش memory_limit به ۲۵۶ مگابایت) که در زمان پردازش
 *     آپلود و فراخوانی API صدا زده می‌شود.
 *  ۲) در صفحه «پیکسوا ← عیب‌یاب AI» اگر upload_max_filesize هاست از
 *     ۳۲ مگابایت کمتر باشد، هشدار راهنما نمایش می‌دهد.
 *
 * همه تلاش‌ها با @ و بررسی وجود تابع انجام می‌شود تا در هاست‌هایی که
 * ini_set غیرفعال است (safe mode / suhosin) خطایی تولید نگردد.
 *
 * @package Pixva
 * @since   1.9.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'pixva_host_raise_limits' ) ) {
	/**
	 * افزایش موقت محدودیت‌های زمان اجرا و حافظه برای پردازش عیب‌یابی.
	 *
	 * @param int $seconds    سقف زمان اجرا (ثانیه).
	 * @param string $memory  سقف حافظه (مثلاً 256M).
	 * @return void
	 */
	function pixva_host_raise_limits( $seconds = 300, $memory = '256M' ) {
		/**
		 * فیلتر سقف زمان اجرای پردازش عیب‌یابی.
		 *
		 * @param int $seconds ثانیه.
		 */
		$seconds = (int) apply_filters( 'pixva_host_time_limit', (int) $seconds );
		/**
		 * فیلتر سقف حافظه پردازش عیب‌یابی.
		 *
		 * @param string $memory مثل 256M.
		 */
		$memory = (string) apply_filters( 'pixva_host_memory_limit', $memory );

		if ( ! function_exists( 'set_time_limit' ) || ( function_exists( 'ini_get' ) && ! ini_get( 'safe_mode' ) ) ) {
			@set_time_limit( $seconds ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		}

		if ( function_exists( 'ini_set' ) ) {
			$current = (string) @ini_get( 'memory_limit' ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			// فقط وقتی افزایش می‌دهیم که سقف فعلی کمتر از مقدار هدف یا نامحدود نباشد.
			if ( pixva_host_bytes( $current ) < pixva_host_bytes( $memory ) ) {
				@ini_set( 'memory_limit', $memory ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			}
		}

		// وردپرس ۴٫۶+ تابع اختصاصی افزایش حافظه دارد (اگر موجود باشد).
		if ( function_exists( 'wp_raise_memory_limit' ) ) {
			@wp_raise_memory_limit( 'pixva_ai' ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		}
	}
}

if ( ! function_exists( 'pixva_host_bytes' ) ) {
	/**
	 * تبدیل مقدار ini (مثل 256M / 1G / 2048K) به بایت.
	 *
	 * @param string|int $value مقدار.
	 * @return int بایت (۰ = نامحدود/نامعتبر).
	 */
	function pixva_host_bytes( $value ) {
		$value = trim( (string) $value );
		if ( '' === $value || '-1' === $value ) {
			return -1 === (int) $value ? PHP_INT_MAX : 0;
		}

		$last = strtolower( substr( $value, -1 ) );
		$num  = (int) preg_replace( '/[^0-9.]/', '', $value );

		switch ( $last ) {
			case 'g':
				$num *= GB_IN_BYTES;
				break;
			case 'm':
				$num *= MB_IN_BYTES;
				break;
			case 'k':
				$num *= KB_IN_BYTES;
				break;
		}

		return (int) $num;
	}
}

if ( ! function_exists( 'pixva_host_upload_max_bytes' ) ) {
	/**
	 * کوچک‌ترین سقف آپلود مؤثر هاست (upload_max_filesize و post_max_size).
	 *
	 * @return int بایت.
	 */
	function pixva_host_upload_max_bytes() {
		$upload = pixva_host_bytes( (string) @ini_get( 'upload_max_filesize' ) ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		$post   = pixva_host_bytes( (string) @ini_get( 'post_max_size' ) ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged

		$values = array_filter( array( $upload, $post ) );
		if ( empty( $values ) ) {
			return 0;
		}
		return (int) min( $values );
	}
}

if ( ! function_exists( 'pixva_host_ai_upload_notice' ) ) {
	/**
	 * هشدار راهنما در صفحه عیب‌یاب AI وقتی سقف آپلود هاست زیر ۳۲ مگابایت است.
	 *
	 * @return void
	 */
	function pixva_host_ai_upload_notice() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
		if ( 'pixva-ai-settings' !== $page && 'pixva-ai-logs' !== $page ) {
			return;
		}

		$max_bytes  = pixva_host_upload_max_bytes();
		$threshold  = (int) apply_filters( 'pixva_host_upload_threshold', 32 * MB_IN_BYTES );

		if ( $max_bytes > 0 && $max_bytes < $threshold ) {
			$max_mb  = round( $max_bytes / MB_IN_BYTES, 1 );
			$want_mb = (int) pixva_ai_handler_max_size_exists() ? pixva_ai_handler_max_size_exists() : 50;
			printf(
				'<div class="notice notice-warning"><p><strong>%s</strong> %s</p></div>',
				esc_html__( 'محدودیت آپلود هاست:', 'pixva' ),
				esc_html(
					sprintf(
						/* translators: 1: سقف فعلی هاست، 2: سقف توصیه‌شده */
						__( 'سقف upload_max_filesize هاست شما حدود %1$s مگابایت است که از %2$s مگابایت توصیه‌شده کمتر است. فایل‌های بزرگ‌تر از این مقدار پیش از رسیدن به جمینای با خطای «حجم فایل بیش از حد مجاز» رد می‌شوند. این مقدار را در php.ini یا .htaccess (php_value upload_max_filesize 64M) افزایش دهید.', 'pixva' ),
						$max_mb,
						$want_mb
					)
				)
			);
		}
	}
	add_action( 'admin_notices', 'pixva_host_ai_upload_notice' );
}

if ( ! function_exists( 'pixva_ai_handler_max_size_exists' ) ) {
	/**
	 * سقف حجم فایل عیب‌یابی (مگابایت) اگر تابع هندلر موجود باشد، در غیر این صورت ۵۰.
	 *
	 * @return int
	 */
	function pixva_ai_handler_max_size_exists() {
		return function_exists( 'pixva_ai_handler_max_size' ) ? (int) pixva_ai_handler_max_size() : 50;
	}
}

<?php
/**
 * توابع کمکی عیب‌یاب هوشمند — لایه ۱٫۶٫۰ (بازآرایی ۱٫۸٫۰).
 *
 * این پرونده فقط «کمکی‌های مشترک» اندپوینت عیب‌یابی را نگه می‌دارد:
 * انواع مجاز رسانه، ساخت کد پیگیری، محدودسازی نرخ، اعتبارسنجی دسترسی
 * (nonce + honeypot) و ذخیره رسانه در کتابخانه پرونده‌ها.
 *
 * از لایه ۱٫۸٫۰ (Master Prompt v9) هندلر REST، مسیر
 * POST wp-json/pixva/v1/ai-diagnose و همه منطق هوش مصنوعی به‌صورت بومی با
 * Google Gemini در inc/ai-handler.php پیاده‌سازی شده و وابستگی به سرویس
 * پایتون/ایجنت بیرونی (pixva_ai_agent_url) کاملاً حذف گردیده است.
 *
 * @package Pixva
 * @since   1.6.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'pixva_ai_diagnose_allowed_types' ) ) {
	/**
	 * MIMEهای مجاز برای رسانه عیب‌یابی.
	 *
	 * @return array<string, string>
	 */
	function pixva_ai_diagnose_allowed_types() {
		$types = array(
			'mp4'  => 'video/mp4',
			'm4v'  => 'video/mp4',
			'webm' => 'video/webm',
			'ogv'  => 'video/ogg',
			'mov'  => 'video/quicktime',
			'avi'  => 'video/x-msvideo',
			'mp3'  => 'audio/mpeg',
			'm4a'  => 'audio/mp4',
			'wav'  => 'audio/wav',
			'ogg'  => 'audio/ogg',
			'weba' => 'audio/webm',
			'amr'  => 'audio/amr',
		);

		/**
		 * فیلتر انواع مجاز رسانه عیب‌یابی.
		 *
		 * @param array $types انواع.
		 */
		return apply_filters( 'pixva_ai_diagnose_allowed_types', $types );
	}
}

if ( ! function_exists( 'pixva_ai_diagnose_ticket' ) ) {
	/**
	 * ساخت کد پیگیری یکتا برای درخواست عیب‌یابی.
	 *
	 * @param int $post_id شناسه نوشته.
	 * @return string
	 */
	function pixva_ai_diagnose_ticket( $post_id ) {
		$seed = strtoupper( substr( md5( 'pixva-ai-' . $post_id . wp_salt( 'auth' ) ), 0, 6 ) );
		return 'PXV-AI-' . $seed;
	}
}

if ( ! function_exists( 'pixva_ai_diagnose_rate_limited' ) ) {
	/**
	 * محدودسازی نرخ ارسال بر اساس IP (جلوگیری از spam).
	 *
	 * @return bool
	 */
	function pixva_ai_diagnose_rate_limited() {
		$ip  = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : 'unknown';
		$key = 'pixva_ai_rate_' . md5( $ip . wp_salt( 'nonce' ) );
		$hit = (int) get_transient( $key );

		if ( $hit >= (int) apply_filters( 'pixva_ai_diagnose_rate_max', 6 ) ) {
			return true;
		}

		set_transient( $key, $hit + 1, (int) apply_filters( 'pixva_ai_diagnose_rate_window', HOUR_IN_SECONDS ) );
		return false;
	}
}

if ( ! function_exists( 'pixva_ai_diagnose_permission' ) ) {
	/**
	 * بررسی دسترسی اندپوینت: nonce اختصاصی + نبود honeypot + نرخ مجاز.
	 *
	 * @param WP_REST_Request $request درخواست.
	 * @return true|WP_Error
	 */
	function pixva_ai_diagnose_permission( $request ) {
		$nonce = (string) $request->get_header( 'x_pixva_nonce' );
		if ( '' === $nonce || ! wp_verify_nonce( $nonce, 'pixva_ai_diagnose' ) ) {
			return new WP_Error( 'pixva_ai_nonce', __( 'نشست شما منقضی شده است؛ صفحه را تازه کنید.', 'pixva' ), array( 'status' => 403 ) );
		}

		$honeypot = (string) $request->get_param( 'pixva_hp' );
		if ( '' !== $honeypot ) {
			return new WP_Error( 'pixva_ai_spam', __( 'درخواست نامعتبر.', 'pixva' ), array( 'status' => 400 ) );
		}

		if ( pixva_ai_diagnose_rate_limited() ) {
			return new WP_Error( 'pixva_ai_rate', __( 'تعداد درخواست‌ها از حد مجاز گذشته است؛ کمی بعد تلاش کنید.', 'pixva' ), array( 'status' => 429 ) );
		}

		return true;
	}
}

if ( ! function_exists( 'pixva_ai_diagnose_store_media' ) ) {
	/**
	 * ذخیره فایل رسانه ارسالی در کتابخانه پرونده‌ها.
	 *
	 * @param array $file آرایه $_FILES['media'].
	 * @return int|WP_Error شناسه پیوست.
	 */
	function pixva_ai_diagnose_store_media( $file ) {
		if ( empty( $file['name'] ) || empty( $file['tmp_name'] ) ) {
			return new WP_Error( 'pixva_ai_empty', __( 'فایلی دریافت نشد.', 'pixva' ), array( 'status' => 400 ) );
		}

		if ( ! empty( $file['error'] ) && UPLOAD_ERR_NO_FILE !== (int) $file['error'] ) {
			return new WP_Error( 'pixva_ai_upload', __( 'خطا در دریافت فایل؛ دوباره تلاش کنید.', 'pixva' ), array( 'status' => 400 ) );
		}

		$max_mb    = function_exists( 'pixva_ai_handler_max_size' ) ? pixva_ai_handler_max_size() : (int) pixva_option( 'pixva_ai_max_size', 50 );
		$max_bytes = (int) apply_filters( 'pixva_ai_diagnose_max_size', (int) $max_mb ) * MB_IN_BYTES;
		if ( (int) $file['size'] > $max_bytes ) {
			return new WP_Error( 'pixva_ai_size', __( 'حجم فایل بیش از حد مجاز است.', 'pixva' ), array( 'status' => 413 ) );
		}

		$checked = wp_check_filetype_and_ext( $file['tmp_name'], $file['name'], pixva_ai_diagnose_allowed_types() );
		if ( empty( $checked['type'] ) || ! in_array( $checked['type'], array_values( pixva_ai_diagnose_allowed_types() ), true ) ) {
			return new WP_Error( 'pixva_ai_type', __( 'فقط فایل ویدیو یا صدا پذیرفته می‌شود.', 'pixva' ), array( 'status' => 415 ) );
		}

		$is_media = 0 === strpos( (string) $checked['type'], 'video/' ) || 0 === strpos( (string) $checked['type'], 'audio/' );
		if ( ! $is_media ) {
			return new WP_Error( 'pixva_ai_type', __( 'فقط فایل ویدیو یا صدا پذیرفته می‌شود.', 'pixva' ), array( 'status' => 415 ) );
		}

		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		$attachment_id = media_handle_sideload(
			array(
				'name'     => sanitize_file_name( $file['name'] ),
				'tmp_name' => $file['tmp_name'],
				'size'     => (int) $file['size'],
				'type'     => $checked['type'],
				'error'    => 0,
			),
			0,
			'',
			array(
				'mime_types' => array_values( pixva_ai_diagnose_allowed_types() ),
			)
		);

		if ( is_wp_error( $attachment_id ) ) {
			return $attachment_id;
		}

		wp_update_post(
			array(
				'ID'          => (int) $attachment_id,
				'post_status' => 'private',
			)
		);

		return (int) $attachment_id;
	}
}

/* --------------------------------------------------------------------------
 * از لایه ۱٫۸٫۰ هندلر REST عیب‌یابی، مسیر ai-diagnose، منطق بومی Gemini،
 * تاریخچه پیشخوان و تبدیل به سفارش در inc/ai-handler.php قرار دارد و
 * وابستگی به ایجنت پایتون (pixva_ai_agent_url/token) کاملاً حذف شده است.
 * ----------------------------------------------------------------------- */

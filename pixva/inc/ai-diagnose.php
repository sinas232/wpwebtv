<?php
/**
 * اندپوینت عیب‌یاب هوشمند — لایه ۱٫۶٫۰.
 *
 * مسیر: POST wp-json/pixva/v1/ai-diagnose
 * ورودی: multipart/form-data با کلید media (ویدیو یا صدا) + brand, model,
 *        symptom, phone, pixva_hp (honeypot).
 * خروجی: JSON شامل کد پیگیری درخواست، وضعیت و (در صورت اتصال ایجنت) تحلیل.
 *
 * معماری AI-Ready: اگر گزینه pixva_ai_agent_url تنظیم شده باشد، همان رسانه و
 * متادیتا به ایجنت بیرونی (مثلاً سرویس پایتون) فرستاده می‌شود و پاسخ آن در
 * خروجی ادغام می‌گردد؛ در غیر این صورت درخواست در صف تحلیل ثبت می‌شود تا
 * کارشناس یا ایجنت بعدی آن را پردازش کند. فیلتر pixva_ai_diagnose_result هم
 * برای پردازش کاملاً سفارشی در دسترس است.
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

		$max_bytes = (int) apply_filters( 'pixva_ai_diagnose_max_size', (int) pixva_option( 'pixva_ai_max_size', 64 ) ) * MB_IN_BYTES;
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

if ( ! function_exists( 'pixva_ai_agent_request' ) ) {
	/**
	 * ارسال رسانه و متادیتا به ایجنت هوش مصنوعی بیرونی (اختیاری).
	 *
	 * @param array $payload داده‌های درخواست.
	 * @return array پاسخ ایجنت (خالی اگر تنظیم نشده باشد).
	 */
	function pixva_ai_agent_request( $payload ) {
		$agent = trim( (string) pixva_option( 'pixva_ai_agent_url', '' ) );
		if ( '' === $agent ) {
			return array();
		}

		$args = array(
			'timeout'     => (int) apply_filters( 'pixva_ai_agent_timeout', 25 ),
			'redirection' => 0,
			'headers'     => array(
				'Content-Type'  => 'application/json',
				'Authorization' => 'Bearer ' . (string) pixva_option( 'pixva_ai_agent_token', '' ),
				'User-Agent'    => 'Pixva-Theme/' . PIXVA_VERSION,
			),
			'body'        => wp_json_encode( $payload ),
		);

		$response = wp_remote_post( esc_url_raw( $agent ), $args );
		if ( is_wp_error( $response ) ) {
			return array(
				'state'   => 'agent_unreachable',
				'message' => $response->get_error_message(),
			);
		}

		$body = json_decode( (string) wp_remote_retrieve_body( $response ), true );
		if ( ! is_array( $body ) ) {
			return array( 'state' => 'agent_invalid' );
		}

		return $body;
	}
}

if ( ! function_exists( 'pixva_rest_ai_diagnose' ) ) {
	/**
	 * هندلر REST عیب‌یابی هوشمند.
	 *
	 * @param WP_REST_Request $request درخواست.
	 * @return WP_REST_Response|WP_Error
	 */
	function pixva_rest_ai_diagnose( $request ) {
		$files = isset( $_FILES['media'] ) ? $_FILES['media'] : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		if ( empty( $files ) ) {
			return new WP_Error( 'pixva_ai_media', __( 'ابتدا یک ویدیو یا صدای دستگاه را اضافه کنید.', 'pixva' ), array( 'status' => 400 ) );
		}

		$attachment_id = pixva_ai_diagnose_store_media( $files );
		if ( is_wp_error( $attachment_id ) ) {
			return $attachment_id;
		}

		$brand   = sanitize_text_field( (string) $request->get_param( 'brand' ) );
		$model   = sanitize_text_field( (string) $request->get_param( 'model' ) );
		$symptom = sanitize_textarea_field( (string) $request->get_param( 'symptom' ) );
		$phone   = function_exists( 'pixva_normalize_mobile' ) ? pixva_normalize_mobile( (string) $request->get_param( 'phone' ) ) : sanitize_text_field( (string) $request->get_param( 'phone' ) );
		$brands  = function_exists( 'pixva_brand_catalog' ) ? pixva_brand_catalog() : array();

		$post_type = post_type_exists( 'pixva_inbox' ) ? 'pixva_inbox' : 'post';
		$post_id   = wp_insert_post(
			array(
				'post_type'    => $post_type,
				'post_status'  => 'private',
				'post_title'   => sprintf(
					/* translators: 1: برند 2: شرح خرابی */
					__( 'عیب‌یابی هوشمند %1$s — %2$s', 'pixva' ),
					isset( $brands[ $brand ]['fa'] ) ? $brands[ $brand ]['fa'] : ( '' !== $brand ? $brand : __( 'نامشخص', 'pixva' ) ),
					'' !== $symptom ? wp_trim_words( $symptom, 8 ) : __( 'بدون شرح', 'pixva' )
				),
				'post_content' => $symptom,
			),
			true
		);

		if ( is_wp_error( $post_id ) || ! $post_id ) {
			return new WP_Error( 'pixva_ai_store', __( 'ثبت درخواست ممکن نشد؛ دوباره تلاش کنید.', 'pixva' ), array( 'status' => 500 ) );
		}

		$ticket = pixva_ai_diagnose_ticket( (int) $post_id );
		$ip     = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';

		update_post_meta( $post_id, '_pixva_ai_state', 'queued' );
		update_post_meta( $post_id, '_pixva_ai_code', $ticket );
		update_post_meta( $post_id, '_pixva_ai_media', (int) $attachment_id );
		update_post_meta( $post_id, '_pixva_ai_brand', $brand );
		update_post_meta( $post_id, '_pixva_ai_model', $model );
		update_post_meta( $post_id, '_pixva_ai_symptom', $symptom );
		update_post_meta( $post_id, '_pixva_ai_phone', $phone );
		update_post_meta( $post_id, '_pixva_ai_ip', $ip );
		update_post_meta( $post_id, '_pixva_ai_kind', 0 === strpos( (string) get_post_mime_type( $attachment_id ), 'audio' ) ? 'audio' : 'video' );
		update_post_meta( $post_id, '_pixva_message_phone', $phone );

		$media_url = (string) wp_get_attachment_url( $attachment_id );
		$agent     = pixva_ai_agent_request(
			array(
				'ticket'    => $ticket,
				'mediaUrl'  => $media_url,
				'mediaKind' => 0 === strpos( (string) get_post_mime_type( $attachment_id ), 'audio' ) ? 'audio' : 'video',
				'brand'     => $brand,
				'model'     => $model,
				'symptom'   => $symptom,
				'phone'     => $phone,
				'site'      => home_url( '/' ),
				'version'   => PIXVA_VERSION,
			)
		);

		$state = isset( $agent['state'] ) ? sanitize_key( (string) $agent['state'] ) : 'queued';
		if ( '' !== $agent ) {
			update_post_meta( $post_id, '_pixva_ai_state', $state );
			update_post_meta( $post_id, '_pixva_ai_agent', wp_json_encode( $agent ) );
		}

		$result = array(
			'success'   => true,
			'ticket'    => $ticket,
			'state'     => $state,
			'message'   => isset( $agent['message'] ) ? sanitize_text_field( (string) $agent['message'] ) : '',
			'analysis'  => isset( $agent['analysis'] ) ? $agent['analysis'] : array(),
			'verdict'   => isset( $agent['verdict'] ) ? sanitize_text_field( (string) $agent['verdict'] ) : '',
			'part'      => isset( $agent['part'] ) ? sanitize_text_field( (string) $agent['part'] ) : '',
			'estimate'  => isset( $agent['estimate'] ) ? sanitize_text_field( (string) $agent['estimate'] ) : '',
			'mediaUrl'  => $media_url,
			'createdAt' => function_exists( 'pixva_fa_num' ) ? pixva_fa_num( wp_date( 'Y/m/d H:i' ) ) : wp_date( 'Y/m/d H:i' ),
			'queueNote' => __( 'درخواست شما در صف تحلیل ثبت شد؛ کارشناسان پیکسوا نتیجه را تماس می‌گیرند.', 'pixva' ),
		);

		/**
		 * فیلتر خروجی عیب‌یابی هوشمند (برای اتصال ایجنت سفارشی).
		 *
		 * @param array         $result      خروجی.
		 * @param int           $post_id     شناسه نوشته درخواست.
		 * @param int           $attachment  شناسه پیوست رسانه.
		 * @param WP_REST_Request $request   درخواست.
		 */
		$result = apply_filters( 'pixva_ai_diagnose_result', $result, (int) $post_id, (int) $attachment_id, $request );

		/**
		 * هوک پس از ثبت درخواست عیب‌یابی (برای پیامک/ایمیل/صف).
		 *
		 * @param int   $post_id       شناسه درخواست.
		 * @param int   $attachment_id شناسه رسانه.
		 * @param array $result        خروجی.
		 */
		do_action( 'pixva_ai_diagnose_saved', (int) $post_id, (int) $attachment_id, $result );

		return rest_ensure_response( $result );
	}
}

if ( ! function_exists( 'pixva_register_ai_diagnose_route' ) ) {
	/**
	 * ثبت مسیر REST عیب‌یابی هوشمند.
	 *
	 * @return void
	 */
	function pixva_register_ai_diagnose_route() {
		register_rest_route(
			'pixva/v1',
			'/ai-diagnose',
			array(
				'methods'             => 'POST',
				'callback'            => 'pixva_rest_ai_diagnose',
				'permission_callback' => 'pixva_ai_diagnose_permission',
				'args'                => array(
					'brand'   => array(
						'required'          => false,
						'sanitize_callback' => 'sanitize_key',
					),
					'model'   => array(
						'required'          => false,
						'sanitize_callback' => 'sanitize_text_field',
					),
					'symptom' => array(
						'required'          => false,
						'sanitize_callback' => 'sanitize_textarea_field',
					),
					'phone'   => array(
						'required'          => false,
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);
	}
	add_action( 'rest_api_init', 'pixva_register_ai_diagnose_route' );
}

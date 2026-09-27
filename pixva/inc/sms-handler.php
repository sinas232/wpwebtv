<?php
/**
 * سامانه بومی پیامک عیب‌یاب هوشمند — لایه ۱٫۹٫۰ (Master Prompt v10).
 *
 * ارسال خودکار پیامک در سه رویداد:
 *  ۱) خوش‌آمدگویی + کد پیگیری اختصاصی به مشتری، به محض تکمیل آنالیز هوش مصنوعی.
 *  ۲) پیامک وضعیت به مشتری، وقتی مدیر در پنل روی «تبدیل به سفارش تعمیرات» کلیک می‌کند.
 *  ۳) هشدار به ادمین/تعمیرکار برای هر عیب‌یابی جدید.
 *
 * درگاه‌ها: کاوه‌نگار (kavenegar)، آی‌پنل (ippanel)، ملی‌پیامک (melipayamak) و
 * اس‌ام‌اس‌آی‌آر (smsir). تنظیمات از کلیدهای اختصاصی pixva_sms_* خوانده می‌شود و
 * در نبود آن‌ها، از تنظیمات پیامک مرکز کنترل (ai/sms) به‌عنوان fallback استفاده
 * می‌گردد تا یک پیکربندی برای کل قالب کافی باشد. همه ارسال‌ها با wp_remote_post
 * و بدون هیچ افزونه یا سرویس بیرونی انجام می‌شود و در گزارش پیامک پوسته ثبت می‌گردد.
 *
 * @package Pixva
 * @since   1.9.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'pixva_sms_handler_providers' ) ) {
	/**
	 * درگاه‌های پیامک پشتیبانی‌شده.
	 *
	 * @return array<string, string>
	 */
	function pixva_sms_handler_providers() {
		$providers = array(
			'none'        => __( 'غیرفعال (بدون ارسال پیامک)', 'pixva' ),
			'kavenegar'   => __( 'کاوه‌نگار (Kavenegar)', 'pixva' ),
			'ippanel'     => __( 'آی‌پنل (IPPanel)', 'pixva' ),
			'melipayamak' => __( 'ملی‌پیامک (Melipayamak)', 'pixva' ),
			'smsir'       => __( 'اس‌ام‌اس‌آی‌آر (SMS.ir)', 'pixva' ),
		);

		/**
		 * فیلتر درگاه‌های پیامک.
		 *
		 * @param array $providers درگاه‌ها.
		 */
		return apply_filters( 'pixva_sms_handler_providers', $providers );
	}
}

if ( ! function_exists( 'pixva_sms_handler_settings' ) ) {
	/**
	 * تنظیمات مؤثر پیامک (کلیدهای اختصاصی با fallback مرکز کنترل).
	 *
	 * @return array<string, mixed>
	 */
	function pixva_sms_handler_settings() {
		$control = function_exists( 'pixva_control_options' ) ? pixva_control_options() : array();

		$provider = (string) pixva_option( 'pixva_sms_provider', '' );
		if ( '' === $provider ) {
			$provider = isset( $control['sms_provider'] ) ? (string) $control['sms_provider'] : 'none';
		}
		if ( ! array_key_exists( $provider, pixva_sms_handler_providers() ) ) {
			$provider = 'none';
		}

		$api_key = trim( (string) pixva_option( 'pixva_sms_api_key', '' ) );
		if ( '' === $api_key ) {
			$api_key = isset( $control['sms_api_key'] ) ? trim( (string) $control['sms_api_key'] ) : '';
		}

		$sender = trim( (string) pixva_option( 'pixva_sms_sender_line', '' ) );
		if ( '' === $sender ) {
			$sender = isset( $control['sms_sender'] ) ? trim( (string) $control['sms_sender'] ) : '';
		}

		$enabled = (bool) pixva_option( 'pixva_sms_enabled', false );

		return array(
			'enabled'     => $enabled,
			'provider'    => $provider,
			'api_key'     => $api_key,
			'sender'      => $sender,
			'pattern'     => trim( (string) pixva_option( 'pixva_sms_pattern', '' ) ),
			'admin_number' => function_exists( 'pixva_normalize_mobile' ) ? pixva_normalize_mobile( (string) pixva_option( 'pixva_sms_admin_number', '' ) ) : trim( (string) pixva_option( 'pixva_sms_admin_number', '' ) ),
			'timeout'     => (int) apply_filters( 'pixva_sms_handler_timeout', 12 ),
		);
	}
}

if ( ! function_exists( 'pixva_sms_handler_configured' ) ) {
	/**
	 * آیا سامانه پیامک آماده ارسال است؟
	 *
	 * @return bool
	 */
	function pixva_sms_handler_configured() {
		$s = pixva_sms_handler_settings();
		return $s['enabled'] && 'none' !== $s['provider'] && '' !== $s['api_key'];
	}
}

if ( ! function_exists( 'pixva_sms_handler_templates' ) ) {
	/**
	 * قالب‌های پیش‌فرض پیامک (قابل بازنویسی با فیلتر یا سفارشی‌ساز).
	 *
	 * جای‌گیرها: {code} {fault} {phone} {brand} {conf} {order_code} {status} {time} {site}
	 *
	 * @return array<string, string>
	 */
	function pixva_sms_handler_templates() {
		$site = function_exists( 'pixva_option' ) ? (string) pixva_option( 'pixva_site_short_name', 'پیکسوا' ) : 'پیکسوا';

		$templates = array(
			'welcome' => (string) pixva_option(
				'pixva_sms_welcome_text',
				sprintf(
					/* translators: %s: نام سایت */
					__( '%s: درخواست عیب‌یابی هوشمند شما ثبت شد. کد پیگیری: {code}. تشخیص: {fault}. کارشناسان ما به‌زودی با شماره {phone} تماس می‌گیرند.', 'pixva' ),
					$site
				)
			),
			'status'  => (string) pixva_option(
				'pixva_sms_status_text',
				sprintf(
					/* translators: %s: نام سایت */
					__( '%s: پرونده تعمیر شما با کد {order_code} ثبت و به واحد فنی ارجاع شد. وضعیت: {status}. زمان تقریبی: {time}.', 'pixva' ),
					$site
				)
			),
			'admin'   => (string) pixva_option(
				'pixva_sms_admin_text',
				sprintf(
					/* translators: %s: نام سایت */
					__( '%s: عیب‌یابی جدید — {brand} | {fault} (اطمینان {conf}٪). مشتری: {phone}. مشاهده در پیشخوان ← تاریخچه عیب‌یابی AI.', 'pixva' ),
					$site
				)
			),
		);

		/**
		 * فیلتر قالب‌های پیامک عیب‌یاب.
		 *
		 * @param array $templates قالب‌ها.
		 */
		return apply_filters( 'pixva_sms_handler_templates', $templates );
	}
}

if ( ! function_exists( 'pixva_sms_handler_fill' ) ) {
	/**
	 * جای‌گذاری متغیرها در قالب پیامک.
	 *
	 * @param string $template     قالب.
	 * @param array  $replacements کلید => مقدار (بدون آکولاد).
	 * @return string
	 */
	function pixva_sms_handler_fill( $template, $replacements = array() ) {
		$template = (string) $template;
		foreach ( (array) $replacements as $key => $value ) {
			$template = str_replace( '{' . $key . '}', (string) $value, $template );
		}
		// پاک‌سازی جای‌گیرهای پر نشده.
		$template = (string) preg_replace( '/\{[a-zA-Z0-9_]+\}/', '', $template );
		return trim( (string) preg_replace( '/[ \t]{2,}/', ' ', $template ) );
	}
}

if ( ! function_exists( 'pixva_sms_handler_dispatch' ) ) {
	/**
	 * فراخوانی HTTP درگاه پیامک انتخاب‌شده (بومی، با wp_remote_post).
	 *
	 * @param array  $s            تنظیمات.
	 * @param string $to           شماره مقصد.
	 * @param string $message      متن پیام.
	 * @param string $pattern_token مقدار جای‌گیر الگو (مثلاً کد پیگیری).
	 * @return bool
	 */
	function pixva_sms_handler_dispatch( $s, $to, $message, $pattern_token = '' ) {
		$provider = (string) $s['provider'];
		$api_key  = (string) $s['api_key'];
		$sender   = (string) $s['sender'];
		$pattern  = (string) $s['pattern'];

		$url     = '';
		$body    = array();
		$headers = array(
			'Content-Type' => 'application/json',
			'Accept'       => 'application/json',
		);
		$ok      = array( 200, 201 );

		switch ( $provider ) {
			case 'kavenegar':
				if ( '' !== $pattern && '' !== $pattern_token ) {
					$url  = 'https://api.kavenegar.com/v1/' . rawurlencode( $api_key ) . '/verify/lookup.json';
					$body = array(
						'receptor' => $to,
						'token'    => $pattern_token,
						'template' => $pattern,
					);
				} else {
					$url  = 'https://api.kavenegar.com/v1/' . rawurlencode( $api_key ) . '/sms/send.json';
					$body = array(
						'sender'   => $sender,
						'receptor' => $to,
						'message'  => $message,
					);
				}
				break;

			case 'ippanel':
				$headers['apikey'] = $api_key;
				if ( '' !== $pattern ) {
					$url  = (string) apply_filters( 'pixva_sms_ippanel_pattern_url', 'https://rest.ippanel.com/v1/send/pattern' );
					$body = array(
						'code'      => $pattern,
						'sender'    => $sender,
						'recipient' => $to,
						'variable'  => array(
							'args' => array(
								'tracking_code' => $pattern_token,
								'message'       => $message,
							),
						),
					);
				} else {
					$url  = (string) apply_filters( 'pixva_sms_ippanel_send_url', 'https://rest.ippanel.com/v1/message/send' );
					$body = array(
						'sender'    => $sender,
						'recipient' => array( $to ),
						'text'      => $message,
					);
				}
				break;

			case 'melipayamak':
				if ( '' !== $pattern ) {
					$url  = 'https://restapi.payamak.com/api/v2/send/verify';
					$body = array(
						'username' => $sender,
						'password' => $api_key,
						'mobile'   => $to,
						'text'     => $pattern_token,
					);
				} else {
					$url  = 'https://restapi.payamak.com/api/v2/SendSMS';
					$body = array(
						'username' => $sender,
						'password' => $api_key,
						'from'     => $sender,
						'to'       => $to,
						'text'     => $message,
						'isFlash'  => false,
					);
				}
				$headers['Content-Type'] = 'application/json';
				break;

			case 'smsir':
				$url                     = 'https://api.sms.ir/v1/send/verify';
				$headers['Authorization'] = 'Bearer ' . $api_key;
				$body                    = array(
					'mobile'     => $to,
					'templateId' => $pattern,
					'parameters' => array(
						array( 'name' => 'TEXT', 'value' => '' !== $pattern_token ? $pattern_token : $message ),
					),
				);
				break;

			default:
				return false;
		}

		/**
		 * فیلتر آدرس/بدنه درخواست پیامک پیش از ارسال.
		 *
		 * @param string $url      آدرس.
		 * @param array  $body     بدنه.
		 * @param array  $headers  هدرها.
		 * @param array  $s        تنظیمات.
		 * @param string $to       مقصد.
		 * @param string $message  متن.
		 */
		$filtered = apply_filters( 'pixva_sms_handler_request', compact( 'url', 'body', 'headers' ), $s, $to, $message );
		$url      = isset( $filtered['url'] ) ? (string) $filtered['url'] : $url;
		$body     = isset( $filtered['body'] ) && is_array( $filtered['body'] ) ? $filtered['body'] : $body;
		$headers  = isset( $filtered['headers'] ) && is_array( $filtered['headers'] ) ? $filtered['headers'] : $headers;

		$response = wp_remote_post(
			$url,
			array(
				'timeout' => (int) $s['timeout'],
				'headers' => $headers,
				'body'    => wp_json_encode( $body ),
			)
		);

		if ( is_wp_error( $response ) ) {
			return false;
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		return in_array( $code, $ok, true );
	}
}

if ( ! function_exists( 'pixva_sms_handler_send' ) ) {
	/**
	 * ارسال یک پیامک عیب‌یاب (با ثبت در گزارش و هوک‌های پوسته).
	 *
	 * @param string $to            شماره مقصد.
	 * @param string $message       متن.
	 * @param string $context       کلید رویداد (ai_welcome/ai_status/ai_admin).
	 * @param string $pattern_token مقدار الگو.
	 * @return bool|WP_Error
	 */
	function pixva_sms_handler_send( $to, $message, $context = 'ai', $pattern_token = '' ) {
		$to      = function_exists( 'pixva_normalize_mobile' ) ? pixva_normalize_mobile( (string) $to ) : sanitize_text_field( (string) $to );
		$message = trim( (string) $message );

		if ( '' === $to || '' === $message ) {
			return new WP_Error( 'pixva_sms_empty', __( 'شماره یا متن پیامک خالی است.', 'pixva' ) );
		}

		/**
		 * فیلتر متن پیامک عیب‌یاب پیش از ارسال.
		 *
		 * @param string $message متن.
		 * @param string $to      مقصد.
		 * @param string $context رویداد.
		 */
		$message = (string) apply_filters( 'pixva_sms_message', $message, $to, $context );

		$s = pixva_sms_handler_settings();

		/**
		 * هوک پیش از ارسال: مقدار غیر null ارسال را لغو می‌کند (صف/سرویس دلخواه).
		 *
		 * @param bool|null $pre     نتیجه پیشاپیش.
		 * @param string    $to      مقصد.
		 * @param string    $message متن.
		 * @param string    $context رویداد.
		 */
		$pre = apply_filters( 'pixva_pre_send_sms', null, $to, $message, $context );
		if ( null !== $pre ) {
			do_action( 'pixva_sms_sent', $to, $message, $context, (bool) $pre );
			return (bool) $pre;
		}

		if ( ! pixva_sms_handler_configured() ) {
			if ( function_exists( 'pixva_crm_log_sms' ) ) {
				pixva_crm_log_sms( $to, $message, $context, 'queued' );
			}
			do_action( 'pixva_sms_queued', $to, $message, $context );
			return false;
		}

		$result = pixva_sms_handler_dispatch( $s, $to, $message, $pattern_token );

		if ( function_exists( 'pixva_crm_log_sms' ) ) {
			pixva_crm_log_sms( $to, $message, $context, $result ? 'sent' : 'failed' );
		}

		/**
		 * هوک پس از ارسال پیامک عیب‌یاب.
		 *
		 * @param string $to      مقصد.
		 * @param string $message متن.
		 * @param string $context رویداد.
		 * @param bool   $result  موفقیت.
		 */
		do_action( 'pixva_sms_sent', $to, $message, $context, $result );

		return $result;
	}
}

/* ==========================================================================
   هوک‌های رویداد — اتصال به چرخه عیب‌یابی و تبدیل به سفارش
   ========================================================================== */

if ( ! function_exists( 'pixva_ai_sms_on_diagnose' ) ) {
	/**
	 * پیامک خوش‌آمدگویی/کد پیگیری به مشتری + هشدار به ادمین، پس از ثبت عیب‌یابی.
	 *
	 * @param int   $post_id       شناسه سابقه.
	 * @param int   $attachment_id شناسه رسانه.
	 * @param array $result        خروجی REST.
	 * @return void
	 */
	function pixva_ai_sms_on_diagnose( $post_id, $attachment_id, $result ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
		if ( ! pixva_sms_handler_configured() ) {
			return;
		}

		$templates = pixva_sms_handler_templates();
		$phone     = (string) get_post_meta( (int) $post_id, '_pixva_ai_phone', true );
		$ticket    = isset( $result['ticket'] ) ? (string) $result['ticket'] : (string) get_post_meta( (int) $post_id, '_pixva_ai_code', true );
		$fault     = isset( $result['fault_type'] ) && '' !== $result['fault_type'] ? (string) $result['fault_type'] : __( 'در صف تحلیل کارشناس', 'pixva' );
		$brand     = (string) get_post_meta( (int) $post_id, '_pixva_ai_brand_model', true );
		if ( '' === $brand ) {
			$brand = (string) get_post_meta( (int) $post_id, '_pixva_ai_brand', true );
		}
		$conf = isset( $result['confidence'] ) ? (int) $result['confidence'] : 0;

		$replacements = array(
			'code'   => $ticket,
			'fault'  => $fault,
			'phone'  => $phone,
			'brand'  => '' !== $brand ? $brand : __( 'نامشخص', 'pixva' ),
			'conf'   => function_exists( 'pixva_fa_num' ) ? pixva_fa_num( $conf ) : (string) $conf,
			'site'   => function_exists( 'pixva_option' ) ? (string) pixva_option( 'pixva_site_short_name', 'پیکسوا' ) : 'پیکسوا',
		);

		// ۱) پیامک مشتری (فقط با شماره معتبر ایران).
		$valid = function_exists( 'pixva_is_valid_iranian_mobile' ) ? pixva_is_valid_iranian_mobile( $phone ) : ( '' !== $phone );
		if ( $valid ) {
			pixva_sms_handler_send( $phone, pixva_sms_handler_fill( $templates['welcome'], $replacements ), 'ai_welcome', $ticket );
		}

		// ۳) هشدار به ادمین/تعمیرکار.
		$s = pixva_sms_handler_settings();
		if ( '' !== $s['admin_number'] ) {
			pixva_sms_handler_send( $s['admin_number'], pixva_sms_handler_fill( $templates['admin'], $replacements ), 'ai_admin', $ticket );
		}
	}
	add_action( 'pixva_ai_diagnose_saved', 'pixva_ai_sms_on_diagnose', 20, 3 );
}

if ( ! function_exists( 'pixva_ai_sms_on_convert' ) ) {
	/**
	 * پیامک وضعیت به مشتری پس از تبدیل عیب‌یابی به پرونده سفارش.
	 *
	 * @param int  $order_id   شناسه پرونده.
	 * @param int  $log_id     شناسه سابقه.
	 * @param bool $auto_draft خودکار بودن.
	 * @return void
	 */
	function pixva_ai_sms_on_convert( $order_id, $log_id, $auto_draft = false ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
		if ( ! pixva_sms_handler_configured() ) {
			return;
		}

		$phone = (string) get_post_meta( (int) $log_id, '_pixva_ai_phone', true );
		$valid = function_exists( 'pixva_is_valid_iranian_mobile' ) ? pixva_is_valid_iranian_mobile( $phone ) : ( '' !== $phone );
		if ( ! $valid ) {
			return;
		}

		$order_code = (string) get_post_meta( (int) $order_id, '_pixva_order_code', true );
		$status     = function_exists( 'pixva_crm_status_label' ) ? pixva_crm_status_label( (string) get_post_meta( (int) $order_id, '_pixva_order_status', true ) ) : __( 'ثبت شد', 'pixva' );
		$time       = (string) get_post_meta( (int) $log_id, '_pixva_ai_time', true );
		if ( '' === $time ) {
			$time = __( 'هماهنگی پس از تماس کارشناس', 'pixva' );
		}

		$templates = pixva_sms_handler_templates();
		$message   = pixva_sms_handler_fill(
			$templates['status'],
			array(
				'order_code' => $order_code,
				'status'     => $status,
				'time'       => $time,
				'phone'      => $phone,
				'site'       => function_exists( 'pixva_option' ) ? (string) pixva_option( 'pixva_site_short_name', 'پیکسوا' ) : 'پیکسوا',
			)
		);

		pixva_sms_handler_send( $phone, $message, 'ai_status', $order_code );
	}
	add_action( 'pixva_ai_log_converted', 'pixva_ai_sms_on_convert', 20, 3 );
}

<?php
/**
 * هندلر بومی عیب‌یابی هوش مصنوعی با Google Gemini — لایه ۱٫۸٫۰ (Master Prompt v9).
 *
 * این پرونده جایگزین کامل مسیر «عامل پایتون» لایه ۱٫۶٫۰ است و همه‌چیز را با
 * توابع بومی وردپرس و API گوگل جمینای اجرا می‌کند:
 *
 *  ۱) تنظیمات: کلید API، مدل، سقف حجم فایل و ایجاد خودکار پیش‌نویس سفارش
 *     (کلیدهای pixva_gemini_api_key / pixva_gemini_model / pixva_ai_max_file_size /
 *      pixva_ai_auto_create_draft) در سفارشی‌ساز و پنل بومی پیشخوان.
 *  ۲) اندپوینت بومی REST: POST wp-json/pixva/v1/ai-diagnose با اعتبارسنجی nonce.
 *  ۳) ارتباط با Gemini: آپلود فایل رسانه در Files API گوگل
 *     (https://generativelanguage.googleapis.com/upload/v1beta/files) با
 *     wp_remote_post، سپس generateContent با پرامپت مهندسی‌شده و خروجی JSON
 *     ساخت‌یافته، ثبت در تاریخچه پیشخوان و پاک‌سازی فایل موقت سمت گوگل.
 *  ۴) پیشخوان: زیرمنوی «تاریخچه عیب‌یابی AI» با جدول نتایج و دکمه
 *     «تبدیل به سفارش تعمیرات» (یک کلیک → پرونده pixva_orders).
 *
 * توابع کمکی مشترک (انواع مجاز، کد پیگیری، محدودسازی نرخ، دسترسی و ذخیره
 * رسانه) در inc/ai-diagnose.php باقی مانده‌اند و اینجا مصرف می‌شوند.
 *
 * @package Pixva
 * @since   1.8.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ==========================================================================
   ۱) تنظیمات — کلید/مدل/سقف/پیش‌نویس (یک کلید برای کل قالب)
   ========================================================================== */

if ( ! function_exists( 'pixva_ai_handler_models' ) ) {
	/**
	 * فهرست مدل‌های جمینای برای انتخاب در پنل.
	 *
	 * @return array<string, string>
	 */
	function pixva_ai_handler_models() {
		$models = array(
			'gemini-1.5-flash'    => __( 'Gemini 1.5 Flash — سریع و رایگان (پیش‌فرض)', 'pixva' ),
			'gemini-1.5-flash-8b' => __( 'Gemini 1.5 Flash 8B — سبک‌ترین مدل', 'pixva' ),
			'gemini-1.5-pro'      => __( 'Gemini 1.5 Pro — دقیق‌تر و کندتر', 'pixva' ),
			'gemini-2.0-flash'    => __( 'Gemini 2.0 Flash — نسل جدید', 'pixva' ),
			'gemini-2.5-flash'    => __( 'Gemini 2.5 Flash — تازه‌ترین مدل سریع', 'pixva' ),
			'gemini-2.5-pro'      => __( 'Gemini 2.5 Pro — دقیق‌ترین مدل', 'pixva' ),
		);

		/**
		 * فیلتر فهرست مدل‌های جمینای.
		 *
		 * @param array $models مدل‌ها.
		 */
		return apply_filters( 'pixva_ai_handler_models', $models );
	}
}

if ( ! function_exists( 'pixva_ai_handler_key' ) ) {
	/**
	 * کلید API جمینای (یک کلید برای کل قالب).
	 *
	 * اولویت با کلید اختصاصی عیب‌یاب در سفارشی‌ساز است؛ اگر خالی باشد، کلید
	 * مرکز کنترل (ai_gemini_key) به‌عنوان fallback خوانده می‌شود تا چت‌بات
	 * شناور و عیب‌یاب هر دو با یک کلید کار کنند. کلید فقط سمت سرور می‌ماند.
	 *
	 * @return string
	 */
	function pixva_ai_handler_key() {
		$key = trim( (string) pixva_option( 'pixva_gemini_api_key', '' ) );
		if ( '' !== $key ) {
			return $key;
		}

		$options = function_exists( 'pixva_control_options' ) ? pixva_control_options() : array();
		return isset( $options['ai_gemini_key'] ) ? trim( (string) $options['ai_gemini_key'] ) : '';
	}
}

if ( ! function_exists( 'pixva_ai_handler_model' ) ) {
	/**
	 * مدل جمینای مؤثر.
	 *
	 * @return string
	 */
	function pixva_ai_handler_model() {
		$model = sanitize_key( (string) pixva_option( 'pixva_gemini_model', 'gemini-1.5-flash' ) );
		if ( '' === $model ) {
			$options = function_exists( 'pixva_control_options' ) ? pixva_control_options() : array();
			$model   = isset( $options['ai_gemini_model'] ) ? sanitize_key( (string) $options['ai_gemini_model'] ) : '';
		}
		if ( '' === $model || ! array_key_exists( $model, pixva_ai_handler_models() ) ) {
			$model = 'gemini-1.5-flash';
		}
		return $model;
	}
}

if ( ! function_exists( 'pixva_ai_handler_max_size' ) ) {
	/**
	 * سقف حجم فایل رسانه (مگابایت).
	 *
	 * @return int
	 */
	function pixva_ai_handler_max_size() {
		$max = (int) pixva_option( 'pixva_ai_max_file_size', 50 );
		if ( $max < 1 ) {
			$max = (int) pixva_option( 'pixva_ai_max_size', 50 );
		}
		/**
		 * فیلتر سقف حجم فایل عیب‌یابی (مگابایت).
		 *
		 * @param int $max مگابایت.
		 */
		return (int) apply_filters( 'pixva_ai_handler_max_size', max( 1, min( 512, $max ) ) );
	}
}

if ( ! function_exists( 'pixva_ai_handler_auto_draft' ) ) {
	/**
	 * آیا به محض تشخیص خطا، پیش‌نویس سفارش خودکار ساخته شود؟
	 *
	 * @return bool
	 */
	function pixva_ai_handler_auto_draft() {
		return (bool) pixva_option( 'pixva_ai_auto_create_draft', false );
	}
}

if ( ! function_exists( 'pixva_ai_handler_configured' ) ) {
	/**
	 * آیا کلید API جمینای تنظیم شده است؟
	 *
	 * @return bool
	 */
	function pixva_ai_handler_configured() {
		return '' !== pixva_ai_handler_key();
	}
}

/* ==========================================================================
   ۲) پرامپت مهندسی‌شده و طرح خروجی ساخت‌یافته
   ========================================================================== */

if ( ! function_exists( 'pixva_ai_handler_prompt' ) ) {
	/**
	 * پرامپت سیستمی عیب‌یابی تخصصی تلویزیون با خروجی JSON دقیق.
	 *
	 * @return string
	 */
	function pixva_ai_handler_prompt() {
		$example = '{' . "\n"
			. '  "fault_type": "نام دقیق و فارسی ایراد (مثلاً: خرابی دیودهای بک‌لایت)",' . "\n"
			. '  "confidence": 92,' . "\n"
			. '  "symptoms_detected": ["علامت اول در ویدیو/صدا", "علامت دوم"],' . "\n"
			. '  "estimated_cost_range": "۱,۲۰۰,۰۰۰ تا ۱,۸۰۰,۰۰۰ تومان",' . "\n"
			. '  "repair_time": "کمتر از ۴ ساعت (خدمات در محل)",' . "\n"
			. '  "technical_note": "توضیح کوتاه فنی جهت اطمینان‌بخشی به مشتری"' . "\n"
			. '}';

		$prompt = 'شما کارشناس ارشد عیب‌یابی تلویزیون و نمایشگر در کارگاه تخصصی «پیکسوا» هستید. '
			. 'یک فایل رسانه‌ای (ویدیو یا صدای ضبط‌شده از دستگاه خراب) به‌همراه برند، مدل و شرح مشتری دریافت می‌کنید. '
			. 'محتوای رسانه را با دقت تحلیل کن: تصویر صفحه (خطوط، لکه، پرش، خاموشی بک‌لایت، شکستگی)، صدای دستگاه (وزوز، کلیک، نویز برد تغذیه) و علائم گزارش‌شده. '
			. 'پاسخ را فقط و فقط به‌صورت یک شیء JSON معتبر و بدون هیچ متن اضافه، توضیح یا بلوک کد برگردان. '
			. 'کلیدها دقیقاً این‌ها باشند: '
			. 'fault_type (نام دقیق و فارسی ایراد)، '
			. 'confidence (عدد صحیح ۰ تا ۱۰۰ نشان‌دهنده درصد اطمینان)، '
			. 'symptoms_detected (آرایه‌ای از علائم مشاهده‌شده در ویدیو/صدا، هرکدام یک رشته کوتاه فارسی)، '
			. 'estimated_cost_range (بازه هزینه تعمیر به تومان با جداکننده هزارگان و واژه «تومان» در انتها)، '
			. 'repair_time (زمان تقریبی تعمیر به‌همراه نوع خدمات، مثل «کمتر از ۴ ساعت (خدمات در محل)»)، '
			. 'technical_note (توضیح کوتاه فنی و اطمینان‌بخش برای مشتری، حداکثر ۲ جمله). '
			. 'اگر رسانه برای تشخیص قطعی کافی نیست، مقدار confidence را پایین (زیر ۵۰) بده و در technical_note بگو چه اطلاعات یا نمای بیشتری لازم است؛ حدس بی‌اساس نزن. '
			. 'هشدار ایمنی برد تغذیه و خطر برق‌گرفتگی را در صورت نیاز داخل technical_note یادآوری کن. '
			. 'خروجی دقیقاً این ساختار باشد:' . "\n" . $example;

		/**
		 * فیلتر پرامپت عیب‌یابی جمینای.
		 *
		 * @param string $prompt پرامپت.
		 */
		return apply_filters( 'pixva_ai_handler_prompt', $prompt );
	}
}

if ( ! function_exists( 'pixva_ai_handler_schema' ) ) {
	/**
	 * طرح پاسخ ساخت‌یافته (responseSchema) برای جمینای.
	 *
	 * @return array<string, mixed>
	 */
	function pixva_ai_handler_schema() {
		return array(
			'type'       => 'OBJECT',
			'properties' => array(
				'fault_type'           => array( 'type' => 'STRING' ),
				'confidence'           => array( 'type' => 'INTEGER' ),
				'symptoms_detected'    => array(
					'type'  => 'ARRAY',
					'items' => array( 'type' => 'STRING' ),
				),
				'estimated_cost_range' => array( 'type' => 'STRING' ),
				'repair_time'          => array( 'type' => 'STRING' ),
				'technical_note'       => array( 'type' => 'STRING' ),
			),
			'required'   => array( 'fault_type', 'confidence', 'symptoms_detected', 'estimated_cost_range', 'repair_time', 'technical_note' ),
		);
	}
}

/* ==========================================================================
   ۳) ارتباط بومی با Gemini (Files API + generateContent) با wp_remote_post
   ========================================================================== */

if ( ! function_exists( 'pixva_ai_handler_upload' ) ) {
	/**
	 * آپلود فایل رسانه به Files API گوگل (پروتکل resumable، دو مرحله).
	 *
	 * @param string $path مسیر مطلق فایل روی هاست.
	 * @param string $mime نوع MIME.
	 * @param string $name نام نمایشی.
	 * @return array{file_name:string, file_uri:string, mime:string, state:string}|WP_Error
	 */
	function pixva_ai_handler_upload( $path, $mime, $name ) {
		$key = pixva_ai_handler_key();
		if ( '' === $key ) {
			return new WP_Error( 'pixva_ai_no_key', __( 'کلید API گوگل جمینای در پیشخوان تنظیم نشده است.', 'pixva' ) );
		}
		if ( ! is_readable( $path ) ) {
			return new WP_Error( 'pixva_ai_read', __( 'خواندن فایل رسانه روی سرور ممکن نشد.', 'pixva' ) );
		}

		$size     = (int) filesize( $path );
		$start    = wp_remote_post(
			'https://generativelanguage.googleapis.com/upload/v1beta/files',
			array(
				'timeout' => (int) apply_filters( 'pixva_ai_upload_start_timeout', 60 ),
				'headers' => array(
					'x-goog-api-key'                    => $key,
					'Content-Type'                      => 'application/json; charset=utf-8',
					'X-Goog-Upload-Protocol'            => 'resumable',
					'X-Goog-Upload-Command'             => 'start',
					'X-Goog-Upload-Header-Content-Length' => (string) $size,
					'X-Goog-Upload-Header-Content-Type' => (string) $mime,
				),
				'body'    => wp_json_encode( array( 'file' => array( 'display_name' => $name ) ) ),
			)
		);

		if ( is_wp_error( $start ) ) {
			return $start;
		}

		$code       = (int) wp_remote_retrieve_response_code( $start );
		$upload_url = (string) wp_remote_retrieve_header( $start, 'x-goog-upload-url' );
		if ( $code < 200 || $code >= 300 || '' === $upload_url ) {
			$body   = json_decode( (string) wp_remote_retrieve_body( $start ), true );
			$detail = is_array( $body ) && isset( $body['error']['message'] ) ? (string) $body['error']['message'] : '';
			return new WP_Error(
				'pixva_ai_upload_start',
				sprintf(
					/* translators: 1: کد HTTP، 2: جزئیات */
					__( 'شروع آپلود فایل به گوگل ناموفق بود (HTTP %1$d). %2$s', 'pixva' ),
					$code,
					$detail
				)
			);
		}

		$bytes = file_get_contents( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		if ( false === $bytes ) {
			return new WP_Error( 'pixva_ai_read', __( 'خواندن فایل رسانه روی سرور ممکن نشد.', 'pixva' ) );
		}

		$upload = wp_remote_post(
			$upload_url,
			array(
				'timeout' => (int) apply_filters( 'pixva_ai_upload_timeout', 180 ),
				'headers' => array(
					'x-goog-api-key'         => $key,
					'Content-Type'           => (string) $mime,
					'X-Goog-Upload-Command'  => 'upload, finalize',
					'X-Goog-Upload-Offset'   => '0',
				),
				'body'    => $bytes,
			)
		);

		if ( is_wp_error( $upload ) ) {
			return $upload;
		}

		$code2 = (int) wp_remote_retrieve_response_code( $upload );
		$body2 = json_decode( (string) wp_remote_retrieve_body( $upload ), true );
		if ( $code2 < 200 || $code2 >= 300 || ! is_array( $body2 ) || empty( $body2['file'] ) ) {
			$detail = is_array( $body2 ) && isset( $body2['error']['message'] ) ? (string) $body2['error']['message'] : '';
			return new WP_Error(
				'pixva_ai_upload',
				sprintf(
					/* translators: 1: کد HTTP، 2: جزئیات */
					__( 'آپلود فایل به گوگل ناموفق بود (HTTP %1$d). %2$s', 'pixva' ),
					$code2,
					$detail
				)
			);
		}

		$file = (array) $body2['file'];
		return array(
			'file_name' => isset( $file['name'] ) ? (string) $file['name'] : '',
			'file_uri'  => isset( $file['uri'] ) ? (string) $file['uri'] : '',
			'mime'      => (string) $mime,
			'state'     => isset( $file['state'] ) ? strtoupper( (string) $file['state'] ) : '',
		);
	}
}

if ( ! function_exists( 'pixva_ai_handler_wait_file' ) ) {
	/**
	 * انتظار برای آماده (ACTIVE) شدن فایل پردازش‌شده در سمت گوگل.
	 *
	 * @param array $uploaded خروجی pixva_ai_handler_upload.
	 * @return array|WP_Error
	 */
	function pixva_ai_handler_wait_file( $uploaded ) {
		if ( 'ACTIVE' === $uploaded['state'] || '' === $uploaded['file_name'] ) {
			return $uploaded;
		}

		$key   = pixva_ai_handler_key();
		$url   = 'https://generativelanguage.googleapis.com/v1beta/files/' . rawurlencode( $uploaded['file_name'] );
		$polls = (int) apply_filters( 'pixva_ai_file_polls', 8 );

		for ( $i = 0; $i < $polls; $i++ ) {
			$response = wp_remote_get(
				$url,
				array(
					'timeout' => 20,
					'headers' => array( 'x-goog-api-key' => $key ),
				)
			);
			if ( is_wp_error( $response ) ) {
				return $response;
			}
			$body  = json_decode( (string) wp_remote_retrieve_body( $response ), true );
			$state = is_array( $body ) && isset( $body['state'] ) ? strtoupper( (string) $body['state'] ) : '';
			if ( 'ACTIVE' === $state ) {
				$uploaded['state']    = $state;
				$uploaded['file_uri'] = isset( $body['uri'] ) ? (string) $body['uri'] : $uploaded['file_uri'];
				return $uploaded;
			}
			if ( 'FAILED' === $state ) {
				return new WP_Error( 'pixva_ai_file_failed', __( 'پردازش فایل رسانه در سمت گوگل ناموفق بود.', 'pixva' ) );
			}
			sleep( 1 );
		}

		return new WP_Error( 'pixva_ai_file_timeout', __( 'پردازش فایل رسانه بیش از حد طول کشید؛ دوباره تلاش کنید.', 'pixva' ) );
	}
}

if ( ! function_exists( 'pixva_ai_handler_user_text' ) ) {
	/**
	 * ساخت متن زمینه درخواست (برند/مدل/شرح) برای ضمیمه به فایل.
	 *
	 * @param array $context زمینه.
	 * @return string
	 */
	function pixva_ai_handler_user_text( $context ) {
		$lines = array();
		$brand = isset( $context['brand_label'] ) ? (string) $context['brand_label'] : '';
		if ( '' !== $brand ) {
			$lines[] = sprintf( __( 'برند دستگاه: %s', 'pixva' ), $brand );
		}
		if ( ! empty( $context['model'] ) ) {
			$lines[] = sprintf( __( 'مدل دستگاه: %s', 'pixva' ), (string) $context['model'] );
		}
		if ( ! empty( $context['symptom'] ) ) {
			$lines[] = sprintf( __( 'شرح مشتری از خرابی: %s', 'pixva' ), (string) $context['symptom'] );
		}
		$lines[] = __( 'فایل رسانه‌ای پیوست (ویدیو یا صدای دستگاه) را تحلیل کن و خروجی را دقیقاً در قالب JSON خواسته‌شده برگردان.', 'pixva' );
		return implode( "\n", $lines );
	}
}

if ( ! function_exists( 'pixva_ai_handler_generate' ) ) {
	/**
	 * ارسال فایل + پرامپت به مدل جمینای و دریافت پاسخ ساخت‌یافته.
	 *
	 * @param array $file    فایل آماده (file_uri/file_name/mime).
	 * @param array $context زمینه درخواست.
	 * @return array{raw:string, model:string, usage:array}|WP_Error
	 */
	function pixva_ai_handler_generate( $file, $context ) {
		$key   = pixva_ai_handler_key();
		$model = pixva_ai_handler_model();
		$url   = 'https://generativelanguage.googleapis.com/v1beta/models/' . rawurlencode( $model ) . ':generateContent';

		$parts = array();
		$uri   = ! empty( $file['file_uri'] ) ? (string) $file['file_uri'] : '';
		if ( '' === $uri && ! empty( $file['file_name'] ) ) {
			$uri = 'https://generativelanguage.googleapis.com/v1beta/' . ltrim( (string) $file['file_name'], '/' );
		}
		if ( '' !== $uri ) {
			$parts[] = array(
				'file_data' => array(
					'mime_type' => (string) $file['mime'],
					'file_uri'  => $uri,
				),
			);
		}
		$parts[] = array( 'text' => pixva_ai_handler_user_text( $context ) );

		$payload = array(
			'systemInstruction' => array(
				'role'  => 'system',
				'parts' => array( array( 'text' => pixva_ai_handler_prompt() ) ),
			),
			'contents'          => array(
				array(
					'role'  => 'user',
					'parts' => $parts,
				),
			),
			'generationConfig'  => array(
				'temperature'      => (float) apply_filters( 'pixva_ai_handler_temperature', 0.2 ),
				'maxOutputTokens'  => (int) apply_filters( 'pixva_ai_handler_max_tokens', 1024 ),
				'responseMimeType' => 'application/json',
				'responseSchema'   => pixva_ai_handler_schema(),
			),
			'safetySettings'    => array(
				array(
					'category'  => 'HARM_CATEGORY_DANGEROUS_CONTENT',
					'threshold' => 'BLOCK_ONLY_HIGH',
				),
			),
		);

		$response = wp_remote_post(
			$url,
			array(
				'timeout' => (int) apply_filters( 'pixva_ai_generate_timeout', 90 ),
				'headers' => array(
					'Content-Type'   => 'application/json',
					'x-goog-api-key' => $key,
				),
				'body'    => wp_json_encode( $payload ),
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		$body = json_decode( (string) wp_remote_retrieve_body( $response ), true );
		if ( $code < 200 || $code >= 300 ) {
			$detail = is_array( $body ) && isset( $body['error']['message'] ) ? (string) $body['error']['message'] : '';
			return new WP_Error(
				'pixva_ai_http_error',
				sprintf(
					/* translators: 1: کد HTTP، 2: جزئیات */
					__( 'پاسخ خطای %1$d از سرویس جمینای. %2$s', 'pixva' ),
					$code,
					$detail
				)
			);
		}

		$text = '';
		if ( is_array( $body ) && isset( $body['candidates'] ) && is_array( $body['candidates'] ) ) {
			foreach ( $body['candidates'] as $candidate ) {
				if ( empty( $candidate['content']['parts'] ) || ! is_array( $candidate['content']['parts'] ) ) {
					continue;
				}
				foreach ( $candidate['content']['parts'] as $part ) {
					if ( isset( $part['text'] ) ) {
						$text .= (string) $part['text'];
					}
				}
			}
		}

		if ( '' === trim( $text ) ) {
			$reason = is_array( $body ) && isset( $body['candidates'][0]['finishReason'] ) ? (string) $body['candidates'][0]['finishReason'] : '';
			return new WP_Error(
				'pixva_ai_empty',
				'' !== $reason
					? sprintf( __( 'سرویس جمینای پاسخی تولید نکرد (%s).', 'pixva' ), $reason )
					: __( 'سرویس جمینای پاسخی تولید نکرد.', 'pixva' )
			);
		}

		$usage = array();
		if ( is_array( $body ) && isset( $body['usageMetadata'] ) ) {
			$usage = array(
				'prompt_tokens'     => isset( $body['usageMetadata']['promptTokenCount'] ) ? (int) $body['usageMetadata']['promptTokenCount'] : 0,
				'completion_tokens' => isset( $body['usageMetadata']['candidatesTokenCount'] ) ? (int) $body['usageMetadata']['candidatesTokenCount'] : 0,
			);
		}

		return array(
			'raw'   => trim( $text ),
			'model' => $model,
			'usage' => $usage,
		);
	}
}

if ( ! function_exists( 'pixva_ai_handler_delete_file' ) ) {
	/**
	 * پاک‌سازی فایل موقت آپلودشده از سمت گوگل پس از اتمام تحلیل.
	 *
	 * @param string $file_name نام فایل (files/…).
	 * @return void
	 */
	function pixva_ai_handler_delete_file( $file_name ) {
		$file_name = (string) $file_name;
		if ( '' === $file_name ) {
			return;
		}
		$key = pixva_ai_handler_key();
		if ( '' === $key ) {
			return;
		}
		wp_remote_request(
			'https://generativelanguage.googleapis.com/v1beta/files/' . rawurlencode( $file_name ),
			array(
				'method'  => 'DELETE',
				'timeout' => 20,
				'headers' => array( 'x-goog-api-key' => $key ),
			)
		);
	}
}

if ( ! function_exists( 'pixva_ai_handler_parse' ) ) {
	/**
	 * تجزیه و نرمال‌سازی پاسخ JSON مدل به ساختار استاندارد عیب‌یابی.
	 *
	 * @param string $raw پاسخ خام.
	 * @return array|null
	 */
	function pixva_ai_handler_parse( $raw ) {
		$raw  = trim( (string) $raw );
		$data = json_decode( $raw, true );

		if ( ! is_array( $data ) ) {
			$start = strpos( $raw, '{' );
			$end   = strrpos( $raw, '}' );
			if ( false !== $start && false !== $end && $end > $start ) {
				$data = json_decode( substr( $raw, $start, $end - $start + 1 ), true );
			}
		}
		if ( ! is_array( $data ) ) {
			return null;
		}

		$confidence = isset( $data['confidence'] ) ? (int) round( (float) $data['confidence'] ) : 0;
		$confidence = max( 0, min( 100, $confidence ) );

		$symptoms = array();
		if ( isset( $data['symptoms_detected'] ) && is_array( $data['symptoms_detected'] ) ) {
			foreach ( $data['symptoms_detected'] as $symptom ) {
				$clean = sanitize_text_field( wp_strip_all_tags( (string) $symptom ) );
				if ( '' !== $clean ) {
					$symptoms[] = $clean;
				}
			}
		}
		$symptoms = array_slice( array_values( $symptoms ), 0, 8 );

		return array(
			'fault_type'           => sanitize_text_field( wp_strip_all_tags( isset( $data['fault_type'] ) ? (string) $data['fault_type'] : '' ) ),
			'confidence'           => $confidence,
			'symptoms_detected'    => $symptoms,
			'estimated_cost_range' => sanitize_text_field( wp_strip_all_tags( isset( $data['estimated_cost_range'] ) ? (string) $data['estimated_cost_range'] : '' ) ),
			'repair_time'          => sanitize_text_field( wp_strip_all_tags( isset( $data['repair_time'] ) ? (string) $data['repair_time'] : '' ) ),
			'technical_note'       => sanitize_textarea_field( wp_strip_all_tags( isset( $data['technical_note'] ) ? (string) $data['technical_note'] : '' ) ),
		);
	}
}

if ( ! function_exists( 'pixva_ai_handler_analyze' ) ) {
	/**
	 * گردش کامل تحلیل بومی: آپلود → انتظار → generate → تجزیه → پاک‌سازی.
	 *
	 * @param int   $attachment_id شناسه پیوست رسانه.
	 * @param array $context       زمینه (brand_label/model/symptom).
	 * @return array|WP_Error ساختار عیب‌یابی یا خطا.
	 */
	function pixva_ai_handler_analyze( $attachment_id, $context = array() ) {
		if ( ! pixva_ai_handler_configured() ) {
			return new WP_Error( 'pixva_ai_no_key', __( 'کلید API گوگل جمینای در پیشخوان تنظیم نشده است.', 'pixva' ) );
		}

		$path = (string) get_attached_file( (int) $attachment_id );
		if ( '' === $path || ! file_exists( $path ) ) {
			return new WP_Error( 'pixva_ai_media_missing', __( 'فایل رسانه روی سرور پیدا نشد.', 'pixva' ) );
		}

		$mime = (string) get_post_mime_type( (int) $attachment_id );
		if ( '' === $mime ) {
			$mime = 'application/octet-stream';
		}

		$uploaded = pixva_ai_handler_upload( $path, $mime, basename( $path ) );
		if ( is_wp_error( $uploaded ) ) {
			return $uploaded;
		}

		$ready = pixva_ai_handler_wait_file( $uploaded );
		if ( is_wp_error( $ready ) ) {
			pixva_ai_handler_delete_file( $uploaded['file_name'] );
			return $ready;
		}

		$generated = pixva_ai_handler_generate( $ready, $context );
		pixva_ai_handler_delete_file( $uploaded['file_name'] ); // پاک‌سازی فایل موقت سمت گوگل.

		if ( is_wp_error( $generated ) ) {
			return $generated;
		}

		$parsed = pixva_ai_handler_parse( $generated['raw'] );
		if ( ! is_array( $parsed ) ) {
			return new WP_Error( 'pixva_ai_parse', __( 'پاسخ مدل قابل تجزیه به JSON نبود.', 'pixva' ) );
		}

		$parsed['model'] = $generated['model'];
		$parsed['usage'] = $generated['usage'];
		return $parsed;
	}
}

if ( ! function_exists( 'pixva_ai_handler_test' ) ) {
	/**
	 * تست اتصال آنلاین کلید API (فهرست مدل‌ها).
	 *
	 * @param string $key کلید اختیاری (خالی = کلید ذخیره‌شده).
	 * @return array|WP_Error
	 */
	function pixva_ai_handler_test( $key = '' ) {
		$key = '' !== $key ? trim( (string) $key ) : pixva_ai_handler_key();
		if ( '' === $key ) {
			return new WP_Error( 'pixva_ai_no_key', __( 'کلید API تنظیم نشده است؛ ابتدا آن را ذخیره کنید.', 'pixva' ) );
		}

		$response = wp_remote_get(
			'https://generativelanguage.googleapis.com/v1beta/models',
			array(
				'timeout' => 20,
				'headers' => array( 'x-goog-api-key' => $key ),
			)
		);
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		$body = json_decode( (string) wp_remote_retrieve_body( $response ), true );
		if ( $code >= 200 && $code < 300 ) {
			$count = is_array( $body ) && isset( $body['models'] ) && is_array( $body['models'] ) ? count( $body['models'] ) : 0;
			return array(
				'ok'     => true,
				'models' => $count,
				'model'  => pixva_ai_handler_model(),
			);
		}

		$detail = is_array( $body ) && isset( $body['error']['message'] ) ? (string) $body['error']['message'] : '';
		return new WP_Error(
			'pixva_ai_test',
			sprintf(
				/* translators: 1: کد HTTP، 2: جزئیات */
				__( 'کلید نامعتبر یا خطای سرویس (HTTP %1$d). %2$s', 'pixva' ),
				$code,
				$detail
			)
		);
	}
}

/* ==========================================================================
   ۳-ب) مقاومت تولیدی: محدودیت روزانه، اعتبارسنجی شماره، مسیر آپلود و پاک‌سازی
   ========================================================================== */

if ( ! function_exists( 'pixva_ai_handler_daily_limited' ) ) {
	/**
	 * محدودیت ۳ درخواست عیب‌یابی در ۲۴ ساعت برای هر کاربر (IP + کوکی).
	 *
	 * برای جلوگیری از اتمام سهمیه Gemini و پر شدن هاست. هم کلید IP (transient)
	 * و هم کوکی مرورگر شمرده می‌شود؛ عبور از هرکدام درخواست را رد می‌کند.
	 *
	 * @return bool
	 */
	function pixva_ai_handler_daily_limited() {
		$max    = (int) apply_filters( 'pixva_ai_daily_max', 3 );
		$window = (int) apply_filters( 'pixva_ai_daily_window', DAY_IN_SECONDS );

		$ip      = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : 'unknown';
		$ip_key  = 'pixva_ai_daily_' . md5( $ip . wp_salt( 'nonce' ) );
		$ip_hit  = (int) get_transient( $ip_key );

		// کوکی سمت مرورگر (برای کاربرانی که IP مشترک/NAT دارند).
		$cookie_hit = isset( $_COOKIE['pixva_ai_daily'] ) ? (int) $_COOKIE['pixva_ai_daily'] : 0; // phpcs:ignore WordPressVIPMinimum.Variables.RestrictedVariables

		if ( $ip_hit >= $max || $cookie_hit >= $max ) {
			return true;
		}

		set_transient( $ip_key, $ip_hit + 1, $window );
		if ( ! headers_sent() ) {
			setcookie( 'pixva_ai_daily', (string) ( $cookie_hit + 1 ), time() + $window, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN );
		}

		return false;
	}
}

if ( ! function_exists( 'pixva_ai_handler_phone_required' ) ) {
	/**
	 * آیا شماره همراه معتبر برای ثبت عیب‌یابی الزامی است؟
	 *
	 * @return bool
	 */
	function pixva_ai_handler_phone_required() {
		return (bool) apply_filters( 'pixva_ai_phone_required', (bool) pixva_option( 'pixva_ai_require_phone', true ) );
	}
}

if ( ! function_exists( 'pixva_ai_upload_subdir' ) ) {
	/**
	 * زیرپوشه اختصاصی آپلود رسانه عیب‌یابی داخل uploads.
	 *
	 * @return string
	 */
	function pixva_ai_upload_subdir() {
		return (string) apply_filters( 'pixva_ai_upload_subdir', 'pixva-ai' );
	}
}

if ( ! function_exists( 'pixva_ai_upload_dir' ) ) {
	/**
	 * فیلتر مسیر آپلود: رسانه عیب‌یابی در uploads/pixva-ai/ (بدون زیرپوشه سال/ماه).
	 *
	 * @param array $uploads خروجی wp_upload_dir.
	 * @return array
	 */
	function pixva_ai_upload_dir( $uploads ) {
		$subdir = '/' . trim( pixva_ai_upload_subdir(), '/' );

		$uploads['subdir'] = $subdir;
		$uploads['path']   = untrailingslashit( $uploads['basedir'] ) . $subdir;
		$uploads['url']    = untrailingslashit( $uploads['baseurl'] ) . $subdir;

		return $uploads;
	}
}

if ( ! function_exists( 'pixva_schedule_media_cleanup' ) ) {
	/**
	 * زمان‌بندی کرون روزانه پاک‌سازی فایل‌های رسانه عیب‌یابی.
	 *
	 * @return void
	 */
	function pixva_schedule_media_cleanup() {
		if ( ! wp_next_scheduled( 'pixva_ai_media_cleanup_event' ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'pixva_ai_media_cleanup_event' );
		}
	}
}

if ( ! function_exists( 'pixva_ai_media_cleanup' ) ) {
	/**
	 * پاک‌سازی فایل‌های ویدیو/صدا/تصویر آپلودشده در uploads/pixva-ai/ که
	 * قدیمی‌تر از N روز (پیش‌فرض ۷) هستند. سوابق و نتایج JSON در پایگاه‌داده
	 * دست‌نخورده می‌مانند و فقط فایل‌های سنگین حذف می‌شوند تا هاست پر نشود.
	 *
	 * @return int تعداد فایل‌های حذف‌شده.
	 */
	function pixva_ai_media_cleanup() {
		$days = (int) apply_filters( 'pixva_ai_media_cleanup_days', 7 );
		$days = max( 1, $days );

		$upload = wp_upload_dir();
		$dir    = untrailingslashit( (string) $upload['basedir'] ) . '/' . trim( pixva_ai_upload_subdir(), '/' );
		if ( ! is_dir( $dir ) ) {
			return 0;
		}

		$exts   = apply_filters( 'pixva_ai_media_cleanup_exts', array( 'mp4', 'm4v', 'webm', 'ogv', 'mov', 'avi', 'mp3', 'm4a', 'wav', 'ogg', 'weba', 'amr', 'jpg', 'jpeg', 'png', 'webp' ) );
		$cutoff = time() - ( $days * DAY_IN_SECONDS );
		$count  = 0;

		$files = glob( $dir . '/*' );
		if ( ! is_array( $files ) ) {
			return 0;
		}

		foreach ( $files as $file ) {
			if ( is_dir( $file ) || ! is_file( $file ) ) {
				continue;
			}
			$ext = strtolower( pathinfo( $file, PATHINFO_EXTENSION ) );
			if ( ! in_array( $ext, (array) $exts, true ) ) {
				continue;
			}
			if ( filemtime( $file ) >= $cutoff ) {
				continue;
			}
			if ( @unlink( $file ) ) { // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
				$count++;
			}
		}

		if ( $count > 0 ) {
			update_option( 'pixva_ai_media_last_cleanup', array( 'time' => time(), 'deleted' => $count ) );
		}

		/**
		 * هوک پس از پاک‌سازی فایل‌های رسانه عیب‌یابی.
		 *
		 * @param int $count تعداد حذف‌شده.
		 * @param string $dir مسیر.
		 */
		do_action( 'pixva_ai_media_cleaned', $count, $dir );

		return $count;
	}
	add_action( 'pixva_ai_media_cleanup_event', 'pixva_ai_media_cleanup' );
}

if ( ! function_exists( 'pixva_ai_media_mark_purged' ) ) {
	/**
	 * نشانه‌گذاری پیوست‌هایی که فایل فیزیکی‌شان پاک‌سازی شده (برای نمایش در تاریخچه).
	 *
	 * @return void
	 */
	function pixva_ai_media_mark_purged() {
		$upload = wp_upload_dir();
		$dir    = untrailingslashit( (string) $upload['basedir'] ) . '/' . trim( pixva_ai_upload_subdir(), '/' );

		$query = new WP_Query(
			array(
				'post_type'      => 'attachment',
				'post_status'    => 'inherit',
				'posts_per_page' => 100,
				'fields'         => 'ids',
				'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					array(
						'key'     => '_pixva_ai_media',
						'compare' => 'EXISTS',
					),
				),
			)
		);

		foreach ( $query->posts as $attachment_id ) {
			$path = (string) get_attached_file( (int) $attachment_id );
			if ( '' !== $path && 0 === strpos( $path, $dir ) && ! file_exists( $path ) ) {
				update_post_meta( (int) $attachment_id, '_pixva_ai_media_purged', 1 );
			}
		}
	}
	add_action( 'pixva_ai_media_cleaned', 'pixva_ai_media_mark_purged' );
}

/* ==========================================================================
   ۴) اندپوینت بومی REST: POST wp-json/pixva/v1/ai-diagnose
   ========================================================================== */

if ( ! function_exists( 'pixva_rest_ai_diagnose' ) ) {
	/**
	 * هندلر REST عیب‌یابی هوشمند (بومی، بدون سرویس پایتون).
	 *
	 * @param WP_REST_Request $request درخواست.
	 * @return WP_REST_Response|WP_Error
	 */
	function pixva_rest_ai_diagnose( $request ) {
		// رفع محدودیت زمان/حافظه هاست پیش از پردازش آپلود و فراخوانی API.
		if ( function_exists( 'pixva_host_raise_limits' ) ) {
			pixva_host_raise_limits();
		}

		$files = isset( $_FILES['media'] ) ? $_FILES['media'] : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		if ( empty( $files ) ) {
			return new WP_Error( 'pixva_ai_media', __( 'ابتدا یک ویدیو یا صدای دستگاه را اضافه کنید.', 'pixva' ), array( 'status' => 400 ) );
		}

		// محدودیت ضداسپم: حداکثر ۳ درخواست در ۲۴ ساعت (IP + کوکی).
		if ( pixva_ai_handler_daily_limited() ) {
			return new WP_Error( 'pixva_ai_daily', __( 'سقف درخواست‌های عیب‌یابی روزانه (۳ در ۲۴ ساعت) پر شده است؛ برای حفظ سهمیه سرویس، فردا دوباره تلاش کنید.', 'pixva' ), array( 'status' => 429 ) );
		}

		// اعتبارسنجی شماره همراه ایران پیش از ذخیره فایل روی هاست.
		$phone_raw = (string) $request->get_param( 'phone' );
		$phone     = function_exists( 'pixva_normalize_mobile' ) ? pixva_normalize_mobile( $phone_raw ) : sanitize_text_field( $phone_raw );
		if ( pixva_ai_handler_phone_required() ) {
			$phone_ok = function_exists( 'pixva_is_valid_iranian_mobile' ) ? pixva_is_valid_iranian_mobile( $phone ) : (bool) preg_match( '/^09[0-9]{9}$/', $phone );
			if ( ! $phone_ok ) {
				return new WP_Error( 'pixva_ai_phone', __( 'شماره موبایل معتبر نیست؛ آن را با ۰۹ و ۱۱ رقم وارد کنید (مثلاً ۰۹۱۲۱۱۱۱۱۱۱).', 'pixva' ), array( 'status' => 400 ) );
			}
		}

		$attachment_id = pixva_ai_diagnose_store_media( $files );
		if ( is_wp_error( $attachment_id ) ) {
			return $attachment_id;
		}

		$brand       = sanitize_text_field( (string) $request->get_param( 'brand' ) );
		$brand_model = sanitize_text_field( (string) $request->get_param( 'brand_model' ) );
		$model       = sanitize_text_field( (string) $request->get_param( 'model' ) );
		if ( '' === $model && '' !== $brand_model ) {
			$model = $brand_model;
		}
		$notes   = sanitize_textarea_field( (string) $request->get_param( 'notes' ) );
		$symptom = sanitize_textarea_field( (string) $request->get_param( 'symptom' ) );
		if ( '' === $symptom && '' !== $notes ) {
			$symptom = $notes;
		}
		$brands      = function_exists( 'pixva_brand_catalog' ) ? pixva_brand_catalog() : array();
		$brand_label = isset( $brands[ $brand ]['fa'] ) ? $brands[ $brand ]['fa'] : ( '' !== $brand ? $brand : ( '' !== $brand_model ? $brand_model : __( 'نامشخص', 'pixva' ) ) );

		$post_type = post_type_exists( 'pixva_inbox' ) ? 'pixva_inbox' : 'post';
		$post_id   = wp_insert_post(
			array(
				'post_type'    => $post_type,
				'post_status'  => 'private',
				'post_title'   => sprintf(
					/* translators: 1: برند 2: شرح خرابی */
					__( 'عیب‌یابی هوشمند %1$s — %2$s', 'pixva' ),
					$brand_label,
					'' !== $symptom ? wp_trim_words( $symptom, 8 ) : __( 'بدون شرح', 'pixva' )
				),
				'post_content' => $symptom,
			),
			true
		);

		if ( is_wp_error( $post_id ) || ! $post_id ) {
			return new WP_Error( 'pixva_ai_store', __( 'ثبت درخواست ممکن نشد؛ دوباره تلاش کنید.', 'pixva' ), array( 'status' => 500 ) );
		}

		$ticket    = pixva_ai_diagnose_ticket( (int) $post_id );
		$ip        = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		$mime      = (string) get_post_mime_type( (int) $attachment_id );
		$kind      = 0 === strpos( $mime, 'audio' ) ? 'audio' : ( 0 === strpos( $mime, 'image' ) ? 'image' : 'video' );
		$media_url = (string) wp_get_attachment_url( (int) $attachment_id );

		update_post_meta( $post_id, '_pixva_ai_code', $ticket );
		update_post_meta( $post_id, '_pixva_ai_media', (int) $attachment_id );
		update_post_meta( $post_id, '_pixva_ai_brand', $brand );
		update_post_meta( $post_id, '_pixva_ai_brand_model', $brand_model );
		update_post_meta( $post_id, '_pixva_ai_model', $model );
		update_post_meta( $post_id, '_pixva_ai_symptom', $symptom );
		update_post_meta( $post_id, '_pixva_ai_phone', $phone );
		update_post_meta( $post_id, '_pixva_ai_ip', $ip );
		update_post_meta( $post_id, '_pixva_ai_kind', $kind );
		update_post_meta( $post_id, '_pixva_ai_status', 'new' );
		update_post_meta( $post_id, '_pixva_message_phone', $phone );

		$context = array(
			'brand_label' => $brand_label,
			'model'       => $model,
			'symptom'     => $symptom,
		);

		$state    = 'queued';
		$engine   = 'none';
		$message  = '';
		$analysis = array();

		if ( pixva_ai_handler_configured() ) {
			$diagnose = pixva_ai_handler_analyze( (int) $attachment_id, $context );
			if ( is_wp_error( $diagnose ) ) {
				$state   = 'error';
				$message = $diagnose->get_error_message();
			} else {
				$state    = 'done';
				$engine   = 'gemini';
				$analysis = $diagnose;

				update_post_meta( $post_id, '_pixva_ai_fault', $diagnose['fault_type'] );
				update_post_meta( $post_id, '_pixva_ai_confidence', (int) $diagnose['confidence'] );
				update_post_meta( $post_id, '_pixva_ai_symptoms', wp_json_encode( $diagnose['symptoms_detected'] ) );
				update_post_meta( $post_id, '_pixva_ai_cost', $diagnose['estimated_cost_range'] );
				update_post_meta( $post_id, '_pixva_ai_time', $diagnose['repair_time'] );
				update_post_meta( $post_id, '_pixva_ai_note', $diagnose['technical_note'] );
				update_post_meta( $post_id, '_pixva_ai_engine', 'gemini' );
				update_post_meta( $post_id, '_pixva_ai_model_used', $diagnose['model'] );

				if ( pixva_ai_handler_auto_draft() && '' !== $diagnose['fault_type'] ) {
					$draft = pixva_ai_handler_convert( (int) $post_id, true );
					if ( ! is_wp_error( $draft ) && ! empty( $draft['id'] ) ) {
						update_post_meta( (int) $draft['id'], '_pixva_order_ai_draft', 1 );
					}
				}
			}
		} else {
			$state   = 'no_key';
			$message = __( 'سامانه عیب‌یابی هوشمند هنوز در پیشخوان پیکربندی نشده است؛ درخواست شما در صف بررسی کارشناسان ثبت شد.', 'pixva' );
		}

		update_post_meta( $post_id, '_pixva_ai_state', $state );
		update_post_meta( $post_id, '_pixva_ai_engine', $engine );
		if ( '' !== $message ) {
			update_post_meta( $post_id, '_pixva_ai_error', $message );
		}

		$created = function_exists( 'pixva_fa_num' ) ? pixva_fa_num( wp_date( 'Y/m/d H:i' ) ) : wp_date( 'Y/m/d H:i' );

		$result = array(
			'success'              => true,
			'ticket'               => $ticket,
			'state'                => $state,
			'engine'               => $engine,
			'message'              => $message,
			'mediaUrl'             => $media_url,
			'createdAt'            => $created,
			'queueNote'            => __( 'درخواست شما ثبت شد؛ کارشناسان پیکسوا به‌زودی با شما تماس می‌گیرند.', 'pixva' ),
			// خروجی ساخت‌یافته جمینای (لایه ۱٫۸٫۰).
			'fault_type'           => isset( $analysis['fault_type'] ) ? $analysis['fault_type'] : '',
			'confidence'           => isset( $analysis['confidence'] ) ? (int) $analysis['confidence'] : 0,
			'symptoms_detected'    => isset( $analysis['symptoms_detected'] ) ? $analysis['symptoms_detected'] : array(),
			'estimated_cost_range' => isset( $analysis['estimated_cost_range'] ) ? $analysis['estimated_cost_range'] : '',
			'repair_time'          => isset( $analysis['repair_time'] ) ? $analysis['repair_time'] : '',
			'technical_note'       => isset( $analysis['technical_note'] ) ? $analysis['technical_note'] : '',
			// کلیدهای سازگار با نسخه پیشین (ویجت/JS قدیمی).
			'verdict'              => isset( $analysis['fault_type'] ) ? $analysis['fault_type'] : '',
			'part'                 => isset( $analysis['fault_type'] ) ? $analysis['fault_type'] : '',
			'analysis'             => $analysis,
		);

		/**
		 * فیلتر خروجی عیب‌یابی هوشمند بومی.
		 *
		 * @param array           $result      خروجی.
		 * @param int             $post_id     شناسه نوشته درخواست.
		 * @param int             $attachment  شناسه پیوست رسانه.
		 * @param WP_REST_Request $request     درخواست.
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
	 * ثبت مسیر REST عیب‌یابی هوشمند بومی.
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
					'brand'       => array(
						'required'          => false,
						'sanitize_callback' => 'sanitize_key',
					),
					'brand_model' => array(
						'required'          => false,
						'sanitize_callback' => 'sanitize_text_field',
					),
					'model'       => array(
						'required'          => false,
						'sanitize_callback' => 'sanitize_text_field',
					),
					'notes'       => array(
						'required'          => false,
						'sanitize_callback' => 'sanitize_textarea_field',
					),
					'symptom'     => array(
						'required'          => false,
						'sanitize_callback' => 'sanitize_textarea_field',
					),
					'phone'       => array(
						'required'          => false,
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);
	}
	add_action( 'rest_api_init', 'pixva_register_ai_diagnose_route' );
}

/* ==========================================================================
   ۵) تاریخچه عیب‌یابی + تبدیل به سفارش تعمیرات
   ========================================================================== */

if ( ! function_exists( 'pixva_ai_handler_status_label' ) ) {
	/**
	 * برچسب فارسی وضعیت سابقه.
	 *
	 * @param string $status وضعیت.
	 * @return string
	 */
	function pixva_ai_handler_status_label( $status ) {
		$map = array(
			'new'       => __( 'جدید', 'pixva' ),
			'converted' => __( 'تبدیل شده به سفارش', 'pixva' ),
			'archived'  => __( 'بایگانی', 'pixva' ),
		);
		$status = (string) $status;
		return isset( $map[ $status ] ) ? $map[ $status ] : $map['new'];
	}
}

if ( ! function_exists( 'pixva_ai_handler_logs' ) ) {
	/**
	 * واکشی سابقه‌های عیب‌یابی برای جدول پیشخوان.
	 *
	 * @param int $page     صفحه.
	 * @param int $per_page تعداد در صفحه.
	 * @return WP_Query
	 */
	function pixva_ai_handler_logs( $page = 1, $per_page = 25 ) {
		return new WP_Query(
			array(
				'post_type'      => post_type_exists( 'pixva_inbox' ) ? 'pixva_inbox' : 'post',
				'post_status'    => array( 'private', 'publish', 'draft' ),
				'posts_per_page' => max( 1, (int) $per_page ),
				'paged'          => max( 1, (int) $page ),
				'orderby'        => 'date',
				'order'          => 'DESC',
				'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					array(
						'key'     => '_pixva_ai_code',
						'compare' => 'EXISTS',
					),
				),
			)
		);
	}
}

if ( ! function_exists( 'pixva_ai_handler_convert' ) ) {
	/**
	 * تبدیل یک سابقه عیب‌یابی به پرونده سفارش تعمیرات (یک کلیک).
	 *
	 * @param int  $log_id    شناسه سابقه (pixva_inbox).
	 * @param bool $auto_draft آیا خودکار (پیش‌نویس) ساخته می‌شود.
	 * @return array{code:string, id:int}|WP_Error
	 */
	function pixva_ai_handler_convert( $log_id, $auto_draft = false ) {
		$log_id = (int) $log_id;
		$log    = get_post( $log_id );
		if ( ! $log instanceof WP_Post ) {
			return new WP_Error( 'pixva_ai_log_missing', __( 'سابقه عیب‌یابی پیدا نشد.', 'pixva' ) );
		}
		if ( ! function_exists( 'pixva_create_order' ) ) {
			return new WP_Error( 'pixva_ai_no_crm', __( 'موتور ثبت سفارش در دسترس نیست.', 'pixva' ) );
		}

		$existing = (int) get_post_meta( $log_id, '_pixva_ai_order', true );
		if ( $existing && 'private' === get_post_status( $existing ) ) {
			return array(
				'code' => (string) get_post_meta( $existing, '_pixva_order_code', true ),
				'id'   => $existing,
			);
		}

		$phone   = (string) get_post_meta( $log_id, '_pixva_ai_phone', true );
		$brand   = (string) get_post_meta( $log_id, '_pixva_ai_brand', true );
		$model   = (string) get_post_meta( $log_id, '_pixva_ai_model', true );
		$fault   = (string) get_post_meta( $log_id, '_pixva_ai_fault', true );
		$symptom = (string) get_post_meta( $log_id, '_pixva_ai_symptom', true );
		$cost    = (string) get_post_meta( $log_id, '_pixva_ai_cost', true );
		$time    = (string) get_post_meta( $log_id, '_pixva_ai_time', true );
		$note    = (string) get_post_meta( $log_id, '_pixva_ai_note', true );
		$symptoms = json_decode( (string) get_post_meta( $log_id, '_pixva_ai_symptoms', true ), true );

		$brands      = function_exists( 'pixva_brand_catalog' ) ? pixva_brand_catalog() : array();
		$brand_label = isset( $brands[ $brand ]['fa'] ) ? $brands[ $brand ]['fa'] : ( '' !== $brand ? $brand : __( 'نامشخص', 'pixva' ) );

		$problem = trim( $fault . ( '' !== $symptom ? ' — ' . $symptom : '' ) );
		if ( '' === $problem ) {
			$problem = __( 'عیب‌یابی هوشمند (بدون تشخیص قطعی)', 'pixva' );
		}

		$estimate = '' !== $cost ? $cost : __( 'بر اساس نرخ‌نامه کارگاه محاسبه می‌شود', 'pixva' );

		$result = pixva_create_order(
			array(
				'phone'    => $phone,
				'brand'    => $brand_label,
				'model'    => $model,
				'problem'  => $problem,
				'estimate' => $estimate,
			)
		);

		if ( empty( $result['id'] ) ) {
			return new WP_Error( 'pixva_ai_convert', __( 'ثبت سفارش ناموفق بود.', 'pixva' ) );
		}

		$order_id = (int) $result['id'];
		$ai_notes = trim(
			( '' !== $note ? $note . "\n" : '' )
			. ( is_array( $symptoms ) && $symptoms ? __( 'علائم تشخیصی: ', 'pixva' ) . implode( '، ', $symptoms ) . "\n" : '' )
			. ( '' !== $time ? __( 'زمان تقریبی تعمیر: ', 'pixva' ) . $time : '' )
		);

		update_post_meta( $order_id, '_pixva_order_source', $auto_draft ? 'ai-auto' : 'ai' );
		update_post_meta( $order_id, '_pixva_order_status', 'pending' );
		if ( '' !== $ai_notes ) {
			update_post_meta( $order_id, '_pixva_order_notes', sanitize_textarea_field( $ai_notes ) );
		}
		update_post_meta( $order_id, '_pixva_order_ai_log', $log_id );

		update_post_meta( $log_id, '_pixva_ai_status', 'converted' );
		update_post_meta( $log_id, '_pixva_ai_order', $order_id );

		/**
		 * هوک پس از تبدیل سابقه عیب‌یابی به سفارش.
		 *
		 * @param int   $order_id شناسه پرونده.
		 * @param int   $log_id   شناسه سابقه.
		 * @param bool  $auto_draft خودکار بودن.
		 */
		do_action( 'pixva_ai_log_converted', $order_id, $log_id, (bool) $auto_draft );

		return $result;
	}
}

if ( ! function_exists( 'pixva_ai_handler_set_status' ) ) {
	/**
	 * تغییر وضعیت یک سابقه (بایگانی/جدید).
	 *
	 * @param int    $log_id شناسه.
	 * @param string $status وضعیت.
	 * @return bool
	 */
	function pixva_ai_handler_set_status( $log_id, $status ) {
		$allowed = array( 'new', 'converted', 'archived' );
		$status  = in_array( $status, $allowed, true ) ? $status : 'new';
		return (bool) update_post_meta( (int) $log_id, '_pixva_ai_status', $status );
	}
}

/* ==========================================================================
   ۶) پیشخوان — منو، صفحه تنظیمات، تاریخچه و اکشن‌ها
   ========================================================================== */

if ( ! function_exists( 'pixva_ai_handler_admin_menu' ) ) {
	/**
	 * ثبت زیرمنوهای عیب‌یاب AI زیر منوی پیکسوا.
	 *
	 * @return void
	 */
	function pixva_ai_handler_admin_menu() {
		add_submenu_page(
			'pixva-control',
			esc_html__( 'تنظیمات عیب‌یاب هوش مصنوعی', 'pixva' ),
			esc_html__( 'عیب‌یاب AI (Gemini)', 'pixva' ),
			'manage_options',
			'pixva-ai-settings',
			'pixva_ai_handler_render_settings'
		);

		add_submenu_page(
			'pixva-control',
			esc_html__( 'تاریخچه عیب‌یابی AI', 'pixva' ),
			esc_html__( 'تاریخچه عیب‌یابی AI', 'pixva' ),
			'manage_options',
			'pixva-ai-logs',
			'pixva_ai_handler_render_logs'
		);
	}
	add_action( 'admin_menu', 'pixva_ai_handler_admin_menu', 20 );
}

if ( ! function_exists( 'pixva_ai_handler_admin_styles' ) ) {
	/**
	 * سبک سبک پیشخوان عیب‌یاب (فقط در صفحه‌های خودش).
	 *
	 * رنگ‌ها با rgb() نوشته می‌شوند تا با ابزار lint پوسته سازگار بماند.
	 *
	 * @param string $hook هوک صفحه.
	 * @return void
	 */
	function pixva_ai_handler_admin_styles( $hook ) {
		if ( false === strpos( (string) $hook, 'pixva-ai-settings' ) && false === strpos( (string) $hook, 'pixva-ai-logs' ) ) {
			return;
		}
		?>
		<style>
			.pixva-ai-wrap .form-table th{width:230px}
			.pixva-ai-keyrow{display:flex;gap:8px;align-items:center;flex-wrap:wrap}
			.pixva-ai-keyrow input[type=password],.pixva-ai-keyrow input[type=text]{min-width:340px;direction:ltr;font-family:monospace}
			.pixva-ai-test{margin-top:6px;font-weight:600}
			.pixva-ai-test.ok{color:rgb(0,120,60)}
			.pixva-ai-test.err{color:rgb(180,30,30)}
			.pixva-ai-badge{display:inline-block;padding:2px 9px;border-radius:999px;font-size:12px;font-weight:600}
			.pixva-ai-badge.new{background:rgb(225,240,255);color:rgb(20,70,140)}
			.pixva-ai-badge.converted{background:rgb(222,247,231);color:rgb(20,110,60)}
			.pixva-ai-badge.archived{background:rgb(238,238,238);color:rgb(90,90,90)}
			.pixva-ai-conf{display:inline-block;min-width:46px;text-align:center}
			.pixva-ai-bar{height:6px;border-radius:3px;background:rgb(225,225,225);overflow:hidden;margin-top:4px;max-width:90px}
			.pixva-ai-bar i{display:block;height:100%;background:rgb(40,140,90)}
			table.pixva-ai-logs td,table.pixva-ai-logs th{vertical-align:middle}
			.pixva-ai-media a{display:inline-block;margin-inline-end:6px}
			.pixva-ai-note{color:rgb(90,90,90);font-size:12px}
		</style>
		<?php
	}
	add_action( 'admin_head', 'pixva_ai_handler_admin_styles' );
}

if ( ! function_exists( 'pixva_ai_handler_render_settings' ) ) {
	/**
	 * رندر صفحه تنظیمات عیب‌یاب هوش مصنوعی (پنل بومی پیشخوان).
	 *
	 * @return void
	 */
	function pixva_ai_handler_render_settings() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'دسترسی مجاز نیست.', 'pixva' ) );
		}

		$has_key   = pixva_ai_handler_configured();
		$model     = pixva_ai_handler_model();
		$max_size  = pixva_ai_handler_max_size();
		$auto      = pixva_ai_handler_auto_draft();
		$models    = pixva_ai_handler_models();
		$sms       = function_exists( 'pixva_sms_handler_settings' ) ? pixva_sms_handler_settings() : array( 'enabled' => false, 'provider' => 'none', 'api_key' => '', 'sender' => '', 'pattern' => '', 'admin_number' => '' );
		$sms_providers = function_exists( 'pixva_sms_handler_providers' ) ? pixva_sms_handler_providers() : array( 'none' => '—' );
		$nonce     = wp_create_nonce( 'pixva_ai_test' );
		$updated   = isset( $_GET['settings-updated'] ) && 'true' === sanitize_key( wp_unslash( $_GET['settings-updated'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		?>
		<div class="wrap pixva-ai-wrap">
			<h1><?php esc_html_e( 'تنظیمات عیب‌یاب هوش مصنوعی (Gemini)', 'pixva' ); ?></h1>

			<?php if ( $updated ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'تنظیمات عیب‌یاب هوش مصنوعی ذخیره شد.', 'pixva' ); ?></p></div>
			<?php endif; ?>

			<?php if ( ! $has_key ) : ?>
				<div class="notice notice-warning"><p><?php esc_html_e( 'کلید API گوگل جمینای تنظیم نشده است؛ تا زمانی که آن را وارد نکنید، عیب‌یابی فرانت‌اند فقط در صف بررسی کارشناسان ثبت می‌شود و تحلیل خودکار انجام نمی‌گیرد.', 'pixva' ); ?></p></div>
			<?php endif; ?>

			<p class="pixva-ai-note"><?php esc_html_e( 'این کلید با چت‌بات هوشمند پیکسوا مشترک است؛ یک کلید برای همه بخش‌های هوش مصنوعی قالب. کلید فقط سمت سرور نگهداری می‌شود و هرگز به مرورگر بازدیدکننده ارسال نمی‌گردد.', 'pixva' ); ?></p>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="pixva_ai_save_settings" />
				<?php wp_nonce_field( 'pixva_ai_save_settings', 'pixva_ai_save_nonce' ); ?>

				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="pixva_gemini_api_key"><?php esc_html_e( 'کلید API گوگل جمینای', 'pixva' ); ?></label></th>
						<td>
							<div class="pixva-ai-keyrow">
								<input type="password" name="pixva_gemini_api_key" id="pixva_gemini_api_key" value="" autocomplete="new-password"
									placeholder="<?php echo $has_key ? esc_attr__( '•••••••• (کلید ذخیره شده — برای تغییر، مقدار تازه وارد کنید)', 'pixva' ) : esc_attr__( 'AIza…', 'pixva' ); ?>" />
								<button type="button" class="button" id="pixva-ai-reveal"><?php esc_html_e( 'نمایش کلید', 'pixva' ); ?></button>
								<button type="button" class="button" id="pixva-ai-test" data-nonce="<?php echo esc_attr( $nonce ); ?>"><?php esc_html_e( 'تست اتصال', 'pixva' ); ?></button>
							</div>
							<label class="pixva-ai-note"><input type="checkbox" name="pixva_gemini_api_key_clear" value="1" /> <?php esc_html_e( 'حذف کلید ذخیره‌شده', 'pixva' ); ?></label>
							<div id="pixva-ai-test-result" class="pixva-ai-test" role="status"></div>
							<p class="description"><?php esc_html_e( 'از Google AI Studio کلید بسازید. برای تست اتصال ابتدا کلید را ذخیره کنید.', 'pixva' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="pixva_gemini_model"><?php esc_html_e( 'مدل جمینای', 'pixva' ); ?></label></th>
						<td>
							<select name="pixva_gemini_model" id="pixva_gemini_model">
								<?php foreach ( $models as $value => $label ) : ?>
									<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $model, $value ); ?>><?php echo esc_html( $label ); ?></option>
								<?php endforeach; ?>
							</select>
							<p class="description"><?php esc_html_e( 'پیش‌فرض: gemini-1.5-flash (سریع و رایگان).', 'pixva' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="pixva_ai_max_file_size"><?php esc_html_e( 'حداکثر حجم فایل آپلودی (مگابایت)', 'pixva' ); ?></label></th>
						<td>
							<input type="number" min="1" max="512" step="1" name="pixva_ai_max_file_size" id="pixva_ai_max_file_size" value="<?php echo esc_attr( (string) $max_size ); ?>" class="small-text" />
							<p class="description"><?php esc_html_e( 'پیش‌فرض ۵۰ مگابایت. این مقدار نمی‌تواند از سقف upload_max_filesize سرور بیشتر اثر کند.', 'pixva' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'ایجاد خودکار پیش‌نویس سفارش', 'pixva' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="pixva_ai_auto_create_draft" id="pixva_ai_auto_create_draft" value="1" <?php checked( $auto, true ); ?> />
								<?php esc_html_e( 'به محض تشخیص خطا توسط هوش مصنوعی، یک پرونده سفارش (pixva_orders) به‌صورت پیش‌نویس ساخته شود.', 'pixva' ); ?>
							</label>
						</td>
					</tr>
				</table>

				<h2><?php esc_html_e( 'سامانه پیامک خودکار', 'pixva' ); ?></h2>
				<p class="pixva-ai-note"><?php esc_html_e( 'ارسال خودکار کد پیگیری به مشتری پس از آنالیز، پیامک وضعیت هنگام تبدیل به سفارش و هشدار عیب‌یابی جدید به مدیر. در نبود تنظیمات، از پیکربندی پیامک مرکز کنترل استفاده می‌شود.', 'pixva' ); ?></p>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'فعال‌سازی پیامک', 'pixva' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="pixva_sms_enabled" value="1" <?php checked( ! empty( $sms['enabled'] ), true ); ?> />
								<?php esc_html_e( 'ارسال پیامک‌های خودکار عیب‌یابی فعال باشد.', 'pixva' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="pixva_sms_provider"><?php esc_html_e( 'پنل پیامک', 'pixva' ); ?></label></th>
						<td>
							<select name="pixva_sms_provider" id="pixva_sms_provider">
								<?php foreach ( $sms_providers as $value => $label ) : ?>
									<option value="<?php echo esc_attr( $value ); ?>" <?php selected( (string) $sms['provider'], $value ); ?>><?php echo esc_html( $label ); ?></option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="pixva_sms_api_key"><?php esc_html_e( 'کلید API پیامک', 'pixva' ); ?></label></th>
						<td>
							<input type="password" name="pixva_sms_api_key" id="pixva_sms_api_key" value="" autocomplete="new-password" dir="ltr" class="regular-text"
								placeholder="<?php echo '' !== $sms['api_key'] ? esc_attr__( '•••••• (ذخیره شده — برای تغییر وارد کنید)', 'pixva' ) : ''; ?>" />
							<label class="pixva-ai-note"><input type="checkbox" name="pixva_sms_api_key_clear" value="1" /> <?php esc_html_e( 'حذف کلید ذخیره‌شده', 'pixva' ); ?></label>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="pixva_sms_sender_line"><?php esc_html_e( 'شماره/خط ارسال‌کننده', 'pixva' ); ?></label></th>
						<td><input type="text" name="pixva_sms_sender_line" id="pixva_sms_sender_line" value="<?php echo esc_attr( (string) $sms['sender'] ); ?>" dir="ltr" class="regular-text" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="pixva_sms_pattern"><?php esc_html_e( 'کد الگو (Pattern)', 'pixva' ); ?></label></th>
						<td>
							<input type="text" name="pixva_sms_pattern" id="pixva_sms_pattern" value="<?php echo esc_attr( (string) $sms['pattern'] ); ?>" dir="ltr" class="regular-text" />
							<p class="description"><?php esc_html_e( 'در صورت داشتن الگوی تأییدشده در پنل (کدنویسی/verify) پر کنید؛ در غیر این صورت متن آزاد ارسال می‌شود.', 'pixva' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="pixva_sms_admin_number"><?php esc_html_e( 'شماره مدیر/تعمیرکار (هشدار)', 'pixva' ); ?></label></th>
						<td><input type="text" name="pixva_sms_admin_number" id="pixva_sms_admin_number" value="<?php echo esc_attr( (string) $sms['admin_number'] ); ?>" dir="ltr" class="regular-text" placeholder="09xxxxxxxxx" /></td>
					</tr>
				</table>

				<?php submit_button( __( 'ذخیره تنظیمات', 'pixva' ) ); ?>
			</form>

			<script>
			(function () {
				var keyInput = document.getElementById('pixva_gemini_api_key');
				var reveal = document.getElementById('pixva-ai-reveal');
				if (reveal && keyInput) {
					reveal.addEventListener('click', function () {
						var showing = 'text' === keyInput.type;
						keyInput.type = showing ? 'password' : 'text';
						reveal.textContent = showing ? '<?php echo esc_js( __( 'نمایش کلید', 'pixva' ) ); ?>' : '<?php echo esc_js( __( 'پنهان کردن', 'pixva' ) ); ?>';
					});
				}
				var btn = document.getElementById('pixva-ai-test');
				if (btn) {
					btn.addEventListener('click', function () {
						var out = document.getElementById('pixva-ai-test-result');
						btn.disabled = true;
						out.className = 'pixva-ai-test';
						out.textContent = '<?php echo esc_js( __( 'در حال تست اتصال…', 'pixva' ) ); ?>';
						var fd = new FormData();
						fd.append('action', 'pixva_ai_test');
						fd.append('_ajax_nonce', btn.getAttribute('data-nonce'));
						window.fetch(ajaxurl, { method: 'POST', body: fd, credentials: 'same-origin' })
							.then(function (r) { return r.json(); })
							.then(function (res) {
								btn.disabled = false;
								if (res && res.success) {
									out.className = 'pixva-ai-test ok';
									out.textContent = (res.data && res.data.message) ? res.data.message : '<?php echo esc_js( __( 'اتصال برقرار است.', 'pixva' ) ); ?>';
								} else {
									out.className = 'pixva-ai-test err';
									out.textContent = (res && res.data && res.data.message) ? res.data.message : '<?php echo esc_js( __( 'تست اتصال ناموفق بود.', 'pixva' ) ); ?>';
								}
							})
							.catch(function () {
								btn.disabled = false;
								out.className = 'pixva-ai-test err';
								out.textContent = '<?php echo esc_js( __( 'خطا در ارتباط با سرور.', 'pixva' ) ); ?>';
							});
					});
				}
			}());
			</script>
		</div>
		<?php
	}
}

if ( ! function_exists( 'pixva_ai_handler_save_settings' ) ) {
	/**
	 * ذخیره تنظیمات عیب‌یاب (admin-post) با nonce و پاک‌سازی کامل.
	 *
	 * @return void
	 */
	function pixva_ai_handler_save_settings() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'دسترسی مجاز نیست.', 'pixva' ) );
		}
		check_admin_referer( 'pixva_ai_save_settings', 'pixva_ai_save_nonce' );

		if ( ! empty( $_POST['pixva_gemini_api_key_clear'] ) ) {
			set_theme_mod( 'pixva_gemini_api_key', '' );
		} elseif ( isset( $_POST['pixva_gemini_api_key'] ) && '' !== trim( (string) wp_unslash( $_POST['pixva_gemini_api_key'] ) ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
			set_theme_mod( 'pixva_gemini_api_key', sanitize_text_field( wp_unslash( $_POST['pixva_gemini_api_key'] ) ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		}

		if ( isset( $_POST['pixva_gemini_model'] ) ) {
			$model = sanitize_key( wp_unslash( $_POST['pixva_gemini_model'] ) );
			if ( array_key_exists( $model, pixva_ai_handler_models() ) ) {
				set_theme_mod( 'pixva_gemini_model', $model );
			}
		}

		if ( isset( $_POST['pixva_ai_max_file_size'] ) ) {
			$max = max( 1, min( 512, (int) $_POST['pixva_ai_max_file_size'] ) );
			set_theme_mod( 'pixva_ai_max_file_size', $max );
		}

		set_theme_mod( 'pixva_ai_auto_create_draft', empty( $_POST['pixva_ai_auto_create_draft'] ) ? false : true );

		// سامانه پیامک.
		set_theme_mod( 'pixva_sms_enabled', empty( $_POST['pixva_sms_enabled'] ) ? false : true );

		if ( isset( $_POST['pixva_sms_provider'] ) ) {
			$provider = sanitize_key( wp_unslash( $_POST['pixva_sms_provider'] ) );
			if ( function_exists( 'pixva_sms_handler_providers' ) && array_key_exists( $provider, pixva_sms_handler_providers() ) ) {
				set_theme_mod( 'pixva_sms_provider', $provider );
			}
		}
		if ( ! empty( $_POST['pixva_sms_api_key_clear'] ) ) {
			set_theme_mod( 'pixva_sms_api_key', '' );
		} elseif ( isset( $_POST['pixva_sms_api_key'] ) && '' !== trim( (string) wp_unslash( $_POST['pixva_sms_api_key'] ) ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
			set_theme_mod( 'pixva_sms_api_key', sanitize_text_field( wp_unslash( $_POST['pixva_sms_api_key'] ) ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		}
		if ( isset( $_POST['pixva_sms_sender_line'] ) ) {
			set_theme_mod( 'pixva_sms_sender_line', sanitize_text_field( wp_unslash( $_POST['pixva_sms_sender_line'] ) ) );
		}
		if ( isset( $_POST['pixva_sms_pattern'] ) ) {
			set_theme_mod( 'pixva_sms_pattern', sanitize_text_field( wp_unslash( $_POST['pixva_sms_pattern'] ) ) );
		}
		if ( isset( $_POST['pixva_sms_admin_number'] ) ) {
			set_theme_mod( 'pixva_sms_admin_number', sanitize_text_field( wp_unslash( $_POST['pixva_sms_admin_number'] ) ) );
		}

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'             => 'pixva-ai-settings',
					'settings-updated' => 'true',
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}
	add_action( 'admin_post_pixva_ai_save_settings', 'pixva_ai_handler_save_settings' );
}

if ( ! function_exists( 'pixva_ai_handler_ajax_test' ) ) {
	/**
	 * AJAX تست اتصال کلید جمینای.
	 *
	 * @return void
	 */
	function pixva_ai_handler_ajax_test() {
		check_ajax_referer( 'pixva_ai_test' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'دسترسی مجاز نیست.', 'pixva' ) ) );
		}

		$test = pixva_ai_handler_test();
		if ( is_wp_error( $test ) ) {
			wp_send_json_error( array( 'message' => $test->get_error_message() ) );
		}

		wp_send_json_success(
			array(
				'message' => sprintf(
					/* translators: 1: تعداد مدل‌ها، 2: مدل فعال */
					__( 'اتصال برقرار است — %1$d مدل در دسترس؛ مدل فعال: %2$s', 'pixva' ),
					(int) $test['models'],
					(string) $test['model']
				),
			)
		);
	}
	add_action( 'wp_ajax_pixva_ai_test', 'pixva_ai_handler_ajax_test' );
}

if ( ! function_exists( 'pixva_ai_handler_convert_action' ) ) {
	/**
	 * اکشن تبدیل سابقه به سفارش (admin-post) با nonce.
	 *
	 * @return void
	 */
	function pixva_ai_handler_convert_action() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'دسترسی مجاز نیست.', 'pixva' ) );
		}
		check_admin_referer( 'pixva_ai_convert' );

		$log_id = isset( $_GET['log'] ) ? absint( $_GET['log'] ) : 0;
		$result = pixva_ai_handler_convert( $log_id );

		$args = array( 'page' => 'pixva-ai-logs' );
		if ( is_wp_error( $result ) ) {
			$args['ai_error'] = rawurlencode( $result->get_error_message() );
		} else {
			$args['converted'] = rawurlencode( (string) $result['code'] );
		}

		wp_safe_redirect( add_query_arg( $args, admin_url( 'admin.php' ) ) );
		exit;
	}
	add_action( 'admin_post_pixva_ai_convert', 'pixva_ai_handler_convert_action' );
}

if ( ! function_exists( 'pixva_ai_handler_archive_action' ) ) {
	/**
	 * اکشن بایگانی/احیای سابقه (admin-post) با nonce.
	 *
	 * @return void
	 */
	function pixva_ai_handler_archive_action() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'دسترسی مجاز نیست.', 'pixva' ) );
		}
		check_admin_referer( 'pixva_ai_archive' );

		$log_id = isset( $_GET['log'] ) ? absint( $_GET['log'] ) : 0;
		$status = isset( $_GET['to'] ) && 'new' === sanitize_key( wp_unslash( $_GET['to'] ) ) ? 'new' : 'archived';
		pixva_ai_handler_set_status( $log_id, $status );

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'    => 'pixva-ai-logs',
					'updated' => 'status',
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}
	add_action( 'admin_post_pixva_ai_archive', 'pixva_ai_handler_archive_action' );
}

if ( ! function_exists( 'pixva_ai_handler_render_logs' ) ) {
	/**
	 * رندر جدول تاریخچه عیب‌یابی AI.
	 *
	 * @return void
	 */
	function pixva_ai_handler_render_logs() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'دسترسی مجاز نیست.', 'pixva' ) );
		}

		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$page_num  = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;
		$converted = isset( $_GET['converted'] ) ? sanitize_text_field( rawurldecode( wp_unslash( $_GET['converted'] ) ) ) : '';
		$ai_error  = isset( $_GET['ai_error'] ) ? sanitize_text_field( rawurldecode( wp_unslash( $_GET['ai_error'] ) ) ) : '';
		$updated   = isset( $_GET['updated'] ) ? sanitize_key( wp_unslash( $_GET['updated'] ) ) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		$query = pixva_ai_handler_logs( $page_num, 25 );
		?>
		<div class="wrap pixva-ai-wrap">
			<h1 class="wp-heading-inline"><?php esc_html_e( 'تاریخچه عیب‌یابی AI', 'pixva' ); ?></h1>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=pixva-ai-settings' ) ); ?>" class="page-title-action"><?php esc_html_e( 'تنظیمات عیب‌یاب', 'pixva' ); ?></a>
			<hr class="wp-header-end" />

			<?php if ( ! pixva_ai_handler_configured() ) : ?>
				<div class="notice notice-warning"><p><?php esc_html_e( 'کلید API جمینای تنظیم نشده؛ تحلیل خودکار انجام نمی‌شود و درخواست‌ها فقط در همین فهرست ثبت می‌مانند.', 'pixva' ); ?></p></div>
			<?php endif; ?>

			<?php if ( '' !== $converted ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php echo esc_html( sprintf( __( 'سفارش تعمیرات با کد %s از روی این عیب‌یابی ساخته شد.', 'pixva' ), $converted ) ); ?></p></div>
			<?php endif; ?>
			<?php if ( '' !== $ai_error ) : ?>
				<div class="notice notice-error is-dismissible"><p><?php echo esc_html( $ai_error ); ?></p></div>
			<?php endif; ?>
			<?php if ( 'status' === $updated ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'وضعیت سابقه به‌روزرسانی شد.', 'pixva' ); ?></p></div>
			<?php endif; ?>

			<?php if ( $query->have_posts() ) : ?>
				<table class="widefat striped pixva-ai-logs">
					<thead>
						<tr>
							<th><?php esc_html_e( 'تاریخ و زمان', 'pixva' ); ?></th>
							<th><?php esc_html_e( 'فایل مشتری', 'pixva' ); ?></th>
							<th><?php esc_html_e( 'ایراد تشخیصی', 'pixva' ); ?></th>
							<th><?php esc_html_e( 'اطمینان', 'pixva' ); ?></th>
							<th><?php esc_html_e( 'وضعیت', 'pixva' ); ?></th>
							<th><?php esc_html_e( 'عملیات', 'pixva' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php
						while ( $query->have_posts() ) :
							$query->the_post();
							$log_id     = get_the_ID();
							$media_id   = (int) get_post_meta( $log_id, '_pixva_ai_media', true );
							$media_url  = $media_id ? (string) wp_get_attachment_url( $media_id ) : '';
							$kind       = (string) get_post_meta( $log_id, '_pixva_ai_kind', true );
							$fault      = (string) get_post_meta( $log_id, '_pixva_ai_fault', true );
							$confidence = (int) get_post_meta( $log_id, '_pixva_ai_confidence', true );
							$cost       = (string) get_post_meta( $log_id, '_pixva_ai_cost', true );
							$time       = (string) get_post_meta( $log_id, '_pixva_ai_time', true );
							$note       = (string) get_post_meta( $log_id, '_pixva_ai_note', true );
							$status     = (string) get_post_meta( $log_id, '_pixva_ai_status', true );
							$status     = '' === $status ? 'new' : $status;
							$state      = (string) get_post_meta( $log_id, '_pixva_ai_state', true );
							$order_id   = (int) get_post_meta( $log_id, '_pixva_ai_order', true );
							$order_code = $order_id ? (string) get_post_meta( $order_id, '_pixva_order_code', true ) : '';
							$convert_url = wp_nonce_url( add_query_arg( array( 'action' => 'pixva_ai_convert', 'log' => $log_id ), admin_url( 'admin-post.php' ) ), 'pixva_ai_convert' );
							$archive_url = wp_nonce_url( add_query_arg( array( 'action' => 'pixva_ai_archive', 'log' => $log_id, 'to' => 'archived' === $status ? 'new' : 'archived' ), admin_url( 'admin-post.php' ) ), 'pixva_ai_archive' );
							$date_label = function_exists( 'pixva_fa_num' ) ? pixva_fa_num( get_the_date( 'Y/m/d H:i' ) ) : get_the_date( 'Y/m/d H:i' );
							?>
							<tr>
								<td><?php echo esc_html( $date_label ); ?></td>
								<td class="pixva-ai-media">
									<?php if ( '' !== $media_url ) : ?>
										<?php if ( 'audio' === $kind ) : ?>
											<audio controls preload="none" src="<?php echo esc_url( $media_url ); ?>" style="max-width:220px"></audio>
										<?php elseif ( 'image' === $kind ) : ?>
											<a href="<?php echo esc_url( $media_url ); ?>" target="_blank" rel="noopener"><img src="<?php echo esc_url( $media_url ); ?>" alt="" style="max-width:120px;height:auto" /></a>
										<?php else : ?>
											<video controls preload="none" src="<?php echo esc_url( $media_url ); ?>" style="max-width:220px"></video>
										<?php endif; ?>
										<br /><a href="<?php echo esc_url( $media_url ); ?>" download><?php esc_html_e( 'دانلود فایل', 'pixva' ); ?></a>
									<?php else : ?>
										<span class="pixva-ai-note"><?php esc_html_e( 'بدون فایل', 'pixva' ); ?></span>
									<?php endif; ?>
								</td>
								<td>
									<?php if ( '' !== $fault ) : ?>
										<strong><?php echo esc_html( $fault ); ?></strong>
									<?php elseif ( 'error' === $state || 'no_key' === $state ) : ?>
										<span class="pixva-ai-note"><?php echo esc_html( (string) get_post_meta( $log_id, '_pixva_ai_error', true ) ?: __( 'تحلیل خودکار انجام نشد', 'pixva' ) ); ?></span>
									<?php else : ?>
										<span class="pixva-ai-note"><?php esc_html_e( 'در صف تحلیل', 'pixva' ); ?></span>
									<?php endif; ?>
									<?php if ( '' !== $cost ) : ?><br /><span class="pixva-ai-note"><?php echo esc_html( $cost ); ?></span><?php endif; ?>
									<?php if ( '' !== $time ) : ?><br /><span class="pixva-ai-note"><?php echo esc_html( $time ); ?></span><?php endif; ?>
									<?php if ( '' !== $note ) : ?><br /><span class="pixva-ai-note"><?php echo esc_html( wp_trim_words( $note, 18 ) ); ?></span><?php endif; ?>
								</td>
								<td>
									<?php if ( $confidence > 0 ) : ?>
										<span class="pixva-ai-conf"><?php echo esc_html( function_exists( 'pixva_fa_num' ) ? pixva_fa_num( $confidence ) . '٪' : $confidence . '%' ); ?></span>
										<span class="pixva-ai-bar"><i style="width:<?php echo esc_attr( (string) min( 100, $confidence ) ); ?>%"></i></span>
									<?php else : ?>
										<span class="pixva-ai-note">—</span>
									<?php endif; ?>
								</td>
								<td><span class="pixva-ai-badge <?php echo esc_attr( $status ); ?>"><?php echo esc_html( pixva_ai_handler_status_label( $status ) ); ?></span></td>
								<td>
									<?php if ( 'converted' === $status && $order_id ) : ?>
										<a class="button button-small" href="<?php echo esc_url( get_edit_post_link( $order_id ) ); ?>"><?php esc_html_e( 'مشاهده سفارش', 'pixva' ); ?> <?php echo esc_html( $order_code ); ?></a>
									<?php else : ?>
										<a class="button button-primary button-small" href="<?php echo esc_url( $convert_url ); ?>"><?php esc_html_e( 'تبدیل به سفارش تعمیرات', 'pixva' ); ?></a>
									<?php endif; ?>
									<a class="button button-small" href="<?php echo esc_url( $archive_url ); ?>"><?php echo 'archived' === $status ? esc_html__( 'احیا', 'pixva' ) : esc_html__( 'بایگانی', 'pixva' ); ?></a>
									<a class="button button-small" href="<?php echo esc_url( get_edit_post_link( $log_id ) ); ?>"><?php esc_html_e( 'جزئیات', 'pixva' ); ?></a>
								</td>
							</tr>
						<?php endwhile; ?>
					</tbody>
				</table>

				<?php
				$total_pages = (int) $query->max_num_pages;
				if ( $total_pages > 1 ) :
					$base = add_query_arg( array( 'page' => 'pixva-ai-logs', 'paged' => '%#%' ), admin_url( 'admin.php' ) );
					echo '<div class="tablenav"><div class="tablenav-pages">' . wp_kses_post( paginate_links( array( 'base' => $base, 'total' => $total_pages, 'current' => $page_num, 'prev_text' => '«', 'next_text' => '»' ) ) ) . '</div></div>';
				endif;
				wp_reset_postdata();
				?>
			<?php else : ?>
				<p><?php esc_html_e( 'هنوز عیب‌یابی هوشمندی ثبت نشده است. به محض ارسال ویدیو یا صدا از فرانت‌اند، نتایج اینجا با امکان تبدیل به سفارش نمایش داده می‌شود.', 'pixva' ); ?></p>
			<?php endif; ?>
		</div>
		<?php
	}
}

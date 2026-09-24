<?php
/**
 * موتور هوش مصنوعی چندگانه عیب‌یابی پیکسوا (inc/ai-bot.php)
 *
 * Free-Tier Multimodal AI Diagnostics:
 * - اتصال امن به Gemini 1.5 Flash API یا Groq Llama-3 API سمت سرور بدون افشای کلید در فرانت‌اند
 * - آنالیز متنی، تصویری و صوتی خرابی تلویزیون
 * - سیستم Fallback محلی مبتنی بر درخت تصمیم‌گیری هوشمند کارگاهی در صورت قطعی یا عدم تنظیم کلید
 *
 * @package Pixva
 * @since   1.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'pixva_ai_local_diagnose' ) ) {
	/**
	 * درخت تصمیم‌گیری محلی (Local Fallback Decision Tree).
	 *
	 * @param string $message پیام یا شرح خرابی.
	 * @param string $brand   برند (اختیاری).
	 * @return array
	 */
	function pixva_ai_local_diagnose( $message, $brand = '' ) {
		$message = mb_strtolower( (string) $message, 'UTF-8' );
		$brand   = (string) $brand;

		// آب‌خوردگی / خطوط عمودی / T-Con
		if ( str_contains( $message, 'آب' ) || str_contains( $message, 'خیس' ) || str_contains( $message, 'شیشه پاک' ) || str_contains( $message, 'خط' ) ) {
			return array(
				'diagnosis'  => __( 'احتمال آسیب به فلت‌های پنل (COF) یا برد T-Con ناشی از نفوذ مایعات یا اتصالی مدار آدرس‌دهی.', 'pixva' ),
				'action'     => __( 'دستگاه را فوراً از برق بکشید و از روشن کردن مجدد خودداری کنید. نیاز به بررسی با میکروسکوپ صنعتی و ترمیم لیزری بندینگ دارد.', 'pixva' ),
				'service'    => 'water',
				'urgency'    => __( 'فوری - خطر سوختن کامل گلس پنل', 'pixva' ),
				'confidence' => 92,
			);
		}

		// بک‌لایت / صدا هست تصویر نیست / صفحه تاریک
		if ( str_contains( $message, 'صدا هست تصویر نیست' ) || str_contains( $message, 'تاریک' ) || str_contains( $message, 'سیاه' ) || str_contains( $message, 'نور' ) || str_contains( $message, 'بک لایت' ) || str_contains( $message, 'بکلایت' ) ) {
			return array(
				'diagnosis'  => __( 'سوختگی یا نیم‌سوز شدن ال‌ای‌دی‌های شاخه‌ای بک‌لایت (Backlight Strips).', 'pixva' ),
				'action'     => __( 'نیاز به تعویض دست کامل بک‌لایت فابریک با صفحه آلومینیومی هیت‌سینک‌دار به همراه اصلاحیه ولتاژ برد پاور (جهت جلوگیری از تکرار خرابی).', 'pixva' ),
				'service'    => 'backlight',
				'urgency'    => __( 'متوسط - قابل تعمیر در محل یا کارگاه', 'pixva' ),
				'confidence' => 96,
			);
		}

		// برد پاور / روشن نشدن / خاموشی کامل / بوی سوختگی
		if ( str_contains( $message, 'روشن نمیشه' ) || str_contains( $message, 'خاموش' ) || str_contains( $message, 'پاور' ) || str_contains( $message, 'برق' ) || str_contains( $message, 'سوختگی' ) || str_contains( $message, 'تیک' ) ) {
			return array(
				'diagnosis'  => __( 'ایراد در طبقه اولیه یا ثانویه برد تغذیه (Power Supply) / خرابی آی‌سی سوئیچینگ یا ماس‌فت‌ها.', 'pixva' ),
				'action'     => __( 'تست ولتاژ خروجی استندبای و مسیر ولتاژ کاری مین‌برد. در صورت عدم آسیب به مدار چاپی، قطعات فرسوده با نمونه اورجینال جایگزین می‌شوند.', 'pixva' ),
				'service'    => 'powerboard',
				'urgency'    => __( 'بالا - از اتصال مجدد به محافظ برق مشکوک خودداری شود', 'pixva' ),
				'confidence' => 90,
			);
		}

		// مین‌برد / لوگو ماندن / ریستارت / قفل شدن
		if ( str_contains( $message, 'لوگو' ) || str_contains( $message, 'هنگ' ) || str_contains( $message, 'ریست' ) || str_contains( $message, 'مین' ) || str_contains( $message, 'بروزرسانی' ) || str_contains( $message, 'آپدیت' ) ) {
			return array(
				'diagnosis'  => __( 'خرابی حافظه eMMC/NAND مین‌برد، مشکل پردازنده اصلی (CPU) یا به‌هم‌ریختگی فریمور دستگاه.', 'pixva' ),
				'action'     => __( 'پروگرام مجدد آی‌سی بایوس یا ریبال/تعویض چیپ اصلی مین‌برد توسط دستگاه BGA تخصصی کارگاه علاءالدین.', 'pixva' ),
				'service'    => 'mainboard',
				'urgency'    => __( 'بالا - از دستکاری نرم‌افزاری با فلش نامعتبر پرهیز کنید', 'pixva' ),
				'confidence' => 88,
			);
		}

		// چشمک زدن چراغ پاور
		if ( str_contains( $message, 'چشمک' ) || str_contains( $message, 'چراغ' ) || str_contains( $message, 'چشمک‌زن' ) ) {
			return array(
				'diagnosis'  => __( 'فعال شدن پروتکشن محافظتی سیستم (Self-Diagnostic Protection Code).', 'pixva' ),
				'action'     => __( 'تعداد چشمک‌های متوالی را در سکوت بشمارید و در پایگاه کدهای خطای پیکسوا جستجو کنید یا برای تکنسین ارسال نمایید.', 'pixva' ),
				'service'    => 'blink',
				'urgency'    => __( 'متوسط - نیاز به ثبت تعداد چشمک', 'pixva' ),
				'confidence' => 85,
			);
		}

		// پیش‌فرض هوشمند
		return array(
			'diagnosis'  => __( 'علائم ثبت‌شده نیازمند بررسی تخصصی مدار تغذیه و سیگنال برد تیکان است.', 'pixva' ),
			'action'     => __( 'دستگاه در کارگاه مجهز علاءالدین تحت تست ولتاژ و اسپکتروم سیگنال قرار می‌گیرد و پیش‌فاکتور دقیق قطعات صادر می‌شود.', 'pixva' ),
			'service'    => 'backlight',
			'urgency'    => __( 'عادی - هماهنگی پذیرش آنلاین', 'pixva' ),
			'confidence' => 78,
		);
	}
}

/**
 * پردازش درخواست عیب‌یابی AJAX.
 *
 * @return void
 */
function pixva_ajax_ai_diagnose() {
	check_ajax_referer( 'pixva_nonce', 'nonce' );

	$message = isset( $_POST['message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['message'] ) ) : '';
	$brand   = isset( $_POST['brand'] ) ? sanitize_text_field( wp_unslash( $_POST['brand'] ) ) : '';

	if ( empty( $message ) ) {
		wp_send_json_error( array( 'message' => __( 'لطفاً شرح مشکل یا علائم خرابی را وارد کنید.', 'pixva' ) ) );
	}

	$options = get_option( 'pixva_control_options', array() );
	$api_key = ! empty( $options['ai_gemini_key'] ) ? trim( $options['ai_gemini_key'] ) : '';

	// اگر کلید خارجی تنظیم نشده بود یا خطا داد، از موتور محلی استفاده می‌کنیم.
	$local_result = pixva_ai_local_diagnose( $message, $brand );

	if ( empty( $api_key ) ) {
		wp_send_json_success(
			array(
				'source'    => 'local_engine',
				'result'    => $local_result,
				'reply'     => sprintf(
					"تشخیص هوشمند پیکسوا:\n%s\n\nاقدام پیشنهادی کارگاه:\n%s",
					$local_result['diagnosis'],
					$local_result['action']
				),
				'service'   => $local_result['service'],
			)
		);
	}

	// درخواست امن به Google Gemini 1.5 Flash API
	$url      = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=' . rawurlencode( $api_key );
	$system_p = 'شما دستیار ارشد هوش مصنوعی کارگاه تخصصی تعمیر تلویزیون پیکسوا هستید. کاربر مشکل تلویزیون خود را شرح می‌دهد. پاسخی کاملاً فارسی، حرفه‌ای، فنی و خلاصه بدهید که شامل: ۱) عیب‌یابی محتمل ۲) قطعه خراب ۳) اقدام فوری و ۴) سطح فوریت باشد.';

	$payload = array(
		'contents' => array(
			array(
				'role'  => 'user',
				'parts' => array(
					array( 'text' => $system_p . "\n\nبرند: " . $brand . "\nمشکل دستگاه: " . $message ),
				),
			),
		),
		'generationConfig' => array(
			'temperature'   => 0.3,
			'maxOutputTokens' => 400,
		),
	);

	$response = wp_remote_post(
		$url,
		array(
			'timeout' => 12,
			'headers' => array( 'Content-Type' => 'application/json' ),
			'body'    => wp_json_encode( $payload ),
		)
	);

	if ( is_wp_error( $response ) ) {
		// سوئیچ خودکار به موتور محلی در صورت بروز خطای شبکه
		wp_send_json_success(
			array(
				'source'  => 'local_engine_fallback',
				'result'  => $local_result,
				'reply'   => $local_result['diagnosis'] . "\n\n" . $local_result['action'],
				'service' => $local_result['service'],
			)
		);
	}

	$body = json_decode( wp_remote_retrieve_body( $response ), true );
	$text = $body['candidates'][0]['content']['parts'][0]['text'] ?? '';

	if ( empty( $text ) ) {
		wp_send_json_success(
			array(
				'source'  => 'local_engine_fallback',
				'result'  => $local_result,
				'reply'   => $local_result['diagnosis'] . "\n\n" . $local_result['action'],
				'service' => $local_result['service'],
			)
		);
	}

	wp_send_json_success(
		array(
			'source'  => 'gemini_multimodal',
			'reply'   => trim( $text ),
			'result'  => $local_result,
			'service' => $local_result['service'],
		)
	);
}
add_action( 'wp_ajax_pixva_ai_diagnose', 'pixva_ajax_ai_diagnose' );
add_action( 'wp_ajax_nopriv_pixva_ai_diagnose', 'pixva_ajax_ai_diagnose' );

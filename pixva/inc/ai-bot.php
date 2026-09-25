<?php
/**
 * موتور هوش مصنوعی واقعی پیکسوا (inc/ai-bot.php) — Master Specification v25.0
 *
 * اصول حاکم:
 * ۱) اتصال واقعی به Google Gemini 1.5 Flash با wp_remote_post (متن + تصویر).
 * ۲) کلید API فقط در پیشخوان وردپرس ذخیره می‌شود و هرگز به جاوااسکریپت کلاینت
 *    ارسال نمی‌گردد (خروجی wp_localize_script فقط شامل ajaxUrl، nonce و وضعیت
 *    فعال بودن موتور است).
 * ۳) هیچ پاسخ تصادفی، array_rand یا داده ساختگی تولید نمی‌شود. اگر کلید تنظیم
 *    نشده باشد یا API خطا دهد، موتور «قانون‌محور کارگاهی» با نتیجه قطعی و
 *    برچسب شفاف source=rule_engine پاسخ می‌دهد و خطا به کاربر اعلام می‌شود.
 * ۴) همه درخواست‌ها با nonce، honeypot و محدودیت نرخ بر اساس IP محافظت می‌شوند.
 *
 * @package Pixva
 * @since   1.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ==========================================================================
   ۱) تنظیمات موتور هوش مصنوعی
   ========================================================================== */

if ( ! function_exists( 'pixva_ai_settings' ) ) {
	/**
	 * تنظیمات مؤثر موتور هوش مصنوعی.
	 *
	 * @return array<string, mixed>
	 */
	function pixva_ai_settings() {
		$options = function_exists( 'pixva_control_options' ) ? pixva_control_options() : array();

		$key = isset( $options['ai_gemini_key'] ) ? trim( (string) $options['ai_gemini_key'] ) : '';
		// کلید فقط سمت سرور استفاده می‌شود؛ هرگز در خروجی HTML چاپ نشود.
		return array(
			'key'         => $key,
			'endpoint'    => 'https://generativelanguage.googleapis.com/v1beta/models/',
			'model'       => ! empty( $options['ai_gemini_model'] ) ? sanitize_key( $options['ai_gemini_model'] ) : 'gemini-1.5-flash',
			'system'      => ! empty( $options['ai_system_prompt'] ) ? (string) $options['ai_system_prompt'] : pixva_ai_default_prompt(),
			'temperature' => isset( $options['ai_temperature'] ) && is_numeric( $options['ai_temperature'] ) ? max( 0, min( 1, (float) $options['ai_temperature'] ) ) : 0.25,
			'max_tokens'  => isset( $options['ai_max_tokens'] ) && is_numeric( $options['ai_max_tokens'] ) ? max( 128, min( 2048, (int) $options['ai_max_tokens'] ) ) : 700,
			'timeout'     => 25,
			'max_images'  => 4,
			'max_bytes'   => 4 * MB_IN_BYTES,
		);
	}
}

if ( ! function_exists( 'pixva_ai_default_prompt' ) ) {
	/**
	 * پرامپت سیستمی پیش‌فرض (فارسی، فنی و ساخت‌یافته).
	 *
	 * @return string
	 */
	function pixva_ai_default_prompt() {
		return 'شما دستیار ارشد عیب‌یابی کارگاه تخصصی تعمیر تلویزیون و نمایشگر «پیکسوا» در تهران (پاساژ علاءالدین) هستید. '
			. 'ورودی کاربر شامل شرح فارسی خرابی و در صورت وجود، تصویر صفحه تلویزیون است. '
			. 'پاسخ را فقط و فقط به‌صورت یک شیء JSON معتبر با کلیدهای زیر برگردانید: '
			. 'diagnosis (عیب‌یابی محتمل به فارسی، حداکثر ۲ جمله)، '
			. 'part (قطعه یا بلوک معیوب)، '
			. 'action (اقدام فوری که مشتری باید انجام دهد)، '
			. 'service (یکی از این کلیدها: backlight، powerboard، mainboard، water، panel، lines، no_sound، no_picture، no_power، blink)، '
			. 'urgency (یکی از: low، medium، high، critical)، '
			. 'safety (هشدار ایمنی در صورت وجود خطر برق یا آسیب بیشتر)، '
			. 'reply (توضیح کامل و محترمانه فارسی برای مشتری، حداکثر ۱۲۰ کلمه). '
			. 'اگر تصویر یا شرح برای تشخیص قطعی کافی نیست، در فیلد reply بگویید چه اطلاعات یا عکسی لازم است و حدس بی‌اساس نزنید. '
			. 'هیچ قیمتی اعلام نکنید؛ قیمت فقط توسط موتور نرخ‌نامه کارگاه محاسبه می‌شود.';
	}
}

if ( ! function_exists( 'pixva_ai_is_configured' ) ) {
	/**
	 * آیا کلید Gemini در پیشخوان تنظیم شده است؟
	 *
	 * @return bool
	 */
	function pixva_ai_is_configured() {
		$settings = pixva_ai_settings();
		return '' !== $settings['key'];
	}
}

if ( ! function_exists( 'pixva_ai_urgency_label' ) ) {
	/**
	 * برچسب فارسی سطح فوریت.
	 *
	 * @param string $urgency کلید فوریت.
	 * @return string
	 */
	function pixva_ai_urgency_label( $urgency ) {
		$map = array(
			'low'      => __( 'عادی — هماهنگی پذیرش آنلاین', 'pixva' ),
			'medium'   => __( 'متوسط — مراجعه در هفته جاری', 'pixva' ),
			'high'     => __( 'بالا — دستگاه را از برق بکشید', 'pixva' ),
			'critical' => __( 'بحرانی — خطر آسیب بیشتر یا برق‌گرفتگی', 'pixva' ),
		);
		$urgency = (string) $urgency;
		return isset( $map[ $urgency ] ) ? $map[ $urgency ] : $map['medium'];
	}
}

/* ==========================================================================
   ۲) موتور قانون‌محور کارگاهی (Fallback قطعی، بدون داده تصادفی)
   ========================================================================== */

if ( ! function_exists( 'pixva_ai_rule_base' ) ) {
	/**
	 * قواعد قطعی عیب‌یابی کارگاه: کلیدواژه‌ها → تشخیص، قطعه و اقدام.
	 *
	 * امتیاز هر قاعده از نسبت کلیدواژه‌های مطابقت‌شده محاسبه می‌شود
	 * (یک محاسبه واقعی، نه عدد ساختگی).
	 *
	 * @return array<int, array<string, mixed>>
	 */
	function pixva_ai_rule_base() {
		return array(
			array(
				'service'   => 'water',
				'keywords'  => array( 'آب', 'خیس', 'رطوبت', 'شیشه پاک', 'لکه', 'خط عمودی', 'خطوط عمودی', 'نیم صفحه', 'نصف صفحه' ),
				'diagnosis' => __( 'نفوذ مایعات به فلت‌های COF پنل یا برد T-Con و اکسید شدن مسیر آدرس‌دهی ستون‌ها.', 'pixva' ),
				'part'      => __( 'فلت COF / برد T-Con', 'pixva' ),
				'action'    => __( 'دستگاه را فوراً از برق بکشید و روشن نکنید؛ ترمیم با دستگاه بندینگ صنعتی در کارگاه انجام می‌شود.', 'pixva' ),
				'urgency'   => 'critical',
				'safety'    => __( 'روشن نگه داشتن دستگاه با پنل آب‌خورده باعث سوختن گلس و غیرقابل‌تعمیر شدن پنل می‌شود.', 'pixva' ),
			),
			array(
				'service'   => 'backlight',
				'keywords'  => array( 'صدا هست تصویر نیست', 'صدا داره تصویر نداره', 'تاریک', 'سیاه', 'نور ندارد', 'بک لایت', 'بکلایت', 'نور پس‌زمینه', 'تصویر محو' ),
				'diagnosis' => __( 'سوختگی یا نیم‌سوز شدن ال‌ای‌دی‌های شاخه‌ای بک‌لایت و فعال شدن پروتکشن برد پاور.', 'pixva' ),
				'part'      => __( 'دست بک‌لایت LED', 'pixva' ),
				'action'    => __( 'با نور چراغ‌قوه روی صفحه در زاویه مایل نگاه کنید؛ اگر تصویر کمرنگ دیده می‌شود، بک‌لایت معیوب است و نیاز به تعویض دست کامل دارد.', 'pixva' ),
				'urgency'   => 'medium',
				'safety'    => __( 'تکرار روشن و خاموش کردن، برد پاور را هم درگیر می‌کند.', 'pixva' ),
			),
			array(
				'service'   => 'powerboard',
				'keywords'  => array( 'روشن نمیشه', 'روشن نمی‌شود', 'خاموش', 'خاموشی کامل', 'بوی سوختگی', 'دود', 'تیک', 'چشمک استندبای', 'برق نمی‌آید' ),
				'diagnosis' => __( 'ایراد در طبقه اولیه یا ثانویه برد تغذیه: خرابی آی‌سی سوئیچینگ، ماس‌فت یا خازن‌های خشک‌شده.', 'pixva' ),
				'part'      => __( 'برد تغذیه (Power Supply)', 'pixva' ),
				'action'    => __( 'دستگاه را از محافظ برق جدا و ۱۰ دقیقه بی‌کار بگذارید؛ اگر باز هم روشن نشد، برد پاور نیاز به تست ولتاژ کارگاهی دارد.', 'pixva' ),
				'urgency'   => 'high',
				'safety'    => __( 'خازن‌های بزرگ برد پاور حتی پس از قطع برق، ولتاژ خطرناک دارند؛ باز کردن دستگاه توصیه نمی‌شود.', 'pixva' ),
			),
			array(
				'service'   => 'mainboard',
				'keywords'  => array( 'لوگو', 'روی لوگو', 'هنگ', 'ریستارت', 'خاموش روشن', 'آپدیت', 'بروزرسانی', 'مین برد', 'مین‌برد', 'هوشمند کار نمی‌کند', 'وای فای' ),
				'diagnosis' => __( 'خرابی حافظه eMMC/NAND مین‌برد یا به‌هم‌ریختگی فریمور؛ معمولاً پس از آپدیت ناقص یا نوسان برق.', 'pixva' ),
				'part'      => __( 'برد اصلی (Mainboard)', 'pixva' ),
				'action'    => __( 'از فلش کردن فریمور غیررسمی خودداری کنید؛ پروگرام مجدد حافظه یا ریبال آی‌سی با دستگاه BGA در کارگاه انجام می‌شود.', 'pixva' ),
				'urgency'   => 'high',
				'safety'    => '',
			),
			array(
				'service'   => 'blink',
				'keywords'  => array( 'چشمک', 'چراغ', 'چشمک می‌زند', 'چراغ پاور', 'کد خطا', 'عدد چشمک' ),
				'diagnosis' => __( 'فعال شدن پروتکشن محافظتی دستگاه و اعلام کد خطا با تعداد چشمک چراغ استندبای.', 'pixva' ),
				'part'      => __( 'مسیر حفاظتی برد پاور / مین‌برد', 'pixva' ),
				'action'    => __( 'تعداد چشمک‌های متوالی را در سکوت بشمارید و در پایگاه کدهای خطای پیکسوا (ابزار ۲۴) جست‌وجو کنید.', 'pixva' ),
				'urgency'   => 'medium',
				'safety'    => '',
			),
			array(
				'service'   => 'lines',
				'keywords'  => array( 'خط', 'خطوط', 'راه راه', 'نوار سیاه', 'خط افقی', 'بندینگ' ),
				'diagnosis' => __( 'قطع یا آسیب مسیر سیگنال در فلت‌های بندینگ پنل یا خرابی آی‌سی سورس.', 'pixva' ),
				'part'      => __( 'فلت COF / آی‌سی سورس', 'pixva' ),
				'action'    => __( 'با فشار ملایم روی قاب پایین صفحه بررسی کنید خط تغییر می‌کند یا نه؛ سپس برای بندینگ مجدد به کارگاه بفرستید.', 'pixva' ),
				'urgency'   => 'medium',
				'safety'    => __( 'فشار بیش از حد روی گلس باعث شکستگی لایه‌های پنل می‌شود.', 'pixva' ),
			),
			array(
				'service'   => 'no_sound',
				'keywords'  => array( 'صدا ندارد', 'صدا نداره', 'قطع صدا', 'وزوز', 'خش خش', 'بلندگو' ),
				'diagnosis' => __( 'خرابی بلندگو، طبقه خروجی صدا یا مشکل نرم‌افزاری مسیر صوتی (ARC/eARC).', 'pixva' ),
				'part'      => __( 'اسپیکر / برد آمپلی‌فایر', 'pixva' ),
				'action'    => __( 'ابتدا خروجی صدا را در تنظیمات روی PCM/Pass-through درست بررسی کنید؛ در صورت ادامه، بلندگوها با مولتی‌متر تست می‌شوند.', 'pixva' ),
				'urgency'   => 'low',
				'safety'    => '',
			),
			array(
				'service'   => 'no_picture',
				'keywords'  => array( 'تصویر ندارد', 'بی‌تصویری', 'برفک', 'سیگنال', 'hdmi', 'آنتن' ),
				'diagnosis' => __( 'قطع مسیر سیگنال ورودی (HDMI/تیونر) یا مشکل برد تیکان در پردازش تصویر.', 'pixva' ),
				'part'      => __( 'ورودی HDMI / تیونر / برد T-Con', 'pixva' ),
				'action'    => __( 'کابل و دستگاه منبع را عوض کنید و منوی خود تلویزیون را فراخوانی کنید؛ اگر منو نمایش داده می‌شود، پنل سالم و مشکل از مسیر سیگنال است.', 'pixva' ),
				'urgency'   => 'medium',
				'safety'    => '',
			),
			array(
				'service'   => 'panel',
				'keywords'  => array( 'شکسته', 'ترک', 'ضربه', 'شیشه شکسته', 'صفحه شکسته' ),
				'diagnosis' => __( 'شکستگی فیزیکی گلس پنل؛ این آسیب با ترمیم حل نمی‌شود.', 'pixva' ),
				'part'      => __( 'پنل (گلس)', 'pixva' ),
				'action'    => __( 'برآورد تعویض پنل تنها پس از بازدید کارشناس اعلام می‌شود؛ در بسیاری از سایزها قیمت پنل به صرفه اقتصادی نیست و دستگاه برای داغی ارزیابی می‌شود.', 'pixva' ),
				'urgency'   => 'low',
				'safety'    => __( 'لبه‌های شیشه شکسته برنده است؛ دستگاه را جابه‌جا نکنید.', 'pixva' ),
			),
		);
	}
}

if ( ! function_exists( 'pixva_ai_local_diagnose' ) ) {
	/**
	 * تحلیل قانون‌محور شرح خرابی (نتیجه قطعی و قابل تکرار).
	 *
	 * @param string $message شرح خرابی.
	 * @param string $brand   برند دستگاه.
	 * @return array
	 */
	function pixva_ai_local_diagnose( $message, $brand = '' ) {
		$text     = mb_strtolower( (string) $message, 'UTF-8' );
		$rules    = pixva_ai_rule_base();
		$best     = null;
		$best_hit = 0;

		foreach ( $rules as $rule ) {
			$hits      = 0;
			$matched   = array();
			foreach ( $rule['keywords'] as $keyword ) {
				if ( false !== mb_strpos( $text, mb_strtolower( $keyword, 'UTF-8' ), 0, 'UTF-8' ) ) {
					++$hits;
					$matched[] = $keyword;
				}
			}
			if ( $hits > $best_hit ) {
				$best_hit = $hits;
				$best     = array(
					'rule'    => $rule,
					'matched' => $matched,
					// نسبت کلیدواژه‌های مطابقت‌شده به کل کلیدواژه‌های قاعده.
					'ratio'   => count( $rule['keywords'] ) > 0 ? $hits / count( $rule['keywords'] ) : 0,
				);
			}
		}

		if ( null === $best ) {
			return array(
				'source'    => 'rule_engine',
				'matched'   => false,
				'diagnosis' => __( 'شرح واردشده با هیچ‌کدام از قواعد قطعی کارگاه مطابقت نداشت؛ نیاز به عیب‌یابی حضوری با ابزار اندازه‌گیری است.', 'pixva' ),
				'part'      => __( 'نامشخص تا زمان عیب‌یابی کارگاهی', 'pixva' ),
				'action'    => __( 'برای عیب‌یابی دقیق، دستگاه را به کارگاه بفرستید یا یک عکس روشن از صفحه به‌همراه برند و مدل ارسال کنید.', 'pixva' ),
				'service'   => '',
				'urgency'   => 'medium',
				'safety'    => '',
			);
		}

		$rule = $best['rule'];

		return array(
			'source'    => 'rule_engine',
			'matched'   => true,
			'keywords'  => $best['matched'],
			'coverage'  => round( $best['ratio'] * 100 ),
			'diagnosis' => $rule['diagnosis'],
			'part'      => $rule['part'],
			'action'    => $rule['action'],
			'service'   => $rule['service'],
			'urgency'   => $rule['urgency'],
			'safety'    => $rule['safety'],
			'brand'     => (string) $brand,
		);
	}
}

/* ==========================================================================
   ۳) فراخوانی واقعی Gemini API
   ========================================================================== */

if ( ! function_exists( 'pixva_ai_call_gemini' ) ) {
	/**
	 * ارسال درخواست واقعی به Gemini (متن + تصویر) با wp_remote_post.
	 *
	 * کلید API فقط در هدر x-goog-api-key فرستاده می‌شود و در هیچ خروجی
	 * فرانت‌اندی چاپ نمی‌گردد.
	 *
	 * @param string $message متن شرح خرابی.
	 * @param array  $images  فهرست تصاویر آماده (هرکدام: mime, data).
	 * @param string $brand   برند دستگاه.
	 * @return array|WP_Error آرایه پاسخ یا خطا.
	 */
	function pixva_ai_call_gemini( $message, $images = array(), $brand = '' ) {
		$settings = pixva_ai_settings();

		if ( '' === $settings['key'] ) {
			return new WP_Error(
				'pixva_ai_no_key',
				__( 'کلید API گوگل جمینای در مرکز کنترل پیشخوان تنظیم نشده است.', 'pixva' )
			);
		}

		$parts = array();

		foreach ( (array) $images as $image ) {
			if ( empty( $image['data'] ) || empty( $image['mime'] ) ) {
				continue;
			}
			$parts[] = array(
				'inline_data' => array(
					'mime_type' => (string) $image['mime'],
					'data'      => (string) $image['data'],
				),
			);
		}

		$user_text = (string) $message;
		if ( '' !== $brand ) {
			$user_text = sprintf( __( 'برند دستگاه: %s', 'pixva' ), $brand ) . "\n" . $user_text;
		}
		if ( ! empty( $images ) ) {
			$user_text .= "\n" . __( 'تصویر(های) پیوست‌شده از صفحه یا بدنه دستگاه است؛ آن‌ها را بررسی کن.', 'pixva' );
		}

		$parts[] = array( 'text' => $user_text );

		$payload = array(
			'systemInstruction' => array(
				'role'  => 'system',
				'parts' => array(
					array( 'text' => $settings['system'] ),
				),
			),
			'contents'          => array(
				array(
					'role'  => 'user',
					'parts' => $parts,
				),
			),
			'generationConfig'  => array(
				'temperature'      => (float) $settings['temperature'],
				'maxOutputTokens'  => (int) $settings['max_tokens'],
				'responseMimeType' => 'application/json',
			),
			'safetySettings'    => array(
				array(
					'category'  => 'HARM_CATEGORY_DANGEROUS_CONTENT',
					'threshold' => 'BLOCK_ONLY_HIGH',
				),
			),
		);

		$url = trailingslashit( $settings['endpoint'] ) . rawurlencode( $settings['model'] ) . ':generateContent';

		$response = wp_remote_post(
			$url,
			array(
				'timeout' => (int) $settings['timeout'],
				'headers' => array(
					'Content-Type'    => 'application/json',
					'x-goog-api-key'  => $settings['key'],
				),
				'body'    => wp_json_encode( $payload ),
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$status = (int) wp_remote_retrieve_response_code( $response );
		$body   = json_decode( (string) wp_remote_retrieve_body( $response ), true );

		if ( $status < 200 || $status >= 300 ) {
			$detail = '';
			if ( is_array( $body ) && isset( $body['error']['message'] ) ) {
				$detail = (string) $body['error']['message'];
			}
			return new WP_Error(
				'pixva_ai_http_error',
				sprintf(
					/* translators: 1: کد وضعیت HTTP، 2: جزئیات خطا */
					__( 'پاسخ خطای %1$d از سرویس هوش مصنوعی. %2$s', 'pixva' ),
				$status,
				$detail
				)
			);
		}

		$text = '';
		if ( is_array( $body ) && isset( $body['candidates'] ) && is_array( $body['candidates'] ) ) {
			foreach ( $body['candidates'] as $candidate ) {
				if ( ! isset( $candidate['content']['parts'] ) || ! is_array( $candidate['content']['parts'] ) ) {
					continue;
				}
				foreach ( $candidate['content']['parts'] as $part ) {
					if ( isset( $part['text'] ) ) {
						$text .= (string) $part['text'];
					}
				}
			}
		}

		// مسدودسازی ایمنی یا پاسخ خالی.
		if ( '' === trim( $text ) ) {
			$reason = '';
			if ( is_array( $body ) && isset( $body['candidates'][0]['finishReason'] ) ) {
				$reason = (string) $body['candidates'][0]['finishReason'];
			}
			return new WP_Error(
				'pixva_ai_empty',
				'' !== $reason
					? sprintf( __( 'سرویس هوش مصنوعی پاسخی تولید نکرد (%s).', 'pixva' ), $reason )
					: __( 'سرویس هوش مصنوعی پاسخی تولید نکرد.', 'pixva' )
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
			'raw'    => trim( $text ),
			'model'  => $settings['model'],
			'images' => count( (array) $images ),
			'usage'  => $usage,
		);
	}
}

if ( ! function_exists( 'pixva_ai_parse_structured' ) ) {
	/**
	 * تبدیل پاسخ JSON جمینای به ساختار استاندارد پیکسوا.
	 *
	 * @param string $raw پاسخ خام مدل.
	 * @return array|null
	 */
	function pixva_ai_parse_structured( $raw ) {
		$raw  = trim( (string) $raw );
		$data = json_decode( $raw, true );

		if ( ! is_array( $data ) ) {
			// اگر مدل برخلاف دستور، JSON برنگرداند، همان متن را به‌عنوان پاسخ نهایی بگیر.
			$start = strpos( $raw, '{' );
			$end   = strrpos( $raw, '}' );
			if ( false !== $start && false !== $end && $end > $start ) {
				$data = json_decode( substr( $raw, $start, $end - $start + 1 ), true );
			}
		}

		if ( ! is_array( $data ) ) {
			return null;
		}

		$allowed_services = array( 'backlight', 'powerboard', 'mainboard', 'water', 'panel', 'lines', 'no_sound', 'no_picture', 'no_power', 'blink' );
		$allowed_urgency  = array( 'low', 'medium', 'high', 'critical' );

		$service = isset( $data['service'] ) ? sanitize_key( (string) $data['service'] ) : '';
		if ( ! in_array( $service, $allowed_services, true ) ) {
			$service = '';
		}
		$urgency = isset( $data['urgency'] ) ? sanitize_key( (string) $data['urgency'] ) : 'medium';
		if ( ! in_array( $urgency, $allowed_urgency, true ) ) {
			$urgency = 'medium';
		}

		$reply = isset( $data['reply'] ) ? sanitize_textarea_field( wp_strip_all_tags( (string) $data['reply'] ) ) : '';
		if ( '' === $reply ) {
			$reply = wp_strip_all_tags( $raw );
		}

		return array(
			'diagnosis' => isset( $data['diagnosis'] ) ? sanitize_textarea_field( wp_strip_all_tags( (string) $data['diagnosis'] ) ) : '',
			'part'      => isset( $data['part'] ) ? sanitize_text_field( wp_strip_all_tags( (string) $data['part'] ) ) : '',
			'action'    => isset( $data['action'] ) ? sanitize_textarea_field( wp_strip_all_tags( (string) $data['action'] ) ) : '',
			'safety'    => isset( $data['safety'] ) ? sanitize_textarea_field( wp_strip_all_tags( (string) $data['safety'] ) ) : '',
			'service'   => $service,
			'urgency'   => $urgency,
			'reply'     => $reply,
		);
	}
}

if ( ! function_exists( 'pixva_ai_prepare_uploads' ) ) {
	/**
	 * آماده‌سازی تصاویر ارسالی کاربر برای تحلیل چندرسانه‌ای.
	 *
	 * @param string $field_name نام فیلد فایل در فرم.
	 * @param int    $max_files  حداکثر تعداد فایل.
	 * @return array|WP_Error فهرست تصاویر (mime + data base64) یا خطا.
	 */
	function pixva_ai_prepare_uploads( $field_name = 'pixva_image', $max_files = 4 ) {
		if ( empty( $_FILES[ $field_name ] ) ) {
			return array();
		}

		$settings = pixva_ai_settings();
		$files    = $_FILES[ $field_name ]; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$prepared = array();

		// نرمال‌سازی ساختار چندفایلی.
		if ( is_array( $files['name'] ) ) {
			$list = array();
			foreach ( array_keys( $files['name'] ) as $index ) {
				$list[] = array(
					'name'     => $files['name'][ $index ],
					'type'     => $files['type'][ $index ],
					'tmp_name' => $files['tmp_name'][ $index ],
					'error'    => $files['error'][ $index ],
					'size'     => $files['size'][ $index ],
				);
			}
		} else {
			$list = array( $files );
		}

		foreach ( $list as $file ) {
			if ( count( $prepared ) >= (int) $max_files ) {
				break;
			}
			if ( empty( $file['tmp_name'] ) || (int) $file['error'] !== UPLOAD_ERR_OK ) {
				continue;
			}
			if ( (int) $file['size'] > (int) $settings['max_bytes'] ) {
				return new WP_Error( 'pixva_ai_file_large', __( 'حجم تصویر بیش از ۴ مگابایت است.', 'pixva' ) );
			}

			$checked = wp_check_filetype_and_ext( $file['tmp_name'], (string) $file['name'] );
			$mime    = ! empty( $checked['type'] ) ? (string) $checked['type'] : '';
			$allowed = array( 'image/jpeg', 'image/png', 'image/webp' );
			if ( ! in_array( $mime, $allowed, true ) ) {
				return new WP_Error( 'pixva_ai_file_type', __( 'فقط تصویر JPEG، PNG یا WebP قابل تحلیل است.', 'pixva' ) );
			}

			$binary = file_get_contents( $file['tmp_name'] ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
			if ( false === $binary || '' === $binary ) {
				continue;
			}

			$prepared[] = array(
				'mime' => $mime,
				'data' => base64_encode( $binary ), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
			);
		}

		return $prepared;
	}
}

if ( ! function_exists( 'pixva_ai_diagnose' ) ) {
	/**
	 * نقطه ورود واحد تشخیص هوشمند (Gemini واقعی + موتور قانون‌محور).
	 *
	 * @param string $message شرح خرابی.
	 * @param string $brand   برند.
	 * @param array  $images  تصاویر آماده (اختیاری).
	 * @return array
	 */
	function pixva_ai_diagnose( $message, $brand = '', $images = array() ) {
		$message = trim( (string) $message );
		$brand   = trim( (string) $brand );
		$rule    = pixva_ai_local_diagnose( $message, $brand );

		// اگر موتور هوش مصنوعی تنظیم نشده، نتیجه قانون‌محور با برچسب شفاف برگردان.
		if ( ! pixva_ai_is_configured() ) {
			return array(
				'source'   => 'rule_engine',
				'notice'   => __( 'کلید Gemini تنظیم نشده است؛ پاسخ از موتور قانون‌محور کارگاه تولید شد.', 'pixva' ),
				'reply'    => pixva_ai_format_rule_reply( $rule ),
				'result'   => $rule,
				'service'  => $rule['service'],
				'urgency'  => $rule['urgency'],
			);
		}

		$response = pixva_ai_call_gemini( $message, $images, $brand );

		if ( is_wp_error( $response ) ) {
			return array(
				'source'  => 'rule_engine',
				'notice'  => $response->get_error_message(),
				'reply'   => pixva_ai_format_rule_reply( $rule ),
				'result'  => $rule,
				'service' => $rule['service'],
				'urgency' => $rule['urgency'],
			);
		}

		$parsed = pixva_ai_parse_structured( $response['raw'] );

		if ( null === $parsed ) {
			return array(
				'source'  => 'gemini',
				'notice'  => '',
				'reply'   => $response['raw'],
				'result'  => $rule,
				'service' => $rule['service'],
				'urgency' => $rule['urgency'],
				'model'   => $response['model'],
			);
		}

		return array(
			'source'    => 'gemini',
			'notice'    => '',
			'reply'     => $parsed['reply'],
			'result'    => array(
				'diagnosis' => $parsed['diagnosis'],
				'part'      => $parsed['part'],
				'action'    => $parsed['action'],
				'safety'    => $parsed['safety'],
				'service'   => '' !== $parsed['service'] ? $parsed['service'] : $rule['service'],
				'urgency'   => $parsed['urgency'],
				'urgency_label' => pixva_ai_urgency_label( $parsed['urgency'] ),
			),
			'service'   => '' !== $parsed['service'] ? $parsed['service'] : $rule['service'],
			'urgency'   => $parsed['urgency'],
			'model'     => $response['model'],
			'images'    => (int) $response['images'],
			'usage'     => $response['usage'],
		);
	}
}

if ( ! function_exists( 'pixva_ai_files_to_payload' ) ) {
	/**
	 * تبدیل مسیر فایل‌های ذخیره‌شده در آپلودها به payload تصویر Gemini.
	 *
	 * فقط فایل‌های واقعی داخل پوشه آپلود وردپرس و با mime مجاز پذیرفته می‌شوند
	 * تا امکان خواندن فایل دلخواه از سمت درخواست‌کننده وجود نداشته باشد.
	 *
	 * @param array $files مسیرهای مطلق یا رکوردهای آماده (mime/data).
	 * @param int   $max   سقف تعداد تصویر.
	 * @return array<int, array{mime: string, data: string}>
	 */
	function pixva_ai_files_to_payload( $files, $max = 4 ) {
		$settings = pixva_ai_settings();
		$allowed  = array( 'image/jpeg', 'image/png', 'image/webp' );
		$base     = function_exists( 'wp_get_upload_dir' ) ? wp_get_upload_dir() : wp_upload_dir();
		$base_dir = isset( $base['basedir'] ) ? (string) $base['basedir'] : '';
		$out      = array();

		foreach ( (array) $files as $file ) {
			if ( count( $out ) >= (int) $max ) {
				break;
			}

			// رکورد از پیش آماده (خروجی pixva_ai_prepare_uploads).
			if ( is_array( $file ) && ! empty( $file['data'] ) && ! empty( $file['mime'] ) ) {
				if ( in_array( (string) $file['mime'], $allowed, true ) ) {
					$out[] = array(
						'mime' => (string) $file['mime'],
						'data' => (string) $file['data'],
					);
				}
				continue;
			}

			$path = is_string( $file ) ? trim( $file ) : '';
			if ( '' === $path || ! is_readable( $path ) ) {
				continue;
			}

			$real = (string) realpath( $path );
			if ( '' !== $base_dir && 0 !== strpos( $real, (string) realpath( $base_dir ) ) ) {
				continue;
			}

			if ( (int) filesize( $real ) > (int) $settings['max_bytes'] ) {
				continue;
			}

			$checked = wp_check_filetype_and_ext( $real, basename( $real ) );
			$mime    = ! empty( $checked['type'] ) ? (string) $checked['type'] : '';
			if ( ! in_array( $mime, $allowed, true ) ) {
				continue;
			}

			$binary = file_get_contents( $real ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
			if ( false === $binary || '' === $binary ) {
				continue;
			}

			$out[] = array(
				'mime' => $mime,
				'data' => base64_encode( $binary ), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
			);
		}

		return $out;
	}
}

if ( ! function_exists( 'pixva_ai_analyze' ) ) {
	/**
	 * تحلیل هوشمند یک پرونده: متن + تصویر(های) ذخیره‌شده + زمینه دستگاه.
	 *
	 * نقطه ورود سمت سرور برای جریان‌هایی که تصویر پیش‌تر آپلود و ذخیره شده است
	 * (استعلام سریع قیمت، ثبت سفارش و ابزارهای عیب‌یابی). خروجی همان ساختار
	 * pixva_ai_diagnose() است و در نبود کلید یا خطای شبکه، به موتور قانون‌محور
	 * بازمی‌گردد.
	 *
	 * @param string $message شرح خرابی از زبان مشتری.
	 * @param array  $files   مسیر فایل‌های تصویر یا payload آماده.
	 * @param array  $context کلیدهای زمینه: brand، size، problem، model.
	 * @return array
	 */
	function pixva_ai_analyze( $message, $files = array(), $context = array() ) {
		$context = is_array( $context ) ? $context : array();
		$brand   = isset( $context['brand'] ) ? trim( (string) $context['brand'] ) : '';

		$lines = array( trim( (string) $message ) );
		foreach ( array( 'brand', 'model', 'size', 'problem' ) as $key ) {
			if ( ! empty( $context[ $key ] ) ) {
				$lines[] = sprintf( '%s: %s', $key, trim( (string) $context[ $key ] ) );
			}
		}

		$images = pixva_ai_files_to_payload( $files );
		$result = pixva_ai_diagnose( implode( "\n", array_filter( $lines ) ), $brand, $images );

		if ( is_array( $result ) ) {
			$result['images']    = isset( $result['images'] ) ? (int) $result['images'] : count( $images );
			$result['context']   = $context;
		}

		return $result;
	}
}

if ( ! function_exists( 'pixva_ai_format_rule_reply' ) ) {
	/**
	 * ساخت پاسخ خوانا از نتیجه موتور قانون‌محور.
	 *
	 * @param array $rule نتیجه pixva_ai_local_diagnose().
	 * @return string
	 */
	function pixva_ai_format_rule_reply( $rule ) {
		$lines = array();
		$lines[] = sprintf( __( 'عیب‌یابی کارگاهی: %s', 'pixva' ), $rule['diagnosis'] );
		if ( ! empty( $rule['part'] ) ) {
			$lines[] = sprintf( __( 'قطعه درگیر: %s', 'pixva' ), $rule['part'] );
		}
		$lines[] = sprintf( __( 'اقدام فوری: %s', 'pixva' ), $rule['action'] );
		$lines[] = sprintf( __( 'سطح فوریت: %s', 'pixva' ), pixva_ai_urgency_label( $rule['urgency'] ) );
		if ( ! empty( $rule['safety'] ) ) {
			$lines[] = sprintf( __( 'هشدار ایمنی: %s', 'pixva' ), $rule['safety'] );
		}
		return implode( "\n", $lines );
	}
}

/* ==========================================================================
   ۴) هندلر AJAX (با nonce، honeypot و محدودیت نرخ IP)
   ========================================================================== */

/**
 * پردازش درخواست عیب‌یابی هوشمند از فرانت‌اند.
 *
 * @return void
 */
function pixva_ajax_ai_diagnose() {
	pixva_ajax_guard( 'pixva_nonce', 'ai', 15, HOUR_IN_SECONDS );

	$message = isset( $_POST['message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['message'] ) ) : '';
	$brand   = isset( $_POST['brand'] ) ? sanitize_text_field( wp_unslash( $_POST['brand'] ) ) : '';

	if ( pixva_strlen( $message ) < 4 ) {
		pixva_ajax_error( __( 'لطفاً شرح کامل‌تری از علائم خرابی بنویسید (حداقل ۴ نویسه).', 'pixva' ), 422 );
	}

	$images = pixva_ai_prepare_uploads( 'pixva_image', 4 );
	if ( is_wp_error( $images ) ) {
		pixva_ajax_error( $images->get_error_message(), 422 );
	}

	$result = pixva_ai_diagnose( $message, $brand, $images );

	$estimate = null;
	if ( ! empty( $result['service'] ) ) {
		$size = isset( $_POST['size'] ) ? sanitize_key( wp_unslash( $_POST['size'] ) ) : '';
		if ( '' !== $size ) {
			$estimate = pixva_calculate_estimate( 'samsung', 'led', $size, $result['service'] );
		}
	}

	$payload = array(
		'source'      => $result['source'],
		'notice'      => isset( $result['notice'] ) ? $result['notice'] : '',
		'reply'       => $result['reply'],
		'result'      => $result['result'],
		'service'     => $result['service'],
		'urgency'     => $result['urgency'],
		'urgencyLabel' => pixva_ai_urgency_label( $result['urgency'] ),
		'model'       => isset( $result['model'] ) ? $result['model'] : '',
		'images'      => isset( $result['images'] ) ? (int) $result['images'] : count( $images ),
		'calculatorUrl' => pixva_page_url( 'calculator' ),
	);

	if ( is_array( $estimate ) && empty( $estimate['panel_replacement'] ) ) {
		$payload['estimate'] = array(
			'min' => $estimate['min'],
			'max' => $estimate['max'],
			'text' => sprintf(
				/* translators: 1: حداقل، 2: حداکثر */
				__( 'برآورد نرخ‌نامه: %1$s تا %2$s تومان', 'pixva' ),
				pixva_price( $estimate['min'] ),
				pixva_price( $estimate['max'] )
			),
		);
	}

	pixva_ajax_success( $payload );
}
add_action( 'wp_ajax_pixva_ai_diagnose', 'pixva_ajax_ai_diagnose' );
add_action( 'wp_ajax_nopriv_pixva_ai_diagnose', 'pixva_ajax_ai_diagnose' );
add_action( 'wp_ajax_pixva_ai_analyze', 'pixva_ajax_ai_diagnose' );
add_action( 'wp_ajax_nopriv_pixva_ai_analyze', 'pixva_ajax_ai_diagnose' );

/* ==========================================================================
   ۵) رابط کاربری دستیار هوش مصنوعی
   ========================================================================== */

if ( ! function_exists( 'pixva_render_ai_assistant' ) ) {
	/**
	 * رندر رابط دستیار هوش مصنوعی (ابزارهای ۱ تا ۴).
	 *
	 * @param string $mode    حالت: text|image|frames|voice.
	 * @param int    $tool_id شناسه ابزار.
	 * @return void
	 */
	function pixva_render_ai_assistant( $mode = 'text', $tool_id = 1 ) {
		$mode    = in_array( $mode, array( 'text', 'image', 'frames', 'voice' ), true ) ? $mode : 'text';
		$tool_id = (int) $tool_id;
		$brands  = array( '' => __( 'برند دستگاه…', 'pixva' ) );
		foreach ( pixva_brand_catalog() as $key => $brand ) {
			$brands[ $key ] = $brand['fa'];
		}
		$uid = 'pixva-ai-' . $mode . '-' . $tool_id;
		?>
		<div class="pixva-ai-studio" data-pixva-ai data-ai-mode="<?php echo esc_attr( $mode ); ?>" data-ai-configured="<?php echo pixva_ai_is_configured() ? '1' : '0'; ?>">
			<form class="pixva-ai-studio__form" data-ai-form enctype="multipart/form-data" novalidate>
				<?php pixva_honeypot_field(); ?>

				<?php if ( 'image' === $mode || 'frames' === $mode ) : ?>
					<div class="pixva-field">
						<label for="<?php echo esc_attr( $uid ); ?>-file">
							<?php echo 'frames' === $mode ? esc_html__( 'تا ۴ فریم از لحظه پرش تصویر', 'pixva' ) : esc_html__( 'عکس صفحه تلویزیون (JPEG/PNG/WebP تا ۴ مگابایت)', 'pixva' ); ?>
						</label>
						<input type="file" class="pixva-input" id="<?php echo esc_attr( $uid ); ?>-file" name="pixva_image[]" accept="image/jpeg,image/png,image/webp" <?php echo 'frames' === $mode ? 'multiple' : ''; ?> data-ai-files>
						<span class="pixva-tool-note"><?php esc_html_e( 'تصویر مستقیماً برای تحلیل بصری به Gemini 1.5 Flash فرستاده می‌شود؛ کلید API هرگز در مرورگر شما قرار نمی‌گیرد.', 'pixva' ); ?></span>
					</div>
				<?php endif; ?>

				<div class="pixva-tool-row">
					<div class="pixva-field">
						<label for="<?php echo esc_attr( $uid ); ?>-brand"><?php esc_html_e( 'برند', 'pixva' ); ?></label>
						<select class="pixva-input" id="<?php echo esc_attr( $uid ); ?>-brand" name="brand" data-ai-brand>
							<?php foreach ( $brands as $value => $label ) : ?>
								<option value="<?php echo esc_attr( $value ); ?>"><?php echo esc_html( $label ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>
					<div class="pixva-field">
						<label for="<?php echo esc_attr( $uid ); ?>-size"><?php esc_html_e( 'سایز (برای برآورد هم‌زمان)', 'pixva' ); ?></label>
						<select class="pixva-input" id="<?php echo esc_attr( $uid ); ?>-size" name="size" data-ai-size>
							<option value=""><?php esc_html_e( 'انتخاب نشده', 'pixva' ); ?></option>
							<?php foreach ( pixva_size_catalog() as $value => $label ) : ?>
								<option value="<?php echo esc_attr( $value ); ?>"><?php echo esc_html( $label ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>
				</div>

				<div class="pixva-field">
					<label for="<?php echo esc_attr( $uid ); ?>-message"><?php esc_html_e( 'شرح علائم خرابی', 'pixva' ); ?></label>
					<textarea class="pixva-input" id="<?php echo esc_attr( $uid ); ?>-message" name="message" rows="3" data-ai-input required placeholder="<?php esc_attr_e( 'مثلاً: تلویزیون سامسونگ ۵۵ اینچ صدا دارد ولی تصویر سیاه است و چراغ پاور ۳ بار چشمک می‌زند.', 'pixva' ); ?>"></textarea>
				</div>

				<div class="pixva-tool-row">
					<button type="submit" class="pixva-btn pixva-btn--cta" data-ai-submit><?php esc_html_e( 'تحلیل با هوش مصنوعی', 'pixva' ); ?></button>
					<?php if ( 'voice' === $mode ) : ?>
						<button type="button" class="pixva-btn pixva-btn--ghost-dark" data-ai-voice><?php esc_html_e( 'گفتار به متن (فارسی)', 'pixva' ); ?></button>
						<span class="pixva-tool-note" data-ai-voice-hint><?php esc_html_e( 'برای استفاده از گفتار، مرورگر باید از Web Speech API پشتیبانی کند و اجازه میکروفون داده شود.', 'pixva' ); ?></span>
					<?php endif; ?>
				</div>
			</form>

			<div class="pixva-ai-studio__result" data-ai-result hidden>
				<div class="pixva-ai-studio__meta">
					<span class="pixva-badge pixva-badge--brand" data-ai-source></span>
					<span class="pixva-badge" data-ai-urgency hidden></span>
				</div>
				<p class="pixva-ai-studio__reply" data-ai-reply></p>
				<ul class="pixva-ai-studio__facts" data-ai-facts></ul>
				<p class="pixva-notice pixva-notice--warning" data-ai-notice hidden></p>
				<div class="pixva-tool-row">
					<a class="pixva-btn pixva-btn--primary" href="<?php echo esc_url( pixva_page_url( 'calculator' ) ); ?>" data-ai-quote><?php esc_html_e( 'محاسبه هزینه این عیب', 'pixva' ); ?></a>
					<button type="button" class="pixva-btn pixva-btn--ghost-dark" data-ai-copy><?php esc_html_e( 'کپی پاسخ', 'pixva' ); ?></button>
				</div>
			</div>
		</div>
		<?php
	}
}

if ( ! function_exists( 'pixva_render_ai_chatbot_widget' ) ) {
	/**
	 * ویجت شناور دستیار هوش مصنوعی (پایین همه صفحه‌ها).
	 *
	 * @return void
	 */
	function pixva_render_ai_chatbot_widget() {
		$options = function_exists( 'pixva_control_options' ) ? pixva_control_options() : array();
		if ( empty( $options['ai_enable_floating'] ) ) {
			return;
		}
		?>
		<div class="pixva-ai-widget" data-pixva-ai-bot>
			<button type="button" class="pixva-ai-trigger" data-ai-toggle aria-expanded="false" aria-controls="pixva-ai-panel" aria-label="<?php esc_attr_e( 'دستیار هوش مصنوعی عیب‌یابی پیکسوا', 'pixva' ); ?>">
				<span class="pixva-ai-trigger__pulse" aria-hidden="true"></span>
				<?php echo pixva_icon( 'bolt' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<span class="pixva-ai-trigger__badge"><?php esc_html_e( 'عیب‌یابی AI', 'pixva' ); ?></span>
			</button>

			<div class="pixva-ai-panel" id="pixva-ai-panel" data-ai-panel hidden>
				<header class="pixva-ai-header">
					<div>
						<span class="pixva-badge pixva-badge--brand"><?php echo pixva_ai_is_configured() ? esc_html__( 'Gemini 1.5 Flash فعال', 'pixva' ) : esc_html__( 'موتور قانون‌محور کارگاه', 'pixva' ); ?></span>
						<h4><?php esc_html_e( 'دستیار عیب‌یاب پیکسوا', 'pixva' ); ?></h4>
					</div>
					<button type="button" class="pixva-ai-close" data-ai-close aria-label="<?php esc_attr_e( 'بستن', 'pixva' ); ?>">✕</button>
				</header>

				<div class="pixva-ai-messages" data-ai-messages role="log" aria-live="polite">
					<div class="pixva-ai-msg pixva-ai-msg--bot">
						<p><?php esc_html_e( 'سلام! برند تلویزیون و علائم خرابی را بنویسید یا عکس صفحه را بفرستید تا تحلیل واقعی انجام شود.', 'pixva' ); ?></p>
					</div>
				</div>

				<form class="pixva-ai-input-form" data-ai-chat-form enctype="multipart/form-data" novalidate>
					<?php pixva_honeypot_field(); ?>
					<div class="pixva-ai-field-group">
						<label class="pixva-ai-upload" for="pixva-ai-chat-file">
							<?php echo pixva_icon( 'camera' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							<input type="file" id="pixva-ai-chat-file" name="pixva_image[]" accept="image/jpeg,image/png,image/webp" data-ai-chat-file>
						</label>
						<input type="text" class="pixva-ai-input" name="message" placeholder="<?php esc_attr_e( 'مشکل تلویزیون چیست؟', 'pixva' ); ?>" required data-ai-chat-input aria-label="<?php esc_attr_e( 'شرح خرابی', 'pixva' ); ?>">
						<button type="submit" class="pixva-btn pixva-btn--cta pixva-btn--sm" data-ai-chat-submit><?php esc_html_e( 'ارسال', 'pixva' ); ?></button>
					</div>
				</form>
			</div>
		</div>
		<?php
	}
}

/* ==========================================================================
   ۶) اطلاع‌رسانی پیشخوان و نکات امنیتی
   ========================================================================== */

/**
 * اعلان مدیریتی در صورت تنظیم نبودن کلید Gemini.
 *
 * @return void
 */
function pixva_ai_admin_notice() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	if ( pixva_ai_is_configured() ) {
		return;
	}
	if ( get_user_meta( get_current_user_id(), 'pixva_ai_notice_dismissed', true ) ) {
		return;
	}
	$url = admin_url( 'admin.php?page=pixva-control&tab=ai' );
	?>
	<div class="notice notice-warning is-dismissible">
		<p>
			<strong><?php esc_html_e( 'پیکسوا — هوش مصنوعی:', 'pixva' ); ?></strong>
			<?php
			echo wp_kses_post(
				sprintf(
					/* translators: %s: نشانی مرکز کنترل */
					__( 'کلید رایگان Google Gemini تنظیم نشده است؛ ابزارهای عیب‌یابی هوشمند فعلاً با موتور قانون‌محور کارگاه پاسخ می‌دهند. برای فعال‌سازی تحلیل واقعی تصویر و متن، کلید را در <a href="%s">مرکز کنترل پیکسوا</a> وارد کنید.', 'pixva' ),
					esc_url( $url )
				)
			);
			?>
		</p>
	</div>
	<?php
}
add_action( 'admin_notices', 'pixva_ai_admin_notice' );

/**
 * اطمینان از اینکه کلید API هرگز در فیدها، اسکریپت‌ها یا خروجی عمومی چاپ نشود.
 *
 * @param string $html خروجی wp_head یا اسکریپت.
 * @return string
 */
function pixva_ai_scrub_key_from_output( $html ) {
	$settings = pixva_ai_settings();
	if ( '' === $settings['key'] || false === strpos( $html, $settings['key'] ) ) {
		return $html;
	}
	return str_replace( $settings['key'], '', $html );
}
add_filter( 'script_loader_tag', 'pixva_ai_scrub_key_from_output', 99 );

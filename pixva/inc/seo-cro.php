<?php
/**
 * معماری سئو-محور، سرعت و بهینه‌سازی تبدیل (CRO) — لایه ۲٫۰٫۰ (Master Prompt v11).
 *
 * این پرونده جای تجربه‌های سنگین لایه‌های قبل را می‌گیرد و چهار ماژول تولیدی می‌سازد:
 *
 *  ۱) پاک‌سازی و سبک‌سازی: حذف کامل قفل‌کننده‌های اسکرول (GSAP/ScrollTrigger) و
 *     مدل سه‌بعدی (Spline) از صف اسکریپت‌ها؛ اسکرول ۱۰۰٪ بومی و بدون گیر.
 *  ۲) راهنمای متنی علائم خرابی (pixva_symptom_guide) با اسکیما FAQPage و Service
 *     تا گوگل پاسخ جستجوهای «صدا دارد تصویر ندارد» را مستقیم از سایت بردارد.
 *  ۳) جدول شفاف هزینه‌ها (pixva_price_calculator) با فیلتر برند/سایز و اسکیما
 *     PriceSpecification — همه اعداد سمت سرور از موتور نرخ‌نامه واقعی می‌آیند.
 *  ۴) چهار اصل اعتماد (pixva_trust_features) و فرم یک‌مرحله‌ای اعزام فوری
 *     (pixva_express_booking) که مستقیم پرونده می‌سازد و پیامک می‌فرستد.
 *
 * اصل حاکم: هیچ متن، قیمت یا متغیری سخت‌کد نشده؛ همه از کاتالوگ‌های نرخ‌نامه،
 * تنظیمات مرکز کنترل و سفارشی‌ساز خوانده می‌شوند و با فیلتر قابل بازنویسی‌اند.
 *
 * @package Pixva
 * @since   2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ==========================================================================
 * ۱) حالت کارایی و پاک‌سازی اسکریپت‌های سنگین
 * ======================================================================= */

if ( ! function_exists( 'pixva_performance_mode' ) ) {
	/**
	 * حالت کارایی (SEO-first): موتورهای اسکرول سینمایی و سه‌بعدی خاموش می‌شوند.
	 *
	 * در لایه ۲٫۰٫۰ پیش‌فرض روشن است؛ مدیر می‌تواند از سفارشی‌ساز یا با فیلتر
	 * آن را خاموش کند تا تجربه سینمایی لایه‌های قبل برگردد.
	 *
	 * @return bool
	 */
	function pixva_performance_mode() {
		/**
		 * فیلتر حالت کارایی سئو-محور.
		 *
		 * @param bool $enabled روشن بودن پاک‌سازی اسکریپت‌های سنگین.
		 */
		return (bool) apply_filters( 'pixva_performance_mode', (bool) pixva_option( 'pixva_performance_mode', true ) );
	}
}

if ( ! function_exists( 'pixva_heavy_script_handles' ) ) {
	/**
	 * فهرست هندل‌های سنگین/قفل‌کننده اسکرول که باید از فرانت‌اند حذف شوند.
	 *
	 * شامل هندل‌های داخلی پوسته و نام‌های رایجی است که افزونه‌ها یا ادیتور
	 * صفحه‌ساز ممکن است با همان نام‌ها صف کنند (تا حتی در حالت ترکیبی هم
	 * اسکرول موبایل بومی و روان بماند).
	 *
	 * @return array<int, string>
	 */
	function pixva_heavy_script_handles() {
		$handles = array(
			// موتور داخلی سینمایی و کتابخانه‌های آن.
			'pixva-cinematic',
			'pixva-gsap',
			'pixva-scrolltrigger',
			'pixva-spline',
			'pixva-spline-3d',
			// نام‌های رایج بیرونی (GSAP / ScrollTrigger / Spline).
			'gsap',
			'gsap-core',
			'scrolltrigger',
			'ScrollTrigger',
			'spline-viewer',
			'splinetool-viewer',
			'@splinetool/viewer',
		);

		/**
		 * فیلتر فهرست هندل‌های سنگین.
		 *
		 * @param array<int, string> $handles هندل‌ها.
		 */
		return (array) apply_filters( 'pixva_heavy_script_handles', $handles );
	}
}

if ( ! function_exists( 'pixva_map_script_handles' ) ) {
	/**
	 * هندل‌های نقشه: نقشه دمو صفحه اصلی حذف می‌شود، اما نقشه کاربردی برگه
	 * پیگیری پرونده (که مشتری واقعاً به آن نیاز دارد) حفظ می‌گردد.
	 *
	 * @return array<int, string>
	 */
	function pixva_map_script_handles() {
		$handles = array( 'pixva-tracker-map', 'pixva-leaflet', 'leaflet' );

		/**
		 * فیلتر هندل‌های نقشه.
		 *
		 * @param array<int, string> $handles هندل‌ها.
		 */
		return (array) apply_filters( 'pixva_map_script_handles', $handles );
	}
}

if ( ! function_exists( 'pixva_is_tracking_view' ) ) {
	/**
	 * آیا صفحه جاری برگه پیگیری پرونده است (نقشه کاربردی مجاز بماند)؟
	 *
	 * @return bool
	 */
	function pixva_is_tracking_view() {
		if ( ! function_exists( 'is_page_template' ) ) {
			return false;
		}

		return (bool) is_page_template(
			array(
				'page-templates/page-tracking.php',
				'page-templates/page-hub-tracking.php',
			)
		);
	}
}

if ( ! function_exists( 'pixva_purge_heavy_scripts' ) ) {
	/**
	 * حذف قاطع همه اسکریپت‌های قفل‌کننده اسکرول و مدل سه‌بعدی از فرانت‌اند.
	 *
	 * در اولویت بسیار دیر اجرا می‌شود تا هر چیزی که پوسته، صفحه‌ساز یا افزونه
	 * صف کرده باشد پیش از چاپ بیرون انداخته شود (Dequeue + Deregister).
	 *
	 * @return void
	 */
	function pixva_purge_heavy_scripts() {
		if ( is_admin() || ! pixva_performance_mode() ) {
			return;
		}

		// فقط در فرانت‌اند عمومی؛ ویرایشگر المنتور باید ویجت‌ها را ببیند.
		if ( function_exists( 'wp_doing_ajax' ) && wp_doing_ajax() ) {
			return;
		}

		foreach ( pixva_heavy_script_handles() as $handle ) {
			if ( wp_script_is( $handle, 'enqueued' ) || wp_script_is( $handle, 'registered' ) ) {
				wp_dequeue_script( $handle );
				wp_deregister_script( $handle );
			}
		}

		/*
		 * نقشه: دمو/مسیر ساختگی صفحه اصلی حذف می‌شود؛ روی برگه پیگیری پرونده
		 * نقشه یک قابلیت واقعی است و دست‌نخورده می‌ماند.
		 */
		if ( ! pixva_is_tracking_view() ) {
			foreach ( pixva_map_script_handles() as $handle ) {
				if ( wp_script_is( $handle, 'enqueued' ) || wp_script_is( $handle, 'registered' ) ) {
					wp_dequeue_script( $handle );
					wp_deregister_script( $handle );
				}
				if ( wp_style_is( $handle, 'enqueued' ) || wp_style_is( $handle, 'registered' ) ) {
					wp_dequeue_style( $handle );
					wp_deregister_style( $handle );
				}
			}
		}

		/**
		 * هوک پس از پاک‌سازی اسکریپت‌های سنگین.
		 */
		do_action( 'pixva_heavy_scripts_purged' );
	}
	add_action( 'wp_enqueue_scripts', 'pixva_purge_heavy_scripts', 9999 );
	add_action( 'wp_print_scripts', 'pixva_purge_heavy_scripts', 1 );
	// اسکریپت‌هایی که پس از wp_head (از شورت‌کد یا بخش صفحه اصلی) صف می‌شوند
	// در فوتر چاپ می‌گردند؛ این هوک آخرین لایه دفاعی پیش از چاپ فوتر است.
	add_action( 'wp_print_footer_scripts', 'pixva_purge_heavy_scripts', 1 );
	add_action( 'elementor/frontend/before_register_scripts', 'pixva_purge_heavy_scripts', 9999 );
}

if ( ! function_exists( 'pixva_native_scroll_attrs' ) ) {
	/**
	 * افزودن کلاس اسکرول بومی به <body>.
	 *
	 * فیلتر body_class در وردپرس همیشه «آرایه» می‌گیرد و باید «آرایه» برگرداند.
	 * الحاق رشته به آرایه (مانند $classes . '...') خطای Array to string conversion
	 * می‌دهد و چون body_class() خروجی را implode می‌کند، کل کلاس‌های بدنه
	 * از بین می‌رود؛ بنابراین کلاس تازه فقط با push به آرایه افزوده می‌شود.
	 *
	 * @param mixed $classes کلاس‌های بدنه (ورودی ممکن است از فیلتر دیگر رشته باشد).
	 * @return array<int, string>
	 */
	function pixva_native_scroll_attrs( $classes ) {
		// ورودی هرچه باشد، با آرایه کار می‌کنیم تا خطای نوع رخ ندهد.
		if ( ! is_array( $classes ) ) {
			$classes = array_filter( array_map( 'trim', explode( ' ', (string) $classes ) ), 'strlen' );
		}

		if ( ! function_exists( 'pixva_performance_mode' ) || ! pixva_performance_mode() ) {
			return $classes;
		}

		if ( ! in_array( 'pixva-native-scroll', $classes, true ) ) {
			$classes[] = 'pixva-native-scroll';
		}

		return $classes;
	}
	add_filter( 'body_class', 'pixva_native_scroll_attrs' );
}

if ( ! function_exists( 'pixva_html_classes' ) ) {
	/**
	 * کلاس‌های عنصر <html> (جایی که scroll-behavior واقعاً اثر می‌کند).
	 *
	 * فیلتر body_class فقط به <body> می‌رسد؛ برای اینکه اسکرول بومی روی عنصر
	 * پیمایش‌گر سند اعمال شود، همان کلاس روی <html> هم چاپ می‌گردد.
	 *
	 * @return array<int, string>
	 */
	function pixva_html_classes() {
		$classes = array( 'no-js' );

		if ( function_exists( 'pixva_performance_mode' ) && pixva_performance_mode() ) {
			$classes[] = 'pixva-native-scroll';
		}

		/**
		 * فیلتر کلاس‌های <html>.
		 *
		 * @param array<int, string> $classes کلاس‌ها.
		 */
		$classes = (array) apply_filters( 'pixva_html_classes', $classes );

		return array_values( array_unique( array_filter( array_map( 'sanitize_html_class', $classes ), 'strlen' ) ) );
	}
}

if ( ! function_exists( 'pixva_html_class_attr' ) ) {
	/**
	 * مقدار آماده چاپ برای ویژگی class عنصر <html>.
	 *
	 * @return string
	 */
	function pixva_html_class_attr() {
		return implode( ' ', pixva_html_classes() );
	}
}

if ( ! function_exists( 'pixva_home_seo_section_settings' ) ) {
	/**
	 * تنظیمات مشترک بخش‌های سئو/تبدیل (از سفارشی‌ساز؛ پیش‌فرض‌ها i18n هستند).
	 *
	 * کلیدهای سفارشی‌ساز با همان نامی خوانده می‌شوند که تابع رندر هر ماژول
	 * استفاده می‌کند تا یک منبع حقیقت وجود داشته باشد و متن سخت‌کد نشود.
	 *
	 * @param string $section کلید بخش صفحه اصلی.
	 * @return array<string, string>
	 */
	function pixva_home_seo_section_settings( $section ) {
		$map = array(
			'trust_features'   => 'trust',
			'symptom_guide'    => 'symptom',
			'price_calculator' => 'price',
			'express_booking'  => 'express',
		);

		$prefix   = isset( $map[ $section ] ) ? $map[ $section ] : str_replace( '_', '-', sanitize_key( (string) $section ) );
		$settings = array(
			'title'    => (string) pixva_option( 'pixva_' . $prefix . '_title', '' ),
			'subtitle' => (string) pixva_option( 'pixva_' . $prefix . '_subtitle', '' ),
		);

		if ( 'express_booking' === $section ) {
			$settings['source']   = 'home';
			$settings['cta_text'] = (string) pixva_option( 'pixva_express_cta', '' );
		}

		/**
		 * فیلتر تنظیمات بخش سئو/تبدیل.
		 *
		 * @param array  $settings تنظیمات.
		 * @param string $section  کلید بخش.
		 */
		return apply_filters( 'pixva_home_seo_section_settings', $settings, $section );
	}
}

if ( ! function_exists( 'pixva_seo_stripos' ) ) {
	/**
	 * جست‌وجوی بی‌تفاوت به بزرگی/کوچکی با fallback برای هاست‌های بدون mbstring.
	 *
	 * @param string $haystack متن میزبان.
	 * @param string $needle   سوزن.
	 * @return bool
	 */
	function pixva_seo_stripos( $haystack, $needle ) {
		if ( '' === $needle ) {
			return false;
		}

		if ( function_exists( 'mb_stripos' ) ) {
			return false !== mb_stripos( (string) $haystack, (string) $needle );
		}

		return false !== stripos( (string) $haystack, (string) $needle );
	}
}

if ( ! function_exists( 'pixva_seo_strlen' ) ) {
	/**
	 * طول متن با fallback برای هاست‌های بدون mbstring.
	 *
	 * @param string $text متن.
	 * @return int
	 */
	function pixva_seo_strlen( $text ) {
		if ( function_exists( 'mb_strlen' ) ) {
			return (int) mb_strlen( (string) $text );
		}

		return (int) strlen( (string) $text );
	}
}

/* ==========================================================================
 * ۲) ماژول سئو ۱ — راهنمای متنی علائم خرابی (pixva_symptom_guide)
 * ======================================================================= */

if ( ! function_exists( 'pixva_symptom_guide_items' ) ) {
	/**
	 * چهار علامت خرابی پرجست‌وجو با پاسخ کارگاهی و برآورد واقعی هزینه.
	 *
	 * هر کارت دقیقاً همان جمله‌ای را دارد که کاربر در گوگل جست‌وجو می‌کند
	 * (search intent) و پاسخ متنی کاملاً ایندکس‌شدنی می‌دهد. برآورد هزینه از
	 * موتور نرخ‌نامه (pixva_calculate_estimate) برای سایز مرجع خوانده می‌شود
	 * تا هیچ عددی سخت‌کد نشود.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	function pixva_symptom_guide_items() {
		$problems = function_exists( 'pixva_problem_catalog' ) ? pixva_problem_catalog() : array();
		$ref_size = (string) pixva_option( 'pixva_symptom_ref_size', '55' );
		$ref_brand = (string) pixva_option( 'pixva_symptom_ref_brand', 'samsung' );

		$items = array(
			array(
				'id'       => 'no-picture-backlight',
				'problem'  => 'no_picture',
				'icon'     => 'sound',
				'question' => __( 'تلویزیون صدا دارد ولی تصویر ندارد؛ علت چیست؟', 'pixva' ),
				'title'    => __( 'صدا دارد ولی تصویر ندارد (خرابی بک‌لایت)', 'pixva' ),
				'cause'    => __( 'خاموش‌شدن یا سوختن ریسه‌های LED بک‌لایت', 'pixva' ),
				'body'     => __( 'وقتی صدا هست و تصویر نیست، در بیشتر موارد پنل سالم است و ریسه‌های بک‌لایت سوخته‌اند یا درایور آن‌ها قطع کرده است. با تاباندن نور چراغ‌قوه از فاصله نزدیک به صفحه، اگر تصویر بسیار کم‌رنگ دیده شد، تشخیص بک‌لایت قطعی است. این ایراد با تعویض کامل ریسه‌ها (نه تعمیر موضعی) و تنظیم مجدد جریان درایور برطرف می‌شود؛ تعمیر موضعی معمولاً چند هفته بعد دوباره خاموش می‌کند.', 'pixva' ),
				'checks'   => array(
					__( 'تلویزیون را در اتاق تاریک روشن کنید و با چراغ‌قوه گوشی از زاویه مایل به صفحه بتابانید.', 'pixva' ),
					__( 'اگر تصویر کم‌رنگ دیده شد، بک‌لایت خاموش است و پنل احتمالاً سالم مانده.', 'pixva' ),
					__( 'منوی تلویزیون را باز کنید؛ اگر منو هم دیده نمی‌شود ایراد از بک‌لایت است نه منبع تصویر.', 'pixva' ),
				),
				'warning'  => __( 'باز کردن پنل بدون دستگاه بندینگ و قاب اختصاصی ریسک شکستن صفحه را دارد؛ خودتان باز نکنید.', 'pixva' ),
			),
			array(
				'id'       => 'vertical-horizontal-lines',
				'problem'  => 'lines',
				'icon'     => 'panel',
				'question' => __( 'خطوط عمودی یا افقی روی صفحه تلویزیون نشانه چیست؟', 'pixva' ),
				'title'    => __( 'خطوط عمودی یا افقی روی صفحه (ایراد پنل/تیکان)', 'pixva' ),
				'cause'    => __( 'قطع شدن فلت‌های COF یا ایراد برد T-Con', 'pixva' ),
				'body'     => __( 'خطوط ثابت عمودی معمولاً از قطع شدن اتصال فلت‌های COF روی لبه پنل یا خرابی برد T-Con می‌آید؛ خطوط افقی بیشتر به فلت‌های سمت منبع تغذیه پنل مربوط است. اگر خط با ضربه آرام به قاب تغییر کرد، ایراد اتصالی است و با بندینگ صنعتی قابل بازسازی است. اما اگر پنل ضربه خورده یا شکستگی داخلی دارد، تعمیر مقرون‌به‌صرفه نیست و پیش از هر کاری صریح اعلام می‌شود.', 'pixva' ),
				'checks'   => array(
					__( 'عکس تمام‌صفحه سفید و تمام‌صفحه مشکی پخش کنید؛ خط‌هایی که در هر دو ثابت‌اند ایراد سخت‌افزاری‌اند.', 'pixva' ),
					__( 'با انگشت اشاره به‌آرامی روی قاب مقابل خط بزنید؛ تغییر خط نشانه قطعی اتصال فلت است.', 'pixva' ),
					__( 'یک منبع دیگر (گیرنده یا کنسول) وصل کنید تا ایراد کابل HDMI رد شود.', 'pixva' ),
				),
				'warning'  => __( 'تعداد و پهنای خط تعیین‌کننده امکان تعمیر است؛ بیش از سه خط پهن معمولاً به بندینگ کامل نیاز دارد.', 'pixva' ),
			),
			array(
				'id'       => 'no-power-blink',
				'problem'  => 'no_power',
				'icon'     => 'plug',
				'question' => __( 'تلویزیون روشن نمی‌شود و چراغ پاور چشمک می‌زند؛ چه کنم؟', 'pixva' ),
				'title'    => __( 'تلویزیون روشن نمی‌شود / چراغ پاور چشمک می‌زند (برد تغذیه)', 'pixva' ),
				'cause'    => __( 'خرابی برد پاور، بادکردن خازن‌ها یا فعال‌شدن محافظت', 'pixva' ),
				'body'     => __( 'چشمک‌زدن چراغ پاور یعنی برد اصلی کد خطا اعلام می‌کند و منبع تغذیه یا یکی از ولتاژهای مسیر روشن نمی‌شود. رایج‌ترین علت، بادکردن خازن‌های خروجی، سوختن ماسفت‌های اینورتر بک‌لایت یا فعال‌شدن مدار محافظت در اثر نوسان برق است. شمارش دقیق تعداد چشمک، برند و مدل را یادداشت کنید؛ همان عدد، قطعه معیوب را در دیتابیس کدهای خطا مشخص می‌کند و برآورد را دقیق‌تر می‌کند.', 'pixva' ),
				'checks'   => array(
					__( 'تلویزیون را ۵ دقیقه از برق بکشید و دوباره بزنید تا مدار محافظت ریست شود.', 'pixva' ),
					__( 'تعداد چشمک چراغ پاور را بشمارید و ثبت کنید (مثلاً ۳ چشمک، مکث، تکرار).', 'pixva' ),
					__( 'دستگاه محافظ یا سه‌راهی را حذف کنید و مستقیم به پریز بزنید تا نوسان ورودی رد شود.', 'pixva' ),
				),
				'warning'  => __( 'برد پاور ولتاژ خطرناک ذخیره‌شده دارد؛ حتی پس از قطع برق، لمس آن خطر برق‌گرفتگی دارد.', 'pixva' ),
			),
			array(
				'id'       => 'logo-loop-reset',
				'problem'  => 'mainboard',
				'icon'     => 'cpu',
				'question' => __( 'تلویزیون روی لوگو گیر کرده یا مدام ریست می‌شود؛ مشکل کجاست؟', 'pixva' ),
				'title'    => __( 'روی لوگو گیر کرده یا ریست می‌شود (مین‌برد)', 'pixva' ),
				'cause'    => __( 'خرابی حافظه، نیم‌سوز شدن مین‌برد یا آسیب فریم‌ور', 'pixva' ),
				'body'     => __( 'ماندن روی لوگو و ریست‌شدن پیاپی یعنی فریم‌ور یا حافظه eMMC/NAND مین‌برد دچار آسیب شده یا یکی از رگولاتورهای تغذیه مین‌برد نیم‌سوز است. در بسیاری از موارد با پروگرام مجدد فریم‌ور و تعویض آی‌سی حافظه، دستگاه کامل برمی‌گردد و نیازی به تعویض کل برد نیست. اگر تلویزیون هوشمند است و پیش از این آپدیت نیمه‌کاره یا نصب برنامه ناشناس داشته، علت تقریباً قطعی نرم‌افزاری است.', 'pixva' ),
				'checks'   => array(
					__( 'ریست سخت‌افزاری: نگه‌داشتن دکمه پاور روی بدنه به مدت ۱۰ ثانیه هنگام وصل بودن برق.', 'pixva' ),
					__( 'همه دستگاه‌های HDMI را جدا کنید؛ گاهی سیگنال HDCP باعث حلقه ریست می‌شود.', 'pixva' ),
					__( 'اگر منوی سرویس باز می‌شود، مدل دقیق و نسخه فریم‌ور را یادداشت کنید.', 'pixva' ),
				),
				'warning'  => __( 'پروگرام فریم‌ور با فایل ناسازگار می‌تواند مین‌برد را غیرقابل‌بازیابی کند؛ فقط با فایل مخصوص همان مدل انجام می‌شود.', 'pixva' ),
			),
		);

		foreach ( $items as $index => $item ) {
			$problem_key = isset( $item['problem'] ) ? $item['problem'] : '';
			$estimate    = null;

			if ( function_exists( 'pixva_calculate_estimate' ) && '' !== $problem_key ) {
				$estimate = pixva_calculate_estimate( $ref_brand, 'led', $ref_size, $problem_key );
			}

			$items[ $index ]['label']    = isset( $problems[ $problem_key ] ) ? $problems[ $problem_key ] : $item['title'];
			$items[ $index ]['estimate'] = is_array( $estimate ) ? $estimate : null;
			$items[ $index ]['days']     = is_array( $estimate ) && isset( $estimate['days'] ) ? (string) $estimate['days'] : '';
		}

		/**
		 * فیلتر کارت‌های راهنمای علائم خرابی.
		 *
		 * @param array $items کارت‌ها.
		 */
		return apply_filters( 'pixva_symptom_guide_items', $items );
	}
}

if ( ! function_exists( 'pixva_symptom_guide_schema' ) ) {
	/**
	 * گره‌های اسکیما برای راهنمای علائم: FAQPage + Service با کاتالوگ پیشنهاد.
	 *
	 * @param array<int, array<string, mixed>> $items کارت‌ها.
	 * @return array<int, array<string, mixed>>
	 */
	function pixva_symptom_guide_schema( $items ) {
		$graph = array();

		// ۱) FAQPage — همان جمله‌هایی که کاربر در گوگل می‌پرسد.
		$questions = array();
		foreach ( $items as $item ) {
			$answer = trim( (string) $item['body'] . ' ' . __( 'علت احتمالی: ', 'pixva' ) . (string) $item['cause'] . '.' );
			if ( ! empty( $item['days'] ) ) {
				$answer .= ' ' . sprintf(
					/* translators: %s: زمان تعمیر */
					__( 'زمان تقریبی تعمیر: %s.', 'pixva' ),
					(string) $item['days']
				);
			}

			$questions[] = array(
				'@type'          => 'Question',
				'name'           => (string) $item['question'],
				'acceptedAnswer' => array(
					'@type' => 'Answer',
					'text'  => wp_strip_all_tags( $answer ),
				),
			);
		}

		if ( ! empty( $questions ) ) {
			$graph[] = array(
				'@type'     => 'FAQPage',
				'mainEntity' => $questions,
			);
		}

		// ۲) Service (RepairService) با کاتالوگ پیشنهاد و بازه قیمت واقعی.
		$offers = array();
		foreach ( $items as $item ) {
			if ( empty( $item['estimate'] ) || empty( $item['estimate']['min'] ) ) {
				continue;
			}

			$offers[] = array(
				'@type'            => 'Offer',
				'name'             => (string) $item['label'],
				'description'      => (string) $item['cause'],
				'priceCurrency'    => 'IRR',
				'price'            => (string) ( (int) $item['estimate']['min'] * 10 ),
				'priceSpecification' => array(
					'@type'         => 'PriceSpecification',
					'minPrice'      => (string) ( (int) $item['estimate']['min'] * 10 ),
					'maxPrice'      => (string) ( (int) $item['estimate']['max'] * 10 ),
					'priceCurrency' => 'IRR',
					'eligibleQuantity' => array(
						'@type' => 'QuantitativeValue',
						'value' => 1,
					),
				),
			);
		}

		$service = array(
			'@type'       => 'Service',
			'serviceType' => __( 'تعمیر تخصصی تلویزیون در محل', 'pixva' ),
			'name'        => (string) pixva_option( 'pixva_symptom_title', __( 'مشکل تلویزیون شما چیست؟', 'pixva' ) ),
			'description' => (string) pixva_option(
				'pixva_symptom_subtitle',
				__( 'راهنمای کارگاهی تشخیص علامت‌های پرتکرار خرابی تلویزیون، همراه با علت احتمالی، تست سریع خانگی و بازه واقعی هزینه تعمیر.', 'pixva' )
			),
			'url'         => home_url( '/' ),
		);

		if ( function_exists( 'pixva_schema_business_id' ) ) {
			$service['provider'] = array( '@id' => pixva_schema_business_id() );
		}

		if ( ! empty( $offers ) ) {
			$service['hasOfferCatalog'] = array(
				'@type'          => 'OfferCatalog',
				'name'           => __( 'خدمات تعمیر تلویزیون', 'pixva' ),
				'itemListElement' => $offers,
			);
		}

		$graph[] = $service;

		/**
		 * فیلتر گراف اسکیمای راهنمای علائم.
		 *
		 * @param array $graph گره‌ها.
		 * @param array $items کارت‌ها.
		 */
		return apply_filters( 'pixva_symptom_guide_schema', $graph, $items );
	}
}

if ( ! function_exists( 'pixva_render_symptom_guide' ) ) {
	/**
	 * رندر بخش «مشکل تلویزیون شما چیست؟» با HTML متنی کاملاً ایندکس‌شدنی.
	 *
	 * بدون تصویر سنگین، بدون انیمیشن اسکرول و بدون JS — فقط متن ساختاریافته
	 * با سرتیترهای معنادار (H2/H3) و فهرست‌های تست خانگی.
	 *
	 * @param array<string, mixed> $settings تنظیمات ویجت/سکشن.
	 * @return void
	 */
	function pixva_render_symptom_guide( $settings = array() ) {
		$items = pixva_symptom_guide_items();
		if ( empty( $items ) ) {
			return;
		}

		$title    = isset( $settings['title'] ) && '' !== $settings['title'] ? $settings['title'] : (string) pixva_option( 'pixva_symptom_title', __( 'مشکل تلویزیون شما چیست؟', 'pixva' ) );
		$subtitle = isset( $settings['subtitle'] ) && '' !== $settings['subtitle'] ? $settings['subtitle'] : (string) pixva_option(
			'pixva_symptom_subtitle',
			__( 'راهنمای کارگاهی تشخیص علامت‌های پرتکرار خرابی تلویزیون، همراه با علت احتمالی، تست سریع خانگی و بازه واقعی هزینه تعمیر.', 'pixva' )
		);
		$cta_text = isset( $settings['cta_text'] ) && '' !== $settings['cta_text'] ? $settings['cta_text'] : __( 'ثبت درخواست اعزام فوری تکنسین', 'pixva' );
		$cta_url  = isset( $settings['cta_url'] ) && '' !== $settings['cta_url'] ? $settings['cta_url'] : ( function_exists( 'pixva_page_url' ) ? pixva_page_url( 'calculator' ) : home_url( '/' ) );
		$diagnose = function_exists( 'pixva_page_url' ) ? pixva_page_url( 'tracking' ) : home_url( '/' );

		$ref_size = (string) pixva_option( 'pixva_symptom_ref_size', '55' );
		?>
		<section class="pixva-seo pixva-symptom" id="symptom-guide">
			<div class="pixva-container">
				<header class="pixva-seo__head">
					<h2><?php echo esc_html( $title ); ?></h2>
					<p><?php echo esc_html( $subtitle ); ?></p>
				</header>

				<div class="pixva-symptom__grid">
					<?php foreach ( $items as $item ) : ?>
						<article class="pixva-symptom__card" id="<?php echo esc_attr( $item['id'] ); ?>">
							<span class="pixva-symptom__icon" aria-hidden="true">
								<?php echo pixva_icon( isset( $item['icon'] ) && '' !== $item['icon'] ? $item['icon'] : 'tool' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							</span>
							<h3><?php echo esc_html( $item['title'] ); ?></h3>

							<p class="pixva-symptom__q"><?php echo esc_html( $item['question'] ); ?></p>

							<p class="pixva-symptom__cause">
								<strong><?php esc_html_e( 'علت احتمالی:', 'pixva' ); ?></strong>
								<?php echo esc_html( $item['cause'] ); ?>
							</p>

							<p><?php echo esc_html( $item['body'] ); ?></p>

							<h4><?php esc_html_e( 'سه تست سریع خانگی', 'pixva' ); ?></h4>
							<ol class="pixva-symptom__checks">
								<?php foreach ( (array) $item['checks'] as $check ) : ?>
									<li><?php echo esc_html( $check ); ?></li>
								<?php endforeach; ?>
							</ol>

							<?php if ( ! empty( $item['warning'] ) ) : ?>
								<p class="pixva-symptom__warn"><?php echo esc_html( $item['warning'] ); ?></p>
							<?php endif; ?>

							<?php if ( ! empty( $item['estimate'] ) && ! empty( $item['estimate']['min'] ) ) : ?>
								<p class="pixva-symptom__cost">
									<strong><?php esc_html_e( 'بازه هزینه تعمیر', 'pixva' ); ?></strong>
									<span>
										<?php
										echo esc_html(
											pixva_price( (int) $item['estimate']['min'] ) . ' — ' . pixva_price( (int) $item['estimate']['max'] )
										);
										?>
										<?php esc_html_e( 'تومان', 'pixva' ); ?>
									</span>
									<small>
										<?php
										echo esc_html(
											sprintf(
												/* translators: 1: سایز مرجع، 2: زمان تعمیر */
												__( 'برآورد %1$s اینچ LED — %2$s', 'pixva' ),
												function_exists( 'pixva_fa_num' ) ? pixva_fa_num( $ref_size ) : $ref_size,
												'' !== $item['days'] ? $item['days'] : __( 'پس از عیب‌یابی در محل', 'pixva' )
											)
										);
										?>
									</small>
								</p>
							<?php endif; ?>

							<a class="pixva-seo__cta" href="<?php echo esc_url( add_query_arg( 'problem', rawurlencode( (string) $item['problem'] ), $cta_url ) ); ?>">
								<?php echo esc_html( $cta_text ); ?>
							</a>
						</article>
					<?php endforeach; ?>
				</div>

				<footer class="pixva-seo__foot">
					<p>
						<?php esc_html_e( 'تشخیص قطعی نیازمند بازدید در محل است؛ عیب‌یابی و برآورد هزینه پیش از شروع تعمیر به شما اعلام می‌شود.', 'pixva' ); ?>
					</p>
					<a class="pixva-seo__link" href="<?php echo esc_url( $diagnose ); ?>">
						<?php esc_html_e( 'پیگیری پرونده تعمیر', 'pixva' ); ?>
					</a>
				</footer>
			</div>

			<?php
			if ( function_exists( 'pixva_print_schema_graph' ) ) {
				pixva_print_schema_graph( pixva_symptom_guide_schema( $items ) );
			}
			?>
		</section>
		<?php
	}
}

/* ==========================================================================
 * ۳) ماژول سئو ۲ — جدول شفاف هزینه‌ها و استعلام آنلاین (pixva_price_calculator)
 * ======================================================================= */

if ( ! function_exists( 'pixva_price_table_sizes' ) ) {
	/**
	 * سایزهای نمایش داده‌شده در جدول قیمت (۳۲ تا ۷۵ اینچ طبق نرخ‌نامه).
	 *
	 * @return array<int, string>
	 */
	function pixva_price_table_sizes() {
		$sizes = array( '32', '43', '50', '55', '65', '75' );

		if ( function_exists( 'pixva_pricing_size_factors' ) ) {
			$sizes = array_values( array_intersect( $sizes, array_keys( (array) pixva_pricing_size_factors() ) ) );
		}

		/**
		 * فیلتر سایزهای جدول قیمت.
		 *
		 * @param array<int, string> $sizes سایزها.
		 */
		return (array) apply_filters( 'pixva_price_table_sizes', $sizes );
	}
}

if ( ! function_exists( 'pixva_price_table_services' ) ) {
	/**
	 * خدمات ردیف‌های جدول قیمت (کلیدهای واقعی موتور نرخ‌نامه).
	 *
	 * @return array<int, string>
	 */
	function pixva_price_table_services() {
		$services = array( 'backlight', 'powerboard', 'mainboard', 'lines', 'panel', 'no_picture' );

		/**
		 * فیلتر خدمات جدول قیمت.
		 *
		 * @param array<int, string> $services کلیدها.
		 */
		return (array) apply_filters( 'pixva_price_table_services', $services );
	}
}

if ( ! function_exists( 'pixva_price_table_matrix' ) ) {
	/**
	 * ماتریس قیمت یک برند در یک سایز: یک ردیف به ازای هر خدمت.
	 *
	 * همه اعداد در لحظه از pixva_calculate_estimate() (نرخ‌نامه ۱۴۰۵) می‌آیند
	 * و هیچ مبلغی در قالب سخت‌کد نشده است.
	 *
	 * @param string $brand کلید برند.
	 * @param string $size  سایز اینچ.
	 * @return array<int, array<string, mixed>>
	 */
	function pixva_price_table_matrix( $brand, $size ) {
		$brand    = sanitize_key( (string) $brand );
		$size     = sanitize_key( (string) $size );
		$problems = function_exists( 'pixva_problem_catalog' ) ? pixva_problem_catalog() : array();
		$days     = function_exists( 'pixva_pricing_days_map' ) ? (array) pixva_pricing_days_map() : array();
		$rows     = array();

		foreach ( pixva_price_table_services() as $service ) {
			$estimate = function_exists( 'pixva_calculate_estimate' ) ? pixva_calculate_estimate( $brand, 'led', $size, $service ) : null;

			$rows[] = array(
				'service'  => $service,
				'label'    => isset( $problems[ $service ] ) ? $problems[ $service ] : $service,
				'min'      => is_array( $estimate ) ? (int) $estimate['min'] : 0,
				'max'      => is_array( $estimate ) ? (int) $estimate['max'] : 0,
				'days'     => is_array( $estimate ) && ! empty( $estimate['days'] ) ? (string) $estimate['days'] : ( isset( $days[ $service ] ) ? (string) $days[ $service ] : '' ),
				'panel_replacement' => is_array( $estimate ) && ! empty( $estimate['panel_replacement'] ),
				'breakdown' => is_array( $estimate ) && isset( $estimate['breakdown'] ) ? (array) $estimate['breakdown'] : array(),
			);
		}

		/**
		 * فیلتر ماتریس قیمت.
		 *
		 * @param array  $rows  ردیف‌ها.
		 * @param string $brand برند.
		 * @param string $size  سایز.
		 */
		return apply_filters( 'pixva_price_table_matrix', $rows, $brand, $size );
	}
}

if ( ! function_exists( 'pixva_price_table_schema' ) ) {
	/**
	 * اسکیما Service + OfferCatalog + PriceSpecification برای جدول قیمت.
	 *
	 * مبلغ‌ها به ریال (IRR) تبدیل می‌شوند چون نرخ‌نامه داخلی بر پایه تومان است.
	 *
	 * @param string $brand کلید برند.
	 * @param string $size  سایز.
	 * @param array  $rows  ردیف‌های ماتریس.
	 * @return array<int, array<string, mixed>>
	 */
	function pixva_price_table_schema( $brand, $size, $rows ) {
		$brands = function_exists( 'pixva_brand_catalog' ) ? (array) pixva_brand_catalog() : array();
		$name   = isset( $brands[ $brand ] ) ? $brands[ $brand ]['fa'] : $brand;

		$offers = array();
		foreach ( (array) $rows as $row ) {
			if ( empty( $row['min'] ) ) {
				continue;
			}

			$offers[] = array(
				'@type'              => 'Offer',
				'name'               => sprintf(
					/* translators: 1: خدمت، 2: برند، 3: سایز */
					__( '%1$s تلویزیون %2$s %3$s اینچ', 'pixva' ),
					$row['label'],
					$name,
					$size
				),
				'priceCurrency'      => 'IRR',
				'price'              => (string) ( (int) $row['min'] * 10 ),
				'availability'       => 'https://schema.org/InStock',
				'priceSpecification' => array(
					'@type'         => 'PriceSpecification',
					'minPrice'      => (string) ( (int) $row['min'] * 10 ),
					'maxPrice'      => (string) ( (int) $row['max'] * 10 ),
					'priceCurrency' => 'IRR',
					'unitText'      => __( 'هر دستگاه تلویزیون', 'pixva' ),
					'valueAddedTaxIncluded' => false,
				),
			);
		}

		if ( empty( $offers ) ) {
			return array();
		}

		$service = array(
			'@type'       => 'Service',
			'serviceType' => __( 'تعمیر تلویزیون در محل با نرخ‌نامه شفاف', 'pixva' ),
			'name'        => sprintf(
				/* translators: %s: برند */
				__( 'قیمت تعمیر تلویزیون %s', 'pixva' ),
				$name
			),
			'description' => __( 'جدول شفاف حدود قیمت قطعات و اجرت تعمیر تلویزیون بر اساس برند و سایز، محاسبه‌شده از نرخ‌نامه رسمی کارگاه.', 'pixva' ),
			'url'         => home_url( '/' ),
			'hasOfferCatalog' => array(
				'@type'           => 'OfferCatalog',
				'name'            => __( 'نرخ‌نامه تعمیر تلویزیون', 'pixva' ),
				'itemListElement' => $offers,
			),
		);

		if ( function_exists( 'pixva_schema_business_id' ) ) {
			$service['provider'] = array( '@id' => pixva_schema_business_id() );
		}

		/**
		 * فیلتر اسکیمای جدول قیمت.
		 *
		 * @param array  $graph گره‌ها.
		 * @param string $brand برند.
		 * @param array  $rows  ردیف‌ها.
		 */
		return (array) apply_filters( 'pixva_price_table_schema', array( $service ), $brand, $rows );
	}
}

if ( ! function_exists( 'pixva_render_price_calculator' ) ) {
	/**
	 * رندر جدول متنی شفاف قیمت با فیلتر سریع برند و سایز.
	 *
	 * جدول پیش‌فرض کاملاً سمت سرور رندر می‌شود (ایندکس بدون JS) و تغییر فیلتر
	 * با یک واکشی سبک به اندپوینت داخلی انجام می‌شود؛ هیچ کتابخانه سنگینی
	 * بارگذاری نمی‌گردد.
	 *
	 * @param array<string, mixed> $settings تنظیمات.
	 * @return void
	 */
	function pixva_render_price_calculator( $settings = array() ) {
		$brands = function_exists( 'pixva_brand_catalog' ) ? (array) pixva_brand_catalog() : array();
		$sizes  = pixva_price_table_sizes();

		if ( empty( $brands ) || empty( $sizes ) ) {
			return;
		}

		$title    = isset( $settings['title'] ) && '' !== $settings['title'] ? $settings['title'] : (string) pixva_option( 'pixva_price_title', __( 'جدول شفاف هزینه تعمیر تلویزیون', 'pixva' ) );
		$subtitle = isset( $settings['subtitle'] ) && '' !== $settings['subtitle'] ? $settings['subtitle'] : (string) pixva_option(
			'pixva_price_subtitle',
			__( 'حدود قیمت قطعه فابریک و اجرت تخصصی بر اساس نرخ‌نامه رسمی کارگاه؛ مبلغ نهایی پس از عیب‌یابی در محل و با تأیید شما قطعی می‌شود.', 'pixva' )
		);

		// برند/سایز پیش‌فرض: تنظیم ویجت ← فیلتر ← سفارشی‌ساز ← اولین مقدار معتبر.
		$default_brand = isset( $settings['default_brand'] ) && '' !== $settings['default_brand']
			? (string) $settings['default_brand']
			: (string) pixva_option( 'pixva_price_default_brand', 'samsung' );
		$default_brand = (string) apply_filters( 'pixva_price_default_brand_override', $default_brand );
		if ( ! isset( $brands[ $default_brand ] ) ) {
			$default_brand = (string) key( $brands );
		}

		$default_size = isset( $settings['default_size'] ) && '' !== $settings['default_size']
			? (string) $settings['default_size']
			: (string) pixva_option( 'pixva_price_default_size', '55' );
		$default_size = (string) apply_filters( 'pixva_price_default_size_override', $default_size );
		if ( ! in_array( $default_size, $sizes, true ) ) {
			$default_size = (string) $sizes[0];
		}

		// فیلتر سریع از کوئری (لینک‌های داخلی راهنمای علائم).
		$query_brand = isset( $_GET['brand'] ) ? sanitize_key( wp_unslash( $_GET['brand'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$query_size  = isset( $_GET['size'] ) ? sanitize_key( wp_unslash( $_GET['size'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $brands[ $query_brand ] ) ) {
			$default_brand = $query_brand;
		}
		if ( in_array( $query_size, $sizes, true ) ) {
			$default_size = $query_size;
		}

		$rows     = pixva_price_table_matrix( $default_brand, $default_size );
		$brand_fa = isset( $brands[ $default_brand ]['fa'] ) ? $brands[ $default_brand ]['fa'] : $default_brand;
		$warranty = function_exists( 'pixva_warranty_days' ) ? (int) pixva_warranty_days() : 180;

		// اسکریپت سبک فیلتر جدول (بدون هیچ کتابخانه بیرونی).
		pixva_seo_cro_assets();
		?>
		<section class="pixva-seo pixva-pricetable" id="price-table"
			data-price-active-brand="<?php echo esc_attr( $default_brand ); ?>"
			data-price-active-size="<?php echo esc_attr( $default_size ); ?>">
			<div class="pixva-container">
				<header class="pixva-seo__head">
					<h2><?php echo esc_html( $title ); ?></h2>
					<p><?php echo esc_html( $subtitle ); ?></p>
				</header>

				<div class="pixva-pricetable__filters" data-price-filters>
					<label class="pixva-pricetable__filter">
						<span><?php esc_html_e( 'برند تلویزیون', 'pixva' ); ?></span>
						<select name="brand" data-price-brand>
							<?php foreach ( $brands as $key => $brand ) : ?>
								<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $default_brand, $key ); ?>>
									<?php echo esc_html( $brand['fa'] . ' (' . $brand['en'] . ')' ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</label>

					<label class="pixva-pricetable__filter">
						<span><?php esc_html_e( 'سایز صفحه', 'pixva' ); ?></span>
						<select name="size" data-price-size>
							<?php foreach ( $sizes as $size ) : ?>
								<option value="<?php echo esc_attr( $size ); ?>" <?php selected( $default_size, $size ); ?>>
									<?php echo esc_html( ( function_exists( 'pixva_fa_num' ) ? pixva_fa_num( $size ) : $size ) . ' ' . __( 'اینچ', 'pixva' ) ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</label>

					<?php
					/*
					 * فیلتر قرصی برند (لایه ۲٫۱٫۰): کلیک روی هر قرص همان
					 * <select data-price-brand> را به‌روز می‌کند تا بدون JS هم
					 * جدول سمت سرور درست بماند و رفتار فیلتر یکی باشد.
					 * ویژگی data-price-pill عمداً با data-price-brand فرق دارد
					 * تا انتخابگر option در JS دوباره دچار تداخل نشود.
					 */
					?>
					<div class="pixva-pricetable__pills" data-price-pills="brand" role="group" aria-label="<?php esc_attr_e( 'فیلتر سریع برند', 'pixva' ); ?>">
						<?php foreach ( $brands as $key => $brand ) : ?>
							<button
								type="button"
								class="px-pill<?php echo $default_brand === $key ? ' is-active' : ''; ?>"
								data-price-pill="<?php echo esc_attr( $key ); ?>"
								aria-pressed="<?php echo $default_brand === $key ? 'true' : 'false'; ?>">
								<?php echo esc_html( $brand['fa'] ); ?>
							</button>
						<?php endforeach; ?>
					</div>

					<p class="pixva-pricetable__live" data-price-status role="status" aria-live="polite"></p>
				</div>

				<div class="pixva-pricetable__wrap">
					<table class="pixva-pricetable__table" data-price-table>
						<caption>
							<?php
							echo esc_html(
								sprintf(
									/* translators: 1: برند، 2: سایز */
									__( 'نرخ‌نامه تعمیر تلویزیون %1$s — %2$s اینچ (تومان)', 'pixva' ),
									$brand_fa,
									function_exists( 'pixva_fa_num' ) ? pixva_fa_num( $default_size ) : $default_size
								)
							);
							?>
						</caption>
						<thead>
							<tr>
								<th scope="col"><?php esc_html_e( 'خدمت تعمیراتی', 'pixva' ); ?></th>
								<th scope="col"><?php esc_html_e( 'حداقل هزینه', 'pixva' ); ?></th>
								<th scope="col"><?php esc_html_e( 'حداکثر هزینه', 'pixva' ); ?></th>
								<th scope="col"><?php esc_html_e( 'زمان تحویل', 'pixva' ); ?></th>
							</tr>
						</thead>
						<tbody data-price-body>
							<?php pixva_price_table_rows_html( $rows ); ?>
						</tbody>
					</table>
				</div>

				<p class="pixva-pricetable__note">
					<?php
					echo esc_html(
						sprintf(
							/* translators: %s: روز */
							__( 'همه مبالغ شامل قطعه فابریک، اجرت تخصصی و %s روز گارانتی کتبی است؛ عیب‌یابی و برآورد قطعی پیش از شروع تعمیر اعلام می‌شود.', 'pixva' ),
							function_exists( 'pixva_fa_num' ) ? pixva_fa_num( (string) $warranty ) : $warranty
						)
					);
					?>
				</p>

				<div class="pixva-pricetable__actions">
					<a class="pixva-seo__cta" href="<?php echo esc_url( function_exists( 'pixva_page_url' ) ? pixva_page_url( 'calculator' ) : home_url( '/' ) ); ?>">
						<?php esc_html_e( 'استعلام دقیق قیمت با مدل دستگاه', 'pixva' ); ?>
					</a>
				</div>
			</div>

			<?php
			if ( function_exists( 'pixva_print_schema_graph' ) ) {
				pixva_print_schema_graph( pixva_price_table_schema( $default_brand, $default_size, $rows ) );
			}
			?>
		</section>
		<?php
	}
}

if ( ! function_exists( 'pixva_price_table_rows_html' ) ) {
	/**
	 * چاپ ردیف‌های جدول قیمت (مشترک بین رندر اولیه و پاسخ REST).
	 *
	 * @param array<int, array<string, mixed>> $rows ردیف‌ها.
	 * @return void
	 */
	function pixva_price_table_rows_html( $rows ) {
		foreach ( (array) $rows as $row ) {
			if ( ! empty( $row['panel_replacement'] ) || empty( $row['min'] ) ) {
				?>
				<tr data-service="<?php echo esc_attr( (string) $row['service'] ); ?>">
					<th scope="row"><?php echo esc_html( (string) $row['label'] ); ?></th>
					<td colspan="2" class="pixva-pricetable__quote">
						<?php esc_html_e( 'تعویض کامل پنل خارج از نرخ‌نامه است؛ پس از بازدید، امکان‌سنجی و قیمت اعلام می‌شود.', 'pixva' ); ?>
					</td>
					<td><?php echo esc_html( (string) $row['days'] ); ?></td>
				</tr>
				<?php
				continue;
			}
			?>
			<tr data-service="<?php echo esc_attr( (string) $row['service'] ); ?>">
				<th scope="row"><?php echo esc_html( (string) $row['label'] ); ?></th>
				<td data-min="<?php echo esc_attr( (string) $row['min'] ); ?>">
					<?php echo esc_html( pixva_price( (int) $row['min'] ) ); ?>
				</td>
				<td data-max="<?php echo esc_attr( (string) $row['max'] ); ?>">
					<?php echo esc_html( pixva_price( (int) $row['max'] ) ); ?>
				</td>
				<td><?php echo esc_html( (string) $row['days'] ); ?></td>
			</tr>
			<?php
		}
	}
}

if ( ! function_exists( 'pixva_rest_price_table' ) ) {
	/**
	 * اندپوینت جدول قیمت برای فیلتر سریع برند/سایز (سبک و بدون کتابخانه).
	 *
	 * @param WP_REST_Request $request درخواست.
	 * @return WP_REST_Response|WP_Error
	 */
	function pixva_rest_price_table( $request ) {
		$brand  = sanitize_key( (string) $request->get_param( 'brand' ) );
		$size   = sanitize_key( (string) $request->get_param( 'size' ) );
		$brands = function_exists( 'pixva_brand_catalog' ) ? (array) pixva_brand_catalog() : array();
		$sizes  = pixva_price_table_sizes();

		if ( ! isset( $brands[ $brand ] ) ) {
			return new WP_Error( 'pixva_price_brand', __( 'برند انتخاب‌شده در نرخ‌نامه نیست.', 'pixva' ), array( 'status' => 400 ) );
		}
		if ( ! in_array( $size, $sizes, true ) ) {
			return new WP_Error( 'pixva_price_size', __( 'سایز انتخاب‌شده در جدول قیمت نیست.', 'pixva' ), array( 'status' => 400 ) );
		}

		$rows = pixva_price_table_matrix( $brand, $size );

		$out_rows = array();
		foreach ( $rows as $row ) {
			$out_rows[] = array(
				'service'          => (string) $row['service'],
				'label'            => (string) $row['label'],
				'min'              => (int) $row['min'],
				'max'              => (int) $row['max'],
				'min_label'        => $row['min'] ? pixva_price( (int) $row['min'] ) : '',
				'max_label'        => $row['max'] ? pixva_price( (int) $row['max'] ) : '',
				'days'             => (string) $row['days'],
				'panel_replacement' => (bool) $row['panel_replacement'],
			);
		}

		$brand_label = isset( $brands[ $brand ]['fa'] ) ? $brands[ $brand ]['fa'] : $brand;
		$size_label  = function_exists( 'pixva_fa_num' ) ? pixva_fa_num( $size ) : $size;

		return rest_ensure_response(
			array(
				'success' => true,
				'data'    => array(
					'brand'       => $brand,
					'brand_label' => $brand_label,
					'size'        => $size,
					'currency'    => __( 'تومان', 'pixva' ),
					// برچسب‌های نمایشی ترجمه‌شده تا هیچ متنی در JS سخت‌کد نشود.
					'caption'     => sprintf(
						/* translators: 1: برند، 2: سایز */
						__( 'نرخ‌نامه تعمیر تلویزیون %1$s — %2$s اینچ (تومان)', 'pixva' ),
						$brand_label,
						$size_label
					),
					'status_label' => sprintf(
						/* translators: 1: برند، 2: سایز */
						__( '%1$s — %2$s اینچ', 'pixva' ),
						$brand_label,
						$size_label
					),
					'quote_label' => __( 'تعویض کامل پنل خارج از نرخ‌نامه است؛ پس از بازدید، امکان‌سنجی و قیمت اعلام می‌شود.', 'pixva' ),
					'rows'        => $out_rows,
					'schema'      => function_exists( 'pixva_price_table_schema' ) ? pixva_price_table_schema( $brand, $size, $rows ) : array(),
				),
			)
		);
	}
}

if ( ! function_exists( 'pixva_register_seo_cro_routes' ) ) {
	/**
	 * ثبت اندپوینت‌های ماژول سئو/تبدیل (جدول قیمت و فرم اعزام فوری).
	 *
	 * @return void
	 */
	function pixva_register_seo_cro_routes() {
		register_rest_route(
			'pixva/v1',
			'/price-table',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => 'pixva_rest_price_table',
				'permission_callback' => '__return_true',
				'args'                => array(
					'brand' => array(
						'required'          => true,
						'sanitize_callback' => 'sanitize_key',
					),
					'size'  => array(
						'required'          => true,
						'sanitize_callback' => 'sanitize_key',
					),
				),
			)
		);

		register_rest_route(
			'pixva/v1',
			'/express-booking',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => 'pixva_rest_express_booking',
				'permission_callback' => '__return_true',
				'args'                => array(
					'phone'   => array(
						'required'          => true,
						'sanitize_callback' => 'sanitize_text_field',
					),
					'details' => array(
						'required'          => true,
						'sanitize_callback' => 'sanitize_textarea_field',
					),
					'nonce'   => array(
						'required'          => true,
						'sanitize_callback' => 'sanitize_text_field',
					),
					'source'  => array(
						'default'           => 'home',
						'sanitize_callback' => 'sanitize_key',
					),
				),
			)
		);
	}
	add_action( 'rest_api_init', 'pixva_register_seo_cro_routes' );
}

/* ==========================================================================
 * ۴) ماژول ۳ — اعتمادپذیری و گارانتی واقعی (pixva_trust_features)
 * ======================================================================= */

if ( ! function_exists( 'pixva_trust_feature_items' ) ) {
	/**
	 * چهار اصل کلیدی اعتماد؛ مقادیر از داده واقعی سایت (گارانتی و ETA) می‌آیند.
	 *
	 * @return array<int, array<string, string>>
	 */
	function pixva_trust_feature_items() {
		$warranty = function_exists( 'pixva_warranty_days' ) ? (int) pixva_warranty_days() : 180;
		$months   = (int) round( $warranty / 30 );
		$months   = $months > 0 ? $months : 6;

		$control = function_exists( 'pixva_control_options' ) ? (array) pixva_control_options() : array();
		$hours   = isset( $control['hub_eta_hours'] ) && '' !== $control['hub_eta_hours'] ? (string) $control['hub_eta_hours'] : __( '۲ ساعت', 'pixva' );

		$items = array(
			array(
				'id'    => 'onsite',
				'icon'  => 'truck',
				'title' => __( 'تعمیر در منزل و محل شما', 'pixva' ),
				'text'  => __( 'تکنسین با ابزار کامل و قطعه پرمصرف به محل می‌آید؛ نیازی به حمل تلویزیون، بسته‌بندی و ریسک ضربه در مسیر نیست. تنها مواردی که به دستگاه بندینگ صنعتی نیاز دارد به کارگاه منتقل و با رسید کتبی تحویل می‌شود.', 'pixva' ),
			),
			array(
				'id'    => 'warranty',
				'icon'  => 'shield',
				'title' => sprintf(
					/* translators: %s: ماه */
					__( '%s ماه گارانتی کتبی قطعات فابریک', 'pixva' ),
					function_exists( 'pixva_fa_num' ) ? pixva_fa_num( (string) $months ) : $months
				),
				'text'  => __( 'گارانتی فقط شفاهی نیست: کارت گارانتی دیجیتال با سریال یکتا، هولوگرام اصالت و فاکتور رسمی صادر می‌شود و در طول دوره، تعویض قطعه معیوب بدون هزینه مجدد انجام می‌گیرد.', 'pixva' ),
			),
			array(
				'id'    => 'dispatch',
				'icon'  => 'bolt',
				'title' => sprintf(
					/* translators: %s: زمان اعزام */
					__( 'اعزام تکنسین در کمتر از %s', 'pixva' ),
					$hours
				),
				'text'  => __( 'پس از ثبت درخواست، نزدیک‌ترین تکنسین شیفت انتخاب و زمان مراجعه هماهنگ می‌شود؛ وضعیت پرونده در هر مرحله پیامک و آنلاین اعلام می‌گردد تا منتظر تماس بی‌پاسخ نمانید.', 'pixva' ),
			),
			array(
				'id'    => 'transparent',
				'icon'  => 'cert',
				'title' => __( 'عیب‌یابی و برآورد هزینه شفاف قبل از تعمیر', 'pixva' ),
				'text'  => __( 'پیش از باز کردن دستگاه، بازه قیمت اعلام می‌شود؛ پس از عیب‌یابی نیز تفکیک قطعه، اجرت و هزینه کارشناسی به شما نشان داده می‌شود و تعمیر فقط با تأیید کتبی یا پیامکی شما شروع می‌گردد.', 'pixva' ),
			),
		);

		/**
		 * فیلتر اصول اعتماد.
		 *
		 * @param array $items موارد.
		 */
		return apply_filters( 'pixva_trust_feature_items', $items );
	}
}

if ( ! function_exists( 'pixva_render_trust_features' ) ) {
	/**
	 * رندر بخش چهار اصل کلیدی اعتماد (سریع، خوانا، بدون انیمیشن سنگین).
	 *
	 * @param array<string, mixed> $settings تنظیمات.
	 * @return void
	 */
	function pixva_render_trust_features( $settings = array() ) {
		$items = pixva_trust_feature_items();
		if ( empty( $items ) ) {
			return;
		}

		$title    = isset( $settings['title'] ) && '' !== $settings['title'] ? $settings['title'] : (string) pixva_option( 'pixva_trust_title', __( 'چرا تعمیر تلویزیون را به پیکسوا بسپارید؟', 'pixva' ) );
		$subtitle = isset( $settings['subtitle'] ) && '' !== $settings['subtitle'] ? $settings['subtitle'] : (string) pixva_option(
			'pixva_trust_subtitle',
			__( 'چهار اصل که در هر پرونده تعمیر، از بازدید تا گارانتی، بدون استثنا رعایت می‌شود.', 'pixva' )
		);
		?>
		<section class="pixva-seo pixva-trust" id="trust-features">
			<div class="pixva-container">
				<header class="pixva-seo__head">
					<h2><?php echo esc_html( $title ); ?></h2>
					<p><?php echo esc_html( $subtitle ); ?></p>
				</header>

				<ul class="pixva-trust__grid">
					<?php foreach ( $items as $item ) : ?>
						<li class="pixva-trust__item" id="<?php echo esc_attr( $item['id'] ); ?>">
							<span class="pixva-trust__icon" aria-hidden="true">
								<?php echo pixva_icon( isset( $item['icon'] ) ? $item['icon'] : 'check' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							</span>
							<h3><?php echo esc_html( $item['title'] ); ?></h3>
							<p><?php echo esc_html( $item['text'] ); ?></p>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
		</section>
		<?php
	}
}

/* ==========================================================================
 * ۵) ماژول ۴ — فرم درخواست سریع یک‌مرحله‌ای (pixva_express_booking)
 * ======================================================================= */

if ( ! function_exists( 'pixva_render_express_booking' ) ) {
	/**
	 * رندر فرم دو فیلدی اعزام فوری تکنسین (۱۰۰٪ ریسپانسیو و بدون JS سنگین).
	 *
	 * @param array<string, mixed> $settings تنظیمات.
	 * @return void
	 */
	function pixva_render_express_booking( $settings = array() ) {
		/*
		 * وارینت card (لایه ۲٫۱٫۰): همان فرم دو فیلدی در قالب کارت elevated
		 * برای ستون کناری هیرو؛ شناسه بخش و منبع ثبت درخواست جدا می‌شود تا
		 * دو فرم در یک صفحه بدون تداخل id رندر شوند.
		 */
		$variant    = isset( $settings['variant'] ) ? (string) $settings['variant'] : '';
		$section_id = isset( $settings['section_id'] ) && '' !== $settings['section_id'] ? (string) $settings['section_id'] : 'express-booking';

		$title    = isset( $settings['title'] ) && '' !== $settings['title'] ? $settings['title'] : (string) pixva_option( 'pixva_express_title', __( 'اعزام فوری تکنسین تعمیر تلویزیون', 'pixva' ) );
		$subtitle = isset( $settings['subtitle'] ) && '' !== $settings['subtitle'] ? $settings['subtitle'] : (string) pixva_option(
			'pixva_express_subtitle',
			__( 'فقط شماره موبایل و شرح کوتاه مشکل را بنویسید؛ کارشناس برای هماهنگی مراجعه و اعلام برآورد هزینه تماس می‌گیرد.', 'pixva' )
		);
		$cta      = isset( $settings['cta_text'] ) && '' !== $settings['cta_text'] ? $settings['cta_text'] : __( 'ثبت درخواست اعزام فوری تکنسین', 'pixva' );

		$phone_label   = isset( $settings['phone_label'] ) && '' !== $settings['phone_label'] ? $settings['phone_label'] : __( 'شماره موبایل', 'pixva' );
		$details_label = isset( $settings['details_label'] ) && '' !== $settings['details_label'] ? $settings['details_label'] : __( 'برند و مشکل دستگاه', 'pixva' );

		$control = function_exists( 'pixva_control_options' ) ? (array) pixva_control_options() : array();
		$hours   = isset( $control['hub_eta_hours'] ) && '' !== $control['hub_eta_hours'] ? (string) $control['hub_eta_hours'] : __( '۲ ساعت', 'pixva' );

		/*
		 * انتخاب‌گر برند پویا (لایه ۳٫۰٫۰): در وارینت کارت هیرو پیش‌فرض روشن
		 * است و با settings['brand_selector'] در هر جای دیگر هم فعال می‌شود.
		 * اختیاری است؛ اگر پر نشود برند از متن آزاد تشخیص داده می‌شود.
		 */
		$show_brand   = 'card' === $variant || ! empty( $settings['brand_selector'] );
		$brands       = function_exists( 'pixva_brand_catalog' ) ? (array) pixva_brand_catalog() : array();
		$brand_label  = isset( $settings['brand_label'] ) && '' !== $settings['brand_label'] ? $settings['brand_label'] : (string) pixva_option( 'pixva_express_brand_label', __( 'برند تلویزیون', 'pixva' ) );

		pixva_seo_cro_assets();
		?>
		<section class="pixva-seo pixva-express<?php echo 'card' === $variant ? ' pixva-express--card' : ''; ?>" id="<?php echo esc_attr( $section_id ); ?>">
			<div class="pixva-container">
				<header class="pixva-seo__head">
					<h2><?php echo esc_html( $title ); ?></h2>
					<p><?php echo esc_html( $subtitle ); ?></p>
				</header>

				<form class="pixva-express__form" data-express-form novalidate>
					<div class="pixva-express__fields">
						<?php if ( $show_brand && ! empty( $brands ) ) : ?>
							<label class="pixva-express__field pixva-express__field--brand">
								<span><?php echo esc_html( $brand_label ); ?></span>
								<select name="brand" data-express-brand>
									<option value=""><?php esc_html_e( 'انتخاب کنید (اختیاری — از متن شرح هم تشخیص داده می‌شود)', 'pixva' ); ?></option>
									<?php foreach ( $brands as $brand_key => $brand ) : ?>
										<option value="<?php echo esc_attr( $brand_key ); ?>">
											<?php echo esc_html( isset( $brand['fa'] ) ? $brand['fa'] . ( isset( $brand['en'] ) ? ' (' . $brand['en'] . ')' : '' ) : $brand_key ); ?>
										</option>
									<?php endforeach; ?>
								</select>
							</label>
						<?php endif; ?>

						<label class="pixva-express__field">
							<span><?php echo esc_html( $phone_label ); ?> <em aria-hidden="true">*</em></span>
							<input
								type="tel"
								name="phone"
								dir="ltr"
								inputmode="numeric"
								autocomplete="tel"
								placeholder="09xxxxxxxxx"
								pattern="09[0-9]{9}"
								required
								data-express-required="phone">
						</label>

						<label class="pixva-express__field pixva-express__field--wide">
							<span><?php echo esc_html( $details_label ); ?> <em aria-hidden="true">*</em></span>
							<input
								type="text"
								name="details"
								autocomplete="off"
								placeholder="<?php echo esc_attr( __( 'مثلاً: سامسونگ ۵۵ اینچ — صدا دارد ولی تصویر ندارد', 'pixva' ) ); ?>"
								required
								data-express-required="details">
						</label>
					</div>

					<!-- تله ضدربات: ربات‌ها این فیلد را پر می‌کنند. -->
					<p class="pixva-express__hp" aria-hidden="true">
						<label>
							<span><?php esc_html_e( 'این فیلد را خالی بگذارید', 'pixva' ); ?></label>
							<input type="text" name="pixva_hp" value="" tabindex="-1" autocomplete="off">
						</label>
					</p>

					<input type="hidden" name="source" value="<?php echo esc_attr( isset( $settings['source'] ) ? $settings['source'] : 'home' ); ?>">
					<input type="hidden" name="nonce" value="<?php echo esc_attr( wp_create_nonce( 'pixva_express_booking' ) ); ?>" data-express-nonce>

					<div class="pixva-express__actions">
						<button type="submit" class="pixva-express__submit" data-express-submit>
							<?php echo esc_html( $cta ); ?>
						</button>
						<p class="pixva-express__hint">
							<?php
							echo esc_html(
								sprintf(
									/* translators: %s: زمان اعزام */
									__( 'اعزام تکنسین در کمتر از %s — عیب‌یابی و برآورد هزینه پیش از تعمیر.', 'pixva' ),
									$hours
								)
							);
							?>
						</p>
					</div>

					<p class="pixva-express__msg" data-express-msg role="status" aria-live="polite" hidden></p>
				</form>
			</div>
		</section>
		<?php
	}
}

if ( ! function_exists( 'pixva_express_detect_brand' ) ) {
	/**
	 * تشخیص برند از متن آزاد کاربر (فا یا لاتین) با کاتالوگ واقعی.
	 *
	 * @param string $text متن.
	 * @return string کلید برند یا رشته خالی.
	 */
	function pixva_express_detect_brand( $text ) {
		$text   = (string) $text;
		$brands = function_exists( 'pixva_brand_catalog' ) ? (array) pixva_brand_catalog() : array();

		foreach ( $brands as $key => $brand ) {
			$needles = array_filter(
				array(
					isset( $brand['fa'] ) ? (string) $brand['fa'] : '',
					isset( $brand['en'] ) ? (string) $brand['en'] : '',
				)
			);

			foreach ( $needles as $needle ) {
				if ( pixva_seo_stripos( $text, $needle ) ) {
					return (string) $key;
				}
			}
		}

		return '';
	}
}

if ( ! function_exists( 'pixva_express_detect_problem' ) ) {
	/**
	 * تشخیص نوع خرابی از متن آزاد کاربر با کاتالوگ علائم.
	 *
	 * @param string $text متن.
	 * @return string کلید مشکل یا رشته خالی.
	 */
	function pixva_express_detect_problem( $text ) {
		$text = (string) $text;

		$patterns = array(
			'no_picture' => array( 'تصویر ندارد', 'بی تصویر', 'بی‌تصویر', 'صدا دارد', 'صدا هست', 'no picture' ),
			'backlight'  => array( 'بک لایت', 'بک‌لایت', 'backlight', 'نور صفحه', 'تاریک است' ),
			'lines'      => array( 'خط عمودی', 'خطوط عمودی', 'خط افقی', 'خطوط افقی', 'خط انداخته', 'lines' ),
			'no_power'   => array( 'روشن نمی شود', 'روشن نمی‌شود', 'خاموش', 'چشمک', 'no power' ),
			'mainboard'  => array( 'لوگو', 'ریست', 'بالا نمی آید', 'بالا نمی‌آید', 'هنگ', 'mainboard' ),
			'no_sound'   => array( 'صدا ندارد', 'قطع صدا', 'no sound' ),
			'water'      => array( 'آب خورد', 'آب‌خورد', 'آب ریخته', 'water' ),
			'panel'      => array( 'شکسته', 'ضربه', 'ترک', 'panel' ),
		);

		foreach ( $patterns as $key => $needles ) {
			foreach ( (array) $needles as $needle ) {
				if ( pixva_seo_stripos( $text, $needle ) ) {
					return (string) $key;
				}
			}
		}

		return '';
	}
}

if ( ! function_exists( 'pixva_express_size_from_text' ) ) {
	/**
	 * بیرون‌کشیدن سایز اینچ از متن آزاد (مثلاً «سامسونگ ۵۵ اینچ»).
	 *
	 * @param string $text متن.
	 * @return string سایز یا رشته خالی.
	 */
	function pixva_express_size_from_text( $text ) {
		$text   = (string) $text;
		$sizes  = function_exists( 'pixva_price_table_sizes' ) ? pixva_price_table_sizes() : array( '32', '43', '50', '55', '65', '75' );
		$fa_map = array( '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4', '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9' );
		$latin  = strtr( $text, $fa_map );

		foreach ( $sizes as $size ) {
			if ( preg_match( '/(?<!\d)' . preg_quote( (string) $size, '/' ) . '(?!\d)/', $latin ) ) {
				return (string) $size;
			}
		}

		return '';
	}
}

if ( ! function_exists( 'pixva_express_booking_limited' ) ) {
	/**
	 * محدودیت ضداسپم فرم اعزام فوری (پیش‌فرض ۵ درخواست در ساعت برای هر IP).
	 *
	 * @return bool
	 */
	function pixva_express_booking_limited() {
		$max    = (int) apply_filters( 'pixva_express_hourly_max', 5 );
		$window = (int) apply_filters( 'pixva_express_window', HOUR_IN_SECONDS );

		$ip  = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : 'unknown';
		$key = 'pixva_express_' . md5( $ip . wp_salt( 'nonce' ) );
		$hit = (int) get_transient( $key );

		if ( $hit >= $max ) {
			return true;
		}

		set_transient( $key, $hit + 1, $window );
		return false;
	}
}

if ( ! function_exists( 'pixva_rest_express_booking' ) ) {
	/**
	 * ثبت سفارش از فرم یک‌مرحله‌ای + ارسال پیامک فوری به مشتری و مدیر.
	 *
	 * @param WP_REST_Request $request درخواست.
	 * @return WP_REST_Response|WP_Error
	 */
	function pixva_rest_express_booking( $request ) {
		if ( function_exists( 'pixva_host_raise_limits' ) ) {
			pixva_host_raise_limits( 120, '256M' );
		}

		// تله ضدربات.
		$honeypot = (string) $request->get_param( 'pixva_hp' );
		if ( '' !== trim( $honeypot ) ) {
			return new WP_Error( 'pixva_express_spam', __( 'ارسال ناموفق؛ لطفاً دوباره تلاش کنید.', 'pixva' ), array( 'status' => 400 ) );
		}

		$nonce = (string) $request->get_param( 'nonce' );
		if ( ! wp_verify_nonce( $nonce, 'pixva_express_booking' ) ) {
			return new WP_Error( 'pixva_express_nonce', __( 'صفحه را تازه‌سازی کنید و دوباره بفرستید (کلید امنیتی منقضی شده).', 'pixva' ), array( 'status' => 403 ) );
		}

		if ( pixva_express_booking_limited() ) {
			return new WP_Error(
				'pixva_express_limit',
				__( 'تعداد درخواست‌های ثبت‌شده از این اتصال زیاد است؛ لطفاً بعداً تماس بگیرید یا دوباره تلاش کنید.', 'pixva' ),
				array( 'status' => 429 )
			);
		}

		$raw_phone = (string) $request->get_param( 'phone' );
		$phone     = function_exists( 'pixva_normalize_mobile' ) ? pixva_normalize_mobile( $raw_phone ) : sanitize_text_field( $raw_phone );

		if ( '' === $phone ) {
			return new WP_Error( 'pixva_express_phone', __( 'شماره موبایل برای هماهنگی اعزام الزامی است.', 'pixva' ), array( 'status' => 400 ) );
		}

		$phone_ok = function_exists( 'pixva_is_valid_iranian_mobile' )
			? pixva_is_valid_iranian_mobile( $phone )
			: (bool) preg_match( '/^09[0-9]{9}$/', $phone );

		if ( ! $phone_ok ) {
			return new WP_Error( 'pixva_express_phone', __( 'شماره موبایل معتبر نیست؛ با ۰۹ و ۱۱ رقم وارد کنید (مثلاً ۰۹۱۲۱۱۱۱۱۱۱).', 'pixva' ), array( 'status' => 400 ) );
		}

		$details = trim( (string) $request->get_param( 'details' ) );
		if ( pixva_seo_strlen( $details ) < 3 ) {
			return new WP_Error( 'pixva_express_details', __( 'برند و مشکل دستگاه را کوتاه بنویسید تا قطعه صحیح همراه تکنسین اعزام شود.', 'pixva' ), array( 'status' => 400 ) );
		}

		$source   = sanitize_key( (string) $request->get_param( 'source' ) );
		$brands   = function_exists( 'pixva_brand_catalog' ) ? (array) pixva_brand_catalog() : array();

		// لایه ۳٫۰٫۰: برند صریحِ انتخاب‌شده در فرم بر تشخیص از متن آزاد اولویت دارد.
		$brand_param = sanitize_key( (string) $request->get_param( 'brand' ) );
		$brand       = ( '' !== $brand_param && isset( $brands[ $brand_param ] ) ) ? $brand_param : pixva_express_detect_brand( $details );
		$problem     = pixva_express_detect_problem( $details );
		$size        = pixva_express_size_from_text( $details );
		$problems = function_exists( 'pixva_problem_catalog' ) ? (array) pixva_problem_catalog() : array();

		$brand_label   = isset( $brands[ $brand ] ) ? $brands[ $brand ]['fa'] : __( 'برند نامشخص', 'pixva' );
		$problem_label = isset( $problems[ $problem ] ) ? $problems[ $problem ] : $details;

		// برآورد واقعی (اگر برند، سایز و نوع خرابی قابل تشخیص باشد).
		$estimate_text = __( 'برآورد پس از عیب‌یابی در محل', 'pixva' );
		$estimate      = null;
		if ( '' !== $brand && '' !== $problem && function_exists( 'pixva_calculate_estimate' ) ) {
			$estimate = pixva_calculate_estimate( $brand, 'led', '' !== $size ? $size : '55', $problem );
			if ( is_array( $estimate ) && ! empty( $estimate['min'] ) ) {
				$estimate_text = pixva_price( (int) $estimate['min'] ) . ' — ' . pixva_price( (int) $estimate['max'] ) . ' ' . __( 'تومان', 'pixva' );
			}
		}

		if ( ! function_exists( 'pixva_create_order' ) ) {
			return new WP_Error( 'pixva_express_engine', __( 'موتور ثبت پرونده در دسترس نیست؛ لطفاً تماس بگیرید.', 'pixva' ), array( 'status' => 500 ) );
		}

		$order_id = (int) pixva_create_order(
			array(
				'phone'    => $phone,
				'brand'    => $brand_label,
				'model'    => '' !== $size ? sprintf( /* translators: %s: سایز */ __( '%s اینچ', 'pixva' ), $size ) : '',
				'problem'  => $problem_label . ' — ' . $details,
				'estimate' => $estimate_text,
			)
		);

		if ( $order_id <= 0 ) {
			return new WP_Error( 'pixva_express_save', __( 'ثبت پرونده ناموفق بود؛ لطفاً دوباره تلاش کنید.', 'pixva' ), array( 'status' => 500 ) );
		}

		update_post_meta( $order_id, '_pixva_order_source', 'express_booking' !== $source ? $source : 'express-booking' );
		update_post_meta( $order_id, '_pixva_order_size', $size );
		update_post_meta( $order_id, '_pixva_order_problem_key', $problem );
		update_post_meta( $order_id, '_pixva_order_brand_key', $brand );

		$code = (string) get_post_meta( $order_id, '_pixva_order_code', true );
		$site = function_exists( 'pixva_option' ) ? (string) pixva_option( 'pixva_site_short_name', 'پیکسوا' ) : 'پیکسوا';

		// پیامک فوری به مشتری.
		if ( function_exists( 'pixva_sms_handler_send' ) ) {
			$customer_text = (string) pixva_option(
				'pixva_sms_express_text',
				sprintf(
					/* translators: %s: نام سایت */
					__( '%s: درخواست اعزام تکنسین ثبت شد. کد پیگیری {code}. تکنسین برای هماهنگی مراجعه با شما تماس می‌گیرد.', 'pixva' ),
					$site
				)
			);
			$customer_text = str_replace( '{code}', $code, $customer_text );
			pixva_sms_handler_send( $phone, $customer_text, 'express_customer', $code );

			// پیامک فوری به مدیر/تکنسین.
			$sms = function_exists( 'pixva_sms_handler_settings' ) ? pixva_sms_handler_settings() : array();
			if ( ! empty( $sms['admin_number'] ) ) {
				$admin_text = (string) pixva_option(
					'pixva_sms_express_admin_text',
					sprintf(
						/* translators: %s: نام سایت */
						__( '%s: درخواست اعزام فوری — {code} | {brand} | {problem} | {phone}', 'pixva' ),
						$site
					)
				);
				$admin_text = str_replace(
					array( '{code}', '{brand}', '{problem}', '{phone}', '{size}' ),
					array( $code, $brand_label, $problem_label, $phone, $size ),
					$admin_text
				);
				pixva_sms_handler_send( $sms['admin_number'], $admin_text, 'express_admin', $code );
			}
		}

		/**
		 * هوک پس از ثبت درخواست اعزام فوری.
		 *
		 * @param int    $order_id شناسه پرونده.
		 * @param string $phone    شماره مشتری.
		 * @param string $details  شرح کاربر.
		 */
		do_action( 'pixva_express_booking_saved', $order_id, $phone, $details );

		return rest_ensure_response(
			array(
				'success' => true,
				'data'    => array(
					'code'     => $code,
					'order_id' => $order_id,
					'brand'    => $brand_label,
					'problem'  => $problem_label,
					'estimate' => $estimate_text,
					'message'  => sprintf(
						/* translators: %s: کد پیگیری */
						__( 'درخواست شما با کد پیگیری %s ثبت شد. تکنسین به‌زودی برای هماهنگی مراجعه تماس می‌گیرد.', 'pixva' ),
						$code
					),
				),
			)
		);
	}
}

/* ==========================================================================
 * ۶) دارایی‌های ماژول (یک فایل JS بسیار سبک + CSS خالص)
 * ======================================================================= */

if ( ! function_exists( 'pixva_seo_cro_assets' ) ) {
	/**
	 * صف‌گذاری اسکریپت سبک ماژول سئو/تبدیل (بدون هیچ کتابخانه بیرونی).
	 *
	 * @return void
	 */
	function pixva_seo_cro_assets() {
		if ( is_admin() ) {
			return;
		}

		if ( ! wp_script_is( 'pixva-seo-cro', 'registered' ) ) {
			wp_register_script(
				'pixva-seo-cro',
				PIXVA_URI . '/assets/js/seo-cro.js',
				array(),
				PIXVA_VERSION,
				array(
					'in_footer' => true,
					'strategy'  => 'defer',
				)
			);
		}

		wp_localize_script(
			'pixva-seo-cro',
			'pixvaSeoCro',
			array(
				'restUrl'   => esc_url_raw( rest_url( 'pixva/v1' ) ),
				'nonce'     => wp_create_nonce( 'pixva_express_booking' ),
				'homeUrl'   => home_url( '/' ),
				'i18n'      => array(
					'needPhone'   => __( 'برای هماهنگی اعزام، شماره موبایل الزامی است.', 'pixva' ),
					'badPhone'    => __( 'شماره موبایل معتبر نیست (مثال: ۰۹۱۲۱۲۳۴۵۶۷).', 'pixva' ),
					'needDetails' => __( 'برند و مشکل دستگاه را کوتاه بنویسید.', 'pixva' ),
					'sending'     => __( 'در حال ثبت درخواست…', 'pixva' ),
					'updating'    => __( 'در حال به‌روزرسانی جدول قیمت…', 'pixva' ),
					'error'       => __( 'خطا در ارتباط با سرور؛ دوباره تلاش کنید.', 'pixva' ),
					'toman'       => __( 'تومان', 'pixva' ),
					'panelQuote'  => __( 'تعویض کامل پنل خارج از نرخ‌نامه است؛ پس از بازدید، امکان‌سنجی و قیمت اعلام می‌شود.', 'pixva' ),
				),
			)
		);

		wp_enqueue_script( 'pixva-seo-cro' );

		if ( wp_style_is( 'pixva-2026', 'registered' ) ) {
			wp_enqueue_style( 'pixva-2026' );
		}

		// لایه ۲٫۱٫۰: سامانه طراحی سازمانی هم در همان مسیرهای اضطراری صف می‌شود.
		if ( wp_style_is( 'pixva-seo-cro', 'registered' ) && ( ! function_exists( 'pixva_corporate_ui_mode' ) || pixva_corporate_ui_mode() ) ) {
			wp_enqueue_style( 'pixva-seo-cro' );
		}
	}
}

if ( ! function_exists( 'pixva_seo_cro_needed' ) ) {
	/**
	 * آیا صفحه جاری به ماژول سئو/تبدیل نیاز دارد؟
	 *
	 * @return bool
	 */
	function pixva_seo_cro_needed() {
		if ( is_admin() ) {
			return false;
		}

		if ( function_exists( 'is_front_page' ) && is_front_page() ) {
			return true;
		}

		if ( ! function_exists( 'pixva_cinematic_content_markers' ) ) {
			return false;
		}

		$content = (string) pixva_cinematic_content_markers();

		foreach ( array( 'pixva_symptom_guide', 'pixva_price_calculator', 'pixva_trust_features', 'pixva_express_booking', 'data-express-form', 'data-price-filters' ) as $marker ) {
			if ( false !== strpos( $content, $marker ) ) {
				return true;
			}
		}

		return false;
	}
}

if ( ! function_exists( 'pixva_seo_cro_enqueue' ) ) {
	/**
	 * صف‌گذاری مشروط دارایی ماژول فقط برای صفحه‌های نیازمند.
	 *
	 * @return void
	 */
	function pixva_seo_cro_enqueue() {
		if ( pixva_seo_cro_needed() ) {
			pixva_seo_cro_assets();
		}
	}
	add_action( 'wp_enqueue_scripts', 'pixva_seo_cro_enqueue', 21 );
}

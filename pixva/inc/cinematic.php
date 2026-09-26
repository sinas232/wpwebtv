<?php
/**
 * موتور سینمایی لایه ۱٫۶٫۰ — اسکرول سینمایی، رندر سه‌بعدی و ویجت‌های Elementor.
 *
 * مسئولیت‌ها:
 *  ۱) تشخیص اینکه صفحه جاری به کدام کتابخانه سنگین نیاز دارد (GSAP/ScrollTrigger،
 *     Spline WebGL، Leaflet، موتور عیب‌یاب هوشمند) و صف‌گذاری مشروط آن‌ها با defer.
 *  ۲) انتخاب منبع کتابخانه: فایل vendor داخل قالب ← آدرس CDN از سفارشی‌ساز ←
 *     موتور سبک داخلی (pixva-gsap-lite) تا قالب بدون وابستگی بیرونی کار کند.
 *  ۳) توابع رندر مشترک بین ویجت المنتور و شورت‌کد (یک منبع برای مارک‌آپ).
 *
 * @package Pixva
 * @since   1.6.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* --------------------------------------------------------------------------
 * ۱) منبع کتابخانه‌ها
 * ----------------------------------------------------------------------- */

if ( ! function_exists( 'pixva_cinematic_vendor' ) ) {
	/**
	 * آیا فایل vendor کتابخانه داخل قالب وجود دارد؟
	 *
	 * مدیر سایت می‌تواند نسخه رسمی GSAP یا Leaflet را در
	 * pixva/assets/js/vendor/ (و pixva/assets/css/vendor/) قرار دهد تا به‌جای
	 * موتور سبک داخلی بارگذاری شود.
	 *
	 * @param string $file نام فایل نسبت به پوشه قالب.
	 * @return bool
	 */
	function pixva_cinematic_vendor( $file ) {
		$path = PIXVA_DIR . '/' . ltrim( $file, '/' );
		return is_readable( $path ) && filesize( $path ) > 0;
	}
}

if ( ! function_exists( 'pixva_cinematic_libs' ) ) {
	/**
	 * منبع هر کتابخانه: vendor | cdn | builtin | none.
	 *
	 * @return array<string, array{source:string, url:string}>
	 */
	function pixva_cinematic_libs() {
		$libs = array();

		// GSAP هسته.
		if ( pixva_cinematic_vendor( 'assets/js/vendor/gsap.min.js' ) ) {
			$libs['gsap'] = array( 'source' => 'vendor', 'url' => PIXVA_URI . '/assets/js/vendor/gsap.min.js' );
		} else {
			$cdn = trim( (string) pixva_option( 'pixva_cdn_gsap', '' ) );
			$libs['gsap'] = '' !== $cdn
				? array( 'source' => 'cdn', 'url' => $cdn )
				: array( 'source' => 'builtin', 'url' => PIXVA_URI . '/assets/js/vendor/pixva-gsap-lite.js' );
		}

		// ScrollTrigger.
		if ( pixva_cinematic_vendor( 'assets/js/vendor/ScrollTrigger.min.js' ) ) {
			$libs['scrolltrigger'] = array( 'source' => 'vendor', 'url' => PIXVA_URI . '/assets/js/vendor/ScrollTrigger.min.js' );
		} else {
			$cdn = trim( (string) pixva_option( 'pixva_cdn_scrolltrigger', '' ) );
			$libs['scrolltrigger'] = '' !== $cdn
				? array( 'source' => 'cdn', 'url' => $cdn )
				: array( 'source' => 'builtin', 'url' => '' );
		}

		// Leaflet (اختیاری؛ در نبود آن، نقشه داخلی SVG دارک رندر می‌شود).
		if ( pixva_cinematic_vendor( 'assets/js/vendor/leaflet.js' ) ) {
			$libs['leaflet'] = array( 'source' => 'vendor', 'url' => PIXVA_URI . '/assets/js/vendor/leaflet.js' );
		} else {
			$cdn = trim( (string) pixva_option( 'pixva_cdn_leaflet', '' ) );
			$libs['leaflet'] = '' !== $cdn
				? array( 'source' => 'cdn', 'url' => $cdn )
				: array( 'source' => 'builtin', 'url' => '' );
		}

		// Spline Viewer همیشه از CDN رسمی (ماژول ES) و فقط هنگام نیاز لود می‌شود.
		$spline = trim( (string) pixva_option( 'pixva_cdn_spline', 'https://unpkg.com/@splinetool/viewer@1.9.48/build/spline-viewer.js' ) );
		$libs['spline'] = array( 'source' => '' !== $spline ? 'cdn' : 'none', 'url' => $spline );

		/**
		 * فیلتر منبع کتابخانه‌های سینمایی.
		 *
		 * @param array $libs کتابخانه‌ها.
		 */
		return apply_filters( 'pixva_cinematic_libs', $libs );
	}
}

if ( ! function_exists( 'pixva_cinematic_leaflet_css' ) ) {
	/**
	 * آدرس سبک Leaflet (vendor یا CDN).
	 *
	 * @return string
	 */
	function pixva_cinematic_leaflet_css() {
		if ( pixva_cinematic_vendor( 'assets/css/vendor/leaflet.css' ) ) {
			return PIXVA_URI . '/assets/css/vendor/leaflet.css';
		}

		return trim( (string) pixva_option( 'pixva_cdn_leaflet_css', '' ) );
	}
}

/* --------------------------------------------------------------------------
 * ۲) تشخیص نیاز صفحه (بارگذاری مشروط — قانون زیر ۲ ثانیه)
 * ----------------------------------------------------------------------- */

if ( ! function_exists( 'pixva_cinematic_content_markers' ) ) {
	/**
	 * مارکرهای موجود در محتوای صفحه جاری (نوشته + JSON المنتور).
	 *
	 * @return string
	 */
	function pixva_cinematic_content_markers() {
		static $content = null;

		if ( null !== $content ) {
			return $content;
		}

		$content = '';
		if ( is_singular() ) {
			$post = get_post();
			if ( $post instanceof WP_Post ) {
				$content = (string) $post->post_content;
			}
		}

		return $content;
	}
}

if ( ! function_exists( 'pixva_cinematic_has_marker' ) ) {
	/**
	 * آیا یکی از مارکرها در محتوای صفحه جاری هست؟
	 *
	 * @param array<int, string> $markers مارکرها.
	 * @return bool
	 */
	function pixva_cinematic_has_marker( $markers ) {
		$content = pixva_cinematic_content_markers();
		if ( '' === $content ) {
			return false;
		}

		foreach ( $markers as $marker ) {
			if ( false !== strpos( $content, $marker ) ) {
				return true;
			}
		}

		return false;
	}
}

if ( ! function_exists( 'pixva_needs_cinematic_js' ) ) {
	/**
	 * آیا صفحه به موتور اسکرول سینمایی (GSAP/ScrollTrigger) نیاز دارد؟
	 *
	 * @return bool
	 */
	function pixva_needs_cinematic_js() {
		if ( is_admin() ) {
			return false;
		}

		$needed = pixva_cinematic_has_marker(
			array(
				'pixva_cinematic_unboxing',
				'elementor-widget-pixva_cinematic_unboxing',
				'data-pixva-cine',
			)
		);

		/**
		 * فیلتر بارگذاری موتور سینمایی.
		 *
		 * @param bool $needed نیاز به اسکریپت.
		 */
		return (bool) apply_filters( 'pixva_needs_cinematic_js', $needed );
	}
}

if ( ! function_exists( 'pixva_needs_spline_js' ) ) {
	/**
	 * آیا صفحه به رندر سه‌بعدی Spline نیاز دارد؟
	 *
	 * @return bool
	 */
	function pixva_needs_spline_js() {
		if ( is_admin() ) {
			return false;
		}

		$needed = pixva_cinematic_has_marker(
			array(
				'pixva_3d_repair',
				'elementor-widget-pixva_3d_repair',
				'data-pixva-spline',
			)
		);

		/**
		 * فیلتر بارگذاری Spline Viewer.
		 *
		 * @param bool $needed نیاز به اسکریپت.
		 */
		return (bool) apply_filters( 'pixva_needs_spline_js', $needed );
	}
}

if ( ! function_exists( 'pixva_needs_ai_diagnose_js' ) ) {
	/**
	 * آیا صفحه به ویجت عیب‌یاب هوشمند نیاز دارد؟
	 *
	 * @return bool
	 */
	function pixva_needs_ai_diagnose_js() {
		if ( is_admin() ) {
			return false;
		}

		$needed = pixva_cinematic_has_marker(
			array(
				'pixva_ai_diagnose',
				'elementor-widget-pixva_ai_diagnose',
				'data-pixva-ai-diagnose',
			)
		);

		/**
		 * فیلتر بارگذاری موتور عیب‌یاب هوشمند.
		 *
		 * @param bool $needed نیاز به اسکریپت.
		 */
		return (bool) apply_filters( 'pixva_needs_ai_diagnose_js', $needed );
	}
}

if ( ! function_exists( 'pixva_needs_tracker_map_js' ) ) {
	/**
	 * آیا صفحه به ماژول نقشه زنده تعمیرکار نیاز دارد؟
	 *
	 * @return bool
	 */
	function pixva_needs_tracker_map_js() {
		if ( is_admin() ) {
			return false;
		}

		$needed = ( pixva_option( 'pixva_map_on_tracking', true ) && is_page_template(
			array(
				'page-templates/page-tracking.php',
				'page-templates/page-hub-tracking.php',
			)
		) ) || pixva_cinematic_has_marker(
			array(
				'pixva_tech_tracker',
				'pixva_technician_tracker',
				'elementor-widget-pixva_tech_tracker',
				'data-pixva-tracker',
			)
		);

		/**
		 * فیلتر بارگذاری ماژول نقشه.
		 *
		 * @param bool $needed نیاز به اسکریپت.
		 */
		return (bool) apply_filters( 'pixva_needs_tracker_map_js', $needed );
	}
}

/* --------------------------------------------------------------------------
 * ۳) صف‌گذاری مشروط دارایی‌ها
 * ----------------------------------------------------------------------- */

if ( ! function_exists( 'pixva_cinematic_register_assets' ) ) {
	/**
	 * ثبت (نه صف‌گذاری) دارایی‌های سینمایی.
	 *
	 * ثبت همیشه انجام می‌شود تا ویجت‌های المنتور بتوانند با
	 * get_script_depends() آن‌ها را در ویرایشگر و فرانت‌اند درخواست دهند؛
	 * خودِ صف‌گذاری در pixva_cinematic_assets() فقط برای صفحه‌های نیازمند است.
	 *
	 * @return void
	 */
	function pixva_cinematic_register_assets() {
		if ( is_admin() ) {
			return;
		}

		$libs = pixva_cinematic_libs();

		wp_register_script(
			'pixva-gsap',
			$libs['gsap']['url'],
			array(),
			'builtin' === $libs['gsap']['source'] ? PIXVA_VERSION : '3.13.0',
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);

		if ( '' !== $libs['scrolltrigger']['url'] ) {
			wp_register_script(
				'pixva-scrolltrigger',
				$libs['scrolltrigger']['url'],
				array( 'pixva-gsap' ),
				'builtin' === $libs['scrolltrigger']['source'] ? PIXVA_VERSION : '3.13.0',
				array(
					'in_footer' => true,
					'strategy'  => 'defer',
				)
			);
		}

		wp_register_script(
			'pixva-cinematic',
			PIXVA_URI . '/assets/js/cinematic.js',
			array( 'pixva-gsap', 'pixva-main' ),
			PIXVA_VERSION,
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);

		wp_localize_script(
			'pixva-cinematic',
			'pixvaCine',
			array(
				'gsapLite' => 'builtin' === $libs['gsap']['source'],
				'i18n'     => array(
					'quote'   => esc_html__( 'استعلام قیمت این قطعه', 'pixva' ),
					'loading' => esc_html__( 'در حال بارگذاری مدل سه‌بعدی…', 'pixva' ),
					'failed'  => esc_html__( 'مدل سه‌بعدی بارگذاری نشد؛ نمای لایه‌ای جایگزین فعال است.', 'pixva' ),
				),
			)
		);

		wp_register_script(
			'pixva-spline',
			PIXVA_URI . '/assets/js/spline-3d.js',
			array( 'pixva-main' ),
			PIXVA_VERSION,
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);

		wp_localize_script(
			'pixva-spline',
			'pixvaSpline',
			array(
				'viewerUrl' => $libs['spline']['url'],
				'calcUrl'   => function_exists( 'pixva_page_url' ) ? pixva_page_url( 'calculator' ) : home_url( '/' ),
				'i18n'      => array(
					'loading' => esc_html__( 'در حال بارگذاری مدل سه‌بعدی…', 'pixva' ),
					'failed'  => esc_html__( 'مدل سه‌بعدی در دسترس نیست؛ نمای لایه‌ای داخلی نمایش داده می‌شود.', 'pixva' ),
					'quote'   => esc_html__( 'استعلام قیمت این قطعه', 'pixva' ),
					'close'   => esc_html__( 'بستن', 'pixva' ),
				),
			)
		);

		wp_register_script(
			'pixva-ai-diagnose',
			PIXVA_URI . '/assets/js/ai-diagnose.js',
			array( 'pixva-main' ),
			PIXVA_VERSION,
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);

		wp_localize_script(
			'pixva-ai-diagnose',
			'pixvaAiDiagnose',
			array(
				'restUrl' => esc_url_raw( rest_url( 'pixva/v1' ) ),
				'nonce'   => wp_create_nonce( 'pixva_ai_diagnose' ),
				'maxSize' => (int) apply_filters( 'pixva_ai_diagnose_max_size', (int) pixva_option( 'pixva_ai_max_size', 64 ) ),
				'logs'    => array(
					esc_html__( 'دریافت بسته رسانه…', 'pixva' ),
					esc_html__( 'استخراج فریم‌های کلیدی…', 'pixva' ),
					esc_html__( 'تحلیل طیف صدا و نویز…', 'pixva' ),
					esc_html__( 'تطبیق با پایگاه کدهای خطا…', 'pixva' ),
					esc_html__( 'شناسایی قطعه مشکوک…', 'pixva' ),
					esc_html__( 'برآورد هزینه و زمان تعمیر…', 'pixva' ),
				),
				'i18n'    => array(
					'recording' => esc_html__( 'در حال ضبط صدا…', 'pixva' ),
					'stop'      => esc_html__( 'توقف ضبط', 'pixva' ),
					'analyzing' => esc_html__( 'در حال تحلیل داده…', 'pixva' ),
					'uploading' => esc_html__( 'در حال ارسال فایل رسانه…', 'pixva' ),
					'tooLarge'  => esc_html__( 'حجم فایل بیش از حد مجاز است.', 'pixva' ),
					'badType'   => esc_html__( 'فقط فایل ویدیو یا صدا پذیرفته می‌شود.', 'pixva' ),
					'needMedia' => esc_html__( 'ابتدا یک ویدیو یا صدای دستگاه را اضافه کنید.', 'pixva' ),
					'noMic'     => esc_html__( 'مرورگر شما از ضبط صدا پشتیبانی نمی‌کند.', 'pixva' ),
					'error'     => esc_html__( 'خطا در ارتباط با سرور؛ دوباره تلاش کنید.', 'pixva' ),
					'done'      => esc_html__( 'تحلیل ثبت شد', 'pixva' ),
					'smart'     => esc_html__( 'تحلیل هوشمند', 'pixva' ),
					'audioName' => esc_html__( 'ضبط صدا', 'pixva' ),
					'videoName' => esc_html__( 'ویدیوی خرابی', 'pixva' ),
					'audioKind' => esc_html__( 'صدا', 'pixva' ),
					'videoKind' => esc_html__( 'ویدیو', 'pixva' ),
					'ticket'    => esc_html__( 'کد پیگیری درخواست', 'pixva' ),
				),
			)
		);

		$leaflet_css = pixva_cinematic_leaflet_css();
		if ( '' !== $leaflet_css ) {
			wp_register_style( 'pixva-leaflet', $leaflet_css, array(), '1.9.4' );
		}
		if ( '' !== $libs['leaflet']['url'] ) {
			wp_register_script(
				'pixva-leaflet',
				$libs['leaflet']['url'],
				array(),
				'1.9.4',
				array(
					'in_footer' => true,
					'strategy'  => 'defer',
				)
			);
		}

		wp_register_script(
			'pixva-tracker-map',
			PIXVA_URI . '/assets/js/tracker-map.js',
			array( 'pixva-main' ),
			PIXVA_VERSION,
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);

		wp_localize_script(
			'pixva-tracker-map',
			'pixvaTrackerMap',
			array(
				'restUrl'     => esc_url_raw( rest_url( 'pixva/v1' ) ),
				'nonce'       => wp_create_nonce( 'wp_rest' ),
				'provider'    => (string) pixva_option( 'pixva_map_provider', 'auto' ),
				'tiles'       => (string) pixva_option( 'pixva_map_tiles', 'https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png' ),
				'attribution' => (string) pixva_option( 'pixva_map_attribution', '© OpenStreetMap contributors © CARTO' ),
				'hasLeaflet'  => '' !== $libs['leaflet']['url'],
				'i18n'        => array(
					'moving'        => esc_html__( 'در حال حرکت به سمت شما', 'pixva' ),
					'eta'           => esc_html__( 'زمان تقریبی رسیدن', 'pixva' ),
					'minutes'       => esc_html__( 'دقیقه', 'pixva' ),
					'tech'          => esc_html__( 'تعمیرکار', 'pixva' ),
					'live'          => esc_html__( 'زنده', 'pixva' ),
					'waiting'       => esc_html__( 'در انتظار تخصیص تعمیرکار', 'pixva' ),
					'error'         => esc_html__( 'اطلاعات موقعیت در دسترس نیست.', 'pixva' ),
					'simulated'     => esc_html__( 'شبیه‌سازی مسیر', 'pixva' ),
					'simulatedNote' => esc_html__( 'نقشه داخلی پیکسوا (بدون سرویس بیرونی)؛ تا اتصال GPS واقعی، مسیر شبیه‌سازی می‌شود.', 'pixva' ),
					'needPhone'     => esc_html__( 'برای نمایش موقعیت زنده، شماره همراه ثبت‌شده روی پرونده لازم است.', 'pixva' ),
				),
			)
		);
	}
	add_action( 'wp_enqueue_scripts', 'pixva_cinematic_register_assets', 15 );
}

if ( ! function_exists( 'pixva_cinematic_assets' ) ) {
	/**
	 * صف‌گذاری اسکریپت‌های سینمایی فقط برای صفحه‌هایی که ویجت دارند.
	 *
	 * @return void
	 */
	function pixva_cinematic_assets() {
		if ( is_admin() ) {
			return;
		}

		$libs = pixva_cinematic_libs();

		if ( pixva_needs_cinematic_js() || pixva_needs_spline_js() ) {
			wp_enqueue_script( 'pixva-gsap' );
			if ( '' !== $libs['scrolltrigger']['url'] ) {
				wp_enqueue_script( 'pixva-scrolltrigger' );
			}
		}

		if ( pixva_needs_cinematic_js() ) {
			wp_enqueue_script( 'pixva-cinematic' );
		}

		if ( pixva_needs_spline_js() ) {
			wp_enqueue_script( 'pixva-spline' );
		}

		if ( pixva_needs_ai_diagnose_js() ) {
			wp_enqueue_script( 'pixva-ai-diagnose' );
		}

		if ( pixva_needs_tracker_map_js() ) {
			if ( wp_style_is( 'pixva-leaflet', 'registered' ) ) {
				wp_enqueue_style( 'pixva-leaflet' );
			}
			if ( wp_script_is( 'pixva-leaflet', 'registered' ) ) {
				wp_enqueue_script( 'pixva-leaflet' );
			}
			wp_enqueue_script( 'pixva-tracker-map' );
		}
	}
	add_action( 'wp_enqueue_scripts', 'pixva_cinematic_assets', 20 );
}

if ( ! function_exists( 'pixva_enqueue_cinematic_assets' ) ) {
	/**
	 * صف‌گذاری مستقیم دارایی‌های یک ماژول (برای شورت‌کدها و فراخوانی‌های پویا).
	 *
	 * چون شورت‌کدها پس از `wp_enqueue_scripts` رندر می‌شوند، این تابع همان شرط‌ها
	 * را دوباره اعمال می‌کند تا اسکریپت فقط برای ماژول درخواستی صف شود.
	 *
	 * @param array<int, string> $modules ماژول‌ها: cinematic|spline|ai|tracker.
	 * @return void
	 */
	function pixva_enqueue_cinematic_assets( $modules = array() ) {
		if ( is_admin() || empty( $modules ) || ! is_array( $modules ) ) {
			return;
		}

		$libs = pixva_cinematic_libs();

		if ( in_array( 'cinematic', $modules, true ) || in_array( 'spline', $modules, true ) ) {
			wp_enqueue_script( 'pixva-gsap' );
			if ( '' !== $libs['scrolltrigger']['url'] ) {
				wp_enqueue_script( 'pixva-scrolltrigger' );
			}
		}

		if ( in_array( 'cinematic', $modules, true ) ) {
			wp_enqueue_script( 'pixva-cinematic' );
		}

		if ( in_array( 'spline', $modules, true ) ) {
			wp_enqueue_script( 'pixva-spline' );
		}

		if ( in_array( 'ai', $modules, true ) ) {
			wp_enqueue_script( 'pixva-ai-diagnose' );
		}

		if ( in_array( 'tracker', $modules, true ) ) {
			if ( wp_style_is( 'pixva-leaflet', 'registered' ) ) {
				wp_enqueue_style( 'pixva-leaflet' );
			}
			if ( wp_script_is( 'pixva-leaflet', 'registered' ) ) {
				wp_enqueue_script( 'pixva-leaflet' );
			}
			wp_enqueue_script( 'pixva-tracker-map' );
		}
	}
}

if ( ! function_exists( 'pixva_shortcode_json' ) ) {
	/**
	 * خواندن آرایه از مشخصه JSON شورت‌کد (لایه‌ها/هات‌اسپیت‌ها).
	 *
	 * در ویرایشگر متن، کوتیشن‌ها اغلب به‌صورت `&#039;` یا `&quot;` ذخیره می‌شوند؛
	 * بنابراین پیش از رمزگشایی، موجودیت‌های HTML ساده بازگردانی می‌شوند.
	 *
	 * @param string $raw مقدار خام.
	 * @return array<int|string, mixed>
	 */
	function pixva_shortcode_json( $raw ) {
		$raw = trim( (string) $raw );
		if ( '' === $raw ) {
			return array();
		}

		$decoded = json_decode( $raw, true );
		if ( ! is_array( $decoded ) ) {
			$decoded = json_decode( wp_specialchars_decode( $raw, ENT_QUOTES ), true );
		}

		return is_array( $decoded ) ? $decoded : array();
	}
}

/* --------------------------------------------------------------------------
 * ۴) رندر مشترک: اسکرول سینمایی (Cinematic TV Unboxing)
 * ----------------------------------------------------------------------- */

if ( ! function_exists( 'pixva_cinematic_default_layers' ) ) {
	/**
	 * لایه‌های پیش‌فرض نمای انفجاری (پنل، بک‌لایت، برد اصلی).
	 *
	 * @return array<int, array<string, mixed>>
	 */
	function pixva_cinematic_default_layers() {
		$layers = array(
			array(
				'image' => PIXVA_URI . '/assets/images/panel-after.jpg',
				'title' => __( 'پنل و شیشه نمایشگر', 'pixva' ),
				'text'  => __( 'بندینگ COF و ترمیم خطوط عمودی/افقی بدون تعویض شیشه، با دستگاه بندینگ صنعتی و تست الگوی کالیبراسیون.', 'pixva' ),
				'link'  => 'panel',
				'depth' => 3,
			),
			array(
				'image' => PIXVA_URI . '/assets/images/bonding-lab.jpg',
				'title' => __( 'ریسه‌های بک‌لایت', 'pixva' ),
				'text'  => __( 'تعویض نوار LED فابریک، رفع تاریکی موضعی و هاله نور با تست یکنواختی نور پس‌زمینه.', 'pixva' ),
				'link'  => 'backlight',
				'depth' => 2,
			),
			array(
				'image' => PIXVA_URI . '/assets/images/hero-workshop.jpg',
				'title' => __( 'برد اصلی و تغذیه', 'pixva' ),
				'text'  => __( 'عیب‌یابی مین‌برد، HDMI، وای‌فای و برد پاور با اسیلوسکوپ، پروگرامر و تست بار.', 'pixva' ),
				'link'  => 'mainboard',
				'depth' => 1,
			),
		);

		/**
		 * فیلتر لایه‌های پیش‌فرض ویجت سینمایی.
		 *
		 * @param array $layers لایه‌ها.
		 */
		return apply_filters( 'pixva_cinematic_default_layers', $layers );
	}
}

if ( ! function_exists( 'pixva_render_cinematic_unboxing' ) ) {
	/**
	 * رندر ویجت اسکرول سینمایی (قفل در مرکز + نمای انفجاری سه‌بعدی).
	 *
	 * @param array $settings تنظیمات ویجت/شورت‌کد.
	 * @return void
	 */
	function pixva_render_cinematic_unboxing( $settings = array() ) {
		$settings = wp_parse_args(
			$settings,
			array(
				'badge'     => __( 'کالبدشکافی زنده', 'pixva' ),
				'title'     => __( 'داخل یک تلویزیون چه می‌گذرد؟', 'pixva' ),
				'subtitle'  => __( 'با اسکرول، لایه‌های دستگاه از هم باز می‌شوند و خدمت تعمیر هر قطعه کنارش ظاهر می‌شود.', 'pixva' ),
				'layers'    => array(),
				'height'    => 320,
				'speed'     => 1,
				'spread'    => 120,
				'rotate'    => 16,
				'pin'       => true,
				'scrub'     => true,
				'neon'      => 'rgb(34, 211, 238)',
				'neon2'     => 'rgb(168, 85, 247)',
				'cta_text'  => __( 'برآورد هزینه این خدمت', 'pixva' ),
				'note'      => __( 'اسکرول را ادامه دهید تا لایه‌ها باز شوند', 'pixva' ),
				'element_id' => '',
			)
		);

		$layers = $settings['layers'];
		if ( empty( $layers ) || ! is_array( $layers ) ) {
			$layers = pixva_cinematic_default_layers();
		}

		$height   = max( 140, (int) $settings['height'] );
		$speed    = max( 0.2, (float) $settings['speed'] );
		$spread   = max( 20, (int) $settings['spread'] );
		$rotate   = max( 0, (int) $settings['rotate'] );
		$id       = '' !== trim( (string) $settings['element_id'] ) ? sanitize_html_class( (string) $settings['element_id'] ) : 'pixva-cine-' . wp_rand( 100, 999 );
		$calc_url = function_exists( 'pixva_page_url' ) ? pixva_page_url( 'calculator' ) : home_url( '/' );
		?>
		<section
			id="<?php echo esc_attr( $id ); ?>"
			class="pixva-cine"
			data-pixva-cine
			data-cine-speed="<?php echo esc_attr( (string) $speed ); ?>"
			data-cine-spread="<?php echo esc_attr( (string) $spread ); ?>"
			data-cine-rotate="<?php echo esc_attr( (string) $rotate ); ?>"
			data-cine-pin="<?php echo $settings['pin'] ? '1' : '0'; ?>"
			data-cine-scrub="<?php echo $settings['scrub'] ? '1' : '0'; ?>"
			style="--cine-neon:<?php echo esc_attr( (string) $settings['neon'] ); ?>;--cine-neon2:<?php echo esc_attr( (string) $settings['neon2'] ); ?>;--cine-length:<?php echo esc_attr( (string) $height ); ?>vh"
		>
			<div class="pixva-cine__track" data-cine-track>
				<div class="pixva-cine__stage" data-cine-stage>
					<header class="pixva-cine__head">
						<?php if ( '' !== trim( (string) $settings['badge'] ) ) : ?>
							<span class="pixva-badge pixva-badge--brand"><?php echo esc_html( (string) $settings['badge'] ); ?></span>
						<?php endif; ?>
						<h2><?php echo esc_html( (string) $settings['title'] ); ?></h2>
						<p class="pixva-muted"><?php echo esc_html( (string) $settings['subtitle'] ); ?></p>
					</header>

					<div class="pixva-cine__scene" data-cine-scene aria-label="<?php esc_attr_e( 'نمای انفجاری لایه‌های تلویزیون', 'pixva' ); ?>">
						<span class="pixva-cine__glow" aria-hidden="true"></span>
						<?php $index = 0; foreach ( $layers as $layer ) : ?>
							<?php
							$index++;
							$depth = isset( $layer['depth'] ) ? (int) $layer['depth'] : $index;
							$image = isset( $layer['image'] ) ? (string) $layer['image'] : '';
							$title = isset( $layer['title'] ) ? (string) $layer['title'] : '';
							$text  = isset( $layer['text'] ) ? (string) $layer['text'] : '';
							$link  = isset( $layer['link'] ) ? (string) $layer['link'] : '';
							$url   = '' !== $link
								? ( function_exists( 'pixva_mega_service_url' ) ? pixva_mega_service_url( $link ) : add_query_arg( 'problem', $link, $calc_url ) )
								: $calc_url;
							if ( filter_var( $link, FILTER_VALIDATE_URL ) ) {
								$url = $link;
							}
							?>
							<figure class="pixva-cine__layer" data-cine-layer data-cine-depth="<?php echo esc_attr( (string) $depth ); ?>" data-cine-index="<?php echo esc_attr( (string) $index ); ?>">
								<?php if ( '' !== $image ) : ?>
									<img src="<?php echo esc_url( $image ); ?>" alt="<?php echo esc_attr( $title ); ?>" loading="lazy" decoding="async">
								<?php else : ?>
									<span class="pixva-cine__plate" aria-hidden="true"><?php echo pixva_icon( 'panel' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
								<?php endif; ?>
								<figcaption>
									<strong><?php echo esc_html( $title ); ?></strong>
								</figcaption>
							</figure>
						<?php endforeach; ?>
					</div>

					<ol class="pixva-cine__notes" data-cine-notes>
						<?php $index = 0; foreach ( $layers as $layer ) : ?>
							<?php
							$index++;
							$title = isset( $layer['title'] ) ? (string) $layer['title'] : '';
							$text  = isset( $layer['text'] ) ? (string) $layer['text'] : '';
							$link  = isset( $layer['link'] ) ? (string) $layer['link'] : '';
							$url   = '' !== $link
								? ( function_exists( 'pixva_mega_service_url' ) ? pixva_mega_service_url( $link ) : add_query_arg( 'problem', $link, $calc_url ) )
								: $calc_url;
							if ( filter_var( $link, FILTER_VALIDATE_URL ) ) {
								$url = $link;
							}
							?>
							<li class="pixva-cine__note" data-cine-note data-cine-note-index="<?php echo esc_attr( (string) $index ); ?>">
								<span class="pixva-cine__note-no" aria-hidden="true"><?php echo esc_html( function_exists( 'pixva_fa_num' ) ? pixva_fa_num( (string) $index ) : $index ); ?></span>
								<h3><?php echo esc_html( $title ); ?></h3>
								<p><?php echo esc_html( $text ); ?></p>
								<a class="pixva-cine__cta" href="<?php echo esc_url( $url ); ?>" data-ripple>
									<?php echo esc_html( (string) $settings['cta_text'] ); ?>
									<?php echo pixva_icon( 'arrow' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								</a>
							</li>
						<?php endforeach; ?>
					</ol>

					<footer class="pixva-cine__foot">
						<span class="pixva-cine__hint"><?php echo esc_html( (string) $settings['note'] ); ?></span>
						<span class="pixva-cine__bar" aria-hidden="true"><i data-cine-bar></i></span>
						<span class="pixva-cine__step" data-cine-step><?php echo esc_html( function_exists( 'pixva_fa_num' ) ? pixva_fa_num( '0' ) : '0' ); ?> / <?php echo esc_html( function_exists( 'pixva_fa_num' ) ? pixva_fa_num( (string) count( $layers ) ) : count( $layers ) ); ?></span>
					</footer>
				</div>
			</div>
		</section>
		<?php
	}
}

/* --------------------------------------------------------------------------
 * ۵) رندر مشترک: مدل سه‌بعدی تعاملی (Spline / WebGL)
 * ----------------------------------------------------------------------- */

if ( ! function_exists( 'pixva_render_spline_3d' ) ) {
	/**
	 * رندر ویجت مدل سه‌بعدی تعاملی با هات‌اسپیت استعلام قیمت.
	 *
	 * @param array $settings تنظیمات.
	 * @return void
	 */
	function pixva_render_spline_3d( $settings = array() ) {
		$settings = wp_parse_args(
			$settings,
			array(
				'badge'      => __( 'نمای سه‌بعدی', 'pixva' ),
				'title'      => __( 'دستگاه را بچرخانید و قطعه معیوب را لمس کنید', 'pixva' ),
				'subtitle'   => __( 'با ماوس مدل را بچرخانید؛ روی نقطه‌های قرمز کلیک کنید تا برآورد قیمت همان قطعه باز شود.', 'pixva' ),
				'url'        => '',
				'lazy'       => true,
				'height'     => 34,
				'hotspots'   => array(),
				'neon'       => 'rgb(248, 113, 113)',
				'accent'     => 'rgb(34, 211, 238)',
				'fallback'   => true,
				'element_id' => '',
			)
		);

		$hotspots = $settings['hotspots'];
		if ( empty( $hotspots ) || ! is_array( $hotspots ) ) {
			$hotspots = array(
				array(
					'x'     => 30,
					'y'     => 42,
					'part'  => 'backlight',
					'label' => __( 'بک‌لایت سوخته', 'pixva' ),
					'text'  => __( 'تاریکی موضعی یا نیمه تاریک شدن تصویر؛ تعویض ریسه LED فابریک.', 'pixva' ),
				),
				array(
					'x'     => 58,
					'y'     => 62,
					'part'  => 'panel',
					'label' => __( 'خطوط پنل', 'pixva' ),
					'text'  => __( 'خطوط عمودی/افقی یا شکستگی شیشه؛ بندینگ COF یا تعویض پنل.', 'pixva' ),
				),
				array(
					'x'     => 74,
					'y'     => 30,
					'part'  => 'mainboard',
					'label' => __( 'برد اصلی', 'pixva' ),
					'text'  => __( 'عدم تصویر، مشکل HDMI یا خاموشی؛ تعمیر مین‌برد در سطح قطعه.', 'pixva' ),
				),
			);
		}

		$id     = '' !== trim( (string) $settings['element_id'] ) ? sanitize_html_class( (string) $settings['element_id'] ) : 'pixva-3d-' . wp_rand( 100, 999 );
		$height = max( 16, (int) $settings['height'] );
		$url    = trim( (string) $settings['url'] );
		$calc   = function_exists( 'pixva_page_url' ) ? pixva_page_url( 'calculator' ) : home_url( '/' );
		?>
		<section
			id="<?php echo esc_attr( $id ); ?>"
			class="pixva-3d"
			data-pixva-spline
			data-spline-url="<?php echo esc_url( $url ); ?>"
			data-spline-lazy="<?php echo $settings['lazy'] ? '1' : '0'; ?>"
			data-spline-fallback="<?php echo $settings['fallback'] ? '1' : '0'; ?>"
			style="--spline-h:<?php echo esc_attr( (string) $height ); ?>rem;--spline-hot:<?php echo esc_attr( (string) $settings['neon'] ); ?>;--spline-accent:<?php echo esc_attr( (string) $settings['accent'] ); ?>"
		>
			<header class="pixva-3d__head">
				<?php if ( '' !== trim( (string) $settings['badge'] ) ) : ?>
					<span class="pixva-badge pixva-badge--brand"><?php echo esc_html( (string) $settings['badge'] ); ?></span>
				<?php endif; ?>
				<h2><?php echo esc_html( (string) $settings['title'] ); ?></h2>
				<p class="pixva-muted"><?php echo esc_html( (string) $settings['subtitle'] ); ?></p>
			</header>

			<div class="pixva-3d__stage" data-spline-stage>
				<div class="pixva-3d__model" data-spline-host>
					<?php if ( '' === $url ) : ?>
						<p class="pixva-3d__empty"><?php esc_html_e( 'آدرس مدل Spline در تنظیمات ویجت وارد نشده؛ نمای لایه‌ای داخلی نمایش داده می‌شود.', 'pixva' ); ?></p>
					<?php endif; ?>
				</div>

				<div class="pixva-3d__fallback" data-spline-fallback-view aria-hidden="true">
					<div class="pixva-3d__tv">
						<span class="pixva-3d__screen"></span>
						<span class="pixva-3d__backlight"></span>
						<span class="pixva-3d__board"></span>
						<span class="pixva-3d__stand"></span>
					</div>
				</div>

				<div class="pixva-3d__hots" data-spline-hots>
					<?php foreach ( $hotspots as $hot ) : ?>
						<?php
						$x    = isset( $hot['x'] ) ? max( 0, min( 100, (float) $hot['x'] ) ) : 50;
						$y    = isset( $hot['y'] ) ? max( 0, min( 100, (float) $hot['y'] ) ) : 50;
						$part = isset( $hot['part'] ) ? (string) $hot['part'] : '';
						$text = isset( $hot['text'] ) ? (string) $hot['text'] : '';
						$label = isset( $hot['label'] ) ? (string) $hot['label'] : '';
						$part_label = '' !== $label && function_exists( 'pixva_mega_service_title' )
							? pixva_mega_service_title( $part, $label )
							: $label;
						$href = '' !== $part
							? ( function_exists( 'pixva_mega_service_url' ) ? pixva_mega_service_url( $part ) : add_query_arg( 'problem', $part, $calc ) )
							: $calc;
						?>
						<button
							type="button"
							class="pixva-3d__hot"
							data-spline-hot="<?php echo esc_attr( $part ); ?>"
							data-spline-part="<?php echo esc_attr( $part_label ); ?>"
							data-spline-text="<?php echo esc_attr( $text ); ?>"
							data-spline-href="<?php echo esc_url( $href ); ?>"
							style="--hx:<?php echo esc_attr( (string) $x ); ?>%;--hy:<?php echo esc_attr( (string) $y ); ?>%"
							aria-label="<?php echo esc_attr( $label ); ?>"
						>
							<span class="pixva-3d__hot-dot" aria-hidden="true"></span>
							<span class="pixva-3d__hot-label"><?php echo esc_html( $label ); ?></span>
						</button>
					<?php endforeach; ?>
				</div>

				<p class="pixva-3d__status" data-spline-status role="status" aria-live="polite" hidden></p>
			</div>

			<div class="pixva-3d__panel pixva-card" data-spline-panel hidden>
				<button type="button" class="pixva-3d__close" data-spline-close aria-label="<?php esc_attr_e( 'بستن', 'pixva' ); ?>">
					<?php echo pixva_icon( 'close' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</button>
				<h3 data-spline-panel-title></h3>
				<p data-spline-panel-text></p>
				<dl class="pixva-3d__meta">
					<div><dt><?php esc_html_e( 'قطعه', 'pixva' ); ?></dt><dd data-spline-panel-part></dd></div>
					<div><dt><?php esc_html_e( 'گارانتی تعمیر', 'pixva' ); ?></dt><dd><?php echo esc_html( function_exists( 'pixva_warranty_days' ) ? sprintf( /* translators: %s: روز */ __( '%s روز', 'pixva' ), pixva_fa_num( (string) pixva_warranty_days() ) ) : '' ); ?></dd></div>
				</dl>
				<a class="pixva-btn pixva-btn--primary" data-spline-panel-cta href="<?php echo esc_url( $calc ); ?>" data-ripple>
					<?php echo pixva_icon( 'calculator' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<span><?php esc_html_e( 'استعلام قیمت این قطعه', 'pixva' ); ?></span>
				</a>
			</div>
		</section>
		<?php
	}
}

/* --------------------------------------------------------------------------
 * ۶) رندر مشترک: عیب‌یاب هوشمند (AI Quick Diagnose)
 * ----------------------------------------------------------------------- */

if ( ! function_exists( 'pixva_render_ai_diagnose' ) ) {
	/**
	 * رندر ویجت عیب‌یاب هوشمند با گوی نوری و ارسال رسانه به REST.
	 *
	 * @param array $settings تنظیمات.
	 * @return void
	 */
	function pixva_render_ai_diagnose( $settings = array() ) {
		$settings = wp_parse_args(
			$settings,
			array(
				'badge'      => __( 'عیب‌یابی هوشمند', 'pixva' ),
				'title'      => __( 'ویدیو یا صدای دستگاه را بفرستید تا تحلیل شود', 'pixva' ),
				'subtitle'   => __( 'یک ویدیوی کوتاه از خرابی یا صدای دستگاه را آپلود کنید؛ خروجی تحلیل به همراه کد پیگیری برای شما ثبت می‌شود.', 'pixva' ),
				'video_label' => __( 'آپلود ویدیوی خرابی', 'pixva' ),
				'mic_label'  => __( 'ضبط صدای دستگاه', 'pixva' ),
				'submit'     => __( 'تحلیل هوشمند', 'pixva' ),
				'privacy'    => __( 'فایل‌ها فقط برای عیب‌یابی پرونده شما استفاده می‌شوند و پس از بسته‌شدن پرونده پاک می‌گردند.', 'pixva' ),
				'max_note'   => __( 'ویدیوی ۱۰ تا ۳۰ ثانیه‌ای کافی است؛ سقف حجم از تنظیمات پوسته خوانده می‌شود.', 'pixva' ),
				'neon'       => 'rgb(56, 189, 248)',
				'neon2'      => 'rgb(168, 85, 247)',
				'pulse'      => 2.4,
				'accept'     => 'video/*,audio/*',
				'show_phone' => true,
				'element_id' => '',
			)
		);

		$id      = '' !== trim( (string) $settings['element_id'] ) ? sanitize_html_class( (string) $settings['element_id'] ) : 'pixva-ai-' . wp_rand( 100, 999 );
		$pulse   = max( 0.8, (float) $settings['pulse'] );
		$brands  = function_exists( 'pixva_brand_catalog' ) ? pixva_brand_catalog() : array();
		?>
		<section
			id="<?php echo esc_attr( $id ); ?>"
			class="pixva-ai"
			data-pixva-ai-diagnose
			data-ai-accept="<?php echo esc_attr( (string) $settings['accept'] ); ?>"
			style="--ai-neon:<?php echo esc_attr( (string) $settings['neon'] ); ?>;--ai-neon2:<?php echo esc_attr( (string) $settings['neon2'] ); ?>;--ai-pulse:<?php echo esc_attr( (string) $pulse ); ?>s"
		>
			<div class="pixva-ai__orb" data-ai-orb aria-hidden="true">
				<span class="pixva-ai__ring pixva-ai__ring--1"></span>
				<span class="pixva-ai__ring pixva-ai__ring--2"></span>
				<span class="pixva-ai__ring pixva-ai__ring--3"></span>
				<span class="pixva-ai__core"><?php echo pixva_icon( 'ai' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
			</div>

			<header class="pixva-ai__head">
				<?php if ( '' !== trim( (string) $settings['badge'] ) ) : ?>
					<span class="pixva-badge pixva-badge--accent"><?php echo esc_html( (string) $settings['badge'] ); ?></span>
				<?php endif; ?>
				<h2><?php echo esc_html( (string) $settings['title'] ); ?></h2>
				<p class="pixva-muted"><?php echo esc_html( (string) $settings['subtitle'] ); ?></p>
			</header>

			<form class="pixva-ai__form" data-ai-form novalidate>
				<div class="pixva-ai__fields">
					<label class="pixva-ai__field">
						<span><?php esc_html_e( 'برند دستگاه', 'pixva' ); ?></span>
						<select name="brand">
							<option value=""><?php esc_html_e( 'انتخاب کنید', 'pixva' ); ?></option>
							<?php foreach ( $brands as $key => $brand ) : ?>
								<option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( isset( $brand['fa'] ) ? $brand['fa'] : $key ); ?></option>
							<?php endforeach; ?>
						</select>
					</label>

					<label class="pixva-ai__field">
						<span><?php esc_html_e( 'مدل دستگاه', 'pixva' ); ?></span>
						<input type="text" name="model" autocomplete="off" placeholder="<?php esc_attr_e( 'مثلاً 55Q80A', 'pixva' ); ?>">
					</label>

					<label class="pixva-ai__field pixva-ai__field--wide">
						<span><?php esc_html_e( 'شرح خرابی', 'pixva' ); ?></span>
						<textarea name="symptom" rows="2" placeholder="<?php esc_attr_e( 'مثلاً تصویر سیاه است ولی صدا دارد', 'pixva' ); ?>"></textarea>
					</label>

					<?php if ( $settings['show_phone'] ) : ?>
						<label class="pixva-ai__field">
							<span><?php esc_html_e( 'شماره تماس', 'pixva' ); ?></span>
							<input type="tel" name="phone" inputmode="numeric" autocomplete="tel" placeholder="09xxxxxxxxx">
						</label>
					<?php endif; ?>
				</div>

				<div class="pixva-ai__media" data-ai-media hidden>
					<span class="pixva-ai__media-icon" data-ai-media-icon><?php echo pixva_icon( 'camera' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
					<span class="pixva-ai__media-body">
						<strong data-ai-media-name></strong>
						<small data-ai-media-meta></small>
					</span>
					<button type="button" class="pixva-ai__media-remove" data-ai-remove aria-label="<?php esc_attr_e( 'حذف فایل', 'pixva' ); ?>">
						<?php echo pixva_icon( 'close' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</button>
				</div>

				<div class="pixva-ai__actions">
					<label class="pixva-btn pixva-btn--ghost-dark pixva-ai__pick" data-ai-pick>
						<?php echo pixva_icon( 'camera' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<span><?php echo esc_html( (string) $settings['video_label'] ); ?></span>
						<input type="file" name="media" accept="<?php echo esc_attr( (string) $settings['accept'] ); ?>" data-ai-file hidden>
					</label>

					<button type="button" class="pixva-btn pixva-btn--ghost-dark pixva-ai__mic" data-ai-mic>
						<?php echo pixva_icon( 'mic' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<span><?php echo esc_html( (string) $settings['mic_label'] ); ?></span>
					</button>

					<button type="submit" class="pixva-btn pixva-btn--primary pixva-ai__submit" data-ai-submit>
						<?php echo pixva_icon( 'bolt' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<span><?php echo esc_html( (string) $settings['submit'] ); ?></span>
					</button>
				</div>

				<?php if ( '' !== trim( (string) $settings['max_note'] ) ) : ?>
					<p class="pixva-ai__note pixva-muted"><?php echo esc_html( (string) $settings['max_note'] ); ?></p>
				<?php endif; ?>

				<p class="pixva-notice pixva-notice--error" data-ai-error role="alert" hidden></p>
			</form>

			<div class="pixva-ai__loading" data-ai-loading hidden aria-hidden="true">
				<div class="pixva-ai__scan"><i></i><i></i><i></i><i></i><i></i><i></i></div>
				<code class="pixva-ai__log" data-ai-log></code>
				<span class="pixva-ai__pct" data-ai-pct>۰٪</span>
			</div>

			<div class="pixva-ai__result pixva-card" data-ai-result hidden>
				<span class="pixva-badge pixva-badge--success" data-ai-result-badge></span>
				<h3 data-ai-result-title></h3>
				<p data-ai-result-text></p>
				<dl class="pixva-ai__meta">
					<div><dt><?php esc_html_e( 'کد پیگیری', 'pixva' ); ?></dt><dd data-ai-result-code></dd></div>
					<div><dt><?php esc_html_e( 'زمان ثبت', 'pixva' ); ?></dt><dd data-ai-result-date></dd></div>
				</dl>
				<div class="pixva-ai__result-actions">
					<a class="pixva-btn pixva-btn--primary pixva-btn--sm" data-ai-result-cta href="<?php echo esc_url( function_exists( 'pixva_page_url' ) ? pixva_page_url( 'calculator' ) : home_url( '/' ) ); ?>" data-ripple>
						<span><?php esc_html_e( 'ثبت سفارش تعمیر', 'pixva' ); ?></span>
					</a>
					<a class="pixva-btn pixva-btn--ghost-dark pixva-btn--sm" data-ai-result-track href="<?php echo esc_url( function_exists( 'pixva_page_url' ) ? pixva_page_url( 'tracking' ) : home_url( '/' ) ); ?>">
						<span><?php esc_html_e( 'پیگیری درخواست', 'pixva' ); ?></span>
					</a>
				</div>
			</div>

			<?php if ( '' !== trim( (string) $settings['privacy'] ) ) : ?>
				<p class="pixva-ai__privacy"><?php echo esc_html( (string) $settings['privacy'] ); ?></p>
			<?php endif; ?>
		</section>
		<?php
	}
}

/* --------------------------------------------------------------------------
 * ۷) رندر مشترک: نقشه زنده تعمیرکار (Uber-style)
 * ----------------------------------------------------------------------- */

if ( ! function_exists( 'pixva_render_technician_tracker' ) ) {
	/**
	 * رندر ماژول نقشه زنده تعمیرکار.
	 *
	 * @param array $settings تنظیمات.
	 * @return void
	 */
	function pixva_render_technician_tracker( $settings = array() ) {
		$settings = wp_parse_args(
			$settings,
			array(
				'badge'      => __( 'ردیابی زنده', 'pixva' ),
				'title'      => __( 'تعمیرکار کجاست؟', 'pixva' ),
				'subtitle'   => __( 'کد پیگیری را وارد کنید تا موقعیت تعمیرکار و زمان تقریبی رسیدن را زنده ببینید.', 'pixva' ),
				'code'       => '',
				'phone'      => '',
				'height'     => 26,
				'zoom'       => 14,
				'refresh'    => 20,
				'neon'       => 'rgb(34, 211, 238)',
				'car'        => 'rgb(248, 113, 113)',
				'lookup'     => true,
				'element_id' => '',
			)
		);

		$id     = '' !== trim( (string) $settings['element_id'] ) ? sanitize_html_class( (string) $settings['element_id'] ) : 'pixva-map-' . wp_rand( 100, 999 );
		$height = max( 14, (int) $settings['height'] );
		$code   = trim( (string) $settings['code'] );
		$phone  = trim( (string) $settings['phone'] );
		?>
		<section
			id="<?php echo esc_attr( $id ); ?>"
			class="pixva-map"
			data-pixva-tracker
			data-map-code="<?php echo esc_attr( $code ); ?>"
			data-map-phone="<?php echo esc_attr( $phone ); ?>"
			data-map-zoom="<?php echo esc_attr( (string) max( 3, min( 18, (int) $settings['zoom'] ) ) ); ?>"
			data-map-refresh="<?php echo esc_attr( (string) max( 5, (int) $settings['refresh'] ) ); ?>"
			style="--map-h:<?php echo esc_attr( (string) $height ); ?>rem;--map-neon:<?php echo esc_attr( (string) $settings['neon'] ); ?>;--map-car:<?php echo esc_attr( (string) $settings['car'] ); ?>"
		>
			<header class="pixva-map__head">
				<?php if ( '' !== trim( (string) $settings['badge'] ) ) : ?>
					<span class="pixva-badge pixva-badge--brand"><?php echo esc_html( (string) $settings['badge'] ); ?></span>
				<?php endif; ?>
				<h2><?php echo esc_html( (string) $settings['title'] ); ?></h2>
				<p class="pixva-muted"><?php echo esc_html( (string) $settings['subtitle'] ); ?></p>
			</header>

			<?php if ( $settings['lookup'] ) : ?>
				<form class="pixva-map__lookup" data-map-form novalidate>
					<label class="pixva-map__field">
						<span><?php esc_html_e( 'کد پیگیری', 'pixva' ); ?></span>
						<input type="text" name="code" value="<?php echo esc_attr( $code ); ?>" placeholder="PXV-..." autocomplete="off" required>
					</label>
					<label class="pixva-map__field">
						<span><?php esc_html_e( 'شماره تماس', 'pixva' ); ?></span>
						<input type="tel" name="phone" inputmode="numeric" placeholder="09xxxxxxxxx" autocomplete="tel">
					</label>
					<button type="submit" class="pixva-btn pixva-btn--primary" data-map-submit>
						<?php echo pixva_icon( 'pin' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<span><?php esc_html_e( 'نمایش موقعیت', 'pixva' ); ?></span>
					</button>
				</form>
			<?php endif; ?>

			<div class="pixva-map__canvas" data-map-canvas role="img" aria-label="<?php esc_attr_e( 'نقشه موقعیت زنده تعمیرکار', 'pixva' ); ?>"></div>

			<div class="pixva-map__status" data-map-status hidden>
				<span class="pixva-map__pulse" aria-hidden="true"></span>
				<span class="pixva-map__status-body">
					<strong data-map-eta></strong>
					<small data-map-tech></small>
				</span>
				<span class="pixva-map__badge" data-map-state></span>
			</div>

			<p class="pixva-notice pixva-notice--error" data-map-error role="alert" hidden></p>
			<p class="pixva-map__attr" data-map-attr></p>
		</section>
		<?php
	}
}

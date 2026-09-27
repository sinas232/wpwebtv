<?php
/**
 * پیکسوا — پرونده اصلی قالب (نسخه ۴٫۰٫۰ / Bento & SaaS)
 *
 * معماری ماژولار و تمیز:
 * ۱) ثابت‌ها           ۲) نقشه ماژول‌ها (آرایه‌محور)   ۳) بارگذاری تنبل المنتور
 * ۴) راه‌اندازی قالب   ۵) دارایی‌ها (بدون jQuery، همه Vanilla و defer)
 * ۶) گیت‌های صف‌گذاری مشروط   ۷) ابزارهای کمکی و فیلترهای هسته
 *
 * @package Pixva
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // خروج مستقیم غیرمجاز.
}

/* ==========================================================================
 * ۱) ثابت‌ها
 * ======================================================================= */

define( 'PIXVA_VERSION', '4.0.0' );
define( 'PIXVA_SPEC_VERSION', '26.0' ); // مستر اسپک «2026 Bento Luxury Edition».
define( 'PIXVA_DIR', get_template_directory() );
define( 'PIXVA_URI', get_template_directory_uri() );

/* ==========================================================================
 * ۲) نقشه ماژول‌ها — بارگذاری آرایه‌محور به‌جای فهرست دستی require
 * ======================================================================= */

if ( ! function_exists( 'pixva_module_map' ) ) {
	/**
	 * فهرست ماژول‌های داخلی قالب (به ترتیب وابستگی).
	 *
	 * @return array<int, string>
	 */
	function pixva_module_map() {
		// ترتیب بارگذاری وابستگی‌های include-time را حفظ می‌کند (دقیقاً ترتیب تاریخی قالب).
		$modules = array(
			'security',
			'pricing-engine',
			'custom-post-types',
			'meta-boxes',
			'cinematic',
			'ai-diagnose',
			'ai-handler',
			'sms-handler',
			'host-fix',
			'seo-cro',
			'corporate-ui',
			'bento-ui',
			'tracker-map',
			'theme-options',
			'home-seed',
			'control-center',
			'admin-settings',
			'hubs',
			'tool-data',
			'tools-registry',
			'tool-renderers',
			'ai-bot',
			'interactive-tools',
			'rest-api',
			'roles-and-cron',
			'shortcodes',
			'nav-menu',
			'fault-simulator',
			'ajax-handlers',
			'crm-engine',
			'crm-wizard',
			'crm-warranty',
			'crm-technician',
			'crm-dispatcher',
			'blog-ecosystem',
			'schema-markup',
			'pwa',
			'template-tags',
			'setup',
			'activation',
		);

		/**
		 * فیلتر نقشه ماژول‌های قالب.
		 *
		 * @param array<int, string> $modules ماژول‌ها (بدون پسوند .php).
		 */
		return (array) apply_filters( 'pixva_module_map', $modules );
	}
}

if ( ! function_exists( 'pixva_load_modules' ) ) {
	/**
	 * بارگذاری همه ماژول‌ها از نقشه (فقط پرونده‌های خواندنی).
	 *
	 * @return void
	 */
	function pixva_load_modules() {
		foreach ( pixva_module_map() as $module ) {
			$path = PIXVA_DIR . '/inc/' . $module . '.php';
			if ( is_readable( $path ) ) {
				require_once $path;
			}
		}
	}
}
pixva_load_modules();

/*
 * ماژول‌های المنتور فقط پس از بارگذاری کامل خود المنتور include می‌شوند تا در
 * نصب‌های بدون المنتور (یا هنگام به‌روزرسانی افزونه) خطای fatal رخ ندهد.
 */
if ( ! function_exists( 'pixva_elementor_section_files' ) ) {
	/**
	 * فهرست پرونده‌های ویجت سکشن در inc/widgets (مرتب‌شده، بدون پرونده محافظ).
	 *
	 * @return array<int, string>
	 */
	function pixva_elementor_section_files() {
		$dir = PIXVA_DIR . '/inc/widgets';
		if ( ! is_dir( $dir ) ) {
			return array();
		}

		$files = glob( $dir . '/class-*.php' );
		if ( ! is_array( $files ) ) {
			return array();
		}

		sort( $files );
		return array_values( array_filter( $files, 'is_readable' ) );
	}
}

if ( ! function_exists( 'pixva_load_elementor_modules' ) ) {
	/**
	 * بارگذاری مشروط پرونده‌های ادغام المنتور (Theme Builder + ویجت‌ها).
	 *
	 * @return void
	 */
	function pixva_load_elementor_modules() {
		if ( ! did_action( 'elementor/loaded' ) ) {
			return;
		}

		foreach ( array( '/inc/elementor-support.php', '/inc/elementor-widgets.php' ) as $module ) {
			$path = PIXVA_DIR . $module;
			if ( is_readable( $path ) ) {
				require_once $path;
			}
		}

		foreach ( pixva_elementor_section_files() as $file ) {
			require_once $file;
		}
	}
	add_action( 'elementor/loaded', 'pixva_load_elementor_modules', 5 );
}

/* ==========================================================================
 * ۳) راه‌اندازی اولیه قالب
 * ======================================================================= */

if ( ! function_exists( 'pixva_setup' ) ) {
	/**
	 * ثبت پشتیبانی‌ها، منوها و اندازه تصاویر قالب.
	 *
	 * @return void
	 */
	function pixva_setup() {
		load_theme_textdomain( 'pixva', PIXVA_DIR . '/languages' );

		add_theme_support( 'title-tag' );
		add_theme_support( 'post-thumbnails' );
		add_theme_support( 'automatic-feed-links' );
		add_theme_support( 'customize-selective-refresh-widgets' );
		add_theme_support( 'responsive-embeds' );
		add_theme_support( 'align-wide' );
		add_theme_support( 'editor-styles' );
		add_editor_style( 'assets/css/editor.css' );
		add_theme_support(
			'custom-logo',
			array(
				'height'      => 96,
				'width'       => 260,
				'flex-height' => true,
				'flex-width'  => true,
			)
		);
		add_theme_support(
			'html5',
			array(
				'search-form',
				'comment-form',
				'comment-list',
				'gallery',
				'caption',
				'style',
				'script',
				'navigation-widgets',
			)
		);

		register_nav_menus(
			array(
				'primary' => esc_html__( 'منوی اصلی هدر', 'pixva' ),
				'footer'  => esc_html__( 'منوی لینک‌های سریع فوتر', 'pixva' ),
			)
		);

		add_image_size( 'pixva-card', 640, 420, true );
		add_image_size( 'pixva-wide', 1280, 640, true );
		add_image_size( 'pixva-ba', 1200, 675, true );
	}
}
add_action( 'after_setup_theme', 'pixva_setup' );

/**
 * عرض محتوای اصلی.
 *
 * @var int
 */
$GLOBALS['content_width'] = 1240;

/* ==========================================================================
 * ۴) دارایی‌ها — سامانه طراحی بنتو سراسری است (ویجت‌ها توکن‌ها را می‌گیرند)
 * ======================================================================= */

if ( ! function_exists( 'pixva_assets' ) ) {
	/**
	 * ثبت و صف‌گذاری دارایی‌های فرانت‌اند (بدون jQuery، همه Vanilla JS).
	 *
	 * @return void
	 */
	function pixva_assets() {
		$defer = array(
			'in_footer' => true,
			'strategy'  => 'defer',
		);

		/* ----- سبک‌ها ----- */
		wp_enqueue_style( 'pixva-style', get_stylesheet_uri(), array(), PIXVA_VERSION );
		wp_enqueue_style( 'pixva-main', PIXVA_URI . '/assets/css/main.css', array( 'pixva-style' ), PIXVA_VERSION );

		if ( pixva_needs_components_css() ) {
			wp_enqueue_style( 'pixva-components', PIXVA_URI . '/assets/css/components.css', array( 'pixva-main' ), PIXVA_VERSION );
		}

		// لایه محتوای داخلی/هاب‌ها (تایپوگرافی مقاله‌ها و ابزارهای قدیمی‌تر).
		wp_enqueue_style( 'pixva-2026', PIXVA_URI . '/assets/css/pixva-2026.css', array( 'pixva-main' ), PIXVA_VERSION );

		/*
		 * سامانه طراحی بنتو (لایه ۴٫۰٫۰): آخرین لایه CSS و سراسری — توکن‌های
		 * ابسیدین/ایندیگو/زمرد، ناوبری قرصی شناور، هیروی مرکزی، گرید ۱۲ ستونه،
		 * داک موبایل و پوشش‌های JetEngine. همیشه بارگذاری می‌شود تا ویجت‌های
		 * المنتور و قالب‌های Theme Builder همان توکن‌ها را به ارث ببرند.
		 */
		if ( pixva_corporate_ui_mode() ) {
			wp_enqueue_style( 'pixva-seo-cro', PIXVA_URI . '/assets/css/seo-cro.css', array( 'pixva-2026' ), PIXVA_VERSION );
		}

		/* ----- اسکریپت‌های سراسری ----- */
		wp_enqueue_script( 'pixva-main', PIXVA_URI . '/assets/js/main.js', array(), PIXVA_VERSION, $defer );
		wp_localize_script(
			'pixva-main',
			'pixvaTheme',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => array(
					'calculator' => wp_create_nonce( 'pixva_calculator_nonce' ),
					'order'      => wp_create_nonce( 'pixva_order_nonce' ),
					'tracking'   => wp_create_nonce( 'pixva_tracking_nonce' ),
					'contact'    => wp_create_nonce( 'pixva_contact_nonce' ),
				),
				'i18n'    => array(
					'loading'   => esc_html__( 'در حال پردازش…', 'pixva' ),
					'error'     => esc_html__( 'خطایی رخ داد؛ دوباره تلاش کنید.', 'pixva' ),
					'menuOpen'  => esc_html__( 'باز کردن منو', 'pixva' ),
					'menuClose' => esc_html__( 'بستن منو', 'pixva' ),
					'sent'      => esc_html__( 'ارسال شد', 'pixva' ),
				),
			)
		);

		// موتور بنتو (لایه ۴٫۰٫۰): هدر قرصی، دروئر، موتور رزرو و کاوشگر.
		wp_enqueue_script( 'pixva-bento', PIXVA_URI . '/assets/js/bento.js', array(), PIXVA_VERSION, $defer );
		if ( function_exists( 'pixva_bento_localize' ) ) {
			wp_localize_script( 'pixva-bento', 'pixvaBento', pixva_bento_localize() );
		}

		// موتور حرکت: ظهور هنگام اسکرول، موج نوری کلیک و اسکرول نرم.
		wp_enqueue_script( 'pixva-motion', PIXVA_URI . '/assets/js/motion.js', array(), PIXVA_VERSION, $defer );

		/* ----- اسکریپت‌های مشروط (سرعت) ----- */
		if ( pixva_needs_tools_js() ) {
			wp_enqueue_script( 'pixva-tools', PIXVA_URI . '/assets/js/interactive-tools.js', array( 'pixva-main' ), PIXVA_VERSION, $defer );

			wp_localize_script(
				'pixva-tools',
				'pixvaVars',
				array(
					'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
					'restUrl'      => esc_url_raw( rest_url( 'pixva/v1' ) ),
					'homeUrl'      => esc_url_raw( home_url( '/' ) ),
					'nonce'        => wp_create_nonce( 'pixva_nonce' ),
					'nonces'       => array(
						'tool'       => wp_create_nonce( 'pixva_nonce' ),
						'calculator' => wp_create_nonce( 'pixva_calculator_nonce' ),
						'order'      => wp_create_nonce( 'pixva_order_nonce' ),
						'tracking'   => wp_create_nonce( 'pixva_tracking_nonce' ),
						'contact'    => wp_create_nonce( 'pixva_contact_nonce' ),
					),
					'aiConfigured' => function_exists( 'pixva_ai_is_configured' ) && pixva_ai_is_configured(),
					'pwa'          => array(
						'enabled' => function_exists( 'pixva_pwa_enabled' ) && pixva_pwa_enabled(),
						'offline' => function_exists( 'pixva_pwa_offline_message' ) ? pixva_pwa_offline_message() : '',
					),
					'i18n'         => array(
						'toman'    => esc_html__( 'تومان', 'pixva' ),
						'loading'  => esc_html__( 'در حال پردازش…', 'pixva' ),
						'error'    => esc_html__( 'خطایی رخ داد؛ دوباره تلاش کنید.', 'pixva' ),
						'sent'     => esc_html__( 'ارسال شد', 'pixva' ),
						'analysis' => esc_html__( 'در حال تحلیل…', 'pixva' ),
					),
				)
			);
		}

		if ( pixva_needs_qrcode_js() ) {
			wp_enqueue_script( 'pixva-qrcode', PIXVA_URI . '/assets/js/vendor/qrcode-generator.js', array(), '2.0.4', $defer );
		}

		if ( pixva_needs_calculator_js() ) {
			wp_enqueue_script( 'pixva-calculator', PIXVA_URI . '/assets/js/calculator.js', array( 'pixva-main' ), PIXVA_VERSION, $defer );
		}

		if ( is_page_template( 'page-templates/page-tracking.php' ) ) {
			wp_enqueue_script( 'pixva-tracker', PIXVA_URI . '/assets/js/tracker.js', array( 'pixva-main' ), PIXVA_VERSION, $defer );
		}

		if ( pixva_needs_crm_js() ) {
			wp_enqueue_script( 'pixva-crm', PIXVA_URI . '/assets/js/crm-engine.js', array( 'pixva-main' ), PIXVA_VERSION, $defer );
			wp_localize_script( 'pixva-crm', 'pixvaCrm', pixva_crm_localize() );
		}

		if ( pixva_needs_before_after_js() ) {
			wp_enqueue_script( 'pixva-before-after', PIXVA_URI . '/assets/js/before-after.js', array( 'pixva-main' ), PIXVA_VERSION, $defer );
		}

		if ( is_singular( 'post' ) ) {
			wp_enqueue_script( 'pixva-toc', PIXVA_URI . '/assets/js/toc.js', array( 'pixva-main' ), PIXVA_VERSION, $defer );
		}

		if ( is_singular() && comments_open() && get_comments_number() ) {
			wp_enqueue_script( 'comment-reply' );
		}
	}
}
add_action( 'wp_enqueue_scripts', 'pixva_assets' );

if ( ! function_exists( 'pixva_preload_font' ) ) {
	/**
	 * Preload فونت متغیر برای کاهش LCP.
	 *
	 * @return void
	 */
	function pixva_preload_font() {
		echo '<link rel="preload" href="' . esc_url( PIXVA_URI . '/assets/fonts/vazirmatn-variable.woff2' ) . '" as="font" type="font/woff2" crossorigin>' . "\n";
	}
}

/* ==========================================================================
 * ۵) گیت‌های صف‌گذاری مشروط
 * ======================================================================= */

if ( ! function_exists( 'pixva_needs_tools_js' ) ) {
	/**
	 * آیا صفحه جاری به اسکریپت ابزارهای تعاملی نیاز دارد؟
	 *
	 * @return bool
	 */
	function pixva_needs_tools_js() {
		if ( is_admin() ) {
			return false;
		}

		$templates = array(
			'page-templates/page-client-hub.php',
			'page-templates/page-hub-ai.php',
			'page-templates/page-hub-pricing.php',
			'page-templates/page-hub-tracking.php',
			'page-templates/page-hub-parts-b2b.php',
			'page-templates/page-parts-stock.php',
			'page-templates/page-error-codes.php',
			'page-templates/page-technician.php',
			'page-templates/page-calculator.php',
			'page-templates/page-b2b.php',
		);

		$needed = is_home() || is_page_template( $templates );

		if ( ! $needed && is_singular() ) {
			$current = get_post();

			if ( $current instanceof WP_Post ) {
				$content = (string) $current->post_content;
				$markers = array( '[pixva_tool', '[pixva_hub', 'data-pixva-tool', 'data-sim-root', 'elementor-widget-pixva' );

				foreach ( $markers as $marker ) {
					if ( false !== strpos( $content, $marker ) ) {
						$needed = true;
						break;
					}
				}
			}
		}

		/**
		 * فیلتر بارگذاری اسکریپت ابزارها در صفحه جاری.
		 *
		 * @param bool $needed نیاز به اسکریپت.
		 */
		return (bool) apply_filters( 'pixva_needs_tools_js', $needed );
	}
}

if ( ! function_exists( 'pixva_needs_components_css' ) ) {
	/**
	 * آیا صفحه جاری به سبک کامپوننت‌ها نیاز دارد؟
	 *
	 * @return bool
	 */
	function pixva_needs_components_css() {
		return (
			is_singular( 'post' )
			|| is_singular( 'tv_services' )
			|| is_singular( 'tv_brands' )
			|| is_singular( 'repair_cases' )
			|| is_singular( 'pixva_repair' )
			|| is_singular( 'pixva_part' )
			|| is_singular( 'pixva_error' )
			|| is_singular( 'pixva_branch' )
			|| is_page_template(
				array(
					'page-templates/page-calculator.php',
					'page-templates/page-tracking.php',
					'page-templates/page-error-codes.php',
					'page-templates/page-faq.php',
					'page-templates/page-contact.php',
					'page-templates/page-rates.php',
					'page-templates/page-hub-ai.php',
					'page-templates/page-hub-pricing.php',
					'page-templates/page-hub-tracking.php',
					'page-templates/page-hub-parts-b2b.php',
					'page-templates/page-parts-stock.php',
					'page-templates/page-client-hub.php',
					'page-templates/page-b2b.php',
				)
			)
			|| is_page()
			|| is_404()
			|| is_search()
		);
	}
}

if ( ! function_exists( 'pixva_needs_calculator_js' ) ) {
	/**
	 * آیا محاسبه‌گر در صفحه جاری حضور دارد؟
	 *
	 * @return bool
	 */
	function pixva_needs_calculator_js() {
		return (
			is_page_template( 'page-templates/page-calculator.php' )
			|| is_singular( 'tv_services' )
			|| is_singular( 'tv_brands' )
		);
	}
}

if ( ! function_exists( 'pixva_needs_before_after_js' ) ) {
	/**
	 * آیا اسلایدر قبل/بعد در صفحه جاری حضور دارد؟
	 *
	 * @return bool
	 */
	function pixva_needs_before_after_js() {
		return (
			is_singular( 'repair_cases' )
			|| is_page_template(
				array(
					'page-templates/page-about.php',
					'page-templates/page-hub-ai.php',
				)
			)
		);
	}
}

if ( ! function_exists( 'pixva_needs_qrcode_js' ) ) {
	/**
	 * آیا صفحه جاری کارت QR گارانتی دارد؟
	 *
	 * @return bool
	 */
	function pixva_needs_qrcode_js() {
		if ( is_admin() ) {
			return false;
		}

		return (bool) apply_filters( 'pixva_needs_qrcode_js', (bool) pixva_option( 'pixva_footer_qr_enabled', true ) );
	}
}

/* ==========================================================================
 * ۶) ابزارهای کمکی و فیلترهای هسته
 * ======================================================================= */

if ( ! function_exists( 'pixva_fa_num' ) ) {
	/**
	 * تبدیل ارقام لاتین به فارسی.
	 *
	 * @param string|int|float $value مقدار ورودی.
	 * @return string
	 */
	function pixva_fa_num( $value ) {
		$map = array(
			'0' => '۰',
			'1' => '۱',
			'2' => '۲',
			'3' => '۳',
			'4' => '۴',
			'5' => '۵',
			'6' => '۶',
			'7' => '۷',
			'8' => '۸',
			'9' => '۹',
		);
		return strtr( (string) $value, $map );
	}
}

if ( ! function_exists( 'pixva_price' ) ) {
	/**
	 * نمایش قیمت تومان با ارقام فارسی و جداکننده هزارگان.
	 *
	 * @param int $amount مبلغ به تومان.
	 * @return string
	 */
	function pixva_price( $amount ) {
		return pixva_fa_num( number_format_i18n( (int) $amount ) );
	}
}

// pixva_reading_time() در inc/blog-ecosystem.php تعریف می‌شود (تعریف یگانه قالب).

if ( ! function_exists( 'pixva_heading_ids' ) ) {
	/**
	 * افزودن id یکتا به سرتیترهای H2 تا H4 محتوا (برای لینک‌دهی).
	 *
	 * @param string $content محتوای مقاله.
	 * @return string
	 */
	function pixva_heading_ids( $content ) {
		if ( ! is_singular( 'post' ) || ! in_the_loop() || ! is_main_query() ) {
			return $content;
		}
		return preg_replace_callback(
			'/<(h[2-4])([^>]*)>(.*?)<\/\1>/is',
			static function ( $matches ) {
				$tag   = strtolower( $matches[1] );
				$attrs = $matches[2];
				$text  = $matches[3];
				if ( false !== strpos( $attrs, 'id=' ) ) {
					return $matches[0];
				}
				$slug = sanitize_title( wp_strip_all_tags( $text ) );
				if ( '' === $slug ) {
					$slug = 'section';
				}
				return sprintf( '<%1$s id="%2$s"%3$s>%4$s</%1$s>', $tag, $slug, $attrs, $text );
			},
			$content
		);
	}
}
add_filter( 'the_content', 'pixva_heading_ids', 5 );

/**
 * قالب فارسی است؛ حتی پیش از نصب بسته زبان، جهت سند راست‌چین می‌ماند.
 *
 * @param string $output ویژگی‌های زبان.
 * @return string
 */
function pixva_force_rtl_attributes( $output ) {
	if ( false === strpos( $output, 'dir=' ) ) {
		$output .= ' dir="rtl"';
	}
	return $output;
}
add_filter( 'language_attributes', 'pixva_force_rtl_attributes' );

if ( ! function_exists( 'pixva_body_classes' ) ) {
	/**
	 * کلاس‌های کمکی بدنه (فیلتر آرایه‌امن: push + return آرایه).
	 *
	 * @param mixed $classes کلاس‌های موجود.
	 * @return array<int, string>
	 */
	function pixva_body_classes( $classes ) {
		if ( ! is_array( $classes ) ) {
			$classes = array();
		}

		$classes[] = 'pixva-theme';
		$classes[] = 'pixva-bento';
		if ( is_front_page() ) {
			$classes[] = 'pixva-home';
		}
		if ( wp_is_mobile() ) {
			$classes[] = 'pixva-is-mobile';
		}
		if ( function_exists( 'pixva_current_hub' ) ) {
			$hub = pixva_current_hub();
			if ( $hub ) {
				$classes[] = 'pixva-hub-' . sanitize_html_class( $hub );
			}
		}

		return array_values( array_unique( array_filter( array_map( 'strval', $classes ), 'strlen' ) ) );
	}
}
add_filter( 'body_class', 'pixva_body_classes' );

if ( ! function_exists( 'pixva_excerpt_length' ) ) {
	/**
	 * طول چکیده در کارت‌های وبلاگ.
	 *
	 * @param int $length طول پیش‌فرض.
	 * @return int
	 */
	function pixva_excerpt_length( $length ) {
		return is_admin() ? $length : 24;
	}
}
add_filter( 'excerpt_length', 'pixva_excerpt_length' );

if ( ! function_exists( 'pixva_excerpt_more' ) ) {
	/**
	 * پایان‌بندی چکیده.
	 *
	 * @param string $more متن پیش‌فرض.
	 * @return string
	 */
	function pixva_excerpt_more( $more ) {
		return is_admin() ? $more : '…';
	}
}
add_filter( 'excerpt_more', 'pixva_excerpt_more' );

if ( ! function_exists( 'pixva_widget_sidebars' ) ) {
	/**
	 * ثبت نواحی ابزارک.
	 *
	 * @return void
	 */
	function pixva_widget_sidebars() {
		$shell = array(
			'before_widget' => '<section id="%1$s" class="pixva-card pixva-widget %2$s">',
			'after_widget'  => '</section>',
			'before_title'  => '<h3 class="pixva-widget__title">',
			'after_title'   => '</h3>',
		);

		$areas = array(
			array(
				'name'        => esc_html__( 'سایدبار وبلاگ و مقالات', 'pixva' ),
				'id'          => 'blog-sidebar',
				'description' => esc_html__( 'ابزارک‌های نمایش‌داده‌شده در آرشیو، دسته‌بندی، جستجو و مقاله‌ها.', 'pixva' ),
			),
			array(
				'name'        => esc_html__( 'سایدبار خدمات تعمیرات', 'pixva' ),
				'id'          => 'services-sidebar',
				'description' => esc_html__( 'باکس مشاوره سریع و دسته‌بندی خدمات در برگه‌های خدمت، برند، نمونه‌کار و عیب.', 'pixva' ),
			),
			array(
				'name'        => esc_html__( 'سایدبار برگه‌ها', 'pixva' ),
				'id'          => 'page-sidebar',
				'description' => esc_html__( 'ابزارک‌های برگه‌های معمولی و برگه‌های هاب.', 'pixva' ),
			),
			array(
				'name'        => esc_html__( 'ناحیه ابزارک فوتر', 'pixva' ),
				'id'          => 'footer-widgets',
				'description' => esc_html__( 'ابزارک‌های اختیاری بالای ستون‌های فوتر.', 'pixva' ),
			),
		);

		foreach ( $areas as $area ) {
			register_sidebar( array_merge( $shell, $area ) );
		}
	}
}
add_action( 'widgets_init', 'pixva_widget_sidebars' );

if ( ! function_exists( 'pixva_flush_rewrite_once' ) ) {
	/**
	 * بازنشانی یک‌باره rewrite rules پس از فعال‌سازی قالب.
	 *
	 * @return void
	 */
	function pixva_flush_rewrite_once() {
		flush_rewrite_rules();
		update_option( 'pixva_rewrite_flushed', 1 );
	}
}
add_action( 'after_switch_theme', 'pixva_flush_rewrite_once' );

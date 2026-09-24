<?php
/**
 * پرونده اصلی قالب پیکسوا: بارگذاری بخش‌ها، ثبت ویژگی‌ها و صف‌گذاری دارایی‌ها
 *
 * @package Pixva
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // خروج مستقیم غیرمجاز.
}

/*
 * نسخه قالب برای cache-busting (بر اساس زمان اصلاح پرونده اصلی).
 */
define( 'PIXVA_VERSION', '1.2.0' );
define( 'PIXVA_DIR', get_template_directory() );
define( 'PIXVA_URI', get_template_directory_uri() );

/*
 * ---------------------------------------------------------------------------
 * ۱) بارگذاری بخش‌های داخلی قالب
 * ---------------------------------------------------------------------------
 */
require_once PIXVA_DIR . '/inc/security.php';
require_once PIXVA_DIR . '/inc/pricing-engine.php';
require_once PIXVA_DIR . '/inc/custom-post-types.php';
require_once PIXVA_DIR . '/inc/theme-options.php';
require_once PIXVA_DIR . '/inc/control-center.php';
require_once PIXVA_DIR . '/inc/admin-settings.php';
require_once PIXVA_DIR . '/inc/ai-bot.php';
require_once PIXVA_DIR . '/inc/interactive-tools.php';
require_once PIXVA_DIR . '/inc/ajax-handlers.php';
require_once PIXVA_DIR . '/inc/schema-markup.php';
require_once PIXVA_DIR . '/inc/template-tags.php';
require_once PIXVA_DIR . '/inc/setup.php';
require_once PIXVA_DIR . '/inc/activation.php';

/*
 * ---------------------------------------------------------------------------
 * ۲) راه‌اندازی اولیه قالب
 * ---------------------------------------------------------------------------
 */
if ( ! function_exists( 'pixva_setup' ) ) {
	/**
	 * ثبت پشتیبانی‌ها، منوها و اندازه تصاویر قالب.
	 *
	 * @return void
	 */
	function pixva_setup() {
		// بارگذاری ترجمه (زبان پیش‌فرض فارسی است اما قالب translation-ready می‌ماند).
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

		// منوهای قالب.
		register_nav_menus(
			array(
				'primary' => esc_html__( 'منوی اصلی هدر', 'pixva' ),
				'footer'  => esc_html__( 'منوی لینک‌های سریع فوتر', 'pixva' ),
			)
		);

		// اندازه تصاویر اختصاصی.
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

/*
 * ---------------------------------------------------------------------------
 * ۳) صف‌گذاری سبک‌ها و اسکریپت‌ها (بدون jQuery، همه Vanilla JS)
 * ---------------------------------------------------------------------------
 */
if ( ! function_exists( 'pixva_assets' ) ) {
	/**
	 * ثبت و صف‌گذاری دارایی‌های فرانت‌اند.
	 *
	 * @return void
	 */
	function pixva_assets() {
		/*
		 * style.css پایه است و rtl.css توسط هسته (locale_stylesheet) به‌صورت مکمل بارگذاری می‌شود.
		 * از حالت replace استفاده نمی‌کنیم؛ آن حالت به‌دنبال style-rtl.css می‌گردد و style.css را حذف می‌کند.
		 */
		wp_enqueue_style( 'pixva-style', get_stylesheet_uri(), array(), PIXVA_VERSION );

		wp_enqueue_style( 'pixva-main', PIXVA_URI . '/assets/css/main.css', array( 'pixva-style' ), PIXVA_VERSION );

		// سبک کامپوننت‌ها تنها در صفحاتی که به آن نیاز دارند بارگذاری می‌شود.
		if ( pixva_needs_components_css() ) {
			wp_enqueue_style( 'pixva-components', PIXVA_URI . '/assets/css/components.css', array( 'pixva-main' ), PIXVA_VERSION );
		}

		wp_enqueue_script(
			'pixva-main',
			PIXVA_URI . '/assets/js/main.js',
			array(),
			PIXVA_VERSION,
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);

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

		wp_enqueue_script(
			'pixva-tools',
			PIXVA_URI . '/assets/js/interactive-tools.js',
			array( 'pixva-main' ),
			PIXVA_VERSION,
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);

		wp_localize_script(
			'pixva-tools',
			'pixvaVars',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'pixva_nonce' ),
			)
		);

		if ( pixva_needs_calculator_js() ) {
			wp_enqueue_script(
				'pixva-calculator',
				PIXVA_URI . '/assets/js/calculator.js',
				array( 'pixva-main' ),
				PIXVA_VERSION,
				array(
					'in_footer' => true,
					'strategy'  => 'defer',
				)
			);
		}

		if ( is_page_template( 'page-templates/page-tracking.php' ) ) {
			wp_enqueue_script(
				'pixva-tracker',
				PIXVA_URI . '/assets/js/tracker.js',
				array( 'pixva-main' ),
				PIXVA_VERSION,
				array(
					'in_footer' => true,
					'strategy'  => 'defer',
				)
			);
		}

		if ( pixva_needs_before_after_js() ) {
			wp_enqueue_script(
				'pixva-before-after',
				PIXVA_URI . '/assets/js/before-after.js',
				array( 'pixva-main' ),
				PIXVA_VERSION,
				array(
					'in_footer' => true,
					'strategy'  => 'defer',
				)
			);
		}

		if ( is_singular( 'post' ) ) {
			wp_enqueue_script(
				'pixva-toc',
				PIXVA_URI . '/assets/js/toc.js',
				array( 'pixva-main' ),
				PIXVA_VERSION,
				array(
					'in_footer' => true,
					'strategy'  => 'defer',
				)
			);
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

if ( ! function_exists( 'pixva_needs_components_css' ) ) {
	/**
	 * آیا صفحه جاری به سبک کامپوننت‌ها نیاز دارد؟
	 *
	 * @return bool
	 */
	function pixva_needs_components_css() {
		return (
			is_front_page()
			|| is_singular( 'post' )
			|| is_singular( 'tv_services' )
			|| is_singular( 'tv_brands' )
			|| is_singular( 'repair_cases' )
			|| is_page_template(
				array(
					'page-templates/page-calculator.php',
					'page-templates/page-tracking.php',
					'page-templates/page-error-codes.php',
					'page-templates/page-faq.php',
					'page-templates/page-contact.php',
					'page-templates/page-rates.php',
				)
			)
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
			is_front_page()
			|| is_page_template( 'page-templates/page-calculator.php' )
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
			is_front_page()
			|| is_singular( 'repair_cases' )
			|| is_page_template( 'page-templates/page-about.php' )
		);
	}
}

/*
 * ---------------------------------------------------------------------------
 * ۴) ابزارهای کمکی قالب
 * ---------------------------------------------------------------------------
 */
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

if ( ! function_exists( 'pixva_reading_time' ) ) {
	/**
	 * محاسبه زمان مطالعه مقاله به دقیقه.
	 *
	 * @param string $content محتوای مقاله.
	 * @return int
	 */
	function pixva_reading_time( $content ) {
		$text  = wp_strip_all_tags( $content );
		$words = preg_split( '/[\s،.!؟؛:]+/u', $text, -1, PREG_SPLIT_NO_EMPTY );
		$count = is_array( $words ) ? count( $words ) : 0;
		return max( 1, (int) ceil( $count / 180 ) ); // سرعت مطالعه رایج فارسی: ۱۸۰ واژه در دقیقه.
	}
}

if ( ! function_exists( 'pixva_heading_ids' ) ) {
	/**
	 * افزودن id یکتا به سرتیترهای H2 تا H4 محتوا (برای TOC و لینک‌دهی).
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
				return sprintf( '<%1$s id="%2$s"%3$s>%4$s</%1$s>', $tag, esc_attr( $slug ), $attrs, $text );
			},
			$content
		);
	}
}
add_filter( 'the_content', 'pixva_heading_ids', 5 );

if ( ! function_exists( 'pixva_build_toc' ) ) {
	/**
	 * ساخت جدول محتوا از سرتیترهای H2/H3 مقاله (خروجی سمت سرور برای سئو).
	 *
	 * @param string $content محتوای مقاله.
	 * @return string HTML فهرست یا رشته خالی.
	 */
	function pixva_build_toc( $content ) {
		if ( ! preg_match_all( '/<h([23]) id="([^"]+)"[^>]*>(.*?)<\/h\1>/is', $content, $matches, PREG_SET_ORDER ) ) {
			return '';
		}
		$items = '';
		foreach ( $matches as $m ) {
			$level  = (int) $m[1];
			$id     = esc_attr( $m[2] );
			$title  = wp_strip_all_tags( $m[3] );
			$items .= sprintf(
				'<li class="pixva-toc__item pixva-toc__item--l%1$d"><a href="#%2$s">%3$s</a></li>',
				$level,
				$id,
				esc_html( $title )
			);
		}
		if ( '' === $items ) {
			return '';
		}
		ob_start();
		?>
		<nav class="pixva-toc pixva-glass" aria-label="<?php esc_attr_e( 'فهرست مطالب مقاله', 'pixva' ); ?>">
			<button type="button" class="pixva-toc__toggle" aria-expanded="true" aria-controls="pixva-toc-list">
				<span class="pixva-toc__title"><?php esc_html_e( 'فهرست مطالب', 'pixva' ); ?></span>
				<span class="pixva-toc__chevron" aria-hidden="true"></span>
			</button>
			<ul id="pixva-toc-list" class="pixva-toc__list">
				<?php echo wp_kses_post( $items ); ?>
			</ul>
		</nav>
		<?php
		return (string) ob_get_clean();
	}
}

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
	 * کلاس‌های کمکی بدنه.
	 *
	 * @param array $classes کلاس‌های موجود.
	 * @return array
	 */
	function pixva_body_classes( $classes ) {
		$classes[] = 'pixva-theme';
		if ( is_front_page() ) {
			$classes[] = 'pixva-home';
		}
		if ( wp_is_mobile() ) {
			$classes[] = 'pixva-is-mobile';
		}
		return $classes;
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
	 * ثبت ناحیه ابزارک (فقط سایدبار وبلاگ).
	 *
	 * @return void
	 */
	function pixva_widget_sidebars() {
		register_sidebar(
			array(
				'name'          => esc_html__( 'سایدبار وبلاگ', 'pixva' ),
				'id'            => 'blog-sidebar',
				'description'   => esc_html__( 'ابزارک‌های نمایش‌داده‌شده در آرشیو و مقاله‌ها.', 'pixva' ),
				'before_widget' => '<section id="%1$s" class="pixva-card pixva-widget %2$s">',
				'after_widget'  => '</section>',
				'before_title'  => '<h3 class="pixva-widget__title">',
				'after_title'   => '</h3>',
			)
		);
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

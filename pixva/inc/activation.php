<?php
/**
 * خودکارسازی راه‌اندازی قالب پیکسوا (inc/activation.php)
 *
 * با هوک after_switch_theme انجام می‌شود:
 * - ساخت خودکار برگه‌های اصلی: خانه، مجله، محاسبه هزینه، پیگیری، کدهای خطا،
 *   درباره ما، تماس با ما، سوالات متداول، نرخ‌نامه، انبار قطعات، پنل مشتریان،
 *   خدمات سازمانی و چهار هاب تخصصی (ai-diagnostics، pricing-calculator،
 *   tracking-warranty و parts-b2b).
 * - ساخت منوی اصلی، تنظیم صفحه خانه/مجله و ثبت واژه‌های تاکسونومی.
 * - مقداردهی اولیه تنظیمات کارگاه در wp_options: آدرس پیش‌فرض علاءالدین تهران،
 *   تلفن، ساعت کاری و مختصات نقشه.
 *
 * هیچ داده نمایشی (پرونده تعمیر یا کارت گارانتی نمونه) ساخته نمی‌شود؛ همه
 * رکوردها باید واقعی و از طریق فرم‌های سایت یا پیشخوان ثبت گردند.
 *
 * @package Pixva
 * @since   1.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // خروج مستقیم غیرمجاز.
}

/*
 * ---------------------------------------------------------------------------
 * ۱) تنظیمات کارگاه در wp_options
 * ---------------------------------------------------------------------------
 */

if ( ! function_exists( 'pixva_workshop_defaults' ) ) {
	/**
	 * مقادیر پیش‌فرض تنظیمات کارگاه (پاساژ علاءالدین تهران).
	 *
	 * @return array<string, string>
	 */
	function pixva_workshop_defaults() {
		return array(
			'address'      => 'تهران، خیابان جمهوری، خیابان ناصرخسرو، پاساژ علاءالدین، طبقه ۴، واحد ۴۱۲',
			'phone'        => '02191009990',
			'hours'        => 'شنبه تا پنجشنبه ۰۹:۰۰ تا ۲۰:۰۰ · جمعه ۱۰:۰۰ تا ۱۶:۰۰',
			'hours_week'   => '09:00-20:00',
			'hours_friday' => '10:00-16:00',
			'latitude'     => '35.6841',
			'longitude'    => '51.4277',
		);
	}
}

if ( ! function_exists( 'pixva_workshop_settings' ) ) {
	/**
	 * تنظیمات مؤثر کارگاه (wp_options ادغام‌شده با مقادیر پیش‌فرض).
	 *
	 * برای آدرس و تلفن، مقدار سفارشی‌ساز (theme_mod) در صورت وجود مقدم است.
	 *
	 * @return array<string, string>
	 */
	function pixva_workshop_settings() {
		$defaults = pixva_workshop_defaults();
		$saved    = get_option( 'pixva_workshop_settings', array() );
		if ( ! is_array( $saved ) ) {
			$saved = array();
		}
		$out = wp_parse_args( $saved, $defaults );

		// سفارشی‌ساز مقدم بر مقدار ذخیره‌شده است.
		if ( function_exists( 'pixva_option' ) ) {
			$out['address'] = (string) pixva_option( 'pixva_workshop_address', $out['address'] );
			$out['phone']   = (string) pixva_option( 'pixva_support_phone', $out['phone'] );
		}

		return $out;
	}
}

if ( ! function_exists( 'pixva_workshop_setting' ) ) {
	/**
	 * خواندن یک تنظیم کارگاه.
	 *
	 * @param string $key کلید (address|phone|hours|hours_week|hours_friday|latitude|longitude).
	 * @return string
	 */
	function pixva_workshop_setting( $key ) {
		$settings = pixva_workshop_settings();
		$key      = (string) $key;
		return isset( $settings[ $key ] ) ? (string) $settings[ $key ] : '';
	}
}

/**
 * مقداردهی اولیه تنظیمات کارگاه در wp_options (بدون بازنویسی تنظیمات کاربر).
 *
 * @return void
 */
function pixva_install_workshop_options() {
	$saved = get_option( 'pixva_workshop_settings', array() );
	if ( ! is_array( $saved ) ) {
		$saved = array();
	}
	$merged = wp_parse_args( $saved, pixva_workshop_defaults() );
	if ( $merged !== $saved ) {
		update_option( 'pixva_workshop_settings', $merged );
	}
}

/*
 * ---------------------------------------------------------------------------
 * ۲) نقطه ورود فعال‌سازی قالب
 * ---------------------------------------------------------------------------
 */

/**
 * نقطه ورود فعال‌سازی قالب.
 *
 * @return void
 */
function pixva_on_switch_theme() {
	pixva_install_site();
	flush_rewrite_rules();
}
add_action( 'after_switch_theme', 'pixva_on_switch_theme' );

/**
 * نصب برگه، منو و نمونه محتوا.
 *
 * @return void
 */
function pixva_install_site() {
	if ( get_option( 'pixva_installed' ) ) {
		return;
	}

	pixva_install_identity();
	pixva_install_workshop_options();
	$pages = pixva_install_pages();
	pixva_install_menu( $pages );
	pixva_install_reading( $pages );
	pixva_install_terms();
	pixva_install_sample_content();

	update_option( 'pixva_installed', 1 );
	update_option( 'pixva_show_setup_notice', 1 );
	update_option( 'pixva_theme_version', PIXVA_VERSION );
}

/**
 * نام سایت را فقط اگر هنوز پیش‌فرض وردپرس است عوض می‌کند.
 *
 * @return void
 */
function pixva_install_identity() {
	$defaults = array( 'WordPress', 'وردپرس', 'وبلاگ من', 'My WordPress', 'My WordPress Website' );
	if ( in_array( get_option( 'blogname' ), $defaults, true ) ) {
		update_option( 'blogname', 'پیکسوا' );
	}
	if ( '' === (string) get_option( 'blogdescription' ) || in_array( get_option( 'blogdescription' ), array( 'Just another WordPress site', 'یک سایت وردپرسی دیگر' ), true ) ) {
		update_option( 'blogdescription', 'مرکز تخصصی تعمیر تلویزیون و نمایشگر' );
	}
	if ( '' === (string) get_option( 'permalink_structure' ) ) {
		update_option( 'permalink_structure', '/%postname%/' );
	}
	update_option( 'timezone_string', get_option( 'timezone_string' ) ? get_option( 'timezone_string' ) : 'Asia/Tehran' );
}

if ( ! function_exists( 'pixva_page_definitions' ) ) {
	/**
	 * تعریف برگه‌های اصلی قالب: نامک => [عنوان, قالب].
	 *
	 * @return array<string, array{0:string, 1:string}>
	 */
	function pixva_page_definitions() {
		return array(
			'home'        => array( 'خانه', '' ),
			'blog'        => array( 'مجله تخصصی', '' ),
			'calculator'  => array( 'محاسبه هزینه تعمیر', 'page-templates/page-calculator.php' ),
			'tracking'    => array( 'پیگیری وضعیت تعمیر', 'page-templates/page-tracking.php' ),
			'error-codes' => array( 'کدهای خطا و چشمک چراغ', 'page-templates/page-error-codes.php' ),
			'about'       => array( 'درباره پیکسوا', 'page-templates/page-about.php' ),
			'contact'     => array( 'تماس با ما', 'page-templates/page-contact.php' ),
			'faq'         => array( 'سوالات متداول', 'page-templates/page-faq.php' ),
			'rates'       => array( 'نرخ‌نامه و تعرفه‌ها', 'page-templates/page-rates.php' ),
			'b2b'         => array( 'خدمات سازمانی و B2B', 'page-templates/page-b2b.php' ),
			'client-hub'  => array( 'پنل مشتریان و گارانتی دیجیتال', 'page-templates/page-client-hub.php' ),
			'parts-stock' => array( 'استعلام انبار قطعات فابریک', 'page-templates/page-parts-stock.php' ),
			/* پنج هاب تخصصی Master Specification v25.0 */
			'ai-diagnostics'    => array( 'عیب‌یابی هوشمند با هوش مصنوعی', 'page-templates/page-hub-ai.php' ),
			'pricing-calculator' => array( 'محاسبه‌گر و نرخ‌نامه تعمیر', 'page-templates/page-hub-pricing.php' ),
			'tracking-warranty' => array( 'پیگیری پرونده و گارانتی', 'page-templates/page-hub-tracking.php' ),
			'parts-b2b'         => array( 'انبار قطعات و خدمات سازمانی', 'page-templates/page-hub-parts-b2b.php' ),
		);
	}
}

/**
 * ساخت برگه‌های لازم قالب (خانه، مجله، محاسبه هزینه، پیگیری، کدهای خطا، درباره ما،
 * تماس با ما، سوالات متداول و نرخ‌نامه).
 *
 * @return array<string, int>
 */
function pixva_install_pages() {
	$definitions = pixva_page_definitions();
	$ids         = array();
	foreach ( $definitions as $slug => $item ) {
		$existing = get_page_by_path( $slug );
		if ( $existing instanceof WP_Post ) {
			$ids[ $slug ] = (int) $existing->ID;
			if ( '' !== $item[1] ) {
				update_post_meta( $existing->ID, '_wp_page_template', $item[1] );
			}
			continue;
		}
		$page_id = wp_insert_post(
			array(
				'post_title'   => $item[0],
				'post_name'    => $slug,
				'post_status'  => 'publish',
				'post_type'    => 'page',
				'post_content' => '',
			)
		);
		if ( ! is_wp_error( $page_id ) && $page_id ) {
			$ids[ $slug ] = (int) $page_id;
			if ( '' !== $item[1] ) {
				update_post_meta( $page_id, '_wp_page_template', $item[1] );
			}
		}
	}
	return $ids;
}

/**
 * منوی اصلی، فقط اگر جایگاهی خالی باشد.
 *
 * @param array $pages شناسه برگه‌ها.
 * @return void
 */
function pixva_install_menu( $pages ) {
	$locations = get_theme_mod( 'nav_menu_locations', array() );
	if ( ! empty( $locations['primary'] ) ) {
		return;
	}
	$menu_id = wp_create_nav_menu( 'منوی پیکسوا' );
	if ( is_wp_error( $menu_id ) ) {
		return;
	}
	$items = array(
		array(
			'title' => 'خانه',
			'type'  => 'custom',
			'url'   => home_url( '/' ),
		),
		array(
			'title' => 'خدمات',
			'type'  => 'custom',
			'url'   => get_post_type_archive_link( 'tv_services' ),
		),
		array(
			'title' => 'برندها',
			'type'  => 'custom',
			'url'   => get_post_type_archive_link( 'tv_brands' ),
		),
	);
	foreach ( array( 'calculator', 'tracking', 'error-codes', 'blog', 'about', 'contact' ) as $slug ) {
		if ( empty( $pages[ $slug ] ) ) {
			continue;
		}
		$short_titles = array(
			'calculator'  => 'محاسبه هزینه',
			'tracking'    => 'پیگیری',
			'error-codes' => 'کدهای خطا',
			'blog'        => 'مجله',
			'about'       => 'درباره ما',
			'contact'     => 'تماس',
		);
		$items[]      = array(
			'title' => isset( $short_titles[ $slug ] ) ? $short_titles[ $slug ] : get_the_title( $pages[ $slug ] ),
			'type'  => 'page',
			'id'    => $pages[ $slug ],
		);
	}
	foreach ( $items as $item ) {
		if ( 'page' === $item['type'] ) {
			wp_update_nav_menu_item(
				$menu_id,
				0,
				array(
					'menu-item-title'     => $item['title'],
					'menu-item-object'    => 'page',
					'menu-item-object-id' => $item['id'],
					'menu-item-type'      => 'post_type',
					'menu-item-status'    => 'publish',
				)
			);
		} else {
			wp_update_nav_menu_item(
				$menu_id,
				0,
				array(
					'menu-item-title'  => $item['title'],
					'menu-item-url'    => $item['url'],
					'menu-item-type'   => 'custom',
					'menu-item-status' => 'publish',
				)
			);
		}
	}
	$locations['primary'] = (int) $menu_id;
	if ( empty( $locations['footer'] ) ) {
		$locations['footer'] = (int) $menu_id;
	}
	set_theme_mod( 'nav_menu_locations', $locations );
}

/**
 * اگر سایت هنوز صفحه ایستا ندارد، خانه و مجله را تنظیم می‌کند.
 *
 * @param array $pages شناسه برگه‌ها.
 * @return void
 */
function pixva_install_reading( $pages ) {
	if ( (int) get_option( 'page_on_front' ) > 0 ) {
		return;
	}
	$counts = wp_count_posts( 'post' );
	if ( isset( $counts->publish ) && (int) $counts->publish > 0 ) {
		return;
	}
	if ( ! empty( $pages['home'] ) ) {
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', (int) $pages['home'] );
	}
	if ( ! empty( $pages['blog'] ) && ! (int) get_option( 'page_for_posts' ) ) {
		update_option( 'page_for_posts', (int) $pages['blog'] );
	}
}

/**
 * ساخت ترم‌های خرابی و تکنولوژی.
 *
 * @return void
 */
function pixva_install_terms() {
	$problems = array(
		'no-picture'    => 'بی‌تصویری',
		'lines'         => 'خطوط عمودی و افقی',
		'no-power'      => 'خاموشی کامل',
		'no-sound'      => 'قطع صدا',
		'blink'         => 'چشمک زدن چراغ',
		'backlight'     => 'تعویض بک‌لایت',
		'water'         => 'آب‌خوردگی',
		'panel-replace' => 'تعویض کامل پنل',
	);
	$techs    = array(
		'led'      => 'LED',
		'qled'     => 'QLED',
		'oled'     => 'OLED',
		'plasma'   => 'Plasma',
		'microled' => 'MicroLED',
	);
	foreach ( $problems as $slug => $name ) {
		if ( ! term_exists( $slug, 'tv_problem' ) ) {
			wp_insert_term( $name, 'tv_problem', array( 'slug' => $slug ) );
		}
	}
	foreach ( $techs as $slug => $name ) {
		if ( ! term_exists( $slug, 'tv_tech' ) ) {
			wp_insert_term( $name, 'tv_tech', array( 'slug' => $slug ) );
		}
	}
	if ( ! term_exists( 'diagnostics', 'category' ) ) {
		wp_insert_term( 'عیب‌یابی', 'category', array( 'slug' => 'diagnostics' ) );
	}
}

/**
 * اعلان یک‌باره پیشخوان پس از نصب.
 *
 * @return void
 */
function pixva_setup_admin_notice() {
	if ( ! current_user_can( 'manage_options' ) || ! get_option( 'pixva_show_setup_notice' ) ) {
		return;
	}
	if ( isset( $_GET['pixva_dismiss_notice'] ) && check_admin_referer( 'pixva_dismiss_notice' ) ) {
		delete_option( 'pixva_show_setup_notice' );
		return;
	}
	$url = wp_nonce_url( add_query_arg( 'pixva_dismiss_notice', '1' ), 'pixva_dismiss_notice' );
	echo '<div class="notice notice-info"><p>';
	echo esc_html__( 'پیکسوا برگه‌های پنج هاب، منوها و محتوای آغازین (خدمات، برندها و دو مقاله نمونه) را ساخت. کلید Gemini را در مرکز کنترل ← عمومی & AI وارد کنید و محتوای نمونه را با داده واقعی کارگاه جایگزین کنید.', 'pixva' );
	echo ' <a href="' . esc_url( $url ) . '">' . esc_html__( 'متوجه شدم', 'pixva' ) . '</a></p></div>';
}
add_action( 'admin_notices', 'pixva_setup_admin_notice' );

/*
 * ---------------------------------------------------------------------------
 * ۳) مسیر ارتقا برای نصب‌های قبلی (نسخه‌های ۱٫x قبلی)
 * ---------------------------------------------------------------------------
 */

/**
 * افزودن برگه‌ها/تنظیمات جدید نسخه ۱٫۲ به نصب‌های موجود.
 *
 * @return void
 */
function pixva_maybe_upgrade() {
	$version = defined( 'PIXVA_VERSION' ) ? PIXVA_VERSION : '1.2.0';
	$spec    = defined( 'PIXVA_SPEC_VERSION' ) ? PIXVA_SPEC_VERSION : '25.0';

	$stored_version = (string) get_option( 'pixva_theme_version' );
	$stored_spec    = (string) get_option( 'pixva_spec_version' );

	if ( $stored_version === $version && $stored_spec === $spec ) {
		return;
	}

	pixva_install_workshop_options();

	// ساخت برگه‌هایی که هنوز وجود ندارند (هاب‌های پنج‌گانه در ارتقا به v25.0).
	foreach ( pixva_page_definitions() as $slug => $item ) {
		$existing = get_page_by_path( $slug );
		if ( ! $existing instanceof WP_Post ) {
			pixva_install_pages();
			break;
		}
	}

	/*
	 * اگر ترتیب سکشن‌های خانه از نسخه قدیمی مانده باشد، بازنشانی می‌شود تا ترتیب
	 * جدید (هیرو ← ویجت قیمت ← خدمات ← مسیر تعمیر ← قبل/بعد ← نظرات) اعمال گردد.
	 */
	if ( '' !== $stored_spec && $stored_spec !== $spec ) {
		$order = (string) get_theme_mod( 'pixva_sections_order', '' );
		if ( '' !== $order && ( false === strpos( $order, 'journey' ) || false === strpos( $order, 'quote' ) ) ) {
			remove_theme_mod( 'pixva_sections_order' );
		}
	}

	update_option( 'pixva_theme_version', $version );
	update_option( 'pixva_spec_version', $spec );
}
add_action( 'init', 'pixva_maybe_upgrade', 5 );

<?php
/**
 * تنظیمات سفارشی‌ساز (Customizer) قالب پیکسوا
 *
 * بخش‌ها: هدر، فوتر، صفحه اصلی (ترتیب و فعال‌بودن سکشن‌ها).
 *
 * @package Pixva
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * فهرست سکشن‌های صفحه اصلی به‌ترتیب پیش‌فرض.
 *
 * @return array<string, string>
 */
function pixva_home_sections() {
	return array(
		'hero'          => esc_html__( 'هیرو', 'pixva' ),
		'problems'      => esc_html__( 'یابنده مشکل', 'pixva' ),
		'brands'        => esc_html__( 'برندها', 'pixva' ),
		'services'      => esc_html__( 'خدمات', 'pixva' ),
		'tools'         => esc_html__( 'تشخیص، برآورد، تستر، پیگیری', 'pixva' ),
		'before_after'  => esc_html__( 'نمونه قبل و بعد', 'pixva' ),
		'process'       => esc_html__( 'فرآیند کار', 'pixva' ),
		'why'           => esc_html__( 'چرا پیکسوا', 'pixva' ),
		'testimonials'  => esc_html__( 'نظرات مشتریان', 'pixva' ),
		'faq'           => esc_html__( 'سوالات متداول', 'pixva' ),
		'blog'          => esc_html__( 'مجله', 'pixva' ),
		'calculator'    => esc_html__( 'برآورد جدا (اگر ابزارها خاموش باشد)', 'pixva' ),
		'errors'        => esc_html__( 'کد خطا جدا', 'pixva' ),
		'tv_simulator'  => esc_html__( 'شبیه‌ساز جدا', 'pixva' ),
		'screen_tester' => esc_html__( 'تستر جدا', 'pixva' ),
		'dispatch_hub'  => esc_html__( 'هاب اعزام جدا', 'pixva' ),
	);
}

/**
 * ابزارهای تکراری پیش‌فرض خاموش‌اند؛ نسخه تمیزشان داخل سکشن tools است.
 *
 * @param string $key کلید سکشن.
 * @return bool
 */
function pixva_section_is_default_on( $key ) {
	return ! in_array( $key, array( 'calculator', 'errors', 'tv_simulator', 'screen_tester', 'dispatch_hub' ), true );
}

/**
 * مقدار پیش‌فرض ترتیب سکشن‌ها.
 *
 * @return string
 */
function pixva_default_section_order() {
	return implode( ',', array_keys( pixva_home_sections() ) );
}

/**
 * اعتبارسنجی و sanitize ترتیب سکشن‌ها.
 *
 * @param string $value ورودی کاربر.
 * @return string
 */
function pixva_sanitize_section_order( $value ) {
	$allowed = array_keys( pixva_home_sections() );
	$parts   = array_map( 'trim', explode( ',', (string) $value ) );
	$clean   = array();
	foreach ( $parts as $part ) {
		if ( in_array( $part, $allowed, true ) && ! in_array( $part, $clean, true ) ) {
			$clean[] = $part;
		}
	}
	// سکشن‌های جاافتاده به انتهای فهرست اضافه می‌شوند تا هیچ‌گاه گم نشوند.
	foreach ( $allowed as $key ) {
		if ( ! in_array( $key, $clean, true ) ) {
			$clean[] = $key;
		}
	}
	return implode( ',', $clean );
}

/**
 * Sanitize شماره تلفن.
 *
 * @param string $value ورودی.
 * @return string
 */
function pixva_sanitize_phone( $value ) {
	return sanitize_text_field( $value );
}

/**
 * Sanitize کلید شبکه اجتماعی.
 *
 * @param string $value ورودی.
 * @return string
 */
function pixva_sanitize_social_key( $value ) {
	$allowed = array( 'instagram', 'telegram', 'whatsapp', 'linkedin', 'youtube' );
	return in_array( $value, $allowed, true ) ? $value : 'instagram';
}

/**
 * ثبت تنظیمات و کنترل‌های Customizer.
 *
 * @param WP_Customize_Manager $wp_customize مدیریت سفارشی‌ساز.
 * @return void
 */
function pixva_customize_register( $wp_customize ) {

	/* ----------------------------- بخش هدر ----------------------------- */
	$wp_customize->add_section(
		'pixva_header',
		array(
			'title'    => esc_html__( 'پیکسوا: هدر', 'pixva' ),
			'priority' => 30,
		)
	);

	$wp_customize->add_setting(
		'pixva_logo_light',
		array(
			'default'           => '',
			'sanitize_callback' => 'esc_url_raw',
			'transport'         => 'refresh',
		)
	);
	$wp_customize->add_control(
		new WP_Customize_Image_Control(
			$wp_customize,
			'pixva_logo_light',
			array(
				'label'   => esc_html__( 'لوگوی روشن (برای هدر تیره)', 'pixva' ),
				'section' => 'pixva_header',
			)
		)
	);

	$wp_customize->add_setting(
		'pixva_logo_dark',
		array(
			'default'           => '',
			'sanitize_callback' => 'esc_url_raw',
		)
	);
	$wp_customize->add_control(
		new WP_Customize_Image_Control(
			$wp_customize,
			'pixva_logo_dark',
			array(
				'label'   => esc_html__( 'لوگوی تیره (برای پس‌زمینه روشن)', 'pixva' ),
				'section' => 'pixva_header',
			)
		)
	);

	$wp_customize->add_setting(
		'pixva_support_phone',
		array(
			'default'           => '02191009990',
			'sanitize_callback' => 'pixva_sanitize_phone',
			'transport'         => 'postMessage',
		)
	);
	$wp_customize->add_control(
		'pixva_support_phone',
		array(
			'label'   => esc_html__( 'شماره تلفن پشتیبانی', 'pixva' ),
			'section' => 'pixva_header',
			'type'    => 'text',
		)
	);

	$wp_customize->add_setting(
		'pixva_header_cta_text',
		array(
			'default'           => 'درخواست تعمیر',
			'sanitize_callback' => 'sanitize_text_field',
			'transport'         => 'postMessage',
		)
	);
	$wp_customize->add_control(
		'pixva_header_cta_text',
		array(
			'label'   => esc_html__( 'متن دکمه CTA هدر', 'pixva' ),
			'section' => 'pixva_header',
			'type'    => 'text',
		)
	);

	$wp_customize->add_setting(
		'pixva_header_cta_url',
		array(
			'default'           => '/contact/',
			'sanitize_callback' => 'esc_url_raw',
		)
	);
	$wp_customize->add_control(
		'pixva_header_cta_url',
		array(
			'label'   => esc_html__( 'لینک دکمه CTA هدر', 'pixva' ),
			'section' => 'pixva_header',
			'type'    => 'url',
		)
	);

	$wp_customize->add_setting(
		'pixva_topbar_enabled',
		array(
			'default'           => true,
			'sanitize_callback' => 'pixva_sanitize_checkbox',
		)
	);
	$wp_customize->add_control(
		'pixva_topbar_enabled',
		array(
			'label'   => esc_html__( 'نمایش نوار اعلان بالای سایت', 'pixva' ),
			'section' => 'pixva_header',
			'type'    => 'checkbox',
		)
	);

	$wp_customize->add_setting(
		'pixva_topbar_text',
		array(
			'default'           => 'ارسال رایگان دستگاه در تهران | گارانتی ۱۸۰ روزه تعمیرات',
			'sanitize_callback' => 'sanitize_text_field',
			'transport'         => 'postMessage',
		)
	);
	$wp_customize->add_control(
		'pixva_topbar_text',
		array(
			'label'   => esc_html__( 'متن نوار اعلان', 'pixva' ),
			'section' => 'pixva_header',
			'type'    => 'text',
		)
	);

	/* ----------------------------- بخش فوتر ---------------------------- */
	$wp_customize->add_section(
		'pixva_footer',
		array(
			'title'    => esc_html__( 'پیکسوا: فوتر', 'pixva' ),
			'priority' => 31,
		)
	);

	$wp_customize->add_setting(
		'pixva_copyright',
		array(
			'default'           => '© تمامی حقوق برای مرکز تخصصی پیکسوا محفوظ است.',
			'sanitize_callback' => 'sanitize_text_field',
			'transport'         => 'postMessage',
		)
	);
	$wp_customize->add_control(
		'pixva_copyright',
		array(
			'label'   => esc_html__( 'متن کپی‌رایت', 'pixva' ),
			'section' => 'pixva_footer',
			'type'    => 'text',
		)
	);

	$wp_customize->add_setting(
		'pixva_workshop_address',
		array(
			'default'           => 'تهران، خیابان جمهوری، خیابان ناصرخسرو، پاساژ علاءالدین، طبقه ۴، واحد ۴۱۲',
			'sanitize_callback' => 'sanitize_text_field',
		)
	);
	$wp_customize->add_control(
		'pixva_workshop_address',
		array(
			'label'   => esc_html__( 'آدرس کارگاه', 'pixva' ),
			'section' => 'pixva_footer',
			'type'    => 'text',
		)
	);

	$wp_customize->add_setting(
		'pixva_footer_phones',
		array(
			'default'           => '02191009990,09120000000',
			'sanitize_callback' => 'sanitize_text_field',
		)
	);
	$wp_customize->add_control(
		'pixva_footer_phones',
		array(
			'label'   => esc_html__( 'شماره‌های تماس (با ویرگول جدا کنید)', 'pixva' ),
			'section' => 'pixva_footer',
			'type'    => 'text',
		)
	);

	$wp_customize->add_setting(
		'pixva_enamad_url',
		array(
			'default'           => '',
			'sanitize_callback' => 'esc_url_raw',
		)
	);
	$wp_customize->add_control(
		'pixva_enamad_url',
		array(
			'label'   => esc_html__( 'لینک نماد اعتماد الکترونیکی (اینماد)', 'pixva' ),
			'section' => 'pixva_footer',
			'type'    => 'url',
		)
	);

	$wp_customize->add_setting(
		'pixva_enamad_image',
		array(
			'default'           => '',
			'sanitize_callback' => 'esc_url_raw',
		)
	);
	$wp_customize->add_control(
		new WP_Customize_Image_Control(
			$wp_customize,
			'pixva_enamad_image',
			array(
				'label'   => esc_html__( 'تصویر اینماد', 'pixva' ),
				'section' => 'pixva_footer',
			)
		)
	);

	$wp_customize->add_setting(
		'pixva_samandehi_url',
		array(
			'default'           => '',
			'sanitize_callback' => 'esc_url_raw',
		)
	);
	$wp_customize->add_control(
		'pixva_samandehi_url',
		array(
			'label'   => esc_html__( 'لینک نشان ساماندهی', 'pixva' ),
			'section' => 'pixva_footer',
			'type'    => 'url',
		)
	);

	$wp_customize->add_setting(
		'pixva_samandehi_image',
		array(
			'default'           => '',
			'sanitize_callback' => 'esc_url_raw',
		)
	);
	$wp_customize->add_control(
		new WP_Customize_Image_Control(
			$wp_customize,
			'pixva_samandehi_image',
			array(
				'label'   => esc_html__( 'تصویر نشان ساماندهی', 'pixva' ),
				'section' => 'pixva_footer',
			)
		)
	);

	// شبکه‌های اجتماعی.
	$socials = array(
		'instagram' => esc_html__( 'اینستاگرام', 'pixva' ),
		'telegram'  => esc_html__( 'تلگرام', 'pixva' ),
		'whatsapp'  => esc_html__( 'واتساپ', 'pixva' ),
		'linkedin'  => esc_html__( 'لینکدین', 'pixva' ),
		'youtube'   => esc_html__( 'یوتیوب', 'pixva' ),
	);
	foreach ( $socials as $key => $label ) {
		$wp_customize->add_setting(
			'pixva_social_' . $key,
			array(
				'default'           => '',
				'sanitize_callback' => 'esc_url_raw',
			)
		);
		$wp_customize->add_control(
			'pixva_social_' . $key,
			array(
				/* translators: %s: نام شبکه اجتماعی */
				'label'   => sprintf( esc_html__( 'لینک %s', 'pixva' ), $label ),
				'section' => 'pixva_footer',
				'type'    => 'url',
			)
		);
	}

	$wp_customize->add_setting(
		'pixva_whatsapp_number',
		array(
			'default'           => '989120000000',
			'sanitize_callback' => 'pixva_sanitize_phone',
		)
	);
	$wp_customize->add_control(
		'pixva_whatsapp_number',
		array(
			'label'   => esc_html__( 'شماره واتساپ (قالب بین‌المللی بدون +)', 'pixva' ),
			'section' => 'pixva_footer',
			'type'    => 'text',
		)
	);

	/* --------------------------- بخش صفحه اصلی -------------------------- */
	$wp_customize->add_section(
		'pixva_homepage',
		array(
			'title'       => esc_html__( 'پیکسوا: سکشن‌های صفحه اصلی', 'pixva' ),
			'description' => esc_html__( 'با غیرفعال‌کردن هر گزینه، سکشن مربوطه حذف می‌شود. ترتیب نمایش را با فهرست ترتیب تنظیم کنید.', 'pixva' ),
			'priority'    => 32,
		)
	);

	foreach ( pixva_home_sections() as $key => $label ) {
		$wp_customize->add_setting(
			'pixva_section_' . $key,
			array(
				'default'           => pixva_section_is_default_on( $key ),
				'sanitize_callback' => 'pixva_sanitize_checkbox',
			)
		);
		$wp_customize->add_control(
			'pixva_section_' . $key,
			array(
				/* translators: %s: نام سکشن */
				'label'   => sprintf( esc_html__( 'نمایش سکشن: %s', 'pixva' ), $label ),
				'section' => 'pixva_homepage',
				'type'    => 'checkbox',
			)
		);
	}

	$wp_customize->add_setting(
		'pixva_sections_order',
		array(
			'default'           => pixva_default_section_order(),
			'sanitize_callback' => 'pixva_sanitize_section_order',
		)
	);
	$wp_customize->add_control(
		'pixva_sections_order',
		array(
			'label'       => esc_html__( 'ترتیب سکشن‌ها (با ویرگول جدا کنید)', 'pixva' ),
			'description' => esc_html__( 'کلیدهای مجاز: hero, problems, services, before_after, process, testimonials, tools, faq, brands, blog', 'pixva' ),
			'section'     => 'pixva_homepage',
			'type'        => 'text',
		)
	);

	$wp_customize->add_setting(
		'pixva_hero_title',
		array(
			'default'           => 'تلویزیونت خراب شده؟ تخصصی تعمیرش می‌کنیم.',
			'sanitize_callback' => 'sanitize_text_field',
		)
	);
	$wp_customize->add_control(
		'pixva_hero_title',
		array(
			'label'   => esc_html__( 'تیتر اصلی هیرو', 'pixva' ),
			'section' => 'pixva_homepage',
			'type'    => 'text',
		)
	);

	$wp_customize->add_setting(
		'pixva_hero_subtitle',
		array(
			'default'           => 'هزینه را قبل از باز کردن دستگاه می‌بینید. گارانتی کتبی ۱۸۰ روز است و پیک، دستگاه را در تهران می‌آورد.',
			'sanitize_callback' => 'sanitize_textarea_field',
		)
	);
	$wp_customize->add_control(
		'pixva_hero_subtitle',
		array(
			'label'   => esc_html__( 'زیرتیتر هیرو', 'pixva' ),
			'section' => 'pixva_homepage',
			'type'    => 'textarea',
		)
	);
}
add_action( 'customize_register', 'pixva_customize_register' );

/**
 * Sanitize چک‌باکس.
 *
 * @param mixed $value ورودی.
 * @return bool
 */
function pixva_sanitize_checkbox( $value ) {
	return (bool) $value;
}

/**
 * خواندن راحت تنظیمات قالب با مقدار پیش‌فرض.
 *
 * @param string $key     نام تنظیم.
 * @param mixed  $default مقدار پیش‌فرض.
 * @return mixed
 */
function pixva_option( $key, $default = '' ) {
	$value = get_theme_mod( $key, $default );
	return ( '' === $value || null === $value ) ? $default : $value;
}

/**
 * یک‌بار چیدمان صفحه اصلی را به روایت کارگاه برمی‌گرداند.
 * انتخاب بعدی سفارشی‌ساز دست نمی‌خورد.
 *
 * @return void
 */
function pixva_migrate_home_ia() {
	if ( '1.6.0' === get_option( 'pixva_ia_version' ) ) {
		return;
	}

	set_theme_mod( 'pixva_sections_order', pixva_default_section_order() );
	foreach ( array( 'calculator', 'errors', 'tv_simulator', 'screen_tester', 'dispatch_hub' ) as $key ) {
		set_theme_mod( 'pixva_section_' . $key, false );
	}
	set_theme_mod( 'pixva_section_tools', true );
	set_theme_mod( 'pixva_section_problems', true );

	$old_titles = array(
		'',
		'تعمیر تخصصی تلویزیون و نمایشگر، با گارانتی کتبی',
		'تعمیر تلویزیون، بدون تعویض بی‌دلیل پنل',
	);
	$title = (string) get_theme_mod( 'pixva_hero_title', '' );
	if ( in_array( $title, $old_titles, true ) ) {
		set_theme_mod( 'pixva_hero_title', 'تلویزیونت خراب شده؟ تخصصی تعمیرش می‌کنیم.' );
	}

	$old_leads = array(
		'',
		'مرکز تخصصی پیکسوا با تجهیز کارگاهی پیشرفته، تعمیر پنل، بک‌لایت و بردهای OLED، QLED و LED را با قطعات فابریک و ضمانت کتبی ۶ ماهه انجام می‌دهد.',
		'مرکز تخصصی پیکسوا با تجهیز کارگاهی کامل، تعمیر پنل، بک‌لایت و بردهای تلویزیون‌های OLED، QLED و LED را در محل یا کارگاه انجام می‌دهد.',
		'قاب را باز می‌کنیم، مسیر ارزان‌تر را اول امتحان می‌کنیم، و هزینه را قبل از تعویض قطعه می‌نویسیم. گارانتی کتبی ۱۸۰ روز است.',
		'هزینه را قبل از باز کردن دستگاه می‌بینید. گارانتی کتبی ۱۸۰ روز است و پیک، دستگاه را در تهران می‌آورد.',
	);
	$lead = (string) get_theme_mod( 'pixva_hero_subtitle', '' );
	if ( in_array( $lead, $old_leads, true ) ) {
		set_theme_mod( 'pixva_hero_subtitle', 'تشخیص دقیق، تعمیر تخصصی و گارانتی کتبی برای انواع تلویزیون. هزینه نهایی بعد از بررسی دستگاه نوشته می‌شود.' );
	}

	$cta = (string) get_theme_mod( 'pixva_header_cta_text', '' );
	if ( '' === $cta || in_array( $cta, array( 'درخواست مشاوره رایگان', 'برآورد هزینه' ), true ) ) {
		set_theme_mod( 'pixva_header_cta_text', 'درخواست تعمیر' );
	}
	$cta_url = (string) get_theme_mod( 'pixva_header_cta_url', '' );
	if ( '' === $cta_url || in_array( $cta_url, array( '/contact/', '/calculator/' ), true ) ) {
		set_theme_mod( 'pixva_header_cta_url', '/contact/' );
	}

	update_option( 'pixva_ia_version', '1.6.0', false );
}
add_action( 'after_setup_theme', 'pixva_migrate_home_ia', 20 );

/**
 * فهرست نهایی سکشن‌های فعال صفحه اصلی به‌ترتیب کاربر.
 *
 * @return array<string>
 */
function pixva_active_home_sections() {
	$order = array_map( 'trim', explode( ',', (string) pixva_option( 'pixva_sections_order', pixva_default_section_order() ) ) );
	$order = pixva_sanitize_section_order( implode( ',', $order ) );
	$keys  = array_map( 'trim', explode( ',', $order ) );
	$keys  = array_filter(
		$keys,
		static function ( $key ) {
			return (bool) pixva_option( 'pixva_section_' . $key, pixva_section_is_default_on( $key ) );
		}
	);
	return array_values( $keys );
}

/**
 * پشتیبانی postMessage برای کنترل‌های انتخاب‌شده.
 *
 * @return void
 */
function pixva_customize_preview_js() {
	wp_enqueue_script(
		'pixva-customizer-preview',
		PIXVA_URI . '/assets/js/customizer-preview.js',
		array( 'customize-preview' ),
		PIXVA_VERSION,
		array(
			'in_footer' => true,
			'strategy'  => 'defer',
		)
	);
}
add_action( 'customize_preview_init', 'pixva_customize_preview_js' );

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
		'hero'         => esc_html__( 'هیرو (بنر اصلی)', 'pixva' ),
		'calculator'   => esc_html__( 'محاسبه‌گر هزینه', 'pixva' ),
		'services'     => esc_html__( 'خدمات تخصصی', 'pixva' ),
		'process'      => esc_html__( 'مسیر پذیرش تا تحویل', 'pixva' ),
		'before_after' => esc_html__( 'اسلایدر قبل/بعد', 'pixva' ),
		'brands'       => esc_html__( 'برندها', 'pixva' ),
		'errors'       => esc_html__( 'کدهای خطا', 'pixva' ),
		'testimonials' => esc_html__( 'نظرات مشتریان', 'pixva' ),
		'faq'          => esc_html__( 'سوالات متداول', 'pixva' ),
		'blog'         => esc_html__( 'مجله تخصصی', 'pixva' ),
	);
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
			'default'           => 'درخواست مشاوره رایگان',
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
				'default'           => true,
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
			'description' => esc_html__( 'کلیدهای مجاز: hero, calculator, services, process, before_after, brands, errors, testimonials, faq, blog', 'pixva' ),
			'section'     => 'pixva_homepage',
			'type'        => 'text',
		)
	);

	$wp_customize->add_setting(
		'pixva_hero_title',
		array(
			'default'           => 'تعمیر تخصصی تلویزیون و نمایشگر، با گارانتی کتبی',
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
			'default'           => 'مرکز تخصصی پیکسوا با تجهیز کارگاهی کامل، تعمیر پنل، بک‌لایت و بردهای تلویزیون‌های OLED، QLED و LED را در محل یا کارگاه انجام می‌دهد.',
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
			return (bool) pixva_option( 'pixva_section_' . $key, true );
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

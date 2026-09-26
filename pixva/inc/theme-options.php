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
	/*
	 * ترتیب کلیدها همان ترتیب پیش‌فرض صفحه است (لایه ۱٫۵٫۰):
	 * چیدمان مینیمال = هیرو ← سه مزیت ← نمونه‌کار تصویری ← نظرات مشتریان.
	 * بقیه سکشن‌ها خاموش‌اند ولی از منوی مگا و هاب‌ها در دسترس می‌مانند.
	 */
	return array(
		'hero'          => esc_html__( 'هیرو با عنوان و دکمه استعلام سریع قیمت', 'pixva' ),
		'advantages'    => esc_html__( 'سه مزیت کلیدی کارگاه (گارانتی، اعزام، قطعه فابریک)', 'pixva' ),
		'work'          => esc_html__( 'نمونه‌کار تصویری گالری تعمیرات', 'pixva' ),
		'testimonials'  => esc_html__( 'نظرات مشتریان', 'pixva' ),
		'quote'         => esc_html__( 'ویجت استعلام سریع قیمت (نرخ‌نامه ۱۴۰۵)', 'pixva' ),
		'services'      => esc_html__( 'چهار کارت خدمت تخصصی', 'pixva' ),
		'journey'       => esc_html__( 'مسیر پنج‌مرحله‌ای تعمیر با نقطه نورانی', 'pixva' ),
		'before_after'  => esc_html__( 'اسلایدر قبل/بعد صحنه واحد (ابزار ۹)', 'pixva' ),
		'dispatch_hub'  => esc_html__( 'هاب اعزام اورژانسی و پیگیری پرونده (ابزار ۱۷ و ۱۹)', 'pixva' ),
		'order_wizard'  => esc_html__( 'جادوگر ثبت سفارش تعمیر — موتور CRM (ابزار ۵)', 'pixva' ),
		'screen_tester' => esc_html__( 'تستر پیکسل‌سوختگی RGB و احیای OLED (ابزار ۶ و ۷)', 'pixva' ),
		'errors'        => esc_html__( 'کدهای خطا و چشمک چراغ پاور', 'pixva' ),
		'brands'        => esc_html__( 'برندها و ضرایب نرخ‌نامه', 'pixva' ),
		'faq'           => esc_html__( 'سوالات متداول', 'pixva' ),
		'blog'          => esc_html__( 'مجله تخصصی', 'pixva' ),
		'process'       => esc_html__( 'مسیر چهارمرحله‌ای پذیرش تا تحویل (قدیمی)', 'pixva' ),
	);
}

/**
 * وضعیت پیش‌فرض نمایش هر سکشن.
 *
 * چیدمان پیش‌فرض مینیمال (Master Prompt v6): هیرو، سه مزیت کلیدی،
 * نمونه‌کار تصویری و نظرات مشتریان؛ بقیه سکشن‌ها خاموش‌اند.
 *
 * @return array<string, bool>
 */
function pixva_home_section_defaults() {
	/*
	 * صفحه اصلی مینیمال (Master Prompt v6): فقط چهار سکشن روشن است و تمام
	 * ابزارها به منوی مگا و صفحات اختصاصی منتقل شده‌اند. کاربر می‌تواند از
	 * بخش «پیکسوا: سکشن‌های صفحه اصلی» هرکدام را دوباره فعال کند.
	 */
	$defaults = array(
		'hero'          => true,
		'advantages'    => true,
		'work'          => true,
		'testimonials'  => true,
		'quote'         => false,
		'services'      => false,
		'journey'       => false,
		'before_after'  => false,
		'dispatch_hub'  => false,
		'order_wizard'  => false,
		'screen_tester' => false,
		'errors'        => false,
		'brands'        => false,
		'faq'           => false,
		'blog'          => false,
		'process'       => false,
	);

	foreach ( array_keys( pixva_home_sections() ) as $key ) {
		if ( ! isset( $defaults[ $key ] ) ) {
			$defaults[ $key ] = true;
		}
	}

	return apply_filters( 'pixva_home_section_defaults', $defaults );
}

/**
 * مقدار پیش‌فرض ترتیب سکشن‌ها.
 *
 * @return string
 */
function pixva_default_section_order() {
	/*
	 * ترتیب فرود طبق Master Prompt v6: هیرو ← سه مزیت ← نمونه‌کار تصویری ←
	 * نظرات مشتریان، سپس سایر سکشن‌ها با همان ترتیب فهرست بالا.
	 */
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

	$section_defaults = pixva_home_section_defaults();
	foreach ( pixva_home_sections() as $key => $label ) {
		$wp_customize->add_setting(
			'pixva_section_' . $key,
			array(
				'default'           => isset( $section_defaults[ $key ] ) ? $section_defaults[ $key ] : true,
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

	/* ------- متن‌های چیدمان مینیمال و منوی مگا (لایه ۱٫۵٫۰) ------- */
	$wp_customize->add_section(
		'pixva_homepage_content',
		array(
			'title'       => esc_html__( 'پیکسوا: متن‌های صفحه اصلی و منوی مگا', 'pixva' ),
			'description' => esc_html__( 'عنوان و زیرعنوان سکشن نمونه‌کار و برچسب چهار گروه منوی مگا. خالی بگذارید تا مقدار پیش‌فرض داده‌محور استفاده شود.', 'pixva' ),
			'priority'    => 33,
		)
	);

	/**
	 * فیلدهای متنی لایه ۱٫۵٫۰: کلید => [برچسب, نوع, پیش‌فرض].
	 *
	 * @var array<string, array{0:string, 1:string, 2:mixed}>
	 */
	$pixva_v6_fields = array(
		'pixva_work_title'                => array( __( 'عنوان سکشن نمونه‌کار', 'pixva' ), 'text', __( 'نمونه‌کارهای کارگاه', 'pixva' ) ),
		'pixva_work_subtitle'             => array( __( 'زیرعنوان سکشن نمونه‌کار', 'pixva' ), 'textarea', __( 'نتیجه واقعی تعمیر پنل، بک‌لایت و برد؛ پیش از آنکه دستگاه را تحویل بگیرید تست نهایی ثبت می‌شود.', 'pixva' ) ),
		'pixva_work_count'                => array( __( 'تعداد نمونه‌کار در صفحه اصلی', 'pixva' ), 'number', 6 ),
		'pixva_mega_group_services'       => array( __( 'منو: عنوان گروه خدمات تعمیرات', 'pixva' ), 'text', __( 'خدمات تعمیرات', 'pixva' ) ),
		'pixva_mega_group_errors'         => array( __( 'منو: عنوان گروه کدهای خطا', 'pixva' ), 'text', __( 'کدهای خطا و عیب‌یابی', 'pixva' ) ),
		'pixva_mega_group_warranty'       => array( __( 'منو: عنوان گروه استعلام و گارانتی', 'pixva' ), 'text', __( 'استعلام و گارانتی', 'pixva' ) ),
		'pixva_mega_group_technician'     => array( __( 'منو: برچسب ورود تعمیرکاران', 'pixva' ), 'text', __( 'ورود تعمیرکاران', 'pixva' ) ),
		'pixva_mega_service_backlight'    => array( __( 'منو: برچسب تعمیر بک‌لایت', 'pixva' ), 'text', '' ),
		'pixva_mega_service_mainboard'    => array( __( 'منو: برچسب تعمیر برد اصلی', 'pixva' ), 'text', '' ),
		'pixva_mega_service_panel'        => array( __( 'منو: برچسب تعمیر پنل OLED/LED', 'pixva' ), 'text', '' ),
		'pixva_mega_parts_title'          => array( __( 'منو: برچسب استعلام اصالت قطعه', 'pixva' ), 'text', '' ),
		'pixva_mega_tracking_title'       => array( __( 'منو: برچسب پیگیری سفارش', 'pixva' ), 'text', '' ),
		'pixva_mega_warranty_title'       => array( __( 'منو: برچسب مشاهده کارت گارانتی', 'pixva' ), 'text', '' ),
		'pixva_technician_title'          => array( __( 'عنوان داشبورد تعمیرکار', 'pixva' ), 'text', __( 'داشبورد تعمیرکار', 'pixva' ) ),
		'pixva_technician_subtitle'       => array( __( 'زیرعنوان داشبورد تعمیرکار', 'pixva' ), 'textarea', __( 'دستگاه‌های تخصیص‌یافته به شما، گزارش فنی قطعات، قیمت نهایی و صدور کارت گارانتی در یک محیط کاری تمیز.', 'pixva' ) ),
	);

	foreach ( $pixva_v6_fields as $pixva_key => $pixva_field ) {
		$pixva_type = $pixva_field[1];
		if ( 'number' === $pixva_type ) {
			$pixva_sanitize = 'absint';
		} elseif ( 'textarea' === $pixva_type ) {
			$pixva_sanitize = 'sanitize_textarea_field';
		} else {
			$pixva_sanitize = 'sanitize_text_field';
		}

		$wp_customize->add_setting(
			$pixva_key,
			array(
				'default'           => $pixva_field[2],
				'sanitize_callback' => $pixva_sanitize,
			)
		);
		$wp_customize->add_control(
			$pixva_key,
			array(
				'label'   => $pixva_field[0],
				'section' => 'pixva_homepage_content',
				'type'    => $pixva_type,
			)
		);
	}
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

	// سکشن‌های تازه‌افزوده‌شده (advantages/work) در ترتیب ذخیره‌شده نصب‌های قدیمی نیستند.
	$default_keys = array_map( 'trim', explode( ',', pixva_default_section_order() ) );
	$missing      = array_values( array_diff( $default_keys, $keys ) );
	if ( ! empty( $missing ) ) {
		$keys = array_merge( $keys, $missing );
	}

	$defaults = pixva_home_section_defaults();
	$keys     = array_filter(
		$keys,
		static function ( $key ) use ( $defaults ) {
			$default = isset( $defaults[ $key ] ) ? $defaults[ $key ] : true;
			return (bool) pixva_option( 'pixva_section_' . $key, $default );
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

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
	 * ترتیب کلیدها همان ترتیب پیش‌فرض صفحه است (لایه ۱٫۷٫۰ / Master Prompt v8):
	 * هیرو و منوی مگا ← اسکرول سینمایی ← عیب‌یاب هوشمند ← مدل سه‌بعدی ←
	 * نقشه زنده تعمیرکار ← سه مزیت ← نمونه‌کار تصویری ← نظرات مشتریان ← فوتر.
	 * بقیه سکشن‌ها خاموش‌اند ولی از منوی مگا و هاب‌ها در دسترس می‌مانند.
	 */
	return array(
		'hero'          => esc_html__( 'هیرو با عنوان و دکمه استعلام سریع قیمت', 'pixva' ),
		'cinematic'     => esc_html__( 'اسکرول سینمایی: نمای انفجاری پنج‌لایه تلویزیون (لایه ۱٫۷٫۰)', 'pixva' ),
		'ai_diagnose'   => esc_html__( 'عیب‌یاب هوشمند با ویدیو و صدای دستگاه (لایه ۱٫۷٫۰)', 'pixva' ),
		'repair_3d'     => esc_html__( 'مدل سه‌بعدی تعاملی با هات‌اسپیت قیمت قطعه (لایه ۱٫۷٫۰)', 'pixva' ),
		'tech_tracker'  => esc_html__( 'نقشه زنده تعمیرکار با مسیر دمو و ETA (لایه ۱٫۷٫۰)', 'pixva' ),
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
	 * صفحه اصلی (Master Prompt v8): هیرو + چهار تجربه سینمایی (اسکرول انفجاری،
	 * عیب‌یاب هوشمند، مدل سه‌بعدی و نقشه زنده) + سه مزیت + نمونه‌کار + نظرات.
	 * بقیه ابزارها خاموش‌اند و از منوی مگا و صفحات اختصاصی در دسترس می‌مانند؛
	 * کاربر می‌تواند هر سکشن را از «پیکسوا: سکشن‌های صفحه اصلی» تغییر دهد.
	 */
	$defaults = array(
		'hero'          => true,
		'cinematic'     => true,
		'ai_diagnose'   => true,
		'repair_3d'     => true,
		'tech_tracker'  => true,
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
	 * ترتیب فرود طبق Master Prompt v8: هیرو ← اسکرول سینمایی ← عیب‌یاب هوشمند ←
	 * مدل سه‌بعدی ← نقشه زنده ← سه مزیت ← نمونه‌کار ← نظرات مشتریان، سپس سایر
	 * سکشن‌ها با همان ترتیب فهرست بالا.
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
	/*
	 * سکشن‌های جاافتاده (نسخه‌های تازه‌تر پوسته) هیچ‌گاه گم نمی‌شوند، اما به
	 * انتهای فهرست پرتاب نمی‌شوند؛ هرکدام دقیقاً در جایگاه استاندارد خودش
	 * درج می‌شود تا ترتیب Master Prompt v8 روی نصب‌های قدیمی هم درست بماند.
	 */
	return implode( ',', pixva_merge_section_order( $clean ) );
}

/**
 * درج کلیدهای جاافتاده در جایگاه استاندارد ترتیب سکشن‌های صفحه اصلی.
 *
 * ترتیب ذخیره‌شده کاربر محترم است؛ فقط کلیدهایی که در آن نیستند به‌جای
 * «انتهای فهرست»، پس از نزدیک‌ترین کلیدِ قبلی در ترتیب پیش‌فرض قرار می‌گیرند.
 *
 * @param array<int, string> $keys ترتیب فعلی.
 * @return array<int, string>
 */
function pixva_merge_section_order( $keys ) {
	$default_keys = array_map( 'trim', explode( ',', pixva_default_section_order() ) );
	$keys         = array_values( array_filter( array_map( 'trim', (array) $keys ), 'strlen' ) );
	$missing      = array_values( array_diff( $default_keys, $keys ) );

	foreach ( $missing as $key ) {
		$canonical = array_search( $key, $default_keys, true );
		$position  = count( $keys );

		if ( false !== $canonical ) {
			$position = 0;
			for ( $i = 0; $i < $canonical; $i++ ) {
				$found = array_search( $default_keys[ $i ], $keys, true );
				if ( false !== $found ) {
					$position = max( $position, $found + 1 );
				}
			}
		}

		array_splice( $keys, (int) $position, 0, array( $key ) );
	}

	return $keys;
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

	/* ------- سینمایی، سه‌بعدی، هوش مصنوعی و نقشه زنده (لایه ۱٫۶٫۰) ------- */
	$wp_customize->add_section(
		'pixva_cinematic',
		array(
			'title'       => esc_html__( 'پیکسوا: سینمایی، سه‌بعدی، هوش مصنوعی و نقشه', 'pixva' ),
			'description' => esc_html__( 'منبع کتابخانه‌های انیمیشن/نقشه، اتصال عامل هوش مصنوعی و داده‌های مبدأ مسیر. همه این گزینه‌ها اختیاری‌اند؛ خالی بودن یعنی رفتار پیش‌فرض داده‌محور.', 'pixva' ),
			'priority'    => 34,
		)
	);

	/**
	 * فیلدهای لایه ۱٫۶٫۰: کلید => [برچسب, نوع, پیش‌فرض, توضیح, گزینه‌ها].
	 *
	 * @var array<string, array{0:string, 1:string, 2:mixed, 3:string, 4?:array<string, string>}>
	 */
	$pixva_v7_fields = array(
		'pixva_cdn_gsap'           => array( __( 'آدرس CDN کتابخانه GSAP', 'pixva' ), 'text', '', __( 'خالی = موتور سبک داخلی پیکسوا. اگر فایل assets/js/vendor/gsap.min.js وجود داشته باشد، اولویت با آن است.', 'pixva' ) ),
		'pixva_cdn_scrolltrigger'  => array( __( 'آدرس CDN افزونه ScrollTrigger', 'pixva' ), 'text', '', __( 'فایل محلی: assets/js/vendor/ScrollTrigger.min.js — بدون آن، موتور داخلی Pin/Scrub را انجام می‌دهد.', 'pixva' ) ),
		'pixva_cdn_leaflet'        => array( __( 'آدرس CDN کتابخانه Leaflet', 'pixva' ), 'text', '', __( 'فایل محلی: assets/js/vendor/leaflet.js — خالی = نقشه داخلی SVG پیکسوا (بدون درخواست بیرونی).', 'pixva' ) ),
		'pixva_cdn_leaflet_css'    => array( __( 'آدرس CDN سبک Leaflet', 'pixva' ), 'text', '', __( 'فایل محلی: assets/css/vendor/leaflet.css', 'pixva' ) ),
		'pixva_cdn_spline'         => array( __( 'آدرس ماژول Spline Viewer', 'pixva' ), 'text', 'https://unpkg.com/@splinetool/viewer@1.9.48/build/spline-viewer.js', __( 'به‌صورت تنبل و فقط در صفحه‌های دارای ویجت سه‌بعدی بارگذاری می‌شود.', 'pixva' ) ),
		'pixva_ai_max_size'        => array( __( 'سقف حجم فایل رسانه (مگابایت) — سازگاری نسخه پیشین', 'pixva' ), 'number', 50, __( 'از لایه ۱٫۸٫۰ سقف اصلی در بخش «عیب‌یاب هوش مصنوعی (Gemini)» با کلید pixva_ai_max_file_size تنظیم می‌شود؛ این مقدار فقط fallback است.', 'pixva' ) ),
		'pixva_map_origin_lat'     => array( __( 'نقشه: عرض جغرافیایی مبدأ (کارگاه)', 'pixva' ), 'coord', 35.6892, __( 'مبدأ مسیر زمانی که موقعیت تعمیرکار ثبت نشده باشد.', 'pixva' ) ),
		'pixva_map_origin_lng'     => array( __( 'نقشه: طول جغرافیایی مبدأ (کارگاه)', 'pixva' ), 'coord', 51.3890, '' ),
		'pixva_map_origin_label'   => array( __( 'نقشه: برچسب مبدأ', 'pixva' ), 'text', __( 'کارگاه مرکزی پیکسوا', 'pixva' ), '' ),
		'pixva_map_zones'          => array( __( 'نقشه: منطقه‌ها (JSON)', 'pixva' ), 'textarea', '', __( 'نمونه: [{"zone":"شمال تهران","lat":35.7810,"lng":51.4100}] — برای ساخت مقصد از منطقه مشتری.', 'pixva' ) ),
		'pixva_map_dest_label'     => array( __( 'نقشه: برچسب مقصد', 'pixva' ), 'text', __( 'محل مشتری', 'pixva' ), '' ),
		'pixva_map_average_speed'  => array( __( 'نقشه: سرعت میانگین (کیلومتر/ساعت)', 'pixva' ), 'number', 22, __( 'برای محاسبه زمان تقریبی رسیدن (ETA).', 'pixva' ) ),
		'pixva_map_refresh'        => array( __( 'نقشه: بازه نوسازی (ثانیه)', 'pixva' ), 'number', 20, __( 'ویجت المنتور می‌تواند این مقدار را برای هر نمونه تغییر دهد.', 'pixva' ) ),
		'pixva_map_provider'       => array( __( 'نقشه: موتور رندر', 'pixva' ), 'select', 'auto', '', array(
			'auto'     => __( 'خودکار (Leaflet اگر موجود باشد)', 'pixva' ),
			'internal' => __( 'همیشه نقشه داخلی پیکسوا', 'pixva' ),
			'leaflet'  => __( 'همیشه Leaflet', 'pixva' ),
		) ),
		'pixva_map_tiles'          => array( __( 'نقشه: آدرس کاشی‌ها', 'pixva' ), 'text', 'https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', __( 'الگوی Leaflet با جای‌گیرهای {s} {z} {x} {y}.', 'pixva' ) ),
		'pixva_map_attribution'    => array( __( 'نقشه: متن منبع (Attribution)', 'pixva' ), 'text', '© OpenStreetMap contributors © CARTO', '' ),
		'pixva_map_on_tracking'    => array( __( 'نقشه زنده در صفحه پیگیری سفارش', 'pixva' ), 'checkbox', true, __( 'ویجت نقشه زیر فرم استعلام قرار می‌گیرد و با همان کد پیگیری پر می‌شود.', 'pixva' ) ),
		'pixva_map_badge'          => array( __( 'نقشه صفحه پیگیری: برچسب', 'pixva' ), 'text', __( 'ردیابی زنده', 'pixva' ), '' ),
		'pixva_map_title'          => array( __( 'نقشه صفحه پیگیری: تیتر', 'pixva' ), 'text', __( 'تعمیرکار کجاست؟', 'pixva' ), '' ),
		'pixva_map_subtitle'       => array( __( 'نقشه صفحه پیگیری: توضیح', 'pixva' ), 'textarea', __( 'پس از استعلام کد پیگیری، موقعیت تعمیرکار و زمان تقریبی رسیدن به‌صورت زنده روی نقشه روشن می‌شود.', 'pixva' ), '' ),
	);

	foreach ( $pixva_v7_fields as $pixva_key => $pixva_field ) {
		$pixva_type = $pixva_field[1];

		if ( 'checkbox' === $pixva_type ) {
			$pixva_sanitize = 'pixva_sanitize_checkbox';
			$pixva_control  = 'checkbox';
		} elseif ( 'number' === $pixva_type ) {
			$pixva_sanitize = 'absint';
			$pixva_control  = 'number';
		} elseif ( 'textarea' === $pixva_type ) {
			$pixva_sanitize = 'sanitize_textarea_field';
			$pixva_control  = 'textarea';
		} elseif ( 'coord' === $pixva_type ) {
			$pixva_sanitize = 'pixva_sanitize_coord';
			$pixva_control  = 'text';
		} elseif ( 'select' === $pixva_type ) {
			$pixva_sanitize = 'pixva_sanitize_map_provider';
			$pixva_control  = 'select';
		} else {
			$pixva_sanitize = 'sanitize_text_field';
			$pixva_control  = 'text';
		}

		$wp_customize->add_setting(
			$pixva_key,
			array(
				'default'           => $pixva_field[2],
				'sanitize_callback' => $pixva_sanitize,
			)
		);

		$pixva_control_args = array(
			'label'       => $pixva_field[0],
			'section'     => 'pixva_cinematic',
			'type'        => $pixva_control,
		);

		if ( '' !== $pixva_field[3] ) {
			$pixva_control_args['description'] = $pixva_field[3];
		}
		if ( ! empty( $pixva_field[4] ) ) {
			$pixva_control_args['choices'] = $pixva_field[4];
		}
		if ( 'number' === $pixva_type ) {
			$pixva_control_args['input_attrs'] = array(
				'min'  => 1,
				'step' => 1,
			);
		}

		$wp_customize->add_control( $pixva_key, $pixva_control_args );
	}

	/* ------- صفحه اصلی سینمایی و دارایی‌های دمو (لایه ۱٫۷٫۰ / Master Prompt v8) ------- */
	$wp_customize->add_section(
		'pixva_home_v8',
		array(
			'title'       => esc_html__( 'پیکسوا: صفحه اصلی سینمایی (v8)', 'pixva' ),
			'description' => esc_html__( 'متن چهار بخش خودکار صفحه اصلی (اسکرول سینمایی، عیب‌یاب هوشمند، مدل سه‌بعدی و نقشه زنده) و حالت چیدمان المنتوری. همه فیلدها اختیاری‌اند؛ خالی یعنی مقدار پیش‌فرض داده‌محور.', 'pixva' ),
			'priority'    => 35,
		)
	);

	$wp_customize->add_section(
		'pixva_demo_assets',
		array(
			'title'       => esc_html__( 'پیکسوا: لایه‌های دمو و مدل سه‌بعدی', 'pixva' ),
			'description' => esc_html__( 'تصویر پنج لایه انفجاری (پیش‌فرض: فایل‌های SVG پوسته در assets/images/demo)، آدرس صحنه Spline، نقطه‌های دمو و داده مسیر نمایشی نقشه.', 'pixva' ),
			'priority'    => 36,
		)
	);

	/**
	 * فیلدهای لایه ۱٫۷٫۰: کلید => [بخش, برچسب, نوع, پیش‌فرض, توضیح].
	 *
	 * @var array<string, array{0:string, 1:string, 2:string, 3:mixed, 4:string}>
	 */
	$pixva_v8_fields = array(
		'pixva_home_use_elementor'    => array( 'pixva_home_v8', __( 'رندر صفحه اصلی با چیدمان المنتور', 'pixva' ), 'checkbox', false, __( 'پس از ذخیره چیدمان پیش‌فرض (دکمه «بارگذاری چیدمان المنتور» در مرکز کنترل)، front-page.php به‌جای سکشن‌های پوسته، محتوای المنتور برگه خانه را چاپ می‌کند.', 'pixva' ) ),
		'pixva_home_cine_badge'       => array( 'pixva_home_v8', __( 'اسکرول سینمایی: برچسب', 'pixva' ), 'text', __( 'کالبدشکافی زنده', 'pixva' ), '' ),
		'pixva_home_cine_title'       => array( 'pixva_home_v8', __( 'اسکرول سینمایی: تیتر', 'pixva' ), 'text', __( 'داخل یک تلویزیون چه می‌گذرد؟', 'pixva' ), '' ),
		'pixva_home_cine_subtitle'    => array( 'pixva_home_v8', __( 'اسکرول سینمایی: توضیح', 'pixva' ), 'textarea', __( 'با اسکرول، پنج لایه دستگاه از هم باز می‌شوند و خدمت تعمیر هر قطعه کنارش ظاهر می‌شود.', 'pixva' ), '' ),
		'pixva_home_ai_badge'         => array( 'pixva_home_v8', __( 'عیب‌یاب هوشمند: برچسب', 'pixva' ), 'text', __( 'عیب‌یابی هوشمند', 'pixva' ), '' ),
		'pixva_home_ai_title'         => array( 'pixva_home_v8', __( 'عیب‌یاب هوشمند: تیتر', 'pixva' ), 'text', __( 'ویدیو یا صدای دستگاه را بفرستید تا تحلیل شود', 'pixva' ), '' ),
		'pixva_home_ai_subtitle'      => array( 'pixva_home_v8', __( 'عیب‌یاب هوشمند: توضیح', 'pixva' ), 'textarea', __( 'یک ویدیوی کوتاه از خرابی یا صدای دستگاه را آپلود کنید؛ خروجی تحلیل به همراه کد پیگیری برای شما ثبت می‌شود.', 'pixva' ), '' ),
		'pixva_home_3d_badge'         => array( 'pixva_home_v8', __( 'مدل سه‌بعدی: برچسب', 'pixva' ), 'text', __( 'نمای سه‌بعدی', 'pixva' ), '' ),
		'pixva_home_3d_title'         => array( 'pixva_home_v8', __( 'مدل سه‌بعدی: تیتر', 'pixva' ), 'text', __( 'دستگاه را بچرخانید و قطعه معیوب را لمس کنید', 'pixva' ), '' ),
		'pixva_home_3d_subtitle'      => array( 'pixva_home_v8', __( 'مدل سه‌بعدی: توضیح', 'pixva' ), 'textarea', __( 'با ماوس مدل را بچرخانید؛ روی نقطه‌های قرمز کلیک کنید تا برآورد قیمت همان قطعه باز شود.', 'pixva' ), '' ),
		'pixva_home_map_badge'        => array( 'pixva_home_v8', __( 'نقشه زنده: برچسب', 'pixva' ), 'text', __( 'ردیابی زنده', 'pixva' ), '' ),
		'pixva_home_map_title'        => array( 'pixva_home_v8', __( 'نقشه زنده: تیتر', 'pixva' ), 'text', __( 'تعمیرکار کجاست؟', 'pixva' ), '' ),
		'pixva_home_map_subtitle'     => array( 'pixva_home_v8', __( 'نقشه زنده: توضیح', 'pixva' ), 'textarea', __( 'مسیر حرکت تعمیرکار تا محل شما روی نقشه تاریک روشن می‌شود؛ با کد پیگیری، موقعیت واقعی پرونده خودتان را ببینید.', 'pixva' ), '' ),
		'pixva_home_map_demo'         => array( 'pixva_home_v8', __( 'نقشه صفحه اصلی: مسیر دمو زنده', 'pixva' ), 'checkbox', true, __( 'بدون کد پیگیری، نقشه با یک مسیر نمایشی که بر پایه زمان جلو می‌رود پر می‌شود.', 'pixva' ) ),
		'pixva_home_map_lookup'       => array( 'pixva_home_v8', __( 'نقشه صفحه اصلی: فرم کد پیگیری', 'pixva' ), 'checkbox', true, __( 'بازدیدکننده می‌تواند کد و شماره همراه پرونده خودش را وارد کند.', 'pixva' ) ),

		'pixva_demo_layer_frame'      => array( 'pixva_demo_assets', __( 'لایه دمو ۱: قاب رویی', 'pixva' ), 'image', '', __( 'پیش‌فرض: assets/images/demo/tv-frame-front.svg', 'pixva' ) ),
		'pixva_demo_layer_glass'      => array( 'pixva_demo_assets', __( 'لایه دمو ۲: صفحه شیشه‌ای', 'pixva' ), 'image', '', __( 'پیش‌فرض: assets/images/demo/tv-glass-screen.svg', 'pixva' ) ),
		'pixva_demo_layer_backlight'  => array( 'pixva_demo_assets', __( 'لایه دمو ۳: بک‌لایت نئونی', 'pixva' ), 'image', '', __( 'پیش‌فرض: assets/images/demo/tv-backlight-neon.svg', 'pixva' ) ),
		'pixva_demo_layer_mainboard'  => array( 'pixva_demo_assets', __( 'لایه دمو ۴: برد اصلی', 'pixva' ), 'image', '', __( 'پیش‌فرض: assets/images/demo/tv-mainboard.svg', 'pixva' ) ),
		'pixva_demo_layer_cover'      => array( 'pixva_demo_assets', __( 'لایه دمو ۵: قاب پشتی', 'pixva' ), 'image', '', __( 'پیش‌فرض: assets/images/demo/tv-back-cover.svg', 'pixva' ) ),
		'pixva_cine_layers_json'      => array( 'pixva_demo_assets', __( 'بازنویسی کامل لایه‌ها (JSON)', 'pixva' ), 'json', '', __( 'نمونه: [{"image":"https://.../layer.png","title":"پنل","text":"شرح خدمت","link":"panel","depth":4}] — اگر پر شود، جای پنج لایه دمو را می‌گیرد.', 'pixva' ) ),
		'pixva_spline_demo_url'       => array( 'pixva_demo_assets', __( 'آدرس صحنه دمو Spline', 'pixva' ), 'url', 'https://prod.spline.design/6Wq1Q7YGyM-iab9i/scene.splinecode', __( 'صحنه نمونه رسمی Spline؛ آدرس scene.splinecode مدل اختصاصی خودتان را جایگزین کنید.', 'pixva' ) ),
		'pixva_spline_hotspots_json'  => array( 'pixva_demo_assets', __( 'بازنویسی نقطه‌های سه‌بعدی (JSON)', 'pixva' ), 'json', '', __( 'نمونه: [{"x":28,"y":40,"part":"backlight","label":"بک‌لایت سوخته","text":"شرح"}] — پیش‌فرض سه نقطه است: بک‌لایت، برد تغذیه و پنل.', 'pixva' ) ),
		'pixva_map_demo_lat'          => array( 'pixva_demo_assets', __( 'مسیر دمو: عرض جغرافیایی مقصد', 'pixva' ), 'coord', '', __( 'خالی = دورترین منطقه از فهرست منطقه‌ها یا مقصد قطعی نزدیک مبدأ.', 'pixva' ) ),
		'pixva_map_demo_lng'          => array( 'pixva_demo_assets', __( 'مسیر دمو: طول جغرافیایی مقصد', 'pixva' ), 'coord', '', '' ),
		'pixva_map_demo_label'        => array( 'pixva_demo_assets', __( 'مسیر دمو: برچسب مقصد', 'pixva' ), 'text', '', __( 'خالی = برچسب مقصد عمومی نقشه.', 'pixva' ) ),
		'pixva_map_demo_tech'         => array( 'pixva_demo_assets', __( 'مسیر دمو: نام تعمیرکار', 'pixva' ), 'text', __( 'تعمیرکار شیفت امروز', 'pixva' ), '' ),
		'pixva_map_demo_skill'        => array( 'pixva_demo_assets', __( 'مسیر دمو: تخصص تعمیرکار', 'pixva' ), 'text', __( 'بک‌لایت، پنل و برد اصلی', 'pixva' ), '' ),
		'pixva_map_demo_brand'        => array( 'pixva_demo_assets', __( 'مسیر دمو: برند دستگاه', 'pixva' ), 'text', 'Samsung', '' ),
		'pixva_map_demo_model'        => array( 'pixva_demo_assets', __( 'مسیر دمو: مدل دستگاه', 'pixva' ), 'text', __( 'نمایش دمو ۵۵ اینچ', 'pixva' ), '' ),
	);

	foreach ( $pixva_v8_fields as $pixva_key => $pixva_field ) {
		$pixva_type = $pixva_field[2];

		if ( 'checkbox' === $pixva_type ) {
			$pixva_sanitize = 'pixva_sanitize_checkbox';
			$pixva_control  = 'checkbox';
		} elseif ( 'number' === $pixva_type ) {
			$pixva_sanitize = 'absint';
			$pixva_control  = 'number';
		} elseif ( 'textarea' === $pixva_type ) {
			$pixva_sanitize = 'sanitize_textarea_field';
			$pixva_control  = 'textarea';
		} elseif ( 'json' === $pixva_type ) {
			$pixva_sanitize = 'pixva_sanitize_json';
			$pixva_control  = 'textarea';
		} elseif ( 'coord' === $pixva_type ) {
			$pixva_sanitize = 'pixva_sanitize_coord';
			$pixva_control  = 'text';
		} elseif ( 'url' === $pixva_type ) {
			$pixva_sanitize = 'esc_url_raw';
			$pixva_control  = 'url';
		} elseif ( 'image' === $pixva_type ) {
			$pixva_sanitize = 'esc_url_raw';
			$pixva_control  = 'image';
		} else {
			$pixva_sanitize = 'sanitize_text_field';
			$pixva_control  = 'text';
		}

		$wp_customize->add_setting(
			$pixva_key,
			array(
				'default'           => $pixva_field[3],
				'sanitize_callback' => $pixva_sanitize,
			)
		);

		$pixva_args = array(
			'label'   => $pixva_field[1],
			'section' => $pixva_field[0],
			'type'    => $pixva_control,
		);

		if ( '' !== $pixva_field[4] ) {
			$pixva_args['description'] = $pixva_field[4];
		}

		if ( 'image' === $pixva_type ) {
			$wp_customize->add_control( new WP_Customize_Image_Control( $wp_customize, $pixva_key, $pixva_args ) );
			continue;
		}

		if ( 'json' === $pixva_type ) {
			$pixva_args['input_attrs'] = array( 'dir' => 'ltr', 'style' => 'font-family:monospace;font-size:12px' );
		}

		$wp_customize->add_control( $pixva_key, $pixva_args );
	}

	/* ------- عیب‌یاب هوش مصنوعی بومی با Google Gemini (لایه ۱٫۸٫۰ / Master Prompt v9) ------- */
	$wp_customize->add_section(
		'pixva_ai_gemini',
		array(
			'title'       => esc_html__( 'پیکسوا: عیب‌یاب هوش مصنوعی (Gemini)', 'pixva' ),
			'description' => esc_html__( 'کلید API گوگل جمینای، مدل، سقف حجم فایل و ایجاد خودکار پیش‌نویس سفارش. این کلید با چت‌بات هوشمند پیکسوا مشترک است و فقط سمت سرور نگهداری می‌شود. دکمه «تست اتصال آنلاین» و فیلد مخفی‌شده کلید در پیشخوان ← پیکسوا ← عیب‌یاب AI (Gemini) قرار دارد.', 'pixva' ),
			'priority'    => 37,
		)
	);

	$wp_customize->add_setting(
		'pixva_gemini_api_key',
		array(
			'default'           => '',
			'sanitize_callback' => 'sanitize_text_field',
		)
	);
	$wp_customize->add_control(
		'pixva_gemini_api_key',
		array(
			'label'       => esc_html__( 'کلید API گوگل جمینای', 'pixva' ),
			'section'     => 'pixva_ai_gemini',
			'type'        => 'text',
			'description' => esc_html__( 'کلید را از Google AI Studio بگیرید. برای امنیت، در پنل بومی پیشخوان به‌صورت مخفی و با دکمه تست اتصال مدیریت می‌شود.', 'pixva' ),
			'input_attrs' => array( 'dir' => 'ltr', 'style' => 'font-family:monospace' ),
		)
	);

	$wp_customize->add_setting(
		'pixva_gemini_model',
		array(
			'default'           => 'gemini-1.5-flash',
			'sanitize_callback' => 'pixva_sanitize_gemini_model',
		)
	);
	$wp_customize->add_control(
		'pixva_gemini_model',
		array(
			'label'   => esc_html__( 'مدل جمینای', 'pixva' ),
			'section' => 'pixva_ai_gemini',
			'type'    => 'select',
			'choices' => function_exists( 'pixva_ai_handler_models' ) ? pixva_ai_handler_models() : array( 'gemini-1.5-flash' => 'Gemini 1.5 Flash' ),
		)
	);

	$wp_customize->add_setting(
		'pixva_ai_max_file_size',
		array(
			'default'           => 50,
			'sanitize_callback' => 'absint',
		)
	);
	$wp_customize->add_control(
		'pixva_ai_max_file_size',
		array(
			'label'       => esc_html__( 'حداکثر حجم فایل آپلودی (مگابایت)', 'pixva' ),
			'section'     => 'pixva_ai_gemini',
			'type'        => 'number',
			'description' => esc_html__( 'پیش‌فرض ۵۰ مگابایت.', 'pixva' ),
			'input_attrs' => array( 'min' => 1, 'max' => 512, 'step' => 1 ),
		)
	);

	$wp_customize->add_setting(
		'pixva_ai_auto_create_draft',
		array(
			'default'           => false,
			'sanitize_callback' => 'pixva_sanitize_checkbox',
		)
	);
	$wp_customize->add_control(
		'pixva_ai_auto_create_draft',
		array(
			'label'       => esc_html__( 'ایجاد خودکار پیش‌نویس سفارش به محض تشخیص خطا', 'pixva' ),
			'section'     => 'pixva_ai_gemini',
			'type'        => 'checkbox',
			'description' => esc_html__( 'یک پرونده pixva_orders از روی نتیجه عیب‌یابی ساخته می‌شود (قابل تبدیل نهایی در تاریخچه).', 'pixva' ),
		)
	);
}
add_action( 'customize_register', 'pixva_customize_register' );

/**
 * پاک‌سازی انتخاب مدل جمینای (فقط مدل‌های مجاز).
 *
 * @param string $model مدل.
 * @return string
 */
function pixva_sanitize_gemini_model( $model ) {
	$model    = sanitize_key( (string) $model );
	$allowed  = function_exists( 'pixva_ai_handler_models' ) ? pixva_ai_handler_models() : array( 'gemini-1.5-flash' => '' );
	return array_key_exists( $model, $allowed ) ? $model : 'gemini-1.5-flash';
}

/**
 * پاک‌سازی فیلد JSON (لایه‌ها و هات‌اسپیت‌های دمو).
 *
 * مقدار نامعتبر به رشته خالی تبدیل می‌شود تا خواننده گزینه، پیش‌فرض
 * داده‌محور پوسته را برگرداند؛ در نتیجه هرگز JSON شکسته به مرورگر نمی‌رسد.
 *
 * @param mixed $value ورودی.
 * @return string
 */
function pixva_sanitize_json( $value ) {
	$value = trim( (string) sanitize_textarea_field( (string) $value ) );

	if ( '' === $value ) {
		return '';
	}

	$decoded = json_decode( $value, true );
	if ( ! is_array( $decoded ) ) {
		$decoded = json_decode( wp_specialchars_decode( $value, ENT_QUOTES ), true );
	}

	if ( ! is_array( $decoded ) ) {
		return '';
	}

	return (string) wp_json_encode( $decoded, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
}

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
 * پاک‌سازی مختصات جغرافیایی (عدد اعشاری بین -90 تا 180).
 *
 * @param mixed $value ورودی.
 * @return string
 */
function pixva_sanitize_coord( $value ) {
	$value = str_replace( array( ',', '،' ), '.', trim( (string) $value ) );

	if ( '' === $value || ! is_numeric( $value ) ) {
		return '';
	}

	$number = (float) $value;
	if ( $number < -180 || $number > 180 ) {
		return '';
	}

	return (string) $number;
}

/**
 * پاک‌سازی انتخاب موتور نقشه.
 *
 * @param mixed $value ورودی.
 * @return string
 */
function pixva_sanitize_map_provider( $value ) {
	$value = sanitize_key( (string) $value );
	return in_array( $value, array( 'auto', 'internal', 'leaflet' ), true ) ? $value : 'auto';
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

	// کلیدهای جاافتاده در جایگاه استاندارد خودشان درج می‌شوند (لایه ۱٫۷٫۰).
	$keys = pixva_merge_section_order( $keys );

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

<?php
/**
 * خودکارسازی صفحه اصلی سینمایی و چیدمان پیش‌فرض المنتور — لایه ۱٫۷٫۰.
 *
 * Master Prompt v8: هر چهار ویجت لایه ۱٫۶٫۰ باید بدون هیچ اقدام دستی روی صفحه
 * اصلی فعال باشند و همان چیدمان در المنتور هم از پیش پر شده باشد. این فایل سه
 * کار انجام می‌دهد:
 *
 *  ۱) تنظیمات چهار بخش صفحه اصلی را از سفارشی‌ساز + پیش‌فرض‌های دمو می‌سازد
 *     (`pixva_home_v8_module_settings`) تا front-page.php و المنتور و شورت‌کد
 *     همه یک خروجی واحد داشته باشند.
 *  ۲) چیدمان پیش‌فرض المنتور را به‌صورت آرایه/JSON می‌سازد
 *     (`pixva_home_elementor_template`) — همان ترتیب صفحه اصلی: اسکرول سینمایی
 *     ← عیب‌یاب هوشمند ← مدل سه‌بعدی ← نقشه زنده.
 *  ۳) آن JSON را در متای `_elementor_data` برگه خانه می‌نویسد
 *     (`pixva_seed_home_elementor`) و یک قالب ذخیره‌شده در کتابخانه المنتور
 *     می‌سازد؛ بدون اینکه هرگز محتوای موجود مدیر را بازنویسی کند (مگر با
 *     درخواست صریح «بازنشانی» از مرکز کنترل).
 *
 * همه متن‌ها و تصویرها از گزینه‌ها می‌آیند؛ هیچ مقدار سخت‌کد محتوایی وجود ندارد.
 *
 * @package Pixva
 * @since   1.7.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* --------------------------------------------------------------------------
 * ۱) تنظیمات چهار بخش خودکار صفحه اصلی
 * ----------------------------------------------------------------------- */

if ( ! function_exists( 'pixva_home_v8_module_settings' ) ) {
	/**
	 * تنظیمات یک ماژول سینمایی برای صفحه اصلی (سفارشی‌ساز + پیش‌فرض دمو).
	 *
	 * کلیدهای لایه‌ها/آدرس مدل/هات‌اسپیت‌ها عمداً فرستاده نمی‌شوند تا رندر
	 * مشترک، پیش‌فرض داده‌محور خودش (پنج لایه دمو، صحنه دمو Spline و سه نقطه
	 * دمو) را اعمال کند و برچسب «حالت دمو» فقط برای مدیر نمایش یابد؛ همان
	 * پیش‌فرض‌ها با گزینه‌های سفارشی‌ساز یا پنل المنتور بازنویسی می‌شوند.
	 *
	 * @param string $module نام ماژول: cinematic|ai|spline|tracker.
	 * @return array<string, mixed>
	 */
	function pixva_home_v8_module_settings( $module ) {
		$settings = array();

		switch ( $module ) {
			case 'cinematic':
				$settings = array(
					'badge'      => (string) pixva_option( 'pixva_home_cine_badge', __( 'کالبدشکافی زنده', 'pixva' ) ),
					'title'      => (string) pixva_option( 'pixva_home_cine_title', __( 'داخل یک تلویزیون چه می‌گذرد؟', 'pixva' ) ),
					'subtitle'   => (string) pixva_option( 'pixva_home_cine_subtitle', __( 'با اسکرول، پنج لایه دستگاه از هم باز می‌شوند و خدمت تعمیر هر قطعه کنارش ظاهر می‌شود.', 'pixva' ) ),
					'height'     => 320,
					'element_id' => 'pixva-home-cinematic',
				);
				break;

			case 'ai':
				$settings = array(
					'badge'       => (string) pixva_option( 'pixva_home_ai_badge', __( 'عیب‌یابی هوشمند', 'pixva' ) ),
					'title'       => (string) pixva_option( 'pixva_home_ai_title', __( 'ویدیو یا صدای دستگاه را بفرستید تا تحلیل شود', 'pixva' ) ),
					'subtitle'    => (string) pixva_option( 'pixva_home_ai_subtitle', __( 'یک ویدیوی کوتاه از خرابی یا صدای دستگاه را آپلود کنید؛ خروجی تحلیل به همراه کد پیگیری برای شما ثبت می‌شود.', 'pixva' ) ),
					'video_label' => __( 'آپلود ویدیوی خرابی', 'pixva' ),
					'mic_label'   => __( 'ضبط صدای دستگاه', 'pixva' ),
					'submit'      => __( 'تحلیل هوشمند', 'pixva' ),
					'show_phone'  => true,
					'element_id'  => 'pixva-home-ai',
				);
				break;

			case 'spline':
				$settings = array(
					'badge'      => (string) pixva_option( 'pixva_home_3d_badge', __( 'نمای سه‌بعدی', 'pixva' ) ),
					'title'      => (string) pixva_option( 'pixva_home_3d_title', __( 'دستگاه را بچرخانید و قطعه معیوب را لمس کنید', 'pixva' ) ),
					'subtitle'   => (string) pixva_option( 'pixva_home_3d_subtitle', __( 'با ماوس مدل را بچرخانید؛ روی نقطه‌های قرمز کلیک کنید تا برآورد قیمت همان قطعه باز شود.', 'pixva' ) ),
					'lazy'       => true,
					'fallback'   => true,
					'height'     => 34,
					'element_id' => 'pixva-home-3d',
				);
				break;

			case 'tracker':
				$settings = array(
					'badge'      => (string) pixva_option( 'pixva_home_map_badge', __( 'ردیابی زنده', 'pixva' ) ),
					'title'      => (string) pixva_option( 'pixva_home_map_title', __( 'تعمیرکار کجاست؟', 'pixva' ) ),
					'subtitle'   => (string) pixva_option( 'pixva_home_map_subtitle', __( 'مسیر حرکت تعمیرکار تا محل شما روی نقشه تاریک روشن می‌شود؛ با کد پیگیری، موقعیت واقعی پرونده خودتان را ببینید.', 'pixva' ) ),
					'lookup'     => (bool) pixva_option( 'pixva_home_map_lookup', true ),
					'demo'       => (bool) pixva_option( 'pixva_home_map_demo', true ),
					'height'     => 26,
					'element_id' => 'pixva-home-map',
				);
				break;
		}

		/**
		 * فیلتر تنظیمات یک بخش سینمایی روی صفحه اصلی.
		 *
		 * @param array  $settings تنظیمات.
		 * @param string $module   نام ماژول.
		 */
		return apply_filters( 'pixva_home_v8_module_settings', $settings, $module );
	}
}

if ( ! function_exists( 'pixva_home_v8_section_settings' ) ) {
	/**
	 * تنظیمات یک سکشن صفحه اصلی (کلید سکشن ← ماژول ← تنظیمات).
	 *
	 * @param string $section کلید سکشن: cinematic|ai_diagnose|repair_3d|tech_tracker.
	 * @return array<string, mixed>
	 */
	function pixva_home_v8_section_settings( $section ) {
		$map = function_exists( 'pixva_home_cinematic_sections' ) ? pixva_home_cinematic_sections() : array();

		if ( ! isset( $map[ $section ] ) ) {
			return array();
		}

		return pixva_home_v8_module_settings( $map[ $section ] );
	}
}

/* --------------------------------------------------------------------------
 * ۲) چیدمان پیش‌فرض المنتور (Default Elementor Page Template)
 * ----------------------------------------------------------------------- */

if ( ! function_exists( 'pixva_elementor_uid' ) ) {
	/**
	 * شناسه هفت‌نویسه المان المنتور (همان قالب `Utils::generate_random_string`).
	 *
	 * @param string $seed بذر خوانا برای لاگ/تست.
	 * @return string
	 */
	function pixva_elementor_uid( $seed = '' ) {
		return substr( md5( $seed . '|' . wp_rand() . '|' . microtime() ), 0, 7 );
	}
}

if ( ! function_exists( 'pixva_elementor_available' ) ) {
	/**
	 * آیا المنتور فعال است؟ (نگهبان فایل‌های المنتور — قانون همیشگی پوسته).
	 *
	 * @return bool
	 */
	function pixva_elementor_available() {
		return did_action( 'elementor/loaded' ) || class_exists( '\Elementor\Plugin' );
	}
}

if ( ! function_exists( 'pixva_home_elementor_widget_settings' ) ) {
	/**
	 * تنظیمات پیش‌پرشده یک ویجت برای چیدمان المنتور (قابل ویرایش در پنل).
	 *
	 * مقدارها با همان پیش‌فرض‌های رندر PHP یکی است تا آنچه مدیر در ویرایشگر
	 * می‌بیند با خروجی front-page.php مو نزند؛ ریپیترها (لایه‌ها و هات‌اسپیت‌ها)
	 * هم با همان ساختار کنترل‌ها پر می‌شوند.
	 *
	 * @param string $module نام ماژول: cinematic|ai|spline|tracker.
	 * @return array<string, mixed>
	 */
	function pixva_home_elementor_widget_settings( $module ) {
		$head = pixva_home_v8_module_settings( $module );

		switch ( $module ) {
			case 'cinematic':
				$layers = array();
				foreach ( pixva_cinematic_default_layers() as $index => $layer ) {
					$layers[] = array(
						'_id'           => pixva_elementor_uid( 'layer' . $index ),
						'layer_image'   => array(
							'url' => isset( $layer['image'] ) ? (string) $layer['image'] : '',
							'id'  => '',
						),
						'layer_title'   => isset( $layer['title'] ) ? (string) $layer['title'] : '',
						'layer_text'    => isset( $layer['text'] ) ? (string) $layer['text'] : '',
						'layer_service' => isset( $layer['link'] ) ? (string) $layer['link'] : '',
						'layer_depth'   => isset( $layer['depth'] ) ? (int) $layer['depth'] : (int) $index + 1,
					);
				}

				return array(
					'badge'    => isset( $head['badge'] ) ? (string) $head['badge'] : '',
					'title'    => isset( $head['title'] ) ? (string) $head['title'] : '',
					'subtitle' => isset( $head['subtitle'] ) ? (string) $head['subtitle'] : '',
					'cta_text' => __( 'برآورد هزینه این خدمت', 'pixva' ),
					'note'     => __( 'اسکرول را ادامه دهید تا لایه‌ها باز شوند', 'pixva' ),
					'layers'   => $layers,
					'height'   => array(
						'unit' => 'vh',
						'size' => 320,
					),
					'speed'    => 1,
					'spread'   => array(
						'unit' => 'px',
						'size' => 120,
					),
					'rotate'   => array(
						'unit' => 'deg',
						'size' => 16,
					),
					'pin'      => 'yes',
					'scrub'    => 'yes',
					'neon'     => 'rgb(34, 211, 238)',
					'neon2'    => 'rgb(168, 85, 247)',
				);

			case 'ai':
				return array(
					'badge'       => isset( $head['badge'] ) ? (string) $head['badge'] : '',
					'title'       => isset( $head['title'] ) ? (string) $head['title'] : '',
					'subtitle'    => isset( $head['subtitle'] ) ? (string) $head['subtitle'] : '',
					'video_label' => __( 'آپلود ویدیوی خرابی', 'pixva' ),
					'mic_label'   => __( 'ضبط صدای دستگاه', 'pixva' ),
					'submit'      => __( 'تحلیل هوشمند', 'pixva' ),
					'privacy'     => __( 'فایل‌ها فقط برای عیب‌یابی پرونده شما استفاده می‌شوند و پس از بسته‌شدن پرونده پاک می‌گردند.', 'pixva' ),
					'max_note'    => __( 'ویدیوی ۱۰ تا ۳۰ ثانیه‌ای کافی است؛ سقف حجم از تنظیمات پوسته خوانده می‌شود.', 'pixva' ),
					'show_phone'  => 'yes',
					'accept'      => 'video/*,audio/*',
					'pulse'       => array( 'size' => 2.4 ),
					'neon'        => 'rgb(56, 189, 248)',
					'neon2'       => 'rgb(168, 85, 247)',
				);

			case 'spline':
				$hotspots = array();
				foreach ( pixva_spline_demo_hotspots() as $index => $hot ) {
					$hotspots[] = array(
						'_id'       => pixva_elementor_uid( 'hot' . $index ),
						'hot_x'     => isset( $hot['x'] ) ? (float) $hot['x'] : 50,
						'hot_y'     => isset( $hot['y'] ) ? (float) $hot['y'] : 50,
						'hot_label' => isset( $hot['label'] ) ? (string) $hot['label'] : '',
						'hot_text'  => isset( $hot['text'] ) ? (string) $hot['text'] : '',
						'hot_part'  => isset( $hot['part'] ) ? (string) $hot['part'] : '',
					);
				}

				return array(
					'badge'    => isset( $head['badge'] ) ? (string) $head['badge'] : '',
					'title'    => isset( $head['title'] ) ? (string) $head['title'] : '',
					'subtitle' => isset( $head['subtitle'] ) ? (string) $head['subtitle'] : '',
					'url'      => array(
						'url'         => pixva_spline_demo_url(),
						'is_external' => '',
						'nofollow'    => '',
					),
					'lazy'     => 'yes',
					'fallback' => 'yes',
					'height'   => array(
						'unit' => 'rem',
						'size' => 34,
					),
					'hotspots' => $hotspots,
					'neon'     => 'rgb(248, 113, 113)',
					'accent'   => 'rgb(34, 211, 238)',
				);

			case 'tracker':
				return array(
					'badge'    => isset( $head['badge'] ) ? (string) $head['badge'] : '',
					'title'    => isset( $head['title'] ) ? (string) $head['title'] : '',
					'subtitle' => isset( $head['subtitle'] ) ? (string) $head['subtitle'] : '',
					'lookup'   => ! empty( $head['lookup'] ) ? 'yes' : '',
					'demo'     => ! empty( $head['demo'] ) ? 'yes' : '',
					'code'     => '',
					'phone'    => '',
					'refresh'  => 20,
					'height'   => array(
						'unit' => 'rem',
						'size' => 26,
					),
					'zoom'     => array( 'size' => 14 ),
					'neon'     => 'rgb(34, 211, 238)',
					'car'      => 'rgb(248, 113, 113)',
				);
		}

		return array();
	}
}

if ( ! function_exists( 'pixva_home_elementor_blocks' ) ) {
	/**
	 * چهار بلوک چیدمان پیش‌فرض: ماژول => [نام ویجت, نامک, عنوان].
	 *
	 * ترتیب همان ترتیب front-page.php است (Master Prompt v8).
	 *
	 * @return array<int, array{module:string, widget:string, slug:string, title:string}>
	 */
	function pixva_home_elementor_blocks() {
		/**
		 * فیلتر بلوک‌های چیدمان پیش‌فرض المنتور.
		 *
		 * @param array $blocks بلوک‌ها.
		 */
		return apply_filters(
			'pixva_home_elementor_blocks',
			array(
				array(
					'module' => 'cinematic',
					'widget' => 'pixva_cinematic_unboxing',
					'slug'   => 'cinematic-unboxing',
					'title'  => __( 'اسکرول سینمایی — نمای انفجاری تلویزیون', 'pixva' ),
				),
				array(
					'module' => 'ai',
					'widget' => 'pixva_ai_diagnose',
					'slug'   => 'ai-diagnose',
					'title'  => __( 'عیب‌یاب هوشمند — ویدیو و صدا', 'pixva' ),
				),
				array(
					'module' => 'spline',
					'widget' => 'pixva_3d_repair',
					'slug'   => 'repair-3d',
					'title'  => __( 'مدل سه‌بعدی تعاملی با هات‌اسپیت قیمت', 'pixva' ),
				),
				array(
					'module' => 'tracker',
					'widget' => 'pixva_tech_tracker',
					'slug'   => 'live-tech-tracker',
					'title'  => __( 'نقشه زنده تعمیرکار با مسیر دمو', 'pixva' ),
				),
			)
		);
	}
}

if ( ! function_exists( 'pixva_home_elementor_section' ) ) {
	/**
	 * ساخت یک «بخش» المنتور با یک ستون تمام‌عرض و ویجت سینمایی پیکسوا.
	 *
	 * @param array{module:string, widget:string, slug:string, title:string} $block بلوک.
	 * @return array<string, mixed>
	 */
	function pixva_home_elementor_section( $block ) {
		$settings = pixva_home_elementor_widget_settings( $block['module'] );

		return array(
			'id'       => pixva_elementor_uid( $block['slug'] . '-section' ),
			'elType'   => 'section',
			'settings' => array(
				/*
				 * شناسه HTML پایدار روی خودِ «بخش» (نه ویجت) تا لنگرها و تست‌ها
				 * به بخش درست برسند و شناسه تکراری با `<section>` داخلی ویجت
				 * ساخته نشود.
				 */
				'_element_id'           => 'pixva-' . $block['slug'],
				'structure'             => '10',
				'layout'                => 'boxed',
				'content_width'         => array(
					'unit' => 'px',
					'size' => 1180,
				),
				'gap'                   => 'no',
				'background_background' => 'classic',
				'background_color'      => '#070B14',
				'padding'               => array(
					'unit'     => 'px',
					'top'      => '12',
					'right'    => '12',
					'bottom'   => '12',
					'left'     => '12',
					'isLinked' => true,
				),
				'pixva_block_title'     => $block['title'],
			),
			'elements' => array(
				array(
					'id'       => pixva_elementor_uid( $block['slug'] . '-column' ),
					'elType'   => 'column',
					'settings' => array(
						'_column_size' => 100,
						'_inline_size' => null,
					),
					'elements' => array(
						array(
							'id'         => pixva_elementor_uid( $block['slug'] . '-widget' ),
							'elType'     => 'widget',
							'settings'   => $settings,
							'elements'   => array(),
							'widgetType' => $block['widget'],
						),
					),
					'isInner'  => false,
				),
			),
			'isInner'  => false,
		);
	}
}

if ( ! function_exists( 'pixva_home_elementor_template' ) ) {
	/**
	 * چیدمان پیش‌فرض المنتور صفحه اصلی (آرایه المان‌ها).
	 *
	 * @return array<int, array<string, mixed>>
	 */
	function pixva_home_elementor_template() {
		$elements = array();

		foreach ( pixva_home_elementor_blocks() as $block ) {
			$elements[] = pixva_home_elementor_section( $block );
		}

		/**
		 * فیلتر چیدمان پیش‌فرض المنتور (برای افزودن هیرو یا فوتر سفارشی).
		 *
		 * @param array $elements المان‌ها.
		 */
		return apply_filters( 'pixva_home_elementor_template', $elements );
	}
}

if ( ! function_exists( 'pixva_home_elementor_json' ) ) {
	/**
	 * JSON چیدمان پیش‌فرض (همان قالب `_elementor_data`).
	 *
	 * @return string
	 */
	function pixva_home_elementor_json() {
		$json = wp_json_encode( pixva_home_elementor_template(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );

		return is_string( $json ) ? $json : '';
	}
}

/* --------------------------------------------------------------------------
 * ۳) نوشتن چیدمان روی برگه خانه (بدون بازنویسی محتوای مدیر)
 * ----------------------------------------------------------------------- */

if ( ! function_exists( 'pixva_home_page_id' ) ) {
	/**
	 * شناسه برگه خانه (صفحه اول سایت).
	 *
	 * @return int
	 */
	function pixva_home_page_id() {
		$front = (int) get_option( 'page_on_front' );

		if ( 'page' === (string) get_option( 'show_on_front' ) && $front > 0 ) {
			return $front;
		}

		$page = get_page_by_path( 'home' );
		if ( $page instanceof WP_Post ) {
			return (int) $page->ID;
		}

		return 0;
	}
}

if ( ! function_exists( 'pixva_home_elementor_status' ) ) {
	/**
	 * وضعیت چیدمان المنتور صفحه اصلی (برای مرکز کنترل و تست‌ها).
	 *
	 * @return array{page:int, elementor:bool, seeded:bool, version:string, blocks:int}
	 */
	function pixva_home_elementor_status() {
		$page_id = pixva_home_page_id();
		$data    = $page_id ? (string) get_post_meta( $page_id, '_elementor_data', true ) : '';

		return array(
			'page'       => $page_id,
			'elementor'  => pixva_elementor_available(),
			'seeded'     => '' !== trim( $data ),
			'version'    => $page_id ? (string) get_post_meta( $page_id, '_pixva_home_seeded', true ) : '',
			'blocks'     => count( pixva_home_elementor_blocks() ),
		);
	}
}

if ( ! function_exists( 'pixva_seed_home_elementor' ) ) {
	/**
	 * نوشتن چیدمان پیش‌فرض در `_elementor_data` برگه خانه.
	 *
	 * نگهبان اصلی: اگر برگه خانه از قبل داده المنتور داشته باشد و `$force`
	 * خاموش باشد، هیچ چیزی بازنویسی نمی‌شود (کار مدیر محترم است).
	 *
	 * @param bool $force بازنویسی صریح (فقط با درخواست مدیر و نونس).
	 * @return array{ok:bool, message:string, page:int, forced:bool}
	 */
	function pixva_seed_home_elementor( $force = false ) {
		$page_id = pixva_home_page_id();

		if ( $page_id <= 0 ) {
			return array(
				'ok'      => false,
				'message' => __( 'برگه خانه پیدا نشد؛ ابتدا در «تنظیمات ← خواندن» یک برگه ایستا به‌عنوان صفحه اول انتخاب کنید.', 'pixva' ),
				'page'    => 0,
				'forced'  => (bool) $force,
			);
		}

		$json = pixva_home_elementor_json();
		if ( '' === $json ) {
			return array(
				'ok'      => false,
				'message' => __( 'چیدمان پیش‌فرض ساخته نشد (خطای JSON).', 'pixva' ),
				'page'    => $page_id,
				'forced'  => (bool) $force,
			);
		}

		$existing = trim( (string) get_post_meta( $page_id, '_elementor_data', true ) );
		if ( '' !== $existing && ! $force ) {
			return array(
				'ok'      => true,
				'message' => __( 'برگه خانه از قبل چیدمان المنتور دارد؛ چیزی بازنویسی نشد.', 'pixva' ),
				'page'    => $page_id,
				'forced'  => false,
			);
		}

		update_post_meta( $page_id, '_elementor_data', wp_slash( $json ) );
		update_post_meta( $page_id, '_elementor_edit_mode', 'builder' );
		update_post_meta( $page_id, '_elementor_template_type', 'wp-page' );
		update_post_meta( $page_id, '_elementor_version', defined( 'ELEMENTOR_VERSION' ) ? ELEMENTOR_VERSION : '3.25.0' );
		update_post_meta( $page_id, '_pixva_home_seeded', defined( 'PIXVA_VERSION' ) ? PIXVA_VERSION : '1.7.0' );
		delete_post_meta( $page_id, '_elementor_css' );

		// قالب ذخیره‌شده در کتابخانه المنتور (برای درج در هر برگه دیگر).
		pixva_home_elementor_library_template( $json );

		/**
		 * پس از نوشتن چیدمان پیش‌فرض صفحه اصلی.
		 *
		 * @param int    $page_id شناسه برگه خانه.
		 * @param bool   $force   بازنویسی صریح بود؟
		 */
		do_action( 'pixva_home_elementor_seeded', $page_id, (bool) $force );

		return array(
			'ok'      => true,
			'message' => $force
				? __( 'چیدمان پیش‌فرض چهار بخش سینمایی روی برگه خانه بازنویسی شد.', 'pixva' )
				: __( 'چیدمان پیش‌فرض چهار بخش سینمایی روی برگه خانه نوشته شد.', 'pixva' ),
			'page'    => $page_id,
			'forced'  => (bool) $force,
		);
	}
}

if ( ! function_exists( 'pixva_home_elementor_library_template' ) ) {
	/**
	 * ثبت چیدمان پیش‌فرض به‌عنوان قالب ذخیره‌شده در کتابخانه المنتور.
	 *
	 * @param string $json داده المان‌ها.
	 * @return int شناسه قالب (۰ یعنی ساخته نشد).
	 */
	function pixva_home_elementor_library_template( $json = '' ) {
		if ( ! pixva_elementor_available() || ! post_type_exists( 'elementor_library' ) ) {
			return 0;
		}

		$json  = '' !== $json ? $json : pixva_home_elementor_json();
		$title = __( 'پیکسوا — چیدمان پیش‌فرض خانه (سینمایی v8)', 'pixva' );

		$existing = get_posts(
			array(
				'post_type'      => 'elementor_library',
				'post_status'    => array( 'publish', 'draft' ),
				'posts_per_page' => 1,
				'no_found_rows'  => true,
				'meta_key'       => '_pixva_home_template',
				'meta_value'     => '1',
				'fields'         => 'ids',
			)
		);

		if ( ! empty( $existing ) ) {
			$template_id = (int) $existing[0];
			update_post_meta( $template_id, '_elementor_data', wp_slash( $json ) );
			return $template_id;
		}

		$template_id = wp_insert_post(
			array(
				'post_title'   => $title,
				'post_name'    => 'pixva-home-default-v8',
				'post_status'  => 'publish',
				'post_type'    => 'elementor_library',
				'post_content' => '',
			)
		);

		if ( is_wp_error( $template_id ) || ! $template_id ) {
			return 0;
		}

		update_post_meta( $template_id, '_elementor_data', wp_slash( $json ) );
		update_post_meta( $template_id, '_elementor_template_type', 'page' );
		update_post_meta( $template_id, '_elementor_edit_mode', 'builder' );
		update_post_meta( $template_id, '_elementor_version', defined( 'ELEMENTOR_VERSION' ) ? ELEMENTOR_VERSION : '3.25.0' );
		update_post_meta( $template_id, '_pixva_home_template', '1' );

		return (int) $template_id;
	}
}

if ( ! function_exists( 'pixva_maybe_seed_home_elementor' ) ) {
	/**
	 * بارگذاری خودکار چیدمان پیش‌فرض هنگام باز شدن برگه خانه در المنتور.
	 *
	 * فقط وقتی داده‌ای وجود نداشته باشد نوشته می‌شود، پس باز کردن مکرر ویرایشگر
	 * بی‌اثر است.
	 *
	 * @return void
	 */
	function pixva_maybe_seed_home_elementor() {
		// فقط در درخواست‌های ویرایشگر (بدون کوئری اضافی روی هر صفحه پیشخوان).
		$action = isset( $_GET['action'] ) ? sanitize_key( wp_unslash( $_GET['action'] ) ) : '';
		if ( ! in_array( $action, array( 'elementor', 'edit' ), true ) ) {
			return;
		}

		$post = isset( $_GET['post'] ) ? absint( wp_unslash( $_GET['post'] ) ) : 0;
		if ( $post <= 0 ) {
			return;
		}

		$home_id = pixva_home_page_id();
		if ( $home_id !== $post ) {
			return;
		}

		if ( '' !== trim( (string) get_post_meta( $home_id, '_elementor_data', true ) ) ) {
			return;
		}

		pixva_seed_home_elementor( false );
	}
	add_action( 'admin_init', 'pixva_maybe_seed_home_elementor', 20 );
}

if ( ! function_exists( 'pixva_seed_home_elementor_on_editor' ) ) {
	/**
	 * نگهبان دوم: هنگام بالا آمدن اسکریپت‌های ویرایشگر المنتور.
	 *
	 * @return void
	 */
	function pixva_seed_home_elementor_on_editor() {
		pixva_maybe_seed_home_elementor();
	}
	add_action( 'elementor/editor/before_enqueue_scripts', 'pixva_seed_home_elementor_on_editor', 5 );
}

if ( ! function_exists( 'pixva_handle_home_elementor_seed' ) ) {
	/**
	 * هندلر دکمه «بارگذاری/بازنشانی چیدمان المنتور» در مرکز کنترل.
	 *
	 * @return void
	 */
	function pixva_handle_home_elementor_seed() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'اجازه دسترسی ندارید.', 'pixva' ) );
		}

		check_admin_referer( 'pixva_seed_home_elementor' );

		$force  = isset( $_GET['force'] ) && '1' === sanitize_key( wp_unslash( (string) $_GET['force'] ) );
		$result = pixva_seed_home_elementor( $force );

		set_transient(
			'pixva_home_seed_result',
			array(
				'ok'      => ! empty( $result['ok'] ),
				'message' => isset( $result['message'] ) ? $result['message'] : '',
				'page'    => isset( $result['page'] ) ? (int) $result['page'] : 0,
			),
			90
		);

		wp_safe_redirect(
			add_query_arg(
				array(
					'page' => 'pixva-control',
					'tab'  => 'general',
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}
	add_action( 'admin_post_pixva_seed_home_elementor', 'pixva_handle_home_elementor_seed' );
}

if ( ! function_exists( 'pixva_home_seed_admin_notice' ) ) {
	/**
	 * اعلام نتیجه عملیات چیدمان پیش‌فرض در پیشخوان.
	 *
	 * @return void
	 */
	function pixva_home_seed_admin_notice() {
		$result = get_transient( 'pixva_home_seed_result' );
		if ( empty( $result ) || ! is_array( $result ) ) {
			return;
		}

		delete_transient( 'pixva_home_seed_result' );

		$class = empty( $result['ok'] ) ? 'notice notice-error' : 'notice notice-success';
		printf(
			'<div class="%1$s is-dismissible"><p>%2$s</p></div>',
			esc_attr( $class ),
			esc_html( (string) $result['message'] )
		);
	}
	add_action( 'admin_notices', 'pixva_home_seed_admin_notice' );
}

if ( ! function_exists( 'pixva_home_elementor_panel_html' ) ) {
	/**
	 * HTML پنل «چیدمان صفحه اصلی و المنتور» (در تب عمومی مرکز کنترل).
	 *
	 * @return string
	 */
	function pixva_home_elementor_panel_html() {
		$status = pixva_home_elementor_status();
		$nonce  = wp_create_nonce( 'pixva_seed_home_elementor' );
		$base   = admin_url( 'admin-post.php?action=pixva_seed_home_elementor' );
		$safe   = add_query_arg( '_wpnonce', $nonce, $base );
		$force  = add_query_arg( 'force', '1', $safe );
		$edit   = $status['page'] ? get_edit_post_link( $status['page'], '' ) : '';

		$rows = array();

		$rows[] = sprintf(
			'<strong>%s</strong> %s',
			esc_html__( 'برگه خانه:', 'pixva' ),
			$status['page']
				? sprintf( '<a href="%s">%s</a>', esc_url( (string) $edit ), esc_html( get_the_title( $status['page'] ) ) )
				: esc_html__( 'تنظیم نشده', 'pixva' )
		);

		$rows[] = sprintf(
			'<strong>%s</strong> %s',
			esc_html__( 'المنتور:', 'pixva' ),
			$status['elementor'] ? esc_html__( 'فعال', 'pixva' ) : esc_html__( 'غیرفعال — چیدمان نوشته می‌شود ولی برای ویرایش باید المنتور نصب باشد.', 'pixva' )
		);

		$rows[] = sprintf(
			'<strong>%s</strong> %s',
			esc_html__( 'چیدمان پیش‌فرض:', 'pixva' ),
			$status['seeded']
				? sprintf( esc_html__( 'نوشته شده (نسخه %s) — %s بخش', 'pixva' ), esc_html( $status['version'] ), esc_html( function_exists( 'pixva_fa_num' ) ? pixva_fa_num( (string) $status['blocks'] ) : (string) $status['blocks'] ) )
				: esc_html__( 'هنوز نوشته نشده', 'pixva' )
		);

		$buttons = sprintf(
			'<a class="button button-primary" href="%s">%s</a> <a class="button" href="%s" onclick="return confirm(\'%s\');">%s</a>%s',
			esc_url( $safe ),
			esc_html__( 'بارگذاری چیدمان پیش‌فرض (بدون بازنویسی)', 'pixva' ),
			esc_url( $force ),
			esc_js( __( 'چیدمان فعلی برگه خانه با چهار بخش پیش‌فرض بازنویسی می‌شود. ادامه می‌دهید؟', 'pixva' ) ),
			esc_html__( 'بازنشانی چیدمان', 'pixva' ),
			$status['page'] && $status['elementor']
				? sprintf( ' <a class="button" href="%s">%s</a>', esc_url( add_query_arg( array( 'action' => 'elementor', 'post' => $status['page'] ), admin_url( 'post.php' ) ) ), esc_html__( 'ویرایش صفحه اصلی در المنتور', 'pixva' ) )
				: ''
		);

		return '<p class="description">' . implode( '<br>', $rows ) . '</p><p>' . $buttons . '</p>';
	}
}

/* --------------------------------------------------------------------------
 * ۴) رندر چیدمان المنتور به‌جای چهار بخش سینمایی (اختیاری)
 * ----------------------------------------------------------------------- */

if ( ! function_exists( 'pixva_home_elementor_layout' ) ) {
	/**
	 * چاپ چیدمان المنتور برگه خانه به‌جای چهار بخش سینمایی پوسته.
	 *
	 * فقط وقتی اثر می‌کند که گزینه «رندر صفحه اصلی با چیدمان المنتور» روشن باشد
	 * و برگه خانه داده المنتور داشته باشد؛ در غیر این صورت false برمی‌گرداند و
	 * front-page.php همان سکشن‌های PHP را چاپ می‌کند. هیرو، سه مزیت، نمونه‌کار و
	 * نظرات در هر دو حالت از پوسته می‌آیند (ترتیب Master Prompt v8 حفظ می‌شود).
	 *
	 * @return bool آیا محتوای المنتور چاپ شد؟
	 */
	function pixva_home_elementor_layout() {
		if ( ! function_exists( 'pixva_option' ) || ! pixva_option( 'pixva_home_use_elementor', false ) ) {
			return false;
		}

		$page_id = pixva_home_page_id();
		if ( $page_id <= 0 || '' === trim( (string) get_post_meta( $page_id, '_elementor_data', true ) ) ) {
			return false;
		}

		if ( pixva_elementor_available() && class_exists( '\Elementor\Plugin' ) ) {
			$frontend = isset( \Elementor\Plugin::$instance ) ? \Elementor\Plugin::$instance->frontend : null;

			if ( $frontend && method_exists( $frontend, 'get_builder_content_for_display' ) ) {
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- خروجی سازنده المنتور.
				echo $frontend->get_builder_content_for_display( $page_id );
				return true;
			}
		}

		$content = (string) apply_filters( 'the_content', (string) get_post_field( 'post_content', $page_id ) );

		if ( '' === trim( wp_strip_all_tags( $content ) ) ) {
			return false;
		}

		echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- از فیلتر the_content گذشته است.
		return true;
	}
}

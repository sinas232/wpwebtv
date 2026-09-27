<?php
/**
 * ویجت المنتور: نقشه زنده تعمیرکار «Technician Tracker» — لایه ۱٫۶٫۰.
 *
 * نقشه حالت‌تاریک (Leaflet یا نقشه داخلی SVG پیکسوا) با مارکر متحرک ون تعمیر،
 * خط مسیر نئونی و کارت «در حال حرکت به سمت شما (ETA: …)». داده موقعیت از
 * `wp-json/pixva/v1/dispatch-live` با کد پیگیری + شماره همراه پرونده خوانده
 * می‌شود و رویداد `pixva:track-result` فرم ردیابی هم همین نقشه را پر می‌کند.
 *
 * @package Pixva
 * @since   1.6.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( '\Elementor\Widget_Base' ) && ! did_action( 'elementor/loaded' ) ) {
	return;
}

if ( ! class_exists( 'Pixva_Technician_Tracker_Widget' ) ) {
	/**
	 * ویجت نقشه زنده تعمیرکار.
	 */
	class Pixva_Technician_Tracker_Widget extends Pixva_Section_Widget_Base {

		/**
		 * نام ویجت.
		 *
		 * @return string
		 */
		public function get_name() {
			return 'pixva_tech_tracker';
		}

		/**
		 * عنوان.
		 *
		 * @return string
		 */
		public function get_title() {
			return esc_html__( 'نقشه زنده تعمیرکار (ردیابی سفارش)', 'pixva' );
		}

		/**
		 * آیکون.
		 *
		 * @return string
		 */
		public function get_icon() {
			return 'eicon-google-maps';
		}

		/**
		 * کلیدواژه‌ها.
		 *
		 * @return array<int, string>
		 */
		public function get_keywords() {
			return array( 'pixva', 'map', 'leaflet', 'tracker', 'live', 'technician', 'eta' );
		}

		/**
		 * وابستگی اسکریپت‌ها.
		 *
		 * @return array<int, string>
		 */
		public function get_script_depends() {
			return array( 'pixva-main', 'pixva-tools', 'pixva-tracker-map' );
		}

		/**
		 * بارگذاری دارایی‌ها هنگام رندر.
		 *
		 * @return void
		 */
		protected function enqueue_front_assets() {
			parent::enqueue_front_assets();
			if ( wp_style_is( 'pixva-leaflet', 'registered' ) ) {
				wp_enqueue_style( 'pixva-leaflet' );
			}
			if ( wp_script_is( 'pixva-leaflet', 'registered' ) ) {
				wp_enqueue_script( 'pixva-leaflet' );
			}
			wp_enqueue_script( 'pixva-tracker-map' );
		}

		/**
		 * ثبت کنترل‌ها.
		 *
		 * @return void
		 */
		protected function register_controls() {
			$this->start_controls_section(
				'pixva_map_head',
				array(
					'label' => esc_html__( 'سربرگ بخش', 'pixva' ),
					'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
				)
			);

			$this->add_control(
				'badge',
				array(
					'label'   => esc_html__( 'برچسب بالایی', 'pixva' ),
					'type'    => \Elementor\Controls_Manager::TEXT,
					'default' => __( 'ردیابی زنده', 'pixva' ),
				)
			);

			$this->add_control(
				'title',
				array(
					'label'   => esc_html__( 'تیتر', 'pixva' ),
					'type'    => \Elementor\Controls_Manager::TEXT,
					'default' => __( 'تعمیرکار کجاست؟', 'pixva' ),
				)
			);

			$this->add_control(
				'subtitle',
				array(
					'label'   => esc_html__( 'توضیح', 'pixva' ),
					'type'    => \Elementor\Controls_Manager::TEXTAREA,
					'default' => __( 'کد پیگیری را وارد کنید تا موقعیت تعمیرکار و زمان تقریبی رسیدن را زنده ببینید.', 'pixva' ),
				)
			);

			$this->end_controls_section();

			/* ----------------------------- جست‌وجو ----------------------------- */
			$this->start_controls_section(
				'pixva_map_lookup',
				array(
					'label' => esc_html__( 'پرونده و جست‌وجو', 'pixva' ),
					'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
				)
			);

			$this->add_control(
				'lookup',
				array(
					'label'        => esc_html__( 'نمایش فرم کد پیگیری', 'pixva' ),
					'type'         => \Elementor\Controls_Manager::SWITCHER,
					'default'      => 'yes',
					'return_value' => 'yes',
					'description'  => esc_html__( 'اگر خاموش باشد، فقط کد واردشده در پایین نمایش داده می‌شود.', 'pixva' ),
				)
			);

			$this->add_control(
				'demo',
				array(
					'label'        => esc_html__( 'حالت دمو (مسیر زنده نمایشی)', 'pixva' ),
					'type'         => \Elementor\Controls_Manager::SWITCHER,
					'default'      => 'yes',
					'return_value' => 'yes',
					'description'  => esc_html__( 'بدون کد پیگیری، نقشه با یک مسیر دمو که بر پایه زمان جلو می‌رود پر می‌شود (پیش‌فرض صفحه اصلی — لایه ۱٫۷٫۰). در صفحه‌های واقعی پرونده آن را خاموش کنید.', 'pixva' ),
				)
			);

			$this->add_control(
				'code',
				array(
					'label'       => esc_html__( 'کد پیگیری ثابت', 'pixva' ),
					'type'        => \Elementor\Controls_Manager::TEXT,
					'default'     => '',
					'placeholder' => 'PXV-G-250926-A1B2C3',
					'description' => esc_html__( 'برای صفحه نمونه/دمو؛ در حالت عادی خالی بگذارید تا کاربر کد خودش را وارد کند.', 'pixva' ),
				)
			);

			$this->add_control(
				'phone',
				array(
					'label'       => esc_html__( 'شماره همراه ثابت', 'pixva' ),
					'type'        => \Elementor\Controls_Manager::TEXT,
					'default'     => '',
					'placeholder' => '09xxxxxxxxx',
					'description' => esc_html__( 'برای تأیید مالکیت پرونده لازم است؛ همراه کد ثابت برای دمو استفاده می‌شود.', 'pixva' ),
				)
			);

			$this->add_control(
				'refresh',
				array(
					'label'       => esc_html__( 'بازه نوسازی موقعیت (ثانیه)', 'pixva' ),
					'type'        => \Elementor\Controls_Manager::NUMBER,
					'min'         => 5,
					'max'         => 120,
					'step'        => 5,
					'default'     => 20,
					'description' => esc_html__( 'موقعیت تعمیرکار در همین بازه از REST به‌روزرسانی می‌شود.', 'pixva' ),
				)
			);

			$this->end_controls_section();

			/* ------------------------------ نقشه ------------------------------ */
			$this->start_controls_section(
				'pixva_map_view',
				array(
					'label' => esc_html__( 'نمای نقشه', 'pixva' ),
					'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
				)
			);

			// ارتفاع درون‌خطی با متغیر --map-h روی خود بخش نوشته می‌شود، بنابراین
			// این کنترل selectors ندارد (قالب CSS روی استایل درون‌خطی اثر نمی‌کند).
			$this->add_control(
				'height',
				array(
					'label'       => esc_html__( 'ارتفاع نقشه (rem)', 'pixva' ),
					'type'        => \Elementor\Controls_Manager::SLIDER,
					'size_units'  => array( 'rem' ),
					'range'       => array(
						'rem' => array(
							'min' => 14,
							'max' => 60,
						),
					),
					'default'     => array(
						'unit' => 'rem',
						'size' => 26,
					),
					'description' => esc_html__( 'برای ارتفاع متفاوت در موبایل، متغیر --map-h را با CSS دلخواه بازنویسی کنید.', 'pixva' ),
				)
			);

			$this->add_control(
				'zoom',
				array(
					'label'   => esc_html__( 'بزرگ‌نمایی اولیه', 'pixva' ),
					'type'    => \Elementor\Controls_Manager::SLIDER,
					'range'   => array(
						'px' => array(
							'min'  => 3,
							'max'  => 18,
							'step' => 1,
						),
					),
					'default' => array(
						'size' => 14,
					),
				)
			);

			$this->add_control(
				'neon',
				array(
					'label'   => esc_html__( 'رنگ نئونی مسیر', 'pixva' ),
					'type'    => \Elementor\Controls_Manager::COLOR,
					'default' => 'rgb(34, 211, 238)',
				)
			);

			$this->add_control(
				'car',
				array(
					'label'   => esc_html__( 'رنگ مارکر تعمیرکار', 'pixva' ),
					'type'    => \Elementor\Controls_Manager::COLOR,
					'default' => 'rgb(248, 113, 113)',
				)
			);

			$this->add_control(
				'map_note',
				array(
					'label'       => esc_html__( 'نکته', 'pixva' ),
					'type'        => \Elementor\Controls_Manager::RAW_HTML,
					'raw'         => esc_html__( 'مبدأ/مقصد، سرعت میانگین و کاشی نقشه در «سفارشی‌سازی پوسته → نقشه زنده» تنظیم می‌شوند؛ اگر Leaflet در assets/js/vendor موجود باشد نقشه واقعی و در غیر این صورت نقشه داخلی SVG بارگذاری می‌شود.', 'pixva' ),
					'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
				)
			);

			$this->end_controls_section();

			$this->pixva_card_style_section();
		}

		/**
		 * خروجی.
		 *
		 * @return void
		 */
		protected function render() {
			$settings = $this->get_settings_for_display();
			$this->enqueue_front_assets();

			$slider = function ( $value, $fallback ) {
				if ( is_array( $value ) ) {
					return isset( $value['size'] ) && '' !== $value['size'] ? (float) $value['size'] : (float) $fallback;
				}
				return is_numeric( $value ) ? (float) $value : (float) $fallback;
			};

			pixva_render_technician_tracker(
				array(
					'badge'      => (string) $settings['badge'],
					'title'      => (string) $settings['title'],
					'subtitle'   => (string) $settings['subtitle'],
					'code'       => (string) $settings['code'],
					'phone'      => (string) $settings['phone'],
					'lookup'     => isset( $settings['lookup'] ) && 'yes' === $settings['lookup'],
					'demo'       => isset( $settings['demo'] ) && 'yes' === $settings['demo'],
					'height'     => (int) $slider( $settings['height'], 26 ),
					'zoom'       => (int) $slider( $settings['zoom'], 14 ),
					'refresh'    => (int) $slider( $settings['refresh'], 20 ),
					'neon'       => '' !== trim( (string) $settings['neon'] ) ? (string) $settings['neon'] : 'rgb(34, 211, 238)',
					'car'        => '' !== trim( (string) $settings['car'] ) ? (string) $settings['car'] : 'rgb(248, 113, 113)',
					'element_id' => isset( $settings['_element_id'] ) ? (string) $settings['_element_id'] : '',
				)
			);
		}
	}
}

<?php
/**
 * ویجت المنتور: مدل سه‌بعدی تعاملی «Interactive 3D Repair» — لایه ۱٫۶٫۰.
 *
 * مدل Spline (WebGL) با بارگذاری تنبل نمایش داده می‌شود؛ کاربر با ماوس آن را
 * می‌چرخاند و با کلیک روی نقطه‌های قرمز (قطعه معیوب) کارت استعلام قیمت باز
 * می‌شود. اگر آدرس مدل خالی باشد یا بارگذاری ناموفق باشد، نمای لایه‌ای داخلی (CSS 3D)
 * جایگزین می‌گردد تا بخش هیچ‌وقت خالی نماند.
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

if ( ! class_exists( 'Pixva_3d_Repair_Widget' ) ) {
	/**
	 * ویجت مدل سه‌بعدی تعاملی.
	 */
	class Pixva_3d_Repair_Widget extends Pixva_Section_Widget_Base {

		/**
		 * نام ویجت.
		 *
		 * @return string
		 */
		public function get_name() {
			return 'pixva_3d_repair';
		}

		/**
		 * عنوان.
		 *
		 * @return string
		 */
		public function get_title() {
			return esc_html__( 'مدل سه‌بعدی تعاملی تعمیر (Spline)', 'pixva' );
		}

		/**
		 * آیکون.
		 *
		 * @return string
		 */
		public function get_icon() {
			return 'eicon-360';
		}

		/**
		 * کلیدواژه‌ها.
		 *
		 * @return array<int, string>
		 */
		public function get_keywords() {
			return array( 'pixva', 'spline', 'webgl', '3d', 'model', 'repair', 'hotspot' );
		}

		/**
		 * وابستگی اسکریپت‌ها.
		 *
		 * @return array<int, string>
		 */
		public function get_script_depends() {
			return array( 'pixva-main', 'pixva-spline' );
		}

		/**
		 * بارگذاری دارایی‌ها هنگام رندر.
		 *
		 * @return void
		 */
		protected function enqueue_front_assets() {
			parent::enqueue_front_assets();
			wp_enqueue_script( 'pixva-spline' );
		}

		/**
		 * گزینه‌های قطعه برای هات‌اسپیت.
		 *
		 * @return array<string, string>
		 */
		protected function part_options() {
			$options = array( '' => esc_html__( '— انتخاب خدمت —', 'pixva' ) );

			if ( function_exists( 'pixva_service_fallbacks' ) ) {
				foreach ( pixva_service_fallbacks() as $item ) {
					if ( ! empty( $item['key'] ) ) {
						$options[ $item['key'] ] = (string) $item['title'];
					}
				}
			}

			$options['custom'] = esc_html__( 'آدرس دلخواه', 'pixva' );

			return $options;
		}

		/**
		 * ثبت کنترل‌ها.
		 *
		 * @return void
		 */
		protected function register_controls() {
			$this->start_controls_section(
				'pixva_3d_head',
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
					'default' => __( 'نمای سه‌بعدی', 'pixva' ),
				)
			);

			$this->add_control(
				'title',
				array(
					'label'   => esc_html__( 'تیتر', 'pixva' ),
					'type'    => \Elementor\Controls_Manager::TEXT,
					'default' => __( 'دستگاه را بچرخانید و قطعه معیوب را لمس کنید', 'pixva' ),
				)
			);

			$this->add_control(
				'subtitle',
				array(
					'label'   => esc_html__( 'توضیح', 'pixva' ),
					'type'    => \Elementor\Controls_Manager::TEXTAREA,
					'default' => __( 'با ماوس مدل را بچرخانید؛ روی نقطه‌های قرمز کلیک کنید تا برآورد قیمت همان قطعه باز شود.', 'pixva' ),
				)
			);

			$this->end_controls_section();

			/* ------------------------------ مدل ------------------------------ */
			$this->start_controls_section(
				'pixva_3d_model',
				array(
					'label' => esc_html__( 'مدل Spline', 'pixva' ),
					'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
				)
			);

			$this->add_control(
				'url',
				array(
					'label'       => esc_html__( 'آدرس مدل Spline', 'pixva' ),
					'type'        => \Elementor\Controls_Manager::URL,
					'placeholder' => 'https://prod.spline.design/xxxx/scene.splinecode',
					'default'     => array( 'url' => '' ),
					'description' => esc_html__( 'از Spline خروجی «Copy as URL / scene.splinecode» بگیرید. خالی بگذارید تا صحنه دمو (لایه ۱٫۷٫۰) بارگذاری شود؛ اگر ماژول یا صحنه در دسترس نبود، نمای لایه‌ای CSS-3D داخلی جایگزین می‌گردد.', 'pixva' ),
				)
			);

			$this->add_control(
				'lazy',
				array(
					'label'        => esc_html__( 'بارگذاری تنبل (فقط هنگام نزدیک شدن به دید کاربر)', 'pixva' ),
					'type'         => \Elementor\Controls_Manager::SWITCHER,
					'default'      => 'yes',
					'return_value' => 'yes',
				)
			);

			$this->add_control(
				'fallback',
				array(
					'label'        => esc_html__( 'نمای لایه‌ای داخلی به‌عنوان جایگزین', 'pixva' ),
					'type'         => \Elementor\Controls_Manager::SWITCHER,
					'default'      => 'yes',
					'return_value' => 'yes',
				)
			);

			$this->add_control(
				'height',
				array(
					'label'      => esc_html__( 'ارتفاع صحنه (rem)', 'pixva' ),
					'type'       => \Elementor\Controls_Manager::SLIDER,
					'size_units' => array( 'rem' ),
					'range'      => array(
						'rem' => array(
							'min' => 16,
							'max' => 60,
						),
					),
					'default'    => array(
						'unit' => 'rem',
						'size' => 34,
					),
				)
			);

			$this->end_controls_section();

			/* --------------------------- هات‌اسپیت‌ها --------------------------- */
			$this->start_controls_section(
				'pixva_3d_hots',
				array(
					'label' => esc_html__( 'نقطه‌های قطعه معیوب', 'pixva' ),
					'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
				)
			);

			$repeater = new \Elementor\Repeater();

			$repeater->add_control(
				'hot_x',
				array(
					'label'   => esc_html__( 'موقعیت افقی (٪)', 'pixva' ),
					'type'    => \Elementor\Controls_Manager::NUMBER,
					'min'     => 0,
					'max'     => 100,
					'default' => 50,
				)
			);

			$repeater->add_control(
				'hot_y',
				array(
					'label'   => esc_html__( 'موقعیت عمودی (٪)', 'pixva' ),
					'type'    => \Elementor\Controls_Manager::NUMBER,
					'min'     => 0,
					'max'     => 100,
					'default' => 50,
				)
			);

			$repeater->add_control(
				'hot_label',
				array(
					'label'   => esc_html__( 'برچسب قطعه', 'pixva' ),
					'type'    => \Elementor\Controls_Manager::TEXT,
					'default' => __( 'بک‌لایت سوخته', 'pixva' ),
				)
			);

			$repeater->add_control(
				'hot_text',
				array(
					'label'   => esc_html__( 'توضیح خرابی', 'pixva' ),
					'type'    => \Elementor\Controls_Manager::TEXTAREA,
					'default' => __( 'شرح کوتاه ایراد و کاری که در کارگاه انجام می‌شود.', 'pixva' ),
				)
			);

			$repeater->add_control(
				'hot_part',
				array(
					'label'   => esc_html__( 'خدمت/قطعه مرتبط', 'pixva' ),
					'type'    => \Elementor\Controls_Manager::SELECT,
					'default' => 'backlight',
					'options' => $this->part_options(),
				)
			);

			$repeater->add_control(
				'hot_url',
				array(
					'label'     => esc_html__( 'آدرس دلخواه دکمه استعلام', 'pixva' ),
					'type'      => \Elementor\Controls_Manager::URL,
					'condition' => array( 'hot_part' => 'custom' ),
				)
			);

			$this->add_control(
				'hotspots',
				array(
					'label'       => esc_html__( 'نقطه‌ها', 'pixva' ),
					'type'        => \Elementor\Controls_Manager::REPEATER,
					'fields'      => $repeater->get_controls(),
					'default'     => array(),
					'title_field' => '{{{ hot_label }}}',
				)
			);

			$this->end_controls_section();

			/* ------------------------------- سبک ------------------------------ */
			$this->start_controls_section(
				'pixva_3d_style',
				array(
					'label' => esc_html__( 'رنگ نئونی', 'pixva' ),
					'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
				)
			);

			$this->add_control(
				'neon',
				array(
					'label'   => esc_html__( 'رنگ نقطه‌های خرابی', 'pixva' ),
					'type'    => \Elementor\Controls_Manager::COLOR,
					'default' => 'rgb(248, 113, 113)',
				)
			);

			$this->add_control(
				'accent',
				array(
					'label'   => esc_html__( 'رنگ نور صحنه', 'pixva' ),
					'type'    => \Elementor\Controls_Manager::COLOR,
					'default' => 'rgb(34, 211, 238)',
				)
			);

			$this->add_responsive_control(
				'hot_size',
				array(
					'label'      => esc_html__( 'اندازه نقطه‌ها', 'pixva' ),
					'type'       => \Elementor\Controls_Manager::SLIDER,
					'size_units' => array( 'px' ),
					'range'      => array(
						'px' => array(
							'min' => 10,
							'max' => 34,
						),
					),
					'default'    => array(
						'unit' => 'px',
						'size' => 18,
					),
					'selectors'  => array(
						'{{WRAPPER}} .pixva-3d__hot-dot' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
					),
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

			$hotspots = array();
			if ( ! empty( $settings['hotspots'] ) && is_array( $settings['hotspots'] ) ) {
				foreach ( $settings['hotspots'] as $row ) {
					$part = isset( $row['hot_part'] ) ? (string) $row['hot_part'] : '';
					if ( 'custom' === $part && ! empty( $row['hot_url']['url'] ) ) {
						$part = (string) $row['hot_url']['url'];
					}

					$hotspots[] = array(
						'x'     => isset( $row['hot_x'] ) ? (float) $row['hot_x'] : 50,
						'y'     => isset( $row['hot_y'] ) ? (float) $row['hot_y'] : 50,
						'label' => isset( $row['hot_label'] ) ? (string) $row['hot_label'] : '',
						'text'  => isset( $row['hot_text'] ) ? (string) $row['hot_text'] : '',
						'part'  => $part,
					);
				}
			}

			$height = is_array( $settings['height'] ) && isset( $settings['height']['size'] ) ? (int) $settings['height']['size'] : 34;

			pixva_render_spline_3d(
				array(
					'badge'      => (string) $settings['badge'],
					'title'      => (string) $settings['title'],
					'subtitle'   => (string) $settings['subtitle'],
					'url'        => ! empty( $settings['url']['url'] ) ? (string) $settings['url']['url'] : '',
					'lazy'       => isset( $settings['lazy'] ) && 'yes' === $settings['lazy'],
					'fallback'   => isset( $settings['fallback'] ) && 'yes' === $settings['fallback'],
					'height'     => $height,
					'hotspots'   => $hotspots,
					'neon'       => '' !== trim( (string) $settings['neon'] ) ? (string) $settings['neon'] : 'rgb(248, 113, 113)',
					'accent'     => '' !== trim( (string) $settings['accent'] ) ? (string) $settings['accent'] : 'rgb(34, 211, 238)',
					'element_id' => isset( $settings['_element_id'] ) ? (string) $settings['_element_id'] : '',
				)
			);
		}
	}
}

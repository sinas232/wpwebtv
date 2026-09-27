<?php
/**
 * ویجت المنتور: چهار اصل اعتماد و گارانتی — ماژول ۳ (لایه ۲٫۰٫۰ / v11).
 *
 * بخش سریع و خوانا شامل تعمیر در محل، گارانتی کتبی قطعات فابریک، اعزام
 * تکنسین در کمترین زمان و برآورد شفاف هزینه پیش از تعمیر. مقادیر عددی
 * (مدت گارانتی و زمان اعزام) از داده واقعی مرکز کنترل خوانده می‌شوند.
 *
 * @package Pixva
 * @since   2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( '\Elementor\Widget_Base' ) && ! did_action( 'elementor/loaded' ) ) {
	return;
}

if ( ! class_exists( 'Pixva_Trust_Features_Widget' ) ) {
	/**
	 * ویجت اصول اعتماد.
	 */
	class Pixva_Trust_Features_Widget extends Pixva_Section_Widget_Base {

		/**
		 * نام ویجت.
		 *
		 * @return string
		 */
		public function get_name() {
			return 'pixva_trust_features';
		}

		/**
		 * عنوان.
		 *
		 * @return string
		 */
		public function get_title() {
			return esc_html__( 'چهار اصل اعتماد و گارانتی', 'pixva' );
		}

		/**
		 * آیکون.
		 *
		 * @return string
		 */
		public function get_icon() {
			return 'eicon-check-circle-o';
		}

		/**
		 * کلیدواژه‌ها.
		 *
		 * @return array<int, string>
		 */
		public function get_keywords() {
			return array( 'pixva', 'trust', 'warranty', 'guarantee', 'onsite', 'cro' );
		}

		/**
		 * وابستگی اسکریپت: هیچ — خروجی HTML/CSS خالص است.
		 *
		 * @return array<int, string>
		 */
		public function get_script_depends() {
			return array();
		}

		/**
		 * دارایی front-end.
		 *
		 * @return void
		 */
		protected function enqueue_front_assets() {
			if ( wp_style_is( 'pixva-2026', 'registered' ) ) {
				wp_enqueue_style( 'pixva-2026' );
			}
		}

		/**
		 * ثبت کنترل‌ها.
		 *
		 * @return void
		 */
		protected function register_controls() {
			$this->start_controls_section(
				'pixva_trust_content',
				array(
					'label' => esc_html__( 'سربرگ', 'pixva' ),
					'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
				)
			);

			$this->add_control(
				'title',
				array(
					'label'       => esc_html__( 'عنوان بخش', 'pixva' ),
					'type'        => \Elementor\Controls_Manager::TEXT,
					'default'     => '',
					'placeholder' => esc_html__( 'چرا تعمیر تلویزیون را به پیکسوا بسپارید؟', 'pixva' ),
					'label_block' => true,
				)
			);

			$this->add_control(
				'subtitle',
				array(
					'label'   => esc_html__( 'توضیح زیر عنوان', 'pixva' ),
					'type'    => \Elementor\Controls_Manager::TEXTAREA,
					'default' => '',
					'rows'    => 2,
				)
			);

			$this->add_control(
				'trust_note',
				array(
					'type'            => \Elementor\Controls_Manager::RAW_HTML,
					'raw'             => esc_html__( 'مدت گارانتی و زمان اعزام از تنظیمات مرکز کنترل خوانده می‌شود؛ نیازی به وارد کردن دستی عدد نیست.', 'pixva' ),
					'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
				)
			);

			$this->end_controls_section();

			$this->start_controls_section(
				'pixva_trust_layout',
				array(
					'label' => esc_html__( 'چیدمان', 'pixva' ),
					'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
				)
			);

			$this->add_responsive_control(
				'columns',
				array(
					'label'          => esc_html__( 'تعداد ستون', 'pixva' ),
					'type'           => \Elementor\Controls_Manager::SLIDER,
					'size_units'     => array( 'px' ),
					'range'          => array(
						'px' => array(
							'min' => 1,
							'max' => 4,
						),
					),
					'default'        => array(
						'unit' => 'px',
						'size' => 4,
					),
					'tablet_default' => array(
						'unit' => 'px',
						'size' => 2,
					),
					'mobile_default' => array(
						'unit' => 'px',
						'size' => 1,
					),
					'selectors'      => array(
						'{{WRAPPER}} .pixva-trust__grid' => 'grid-template-columns: repeat({{SIZE}}, minmax(0, 1fr));',
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

			if ( ! function_exists( 'pixva_render_trust_features' ) ) {
				return;
			}

			pixva_render_trust_features(
				array(
					'title'    => (string) $settings['title'],
					'subtitle' => (string) $settings['subtitle'],
				)
			);
		}
	}
}

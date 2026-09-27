<?php
/**
 * ویجت المنتور: راهنمای متنی علائم خرابی — ماژول سئو ۱ (لایه ۲٫۰٫۰ / v11).
 *
 * کارت‌های متنی کاملاً ایندکس‌شدنی برای جست‌وجوهای پرتکرار گوگل («صدا دارد
 * ولی تصویر ندارد»، «خطوط عمودی»، «روشن نمی‌شود»، «روی لوگو گیر کرده») به‌همراه
 * خروجی اسکیما FAQPage و Service تا گوگل پاسخ را مستقیم در نتایج نشان دهد.
 *
 * بدون تصویر سنگین، بدون انیمیشن اسکرول و بدون هیچ کتابخانه JS.
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

if ( ! class_exists( 'Pixva_Symptom_Guide_Widget' ) ) {
	/**
	 * ویجت راهنمای علائم خرابی (سئو).
	 */
	class Pixva_Symptom_Guide_Widget extends Pixva_Section_Widget_Base {

		/**
		 * نام ویجت.
		 *
		 * @return string
		 */
		public function get_name() {
			return 'pixva_symptom_guide';
		}

		/**
		 * عنوان.
		 *
		 * @return string
		 */
		public function get_title() {
			return esc_html__( 'راهنمای علائم خرابی (سئو)', 'pixva' );
		}

		/**
		 * آیکون.
		 *
		 * @return string
		 */
		public function get_icon() {
			return 'eicon-search-bold';
		}

		/**
		 * کلیدواژه‌ها.
		 *
		 * @return array<int, string>
		 */
		public function get_keywords() {
			return array( 'pixva', 'seo', 'symptom', 'faq', 'schema', 'guide', 'backlight' );
		}

		/**
		 * وابستگی اسکریپت: هیچ — خروجی صرفاً HTML متنی است.
		 *
		 * @return array<int, string>
		 */
		public function get_script_depends() {
			return array();
		}

		/**
		 * دارایی front-end: فقط سبک پوسته (بدون اسکریپت سنگین).
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
				'pixva_symptom_content',
				array(
					'label' => esc_html__( 'سربرگ و دکمه', 'pixva' ),
					'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
				)
			);

			$this->add_control(
				'title',
				array(
					'label'       => esc_html__( 'عنوان بخش', 'pixva' ),
					'type'        => \Elementor\Controls_Manager::TEXT,
					'default'     => '',
					'placeholder' => esc_html__( 'مشکل تلویزیون شما چیست؟', 'pixva' ),
					'description' => esc_html__( 'خالی = مقدار سفارشی‌ساز/پیش‌فرض ترجمه‌شده.', 'pixva' ),
					'label_block' => true,
				)
			);

			$this->add_control(
				'subtitle',
				array(
					'label'   => esc_html__( 'توضیح زیر عنوان', 'pixva' ),
					'type'    => \Elementor\Controls_Manager::TEXTAREA,
					'default' => '',
					'rows'    => 3,
				)
			);

			$this->add_control(
				'cta_text',
				array(
					'label'       => esc_html__( 'متن دکمه هر کارت', 'pixva' ),
					'type'        => \Elementor\Controls_Manager::TEXT,
					'default'     => '',
					'placeholder' => esc_html__( 'ثبت درخواست اعزام فوری تکنسین', 'pixva' ),
					'label_block' => true,
				)
			);

			$this->add_control(
				'cta_url',
				array(
					'label'       => esc_html__( 'مقصد دکمه', 'pixva' ),
					'type'        => \Elementor\Controls_Manager::URL,
					'placeholder' => esc_html__( 'خالی = برگه محاسبه‌گر قیمت', 'pixva' ),
				)
			);

			$this->add_control(
				'schema_note',
				array(
					'type'            => \Elementor\Controls_Manager::RAW_HTML,
					'raw'             => esc_html__( 'اسکیما FAQPage و Service به‌صورت خودکار همراه بخش چاپ می‌شود تا گوگل پاسخ‌ها را در نتایج جستجو نمایش دهد.', 'pixva' ),
					'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
				)
			);

			$this->end_controls_section();

			$this->start_controls_section(
				'pixva_symptom_layout',
				array(
					'label' => esc_html__( 'چیدمان', 'pixva' ),
					'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
				)
			);

			$this->add_responsive_control(
				'columns',
				array(
					'label'          => esc_html__( 'تعداد ستون کارت‌ها', 'pixva' ),
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
						'size' => 2,
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
						'{{WRAPPER}} .pixva-symptom__grid' => 'grid-template-columns: repeat({{SIZE}}, minmax(0, 1fr));',
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

			if ( ! function_exists( 'pixva_render_symptom_guide' ) ) {
				return;
			}

			$cta_url = '';
			if ( ! empty( $settings['cta_url']['url'] ) ) {
				$cta_url = (string) $settings['cta_url']['url'];
			}

			pixva_render_symptom_guide(
				array(
					'title'    => (string) $settings['title'],
					'subtitle' => (string) $settings['subtitle'],
					'cta_text' => (string) $settings['cta_text'],
					'cta_url'  => $cta_url,
				)
			);
		}
	}
}

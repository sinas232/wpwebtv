<?php
/**
 * ویجت المنتور: جدول شفاف هزینه‌ها — ماژول سئو ۲ (لایه ۲٫۰٫۰ / v11).
 *
 * جدول متنی و ایندکس‌شدنی حدود قیمت قطعه و اجرت تعمیر با فیلتر سریع برند و
 * سایز (۳۲ تا ۷۵ اینچ). همه مبالغ در لحظه از موتور نرخ‌نامه واقعی
 * (pixva_calculate_estimate) محاسبه می‌شوند و اسکیما Service با
 * PriceSpecification همراه جدول چاپ می‌شود.
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

if ( ! class_exists( 'Pixva_Price_Calculator_Widget' ) ) {
	/**
	 * ویجت جدول شفاف قیمت تعمیر.
	 */
	class Pixva_Price_Calculator_Widget extends Pixva_Section_Widget_Base {

		/**
		 * نام ویجت.
		 *
		 * @return string
		 */
		public function get_name() {
			return 'pixva_price_calculator';
		}

		/**
		 * عنوان.
		 *
		 * @return string
		 */
		public function get_title() {
			return esc_html__( 'جدول شفاف هزینه تعمیر (سئو)', 'pixva' );
		}

		/**
		 * آیکون.
		 *
		 * @return string
		 */
		public function get_icon() {
			return 'eicon-price-list';
		}

		/**
		 * کلیدواژه‌ها.
		 *
		 * @return array<int, string>
		 */
		public function get_keywords() {
			return array( 'pixva', 'seo', 'price', 'table', 'schema', 'cost', 'rates' );
		}

		/**
		 * وابستگی اسکریپت: فقط فایل سبک فیلتر جدول (بدون کتابخانه بیرونی).
		 *
		 * @return array<int, string>
		 */
		public function get_script_depends() {
			return array( 'pixva-seo-cro' );
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
			if ( function_exists( 'pixva_seo_cro_assets' ) ) {
				pixva_seo_cro_assets();
			}
		}

		/**
		 * ثبت کنترل‌ها.
		 *
		 * @return void
		 */
		protected function register_controls() {
			$this->start_controls_section(
				'pixva_price_content',
				array(
					'label' => esc_html__( 'سربرگ و مقادیر پیش‌فرض', 'pixva' ),
					'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
				)
			);

			$this->add_control(
				'title',
				array(
					'label'       => esc_html__( 'عنوان بخش', 'pixva' ),
					'type'        => \Elementor\Controls_Manager::TEXT,
					'default'     => '',
					'placeholder' => esc_html__( 'جدول شفاف هزینه تعمیر تلویزیون', 'pixva' ),
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

			$brand_options = array( '' => esc_html__( 'پیش‌فرض سفارشی‌ساز', 'pixva' ) );
			if ( function_exists( 'pixva_brand_catalog' ) ) {
				foreach ( (array) pixva_brand_catalog() as $key => $brand ) {
					$brand_options[ $key ] = isset( $brand['fa'] ) ? $brand['fa'] : $key;
				}
			}

			$this->add_control(
				'default_brand',
				array(
					'label'       => esc_html__( 'برند پیش‌فرض جدول', 'pixva' ),
					'type'        => \Elementor\Controls_Manager::SELECT,
					'default'     => '',
					'options'     => $brand_options,
					'description' => esc_html__( 'کاربر می‌تواند از فیلتر روی بخش، برند را عوض کند.', 'pixva' ),
				)
			);

			$size_options = array( '' => esc_html__( 'پیش‌فرض سفارشی‌ساز', 'pixva' ) );
			if ( function_exists( 'pixva_price_table_sizes' ) ) {
				foreach ( (array) pixva_price_table_sizes() as $size ) {
					$size_options[ $size ] = $size . ' ' . esc_html__( 'اینچ', 'pixva' );
				}
			}

			$this->add_control(
				'default_size',
				array(
					'label'   => esc_html__( 'سایز پیش‌فرض جدول', 'pixva' ),
					'type'    => \Elementor\Controls_Manager::SELECT,
					'default' => '',
					'options' => $size_options,
				)
			);

			$this->add_control(
				'price_note',
				array(
					'type'            => \Elementor\Controls_Manager::RAW_HTML,
					'raw'             => esc_html__( 'مبلغ‌ها سمت سرور از نرخ‌نامه رسمی کارگاه محاسبه می‌شوند و اسکیما PriceSpecification به‌صورت خودکار چاپ می‌شود.', 'pixva' ),
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

			if ( ! function_exists( 'pixva_render_price_calculator' ) ) {
				return;
			}

			pixva_render_price_calculator(
				array(
					'title'         => (string) $settings['title'],
					'subtitle'      => (string) $settings['subtitle'],
					'default_brand' => isset( $settings['default_brand'] ) ? (string) $settings['default_brand'] : '',
					'default_size'  => isset( $settings['default_size'] ) ? (string) $settings['default_size'] : '',
				)
			);
		}
	}
}

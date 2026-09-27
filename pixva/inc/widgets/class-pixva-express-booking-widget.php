<?php
/**
 * ویجت المنتور: فرم درخواست سریع یک‌مرحله‌ای — ماژول ۴ (لایه ۲٫۰٫۰ / v11).
 *
 * فقط دو فیلد (شماره موبایل + برند و مشکل دستگاه) با دکمه «ثبت درخواست اعزام
 * فوری تکنسین». ارسال به اندپوینت داخلی pixva/v1/express-booking که پرونده
 * تعمیر می‌سازد و پیامک فوری به مشتری و مدیر/تکنسین می‌فرستد.
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

if ( ! class_exists( 'Pixva_Express_Booking_Widget' ) ) {
	/**
	 * ویجت فرم اعزام فوری تکنسین.
	 */
	class Pixva_Express_Booking_Widget extends Pixva_Section_Widget_Base {

		/**
		 * نام ویجت.
		 *
		 * @return string
		 */
		public function get_name() {
			return 'pixva_express_booking';
		}

		/**
		 * عنوان.
		 *
		 * @return string
		 */
		public function get_title() {
			return esc_html__( 'فرم اعزام فوری تکنسین (تبدیل)', 'pixva' );
		}

		/**
		 * آیکون.
		 *
		 * @return string
		 */
		public function get_icon() {
			return 'eicon-form-horizontal';
		}

		/**
		 * کلیدواژه‌ها.
		 *
		 * @return array<int, string>
		 */
		public function get_keywords() {
			return array( 'pixva', 'booking', 'express', 'form', 'lead', 'cro', 'dispatch' );
		}

		/**
		 * وابستگی اسکریپت: فقط فایل سبک فرم.
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
				'pixva_express_content',
				array(
					'label' => esc_html__( 'متن‌ها و برچسب فیلدها', 'pixva' ),
					'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
				)
			);

			$this->add_control(
				'title',
				array(
					'label'       => esc_html__( 'عنوان بخش', 'pixva' ),
					'type'        => \Elementor\Controls_Manager::TEXT,
					'default'     => '',
					'placeholder' => esc_html__( 'اعزام فوری تکنسین تعمیر تلویزیون', 'pixva' ),
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
				'phone_label',
				array(
					'label'       => esc_html__( 'برچسب فیلد موبایل', 'pixva' ),
					'type'        => \Elementor\Controls_Manager::TEXT,
					'default'     => '',
					'placeholder' => esc_html__( 'شماره موبایل', 'pixva' ),
					'label_block' => true,
				)
			);

			$this->add_control(
				'details_label',
				array(
					'label'       => esc_html__( 'برچسب فیلد برند و مشکل', 'pixva' ),
					'type'        => \Elementor\Controls_Manager::TEXT,
					'default'     => '',
					'placeholder' => esc_html__( 'برند و مشکل دستگاه', 'pixva' ),
					'label_block' => true,
				)
			);

			$this->add_control(
				'cta_text',
				array(
					'label'       => esc_html__( 'متن دکمه اقدام', 'pixva' ),
					'type'        => \Elementor\Controls_Manager::TEXT,
					'default'     => '',
					'placeholder' => esc_html__( 'ثبت درخواست اعزام فوری تکنسین', 'pixva' ),
					'label_block' => true,
				)
			);

			$this->add_control(
				'source',
				array(
					'label'       => esc_html__( 'منبع پرونده (گزارش‌گیری)', 'pixva' ),
					'type'        => \Elementor\Controls_Manager::TEXT,
					'default'     => 'elementor',
					'description' => esc_html__( 'در متای پرونده ثبت می‌شود تا بدانید درخواست از کدام بخش آمده است.', 'pixva' ),
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

			if ( ! function_exists( 'pixva_render_express_booking' ) ) {
				return;
			}

			pixva_render_express_booking(
				array(
					'title'         => (string) $settings['title'],
					'subtitle'      => (string) $settings['subtitle'],
					'phone_label'   => (string) $settings['phone_label'],
					'details_label' => (string) $settings['details_label'],
					'cta_text'      => (string) $settings['cta_text'],
					'source'        => '' !== trim( (string) $settings['source'] ) ? sanitize_key( (string) $settings['source'] ) : 'elementor',
				)
			);
		}
	}
}

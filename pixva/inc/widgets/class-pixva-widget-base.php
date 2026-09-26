<?php
/**
 * کلاس پایه ویجت‌های سکشن پیکسوا در المنتور.
 *
 * مسئولیت‌ها: دسته‌بندی مشترک، کنترل‌های سبک یکسان (کارت شیشه‌ای، شعاع، سایه
 * لایه‌ای، گرادیان اکشن)، تولید متغیرهای CSS از تنظیمات و بارگذاری دارایی‌ها.
 *
 * @package Pixva
 * @since   1.2.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( '\Elementor\Widget_Base' ) && ! did_action( 'elementor/loaded' ) ) {
	return;
}

if ( ! class_exists( 'Pixva_Section_Widget_Base' ) ) {
	/**
	 * پایه مشترک ویجت‌های سکشن.
	 */
	abstract class Pixva_Section_Widget_Base extends \Elementor\Widget_Base {

		/**
		 * دسته‌بندی ویجت در پنل المنتور.
		 *
		 * @return array<int, string>
		 */
		public function get_categories() {
			return array( 'pixva-diagnostics' );
		}

		/**
		 * کلیدواژه‌های جستجو.
		 *
		 * @return array<int, string>
		 */
		public function get_keywords() {
			return array( 'pixva', 'tv', 'repair', 'television', 'diagnostics' );
		}

		/**
		 * وابستگی‌های سبک.
		 *
		 * @return array<int, string>
		 */
		public function get_style_depends() {
			return array( 'pixva-2026' );
		}

		/**
		 * وابستگی‌های اسکریپت.
		 *
		 * @return array<int, string>
		 */
		public function get_script_depends() {
			return array( 'pixva-main', 'pixva-tools' );
		}

		/**
		 * بارگذاری دارایی‌های پوسته هنگام رندر (برای نصب‌هایی که اسکریپت‌ها
		 * به‌صورت شرطی صف می‌شوند).
		 *
		 * @return void
		 */
		protected function enqueue_front_assets() {
			wp_enqueue_style( 'pixva-2026' );
			wp_enqueue_script( 'pixva-main' );
			wp_enqueue_script( 'pixva-tools' );
		}

		/**
		 * سکشن مشترک «سبک کارت» برای همه ویجت‌های سکشن.
		 *
		 * @return void
		 */
		protected function pixva_card_style_section() {
			$this->start_controls_section(
				'pixva_card_style',
				array(
					'label' => esc_html__( 'سبک کارت پیکسوا', 'pixva' ),
					'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
				)
			);

			$this->add_control(
				'card_style',
				array(
					'label'   => esc_html__( 'متریال کارت', 'pixva' ),
					'type'    => \Elementor\Controls_Manager::SELECT,
					'default' => 'glass',
					'options' => array(
						'glass'   => esc_html__( 'شیشه‌ای (Backdrop Blur)', 'pixva' ),
						'solid'   => esc_html__( 'سفید یکدست', 'pixva' ),
						'dark'    => esc_html__( 'سرمه‌ای تیره', 'pixva' ),
						'none'    => esc_html__( 'بدون کارت', 'pixva' ),
					),
				)
			);

			$this->add_responsive_control(
				'card_radius',
				array(
					'label'      => esc_html__( 'گردی گوشه‌ها', 'pixva' ),
					'type'       => \Elementor\Controls_Manager::SLIDER,
					'size_units' => array( 'px', 'rem' ),
					'range'      => array(
						'px' => array(
							'min' => 0,
							'max' => 48,
						),
					),
					'default'    => array(
						'unit' => 'px',
						'size' => 24,
					),
					'selectors'  => array(
						'{{WRAPPER}} .pixva-block' => 'border-radius: {{SIZE}}{{UNIT}};',
					),
				)
			);

			$this->add_responsive_control(
				'card_padding',
				array(
					'label'      => esc_html__( 'فضای داخلی', 'pixva' ),
					'type'       => \Elementor\Controls_Manager::SLIDER,
					'size_units' => array( 'px', 'rem' ),
					'range'      => array(
						'px' => array(
							'min' => 0,
							'max' => 80,
						),
					),
					'default'    => array(
						'unit' => 'px',
						'size' => 26,
					),
					'selectors'  => array(
						'{{WRAPPER}} .pixva-block' => 'padding: {{SIZE}}{{UNIT}};',
					),
				)
			);

			$this->add_control(
				'accent_start',
				array(
					'label'   => esc_html__( 'گرادیان اکشن — رنگ اول', 'pixva' ),
					'type'    => \Elementor\Controls_Manager::COLOR,
					'default' => '#6366F1',
				)
			);

			$this->add_control(
				'accent_end',
				array(
					'label'   => esc_html__( 'گرادیان اکشن — رنگ دوم', 'pixva' ),
					'type'    => \Elementor\Controls_Manager::COLOR,
					'default' => '#8B5CF6',
				)
			);

			$this->add_control(
				'heading_color',
				array(
					'label'   => esc_html__( 'رنگ عنوان‌ها', 'pixva' ),
					'type'    => \Elementor\Controls_Manager::COLOR,
					'default' => '#0F172A',
					'selectors' => array(
						'{{WRAPPER}} h2, {{WRAPPER}} h3, {{WRAPPER}} h4, {{WRAPPER}} strong' => 'color: {{VALUE}};',
					),
				)
			);

			$this->add_control(
				'body_color',
				array(
					'label'     => esc_html__( 'رنگ متن', 'pixva' ),
					'type'      => \Elementor\Controls_Manager::COLOR,
					'default'   => '#334155',
					'selectors' => array(
						'{{WRAPPER}} p, {{WRAPPER}} span, {{WRAPPER}} li' => 'color: {{VALUE}};',
					),
				)
			);

			$this->end_controls_section();
		}

		/**
		 * ساخت متغیرهای CSS درون‌خطی از تنظیمات سبک.
		 *
		 * @param array $settings تنظیمات ویجت.
		 * @return string
		 */
		protected function pixva_style_vars( $settings ) {
			$vars = array();

			if ( ! empty( $settings['accent_start'] ) ) {
				$vars[] = '--pixva-accent-a:' . $settings['accent_start'];
			}
			if ( ! empty( $settings['accent_end'] ) ) {
				$vars[] = '--pixva-accent-b:' . $settings['accent_end'];
			}
			if ( ! empty( $settings['heading_color'] ) ) {
				$vars[] = '--pixva-heading:' . $settings['heading_color'];
			}
			if ( ! empty( $settings['body_color'] ) ) {
				$vars[] = '--pixva-body:' . $settings['body_color'];
			}

			return $vars ? esc_attr( implode( ';', $vars ) . ';' ) : '';
		}

		/**
		 * کلاس پوسته بر اساس متریال انتخابی.
		 *
		 * @param array $settings تنظیمات ویجت.
		 * @return string
		 */
		protected function pixva_block_class( $settings ) {
			$style = isset( $settings['card_style'] ) ? (string) $settings['card_style'] : 'glass';
			return 'pixva-block pixva-block--' . sanitize_html_class( $style );
		}

		/**
		 * گزینه‌های نوع خرابی برای کنترل‌های SELECT (از کاتالوگ داینامیک).
		 *
		 * @return array<string, string>
		 */
		protected function pixva_problem_options() {
			$options = array( '' => esc_html__( '— بدون کلید نرخ‌نامه —', 'pixva' ) );
			if ( function_exists( 'pixva_problem_catalog' ) ) {
				foreach ( pixva_problem_catalog() as $key => $label ) {
					$options[ $key ] = $label;
				}
			}
			return $options;
		}
	}
}

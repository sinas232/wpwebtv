<?php
/**
 * ویجت المنتور: اسکرول سینمایی «Cinematic TV Unboxing» — لایه ۱٫۶٫۰.
 *
 * المان در مرکز صفحه قفل (Pin) می‌شود و با اسکرول، لایه‌های دستگاه به‌صورت
 * نمای انفجاری سه‌بعدی از هم باز می‌شوند؛ توضیح خدمت هر قطعه کنارش ظاهر می‌شود.
 * همه تنظیمات (تصویر لایه‌ها، سرعت، میزان باز شدن، چرخش، رنگ نئونی و متن‌ها)
 * در المنتور رایگان قابل ویرایش است.
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

if ( ! class_exists( 'Pixva_Cinematic_Unboxing_Widget' ) ) {
	/**
	 * ویجت اسکرول سینمایی.
	 */
	class Pixva_Cinematic_Unboxing_Widget extends Pixva_Section_Widget_Base {

		/**
		 * نام ویجت.
		 *
		 * @return string
		 */
		public function get_name() {
			return 'pixva_cinematic_unboxing';
		}

		/**
		 * عنوان ویجت در پنل.
		 *
		 * @return string
		 */
		public function get_title() {
			return esc_html__( 'اسکرول سینمایی (نمای انفجاری دستگاه)', 'pixva' );
		}

		/**
		 * آیکون.
		 *
		 * @return string
		 */
		public function get_icon() {
			return 'eicon-animation-text';
		}

		/**
		 * کلیدواژه‌ها.
		 *
		 * @return array<int, string>
		 */
		public function get_keywords() {
			return array( 'pixva', 'gsap', 'scroll', 'cinematic', '3d', 'unboxing', 'exploded' );
		}

		/**
		 * وابستگی اسکریپت‌ها (برای بارگذاری خودکار در المنتور).
		 *
		 * @return array<int, string>
		 */
		public function get_script_depends() {
			return array( 'pixva-main', 'pixva-gsap', 'pixva-scrolltrigger', 'pixva-cinematic' );
		}

		/**
		 * بارگذاری دارایی‌ها هنگام رندر.
		 *
		 * @return void
		 */
		protected function enqueue_front_assets() {
			parent::enqueue_front_assets();
			wp_enqueue_script( 'pixva-gsap' );
			if ( wp_script_is( 'pixva-scrolltrigger', 'registered' ) ) {
				wp_enqueue_script( 'pixva-scrolltrigger' );
			}
			wp_enqueue_script( 'pixva-cinematic' );
		}

		/**
		 * گزینه‌های خدمت برای هر لایه.
		 *
		 * @return array<string, string>
		 */
		protected function service_options() {
			$options = array(
				'' => esc_html__( 'بدون پیوند (فقط نمایش)', 'pixva' ),
			);

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
				'pixva_cine_head',
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
					'default' => __( 'کالبدشکافی زنده', 'pixva' ),
				)
			);

			$this->add_control(
				'title',
				array(
					'label'   => esc_html__( 'تیتر', 'pixva' ),
					'type'    => \Elementor\Controls_Manager::TEXT,
					'default' => __( 'داخل یک تلویزیون چه می‌گذرد؟', 'pixva' ),
				)
			);

			$this->add_control(
				'subtitle',
				array(
					'label'   => esc_html__( 'توضیح', 'pixva' ),
					'type'    => \Elementor\Controls_Manager::TEXTAREA,
					'default' => __( 'با اسکرول، لایه‌های دستگاه از هم باز می‌شوند و خدمت تعمیر هر قطعه کنارش ظاهر می‌شود.', 'pixva' ),
				)
			);

			$this->add_control(
				'cta_text',
				array(
					'label'   => esc_html__( 'متن دکمه هر لایه', 'pixva' ),
					'type'    => \Elementor\Controls_Manager::TEXT,
					'default' => __( 'برآورد هزینه این خدمت', 'pixva' ),
				)
			);

			$this->add_control(
				'note',
				array(
					'label'   => esc_html__( 'راهنمای اسکرول', 'pixva' ),
					'type'    => \Elementor\Controls_Manager::TEXT,
					'default' => __( 'اسکرول را ادامه دهید تا لایه‌ها باز شوند', 'pixva' ),
				)
			);

			$this->end_controls_section();

			/* ------------------------- لایه‌های دستگاه ------------------------ */
			$this->start_controls_section(
				'pixva_cine_layers',
				array(
					'label' => esc_html__( 'لایه‌های دستگاه', 'pixva' ),
					'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
				)
			);

			$repeater = new \Elementor\Repeater();

			$repeater->add_control(
				'layer_image',
				array(
					'label'   => esc_html__( 'تصویر لایه', 'pixva' ),
					'type'    => \Elementor\Controls_Manager::MEDIA,
					'default' => array( 'url' => '' ),
				)
			);

			$repeater->add_control(
				'layer_title',
				array(
					'label'   => esc_html__( 'عنوان قطعه', 'pixva' ),
					'type'    => \Elementor\Controls_Manager::TEXT,
					'default' => __( 'پنل و شیشه نمایشگر', 'pixva' ),
				)
			);

			$repeater->add_control(
				'layer_text',
				array(
					'label'   => esc_html__( 'توضیح خدمت تعمیر', 'pixva' ),
					'type'    => \Elementor\Controls_Manager::TEXTAREA,
					'default' => __( 'شرح کوتاه خدمتی که برای این قطعه ارائه می‌شود.', 'pixva' ),
				)
			);

			$repeater->add_control(
				'layer_service',
				array(
					'label'   => esc_html__( 'خدمت مرتبط', 'pixva' ),
					'type'    => \Elementor\Controls_Manager::SELECT,
					'default' => 'panel',
					'options' => $this->service_options(),
				)
			);

			$repeater->add_control(
				'layer_url',
				array(
					'label'       => esc_html__( 'آدرس دلخواه', 'pixva' ),
					'type'        => \Elementor\Controls_Manager::URL,
					'placeholder' => 'https://',
					'condition'   => array( 'layer_service' => 'custom' ),
				)
			);

			$repeater->add_control(
				'layer_depth',
				array(
					'label'   => esc_html__( 'عمق لایه (۱ نزدیک‌ترین)', 'pixva' ),
					'type'    => \Elementor\Controls_Manager::NUMBER,
					'min'     => 1,
					'max'     => 9,
					'default' => 1,
				)
			);

			$this->add_control(
				'layers',
				array(
					'label'       => esc_html__( 'لایه‌ها (به‌ترتیب نمایش)', 'pixva' ),
					'type'        => \Elementor\Controls_Manager::REPEATER,
					'fields'      => $repeater->get_controls(),
					'default'     => array(),
					'title_field' => '{{{ layer_title }}}',
				)
			);

			$this->add_control(
				'layers_notice',
				array(
					'type'        => \Elementor\Controls_Manager::HEADING,
					'label'       => esc_html__( 'اگر لایه‌ای وارد نکنید، پنج لایه دمو (قاب رویی، شیشه پنل، بک‌لایت نئونی، برد اصلی و قاب پشتی) از assets/images/demo نمایش داده می‌شود.', 'pixva' ),
					'separator'   => 'before',
				)
			);

			$this->end_controls_section();

			/* --------------------------- انیمیشن اسکرول -------------------------- */
			$this->start_controls_section(
				'pixva_cine_motion',
				array(
					'label' => esc_html__( 'انیمیشن اسکرول', 'pixva' ),
					'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
				)
			);

			$this->add_control(
				'height',
				array(
					'label'       => esc_html__( 'طول اسکرول (ارتفاع viewport درصد)', 'pixva' ),
					'type'        => \Elementor\Controls_Manager::SLIDER,
					'size_units'  => array( 'vh' ),
					'range'       => array(
						'vh' => array(
							'min' => 150,
							'max' => 700,
						),
					),
					'default'     => array(
						'unit' => 'vh',
						'size' => 320,
					),
				)
			);

			$this->add_control(
				'speed',
				array(
					'label'       => esc_html__( 'سرعت انیمیشن (۱ = عادی)', 'pixva' ),
					'type'        => \Elementor\Controls_Manager::NUMBER,
					'min'         => 0.2,
					'max'         => 3,
					'step'        => 0.1,
					'default'     => 1,
				)
			);

			$this->add_control(
				'spread',
				array(
					'label'       => esc_html__( 'میزان باز شدن لایه‌ها (پیکسل)', 'pixva' ),
					'type'        => \Elementor\Controls_Manager::SLIDER,
					'size_units'  => array( 'px' ),
					'range'       => array(
						'px' => array(
							'min' => 40,
							'max' => 320,
						),
					),
					'default'     => array(
						'unit' => 'px',
						'size' => 120,
					),
				)
			);

			$this->add_control(
				'rotate',
				array(
					'label'       => esc_html__( 'چرخش سه‌بعدی (درجه)', 'pixva' ),
					'type'        => \Elementor\Controls_Manager::SLIDER,
					'size_units'  => array( 'deg' ),
					'range'       => array(
						'deg' => array(
							'min' => 0,
							'max' => 45,
						),
					),
					'default'     => array(
						'unit' => 'deg',
						'size' => 16,
					),
				)
			);

			$this->add_control(
				'pin',
				array(
					'label'        => esc_html__( 'قفل شدن در مرکز صفحه (Pin)', 'pixva' ),
					'type'         => \Elementor\Controls_Manager::SWITCHER,
					'default'      => 'yes',
					'return_value' => 'yes',
				)
			);

			$this->add_control(
				'scrub',
				array(
					'label'        => esc_html__( 'وابسته به اسکرول (Scrub)', 'pixva' ),
					'type'         => \Elementor\Controls_Manager::SWITCHER,
					'default'      => 'yes',
					'return_value' => 'yes',
				)
			);

			$this->end_controls_section();

			/* ------------------------------ سبک ------------------------------ */
			$this->start_controls_section(
				'pixva_cine_style',
				array(
					'label' => esc_html__( 'رنگ نئونی', 'pixva' ),
					'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
				)
			);

			$this->add_control(
				'neon',
				array(
					'label'   => esc_html__( 'نور اصلی', 'pixva' ),
					'type'    => \Elementor\Controls_Manager::COLOR,
					'default' => 'rgb(34, 211, 238)',
				)
			);

			$this->add_control(
				'neon2',
				array(
					'label'   => esc_html__( 'نور مکمل', 'pixva' ),
					'type'    => \Elementor\Controls_Manager::COLOR,
					'default' => 'rgb(168, 85, 247)',
				)
			);

			$this->add_responsive_control(
				'perspective',
				array(
					'label'      => esc_html__( 'عمق میدان سه‌بعدی (Perspective)', 'pixva' ),
					'type'       => \Elementor\Controls_Manager::SLIDER,
					'size_units' => array( 'px' ),
					'range'      => array(
						'px' => array(
							'min' => 600,
							'max' => 2400,
						),
					),
					'default'    => array(
						'unit' => 'px',
						'size' => 1200,
					),
					'selectors'  => array(
						'{{WRAPPER}} .pixva-cine__scene' => 'perspective: {{SIZE}}{{UNIT}};',
					),
				)
			);

			$this->add_responsive_control(
				'layer_radius',
				array(
					'label'      => esc_html__( 'گردی گوشه لایه‌ها', 'pixva' ),
					'type'       => \Elementor\Controls_Manager::SLIDER,
					'size_units' => array( 'px' ),
					'range'      => array(
						'px' => array(
							'min' => 0,
							'max' => 40,
						),
					),
					'default'    => array(
						'unit' => 'px',
						'size' => 18,
					),
					'selectors'  => array(
						'{{WRAPPER}} .pixva-cine__layer' => 'border-radius: {{SIZE}}{{UNIT}};',
					),
				)
			);

			$this->end_controls_section();

			$this->pixva_card_style_section();
		}

		/**
		 * خروجی ویجت.
		 *
		 * @return void
		 */
		protected function render() {
			$settings = $this->get_settings_for_display();
			$this->enqueue_front_assets();

			$layers = array();
			if ( ! empty( $settings['layers'] ) && is_array( $settings['layers'] ) ) {
				foreach ( $settings['layers'] as $row ) {
					$image   = '';
					if ( ! empty( $row['layer_image']['url'] ) ) {
						$image = (string) $row['layer_image']['url'];
					}

					$service = isset( $row['layer_service'] ) ? (string) $row['layer_service'] : '';
					if ( 'custom' === $service && ! empty( $row['layer_url']['url'] ) ) {
						$service = (string) $row['layer_url']['url'];
					}

					$layers[] = array(
						'image' => $image,
						'title' => isset( $row['layer_title'] ) ? (string) $row['layer_title'] : '',
						'text'  => isset( $row['layer_text'] ) ? (string) $row['layer_text'] : '',
						'link'  => $service,
						'depth' => isset( $row['layer_depth'] ) ? (int) $row['layer_depth'] : 1,
					);
				}
			}

			// کنترل‌های SLIDER آرایه و کنترل‌های NUMBER مقدار ساده برمی‌گردانند.
			$slider = function ( $value, $fallback ) {
				if ( is_array( $value ) ) {
					return isset( $value['size'] ) && '' !== $value['size'] ? (float) $value['size'] : (float) $fallback;
				}
				return is_numeric( $value ) ? (float) $value : (float) $fallback;
			};

			pixva_render_cinematic_unboxing(
				array(
					'badge'      => (string) $settings['badge'],
					'title'      => (string) $settings['title'],
					'subtitle'   => (string) $settings['subtitle'],
					'cta_text'   => (string) $settings['cta_text'],
					'note'       => (string) $settings['note'],
					'layers'     => $layers,
					'height'     => (int) $slider( $settings['height'], 320 ),
					'speed'      => $slider( $settings['speed'], 1 ),
					'spread'     => (int) $slider( $settings['spread'], 120 ),
					'rotate'     => (int) $slider( $settings['rotate'], 16 ),
					'pin'        => isset( $settings['pin'] ) && 'yes' === $settings['pin'],
					'scrub'      => isset( $settings['scrub'] ) && 'yes' === $settings['scrub'],
					'neon'       => '' !== trim( (string) $settings['neon'] ) ? (string) $settings['neon'] : 'rgb(34, 211, 238)',
					'neon2'      => '' !== trim( (string) $settings['neon2'] ) ? (string) $settings['neon2'] : 'rgb(168, 85, 247)',
					'element_id' => isset( $settings['_element_id'] ) ? (string) $settings['_element_id'] : '',
				)
			);
		}
	}
}

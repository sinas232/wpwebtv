<?php
/**
 * ویجت المنتور: هیرو لندینگ (متن + اقدام‌ها + سیمولاتور زنده).
 *
 * همان ساختار هیروی صفحه اصلی قالب، اما کاملاً قابل ویرایش در المنتور:
 * عنوان، زیرتیتر، بج‌ها، دکمه‌ها، آمار و جاسازی سیمولاتور عیب‌یابی.
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

if ( ! class_exists( 'Pixva_Hero_Widget' ) ) {
	/**
	 * ویجت هیرو.
	 */
	class Pixva_Hero_Widget extends Pixva_Section_Widget_Base {

		/**
		 * نام ویجت.
		 *
		 * @return string
		 */
		public function get_name() {
			return 'pixva_hero';
		}

		/**
		 * عنوان.
		 *
		 * @return string
		 */
		public function get_title() {
			return esc_html__( 'هیرو لندینگ پیکسوا', 'pixva' );
		}

		/**
		 * آیکون.
		 *
		 * @return string
		 */
		public function get_icon() {
			return 'eicon-banner';
		}

		/**
		 * کنترل‌ها.
		 *
		 * @return void
		 */
		protected function register_controls() {
			$this->start_controls_section(
				'pixva_hero_content',
				array(
					'label' => esc_html__( 'محتوا', 'pixva' ),
					'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
				)
			);

			$this->add_control(
				'title',
				array(
					'label'       => esc_html__( 'عنوان اصلی (H1)', 'pixva' ),
					'type'        => \Elementor\Controls_Manager::TEXT,
					'default'     => esc_html__( 'تعمیر تخصصی تلویزیون با گارانتی کتبی ۱۸۰ روزه', 'pixva' ),
					'label_block' => true,
					'dynamic'     => array( 'active' => true ),
				)
			);

			$this->add_control(
				'subtitle',
				array(
					'label'   => esc_html__( 'توضیح', 'pixva' ),
					'type'    => \Elementor\Controls_Manager::TEXTAREA,
					'rows'    => 4,
					'default' => esc_html__( 'کارگاه مرکزی پیکسوا؛ تعمیر پنل، بک‌لایت، برد پاور و مین‌برد با قطعات فابریک و دستگاه بندینگ صنعتی.', 'pixva' ),
					'dynamic' => array( 'active' => true ),
				)
			);

			$badges = new \Elementor\Repeater();
			$badges->add_control(
				'badge_text',
				array(
					'label'       => esc_html__( 'متن بج', 'pixva' ),
					'type'        => \Elementor\Controls_Manager::TEXT,
					'default'     => esc_html__( 'گارانتی کتبی ۱۸۰ روزه', 'pixva' ),
					'label_block' => true,
				)
			);
			$badges->add_control(
				'badge_style',
				array(
					'label'   => esc_html__( 'سبک', 'pixva' ),
					'type'    => \Elementor\Controls_Manager::SELECT,
					'default' => 'brand',
					'options' => array(
						'brand'   => esc_html__( 'برند', 'pixva' ),
						'success' => esc_html__( 'سبز (گارانتی)', 'pixva' ),
						'accent'  => esc_html__( 'فیروزه‌ای', 'pixva' ),
						''        => esc_html__( 'خنثی', 'pixva' ),
					),
				)
			);

			$this->add_control(
				'badges',
				array(
					'label'       => esc_html__( 'بج‌های اعتماد', 'pixva' ),
					'type'        => \Elementor\Controls_Manager::REPEATER,
					'fields'      => $badges->get_controls(),
					'default'     => array(),
					'title_field' => '{{{ badge_text }}}',
				)
			);

			$buttons = new \Elementor\Repeater();
			$buttons->add_control(
				'button_text',
				array(
					'label'       => esc_html__( 'متن دکمه', 'pixva' ),
					'type'        => \Elementor\Controls_Manager::TEXT,
					'default'     => esc_html__( 'استعلام سریع قیمت', 'pixva' ),
					'label_block' => true,
				)
			);
			$buttons->add_control(
				'button_link',
				array(
					'label'       => esc_html__( 'لینک', 'pixva' ),
					'type'        => \Elementor\Controls_Manager::URL,
					'default'     => array( 'url' => '#' ),
					'label_block' => true,
				)
			);
			$buttons->add_control(
				'button_style',
				array(
					'label'   => esc_html__( 'سبک دکمه', 'pixva' ),
					'type'    => \Elementor\Controls_Manager::SELECT,
					'default' => 'gradient',
					'options' => array(
						'gradient' => esc_html__( 'گرادیان آبی-بنفش', 'pixva' ),
						'orange'   => esc_html__( 'نارنجی تبدیل', 'pixva' ),
						'ghost'    => esc_html__( 'حاشیه‌دار روشن', 'pixva' ),
						'primary'  => esc_html__( 'رنگ اصلی پوسته', 'pixva' ),
					),
				)
			);

			$this->add_control(
				'buttons',
				array(
					'label'       => esc_html__( 'دکمه‌های اقدام', 'pixva' ),
					'type'        => \Elementor\Controls_Manager::REPEATER,
					'fields'      => $buttons->get_controls(),
					'default'     => array(),
					'title_field' => '{{{ button_text }}}',
				)
			);

			$this->add_control(
				'show_stats',
				array(
					'label'        => esc_html__( 'نمایش آمار', 'pixva' ),
					'type'         => \Elementor\Controls_Manager::SWITCHER,
					'return_value' => 'yes',
					'default'      => 'yes',
				)
			);

			$this->add_control(
				'show_simulator',
				array(
					'label'        => esc_html__( 'جاسازی سیمولاتور عیب‌یابی', 'pixva' ),
					'type'         => \Elementor\Controls_Manager::SWITCHER,
					'return_value' => 'yes',
					'default'      => 'yes',
				)
			);

			$this->add_control(
				'simulator_source',
				array(
					'label'     => esc_html__( 'منبع ایرادهای سیمولاتور', 'pixva' ),
					'type'      => \Elementor\Controls_Manager::SELECT,
					'default'   => 'rate_card',
					'options'   => array(
						'rate_card' => esc_html__( 'نرخ‌نامه (سه ایراد پرکاربرد)', 'pixva' ),
						'widget'    => esc_html__( 'ویجت جداگانه سیمولاتور را کنار این هیرو بگذارید', 'pixva' ),
					),
					'condition' => array( 'show_simulator' => 'yes' ),
				)
			);

			$this->end_controls_section();

			$this->start_controls_section(
				'pixva_hero_style',
				array(
					'label' => esc_html__( 'پس‌زمینه', 'pixva' ),
					'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
				)
			);

			$this->add_control(
				'hero_background',
				array(
					'label'   => esc_html__( 'رنگ پس‌زمینه', 'pixva' ),
					'type'    => \Elementor\Controls_Manager::COLOR,
					'default' => '#F8FAFC',
					'selectors' => array(
						'{{WRAPPER}} .pixva-hero-widget' => 'background-color: {{VALUE}};',
					),
				)
			);

			$this->add_control(
				'aurora_opacity',
				array(
					'label'   => esc_html__( 'شدت هاله‌های پس‌زمینه (٪)', 'pixva' ),
					'type'    => \Elementor\Controls_Manager::SLIDER,
					'range'   => array(
						'px' => array(
							'min' => 0,
							'max' => 40,
						),
					),
					'default' => array(
						'unit' => 'px',
						'size' => 4,
					),
					'selectors' => array(
						'{{WRAPPER}} .pixva-aurora-blob' => 'opacity: calc({{SIZE}} / 100);',
					),
				)
			);

			$this->add_responsive_control(
				'title_size',
				array(
					'label'      => esc_html__( 'اندازه عنوان', 'pixva' ),
					'type'       => \Elementor\Controls_Manager::SLIDER,
					'size_units' => array( 'rem', 'px' ),
					'range'      => array(
						'rem' => array(
							'min' => 1,
							'max' => 5,
							'step' => 0.05,
						),
					),
					'default'    => array(
						'unit' => 'rem',
						'size' => 2.6,
					),
					'selectors'  => array(
						'{{WRAPPER}} h1' => 'font-size: {{SIZE}}{{UNIT}};',
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
			wp_enqueue_script( 'pixva-calculator' );

			$style = $this->pixva_style_vars( $settings );
			?>
			<section class="pixva-hero pixva-hero--light pixva-hero--simulator pixva-hero-widget" <?php echo $style ? 'style="' . $style . '"' : ''; ?>>
				<div class="pixva-hero__aurora" aria-hidden="true">
					<span class="pixva-aurora-blob pixva-aurora-blob--a"></span>
					<span class="pixva-aurora-blob pixva-aurora-blob--b"></span>
					<span class="pixva-aurora-blob pixva-aurora-blob--c"></span>
				</div>

				<div class="pixva-container pixva-hero__grid">
					<div class="pixva-hero__content">
						<?php if ( ! empty( $settings['badges'] ) ) : ?>
							<div class="pixva-hero__badges">
								<?php foreach ( (array) $settings['badges'] as $badge ) : ?>
									<?php if ( '' === trim( (string) $badge['badge_text'] ) ) { continue; } ?>
									<span class="pixva-badge pixva-badge--<?php echo esc_attr( sanitize_html_class( (string) $badge['badge_style'] ) ); ?>"><?php echo esc_html( (string) $badge['badge_text'] ); ?></span>
								<?php endforeach; ?>
							</div>
						<?php endif; ?>

						<h1><?php echo esc_html( (string) $settings['title'] ); ?></h1>
						<p class="pixva-hero__lead"><?php echo esc_html( (string) $settings['subtitle'] ); ?></p>

						<?php if ( ! empty( $settings['buttons'] ) ) : ?>
							<div class="pixva-hero__actions">
								<?php foreach ( (array) $settings['buttons'] as $button ) : ?>
									<?php
									if ( '' === trim( (string) $button['button_text'] ) ) {
										continue;
									}
									$variant = sanitize_html_class( (string) $button['button_style'] );
									$url     = isset( $button['button_link']['url'] ) ? (string) $button['button_link']['url'] : '#';
									?>
									<a class="pixva-btn pixva-btn--<?php echo esc_attr( $variant ); ?> pixva-btn--shimmer" href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( (string) $button['button_text'] ); ?></a>
								<?php endforeach; ?>
							</div>
						<?php endif; ?>

						<?php if ( 'yes' === $settings['show_stats'] ) : ?>
							<div class="pixva-stats">
								<?php foreach ( pixva_hero_stats() as $stat ) : ?>
									<div class="pixva-stat">
										<strong data-count-to="<?php echo esc_attr( (string) $stat['value'] ); ?>"<?php echo $stat['suffix'] ? ' data-count-suffix="' . esc_attr( $stat['suffix'] ) . '"' : ''; ?>>۰</strong>
										<span><?php echo esc_html( $stat['label'] ); ?></span>
									</div>
								<?php endforeach; ?>
							</div>
						<?php endif; ?>
					</div>

					<?php if ( 'yes' === $settings['show_simulator'] && 'rate_card' === $settings['simulator_source'] ) : ?>
						<div class="pixva-hero__visual">
							<?php
							pixva_render_fault_simulator(
								array(
									'id'          => 'pixva-hero-widget-sim-' . $this->get_id(),
									'variant'     => 'hero',
									'calc_target' => '#quick-calc',
								)
							);
							?>
						</div>
					<?php endif; ?>
				</div>
			</section>
			<?php
		}
	}
}

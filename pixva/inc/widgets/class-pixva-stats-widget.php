<?php
/**
 * ویجت المنتور: آمار و فاکتورهای اعتماد (شمارنده متحرک).
 *
 * منبع داده: ریپیتر دلخواه ادمین یا آمار تنظیمات پوسته (pixva_hero_stats).
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

if ( ! class_exists( 'Pixva_Stats_Widget' ) ) {
	/**
	 * ویجت آمار.
	 */
	class Pixva_Stats_Widget extends Pixva_Section_Widget_Base {

		/**
		 * نام ویجت.
		 *
		 * @return string
		 */
		public function get_name() {
			return 'pixva_stats';
		}

		/**
		 * عنوان.
		 *
		 * @return string
		 */
		public function get_title() {
			return esc_html__( 'آمار و فاکتورهای اعتماد', 'pixva' );
		}

		/**
		 * آیکون.
		 *
		 * @return string
		 */
		public function get_icon() {
			return 'eicon-counter';
		}

		/**
		 * کنترل‌ها.
		 *
		 * @return void
		 */
		protected function register_controls() {
			$this->start_controls_section(
				'pixva_stats_head',
				array(
					'label' => esc_html__( 'سربرگ بخش', 'pixva' ),
					'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
				)
			);

			$this->add_control(
				'kicker',
				array(
					'label'       => esc_html__( 'برچسب بالایی', 'pixva' ),
					'type'        => \Elementor\Controls_Manager::TEXT,
					'default'     => esc_html__( 'کارگاه مرکزی پیکسوا', 'pixva' ),
					'label_block' => true,
				)
			);

			$this->add_control(
				'heading',
				array(
					'label'       => esc_html__( 'عنوان', 'pixva' ),
					'type'        => \Elementor\Controls_Manager::TEXT,
					'default'     => esc_html__( 'نتیجه‌ها را با عدد می‌سنجیم', 'pixva' ),
					'label_block' => true,
					'dynamic'     => array( 'active' => true ),
				)
			);

			$this->add_control(
				'subheading',
				array(
					'label'   => esc_html__( 'توضیح', 'pixva' ),
					'type'    => \Elementor\Controls_Manager::TEXTAREA,
					'rows'    => 2,
					'default' => '',
					'dynamic' => array( 'active' => true ),
				)
			);

			$this->end_controls_section();

			$this->start_controls_section(
				'pixva_stats_items',
				array(
					'label' => esc_html__( 'فاکتورها', 'pixva' ),
					'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
				)
			);

			$this->add_control(
				'stats_source',
				array(
					'label'   => esc_html__( 'منبع داده', 'pixva' ),
					'type'    => \Elementor\Controls_Manager::SELECT,
					'default' => 'theme',
					'options' => array(
						'theme'  => esc_html__( 'آمار تنظیمات پوسته', 'pixva' ),
						'custom' => esc_html__( 'موارد دلخواه (ریپیتر)', 'pixva' ),
					),
				)
			);

			$repeater = new \Elementor\Repeater();

			$repeater->add_control(
				'stat_value',
				array(
					'label'   => esc_html__( 'عدد', 'pixva' ),
					'type'    => \Elementor\Controls_Manager::NUMBER,
					'default' => 100,
				)
			);

			$repeater->add_control(
				'stat_suffix',
				array(
					'label'   => esc_html__( 'پسوند', 'pixva' ),
					'type'    => \Elementor\Controls_Manager::TEXT,
					'default' => '+',
				)
			);

			$repeater->add_control(
				'stat_label',
				array(
					'label'       => esc_html__( 'عنوان', 'pixva' ),
					'type'        => \Elementor\Controls_Manager::TEXT,
					'default'     => esc_html__( 'دستگاه تعمیرشده', 'pixva' ),
					'label_block' => true,
				)
			);

			$this->add_control(
				'stats',
				array(
					'label'       => esc_html__( 'فهرست فاکتورها', 'pixva' ),
					'type'        => \Elementor\Controls_Manager::REPEATER,
					'fields'      => $repeater->get_controls(),
					'default'     => array(),
					'title_field' => '{{{ stat_label }}}',
					'condition'   => array( 'stats_source' => 'custom' ),
				)
			);

			$this->add_responsive_control(
				'columns',
				array(
					'label'     => esc_html__( 'ستون‌ها', 'pixva' ),
					'type'      => \Elementor\Controls_Manager::SLIDER,
					'range'     => array(
						'px' => array(
							'min' => 1,
							'max' => 6,
						),
					),
					'default'   => array(
						'unit' => 'px',
						'size' => 4,
					),
					'selectors' => array(
						'{{WRAPPER}} .pixva-stats' => 'grid-template-columns: repeat({{SIZE}}, minmax(0, 1fr));',
					),
				)
			);

			$this->add_control(
				'animate_counters',
				array(
					'label'        => esc_html__( 'شمارنده متحرک', 'pixva' ),
					'type'         => \Elementor\Controls_Manager::SWITCHER,
					'label_on'     => esc_html__( 'روشن', 'pixva' ),
					'label_off'    => esc_html__( 'خاموش', 'pixva' ),
					'return_value' => 'yes',
					'default'      => 'yes',
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

			$items = array();
			if ( 'custom' === $settings['stats_source'] && ! empty( $settings['stats'] ) ) {
				foreach ( (array) $settings['stats'] as $row ) {
					$items[] = array(
						'value'  => isset( $row['stat_value'] ) ? (int) $row['stat_value'] : 0,
						'suffix' => isset( $row['stat_suffix'] ) ? (string) $row['stat_suffix'] : '',
						'label'  => isset( $row['stat_label'] ) ? (string) $row['stat_label'] : '',
					);
				}
			}
			if ( empty( $items ) ) {
				$items = function_exists( 'pixva_hero_stats' ) ? pixva_hero_stats() : array();
			}

			$animate = 'yes' === $settings['animate_counters'];
			$style   = $this->pixva_style_vars( $settings );
			?>
			<section class="<?php echo esc_attr( $this->pixva_block_class( $settings ) ); ?> pixva-stats-block" <?php echo $style ? 'style="' . $style . '"' : ''; ?>>
				<?php if ( '' !== trim( (string) $settings['kicker'] ) || '' !== trim( (string) $settings['heading'] ) ) : ?>
					<div class="pixva-section-head">
						<?php if ( '' !== trim( (string) $settings['kicker'] ) ) : ?>
							<span class="pixva-badge"><?php echo esc_html( (string) $settings['kicker'] ); ?></span>
						<?php endif; ?>
						<?php if ( '' !== trim( (string) $settings['heading'] ) ) : ?>
							<h2><?php echo esc_html( (string) $settings['heading'] ); ?></h2>
						<?php endif; ?>
						<?php if ( '' !== trim( (string) $settings['subheading'] ) ) : ?>
							<p><?php echo esc_html( (string) $settings['subheading'] ); ?></p>
						<?php endif; ?>
					</div>
				<?php endif; ?>

				<div class="pixva-stats">
					<?php foreach ( $items as $item ) : ?>
						<div class="pixva-stat">
							<strong <?php echo $animate ? 'data-count-to="' . esc_attr( (string) $item['value'] ) . '"' : ''; ?><?php echo $animate && '' !== $item['suffix'] ? ' data-count-suffix="' . esc_attr( $item['suffix'] ) . '"' : ''; ?>><?php echo esc_html( $animate ? '۰' : pixva_fa_num( (string) $item['value'] . $item['suffix'] ) ); ?></strong>
							<span><?php echo esc_html( $item['label'] ); ?></span>
						</div>
					<?php endforeach; ?>
				</div>
			</section>
			<?php
		}
	}
}

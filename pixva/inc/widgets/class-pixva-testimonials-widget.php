<?php
/**
 * ویجت المنتور: نظرات مشتریان.
 *
 * منبع داده: نوع محتوای pixva_review (پیشخوان وردپرس) یا ریپیتر دلخواه.
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

if ( ! class_exists( 'Pixva_Testimonials_Widget' ) ) {
	/**
	 * ویجت نظرات مشتریان.
	 */
	class Pixva_Testimonials_Widget extends Pixva_Section_Widget_Base {

		/**
		 * نام ویجت.
		 *
		 * @return string
		 */
		public function get_name() {
			return 'pixva_testimonials';
		}

		/**
		 * عنوان.
		 *
		 * @return string
		 */
		public function get_title() {
			return esc_html__( 'نظرات مشتریان', 'pixva' );
		}

		/**
		 * آیکون.
		 *
		 * @return string
		 */
		public function get_icon() {
			return 'eicon-review';
		}

		/**
		 * کنترل‌ها.
		 *
		 * @return void
		 */
		protected function register_controls() {
			$this->start_controls_section(
				'pixva_tmn_content',
				array(
					'label' => esc_html__( 'محتوا', 'pixva' ),
					'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
				)
			);

			$this->add_control(
				'kicker',
				array(
					'label'       => esc_html__( 'برچسب بالایی', 'pixva' ),
					'type'        => \Elementor\Controls_Manager::TEXT,
					'default'     => esc_html__( 'از زبان مشتری', 'pixva' ),
					'label_block' => true,
				)
			);

			$this->add_control(
				'heading',
				array(
					'label'       => esc_html__( 'عنوان', 'pixva' ),
					'type'        => \Elementor\Controls_Manager::TEXT,
					'default'     => esc_html__( 'تعمیر را با نتیجه می‌سنجیم', 'pixva' ),
					'label_block' => true,
					'dynamic'     => array( 'active' => true ),
				)
			);

			$this->add_control(
				'source',
				array(
					'label'   => esc_html__( 'منبع نظرات', 'pixva' ),
					'type'    => \Elementor\Controls_Manager::SELECT,
					'default' => 'cms',
					'options' => array(
						'cms'      => esc_html__( 'نوع محتوای «نظرات مشتریان»', 'pixva' ),
						'featured' => esc_html__( 'فقط نظرات نشان‌خورده صفحه اصلی', 'pixva' ),
						'custom'   => esc_html__( 'موارد دلخواه (ریپیتر)', 'pixva' ),
					),
				)
			);

			$this->add_control(
				'count',
				array(
					'label'   => esc_html__( 'تعداد', 'pixva' ),
					'type'    => \Elementor\Controls_Manager::NUMBER,
					'min'     => 1,
					'max'     => 24,
					'default' => 3,
				)
			);

			$repeater = new \Elementor\Repeater();

			$repeater->add_control(
				'quote',
				array(
					'label'   => esc_html__( 'متن نظر', 'pixva' ),
					'type'    => \Elementor\Controls_Manager::TEXTAREA,
					'rows'    => 4,
					'default' => esc_html__( 'توضیح مشتری درباره نتیجه تعمیر.', 'pixva' ),
				)
			);

			$repeater->add_control(
				'name',
				array(
					'label'       => esc_html__( 'نام مشتری', 'pixva' ),
					'type'        => \Elementor\Controls_Manager::TEXT,
					'default'     => esc_html__( 'نام و نام خانوادگی', 'pixva' ),
					'label_block' => true,
				)
			);

			$repeater->add_control(
				'role',
				array(
					'label'       => esc_html__( 'خدمت / شهر', 'pixva' ),
					'type'        => \Elementor\Controls_Manager::TEXT,
					'default'     => esc_html__( 'تعویض بک‌لایت، تهران', 'pixva' ),
					'label_block' => true,
				)
			);

			$repeater->add_control(
				'rating',
				array(
					'label'   => esc_html__( 'امتیاز', 'pixva' ),
					'type'    => \Elementor\Controls_Manager::NUMBER,
					'min'     => 1,
					'max'     => 5,
					'default' => 5,
				)
			);

			$repeater->add_control(
				'avatar',
				array(
					'label'   => esc_html__( 'تصویر مشتری', 'pixva' ),
					'type'    => \Elementor\Controls_Manager::MEDIA,
					'default' => array( 'url' => '' ),
				)
			);

			$this->add_control(
				'reviews',
				array(
					'label'       => esc_html__( 'نظرهای دلخواه', 'pixva' ),
					'type'        => \Elementor\Controls_Manager::REPEATER,
					'fields'      => $repeater->get_controls(),
					'default'     => array(),
					'title_field' => '{{{ name }}}',
					'condition'   => array( 'source' => 'custom' ),
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
							'max' => 4,
						),
					),
					'default'   => array(
						'unit' => 'px',
						'size' => 3,
					),
					'selectors' => array(
						'{{WRAPPER}} .pixva-tmn__grid' => 'grid-template-columns: repeat({{SIZE}}, minmax(0, 1fr));',
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

			$count = max( 1, (int) $settings['count'] );
			$items = array();

			if ( 'custom' === $settings['source'] && ! empty( $settings['reviews'] ) ) {
				foreach ( (array) $settings['reviews'] as $row ) {
					$items[] = array(
						'quote'  => isset( $row['quote'] ) ? (string) $row['quote'] : '',
						'name'   => isset( $row['name'] ) ? (string) $row['name'] : '',
						'role'   => isset( $row['role'] ) ? (string) $row['role'] : '',
						'rating' => isset( $row['rating'] ) ? (int) $row['rating'] : 5,
						'avatar' => pixva_simulator_media_url( isset( $row['avatar'] ) ? $row['avatar'] : '' ),
					);
				}
				$items = array_slice( $items, 0, $count );
			} else {
				$items = pixva_testimonials(
					array(
						'count'         => $count,
						'featured_only' => 'featured' === $settings['source'],
					)
				);
			}

			if ( empty( $items ) ) {
				return;
			}

			$style = $this->pixva_style_vars( $settings );
			?>
			<section class="<?php echo esc_attr( $this->pixva_block_class( $settings ) ); ?> pixva-tmn" <?php echo $style ? 'style="' . $style . '"' : ''; ?>>
				<?php if ( '' !== trim( (string) $settings['kicker'] ) || '' !== trim( (string) $settings['heading'] ) ) : ?>
					<div class="pixva-section-head">
						<?php if ( '' !== trim( (string) $settings['kicker'] ) ) : ?>
							<span class="pixva-badge"><?php echo esc_html( (string) $settings['kicker'] ); ?></span>
						<?php endif; ?>
						<?php if ( '' !== trim( (string) $settings['heading'] ) ) : ?>
							<h2><?php echo esc_html( (string) $settings['heading'] ); ?></h2>
						<?php endif; ?>
					</div>
				<?php endif; ?>

				<div class="pixva-tmn__grid">
					<?php foreach ( $items as $item ) : ?>
						<?php
						$rating = isset( $item['rating'] ) ? max( 1, min( 5, (int) $item['rating'] ) ) : 5;
						$stars  = str_repeat( '★', $rating ) . str_repeat( '☆', 5 - $rating );
						?>
						<blockquote class="pixva-card pixva-quote pixva-tmn__item pixva-reveal">
							<div class="pixva-stars" role="img" aria-label="<?php echo esc_attr( sprintf( __( '%d از ۵', 'pixva' ), $rating ) ); ?>"><?php echo esc_html( $stars ); ?></div>
							<p><?php echo esc_html( (string) $item['quote'] ); ?></p>
							<footer>
								<?php if ( ! empty( $item['avatar'] ) ) : ?>
									<img class="pixva-tmn__avatar" src="<?php echo esc_url( (string) $item['avatar'] ); ?>" alt="<?php echo esc_attr( (string) $item['name'] ); ?>" loading="lazy" decoding="async">
								<?php endif; ?>
								<span>
									<strong><?php echo esc_html( (string) $item['name'] ); ?></strong>
									<?php if ( ! empty( $item['role'] ) ) : ?>
										<small><?php echo esc_html( (string) $item['role'] ); ?></small>
									<?php endif; ?>
								</span>
							</footer>
						</blockquote>
					<?php endforeach; ?>
				</div>
			</section>
			<?php
		}
	}
}

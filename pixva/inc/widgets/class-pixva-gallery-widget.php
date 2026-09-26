<?php
/**
 * ویجت المنتور: گالری نمونه‌کارهای تعمیر (قبل و بعد).
 *
 * منبع داده: نوع محتوای repair_cases یا ریپیتر تصویری دلخواه.
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

if ( ! class_exists( 'Pixva_Gallery_Widget' ) ) {
	/**
	 * ویجت گالری نمونه‌کار.
	 */
	class Pixva_Gallery_Widget extends Pixva_Section_Widget_Base {

		/**
		 * نام ویجت.
		 *
		 * @return string
		 */
		public function get_name() {
			return 'pixva_gallery';
		}

		/**
		 * عنوان.
		 *
		 * @return string
		 */
		public function get_title() {
			return esc_html__( 'گالری نمونه‌کار تعمیر', 'pixva' );
		}

		/**
		 * آیکون.
		 *
		 * @return string
		 */
		public function get_icon() {
			return 'eicon-gallery-grid';
		}

		/**
		 * وابستگی اسکریپت اسلایدر قبل/بعد.
		 *
		 * @return array<int, string>
		 */
		public function get_script_depends() {
			return array( 'pixva-main', 'pixva-tools', 'pixva-before-after' );
		}

		/**
		 * کنترل‌ها.
		 *
		 * @return void
		 */
		protected function register_controls() {
			$this->start_controls_section(
				'pixva_gallery_content',
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
					'default'     => esc_html__( 'نمونه‌کار واقعی', 'pixva' ),
					'label_block' => true,
				)
			);

			$this->add_control(
				'heading',
				array(
					'label'       => esc_html__( 'عنوان', 'pixva' ),
					'type'        => \Elementor\Controls_Manager::TEXT,
					'default'     => esc_html__( 'قبل و بعد تعمیر در کارگاه', 'pixva' ),
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

			$this->add_control(
				'source',
				array(
					'label'   => esc_html__( 'منبع', 'pixva' ),
					'type'    => \Elementor\Controls_Manager::SELECT,
					'default' => 'cases',
					'options' => array(
						'cases'  => esc_html__( 'نوع محتوای نمونه‌کارها', 'pixva' ),
						'custom' => esc_html__( 'تصاویر دلخواه (ریپیتر)', 'pixva' ),
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

			$this->add_control(
				'orderby',
				array(
					'label'   => esc_html__( 'ترتیب', 'pixva' ),
					'type'    => \Elementor\Controls_Manager::SELECT,
					'default' => 'date',
					'options' => array(
						'date'       => esc_html__( 'تاریخ انتشار', 'pixva' ),
						'title'      => esc_html__( 'عنوان', 'pixva' ),
						'menu_order' => esc_html__( 'ترتیب دستی', 'pixva' ),
					),
				)
			);

			$this->add_control(
				'problem_filter',
				array(
					'label'   => esc_html__( 'فیلتر نوع خرابی', 'pixva' ),
					'type'    => \Elementor\Controls_Manager::SELECT,
					'default' => '',
					'options' => $this->pixva_term_options( 'tv_problem' ),
				)
			);

			$repeater = new \Elementor\Repeater();

			$repeater->add_control(
				'before_image',
				array(
					'label'   => esc_html__( 'تصویر قبل', 'pixva' ),
					'type'    => \Elementor\Controls_Manager::MEDIA,
					'default' => array( 'url' => '' ),
				)
			);

			$repeater->add_control(
				'after_image',
				array(
					'label'   => esc_html__( 'تصویر بعد', 'pixva' ),
					'type'    => \Elementor\Controls_Manager::MEDIA,
					'default' => array( 'url' => '' ),
				)
			);

			$repeater->add_control(
				'caption',
				array(
					'label'       => esc_html__( 'عنوان مورد', 'pixva' ),
					'type'        => \Elementor\Controls_Manager::TEXT,
					'default'     => esc_html__( 'تعویض بک‌لایت ۵۵ اینچ', 'pixva' ),
					'label_block' => true,
				)
			);

			$repeater->add_control(
				'meta',
				array(
					'label'       => esc_html__( 'توضیح کوتاه', 'pixva' ),
					'type'        => \Elementor\Controls_Manager::TEXT,
					'default'     => '',
					'label_block' => true,
				)
			);

			$this->add_control(
				'images',
				array(
					'label'       => esc_html__( 'تصاویر دلخواه', 'pixva' ),
					'type'        => \Elementor\Controls_Manager::REPEATER,
					'fields'      => $repeater->get_controls(),
					'default'     => array(),
					'title_field' => '{{{ caption }}}',
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
						'{{WRAPPER}} .pixva-gallery__grid' => 'grid-template-columns: repeat({{SIZE}}, minmax(0, 1fr));',
					),
				)
			);

			$this->end_controls_section();

			$this->pixva_card_style_section();
		}

		/**
		 * گزینه‌های یک تاکسونومی برای کنترل SELECT.
		 *
		 * @param string $taxonomy نام تاکسونومی.
		 * @return array<string, string>
		 */
		protected function pixva_term_options( $taxonomy ) {
			$options = array( '' => esc_html__( 'همه موارد', 'pixva' ) );
			if ( ! taxonomy_exists( $taxonomy ) ) {
				return $options;
			}

			$terms = get_terms(
				array(
					'taxonomy'   => $taxonomy,
					'hide_empty' => false,
					'number'     => 100,
				)
			);

			if ( is_array( $terms ) ) {
				foreach ( $terms as $term ) {
					$options[ (string) $term->slug ] = (string) $term->name;
				}
			}

			return $options;
		}

		/**
		 * خروجی.
		 *
		 * @return void
		 */
		protected function render() {
			$settings = $this->get_settings_for_display();
			$this->enqueue_front_assets();
			wp_enqueue_script( 'pixva-before-after' );

			$count = max( 1, (int) $settings['count'] );
			$cards = array();

			if ( 'custom' === $settings['source'] && ! empty( $settings['images'] ) ) {
				foreach ( (array) $settings['images'] as $row ) {
					$before = pixva_simulator_media_url( isset( $row['before_image'] ) ? $row['before_image'] : '' );
					$after  = pixva_simulator_media_url( isset( $row['after_image'] ) ? $row['after_image'] : '' );
					$cards[] = array(
						'before' => $before,
						'after'  => $after,
						'title'  => isset( $row['caption'] ) ? (string) $row['caption'] : '',
						'meta'   => isset( $row['meta'] ) ? (string) $row['meta'] : '',
						'url'    => '',
					);
				}
				$cards = array_slice( $cards, 0, $count );
			} elseif ( post_type_exists( 'repair_cases' ) ) {
				$query_args = array(
					'post_type'      => 'repair_cases',
					'post_status'    => 'publish',
					'posts_per_page' => $count,
					'orderby'        => sanitize_key( (string) $settings['orderby'] ),
					'order'          => 'menu_order' === $settings['orderby'] ? 'ASC' : 'DESC',
					'no_found_rows'  => true,
				);

				if ( ! empty( $settings['problem_filter'] ) && taxonomy_exists( 'tv_problem' ) ) {
					$query_args['tax_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
						array(
							'taxonomy' => 'tv_problem',
							'field'    => 'slug',
							'terms'    => sanitize_title( (string) $settings['problem_filter'] ),
						),
					);
				}

				$query = new WP_Query( apply_filters( 'pixva_gallery_query', $query_args ) );

				foreach ( $query->posts as $case ) {
					$images  = pixva_case_images( $case->ID );
					$parts   = (string) get_post_meta( $case->ID, '_pixva_case_parts', true );
					$duration = (string) get_post_meta( $case->ID, '_pixva_case_duration', true );
					$meta    = trim( implode( ' · ', array_filter( array( $parts, $duration ) ) ) );

					$cards[] = array(
						'before' => $images['before'],
						'after'  => $images['after'],
						'title'  => get_the_title( $case ),
						'meta'   => $meta,
						'url'    => get_permalink( $case ),
					);
				}
			}

			if ( empty( $cards ) ) {
				return;
			}

			$style = $this->pixva_style_vars( $settings );
			?>
			<section class="<?php echo esc_attr( $this->pixva_block_class( $settings ) ); ?> pixva-gallery" <?php echo $style ? 'style="' . $style . '"' : ''; ?>>
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

				<div class="pixva-gallery__grid">
					<?php foreach ( $cards as $card ) : ?>
						<article class="pixva-card pixva-gallery__item pixva-reveal">
							<?php if ( $card['before'] && $card['after'] ) : ?>
								<?php pixva_render_before_after( $card['before'], $card['after'], $card['title'] ); ?>
							<?php elseif ( $card['after'] ) : ?>
								<img src="<?php echo esc_url( $card['after'] ); ?>" alt="<?php echo esc_attr( $card['title'] ); ?>" loading="lazy" decoding="async">
							<?php endif; ?>

							<div class="pixva-gallery__body">
								<?php if ( $card['url'] ) : ?>
									<h3><a href="<?php echo esc_url( $card['url'] ); ?>"><?php echo esc_html( $card['title'] ); ?></a></h3>
								<?php else : ?>
									<h3><?php echo esc_html( $card['title'] ); ?></h3>
								<?php endif; ?>
								<?php if ( '' !== $card['meta'] ) : ?>
									<p><?php echo esc_html( $card['meta'] ); ?></p>
								<?php endif; ?>
							</div>
						</article>
					<?php endforeach; ?>
				</div>
			</section>
			<?php
		}
	}
}

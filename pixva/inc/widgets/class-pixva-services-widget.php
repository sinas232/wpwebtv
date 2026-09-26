<?php
/**
 * ویجت المنتور: خدمات تعمیرات.
 *
 * منبع داده: نوع محتوای tv_services (با برآورد قیمت زنده از نرخ‌نامه) یا
 * خدمات پیش‌فرض پوسته.
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

if ( ! class_exists( 'Pixva_Services_Widget' ) ) {
	/**
	 * ویجت خدمات.
	 */
	class Pixva_Services_Widget extends Pixva_Section_Widget_Base {

		/**
		 * نام ویجت.
		 *
		 * @return string
		 */
		public function get_name() {
			return 'pixva_services';
		}

		/**
		 * عنوان.
		 *
		 * @return string
		 */
		public function get_title() {
			return esc_html__( 'خدمات تعمیرات', 'pixva' );
		}

		/**
		 * آیکون.
		 *
		 * @return string
		 */
		public function get_icon() {
			return 'eicon-settings';
		}

		/**
		 * کنترل‌ها.
		 *
		 * @return void
		 */
		protected function register_controls() {
			$this->start_controls_section(
				'pixva_services_content',
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
					'default'     => esc_html__( 'خدمات تخصصی', 'pixva' ),
					'label_block' => true,
				)
			);

			$this->add_control(
				'heading',
				array(
					'label'       => esc_html__( 'عنوان', 'pixva' ),
					'type'        => \Elementor\Controls_Manager::TEXT,
					'default'     => esc_html__( 'تعمیر همان‌جایی که خرابی است', 'pixva' ),
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
					'default' => esc_html__( 'پنل را بی‌دلیل تعویض نمی‌کنیم؛ اول مسیر ارزان‌تر و قابل ضمانت بررسی می‌شود.', 'pixva' ),
					'dynamic' => array( 'active' => true ),
				)
			);

			$this->add_control(
				'count',
				array(
					'label'   => esc_html__( 'تعداد خدمت', 'pixva' ),
					'type'    => \Elementor\Controls_Manager::NUMBER,
					'min'     => 1,
					'max'     => 24,
					'default' => 4,
				)
			);

			$this->add_control(
				'show_price',
				array(
					'label'        => esc_html__( 'نمایش برآورد قیمت زنده', 'pixva' ),
					'type'         => \Elementor\Controls_Manager::SWITCHER,
					'label_on'     => esc_html__( 'نمایش', 'pixva' ),
					'label_off'    => esc_html__( 'پنهان', 'pixva' ),
					'return_value' => 'yes',
					'default'      => 'yes',
				)
			);

			$this->add_control(
				'price_brand',
				array(
					'label'     => esc_html__( 'برند نمونه برای برآورد', 'pixva' ),
					'type'      => \Elementor\Controls_Manager::SELECT,
					'default'   => 'samsung',
					'options'   => $this->pixva_catalog_options( 'brand' ),
					'condition' => array( 'show_price' => 'yes' ),
				)
			);

			$this->add_control(
				'price_size',
				array(
					'label'     => esc_html__( 'سایز نمونه برای برآورد', 'pixva' ),
					'type'      => \Elementor\Controls_Manager::SELECT,
					'default'   => '55',
					'options'   => $this->pixva_catalog_options( 'size' ),
					'condition' => array( 'show_price' => 'yes' ),
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
						'size' => 4,
					),
					'selectors' => array(
						'{{WRAPPER}} .pixva-services__grid' => 'grid-template-columns: repeat({{SIZE}}, minmax(0, 1fr));',
					),
				)
			);

			$this->end_controls_section();

			$this->pixva_card_style_section();
		}

		/**
		 * گزینه‌های کاتالوگ برند/سایز/تکنولوژی.
		 *
		 * @param string $group نام گروه.
		 * @return array<string, string>
		 */
		protected function pixva_catalog_options( $group ) {
			$labels = function_exists( 'pixva_calculator_labels' ) ? pixva_calculator_labels() : array();
			return isset( $labels[ $group ] ) ? $labels[ $group ] : array();
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
			$brand = sanitize_key( (string) $settings['price_brand'] );
			$size  = sanitize_key( (string) $settings['price_size'] );
			$posts = array();

			if ( post_type_exists( 'tv_services' ) ) {
				$posts = get_posts(
					array(
						'post_type'      => 'tv_services',
						'post_status'    => 'publish',
						'posts_per_page' => $count,
						'orderby'        => 'menu_order title',
						'order'          => 'ASC',
						'no_found_rows'  => true,
					)
				);
			}

			$style = $this->pixva_style_vars( $settings );
			?>
			<section class="<?php echo esc_attr( $this->pixva_block_class( $settings ) ); ?> pixva-services" <?php echo $style ? 'style="' . $style . '"' : ''; ?>>
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

				<div class="pixva-services__grid">
					<?php if ( ! empty( $posts ) ) : ?>
						<?php foreach ( $posts as $service ) : ?>
							<?php
							$problem  = sanitize_title( (string) get_post_field( 'post_name', $service ) );
							$estimate = 'yes' === $settings['show_price'] ? pixva_calculate_estimate( $brand, 'led', $size, $problem ) : null;
							?>
							<a class="pixva-card pixva-service-card pixva-reveal" href="<?php echo esc_url( get_permalink( $service ) ); ?>">
								<?php
								if ( has_post_thumbnail( $service ) ) {
									echo get_the_post_thumbnail( $service, 'pixva-card' );
								} else {
									echo pixva_icon( 'tool' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
								}
								?>
								<h3><?php echo esc_html( get_the_title( $service ) ); ?></h3>
								<p><?php echo esc_html( get_the_excerpt( $service ) ); ?></p>
								<?php if ( is_array( $estimate ) && empty( $estimate['panel_replacement'] ) ) : ?>
									<span class="pixva-service-card__price">
										<?php echo esc_html( sprintf( /* translators: 1: min price, 2: max price. */ __( '%1$s تا %2$s تومان', 'pixva' ), pixva_price( $estimate['min'] ), pixva_price( $estimate['max'] ) ) ); ?>
									</span>
								<?php endif; ?>
								<span class="pixva-service-card__cta"><?php esc_html_e( 'برآورد هزینه این خدمت', 'pixva' ); ?></span>
							</a>
						<?php endforeach; ?>
					<?php else : ?>
						<?php foreach ( array_slice( pixva_service_fallbacks(), 0, $count ) as $service ) : ?>
							<a class="pixva-card pixva-service-card pixva-reveal" href="<?php echo esc_url( add_query_arg( 'problem', $service['key'], pixva_page_url( 'calculator' ) ) ); ?>">
								<?php echo pixva_icon( $service['icon'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								<h3><?php echo esc_html( $service['title'] ); ?></h3>
								<p><?php echo esc_html( $service['text'] ); ?></p>
								<span class="pixva-service-card__cta"><?php esc_html_e( 'برآورد هزینه این خدمت', 'pixva' ); ?></span>
							</a>
						<?php endforeach; ?>
					<?php endif; ?>
				</div>
			</section>
			<?php
		}
	}
}

<?php
/**
 * ویجت المنتور: باکس مشاوره سریع و تماس.
 *
 * همه داده‌ها از تنظیمات پوسته (شماره‌ها، واتساپ، نشانی، ساعات) خوانده می‌شود.
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

if ( ! class_exists( 'Pixva_Consult_Widget' ) ) {
	/**
	 * ویجت مشاوره سریع.
	 */
	class Pixva_Consult_Widget extends Pixva_Section_Widget_Base {

		/**
		 * نام ویجت.
		 *
		 * @return string
		 */
		public function get_name() {
			return 'pixva_consult';
		}

		/**
		 * عنوان.
		 *
		 * @return string
		 */
		public function get_title() {
			return esc_html__( 'مشاوره سریع و تماس', 'pixva' );
		}

		/**
		 * آیکون.
		 *
		 * @return string
		 */
		public function get_icon() {
			return 'eicon-headphones';
		}

		/**
		 * کنترل‌ها.
		 *
		 * @return void
		 */
		protected function register_controls() {
			$this->start_controls_section(
				'pixva_consult_content',
				array(
					'label' => esc_html__( 'محتوا', 'pixva' ),
					'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
				)
			);

			$this->add_control(
				'heading',
				array(
					'label'       => esc_html__( 'عنوان', 'pixva' ),
					'type'        => \Elementor\Controls_Manager::TEXT,
					'default'     => esc_html__( 'علامت خرابی را بگویید، کارشناس همان بخش پاسخ می‌دهد', 'pixva' ),
					'label_block' => true,
					'dynamic'     => array( 'active' => true ),
				)
			);

			$this->add_control(
				'subheading',
				array(
					'label'   => esc_html__( 'توضیح', 'pixva' ),
					'type'    => \Elementor\Controls_Manager::TEXTAREA,
					'rows'    => 3,
					'default' => esc_html__( 'پیش از هر اقدامی، برآورد هزینه و زمان را اعلام می‌کنیم و پس از تأیید شما تعمیر شروع می‌شود.', 'pixva' ),
					'dynamic' => array( 'active' => true ),
				)
			);

			$this->add_control(
				'show_phone',
				array(
					'label'        => esc_html__( 'دکمه تماس', 'pixva' ),
					'type'         => \Elementor\Controls_Manager::SWITCHER,
					'return_value' => 'yes',
					'default'      => 'yes',
				)
			);

			$this->add_control(
				'show_whatsapp',
				array(
					'label'        => esc_html__( 'دکمه واتساپ', 'pixva' ),
					'type'         => \Elementor\Controls_Manager::SWITCHER,
					'return_value' => 'yes',
					'default'      => 'yes',
				)
			);

			$this->add_control(
				'calculator_link',
				array(
					'label'        => esc_html__( 'دکمه محاسبه‌گر', 'pixva' ),
					'type'         => \Elementor\Controls_Manager::SWITCHER,
					'return_value' => 'yes',
					'default'      => 'yes',
				)
			);

			$this->add_control(
				'show_address',
				array(
					'label'        => esc_html__( 'نشانی و ساعات کارگاه', 'pixva' ),
					'type'         => \Elementor\Controls_Manager::SWITCHER,
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

			$phone   = function_exists( 'pixva_support_phone' ) ? pixva_support_phone() : '';
			$control = function_exists( 'pixva_control_options' ) ? pixva_control_options() : array();
			$address = isset( $control['hub_address'] ) ? $control['hub_address'] : (string) pixva_option( 'pixva_workshop_address', '' );
			$hours   = isset( $control['hub_hours'] ) ? $control['hub_hours'] : (string) pixva_option( 'pixva_workshop_hours', '' );
			$style   = $this->pixva_style_vars( $settings );
			?>
			<section class="<?php echo esc_attr( $this->pixva_block_class( $settings ) ); ?> pixva-consult" <?php echo $style ? 'style="' . $style . '"' : ''; ?>>
				<div class="pixva-consult__body">
					<?php if ( '' !== trim( (string) $settings['heading'] ) ) : ?>
						<h2><?php echo esc_html( (string) $settings['heading'] ); ?></h2>
					<?php endif; ?>
					<?php if ( '' !== trim( (string) $settings['subheading'] ) ) : ?>
						<p><?php echo esc_html( (string) $settings['subheading'] ); ?></p>
					<?php endif; ?>

					<div class="pixva-consult__actions">
						<?php if ( 'yes' === $settings['show_phone'] && '' !== $phone ) : ?>
							<a class="pixva-btn pixva-btn--gradient" href="<?php echo esc_url( pixva_tel_href( $phone ) ); ?>">
								<?php echo pixva_icon( 'phone' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								<span><?php echo esc_html( pixva_fa_num( $phone ) ); ?></span>
							</a>
						<?php endif; ?>

						<?php if ( 'yes' === $settings['show_whatsapp'] ) : ?>
							<a class="pixva-btn pixva-btn--ghost-dark" href="<?php echo esc_url( pixva_whatsapp_url( (string) pixva_option( 'pixva_consult_whatsapp_text', __( 'سلام، برای تعمیر تلویزیون مشاوره می‌خواهم.', 'pixva' ) ) ) ); ?>" target="_blank" rel="noopener noreferrer">
								<?php echo pixva_icon( 'whatsapp' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								<span><?php esc_html_e( 'واتساپ کارگاه', 'pixva' ); ?></span>
							</a>
						<?php endif; ?>

						<?php if ( 'yes' === $settings['calculator_link'] ) : ?>
							<a class="pixva-btn pixva-btn--ghost-dark" href="<?php echo esc_url( pixva_page_url( 'calculator' ) ); ?>">
								<?php echo pixva_icon( 'calculator' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								<span><?php esc_html_e( 'محاسبه‌گر هزینه', 'pixva' ); ?></span>
							</a>
						<?php endif; ?>
					</div>

					<?php if ( 'yes' === $settings['show_address'] && ( '' !== $address || '' !== $hours ) ) : ?>
						<ul class="pixva-consult__meta">
							<?php if ( '' !== $address ) : ?>
								<li>
									<?php echo pixva_icon( 'pin' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
									<span><?php echo esc_html( $address ); ?></span>
								</li>
							<?php endif; ?>
							<?php if ( '' !== $hours ) : ?>
								<li>
									<?php echo pixva_icon( 'clock' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
									<span><?php echo esc_html( $hours ); ?></span>
								</li>
							<?php endif; ?>
						</ul>
					<?php endif; ?>
				</div>
			</section>
			<?php
		}
	}
}

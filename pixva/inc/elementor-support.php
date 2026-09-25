<?php
/**
 * ادغام بومی با المنتور (inc/elementor-support.php) — Master Specification v25.0
 *
 * - دسته‌بندی رسمی ویجت‌ها: Pixva Diagnostics
 * - ۶۰ ویجت بومی المنتور (یک ویجت به ازای هر ابزار) با کنترل‌های کامل محتوایی و سبک
 * - ویجت هاب (نمایه ۵ هاب تخصصی) و ویجت جامع «۶۰ در ۱»
 * - شورت‌کد جایگزین: [pixva_tool id="1"] تا [pixva_tool id="60"]
 *
 * @package Pixva
 * @since   1.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * افزودن دسته‌بندی اختصاصی «Pixva Diagnostics» به المنتور.
 *
 * @param \Elementor\Elements_Manager $elements_manager مدیر المان‌های المنتور.
 * @return void
 */
function pixva_add_elementor_widget_categories( $elements_manager ) {
	$elements_manager->add_category(
		'pixva-diagnostics',
		array(
			'title' => esc_html__( 'Pixva Diagnostics', 'pixva' ),
			'icon'  => 'eicon-device-tv',
		)
	);
}
add_action( 'elementor/elements/categories_registered', 'pixva_add_elementor_widget_categories' );

if ( ! class_exists( 'Pixva_Tool_Widget_Base' ) ) {
	/**
	 * کلاس پایه ویجت‌های ابزار پیکسوا در المنتور.
	 */
	abstract class Pixva_Tool_Widget_Base extends \Elementor\Widget_Base {

		/**
		 * شناسه ابزار (۱ تا ۶۰).
		 *
		 * @return int
		 */
		abstract public function tool_id();

		/**
		 * رکورد رجیستری ابزار.
		 *
		 * @return array
		 */
		protected function tool_record() {
			$tool = pixva_tool_get( $this->tool_id() );
			return is_array( $tool ) ? $tool : array(
				'title'    => __( 'ابزار پیکسوا', 'pixva' ),
				'summary'  => '',
				'group'    => '',
				'renderer' => '',
			);
		}

		/**
		 * نام ویجت.
		 *
		 * @return string
		 */
		public function get_name() {
			return 'pixva_tool_' . str_pad( (string) $this->tool_id(), 2, '0', STR_PAD_LEFT );
		}

		/**
		 * عنوان ویجت در پنل المنتور.
		 *
		 * @return string
		 */
		public function get_title() {
			$tool = $this->tool_record();
			return sprintf( '%s. %s', $this->tool_id(), $tool['title'] );
		}

		/**
		 * آیکون ویجت.
		 *
		 * @return string
		 */
		public function get_icon() {
			$map = array(
				'ai'         => 'eicon-ai-text',
				'camera'     => 'eicon-camera',
				'mic'        => 'eicon-microphone',
				'layers'     => 'eicon-layers',
				'panel'      => 'eicon-device-tv',
				'sun'        => 'eicon-sun',
				'bolt'       => 'eicon-flash',
				'sound'      => 'eicon-speaker',
				'calculator' => 'eicon-calculator',
				'chart'      => 'eicon-graph',
				'box'        => 'eicon-product-images',
				'route'      => 'eicon-route',
				'cert'       => 'eicon-certificate',
				'shield'     => 'eicon-shield',
				'search'     => 'eicon-search',
				'calendar'   => 'eicon-calendar',
				'user'       => 'eicon-user',
				'pin'        => 'eicon-google-maps',
				'book'       => 'eicon-notebook',
				'star'       => 'eicon-rating',
				'menu'       => 'eicon-nav-menu',
				'clock'      => 'eicon-time-line',
				'plug'       => 'eicon-settings',
				'cpu'        => 'eicon-device-desktop',
				'truck'      => 'eicon-cart',
				'tool'       => 'eicon-tools',
				'doc'        => 'eicon-document-file',
			);
			$tool = $this->tool_record();
			$icon = isset( $tool['icon'] ) ? $tool['icon'] : 'tool';
			return isset( $map[ $icon ] ) ? $map[ $icon ] : 'eicon-tools';
		}

		/**
		 * دسته‌بندی رسمی ویجت.
		 *
		 * @return array
		 */
		public function get_categories() {
			return array( 'pixva-diagnostics' );
		}

		/**
		 * کلیدواژه‌های جست‌وجو.
		 *
		 * @return array
		 */
		public function get_keywords() {
			$tool = $this->tool_record();
			return array( 'pixva', 'tv', 'repair', 'diagnostics', 'television', 'ابزار پیکسوا', $tool['en'] );
		}

		/**
		 * کنترل‌های محتوایی.
		 *
		 * @return void
		 */
		protected function register_controls() {
			$tool = $this->tool_record();

			$this->start_controls_section(
				'pixva_content',
				array(
					'label' => esc_html__( 'محتوای ابزار', 'pixva' ),
					'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
				)
			);

			$this->add_control(
				'layout',
				array(
					'label'   => esc_html__( 'حالت نمایش', 'pixva' ),
					'type'    => \Elementor\Controls_Manager::SELECT,
					'default' => 'section',
					'options' => array(
						'section' => esc_html__( 'سکشن کامل (با سربرگ مرکزی)', 'pixva' ),
						'inline'  => esc_html__( 'فشرده / درون‌خطی (کارت تنها)', 'pixva' ),
					),
				)
			);

			$this->add_control(
				'show_heading',
				array(
					'label'        => esc_html__( 'نمایش عنوان و توضیح ابزار', 'pixva' ),
					'type'         => \Elementor\Controls_Manager::SWITCHER,
					'label_on'     => esc_html__( 'بله', 'pixva' ),
					'label_off'    => esc_html__( 'خیر', 'pixva' ),
					'return_value' => 'yes',
					'default'      => 'yes',
				)
			);

			$this->add_control(
				'custom_title',
				array(
					'label'       => esc_html__( 'عنوان دلخواه', 'pixva' ),
					'type'        => \Elementor\Controls_Manager::TEXT,
					'default'     => $tool['title'],
					'label_block' => true,
					'condition'   => array( 'show_heading' => 'yes' ),
				)
			);

			$this->add_control(
				'custom_summary',
				array(
					'label'   => esc_html__( 'توضیح دلخواه', 'pixva' ),
					'type'    => \Elementor\Controls_Manager::TEXTAREA,
					'default' => $tool['summary'],
					'rows'    => 3,
					'condition' => array( 'show_heading' => 'yes' ),
				)
			);

			$this->end_controls_section();

			$this->start_controls_section(
				'pixva_style',
				array(
					'label' => esc_html__( 'سبک کارت ابزار', 'pixva' ),
					'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
				)
			);

			$this->add_control(
				'accent_color',
				array(
					'label'   => esc_html__( 'رنگ تأکید (آیکن و نشان‌ها)', 'pixva' ),
					'type'    => \Elementor\Controls_Manager::COLOR,
					'default' => '#176B87',
				)
			);

			$this->add_control(
				'heading_color',
				array(
					'label'   => esc_html__( 'رنگ سرتیترها', 'pixva' ),
					'type'    => \Elementor\Controls_Manager::COLOR,
					'default' => '#123B4A',
				)
			);

			$this->add_control(
				'card_background',
				array(
					'label'   => esc_html__( 'پس‌زمینه کارت', 'pixva' ),
					'type'    => \Elementor\Controls_Manager::COLOR,
					'default' => '#FFFFFF',
				)
			);

			$this->add_responsive_control(
				'card_padding',
				array(
					'label'      => esc_html__( 'فضای داخلی کارت', 'pixva' ),
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
						'size' => 24,
					),
					'selectors'  => array(
						'{{WRAPPER}} .pixva-tool-shell' => 'padding: {{SIZE}}{{UNIT}};',
					),
				)
			);

			$this->add_responsive_control(
				'card_radius',
				array(
					'label'      => esc_html__( 'گردی گوشه‌ها', 'pixva' ),
					'type'       => \Elementor\Controls_Manager::SLIDER,
					'size_units' => array( 'px' ),
					'range'      => array(
						'px' => array(
							'min' => 0,
							'max' => 48,
						),
					),
					'default'    => array(
						'unit' => 'px',
						'size' => 22,
					),
					'selectors'  => array(
						'{{WRAPPER}} .pixva-tool-shell' => 'border-radius: {{SIZE}}{{UNIT}};',
					),
				)
			);

			$this->end_controls_section();
		}

		/**
		 * خروجی ویجت.
		 *
		 * @return void
		 */
		protected function render() {
			$settings = $this->get_settings_for_display();
			$layout   = isset( $settings['layout'] ) && 'inline' === $settings['layout'] ? 'inline' : 'section';
			$title    = isset( $settings['custom_title'] ) ? trim( (string) $settings['custom_title'] ) : '';
			$summary  = isset( $settings['custom_summary'] ) ? trim( (string) $settings['custom_summary'] ) : '';
			$show     = ! isset( $settings['show_heading'] ) || 'yes' === $settings['show_heading'];
			$id       = (int) $this->tool_id();

			// رنگ‌های دلخواه پنل به‌صورت متغیر CSS روی پوسته ابزار اعمال می‌شود.
			$inline_style = '';
			if ( ! empty( $settings['accent_color'] ) ) {
				$inline_style .= '--pixva-tool-accent:' . $settings['accent_color'] . ';';
			}
			if ( ! empty( $settings['heading_color'] ) ) {
				$inline_style .= '--pixva-tool-heading:' . $settings['heading_color'] . ';';
			}
			if ( ! empty( $settings['card_background'] ) ) {
				$inline_style .= '--pixva-tool-card:' . $settings['card_background'] . ';';
			}

			echo '<div class="pixva-elementor-tool" style="' . esc_attr( $inline_style ) . '">';

			if ( $show && ( '' !== $title || '' !== $summary ) ) {
				$filter_key = 'pixva_tool_override_' . $id;
				add_filter(
					$filter_key,
					static function () use ( $title, $summary ) {
						return array(
							'title'   => $title,
							'summary' => $summary,
						);
					}
				);
			}

			echo pixva_render_tool_by_id( $id, $layout ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

			echo '</div>';
		}
	}
}

if ( ! class_exists( 'Pixva_Hub_Index_Widget' ) ) {
	/**
	 * ویجت نمایه یکی از ۵ هاب تخصصی.
	 */
	class Pixva_Hub_Index_Widget extends \Elementor\Widget_Base {

		/**
		 * نام ویجت.
		 *
		 * @return string
		 */
		public function get_name() {
			return 'pixva_hub_index';
		}

		/**
		 * عنوان ویجت.
		 *
		 * @return string
		 */
		public function get_title() {
			return esc_html__( 'نمایه هاب تخصصی پیکسوا', 'pixva' );
		}

		/**
		 * آیکون.
		 *
		 * @return string
		 */
		public function get_icon() {
			return 'eicon-apps';
		}

		/**
		 * دسته‌بندی.
		 *
		 * @return array
		 */
		public function get_categories() {
			return array( 'pixva-diagnostics' );
		}

		/**
		 * کلیدواژه‌ها.
		 *
		 * @return array
		 */
		public function get_keywords() {
			return array( 'pixva', 'hub', 'tools', 'هاب', 'ابزارها' );
		}

		/**
		 * کنترل‌ها.
		 *
		 * @return void
		 */
		protected function register_controls() {
			$this->start_controls_section(
				'pixva_hub_section',
				array(
					'label' => esc_html__( 'هاب تخصصی', 'pixva' ),
					'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
				)
			);

			$options = array();
			foreach ( pixva_hubs() as $slug => $hub ) {
				$options[ $slug ] = sprintf( '%s. %s', $hub['no'], $hub['title'] );
			}

			$this->add_control(
				'hub',
				array(
					'label'   => esc_html__( 'انتخاب هاب', 'pixva' ),
					'type'    => \Elementor\Controls_Manager::SELECT,
					'default' => 'ai-diagnostics',
					'options' => $options,
				)
			);

			$this->add_control(
				'heading',
				array(
					'label'       => esc_html__( 'عنوان بخش', 'pixva' ),
					'type'        => \Elementor\Controls_Manager::TEXT,
					'default'     => '',
					'label_block' => true,
				)
			);

			$this->end_controls_section();
		}

		/**
		 * خروجی.
		 *
		 * @return void
		 */
		protected function render() {
			$settings = $this->get_settings_for_display();
			$hub      = isset( $settings['hub'] ) ? (string) $settings['hub'] : 'ai-diagnostics';
			$heading  = isset( $settings['heading'] ) ? trim( (string) $settings['heading'] ) : '';
			pixva_render_hub_index( $hub, $heading );
		}
	}
}

/**
 * ثبت ویجت‌های المنتور (۶۰ ابزار + نمایه هاب + ویجت جامع).
 *
 * @param \Elementor\Widgets_Manager $widgets_manager مدیر ویجت‌ها.
 * @return void
 */
function pixva_register_elementor_widgets( $widgets_manager ) {
	if ( ! class_exists( '\Elementor\Widget_Base' ) ) {
		return;
	}

	require_once PIXVA_DIR . '/inc/elementor-widgets.php';

	$classes = pixva_elementor_widget_classes();
	foreach ( $classes as $class ) {
		if ( class_exists( $class ) ) {
			$widgets_manager->register( new $class() );
		}
	}

	$widgets_manager->register( new Pixva_Hub_Index_Widget() );

	if ( class_exists( 'Pixva_All_Tools_Widget' ) ) {
		$widgets_manager->register( new Pixva_All_Tools_Widget() );
	}
}
add_action( 'elementor/widgets/register', 'pixva_register_elementor_widgets' );

/**
 * سازگاری با نسخه‌های قدیمی‌تر المنتور (قبل از API ثبت ویجت).
 *
 * @return void
 */
function pixva_register_elementor_widgets_legacy() {
	if ( ! did_action( 'elementor/loaded' ) || ! class_exists( '\Elementor\Plugin' ) ) {
		return;
	}
	if ( method_exists( \Elementor\Plugin::$instance->widgets_manager, 'register' ) ) {
		return;
	}
	require_once PIXVA_DIR . '/inc/elementor-widgets.php';
	foreach ( pixva_elementor_widget_classes() as $class ) {
		if ( class_exists( $class ) ) {
			\Elementor\Plugin::$instance->widgets_manager->register_widget_type( new $class() );
		}
	}
	if ( class_exists( 'Pixva_Hub_Index_Widget' ) ) {
		\Elementor\Plugin::$instance->widgets_manager->register_widget_type( new Pixva_Hub_Index_Widget() );
	}
}
add_action( 'elementor/widgets/widgets_registered', 'pixva_register_elementor_widgets_legacy' );

/**
 * بارگذاری سبک‌های ویرایشگر المنتور برای ویجت‌های پیکسوا.
 *
 * @return void
 */
function pixva_elementor_editor_assets() {
	if ( ! did_action( 'elementor/loaded' ) ) {
		return;
	}
	wp_enqueue_style( 'pixva-2026', PIXVA_URI . '/assets/css/pixva-2026.css', array(), PIXVA_VERSION );
}
add_action( 'elementor/editor/before_enqueue_scripts', 'pixva_elementor_editor_assets' );

/**
 * شورت‌کد جایگزین برای فراخوانی هر یک از ۶۰ ابزار.
 *
 * نمونه: [pixva_tool id="5"] یا [pixva_tool id="12" layout="inline"]
 *
 * @param array $atts مشخصه‌های شورت‌کد.
 * @return string
 */
function pixva_tool_shortcode( $atts ) {
	$atts = shortcode_atts(
		array(
			'id'     => 1,
			'layout' => 'section',
		),
		$atts,
		'pixva_tool'
	);

	$id = (int) $atts['id'];
	if ( $id < 1 || $id > 60 ) {
		return '';
	}

	return pixva_render_tool_by_id( $id, 'inline' === $atts['layout'] ? 'inline' : 'section' );
}
add_shortcode( 'pixva_tool', 'pixva_tool_shortcode' );

/**
 * شورت‌کد نمایه یک هاب: [pixva_hub slug="ai-diagnostics"]
 *
 * @param array $atts مشخصه‌ها.
 * @return string
 */
function pixva_hub_shortcode( $atts ) {
	$atts = shortcode_atts(
		array(
			'slug'  => 'ai-diagnostics',
			'title' => '',
		),
		$atts,
		'pixva_hub'
	);

	$slug = sanitize_key( $atts['slug'] );
	if ( ! isset( pixva_hubs()[ $slug ] ) ) {
		return '';
	}

	ob_start();
	pixva_render_hub_index( $slug, (string) $atts['title'] );
	return (string) ob_get_clean();
}
add_shortcode( 'pixva_hub', 'pixva_hub_shortcode' );

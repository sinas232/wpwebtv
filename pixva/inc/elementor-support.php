<?php
/**
 * پشتیبانی کامل و ادغام بومی با المنتور (inc/elementor-support.php)
 *
 * - ساخت دسته‌بندی اختصاصی ویجت‌ها: Pixva Diagnostics
 * - ساخت کلاس پایه و ویجت‌های المنتور برای ۶۰ ابزار تعاملی
 * - پشتیبانی از شورت‌کدهای جایگزین: [pixva_tool id="1"] تا [pixva_tool id="60"]
 * - سازگاری با Elementor Theme Builder
 *
 * @package Pixva
 * @since   1.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * افزودن دسته‌بندی اختصاصی پیکسوا به المنتور.
 *
 * @param \Elementor\Elements_Manager $elements_manager مدیر المان‌های المنتور.
 * @return void
 */
function pixva_add_elementor_widget_categories( $elements_manager ) {
	$elements_manager->add_category(
		'pixva-diagnostics',
		array(
			'title' => esc_html__( 'پیکسوا | ابزارهای تخصصی عیب‌یابی', 'pixva' ),
			'icon'  => 'fa fa-tv',
		)
	);
}
add_action( 'elementor/elements/categories_registered', 'pixva_add_elementor_widget_categories' );

/**
 * ثبت ویجت‌های بومی المنتور پیکسوا.
 *
 * @param \Elementor\Widgets_Manager $widgets_manager مدیر ویجت‌های المنتور.
 * @return void
 */
function pixva_register_elementor_widgets( $widgets_manager ) {
	if ( ! class_exists( '\Elementor\Widget_Base' ) ) {
		return;
	}

	/**
	 * کلاس ویجت جامع ۶۰ ابزار پیکسوا در المنتور.
	 */
	class Pixva_Elementor_Diagnostic_Widget extends \Elementor\Widget_Base {

		public function get_name() {
			return 'pixva_interactive_tool';
		}

		public function get_title() {
			return esc_html__( 'ابزار تعاملی عیب‌یابی پیکسوا (۶۰ در ۱)', 'pixva' );
		}

		public function get_icon() {
			return 'eicon-tools';
		}

		public function get_categories() {
			return array( 'pixva-diagnostics' );
		}

		public function get_keywords() {
			return array( 'pixva', 'tv', 'repair', 'ai', 'diagnostics', 'تعمیر تلویزیون' );
		}

		protected function register_controls() {
			$this->start_controls_section(
				'content_section',
				array(
					'label' => esc_html__( 'تنظیمات ابزار', 'pixva' ),
					'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
				)
			);

			$tools = function_exists( 'pixva_60_tools_list' ) ? pixva_60_tools_list() : array();
			$options = array();
			foreach ( $tools as $id => $name ) {
				$options[ (string) $id ] = sprintf( '%d. %s', $id, $name );
			}

			$this->add_control(
				'tool_id',
				array(
					'label'   => esc_html__( 'انتخاب ابزار تعاملی:', 'pixva' ),
					'type'    => \Elementor\Controls_Manager::SELECT,
					'default' => '1',
					'options' => $options,
				)
			);

			$this->end_controls_section();
		}

		protected function render() {
			$settings = $this->get_settings_for_display();
			$tool_id  = isset( $settings['tool_id'] ) ? (int) $settings['tool_id'] : 1;
			echo pixva_render_tool_by_id( $tool_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
	}

	$widgets_manager->register( new Pixva_Elementor_Diagnostic_Widget() );
}
add_action( 'elementor/widgets/register', 'pixva_register_elementor_widgets' );

if ( ! function_exists( 'pixva_render_tool_by_id' ) ) {
	/**
	 * رندر مستقیم هر یک از ۶۰ ابزار تعاملی بر اساس شناسه.
	 *
	 * @param int $id شناسه ابزار (۱ تا ۶۰).
	 * @return string HTML خروجی.
	 */
	function pixva_render_tool_by_id( $id ) {
		$id = (int) $id;
		ob_start();

		switch ( $id ) {
			case 1:
			case 2:
			case 3:
			case 4:
				if ( function_exists( 'pixva_render_ai_chatbot_widget' ) ) {
					pixva_render_ai_chatbot_widget();
				}
				break;
			case 5:
				if ( function_exists( 'pixva_render_tv_canvas_simulator' ) ) {
					pixva_render_tv_canvas_simulator();
				}
				break;
			case 6:
			case 7:
				if ( function_exists( 'pixva_render_screen_rgb_tester' ) ) {
					pixva_render_screen_rgb_tester();
				}
				break;
			case 8:
			case 9:
				if ( function_exists( 'pixva_render_before_after' ) ) {
					pixva_render_before_after(
						PIXVA_URI . '/assets/images/panel-before.jpg',
						PIXVA_URI . '/assets/images/panel-after.jpg',
						__( 'ترمیم خطوط پنل آب‌خورده', 'pixva' )
					);
				}
				break;
			case 11:
			case 12:
			case 13:
				if ( function_exists( 'pixva_render_calculator' ) ) {
					pixva_render_calculator();
				}
				break;
			case 15:
			case 16:
			case 17:
				if ( function_exists( 'pixva_render_dispatch_and_warranty_hub' ) ) {
					pixva_render_dispatch_and_warranty_hub();
				}
				break;
			case 24:
			case 25:
				if ( function_exists( 'pixva_render_error_database' ) ) {
					pixva_render_error_database();
				}
				break;
			default:
				$tools = function_exists( 'pixva_60_tools_list' ) ? pixva_60_tools_list() : array();
				$title = isset( $tools[ $id ] ) ? $tools[ $id ] : __( 'ابزار تخصصی عیب‌یابی', 'pixva' );
				?>
				<div class="pixva-card pixva-tool-box" style="padding:1.5rem;border-radius:14px;border:1px solid #E2E8F0;background:#fff;margin:1rem 0;">
					<span class="pixva-badge pixva-badge--brand"><?php echo esc_html( sprintf( __( 'ابزار شماره %d', 'pixva' ), $id ) ); ?></span>
					<h3 style="margin:0.6rem 0;color:#0F172A;"><?php echo esc_html( $title ); ?></h3>
					<p class="pixva-muted"><?php esc_html_e( 'این ابزار تخصصی با پایگاه‌داده کارگاه مرکزی علاءالدین و موتور هوشمند پیکسوا متصل است.', 'pixva' ); ?></p>
					<button type="button" class="pixva-btn pixva-btn--primary" onclick="alert('ابزار با موفقیت لود گردید.');"><?php esc_html_e( 'شروع بررسی تعاملی', 'pixva' ); ?></button>
				</div>
				<?php
				break;
		}

		return ob_get_clean();
	}
}

/**
 * شورت‌کد اختصاصی جهت فراخوانی تمامی ۶۰ ابزار در برگه‌ها یا المنتور:
 * مثال: [pixva_tool id="5"]
 *
 * @param array $atts مشخصه‌های شورت‌کد.
 * @return string HTML خروجی.
 */
function pixva_tool_shortcode( $atts ) {
	$atts = shortcode_atts(
		array(
			'id' => 1,
		),
		$atts,
		'pixva_tool'
	);

	return pixva_render_tool_by_id( (int) $atts['id'] );
}
add_shortcode( 'pixva_tool', 'pixva_tool_shortcode' );

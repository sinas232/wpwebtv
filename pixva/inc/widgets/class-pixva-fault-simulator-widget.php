<?php
/**
 * ویجت المنتور: سیمولاتور تعاملی عیب‌یابی تلویزیون.
 *
 * ادمین می‌تواند تیتر، زیرتیتر، قاب مانیتور و بی‌نهایت «ایراد» را با Repeater
 * تعریف کند (عنوان دکمه، آیکون، تصویر/گیف داخل مانیتور، علت، هزینه، زمان و لینک
 * CTA). هزینه و زمان خالی از نرخ‌نامه سمت سرور پر می‌شود تا هیچ قیمتی دستی
 * وارد کد نشود.
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

if ( ! class_exists( 'Pixva_Fault_Simulator_Widget' ) ) {
	/**
	 * ویجت سیمولاتور عیب‌یابی.
	 */
	class Pixva_Fault_Simulator_Widget extends Pixva_Section_Widget_Base {

		/**
		 * نام ویجت.
		 *
		 * @return string
		 */
		public function get_name() {
			return 'pixva_fault_simulator';
		}

		/**
		 * عنوان در پنل.
		 *
		 * @return string
		 */
		public function get_title() {
			return esc_html__( 'سیمولاتور تعاملی عیب‌یابی', 'pixva' );
		}

		/**
		 * آیکون.
		 *
		 * @return string
		 */
		public function get_icon() {
			return 'eicon-device-tv';
		}

		/**
		 * کنترل‌های ویجت.
		 *
		 * @return void
		 */
		protected function register_controls() {
			$this->content_controls();
			$this->fault_repeater_controls();
			$this->monitor_controls();
			$this->action_controls();
			$this->responsive_controls();
			$this->pixva_card_style_section();
		}

		/**
		 * بخش تنظیمات کلی.
		 *
		 * @return void
		 */
		protected function content_controls() {
			$this->start_controls_section(
				'pixva_sim_content',
				array(
					'label' => esc_html__( 'تنظیمات کلی', 'pixva' ),
					'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
				)
			);

			$this->add_control(
				'badge_text',
				array(
					'label'       => esc_html__( 'متن بج زنده', 'pixva' ),
					'type'        => \Elementor\Controls_Manager::TEXT,
					'default'     => esc_html__( 'سیمولاتور زنده تشخیص عیب', 'pixva' ),
					'label_block' => true,
					'description' => esc_html__( 'خالی بگذارید تا بج نمایش داده نشود.', 'pixva' ),
				)
			);

			$this->add_control(
				'sim_title',
				array(
					'label'       => esc_html__( 'تیتر اصلی', 'pixva' ),
					'type'        => \Elementor\Controls_Manager::TEXT,
					'default'     => '',
					'label_block' => true,
					'dynamic'     => array( 'active' => true ),
				)
			);

			$this->add_control(
				'sim_subtitle',
				array(
					'label'    => esc_html__( 'زیرتیتر', 'pixva' ),
					'type'     => \Elementor\Controls_Manager::TEXTAREA,
					'default'  => '',
					'rows'     => 3,
					'dynamic'  => array( 'active' => true ),
				)
			);

			$this->add_control(
				'items_source',
				array(
					'label'   => esc_html__( 'منبع ایرادها', 'pixva' ),
					'type'    => \Elementor\Controls_Manager::SELECT,
					'default' => 'rate_card',
					'options' => array(
						'rate_card' => esc_html__( 'سه ایراد پرکاربرد از نرخ‌نامه', 'pixva' ),
						'repeater'  => esc_html__( 'فقط موارد دلخواه (ریپیتر)', 'pixva' ),
						'merge'     => esc_html__( 'نرخ‌نامه + موارد دلخواه', 'pixva' ),
					),
				)
			);

			$this->add_control(
				'pricing_note',
				array(
					'type'  => \Elementor\Controls_Manager::RAW_HTML,
					'raw'   => esc_html__( 'نمونه قیمت زیر برای پرکردن خودکار «هزینه» و «زمان» خالی از موتور نرخ‌نامه استفاده می‌شود.', 'pixva' ),
				)
			);

			$brands = array();
			if ( function_exists( 'pixva_brand_catalog' ) ) {
				foreach ( pixva_brand_catalog() as $key => $brand ) {
					$brands[ $key ] = $brand['fa'];
				}
			}
			$techs  = function_exists( 'pixva_tech_catalog' ) ? pixva_tech_catalog() : array();
			$sizes  = function_exists( 'pixva_size_catalog' ) ? pixva_size_catalog() : array();

			$this->add_control(
				'sample_brand',
				array(
					'label'   => esc_html__( 'برند نمونه', 'pixva' ),
					'type'    => \Elementor\Controls_Manager::SELECT,
					'default' => 'samsung',
					'options' => $brands,
				)
			);

			$this->add_control(
				'sample_tech',
				array(
					'label'   => esc_html__( 'تکنولوژی نمونه', 'pixva' ),
					'type'    => \Elementor\Controls_Manager::SELECT,
					'default' => 'led',
					'options' => $techs,
				)
			);

			$this->add_control(
				'sample_size',
				array(
					'label'   => esc_html__( 'سایز نمونه', 'pixva' ),
					'type'    => \Elementor\Controls_Manager::SELECT,
					'default' => '55',
					'options' => $sizes,
				)
			);

			$this->end_controls_section();
		}

		/**
		 * ریپیتر ایرادها (بی‌نهایت مورد).
		 *
		 * @return void
		 */
		protected function fault_repeater_controls() {
			$repeater = new \Elementor\Repeater();

			$repeater->add_control(
				'fault_label',
				array(
					'label'       => esc_html__( 'عنوان دکمه', 'pixva' ),
					'type'        => \Elementor\Controls_Manager::TEXT,
					'default'     => esc_html__( 'تصویر سیاه است', 'pixva' ),
					'label_block' => true,
				)
			);

			$repeater->add_control(
				'fault_tag',
				array(
					'label'       => esc_html__( 'برچسب کوتاه (بخش معیوب)', 'pixva' ),
					'type'        => \Elementor\Controls_Manager::TEXT,
					'default'     => esc_html__( 'بک‌لایت', 'pixva' ),
					'label_block' => true,
				)
			);

			$repeater->add_control(
				'fault_problem',
				array(
					'label'       => esc_html__( 'کلید نرخ‌نامه (برای هزینه و زمان خودکار)', 'pixva' ),
					'type'        => \Elementor\Controls_Manager::SELECT,
					'default'     => 'no_picture',
					'options'     => $this->pixva_problem_options(),
					'label_block' => true,
				)
			);

			$repeater->add_control(
				'fault_icon',
				array(
					'label'   => esc_html__( 'آیکون دکمه', 'pixva' ),
					'type'    => \Elementor\Controls_Manager::MEDIA,
					'default' => array( 'url' => '' ),
				)
			);

			$repeater->add_control(
				'fault_emoji',
				array(
					'label'       => esc_html__( 'آیکون متنی (در نبود تصویر)', 'pixva' ),
					'type'        => \Elementor\Controls_Manager::TEXT,
					'default'     => '🔴',
					'label_block' => false,
				)
			);

			$repeater->add_control(
				'fault_media',
				array(
					'label'       => esc_html__( 'تصویر/گیف داخل مانیتور', 'pixva' ),
					'type'        => \Elementor\Controls_Manager::MEDIA,
					'default'     => array( 'url' => '' ),
					'description' => esc_html__( 'خالی بگذارید تا انیمیشن CSS همان عیب پخش شود.', 'pixva' ),
				)
			);

			$repeater->add_control(
				'fault_cause',
				array(
					'label'   => esc_html__( 'علت احتمالی', 'pixva' ),
					'type'    => \Elementor\Controls_Manager::TEXTAREA,
					'rows'    => 3,
					'default' => esc_html__( 'سوختن ریسه‌های LED بک‌لایت یا درایور بک‌لایت', 'pixva' ),
				)
			);

			$repeater->add_control(
				'fault_cost',
				array(
					'label'       => esc_html__( 'حدود هزینه', 'pixva' ),
					'type'        => \Elementor\Controls_Manager::TEXT,
					'default'     => '',
					'label_block' => true,
					'description' => esc_html__( 'خالی = محاسبه خودکار از نرخ‌نامه.', 'pixva' ),
				)
			);

			$repeater->add_control(
				'fault_time',
				array(
					'label'       => esc_html__( 'زمان تعمیر', 'pixva' ),
					'type'        => \Elementor\Controls_Manager::TEXT,
					'default'     => '',
					'label_block' => true,
					'description' => esc_html__( 'خالی = زمان اعلامی نرخ‌نامه.', 'pixva' ),
				)
			);

			$repeater->add_control(
				'fault_cta',
				array(
					'label'       => esc_html__( 'لینک دکمه CTA این ایراد', 'pixva' ),
					'type'        => \Elementor\Controls_Manager::URL,
					'placeholder' => esc_html__( 'https://', 'pixva' ),
					'default'     => array( 'url' => '' ),
					'label_block' => true,
					'description' => esc_html__( 'خالی = پیش‌تنظیم محاسبه‌گر همان صفحه با این ایراد.', 'pixva' ),
				)
			);

			$repeater->add_control(
				'fault_accent',
				array(
					'label'   => esc_html__( 'رنگ تأکیدی این ایراد', 'pixva' ),
					'type'    => \Elementor\Controls_Manager::COLOR,
					'default' => '',
				)
			);

			$this->start_controls_section(
				'pixva_sim_faults',
				array(
					'label' => esc_html__( 'ایرادهای تلویزیون (Repeater)', 'pixva' ),
					'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
				)
			);

			$this->add_control(
				'faults',
				array(
					'label'       => esc_html__( 'فهرست ایرادها', 'pixva' ),
					'type'        => \Elementor\Controls_Manager::REPEATER,
					'fields'      => $repeater->get_controls(),
					'default'     => array(),
					'title_field' => '{{{ fault_label }}}',
				)
			);

			$this->add_control(
				'active_index',
				array(
					'label'   => esc_html__( 'ایراد فعال پیش‌فرض (از ۱)', 'pixva' ),
					'type'    => \Elementor\Controls_Manager::NUMBER,
					'min'     => 1,
					'max'     => 40,
					'step'    => 1,
					'default' => 1,
				)
			);

			$this->end_controls_section();
		}

		/**
		 * ظاهر مانیتور و نورپردازی محیطی.
		 *
		 * @return void
		 */
		protected function monitor_controls() {
			$this->start_controls_section(
				'pixva_sim_monitor',
				array(
					'label' => esc_html__( 'مانیتور و نورپردازی', 'pixva' ),
					'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
				)
			);

			$this->add_control(
				'frame_image',
				array(
					'label'       => esc_html__( 'تصویر فریم تلویزیون (اختیاری)', 'pixva' ),
					'type'        => \Elementor\Controls_Manager::MEDIA,
					'default'     => array( 'url' => '' ),
					'description' => esc_html__( 'اگر تصویر بگذارید، قاب CSS با تصویر شما جایگزین می‌شود.', 'pixva' ),
				)
			);

			$this->add_control(
				'frame_color',
				array(
					'label'   => esc_html__( 'رنگ فریم', 'pixva' ),
					'type'    => \Elementor\Controls_Manager::COLOR,
					'default' => '#020617',
				)
			);

			$this->add_control(
				'frame_border',
				array(
					'label'   => esc_html__( 'رنگ حاشیه فریم', 'pixva' ),
					'type'    => \Elementor\Controls_Manager::COLOR,
					'default' => '#1E293B',
				)
			);

			$this->add_control(
				'frame_radius',
				array(
					'label'   => esc_html__( 'گردی فریم (پیکسل)', 'pixva' ),
					'type'    => \Elementor\Controls_Manager::SLIDER,
					'range'   => array(
						'px' => array(
							'min' => 0,
							'max' => 48,
						),
					),
					'default' => array(
						'unit' => 'px',
						'size' => 20,
					),
				)
			);

			$this->add_control(
				'glow_color',
				array(
					'label'   => esc_html__( 'رنگ هاله محیطی (Ambient Glow)', 'pixva' ),
					'type'    => \Elementor\Controls_Manager::COLOR,
					'default' => '#3B82F6',
				)
			);

			$this->add_control(
				'glow_strength',
				array(
					'label'   => esc_html__( 'شدت هاله (٪)', 'pixva' ),
					'type'    => \Elementor\Controls_Manager::SLIDER,
					'range'   => array(
						'px' => array(
							'min' => 0,
							'max' => 100,
						),
					),
					'default' => array(
						'unit' => 'px',
						'size' => 45,
					),
				)
			);

			$this->add_control(
				'show_scan',
				array(
					'label'        => esc_html__( 'خط اسکن روی صفحه', 'pixva' ),
					'type'         => \Elementor\Controls_Manager::SWITCHER,
					'label_on'     => esc_html__( 'نمایش', 'pixva' ),
					'label_off'    => esc_html__( 'پنهان', 'pixva' ),
					'return_value' => 'yes',
					'default'      => 'yes',
				)
			);

			$this->add_control(
				'show_stand',
				array(
					'label'        => esc_html__( 'پایه مانیتور', 'pixva' ),
					'type'         => \Elementor\Controls_Manager::SWITCHER,
					'label_on'     => esc_html__( 'نمایش', 'pixva' ),
					'label_off'    => esc_html__( 'پنهان', 'pixva' ),
					'return_value' => 'yes',
					'default'      => 'yes',
				)
			);

			$this->end_controls_section();
		}

		/**
		 * دکمه‌های اقدام.
		 *
		 * @return void
		 */
		protected function action_controls() {
			$this->start_controls_section(
				'pixva_sim_actions',
				array(
					'label' => esc_html__( 'اقدام و تبدیل', 'pixva' ),
					'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
				)
			);

			$this->add_control(
				'cta_text',
				array(
					'label'       => esc_html__( 'متن دکمه اصلی', 'pixva' ),
					'type'        => \Elementor\Controls_Manager::TEXT,
					'default'     => esc_html__( 'ثبت درخواست تعمیر این ایراد', 'pixva' ),
					'label_block' => true,
				)
			);

			$this->add_control(
				'cta_url',
				array(
					'label'       => esc_html__( 'لینک پیش‌فرض دکمه اصلی', 'pixva' ),
					'type'        => \Elementor\Controls_Manager::URL,
					'placeholder' => esc_html__( 'https://', 'pixva' ),
					'default'     => array(
						'url' => function_exists( 'pixva_page_url' ) ? pixva_page_url( 'calculator' ) : '',
					),
					'label_block' => true,
				)
			);

			$this->add_control(
				'calc_target',
				array(
					'label'       => esc_html__( 'سلکتور محاسبه‌گر همان صفحه', 'pixva' ),
					'type'        => \Elementor\Controls_Manager::TEXT,
					'default'     => '#quick-calc',
					'label_block' => true,
					'description' => esc_html__( 'اگر محاسبه‌گر در صفحه باشد، دکمه اصلی آن را با ایراد انتخابی پیش‌تنظیم می‌کند.', 'pixva' ),
				)
			);

			$this->add_control(
				'whatsapp_enabled',
				array(
					'label'        => esc_html__( 'دکمه واتساپ', 'pixva' ),
					'type'         => \Elementor\Controls_Manager::SWITCHER,
					'label_on'     => esc_html__( 'نمایش', 'pixva' ),
					'label_off'    => esc_html__( 'پنهان', 'pixva' ),
					'return_value' => 'yes',
					'default'      => 'yes',
				)
			);

			$this->add_control(
				'whatsapp_text',
				array(
					'label'       => esc_html__( 'متن دکمه واتساپ', 'pixva' ),
					'type'        => \Elementor\Controls_Manager::TEXT,
					'default'     => esc_html__( 'مشاوره فوری در واتساپ', 'pixva' ),
					'label_block' => true,
				)
			);

			$this->add_control(
				'sim_note',
				array(
					'label'   => esc_html__( 'یادداشت زیر دکمه‌ها', 'pixva' ),
					'type'    => \Elementor\Controls_Manager::TEXTAREA,
					'rows'    => 2,
					'default' => esc_html__( 'قیمت نهایی پس از عیب‌یابی رایگان تأیید شما می‌رسد.', 'pixva' ),
				)
			);

			$this->end_controls_section();
		}

		/**
		 * رفتار ریسپانسیو.
		 *
		 * @return void
		 */
		protected function responsive_controls() {
			$this->start_controls_section(
				'pixva_sim_responsive',
				array(
					'label' => esc_html__( 'چیدمان و موبایل', 'pixva' ),
					'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
				)
			);

			$this->add_responsive_control(
				'buttons_columns',
				array(
					'label'   => esc_html__( 'تعداد دکمه‌ها در هر ردیف', 'pixva' ),
					'type'    => \Elementor\Controls_Manager::SLIDER,
					'range'   => array(
						'px' => array(
							'min' => 1,
							'max' => 6,
						),
					),
					'default' => array(
						'unit' => 'px',
						'size' => 3,
					),
					'selectors' => array(
						'{{WRAPPER}} .pixva-sim__symptoms' => 'grid-template-columns: repeat({{SIZE}}, minmax(0, 1fr));',
					),
				)
			);

			$this->add_control(
				'mobile_swipe',
				array(
					'label'        => esc_html__( 'اسلایدر لمسی در موبایل', 'pixva' ),
					'type'         => \Elementor\Controls_Manager::SWITCHER,
					'label_on'     => esc_html__( 'فعال', 'pixva' ),
					'label_off'    => esc_html__( 'غیرفعال', 'pixva' ),
					'return_value' => 'yes',
					'default'      => 'yes',
				)
			);

			$this->add_control(
				'swipe_dots',
				array(
					'label'        => esc_html__( 'نمایش نقطه‌های اسلایدر', 'pixva' ),
					'type'         => \Elementor\Controls_Manager::SWITCHER,
					'label_on'     => esc_html__( 'نمایش', 'pixva' ),
					'label_off'    => esc_html__( 'پنهان', 'pixva' ),
					'return_value' => 'yes',
					'default'      => 'yes',
					'condition'    => array( 'mobile_swipe' => 'yes' ),
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
			$this->enqueue_front_assets();

			$pricing = array(
				'brand' => sanitize_key( (string) $settings['sample_brand'] ),
				'tech'  => sanitize_key( (string) $settings['sample_tech'] ),
				'size'  => sanitize_key( (string) $settings['sample_size'] ),
			);

			$source = isset( $settings['items_source'] ) ? (string) $settings['items_source'] : 'rate_card';
			$items  = array();

			if ( 'repeater' !== $source ) {
				$items = pixva_fault_simulator_items( $pricing );
			}

			if ( 'rate_card' !== $source && ! empty( $settings['faults'] ) ) {
				foreach ( (array) $settings['faults'] as $row ) {
					$label = isset( $row['fault_label'] ) ? trim( (string) $row['fault_label'] ) : '';
					if ( '' === $label ) {
						continue;
					}

					$problem = isset( $row['fault_problem'] ) ? sanitize_key( (string) $row['fault_problem'] ) : '';
					$items[] = array(
						'key'     => '' !== $problem ? $problem : 'custom_' . count( $items ),
						'problem' => $problem,
						'label'   => $label,
						'tag'     => isset( $row['fault_tag'] ) ? (string) $row['fault_tag'] : '',
						'icon'    => isset( $row['fault_icon'] ) ? $row['fault_icon'] : '',
						'emoji'   => isset( $row['fault_emoji'] ) ? (string) $row['fault_emoji'] : '',
						'media'   => isset( $row['fault_media'] ) ? $row['fault_media'] : '',
						'cause'   => isset( $row['fault_cause'] ) ? (string) $row['fault_cause'] : '',
						'cost'    => isset( $row['fault_cost'] ) ? (string) $row['fault_cost'] : '',
						'days'    => isset( $row['fault_time'] ) ? (string) $row['fault_time'] : '',
						'cta_url' => isset( $row['fault_cta']['url'] ) ? (string) $row['fault_cta']['url'] : '',
						'accent'  => isset( $row['fault_accent'] ) ? (string) $row['fault_accent'] : '',
					);
				}
			}

			$active = max( 1, (int) $settings['active_index'] ) - 1;
			if ( $active > 0 && $active < count( $items ) ) {
				$items = array_merge( array_slice( $items, $active ), array_slice( $items, 0, $active ) );
			}

			$cta_url = isset( $settings['cta_url']['url'] ) && '' !== $settings['cta_url']['url']
				? (string) $settings['cta_url']['url']
				: ( function_exists( 'pixva_page_url' ) ? pixva_page_url( 'calculator' ) : '' );

			$glow_strength = isset( $settings['glow_strength']['size'] ) ? (float) $settings['glow_strength']['size'] / 100 : 0.45;

			$args = array(
				'id'            => 'pixva-sim-' . $this->get_id(),
				'variant'       => 'widget',
				'items'         => $items,
				'pricing'       => $pricing,
				'badge'         => (string) $settings['badge_text'],
				'title'         => (string) $settings['sim_title'],
				'subtitle'      => (string) $settings['sim_subtitle'],
				'cta_text'      => (string) $settings['cta_text'],
				'cta_url'       => $cta_url,
				'calc_target'   => (string) $settings['calc_target'],
				'wa_enabled'    => 'yes' === $settings['whatsapp_enabled'],
				'wa_label'      => (string) $settings['whatsapp_text'],
				'note'          => (string) $settings['sim_note'],
				'frame_image'   => isset( $settings['frame_image'] ) ? $settings['frame_image'] : '',
				'frame_color'   => (string) $settings['frame_color'],
				'frame_border'  => (string) $settings['frame_border'],
				'radius'        => isset( $settings['frame_radius']['size'] ) ? (int) $settings['frame_radius']['size'] : 20,
				'glow'          => (string) $settings['glow_color'],
				'glow_strength' => $glow_strength,
				'scan'          => 'yes' === $settings['show_scan'],
				'stand'         => 'yes' === $settings['show_stand'],
				'swipe'         => 'yes' === $settings['mobile_swipe'],
				'dots'          => 'yes' === $settings['swipe_dots'],
			);

			$style = $this->pixva_style_vars( $settings );
			?>
			<div class="<?php echo esc_attr( $this->pixva_block_class( $settings ) ); ?> pixva-sim-block" <?php echo $style ? 'style="' . $style . '"' : ''; ?>>
				<?php pixva_render_fault_simulator( $args ); ?>
			</div>
			<?php
		}

		/**
		 * خروجی قالب ویرایشگر (پیش‌نمایش زنده در المنتور).
		 *
		 * @return void
		 */
		protected function content_template() {}
	}
}

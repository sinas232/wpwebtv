<?php
/**
 * PIXVA widgets for the Elementor editor (free Elementor).
 *
 * Every widget delegates to the theme's existing render functions and real
 * data sources — nothing is duplicated and no business logic moves into the
 * page builder (orders/tracking/warranty/booking stay in the secure WP
 * layer). The whole file returns early when Elementor is inactive, so the
 * theme never fatals without the plugin.
 *
 * @package Pixva
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Only ever loaded from the elementor/widgets/register callback (see
// elementor-widgets.php), at which point \Elementor\Widget_Base exists.
if ( ! class_exists( '\Elementor\Widget_Base' ) ) {
	return;
}

/**
 * Shared behaviour for all PIXVA widgets.
 *
 * @since 2.0.0
 */
abstract class Pixva_Elementor_Widget extends \Elementor\Widget_Base {

	/**
	 * Category in the panel.
	 *
	 * @return array<string>
	 */
	public function get_category() {
		return array( 'pixva' );
	}

	/**
	 * Section heading controls (title / lead / "see all" link).
	 *
	 * @param string $id     Control section id.
	 * @param array  $labels Labels: title, lead, more.
	 * @return void
	 */
	protected function pixva_head_controls( $id, $labels = array() ) {
		$labels = wp_parse_args(
			$labels,
			array(
				'title' => __( 'عنوان بخش', 'pixva' ),
				'lead'  => __( 'توضیح کوتاه', 'pixva' ),
			)
		);
		$this->start_controls_section(
			$id,
			array(
				'label' => __( 'سربرگ بخش', 'pixva' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);
		$this->add_control(
			$id . '_show',
			array(
				'label'        => __( 'نمایش سربرگ', 'pixva' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => __( 'نمایش', 'pixva' ),
				'label_off'    => __( 'پنهان', 'pixva' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);
		$this->add_control(
			$id . '_title',
			array(
				'label'   => $labels['title'],
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => __( 'بخش PIXVA', 'pixva' ),
				'dynamic' => array( 'active' => true ),
				'condition' => array( $id . '_show' => 'yes' ),
			)
		);
		$this->add_control(
			$id . '_lead',
			array(
				'label'   => $labels['lead'],
				'type'    => \Elementor\Controls_Manager::TEXTAREA,
				'dynamic' => array( 'active' => true ),
				'condition' => array( $id . '_show' => 'yes' ),
			)
		);
		$this->add_control(
			$id . '_more_url',
			array(
				'label'         => __( 'لینک «مشاهده همه» (اختیاری)', 'pixva' ),
				'type'          => \Elementor\Controls_Manager::URL,
				'placeholder'   => 'https://…',
				'show_external' => true,
				'dynamic'       => array( 'active' => true ),
				'condition'     => array( $id . '_show' => 'yes' ),
			)
		);
		$this->end_controls_section();
	}

	/**
	 * Print the section heading using the theme's markup.
	 *
	 * @param array  $settings Settings.
	 * @param string $id       Section id (aria-labelledby).
	 * @return bool Whether a section wrapper was opened.
	 */
	protected function pixva_render_head( $settings, $id ) {
		if ( empty( $settings[ $id . '_show' ] ) ) {
			return false;
		}
		$more = '';
		if ( ! empty( $settings[ $id . '_more_url']['url'] ) ) {
			$more = $settings[ $id . '_more_url']['url'];
		}
		pixva_section_open(
			$id,
			(string) $settings[ $id . '_title'],
			$more,
			(string) ( $settings[ $id . '_lead'] ?? '' )
		);
		return true;
	}

	/**
	 * Generic surface controls (background, padding, radius) for a selector.
	 *
	 * @param string $id       Section id.
	 * @param string $selector CSS selector.
	 * @param bool   $with_bg  Show background color control.
	 * @return void
	 */
	protected function pixva_surface_controls( $id, $selector, $with_bg = true ) {
		$this->start_controls_section(
			$id,
			array(
				'label' => __( 'ظاهر', 'pixva' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			)
		);
		if ( $with_bg ) {
			$this->add_control(
				$id . '_bg',
				array(
					'label'     => __( 'رنگ پس‌زمینه', 'pixva' ),
					'type'      => \Elementor\Controls_Manager::COLOR,
					'default'   => '',
					'selectors' => array( $selector => 'background-color: {{VALUE}};' ),
				)
			);
		}
		$this->add_responsive_control(
			$id . '_padding',
			array(
				'label'      => __( 'فاصله داخلی', 'pixva' ),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%' ),
				'selectors'  => array( $selector => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
			)
		);
		$this->add_control(
			$id . '_radius',
			array(
				'label'      => __( 'گردی گوشه', 'pixva' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px', '%' ),
				'range'      => array( 'px' => array( 'max' => 40 ) ),
				'selectors'  => array( $selector => 'border-radius: {{SIZE}}{{UNIT}};' ),
			)
		);
		$this->end_controls_section();
	}
}

/**
 * Hero (front-page style) — real diagnosis entry + real CTA routes.
 */
class Pixva_Elementor_Hero extends Pixva_Elementor_Widget {

	public function get_name() {
		return 'pixva-hero';
	}

	public function get_title() {
		return __( 'PIXVA — پیشبرد (Hero)', 'pixva' );
	}

	public function get_icon() {
		return 'eicon-banner';
	}

	public function get_description() {
		return __( 'سربرگ صفحه اول با عنوان، متن معرفی، فرم تشخیص و دکمه‌های واقعی ثبت/پیگیری.', 'pixva' );
	}

	protected function register_controls() {
		$this->start_controls_section(
			'pixva_hero_content',
			array(
				'label' => __( 'محتوا', 'pixva' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);
		$this->add_control(
			'pixva_hero_eyebrow',
			array(
				'label'       => __( 'برچسب بالا (پیش‌فرض: نام کسب‌وکار)', 'pixva' ),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'default'     => pixva_business_name(),
				'dynamic'     => array( 'active' => true ),
				'label_block' => true,
			)
		);
		$this->add_control(
			'pixva_hero_title',
			array(
				'label'   => __( 'عنوان', 'pixva' ),
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => __( 'تشخیص و تعمیر تلویزیون، شفاف و قابل پیگیری', 'pixva' ),
				'dynamic' => array( 'active' => true ),
			)
		);
		$this->add_control(
			'pixva_hero_lead',
			array(
				'label'   => __( 'متن معرفی (پیش‌فرض: چکیده صفحه اول)', 'pixva' ),
				'type'    => \Elementor\Controls_Manager::TEXTAREA,
				'default' => pixva_front_lead(),
				'dynamic' => array( 'active' => true ),
			)
		);
		$this->add_control(
			'pixva_hero_quick',
			array(
				'label'        => __( 'فرم سریع تشخیص', 'pixva' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);
		$this->add_control(
			'pixva_hero_cta1',
			array(
				'label'   => __( 'دکمه اول (متن)', 'pixva' ),
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => __( 'ثبت درخواست تعمیر', 'pixva' ),
			)
		);
		$this->add_control(
			'pixva_hero_cta1_url',
			array(
				'label'   => __( 'دکمه اول (لینک — پیش‌فرض: رزرو)', 'pixva' ),
				'type'    => \Elementor\Controls_Manager::URL,
				'default' => array( 'url' => pixva_route_url( 'booking' ) ),
				'dynamic' => array( 'active' => true ),
			)
		);
		$this->add_control(
			'pixva_hero_cta2',
			array(
				'label'   => __( 'دکمه دوم (متن)', 'pixva' ),
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => __( 'پیگیری درخواست', 'pixva' ),
			)
		);
		$this->add_control(
			'pixva_hero_cta2_url',
			array(
				'label'   => __( 'دکمه دوم (لینک — پیش‌فرض: پیگیری)', 'pixva' ),
				'type'    => \Elementor\Controls_Manager::URL,
				'default' => array( 'url' => pixva_route_url( 'tracking' ) ),
				'dynamic' => array( 'active' => true ),
			)
		);
		$this->add_control(
			'pixva_hero_visual',
			array(
				'label'        => __( 'تصویر تزئینی تلویزیون', 'pixva' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);
		$this->end_controls_section();

		$this->start_controls_section(
			'pixva_hero_style',
			array(
				'label' => __( 'ظاهر', 'pixva' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			)
		);
		$this->add_control(
			'pixva_hero_title_color',
			array(
				'label'     => __( 'رنگ عنوان', 'pixva' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .hero__title' => 'color: {{VALUE}};' ),
			)
		);
		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			array(
				'name'     => 'pixva_hero_title_typo',
				'label'    => __( 'تایپوگرافی عنوان', 'pixva' ),
				'selector' => '{{WRAPPER}} .hero__title',
			)
		);
		$this->add_control(
			'pixva_hero_lead_color',
			array(
				'label'     => __( 'رنگ متن معرفی', 'pixva' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .hero__lead' => 'color: {{VALUE}};' ),
			)
		);
		$this->end_controls_section();
	}

	protected function render() {
		$settings = $this->get_settings_for_display();
		$problems = pixva_diagnosis_problems();
		?>
		<section class="hero" aria-labelledby="hero-title">
			<div class="container hero__grid">
				<div class="hero__text">
					<p class="eyebrow"><?php echo esc_html( (string) $settings['pixva_hero_eyebrow'] ); ?></p>
					<h1 class="hero__title" id="hero-title"><?php echo esc_html( (string) $settings['pixva_hero_title'] ); ?></h1>
					<p class="hero__lead"><?php echo esc_html( (string) $settings['pixva_hero_lead'] ); ?></p>
					<?php if ( 'yes' === $settings['pixva_hero_quick'] && $problems ) : ?>
						<form class="hero__quick" method="get" action="<?php echo esc_url( pixva_route_url( 'diagnosis' ) ); ?>" data-track-submit="cta_click" data-track-label="diagnosis" data-track-location="elementor_hero">
							<label for="hero-problem-el"><?php esc_html_e( 'تلویزیون شما چه مشکلی دارد؟', 'pixva' ); ?></label>
							<div class="input-group">
								<select id="hero-problem-el" name="problem">
									<option value=""><?php esc_html_e( 'انتخاب کنید…', 'pixva' ); ?></option>
									<?php foreach ( $problems as $k => $p ) : ?>
										<option value="<?php echo esc_attr( $k ); ?>"><?php echo esc_html( $p['label'] ); ?></option>
									<?php endforeach; ?>
								</select>
								<input type="hidden" name="step" value="1">
								<button class="btn btn--accent" type="submit"><?php esc_html_e( 'شروع تشخیص', 'pixva' ); ?></button>
							</div>
						</form>
					<?php endif; ?>
					<?php if ( ! empty( $settings['pixva_hero_cta1'] ) || ! empty( $settings['pixva_hero_cta2'] ) ) : ?>
						<p class="hero__actions">
							<?php if ( ! empty( $settings['pixva_hero_cta1'] ) ) : ?>
								<a class="btn btn--ghost-light" data-track="cta_click" data-track-label="booking" data-track-location="elementor_hero" href="<?php echo esc_url( ! empty( $settings['pixva_hero_cta1_url']['url'] ) ? $settings['pixva_hero_cta1_url']['url'] : pixva_route_url( 'booking' ) ); ?>"><?php echo esc_html( (string) $settings['pixva_hero_cta1'] ); ?></a>
							<?php endif; ?>
							<?php if ( ! empty( $settings['pixva_hero_cta2'] ) ) : ?>
								<a class="btn btn--link-light" href="<?php echo esc_url( ! empty( $settings['pixva_hero_cta2_url']['url'] ) ? $settings['pixva_hero_cta2_url']['url'] : pixva_route_url( 'tracking' ) ); ?>"><?php echo esc_html( (string) $settings['pixva_hero_cta2'] ); ?></a>
							<?php endif; ?>
						</p>
					<?php endif; ?>
				</div>
				<?php if ( 'yes' === $settings['pixva_hero_visual'] ) : ?>
					<div class="hero__visual" aria-hidden="true">
						<div class="tv-mock"><div class="tv-mock__screen"><span></span><span></span><span></span><span></span><span></span><span></span></div><div class="tv-mock__stand"></div></div>
					</div>
				<?php endif; ?>
			</div>
		</section>
		<?php
	}
}

/**
 * Services grid (tv_services CPT).
 */
class Pixva_Elementor_Services extends Pixva_Elementor_Widget {

	public function get_name() {
		return 'pixva-services';
	}

	public function get_title() {
		return __( 'PIXVA — خدمات تعمیر', 'pixva' );
	}

	public function get_icon() {
		return 'eicon-video-playlist';
	}

	public function get_description() {
		return __( 'کارت‌های خدمات از CPT واقعی «خدمات» با مرتب‌سازی منو.', 'pixva' );
	}

	protected function register_controls() {
		$this->pixva_head_controls(
			'pixva_services_head',
			array( 'title' => __( 'عنوان (پیش‌فرض: خدمات تعمیر)', 'pixva' ) )
		);
				$this->start_controls_section(
			'pixva_services_query',
			array(
				'label' => __( 'منبع داده', 'pixva' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);
		$this->add_responsive_control(
			'pixva_services_count',
			array(
				'label'   => __( 'تعداد (۰ = همه)', 'pixva' ),
				'type'    => \Elementor\Controls_Manager::NUMBER,
				'min'     => 0,
				'max'     => 48,
				'default' => 6,
			)
		);
		$this->add_control(
			'pixva_services_empty',
			array(
				'label'   => __( 'پیام خالی‌بودن', 'pixva' ),
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => __( 'هنوز خدماتی ثبت نشده است.', 'pixva' ),
			)
		);
		$this->end_controls_section();
		$this->pixva_surface_controls( 'pixva_services_style', '{{WRAPPER}} .grid--cards' );
	}

	protected function render() {
		$settings = $this->get_settings_for_display();
		$count    = (int) ( $settings['pixva_services_count'] ?? 6 );
		$posts    = get_posts(
			array(
				'post_type'      => 'tv_services',
				'post_status'    => 'publish',
				'posts_per_page' => $count > 0 ? $count : -1,
				'orderby'        => array(
					'menu_order' => 'ASC',
					'title'      => 'ASC',
				),
				'no_found_rows'  => true,
			)
		);
		$empty_msg = (string) ( $settings['pixva_services_empty'] ?? '' );
		if ( ! $posts && '' === $empty_msg ) {
			// Classic parity: no published services and no empty message ⇒ no section.
			return;
		}
		$opened = $this->pixva_render_head( $settings, 'pixva_services_head' );
		if ( $posts ) {
			pixva_card_grid( $posts );
		} elseif ( '' !== $empty_msg ) {
			echo '<p class="section__lead">' . esc_html( $empty_msg ) . '</p>';
		}
		if ( $opened ) {
			pixva_section_close();
		}
	}
}

/**
 * Brands chips (tv_brands CPT).
 */
class Pixva_Elementor_Brands extends Pixva_Elementor_Widget {

	public function get_name() {
		return 'pixva-brands';
	}

	public function get_title() {
		return __( 'PIXVA — برندها', 'pixva' );
	}

	public function get_icon() {
		return 'eicon-t-shirt';
	}

	public function get_description() {
		return __( 'چیپ‌های برندها از CPT واقعی «برندهای تلویزیون».', 'pixva' );
	}

	protected function register_controls() {
		$this->pixva_head_controls( 'pixva_brands_head', array( 'title' => __( 'عنوان (پیش‌فرض: برندها)', 'pixva' ) ) );
						$this->start_controls_section(
			'pixva_brands_opts',
			array(
				'label' => __( 'پرس‌وجو', 'pixva' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);
$this->add_responsive_control(
			'pixva_brands_count',
			array(
				'label'   => __( 'تعداد (۰ = همه)', 'pixva' ),
				'type'    => \Elementor\Controls_Manager::NUMBER,
				'min'     => 0,
				'max'     => 96,
				'default' => 24,
			)
		);
				$this->end_controls_section();
$this->start_controls_section(
			'pixva_brands_style',
			array(
				'label' => __( 'ظاهر چیپ‌ها', 'pixva' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			)
		);
		$this->add_control(
			'pixva_brands_chip_bg',
			array(
				'label'     => __( 'رنگ پس‌زمینه چیپ', 'pixva' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .chip' => 'background-color: {{VALUE}};' ),
			)
		);
		$this->add_control(
			'pixva_brands_chip_color',
			array(
				'label'     => __( 'رنگ متن چیپ', 'pixva' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .chip' => 'color: {{VALUE}};' ),
			)
		);
		$this->add_control(
			'pixva_brands_chip_radius',
			array(
				'label'      => __( 'گردی گوشه', 'pixva' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px', '%' ),
				'selectors'  => array( '{{WRAPPER}} .chip' => 'border-radius: {{SIZE}}{{UNIT}};' ),
			)
		);
		$this->end_controls_section();
	}

	protected function render() {
		$settings = $this->get_settings_for_display();
		$posts    = get_posts(
			array(
				'post_type'      => 'tv_brands',
				'post_status'    => 'publish',
				'posts_per_page' => (int) $settings['pixva_brands_count'] > 0 ? (int) $settings['pixva_brands_count'] : -1,
				'orderby'        => array(
					'menu_order' => 'ASC',
					'title'      => 'ASC',
				),
				'no_found_rows'  => true,
			)
		);
		if ( ! $posts ) {
			return;
		}
		$opened = $this->pixva_render_head( $settings, 'pixva_brands_head' );
		if ( $posts ) {
			echo '<ul class="chips chips--lg">';
			foreach ( $posts as $b ) {
				$en = (string) get_post_meta( $b->ID, '_pixva_brand_en', true );
				echo '<li><a class="chip" href="' . esc_url( get_permalink( $b ) ) . '">' . esc_html( trim( preg_replace( '/^تعمیر\s+(تلویزیون\s+)?/u', '', get_the_title( $b ) ) ) ) . ( '' !== $en ? ' <span lang="en" dir="ltr">' . esc_html( $en ) . '</span>' : '' ) . '</a></li>';
			}
			echo '</ul>';
		}
		if ( $opened ) {
			pixva_section_close();
		}
	}
}

/**
 * Common problem tiles.
 */
class Pixva_Elementor_Problems extends Pixva_Elementor_Widget {

	public function get_name() {
		return 'pixva-problems';
	}

	public function get_title() {
		return __( 'PIXVA — مشکلات رایج', 'pixva' );
	}

	public function get_icon() {
		return 'eicon-warning';
	}

	public function get_description() {
		return __( 'کاشی‌های مشکل رایج از داده تشخیص واقعی قالب.', 'pixva' );
	}

	protected function register_controls() {
		$this->pixva_head_controls( 'pixva_problems_head', array( 'title' => __( 'عنوان (پیش‌فرض: مشکل رایج خود را انتخاب کنید)', 'pixva' ) ) );
						$this->start_controls_section(
			'pixva_problems_opts',
			array(
				'label' => __( 'کاشی‌ها', 'pixva' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);
$this->add_control(
			'pixva_problems_empty_mode',
			array(
				'label'   => __( 'وقتی هیچ مشکلی منتشر نشده', 'pixva' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'options' => array(
					'diagnosis' => __( 'پیشنهاد فهرست تشخیص آنلاین (مانند صفحه اصلی)', 'pixva' ),
					'state'     => __( 'پیام خالی با دکمه‌ها (مانند صفحه مشکلات)', 'pixva' ),
				),
				'default' => 'diagnosis',
			)
		);
		$this->add_responsive_control(
			'pixva_problems_limit',
			array(
				'label'   => __( 'حداکثر تعداد', 'pixva' ),
				'type'    => \Elementor\Controls_Manager::NUMBER,
				'min'     => 1,
				'max'     => 40,
				'default' => 8,
			)
		);
				$this->end_controls_section();
$this->pixva_surface_controls( 'pixva_problems_style', '{{WRAPPER}} .grid--tiles', false );
	}

	protected function render() {
		$settings = $this->get_settings_for_display();
		$opened   = $this->pixva_render_head( $settings, 'pixva_problems_head' );
		if ( ! pixva_problem_tiles( (int) $settings['pixva_problems_limit'] ) ) {
			if ( 'state' === ( $settings['pixva_problems_empty_mode'] ?? 'diagnosis' ) ) {
				pixva_empty_state(
					__( 'راهنمای مشکلات هنوز منتشر نشده است', 'pixva' ),
					__( 'تا آن زمان می‌توانید با تشخیص آنلاین، علت‌های محتمل ایراد تلویزیون خود را ببینید.', 'pixva' ),
					array(
						__( 'شروع تشخیص آنلاین', 'pixva' ) => pixva_route_url( 'diagnosis' ),
						__( 'ثبت درخواست تعمیر', 'pixva' ) => pixva_route_url( 'booking' ),
					)
				);
				if ( $opened ) {
					pixva_section_close();
				}
				return;
			}
			$problems = array_slice( pixva_diagnosis_problems(), 0, (int) $settings['pixva_problems_limit'], true );
			if ( $problems ) {
				echo '<ul class="grid grid--tiles">';
				foreach ( $problems as $k => $p ) {
					echo '<li class="tile"><a href="' . esc_url( pixva_route_url( 'diagnosis', array( 'problem' => $k, 'step' => '1' ) ) ) . '"><span class="tile__title">' . esc_html( $p['label'] ) . '</span><span class="tile__text">' . esc_html( $p['desc'] ) . '</span></a></li>';
				}
				echo '</ul>';
			}
		}
		if ( $opened ) {
			pixva_section_close();
		}
	}
}

/**
 * Free troubleshooting tools.
 */
class Pixva_Elementor_Tools extends Pixva_Elementor_Widget {

	public function get_name() {
		return 'pixva-tools';
	}

	public function get_title() {
		return __( 'PIXVA — ابزارهای رایگان', 'pixva' );
	}

	public function get_icon() {
		return 'eicon-tools';
	}

	protected function register_controls() {
		$this->pixva_head_controls( 'pixva_tools_head', array( 'title' => __( 'عنوان (پیش‌فرض: ابزارهای رایگان عیب‌یابی)', 'pixva' ) ) );
			}

	protected function render() {
		$settings = $this->get_settings_for_display();
		$opened   = $this->pixva_render_head( $settings, 'pixva_tools_head' );
		pixva_tool_cards();
		if ( $opened ) {
			pixva_section_close();
		}
	}
}

/**
 * Process steps (editable text items — no fabricated numbers/claims).
 */
class Pixva_Elementor_Steps extends Pixva_Elementor_Widget {

	public function get_name() {
		return 'pixva-steps';
	}

	public function get_title() {
		return __( 'PIXVA — مراحل کار', 'pixva' );
	}

	public function get_icon() {
		return 'eicon-checklist';
	}

	protected function register_controls() {
		$this->pixva_head_controls( 'pixva_steps_head', array( 'title' => __( 'عنوان (پیش‌فرض: روند کار چطور است؟)', 'pixva' ) ) );
		$this->start_controls_section(
			'pixva_steps_items',
			array(
				'label' => __( 'مراحل', 'pixva' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);
		$repeater = new \Elementor\Repeater();
		$repeater->add_control(
			'item_title',
			array(
				'label'   => __( 'عنوان مرحله', 'pixva' ),
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => __( 'ثبت درخواست', 'pixva' ),
			)
		);
		$repeater->add_control(
			'item_text',
			array(
				'label'   => __( 'شرح مرحله', 'pixva' ),
				'type'    => \Elementor\Controls_Manager::TEXTAREA,
				'default' => __( 'مشخصات دستگاه و ایراد را ثبت می‌کنید و کد پیگیری می‌گیرید.', 'pixva' ),
			)
		);
		$this->add_control(
			'pixva_steps_list',
			array(
				'label'         => __( 'فهرست مراحل', 'pixva' ),
				'type'          => \Elementor\Controls_Manager::REPEATER,
				'fields'        => array(
					$repeater->get_controls(),
				),
				'title_field'   => '{{{ item_title }}}',
				'default'       => array(
					array(
						'item_title' => __( 'ثبت درخواست', 'pixva' ),
						'item_text'  => __( 'مشخصات دستگاه و ایراد را ثبت می‌کنید و کد پیگیری می‌گیرید.', 'pixva' ),
					),
					array(
						'item_title' => __( 'بررسی و اعلام هزینه', 'pixva' ),
						'item_text'  => __( 'پس از کارشناسی، علت خرابی و هزینه پیش از شروع کار به شما اعلام می‌شود.', 'pixva' ),
					),
					array(
						'item_title' => __( 'تعمیر با تأیید شما', 'pixva' ),
						'item_text'  => __( 'تعمیر فقط بعد از تأیید هزینه انجام می‌شود.', 'pixva' ),
					),
					array(
						'item_title' => __( 'پیگیری و تحویل', 'pixva' ),
						'item_text'  => __( 'وضعیت هر مرحله را با کد پیگیری آنلاین می‌بینید.', 'pixva' ),
					),
				),
			)
		);
		$this->end_controls_section();
		$this->start_controls_section(
			'pixva_steps_style',
			array(
				'label' => __( 'ظاهر', 'pixva' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			)
		);
		$this->add_control(
			'pixva_steps_color',
			array(
				'label'     => __( 'رنگ عنوان مرحله', 'pixva' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .steps__item h3' => 'color: {{VALUE}};' ),
			)
		);
		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			array(
				'name'     => 'pixva_steps_title_typo',
				'label'    => __( 'تایپوگرافی عنوان', 'pixva' ),
				'selector' => '{{WRAPPER}} .steps__item h3',
			)
		);
		$this->end_controls_section();
	}

	protected function render() {
		$settings = $this->get_settings_for_display();
		$opened   = $this->pixva_render_head( $settings, 'pixva_steps_head' );
		$items    = $settings['pixva_steps_list'];
		if ( $items ) {
			echo '<ol class="steps">';
			foreach ( $items as $item ) {
				echo '<li class="steps__item"><h3>' . esc_html( (string) $item['item_title'] ) . '</h3><p>' . esc_html( (string) $item['item_text'] ) . '</p></li>';
			}
			echo '</ol>';
		}
		if ( $opened ) {
			pixva_section_close();
		}
	}
}

/**
 * FAQ (pixva_faq CPT — real entries only).
 */
class Pixva_Elementor_FAQ extends Pixva_Elementor_Widget {

	public function get_name() {
		return 'pixva-faq';
	}

	public function get_title() {
		return __( 'PIXVA — پرسش‌های متداول', 'pixva' );
	}

	public function get_icon() {
		return 'eicon-faq';
	}

	protected function register_controls() {
		$this->pixva_head_controls( 'pixva_faq_head', array( 'title' => __( 'عنوان (پیش‌فرض: پرسش‌های متداول)', 'pixva' ) ) );
				$this->start_controls_section(
			'pixva_faq_query',
			array(
				'label' => __( 'منبع داده', 'pixva' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);
		$this->add_control(
			'pixva_faq_topic',
			array(
				'label'       => __( 'موضوع', 'pixva' ),
				'type'        => \Elementor\Controls_Manager::SELECT,
				'options'     => array_merge( array( '' => __( 'همه موضوعات', 'pixva' ) ), pixva_faq_topics() ),
				'default'     => 'general',
				'label_block' => true,
			)
		);
		$this->add_control(
			'pixva_faq_empty_mode',
			array(
				'label'   => __( 'وقتی پرسشی موجود نیست', 'pixva' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'options' => array(
					'state' => __( 'پیام خالی با دکمه تماس (مانند صفحه پرسش‌ها)', 'pixva' ),
					'skip'  => __( 'بدون نمایش چیزی (مانند بخش‌های صفحه اصلی)', 'pixva' ),
				),
				'default' => 'state',
			)
		);
		$this->add_control(
			'pixva_faq_group',
			array(
				'label'        => __( 'گروه‌بندی بر اساس موضوع (با فهرست دسته‌ها، مانند صفحه پرسش‌ها)', 'pixva' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'no',
			)
		);
		$this->add_responsive_control(
			'pixva_faq_count',
			array(
				'label'   => __( 'حداکثر تعداد', 'pixva' ),
				'type'    => \Elementor\Controls_Manager::NUMBER,
				'min'     => 1,
				'max'     => 50,
				'default' => 6,
			)
		);
		$this->add_control(
			'pixva_faq_html',
			array(
				'label'        => __( 'قالب‌بندی HTML پاسخ‌ها', 'pixva' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);
		$this->end_controls_section();
		$this->start_controls_section(
			'pixva_faq_style',
			array(
				'label' => __( 'ظاهر', 'pixva' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			)
		);
		$this->add_control(
			'pixva_faq_q_color',
			array(
				'label'     => __( 'رنگ پرسش', 'pixva' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .faq__item summary' => 'color: {{VALUE}};' ),
			)
		);
		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			array(
				'name'     => 'pixva_faq_q_typo',
				'label'    => __( 'تایپوگرافی پرسش', 'pixva' ),
				'selector' => '{{WRAPPER}} .faq__item summary',
			)
		);
		$this->end_controls_section();
	}

	protected function render() {
		$settings = $this->get_settings_for_display();
		$items    = pixva_faq_items( (string) $settings['pixva_faq_topic'] );
		$limit    = (int) $settings['pixva_faq_count'];
		$items    = $limit > 0 ? array_slice( $items, 0, $limit ) : $items;
		if ( ! $items ) {
			if ( 'skip' === ( $settings['pixva_faq_empty_mode'] ?? 'state' ) ) {
				return;
			}
			// Classic /faq/ parity: real empty state, never fabricated entries.
			pixva_empty_state(
				__( 'هنوز پرسشی منتشر نشده است', 'pixva' ),
				__( 'پرسش خود را از طریق فرم تماس بفرستید.', 'pixva' ),
				array( __( 'تماس با ما', 'pixva' ) => pixva_route_url( 'contact' ) )
			);
			return;
		}
		if ( 'yes' === ( $settings['pixva_faq_group'] ?? 'no' ) ) {
			// Grouped rendering identical to the classic /faq/ template.
			$topics = array(
				'general'  => __( 'عمومی', 'pixva' ),
				'booking'  => __( 'ثبت درخواست', 'pixva' ),
				'pricing'  => __( 'هزینه', 'pixva' ),
				'tracking' => __( 'پیگیری', 'pixva' ),
				'warranty' => __( 'گارانتی', 'pixva' ),
			);
			$groups = array();
			foreach ( $items as $it ) {
				$t              = isset( $topics[ $it['topic'] ] ) ? $it['topic'] : 'general';
				$groups[ $t ][] = $it;
			}
			$opened = $this->pixva_render_head( $settings, 'pixva_faq_head' );
			if ( count( $groups ) > 1 ) {
				echo '<nav class="toc" aria-label="' . esc_attr__( 'دسته‌ها', 'pixva' ) . '"><ul>';
				foreach ( $topics as $k => $label ) {
					if ( ! empty( $groups[ $k ] ) ) {
						echo '<li><a href="#faq-' . esc_attr( $k ) . '">' . esc_html( $label ) . '</a></li>';
					}
				}
				echo '</ul></nav>';
			}
			foreach ( $topics as $k => $label ) {
				if ( ! empty( $groups[ $k ] ) ) {
					echo '<section class="section--tight" id="faq-' . esc_attr( $k ) . '" aria-labelledby="faq-h-' . esc_attr( $k ) . '">';
					echo '<h2 id="faq-h-' . esc_attr( $k ) . '">' . esc_html( $label ) . '</h2>';
					pixva_faq_list( $groups[ $k ], true );
					echo '</section>';
				}
			}
			if ( $opened ) {
				pixva_section_close();
			}
			return;
		}
		$opened = $this->pixva_render_head( $settings, 'pixva_faq_head' );
		pixva_faq_list( $items, 'yes' === $settings['pixva_faq_html'] );
		if ( $opened ) {
			pixva_section_close();
		}
	}
}

/**
 * CTA banner (real booking/diagnosis/phone actions).
 */
class Pixva_Elementor_CTA extends Pixva_Elementor_Widget {

	public function get_name() {
		return 'pixva-cta';
	}

	public function get_title() {
		return __( 'PIXVA — بنر اقدام', 'pixva' );
	}

	public function get_icon() {
		return 'eicon-cta';
	}

	protected function register_controls() {
		$this->start_controls_section(
			'pixva_cta_content',
			array(
				'label' => __( 'محتوا', 'pixva' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);
		$this->add_control(
			'pixva_cta_title',
			array(
				'label'   => __( 'عنوان', 'pixva' ),
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => __( 'مطمئن نیستید ایراد از کجاست؟', 'pixva' ),
				'dynamic' => array( 'active' => true ),
			)
		);
		$this->add_control(
			'pixva_cta_text',
			array(
				'label'   => __( 'متن', 'pixva' ),
				'type'    => \Elementor\Controls_Manager::TEXTAREA,
				'default' => __( 'با ابزار تشخیص علت‌های محتمل را ببینید یا مستقیم درخواست بررسی ثبت کنید.', 'pixva' ),
				'dynamic' => array( 'active' => true ),
			)
		);
		$this->add_control(
			'pixva_cta_location',
			array(
				'label'       => __( 'محل تحلیل دکمه‌ها (data-track-location)', 'pixva' ),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'default'     => 'elementor_cta',
				'label_block' => true,
			)
		);
		$this->add_control(
			'pixva_cta_show_phone',
			array(
				'label'        => __( 'نمایش شماره تماس واقعی (اگر ثبت شده)', 'pixva' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);
		$this->end_controls_section();
		$this->start_controls_section(
			'pixva_cta_style',
			array(
				'label' => __( 'ظاهر', 'pixva' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			)
		);
		$this->add_control(
			'pixva_cta_bg',
			array(
				'label'     => __( 'رنگ پس‌زمینه', 'pixva' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .cta' => 'background-color: {{VALUE}};' ),
			)
		);
		$this->end_controls_section();
	}

	protected function render() {
		$settings = $this->get_settings_for_display();
		if ( 'yes' !== $settings['pixva_cta_show_phone'] ) {
			// Render without the phone by temporarily hiding the primary phone filter.
			add_filter( 'pixva_primary_phone', '__return_empty_string' );
			pixva_cta_box( (string) $settings['pixva_cta_title'], (string) $settings['pixva_cta_text'], (string) ( $settings['pixva_cta_location'] ?? 'elementor_cta' ) );
			remove_filter( 'pixva_primary_phone', '__return_empty_string' );
		} else {
			pixva_cta_box( (string) $settings['pixva_cta_title'], (string) $settings['pixva_cta_text'], (string) ( $settings['pixva_cta_location'] ?? 'elementor_cta' ) );
		}
	}
}

/**
 * Contact details (real business claims only).
 */
class Pixva_Elementor_Contact extends Pixva_Elementor_Widget {

	public function get_name() {
		return 'pixva-contact';
	}

	public function get_title() {
		return __( 'PIXVA — اطلاعات تماس و ساعت کاری', 'pixva' );
	}

	public function get_icon() {
		return 'eicon-phone';
	}

	public function get_description() {
		return __( 'فقط داده‌های واقعی ثبت‌شده در «اطلاعات کسب‌وکار» را نشان می‌دهد؛ چیزی ساخته نمی‌شود.', 'pixva' );
	}

	protected function register_controls() {
		$this->pixva_head_controls( 'pixva_contact_head', array( 'title' => __( 'عنوان (اختیاری — خالی = بدون سربرگ)', 'pixva' ), 'lead' => ' ' ) );
				$this->start_controls_section(
			'pixva_contact_opts',
			array(
				'label' => __( 'گزینه‌ها', 'pixva' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);
$this->add_control(
			'pixva_contact_social',
			array(
				'label'        => __( 'نمایش شبکه‌های اجتماعی', 'pixva' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);
				$this->end_controls_section();
$this->pixva_surface_controls( 'pixva_contact_style', '{{WRAPPER}} .details', false );
	}

	protected function render() {
		$settings = $this->get_settings_for_display();
		$opened   = $this->pixva_render_head( $settings, 'pixva_contact_head' );
		// pixva_contact_details() prints phone/mobile/address/hours/map/service
		// area from the real business claims (hours included) or returns false.
		if ( ! pixva_contact_details() ) {
			echo '<p>' . esc_html__( 'اطلاعات تماس هنوز منتشر نشده است؛ از فرم استفاده کنید.', 'pixva' ) . '</p>';
		}
		if ( pixva_has_claim( 'response_time' ) ) {
			echo '<p>' . esc_html( sprintf( /* translators: %s: response time. */ __( 'زمان پاسخ‌گویی: %s', 'pixva' ), (string) pixva_claim( 'response_time' ) ) ) . '</p>';
		}
		if ( 'yes' === $settings['pixva_contact_social'] ) {
			$social = pixva_social_links();
			if ( $social ) {
				echo '<ul class="social" aria-label="' . esc_attr__( 'شبکه‌های اجتماعی', 'pixva' ) . '">';
				foreach ( $social as $s ) {
					echo '<li><a href="' . esc_url( $s[1] ) . '" rel="noopener me" target="_blank">' . esc_html( $s[0] ) . '</a></li>';
				}
				echo '</ul>';
			}
		}
		if ( $opened ) {
			pixva_section_close();
		}
	}
}

/**
 * Announcement bar (owner-configured — never fabricated).
 */
class Pixva_Elementor_Notice extends Pixva_Elementor_Widget {

	public function get_name() {
		return 'pixva-notice';
	}

	public function get_title() {
		return __( 'PIXVA — نوار اعلان', 'pixva' );
	}

	public function get_icon() {
		return 'eicon-alert';
	}

	protected function register_controls() {
		$this->start_controls_section(
			'pixva_notice_content',
			array(
				'label' => __( 'محتوا', 'pixva' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);
		$this->add_control(
			'pixva_notice_text',
			array(
				'label'       => __( 'متن اعلان (پیش‌فرض: اعلان تنظیمات PIXVA)', 'pixva' ),
				'type'        => \Elementor\Controls_Manager::TEXTAREA,
				'dynamic'     => array( 'active' => true ),
				'label_block' => true,
			)
		);
		$this->add_control(
			'pixva_notice_link',
			array(
				'label'         => __( 'لینک (اختیاری)', 'pixva' ),
				'type'          => \Elementor\Controls_Manager::URL,
				'show_external' => true,
				'dynamic'       => array( 'active' => true ),
			)
		);
		$this->add_control(
			'pixva_notice_link_text',
			array(
				'label'   => __( 'متن لینک', 'pixva' ),
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => __( 'بیشتر بدانید', 'pixva' ),
			)
		);
		$this->end_controls_section();
		$this->start_controls_section(
			'pixva_notice_style',
			array(
				'label' => __( 'ظاهر', 'pixva' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			)
		);
		$this->add_control(
			'pixva_notice_bg',
			array(
				'label'     => __( 'رنگ پس‌زمینه', 'pixva' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#0B1C2E',
				'selectors' => array( '{{WRAPPER}} .pixva-notice-bar' => 'background-color: {{VALUE}};' ),
			)
		);
		$this->add_control(
			'pixva_notice_color',
			array(
				'label'     => __( 'رنگ متن', 'pixva' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#F8FAFC',
				'selectors' => array( '{{WRAPPER}} .pixva-notice-bar' => 'color: {{VALUE}};' ),
			)
		);
		$this->end_controls_section();
	}

	protected function render() {
		$settings = $this->get_settings_for_display();
		$text     = (string) $settings['pixva_notice_text'];
		if ( '' === $text ) {
			$announcement = get_option( 'pixva_announcement', array() );
			$text         = is_array( $announcement ) ? (string) ( $announcement['text'] ?? '' ) : '';
		}
		if ( '' === $text ) {
			return; // Never invent an announcement.
		}
		$url  = ! empty( $settings['pixva_notice_link']['url'] ) ? $settings['pixva_notice_link']['url'] : '';
		if ( '' === $url ) {
			$announcement = get_option( 'pixva_announcement', array() );
			$url          = is_array( $announcement ) ? (string) ( $announcement['url'] ?? '' ) : '';
		}
		echo '<div class="pixva-notice-bar" role="status"><div class="container"><p>' . esc_html( $text );
		if ( '' !== $url ) {
			echo ' <a href="' . esc_url( $url ) . '">' . esc_html( (string) $settings['pixva_notice_link_text'] ) . '</a>';
		}
		echo '</p></div></div>';
	}
}

/**
 * Breadcrumbs (theme's schema-aware output).
 */
class Pixva_Elementor_Breadcrumbs extends Pixva_Elementor_Widget {

	public function get_name() {
		return 'pixva-breadcrumbs';
	}

	public function get_title() {
		return __( 'PIXVA — مسیر راهنما (Breadcrumb)', 'pixva' );
	}

	public function get_icon() {
		return 'eicon-bullet-list';
	}

	protected function register_controls() {
				$this->start_controls_section(
			'pixva_bc_opts',
			array(
				'label' => __( 'گزینه‌ها', 'pixva' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);
$this->add_control(
			'pixva_bc_note',
			array(
				'type'      => \Elementor\Controls_Manager::RAW_HTML,
				'raw'       => __( 'خروجی و اسکیمای Breadcrumb همان خروجی هسته قالب است و با سئوی صفحه تداخلی ایجاد نمی‌کند.', 'pixva' ),
				'content_classes' => 'elementor-inline-editing',
			)
		);
		$this->end_controls_section();
	}

	protected function render() {
		pixva_breadcrumbs();
	}
}

/**
 * Recent content cards (posts or repair cases).
 */
class Pixva_Elementor_Posts extends Pixva_Elementor_Widget {

	public function get_name() {
		return 'pixva-posts';
	}

	public function get_title() {
		return __( 'PIXVA — مقالات/نمونه‌کارها', 'pixva' );
	}

	public function get_icon() {
		return 'eicon-post-list';
	}

	protected function register_controls() {
		$this->pixva_head_controls( 'pixva_posts_head', array( 'title' => __( 'عنوان (پیش‌فرض: از مجله پیکسوا)', 'pixva' ) ) );
		$this->start_controls_section(
			'pixva_posts_query',
			array(
				'label' => __( 'منبع داده', 'pixva' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);
		$this->add_control(
			'pixva_posts_type',
			array(
				'label'   => __( 'نوع محتوا', 'pixva' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'options' => array(
					'post'           => __( 'مقالات مجله', 'pixva' ),
					'repair_cases'   => __( 'نمونه‌کارهای تعمیر', 'pixva' ),
				),
				'default' => 'post',
			)
		);
		$this->add_responsive_control(
			'pixva_posts_count',
			array(
				'label'   => __( 'تعداد', 'pixva' ),
				'type'    => \Elementor\Controls_Manager::NUMBER,
				'min'     => 1,
				'max'     => 12,
				'default' => 3,
			)
		);
		$this->add_control(
			'pixva_posts_more',
			array(
				'label'   => __( 'لینک مشاهده همه', 'pixva' ),
				'type'    => \Elementor\Controls_Manager::URL,
				'default' => array( 'url' => pixva_route_url( 'blog' ) ),
				'dynamic' => array( 'active' => true ),
			)
		);
		$this->end_controls_section();
		$this->pixva_surface_controls( 'pixva_posts_style', '{{WRAPPER}} .grid--cards', false );
	}

	protected function render() {
		$settings = $this->get_settings_for_display();
		$posts    = get_posts(
			array(
				'post_type'           => 'repair_cases' === $settings['pixva_posts_type'] ? 'repair_cases' : 'post',
				'post_status'         => 'publish',
				'posts_per_page'      => (int) $settings['pixva_posts_count'],
				'no_found_rows'       => true,
				'ignore_sticky_posts' => true,
			)
		);
		if ( ! $posts ) {
			return;
		}
		$opened = $this->pixva_render_head( $settings, 'pixva_posts_head' );
		if ( $posts ) {
			pixva_card_grid( $posts );
		}
		if ( $opened ) {
			pixva_section_close();
		}
	}
}

/**
 * Booking form widget — full §11 pipeline via the shared renderer.
 */
class Pixva_Elementor_Booking extends Pixva_Elementor_Widget {

	public function get_name() {
		return 'pixva-booking-form';
	}

	public function get_title() {
		return __( 'PIXVA — فرم ثبت درخواست تعمیر', 'pixva' );
	}

	public function get_icon() {
		return 'eicon-form-horizontal';
	}

	public function get_description() {
		return __( 'همان فرم امن صفحه رزرو: nonce، محافظ نوشته، محدودیت نرخ و ذخیره امن عکس‌ها.', 'pixva' );
	}

	protected function register_controls() {
				$this->start_controls_section(
			'pixva_booking_opts',
			array(
				'label' => __( 'گزینه‌ها', 'pixva' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);
$this->add_control(
			'pixva_booking_faq',
			array(
				'label'        => __( 'نمایش پرسش‌های رایج رزرو زیر فرم', 'pixva' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);
		$this->add_control(
			'pixva_booking_note',
			array(
				'type'            => \Elementor\Controls_Manager::RAW_HTML,
				'raw'             => __( 'ارسال فرم به admin-post وارد می‌شود؛ هیچ‌یک از قواعد امنیتی فرم تغییر نمی‌کند.', 'pixva' ),
				'content_classes' => 'elementor-inline-editing',
			)
		);
		$this->end_controls_section();
	}

	protected function render() {
		$settings = $this->get_settings_for_display();
		pixva_booking_form_block();
		if ( 'yes' === $settings['pixva_booking_faq'] ) {
			$faq = pixva_faq_items( 'booking' );
			if ( $faq ) {
				echo '<section class="section--tight" aria-labelledby="bk-faq-el"><h2 id="bk-faq-el">' . esc_html__( 'پرسش‌های رایج', 'pixva' ) . '</h2>';
				pixva_faq_list( $faq, true );
				echo '</section>';
			}
		}
	}
}

/**
 * Tracking lookup widget (real code+phone verification, POST same-page).
 */
class Pixva_Elementor_Tracking extends Pixva_Elementor_Widget {

	public function get_name() {
		return 'pixva-tracking-form';
	}

	public function get_title() {
		return __( 'PIXVA — فرم پیگیری تعمیر', 'pixva' );
	}

	public function get_icon() {
		return 'eicon-search';
	}

	protected function register_controls() {
				$this->start_controls_section(
			'pixva_track_opts',
			array(
				'label' => __( 'گزینه‌ها', 'pixva' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);
$this->add_control(
			'pixva_track_faq',
			array(
				'label'        => __( 'نمایش پرسش‌های رایج پیگیری زیر فرم', 'pixva' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'no',
			)
		);
		$this->add_control(
			'pixva_track_note',
			array(
				'type'            => \Elementor\Controls_Manager::RAW_HTML,
				'raw'             => __( 'تأیید کد+شماره، محدودیت نرخ و عدم افشای PII همان مسیر هسته است.', 'pixva' ),
				'content_classes' => 'elementor-inline-editing',
			)
		);
		$this->end_controls_section();
	}

	protected function render() {
		$settings = $this->get_settings_for_display();
		$result   = pixva_handle_page_lookup( 'pixva_track' );
		?>
		<div class="pixva-lookup" data-pixva-lookup data-endpoint="track">
			<?php pixva_lookup_form( 'pixva_track', 'track', __( 'نمایش وضعیت', 'pixva' ), 'tracking_viewed' ); ?>
			<div class="lookup__result" data-lookup-result aria-live="polite" tabindex="-1">
				<?php if ( is_wp_error( $result ) ) : ?>
					<?php pixva_notice( 'error', $result->get_error_message(), '', true ); ?>
				<?php elseif ( is_array( $result ) ) : ?>
					<div data-track-view="tracking_viewed"><?php pixva_order_view( $result ); ?></div>
				<?php endif; ?>
			</div>
		</div>
		<?php
		// Classic template parity: the same follow-up help line.
		if ( is_user_logged_in() ) {
			echo '<p><a class="link-more" href="' . esc_url( pixva_route_url( 'account_repairs' ) ) . '">' . esc_html__( 'همه درخواست‌های من', 'pixva' ) . '</a></p>';
		} else {
			echo '<p class="field__help">' . esc_html__( 'کد را گم کرده‌اید؟ اگر با حساب کاربری درخواست ثبت کرده‌اید، وارد حساب شوید.', 'pixva' ) . ' <a href="' . esc_url( pixva_route_url( 'account' ) ) . '">' . esc_html__( 'ورود', 'pixva' ) . '</a></p>';
		}
		if ( 'yes' === $settings['pixva_track_faq'] ) {
			$faq = pixva_faq_items( 'tracking' );
			if ( $faq ) {
				echo '<section class="section--tight" aria-labelledby="tr-faq-el"><h2 id="tr-faq-el">' . esc_html__( 'پرسش‌های رایج', 'pixva' ) . '</h2>';
				pixva_faq_list( $faq, true );
				echo '</section>';
			}
		}
	}
}

/**
 * Warranty lookup widget.
 */
class Pixva_Elementor_Warranty extends Pixva_Elementor_Widget {

	public function get_name() {
		return 'pixva-warranty-form';
	}

	public function get_title() {
		return __( 'PIXVA — استعلام گارانتی', 'pixva' );
	}

	public function get_icon() {
		return 'eicon-verified';
	}

	protected function register_controls() {
				$this->start_controls_section(
			'pixva_warranty_opts',
			array(
				'label' => __( 'گزینه‌ها', 'pixva' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);
$this->add_control(
			'pixva_warranty_policy',
			array(
				'label'        => __( 'نمایش سیاست گارانتی (از ادعاهای واقعی کسب‌وکار)', 'pixva' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);
		$this->add_control(
			'pixva_warranty_faq',
			array(
				'label'        => __( 'نمایش پرسش‌های رایج گارانتی زیر استعلام', 'pixva' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);
		$this->add_control(
			'pixva_warranty_note',
			array(
				'type'            => \Elementor\Controls_Manager::RAW_HTML,
				'raw'             => __( 'استعلام با کد+شماره واقعی؛ پاسخ فقط از داده ثبت‌شده ساخته می‌شود.', 'pixva' ),
				'content_classes' => 'elementor-inline-editing',
			)
		);
		$this->end_controls_section();
	}

	protected function render() {
		$settings = $this->get_settings_for_display();
		$result   = pixva_handle_page_lookup( 'pixva_warranty' );
		if ( 'yes' === ( $settings['pixva_warranty_policy'] ?? 'yes' ) ) {
			$days = pixva_warranty_policy_days();
			$on   = (bool) pixva_claim( 'warranty_enabled' );
			?>
			<section aria-labelledby="wr-policy">
				<h2 id="wr-policy"><?php esc_html_e( 'سیاست گارانتی', 'pixva' ); ?></h2>
				<?php if ( $days > 0 ) : ?>
					<p class="price"><?php echo esc_html( sprintf( /* translators: %s: days. */ __( '%s روز گارانتی تعمیر', 'pixva' ), pixva_fa_num( $days ) ) ); ?></p>
				<?php endif; ?>
				<?php if ( $on && pixva_has_claim( 'warranty_terms' ) ) : ?>
					<div class="entry-content"><?php echo wp_kses_post( wpautop( (string) pixva_claim( 'warranty_terms' ) ) ); ?></div>
				<?php endif; ?>
				<?php if ( $on && pixva_has_claim( 'warranty_excluded' ) ) : ?>
					<h3><?php esc_html_e( 'موارد خارج از پوشش', 'pixva' ); ?></h3>
					<div class="entry-content"><?php echo wp_kses_post( wpautop( (string) pixva_claim( 'warranty_excluded' ) ) ); ?></div>
				<?php endif; ?>
				<?php if ( ! $on || ( $days <= 0 && ! pixva_has_claim( 'warranty_terms' ) ) ) : ?>
					<?php pixva_notice( 'info', __( 'سیاست گارانتی عمومی هنوز منتشر نشده است. شرایط و مدت گارانتی هر تعمیر را پیش از شروع کار از کارشناس بپرسید؛ پس از ثبت، وضعیت گارانتی تعمیر شما در این صفحه قابل استعلام است.', 'pixva' ) ); ?>
				<?php endif; ?>
			</section>
			<?php
		}
		?>
		<section class="section--tight" aria-labelledby="wr-lookup">
			<h2 id="wr-lookup"><?php esc_html_e( 'استعلام گارانتی تعمیر', 'pixva' ); ?></h2>
		<div class="pixva-lookup" data-pixva-lookup data-endpoint="warranty">
			<?php pixva_lookup_form( 'pixva_warranty', 'warranty', __( 'استعلام', 'pixva' ), 'warranty_lookup' ); ?>
			<div class="lookup__result" data-lookup-result aria-live="polite" tabindex="-1">
				<?php if ( is_wp_error( $result ) ) : ?>
					<?php pixva_notice( 'error', $result->get_error_message(), '', true ); ?>
				<?php elseif ( is_array( $result ) ) : ?>
					<div data-track-view="warranty_lookup"><?php pixva_warranty_view( $result ); ?></div>
				<?php endif; ?>
			</div>
		</div>
		</section>
		<?php
		if ( 'yes' === ( $settings['pixva_warranty_faq'] ?? 'yes' ) ) {
			$faq = pixva_faq_items( 'warranty' );
			if ( $faq ) {
				echo '<section class="section--tight" aria-labelledby="wr-faq"><h2 id="wr-faq">' . esc_html__( 'پرسش‌های رایج', 'pixva' ) . '</h2>';
				pixva_faq_list( $faq, true );
				echo '</section>';
			}
		}
	}
}
/**
 * Secure contact form widget — byte-for-byte the classic /contact/ form
 * (admin-post + nonce + honeypot + rate limit + PRG live in inc/forms.php).
 */
class Pixva_Elementor_ContactForm extends Pixva_Elementor_Widget {

	public function get_name() {
		return 'pixva-contact-form';
	}

	public function get_title() {
		return __( 'PIXVA — فرم تماس امن', 'pixva' );
	}

	public function get_icon() {
		return 'eicon-form-horizontal';
	}

	public function get_description() {
		return __( 'همان فرم امن صفحه تماس: ارسال به admin-post، nonce، محافظ نوشته، محدودیت نرخ و PRG — بدون تغییر در قواعد امنیتی.', 'pixva' );
	}

	protected function register_controls() {
				$this->start_controls_section(
			'pixva_contact_form_opts',
			array(
				'label' => __( 'گزینه‌ها', 'pixva' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);
$this->add_control(
			'pixva_contact_form_note',
			array(
				'type'            => \Elementor\Controls_Manager::RAW_HTML,
				'raw'             => __( 'ساختار، نام فیلدها و مقصد ارسال با قالب کلاسیک یکسان است؛ فقط محل نمایش در صفحه را Elementor تعیین می‌کند.', 'pixva' ),
				'content_classes' => 'elementor-inline-editing',
			)
		);
		$this->end_controls_section();
	}

	protected function render() {
		$r = pixva_form_result( 'pixva_contact' );
		?>
		<div id="pixva-contact" class="form-wrap" tabindex="-1">
			<?php if ( is_array( $r ) && $r['ok'] ) : ?>
				<div class="success">
					<?php pixva_notice( 'success', $r['payload']['message'], __( 'پیام شما ثبت شد', 'pixva' ), true ); ?>
				</div>
			<?php else : ?>
				<h2><?php esc_html_e( 'ارسال پیام', 'pixva' ); ?></h2>
				<p class="field__help"><?php esc_html_e( 'برای ثبت تعمیر از فرم درخواست تعمیر استفاده کنید تا کد پیگیری بگیرید.', 'pixva' ); ?> <a href="<?php echo esc_url( pixva_route_url( 'booking' ) ); ?>"><?php esc_html_e( 'ثبت درخواست تعمیر', 'pixva' ); ?></a></p>
				<form class="form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" data-pixva-form data-replace-on-success novalidate>
					<?php pixva_form_fields( 'pixva_contact' ); ?>
					<?php pixva_form_status( $r ); ?>
					<div class="form-grid">
						<?php
						pixva_field(
							array(
								'name'     => 'name',
								'id'       => 'ct-name',
								'label'    => __( 'نام', 'pixva' ),
								'required' => true,
								'attrs'    => array( 'autocomplete' => 'name' ),
							),
							$r
						);
						pixva_field(
							array(
								'name'  => 'phone',
								'id'    => 'ct-phone',
								'label' => __( 'شماره همراه', 'pixva' ),
								'type'  => 'tel',
								'help'  => __( 'شماره همراه یا ایمیل؛ دست‌کم یکی لازم است.', 'pixva' ),
								'attrs' => array(
									'autocomplete' => 'tel',
									'dir'          => 'ltr',
									'inputmode'    => 'tel',
								),
							),
							$r
						);
						pixva_field(
							array(
								'name'  => 'email',
								'id'    => 'ct-email',
								'label' => __( 'ایمیل', 'pixva' ),
								'type'  => 'email',
								'attrs' => array(
									'autocomplete' => 'email',
									'dir'          => 'ltr',
								),
							),
							$r
						);
						?>
					</div>
					<?php
					pixva_field(
						array(
							'name'     => 'message',
							'id'       => 'ct-message',
							'label'    => __( 'پیام', 'pixva' ),
							'type'     => 'textarea',
							'required' => true,
							'attrs'    => array(
								'rows'      => '5',
								'maxlength' => '3000',
							),
						),
						$r
					);
					pixva_field(
						array(
							'name'     => 'consent',
							'id'       => 'ct-consent',
							'type'     => 'checkbox',
							'required' => true,
							'label'    => pixva_consent_label( __( 'با ذخیره اطلاعاتم برای پاسخ‌گویی موافقم.', 'pixva' ) ),
						),
						$r
					);
					?>
					<div class="form__actions"><button class="btn btn--primary" type="submit" data-submit><?php esc_html_e( 'ارسال', 'pixva' ); ?></button></div>
				</form>
			<?php endif; ?>
		</div>
		<?php
	}
}

/**
 * Service delivery modes widget — configured claims only (never invented).
 */
class Pixva_Elementor_ServiceModes extends Pixva_Elementor_Widget {

	public function get_name() {
		return 'pixva-service-modes';
	}

	public function get_title() {
		return __( 'PIXVA — شیوه‌های دریافت خدمت', 'pixva' );
	}

	public function get_icon() {
		return 'eicon-handshake';
	}

	public function get_description() {
		return __( 'فهرست شیوه‌هایی که واقعاً در «اطلاعات کسب‌وکار» فعال شده‌اند؛ اگر چیزی فعال نباشد چیزی نمایش داده نمی‌شود.', 'pixva' );
	}

	protected function register_controls() {
		$this->pixva_head_controls(
			'pixva_modes_head',
			array(
				'title' => __( 'عنوان (پیش‌فرض: شیوه‌های دریافت خدمت)', 'pixva' ),
			)
		);
	}

	protected function render() {
		$settings = $this->get_settings_for_display();
		$modes    = pixva_service_modes();
		if ( ! $modes ) {
			return;
		}
		$opened = $this->pixva_render_head( $settings, 'pixva_modes_head' );
		pixva_list( array_values( $modes ), 'checks' );
		if ( pixva_has_claim( 'service_area' ) ) {
			echo '<p>' . esc_html( sprintf( /* translators: %s: area. */ __( 'محدوده خدمت: %s', 'pixva' ), (string) pixva_claim( 'service_area' ) ) ) . '</p>';
		}
		if ( $opened ) {
			pixva_section_close();
		}
	}
}

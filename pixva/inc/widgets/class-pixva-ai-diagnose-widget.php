<?php
/**
 * ویجت المنتور: عیب‌یاب هوشمند «AI Quick Diagnose» — لایه ۱٫۶٫۰.
 *
 * گوی درخشان (الهام‌گرفته از Siri/Gemini) با تپش نئونی + دو دکمه «آپلود ویدیوی
 * خرابی» و «ضبط صدای دستگاه». فایل رسانه با AJAX به
 * `wp-json/pixva/v1/ai-diagnose` فرستاده می‌شود تا بعداً بتوان عامل پایتون/هوش
 * مصنوعی دلخواه را به همان آدرس وصل کرد؛ تا آن زمان پرونده در صندوق ورودی ثبت
 * می‌شود و کد پیگیری برمی‌گردد.
 *
 * @package Pixva
 * @since   1.6.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( '\Elementor\Widget_Base' ) && ! did_action( 'elementor/loaded' ) ) {
	return;
}

if ( ! class_exists( 'Pixva_Ai_Diagnose_Widget' ) ) {
	/**
	 * ویجت عیب‌یاب هوشمند رسانه‌محور.
	 */
	class Pixva_Ai_Diagnose_Widget extends Pixva_Section_Widget_Base {

		/**
		 * نام ویجت.
		 *
		 * @return string
		 */
		public function get_name() {
			return 'pixva_ai_diagnose';
		}

		/**
		 * عنوان.
		 *
		 * @return string
		 */
		public function get_title() {
			return esc_html__( 'عیب‌یابی سریع هوشمند (ویدیو/صدا)', 'pixva' );
		}

		/**
		 * آیکون.
		 *
		 * @return string
		 */
		public function get_icon() {
			return 'eicon-ai';
		}

		/**
		 * کلیدواژه‌ها.
		 *
		 * @return array<int, string>
		 */
		public function get_keywords() {
			return array( 'pixva', 'ai', 'diagnose', 'video', 'audio', 'upload', 'smart' );
		}

		/**
		 * وابستگی اسکریپت‌ها.
		 *
		 * @return array<int, string>
		 */
		public function get_script_depends() {
			return array( 'pixva-main', 'pixva-ai-diagnose' );
		}

		/**
		 * بارگذاری دارایی‌ها هنگام رندر.
		 *
		 * @return void
		 */
		protected function enqueue_front_assets() {
			parent::enqueue_front_assets();
			wp_enqueue_script( 'pixva-ai-diagnose' );
		}

		/**
		 * ثبت کنترل‌ها.
		 *
		 * @return void
		 */
		protected function register_controls() {
			$this->start_controls_section(
				'pixva_ai_head',
				array(
					'label' => esc_html__( 'سربرگ و متن‌ها', 'pixva' ),
					'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
				)
			);

			$this->add_control(
				'badge',
				array(
					'label'   => esc_html__( 'برچسب بالایی', 'pixva' ),
					'type'    => \Elementor\Controls_Manager::TEXT,
					'default' => __( 'عیب‌یابی هوشمند', 'pixva' ),
				)
			);

			$this->add_control(
				'title',
				array(
					'label'   => esc_html__( 'تیتر', 'pixva' ),
					'type'    => \Elementor\Controls_Manager::TEXT,
					'default' => __( 'ویدیو یا صدای دستگاه را بفرستید تا تحلیل شود', 'pixva' ),
				)
			);

			$this->add_control(
				'subtitle',
				array(
					'label'   => esc_html__( 'توضیح', 'pixva' ),
					'type'    => \Elementor\Controls_Manager::TEXTAREA,
					'default' => __( 'یک ویدیوی کوتاه از خرابی یا صدای دستگاه را آپلود کنید؛ خروجی تحلیل به همراه کد پیگیری برای شما ثبت می‌شود.', 'pixva' ),
				)
			);

			$this->add_control(
				'video_label',
				array(
					'label'   => esc_html__( 'متن دکمه آپلود ویدیو', 'pixva' ),
					'type'    => \Elementor\Controls_Manager::TEXT,
					'default' => __( 'آپلود ویدیوی خرابی', 'pixva' ),
				)
			);

			$this->add_control(
				'mic_label',
				array(
					'label'   => esc_html__( 'متن دکمه ضبط صدا', 'pixva' ),
					'type'    => \Elementor\Controls_Manager::TEXT,
					'default' => __( 'ضبط صدای دستگاه', 'pixva' ),
				)
			);

			$this->add_control(
				'submit',
				array(
					'label'   => esc_html__( 'متن دکمه تحلیل', 'pixva' ),
					'type'    => \Elementor\Controls_Manager::TEXT,
					'default' => __( 'تحلیل هوشمند', 'pixva' ),
				)
			);

			$this->add_control(
				'privacy',
				array(
					'label'   => esc_html__( 'یادداشت حریم خصوصی', 'pixva' ),
					'type'    => \Elementor\Controls_Manager::TEXTAREA,
					'default' => __( 'فایل‌ها فقط برای عیب‌یابی پرونده شما استفاده می‌شوند و پس از بسته‌شدن پرونده پاک می‌گردند.', 'pixva' ),
				)
			);

			$this->end_controls_section();

			/* ------------------------------ رفتار ----------------------------- */
			$this->start_controls_section(
				'pixva_ai_behavior',
				array(
					'label' => esc_html__( 'رفتار فرم', 'pixva' ),
					'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
				)
			);

			$this->add_control(
				'show_phone',
				array(
					'label'        => esc_html__( 'دریافت شماره همراه', 'pixva' ),
					'type'         => \Elementor\Controls_Manager::SWITCHER,
					'default'      => 'yes',
					'return_value' => 'yes',
				)
			);

			$this->add_control(
				'accept',
				array(
					'label'       => esc_html__( 'انواع فایل مجاز', 'pixva' ),
					'type'        => \Elementor\Controls_Manager::TEXT,
					'default'     => 'video/*,audio/*',
					'description' => esc_html__( 'مقدار HTML «accept» برای ورودی فایل.', 'pixva' ),
				)
			);

			$this->add_control(
				'max_note',
				array(
					'label'       => esc_html__( 'یادداشت سقف حجم', 'pixva' ),
					'type'        => \Elementor\Controls_Manager::TEXT,
					'default'     => __( 'ویدیوی ۱۰ تا ۳۰ ثانیه‌ای کافی است؛ سقف حجم از تنظیمات پوسته خوانده می‌شود.', 'pixva' ),
					'description' => esc_html__( 'برای تغییر سقف از فیلتر pixva_ai_diagnose_max_size (مگابایت) استفاده کنید.', 'pixva' ),
				)
			);

			$this->add_control(
				'pulse',
				array(
					'label'   => esc_html__( 'سرعت تپش گوی (ثانیه)', 'pixva' ),
					'type'    => \Elementor\Controls_Manager::SLIDER,
					'range'   => array(
						'px' => array(
							'min'  => 1,
							'max'  => 6,
							'step' => 0.1,
						),
					),
					'default' => array(
						'size' => 2.4,
					),
				)
			);

			$this->end_controls_section();

			/* ------------------------------- سبک ------------------------------ */
			$this->start_controls_section(
				'pixva_ai_style',
				array(
					'label' => esc_html__( 'رنگ نئونی گوی', 'pixva' ),
					'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
				)
			);

			$this->add_control(
				'neon',
				array(
					'label'   => esc_html__( 'رنگ اول', 'pixva' ),
					'type'    => \Elementor\Controls_Manager::COLOR,
					'default' => 'rgb(56, 189, 248)',
				)
			);

			$this->add_control(
				'neon2',
				array(
					'label'   => esc_html__( 'رنگ دوم', 'pixva' ),
					'type'    => \Elementor\Controls_Manager::COLOR,
					'default' => 'rgb(168, 85, 247)',
				)
			);

			$this->add_responsive_control(
				'orb_size',
				array(
					'label'      => esc_html__( 'اندازه گوی', 'pixva' ),
					'type'       => \Elementor\Controls_Manager::SLIDER,
					'size_units' => array( 'px', 'rem' ),
					'range'      => array(
						'px' => array(
							'min' => 80,
							'max' => 320,
						),
					),
					'default'    => array(
						'unit' => 'px',
						'size' => 168,
					),
					'selectors'  => array(
						'{{WRAPPER}} .pixva-ai__orb' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
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

			$pulse = 2.4;
			if ( is_array( $settings['pulse'] ) && isset( $settings['pulse']['size'] ) && '' !== $settings['pulse']['size'] ) {
				$pulse = (float) $settings['pulse']['size'];
			} elseif ( is_numeric( $settings['pulse'] ) ) {
				$pulse = (float) $settings['pulse'];
			}

			pixva_render_ai_diagnose(
				array(
					'badge'       => (string) $settings['badge'],
					'title'       => (string) $settings['title'],
					'subtitle'    => (string) $settings['subtitle'],
					'video_label' => (string) $settings['video_label'],
					'mic_label'   => (string) $settings['mic_label'],
					'submit'      => (string) $settings['submit'],
					'privacy'     => (string) $settings['privacy'],
					'max_note'    => (string) $settings['max_note'],
					'accept'      => '' !== trim( (string) $settings['accept'] ) ? (string) $settings['accept'] : 'video/*,audio/*',
					'show_phone'  => isset( $settings['show_phone'] ) && 'yes' === $settings['show_phone'],
					'pulse'       => $pulse,
					'neon'        => '' !== trim( (string) $settings['neon'] ) ? (string) $settings['neon'] : 'rgb(56, 189, 248)',
					'neon2'       => '' !== trim( (string) $settings['neon2'] ) ? (string) $settings['neon2'] : 'rgb(168, 85, 247)',
					'element_id'  => isset( $settings['_element_id'] ) ? (string) $settings['_element_id'] : '',
				)
			);
		}
	}
}

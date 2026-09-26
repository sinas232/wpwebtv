<?php
/**
 * شورت‌کدهای عمومی پیکسوا (inc/shortcodes.php)
 *
 * این پرونده هیچ وابستگی به المنتور ندارد تا شورت‌کدهای ۶۰ ابزار و نمایه هاب‌ها
 * روی هر نصبی (با المنتور یا بدون آن) کار کنند:
 *   [pixva_tool id="1"] … [pixva_tool id="60"]   با layout="section|inline"
 *   [pixva_hub slug="ai-diagnostics" title="…"]
 *
 * @package Pixva
 * @since   1.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'pixva_tool_shortcode' ) ) {
	/**
	 * شورت‌کد فراخوانی هر یک از ۶۰ ابزار تعاملی.
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
		if ( $id < 1 || $id > 60 || ! function_exists( 'pixva_render_tool_by_id' ) ) {
			return '';
		}

		return (string) pixva_render_tool_by_id( $id, 'inline' === $atts['layout'] ? 'inline' : 'section' );
	}
	add_shortcode( 'pixva_tool', 'pixva_tool_shortcode' );
}

if ( ! function_exists( 'pixva_hub_shortcode' ) ) {
	/**
	 * شورت‌کد نمایه یک هاب: [pixva_hub slug="pricing-calculator"]
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
		if ( ! function_exists( 'pixva_hubs' ) || ! function_exists( 'pixva_render_hub_index' ) ) {
			return '';
		}

		$hubs = pixva_hubs();
		if ( ! isset( $hubs[ $slug ] ) ) {
			return '';
		}

		ob_start();
		pixva_render_hub_index( $slug, (string) $atts['title'] );
		return (string) ob_get_clean();
	}
	add_shortcode( 'pixva_hub', 'pixva_hub_shortcode' );
}

if ( ! function_exists( 'pixva_hub_tools_shortcode' ) ) {
	/**
	 * شورت‌کد ابزارهای یک هاب به‌صورت آکاردئون: [pixva_hub_tools slug="error-codes"]
	 *
	 * @param array $atts مشخصه‌ها.
	 * @return string
	 */
	function pixva_hub_tools_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'slug' => 'ai-diagnostics',
				'open' => 0,
			),
			$atts,
			'pixva_hub_tools'
		);

		$slug = sanitize_key( $atts['slug'] );
		if ( ! function_exists( 'pixva_hubs' ) || ! function_exists( 'pixva_render_hub_tools' ) ) {
			return '';
		}

		$hubs = pixva_hubs();
		if ( ! isset( $hubs[ $slug ] ) ) {
			return '';
		}

		ob_start();
		pixva_render_hub_tools( $slug, (bool) $atts['open'] );
		return (string) ob_get_clean();
	}
	add_shortcode( 'pixva_hub_tools', 'pixva_hub_tools_shortcode' );
}

if ( ! function_exists( 'pixva_technician_panel_shortcode' ) ) {
	/**
	 * شورت‌کد داشبورد تعمیرکار: [pixva_technician_panel].
	 *
	 * فقط دستگاه‌های تخصیص‌یافته به تعمیرکارِ واردشده را همراه با فرم گزارش
	 * فنی، قیمت نهایی و دکمه «تأیید و صدور کارت گارانتی» نمایش می‌دهد.
	 *
	 * @param array|string $atts ویژگی‌های شورت‌کد.
	 * @return string
	 */
	function pixva_technician_panel_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'title'    => '',
				'subtitle' => '',
				'hero'     => 'yes',
				'status'   => '',
				'limit'    => 30,
			),
			$atts,
			'pixva_technician_panel'
		);

		if ( ! function_exists( 'pixva_render_technician_panel' ) ) {
			return '';
		}

		$title = '' !== trim( (string) $atts['title'] )
			? (string) $atts['title']
			: (string) pixva_option( 'pixva_technician_title', __( 'داشبورد تعمیرکار', 'pixva' ) );
		$subtitle = '' !== trim( (string) $atts['subtitle'] )
			? (string) $atts['subtitle']
			: (string) pixva_option(
				'pixva_technician_subtitle',
				__( 'دستگاه‌های تخصیص‌یافته به شما، گزارش فنی قطعات، قیمت نهایی و صدور کارت گارانتی در یک محیط کاری تمیز.', 'pixva' )
			);
		$show_hero = ! in_array( strtolower( (string) $atts['hero'] ), array( 'no', 'false', '0' ), true );

		ob_start();
		?>
		<div class="pixva-tech-dash">
			<?php if ( $show_hero && function_exists( 'pixva_page_hero' ) ) : ?>
				<?php pixva_page_hero( $title, $subtitle ); ?>
			<?php endif; ?>

			<div class="pixva-container">
				<?php if ( function_exists( 'pixva_technician_dashboard_bar' ) ) : ?>
					<?php pixva_technician_dashboard_bar(); ?>
				<?php endif; ?>

				<?php
				pixva_render_technician_panel(
					array(
						'title'  => $show_hero ? '' : $title,
						'status' => sanitize_key( (string) $atts['status'] ),
						'limit'  => max( 1, (int) $atts['limit'] ),
					)
				);
				?>
			</div>
		</div>
		<?php
		return (string) ob_get_clean();
	}
	add_shortcode( 'pixva_technician_panel', 'pixva_technician_panel_shortcode' );
}


/*
 * ---------------------------------------------------------------------------
 * شورت‌کدهای لایه ۱٫۶٫۰ (سینمایی، سه‌بعدی، عیب‌یاب هوشمند، نقشه زنده)
 *
 * همان خروجی ویجت‌های المنتور را بدون المنتور می‌سازند؛ اسکریپت هر بخش فقط
 * وقتی همان شورت‌کد در صفحه باشد صف می‌شود (بارگذاری شرطی).
 * ---------------------------------------------------------------------------
 */

if ( ! function_exists( 'pixva_cinematic_shortcode' ) ) {
	/**
	 * شورت‌کد اسکرول سینمایی: [pixva_cinematic_unboxing height="320" speed="1"].
	 *
	 * @param array|string $atts ویژگی‌های شورت‌کد.
	 * @return string
	 */
	function pixva_cinematic_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'badge'    => '',
				'title'    => '',
				'subtitle' => '',
				'cta'      => '',
				'note'     => '',
				'height'   => 320,
				'speed'    => 1,
				'spread'   => 120,
				'rotate'   => 16,
				'pin'      => 'yes',
				'scrub'    => 'yes',
				'neon'     => '',
				'neon2'    => '',
				'layers'   => '',
				'id'       => '',
			),
			$atts,
			'pixva_cinematic_unboxing'
		);

		if ( ! function_exists( 'pixva_render_cinematic_unboxing' ) ) {
			return '';
		}

		pixva_enqueue_cinematic_assets( array( 'cinematic' ) );

		$settings = array(
			'height'     => max( 100, (int) $atts['height'] ),
			'speed'      => max( 0.2, (float) $atts['speed'] ),
			'spread'     => max( 20, (int) $atts['spread'] ),
			'rotate'     => max( 0, (int) $atts['rotate'] ),
			'pin'        => ! in_array( strtolower( (string) $atts['pin'] ), array( 'no', 'false', '0' ), true ),
			'scrub'      => ! in_array( strtolower( (string) $atts['scrub'] ), array( 'no', 'false', '0' ), true ),
			'element_id' => sanitize_html_class( (string) $atts['id'] ),
		);

		foreach ( array(
			'badge'    => 'badge',
			'title'    => 'title',
			'subtitle' => 'subtitle',
			'cta'      => 'cta_text',
			'note'     => 'note',
			'neon'     => 'neon',
			'neon2'    => 'neon2',
		) as $from => $to ) {
			if ( '' !== trim( (string) $atts[ $from ] ) ) {
				$settings[ $to ] = (string) $atts[ $from ];
			}
		}

		$layers = pixva_shortcode_json( (string) $atts['layers'] );
		if ( ! empty( $layers ) ) {
			$settings['layers'] = $layers;
		}

		ob_start();
		pixva_render_cinematic_unboxing( $settings );
		return (string) ob_get_clean();
	}
	add_shortcode( 'pixva_cinematic_unboxing', 'pixva_cinematic_shortcode' );
}

if ( ! function_exists( 'pixva_3d_shortcode' ) ) {
	/**
	 * شورت‌کد مدل سه‌بعدی: [pixva_3d_repair url="https://prod.spline.design/..."].
	 *
	 * @param array|string $atts ویژگی‌های شورت‌کد.
	 * @return string
	 */
	function pixva_3d_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'url'      => '',
				'badge'    => '',
				'title'    => '',
				'subtitle' => '',
				'height'   => 34,
				'lazy'     => 'yes',
				'fallback' => 'yes',
				'hotspots' => '',
				'neon'     => '',
				'accent'   => '',
				'id'       => '',
			),
			$atts,
			'pixva_3d_repair'
		);

		if ( ! function_exists( 'pixva_render_spline_3d' ) ) {
			return '';
		}

		pixva_enqueue_cinematic_assets( array( 'spline' ) );

		$settings = array(
			'url'        => '' !== trim( (string) $atts['url'] ) ? esc_url_raw( (string) $atts['url'] ) : '',
			'height'     => max( 12, (int) $atts['height'] ),
			'lazy'       => ! in_array( strtolower( (string) $atts['lazy'] ), array( 'no', 'false', '0' ), true ),
			'fallback'   => ! in_array( strtolower( (string) $atts['fallback'] ), array( 'no', 'false', '0' ), true ),
			'element_id' => sanitize_html_class( (string) $atts['id'] ),
		);

		foreach ( array(
			'badge'    => 'badge',
			'title'    => 'title',
			'subtitle' => 'subtitle',
			'neon'     => 'neon',
			'accent'   => 'accent',
		) as $from => $to ) {
			if ( '' !== trim( (string) $atts[ $from ] ) ) {
				$settings[ $to ] = (string) $atts[ $from ];
			}
		}

		$hotspots = pixva_shortcode_json( (string) $atts['hotspots'] );
		if ( ! empty( $hotspots ) ) {
			$settings['hotspots'] = $hotspots;
		}

		ob_start();
		pixva_render_spline_3d( $settings );
		return (string) ob_get_clean();
	}
	add_shortcode( 'pixva_3d_repair', 'pixva_3d_shortcode' );
}

if ( ! function_exists( 'pixva_ai_shortcode' ) ) {
	/**
	 * شورت‌کد عیب‌یاب هوشمند: [pixva_ai_diagnose pulse="2.4"].
	 *
	 * @param array|string $atts ویژگی‌های شورت‌کد.
	 * @return string
	 */
	function pixva_ai_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'badge'     => '',
				'title'     => '',
				'subtitle'  => '',
				'video'     => '',
				'mic'       => '',
				'submit'    => '',
				'privacy'   => '',
				'max_note'  => '',
				'accept'    => '',
				'phone'     => 'yes',
				'pulse'     => 2.4,
				'neon'      => '',
				'neon2'     => '',
				'id'        => '',
			),
			$atts,
			'pixva_ai_diagnose'
		);

		if ( ! function_exists( 'pixva_render_ai_diagnose' ) ) {
			return '';
		}

		pixva_enqueue_cinematic_assets( array( 'ai' ) );

		$settings = array(
			'show_phone' => ! in_array( strtolower( (string) $atts['phone'] ), array( 'no', 'false', '0' ), true ),
			'pulse'      => max( 0.8, (float) $atts['pulse'] ),
			'element_id' => sanitize_html_class( (string) $atts['id'] ),
		);

		if ( '' !== trim( (string) $atts['accept'] ) ) {
			$settings['accept'] = (string) $atts['accept'];
		}

		foreach ( array(
			'badge'    => 'badge',
			'title'    => 'title',
			'subtitle' => 'subtitle',
			'video'    => 'video_label',
			'mic'      => 'mic_label',
			'submit'   => 'submit',
			'privacy'  => 'privacy',
			'max_note' => 'max_note',
			'neon'     => 'neon',
			'neon2'    => 'neon2',
		) as $from => $to ) {
			if ( '' !== trim( (string) $atts[ $from ] ) ) {
				$settings[ $to ] = (string) $atts[ $from ];
			}
		}

		ob_start();
		pixva_render_ai_diagnose( $settings );
		return (string) ob_get_clean();
	}
	add_shortcode( 'pixva_ai_diagnose', 'pixva_ai_shortcode' );
}

if ( ! function_exists( 'pixva_tracker_shortcode' ) ) {
	/**
	 * شورت‌کد نقشه زنده تعمیرکار: [pixva_technician_tracker code="PXV-..."].
	 *
	 * @param array|string $atts ویژگی‌های شورت‌کد.
	 * @return string
	 */
	function pixva_tracker_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'badge'    => '',
				'title'    => '',
				'subtitle' => '',
				'code'     => '',
				'phone'    => '',
				'height'   => 26,
				'zoom'     => 14,
				'refresh'  => 20,
				'lookup'   => 'yes',
				'neon'     => '',
				'car'      => '',
				'id'       => '',
			),
			$atts,
			'pixva_technician_tracker'
		);

		if ( ! function_exists( 'pixva_render_technician_tracker' ) ) {
			return '';
		}

		pixva_enqueue_cinematic_assets( array( 'tracker' ) );

		$settings = array(
			'code'       => sanitize_text_field( (string) $atts['code'] ),
			'phone'      => sanitize_text_field( (string) $atts['phone'] ),
			'height'     => max( 14, (int) $atts['height'] ),
			'zoom'       => max( 3, min( 18, (int) $atts['zoom'] ) ),
			'refresh'    => max( 5, (int) $atts['refresh'] ),
			'lookup'     => ! in_array( strtolower( (string) $atts['lookup'] ), array( 'no', 'false', '0' ), true ),
			'element_id' => sanitize_html_class( (string) $atts['id'] ),
		);

		foreach ( array(
			'badge'    => 'badge',
			'title'    => 'title',
			'subtitle' => 'subtitle',
			'neon'     => 'neon',
			'car'      => 'car',
		) as $from => $to ) {
			if ( '' !== trim( (string) $atts[ $from ] ) ) {
				$settings[ $to ] = (string) $atts[ $from ];
			}
		}

		ob_start();
		pixva_render_technician_tracker( $settings );
		return (string) ob_get_clean();
	}
	add_shortcode( 'pixva_technician_tracker', 'pixva_tracker_shortcode' );
	add_shortcode( 'pixva_tech_tracker', 'pixva_tracker_shortcode' );
}

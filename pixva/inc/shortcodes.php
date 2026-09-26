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

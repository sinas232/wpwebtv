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

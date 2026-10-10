<?php
/**
 * Site announcement (notice bar): one owner-controlled setting, rendered
 * sitewide on wp_body_open and used as the default by the Elementor
 * «PIXVA — نوار اعلان» widget and the [pixva_notice] shortcode. Text is
 * never fabricated; when disabled or empty nothing prints.
 *
 * @package Pixva
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sanitize the announcement option (Settings API, server-side).
 *
 * @param mixed $value Raw value.
 * @return array{enabled:int,text:string,url:string}
 */
function pixva_sanitize_announcement( $value ) {
	$value = (array) $value;
	$text  = isset( $value['text'] ) ? sanitize_textarea_field( wp_unslash( $value['text'] ) ) : '';
	$url   = isset( $value['url'] ) ? esc_url_raw( wp_unslash( $value['url'] ) ) : '';
	return array(
		'enabled' => empty( $value['enabled'] ) ? 0 : 1,
		'text'    => $text,
		'url'     => $url,
	);
}
add_action(
	'admin_init',
	static function () {
		register_setting(
			'pixva_announcement',
			'pixva_announcement',
			array(
				'type'              => 'array',
				'sanitize_callback' => 'pixva_sanitize_announcement',
				'default'           => array(),
			)
		);
	}
);
add_filter(
	'option_page_capability_pixva_announcement',
	static function () {
		return 'pixva_manage_settings';
	}
);

/**
 * Stored announcement (safe defaults).
 *
 * @return array{enabled:int,text:string,url:string}
 */
function pixva_announcement_get() {
	$v = get_option( 'pixva_announcement', array() );
	$v = is_array( $v ) ? $v : array();
	return array(
		'enabled' => empty( $v['enabled'] ) ? 0 : 1,
		'text'    => (string) ( $v['text'] ?? '' ),
		'url'     => (string) ( $v['url'] ?? '' ),
	);
}

/**
 * Sitewide notice bar (early in the body so assistive tech reaches it first).
 *
 * @return void
 */
function pixva_render_announcement() {
	$a = pixva_announcement_get();
	if ( ! $a['enabled'] || '' === $a['text'] || is_admin() ) {
		return;
	}
	echo '<div class="pixva-notice-bar" role="status"><div class="container"><p>' . esc_html( $a['text'] );
	if ( '' !== $a['url'] ) {
		echo ' <a href="' . esc_url( $a['url'] ) . '">' . esc_html__( 'بیشتر بدانید', 'pixva' ) . '</a>';
	}
	echo '</p></div></div>';
}
add_action( 'wp_body_open', 'pixva_render_announcement', 0 );

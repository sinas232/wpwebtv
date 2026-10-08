<?php
/**
 * Configurable pricing (§08, §34).
 *
 * Option `pixva_pricing`:
 *  enabled      bool    master switch (default false)
 *  currency     string  display unit (default "تومان")
 *  services     key => {label, min, max, sized}
 *  sizes        key => {label, factor}
 *  brands       brand_post_id => factor (missing = 1)
 *  inspection   {min, max} optional inspection fee range
 *  disclaimer   text shown under every estimate
 *  updated      unix time of last save (shown as "prices updated on")
 *
 * There are NO built-in prices. Until an administrator enables pricing and
 * enters at least one service range, every estimate request answers
 * "inspection required" and offers booking. All maths is server-side.
 *
 * @package Pixva
 * @since   2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Empty default configuration.
 *
 * @return array
 */
function pixva_pricing_defaults() {
	return array(
		'enabled'    => false,
		'currency'   => __( 'تومان', 'pixva' ),
		'services'   => array(),
		'sizes'      => array(),
		'brands'     => array(),
		'inspection' => array(
			'min' => 0,
			'max' => 0,
		),
		'disclaimer' => '',
		'updated'    => 0,
	);
}

/**
 * Effective configuration.
 *
 * @return array
 */
function pixva_pricing() {
	$saved = get_option( 'pixva_pricing', array() );
	return wp_parse_args( is_array( $saved ) ? $saved : array(), pixva_pricing_defaults() );
}

/**
 * Whether online estimates are available (enabled + ≥1 priced service).
 *
 * @return bool
 */
function pixva_pricing_active() {
	$p = pixva_pricing();
	if ( empty( $p['enabled'] ) ) {
		return false;
	}
	foreach ( (array) $p['services'] as $s ) {
		if ( (int) $s['max'] > 0 ) {
			return true;
		}
	}
	return false;
}

/**
 * Service options for selects (key => label), priced services only.
 *
 * @return array<string,string>
 */
function pixva_pricing_service_options() {
	$out = array();
	foreach ( (array) pixva_pricing()['services'] as $key => $s ) {
		$out[ $key ] = (string) $s['label'];
	}
	return $out;
}

/**
 * Sanitize the pricing option.
 *
 * @param mixed $input Raw.
 * @return array
 */
function pixva_sanitize_pricing( $input ) {
	$input = is_array( $input ) ? $input : array();
	$out   = pixva_pricing_defaults();
	$int   = static fn( $v ) => max( 0, (int) preg_replace( '/[^0-9]/', '', pixva_en_num( (string) $v ) ) );
	$float = static function ( $v ) {
		$v = str_replace( '/', '.', pixva_en_num( (string) $v ) );
		return is_numeric( $v ) ? max( 0.1, min( 5.0, (float) $v ) ) : 1.0;
	};

	$out['enabled']    = ! empty( $input['enabled'] );
	$out['currency']   = sanitize_text_field( (string) ( $input['currency'] ?? $out['currency'] ) );
	$out['currency']   = '' === $out['currency'] ? __( 'تومان', 'pixva' ) : $out['currency'];
	$out['disclaimer'] = sanitize_textarea_field( (string) ( $input['disclaimer'] ?? '' ) );

	foreach ( (array) ( $input['services'] ?? array() ) as $row ) {
		$label = sanitize_text_field( (string) ( $row['label'] ?? '' ) );
		if ( '' === $label ) {
			continue;
		}
		$key = sanitize_key( (string) ( $row['key'] ?? '' ) );
		$key = '' === $key ? 's' . substr( md5( $label ), 0, 6 ) : $key;
		$min = $int( $row['min'] ?? 0 );
		$max = $int( $row['max'] ?? 0 );
		if ( $max < $min ) {
			$max = $min;
		}
		$out['services'][ $key ] = array(
			'label' => $label,
			'min'   => $min,
			'max'   => $max,
			'sized' => ! empty( $row['sized'] ),
		);
	}
	foreach ( (array) ( $input['sizes'] ?? array() ) as $row ) {
		$label = sanitize_text_field( (string) ( $row['label'] ?? '' ) );
		if ( '' === $label ) {
			continue;
		}
		$key                  = sanitize_key( (string) ( $row['key'] ?? '' ) );
		$key                  = '' === $key ? 'z' . substr( md5( $label ), 0, 6 ) : $key;
		$out['sizes'][ $key ] = array(
			'label'  => $label,
			'factor' => $float( $row['factor'] ?? 1 ),
		);
	}
	foreach ( (array) ( $input['brands'] ?? array() ) as $brand_id => $factor ) {
		$factor = trim( (string) $factor );
		if ( '' !== $factor && 'tv_brands' === get_post_type( absint( $brand_id ) ) ) {
			$out['brands'][ absint( $brand_id ) ] = $float( $factor );
		}
	}
	$out['inspection'] = array(
		'min' => $int( $input['inspection']['min'] ?? 0 ),
		'max' => $int( $input['inspection']['max'] ?? 0 ),
	);
	if ( $out['inspection']['max'] < $out['inspection']['min'] ) {
		$out['inspection']['max'] = $out['inspection']['min'];
	}
	$out['updated'] = time();
	return $out;
}

/**
 * Server-side estimate.
 *
 * @param string $service  Service key.
 * @param string $size     Size key ('' allowed).
 * @param int    $brand_id Brand post id (0 allowed).
 * @return array{available:bool,reason:string,min:int,max:int,currency:string,inspection:array,disclaimer:string,updated:string,label:string}
 */
function pixva_estimate( $service, $size = '', $brand_id = 0 ) {
	$p    = pixva_pricing();
	$base = array(
		'available'  => false,
		'reason'     => 'inspection',
		'min'        => 0,
		'max'        => 0,
		'currency'   => (string) $p['currency'],
		'inspection' => $p['inspection'],
		'disclaimer' => (string) $p['disclaimer'],
		'updated'    => $p['updated'] ? pixva_format_date( (int) $p['updated'] ) : '',
		'label'      => '',
	);
	if ( ! pixva_pricing_active() ) {
		$base['reason'] = 'disabled';
		return $base;
	}
	$service = sanitize_key( $service );
	$s       = $p['services'][ $service ] ?? null;
	if ( ! $s || (int) $s['max'] <= 0 ) {
		return $base;
	}
	$factor = 1.0;
	if ( ! empty( $s['sized'] ) && '' !== $size ) {
		if ( ! isset( $p['sizes'][ $size ] ) ) {
			$base['reason'] = 'invalid';
			return $base;
		}
		$factor *= (float) $p['sizes'][ $size ]['factor'];
	}
	if ( $brand_id && isset( $p['brands'][ $brand_id ] ) ) {
		$factor *= (float) $p['brands'][ $brand_id ];
	}
	$step              = 10000;
	$base['available'] = true;
	$base['reason']    = '';
	$base['label']     = (string) $s['label'];
	$base['min']       = (int) ( floor( ( (int) $s['min'] * $factor ) / $step ) * $step );
	$base['max']       = (int) ( ceil( ( (int) $s['max'] * $factor ) / $step ) * $step );
	return $base;
}

/**
 * Human-readable range: "۲,۵۰۰,۰۰۰ تا ۳,۰۰۰,۰۰۰ تومان".
 *
 * @param int    $min      Min.
 * @param int    $max      Max.
 * @param string $currency Unit.
 * @return string
 */
function pixva_format_range( $min, $max, $currency ) {
	if ( $min === $max ) {
		return pixva_price( $min ) . ' ' . $currency;
	}
	/* translators: 1: min, 2: max, 3: currency. */
	return sprintf( __( '%1$s تا %2$s %3$s', 'pixva' ), pixva_price( $min ), pixva_price( $max ), $currency );
}

/**
 * Import v1.x pricing (only if an administrator had saved that form). The
 * imported table is DISABLED so nothing renders until reviewed (§34).
 *
 * @return bool Whether something was imported.
 */
function pixva_import_legacy_pricing() {
	$legacy = get_option( 'pixva_pricing_settings', null );
	if ( ! is_array( $legacy ) || get_option( 'pixva_pricing', null ) !== null ) {
		return false;
	}
	$labels = array(
		'backlight'  => __( 'تعویض بک‌لایت', 'pixva' ),
		'powerboard' => __( 'تعمیر برد پاور', 'pixva' ),
		'mainboard'  => __( 'تعمیر برد اصلی', 'pixva' ),
		'panel'      => __( 'تعمیر پنل', 'pixva' ),
		'water'      => __( 'رفع آب‌خوردگی', 'pixva' ),
	);
	$p      = pixva_pricing_defaults();
	foreach ( (array) ( $legacy['services'] ?? array() ) as $key => $row ) {
		$key = sanitize_key( $key );
		if ( isset( $row['min'], $row['max'] ) ) {
			$p['services'][ $key ] = array(
				'label' => $labels[ $key ] ?? $key,
				'min'   => (int) $row['min'],
				'max'   => (int) $row['max'],
				'sized' => false,
			);
		}
	}
	$p['enabled'] = false;
	$p['updated'] = 0;
	add_option( 'pixva_pricing', $p, '', false );
	update_option( 'pixva_pricing_legacy_imported', 1, false );
	return true;
}

<?php
/**
 * Pure helpers shared by every module: input access, normalisation,
 * Persian digits, Jalali dates, formatting, icons.
 *
 * Nothing in this file has side effects (no hooks), except the heading-id
 * content filter at the bottom which is documented there.
 *
 * @package Pixva
 * @since   2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
 * ---------------------------------------------------------------------------
 * Request input
 * ---------------------------------------------------------------------------
 */

/**
 * Sanitized scalar from $_POST. Nonce verification is the caller's job.
 *
 * @param string $key     Field name.
 * @param string $fallback Default.
 * @return string
 */
function pixva_get_post_var( $key, $fallback = '' ) {
	if ( ! isset( $_POST[ $key ] ) || is_array( $_POST[ $key ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- verified by caller.
		return $fallback;
	}
	return sanitize_text_field( wp_unslash( $_POST[ $key ] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
}

/**
 * Sanitized multi-line text from $_POST.
 *
 * @param string $key Field name.
 * @return string
 */
function pixva_get_post_textarea( $key ) {
	if ( ! isset( $_POST[ $key ] ) || is_array( $_POST[ $key ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
		return '';
	}
	return sanitize_textarea_field( wp_unslash( $_POST[ $key ] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
}

/**
 * Sanitized array of keys from $_POST (checkbox groups).
 *
 * @param string $key Field name.
 * @return string[]
 */
function pixva_get_post_keys( $key ) {
	if ( ! isset( $_POST[ $key ] ) || ! is_array( $_POST[ $key ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
		return array();
	}
	return array_values( array_filter( array_map( 'sanitize_key', wp_unslash( $_POST[ $key ] ) ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
}

/**
 * Sanitized scalar from $_GET (read-only views).
 *
 * @param string $key     Field name.
 * @param string $fallback Default.
 * @return string
 */
function pixva_get_request_var( $key, $fallback = '' ) {
	if ( ! isset( $_GET[ $key ] ) || is_array( $_GET[ $key ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read only.
		return $fallback;
	}
	return sanitize_text_field( wp_unslash( $_GET[ $key ] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
}

/**
 * Array of keys from $_GET (read-only, e.g. symptom checkboxes or a CSV list).
 *
 * @param string $key Field name.
 * @return string[]
 */
function pixva_get_request_keys( $key ) {
	if ( ! isset( $_GET[ $key ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return array();
	}
	$raw = wp_unslash( $_GET[ $key ] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized below.
	// Accept both key[]=a&key[]=b and the compact key=a,b form (shareable URLs).
	$raw = is_array( $raw ) ? $raw : explode( ',', (string) $raw );
	return array_values( array_unique( array_filter( array_map( 'sanitize_key', $raw ) ) ) );
}

/*
 * ---------------------------------------------------------------------------
 * Strings, numbers, phones
 * ---------------------------------------------------------------------------
 */

/**
 * Multibyte-safe length.
 *
 * @param string $text Text.
 * @return int
 */
function pixva_strlen( $text ) {
	$text = (string) $text;
	return function_exists( 'mb_strlen' ) ? (int) mb_strlen( $text ) : strlen( $text );
}

/**
 * Multibyte-safe substring.
 *
 * @param string $text   Text.
 * @param int    $start  Start.
 * @param int    $length Length.
 * @return string
 */
function pixva_substr( $text, $start, $length ) {
	$text = (string) $text;
	return function_exists( 'mb_substr' ) ? mb_substr( $text, $start, $length ) : substr( $text, $start, $length );
}

/**
 * Western → Persian digits.
 *
 * @param mixed $value Value.
 * @return string
 */
function pixva_fa_num( $value ) {
	return strtr(
		(string) $value,
		array(
			'0' => '۰',
			'1' => '۱',
			'2' => '۲',
			'3' => '۳',
			'4' => '۴',
			'5' => '۵',
			'6' => '۶',
			'7' => '۷',
			'8' => '۸',
			'9' => '۹',
		)
	);
}

/**
 * Persian/Arabic → Western digits (for parsing user input).
 *
 * @param string $value Value.
 * @return string
 */
function pixva_en_num( $value ) {
	return strtr(
		(string) $value,
		array(
			'۰' => '0',
			'۱' => '1',
			'۲' => '2',
			'۳' => '3',
			'۴' => '4',
			'۵' => '5',
			'۶' => '6',
			'۷' => '7',
			'۸' => '8',
			'۹' => '9',
			'٠' => '0',
			'١' => '1',
			'٢' => '2',
			'٣' => '3',
			'٤' => '4',
			'٥' => '5',
			'٦' => '6',
			'٧' => '7',
			'٨' => '8',
			'٩' => '9',
		)
	);
}

/**
 * Integer amount formatted with Persian digits and thousands separators.
 *
 * @param int $amount Amount.
 * @return string
 */
function pixva_price( $amount ) {
	return pixva_fa_num( number_format( (int) $amount ) );
}

/**
 * Normalise an Iranian mobile number to 09xxxxxxxxx (accepts Persian digits, +98, 98).
 *
 * @param string $mobile Input.
 * @return string
 */
function pixva_normalize_mobile( $mobile ) {
	$mobile = preg_replace( '/[^0-9]/', '', pixva_en_num( (string) $mobile ) );
	if ( 12 === strlen( $mobile ) && 0 === strpos( $mobile, '989' ) ) {
		$mobile = '0' . substr( $mobile, 2 );
	} elseif ( 10 === strlen( $mobile ) && 0 === strpos( $mobile, '9' ) ) {
		$mobile = '0' . $mobile;
	}
	return $mobile;
}

/**
 * Whether input is a valid Iranian mobile number.
 *
 * @param string $mobile Input.
 * @return bool
 */
function pixva_is_valid_iranian_mobile( $mobile ) {
	return (bool) preg_match( '/^09[0-9]{9}$/', pixva_normalize_mobile( $mobile ) );
}

/**
 * Mask a phone for staff views that must not see full PII.
 *
 * @param string $phone Phone.
 * @return string
 */
function pixva_mask_phone( $phone ) {
	$phone = pixva_normalize_mobile( $phone );
	if ( strlen( $phone ) < 8 ) {
		return '';
	}
	return pixva_fa_num( substr( $phone, 0, 4 ) . '•••' . substr( $phone, -3 ) );
}

/**
 * Phone → tel: href value. Returns '' for empty input.
 *
 * @param string $phone Phone.
 * @return string
 */
function pixva_tel_href( $phone ) {
	$digits = preg_replace( '/[^0-9+]/', '', pixva_en_num( (string) $phone ) );
	return '' === $digits ? '' : 'tel:' . $digits;
}

/**
 * Allowed HTML for rich content saved by staff (no script/iframe/form).
 *
 * @return array
 */
function pixva_kses_content() {
	$allowed = wp_kses_allowed_html( 'post' );
	unset( $allowed['script'], $allowed['iframe'], $allowed['form'], $allowed['input'], $allowed['style'] );
	return $allowed;
}

/*
 * ---------------------------------------------------------------------------
 * Dates
 * ---------------------------------------------------------------------------
 */

/**
 * Gregorian → Jalali (algorithmic, independent of language packs).
 *
 * @param int $gy Year.
 * @param int $gm Month.
 * @param int $gd Day.
 * @return int[] Jalali [y, m, d].
 */
function pixva_gregorian_to_jalali( $gy, $gm, $gd ) {
	$g_d_m = array( 0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334 );
	$gy2   = ( $gm > 2 ) ? ( $gy + 1 ) : $gy;
	$days  = 355666 + ( 365 * $gy ) + (int) ( ( $gy2 + 3 ) / 4 ) - (int) ( ( $gy2 + 99 ) / 100 ) + (int) ( ( $gy2 + 399 ) / 400 ) + $gd + $g_d_m[ $gm - 1 ];
	$jy    = -1595 + ( 33 * (int) ( $days / 12053 ) );
	$days %= 12053;
	$jy   += 4 * (int) ( $days / 1461 );
	$days %= 1461;
	if ( $days > 365 ) {
		--$days;
		$jy   += (int) ( $days / 365 );
		$days %= 365;
	}
	if ( $days < 186 ) {
		$jm = 1 + (int) ( $days / 31 );
		$jd = 1 + ( $days % 31 );
	} else {
		$jm = 7 + (int) ( ( $days - 186 ) / 30 );
		$jd = 1 + ( ( $days - 186 ) % 30 );
	}
	return array( $jy, $jm, $jd );
}

/**
 * Jalali date with Persian digits, e.g. "۱۶ مهر ۱۴۰۵".
 *
 * @param int $timestamp Unix timestamp.
 * @return string
 */
function pixva_format_date( $timestamp ) {
	$timestamp = (int) $timestamp;
	if ( $timestamp <= 0 ) {
		return '';
	}
	$months = array(
		1 => 'فروردین',
		'اردیبهشت',
		'خرداد',
		'تیر',
		'مرداد',
		'شهریور',
		'مهر',
		'آبان',
		'آذر',
		'دی',
		'بهمن',
		'اسفند',
	);
	$p      = pixva_gregorian_to_jalali( (int) wp_date( 'Y', $timestamp ), (int) wp_date( 'n', $timestamp ), (int) wp_date( 'j', $timestamp ) );
	return pixva_fa_num( $p[2] . ' ' . ( $months[ $p[1] ] ?? '' ) . ' ' . $p[0] );
}

/*
 * ---------------------------------------------------------------------------
 * Content helpers
 * ---------------------------------------------------------------------------
 */

/**
 * Estimated reading time in minutes (≈180 Persian words/minute).
 *
 * @param string $content HTML.
 * @return int
 */
function pixva_reading_time( $content ) {
	$words = preg_split( '/[\s،.!؟؛:]+/u', wp_strip_all_tags( (string) $content ), -1, PREG_SPLIT_NO_EMPTY );
	return max( 1, (int) ceil( ( is_array( $words ) ? count( $words ) : 0 ) / 180 ) );
}

/**
 * Add unique ids to h2–h4 in long-form content so a TOC can link to them.
 * Duplicate heading texts get a numeric suffix (valid, unique ids).
 *
 * @param string $content HTML.
 * @return string
 */
function pixva_heading_ids( $content ) {
	if ( ! is_singular() || ! in_the_loop() || ! is_main_query() ) {
		return $content;
	}
	$used = array();
	return preg_replace_callback(
		'/<(h[2-4])([^>]*)>(.*?)<\/\1>/is',
		static function ( $m ) use ( &$used ) {
			if ( false !== stripos( $m[2], 'id=' ) ) {
				return $m[0];
			}
			$slug = sanitize_title( wp_strip_all_tags( $m[3] ) );
			$slug = '' === $slug ? 'section' : $slug;
			$base = $slug;
			$i    = 2;
			while ( isset( $used[ $slug ] ) ) {
				$slug = $base . '-' . ( $i++ );
			}
			$used[ $slug ] = true;
			return sprintf( '<%1$s id="%2$s"%3$s>%4$s</%1$s>', strtolower( $m[1] ), esc_attr( $slug ), $m[2], $m[3] );
		},
		$content
	);
}
add_filter( 'the_content', 'pixva_heading_ids', 5 );

/**
 * Build a table of contents from h2/h3 that carry ids. Returns '' when fewer
 * than three headings exist (a TOC for short content is noise).
 *
 * @param string $content Filtered HTML.
 * @return string Safe HTML.
 */
function pixva_build_toc( $content ) {
	if ( ! preg_match_all( '/<h([23]) id="([^"]+)"[^>]*>(.*?)<\/h\1>/is', (string) $content, $matches, PREG_SET_ORDER ) || count( $matches ) < 3 ) {
		return '';
	}
	$items = '';
	foreach ( $matches as $m ) {
		$items .= sprintf( '<li class="toc__item toc__item--l%1$d"><a href="#%2$s">%3$s</a></li>', (int) $m[1], esc_attr( $m[2] ), esc_html( wp_strip_all_tags( $m[3] ) ) );
	}
	return '<nav class="toc" aria-labelledby="toc-title"><details open><summary id="toc-title">' . esc_html__( 'فهرست مطالب', 'pixva' ) . '</summary><ol>' . $items . '</ol></details></nav>';
}

/*
 * ---------------------------------------------------------------------------
 * Icons (inline SVG, decorative → aria-hidden)
 * ---------------------------------------------------------------------------
 */

/**
 * Inline 24px line icon. Always decorative; pair with visible text or an
 * aria-label on the parent control.
 *
 * @param string $name Icon key.
 * @return string Safe SVG markup.
 */
function pixva_icon( $name ) {
	static $paths = array(
		'phone'    => '<path d="M7 3.5h3.2l1.2 3-2 1.2a12.5 12.5 0 0 0 6 6l1.2-2 3 1.2V16a2 2 0 0 1-2.2 2A16.5 16.5 0 0 1 5 7.7 2 2 0 0 1 7 3.5z"/>',
		'menu'     => '<path d="M4 7h16M4 12h16M4 17h16"/>',
		'close'    => '<path d="M6 6l12 12M18 6L6 18"/>',
		'search'   => '<circle cx="11" cy="11" r="6"/><path d="M16 16l4 4"/>',
		'shield'   => '<path d="M12 3l7 3v6c0 4.5-3 7-7 9-4-2-7-4.5-7-9V6l7-3z"/>',
		'pin'      => '<path d="M12 21s6-5.2 6-10a6 6 0 1 0-12 0c0 4.8 6 10 6 10z"/><circle cx="12" cy="11" r="2"/>',
		'check'    => '<path d="M5 12.5l4.2 4.2L19 7"/>',
		'arrow'    => '<path d="M15 6l-6 6 6 6"/>',
		'chevron'  => '<path d="M6 9l6 6 6-6"/>',
		'tool'     => '<path d="M14 7a4 4 0 0 0-5.7 5.3L4 16.6 7.4 20l4.3-4.3A4 4 0 0 0 17 10l-3 1-1-1 1-3z"/>',
		'panel'    => '<rect x="3" y="5" width="18" height="12" rx="2"/><path d="M8 21h8M12 17v4"/>',
		'pulse'    => '<path d="M3 12h4l2-5 4 10 2-5h6"/>',
		'calc'     => '<rect x="5" y="3" width="14" height="18" rx="2"/><path d="M8 7h8M8 11h2M12 11h2M16 11v6M8 15h2M12 15h2"/>',
		'grid'     => '<rect x="4" y="4" width="7" height="7" rx="1"/><rect x="13" y="4" width="7" height="7" rx="1"/><rect x="4" y="13" width="7" height="7" rx="1"/><rect x="13" y="13" width="7" height="7" rx="1"/>',
		'code'     => '<path d="M9 8l-4 4 4 4M15 8l4 4-4 4"/>',
		'calendar' => '<rect x="4" y="5" width="16" height="15" rx="2"/><path d="M4 10h16M9 3v4M15 3v4"/>',
		'track'    => '<circle cx="12" cy="12" r="8"/><path d="M12 8v4l3 2"/>',
		'user'     => '<circle cx="12" cy="8" r="3"/><path d="M5 19c1.4-3 3.8-4.5 7-4.5S17.6 16 19 19"/>',
		'book'     => '<path d="M5 5.5A3.5 3.5 0 0 1 8.5 4H20v15H8.5A3.5 3.5 0 0 0 5 22.5z"/><path d="M5 5.5V22"/>',
		'chat'     => '<path d="M5 6.5A2.5 2.5 0 0 1 7.5 4h9A2.5 2.5 0 0 1 19 6.5v6A2.5 2.5 0 0 1 16.5 15H10l-4 3.5V15H7.5A2.5 2.5 0 0 1 5 12.5z"/>',
		'alert'    => '<path d="M12 4l9 16H3z"/><path d="M12 10v4M12 17v.5"/>',
		'info'     => '<circle cx="12" cy="12" r="8"/><path d="M12 11v5M12 8v.5"/>',
		'expand'   => '<path d="M4 9V4h5M20 9V4h-5M4 15v5h5M20 15v5h-5"/>',
		'mail'     => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 7l9 6 9-6"/>',
		'clock'    => '<circle cx="12" cy="12" r="8"/><path d="M12 8v5l3 2"/>',
		'logout'   => '<path d="M10 5H6a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1h4M14 8l-4 4 4 4M10 12h10"/>',
		/* v2.1.0 visual system — problem categories, tools, journey, UI */
		'tv'       => '<rect x="3" y="4.5" width="18" height="12.5" rx="2"/><path d="M9 21h6M12 17v4"/>',
		'picture'  => '<rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="9" cy="9.5" r="1.5"/><path d="M3.5 17.5l4.7-4.7 3.3 3.3 3-3 6 5.4"/>',
		'sound'    => '<path d="M4 9.5v5h3.5L12 18.5v-13L7.5 9.5H4z"/><path d="M15.5 9.2a4.2 4.2 0 0 1 0 5.6M18 6.8a7.6 7.6 0 0 1 0 10.4"/>',
		'power'    => '<path d="M12 3.5V11"/><path d="M7.2 6.4a7.5 7.5 0 1 0 9.6 0"/>',
		'lines'    => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="M6.5 9.5h11M6.5 12.5h11M6.5 15.5h7"/>',
		'blink'    => '<circle cx="12" cy="12" r="8"/><circle cx="12" cy="12" r="2.4"/>',
		'sun'      => '<circle cx="12" cy="12" r="3.6"/><path d="M12 3.5v2M12 18.5v2M3.5 12h2M18.5 12h2M6 6l1.4 1.4M16.6 16.6L18 18M18 6l-1.4 1.4M7.4 16.6L6 18"/>',
		'droplet'  => '<path d="M12 3.5s6 6.4 6 10.1a6 6 0 1 1-12 0c0-3.7 6-10.1 6-10.1z"/>',
		'crack'    => '<rect x="3" y="4" width="18" height="16" rx="2"/><path d="M12.5 4l-2 4.5 3 3-4.5 4 2.5 4.5"/>',
		'refresh'  => '<path d="M20 12a8 8 0 1 1-2.4-5.7"/><path d="M20 3.5V8h-4.5"/>',
		'cpu'      => '<rect x="7" y="7" width="10" height="10" rx="2"/><path d="M10 2.5v3M14 2.5v3M10 18.5v3M14 18.5v3M2.5 10h3M2.5 14h3M18.5 10h3M18.5 14h3"/>',
		'remote'   => '<rect x="8" y="3" width="8" height="18" rx="4"/><path d="M12 7.5h.01M12 11.5h.01M12 15.5h.01"/>',
		'signal'   => '<path d="M4.5 11.5a10 10 0 0 1 15 0"/><path d="M7.5 14.7a6 6 0 0 1 9 0"/><circle cx="12" cy="18.3" r="1.2"/>',
		'bolt'     => '<path d="M13 3L5.5 13.5H11L10 21l7.5-10.5H12z"/>',
		'route'    => '<circle cx="6" cy="18" r="2.4"/><circle cx="18" cy="6" r="2.4"/><path d="M8.4 18H15a3.5 3.5 0 0 0 0-7H9a3.5 3.5 0 0 1 0-7h6.6"/>',
		'arrow-back' => '<path d="M9 6l6 6-6 6"/>',
		'plus'     => '<path d="M12 5.5v13M5.5 12h13"/>',
		'upload'   => '<path d="M12 15.5V4M7.5 8.5L12 4l4.5 4.5"/><path d="M4 19.5h16"/>',
		'camera'   => '<rect x="3" y="7" width="18" height="13" rx="2"/><path d="M9 7l1.3-2.2h3.4L15 7"/><circle cx="12" cy="13.2" r="3.4"/>',
		'file'     => '<path d="M6.5 3h7L18 7.5V21h-11.5z"/><path d="M13.5 3v4.5H18"/>',
		'headset'  => '<path d="M4.5 13.5a7.5 7.5 0 0 1 15 0"/><rect x="3.5" y="13" width="4" height="6" rx="1.6"/><rect x="16.5" y="13" width="4" height="6" rx="1.6"/>',
	);
	if ( ! isset( $paths[ $name ] ) ) {
		return '';
	}
	return '<svg class="icon" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $paths[ $name ] . '</svg>';
}

/**
 * Allowed tags for pixva_icon output when passed through wp_kses.
 *
 * @return array
 */
function pixva_svg_allowed() {
	$shape = array(
		'd'      => true,
		'cx'     => true,
		'cy'     => true,
		'r'      => true,
		'x'      => true,
		'y'      => true,
		'width'  => true,
		'height' => true,
		'rx'     => true,
	);
	return array(
		'svg'    => array(
			'class'           => true,
			'width'           => true,
			'height'          => true,
			'viewbox'         => true,
			'fill'            => true,
			'stroke'          => true,
			'stroke-width'    => true,
			'stroke-linecap'  => true,
			'stroke-linejoin' => true,
			'aria-hidden'     => true,
			'focusable'       => true,
		),
		'path'   => $shape,
		'circle' => $shape,
		'rect'   => $shape,
	);
}

/**
 * Current request path relative to home, with leading and trailing slash.
 * Example: "/tools/diagnosis/".
 *
 * @return string
 */
function pixva_request_path() {
	$uri  = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : '/'; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- normalised below.
	$path = (string) wp_parse_url( $uri, PHP_URL_PATH );
	$home = (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH );
	if ( '' !== $home && '/' !== $home && 0 === strpos( $path, rtrim( $home, '/' ) ) ) {
		$path = substr( $path, strlen( rtrim( $home, '/' ) ) );
	}
	return pixva_normalize_path( rawurldecode( $path ) );
}

/**
 * Normalise a path to "/a/b/" form (lowercase not enforced: slugs may be Persian).
 *
 * @param string $path Path.
 * @return string
 */
function pixva_normalize_path( $path ) {
	$path = '/' . trim( (string) $path, "/ \t\n\r" );
	$path = preg_replace( '#/+#', '/', $path );
	return '/' === $path ? '/' : $path . '/';
}

/**
 * JSON-encode a value for storage in post meta.
 *
 * Note: update_post_meta()/meta_input run wp_unslash() on the value, which would
 * strip the backslashes of JSON escapes (\" and \uXXXX) and corrupt the
 * document. The result is therefore pre-slashed.
 *
 * @param mixed $value Value.
 * @return string
 */
function pixva_json_meta( $value ) {
	return wp_slash( (string) wp_json_encode( $value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) );
}

<?php
/**
 * Redirect manager & gone (410) handling (§23, §24).
 *
 * Layers, evaluated once per request on template_redirect (priority 1):
 *  1. Custom rules (option `pixva_redirects`): exact source path → destination
 *     with status 301/302/307/308/410. Each rule: id, source, destination,
 *     status, created, notes, active. Saving FLATTENS chains (A→B + B→C
 *     becomes A→C) and REJECTS loops, so every redirect is a single hop.
 *  2. System legacy map (v1.x URLs → v2 routes), exact and prefix based.
 *  3. Gone list: system prefixes for retired sections + paths of content
 *     that was removed (`pixva_gone_paths`, maintained automatically).
 *     Applied only when WordPress would otherwise return 404.
 *  4. Old post permalinks (/{slug}/ → /blog/{slug}/) by EXACT slug match;
 *     WordPress' fuzzy 404 guessing is disabled (no wrong-target redirects).
 *
 * If a redirect target is itself gone, the source answers 410 directly
 * (no redirect-to-410 chain).
 *
 * @package Pixva
 * @since   2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Allowed statuses.
 *
 * @return array<int,string>
 */
function pixva_redirect_statuses() {
	return array(
		301 => __( '۳۰۱ — انتقال دائمی', 'pixva' ),
		302 => __( '۳۰۲ — انتقال موقت', 'pixva' ),
		307 => __( '۳۰۷ — انتقال موقت (حفظ متد)', 'pixva' ),
		308 => __( '۳۰۸ — انتقال دائمی (حفظ متد)', 'pixva' ),
		410 => __( '۴۱۰ — حذف دائمی محتوا', 'pixva' ),
	);
}

/**
 * Stored custom rules.
 *
 * @return array<int,array>
 */
function pixva_get_redirects() {
	$rules = get_option( 'pixva_redirects', array() );
	return is_array( $rules ) ? array_values( $rules ) : array();
}

/**
 * Normalise a destination: internal → "/path/" (query kept), external → URL.
 *
 * @param string $dest Destination.
 * @return string '' when invalid.
 */
function pixva_normalize_destination( $dest ) {
	$dest = trim( (string) $dest );
	if ( '' === $dest ) {
		return '';
	}
	$home_host = (string) wp_parse_url( home_url(), PHP_URL_HOST );
	if ( preg_match( '#^https?://#i', $dest ) ) {
		$host = (string) wp_parse_url( $dest, PHP_URL_HOST );
		if ( strtolower( $host ) !== strtolower( $home_host ) ) {
			return esc_url_raw( $dest, array( 'http', 'https' ) );
		}
		$path  = (string) wp_parse_url( $dest, PHP_URL_PATH );
		$query = (string) wp_parse_url( $dest, PHP_URL_QUERY );
		$dest  = $path . ( '' !== $query ? '?' . $query : '' );
	}
	if ( '/' !== substr( $dest, 0, 1 ) ) {
		return '';
	}
	$parts = explode( '?', $dest, 2 );
	return pixva_normalize_path( $parts[0] ) . ( isset( $parts[1] ) ? '?' . $parts[1] : '' );
}

/**
 * Internal path portion of a normalised destination ('' for external).
 *
 * @param string $dest Destination.
 * @return string
 */
function pixva_destination_path( $dest ) {
	if ( '' === $dest || '/' !== substr( $dest, 0, 1 ) ) {
		return '';
	}
	return explode( '?', $dest, 2 )[0];
}

/**
 * Validate & insert/update a rule, flattening chains and rejecting loops.
 *
 * @param array $rule  Rule (source, destination, status, notes, active, id?).
 * @param array $rules Existing rules (by ref; updated on success).
 * @return true|WP_Error
 */
function pixva_redirect_upsert( $rule, &$rules ) {
	$source = pixva_normalize_path( (string) ( $rule['source'] ?? '' ) );
	$status = (int) ( $rule['status'] ?? 301 );
	$dest   = 410 === $status ? '' : pixva_normalize_destination( (string) ( $rule['destination'] ?? '' ) );
	$id     = sanitize_key( (string) ( $rule['id'] ?? '' ) );

	if ( '/' === $source || 0 === strpos( $source, '/wp-' ) ) {
		return new WP_Error( 'source', __( 'مسیر مبدأ مجاز نیست.', 'pixva' ) );
	}
	if ( ! isset( pixva_redirect_statuses()[ $status ] ) ) {
		return new WP_Error( 'status', __( 'کد وضعیت نامعتبر است.', 'pixva' ) );
	}
	if ( 410 !== $status && '' === $dest ) {
		return new WP_Error( 'destination', __( 'مقصد باید مسیر داخلی (با / شروع شود) یا نشانی کامل باشد.', 'pixva' ) );
	}
	foreach ( pixva_routes() as $route ) {
		if ( 'redirect' !== $route['source'] && $route['path'] === $source ) {
			return new WP_Error( 'route', __( 'این مسیر یکی از صفحه‌های اصلی سایت است و نمی‌توان آن را منتقل کرد.', 'pixva' ) );
		}
	}

	// Flatten: follow destination through existing active rules.
	$active = array();
	foreach ( $rules as $r ) {
		if ( ! empty( $r['active'] ) && $r['id'] !== $id ) {
			$active[ $r['source'] ] = $r;
		}
	}
	if ( isset( $active[ $source ] ) ) {
		return new WP_Error( 'duplicate', __( 'برای این مبدأ قبلاً قانون فعال ثبت شده است.', 'pixva' ) );
	}
	$seen = array( $source => true );
	$hops = 0;
	while ( '' !== pixva_destination_path( $dest ) && isset( $active[ pixva_destination_path( $dest ) ] ) && $hops < 20 ) {
		$next = $active[ pixva_destination_path( $dest ) ];
		if ( 410 === (int) $next['status'] ) {
			$status = 410;
			$dest   = '';
			break;
		}
		$dest = $next['destination'];
		++$hops;
		if ( isset( $seen[ pixva_destination_path( $dest ) ] ) ) {
			return new WP_Error( 'loop', __( 'این قانون حلقه انتقال ایجاد می‌کند.', 'pixva' ) );
		}
		$seen[ pixva_destination_path( $dest ) ] = true;
	}
	if ( '' !== $dest && pixva_destination_path( $dest ) === $source ) {
		return new WP_Error( 'loop', __( 'مبدأ و مقصد یکسان است.', 'pixva' ) );
	}

	$row = array(
		'id'          => $id ? $id : substr( md5( $source . microtime() ), 0, 12 ),
		'source'      => $source,
		'destination' => $dest,
		'status'      => $status,
		'created'     => (int) ( $rule['created'] ?? time() ),
		'notes'       => sanitize_text_field( (string) ( $rule['notes'] ?? '' ) ),
		'active'      => ! isset( $rule['active'] ) || ! empty( $rule['active'] ),
	);

	// Re-point existing rules that targeted this source (X→source becomes X→dest).
	foreach ( $rules as $i => $r ) {
		if ( $r['id'] === $row['id'] ) {
			unset( $rules[ $i ] );
			continue;
		}
		if ( $row['active'] && ! empty( $r['active'] ) && pixva_destination_path( (string) $r['destination'] ) === $source ) {
			$rules[ $i ]['destination'] = $row['destination'];
			$rules[ $i ]['status']      = 410 === $row['status'] ? 410 : (int) $r['status'];
			$rules[ $i ]['notes']       = trim( $r['notes'] . ' ' . __( '[زنجیره به‌صورت خودکار یک‌مرحله‌ای شد]', 'pixva' ) );
		}
	}
	$rules[] = $row;
	$rules   = array_values( $rules );
	return true;
}

/**
 * Exact system redirects from v1.x (path => route key).
 *
 * @return array<string,string>
 */
function pixva_system_redirects() {
	return array(
		'/calculator/'        => 'price_calculator',
		'/rates/'             => 'price_calculator',
		'/diagnosis/'         => 'diagnosis',
		'/technician/'        => 'dashboard',
		'/client-hub/'        => 'account',
		'/tools/error-codes/' => 'error_codes',
		'/error-database/'    => 'error_codes',
		'/cases/'             => 'portfolio',
		'/problem/'           => 'problems',
	);
}

/**
 * Prefix system redirects (old base → new base, keeps the remainder).
 *
 * @return array<string,string>
 */
function pixva_system_prefix_redirects() {
	return array(
		'/cases/'          => '/portfolio/',
		'/problem/'        => '/problems/',
		'/error-database/' => '/error-codes/',
	);
}

/**
 * Version 1.x used /%postname%/ with the default /category/ and /tag/ bases; v2
 * moves posts under /blog/, which moves the taxonomy bases with them. Map
 * the old archive URLs (only when the old base is no longer live and the
 * term still exists, so nothing redirects into a 404 or a loop).
 *
 * @param string $path Normalised request path.
 * @return string Destination path or ''.
 */
function pixva_legacy_taxonomy_redirect( $path ) {
	$map = array(
		'category' => '/category/',
		'post_tag' => '/tag/',
	);
	foreach ( $map as $taxonomy => $old ) {
		if ( 0 !== strpos( $path, $old ) ) {
			continue;
		}
		$parts = array_values( array_filter( explode( '/', substr( $path, strlen( $old ) ) ) ) );
		if ( ! $parts ) {
			return '';
		}
		$term = get_term_by( 'slug', sanitize_title( rawurldecode( end( $parts ) ) ), $taxonomy );
		if ( ! $term instanceof WP_Term ) {
			return '';
		}
		$link = get_term_link( $term );
		$rel  = is_wp_error( $link ) ? '' : wp_make_link_relative( $link );
		if ( '' === $rel || 0 === strpos( $rel, $old ) ) {
			return ''; // Old base is still the live base.
		}
		return $rel; // Relative: goes through wp_safe_redirect().
	}
	return '';
}

/**
 * Retired sections that answer 410 (exact paths and prefixes).
 *
 * @return array{exact:string[],prefix:string[]}
 */
function pixva_system_gone() {
	return array(
		'exact'  => array( '/b2b/', '/parts-stock/', '/parts/', '/branches/', '/tech/' ),
		'prefix' => array( '/parts/', '/branches/', '/tech/' ),
	);
}

/**
 * Whether a path is gone (system list or removed content).
 *
 * @param string $path Normalised path.
 * @return bool
 */
function pixva_is_gone_path( $path ) {
	$sys = pixva_system_gone();
	if ( in_array( $path, $sys['exact'], true ) ) {
		return true;
	}
	foreach ( $sys['prefix'] as $prefix ) {
		if ( 0 === strpos( $path, $prefix ) ) {
			return true;
		}
	}
	$gone = get_option( 'pixva_gone_paths', array() );
	return is_array( $gone ) && isset( $gone[ $path ] );
}

/**
 * Send a 410 response with the 404 template content (useful page, §24).
 *
 * @return void
 */
function pixva_send_gone() {
	global $wp_query;
	$wp_query->set_404();
	$GLOBALS['pixva_is_gone'] = true;
	status_header( 410 );
	nocache_headers();
	header( 'X-Robots-Tag: noindex' );
}

/**
 * Perform a redirect to a normalised destination with one hop.
 *
 * @param string $dest   Destination (internal path or URL).
 * @param int    $status Status.
 * @return void
 */
function pixva_do_redirect( $dest, $status ) {
	$url = '/' === substr( $dest, 0, 1 ) ? home_url( $dest ) : $dest;
	if ( '/' === substr( $dest, 0, 1 ) ) {
		wp_safe_redirect( $url, $status, 'PIXVA' );
	} else {
		wp_redirect( $url, $status, 'PIXVA' ); // phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect -- external targets are set by administrators.
	}
	exit;
}

/**
 * Resolve the current request against all layers.
 *
 * @return void
 */
function pixva_handle_redirects() {
	if ( is_admin() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) || is_preview() ) {
		return;
	}
	$path = pixva_request_path();
	if ( '/' === $path ) {
		return;
	}

	// 1) Custom rules.
	foreach ( pixva_get_redirects() as $rule ) {
		if ( empty( $rule['active'] ) || $rule['source'] !== $path ) {
			continue;
		}
		if ( 410 === (int) $rule['status'] ) {
			pixva_send_gone();
			return;
		}
		$dpath = pixva_destination_path( $rule['destination'] );
		if ( '' !== $dpath && pixva_is_gone_path( $dpath ) ) {
			pixva_send_gone();
			return;
		}
		pixva_do_redirect( $rule['destination'], (int) $rule['status'] );
	}

	// 2) System legacy redirects — only when the old URL is not live content.
	if ( is_404() ) {
		$exact = pixva_system_redirects();
		if ( isset( $exact[ $path ] ) ) {
			pixva_do_redirect( pixva_destination_path( pixva_normalize_destination( pixva_route_url( $exact[ $path ] ) ) ), 301 );
		}
		foreach ( pixva_system_prefix_redirects() as $old => $new ) {
			if ( 0 === strpos( $path, $old ) && strlen( $path ) > strlen( $old ) ) {
				$target = $new . substr( $path, strlen( $old ) );
				if ( pixva_is_gone_path( $target ) ) {
					pixva_send_gone();
					return;
				}
				pixva_do_redirect( $target, 301 );
			}
		}
		$tax_target = pixva_legacy_taxonomy_redirect( $path );
		if ( '' !== $tax_target ) {
			pixva_do_redirect( $tax_target, 301 );
		}
	}

	if ( ! is_404() ) {
		return;
	}

	// 3) Gone.
	if ( pixva_is_gone_path( $path ) ) {
		pixva_send_gone();
		return;
	}

	// 4) Old post permalinks: /{slug}/ → /blog/{slug}/ (exact slug only).
	if ( false === strpos( trim( $path, '/' ), '/' ) ) {
		$post = get_page_by_path( trim( $path, '/' ), OBJECT, 'post' );
		if ( $post && 'publish' === $post->post_status ) {
			wp_safe_redirect( get_permalink( $post ), 301, 'PIXVA' );
			exit;
		}
	}
}
add_action( 'template_redirect', 'pixva_handle_redirects', 1 );

add_filter( 'strict_redirect_guess_404_permalink', '__return_true' );

/*
 * ---------------------------------------------------------------------------
 * Automatic gone tracking for removed public content.
 * ---------------------------------------------------------------------------
 */

/**
 * Public post types whose removal should produce 410.
 *
 * @return string[]
 */
function pixva_gone_tracked_types() {
	return array( 'post', 'page', 'tv_services', 'tv_brands', 'tv_model', 'pixva_error', 'repair_cases' );
}

/**
 * Record a path as gone.
 *
 * @param string $path Path.
 * @return void
 */
function pixva_mark_gone( $path ) {
	$path = pixva_normalize_path( $path );
	if ( '/' === $path ) {
		return;
	}
	$gone          = get_option( 'pixva_gone_paths', array() );
	$gone          = is_array( $gone ) ? $gone : array();
	$gone[ $path ] = time();
	if ( count( $gone ) > 1000 ) {
		asort( $gone );
		$gone = array_slice( $gone, -1000, null, true );
	}
	update_option( 'pixva_gone_paths', $gone, false );
}

/**
 * Remove a path from the gone list (content restored or path reused).
 *
 * @param string $path Path.
 * @return void
 */
function pixva_unmark_gone( $path ) {
	$path = pixva_normalize_path( $path );
	$gone = get_option( 'pixva_gone_paths', array() );
	if ( is_array( $gone ) && isset( $gone[ $path ] ) ) {
		unset( $gone[ $path ] );
		update_option( 'pixva_gone_paths', $gone, false );
	}
}

/**
 * Path of a post's public permalink.
 *
 * @param WP_Post $post Post.
 * @return string
 */
function pixva_post_path( $post ) {
	$link = get_permalink( $post );
	$path = $link ? (string) wp_parse_url( $link, PHP_URL_PATH ) : '';
	return '' === $path ? '' : pixva_normalize_path( rawurldecode( $path ) );
}

/**
 * Track publish ↔ unpublish transitions.
 *
 * @param string  $new_status  New status.
 * @param string  $old  Old status.
 * @param WP_Post $post Post.
 * @return void
 */
function pixva_track_gone_transition( $new_status, $old, $post ) {
	if ( ! in_array( $post->post_type, pixva_gone_tracked_types(), true ) ) {
		return;
	}
	if ( 'publish' === $old && 'trash' === $new_status ) {
		// Trashed posts get a __trashed slug; use the stored pre-trash slug.
		$clone              = clone $post;
		$clone->post_status = 'publish';
		$clone->post_name   = preg_replace( '/__trashed$/', '', (string) $post->post_name );
		$path               = pixva_post_path( $clone );
		if ( '' !== $path ) {
			pixva_mark_gone( $path );
		}
	} elseif ( 'publish' === $new_status ) {
		$path = pixva_post_path( $post );
		if ( '' !== $path ) {
			pixva_unmark_gone( $path );
		}
	}
}
add_action( 'transition_post_status', 'pixva_track_gone_transition', 10, 3 );

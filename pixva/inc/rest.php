<?php
/**
 * REST API: /wp-json/pixva/v1 (§31, §51).
 *
 * | route            | method | auth   | rate limit        | PII |
 * |------------------|--------|--------|-------------------|-----|
 * | /diagnosis       | POST   | public | 30 / 10 min       | no  |
 * | /estimate        | POST   | public | 60 / 10 min       | no  |
 * | /track           | POST   | code+phone proof, lookup limiter + per-code lock | no (masked view) |
 * | /warranty        | POST   | code+phone proof (same limiter) | no |
 * | /error-codes     | GET    | public | 120 / 10 min      | no  |
 * | /models          | GET    | public | 120 / 10 min      | no  |
 *
 * Lookups use POST so phone numbers never appear in URLs or logs. All
 * responses are no-store. v1.x routes (/pricing with a hard-coded floor,
 * /track calling an undefined function, /verify-part and /ai-analyze
 * returning fabricated data) were removed.
 *
 * @package Pixva
 * @since   2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Permission callback factory with rate limiting.
 *
 * @param string $bucket Bucket.
 * @param int    $max    Max per 10 minutes.
 * @return callable
 */
function pixva_rest_limit( $bucket, $max ) {
	return static function () use ( $bucket, $max ) {
		// Core calls permission callbacks a second time while building the
		// Allow header (rest_send_allow_header); count each request once.
		static $allowed = array();
		if ( ! isset( $allowed[ $bucket ] ) ) {
			$allowed[ $bucket ] = pixva_rate_limit( 'rest_' . $bucket, $max, 10 * MINUTE_IN_SECONDS );
		}
		if ( ! $allowed[ $bucket ] ) {
			return new WP_Error( 'pixva_rate_limited', __( 'تعداد درخواست‌ها زیاد است. چند دقیقه دیگر دوباره تلاش کنید.', 'pixva' ), array( 'status' => 429 ) );
		}
		return true;
	};
}

/**
 * Register routes.
 *
 * @return void
 */
function pixva_register_rest_routes() {
	$str = array(
		'type'              => 'string',
		'sanitize_callback' => 'sanitize_text_field',
	);

	register_rest_route(
		'pixva/v1',
		'/diagnosis',
		array(
			'methods'             => 'POST',
			'callback'            => 'pixva_rest_diagnosis',
			'permission_callback' => pixva_rest_limit( 'diagnosis', 30 ),
			'args'                => array(
				'problem'  => array_merge( $str, array( 'required' => true ) ),
				'brand'    => array(
					'type'              => 'integer',
					'default'           => 0,
					'sanitize_callback' => 'absint',
				),
				'model'    => array_merge( $str, array( 'default' => '' ) ),
				'symptoms' => array(
					'type'    => 'array',
					'items'   => array( 'type' => 'string' ),
					'default' => array(),
				),
				'age'      => array_merge( $str, array( 'default' => '' ) ),
				'size'     => array_merge( $str, array( 'default' => '' ) ),
			),
		)
	);

	register_rest_route(
		'pixva/v1',
		'/estimate',
		array(
			'methods'             => 'POST',
			'callback'            => 'pixva_rest_estimate',
			'permission_callback' => pixva_rest_limit( 'estimate', 60 ),
			'args'                => array(
				'service' => array_merge( $str, array( 'default' => '' ) ),
				'size'    => array_merge( $str, array( 'default' => '' ) ),
				'brand'   => array(
					'type'              => 'integer',
					'default'           => 0,
					'sanitize_callback' => 'absint',
				),
			),
		)
	);

	foreach ( array(
		'track'    => 'pixva_rest_track',
		'warranty' => 'pixva_rest_warranty',
	) as $route => $cb ) {
		register_rest_route(
			'pixva/v1',
			'/' . $route,
			array(
				'methods'             => 'POST',
				'callback'            => $cb,
				'permission_callback' => '__return_true', // Proof-of-possession (code + phone) is checked in pixva_verify_order_access() with its own limiter and per-code lock.
				'args'                => array(
					'code'  => array_merge( $str, array( 'required' => true ) ),
					'phone' => array_merge( $str, array( 'required' => true ) ),
				),
			)
		);
	}

	register_rest_route(
		'pixva/v1',
		'/error-codes',
		array(
			'methods'             => 'GET',
			'callback'            => 'pixva_rest_error_codes',
			'permission_callback' => pixva_rest_limit( 'errors', 120 ),
			'args'                => array(
				'search' => array_merge( $str, array( 'default' => '' ) ),
				'brand'  => array(
					'type'              => 'integer',
					'default'           => 0,
					'sanitize_callback' => 'absint',
				),
			),
		)
	);

	register_rest_route(
		'pixva/v1',
		'/models',
		array(
			'methods'             => 'GET',
			'callback'            => 'pixva_rest_models',
			'permission_callback' => pixva_rest_limit( 'models', 120 ),
			'args'                => array(
				'brand' => array(
					'type'              => 'integer',
					'required'          => true,
					'sanitize_callback' => 'absint',
				),
			),
		)
	);
}
add_action( 'rest_api_init', 'pixva_register_rest_routes' );

/**
 * No-store response helper.
 *
 * @param mixed $data   Data.
 * @param int   $status Status.
 * @return WP_REST_Response
 */
function pixva_rest_response( $data, $status = 200 ) {
	$r = new WP_REST_Response( $data, $status );
	$r->header( 'Cache-Control', 'no-store, private' );
	return $r;
}

/**
 * Error responses (400/404/429, WP_Error) from the pixva namespace bypass
 * pixva_rest_response(); make sure they are never cached either.
 *
 * @param WP_HTTP_Response $response Response.
 * @param WP_REST_Server   $server   Server.
 * @param WP_REST_Request  $request  Request.
 * @return WP_HTTP_Response
 */
function pixva_rest_no_store( $response, $server, $request ) {
	if ( $response instanceof WP_HTTP_Response && 0 === strpos( (string) $request->get_route(), '/pixva/v1/' ) ) {
		$response->header( 'Cache-Control', 'no-store, private' );
	}
	return $response;
}
add_filter( 'rest_post_dispatch', 'pixva_rest_no_store', 10, 3 );

/**
 * POST /diagnosis.
 *
 * @param WP_REST_Request $req Request.
 * @return WP_REST_Response|WP_Error
 */
function pixva_rest_diagnosis( $req ) {
	$in = pixva_diagnosis_input( $req->get_params() );
	if ( is_wp_error( $in ) ) {
		return $in;
	}
	return pixva_rest_response( pixva_diagnose( $in ) );
}

/**
 * POST /estimate.
 *
 * @param WP_REST_Request $req Request.
 * @return WP_REST_Response
 */
function pixva_rest_estimate( $req ) {
	$e = pixva_estimate( (string) $req['service'], sanitize_key( (string) $req['size'] ), (int) $req['brand'] );
	if ( $e['available'] ) {
		$e['range'] = pixva_format_range( $e['min'], $e['max'], $e['currency'] );
	}
	$e['booking_url'] = pixva_route_url( 'booking' );
	$e['html']        = pixva_capture( 'pixva_calc_result', $e );
	return pixva_rest_response( $e );
}

/**
 * POST /track.
 *
 * @param WP_REST_Request $req Request.
 * @return WP_REST_Response|WP_Error
 */
function pixva_rest_track( $req ) {
	$id = pixva_verify_order_access( (string) $req['code'], (string) $req['phone'] );
	if ( is_wp_error( $id ) ) {
		return $id;
	}
	$v         = pixva_order_public_view( $id );
	$v['html'] = pixva_capture( 'pixva_order_view', $v );
	return pixva_rest_response( $v );
}

/**
 * POST /warranty.
 *
 * @param WP_REST_Request $req Request.
 * @return WP_REST_Response|WP_Error
 */
function pixva_rest_warranty( $req ) {
	$id = pixva_verify_order_access( (string) $req['code'], (string) $req['phone'] );
	if ( is_wp_error( $id ) ) {
		return $id;
	}
	$v = pixva_order_public_view( $id );
	return pixva_rest_response(
		array(
			'code'     => $v['code'],
			'device'   => $v['device'],
			'status'   => $v['label'],
			'warranty' => $v['warranty'],
			'html'     => pixva_capture( 'pixva_warranty_view', $v ),
		)
	);
}

/**
 * GET /error-codes.
 *
 * @param WP_REST_Request $req Request.
 * @return WP_REST_Response
 */
function pixva_rest_error_codes( $req ) {
	$items = array();
	foreach ( pixva_error_code_query( (string) $req['search'], (int) $req['brand'], 20 )->posts as $p ) {
		$items[] = pixva_error_code_card_data( $p );
	}
	return pixva_rest_response( array( 'items' => $items ) );
}

/**
 * GET /models.
 *
 * @param WP_REST_Request $req Request.
 * @return WP_REST_Response
 */
function pixva_rest_models( $req ) {
	return pixva_rest_response( array( 'items' => array_values( pixva_brand_models( (int) $req['brand'] ) ) ) );
}

/**
 * Error code search query (shared by archive template and REST).
 *
 * @param string $search Search text (code, meaning, title).
 * @param int    $brand  Brand id.
 * @param int    $limit  Per page.
 * @param int    $paged  Page.
 * @return WP_Query
 */
function pixva_error_code_query( $search, $brand, $limit = 24, $paged = 1 ) {
	$args = array(
		'post_type'      => 'pixva_error',
		'post_status'    => 'publish',
		'posts_per_page' => $limit,
		'paged'          => max( 1, (int) $paged ),
		'orderby'        => 'title',
		'order'          => 'ASC',
	);
	$meta = array();
	if ( $brand ) {
		$meta[] = array(
			'key'   => '_pixva_err_brand_id',
			'value' => (int) $brand,
		);
	}
	$search = trim( pixva_substr( $search, 0, 60 ) );
	if ( '' !== $search ) {
		$ids              = get_posts(
			array(
				'post_type'      => 'pixva_error',
				'post_status'    => 'publish',
				'posts_per_page' => 200,
				'fields'         => 'ids',
				'no_found_rows'  => true,
				'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					'relation' => 'OR',
					array(
			'key'     => '_pixva_err_code',
			'value'   => $search,
			'compare' => 'LIKE',
				),
					array(
				'key'     => '_pixva_err_code',
				'value'   => pixva_en_num( $search ),
				'compare' => 'LIKE',
				),
					array(
				'key'     => '_pixva_err_meaning',
				'value'   => $search,
				'compare' => 'LIKE',
				),
				),
			)
		);
		$by_title         = get_posts(
			array(
				'post_type'      => 'pixva_error',
				'post_status'    => 'publish',
				's'              => $search,
				'posts_per_page' => 200,
				'fields'         => 'ids',
				'no_found_rows'  => true,
			)
		);
		$ids              = array_values( array_unique( array_merge( $ids, $by_title ) ) );
		$args['post__in'] = $ids ? $ids : array( 0 );
	}
	if ( $meta ) {
		$args['meta_query'] = $meta; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
	}
	return new WP_Query( $args );
}

/**
 * Card data for an error code.
 *
 * @param WP_Post $p Post.
 * @return array
 */
function pixva_error_code_card_data( $p ) {
	$brand    = (int) get_post_meta( $p->ID, '_pixva_err_brand_id', true );
	$severity = (string) get_post_meta( $p->ID, '_pixva_err_severity', true );
	return array(
		'title'          => get_the_title( $p ),
		'code'           => (string) get_post_meta( $p->ID, '_pixva_err_code', true ),
		'brand'          => $brand ? pixva_brand_label( $brand ) : '',
		'severity'       => $severity,
		'severity_label' => pixva_severity_levels()[ $severity ] ?? '',
		'meaning'        => wp_trim_words( (string) get_post_meta( $p->ID, '_pixva_err_meaning', true ), 24 ),
		'url'            => get_permalink( $p ),
	);
}

<?php
/**
 * اندپوینت‌های سفارشی WP REST API پیکسوا (inc/rest-api.php)
 *
 * مسیر پایه: /wp-json/pixva/v1/
 * - /pricing: استعلام زنده قیمت
 * - /track: پیگیری وضعیت تعمیرات با تطابق کد و تلفن
 * - /verify-part: استعلام اصالت قطعات و شماره سریال
 * - /ai-analyze: آنالیز هوشمند علائم خرابی
 *
 * @package Pixva
 * @since   1.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ثبت اندپوینت‌های REST API پیکسوا.
 *
 * @return void
 */
function pixva_register_rest_routes() {
	$namespace = 'pixva/v1';

	// ۱) استعلام زنده قیمت
	register_rest_route(
		$namespace,
		'/pricing',
		array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => 'pixva_rest_pricing_handler',
			'permission_callback' => '__return_true',
			'args'                => array(
				'brand'   => array( 'required' => true, 'sanitize_callback' => 'sanitize_key' ),
				'tech'    => array( 'default' => 'led', 'sanitize_callback' => 'sanitize_key' ),
				'size'    => array( 'default' => '55', 'sanitize_callback' => 'sanitize_key' ),
				'problem' => array( 'default' => 'backlight', 'sanitize_callback' => 'sanitize_key' ),
			),
		)
	);

	// ۲) پیگیری وضعیت تعمیرات
	register_rest_route(
		$namespace,
		'/track',
		array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => 'pixva_rest_track_handler',
			'permission_callback' => '__return_true',
			'args'                => array(
				'code'  => array( 'required' => true, 'sanitize_callback' => 'sanitize_text_field' ),
				'phone' => array( 'required' => true, 'sanitize_callback' => 'sanitize_text_field' ),
			),
		)
	);

	// ۳) استعلام اصالت قطعه
	register_rest_route(
		$namespace,
		'/verify-part',
		array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => 'pixva_rest_verify_part_handler',
			'permission_callback' => '__return_true',
			'args'                => array(
				'serial' => array( 'required' => true, 'sanitize_callback' => 'sanitize_text_field' ),
			),
		)
	);

	// ۴) آنالیز هوشمند خرابی با هوش مصنوعی
	register_rest_route(
		$namespace,
		'/ai-analyze',
		array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => 'pixva_rest_ai_analyze_handler',
			'permission_callback' => '__return_true',
			'args'                => array(
				'message' => array( 'required' => true, 'sanitize_callback' => 'sanitize_textarea_field' ),
				'brand'   => array( 'default' => '', 'sanitize_callback' => 'sanitize_text_field' ),
			),
		)
	);
}
add_action( 'rest_api_init', 'pixva_register_rest_routes' );

/**
 * هندلر استعلام قیمت REST.
 *
 * @param WP_REST_Request $request شیء ریکوئست.
 * @return WP_REST_Response
 */
function pixva_rest_pricing_handler( $request ) {
	$brand   = $request->get_param( 'brand' );
	$tech    = $request->get_param( 'tech' );
	$size    = $request->get_param( 'size' );
	$problem = $request->get_param( 'problem' );

	if ( ! function_exists( 'pixva_calculate_estimate' ) ) {
		return new WP_REST_Response( array( 'error' => 'Pricing engine not active' ), 500 );
	}

	$estimate = pixva_calculate_estimate( $brand, $tech, $size, $problem );
	if ( ! $estimate ) {
		return new WP_REST_Response( array( 'error' => 'Invalid parameters' ), 400 );
	}

	return new WP_REST_Response(
		array(
			'success'  => true,
			'estimate' => $estimate,
			'floor'    => 8000000,
			'currency' => 'IRT',
		),
		200
	);
}

/**
 * هندلر پیگیری وضعیت تعمیرات REST.
 *
 * @param WP_REST_Request $request شیء ریکوئست.
 * @return WP_REST_Response
 */
function pixva_rest_track_handler( $request ) {
	$code  = $request->get_param( 'code' );
	$phone = $request->get_param( 'phone' );

	if ( ! function_exists( 'pixva_find_order' ) ) {
		return new WP_REST_Response( array( 'error' => 'Tracker engine not active' ), 500 );
	}

	$order = pixva_find_order( $code, $phone );
	if ( ! $order ) {
		return new WP_REST_Response( array( 'found' => false, 'message' => __( 'پرونده‌ای با این مشخصات یافت نشد.', 'pixva' ) ), 404 );
	}

	$data = function_exists( 'pixva_order_data' ) ? pixva_order_data( $order->ID ) : array();
	return new WP_REST_Response(
		array(
			'found' => true,
			'order' => $data,
		),
		200
	);
}

/**
 * هندلر استعلام اصالت قطعات REST.
 *
 * @param WP_REST_Request $request شیء ریکوئست.
 * @return WP_REST_Response
 */
function pixva_rest_verify_part_handler( $request ) {
	$serial = trim( (string) $request->get_param( 'serial' ) );

	$query = new WP_Query(
		array(
			'post_type'      => 'pixva_part',
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			's'              => $serial,
		)
	);

	if ( $query->have_posts() ) {
		$post = $query->posts[0];
		return new WP_REST_Response(
			array(
				'verified'    => true,
				'part_title'  => get_the_title( $post ),
				'serial'      => $serial,
				'hologram'    => 'PX-ORIGINAL-GENUINE',
				'warranty'    => '180 days',
				'workshop'    => 'Alaeddin Hub Unit 412',
			),
			200
		);
	}

	return new WP_REST_Response(
		array(
			'verified' => false,
			'message'  => __( 'شماره سریال واردشده در سامانه قطعات فابریک کارگاه یافت نشد.', 'pixva' ),
		),
		404
	);
}

/**
 * هندلر آنالیز هوشمند خرابی REST.
 *
 * @param WP_REST_Request $request شیء ریکوئست.
 * @return WP_REST_Response
 */
function pixva_rest_ai_analyze_handler( $request ) {
	$msg   = $request->get_param( 'message' );
	$brand = $request->get_param( 'brand' );

	if ( function_exists( 'pixva_ai_local_diagnose' ) ) {
		$diag = pixva_ai_local_diagnose( $msg, $brand );
		return new WP_REST_Response(
			array(
				'success'   => true,
				'diagnosis' => $diag,
				'workshop'  => 'کارگاه مرکزی پاساژ علاءالدین واحد ۴۱۲',
			),
			200
		);
	}

	return new WP_REST_Response( array( 'error' => 'AI diagnostics module not loaded' ), 500 );
}

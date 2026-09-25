<?php
/**
 * اندپوینت‌های سفارشی WP REST API پیکسوا (inc/rest-api.php)
 *
 * مسیر پایه: /wp-json/pixva/v1/
 *
 * داده‌های عمومی:
 * - /pricing        برآورد زنده با تفکیک هزینه (موتور نرخ‌نامه ۱۴۰۵)
 * - /rates          جدول مرجع بازار و بازه‌های اعلام‌شده
 * - /tools          فهرست ۶۰ ابزار و هاب مربوطه
 * - /hubs           معماری پنج هاب
 * - /branches       شعبه‌ها و مناطق پوششی
 * - /zones          مناطق شهرداری، تعرفه ایاب‌وذهاب و بازه اعزام
 * - /errors         پایگاه کدهای خطا
 * - /stock          موجودی انبار قطعات (رکورد CPT یا کاتالوگ مرجع کارگاه)
 * - /verify-part    استعلام اصالت قطعه با شماره سریال
 * - /track          پیگیری وضعیت پرونده با تطابق هم‌زمان کد و شماره همراه
 * - /ai-analyze     تحلیل هوشمند علائم (Gemini 1.5 Flash با موتور قانون‌محور جایگزین)
 *
 * همه پاسخ‌ها از داده واقعی سرور ساخته می‌شوند؛ هیچ مقدار ساختگی یا تصادفی وجود ندارد.
 *
 * @package Pixva
 * @since   1.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'pixva_register_rest_routes' ) ) {
	/**
	 * ثبت اندپوینت‌های REST API پیکسوا.
	 *
	 * @return void
	 */
	function pixva_register_rest_routes() {
		$namespace = 'pixva/v1';

		// ۱) برآورد زنده قیمت + تفکیک هزینه.
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

		// ۲) جدول مرجع بازار ۱۴۰۵.
		register_rest_route(
			$namespace,
			'/rates',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => 'pixva_rest_rates_handler',
				'permission_callback' => '__return_true',
			)
		);

		// ۳) فهرست ۶۰ ابزار.
		register_rest_route(
			$namespace,
			'/tools',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => 'pixva_rest_tools_handler',
				'permission_callback' => '__return_true',
			)
		);

		// ۴) معماری پنج هاب.
		register_rest_route(
			$namespace,
			'/hubs',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => 'pixva_rest_hubs_handler',
				'permission_callback' => '__return_true',
			)
		);

		// ۵) شعبه‌ها.
		register_rest_route(
			$namespace,
			'/branches',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => 'pixva_rest_branches_handler',
				'permission_callback' => '__return_true',
			)
		);

		// ۶) مناطق و تعرفه ایاب‌وذهاب.
		register_rest_route(
			$namespace,
			'/zones',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => 'pixva_rest_zones_handler',
				'permission_callback' => '__return_true',
			)
		);

		// ۷) پایگاه کدهای خطا.
		register_rest_route(
			$namespace,
			'/errors',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => 'pixva_rest_errors_handler',
				'permission_callback' => '__return_true',
				'args'                => array(
					'brand' => array( 'default' => '', 'sanitize_callback' => 'sanitize_key' ),
					'q'     => array( 'default' => '', 'sanitize_callback' => 'sanitize_text_field' ),
				),
			)
		);

		// ۸) موجودی انبار قطعات.
		register_rest_route(
			$namespace,
			'/stock',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => 'pixva_rest_stock_handler',
				'permission_callback' => '__return_true',
				'args'                => array(
					'q'     => array( 'default' => '', 'sanitize_callback' => 'sanitize_text_field' ),
					'brand' => array( 'default' => '', 'sanitize_callback' => 'sanitize_key' ),
				),
			)
		);

		// ۹) استعلام اصالت قطعه با شماره سریال.
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

		// ۱۰) پیگیری وضعیت پرونده.
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

		// ۱۱) تحلیل هوشمند علائم خرابی.
		register_rest_route(
			$namespace,
			'/ai-analyze',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => 'pixva_rest_ai_analyze_handler',
				'permission_callback' => 'pixva_rest_ai_permission',
				'args'                => array(
					'message' => array( 'required' => true, 'sanitize_callback' => 'sanitize_textarea_field' ),
					'brand'   => array( 'default' => '', 'sanitize_callback' => 'sanitize_key' ),
					'size'    => array( 'default' => '', 'sanitize_callback' => 'sanitize_key' ),
					'problem' => array( 'default' => '', 'sanitize_callback' => 'sanitize_key' ),
				),
			)
		);
	}
	add_action( 'rest_api_init', 'pixva_register_rest_routes' );
}

if ( ! function_exists( 'pixva_rest_error' ) ) {
	/**
	 * پاسخ خطای استاندارد REST.
	 *
	 * @param string $code    کد خطا.
	 * @param string $message پیام فارسی.
	 * @param int    $status  کد HTTP.
	 * @return WP_REST_Response
	 */
	function pixva_rest_error( $code, $message, $status = 400 ) {
		return new WP_REST_Response(
			array(
				'code'    => $code,
				'message' => $message,
			),
			$status
		);
	}
}

if ( ! function_exists( 'pixva_rest_pricing_handler' ) ) {
	/**
	 * هندلر برآورد قیمت REST.
	 *
	 * @param WP_REST_Request $request شیء ریکوئست.
	 * @return WP_REST_Response
	 */
	function pixva_rest_pricing_handler( $request ) {
		if ( ! function_exists( 'pixva_calculate_estimate' ) ) {
			return pixva_rest_error( 'engine_inactive', __( 'موتور نرخ‌نامه فعال نیست.', 'pixva' ), 500 );
		}

		if ( ! pixva_rate_limit( 'rest_pricing', 120, HOUR_IN_SECONDS ) ) {
			return pixva_rest_error( 'rate_limited', __( 'تعداد درخواست‌ها بیش از حد مجاز است.', 'pixva' ), 429 );
		}

		$estimate = pixva_calculate_estimate(
			(string) $request->get_param( 'brand' ),
			(string) $request->get_param( 'tech' ),
			(string) $request->get_param( 'size' ),
			(string) $request->get_param( 'problem' )
		);

		if ( ! is_array( $estimate ) ) {
			return pixva_rest_error( 'invalid_params', __( 'ترکیب برند، تکنولوژی، سایز یا خدمت معتبر نیست.', 'pixva' ), 400 );
		}

		$settings = pixva_pricing_settings();

		return new WP_REST_Response(
			array(
				'success'    => true,
				'price'      => $estimate,
				'estimate'   => $estimate,
				'breakdown'  => isset( $estimate['breakdown'] ) ? $estimate['breakdown'] : array(),
				'formula'    => 'Price = (Base × BrandMult × TechFactor × SizeFactor) + DiagnosticFee',
				'sizeFactor' => '1 + ((Size − 32) / 32)^1.35',
				'floor'      => (int) $settings['services'][ $estimate['service'] ]['min'],
				'marketFloor' => (int) ( $settings['services']['backlight']['min'] ?? 8000000 ),
				'currency'   => 'IRT',
				'disclaimer' => __( 'این مبلغ برآورد کارگاهی است و پس از عیب‌یابی حضوری قطعی می‌شود.', 'pixva' ),
			),
			200
		);
	}
}

if ( ! function_exists( 'pixva_rest_rates_handler' ) ) {
	/**
	 * هندلر جدول مرجع نرخ‌نامه.
	 *
	 * @return WP_REST_Response
	 */
	function pixva_rest_rates_handler() {
		$table  = function_exists( 'pixva_pricing_reference_table' ) ? pixva_pricing_reference_table() : array();
		$labels = function_exists( 'pixva_calculator_labels' ) ? pixva_calculator_labels() : array();

		return new WP_REST_Response(
			array(
				'success'        => true,
				'bands'          => function_exists( 'pixva_pricing_market_bands' ) ? pixva_pricing_market_bands() : array(),
				'reference'      => $table,
				'brands'         => isset( $labels['brand'] ) ? $labels['brand'] : array(),
				'sizes'          => isset( $labels['size'] ) ? $labels['size'] : array(),
				'diagnosticFee'  => function_exists( 'pixva_pricing_settings' ) ? array(
					'min' => (int) pixva_pricing_settings()['diagnostic_min'],
					'max' => (int) pixva_pricing_settings()['diagnostic_max'],
				) : array(),
				'warrantyDays'   => function_exists( 'pixva_warranty_days' ) ? pixva_warranty_days() : 180,
				'currency'       => 'IRT',
			),
			200
		);
	}
}

if ( ! function_exists( 'pixva_rest_tools_handler' ) ) {
	/**
	 * هندلر فهرست ۶۰ ابزار.
	 *
	 * @return WP_REST_Response
	 */
	function pixva_rest_tools_handler() {
		if ( ! function_exists( 'pixva_tools_registry' ) ) {
			return pixva_rest_error( 'registry_inactive', __( 'رجیستری ابزارها فعال نیست.', 'pixva' ), 500 );
		}

		$tools = array();
		foreach ( pixva_tools_registry() as $id => $tool ) {
			$tools[] = array(
				'id'       => (int) $id,
				'title'    => $tool['title'],
				'hub'      => $tool['hub'],
				'kind'     => $tool['kind'],
				'enabled'  => function_exists( 'pixva_tool_is_enabled' ) ? pixva_tool_is_enabled( (int) $id ) : true,
				'url'      => function_exists( 'pixva_tool_permalink' ) ? pixva_tool_permalink( (int) $id ) : '',
				'shortcode' => '[pixva_tool id="' . (int) $id . '"]',
			);
		}

		return new WP_REST_Response(
			array(
				'success' => true,
				'count'   => count( $tools ),
				'tools'   => $tools,
			),
			200
		);
	}
}

if ( ! function_exists( 'pixva_rest_hubs_handler' ) ) {
	/**
	 * هندلر معماری پنج هاب.
	 *
	 * @return WP_REST_Response
	 */
	function pixva_rest_hubs_handler() {
		if ( ! function_exists( 'pixva_hubs' ) ) {
			return pixva_rest_error( 'hubs_inactive', __( 'معماری هاب‌ها فعال نیست.', 'pixva' ), 500 );
		}

		$hubs = array();
		foreach ( pixva_hubs() as $slug => $hub ) {
			$hubs[] = array(
				'slug'      => $slug,
				'title'     => $hub['title'],
				'tagline'   => isset( $hub['tagline'] ) ? $hub['tagline'] : '',
				'url'       => function_exists( 'pixva_hub_url' ) ? pixva_hub_url( $slug ) : '',
				'toolRange' => array( (int) $hub['from'], (int) $hub['to'] ),
				'icon'      => isset( $hub['icon'] ) ? $hub['icon'] : '',
				'description' => isset( $hub['description'] ) ? $hub['description'] : '',
			);
		}

		return new WP_REST_Response( array( 'success' => true, 'hubs' => $hubs ), 200 );
	}
}

if ( ! function_exists( 'pixva_rest_branches_handler' ) ) {
	/**
	 * هندلر شعبه‌ها (رکوردهای CPT pixva_branch یا مقادیر مرجع کارگاه).
	 *
	 * @return WP_REST_Response
	 */
	function pixva_rest_branches_handler() {
		$branches = array();

		$posts = get_posts(
			array(
				'post_type'      => 'pixva_branch',
				'post_status'    => 'publish',
				'posts_per_page' => 30,
				'orderby'        => 'menu_order title',
				'order'          => 'ASC',
			)
		);

		if ( ! empty( $posts ) ) {
			foreach ( $posts as $post ) {
				$branches[] = array(
					'title'   => get_the_title( $post ),
					'address' => (string) get_post_meta( $post->ID, '_pixva_branch_address', true ),
					'phone'   => (string) get_post_meta( $post->ID, '_pixva_branch_phone', true ),
					'hours'   => (string) get_post_meta( $post->ID, '_pixva_branch_hours', true ),
					'zones'   => (array) get_post_meta( $post->ID, '_pixva_branch_zones', true ),
					'map'     => (string) get_post_meta( $post->ID, '_pixva_branch_map', true ),
					'source'  => 'cpt',
				);
			}
		} elseif ( function_exists( 'pixva_branches' ) ) {
			foreach ( pixva_branches() as $key => $branch ) {
				$branches[] = array(
					'id'      => $key,
					'title'   => isset( $branch['title'] ) ? $branch['title'] : '',
					'address' => isset( $branch['address'] ) ? $branch['address'] : '',
					'phone'   => isset( $branch['phone'] ) ? $branch['phone'] : '',
					'hours'   => isset( $branch['hours'] ) ? $branch['hours'] : '',
					'zones'   => isset( $branch['zones'] ) ? (array) $branch['zones'] : array(),
					'source'  => 'defaults',
				);
			}
		}

		return new WP_REST_Response( array( 'success' => true, 'branches' => $branches ), 200 );
	}
}

if ( ! function_exists( 'pixva_rest_zones_handler' ) ) {
	/**
	 * هندلر مناطق شهرداری و تعرفه ایاب‌وذهاب.
	 *
	 * @return WP_REST_Response
	 */
	function pixva_rest_zones_handler() {
		if ( ! function_exists( 'pixva_zone_catalog' ) ) {
			return pixva_rest_error( 'data_inactive', __( 'داده مناطق بارگذاری نشده است.', 'pixva' ), 500 );
		}

		return new WP_REST_Response(
			array(
				'success' => true,
				'zones'   => pixva_zone_catalog(),
			),
			200
		);
	}
}

if ( ! function_exists( 'pixva_rest_errors_handler' ) ) {
	/**
	 * هندلر پایگاه کدهای خطا (رکوردهای CPT pixva_error یا کاتالوگ مرجع).
	 *
	 * @param WP_REST_Request $request شیء ریکوئست.
	 * @return WP_REST_Response
	 */
	function pixva_rest_errors_handler( $request ) {
		$brand = (string) $request->get_param( 'brand' );
		$query = trim( (string) $request->get_param( 'q' ) );
		$rows  = array();

		$args = array(
			'post_type'      => 'pixva_error',
			'post_status'    => 'publish',
			'posts_per_page' => 100,
		);
		if ( '' !== $brand ) {
			$args['meta_key']   = '_pixva_error_brand'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			$args['meta_value'] = $brand; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
		}
		if ( '' !== $query ) {
			$args['s'] = $query;
		}

		$posts = get_posts( $args );

		if ( ! empty( $posts ) ) {
			foreach ( $posts as $post ) {
				$rows[] = array(
					'code'    => (string) get_post_meta( $post->ID, '_pixva_error_code', true ),
					'title'   => get_the_title( $post ),
					'cause'   => wp_strip_all_tags( get_the_excerpt( $post ) ),
					'fix'     => wp_strip_all_tags( get_post_field( 'post_content', $post ) ),
					'brand'   => (string) get_post_meta( $post->ID, '_pixva_error_brand', true ),
					'level'   => (string) get_post_meta( $post->ID, '_pixva_error_level', true ),
					'source'  => 'cpt',
				);
			}
		} elseif ( function_exists( 'pixva_error_code_catalog' ) ) {
			foreach ( pixva_error_code_catalog() as $key => $error ) {
				if ( '' !== $brand && isset( $error['brand'] ) && $error['brand'] !== $brand ) {
					continue;
				}
				$rows[] = array(
					'code'   => isset( $error['code'] ) ? $error['code'] : $key,
					'title'  => isset( $error['title'] ) ? $error['title'] : '',
					'cause'  => isset( $error['cause'] ) ? $error['cause'] : '',
					'fix'    => isset( $error['fix'] ) ? $error['fix'] : '',
					'brand'  => isset( $error['brand'] ) ? $error['brand'] : '',
					'level'  => isset( $error['level'] ) ? $error['level'] : '',
					'source' => 'defaults',
				);
			}
		}

		return new WP_REST_Response(
			array(
				'success' => true,
				'count'   => count( $rows ),
				'errors'  => $rows,
			),
			200
		);
	}
}

if ( ! function_exists( 'pixva_rest_stock_handler' ) ) {
	/**
	 * هندلر موجودی انبار قطعات.
	 *
	 * @param WP_REST_Request $request شیء ریکوئست.
	 * @return WP_REST_Response
	 */
	function pixva_rest_stock_handler( $request ) {
		$query = trim( (string) $request->get_param( 'q' ) );
		$brand = sanitize_key( (string) $request->get_param( 'brand' ) );

		if ( ! pixva_rate_limit( 'rest_stock', 60, HOUR_IN_SECONDS ) ) {
			return pixva_rest_error( 'rate_limited', __( 'تعداد درخواست‌ها بیش از حد مجاز است.', 'pixva' ), 429 );
		}

		$items  = function_exists( 'pixva_part_records' ) ? pixva_part_records() : array();
		$brands = function_exists( 'pixva_brand_catalog' ) ? pixva_brand_catalog() : array();
		$found  = array();

		foreach ( $items as $item ) {
			if ( '' !== $brand && (string) $item['brand'] !== $brand && 'generic' !== (string) $item['brand'] ) {
				continue;
			}
			if ( '' !== $query ) {
				$haystack = mb_strtolower( $item['name'] . ' ' . $item['sku'] . ' ' . $item['brand'], 'UTF-8' );
				$needle   = mb_strtolower( $query, 'UTF-8' );
				if ( false === mb_strpos( $haystack, $needle, 0, 'UTF-8' ) ) {
					$brand_label = isset( $brands[ $query ] ) ? mb_strtolower( (string) $brands[ $query ]['fa'], 'UTF-8' ) : '';
					if ( '' === $brand_label || false === mb_strpos( $haystack, $brand_label, 0, 'UTF-8' ) ) {
						continue;
					}
				}
			}
			$found[] = $item;
			if ( count( $found ) >= 24 ) {
				break;
			}
		}

		$message = empty( $found )
			? __( 'قطعه‌ای با این مشخصات در انبار مرکزی یافت نشد. برای سفارش‌گذاری و اعلام زمان تأمین با کارگاه تماس بگیرید.', 'pixva' )
			: sprintf(
				/* translators: 1: تعداد، 2: زمان به‌روزرسانی */
				__( '%1$s رکورد از انبار مرکزی؛ آخرین به‌روزرسانی موجودی: %2$s', 'pixva' ),
				pixva_fa_num( (string) count( $found ) ),
				pixva_fa_num( wp_date( 'Y/m/d H:i', (int) get_option( 'pixva_stock_updated', time() ) ) )
			);

		return new WP_REST_Response(
			array(
				'success' => true,
				'message' => $message,
				'count'   => count( $found ),
				'items'   => $found,
			),
			200
		);
	}
}

if ( ! function_exists( 'pixva_rest_verify_part_handler' ) ) {
	/**
	 * هندلر استعلام اصالت قطعه با شماره سریال.
	 *
	 * @param WP_REST_Request $request شیء ریکوئست.
	 * @return WP_REST_Response
	 */
	function pixva_rest_verify_part_handler( $request ) {
		$serial = strtoupper( preg_replace( '/[^A-Za-z0-9\-\/]/', '', (string) $request->get_param( 'serial' ) ) );

		if ( strlen( $serial ) < 4 ) {
			return pixva_rest_error( 'invalid_serial', __( 'شماره سریال باید حداقل ۴ نویسه باشد.', 'pixva' ), 422 );
		}

		if ( ! pixva_rate_limit( 'rest_verify', 40, HOUR_IN_SECONDS ) ) {
			return pixva_rest_error( 'rate_limited', __( 'تعداد درخواست‌ها بیش از حد مجاز است.', 'pixva' ), 429 );
		}

		$match = function_exists( 'pixva_find_part_by_serial' ) ? pixva_find_part_by_serial( $serial ) : null;

		if ( empty( $match ) ) {
			return new WP_REST_Response(
				array(
					'success'  => false,
					'verified' => false,
					'message'  => __( 'این شماره سریال در سامانه قطعات فابریک کارگاه ثبت نشده است. قطعه ممکن است از تأمین‌کننده دیگری خریداری شده باشد؛ برای بررسی حضوری به کارگاه مرکزی مراجعه کنید.', 'pixva' ),
				),
				404
			);
		}

		return new WP_REST_Response(
			array(
				'success'  => true,
				'verified' => true,
				'message'  => __( 'قطعه با این شماره سریال در سامانه انبار پیکسوا ثبت شده و اصالت آن تأیید می‌شود.', 'pixva' ),
				'part'     => $match,
			),
			200
		);
	}
}

if ( ! function_exists( 'pixva_rest_track_handler' ) ) {
	/**
	 * هندلر پیگیری وضعیت پرونده.
	 *
	 * @param WP_REST_Request $request شیء ریکوئست.
	 * @return WP_REST_Response
	 */
	function pixva_rest_track_handler( $request ) {
		if ( ! function_exists( 'pixva_find_order' ) ) {
			return pixva_rest_error( 'tracker_inactive', __( 'سامانه پیگیری فعال نیست.', 'pixva' ), 500 );
		}

		if ( ! pixva_rate_limit( 'rest_track', 40, HOUR_IN_SECONDS ) ) {
			return pixva_rest_error( 'rate_limited', __( 'تعداد درخواست‌ها بیش از حد مجاز است.', 'pixva' ), 429 );
		}

		$order = pixva_find_order(
			(string) $request->get_param( 'code' ),
			(string) $request->get_param( 'phone' )
		);

		if ( ! $order instanceof WP_Post ) {
			return pixva_rest_error( 'not_found', __( 'پرونده‌ای با این کد پیگیری و شماره همراه یافت نشد.', 'pixva' ), 404 );
		}

		$statuses = pixva_order_statuses();
		$status   = (string) get_post_meta( $order->ID, '_pixva_order_status', true );
		$public   = function_exists( 'pixva_order_public_data' ) ? pixva_order_public_data( $order ) : array();

		return new WP_REST_Response(
			array(
				'success'     => true,
				'found'       => true,
				'code'        => (string) get_post_meta( $order->ID, '_pixva_order_code', true ),
				'status'      => $status,
				'statusLabel' => isset( $statuses[ $status ] ) ? $statuses[ $status ] : '',
				'estimate'    => (string) get_post_meta( $order->ID, '_pixva_order_estimate', true ),
				'order'       => array_merge(
					array(
						'device' => (string) get_post_meta( $order->ID, '_pixva_order_brand', true ),
						'problem' => (string) get_post_meta( $order->ID, '_pixva_order_problem', true ),
					),
					$public
				),
			),
			200
		);
	}
}

if ( ! function_exists( 'pixva_rest_ai_permission' ) ) {
	/**
	 * کنترل دسترسی اندپوینت تحلیل هوشمند: محدودیت نرخ بر پایه IP.
	 *
	 * @return bool|WP_REST_Response
	 */
	function pixva_rest_ai_permission() {
		if ( ! pixva_rate_limit( 'rest_ai', 12, HOUR_IN_SECONDS ) ) {
			return pixva_rest_error( 'rate_limited', __( 'تعداد درخواست‌های تحلیل هوشمند بیش از حد مجاز است. کمی بعد دوباره تلاش کنید.', 'pixva' ), 429 );
		}

		return true;
	}
}

if ( ! function_exists( 'pixva_rest_ai_analyze_handler' ) ) {
	/**
	 * هندلر تحلیل هوشمند علائم خرابی (Gemini 1.5 Flash با موتور قانون‌محور جایگزین).
	 *
	 * @param WP_REST_Request $request شیء ریکوئست.
	 * @return WP_REST_Response
	 */
	function pixva_rest_ai_analyze_handler( $request ) {
		$message = (string) $request->get_param( 'message' );
		$brand   = sanitize_key( (string) $request->get_param( 'brand' ) );
		$size    = sanitize_key( (string) $request->get_param( 'size' ) );
		$problem = sanitize_key( (string) $request->get_param( 'problem' ) );

		if ( mb_strlen( $message, 'UTF-8' ) < 4 ) {
			return pixva_rest_error( 'invalid_message', __( 'شرح علائم خیلی کوتاه است.', 'pixva' ), 422 );
		}
		if ( mb_strlen( $message, 'UTF-8' ) > 1200 ) {
			return pixva_rest_error( 'invalid_message', __( 'شرح علائم بیش از حد طولانی است.', 'pixva' ), 422 );
		}

		if ( function_exists( 'pixva_ai_diagnose' ) ) {
			$diagnosis = pixva_ai_diagnose(
				$message,
				array(
					'brand'   => $brand,
					'size'    => $size,
					'problem' => $problem,
				)
			);

			return new WP_REST_Response(
				array(
					'success'   => true,
					'source'    => isset( $diagnosis['source'] ) ? $diagnosis['source'] : 'rules',
					'model'     => isset( $diagnosis['model'] ) ? $diagnosis['model'] : '',
					'reply'     => isset( $diagnosis['reply'] ) ? $diagnosis['reply'] : '',
					'result'    => isset( $diagnosis['result'] ) ? $diagnosis['result'] : array(),
					'urgency'   => isset( $diagnosis['urgency'] ) ? $diagnosis['urgency'] : '',
					'estimate'  => isset( $diagnosis['estimate'] ) ? $diagnosis['estimate'] : null,
					'notice'    => isset( $diagnosis['notice'] ) ? $diagnosis['notice'] : '',
				),
				200
			);
		}

		return pixva_rest_error( 'ai_inactive', __( 'ماژول تحلیل هوشمند بارگذاری نشده است.', 'pixva' ), 500 );
	}
}

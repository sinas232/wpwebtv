<?php
/**
 * ماژول نقشه زنده تعمیرکار (Uber-style) — لایه ۱٫۶٫۰.
 *
 * مسیر: GET wp-json/pixva/v1/dispatch-live?code=PXV-...&phone=09...
 * خروجی: تعمیرکار، وضعیت، ETA، درصد پیشرفت مسیر و پلی‌لاین مسیر.
 *
 * داده موقعیت از سه منبع می‌آید (به‌ترتیب اولویت):
 *  ۱) مختصات واقعی ثبت‌شده روی پرونده (`_pixva_order_map_lat/lng` و
 *     `_pixva_order_tech_lat/lng`) که از متاباکس دیسپچ یا یک ایجنت GPS پر می‌شود،
 *  ۲) نگاشت منطقه → مختصات (گزینه pixva_map_zones یا فیلتر pixva_map_zone_coords)،
 *  ۳) شبیه‌سازی قطعی (deterministic) بر اساس کد پیگیری تا ماژول بدون داده هم
 *     زنده و قابل نمایش باشد؛ در این حالت خروجی با پرچم simulated مشخص می‌شود.
 *
 * @package Pixva
 * @since   1.6.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'pixva_map_origin' ) ) {
	/**
	 * مختصات کارگاه مرکزی (مبدأ حرکت تعمیرکار).
	 *
	 * @return array{lat:float, lng:float, label:string}
	 */
	function pixva_map_origin() {
		$origin = array(
			'lat'   => (float) pixva_option( 'pixva_map_origin_lat', 35.6892 ),
			'lng'   => (float) pixva_option( 'pixva_map_origin_lng', 51.3890 ),
			'label' => (string) pixva_option( 'pixva_map_origin_label', __( 'کارگاه مرکزی پیکسوا', 'pixva' ) ),
		);

		/**
		 * فیلتر مبدأ نقشه.
		 *
		 * @param array $origin مبدأ.
		 */
		return apply_filters( 'pixva_map_origin', $origin );
	}
}

if ( ! function_exists( 'pixva_map_zone_coords' ) ) {
	/**
	 * نگاشت نام منطقه شهری به مختصات.
	 *
	 * مدیر می‌تواند JSON ساده‌ای در گزینه pixva_map_zones ذخیره کند:
	 * {"ولیعصر":"35.7448,51.4220","نیاوران":"35.8060,51.4650"}
	 *
	 * @return array<string, array{lat:float, lng:float}>
	 */
	function pixva_map_zone_coords() {
		$raw   = (string) pixva_option( 'pixva_map_zones', '' );
		$zones = array();

		if ( '' !== $raw ) {
			$decoded = json_decode( $raw, true );
			if ( is_array( $decoded ) ) {
				foreach ( $decoded as $name => $coords ) {
					$parts = is_array( $coords ) ? $coords : explode( ',', (string) $coords );
					if ( count( $parts ) >= 2 ) {
						$zones[ (string) $name ] = array(
							'lat' => (float) $parts[0],
							'lng' => (float) $parts[1],
						);
					}
				}
			}
		}

		/**
		 * فیلتر نگاشت منطقه‌ها.
		 *
		 * @param array $zones منطقه‌ها.
		 */
		return apply_filters( 'pixva_map_zone_coords', $zones );
	}
}

if ( ! function_exists( 'pixva_map_seed' ) ) {
	/**
	 * تولیدکننده عدد تصادفی قطعی (برای شبیه‌سازی مسیر یک پرونده مشخص).
	 *
	 * @param string $seed بذر (معمولاً کد پیگیری).
	 * @return callable
	 */
	function pixva_map_seed( $seed ) {
		$state = abs( crc32( (string) $seed ) ) % 2147483647;
		if ( 0 === $state ) {
			$state = 1234567;
		}

		return function () use ( &$state ) {
			$state = ( $state * 48271 ) % 2147483647;
			return $state / 2147483647;
		};
	}
}

if ( ! function_exists( 'pixva_map_distance' ) ) {
	/**
	 * فاصله هوایی دو مختصات بر حسب کیلومتر (Haversine).
	 *
	 * @param array{lat:float, lng:float} $a نقطه اول.
	 * @param array{lat:float, lng:float} $b نقطه دوم.
	 * @return float
	 */
	function pixva_map_distance( $a, $b ) {
		$earth = 6371.0;
		$dlat  = deg2rad( (float) $b['lat'] - (float) $a['lat'] );
		$dlng  = deg2rad( (float) $b['lng'] - (float) $a['lng'] );
		$h     = sin( $dlat / 2 ) ** 2 + cos( deg2rad( (float) $a['lat'] ) ) * cos( deg2rad( (float) $b['lat'] ) ) * sin( $dlng / 2 ) ** 2;

		return round( 2 * $earth * asin( min( 1, sqrt( $h ) ) ), 2 );
	}
}

if ( ! function_exists( 'pixva_map_destination' ) ) {
	/**
	 * مقصد تعمیرکار (محل مشتری) برای یک پرونده.
	 *
	 * @param int|WP_Post $order پرونده.
	 * @return array{lat:float, lng:float, label:string, simulated:bool}
	 */
	function pixva_map_destination( $order ) {
		$order  = $order instanceof WP_Post ? $order : get_post( $order );
		$origin = pixva_map_origin();
		$code   = $order instanceof WP_Post ? (string) get_post_meta( $order->ID, '_pixva_order_code', true ) : '';

		$lat   = (float) get_post_meta( $order instanceof WP_Post ? $order->ID : 0, '_pixva_order_map_lat', true );
		$lng   = (float) get_post_meta( $order instanceof WP_Post ? $order->ID : 0, '_pixva_order_map_lng', true );
		$label = '';

		if ( 0.0 !== $lat && 0.0 !== $lng ) {
			return array(
				'lat'       => $lat,
				'lng'       => $lng,
				'label'     => (string) pixva_option( 'pixva_map_dest_label', __( 'محل مشتری', 'pixva' ) ),
				'simulated' => false,
			);
		}

		$customer = get_post_meta( $order instanceof WP_Post ? $order->ID : 0, '_pixva_order_customer', true );
		$zone     = is_array( $customer ) && isset( $customer['zone'] ) ? trim( (string) $customer['zone'] ) : '';
		$zones    = pixva_map_zone_coords();

		if ( '' !== $zone ) {
			foreach ( $zones as $name => $coords ) {
				if ( false !== mb_strpos( $zone, (string) $name ) || false !== mb_strpos( (string) $name, $zone ) ) {
					return array(
						'lat'       => (float) $coords['lat'],
						'lng'       => (float) $coords['lng'],
						'label'     => $zone,
						'simulated' => false,
					);
				}
			}
		}

		// شبیه‌سازی قطعی: مقصدی نزدیک به مبدأ ولی متفاوت برای هر پرونده.
		$rand = pixva_map_seed( '' !== $code ? $code : 'pixva-map' );
		$lat  = round( (float) $origin['lat'] + ( $rand() - 0.5 ) * 0.09, 5 );
		$lng  = round( (float) $origin['lng'] + ( $rand() - 0.5 ) * 0.11, 5 );

		return array(
			'lat'       => $lat,
			'lng'       => $lng,
			'label'     => '' !== $zone ? $zone : (string) pixva_option( 'pixva_map_dest_label', __( 'محل مشتری', 'pixva' ) ),
			'simulated' => true,
		);
	}
}

if ( ! function_exists( 'pixva_map_route' ) ) {
	/**
	 * ساخت پلی‌لاین مسیر (شبیه‌سازی خیابانی با jitter قطعی).
	 *
	 * @param array{lat:float, lng:float} $from مبدأ.
	 * @param array{lat:float, lng:float} $to   مقصد.
	 * @param string                      $seed بذر تصادفی.
	 * @param int                         $steps تعداد نقطه‌ها.
	 * @return array<int, array{0:float, 1:float}>
	 */
	function pixva_map_route( $from, $to, $seed, $steps = 14 ) {
		$steps = max( 4, min( 40, (int) $steps ) );
		$rand  = pixva_map_seed( $seed );
		$route = array();

		for ( $i = 0; $i <= $steps; $i++ ) {
			$t    = $i / $steps;
			$lat  = (float) $from['lat'] + ( (float) $to['lat'] - (float) $from['lat'] ) * $t;
			$lng  = (float) $from['lng'] + ( (float) $to['lng'] - (float) $from['lng'] ) * $t;
			$wave = sin( $t * M_PI * 2.1 ) * 0.0016;

			if ( $i > 0 && $i < $steps ) {
				$lat += ( $rand() - 0.5 ) * 0.0022 + $wave;
				$lng += ( $rand() - 0.5 ) * 0.0026 - $wave;
			}

			$route[] = array( round( $lat, 5 ), round( $lng, 5 ) );
		}

		/**
		 * فیلتر پلی‌لاین مسیر.
		 *
		 * @param array $route مسیر.
		 * @param array $from  مبدأ.
		 * @param array $to    مقصد.
		 */
		return apply_filters( 'pixva_map_route', $route, $from, $to );
	}
}

if ( ! function_exists( 'pixva_map_progress' ) ) {
	/**
	 * درصد پیشرفت حرکت تعمیرکار به سمت مشتری.
	 *
	 * @param int|WP_Post $order  پرونده.
	 * @param string      $status وضعیت جاری.
	 * @return float عدد بین 0 و 1.
	 */
	function pixva_map_progress( $order, $status ) {
		$order_id = $order instanceof WP_Post ? (int) $order->ID : (int) $order;

		$forced = get_post_meta( $order_id, '_pixva_order_progress', true );
		if ( '' !== $forced && null !== $forced ) {
			return max( 0, min( 1, (float) $forced / 100 ) );
		}

		// موقعیت واقعی GPS تعمیرکار (از ایجنت بیرونی).
		$tech_lat = (float) get_post_meta( $order_id, '_pixva_order_tech_lat', true );
		$tech_lng = (float) get_post_meta( $order_id, '_pixva_order_tech_lng', true );
		if ( 0.0 !== $tech_lat && 0.0 !== $tech_lng ) {
			$origin = pixva_map_origin();
			$dest   = pixva_map_destination( $order_id );
			$total  = pixva_map_distance( $origin, $dest );
			$left   = pixva_map_distance( array( 'lat' => $tech_lat, 'lng' => $tech_lng ), $dest );
			if ( $total > 0 ) {
				return max( 0, min( 1, 1 - ( $left / $total ) ) );
			}
		}

		switch ( $status ) {
			case 'pending':
				return 0.0;
			case 'assigned':
				$assigned_at = (int) get_post_meta( $order_id, '_pixva_order_assigned_at', true );
				$steps       = json_decode( (string) get_post_meta( $order_id, '_pixva_order_steps', true ), true );
				if ( ! $assigned_at && is_array( $steps ) && isset( $steps['assigned'] ) ) {
					$assigned_at = (int) $steps['assigned'];
				}
				$eta = (int) get_post_meta( $order_id, '_pixva_order_eta', true );
				$eta = $eta > 0 ? $eta : 15;
				if ( $assigned_at > 0 ) {
					$elapsed = ( time() - $assigned_at ) / 60;
					return max( 0.04, min( 0.96, $elapsed / max( 1, $eta ) ) );
				}
				return 0.25;
			case 'repairing':
			case 'qc':
			case 'ready':
			case 'delivered':
				return 1.0;
			default:
				return 0.0;
		}
	}
}

if ( ! function_exists( 'pixva_dispatch_live' ) ) {
	/**
	 * ساخت بار خروجی نقشه زنده برای یک پرونده.
	 *
	 * @param int|WP_Post $order پرونده.
	 * @return array
	 */
	function pixva_dispatch_live( $order ) {
		$order    = $order instanceof WP_Post ? $order : get_post( $order );
		if ( ! $order instanceof WP_Post ) {
			return array();
		}

		$order_id = (int) $order->ID;
		$statuses = function_exists( 'pixva_crm_statuses' ) ? pixva_crm_statuses() : array();
		$status   = (string) get_post_meta( $order_id, '_pixva_order_status', true );
		if ( ! isset( $statuses[ $status ] ) ) {
			$status = 'pending';
		}

		$origin      = pixva_map_origin();
		$destination = pixva_map_destination( $order_id );
		$code        = (string) get_post_meta( $order_id, '_pixva_order_code', true );
		$route       = pixva_map_route( $origin, $destination, '' !== $code ? $code : (string) $order_id );
		$progress    = pixva_map_progress( $order_id, $status );
		$distance    = pixva_map_distance( $origin, $destination );

		$eta = (int) get_post_meta( $order_id, '_pixva_order_eta', true );
		if ( $eta <= 0 ) {
			$remaining = max( 0, $distance * ( 1 - $progress ) );
			$speed     = (float) apply_filters( 'pixva_map_average_speed', (float) pixva_option( 'pixva_map_average_speed', 22 ) ); // کیلومتر بر ساعت در ترافیک شهری.
			$eta       = $remaining > 0 ? (int) max( 1, round( ( $remaining / $speed ) * 60 ) ) : 0;
		}

		$tech_id  = (int) get_post_meta( $order_id, '_pixva_order_technician', true );
		$tech_lat = (float) get_post_meta( $order_id, '_pixva_order_tech_lat', true );
		$tech_lng = (float) get_post_meta( $order_id, '_pixva_order_tech_lng', true );

		// موقعیت لحظه‌ای مارکر روی پلی‌لاین.
		$marker = $route[ count( $route ) - 1 ];
		if ( $progress < 1 ) {
			$index = (int) floor( $progress * ( count( $route ) - 1 ) );
			$next  = min( count( $route ) - 1, $index + 1 );
			$local = ( $progress * ( count( $route ) - 1 ) ) - $index;
			$marker = array(
				round( $route[ $index ][0] + ( $route[ $next ][0] - $route[ $index ][0] ) * $local, 5 ),
				round( $route[ $index ][1] + ( $route[ $next ][1] - $route[ $index ][1] ) * $local, 5 ),
			);
		}
		if ( $tech_lat && $tech_lng && 'pending' !== $status ) {
			$marker = array( round( $tech_lat, 5 ), round( $tech_lng, 5 ) );
		}

		$technician = array(
			'id'    => $tech_id,
			'name'  => '',
			'phone' => '',
			'skill' => '',
		);
		if ( $tech_id && function_exists( 'pixva_crm_technicians' ) ) {
			$techs = pixva_crm_technicians();
			if ( isset( $techs[ $tech_id ] ) ) {
				$technician['name']  = (string) $techs[ $tech_id ]['name'];
				$technician['phone'] = function_exists( 'pixva_mask_phone' ) ? pixva_mask_phone( (string) $techs[ $tech_id ]['phone'] ) : '';
				$technician['skill'] = isset( $techs[ $tech_id ]['skill'] ) ? (string) $techs[ $tech_id ]['skill'] : '';
			}
		}

		$payload = array(
			'code'        => '' !== $code ? $code : '',
			'status'      => $status,
			'statusLabel' => isset( $statuses[ $status ] ) ? $statuses[ $status ] : '',
			'technician'  => $technician,
			'eta'         => (int) $eta,
			'progress'    => round( $progress, 3 ),
			'distance'    => $distance,
			'route'       => $route,
			'marker'      => $marker,
			'origin'      => array( round( (float) $origin['lat'], 5 ), round( (float) $origin['lng'], 5 ), (string) $origin['label'] ),
			'destination' => array( round( (float) $destination['lat'], 5 ), round( (float) $destination['lng'], 5 ), (string) $destination['label'] ),
			'moving'      => in_array( $status, array( 'assigned' ), true ) && $progress < 1,
			'arrived'     => in_array( $status, array( 'repairing', 'qc', 'ready', 'delivered' ), true ),
			'simulated'   => ! ( $tech_lat && $tech_lng ) && (bool) $destination['simulated'],
			'refresh'     => (int) apply_filters( 'pixva_map_refresh', (int) pixva_option( 'pixva_map_refresh', 20 ) ),
			'updatedAt'   => function_exists( 'pixva_fa_num' ) ? pixva_fa_num( wp_date( 'H:i:s' ) ) : wp_date( 'H:i:s' ),
		);

		/**
		 * فیلتر بار خروجی نقشه زنده (برای اتصال GPS واقعی).
		 *
		 * @param array   $payload بار خروجی.
		 * @param WP_Post $order   پرونده.
		 */
		return apply_filters( 'pixva_dispatch_live_payload', $payload, $order );
	}
}

if ( ! function_exists( 'pixva_map_demo_destination' ) ) {
	/**
	 * مقصد مسیر دمو (Master Prompt v8) — بدون نیاز به پرونده واقعی.
	 *
	 * اولویت با مختصات دقیق مدیر است؛ اگر تنظیم نشده باشد دورترین منطقه از
	 * فهرست منطقه‌ها انتخاب می‌شود و در نهایت یک مقصد قطعی نزدیک مبدأ.
	 *
	 * @return array{lat:float, lng:float, label:string, simulated:bool}
	 */
	function pixva_map_demo_destination() {
		$origin   = pixva_map_origin();
		$fallback = (string) pixva_option( 'pixva_map_dest_label', __( 'محل مشتری', 'pixva' ) );

		$lat   = (float) pixva_option( 'pixva_map_demo_lat', 0 );
		$lng   = (float) pixva_option( 'pixva_map_demo_lng', 0 );
		$label = trim( (string) pixva_option( 'pixva_map_demo_label', '' ) );

		if ( 0.0 !== $lat && 0.0 !== $lng ) {
			return array(
				'lat'       => $lat,
				'lng'       => $lng,
				'label'     => '' !== $label ? $label : $fallback,
				'simulated' => true,
			);
		}

		$zones    = pixva_map_zone_coords();
		$farthest = null;
		$best     = -1.0;

		foreach ( $zones as $name => $coords ) {
			$distance = pixva_map_distance( $origin, $coords );
			if ( $distance > $best ) {
				$best     = $distance;
				$farthest = array(
					'lat'   => (float) $coords['lat'],
					'lng'   => (float) $coords['lng'],
					'label' => (string) $name,
				);
			}
		}

		if ( null !== $farthest && $best > 0 ) {
			$farthest['simulated'] = true;
			/** This filter is documented in inc/tracker-map.php */
			return apply_filters( 'pixva_map_demo_destination', $farthest );
		}

		$rand = pixva_map_seed( 'pixva-map-demo' );
		$dest = array(
			'lat'       => round( (float) $origin['lat'] + 0.018 + ( $rand() * 0.012 ), 5 ),
			'lng'       => round( (float) $origin['lng'] + 0.024 + ( $rand() * 0.016 ), 5 ),
			'label'     => '' !== $label ? $label : $fallback,
			'simulated' => true,
		);

		/**
		 * فیلتر مقصد مسیر دمو.
		 *
		 * @param array $dest مقصد.
		 */
		return apply_filters( 'pixva_map_demo_destination', $dest );
	}
}

if ( ! function_exists( 'pixva_map_demo_payload' ) ) {
	/**
	 * بار خروجی نقشه دمو: مسیر زنده نمایشی روی صفحه اصلی (Master Prompt v8).
	 *
	 * پیشرفت مسیر بر پایه زمان واقعی محاسبه می‌شود تا هر نوسازی، ون تعمیرکار
	 * کمی جلو برود و پس از یک چرخه کامل (پیش‌فرض ۱۵ دقیقه، با فیلتر
	 * pixva_map_demo_cycle قابل تغییر) دوباره از کارگاه شروع کند؛ یعنی بدون
	 * هیچ پرونده‌ای هم نقشه «زنده» به‌نظر می‌رسد و کنسول خطا نمی‌گیرد.
	 *
	 * @return array<string, mixed>
	 */
	function pixva_map_demo_payload() {
		$origin      = pixva_map_origin();
		$destination = pixva_map_demo_destination();
		$route       = pixva_map_route( $origin, $destination, 'pixva-demo-route', 16 );
		$distance    = pixva_map_distance( $origin, $destination );
		$speed       = max( 4, (float) apply_filters( 'pixva_map_average_speed', (float) pixva_option( 'pixva_map_average_speed', 22 ) ) );

		$cycle      = max( 90, (int) apply_filters( 'pixva_map_demo_cycle', 900 ) );
		$progress   = fmod( (float) time(), (float) $cycle ) / (float) $cycle;
		$progress   = max( 0.02, min( 0.995, $progress ) );
		$remaining  = max( 0, $distance * ( 1 - $progress ) );
		$eta        = (int) max( 1, round( ( $remaining / $speed ) * 60 ) );

		$marker = $route[ count( $route ) - 1 ];
		$index  = (int) floor( $progress * ( count( $route ) - 1 ) );
		$next   = min( count( $route ) - 1, $index + 1 );
		$local  = ( $progress * ( count( $route ) - 1 ) ) - $index;
		$marker = array(
			round( $route[ $index ][0] + ( $route[ $next ][0] - $route[ $index ][0] ) * $local, 5 ),
			round( $route[ $index ][1] + ( $route[ $next ][1] - $route[ $index ][1] ) * $local, 5 ),
		);

		$statuses = function_exists( 'pixva_crm_statuses' ) ? pixva_crm_statuses() : array();
		$status   = isset( $statuses['assigned'] ) ? 'assigned' : 'en_route';

		$payload = array(
			'code'        => 'DEMO',
			'status'      => $status,
			'statusLabel' => isset( $statuses[ $status ] ) ? $statuses[ $status ] : __( 'در مسیر مشتری', 'pixva' ),
			'technician'  => array(
				'id'    => 0,
				'name'  => (string) pixva_option( 'pixva_map_demo_tech', __( 'تعمیرکار شیفت امروز', 'pixva' ) ),
				'phone' => '',
				'skill' => (string) pixva_option( 'pixva_map_demo_skill', __( 'بک‌لایت، پنل و برد اصلی', 'pixva' ) ),
			),
			'eta'         => $eta,
			'progress'    => round( $progress, 3 ),
			'distance'    => $distance,
			'route'       => $route,
			'marker'      => $marker,
			'origin'      => array( round( (float) $origin['lat'], 5 ), round( (float) $origin['lng'], 5 ), (string) $origin['label'] ),
			'destination' => array( round( (float) $destination['lat'], 5 ), round( (float) $destination['lng'], 5 ), (string) $destination['label'] ),
			'moving'      => true,
			'arrived'     => false,
			'simulated'   => true,
			'demo'        => true,
			'refresh'     => (int) apply_filters( 'pixva_map_refresh', (int) pixva_option( 'pixva_map_refresh', 20 ) ),
			'updatedAt'   => function_exists( 'pixva_fa_num' ) ? pixva_fa_num( wp_date( 'H:i:s' ) ) : wp_date( 'H:i:s' ),
			'device'      => array(
				'brand' => (string) pixva_option( 'pixva_map_demo_brand', 'Samsung' ),
				'model' => (string) pixva_option( 'pixva_map_demo_model', __( 'نمایش دمو ۵۵ اینچ', 'pixva' ) ),
			),
		);

		/**
		 * فیلتر بار خروجی نقشه دمو.
		 *
		 * @param array $payload بار خروجی.
		 */
		return apply_filters( 'pixva_map_demo_payload', $payload );
	}
}

if ( ! function_exists( 'pixva_map_rate_limited' ) ) {
	/**
	 * محدودسازی نرخ درخواست نقشه.
	 *
	 * @return bool
	 */
	function pixva_map_rate_limited() {
		$ip  = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : 'unknown';
		$key = 'pixva_map_rate_' . md5( $ip . wp_salt( 'nonce' ) );
		$hit = (int) get_transient( $key );

		if ( $hit >= (int) apply_filters( 'pixva_map_rate_max', 240 ) ) {
			return true;
		}

		set_transient( $key, $hit + 1, MINUTE_IN_SECONDS );
		return false;
	}
}

if ( ! function_exists( 'pixva_rest_dispatch_live' ) ) {
	/**
	 * هندلر REST نقشه زنده تعمیرکار.
	 *
	 * @param WP_REST_Request $request درخواست.
	 * @return WP_REST_Response|WP_Error
	 */
	function pixva_rest_dispatch_live( $request ) {
		if ( pixva_map_rate_limited() ) {
			return new WP_Error( 'pixva_map_rate', __( 'تعداد درخواست‌ها زیاد است؛ چند لحظه صبر کنید.', 'pixva' ), array( 'status' => 429 ) );
		}

		// حالت دمو (Master Prompt v8): مسیر زنده نمایشی بدون پرونده و بدون شماره.
		$demo = trim( (string) $request->get_param( 'demo' ) );
		if ( '' !== $demo && '0' !== $demo && 'false' !== strtolower( $demo ) ) {
			$demo_payload = pixva_map_demo_payload();
			if ( empty( $demo_payload ) ) {
				return new WP_Error( 'pixva_map_demo', __( 'داده مسیر دمو ساخته نشد.', 'pixva' ), array( 'status' => 500 ) );
			}

			return rest_ensure_response( $demo_payload );
		}

		$code  = strtoupper( preg_replace( '/[^A-Za-z0-9\-]/', '', (string) $request->get_param( 'code' ) ) );
		$phone = function_exists( 'pixva_normalize_mobile' ) ? pixva_normalize_mobile( (string) $request->get_param( 'phone' ) ) : sanitize_text_field( (string) $request->get_param( 'phone' ) );

		if ( '' === $code || '' === $phone ) {
			return new WP_Error( 'pixva_map_code', __( 'کد پیگیری و شماره همراه هر دو لازم است.', 'pixva' ), array( 'status' => 400 ) );
		}

		if ( function_exists( 'pixva_is_valid_iranian_mobile' ) && ! pixva_is_valid_iranian_mobile( $phone ) ) {
			return new WP_Error( 'pixva_map_phone', __( 'شماره همراه معتبر نیست. نمونه درست: ۰۹۱۲۳۴۵۶۷۸۹', 'pixva' ), array( 'status' => 422 ) );
		}

		if ( ! function_exists( 'pixva_find_order' ) ) {
			return new WP_Error( 'pixva_map_engine', __( 'موتور پرونده‌ها در دسترس نیست.', 'pixva' ), array( 'status' => 500 ) );
		}

		$order = pixva_find_order( $code, $phone );
		if ( ! $order instanceof WP_Post ) {
			return new WP_Error( 'pixva_map_missing', __( 'پرونده‌ای با این کد و شماره همراه پیدا نشد.', 'pixva' ), array( 'status' => 404 ) );
		}

		$payload = pixva_dispatch_live( $order );
		if ( empty( $payload ) ) {
			return new WP_Error( 'pixva_map_payload', __( 'داده موقعیت ساخته نشد.', 'pixva' ), array( 'status' => 500 ) );
		}

		$payload['device'] = array(
			'brand' => (string) get_post_meta( $order->ID, '_pixva_order_brand', true ),
			'model' => (string) get_post_meta( $order->ID, '_pixva_order_model', true ),
		);

		return rest_ensure_response( $payload );
	}
}

if ( ! function_exists( 'pixva_register_tracker_map_route' ) ) {
	/**
	 * ثبت مسیر REST نقشه زنده.
	 *
	 * @return void
	 */
	function pixva_register_tracker_map_route() {
		register_rest_route(
			'pixva/v1',
			'/dispatch-live',
			array(
				'methods'             => 'GET',
				'callback'            => 'pixva_rest_dispatch_live',
				'permission_callback' => '__return_true',
				'args'                => array(
					/*
					 * code و phone برای درخواست واقعی لازم‌اند اما در حالت دمو
					 * (demo=1) فرستاده نمی‌شوند؛ اعتبارسنجی هر دو داخل هندلر و با
					 * همان پیام ۴۰۰ انجام می‌شود.
					 */
					'code'  => array(
						'required'          => false,
						'default'           => '',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'phone' => array(
						'required'          => false,
						'default'           => '',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'demo'  => array(
						'required'          => false,
						'default'           => '',
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);
	}
	add_action( 'rest_api_init', 'pixva_register_tracker_map_route' );
}

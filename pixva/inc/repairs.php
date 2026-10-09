<?php
/**
 * Repair orders domain (§11, §12, §13, §14, §49).
 *
 * Storage: post type `pixva_orders` (private status, custom caps). Meta:
 *  _pixva_order_code        PXV-XXXX-XXXX (random_int, unambiguous alphabet)
 *  _pixva_order_name        customer name                       (PII)
 *  _pixva_order_phone       normalised mobile                   (PII)
 *  _pixva_phone_hash        HMAC of phone (lookup without exposing phone)
 *  _pixva_order_address     address for pickup/onsite           (PII)
 *  _pixva_order_brand       brand label (free text or brand title)
 *  _pixva_order_brand_id    tv_brands id when chosen from list
 *  _pixva_order_model       model text
 *  _pixva_order_problem     diagnosis problem key
 *  _pixva_order_description customer description               (PII-adjacent)
 *  _pixva_order_mode        service mode key
 *  _pixva_order_time        preferred contact time text
 *  _pixva_order_photos      JSON list of private filenames      (PII)
 *  _pixva_order_diagnosis   JSON summary from the wizard (if any)
 *  _pixva_order_status      status key
 *  _pixva_order_steps       JSON history [{s,t,n}] (n = public note)
 *  _pixva_order_notes       internal notes (staff only)
 *  _pixva_order_estimate    staff quote text (shown to owner)
 *  _pixva_customer_id       owning user id
 *  _pixva_technician_id     assigned technician user id
 *  _pixva_order_parts       comma ids of pixva_part
 *  _pixva_warranty_start/_until (Y-m-d), _pixva_warranty_source policy|manual|legacy
 *
 * @package Pixva
 * @since   2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Status definitions in workflow order.
 *
 * @return array<string,array{label:string,desc:string,step:int}>
 */
function pixva_order_statuses() {
	return array(
		'new'       => array(
			'label' => __( 'ثبت شد', 'pixva' ),
			'desc'  => __( 'درخواست ثبت شده و منتظر تماس کارشناس است.', 'pixva' ),
			'step'  => 1,
		),
		'received'  => array(
			'label' => __( 'دستگاه تحویل گرفته شد', 'pixva' ),
			'desc'  => __( 'دستگاه در صف بررسی قرار گرفته است.', 'pixva' ),
			'step'  => 2,
		),
		'diagnosed' => array(
			'label' => __( 'عیب‌یابی انجام شد', 'pixva' ),
			'desc'  => __( 'نتیجه بررسی و هزینه برای شما اعلام می‌شود.', 'pixva' ),
			'step'  => 3,
		),
		'waiting'   => array(
			'label' => __( 'در انتظار تأیید شما', 'pixva' ),
			'desc'  => __( 'برای ادامه تعمیر، تأیید هزینه لازم است.', 'pixva' ),
			'step'  => 3,
		),
		'parts'     => array(
			'label' => __( 'در انتظار قطعه', 'pixva' ),
			'desc'  => __( 'قطعه موردنیاز در حال تأمین است.', 'pixva' ),
			'step'  => 4,
		),
		'repairing' => array(
			'label' => __( 'در حال تعمیر', 'pixva' ),
			'desc'  => __( 'تعمیر دستگاه در حال انجام است.', 'pixva' ),
			'step'  => 4,
		),
		'testing'   => array(
			'label' => __( 'در حال تست', 'pixva' ),
			'desc'  => __( 'دستگاه پس از تعمیر در حال آزمایش است.', 'pixva' ),
			'step'  => 5,
		),
		'ready'     => array(
			'label' => __( 'آماده تحویل', 'pixva' ),
			'desc'  => __( 'دستگاه آماده تحویل است.', 'pixva' ),
			'step'  => 6,
		),
		'delivered' => array(
			'label' => __( 'تحویل شد', 'pixva' ),
			'desc'  => __( 'دستگاه به شما تحویل داده شد.', 'pixva' ),
			'step'  => 7,
		),
		'cancelled' => array(
			'label' => __( 'لغو شد', 'pixva' ),
			'desc'  => __( 'این درخواست لغو شده است.', 'pixva' ),
			'step'  => 0,
		),
	);
}

/**
 * Progress milestones shown in the tracker.
 *
 * @return array<int,string>
 */
function pixva_order_milestones() {
	return array(
		1 => __( 'ثبت', 'pixva' ),
		2 => __( 'پذیرش', 'pixva' ),
		3 => __( 'عیب‌یابی', 'pixva' ),
		4 => __( 'تعمیر', 'pixva' ),
		5 => __( 'تست', 'pixva' ),
		6 => __( 'آماده', 'pixva' ),
		7 => __( 'تحویل', 'pixva' ),
	);
}

/**
 * Generate a unique, unguessable order code (≈ 31^8 ≈ 8.5e11 space).
 *
 * @return string
 */
function pixva_generate_order_code() {
	$alphabet = '23456789ABCDEFGHJKMNPQRSTUVWXYZ';
	$max      = strlen( $alphabet ) - 1;
	for ( $attempt = 0; $attempt < 10; $attempt++ ) {
		$raw = '';
		for ( $i = 0; $i < 8; $i++ ) {
			$raw .= $alphabet[ random_int( 0, $max ) ];
		}
		$code = 'PXV-' . substr( $raw, 0, 4 ) . '-' . substr( $raw, 4, 4 );
		if ( ! pixva_find_order_by_code( $code ) ) {
			return $code;
		}
	}
	return 'PXV-' . strtoupper( wp_generate_password( 4, false, false ) ) . '-' . strtoupper( wp_generate_password( 6, false, false ) );
}

/**
 * Normalise user-typed code (Persian digits, lowercase, spaces).
 *
 * @param string $code Code.
 * @return string '' when malformed.
 */
function pixva_normalize_order_code( $code ) {
	$code = strtoupper( preg_replace( '/\s+/', '', pixva_en_num( (string) $code ) ) );
	return preg_match( '/^PXV-[A-Z0-9]+(-[A-Z0-9]+)*$/', $code ) && strlen( $code ) <= 24 ? $code : '';
}

/**
 * Order id by code (0 when none).
 *
 * @param string $code Normalised code.
 * @return int
 */
function pixva_find_order_by_code( $code ) {
	if ( '' === $code ) {
		return 0;
	}
	$ids = get_posts(
		array(
			'post_type'        => 'pixva_orders',
			'post_status'      => 'any',
			'meta_key'         => '_pixva_order_code', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'meta_value'       => $code, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			'posts_per_page'   => 1,
			'fields'           => 'ids',
			'no_found_rows'    => true,
			'suppress_filters' => true,
		)
	);
	return $ids ? (int) $ids[0] : 0;
}

/**
 * Keyed hash of a phone for comparisons.
 *
 * @param string $phone Phone.
 * @return string
 */
function pixva_phone_hash( $phone ) {
	return hash_hmac( 'sha256', pixva_normalize_mobile( $phone ), wp_salt( 'auth' ) );
}

/**
 * Whether the phone matches the order (constant-time).
 *
 * @param int    $order_id Order.
 * @param string $phone    Phone.
 * @return bool
 */
function pixva_order_phone_matches( $order_id, $phone ) {
	if ( ! pixva_is_valid_iranian_mobile( $phone ) ) {
		return false;
	}
	$stored = (string) get_post_meta( $order_id, '_pixva_phone_hash', true );
	if ( '' === $stored ) {
		$legacy = (string) get_post_meta( $order_id, '_pixva_order_phone', true );
		$stored = '' !== $legacy ? pixva_phone_hash( $legacy ) : '';
	}
	return '' !== $stored && hash_equals( $stored, pixva_phone_hash( $phone ) );
}

/**
 * Verify code + phone with brute-force protection: client rate limit plus
 * a per-code failure lock (5 failures/hour), so neither codes nor phones
 * can be enumerated.
 *
 * @param string $code  Raw code.
 * @param string $phone Raw phone.
 * @return int|WP_Error Order id.
 */
function pixva_verify_order_access( $code, $phone ) {
	$code  = pixva_normalize_order_code( $code );
	$phone = pixva_normalize_mobile( $phone );
	if ( '' === $code || ! pixva_is_valid_iranian_mobile( $phone ) ) {
		return new WP_Error( 'invalid', __( 'کد پیگیری یا شماره همراه به‌درستی وارد نشده است.', 'pixva' ), array( 'status' => 400 ) );
	}
	if ( pixva_code_attempts_locked( $code ) ) {
		return new WP_Error( 'locked', __( 'به دلیل تلاش‌های ناموفق، پیگیری این کد موقتاً قفل شده است.', 'pixva' ), array( 'status' => 429 ) );
	}
	$id = pixva_find_order_by_code( $code );
	if ( $id && pixva_order_phone_matches( $id, $phone ) ) {
		// A correct lookup is never refused by the general client budget, so a
		// client behind a shared address cannot lock real customers out this way.
		pixva_clear_code_attempt_failures( $code );
		return $id;
	}
	// Only failures use the general client budget (enumeration volume). It is
	// read-then-write, so it limits volume and does not prove brute-force resistance.
	if ( pixva_rate_exhausted( 'lookup', 20, 10 * MINUTE_IN_SECONDS ) ) {
		return new WP_Error( 'rate', __( 'تعداد درخواست‌ها زیاد است. چند دقیقه دیگر دوباره تلاش کنید.', 'pixva' ), array( 'status' => 429 ) );
	}
	pixva_rate_count( 'lookup', 10 * MINUTE_IN_SECONDS );
	pixva_record_code_attempt_failure( $code );
	// Same message for unknown code and wrong phone (no oracle).
	return new WP_Error( 'not_found', __( 'پرونده‌ای با این کد و شماره پیدا نشد.', 'pixva' ), array( 'status' => 404 ) );
}

/**
 * Failed-attempt counter scope for one order code.
 *
 * The scope is (code, client key, clock hour). Failures from one client never
 * count against another client, so a person who only knows the code cannot lock
 * the real customer out. The client key comes from REMOTE_ADDR (see
 * pixva_client_key()). Keys are HMAC-hashed so the code does not appear in
 * wp_options.
 *
 * @param string $code   Normalised order code.
 * @param int    $bucket Clock hour index.
 * @param int    $slot   Attempt slot, 1..PIXVA_CODE_ATTEMPT_MAX.
 * @return string
 */
function pixva_code_attempt_slot( $code, $bucket, $slot ) {
	$scope = hash_hmac( 'sha256', strtoupper( (string) $code ) . '|' . pixva_client_key(), wp_salt( 'auth' ) );
	return 'pixva_cf_' . substr( $scope, 0, 32 ) . '_' . (int) $bucket . '_' . (int) $slot;
}

/** Maximum failed attempts per (code, client, hour) before the lookup refuses. */
const PIXVA_CODE_ATTEMPT_MAX = 5;

/**
 * Whether this client has used up its failed attempts for this code this hour.
 *
 * Slots are created with pixva_create_once(), so concurrent failures can never
 * create more than PIXVA_CODE_ATTEMPT_MAX rows.
 *
 * @param string $code Normalised order code.
 * @return bool
 */
function pixva_code_attempts_locked( $code ) {
	$bucket = (int) floor( pixva_now() / HOUR_IN_SECONDS );
	for ( $slot = 1; $slot <= PIXVA_CODE_ATTEMPT_MAX; $slot++ ) {
		if ( null === pixva_option_value( pixva_code_attempt_slot( $code, $bucket, $slot ) ) ) {
			return false;
		}
	}
	return true;
}

/**
 * Record one failed lookup for this (code, client) in the current hour.
 *
 * @param string $code Normalised order code.
 * @return void
 */
function pixva_record_code_attempt_failure( $code ) {
	$bucket = (int) floor( pixva_now() / HOUR_IN_SECONDS );
	for ( $slot = 1; $slot <= PIXVA_CODE_ATTEMPT_MAX; $slot++ ) {
		if ( pixva_create_once( pixva_code_attempt_slot( $code, $bucket, $slot ), (string) pixva_now() ) ) {
			break;
		}
	}
	pixva_count_code_failure_for_monitoring( $code, $bucket );
	// Drop the previous hour's slots for this scope so rows do not pile up.
	foreach ( range( 1, PIXVA_CODE_ATTEMPT_MAX ) as $slot ) {
		pixva_delete_option_row( pixva_code_attempt_slot( $code, $bucket - 1, $slot ), (string) pixva_option_value( pixva_code_attempt_slot( $code, $bucket - 1, $slot ) ) );
	}
}

/**
 * Monitoring only: count failed lookups per code across all clients, per hour.
 *
 * This never blocks anyone (a per-code block is the denial-of-service this design
 * avoids). It fires pixva_suspicious_lookup once when PIXVA_CODE_GLOBAL_ALERT
 * failures are seen for a code in an hour, so site owners can react. The slots
 * are capped at PIXVA_CODE_GLOBAL_ALERT rows per code per hour and are pruned
 * by pixva_prune_expiring_rows().
 *
 * @param string $code   Normalised order code.
 * @param int    $bucket Clock hour index.
 * @return void
 */
function pixva_count_code_failure_for_monitoring( $code, $bucket ) {
	$scope = substr( hash_hmac( 'sha256', strtoupper( (string) $code ), wp_salt( 'auth' ) ), 0, 32 );
	for ( $n = 1; $n <= PIXVA_CODE_GLOBAL_ALERT; $n++ ) {
		$name = 'pixva_cg_' . $scope . '_' . (int) $bucket . '_' . $n;
		if ( pixva_create_once( $name, (string) pixva_now() ) ) {
			if ( PIXVA_CODE_GLOBAL_ALERT === $n ) {
				do_action( 'pixva_suspicious_lookup', substr( $scope, 0, 12 ), $n, (int) $bucket );
			}
			return;
		}
	}
}

/** Failed lookups for one code in one hour that trigger the monitoring action. */
const PIXVA_CODE_GLOBAL_ALERT = 50;

/**
 * Forget this client's failures for the code after a successful lookup.
 *
 * @param string $code Normalised order code.
 * @return void
 */
function pixva_clear_code_attempt_failures( $code ) {
	$bucket = (int) floor( pixva_now() / HOUR_IN_SECONDS );
	foreach ( array( $bucket, $bucket - 1 ) as $b ) {
		foreach ( range( 1, PIXVA_CODE_ATTEMPT_MAX ) as $slot ) {
			$name  = pixva_code_attempt_slot( $code, $b, $slot );
			$value = pixva_option_value( $name );
			if ( null !== $value ) {
				pixva_delete_option_row( $name, $value );
			}
		}
	}
}

/**
 * Create an order from validated data.
 *
 * @param array $d Validated data (name, phone, brand, brand_id, model, problem,
 *                 description, mode, address, time, photos, diagnosis, customer_id).
 * @return int|WP_Error
 */
function pixva_create_order( $d ) {
	$id = pixva_insert_order( $d );
	if ( is_wp_error( $id ) ) {
		return $id;
	}
	do_action( 'pixva_order_created', $id );
	return $id;
}

/**
 * Insert an order and its data, without announcing it.
 *
 * The post is created with post_name = submission id (when given) in the same
 * INSERT as the post row, so a retry can find it by pixva_orders_by_submission()
 * even if the process dies before the reservation is linked.
 *
 * @param array $d Validated data (see pixva_create_order()).
 * @return int|WP_Error
 */
function pixva_insert_order( $d ) {
	$code = pixva_generate_order_code();
	$args = array(
		'post_type'   => 'pixva_orders',
		'post_status' => 'private',
		'post_title'  => $code,
		'post_author' => 0,
	);
	$sid  = pixva_normalize_submission_id( $d['submission_id'] ?? '' );
	if ( '' !== $sid ) {
		$args['post_name'] = $sid;
	}
	$id = wp_insert_post( $args, true );
	if ( is_wp_error( $id ) ) {
		return $id;
	}
	pixva_write_order_data( (int) $id, $d, $code );
	return (int) $id;
}

/**
 * Write the order meta. Idempotent: an adopted order is rewritten with the same
 * data, so a partly written order is completed.
 *
 * @param int    $id   Order id.
 * @param array  $d    Validated data.
 * @param string $code Order code.
 * @return void
 */
function pixva_write_order_data( $id, $d, $code ) {
	$now  = time();
	$meta = array(
		'_pixva_order_code'        => $code,
		'_pixva_order_name'        => $d['name'] ?? '',
		'_pixva_order_phone'       => pixva_normalize_mobile( $d['phone'] ?? '' ),
		'_pixva_phone_hash'        => pixva_phone_hash( $d['phone'] ?? '' ),
		'_pixva_order_address'     => $d['address'] ?? '',
		'_pixva_order_brand'       => $d['brand'] ?? '',
		'_pixva_order_brand_id'    => (int) ( $d['brand_id'] ?? 0 ),
		'_pixva_order_model'       => $d['model'] ?? '',
		'_pixva_order_problem'     => $d['problem'] ?? '',
		'_pixva_order_description' => $d['description'] ?? '',
		'_pixva_order_mode'        => $d['mode'] ?? '',
		'_pixva_order_time'        => $d['time'] ?? '',
		'_pixva_order_photos'      => pixva_json_meta( array_values( (array) ( $d['photos'] ?? array() ) ) ),
		'_pixva_order_diagnosis'   => ! empty( $d['diagnosis'] ) ? pixva_json_meta( $d['diagnosis'] ) : '',
		'_pixva_order_status'      => 'new',
		'_pixva_order_steps'       => pixva_json_meta(
			array(
				array(
					's' => 'new',
					't' => $now,
					'n' => '',
				),
			)
		),
		'_pixva_customer_id'       => (int) ( $d['customer_id'] ?? 0 ),
	);
	$json = array( '_pixva_order_photos', '_pixva_order_diagnosis', '_pixva_order_steps' ); // Already slashed by pixva_json_meta().
	foreach ( $meta as $key => $value ) {
		if ( '' !== $value && 0 !== $value ) {
			update_post_meta( $id, $key, is_string( $value ) && ! in_array( $key, $json, true ) ? wp_slash( $value ) : $value );
		}
	}
}

/**
 * Whether an id is an existing order post.
 *
 * @param int $id Post id.
 * @return bool
 */
function pixva_order_exists( $id ) {
	$post = get_post( (int) $id );
	return is_object( $post ) && 'pixva_orders' === $post->post_type;
}

/**
 * Order ids whose post_name starts with this submission id (lowest id first).
 * Includes WordPress's suffixed slugs (sid-2) and trashed posts. Uses the
 * indexed post_name column (WordPress core: KEY post_name).
 *
 * @param string $sid Normalised submission id.
 * @return int[]
 */
function pixva_orders_by_submission( $sid ) {
	global $wpdb;
	$sid = pixva_normalize_submission_id( $sid );
	if ( '' === $sid ) {
		return array();
	}
	$ids = $wpdb->get_col(
		$wpdb->prepare(
			"SELECT ID FROM {$wpdb->posts} WHERE post_type = %s AND post_name LIKE %s ORDER BY ID ASC LIMIT 20", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			'pixva_orders',
			$wpdb->esc_like( $sid ) . '%'
		) // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
	);
	return array_map( 'intval', (array) $ids );
}

/**
 * Delete order posts for this submission id other than the linked one.
 * Only called once the reservation links $keep: after that no other post can be
 * linked to this id, so the others are unannounced duplicates.
 *
 * @param string $sid  Submission id.
 * @param int    $keep Linked order id.
 * @return void
 */
function pixva_remove_duplicate_orders( $sid, $keep ) {
	foreach ( pixva_orders_by_submission( $sid ) as $id ) {
		if ( $id !== (int) $keep ) {
			wp_delete_post( $id, true );
		}
	}
}

/**
 * Place the order for a submission exactly once.
 *
 * Caller must hold the submission claim (pixva_claim_submission()). The claim is
 * exclusive, so when this runs every earlier attempt for the id has expired.
 *
 * Reservation row pixva_ord_<sid> in wp_options:
 *   creating: {s, k (token), t}  an attempt is placing the order
 *   linked:   {s, o (order id), t}  the order is final and was announced once
 *
 * Steps (all state changes are UNIQUE create or compare-and-replace):
 *  1. Read the reservation. Linked to an existing order: return that order.
 *  2. Otherwise create it (UNIQUE) as "creating" with our token, or replace a
 *     stale "creating" row with ours (compare on the exact old value).
 *  3. Adopt an order a previous attempt already inserted (found by post_name),
 *     otherwise insert one. A post is never inserted when one exists.
 *  4. Link: compare-and-replace our "creating" row with "linked". Only the
 *     request whose link succeeds fires pixva_order_created (the notification).
 *     Duplicates are then deleted.
 *  5. If the link fails, another attempt took over. Re-read and retry. Our
 *     unlinked order is removed by whoever links the reservation.
 *
 * Error handling: a WP_Error from the insert returns the error with the
 * reservation still "creating", so a retry adopts whatever was written. A fatal
 * error at any step leaves the claim pending; after PIXVA_CLAIM_PENDING_TTL the
 * next attempt runs steps 1–4 and recovers the same order.
 *
 * Residual: an attempt that stalls for longer than the claim TTL between step 3
 * and step 4 is fenced at step 4 (its link fails). No other order can be linked.
 * If its insert completes first, the unlinked post is removed by the next
 * attempt that links the reservation. Between the insert and the link there is
 * no DB transaction. Verify on the target engine (see tests/unit/README.md).
 *
 * @param string $sid Submission id (32 hex, normalised here too).
 * @param array  $d   Validated data for pixva_insert_order().
 * @return int|WP_Error Order id.
 */
function pixva_place_order_once( $sid, $d ) {
	$sid = pixva_normalize_submission_id( $sid );
	if ( '' === $sid ) {
		return new WP_Error( 'sid', __( 'شناسه ارسال نامعتبر است.', 'pixva' ), array( 'status' => 400 ) );
	}
	$name  = 'pixva_ord_' . $sid;
	$token = wp_generate_password( 24, false );
	$mine  = (string) wp_json_encode(
		array(
			's' => 'creating',
			'k' => $token,
			't' => pixva_now(),
		)
	);
	$d['submission_id'] = $sid;

	for ( $try = 0; $try < 5; $try++ ) {
		$held = pixva_option_value( $name );
		if ( null === $held ) {
			if ( ! pixva_create_once( $name, $mine ) ) {
				continue; // Created by someone else between the two reads: re-read.
			}
		} else {
			$row = json_decode( $held, true );
			if ( is_array( $row ) && 'linked' === ( $row['s'] ?? '' ) && pixva_order_exists( (int) ( $row['o'] ?? 0 ) ) ) {
				pixva_remove_duplicate_orders( $sid, (int) $row['o'] );
				return (int) $row['o'];
			}
			// "creating" by an expired attempt, or "linked" to a deleted order: take over.
			if ( ! pixva_replace_option_row( $name, $held, $mine ) ) {
				continue;
			}
		}

		// We own the reservation. Adopt an order a previous attempt inserted, or insert one.
		$found = pixva_orders_by_submission( $sid );
		if ( $found ) {
			$id   = $found[0];
			$code = (string) get_post_meta( $id, '_pixva_order_code', true );
			if ( '' === $code ) {
				// Partly written by a crashed attempt: give it a code and matching title.
				$code = pixva_generate_order_code();
				wp_update_post(
					array(
						'ID'         => $id,
						'post_title' => $code,
					)
				);
			}
			pixva_write_order_data( $id, $d, $code );
		} else {
			do_action( 'pixva_order_before_insert', $sid );
			$id = pixva_insert_order( $d );
			if ( is_wp_error( $id ) ) {
				return $id; // Reservation stays "creating" with our token; a retry adopts any partial write.
			}
		}

		do_action( 'pixva_order_before_link', $sid, $id );
		$linked = (string) wp_json_encode(
			array(
				's' => 'linked',
				'o' => $id,
				't' => pixva_now(),
			)
		);
		if ( pixva_replace_option_row( $name, $mine, $linked ) ) {
			pixva_remove_duplicate_orders( $sid, $id );
			do_action( 'pixva_order_created', $id );
			return $id;
		}
		// Taken over while we worked (we were fenced out). Re-read on the next pass.
	}
	return new WP_Error( 'busy', __( 'ثبت درخواست ممکن نشد. لطفاً دوباره تلاش کنید.', 'pixva' ), array( 'status' => 409 ) );
}

/**
 * Status history.
 *
 * @param int $order_id Order.
 * @return array<int,array{s:string,t:int,n:string}>
 */
function pixva_order_history( $order_id ) {
	$steps = json_decode( (string) get_post_meta( $order_id, '_pixva_order_steps', true ), true );
	$out   = array();
	foreach ( is_array( $steps ) ? $steps : array() as $key => $step ) {
		// v1.x stored {status: timestamp}; v2 stores a list.
		if ( is_array( $step ) && isset( $step['s'] ) ) {
			$out[] = array(
				's' => (string) $step['s'],
				't' => (int) ( $step['t'] ?? 0 ),
				'n' => (string) ( $step['n'] ?? '' ),
			);
		} elseif ( is_string( $key ) && is_numeric( $step ) ) {
			$out[] = array(
				's' => $key,
				't' => (int) $step,
				'n' => '',
			);
		}
	}
	return $out;
}

/**
 * Change status, append history, apply warranty on delivery.
 *
 * @param int    $order_id Order.
 * @param string $status   New status.
 * @param string $note     Public note (shown to the customer).
 * @return true|WP_Error
 */
function pixva_set_order_status( $order_id, $status, $note = '' ) {
	$order_id = (int) $order_id;
	$owner    = pixva_order_lock_acquire( $order_id );
	if ( is_wp_error( $owner ) ) {
		return $owner;
	}
	try {
		$outcome = pixva_set_order_status_locked( $order_id, $status, $note, $owner );
	} finally {
		if ( ! pixva_order_lock_release( $order_id, $owner ) ) {
			do_action( 'pixva_order_lock_lost', $order_id );
		}
	}
	if ( is_wp_error( $outcome ) ) {
		return $outcome;
	}
	if ( ! empty( $outcome['changed'] ) ) {
		// Listeners run after the lock is released, so they cannot deadlock or extend it.
		do_action( 'pixva_order_status_changed', $order_id, $outcome['status'], $outcome['from'] );
	}
	return true;
}

/**
 * Run $fn while holding the per-order lock (keyed by order id, never by code).
 *
 * Used for order writes that are not status changes but still read and write
 * order history or ownership. The lock is released even if $fn throws.
 *
 * @param int      $order_id Order id.
 * @param callable $fn       Work to run under the lock.
 * @return mixed|WP_Error The callable's result, or WP_Error when the lock is busy.
 */
function pixva_with_order_lock( $order_id, $fn ) {
	$order_id = (int) $order_id;
	$owner    = pixva_order_lock_acquire( $order_id );
	if ( is_wp_error( $owner ) ) {
		return $owner;
	}
	try {
		$result = $fn();
	} finally {
		if ( ! pixva_order_lock_release( $order_id, $owner ) ) {
			do_action( 'pixva_order_lock_lost', $order_id );
		}
	}
	return $result;
}

/**
 * Per-order mutex for status changes.
 *
 * A status change reads the current status and history, then writes both.
 * Without a lock, two overlapping changes both read the same history and the
 * second write drops the first entry; a change can also be checked against a
 * status that another request has just replaced. The lock covers the whole
 * read-check-write sequence.
 *
 * It is built on pixva_create_once(), so acquiring is atomic at the database
 * level. A lock older than PIXVA_ORDER_LOCK_STALE seconds is treated as left
 * behind by a crashed request and removed with a compare-and-delete, so two
 * processes cannot both take over the same stale lock.
 *
 * @param int $order_id Order.
 * @return string|WP_Error Owner token to pass to pixva_order_lock_release().
 */
function pixva_order_lock_acquire( $order_id ) {
	$name     = 'pixva_olock_' . (int) $order_id;
	$deadline = microtime( true ) + 10;
	do {
		$owner = wp_generate_password( 24, false ) . '|' . pixva_now();
		if ( pixva_create_once( $name, $owner ) ) {
			return $owner;
		}
		$held = pixva_option_value( $name );
		if ( null === $held ) {
			continue; // Released between the two reads; try again at once.
		}
		$taken_at = (int) substr( $held, (int) strrpos( $held, '|' ) + 1 );
		if ( pixva_now() - $taken_at > PIXVA_ORDER_LOCK_STALE ) {
			pixva_delete_option_row( $name, $held ); // Only removes that exact stale owner.
			continue;
		}
		usleep( 50000 );
	} while ( microtime( true ) < $deadline );

	return new WP_Error( 'busy', __( 'این پرونده در حال به‌روزرسانی است؛ چند لحظه بعد دوباره تلاش کنید.', 'pixva' ), array( 'status' => 409 ) );
}

/**
 * Release an order lock this request owns. A lock taken over by someone else
 * is left untouched, because the delete is conditional on the owner token.
 *
 * @param int    $order_id Order.
 * @param string $owner    Token from pixva_order_lock_acquire().
 * @return void
 */
function pixva_order_lock_release( $order_id, $owner ) {
	return pixva_delete_option_row( 'pixva_olock_' . (int) $order_id, $owner );
}

/**
 * Whether the order lock still holds this owner token (fencing check).
 *
 * @param int    $order_id Order.
 * @param string $owner    Token from pixva_order_lock_acquire().
 * @return bool
 */
function pixva_order_lock_held( $order_id, $owner ) {
	return (string) $owner === (string) pixva_option_value( 'pixva_olock_' . (int) $order_id );
}

/** Seconds after which an order lock is considered abandoned. */
const PIXVA_ORDER_LOCK_STALE = 30;

/**
 * Status change body. Call only through pixva_set_order_status(), which holds
 * the order lock.
 *
 * @param int    $order_id Order.
 * @param string $status   Target status key.
 * @param string $note     Public note.
 * @return true|WP_Error
 */
function pixva_set_order_status_locked( $order_id, $status, $note = '', $owner = null ) {
	$statuses = pixva_order_statuses();
	if ( ! isset( $statuses[ $status ] ) ) {
		return new WP_Error( 'status', __( 'وضعیت نامعتبر است.', 'pixva' ) );
	}
	$current = (string) get_post_meta( $order_id, '_pixva_order_status', true );
	if ( $current === $status && '' === $note ) {
		return array( 'changed' => false );
	}
	if ( in_array( $current, array( 'delivered', 'cancelled' ), true ) && ! current_user_can( 'pixva_manage_orders' ) ) {
		return new WP_Error( 'closed', __( 'این پرونده بسته شده و فقط مدیر می‌تواند آن را تغییر دهد.', 'pixva' ) );
	}
	$history   = pixva_order_history( $order_id );
	$history[] = array(
		's' => $status,
		't' => pixva_now(),
		'n' => pixva_substr( sanitize_textarea_field( $note ), 0, 500 ),
	);
	// Fencing: if the lock was taken over while this request was running, stop before writing.
	if ( null !== $owner && ! pixva_order_lock_held( $order_id, $owner ) ) {
		return new WP_Error( 'busy', __( 'این پرونده در حال به‌روزرسانی است؛ چند لحظه بعد دوباره تلاش کنید.', 'pixva' ), array( 'status' => 409 ) );
	}
	update_post_meta( $order_id, '_pixva_order_status', $status );
	update_post_meta( $order_id, '_pixva_order_steps', pixva_json_meta( $history ) );

	if ( 'delivered' === $status && ! get_post_meta( $order_id, '_pixva_warranty_until', true ) ) {
		$days = pixva_warranty_policy_days();
		if ( $days > 0 ) {
			$start = wp_date( 'Y-m-d' );
			update_post_meta( $order_id, '_pixva_warranty_start', $start );
			update_post_meta( $order_id, '_pixva_warranty_until', wp_date( 'Y-m-d', strtotime( $start . ' +' . $days . ' days' ) ) );
			update_post_meta( $order_id, '_pixva_warranty_source', 'policy' );
		}
	}
	return array(
		'changed' => true,
		'status'  => $status,
		'from'    => $current,
	);
}

/**
 * Warranty state of an order.
 *
 * @param int $order_id Order.
 * @return array{state:string,start:string,until:string,days_left:int}
 *         state: none|active|expired
 */
function pixva_order_warranty( $order_id ) {
	$until = (string) get_post_meta( $order_id, '_pixva_warranty_until', true );
	$start = (string) get_post_meta( $order_id, '_pixva_warranty_start', true );
	if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $until ) ) {
		return array(
			'state'     => 'none',
			'start'     => '',
			'until'     => '',
			'days_left' => 0,
		);
	}
	$end  = strtotime( $until . ' 23:59:59' );
	$left = (int) floor( ( $end - time() ) / DAY_IN_SECONDS );
	return array(
		'state'     => $end >= time() ? 'active' : 'expired',
		'start'     => $start,
		'until'     => $until,
		'days_left' => max( 0, $left ),
	);
}

/**
 * Public, PII-free view of an order for tracking (§12).
 *
 * @param int $order_id Order.
 * @return array
 */
function pixva_order_public_view( $order_id ) {
	$statuses = pixva_order_statuses();
	$status   = (string) get_post_meta( $order_id, '_pixva_order_status', true );
	$status   = isset( $statuses[ $status ] ) ? $status : 'new';
	$history  = array();
	foreach ( pixva_order_history( $order_id ) as $step ) {
		if ( ! isset( $statuses[ $step['s'] ] ) ) {
			continue;
		}
		$history[] = array(
			'status' => $step['s'],
			'label'  => $statuses[ $step['s'] ]['label'],
			'date'   => pixva_format_date( $step['t'] ),
			'iso'    => $step['t'] ? gmdate( 'c', $step['t'] ) : '',
			'note'   => $step['n'],
		);
	}
	$warranty = pixva_order_warranty( $order_id );
	return array(
		'code'        => (string) get_post_meta( $order_id, '_pixva_order_code', true ),
		'status'      => $status,
		'label'       => $statuses[ $status ]['label'],
		'description' => $statuses[ $status ]['desc'],
		'step'        => $statuses[ $status ]['step'],
		'milestones'  => pixva_order_milestones(),
		'device'      => trim( get_post_meta( $order_id, '_pixva_order_brand', true ) . ' ' . get_post_meta( $order_id, '_pixva_order_model', true ) ),
		'created'     => pixva_format_date( (int) get_post_time( 'U', true, $order_id ) ),
		'history'     => array_reverse( $history ),
		'estimate'    => (string) get_post_meta( $order_id, '_pixva_order_estimate', true ),
		'warranty'    => array(
			'state' => $warranty['state'],
			'until' => '' !== $warranty['until'] ? pixva_format_date( strtotime( $warranty['until'] ) ) : '',
			'left'  => $warranty['days_left'],
		),
	);
}

/**
 * Private photo filenames of an order.
 *
 * @param int $order_id Order.
 * @return string[]
 */
function pixva_order_photos( $order_id ) {
	$list = json_decode( (string) get_post_meta( $order_id, '_pixva_order_photos', true ), true );
	return array_values( array_filter( is_array( $list ) ? $list : array(), static fn( $f ) => is_string( $f ) && preg_match( '/^[0-9]{6}-[A-Za-z0-9]{24}\.(jpg|png|webp)$/', $f ) ) );
}

/**
 * Signed URL for an order photo (streamed through admin-post after cap check).
 *
 * @param int    $order_id Order.
 * @param string $file     Filename.
 * @return string
 */
function pixva_order_photo_url( $order_id, $file ) {
	return wp_nonce_url( admin_url( 'admin-post.php?action=pixva_order_photo&order=' . (int) $order_id . '&file=' . rawurlencode( $file ) ), 'pixva_photo_' . (int) $order_id );
}

/**
 * Stream a private order photo to an authorised user.
 *
 * @return void
 */
function pixva_stream_order_photo() {
	$order_id = absint( pixva_get_request_var( 'order', '0' ) );
	$file     = sanitize_file_name( pixva_get_request_var( 'file' ) );
	if ( ! $order_id || ! wp_verify_nonce( pixva_get_request_var( '_wpnonce' ), 'pixva_photo_' . $order_id ) || ! current_user_can( 'pixva_view_order', $order_id ) || ! in_array( $file, pixva_order_photos( $order_id ), true ) ) {
		wp_die( esc_html__( 'دسترسی مجاز نیست.', 'pixva' ), '', array( 'response' => 403 ) );
	}
	$path = pixva_private_dir() . '/' . $file;
	if ( ! is_readable( $path ) ) {
		wp_die( esc_html__( 'فایل پیدا نشد.', 'pixva' ), '', array( 'response' => 404 ) );
	}
	$mime = pixva_upload_mimes()[ pathinfo( $file, PATHINFO_EXTENSION ) ] ?? 'application/octet-stream';
	nocache_headers();
	header( 'Content-Type: ' . $mime );
	header( 'Content-Length: ' . filesize( $path ) );
	header( 'Content-Disposition: inline; filename="' . $file . '"' );
	header( 'X-Content-Type-Options: nosniff' );
	header( 'Cache-Control: private, no-store' );
	readfile( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile
	exit;
}
add_action( 'admin_post_pixva_order_photo', 'pixva_stream_order_photo' );

/**
 * Users who can be assigned as technicians.
 *
 * @return WP_User[]
 */
function pixva_technicians() {
	return get_users(
		array(
			'role__in' => array( 'pixva_technician', 'pixva_manager', 'administrator' ),
			'orderby'  => 'display_name',
			'fields'   => array( 'ID', 'display_name' ),
		)
	);
}

/**
 * Notify staff of a new order. No PII in the subject; body contains only
 * the code, device and an admin link (details are behind login).
 *
 * @param int $order_id Order.
 * @return void
 */
function pixva_notify_new_order( $order_id ) {
	$code = (string) get_post_meta( $order_id, '_pixva_order_code', true );
	/* translators: %s: order code. */
	$subject = sprintf( __( 'درخواست تعمیر جدید %s', 'pixva' ), $code );
	$body    = implode(
		"\n",
		array(
			/* translators: %s: order code. */
			sprintf( __( 'کد: %s', 'pixva' ), $code ),
			/* translators: %s: device. */
			sprintf( __( 'دستگاه: %s', 'pixva' ), trim( get_post_meta( $order_id, '_pixva_order_brand', true ) . ' ' . get_post_meta( $order_id, '_pixva_order_model', true ) ) ),
			/* translators: %s: link. */
			sprintf( __( 'جزئیات: %s', 'pixva' ), admin_url( 'post.php?post=' . (int) $order_id . '&action=edit' ) ),
		)
	);
	pixva_safe_mail( pixva_notify_email(), $subject, $body );
}
add_action( 'pixva_order_created', 'pixva_notify_new_order' );

/*
 * ---------------------------------------------------------------------------
 * Admin: order edit screen & list
 * ---------------------------------------------------------------------------
 */

/**
 * Register the order meta box.
 *
 * @return void
 */
function pixva_order_meta_boxes() {
	add_meta_box( 'pixva_order_manage', __( 'مدیریت پرونده', 'pixva' ), 'pixva_render_order_box', 'pixva_orders', 'normal', 'high' );
	remove_meta_box( 'slugdiv', 'pixva_orders', 'normal' );
}
add_action( 'add_meta_boxes_pixva_orders', 'pixva_order_meta_boxes' );

/**
 * Render the order management box.
 *
 * @param WP_Post $post Order.
 * @return void
 */
function pixva_render_order_box( $post ) {
	$id = (int) $post->ID;
	wp_nonce_field( 'pixva_order_admin_' . $id, 'pixva_order_admin_nonce' );
	$code     = (string) get_post_meta( $id, '_pixva_order_code', true );
	$status   = (string) get_post_meta( $id, '_pixva_order_status', true );
	$pii      = current_user_can( 'pixva_view_order_pii' );
	$problems = pixva_diagnosis_problems();
	$problem  = (string) get_post_meta( $id, '_pixva_order_problem', true );
	echo '<table class="form-table" role="presentation"><tbody>';
	echo '<tr><th>' . esc_html__( 'کد پیگیری', 'pixva' ) . '</th><td><code>' . esc_html( $code ? $code : __( '(پس از ذخیره ساخته می‌شود)', 'pixva' ) ) . '</code></td></tr>';
	if ( $pii ) {
		echo '<tr><th><label for="pixva-o-name">' . esc_html__( 'نام مشتری', 'pixva' ) . '</label></th><td><input class="regular-text" id="pixva-o-name" name="pixva_o[name]" value="' . esc_attr( get_post_meta( $id, '_pixva_order_name', true ) ) . '"></td></tr>';
		echo '<tr><th><label for="pixva-o-phone">' . esc_html__( 'شماره همراه', 'pixva' ) . '</label></th><td><input class="regular-text" dir="ltr" id="pixva-o-phone" name="pixva_o[phone]" value="' . esc_attr( get_post_meta( $id, '_pixva_order_phone', true ) ) . '"></td></tr>';
		echo '<tr><th><label for="pixva-o-address">' . esc_html__( 'نشانی', 'pixva' ) . '</label></th><td><textarea class="large-text" rows="2" id="pixva-o-address" name="pixva_o[address]">' . esc_textarea( get_post_meta( $id, '_pixva_order_address', true ) ) . '</textarea></td></tr>';
	}
	echo '<tr><th><label for="pixva-o-brand">' . esc_html__( 'برند و مدل', 'pixva' ) . '</label></th><td><input id="pixva-o-brand" name="pixva_o[brand]" value="' . esc_attr( get_post_meta( $id, '_pixva_order_brand', true ) ) . '"> <input aria-label="' . esc_attr__( 'مدل', 'pixva' ) . '" name="pixva_o[model]" value="' . esc_attr( get_post_meta( $id, '_pixva_order_model', true ) ) . '"></td></tr>';
	echo '<tr><th>' . esc_html__( 'مشکل اعلام‌شده', 'pixva' ) . '</th><td>' . esc_html( $problems[ $problem ]['label'] ?? $problem ) . '<p>' . nl2br( esc_html( get_post_meta( $id, '_pixva_order_description', true ) ) ) . '</p></td></tr>';
	$diag = json_decode( (string) get_post_meta( $id, '_pixva_order_diagnosis', true ), true );
	if ( is_array( $diag ) && ! empty( $diag['causes'] ) ) {
		echo '<tr><th>' . esc_html__( 'نتیجه ابزار تشخیص', 'pixva' ) . '</th><td><ul>';
		foreach ( (array) $diag['causes'] as $c ) {
			echo '<li>' . esc_html( ( $c['label'] ?? '' ) . ' — ' . ( $c['level_label'] ?? '' ) ) . '</li>';
		}
		echo '</ul></td></tr>';
	}
	$modes = array(
		'dropoff' => __( 'تحویل در کارگاه', 'pixva' ),
		'pickup'  => __( 'دریافت از محل', 'pixva' ),
		'onsite'  => __( 'بازدید در محل', 'pixva' ),
	);
	$mode  = (string) get_post_meta( $id, '_pixva_order_mode', true );
	if ( $mode || get_post_meta( $id, '_pixva_order_time', true ) ) {
		echo '<tr><th>' . esc_html__( 'شیوه و زمان', 'pixva' ) . '</th><td>' . esc_html( trim( ( $modes[ $mode ] ?? '' ) . ' — ' . get_post_meta( $id, '_pixva_order_time', true ), ' —' ) ) . '</td></tr>';
	}
	$photos = pixva_order_photos( $id );
	if ( $photos ) {
		echo '<tr><th>' . esc_html__( 'تصاویر مشتری', 'pixva' ) . '</th><td>';
		foreach ( $photos as $i => $file ) {
			/* translators: %d: photo number. */
			echo '<a class="button" target="_blank" rel="noopener" href="' . esc_url( pixva_order_photo_url( $id, $file ) ) . '">' . esc_html( sprintf( __( 'تصویر %d', 'pixva' ), $i + 1 ) ) . '</a> ';
		}
		echo '</td></tr>';
	}
	echo '<tr><th><label for="pixva-o-status">' . esc_html__( 'وضعیت', 'pixva' ) . '</label></th><td><select id="pixva-o-status" name="pixva_o[status]">';
	foreach ( pixva_order_statuses() as $key => $def ) {
		echo '<option value="' . esc_attr( $key ) . '" ' . selected( $status ? $status : 'new', $key, false ) . '>' . esc_html( $def['label'] ) . '</option>';
	}
	echo '</select></td></tr>';
	echo '<tr><th><label for="pixva-o-note">' . esc_html__( 'یادداشت برای مشتری', 'pixva' ) . '</label></th><td><textarea class="large-text" rows="2" id="pixva-o-note" name="pixva_o[note]" aria-describedby="pixva-o-note-h"></textarea><p class="description" id="pixva-o-note-h">' . esc_html__( 'با ذخیره، همراه تغییر وضعیت در صفحه پیگیری مشتری دیده می‌شود.', 'pixva' ) . '</p></td></tr>';
	echo '<tr><th><label for="pixva-o-estimate">' . esc_html__( 'برآورد/هزینه اعلام‌شده', 'pixva' ) . '</label></th><td><input class="regular-text" id="pixva-o-estimate" name="pixva_o[estimate]" value="' . esc_attr( get_post_meta( $id, '_pixva_order_estimate', true ) ) . '"></td></tr>';
	$tech = (int) get_post_meta( $id, '_pixva_technician_id', true );
	echo '<tr><th><label for="pixva-o-tech">' . esc_html__( 'تکنسین', 'pixva' ) . '</label></th><td><select id="pixva-o-tech" name="pixva_o[technician]"><option value="0">' . esc_html__( '— تعیین نشده —', 'pixva' ) . '</option>';
	foreach ( pixva_technicians() as $u ) {
		echo '<option value="' . esc_attr( $u->ID ) . '" ' . selected( $tech, (int) $u->ID, false ) . '>' . esc_html( $u->display_name ) . '</option>';
	}
	echo '</select></td></tr>';
	echo '<tr><th><label for="pixva-o-notes">' . esc_html__( 'یادداشت داخلی', 'pixva' ) . '</label></th><td><textarea class="large-text" rows="3" id="pixva-o-notes" name="pixva_o[notes]">' . esc_textarea( get_post_meta( $id, '_pixva_order_notes', true ) ) . '</textarea><p class="description">' . esc_html__( 'هرگز به مشتری نمایش داده نمی‌شود.', 'pixva' ) . '</p></td></tr>';
	echo '<tr><th>' . esc_html__( 'گارانتی', 'pixva' ) . '</th><td><label>' . esc_html__( 'شروع', 'pixva' ) . ' <input type="date" name="pixva_o[w_start]" value="' . esc_attr( get_post_meta( $id, '_pixva_warranty_start', true ) ) . '"></label> <label>' . esc_html__( 'پایان', 'pixva' ) . ' <input type="date" name="pixva_o[w_until]" value="' . esc_attr( get_post_meta( $id, '_pixva_warranty_until', true ) ) . '"></label>';
	$days = pixva_warranty_policy_days();
	echo '<p class="description">' . ( $days ? esc_html( sprintf( /* translators: %s: days. */ __( 'با تغییر وضعیت به «تحویل شد»، گارانتی %s روزه طبق سیاست ثبت‌شده اعمال می‌شود؛ می‌توانید دستی تغییرش دهید.', 'pixva' ), pixva_fa_num( $days ) ) ) : esc_html__( 'سیاست گارانتی پیش‌فرض تنظیم نشده؛ در صورت ارائه گارانتی، تاریخ‌ها را دستی وارد کنید.', 'pixva' ) ) . '</p></td></tr>';
	$history = pixva_order_history( $id );
	if ( $history ) {
		echo '<tr><th>' . esc_html__( 'تاریخچه', 'pixva' ) . '</th><td><ol>';
		foreach ( $history as $h ) {
			echo '<li>' . esc_html( ( pixva_order_statuses()[ $h['s'] ]['label'] ?? $h['s'] ) . ' — ' . pixva_format_date( $h['t'] ) . ( $h['n'] ? ' — ' . $h['n'] : '' ) ) . '</li>';
		}
		echo '</ol></td></tr>';
	}
	echo '</tbody></table>';
}

/**
 * Save the order management box.
 *
 * @param int $post_id Order id.
 * @return void
 */
function pixva_save_order_box( $post_id ) {
	if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $post_id ) ) {
		return;
	}
	if ( ! isset( $_POST['pixva_order_admin_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['pixva_order_admin_nonce'] ) ), 'pixva_order_admin_' . $post_id ) ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	$in = isset( $_POST['pixva_o'] ) && is_array( $_POST['pixva_o'] ) ? wp_unslash( $_POST['pixva_o'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized per key.

	if ( ! get_post_meta( $post_id, '_pixva_order_code', true ) ) {
		// Initialises history and status, so it must not race a status change.
		pixva_with_order_lock(
			$post_id,
			static function () use ( $post_id ) {
				$code = pixva_generate_order_code();
				update_post_meta( $post_id, '_pixva_order_code', $code );
				remove_action( 'save_post_pixva_orders', 'pixva_save_order_box', 10 );
				wp_update_post(
					array(
						'ID'          => $post_id,
						'post_title'  => $code,
						'post_status' => 'private',
					)
				);
				add_action( 'save_post_pixva_orders', 'pixva_save_order_box', 10, 1 );
				update_post_meta(
					$post_id,
					'_pixva_order_steps',
					pixva_json_meta(
						array(
							array(
								's' => 'new',
								't' => time(),
								'n' => '',
							),
						)
					)
				);
				update_post_meta( $post_id, '_pixva_order_status', 'new' );
			}
		);
	}

	if ( current_user_can( 'pixva_view_order_pii' ) ) {
		if ( isset( $in['name'] ) ) {
			update_post_meta( $post_id, '_pixva_order_name', wp_slash( sanitize_text_field( (string) $in['name'] ) ) );
		}
		if ( isset( $in['phone'] ) && pixva_is_valid_iranian_mobile( (string) $in['phone'] ) ) {
			update_post_meta( $post_id, '_pixva_order_phone', pixva_normalize_mobile( (string) $in['phone'] ) );
			update_post_meta( $post_id, '_pixva_phone_hash', pixva_phone_hash( (string) $in['phone'] ) );
		}
		if ( isset( $in['address'] ) ) {
			update_post_meta( $post_id, '_pixva_order_address', wp_slash( sanitize_textarea_field( (string) $in['address'] ) ) );
		}
	}
	foreach ( array(
		'brand'    => '_pixva_order_brand',
		'model'    => '_pixva_order_model',
		'estimate' => '_pixva_order_estimate',
	) as $k => $meta ) {
		if ( isset( $in[ $k ] ) ) {
			update_post_meta( $post_id, $meta, wp_slash( sanitize_text_field( (string) $in[ $k ] ) ) );
		}
	}
	if ( isset( $in['notes'] ) ) {
		update_post_meta( $post_id, '_pixva_order_notes', wp_slash( sanitize_textarea_field( (string) $in['notes'] ) ) );
	}
	if ( isset( $in['technician'] ) ) {
		$tech = absint( $in['technician'] );
		if ( $tech && ( user_can( $tech, 'pixva_work_orders' ) || user_can( $tech, 'pixva_manage_orders' ) ) ) {
			update_post_meta( $post_id, '_pixva_technician_id', $tech );
		} elseif ( 0 === $tech ) {
			delete_post_meta( $post_id, '_pixva_technician_id' );
		}
	}
	foreach ( array(
		'w_start' => '_pixva_warranty_start',
		'w_until' => '_pixva_warranty_until',
	) as $k => $meta ) {
		if ( ! isset( $in[ $k ] ) ) {
			continue;
		}
		$v = sanitize_text_field( (string) $in[ $k ] );
		$o = (string) get_post_meta( $post_id, $meta, true );
		if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $v ) ) {
			if ( $v !== $o ) {
				update_post_meta( $post_id, $meta, $v );
				update_post_meta( $post_id, '_pixva_warranty_source', 'manual' );
			}
		} elseif ( '' === $v && '' !== $o ) {
			delete_post_meta( $post_id, $meta );
		}
	}
	if ( isset( $in['status'] ) ) {
		pixva_set_order_status( $post_id, sanitize_key( (string) $in['status'] ), isset( $in['note'] ) ? (string) $in['note'] : '' );
	}
}
add_action( 'save_post_pixva_orders', 'pixva_save_order_box', 10, 1 );

/**
 * Orders are always private, whatever the publish box says.
 *
 * @param array $data Post data.
 * @return array
 */
function pixva_force_private_orders( $data ) {
	if ( in_array( $data['post_type'], array( 'pixva_orders', 'pixva_inbox' ), true ) && in_array( $data['post_status'], array( 'publish', 'future', 'pending' ), true ) ) {
		$data['post_status'] = 'private';
	}
	return $data;
}
add_filter( 'wp_insert_post_data', 'pixva_force_private_orders' );

/**
 * Admin list columns.
 *
 * @param array $cols Columns.
 * @return array
 */
function pixva_order_columns( $cols ) {
	return array(
		'cb'           => $cols['cb'] ?? '',
		'title'        => __( 'کد', 'pixva' ),
		'pixva_status' => __( 'وضعیت', 'pixva' ),
		'pixva_device' => __( 'دستگاه', 'pixva' ),
		'pixva_tech'   => __( 'تکنسین', 'pixva' ),
		'date'         => __( 'تاریخ', 'pixva' ),
	);
}
add_filter( 'manage_pixva_orders_posts_columns', 'pixva_order_columns' );

/**
 * Admin list cells.
 *
 * @param string $col     Column.
 * @param int    $post_id Order.
 * @return void
 */
function pixva_order_column_cells( $col, $post_id ) {
	if ( 'pixva_status' === $col ) {
		$s = (string) get_post_meta( $post_id, '_pixva_order_status', true );
		echo esc_html( pixva_order_statuses()[ $s ]['label'] ?? $s );
	} elseif ( 'pixva_device' === $col ) {
		echo esc_html( trim( get_post_meta( $post_id, '_pixva_order_brand', true ) . ' ' . get_post_meta( $post_id, '_pixva_order_model', true ) ) );
	} elseif ( 'pixva_tech' === $col ) {
		$t = (int) get_post_meta( $post_id, '_pixva_technician_id', true );
		echo esc_html( $t ? (string) get_the_author_meta( 'display_name', $t ) : '—' );
	}
}
add_action( 'manage_pixva_orders_posts_custom_column', 'pixva_order_column_cells', 10, 2 );

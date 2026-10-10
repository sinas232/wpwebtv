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
 * Order id created for a form submission id (idempotent-recovery lookup; 0 when none).
 *
 * @param string $sid Submission id.
 * @return int
 */
function pixva_find_order_by_submission( $sid ) {
	$sid = pixva_submission_normalize_id( $sid );
	if ( '' === $sid ) {
		return 0;
	}
	$ids = get_posts(
		array(
			'post_type'        => 'pixva_orders',
			'post_status'      => 'any',
			'meta_key'         => '_pixva_submission_id', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'meta_value'       => $sid, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			'posts_per_page'   => 1,
			'fields'           => 'ids',
			'no_found_rows'    => true,
			'suppress_filters' => true,
		)
	);
	return $ids ? (int) $ids[0] : 0;
}

/**
 * Raw (database-form) value of one meta row — first row, mirroring
 * get_post_meta( …, true ) — without touching the per-request meta cache.
 *
 * Used as the compare value of compare-and-swap writes so concurrent
 * requests arbitrate on the database, not on a stale cache.
 *
 * @param int    $post_id Post id.
 * @param string $key     Meta key.
 * @return string|null Null when the row does not exist.
 */
function pixva_meta_raw( $post_id, $key ) {
	global $wpdb;
	return $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- CAS read, must not be cached.
		$wpdb->prepare(
			'SELECT meta_value FROM ' . $wpdb->postmeta . ' WHERE post_id = %d AND meta_key = %s ORDER BY meta_id ASC LIMIT 1',
			(int) $post_id,
			$key
		)
	);
}

/**
 * Compare-and-swap write of one meta row: updates only when the stored value
 * is still exactly $old_raw.
 *
 * @param int         $post_id Post id.
 * @param string      $key     Meta key.
 * @param string|null $old_raw Expected current value (null = match any existing row).
 * @param string      $new     New value already in stored form (not slashed).
 * @return int|false Rows affected (0 = lost race), false on database error.
 */
function pixva_update_meta_cas( $post_id, $key, $old_raw, $new ) {
	global $wpdb;
	$where = array(
		'post_id'  => (int) $post_id,
		'meta_key' => $key,
	);
	if ( null !== $old_raw ) {
		$where['meta_value'] = $old_raw;
	}
	$n = $wpdb->update( $wpdb->postmeta, array( 'meta_value' => (string) $new ), $where, null, null ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- optimistic concurrency primitive.
	return false === $n ? false : (int) $n;
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
	if ( ! pixva_rate_limit( 'lookup', 20, 10 * MINUTE_IN_SECONDS ) ) {
		return new WP_Error( 'rate', __( 'تعداد درخواست‌ها زیاد است. چند دقیقه دیگر دوباره تلاش کنید.', 'pixva' ), array( 'status' => 429 ) );
	}
	$code  = pixva_normalize_order_code( $code );
	$phone = pixva_normalize_mobile( $phone );
	if ( '' === $code || ! pixva_is_valid_iranian_mobile( $phone ) ) {
		return new WP_Error( 'invalid', __( 'کد پیگیری یا شماره همراه به‌درستی وارد نشده است.', 'pixva' ), array( 'status' => 400 ) );
	}
	$lock_key = 'pixva_codefail_' . md5( $code );
	if ( (int) get_transient( $lock_key ) >= 5 ) {
		return new WP_Error( 'locked', __( 'به دلیل تلاش‌های ناموفق، پیگیری این کد موقتاً قفل شده است.', 'pixva' ), array( 'status' => 429 ) );
	}
	$id = pixva_find_order_by_code( $code );
	if ( ! $id || ! pixva_order_phone_matches( $id, $phone ) ) {
		set_transient( $lock_key, (int) get_transient( $lock_key ) + 1, HOUR_IN_SECONDS );
		// Same message for unknown code and wrong phone (no oracle).
		return new WP_Error( 'not_found', __( 'پرونده‌ای با این کد و شماره پیدا نشد.', 'pixva' ), array( 'status' => 404 ) );
	}
	delete_transient( $lock_key );
	return $id;
}

/**
 * Create an order from validated data.
 *
 * @param array $d Validated data (name, phone, brand, brand_id, model, problem,
 *                 description, mode, address, time, photos, diagnosis, customer_id).
 * @return int|WP_Error
 */
function pixva_create_order( $d ) {
	$code = pixva_generate_order_code();
	$pid  = pixva_submission_normalize_id( $d['submission_id'] ?? '' );
	$args = array(
		'post_type'   => 'pixva_orders',
		'post_status' => 'private',
		'post_title'  => $code,
		'post_author' => 0,
	);
	// The idempotency key is written by the insert itself so a crash between
	// the post row and the first meta write can never orphan an untracked order.
	if ( '' !== $pid ) {
		$args['meta_input'] = array( '_pixva_submission_id' => $pid );
	}
	$id = wp_insert_post( $args, true );
	if ( is_wp_error( $id ) ) {
		return $id;
	}
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
	do_action( 'pixva_order_created', $id );
	return (int) $id;
}

/**
 * Parse a raw `_pixva_order_steps` value into a normalised history list.
 *
 * @param string $raw Stored JSON (or null/'' when absent).
 * @return array<int,array{s:string,t:int,n:string}>
 */
function pixva_parse_order_history( $raw ) {
	$steps = json_decode( (string) $raw, true );
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
 * Status history.
 *
 * @param int $order_id Order.
 * @return array<int,array{s:string,t:int,n:string}>
 */
function pixva_order_history( $order_id ) {
	return pixva_parse_order_history( (string) get_post_meta( $order_id, '_pixva_order_steps', true ) );
}

/**
 * Change status, append history, apply warranty on delivery.
 *
 * Concurrency: both writes are compare-and-swap operations against the
 * database — the status update is guarded by the previously observed value
 * and the history append by the previously observed JSON — so a stale writer
 * can never overwrite a newer one and history entries are append-only. On a
 * lost race the caller receives a `conflict` WP_Error instead of a silent
 * overwrite. Two separate rows cannot be updated in one atomic unit through
 * the portable WordPress meta API (no transaction primitive); under extreme
 * concurrent edits the history keeps every event and the status reflects the
 * last successful guarded write.
 *
 * @param int    $order_id Order.
 * @param string $status   New status.
 * @param string $note     Public note (shown to the customer).
 * @return true|WP_Error WP_Error codes: status, closed, conflict, db.
 */
function pixva_set_order_status( $order_id, $status, $note = '' ) {
	$statuses = pixva_order_statuses();
	if ( ! isset( $statuses[ $status ] ) ) {
		return new WP_Error( 'status', __( 'وضعیت نامعتبر است.', 'pixva' ) );
	}
	$note        = pixva_substr( sanitize_textarea_field( $note ), 0, 500 );
	$from        = null;
	$status_done = false;
	$steps_done  = false;
	$entry       = null;
	for ( $attempt = 0; $attempt < 5 && ! ( $status_done && $steps_done ); $attempt++ ) {
		if ( ! $status_done ) {
			$raw_status = pixva_meta_raw( $order_id, '_pixva_order_status' );
			$current    = (string) $raw_status;
			if ( null === $from ) {
				$from = $current;
			}
			if ( $current === $status && '' === $note ) {
				return true;
			}
			if ( in_array( $current, array( 'delivered', 'cancelled' ), true ) && ! current_user_can( 'pixva_manage_orders' ) ) {
				return new WP_Error( 'closed', __( 'این پرونده بسته شده و فقط مدیر می‌تواند آن را تغییر دهد.', 'pixva' ) );
			}
			if ( $current === $status ) {
				$status_done = true; // Same value: no write needed (note-only change).
			} elseif ( null === $raw_status ) {
				// Row missing (never initialised): plain insert, then verify.
				add_post_meta( $order_id, '_pixva_order_status', $status );
				$status_done = $status === (string) pixva_meta_raw( $order_id, '_pixva_order_status' );
			} else {
				$n = pixva_update_meta_cas( $order_id, '_pixva_order_status', $raw_status, $status );
				if ( false === $n ) {
					return new WP_Error( 'db', __( 'به‌روزرسانی انجام نشد. دوباره تلاش کنید.', 'pixva' ), array( 'status' => 500 ) );
				}
				if ( 1 === $n ) {
					$status_done = true;
				} else {
					// Lost the race: the status changed under us.
					$fresh = (string) pixva_meta_raw( $order_id, '_pixva_order_status' );
					if ( $fresh === $status ) {
						$status_done = true; // Same transition applied concurrently.
					} else {
						return new WP_Error(
							'conflict',
							__( 'پرونده همزمان توسط کاربر دیگری تغییر کرد. وضعیت را دوباره بارگذاری کنید.', 'pixva' ),
							array( 'status' => 409 )
						);
					}
				}
			}
			if ( $status_done ) {
				wp_cache_delete( (int) $order_id, 'post_meta' );
			}
		}
		if ( $steps_done ) {
			continue;
		}
		if ( null === $entry ) {
			$entry = array(
				's' => $status,
				't' => time(),
				'n' => $note,
			);
		}
		$raw_steps = pixva_meta_raw( $order_id, '_pixva_order_steps' );
		$history   = pixva_parse_order_history( $raw_steps );
		$history[] = $entry;
		$new_json  = (string) wp_json_encode( $history, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
		if ( null === $raw_steps ) {
			if ( add_post_meta( $order_id, '_pixva_order_steps', wp_slash( $new_json ) ) ) {
				$steps_done = true;
				wp_cache_delete( (int) $order_id, 'post_meta' );
			}
			continue; // Row appeared underneath us: retry with the fresh value.
		}
		$n = pixva_update_meta_cas( $order_id, '_pixva_order_steps', $raw_steps, $new_json );
		if ( false === $n ) {
			return new WP_Error( 'db', __( 'تاریخچه پرونده به‌روزرسانی نشد. دوباره تلاش کنید.', 'pixva' ), array( 'status' => 500 ) );
		}
		if ( 1 === $n ) {
			$steps_done = true;
			wp_cache_delete( (int) $order_id, 'post_meta' );
		} // 0 = another writer appended first: re-read and append to the fresh list.
	}
	if ( ! $status_done || ! $steps_done ) {
		return new WP_Error(
			'conflict',
			__( 'پرونده همزمان توسط کاربر دیگری تغییر کرد. وضعیت را دوباره بارگذاری کنید.', 'pixva' ),
			array( 'status' => 409 )
		);
	}

	if ( 'delivered' === $status && ! get_post_meta( $order_id, '_pixva_warranty_until', true ) ) {
		$days = pixva_warranty_policy_days();
		if ( $days > 0 ) {
			$start = wp_date( 'Y-m-d' );
			update_post_meta( $order_id, '_pixva_warranty_start', $start );
			update_post_meta( $order_id, '_pixva_warranty_until', wp_date( 'Y-m-d', strtotime( $start . ' +' . $days . ' days' ) ) );
			update_post_meta( $order_id, '_pixva_warranty_source', 'policy' );
		}
	}
	do_action( 'pixva_order_status_changed', $order_id, $status, (string) $from );
	return true;
}

/**
 * Append one line to the internal staff notes with optimistic concurrency.
 *
 * Every write path that appends (never rewrites) notes goes through here, so
 * two concurrent notes cannot silently overwrite each other: the append is a
 * CAS against the previously read value and retries on a lost race.
 *
 * @param int    $order_id Order.
 * @param string $text     Note text (already sanitised by the caller).
 * @param string $author   Display name shown in the line header.
 * @return true|WP_Error
 */
function pixva_append_order_note( $order_id, $text, $author ) {
	$text = trim( (string) $text );
	if ( '' === $text ) {
		return true;
	}
	$line = '[' . wp_date( 'Y-m-d H:i' ) . ' — ' . trim( (string) $author ) . '] ' . $text;
	for ( $attempt = 0; $attempt < 5; $attempt++ ) {
		$raw  = pixva_meta_raw( $order_id, '_pixva_order_notes' );
		$prev = null === $raw ? '' : (string) $raw;
		$new  = trim( $prev . "\n" . $line );
		if ( null === $raw ) {
			// First note on this order: add_post_meta( unique ) arbitrates on the
			// meta index check; on a lost race re-read and retry the append.
			if ( add_post_meta( $order_id, '_pixva_order_notes', wp_slash( $new ), true ) ) {
				wp_cache_delete( (int) $order_id, 'post_meta' );
				return true;
			}
			continue;
		}
		$n = pixva_update_meta_cas( $order_id, '_pixva_order_notes', $raw, $new );
		if ( false === $n ) {
			return new WP_Error( 'db', __( 'یادداشت ذخیره نشد. دوباره تلاش کنید.', 'pixva' ), array( 'status' => 500 ) );
		}
		if ( 1 === $n ) {
			wp_cache_delete( (int) $order_id, 'post_meta' );
			return true;
		}
	}
	return new WP_Error(
		'conflict',
		__( 'یادداشت همزمان توسط کاربر دیگری ثبت شد. صفحه را تازه کنید.', 'pixva' ),
		array( 'status' => 409 )
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
	// Sent-marker: an idempotent-recovery retry must not email staff twice.
	if ( get_post_meta( $order_id, '_pixva_notify_sent', true ) ) {
		return;
	}
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
	if ( pixva_safe_mail( pixva_notify_email(), $subject, $body ) ) {
		update_post_meta( $order_id, '_pixva_notify_sent', 1 );
	}
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
	echo '<tr><th><label for="pixva-o-notes">' . esc_html__( 'یادداشت داخلی', 'pixva' ) . '</label></th><td><textarea class="large-text" rows="3" id="pixva-o-notes" name="pixva_o[notes]">' . esc_textarea( get_post_meta( $id, '_pixva_order_notes', true ) ) . '</textarea><input type="hidden" name="pixva_o[notes_base]" value="' . esc_attr( (string) pixva_meta_raw( $id, '_pixva_order_notes' ) ) . '"><p class="description">' . esc_html__( 'هرگز به مشتری نمایش داده نمی‌شود.', 'pixva' ) . '</p></td></tr>';
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
		// Optimistic concurrency: the form carries the value it was rendered
		// with; if the stored notes changed in the meantime the save is skipped
		// (with a notice) instead of silently overwriting the other edit.
		$normalize = static function ( $v ) {
			return str_replace( array( "\r\n", "\r" ), "\n", (string) $v );
		};
		$raw  = pixva_meta_raw( $post_id, '_pixva_order_notes' );
		$cur  = null === $raw ? '' : (string) $raw;
		$new  = sanitize_textarea_field( (string) $in['notes'] );
		$base = isset( $in['notes_base'] ) ? $normalize( $in['notes_base'] ) : null;
		if ( $new !== $cur ) {
			if ( null === $base || $base !== $normalize( $cur ) ) {
				set_transient( 'pixva_notes_conflict_' . get_current_user_id(), 1, 60 );
			} elseif ( null === $raw ) {
				if ( '' !== $new && ! add_post_meta( $post_id, '_pixva_order_notes', wp_slash( $new ), true ) ) {
					set_transient( 'pixva_notes_conflict_' . get_current_user_id(), 1, 60 );
				}
			} else {
				$n = pixva_update_meta_cas( $post_id, '_pixva_order_notes', $raw, $new );
				if ( false === $n || 0 === $n ) {
					set_transient( 'pixva_notes_conflict_' . get_current_user_id(), 1, 60 );
				}
			}
		}
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
 * Show a notice when a concurrent notes edit was detected and skipped.
 *
 * @return void
 */
function pixva_notes_conflict_notice() {
	$uid = get_current_user_id();
	if ( ! $uid || ! get_transient( 'pixva_notes_conflict_' . $uid ) ) {
		return;
	}
	delete_transient( 'pixva_notes_conflict_' . $uid );
	echo '<div class="notice notice-warning is-dismissible"><p>' . esc_html__( 'یادداشت‌های داخلی همزمان توسط کاربر دیگری تغییر کرده بود؛ ذخیرهٔ یادداشت انجام نشد تا روی نسخهٔ تازه‌تر نوشته نشود. صفحه را تازه کنید و در صورت نیاز دوباره وارد کنید.', 'pixva' ) . '</p></div>';
}
add_action( 'admin_notices', 'pixva_notes_conflict_notice' );

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
		$label = pixva_order_statuses()[ $s ]['label'] ?? $s;
		echo '<span class="pixva-badge pixva-badge--' . esc_attr( $s ? $s : 'new' ) . '">' . esc_html( $label ) . '</span>';
	} elseif ( 'pixva_device' === $col ) {
		echo esc_html( trim( get_post_meta( $post_id, '_pixva_order_brand', true ) . ' ' . get_post_meta( $post_id, '_pixva_order_model', true ) ) );
	} elseif ( 'pixva_tech' === $col ) {
		$t = (int) get_post_meta( $post_id, '_pixva_technician_id', true );
		echo esc_html( $t ? (string) get_the_author_meta( 'display_name', $t ) : '—' );
	}
}
add_action( 'manage_pixva_orders_posts_custom_column', 'pixva_order_column_cells', 10, 2 );

/*
 * ---------------------------------------------------------------------------
 * Orders list workflow: status filter dropdown + search by code/phone/name.
 * All server-side; capability checks remain on the CPT + screen access.
 * ---------------------------------------------------------------------------
 */

/**
 * Status filter dropdown on the orders list table.
 *
 * @param string $post_type Current list post type.
 * @return void
 */
function pixva_orders_admin_filter( $post_type ) {
	if ( 'pixva_orders' !== $post_type || ! current_user_can( 'pixva_manage_orders' ) ) {
		return;
	}
	$current = isset( $_GET['pixva_status'] ) ? sanitize_key( wp_unslash( $_GET['pixva_status'] ) ) : '';
	echo '<select name="pixva_status">';
	echo '<option value="">' . esc_html__( 'همه وضعیت‌ها', 'pixva' ) . '</option>';
	foreach ( pixva_order_statuses() as $key => $def ) {
		printf(
			'<option value="%1$s" %2$s>%3$s</option>',
			esc_attr( $key ),
			selected( $current, $key, false ),
			esc_html( $def['label'] )
		);
	}
	echo '</select>';
}
add_action( 'restrict_manage_posts', 'pixva_orders_admin_filter' );

/**
 * Apply the status filter to the orders list query.
 *
 * @param WP_Query $q Query.
 * @return void
 */
function pixva_orders_admin_query( $q ) {
	if ( ! is_admin() || ! $q->is_main_query() ) {
		return;
	}
	$type = $q->get( 'post_type' );
	if ( 'pixva_orders' !== $type || ! current_user_can( 'pixva_manage_orders' ) ) {
		return;
	}
	$status = isset( $_GET['pixva_status'] ) ? sanitize_key( wp_unslash( $_GET['pixva_status'] ) ) : '';
	if ( '' !== $status && array_key_exists( $status, pixva_order_statuses() ) ) {
		$q->set(
			'meta_query',
			array(
				array(
					'key'   => '_pixva_order_status',
					'value' => $status,
				),
			)
		);
	}
}
add_action( 'pre_get_posts', 'pixva_orders_admin_query' );

/**
 * Extend the orders list search with customer phone and name (staff need
 * both; the screen itself is gated by pixva_manage_orders). The default
 * title search already matches the tracking code (post_title).
 *
 * @param string    $search SQL fragment.
 * @param WP_Query  $q      Query.
 * @return string
 */
function pixva_orders_admin_search( $search, $q ) {
	if ( ! is_admin() || ! $q->is_search() || ! $q->is_main_query() ) {
		return $search;
	}
	$type = $q->get( 'post_type' );
	if ( 'pixva_orders' !== $type || ! current_user_can( 'pixva_manage_orders' ) ) {
		return $search;
	}
	global $wpdb;
	$term = trim( (string) $q->get( 's' ) );
	if ( '' === $term ) {
		return $search;
	}
	$like   = '%' . $wpdb->esc_like( $term ) . '%';
	$exists = $wpdb->prepare(
		"EXISTS (SELECT 1 FROM {$wpdb->postmeta} pm WHERE pm.post_id = {$wpdb->posts}.ID AND pm.meta_key IN ('_pixva_order_phone','_pixva_order_name') AND pm.meta_value LIKE %s)",
		$like
	);
	// Re-wrap: (title/name match) OR (phone/name meta match) inside one AND group,
	// so a non-matching row can never leak past the post-type conditions.
	$inner = trim( (string) $search );
	$inner = preg_replace( '/^AND\s+/i', '', $inner );
	if ( '' === $inner ) {
		return ' AND ( ' . $exists . ' )';
	}
	return ' AND ( ( ' . $inner . ' ) OR ' . $exists . ' )';
}
add_filter( 'posts_search', 'pixva_orders_admin_search', 10, 2 );

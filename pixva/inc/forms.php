<?php
/**
 * Write-operation form handlers (§11, §14, §31, §46).
 *
 * Every form posts to admin-post.php (works without JavaScript, PRG
 * pattern) and the same handler answers admin-ajax.php with JSON when
 * enhanced by assets/js/app.js. Shared pipeline:
 *   nonce → honeypot → rate limit → idempotency (submission id) →
 *   validation (server is the authority) → action → response.
 *
 * No-JS responses redirect back with `?pixva_r={token}`; the token maps to
 * a 15-minute transient holding the outcome (+ sanitized old input on
 * error), so no data is ever put in the URL.
 *
 * @package Pixva
 * @since   2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Form registry: action => [handler, public (nopriv), rate max/hour].
 *
 * @return array<string,array>
 */
function pixva_forms() {
	return array(
		'pixva_booking'      => array( 'pixva_handle_booking', true, 6 ),
		'pixva_contact'      => array( 'pixva_handle_contact', true, 6 ),
		'pixva_register'     => array( 'pixva_handle_register', true, 5 ),
		'pixva_profile'      => array( 'pixva_handle_profile', false, 20 ),
		'pixva_claim_order'  => array( 'pixva_handle_claim', false, 10 ),
		'pixva_order_update' => array( 'pixva_handle_order_update', false, 120 ),
	);
}

/**
 * Hook all forms.
 *
 * @return void
 */
function pixva_register_forms() {
	foreach ( pixva_forms() as $action => $def ) {
		$cb = static function () use ( $action ) {
			pixva_dispatch_form( $action );
		};
		add_action( 'admin_post_' . $action, $cb );
		add_action( 'wp_ajax_' . $action, $cb );
		if ( $def[1] ) {
			add_action( 'admin_post_nopriv_' . $action, $cb );
			add_action( 'wp_ajax_nopriv_' . $action, $cb );
		} else {
			add_action(
				'admin_post_nopriv_' . $action,
				static function () {
					wp_safe_redirect( pixva_route_url( 'account' ) );
					exit;
				}
			);
		}
	}
}
add_action( 'init', 'pixva_register_forms' );

/**
 * Hidden fields every form needs.
 *
 * @param string $action Form action.
 * @return void
 */
function pixva_form_fields( $action ) {
	echo '<input type="hidden" name="action" value="' . esc_attr( $action ) . '">';
	wp_nonce_field( $action, '_pixva_nonce', false );
	echo '<input type="hidden" name="_pixva_sid" value="' . esc_attr( pixva_submission_id() ) . '">';
	echo '<input type="hidden" name="_pixva_back" value="' . esc_attr( pixva_current_url() ) . '">';
	pixva_honeypot_field();
}

/**
 * Current URL without the result token (for PRG round trips).
 *
 * @return string
 */
function pixva_current_url() {
	$uri  = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '/';
	$base = (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH );
	$base = '' === $base ? '/' : trailingslashit( $base );
	// REQUEST_URI already contains the install subdirectory; strip it so home_url() does not duplicate it.
	if ( '/' !== $base && 0 === strpos( $uri, $base ) ) {
		$uri = '/' . substr( $uri, strlen( $base ) );
	}
	return remove_query_arg( array( 'pixva_r' ), home_url( $uri ) );
}

/**
 * Run the shared pipeline and the form handler.
 *
 * @param string $action Action.
 * @return void
 */
function pixva_dispatch_form( $action ) {
	$def = pixva_forms()[ $action ];
	$sid = pixva_get_post_var( '_pixva_sid' );

	if ( ! wp_verify_nonce( pixva_get_post_var( '_pixva_nonce' ), $action ) ) {
		pixva_form_respond( $action, false, array(), array( '_form' => __( 'نشست فرم منقضی شده است. صفحه را تازه کنید و دوباره ارسال کنید.', 'pixva' ) ), 403 );
	}
	if ( ! pixva_honeypot_passed() ) {
		// Pretend success to bots, do nothing.
		pixva_form_respond( $action, true, array( 'message' => __( 'دریافت شد.', 'pixva' ) ), array(), 200, false );
	}
	if ( ! pixva_rate_limit( 'form_' . $action, (int) $def[2], HOUR_IN_SECONDS ) ) {
		pixva_form_respond( $action, false, array(), array( '_form' => __( 'تعداد ارسال‌ها بیش از حد مجاز است. کمی بعد دوباره تلاش کنید.', 'pixva' ) ), 429 );
	}
	$claim = pixva_claim_submission( $sid, $action );
	if ( false === $claim ) {
		pixva_form_respond( $action, false, array(), array( '_form' => __( 'فرم نامعتبر است. صفحه را تازه کنید.', 'pixva' ) ), 400 );
	}
	if ( is_array( $claim ) ) {
		if ( ! empty( $claim['pending'] ) ) {
			pixva_form_respond( $action, false, array(), array( '_form' => __( 'این فرم در حال پردازش است؛ لطفاً چند لحظه صبر کنید.', 'pixva' ) ), 409 );
		}
		// Already processed: return the original outcome (double submit).
		pixva_form_respond( $action, true, $claim, array(), 200, false );
	}

	// The handler receives the submission id so it can recover idempotently
	// after a crash between the write and pixva_finish_submission().
	$result = call_user_func( $def[0], $sid );
	if ( is_wp_error( $result ) ) {
		pixva_release_submission( $sid );
		$data   = (array) $result->get_error_data();
		$errors = isset( $data['fields'] ) ? (array) $data['fields'] : array( '_form' => $result->get_error_message() );
		pixva_form_respond( $action, false, array(), $errors, (int) ( $data['status'] ?? 422 ) );
	}
	pixva_finish_submission( $sid, (array) $result );
	pixva_form_respond( $action, true, (array) $result );
}

/**
 * Send JSON (AJAX) or redirect with a result token (no-JS).
 *
 * @param string $action  Action.
 * @param bool   $ok      Success.
 * @param array  $payload Success payload (message, code, redirect…).
 * @param array  $errors  field => message.
 * @param int    $status  HTTP status for JSON.
 * @param bool   $track   Whether analytics should count it (bots → false).
 * @return void
 */
function pixva_form_respond( $action, $ok, $payload = array(), $errors = array(), $status = 200, $track = true ) {
	if ( wp_doing_ajax() ) {
		if ( $ok ) {
			wp_send_json_success( $payload + array( 'track' => $track ), 200 );
		}
		wp_send_json_error(
			array(
				'errors'  => $errors,
				'message' => $errors['_form'] ?? __( 'لطفاً خطاهای فرم را برطرف کنید.', 'pixva' ),
			),
			$status
		);
	}
	$token = wp_generate_password( 20, false, false );
	$old   = array();
	if ( ! $ok ) {
		foreach ( array( 'name', 'phone', 'email', 'brand', 'brand_other', 'model', 'problem', 'description', 'mode', 'address', 'time', 'message', 'code', 'first_name', 'last_name', 'size' ) as $k ) {
			$old[ $k ] = 'description' === $k || 'message' === $k || 'address' === $k ? pixva_get_post_textarea( $k ) : pixva_get_post_var( $k );
		}
	}
	set_transient(
		'pixva_r_' . $token,
		array(
			'form'    => $action,
			'ok'      => $ok,
			'payload' => $payload,
			'errors'  => $errors,
			'old'     => $old,
		),
		15 * MINUTE_IN_SECONDS
	);
	$back = pixva_get_post_var( '_pixva_back' );
	$back = $back ? wp_validate_redirect( $back, home_url( '/' ) ) : home_url( '/' );
	if ( $ok && ! empty( $payload['redirect'] ) ) {
		$back = wp_validate_redirect( (string) $payload['redirect'], $back );
	}
	wp_safe_redirect( add_query_arg( 'pixva_r', $token, $back ) . '#' . sanitize_html_class( str_replace( '_', '-', $action ) ), 303 );
	exit;
}

/**
 * Read (and consume) the no-JS result for a form on this page.
 *
 * @param string $action Form action.
 * @return array|null
 */
function pixva_form_result( $action ) {
	static $cache = array();
	$token        = preg_replace( '/[^A-Za-z0-9]/', '', pixva_get_request_var( 'pixva_r' ) );
	if ( '' === $token ) {
		return null;
	}
	if ( ! array_key_exists( $token, $cache ) ) {
		$cache[ $token ] = get_transient( 'pixva_r_' . $token );
		delete_transient( 'pixva_r_' . $token );
	}
	$r = $cache[ $token ];
	return ( is_array( $r ) && $r['form'] === $action ) ? $r : null;
}

/**
 * Error message for a field from a result ('' if none).
 *
 * @param array|null $r     Result.
 * @param string     $field Field.
 * @return string
 */
function pixva_field_error( $r, $field ) {
	return is_array( $r ) && isset( $r['errors'][ $field ] ) ? (string) $r['errors'][ $field ] : '';
}

/**
 * Old input value from a result.
 *
 * @param array|null $r       Result.
 * @param string     $field   Field.
 * @param string     $fallback Default.
 * @return string
 */
function pixva_old( $r, $field, $fallback = '' ) {
	return is_array( $r ) && isset( $r['old'][ $field ] ) && '' !== $r['old'][ $field ] ? (string) $r['old'][ $field ] : $fallback;
}

/**
 * Build a field-validation WP_Error.
 *
 * @param array $fields field => message.
 * @return WP_Error
 */
function pixva_validation_error( $fields ) {
	return new WP_Error(
		'validation',
		__( 'لطفاً خطاهای فرم را برطرف کنید.', 'pixva' ),
		array(
			'status' => 422,
			'fields' => $fields,
		)
	);
}

/*
 * ---------------------------------------------------------------------------
 * Booking (§11)
 * ---------------------------------------------------------------------------
 */

/**
 * Validate & create a repair order.
 *
 * @param string $sid Submission id (idempotency recovery key, may be '').
 * @return array|WP_Error
 */
function pixva_handle_booking( $sid = '' ) {
	// Crash recovery: if a previous attempt with this id already persisted the
	// order, return the same outcome instead of creating a duplicate.
	$existing = $sid ? pixva_find_order_by_submission( $sid ) : 0;
	if ( $existing ) {
		// A previous attempt persisted the order: return its outcome, and make
		// sure the staff notification (sent from the created-hook) was not lost
		// between the insert and the crash — the sent-marker keeps it single.
		pixva_notify_new_order( $existing );
		return array(
			'message'      => __( 'درخواست شما ثبت شد.', 'pixva' ),
			'code'         => (string) get_post_meta( $existing, '_pixva_order_code', true ),
			'tracking_url' => pixva_route_url( 'tracking' ),
		);
	}
	$e        = array();
	$name     = pixva_get_post_var( 'name' );
	$phone    = pixva_normalize_mobile( pixva_get_post_var( 'phone' ) );
	$brand    = pixva_get_post_var( 'brand' );
	$brand_ot = pixva_substr( pixva_get_post_var( 'brand_other' ), 0, 40 );
	$model    = pixva_substr( pixva_get_post_var( 'model' ), 0, 60 );
	$problem  = sanitize_key( pixva_get_post_var( 'problem' ) );
	$desc     = pixva_substr( pixva_get_post_textarea( 'description' ), 0, 1500 );
	$mode     = sanitize_key( pixva_get_post_var( 'mode' ) );
	$address  = pixva_substr( pixva_get_post_textarea( 'address' ), 0, 400 );
	$time     = pixva_substr( pixva_get_post_var( 'time' ), 0, 100 );
	$problems = pixva_diagnosis_problems();
	$modes    = pixva_service_modes();

	if ( pixva_strlen( $name ) < 2 || pixva_strlen( $name ) > 60 ) {
		$e['name'] = __( 'نام خود را وارد کنید (۲ تا ۶۰ نویسه).', 'pixva' );
	}
	if ( ! pixva_is_valid_iranian_mobile( $phone ) ) {
		$e['phone'] = __( 'شماره همراه معتبر وارد کنید؛ مثل ۰۹۱۲۳۴۵۶۷۸۹.', 'pixva' );
	}
	$brand_id    = absint( $brand );
	$brand_label = '';
	if ( $brand_id && 'tv_brands' === get_post_type( $brand_id ) && 'publish' === get_post_status( $brand_id ) ) {
		$brand_label = pixva_brand_label( $brand_id );
	} elseif ( 'other' === $brand && '' !== $brand_ot ) {
		$brand_id    = 0;
		$brand_label = $brand_ot;
	} elseif ( '' !== $brand_ot ) {
		$brand_id    = 0;
		$brand_label = $brand_ot;
	} else {
		$e['brand'] = __( 'برند تلویزیون را انتخاب یا وارد کنید.', 'pixva' );
	}
	if ( 'other' !== $problem && ! isset( $problems[ $problem ] ) ) {
		$e['problem'] = __( 'مشکل اصلی را انتخاب کنید.', 'pixva' );
	}
	if ( 'other' === $problem && pixva_strlen( $desc ) < 10 ) {
		$e['description'] = __( 'مشکل را در چند کلمه توضیح دهید.', 'pixva' );
	}
	if ( $modes ) {
		if ( ! isset( $modes[ $mode ] ) ) {
			$e['mode'] = __( 'شیوه تحویل دستگاه را انتخاب کنید.', 'pixva' );
		} elseif ( in_array( $mode, array( 'pickup', 'onsite' ), true ) && pixva_strlen( $address ) < 10 ) {
			$e['address'] = __( 'برای دریافت یا بازدید در محل، نشانی کامل لازم است.', 'pixva' );
		}
	} else {
		$mode = '';
	}
	if ( '1' !== pixva_get_post_var( 'consent' ) ) {
		$e['consent'] = __( 'برای ثبت درخواست، موافقت با استفاده از اطلاعات تماس لازم است.', 'pixva' );
	}

	// Photos: validated before anything is stored.
	$files = pixva_collect_files( 'photos', 3 );
	if ( count( $files ) > 3 ) {
		$e['photos'] = __( 'حداکثر سه تصویر می‌توانید بفرستید.', 'pixva' );
	}
	if ( $e ) {
		return pixva_validation_error( $e );
	}
	$stored = array();
	foreach ( $files as $file ) {
		$name_or_error = pixva_store_private_image( $file );
		if ( is_wp_error( $name_or_error ) ) {
			foreach ( $stored as $s ) {
				wp_delete_file( pixva_private_dir() . '/' . $s );
			}
			return pixva_validation_error( array( 'photos' => $name_or_error->get_error_message() ) );
		}
		$stored[] = $name_or_error;
	}
	// Diagnosis summary is recomputed on the server, never trusted from the client.
	$diagnosis = null;
	if ( 'diagnosis' === pixva_get_post_var( 'from' ) && isset( $problems[ $problem ] ) ) {
		$in = pixva_diagnosis_input(
			array(
				'problem'  => $problem,
				'brand'    => $brand_id,
				'model'    => $model,
				'symptoms' => array_filter( explode( ',', pixva_get_post_var( 'symptoms' ) ) ),
				'age'      => pixva_get_post_var( 'age' ),
			)
		);
		if ( ! is_wp_error( $in ) ) {
			$r         = pixva_diagnose( $in );
			$diagnosis = array(
				'symptoms' => $in['symptoms'],
				'causes'   => array_map(
					static fn( $c ) => array(
						'label'       => $c['label'],
						'level_label' => $c['level_label'],
					),
					$r['causes']
				),
			);
		}
	}

	$id = pixva_create_order(
		array(
			'name'           => $name,
			'phone'          => $phone,
			'brand'          => $brand_label,
			'brand_id'       => $brand_id,
			'model'          => $model,
			'problem'        => $problem,
			'description'    => $desc,
			'mode'           => $mode,
			'address'        => $address,
			'time'           => $time,
			'photos'         => $stored,
			'diagnosis'      => $diagnosis,
			'customer_id'    => get_current_user_id(),
			'submission_id'  => $sid,
		)
	);
	if ( is_wp_error( $id ) ) {
		// The order was not persisted: remove photos stored for this attempt.
		foreach ( $stored as $s ) {
			wp_delete_file( pixva_private_dir() . '/' . $s );
		}
		return new WP_Error( 'save', __( 'ثبت درخواست ممکن نشد. لطفاً دوباره تلاش کنید.', 'pixva' ), array( 'status' => 500 ) );
	}
	$code = (string) get_post_meta( $id, '_pixva_order_code', true );
	return array(
		'message'      => __( 'درخواست شما ثبت شد.', 'pixva' ),
		'code'         => $code,
		'tracking_url' => pixva_route_url( 'tracking' ),
	);
}

/*
 * ---------------------------------------------------------------------------
 * Contact
 * ---------------------------------------------------------------------------
 */

/**
 * Store a contact message privately and notify staff.
 *
 * The message row is keyed by the submission id so a crash between the
 * insert and the reply never produces a second inbox entry on retry, and
 * the notification email carries its own sent-marker (best-effort; an email
 * may be repeated only if the process dies between sending and marking —
 * delivery is at-least-once, the stored message itself is exactly-once).
 *
 * @param string $sid Submission id (idempotency recovery key, may be '').
 * @return array|WP_Error
 */
function pixva_handle_contact( $sid = '' ) {
	$existing = $sid ? pixva_find_inbox_by_submission( $sid ) : 0;
	if ( $existing ) {
		return pixva_complete_contact_submission( $existing, $sid );
	}
	$e       = array();
	$name    = pixva_get_post_var( 'name' );
	$phone   = pixva_normalize_mobile( pixva_get_post_var( 'phone' ) );
	$email   = sanitize_email( pixva_get_post_var( 'email' ) );
	$message = pixva_substr( pixva_get_post_textarea( 'message' ), 0, 2000 );
	if ( pixva_strlen( $name ) < 2 ) {
		$e['name'] = __( 'نام خود را وارد کنید.', 'pixva' );
	}
	if ( '' === $phone && '' === $email ) {
		$e['phone'] = __( 'شماره همراه یا ایمیل را برای پاسخ وارد کنید.', 'pixva' );
	} elseif ( '' !== $phone && ! pixva_is_valid_iranian_mobile( $phone ) ) {
		$e['phone'] = __( 'شماره همراه معتبر نیست.', 'pixva' );
	}
	if ( '' !== pixva_get_post_var( 'email' ) && ! is_email( $email ) ) {
		$e['email'] = __( 'ایمیل معتبر نیست.', 'pixva' );
	}
	if ( pixva_strlen( $message ) < 10 ) {
		$e['message'] = __( 'پیام را بنویسید (حداقل ۱۰ نویسه).', 'pixva' );
	}
	if ( '1' !== pixva_get_post_var( 'consent' ) ) {
		$e['consent'] = __( 'برای ارسال پیام، موافقت با استفاده از اطلاعات تماس لازم است.', 'pixva' );
	}
	if ( $e ) {
		return pixva_validation_error( $e );
	}
	$id = wp_insert_post(
		array(
			'post_type'   => 'pixva_inbox',
			'post_status' => 'private',
			/* translators: %s: date. */
			'post_title'  => sprintf( __( 'پیام %s', 'pixva' ), wp_date( 'Y-m-d H:i' ) ),
			'post_author' => 0,
		),
		true
	);
	if ( is_wp_error( $id ) ) {
		return new WP_Error( 'save', __( 'ارسال پیام ممکن نشد. لطفاً دوباره تلاش کنید.', 'pixva' ), array( 'status' => 500 ) );
	}
	if ( $sid ) {
		// Must match pixva_find_inbox_by_submission(): normalised (dashes stripped).
		update_post_meta( $id, '_pixva_msg_sid', pixva_submission_normalize_id( $sid ) );
	}
	return pixva_complete_contact_submission( $id, $sid );
}

/**
 * Finish a contact submission: ensure all meta fields are stored (a crash
 * may have interrupted the writes) and notify staff at most once per stored
 * message until the send succeeds.
 *
 * @param int    $post_id Inbox post id.
 * @param string $sid     Submission id.
 * @return array
 */
function pixva_complete_contact_submission( $post_id, $sid ) {
	// Complete any meta writes interrupted by a crash (retry carries the same payload).
	if ( '' === (string) get_post_meta( $post_id, '_pixva_msg_body', true ) ) {
		update_post_meta( $post_id, '_pixva_msg_name', wp_slash( pixva_get_post_var( 'name' ) ) );
		update_post_meta( $post_id, '_pixva_msg_phone', wp_slash( pixva_normalize_mobile( pixva_get_post_var( 'phone' ) ) ) );
		update_post_meta( $post_id, '_pixva_msg_email', wp_slash( sanitize_email( pixva_get_post_var( 'email' ) ) ) );
		update_post_meta( $post_id, '_pixva_msg_body', wp_slash( pixva_substr( pixva_get_post_textarea( 'message' ), 0, 2000 ) ) );
	}
	if ( ! get_post_meta( $post_id, '_pixva_msg_mail_sent', true ) ) {
		$sent = pixva_safe_mail(
			pixva_notify_email(),
			__( 'پیام جدید از فرم تماس', 'pixva' ),
			__( 'پیام جدیدی ثبت شد:', 'pixva' ) . ' ' . admin_url( 'post.php?post=' . (int) $post_id . '&action=edit' )
		);
		if ( $sent ) {
			update_post_meta( $post_id, '_pixva_msg_mail_sent', 1 );
		}
	}
	return array( 'message' => __( 'پیام شما دریافت شد. به‌زودی پاسخ می‌دهیم.', 'pixva' ) );
}

/**
 * Inbox post id previously stored for a submission id (0 when none).
 *
 * @param string $sid Submission id.
 * @return int
 */
function pixva_find_inbox_by_submission( $sid ) {
	$sid = pixva_submission_normalize_id( $sid );
	if ( '' === $sid ) {
		return 0;
	}
	$ids = get_posts(
		array(
			'post_type'        => 'pixva_inbox',
			'post_status'      => 'any',
			'meta_key'         => '_pixva_msg_sid', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
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
 * Message view box (read-only, staff with message caps).
 *
 * @return void
 */
function pixva_inbox_meta_box() {
	add_meta_box(
		'pixva_msg',
		__( 'متن پیام', 'pixva' ),
		static function ( $post ) {
			$rows = array(
				__( 'نام', 'pixva' )   => get_post_meta( $post->ID, '_pixva_msg_name', true ),
				__( 'همراه', 'pixva' ) => get_post_meta( $post->ID, '_pixva_msg_phone', true ),
				__( 'ایمیل', 'pixva' ) => get_post_meta( $post->ID, '_pixva_msg_email', true ),
			);
			echo '<table class="form-table" role="presentation">';
			foreach ( $rows as $label => $value ) {
				echo '<tr><th>' . esc_html( $label ) . '</th><td dir="auto">' . esc_html( (string) $value ) . '</td></tr>';
			}
			$body = (string) get_post_meta( $post->ID, '_pixva_msg_body', true );
			if ( '' === $body ) {
				$body = (string) get_post_meta( $post->ID, '_pixva_inbox_message', true ); // v1.x key.
			}
			echo '<tr><th>' . esc_html__( 'پیام', 'pixva' ) . '</th><td>' . nl2br( esc_html( $body ) ) . '</td></tr></table>';
		},
		'pixva_inbox',
		'normal',
		'high'
	);
}
add_action( 'add_meta_boxes_pixva_inbox', 'pixva_inbox_meta_box' );

/*
 * ---------------------------------------------------------------------------
 * Account: register, profile, claim (§14)
 * ---------------------------------------------------------------------------
 */

/**
 * Customer self-registration (only when Settings → General allows it).
 *
 * @return array|WP_Error
 */
function pixva_handle_register() {
	if ( ! get_option( 'users_can_register' ) ) {
		return new WP_Error( 'closed', __( 'ثبت‌نام در حال حاضر فعال نیست.', 'pixva' ), array( 'status' => 403 ) );
	}
	if ( is_user_logged_in() ) {
		return array(
			'message'  => __( 'شما وارد شده‌اید.', 'pixva' ),
			'redirect' => pixva_route_url( 'account' ),
		);
	}
	$e     = array();
	$name  = pixva_get_post_var( 'name' );
	$email = sanitize_email( pixva_get_post_var( 'email' ) );
	$phone = pixva_normalize_mobile( pixva_get_post_var( 'phone' ) );
	$pass  = isset( $_POST['password'] ) ? (string) wp_unslash( $_POST['password'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- verified in dispatcher; passwords are not sanitized.
	if ( pixva_strlen( $name ) < 2 ) {
		$e['name'] = __( 'نام خود را وارد کنید.', 'pixva' );
	}
	if ( ! is_email( $email ) ) {
		$e['email'] = __( 'ایمیل معتبر وارد کنید.', 'pixva' );
	} elseif ( email_exists( $email ) ) {
		$e['email'] = __( 'با این ایمیل قبلاً حساب ساخته شده است. وارد شوید یا رمز را بازیابی کنید.', 'pixva' );
	}
	if ( '' !== $phone && ! pixva_is_valid_iranian_mobile( $phone ) ) {
		$e['phone'] = __( 'شماره همراه معتبر نیست.', 'pixva' );
	}
	if ( strlen( $pass ) < 8 ) {
		$e['password'] = __( 'رمز عبور حداقل ۸ نویسه باشد.', 'pixva' );
	}
	if ( '1' !== pixva_get_post_var( 'consent' ) ) {
		$e['consent'] = __( 'پذیرش سیاست حریم خصوصی لازم است.', 'pixva' );
	}
	if ( $e ) {
		return pixva_validation_error( $e );
	}
	$login = sanitize_user( strstr( $email, '@', true ), true );
	$login = '' === $login ? 'customer' : $login;
	$base  = $login;
	$i     = 1;
	while ( username_exists( $login ) ) {
		$login = $base . ( ++$i );
	}
	$uid = wp_insert_user(
		array(
			'user_login'   => $login,
			'user_email'   => $email,
			'user_pass'    => $pass,
			'display_name' => $name,
			'first_name'   => $name,
			'role'         => 'pixva_customer',
		)
	);
	if ( is_wp_error( $uid ) ) {
		return new WP_Error( 'save', __( 'ساخت حساب ممکن نشد.', 'pixva' ), array( 'status' => 500 ) );
	}
	if ( '' !== $phone ) {
		update_user_meta( $uid, 'pixva_phone', $phone );
	}
	wp_set_current_user( $uid );
	wp_set_auth_cookie( $uid, true, is_ssl() );
	// Emitted once on the next page (both AJAX and no-JS paths redirect to /account/).
	update_user_meta( $uid, '_pixva_pending_event', 'account_registration' );
	return array(
		'message'  => __( 'حساب شما ساخته شد.', 'pixva' ),
		'redirect' => pixva_route_url( 'account' ),
	);
}

/**
 * Update own profile.
 *
 * @return array|WP_Error
 */
function pixva_handle_profile() {
	$user = wp_get_current_user();
	if ( ! $user->exists() ) {
		return new WP_Error( 'auth', __( 'ابتدا وارد شوید.', 'pixva' ), array( 'status' => 401 ) );
	}
	$e     = array();
	$name  = pixva_get_post_var( 'name' );
	$phone = pixva_normalize_mobile( pixva_get_post_var( 'phone' ) );
	$email = sanitize_email( pixva_get_post_var( 'email' ) );
	$pass  = isset( $_POST['current_password'] ) ? (string) wp_unslash( $_POST['current_password'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	if ( pixva_strlen( $name ) < 2 ) {
		$e['name'] = __( 'نام خود را وارد کنید.', 'pixva' );
	}
	if ( '' !== $phone && ! pixva_is_valid_iranian_mobile( $phone ) ) {
		$e['phone'] = __( 'شماره همراه معتبر نیست.', 'pixva' );
	}
	$email_changed = '' !== $email && strtolower( $email ) !== strtolower( $user->user_email );
	if ( $email_changed ) {
		if ( ! is_email( $email ) ) {
			$e['email'] = __( 'ایمیل معتبر نیست.', 'pixva' );
		} elseif ( email_exists( $email ) ) {
			$e['email'] = __( 'این ایمیل برای حساب دیگری ثبت شده است.', 'pixva' );
		} elseif ( ! wp_check_password( $pass, $user->user_pass, $user->ID ) ) {
			$e['current_password'] = __( 'برای تغییر ایمیل، رمز فعلی را درست وارد کنید.', 'pixva' );
		}
	}
	if ( $e ) {
		return pixva_validation_error( $e );
	}
	$upd = array(
		'ID'           => $user->ID,
		'display_name' => $name,
		'first_name'   => $name,
	);
	if ( $email_changed ) {
		$upd['user_email'] = $email;
	}
	$res = wp_update_user( $upd );
	if ( is_wp_error( $res ) ) {
		return new WP_Error( 'save', __( 'ذخیره ممکن نشد.', 'pixva' ), array( 'status' => 500 ) );
	}
	if ( '' === $phone ) {
		delete_user_meta( $user->ID, 'pixva_phone' );
	} else {
		update_user_meta( $user->ID, 'pixva_phone', $phone );
	}
	return array( 'message' => __( 'مشخصات ذخیره شد.', 'pixva' ) );
}

/**
 * Attach an unowned order to the current account after code + phone proof.
 * (v1.x linked orders by a phone typed in the profile — an IDOR; removed.)
 *
 * @return array|WP_Error
 */
function pixva_handle_claim() {
	$uid = get_current_user_id();
	if ( ! $uid ) {
		return new WP_Error( 'auth', __( 'ابتدا وارد شوید.', 'pixva' ), array( 'status' => 401 ) );
	}
	$id = pixva_verify_order_access( pixva_get_post_var( 'code' ), pixva_get_post_var( 'phone' ) );
	if ( is_wp_error( $id ) ) {
		$data = (array) $id->get_error_data();
		return 'invalid' === $id->get_error_code() ? pixva_validation_error( array( 'code' => $id->get_error_message() ) ) : new WP_Error( 'claim', $id->get_error_message(), array( 'status' => (int) ( $data['status'] ?? 404 ) ) );
	}
	$owner = (int) get_post_meta( $id, '_pixva_customer_id', true );
	if ( $owner && $owner !== $uid ) {
		// Same message as not found: do not reveal that the order exists.
		return new WP_Error( 'claim', __( 'پرونده‌ای با این کد و شماره پیدا نشد.', 'pixva' ), array( 'status' => 404 ) );
	}
	if ( ! $owner ) {
		// First claim wins: the unique insert arbitrates two concurrent claims
		// instead of both overwriting each other.
		if ( ! add_post_meta( $id, '_pixva_customer_id', $uid, true ) ) {
			wp_cache_delete( (int) $id, 'post_meta' );
			$owner = (int) get_post_meta( $id, '_pixva_customer_id', true );
			if ( $owner && $owner !== $uid ) {
				return new WP_Error( 'claim', __( 'پرونده‌ای با این کد و شماره پیدا نشد.', 'pixva' ), array( 'status' => 404 ) );
			}
		}
	}
	return array(
		'message'  => __( 'پرونده به حساب شما افزوده شد.', 'pixva' ),
		'redirect' => pixva_route_url( 'account_repairs' ),
	);
}

/*
 * ---------------------------------------------------------------------------
 * Staff: status update from the front-end dashboard (§15)
 * ---------------------------------------------------------------------------
 */

/**
 * Update status / notes of an assigned order.
 *
 * @return array|WP_Error
 */
function pixva_handle_order_update() {
	$id = absint( pixva_get_post_var( 'order' ) );
	if ( ! $id || ! current_user_can( 'pixva_work_order', $id ) ) {
		return new WP_Error( 'forbidden', __( 'اجازه ویرایش این پرونده را ندارید.', 'pixva' ), array( 'status' => 403 ) );
	}
	$status = sanitize_key( pixva_get_post_var( 'status' ) );
	$note   = pixva_get_post_textarea( 'note' );
	$intern = pixva_get_post_textarea( 'internal' );
	$res    = pixva_set_order_status( $id, $status, $note );
	if ( is_wp_error( $res ) ) {
		if ( 'conflict' === $res->get_error_code() ) {
			return pixva_validation_error( array( '_form' => $res->get_error_message() ) );
		}
		return pixva_validation_error( array( 'status' => $res->get_error_message() ) );
	}
	if ( '' !== trim( $intern ) ) {
		$note_err = pixva_append_order_note( $id, $intern, wp_get_current_user()->display_name );
		if ( is_wp_error( $note_err ) ) {
			return pixva_validation_error( array( '_form' => $note_err->get_error_message() ) );
		}
	}
	return array( 'message' => __( 'پرونده به‌روزرسانی شد.', 'pixva' ) );
}

/**
 * Booking form block (used by the booking page template and the Elementor
 * booking widget). Renders prefills, PRG result and the full form.
 *
 * POST target stays admin-post/admin-ajax (unchanged §11 contract).
 *
 * @return void
 */
function pixva_booking_form_block() {
	$pixva_r        = pixva_form_result( 'pixva_booking' );
	$pixva_problems = pixva_diagnosis_problems();
	$pixva_brands   = pixva_brand_choices();
	$pixva_modes    = pixva_service_modes();
	$pixva_user     = wp_get_current_user();
	$pixva_from     = 'diagnosis' === pixva_get_request_var( 'from' ) ? 'diagnosis' : '';
	$pixva_pre      = array(
		'problem' => sanitize_key( pixva_get_request_var( 'problem' ) ),
		'brand'   => (string) absint( pixva_get_request_var( 'brand', '0' ) ),
		'model'   => pixva_substr( pixva_get_request_var( 'model' ), 0, 60 ),
	);
	$pixva_service  = pixva_service_by_slug( sanitize_title( pixva_get_request_var( 'service' ) ) );
	$pixva_desc     = $pixva_service ? sprintf( /* translators: %s: service. */ __( 'خدمت موردنظر: %s', 'pixva' ), get_the_title( $pixva_service ) ) . "\n" : '';
	$pixva_symptoms = array();
	if ( 'diagnosis' === $pixva_from && isset( $pixva_problems[ $pixva_pre['problem'] ] ) ) {
		$pixva_symptoms = array_values( array_intersect( pixva_get_request_keys( 'symptoms' ), array_keys( $pixva_problems[ $pixva_pre['problem'] ]['symptoms'] ) ) );
	}
	$pixva_problem_opts = array( '' => __( 'انتخاب کنید…', 'pixva' ) ) + wp_list_pluck( $pixva_problems, 'label' ) + array( 'other' => __( 'سایر / مطمئن نیستم', 'pixva' ) );
	$pixva_brand_opts   = array( '' => __( 'انتخاب کنید…', 'pixva' ) ) + array_map( 'strval', $pixva_brands ) + array( 'other' => __( 'سایر برندها', 'pixva' ) );
	?>
			<div id="pixva-booking" class="form-wrap">
			<?php if ( is_array( $pixva_r ) && $pixva_r['ok'] && ! empty( $pixva_r['payload']['code'] ) ) : ?>
				<div class="success" data-track-view="booking_submitted" tabindex="-1">
					<h2 class="success__title"><?php echo wp_kses( pixva_icon( 'check' ), pixva_svg_allowed() ); ?> <?php echo esc_html( $pixva_r['payload']['message'] ); ?></h2>
					<p><?php esc_html_e( 'کد پیگیری شما:', 'pixva' ); ?></p>
					<p class="code code--lg" dir="ltr"><?php echo esc_html( $pixva_r['payload']['code'] ); ?></p>
					<p><?php esc_html_e( 'این کد را نگه دارید. با کد و شماره همراهی که وارد کردید، وضعیت درخواست را در صفحه پیگیری می‌بینید.', 'pixva' ); ?></p>
					<p class="wizard__actions">
						<a class="btn btn--primary" href="<?php echo esc_url( $pixva_r['payload']['tracking_url'] ); ?>"><?php esc_html_e( 'صفحه پیگیری', 'pixva' ); ?></a>
						<?php if ( ! is_user_logged_in() ) : ?>
							<a class="btn btn--ghost" href="<?php echo esc_url( pixva_route_url( 'account' ) ); ?>"><?php esc_html_e( 'ساخت حساب برای دیدن همه درخواست‌ها', 'pixva' ); ?></a>
						<?php endif; ?>
					</p>
				</div>
			<?php else : ?>
				<?php if ( is_array( $pixva_r ) && ! $pixva_r['ok'] ) : ?>
					<span hidden data-track-view="booking_failed"></span>
				<?php endif; ?>
				<?php if ( 'diagnosis' === $pixva_from && isset( $pixva_problems[ $pixva_pre['problem'] ] ) ) : ?>
					<?php pixva_notice( 'info', __( 'اطلاعات ابزار تشخیص به فرم اضافه شد. نتیجه تشخیص همراه درخواست برای کارشناس ارسال می‌شود.', 'pixva' ) ); ?>
				<?php endif; ?>
				<form class="form" method="post" enctype="multipart/form-data" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" data-pixva-form data-code-help="<?php esc_attr_e( 'این کد را نگه دارید. با کد و شماره همراهی که وارد کردید، وضعیت درخواست را در صفحه پیگیری می‌بینید.', 'pixva' ); ?>" data-tracking-label="<?php esc_attr_e( 'صفحه پیگیری', 'pixva' ); ?>" data-track-start="booking_started" data-track-success="booking_submitted" data-track-fail="booking_failed" novalidate>
					<?php pixva_form_fields( 'pixva_booking' ); ?>
					<input type="hidden" name="from" value="<?php echo esc_attr( $pixva_from ); ?>">
					<input type="hidden" name="symptoms" value="<?php echo esc_attr( implode( ',', $pixva_symptoms ) ); ?>">
					<input type="hidden" name="age" value="<?php echo esc_attr( 'diagnosis' === $pixva_from ? sanitize_key( pixva_get_request_var( 'age' ) ) : '' ); ?>">
					<?php pixva_form_status( $pixva_r ); ?>

					<fieldset class="form__section">
						<legend><?php esc_html_e( 'اطلاعات تماس', 'pixva' ); ?></legend>
						<div class="form-grid">
							<?php
							pixva_field(
								array(
									'name'     => 'name',
									'label'    => __( 'نام و نام خانوادگی', 'pixva' ),
									'required' => true,
									'value'    => $pixva_user->exists() ? $pixva_user->display_name : '',
									'attrs'    => array(
										'autocomplete' => 'name',
										'maxlength'    => '60',
									),
								),
								$pixva_r
							);
							pixva_field(
								array(
									'name'     => 'phone',
									'label'    => __( 'شماره همراه', 'pixva' ),
									'type'     => 'tel',
									'required' => true,
									'value'    => $pixva_user->exists() ? (string) get_user_meta( $pixva_user->ID, 'pixva_phone', true ) : '',
									'help'     => __( 'برای هماهنگی و پیگیری استفاده می‌شود؛ مثل ۰۹۱۲۳۴۵۶۷۸۹.', 'pixva' ),
									'attrs'    => array(
										'autocomplete' => 'tel',
										'inputmode'    => 'tel',
										'dir'          => 'ltr',
										'maxlength'    => '14',
									),
								),
								$pixva_r
							);
							?>
						</div>
					</fieldset>

					<fieldset class="form__section">
						<legend><?php esc_html_e( 'دستگاه و ایراد', 'pixva' ); ?></legend>
						<div class="form-grid">
							<?php
							if ( $pixva_brands ) {
								pixva_field(
									array(
										'name'     => 'brand',
										'label'    => __( 'برند', 'pixva' ),
										'type'     => 'select',
										'required' => true,
										'options'  => $pixva_brand_opts,
										'value'    => '0' !== $pixva_pre['brand'] ? $pixva_pre['brand'] : '',
									),
									$pixva_r
								);
								pixva_field(
									array(
										'name'  => 'brand_other',
										'label' => __( 'نام برند (اگر در فهرست نیست)', 'pixva' ),
										'attrs' => array(
											'maxlength' => '40',
											'data-show-when' => 'brand=other',
										),
									),
									$pixva_r
								);
							} else {
								echo '<input type="hidden" name="brand" value="other">';
								pixva_field(
									array(
										'name'     => 'brand_other',
										'label'    => __( 'برند', 'pixva' ),
										'required' => true,
										'attrs'    => array( 'maxlength' => '40' ),
									),
									$pixva_r
								);
							}
							pixva_field(
								array(
									'name'  => 'model',
									'label' => __( 'مدل', 'pixva' ),
									'value' => $pixva_pre['model'],
									'help'  => __( 'روی برچسب پشت دستگاه.', 'pixva' ),
									'attrs' => array(
										'maxlength' => '60',
										'dir'       => 'auto',
									),
								),
								$pixva_r
							);
							pixva_field(
								array(
									'name'     => 'problem',
									'label'    => __( 'مشکل اصلی', 'pixva' ),
									'type'     => 'select',
									'required' => true,
									'options'  => $pixva_problem_opts,
									'value'    => isset( $pixva_problem_opts[ $pixva_pre['problem'] ] ) ? $pixva_pre['problem'] : '',
								),
								$pixva_r
							);
							?>
						</div>
						<?php
						if ( $pixva_symptoms ) {
							echo '<p class="field__help">' . esc_html__( 'نشانه‌های انتخاب‌شده در تشخیص:', 'pixva' ) . ' ' . esc_html( implode( '، ', array_intersect_key( $pixva_problems[ $pixva_pre['problem'] ]['symptoms'], array_flip( $pixva_symptoms ) ) ) ) . '</p>';
						}
						pixva_field(
							array(
								'name'  => 'description',
								'label' => __( 'توضیح ایراد', 'pixva' ),
								'type'  => 'textarea',
								'rows'  => 4,
								'value' => $pixva_desc,
								'help'  => __( 'از کی شروع شد؟ بعد از چه اتفاقی؟ (برای «سایر» لازم است)', 'pixva' ),
								'attrs' => array( 'maxlength' => '1500' ),
							),
							$pixva_r
						);
						pixva_field(
							array(
								'name'  => 'photos[]',
								'id'    => 'f-photos',
								'label' => __( 'تصویر صفحه یا برچسب دستگاه', 'pixva' ),
								'type'  => 'file',
								'help'  => __( 'حداکثر ۳ تصویر JPG، PNG یا WebP، هر کدام تا ۵ مگابایت. تصاویر فقط برای کارشناسان قابل مشاهده است.', 'pixva' ),
								'attrs' => array(
									'accept'   => 'image/jpeg,image/png,image/webp',
									'multiple' => 'multiple',
								),
							),
							$pixva_r
						);
						?>
					</fieldset>

					<?php if ( $pixva_modes ) : ?>
						<fieldset class="form__section field<?php echo '' !== pixva_field_error( $pixva_r, 'mode' ) ? ' field--error' : ''; ?>" data-field="mode" aria-describedby="f-mode-err">
							<legend><?php esc_html_e( 'شیوه تحویل دستگاه', 'pixva' ); ?> <span class="req" aria-hidden="true">*</span></legend>
							<?php foreach ( $pixva_modes as $pixva_k => $pixva_l ) : ?>
								<label class="check"><input type="radio" name="mode" value="<?php echo esc_attr( $pixva_k ); ?>" required <?php checked( pixva_old( $pixva_r, 'mode', count( $pixva_modes ) === 1 ? $pixva_k : '' ), $pixva_k ); ?>> <span><?php echo esc_html( $pixva_l ); ?></span></label>
							<?php endforeach; ?>
							<p class="field__error" id="f-mode-err"<?php echo '' === pixva_field_error( $pixva_r, 'mode' ) ? ' hidden' : ''; ?>><?php echo esc_html( pixva_field_error( $pixva_r, 'mode' ) ); ?></p>
							<?php if ( isset( $pixva_modes['pickup'] ) || isset( $pixva_modes['onsite'] ) ) : ?>
								<?php
								pixva_field(
									array(
										'name'  => 'address',
										'label' => __( 'نشانی', 'pixva' ),
										'type'  => 'textarea',
										'rows'  => 2,
										'help'  => __( 'فقط برای دریافت از محل یا بازدید در محل لازم است.', 'pixva' ),
										'attrs' => array(
											'autocomplete' => 'street-address',
											'maxlength'    => '400',
											'data-show-when' => 'mode=pickup|onsite',
										),
									),
									$pixva_r
								);
								?>
							<?php endif; ?>
						</fieldset>
					<?php endif; ?>

					<?php
					pixva_field(
						array(
							'name'        => 'time',
							'label'       => __( 'زمان مناسب برای تماس', 'pixva' ),
							'placeholder' => __( 'مثلاً عصرها بعد از ساعت ۱۷', 'pixva' ),
							'attrs'       => array( 'maxlength' => '100' ),
						),
						$pixva_r
					);
					?>
					<?php
					pixva_field(
						array(
							'name'     => 'consent',
							'type'     => 'checkbox',
							'required' => true,
							'label'    => pixva_consent_label( __( 'موافقم اطلاعات تماس و دستگاه برای رسیدگی به این درخواست ذخیره و استفاده شود.', 'pixva' ) ),
						),
						$pixva_r
					);
					?>

					<div class="form__actions">
						<button class="btn btn--accent btn--lg" type="submit" data-submit><?php esc_html_e( 'ثبت درخواست', 'pixva' ); ?></button>
					</div>
				</form>
			<?php endif; ?>
		</div>
	<?php
}

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
	$claim = pixva_claim_submission( $sid );
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

	$result = call_user_func( $def[0] );
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
 * @return array|WP_Error
 */
function pixva_handle_booking() {
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
			'name'        => $name,
			'phone'       => $phone,
			'brand'       => $brand_label,
			'brand_id'    => $brand_id,
			'model'       => $model,
			'problem'     => $problem,
			'description' => $desc,
			'mode'        => $mode,
			'address'     => $address,
			'time'        => $time,
			'photos'      => $stored,
			'diagnosis'   => $diagnosis,
			'customer_id' => get_current_user_id(),
		)
	);
	if ( is_wp_error( $id ) ) {
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
 * @return array|WP_Error
 */
function pixva_handle_contact() {
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
	update_post_meta( $id, '_pixva_msg_name', wp_slash( $name ) );
	update_post_meta( $id, '_pixva_msg_phone', wp_slash( $phone ) );
	update_post_meta( $id, '_pixva_msg_email', wp_slash( $email ) );
	update_post_meta( $id, '_pixva_msg_body', wp_slash( $message ) );
	pixva_safe_mail( pixva_notify_email(), __( 'پیام جدید از فرم تماس', 'pixva' ), __( 'پیام جدیدی ثبت شد:', 'pixva' ) . ' ' . admin_url( 'post.php?post=' . (int) $id . '&action=edit' ) );
	return array( 'message' => __( 'پیام شما دریافت شد. به‌زودی پاسخ می‌دهیم.', 'pixva' ) );
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
	update_post_meta( $id, '_pixva_customer_id', $uid );
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
		return pixva_validation_error( array( 'status' => $res->get_error_message() ) );
	}
	if ( '' !== trim( $intern ) ) {
		$prev = (string) get_post_meta( $id, '_pixva_order_notes', true );
		$line = '[' . wp_date( 'Y-m-d H:i' ) . ' — ' . wp_get_current_user()->display_name . '] ' . $intern;
		update_post_meta( $id, '_pixva_order_notes', wp_slash( trim( $prev . "\n" . $line ) ) );
	}
	return array( 'message' => __( 'پرونده به‌روزرسانی شد.', 'pixva' ) );
}

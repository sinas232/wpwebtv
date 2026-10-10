<?php
/**
 * Security layer: WordPress hardening, rate limiting, honeypot, idempotency,
 * safe mail, private upload storage.
 *
 * @package Pixva
 * @since   2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
 * ---------------------------------------------------------------------------
 * 1) Hardening (kept from v1.x, reviewed)
 * ---------------------------------------------------------------------------
 */

/**
 * Remove version leaks, RSD/WLW links and pingbacks.
 *
 * @return void
 */
function pixva_harden_head() {
	remove_action( 'wp_head', 'wp_generator' );
	remove_action( 'wp_head', 'rsd_link' );
	remove_action( 'wp_head', 'wlwmanifest_link' );
	add_filter( 'pings_open', '__return_false' );
	add_filter( 'pre_option_use_pingbacks', '__return_zero' );
}
add_action( 'init', 'pixva_harden_head' );

add_filter( 'xmlrpc_enabled', '__return_false' );

/**
 * Generic login error (no username enumeration).
 *
 * @return string
 */
function pixva_generic_login_error() {
	return esc_html__( 'نام کاربری یا رمز عبور نامعتبر است.', 'pixva' );
}
add_filter( 'login_errors', 'pixva_generic_login_error' );

if ( ! defined( 'DISALLOW_FILE_EDIT' ) ) {
	define( 'DISALLOW_FILE_EDIT', true ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound -- core constant, intentionally set (§31).
}

/**
 * Restrict /wp/v2/users (user enumeration, §31/§49).
 *
 * Only staff who need user lookups in the editor (edit_posts / list_users)
 * keep the collection and single-user routes. Other logged-in users
 * (customers, technicians) keep only /wp/v2/users/me, which returns their
 * own record; guests get none of the routes.
 *
 * @param array $endpoints Endpoints.
 * @return array
 */
function pixva_restrict_rest_users( $endpoints ) {
	if ( current_user_can( 'edit_posts' ) || current_user_can( 'list_users' ) ) {
		return $endpoints;
	}
	foreach ( array_keys( $endpoints ) as $route ) {
		if ( 0 !== strpos( $route, '/wp/v2/users' ) ) {
			continue;
		}
		if ( '/wp/v2/users/me' === $route && is_user_logged_in() ) {
			continue;
		}
		unset( $endpoints[ $route ] );
	}
	return $endpoints;
}
add_filter( 'rest_endpoints', 'pixva_restrict_rest_users' );

/**
 * Drop the author archive URL (contains the login slug and is a 404 for
 * the public anyway) from oEmbed responses.
 *
 * @param array $data oEmbed data.
 * @return array
 */
function pixva_oembed_no_author_url( $data ) {
	unset( $data['author_url'] );
	return $data;
}
add_filter( 'oembed_response_data', 'pixva_oembed_no_author_url' );

/**
 * Author archives are not part of the IA (§05) and leak usernames: 404 them
 * for everyone except administrators. A 404 (not a redirect) avoids soft-404s.
 *
 * @return void
 */
function pixva_block_author_archives() {
	if ( is_author() && ! current_user_can( 'manage_options' ) ) {
		global $wp_query;
		$wp_query->set_404();
		status_header( 404 );
		nocache_headers();
	}
}
add_action( 'template_redirect', 'pixva_block_author_archives', 1 );

/**
 * Baseline security headers.
 *
 * @return void
 */
function pixva_send_security_headers() {
	if ( headers_sent() ) {
		return;
	}
	header( 'X-Content-Type-Options: nosniff' );
	header( 'Referrer-Policy: strict-origin-when-cross-origin' );
	header( 'X-Frame-Options: SAMEORIGIN' );
	header( 'Permissions-Policy: camera=(), microphone=(), geolocation=()' );
}
add_action( 'send_headers', 'pixva_send_security_headers' );

/*
 * ---------------------------------------------------------------------------
 * 2) Rate limiting
 * ---------------------------------------------------------------------------
 */

/**
 * Opaque per-client key. The full IP is hashed with the site salt so buckets
 * are per client (v1.x anonymised the IP first, which shared one bucket per
 * /24 network) while no raw IP is ever stored.
 *
 * @return string
 */
function pixva_client_key() {
	$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '0.0.0.0';
	/**
	 * Client IP used for rate limiting. Defaults to REMOTE_ADDR, which cannot
	 * be spoofed with request headers. Behind a reverse proxy/CDN, configure
	 * the web server to restore the real IP (nginx real_ip, Apache remoteip)
	 * or filter this value using the proxy's trusted header.
	 *
	 * @param string $ip REMOTE_ADDR.
	 */
	$ip = (string) apply_filters( 'pixva_client_ip', $ip );
	return substr( hash_hmac( 'sha256', $ip, wp_salt( 'nonce' ) ), 0, 32 );
}

/**
 * Fixed-window rate limiter. Returns false when the limit is exceeded.
 *
 * @param string $action Bucket name.
 * @param int    $max    Max hits per window.
 * @param int    $window Window seconds.
 * @return bool
 */
function pixva_rate_limit( $action, $max = 10, $window = HOUR_IN_SECONDS ) {
	$max = (int) apply_filters( 'pixva_rate_limit_max', (int) $max, $action );
	$key = 'pixva_rl_' . sanitize_key( $action ) . '_' . pixva_client_key();
	$hit = get_transient( $key );
	$hit = is_array( $hit ) ? $hit : array(
		'n' => 0,
		't' => time(),
	);
	if ( $hit['n'] >= $max ) {
		return false;
	}
	++$hit['n'];
	set_transient( $key, $hit, max( 1, (int) $window - ( time() - (int) $hit['t'] ) ) );
	return true;
}

/*
 * ---------------------------------------------------------------------------
 * 3) Spam & duplicate protection
 *
 * Submission idempotency records are stored in `wp_options` (autoload "no")
 * instead of transients: the `option_name` UNIQUE index makes the claiming
 * INSERT atomic, so two concurrent requests carrying the same submission id
 * can never both proceed. Transients (plain SELECT/UPDATE) have no such
 * guarantee. A crashed request leaves a "pending" record that is taken over
 * after PIXVA_SUB_PENDING_TTL so the user is not locked out until the
 * record's full TTL; finished records are replayed for PIXVA_SUB_TTL.
 * ---------------------------------------------------------------------------
 */

/**
 * How long a finished submission keeps replaying its result (seconds).
 */
const PIXVA_SUB_TTL = DAY_IN_SECONDS;

/**
 * After this many seconds a "pending" record is considered abandoned
 * (request crashed between claim and finish) and may be taken over.
 */
const PIXVA_SUB_PENDING_TTL = 3 * MINUTE_IN_SECONDS;

/**
 * Normalise a submission id from the form. Returns '' when malformed.
 *
 * @param string $id Raw id.
 * @return string
 */
function pixva_submission_normalize_id( $id ) {
	$id = sanitize_key( str_replace( '-', '', (string) $id ) );
	return 32 === strlen( $id ) ? $id : '';
}

/**
 * Read a submission record straight from the database (bypasses the
 * notoptions/alloptions caches so arbitration always sees the latest row).
 *
 * @param string $id Normalised submission id.
 * @return array|null Unserialised row or null when absent.
 */
function pixva_submission_row( $id ) {
	global $wpdb;
	$raw = $wpdb->get_var( $wpdb->prepare( 'SELECT option_value FROM ' . $wpdb->options . ' WHERE option_name = %s LIMIT 1', 'pixva_sub_' . $id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- idempotency arbitration must not read a stale cache.
	if ( null === $raw ) {
		return null;
	}
	$row = maybe_unserialize( $raw );
	return ( is_array( $row ) && isset( $row['s'], $row['t'] ) ) ? $row : null;
}

/**
 * Delete one stored submission row (unconditional; caller owns the row).
 *
 * @param string $id Normalised submission id.
 * @return void
 */
function pixva_submission_delete( $id ) {
	global $wpdb;
	$wpdb->delete( $wpdb->options, array( 'option_name' => 'pixva_sub_' . $id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
	// Raw delete bypasses delete_option(): drop both caches so a later
	// add_option() in the same request does not see the removed row.
	wp_cache_delete( 'pixva_sub_' . $id, 'options' );
	wp_cache_delete( 'notoptions', 'options' );
}

/**
 * Opportunistically remove expired submission records (throttled to once
 * per hour). Options have no built-in expiry like transients do.
 *
 * @return void
 */
function pixva_gc_submissions() {
	$last = (int) get_option( 'pixva_sub_gc', 0 );
	if ( time() - $last < HOUR_IN_SECONDS ) {
		return;
	}
	update_option( 'pixva_sub_gc', time(), false ); // Throttle marker only; losing the race is harmless.
	global $wpdb;
	$rows = $wpdb->get_results( 'SELECT option_name, option_value FROM ' . $wpdb->options . " WHERE option_name LIKE 'pixva_sub_%' LIMIT 1000" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- prefix scan, no user input.
	foreach ( (array) $rows as $row ) {
		if ( 0 !== strpos( (string) $row->option_name, 'pixva_sub_' ) ) {
			continue;
		}
		$rec = maybe_unserialize( $row->option_value );
		$age = is_array( $rec ) && isset( $rec['t'] ) ? time() - (int) $rec['t'] : PHP_INT_MAX;
		$ttl = is_array( $rec ) && 'pending' === ( $rec['s'] ?? '' ) ? PIXVA_SUB_PENDING_TTL : PIXVA_SUB_TTL;
		if ( $age > $ttl ) {
			$wpdb->delete( $wpdb->options, array( 'option_name' => $row->option_name ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		}
	}
	wp_cache_delete( 'notoptions', 'options' );
}

/**
 * Hidden honeypot field. Visually hidden & out of tab order; screen readers
 * skip it via aria-hidden on the wrapper.
 *
 * @return void
 */
function pixva_honeypot_field() {
	$id = 'pixva-hp-' . wp_rand( 1000, 9999 );
	echo '<div class="hp-field" aria-hidden="true"><label for="' . esc_attr( $id ) . '">' . esc_html__( 'این فیلد را خالی بگذارید', 'pixva' ) . '</label><input type="text" id="' . esc_attr( $id ) . '" name="pixva_hp" tabindex="-1" autocomplete="off" value=""></div>';
}

/**
 * Whether the honeypot is empty.
 *
 * @return bool
 */
function pixva_honeypot_passed() {
	return '' === pixva_get_post_var( 'pixva_hp' );
}

/**
 * Random token placed in a form so a double-submit (double click, refresh,
 * network retry) is processed once.
 *
 * @return string
 */
function pixva_submission_id() {
	return wp_generate_uuid4();
}

/**
 * Claim a submission id. Returns the stored result if it was already
 * processed, array('pending' => true) while another request holds it,
 * true when this request reserved it, false if invalid.
 *
 * The reservation INSERT relies on the UNIQUE index of `option_name`, so
 * exactly one of several concurrent requests wins the claim.
 *
 * @param string $id    Submission id from the form.
 * @param string $action Form action the id is being claimed for (binding
 *                       prevents a booking id being replayed to another form).
 * @return true|false|array
 */
function pixva_claim_submission( $id, $action = '' ) {
	$id = pixva_submission_normalize_id( $id );
	if ( '' === $id ) {
		return false;
	}
	pixva_gc_submissions();
	$key = 'pixva_sub_' . $id;
	for ( $try = 0; $try < 3; $try++ ) {
		global $wpdb;
		$raw = $wpdb->get_var( $wpdb->prepare( 'SELECT option_value FROM ' . $wpdb->options . ' WHERE option_name = %s LIMIT 1', $key ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- atomic arbitration.
		if ( null === $raw ) {
			$record = array(
				's' => 'pending',
				't' => time(),
				'a' => (string) $action,
			);
			if ( add_option( $key, $record, '', 'no' ) ) {
				return true;
			}
			continue; // Lost the INSERT race: re-read the winner's row.
		}
		$row = maybe_unserialize( $raw );
		if ( ! is_array( $row ) || ! isset( $row['s'], $row['t'] ) ) {
			return false; // Corrupt row: reject instead of guessing.
		}
		if ( $action && isset( $row['a'] ) && '' !== (string) $row['a'] && $row['a'] !== $action ) {
			return false; // Id was issued for a different form.
		}
		$age = time() - (int) $row['t'];
		if ( 'done' === $row['s'] ) {
			if ( $age > PIXVA_SUB_TTL ) {
				$wpdb->delete( $wpdb->options, array( 'option_name' => $key, 'option_value' => $raw ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- CAS delete of our own expired row.
				wp_cache_delete( $key, 'options' );
				wp_cache_delete( 'notoptions', 'options' );
				continue;
			}
			return is_array( $row['p'] ?? null ) ? $row['p'] : array();
		}
		// Pending record.
		if ( $age < PIXVA_SUB_PENDING_TTL ) {
			return array( 'pending' => true );
		}
		// Abandoned by a crashed request: take over with a CAS replace so a
		// second takeover cannot clobber a third request's fresh claim.
		$fresh = array(
			's' => 'pending',
			't' => time(),
			'a' => (string) $action,
		);
		$n = $wpdb->update(
			$wpdb->options,
			array( 'option_value' => maybe_serialize( $fresh ) ),
			array(
				'option_name'  => $key,
				'option_value' => $raw,
			),
			null,
			null
		); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		if ( false !== $n ) {
				wp_cache_delete( $key, 'options' );
				wp_cache_delete( 'notoptions', 'options' );
				return true;
			}
	}
	return false;
}

/**
 * Store the outcome of a processed submission id (replayed on double submit).
 *
 * @param string $id     Submission id.
 * @param array  $result Result payload (no PII beyond what the submitter already has).
 * @return void
 */
function pixva_finish_submission( $id, $result ) {
	$id = pixva_submission_normalize_id( $id );
	if ( '' === $id ) {
		return;
	}
	global $wpdb;
	$key    = 'pixva_sub_' . $id;
	$record = array(
		's' => 'done',
		't' => time(),
		'p' => (array) $result,
	);
	$updated = $wpdb->update(
		$wpdb->options,
		array( 'option_value' => maybe_serialize( $record ) ),
		array( 'option_name' => $key ),
		null,
		null
	); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- claim row is owned by this request.
	if ( empty( $updated ) ) {
		// Row vanished (explicit release raced the finish): re-insert once.
		add_option( $key, $record, '', 'no' );
	}
	wp_cache_delete( $key, 'options' );
	wp_cache_delete( 'notoptions', 'options' );
}

/**
 * Release a reserved id after a validation failure so the user can retry.
 *
 * @param string $id Submission id.
 * @return void
 */
function pixva_release_submission( $id ) {
	$id = pixva_submission_normalize_id( $id );
	if ( '' === $id ) {
		return;
	}
	pixva_submission_delete( $id );
}

/*
 * ---------------------------------------------------------------------------
 * 4) Mail
 * ---------------------------------------------------------------------------
 */

/**
 * Strip CR/LF (header injection).
 *
 * @param string $text Text.
 * @return string
 */
function pixva_strip_header_breaks( $text ) {
	return trim( (string) preg_replace( '/[\r\n]+/', ' ', (string) $text ) );
}

/**
 * Send a plain-text notification safely.
 *
 * @param string $to      Recipient.
 * @param string $subject Subject.
 * @param string $body    Body.
 * @return bool
 */
function pixva_safe_mail( $to, $subject, $body ) {
	$to      = sanitize_email( (string) $to );
	$subject = pixva_strip_header_breaks( $subject );
	if ( ! is_email( $to ) || '' === $subject ) {
		return false;
	}
	return (bool) wp_mail( $to, $subject, (string) $body );
}

/**
 * Second line of defence on every wp_mail call.
 *
 * @param array $args Args.
 * @return array
 */
function pixva_harden_wp_mail( $args ) {
	if ( isset( $args['subject'] ) ) {
		$args['subject'] = pixva_strip_header_breaks( $args['subject'] );
	}
	if ( isset( $args['headers'] ) ) {
		$args['headers'] = is_array( $args['headers'] ) ? array_map( 'pixva_strip_header_breaks', $args['headers'] ) : pixva_strip_header_breaks( $args['headers'] );
	}
	return $args;
}
add_filter( 'wp_mail', 'pixva_harden_wp_mail' );

/*
 * ---------------------------------------------------------------------------
 * 5) Private uploads (§11)
 * ---------------------------------------------------------------------------
 */

/**
 * Absolute path of the private upload directory; created with deny rules.
 * Files here are never linked publicly; they are streamed by
 * pixva_stream_order_photo() (repairs.php) after a capability check.
 *
 * @return string Empty string on failure.
 */
function pixva_private_dir() {
	$uploads = wp_upload_dir( null, false );
	if ( ! empty( $uploads['error'] ) ) {
		return '';
	}
	$dir = trailingslashit( $uploads['basedir'] ) . 'pixva-private';
	if ( ! is_dir( $dir ) && ! wp_mkdir_p( $dir ) ) {
		return '';
	}
	// Apache 2.4 (with or without mod_access_compat) and 2.2. A bare "Deny" line
	// is a 500 on 2.4 without mod_access_compat, so the 2.0.0 file is replaced.
	$htaccess = $dir . '/.htaccess';
	$rules    = "<IfModule mod_authz_core.c>\n\tRequire all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\n\tOrder allow,deny\n\tDeny from all\n</IfModule>\n";
	if ( ! file_exists( $htaccess ) || "Require all denied\nDeny from all\n" === file_get_contents( $htaccess ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- local file.
		file_put_contents( $htaccess, $rules ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
	}
	if ( ! file_exists( $dir . '/index.php' ) ) {
		file_put_contents( $dir . '/index.php', "<?php\n// Silence.\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
	}
	return $dir;
}

/**
 * Allowed image types for customer uploads: real MIME sniffed from content.
 *
 * @return array<string,string> ext => mime
 */
function pixva_upload_mimes() {
	return array(
		'jpg'  => 'image/jpeg',
		'png'  => 'image/png',
		'webp' => 'image/webp',
	);
}

/**
 * Max upload size in bytes (filterable, default 5 MB).
 *
 * @return int
 */
function pixva_upload_max_bytes() {
	return (int) apply_filters( 'pixva_upload_max_bytes', 5 * MB_IN_BYTES );
}

/**
 * Validate and store one uploaded image privately.
 *
 * Checks: PHP upload error, is_uploaded_file, size, real content type via
 * wp_get_image_mime (magic bytes), getimagesize, extension whitelist. The
 * stored name is random; the original filename is discarded.
 *
 * @param array $file Single $_FILES entry.
 * @return string|WP_Error Stored relative filename.
 */
function pixva_store_private_image( $file ) {
	if ( ! is_array( $file ) || ! isset( $file['error'], $file['tmp_name'], $file['size'] ) ) {
		return new WP_Error( 'upload_invalid', __( 'فایل ارسال‌شده معتبر نیست.', 'pixva' ) );
	}
	if ( UPLOAD_ERR_OK !== (int) $file['error'] ) {
		return new WP_Error( 'upload_error', __( 'بارگذاری فایل انجام نشد. دوباره تلاش کنید.', 'pixva' ) );
	}
	if ( (int) $file['size'] <= 0 || (int) $file['size'] > pixva_upload_max_bytes() ) {
		/* translators: %s: max size in MB. */
		return new WP_Error( 'upload_size', sprintf( __( 'حجم هر تصویر باید کمتر از %s مگابایت باشد.', 'pixva' ), pixva_fa_num( (int) round( pixva_upload_max_bytes() / MB_IN_BYTES ) ) ) );
	}
	$tmp = (string) $file['tmp_name'];
	if ( ! apply_filters( 'pixva_is_uploaded_file', is_uploaded_file( $tmp ), $tmp ) ) {
		return new WP_Error( 'upload_invalid', __( 'فایل ارسال‌شده معتبر نیست.', 'pixva' ) );
	}
	$mime    = function_exists( 'wp_get_image_mime' ) ? wp_get_image_mime( $tmp ) : false;
	$allowed = pixva_upload_mimes();
	$ext     = array_search( $mime, $allowed, true );
	if ( false === $ext || false === @getimagesize( $tmp ) ) { // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- invalid images must fail quietly.
		return new WP_Error( 'upload_type', __( 'فقط تصویر JPG، PNG یا WebP پذیرفته می‌شود.', 'pixva' ) );
	}
	$dir = pixva_private_dir();
	if ( '' === $dir ) {
		return new WP_Error( 'upload_dir', __( 'ذخیره تصویر ممکن نشد.', 'pixva' ) );
	}
	$name = gmdate( 'Ym' ) . '-' . wp_generate_password( 24, false, false ) . '.' . $ext;
	$dest = $dir . '/' . $name;
	$ok   = apply_filters( 'pixva_move_uploaded_file', null, $tmp, $dest );
	if ( null === $ok ) {
		$ok = @move_uploaded_file( $tmp, $dest ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
	}
	if ( ! $ok ) {
		return new WP_Error( 'upload_move', __( 'ذخیره تصویر ممکن نشد.', 'pixva' ) );
	}
	@chmod( $dest, 0640 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged,WordPress.WP.AlternativeFunctions.file_system_operations_chmod -- file was just written by move_uploaded_file(); WP_Filesystem may need credentials.
	return $name;
}

/**
 * Normalise a multi-file $_FILES field into a list of single entries.
 *
 * @param string $field Field name.
 * @param int    $max   Max files.
 * @return array[]
 */
function pixva_collect_files( $field, $max = 3 ) {
	if ( empty( $_FILES[ $field ] ) || ! is_array( $_FILES[ $field ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- verified by caller.
		return array();
	}
	$f   = $_FILES[ $field ]; // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- validated per file.
	$out = array();
	if ( is_array( $f['name'] ) ) {
		foreach ( array_keys( $f['name'] ) as $i ) {
			if ( isset( $f['error'][ $i ] ) && UPLOAD_ERR_NO_FILE === (int) $f['error'][ $i ] ) {
				continue;
			}
			$out[] = array(
				'name'     => $f['name'][ $i ],
				'type'     => $f['type'][ $i ] ?? '',
				'tmp_name' => $f['tmp_name'][ $i ] ?? '',
				'error'    => $f['error'][ $i ] ?? UPLOAD_ERR_NO_FILE,
				'size'     => $f['size'][ $i ] ?? 0,
			);
		}
	} elseif ( UPLOAD_ERR_NO_FILE !== (int) ( $f['error'] ?? UPLOAD_ERR_NO_FILE ) ) {
		$out[] = $f;
	}
	return array_slice( $out, 0, max( 0, (int) $max ) + 1 ); // +1 so caller can detect "too many".
}

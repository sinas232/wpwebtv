<?php
/**
 * Unit test: order lookup lock and order status mutex (pixva/inc/security.php, pixva/inc/repairs.php).
 *
 * Loads the real theme code with a small in-memory WordPress stub. The stub's
 * wpdb->insert() enforces UNIQUE(option_name) the way wp_options does, so the
 * create-once primitive is exercised as written.
 *
 * LIMITS (not covered here): real MySQL/SQLite concurrency, real object cache,
 * clock rollover at the hour boundary. Those need a live WordPress; see the QA report.
 *
 * Run: php tests/unit/php/order-security.test.php   (exit 0 = pass)
 */

// ---------------------------------------------------------------------------
// Minimal WordPress stub.
// ---------------------------------------------------------------------------
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}
if ( ! defined( 'HOUR_IN_SECONDS' ) ) {
	define( 'HOUR_IN_SECONDS', 3600 );
}
if ( ! defined( 'MINUTE_IN_SECONDS' ) ) {
	define( 'MINUTE_IN_SECONDS', 60 );
}
if ( ! defined( 'DAY_IN_SECONDS' ) ) {
	define( 'DAY_IN_SECONDS', 86400 );
}

class WP_Error {
	public $code;
	public $message;
	public $data;
	public function __construct( $code = '', $message = '', $data = null ) {
		$this->code    = $code;
		$this->message = $message;
		$this->data    = $data;
	}
	public function get_error_code() {
		return $this->code;
	}
}

function is_wp_error( $thing ) {
	return $thing instanceof WP_Error;
}

class FakeWpdb {
	public $options = 'wp_options';
	public $rows    = array(); // option_name => option_value (UNIQUE key)

	public function suppress_errors( $on = true ) {
		return false;
	}

	/** Mirrors $wpdb->insert(): returns 1 on success, false on a duplicate key. */
	public function insert( $table, $data, $format = null ) {
		$name = $data['option_name'];
		if ( array_key_exists( $name, $this->rows ) ) {
			return false; // duplicate key error
		}
		$this->rows[ $name ] = $data['option_value'];
		return 1;
	}

	/** Mirrors $wpdb->delete() with AND-ed equality conditions. */
	public function delete( $table, $where, $format = null ) {
		$name = $where['option_name'];
		if ( ! array_key_exists( $name, $this->rows ) ) {
			return 0;
		}
		if ( isset( $where['option_value'] ) && (string) $this->rows[ $name ] !== (string) $where['option_value'] ) {
			return 0;
		}
		unset( $this->rows[ $name ] );
		return 1;
	}

	public function prepare( $sql, ...$args ) {
		return array( 'sql' => $sql, 'args' => $args );
	}

	public function get_var( $prepared ) {
		$name = $prepared['args'][0];
		return array_key_exists( $name, $this->rows ) ? $this->rows[ $name ] : null;
	}
}

$GLOBALS['wpdb']      = new FakeWpdb();
$GLOBALS['transient'] = array();
$GLOBALS['post_meta'] = array();
$GLOBALS['posts']     = array();

function wp_cache_delete( $k, $g = '' ) {
	return true;
}
function wp_salt( $s = 'auth' ) {
	return 'test-salt-' . $s;
}
function wp_generate_password( $len = 12, $special = true, $extra = false ) {
	static $n = 0;
	$n++;
	return str_pad( dechex( $n ), $len, 'a' );
}
function get_transient( $k ) {
	return $GLOBALS['transient'][ $k ] ?? false;
}
function set_transient( $k, $v, $e = 0 ) {
	$GLOBALS['transient'][ $k ] = $v;
	return true;
}
function delete_transient( $k ) {
	unset( $GLOBALS['transient'][ $k ] );
	return true;
}
function apply_filters( $tag, $value, ...$args ) {
	return $value;
}
function do_action( ...$args ) {
}
function add_action( ...$args ) {
}
function add_filter( ...$args ) {
}
function add_shortcode( ...$args ) {
}
function sanitize_key( $k ) {
	return strtolower( preg_replace( '/[^a-z0-9_\-]/i', '', (string) $k ) );
}
function sanitize_text_field( $s ) {
	return trim( (string) $s );
}
function sanitize_textarea_field( $s ) {
	return trim( (string) $s );
}
function wp_unslash( $s ) {
	return $s;
}
function __( $s, $d = '' ) {
	return $s;
}
function esc_html__( $s, $d = '' ) {
	return $s;
}
function esc_attr__( $s, $d = '' ) {
	return $s;
}
function get_post_meta( $id, $key = '', $single = false ) {
	return $GLOBALS['post_meta'][ $id ][ $key ] ?? '';
}
function update_post_meta( $id, $key, $value ) {
	$GLOBALS['post_meta'][ $id ][ $key ] = $value;
	return true;
}
function get_posts( $args ) {
	$code = $args['meta_value'];
	foreach ( $GLOBALS['posts'] as $id => $c ) {
		if ( $c === $code ) {
			return array( $id );
		}
	}
	return array();
}
function get_post( $id ) {
	return null;
}
function wp_slash( $s ) {
	return $s;
}
function wp_json_encode( $v, $o = 0, $d = 512 ) {
	return json_encode( $v, $o, $d );
}
function wp_date( $f, $t = null ) {
	return '2026-10-09';
}
function current_time( $t = 'mysql' ) {
	return '2026-10-09 00:00:00';
}

// Theme code under test.
$root = dirname( __DIR__, 3 ) . '/pixva/inc/';
require_once $root . 'helpers.php';
require_once $root . 'security.php';
require_once $root . 'repairs.php';

// ---------------------------------------------------------------------------
// Harness.
// ---------------------------------------------------------------------------
$passed = 0;
$failed = 0;
function check( $name, $cond, $detail = '' ) {
	global $passed, $failed;
	if ( $cond ) {
		$passed++;
		echo "ok   - $name\n";
	} else {
		$failed++;
		echo "FAIL - $name" . ( '' !== $detail ? " ($detail)" : '' ) . "\n";
	}
}

function reset_state() {
	$GLOBALS['wpdb']->rows      = array();
	$GLOBALS['transient']       = array();
	$GLOBALS['post_meta']       = array();
	$GLOBALS['posts']           = array( 101 => 'PXV-ABC-123' );
	$GLOBALS['post_meta'][101]  = array(
		'_pixva_order_phone'      => '09121234567',
		'_pixva_order_status'     => 'new',
	);
}

function set_client( $ip ) {
	$_SERVER['REMOTE_ADDR'] = $ip;
}

// ---------------------------------------------------------------------------
// A. Create-once primitive.
// ---------------------------------------------------------------------------
reset_state();
check( 'create_once creates a missing row', pixva_create_once( 'pixva_t_a', 'v1' ) === true );
check( 'create_once refuses an existing row', pixva_create_once( 'pixva_t_a', 'v2' ) === false );
check( 'create_once keeps the first value', pixva_option_value( 'pixva_t_a' ) === 'v1' );
check( 'delete_option_row with wrong value keeps the row', pixva_delete_option_row( 'pixva_t_a', 'nope' ) === false && pixva_option_value( 'pixva_t_a' ) === 'v1' );
check( 'delete_option_row with matching value removes it', pixva_delete_option_row( 'pixva_t_a', 'v1' ) === true && pixva_option_value( 'pixva_t_a' ) === null );

// ---------------------------------------------------------------------------
// B. Order status mutex.
// ---------------------------------------------------------------------------
reset_state();
$owner = pixva_order_lock_acquire( 101 );
check( 'first acquire succeeds', is_string( $owner ) );
check( 'lock row exists while held', pixva_option_value( 'pixva_olock_101' ) === $owner );

// A second holder must wait and then give up with WP_Error (10 s budget).
$t0     = microtime( true );
$second = pixva_order_lock_acquire( 101 );
$waited = microtime( true ) - $t0;
check( 'second acquire is refused while the lock is fresh', is_wp_error( $second ) && 'busy' === $second->code );
check( 'second acquire waits before giving up', $waited >= 9.0, sprintf( '%.2fs', $waited ) );

pixva_order_lock_release( 101, 'not-the-owner' );
check( 'release with a foreign token leaves the lock in place', pixva_option_value( 'pixva_olock_101' ) === $owner );
pixva_order_lock_release( 101, $owner );
check( 'release with the owner token removes the lock', pixva_option_value( 'pixva_olock_101' ) === null );

// Stale lock (owner crashed) is taken over.
reset_state();
$GLOBALS['wpdb']->rows['pixva_olock_101'] = 'dead-owner|' . ( time() - 120 );
$owner = pixva_order_lock_acquire( 101 );
check( 'stale lock is taken over', is_string( $owner ) && pixva_option_value( 'pixva_olock_101' ) === $owner );
pixva_order_lock_release( 101, $owner );

// Fresh lock is never taken over, even if its timestamp looks old to a racing reader.
reset_state();
$GLOBALS['wpdb']->rows['pixva_olock_101'] = 'live-owner|' . time();
check( 'fresh lock is not stale', is_wp_error( pixva_order_lock_acquire( 101 ) ) );
$GLOBALS['wpdb']->rows = array();

// Lock released when the locked body returns an error.
reset_state();
$res = pixva_set_order_status( 101, 'no_such_status', '' );
check( 'invalid status returns WP_Error through the wrapper', is_wp_error( $res ) );
check( 'lock is released after an error return', pixva_option_value( 'pixva_olock_101' ) === null );
$res = pixva_set_order_status( 101, 'received', '' );
check( 'a valid change succeeds after an error return', $res === true, is_wp_error( $res ) ? $res->message : '' );
check( 'valid change persists the status', get_post_meta( 101, '_pixva_order_status', true ) === 'received' );
check( 'lock is released after a successful change', pixva_option_value( 'pixva_olock_101' ) === null );

// History is appended under the lock, not overwritten.
reset_state();
pixva_set_order_status( 101, 'received', 'first' );
pixva_set_order_status( 101, 'diagnosed', 'second' );
$steps = json_decode( (string) get_post_meta( 101, '_pixva_order_steps', true ), true );
check( 'both history entries are kept', is_array( $steps ) && count( $steps ) >= 2, is_array( $steps ) ? count( $steps ) . ' entries' : 'not an array' );

// ---------------------------------------------------------------------------
// C. Lookup lock: per (code, client), no global per-code lockout.
// ---------------------------------------------------------------------------
reset_state();
set_client( '198.51.100.10' ); // attacker, knows the code only
for ( $i = 0; $i < 5; $i++ ) {
	$r = pixva_verify_order_access( 'PXV-ABC-123', '09000000000' );
	$ok = is_wp_error( $r ) && 'not_found' === $r->code;
	if ( ! $ok ) {
		break;
	}
}
check( 'five wrong phones return not_found', $ok );
$r = pixva_verify_order_access( 'PXV-ABC-123', '09000000001' );
check( 'sixth attempt from the same client is locked', is_wp_error( $r ) && 'locked' === $r->code );

set_client( '203.0.113.77' ); // real customer, different client
$r = pixva_verify_order_access( 'PXV-ABC-123', '09121234567' );
check( 'the real customer is NOT locked out by the attacker', $r === 101, is_wp_error( $r ) ? $r->code : (string) $r );

set_client( '198.51.100.10' );
$r = pixva_verify_order_access( 'PXV-ABC-123', '09121234567' );
check( 'attacker stays locked even with the correct phone (for their own client only)', is_wp_error( $r ) && 'locked' === $r->code );

// Success clears that client's failures.
reset_state();
set_client( '198.51.100.20' );
for ( $i = 0; $i < 4; $i++ ) {
	pixva_verify_order_access( 'PXV-ABC-123', '09000000000' );
}
$r = pixva_verify_order_access( 'PXV-ABC-123', '09121234567' );
check( 'correct phone after four failures succeeds', $r === 101 );
for ( $i = 0; $i < 4; $i++ ) {
	pixva_verify_order_access( 'PXV-ABC-123', '09000000000' );
}
$r = pixva_verify_order_access( 'PXV-ABC-123', '09000000002' );
check( 'failures were cleared by the success (four more still not locked)', is_wp_error( $r ) && 'not_found' === $r->code );

// Lock applies identically to unknown codes, so there is no existence oracle.
reset_state();
set_client( '198.51.100.30' );
for ( $i = 0; $i < 5; $i++ ) {
	pixva_verify_order_access( 'PXV-ZZZ-999', '09000000000' );
}
$r = pixva_verify_order_access( 'PXV-ZZZ-999', '09000000000' );
check( 'unknown code locks the same way as a real one', is_wp_error( $r ) && 'locked' === $r->code );

// Attempt slots are bounded: concurrent-style repeated failures never create more than the max.
reset_state();
set_client( '198.51.100.40' );
for ( $i = 0; $i < 20; $i++ ) {
	pixva_verify_order_access( 'PXV-ABC-123', '09000000000' );
}
$slots = array_filter( array_keys( $GLOBALS['wpdb']->rows ), function ( $k ) {
	return 0 === strpos( $k, 'pixva_cf_' );
} );
check( 'failure rows are capped at PIXVA_CODE_ATTEMPT_MAX for the scope', count( $slots ) === PIXVA_CODE_ATTEMPT_MAX, count( $slots ) . ' rows' );

echo "\n$passed passed, $failed failed\n";
exit( $failed ? 1 : 0 );

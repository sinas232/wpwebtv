<?php
/**
 * Unit tests: lookup attempt limits, order status lock, submission claims and row pruning.
 *
 * Loads the real theme code with a small in-memory WordPress stub. The stub's
 * wpdb->insert() and wpdb->update() enforce the same conditions as wp_options
 * (UNIQUE option_name; UPDATE/DELETE only when the old value matches), so the
 * create-once and compare-and-replace logic is exercised as written.
 *
 * SIMULATED, NOT PROVEN HERE: real MySQL/SQLite row locking and concurrency,
 * the object cache, real WP-Cron, and real clients behind proxies. These are
 * listed as NOT TESTED in the report; see tests/unit/README.md.
 *
 * Run: PHP=8.3 php-wasm-cli tests/unit/php/order-security.test.php
 * (the runner prints "N passed, M failed"; read that line, not the exit code.)
 */

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

// ---------------------------------------------------------------------------
// Minimal WordPress stub.
// ---------------------------------------------------------------------------
class WP_Error {
	public $code;
	public $message;
	public $data;
	public function __construct( $code = '', $message = '', $data = null ) {
		$this->code    = $code;
		$this->message = $message;
		$this->data    = $data;
	}
}
function is_wp_error( $thing ) {
	return $thing instanceof WP_Error;
}

$GLOBALS['hooks'] = array(
	'action' => array(),
	'filter' => array(),
);
function add_action( $tag, $cb, $priority = 10, $args = 1 ) {
	$GLOBALS['hooks']['action'][ $tag ][] = $cb;
}
function add_filter( $tag, $cb, $priority = 10, $args = 1 ) {
	$GLOBALS['hooks']['filter'][ $tag ][] = $cb;
}
function do_action( $tag, ...$args ) {
	foreach ( $GLOBALS['hooks']['action'][ $tag ] ?? array() as $cb ) {
		call_user_func_array( $cb, $args );
	}
}
function apply_filters( $tag, $value, ...$args ) {
	if ( 'pixva_now' === $tag && isset( $GLOBALS['clock'] ) ) {
		return $GLOBALS['clock'];
	}
	foreach ( $GLOBALS['hooks']['filter'][ $tag ] ?? array() as $cb ) {
		$value = call_user_func_array( $cb, array_merge( array( $value ), $args ) );
	}
	return $value;
}
function add_shortcode( ...$args ) {
}

class FakeWpdb {
	public $options = 'wp_options';
	public $posts   = 'wp_posts';
	public $rows    = array();

	public function suppress_errors( $on = true ) {
		return false;
	}
	public function insert( $table, $data, $format = null ) {
		if ( array_key_exists( $data['option_name'], $this->rows ) ) {
			return false; // UNIQUE(option_name) violation.
		}
		$this->rows[ $data['option_name'] ] = (string) $data['option_value'];
		return 1;
	}
	public function update( $table, $data, $where, $format = null, $where_format = null ) {
		$name = $where['option_name'];
		if ( ! array_key_exists( $name, $this->rows ) || $this->rows[ $name ] !== (string) $where['option_value'] ) {
			return 0;
		}
		$this->rows[ $name ] = (string) $data['option_value'];
		return 1;
	}
	public function delete( $table, $where, $format = null ) {
		$name = $where['option_name'];
		if ( ! array_key_exists( $name, $this->rows ) ) {
			return 0;
		}
		if ( isset( $where['option_value'] ) && $this->rows[ $name ] !== (string) $where['option_value'] ) {
			return 0;
		}
		unset( $this->rows[ $name ] );
		return 1;
	}
	public function esc_like( $text ) {
		return addcslashes( (string) $text, '_%\\' );
	}
	public function prepare( $sql, ...$args ) {
		return array( 'sql' => $sql, 'args' => $args );
	}
	public function get_col( $prepared ) {
		// Only the reservation lookup uses get_col(): SELECT ID ... post_name LIKE 'sid%'.
		$type    = $prepared['args'][0];
		$prefix  = rtrim( str_replace( '\\_', '_', (string) $prepared['args'][1] ), '%' );
		$ids     = array();
		foreach ( $GLOBALS['post_rows'] as $id => $row ) {
			if ( $row['post_type'] === $type && 0 === strpos( (string) $row['post_name'], $prefix ) ) {
				$ids[] = (int) $id;
			}
		}
		sort( $ids );
		return array_slice( $ids, 0, 20 );
	}
	public function get_var( $prepared ) {
		$name = $prepared['args'][0];
		return array_key_exists( $name, $this->rows ) ? $this->rows[ $name ] : null;
	}
	public function get_results( $prepared ) {
		$pattern = str_replace( '\\_', '_', (string) $prepared['args'][0] );
		$prefix  = rtrim( $pattern, '%' );
		$out     = array();
		foreach ( $this->rows as $name => $value ) {
			if ( 0 === strpos( $name, $prefix ) ) {
				$out[] = (object) array(
					'option_name'  => $name,
					'option_value' => $value,
				);
				if ( count( $out ) >= 500 ) {
					break;
				}
			}
		}
		return $out;
	}
}

$GLOBALS['wpdb']      = new FakeWpdb();
$GLOBALS['transient'] = array();
$GLOBALS['post_meta'] = array();
$GLOBALS['posts']     = array();
$GLOBALS['post_rows'] = array();

function wp_cache_delete( $k, $g = '' ) {
	return true;
}
function wp_salt( $s = 'auth' ) {
	return 'test-salt-' . $s;
}
function wp_generate_password( $len = 12, $special = true, $extra = false ) {
	static $n = 0;
	$n++;
	return str_pad( dechex( $n ) . bin2hex( random_bytes( 4 ) ), $len, 'a' );
}
function wp_json_encode( $v, $o = 0, $d = 512 ) {
	return json_encode( $v, $o, $d );
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
function wp_slash( $s ) {
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
	$row = $GLOBALS['post_rows'][ $id ] ?? null;
	return $row ? (object) $row : null;
}
// Post store for order rows: post_name is what WordPress uses for the slug.
function wp_insert_post( $args, $wp_error = false ) {
	$GLOBALS['post_id_seq'] = ( $GLOBALS['post_id_seq'] ?? 200 ) + 1;
	$id                     = $GLOBALS['post_id_seq'];
	$name                   = (string) ( $args['post_name'] ?? '' );
	if ( '' !== $name ) {
		// WordPress appends -2, -3 … when another post of the type already has the slug.
		$base = $name;
		for ( $n = 2; ; $n++ ) {
			$taken = false;
			foreach ( $GLOBALS['post_rows'] as $row ) {
				if ( $row['post_type'] === $args['post_type'] && $row['post_name'] === $name ) {
					$taken = true;
					break;
				}
			}
			if ( ! $taken ) {
				break;
			}
			$name = $base . '-' . $n;
		}
	}
	if ( ! empty( $GLOBALS['insert_fails'] ) ) {
		--$GLOBALS['insert_fails'];
		return new WP_Error( 'db', 'insert failed' );
	}
	$GLOBALS['post_rows'][ $id ] = array(
		'post_type' => $args['post_type'],
		'post_name' => $name,
	);
	$GLOBALS['posts'][ $id ]     = (string) $args['post_title'];
	$GLOBALS['insert_log'][]     = $id;
	return $id;
}
function wp_update_post( $args ) {
	$id = (int) $args['ID'];
	if ( isset( $args['post_title'] ) ) {
		$GLOBALS['posts'][ $id ] = (string) $args['post_title'];
	}
	return $id;
}
function wp_delete_post( $id, $force = false ) {
	$id = (int) $id;
	unset( $GLOBALS['post_rows'][ $id ], $GLOBALS['posts'][ $id ] );
	$GLOBALS['deleted_log'][] = $id;
	return true;
}
function wp_date( $f, $t = null ) {
	return '2026-10-09';
}
function current_time( $t = 'mysql' ) {
	return '2026-10-09 00:00:00';
}
function current_user_can( $cap, ...$args ) {
	return ! empty( $GLOBALS['can'] );
}

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
	$GLOBALS['wpdb']->rows     = array();
	$GLOBALS['transient']      = array();
	$GLOBALS['post_meta']      = array();
	$GLOBALS['posts']          = array( 101 => 'PXV-ABC-123' );
	$GLOBALS['post_rows']      = array( 101 => array( 'post_type' => 'pixva_orders', 'post_name' => 'pxv-abc-123' ) );
	$GLOBALS['insert_log']     = array();
	$GLOBALS['deleted_log']    = array();
	$GLOBALS['insert_fails']   = 0;
	$GLOBALS['post_meta'][101] = array(
		'_pixva_order_phone'  => '09121234567',
		'_pixva_order_status' => 'new',
	);
	$GLOBALS['hooks']['action'] = array();
	$GLOBALS['clock']           = null;
	$GLOBALS['can']             = true;
}
function set_client( $ip ) {
	$_SERVER['REMOTE_ADDR'] = $ip;
}
function set_clock( $t ) {
	$GLOBALS['clock'] = $t;
}
function rows_with_prefix( $prefix ) {
	return array_values( array_filter( array_keys( $GLOBALS['wpdb']->rows ), function ( $k ) use ( $prefix ) {
		return 0 === strpos( $k, $prefix );
	} ) );
}
function sid( $n ) {
	return str_pad( dechex( $n ), 32, '0', STR_PAD_LEFT );
}

// ---------------------------------------------------------------------------
// A. Create-once and compare-and-replace primitives.
// ---------------------------------------------------------------------------
reset_state();
listen_created();
check( 'create_once creates a missing row', pixva_create_once( 'pixva_t_a', 'v1' ) === true );
check( 'create_once refuses an existing row', pixva_create_once( 'pixva_t_a', 'v2' ) === false );
check( 'create_once keeps the first value', pixva_option_value( 'pixva_t_a' ) === 'v1' );
check( 'replace with a stale expected value fails', pixva_replace_option_row( 'pixva_t_a', 'old', 'v3' ) === false && pixva_option_value( 'pixva_t_a' ) === 'v1' );
check( 'replace with the current value succeeds once', pixva_replace_option_row( 'pixva_t_a', 'v1', 'v3' ) === true && pixva_replace_option_row( 'pixva_t_a', 'v1', 'v4' ) === false );
check( 'delete with wrong value keeps the row', pixva_delete_option_row( 'pixva_t_a', 'nope' ) === false && pixva_option_value( 'pixva_t_a' ) === 'v3' );
check( 'delete with matching value removes it', pixva_delete_option_row( 'pixva_t_a', 'v3' ) === true && pixva_option_value( 'pixva_t_a' ) === null );

// ---------------------------------------------------------------------------
// B. Order status lock.
// ---------------------------------------------------------------------------
reset_state();
listen_created();
$owner = pixva_order_lock_acquire( 101 );
check( 'first acquire succeeds', is_string( $owner ) );
check( 'lock row holds the owner token', pixva_option_value( 'pixva_olock_101' ) === $owner );

$t0     = microtime( true );
$second = pixva_order_lock_acquire( 101 );
$waited = microtime( true ) - $t0;
check( 'second acquire is refused while the lock is fresh', is_wp_error( $second ) && 'busy' === $second->code );
check( 'second acquire waits up to its budget before giving up', $waited >= 9.0, sprintf( '%.2fs', $waited ) );

check( 'release with a foreign token leaves the lock in place', pixva_order_lock_release( 101, 'not-the-owner' ) === false && pixva_option_value( 'pixva_olock_101' ) === $owner );
check( 'lock_held is true for the owner', pixva_order_lock_held( 101, $owner ) === true );
check( 'release with the owner token removes the lock', pixva_order_lock_release( 101, $owner ) === true && pixva_option_value( 'pixva_olock_101' ) === null );

// Stale lock (owner presumed dead) is taken over; the old owner is then fenced out.
reset_state();
listen_created();
$GLOBALS['wpdb']->rows['pixva_olock_101'] = 'dead-owner|' . ( time() - 120 );
$owner = pixva_order_lock_acquire( 101 );
check( 'stale lock is taken over', is_string( $owner ) && pixva_option_value( 'pixva_olock_101' ) === $owner );
pixva_order_lock_release( 101, $owner );

reset_state();
listen_created();
$GLOBALS['wpdb']->rows['pixva_olock_101'] = 'live-owner|' . time();
check( 'fresh lock is not stale', is_wp_error( pixva_order_lock_acquire( 101 ) ) );
$GLOBALS['wpdb']->rows = array();

// Fencing: an owner whose lock was taken over must not write.
reset_state();
listen_created();
$owner_a = pixva_order_lock_acquire( 101 );
$GLOBALS['wpdb']->rows['pixva_olock_101'] = 'takeover-owner|' . time(); // another process took the lock
$res = pixva_set_order_status_locked( 101, 'received', '', $owner_a );
check( 'fenced owner gets busy and writes nothing', is_wp_error( $res ) && 'busy' === $res->code && get_post_meta( 101, '_pixva_order_status', true ) === 'new' );
$takeover_value = pixva_option_value( 'pixva_olock_101' );
check( 'fenced owner does not remove the new owner\'s lock', pixva_order_lock_release( 101, $owner_a ) === false && pixva_option_value( 'pixva_olock_101' ) === $takeover_value );

// Lock released on error and on success; a listener runs after release (no deadlock).
reset_state();
listen_created();
$res = pixva_set_order_status( 101, 'no_such_status', '' );
check( 'invalid status returns WP_Error through the wrapper', is_wp_error( $res ) );
check( 'lock released after an error return', pixva_option_value( 'pixva_olock_101' ) === null );

reset_state();
listen_created();
$GLOBALS['inhook'] = null;
add_action( 'pixva_order_status_changed', function ( $id, $status, $from ) {
	$o                 = pixva_order_lock_acquire( $id ); // Must succeed: lock already released.
	$GLOBALS['inhook'] = is_string( $o );
	if ( is_string( $o ) ) {
		pixva_order_lock_release( $id, $o );
	}
} );
$res = pixva_set_order_status( 101, 'received', '' );
check( 'valid change succeeds', $res === true, is_wp_error( $res ) ? $res->message : '' );
check( 'status persisted', get_post_meta( 101, '_pixva_order_status', true ) === 'received' );
check( 'listener runs after the lock is released (no deadlock)', $GLOBALS['inhook'] === true );
check( 'lock released after success', pixva_option_value( 'pixva_olock_101' ) === null );

reset_state();
listen_created();
pixva_set_order_status( 101, 'received', 'first' );
pixva_set_order_status( 101, 'diagnosed', 'second' );
$steps = json_decode( (string) get_post_meta( 101, '_pixva_order_steps', true ), true );
check( 'both history entries are kept', is_array( $steps ) && count( $steps ) >= 2 );

// ---------------------------------------------------------------------------
// C. Client scope (IPv4, IPv6 /64, invalid input).
// ---------------------------------------------------------------------------
set_client( '2001:db8:aaaa:1::1' );
$k1 = pixva_client_key();
set_client( '2001:db8:aaaa:1:ffff:ffff:ffff:ffff' );
$k2 = pixva_client_key();
set_client( '2001:db8:aaaa:2::1' );
$k3 = pixva_client_key();
check( 'IPv6 addresses in one /64 share a client key', $k1 === $k2 );
check( 'IPv6 addresses in another /64 do not', $k1 !== $k3 );
set_client( '::ffff:192.0.2.1' );
$m = pixva_client_key();
set_client( '192.0.2.1' );
check( 'IPv4-mapped IPv6 equals its IPv4 client key', $m === pixva_client_key() );
set_client( 'not-an-ip' );
$g1 = pixva_client_key();
set_client( 'also bad' );
check( 'invalid addresses share one bucket', $g1 === pixva_client_key() );
set_client( '198.51.100.1' );
check( 'IPv4 client key is stable', pixva_client_key() === pixva_client_key() );

// ---------------------------------------------------------------------------
// D. Lookup attempts: per (code, client); no global block; hour boundary.
// ---------------------------------------------------------------------------
reset_state();
listen_created();
set_clock( 3600 * 100 + 600 );
set_client( '198.51.100.10' );
$ok = true;
for ( $i = 0; $i < 5; $i++ ) {
	$r = pixva_verify_order_access( 'PXV-ABC-123', '09000000000' );
	$ok = $ok && is_wp_error( $r ) && 'not_found' === $r->code;
}
check( 'five wrong phones return not_found', $ok );
$r = pixva_verify_order_access( 'PXV-ABC-123', '09000000001' );
check( 'sixth attempt from the same client is locked', is_wp_error( $r ) && 'locked' === $r->code );

set_client( '203.0.113.77' );
$r = pixva_verify_order_access( 'PXV-ABC-123', '09121234567' );
check( 'the real customer on another client is NOT locked out', $r === 101, is_wp_error( $r ) ? $r->code : (string) $r );

set_client( '198.51.100.10' );
$r = pixva_verify_order_access( 'PXV-ABC-123', '09121234567' );
check( 'the attacker stays locked, even with the correct phone (own scope only)', is_wp_error( $r ) && 'locked' === $r->code );

// IPv6: changing the low 64 bits does not escape the lock.
reset_state();
listen_created();
set_clock( 3600 * 100 + 600 );
set_client( '2001:db8:bbbb::1' );
for ( $i = 0; $i < 5; $i++ ) {
	pixva_verify_order_access( 'PXV-ABC-123', '09000000000' );
}
set_client( '2001:db8:bbbb::2' );
$r = pixva_verify_order_access( 'PXV-ABC-123', '09000000000' );
check( 'IPv6 host bits do not reset the lock (same /64)', is_wp_error( $r ) && 'locked' === $r->code );

// Success clears that client's failures.
reset_state();
listen_created();
set_clock( 3600 * 100 + 600 );
set_client( '198.51.100.20' );
for ( $i = 0; $i < 4; $i++ ) {
	pixva_verify_order_access( 'PXV-ABC-123', '09000000000' );
}
check( 'correct phone after four failures succeeds', pixva_verify_order_access( 'PXV-ABC-123', '09121234567' ) === 101 );
for ( $i = 0; $i < 4; $i++ ) {
	pixva_verify_order_access( 'PXV-ABC-123', '09000000000' );
}
$r = pixva_verify_order_access( 'PXV-ABC-123', '09000000002' );
check( 'success cleared the earlier failures (four more still not locked)', is_wp_error( $r ) && 'not_found' === $r->code );

// Unknown code locks the same way: no existence oracle.
reset_state();
listen_created();
set_clock( 3600 * 100 + 600 );
set_client( '198.51.100.30' );
for ( $i = 0; $i < 5; $i++ ) {
	pixva_verify_order_access( 'PXV-ZZZ-999', '09000000000' );
}
$r = pixva_verify_order_access( 'PXV-ZZZ-999', '09000000000' );
check( 'unknown code locks the same way as a real one', is_wp_error( $r ) && 'locked' === $r->code );

// Slots are bounded for a scope.
reset_state();
listen_created();
set_clock( 3600 * 100 + 600 );
set_client( '198.51.100.40' );
for ( $i = 0; $i < 20; $i++ ) {
	pixva_verify_order_access( 'PXV-ABC-123', '09000000000' );
}
check( 'attempt rows are capped at PIXVA_CODE_ATTEMPT_MAX per scope and hour', count( rows_with_prefix( 'pixva_cf_' ) ) === PIXVA_CODE_ATTEMPT_MAX, count( rows_with_prefix( 'pixva_cf_' ) ) . ' rows' );

// Hour boundary: the lock ends at the bucket edge; this is the documented burst.
reset_state();
listen_created();
set_client( '198.51.100.50' );
set_clock( 3600 * 100 + 3590 );
for ( $i = 0; $i < 5; $i++ ) {
	pixva_verify_order_access( 'PXV-ABC-123', '09000000000' );
}
$r = pixva_verify_order_access( 'PXV-ABC-123', '09000000000' );
check( 'lock holds until the bucket edge', is_wp_error( $r ) && 'locked' === $r->code );
set_clock( 3600 * 101 + 1 );
$accepted = 0;
for ( $i = 0; $i < 6; $i++ ) {
	$r = pixva_verify_order_access( 'PXV-ABC-123', '09000000000' );
	if ( is_wp_error( $r ) && 'not_found' === $r->code ) {
		$accepted++;
	}
}
check( 'LIMIT: after the hour edge the bucket resets, so 5 more failures are accepted (boundary burst; 10 in about 11 s)', $accepted === 5, "accepted after edge: $accepted" );

// ---------------------------------------------------------------------------
// E. Monitoring: counts across clients, alerts once, never blocks.
// ---------------------------------------------------------------------------
reset_state();
listen_created();
set_clock( 3600 * 100 + 600 );
$GLOBALS['alerts'] = 0;
add_action( 'pixva_suspicious_lookup', function () {
	$GLOBALS['alerts']++;
} );
for ( $c = 1; $c <= PIXVA_CODE_GLOBAL_ALERT + 5; $c++ ) {
	set_client( '192.0.2.' . $c ); // Distinct clients, one code.
	pixva_verify_order_access( 'PXV-ABC-123', '09000000000' );
}
check( 'suspicious-lookup action fires exactly once at the threshold', $GLOBALS['alerts'] === 1, 'fired ' . $GLOBALS['alerts'] );
set_client( '192.0.2.250' );
$r = pixva_verify_order_access( 'PXV-ABC-123', '09121234567' );
check( 'monitoring never blocks: a new client still gets the order', $r === 101, is_wp_error( $r ) ? $r->code : '' );
check( 'monitoring slots are capped per code and hour', count( rows_with_prefix( 'pixva_cg_' ) ) === PIXVA_CODE_GLOBAL_ALERT, count( rows_with_prefix( 'pixva_cg_' ) ) . ' rows' );

// ---------------------------------------------------------------------------
// F. Submission claims.
// ---------------------------------------------------------------------------
reset_state();
listen_created();
set_clock( 3600 * 200 );
$GLOBALS['orders'] = 0;
// Simulates pixva_dispatch_form for a handler that creates one order.
function run_form( $sid, $fail = false ) {
	$claim = pixva_claim_submission( $sid );
	if ( false === $claim ) {
		return 'invalid';
	}
	if ( is_array( $claim ) ) {
		return ! empty( $claim['pending'] ) ? 'pending' : 'replayed';
	}
	if ( $fail ) {
		pixva_release_submission( $sid );
		return 'error';
	}
	$GLOBALS['orders']++;
	pixva_finish_submission( $sid, array( 'code' => 'PXV-' . $GLOBALS['orders'] ) );
	return 'created';
}

$sid = sid( 1 );
check( 'malformed id is refused', pixva_claim_submission( 'short' ) === false );
check( 'first submission creates one order', run_form( $sid ) === 'created' && $GLOBALS['orders'] === 1 );
check( 'same id again replays the stored outcome, no second order', run_form( $sid ) === 'replayed' && $GLOBALS['orders'] === 1 );

// Simultaneous submissions: two claims both reach the database before either finishes.
reset_state();
listen_created();
set_clock( 3600 * 200 );
$GLOBALS['orders'] = 0;
$sid               = sid( 2 );
$c1                = pixva_claim_submission( $sid ); // Request 1 owns the claim.
$c2                = pixva_claim_submission( $sid ); // Request 2 (same id, concurrent).
check( 'of two simultaneous claims exactly one owns the id', true === $c1 && is_array( $c2 ) && ! empty( $c2['pending'] ) );
check( 'the second concurrent request gets pending (409 at the form), not a second order', run_form( $sid ) === 'pending' && $GLOBALS['orders'] === 0 );
pixva_finish_submission( $sid, array( 'code' => 'PXV-X' ) );
check( 'after the owner finishes, the id replays the outcome', run_form( $sid ) === 'replayed' );

// Error path: release lets the user retry the same id.
reset_state();
listen_created();
set_clock( 3600 * 200 );
$GLOBALS['orders'] = 0;
$sid               = sid( 3 );
check( 'handler error releases the claim', run_form( $sid, true ) === 'error' );
check( 'retry with the same id after an error creates the order', run_form( $sid ) === 'created' && $GLOBALS['orders'] === 1 );

// Crash path: a pending claim blocks until its TTL, then can be recovered.
reset_state();
listen_created();
set_clock( 3600 * 200 );
$GLOBALS['orders'] = 0;
$sid               = sid( 4 );
pixva_claim_submission( $sid ); // Owner "crashes" without finishing.
set_clock( 3600 * 200 + 60 );
check( 'pending claim blocks inside its TTL', run_form( $sid ) === 'pending' );
set_clock( 3600 * 200 + PIXVA_CLAIM_PENDING_TTL + 1 );
check( 'pending claim is recovered after its TTL', run_form( $sid ) === 'created' && $GLOBALS['orders'] === 1 );

// Lease lost: the owner runs past the TTL, another request takes over, the owner must not overwrite.
reset_state();
listen_created();
set_clock( 3600 * 200 );
$lost_action = 0;
add_action( 'pixva_submission_lease_lost', function () use ( &$lost_action ) {
	$lost_action++;
} );
// Each WordPress request is its own PHP process, so the claim registry is per request.
// Simulate that by saving and restoring the registry around each "process".
$sid   = sid( 5 );
$reg   = &pixva_claim_registry();
$claim = pixva_claim_submission( $sid ); // Slow owner A (process 1).
$reg_a = $reg;
$reg   = array();
set_clock( 3600 * 200 + PIXVA_CLAIM_PENDING_TTL + 1 );
$takeover = pixva_claim_submission( $sid ); // B (process 2) takes over the stale claim.
$reg_b    = $reg;
check( 'slow owner A got true and B took over the stale claim', true === $claim && true === $takeover );
$reg = $reg_b;
$fin_b = pixva_finish_submission( $sid, array( 'code' => 'B' ) );
check( 'the new owner can finish', true === $fin_b );
$reg = $reg_a; // Back in process 1: A finishes late.
$fin_a = pixva_finish_submission( $sid, array( 'code' => 'A' ) );
check( 'slow owner cannot overwrite the new owner (finish returns false)', false === $fin_a );
check( 'lease-lost action fired for the slow owner', 1 === $lost_action );
$row = json_decode( (string) pixva_option_value( 'pixva_sub_' . $sid ), true );
check( 'stored outcome is the new owner\'s', is_array( $row ) && 'B' === ( $row['r']['code'] ?? '' ) );

// Expired outcome: after DONE_TTL the id is free again (same behaviour as the old one-day transient).
reset_state();
listen_created();
set_clock( 3600 * 200 );
$sid = sid( 6 );
pixva_claim_submission( $sid );
pixva_finish_submission( $sid, array( 'code' => 'OLD' ) );
set_clock( 3600 * 200 + PIXVA_CLAIM_DONE_TTL + 1 );
check( 'an expired outcome lets the id be claimed again', pixva_claim_submission( $sid ) === true );

// Unreadable row is replaced atomically.
reset_state();
listen_created();
$sid = sid( 7 );
$GLOBALS['wpdb']->rows[ 'pixva_sub_' . $sid ] = 'garbage';
check( 'unreadable claim row is replaced', pixva_claim_submission( $sid ) === true );

// Many simultaneous claims in one simulated round: exactly one winner.
reset_state();
listen_created();
set_clock( 3600 * 200 );
$sid     = sid( 8 );
$winners = 0;
for ( $i = 0; $i < 25; $i++ ) {
	if ( true === pixva_claim_submission( $sid ) ) {
		$winners++;
	}
}
check( '25 simultaneous claims produce exactly one owner (simulated)', $winners === 1, "winners=$winners" );

// ---------------------------------------------------------------------------
// G. Housekeeping: pruning removes only expired private rows.
// ---------------------------------------------------------------------------
reset_state();
listen_created();
set_clock( 3600 * 300 );
$now = 3600 * 300;
$db  = &$GLOBALS['wpdb']->rows;
$db['pixva_cf_aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa_1_1'] = (string) ( $now - 3 * 3600 ); // expired
$db['pixva_cf_bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb_300_1'] = (string) $now;             // fresh
$db['pixva_cg_cccccccccccccccccccccccccccccccc_1_1'] = (string) ( $now - 3 * 3600 ); // expired
$db['pixva_cg_dddddddddddddddddddddddddddddddd_300_1'] = (string) $now;             // fresh
$db['pixva_olock_5'] = 'tok|' . ( $now - 7200 );  // abandoned (older than 1 h)
$db['pixva_olock_6'] = 'tok|' . $now;             // live
$db[ 'pixva_sub_' . sid( 9 ) ]  = wp_json_encode( array( 's' => 'done', 't' => $now - PIXVA_CLAIM_DONE_TTL - 10, 'r' => array() ) ); // expired
$db[ 'pixva_sub_' . sid( 10 ) ] = wp_json_encode( array( 's' => 'done', 't' => $now - 10, 'r' => array() ) );                         // fresh
$db[ 'pixva_sub_' . sid( 11 ) ] = wp_json_encode( array( 's' => 'pending', 't' => $now - PIXVA_CLAIM_PENDING_TTL - 1 ) );            // dead pending
$db[ 'pixva_sub_' . sid( 12 ) ] = wp_json_encode( array( 's' => 'pending', 't' => $now - 5 ) );                                        // live pending
$db['siteurl'] = 'https://example.test';                                                                                                 // unrelated
$db[ 'pixva_ord_' . sid( 50 ) ] = wp_json_encode( array( 's' => 'linked', 'o' => 1, 't' => $now - PIXVA_ORDER_LINK_TTL - 1 ) ); // expired link
$db[ 'pixva_ord_' . sid( 51 ) ] = wp_json_encode( array( 's' => 'linked', 'o' => 2, 't' => $now - 60 ) );                       // fresh link
$db[ 'pixva_ord_' . sid( 52 ) ] = wp_json_encode( array( 's' => 'creating', 'k' => 'x', 't' => $now - PIXVA_CLAIM_PENDING_TTL - 1 ) ); // dead attempt
$db[ 'pixva_ord_' . sid( 53 ) ] = 'not-json';                                                                                       // unreadable: kept
$db[ 'pixva_ord_' . sid( 54 ) ] = wp_json_encode( array( 's' => 'linked', 'o' => 3, 't' => $now - 3600 ) );                       // link 1 h old: kept (TTL is 30 days)

$removed = pixva_prune_expiring_rows();
check( 'prune removes exactly the 7 expired private rows (2 attempt/monitoring, 1 lock, 2 claims, 2 order reservations)', $removed === 7, "removed $removed" );
check( 'prune keeps fresh, unreadable and one-hour-old order reservations', isset( $db[ 'pixva_ord_' . sid( 51 ) ] ) && isset( $db[ 'pixva_ord_' . sid( 53 ) ] ) && isset( $db[ 'pixva_ord_' . sid( 54 ) ] ) );
check( 'prune keeps fresh attempt, monitoring, lock and claim rows', isset( $db['pixva_cf_bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb_300_1'] ) && isset( $db['pixva_cg_dddddddddddddddddddddddddddddddd_300_1'] ) && isset( $db['pixva_olock_6'] ) && isset( $db[ 'pixva_sub_' . sid( 10 ) ] ) && isset( $db[ 'pixva_sub_' . sid( 12 ) ] ) );
check( 'prune never touches unrelated options', ( $db['siteurl'] ?? '' ) === 'https://example.test' );
check( 'prune is idempotent', pixva_prune_expiring_rows() === 0 );

// ---------------------------------------------------------------------------
// H. Order placement: one order per submission id, recovery after a crash.
// ---------------------------------------------------------------------------
function order_data() {
	return array(
		'name'        => 'Ali',
		'phone'       => '09121234567',
		'brand'       => 'Samsung',
		'model'       => 'UE55',
		'problem'     => 'no picture',
		'description' => '',
		'mode'        => 'pickup',
		'address'     => '',
		'time'        => '',
		'photos'      => array(),
		'diagnosis'   => array(),
		'customer_id' => 0,
	);
}
function place( $s ) {
	return pixva_place_order_once( $s, order_data() );
}
function orders_for( $s ) {
	return array_values( array_filter( array_keys( $GLOBALS['post_rows'] ), function ( $id ) use ( $s ) {
		$row = $GLOBALS['post_rows'][ $id ];
		return 'pixva_orders' === $row['post_type'] && 0 === strpos( $row['post_name'], $s );
	} ) );
}
function reservation( $s ) {
	return json_decode( (string) ( $GLOBALS['wpdb']->rows[ 'pixva_ord_' . $s ] ?? '' ), true );
}
function stale_creating( $s, $token = 'dead-token' ) {
	$GLOBALS['wpdb']->rows[ 'pixva_ord_' . $s ] = wp_json_encode( array( 's' => 'creating', 'k' => $token, 't' => 3600 * 200 - 400 ) );
}

function listen_created() {
	add_action( 'pixva_order_created', function ( $id ) {
		$GLOBALS['created'][] = $id;
	} );
}
reset_state();
set_clock( 3600 * 200 );
listen_created();
$GLOBALS['created'] = array();

$s  = sid( 20 );
$id = place( $s );
check( 'H1 first placement creates one order named by the submission id', is_int( $id ) && 1 === count( orders_for( $s ) ) && $GLOBALS['post_rows'][ $id ]['post_name'] === $s );
check( 'H1 the order is announced exactly once', $GLOBALS['created'] === array( $id ) );
check( 'H1 the reservation is linked to that order', ( reservation( $s )['s'] ?? '' ) === 'linked' && (int) reservation( $s )['o'] === $id );

check( 'H2 same id again returns the same order, no second insert or announcement', place( $s ) === $id && 1 === count( orders_for( $s ) ) && count( $GLOBALS['created'] ) === 1 );

// Crash after the order was inserted but before it was linked (the reported bug).
reset_state();
listen_created();
set_clock( 3600 * 200 );
$GLOBALS['created'] = array();
$s       = sid( 21 );
$orphan  = pixva_insert_order( order_data() + array( 'submission_id' => $s ) );
stale_creating( $s );
check( 'H3 setup: an unlinked order exists and the reservation is stale', 1 === count( orders_for( $s ) ) && empty( $GLOBALS['created'] ) );
set_clock( 3600 * 200 + PIXVA_CLAIM_PENDING_TTL + 1 );
$id = place( $s );
check( 'H3 retry after the crash adopts that order: no second order', $id === $orphan && 1 === count( orders_for( $s ) ) );
check( 'H3 adoption announces the order once, after it is linked', $GLOBALS['created'] === array( $id ) );

// Crash after the reservation was taken, before any insert.
reset_state();
listen_created();
set_clock( 3600 * 200 );
$GLOBALS['created'] = array();
$s = sid( 22 );
stale_creating( $s );
set_clock( 3600 * 200 + PIXVA_CLAIM_PENDING_TTL + 1 );
$id = place( $s );
check( 'H4 crash before insert: retry creates exactly one order', is_int( $id ) && 1 === count( orders_for( $s ) ) );
check( 'H4 announced once', $GLOBALS['created'] === array( $id ) );

// Fatal error inside the link step, then recovery after the claim TTL.
reset_state();
listen_created();
set_clock( 3600 * 200 );
$GLOBALS['created'] = array();
$s     = sid( 23 );
$fired = false;
add_action( 'pixva_order_before_link', function () use ( &$fired ) {
	if ( ! $fired ) {
		$fired = true;
		throw new RuntimeException( 'simulated fatal' );
	}
} );
$threw = false;
try {
	place( $s );
} catch ( RuntimeException $e ) {
	$threw = true;
}
check( 'H5 setup: the simulated fatal happened after the insert', $threw && 1 === count( orders_for( $s ) ) && empty( $GLOBALS['created'] ) );
set_clock( 3600 * 200 + PIXVA_CLAIM_PENDING_TTL + 1 );
$id = place( $s );
check( 'H5 recovery returns the same order; no duplicate', 1 === count( orders_for( $s ) ) && $GLOBALS['created'] === array( $id ) );

// Stalled attempt A is past the TTL. B takes over after A inserted, adopts A's order and links it.
// A resumes: its link must fail, and it must not announce the order a second time.
reset_state();
listen_created();
set_clock( 3600 * 200 );
$GLOBALS['created'] = array();
$s       = sid( 24 );
$nested  = false;
$b_id    = null;
add_action( 'pixva_order_before_link', function ( $sid_arg ) use ( &$nested, &$b_id ) {
	if ( ! $nested ) {
		$nested = true;
		$b_id   = place( $sid_arg ); // B runs to completion inside A's pause.
	}
} );
$a_id = place( $s );
check( 'H6 A returns the order B linked (fenced: its own link failed)', $a_id === $b_id && 1 === count( orders_for( $s ) ) );
check( 'H6 the order is announced once in total', $GLOBALS['created'] === array( $b_id ) );

// Stalled attempt A inserted nothing yet; B creates and links. A then inserts, fails to link, and removes its own unlinked order.
reset_state();
listen_created();
set_clock( 3600 * 200 );
$GLOBALS['created'] = array();
$s      = sid( 25 );
$nested = false;
$b_id   = null;
add_action( 'pixva_order_before_insert', function ( $sid_arg ) use ( &$nested, &$b_id ) {
	if ( ! $nested ) {
		$nested = true;
		$b_id   = place( $sid_arg );
	}
} );
$a_id = place( $s );
check( 'H7 the stalled attempt returns the linked order and removes its own duplicate', $a_id === $b_id && 1 === count( orders_for( $s ) ) );
check( 'H7 the removed duplicate was never announced', $GLOBALS['created'] === array( $b_id ) && count( $GLOBALS['deleted_log'] ) === 1 );

// A failed insert: nothing is announced, and a retry creates exactly one order.
reset_state();
listen_created();
set_clock( 3600 * 200 );
$GLOBALS['created']  = array();
$GLOBALS['insert_fails'] = 1;
$s   = sid( 26 );
$err = place( $s );
check( 'H8 failed insert returns WP_Error and announces nothing', is_wp_error( $err ) && empty( $GLOBALS['created'] ) );
$id = place( $s );
check( 'H8 retry creates exactly one order, announced once', is_int( $id ) && 1 === count( orders_for( $s ) ) && $GLOBALS['created'] === array( $id ) );

// Two submission ids never share an order.
reset_state();
listen_created();
set_clock( 3600 * 200 );
$a = place( sid( 30 ) );
$b = place( sid( 31 ) );
check( 'H9 different submission ids create different orders', $a !== $b && 1 === count( orders_for( sid( 30 ) ) ) && 1 === count( orders_for( sid( 31 ) ) ) );

// A suffixed slug (sid-2) left by an older duplicate is still found and adopted.
reset_state();
listen_created();
set_clock( 3600 * 200 );
$GLOBALS['created'] = array();
$s = sid( 32 );
$GLOBALS['post_rows'][900] = array( 'post_type' => 'pixva_orders', 'post_name' => $s . '-2' );
$GLOBALS['posts'][900]     = 'PXV-OLD-1';
stale_creating( $s );
set_clock( 3600 * 200 + PIXVA_CLAIM_PENDING_TTL + 1 );
$id = place( $s );
check( 'H10 a suffixed slug is adopted, not duplicated', $id === 900 && 1 === count( orders_for( $s ) ) );
check( 'H10 adopting an order without a code gives it one (title matches)', ( get_post_meta( 900, '_pixva_order_code', true ) !== '' ) && $GLOBALS['posts'][900] === get_post_meta( 900, '_pixva_order_code', true ) );

// Success path with an existing duplicate: an unlinked order and a suffixed copy. The attempt
// adopts the lowest id, links it, and removes the copy before announcing.
reset_state();
set_clock( 3600 * 200 );
listen_created();
$GLOBALS['created'] = array();
$s = sid( 34 );
$GLOBALS['post_rows'][901] = array( 'post_type' => 'pixva_orders', 'post_name' => $s );
$GLOBALS['post_rows'][902] = array( 'post_type' => 'pixva_orders', 'post_name' => $s . '-2' );
$GLOBALS['posts'][901]     = 'PXV-DUP-1';
$GLOBALS['posts'][902]     = 'PXV-DUP-2';
update_post_meta( 901, '_pixva_order_code', 'PXV-DUP-1' );
update_post_meta( 902, '_pixva_order_code', 'PXV-DUP-2' );
stale_creating( $s );
set_clock( 3600 * 200 + PIXVA_CLAIM_PENDING_TTL + 1 );
$id = place( $s );
check( 'H10b adopts the lowest id and removes the duplicate before announcing', 901 === $id && array( 902 ) === $GLOBALS['deleted_log'] && 1 === count( orders_for( $s ) ) );
check( 'H10b the adopted order is announced once', $GLOBALS['created'] === array( 901 ) );

// A link to a deleted order is replaced by a fresh order, not returned.
reset_state();
listen_created();
set_clock( 3600 * 200 );
$GLOBALS['created'] = array();
$s = sid( 33 );
$GLOBALS['wpdb']->rows[ 'pixva_ord_' . $s ] = wp_json_encode( array( 's' => 'linked', 'o' => 999, 't' => 3600 * 200 ) );
$id = place( $s );
check( 'H11 a link to a missing order is replaced by a new order', is_int( $id ) && $id !== 999 && 1 === count( orders_for( $s ) ) && $GLOBALS['created'] === array( $id ) );

// Malformed ids never reach the database.
reset_state();
listen_created();
check( 'H12 malformed submission id is refused before any write', is_wp_error( place( 'not-a-valid-id' ) ) && empty( $GLOBALS['wpdb']->rows ) && count( $GLOBALS['post_rows'] ) === 1 );

// Claim + reservation, end to end: a fatal after insert, the claim stays pending, then recovery.
reset_state();
listen_created();
set_clock( 3600 * 200 );
$GLOBALS['created'] = array();
$s     = sid( 40 );
$fired = false;
add_action( 'pixva_order_before_link', function () use ( &$fired ) {
	if ( ! $fired ) {
		$fired = true;
		throw new RuntimeException( 'fatal after insert' );
	}
} );
$claim1 = pixva_claim_submission( $s );
$died   = false;
try {
	place( $s );
} catch ( RuntimeException $e ) {
	$died = true;
}
check( 'H13 setup: first request dies after the insert; claim is still pending', true === $claim1 && $died && pixva_claim_submission( $s ) === array( 'pending' => true ) );
set_clock( 3600 * 200 + PIXVA_CLAIM_PENDING_TTL + 1 );
$claim2 = pixva_claim_submission( $s );
$id     = place( $s );
pixva_finish_submission( $s, array( 'code' => get_post_meta( $id, '_pixva_order_code', true ) ) );
$replay = pixva_claim_submission( $s );
check( 'H13 after the TTL the retry recovers the same order and finishes the claim', true === $claim2 && 1 === count( orders_for( $s ) ) );
check( 'H13 a repeated submission replays the same code, no second order', is_array( $replay ) && ( $replay['code'] ?? '' ) === get_post_meta( $id, '_pixva_order_code', true ) && 1 === count( orders_for( $s ) ) );
check( 'H13 the order was announced exactly once', $GLOBALS['created'] === array( $id ) );

// ---------------------------------------------------------------------------
// I. Client address: forwarded headers are trusted only from a configured proxy.
// ---------------------------------------------------------------------------
function with_proxies( $list, $header = null ) {
	$GLOBALS['hooks']['filter']['pixva_trusted_proxies']      = array();
	$GLOBALS['hooks']['filter']['pixva_client_ip_header']     = array();
	if ( null !== $list ) {
		add_filter( 'pixva_trusted_proxies', function () use ( $list ) {
			return $list;
		} );
	}
	if ( null !== $header ) {
		add_filter( 'pixva_client_ip_header', function () use ( $header ) {
			return $header;
		} );
	}
}
function xff( $value ) {
	$_SERVER['HTTP_X_FORWARDED_FOR'] = $value;
}
function clear_xff() {
	unset( $_SERVER['HTTP_X_FORWARDED_FOR'] );
}

with_proxies( null );
set_client( '203.0.113.9' );
xff( '198.51.100.7' );
check( 'I1 no trusted proxy configured: the header is ignored', pixva_client_ip() === '203.0.113.9' );

with_proxies( array( '10.0.0.0/8' ) );
set_client( '10.1.1.1' );
xff( '198.51.100.7' );
check( 'I2 trusted proxy: the forwarded client is used', pixva_client_ip() === '198.51.100.7' );

with_proxies( array( '10.0.0.0/8' ) );
set_client( '203.0.113.9' );
xff( '1.2.3.4' );
check( 'I3 untrusted peer: its forwarded header is ignored (no spoofing)', pixva_client_ip() === '203.0.113.9' );

with_proxies( array( '10.0.0.0/8' ) );
set_client( '10.1.1.1' );
xff( '1.1.1.1, 198.51.100.7, 10.2.2.2' );
check( 'I4 chain walked from the right: the spoofed leftmost hop is not used', pixva_client_ip() === '198.51.100.7' );

with_proxies( array( '10.0.0.0/8' ) );
set_client( '10.1.1.1' );
xff( '198.51.100.7, garbage' );
check( 'I5 malformed hop next to the client falls back to the proxy address (no guessing)', pixva_client_ip() === '10.1.1.1' );

with_proxies( array( '10.0.0.0/8' ) );
set_client( '10.1.1.1' );
xff( '10.2.2.2' );
check( 'I6 every hop trusted: the proxy address is used', pixva_client_ip() === '10.1.1.1' );

with_proxies( array( '2001:db8::/32' ) );
set_client( '2001:db8::5' );
xff( '203.0.113.50' );
check( 'I7 IPv6 CIDR trust works', pixva_client_ip() === '203.0.113.50' );

with_proxies( array( '10.0.0.0/8' ), 'REMOTE_ADDR' );
set_client( '10.1.1.1' );
xff( '198.51.100.7' );
check( 'I8 a header name that is not HTTP_* is refused', pixva_client_ip() === '10.1.1.1' );

with_proxies( array( '10.0.0.0/8' ), 'HTTP_CF_CONNECTING_IP' );
set_client( '10.1.1.1' );
$_SERVER['HTTP_CF_CONNECTING_IP'] = '198.51.100.8';
check( 'I9 a single-value header (CF-Connecting-IP) is used only from a trusted proxy', pixva_client_ip() === '198.51.100.8' );
unset( $_SERVER['HTTP_CF_CONNECTING_IP'] );

with_proxies( array( '10.0.0.0/8' ) );
set_client( '10.1.1.1' );
xff( '198.51.100.7' );
$k1 = pixva_client_key();
xff( '198.51.100.8' );
$k2 = pixva_client_key();
check( 'I10 different forwarded clients get different keys', $k1 !== $k2 );

with_proxies( null );
clear_xff();
set_client( '203.0.113.1' );
xff( '198.51.100.7' );
$k_a = pixva_client_key();
xff( '198.51.100.8' );
$k_b = pixva_client_key();
check( 'I11 documented: without a proxy config, headers do not split the scope; visitors behind one proxy share REMOTE_ADDR', $k_a === $k_b );
clear_xff();

// ---------------------------------------------------------------------------
// J. The general lookup budget counts failures only.
// ---------------------------------------------------------------------------
reset_state();
listen_created();
set_clock( 3600 * 200 );
with_proxies( null );
set_client( '198.51.100.20' );
for ( $i = 0; $i < 25; $i++ ) {
	pixva_rate_count( 'lookup', 600 ); // Budget exhausted for this client.
}
$ok = pixva_verify_order_access( 'PXV-ABC-123', '09121234567' );
check( 'J1 a correct lookup is not refused by an exhausted budget', 101 === $ok );

reset_state();
listen_created();
set_clock( 3600 * 200 );
set_client( '198.51.100.21' );
for ( $i = 0; $i < 25; $i++ ) {
	pixva_rate_count( 'lookup', 600 );
}
$err = pixva_verify_order_access( 'PXV-ABC-123', '09129999999' );
check( 'J2 a wrong phone with an exhausted budget gets the rate error', is_wp_error( $err ) && 'rate' === $err->code );

reset_state();
listen_created();
set_clock( 3600 * 200 );
set_client( '198.51.100.22' );
for ( $i = 0; $i < 20; $i++ ) {
	pixva_verify_order_access( 'PXV-ABC-123', '09121234567' ); // Successes: not counted.
}
$err = pixva_verify_order_access( 'PXV-ABC-123', '09129999999' );
check( 'J3 successes do not use up the failure budget', is_wp_error( $err ) && 'not_found' === $err->code );

// ---------------------------------------------------------------------------
// K. Order-level lock helper used by the claim, notes and admin initialisation paths.
// ---------------------------------------------------------------------------
reset_state();
set_clock( 3600 * 200 );
$ran = pixva_with_order_lock( 101, static function () {
	return 'done';
} );
check( 'K1 the work runs under the lock and the lock is released afterwards', 'done' === $ran && null === pixva_option_value( 'pixva_olock_101' ) );

reset_state();
set_clock( 3600 * 200 );
$threw = false;
try {
	pixva_with_order_lock( 101, static function () {
		throw new RuntimeException( 'boom' );
	} );
} catch ( RuntimeException $e ) {
	$threw = true;
}
check( 'K2 the lock is released even when the work throws', $threw && null === pixva_option_value( 'pixva_olock_101' ) );

reset_state();
set_clock( 3600 * 200 );
$lost = array();
add_action( 'pixva_order_lock_lost', function ( $id ) use ( &$lost ) {
	$lost[] = $id;
} );
pixva_with_order_lock( 101, static function () {
	// Simulate a takeover: another owner replaced the lock while the work ran.
	$GLOBALS['wpdb']->rows['pixva_olock_101'] = 'someone-else|' . pixva_now();
	return true;
} );
check( 'K3 a lock lost during the work fires pixva_order_lock_lost and leaves the new owner in place', $lost === array( 101 ) && 'someone-else|' . pixva_now() === $GLOBALS['wpdb']->rows['pixva_olock_101'] );

// ---------------------------------------------------------------------------
echo "\n$passed passed, $failed failed\n";
exit( $failed ? 1 : 0 );

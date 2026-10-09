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
	return null;
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
$GLOBALS['wpdb']->rows['pixva_olock_101'] = 'dead-owner|' . ( time() - 120 );
$owner = pixva_order_lock_acquire( 101 );
check( 'stale lock is taken over', is_string( $owner ) && pixva_option_value( 'pixva_olock_101' ) === $owner );
pixva_order_lock_release( 101, $owner );

reset_state();
$GLOBALS['wpdb']->rows['pixva_olock_101'] = 'live-owner|' . time();
check( 'fresh lock is not stale', is_wp_error( pixva_order_lock_acquire( 101 ) ) );
$GLOBALS['wpdb']->rows = array();

// Fencing: an owner whose lock was taken over must not write.
reset_state();
$owner_a = pixva_order_lock_acquire( 101 );
$GLOBALS['wpdb']->rows['pixva_olock_101'] = 'takeover-owner|' . time(); // another process took the lock
$res = pixva_set_order_status_locked( 101, 'received', '', $owner_a );
check( 'fenced owner gets busy and writes nothing', is_wp_error( $res ) && 'busy' === $res->code && get_post_meta( 101, '_pixva_order_status', true ) === 'new' );
$takeover_value = pixva_option_value( 'pixva_olock_101' );
check( 'fenced owner does not remove the new owner\'s lock', pixva_order_lock_release( 101, $owner_a ) === false && pixva_option_value( 'pixva_olock_101' ) === $takeover_value );

// Lock released on error and on success; a listener runs after release (no deadlock).
reset_state();
$res = pixva_set_order_status( 101, 'no_such_status', '' );
check( 'invalid status returns WP_Error through the wrapper', is_wp_error( $res ) );
check( 'lock released after an error return', pixva_option_value( 'pixva_olock_101' ) === null );

reset_state();
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
set_clock( 3600 * 100 + 600 );
set_client( '198.51.100.30' );
for ( $i = 0; $i < 5; $i++ ) {
	pixva_verify_order_access( 'PXV-ZZZ-999', '09000000000' );
}
$r = pixva_verify_order_access( 'PXV-ZZZ-999', '09000000000' );
check( 'unknown code locks the same way as a real one', is_wp_error( $r ) && 'locked' === $r->code );

// Slots are bounded for a scope.
reset_state();
set_clock( 3600 * 100 + 600 );
set_client( '198.51.100.40' );
for ( $i = 0; $i < 20; $i++ ) {
	pixva_verify_order_access( 'PXV-ABC-123', '09000000000' );
}
check( 'attempt rows are capped at PIXVA_CODE_ATTEMPT_MAX per scope and hour', count( rows_with_prefix( 'pixva_cf_' ) ) === PIXVA_CODE_ATTEMPT_MAX, count( rows_with_prefix( 'pixva_cf_' ) ) . ' rows' );

// Hour boundary: the lock ends at the bucket edge; this is the documented burst.
reset_state();
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
set_clock( 3600 * 200 );
$GLOBALS['orders'] = 0;
$sid               = sid( 3 );
check( 'handler error releases the claim', run_form( $sid, true ) === 'error' );
check( 'retry with the same id after an error creates the order', run_form( $sid ) === 'created' && $GLOBALS['orders'] === 1 );

// Crash path: a pending claim blocks until its TTL, then can be recovered.
reset_state();
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
set_clock( 3600 * 200 );
$sid = sid( 6 );
pixva_claim_submission( $sid );
pixva_finish_submission( $sid, array( 'code' => 'OLD' ) );
set_clock( 3600 * 200 + PIXVA_CLAIM_DONE_TTL + 1 );
check( 'an expired outcome lets the id be claimed again', pixva_claim_submission( $sid ) === true );

// Unreadable row is replaced atomically.
reset_state();
$sid = sid( 7 );
$GLOBALS['wpdb']->rows[ 'pixva_sub_' . $sid ] = 'garbage';
check( 'unreadable claim row is replaced', pixva_claim_submission( $sid ) === true );

// Many simultaneous claims in one simulated round: exactly one winner.
reset_state();
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

$removed = pixva_prune_expiring_rows();
check( 'prune removes exactly the 5 expired private rows (2 attempt/monitoring, 1 lock, 2 claims)', $removed === 5, "removed $removed" );
check( 'prune keeps fresh attempt, monitoring, lock and claim rows', isset( $db['pixva_cf_bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb_300_1'] ) && isset( $db['pixva_cg_dddddddddddddddddddddddddddddddd_300_1'] ) && isset( $db['pixva_olock_6'] ) && isset( $db[ 'pixva_sub_' . sid( 10 ) ] ) && isset( $db[ 'pixva_sub_' . sid( 12 ) ] ) );
check( 'prune never touches unrelated options', ( $db['siteurl'] ?? '' ) === 'https://example.test' );
check( 'prune is idempotent', pixva_prune_expiring_rows() === 0 );

// ---------------------------------------------------------------------------
echo "\n$passed passed, $failed failed\n";
exit( $failed ? 1 : 0 );

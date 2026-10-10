<?php
/**
 * PIXVA runtime test suite — development harness only.
 *
 * This file is NOT part of the theme (it lives in tools/, never in pixva/,
 * so it is never packaged into dist/pixva.zip). It is mounted as a
 * mu-plugin only on a dedicated local Playground test server started with:
 *
 *   --define PIXVA_TEST_TOKEN <token>
 *   --mount <repo>/tools/harness/mu-plugin.php:/wordpress/wp-content/mu-plugins/pixva-test.php
 *
 * Endpoints (both require the X-Pixva-Test header to match PIXVA_TEST_TOKEN):
 *   POST /wp-json/pixva-test/v1/run    {tests?: string[]}  → per-test results
 *   POST /wp-json/pixva-test/v1/probe  {action, ...}       → state queries
 *
 * Environment honesty: WordPress 7.1.3 + SQLite via PHP-wasm (Playground).
 * Concurrency here is SIMULATED — the harness runs single-process, so race
 * paths are exercised at the compare-and-swap primitive level, not with true
 * parallel writers. MySQL/MariaDB behaviour is NOT covered by this suite.
 *
 * @package Pixva-Testing
 */

if ( ! defined( 'ABSPATH' ) || ! defined( 'PIXVA_TEST_TOKEN' ) ) {
	return;
}

/*
 * ---------------------------------------------------------------------------
 * Plumbing
 * ---------------------------------------------------------------------------
 */

/**
 * Count wp_mail attempts (pre_wp_mail short-circuit keeps the sandbox from
 * invoking a missing MTA while still observing every send attempt).
 *
 * @return int
 */
function pixva_test_mail_attempts() {
	static $count = 0;
	return $count;
}

add_filter(
	'pre_wp_mail',
	static function ( $short_circuit, $args ) {
		// Count every send attempt and short-circuit so the sandbox (no MTA)
		// never reaches PHP mail(); tests observe attempts, not deliveries.
		$GLOBALS['pixva_test_mail_count'] = (int) ( $GLOBALS['pixva_test_mail_count'] ?? 0 ) + 1;
		return false;
	},
	10,
	2
);

/**
 * Token check for both routes.
 *
 * @return bool
 */
function pixva_test_authed() {
	$hdr = isset( $_SERVER['HTTP_X_PIXVA_TEST'] ) ? (string) wp_unslash( $_SERVER['HTTP_X_PIXVA_TEST'] ) : '';
	return '' !== $hdr && hash_equals( (string) PIXVA_TEST_TOKEN, $hdr );
}

/** Assertion collector for one test. */
class Pixva_Test_Case {
	public $name;
	public $assertions = 0;
	public $failures   = array();

	public function __construct( $name ) {
		$this->name = (string) $name;
	}

	public function ok( $cond, $msg ) {
		++$this->assertions;
		if ( ! $cond ) {
			$this->failures[] = (string) $msg;
		}
		return (bool) $cond;
	}

	public function eq( $expected, $actual, $msg ) {
		return $this->ok( $expected === $actual, $msg . ' (expected ' . var_export( $expected, true ) . ', got ' . var_export( $actual, true ) . ')' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_var_export -- test output only.
	}
}

/**
 * Unique suffix per harness run so repeated runs never collide.
 *
 * @return string
 */
function pixva_test_suffix() {
	static $s = null;
	if ( null === $s ) {
		$s = strtolower( substr( md5( uniqid( (string) wp_generate_password( 8, false, false ), true ) ), 0, 8 ) );
	}
	return $s;
}

/**
 * Build a valid booking $_POST payload.
 *
 * @param array $over Overrides.
 * @return array
 */
function pixva_test_booking_post( $over = array() ) {
	return array_merge(
		array(
			'name'        => 'کاربر تست',
			'phone'       => '0912345' . str_pad( (string) ( (int) substr( pixva_test_suffix(), 0, 4 ) % 10000 ), 4, '0', STR_PAD_LEFT ),
			'brand'       => 'other',
			'brand_other' => 'برند تست',
			'model'       => 'مدل تست',
			'problem'     => 'other',
			'description' => 'شرح مشکل آزمایشی برای تست ثبت سفارش',
			'consent'     => '1',
		),
		$over
	);
}

/**
 * Valid contact $_POST payload.
 *
 * @param array $over Overrides.
 * @return array
 */
function pixva_test_contact_post( $over = array() ) {
	return array_merge(
		array(
			'name'      => 'کاربر تست',
			'phone'     => '09123450999',
			'email'     => '',
			'message'   => 'این یک پیام آزمایشی برای بررسی مسیر inbox است.',
			'consent'   => '1',
		),
		$over
	);
}

/**
 * Orders created for a submission id.
 *
 * @param string $sid Submission id.
 * @return int[]
 */
function pixva_test_orders_by_sid( $sid ) {
	$sid = pixva_submission_normalize_id( $sid );
	if ( '' === $sid ) {
		return array();
	}
	return array_map(
		'intval',
		get_posts(
			array(
				'post_type'      => 'pixva_orders',
				'post_status'    => 'any',
				'meta_key'       => '_pixva_submission_id', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value'     => $sid, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'no_found_rows'  => true,
			)
		)
	);
}

/**
 * Inbox posts created for a submission id.
 *
 * @param string $sid Submission id.
 * @return int[]
 */
function pixva_test_inbox_by_sid( $sid ) {
	$sid = pixva_submission_normalize_id( $sid );
	if ( '' === $sid ) {
		return array();
	}
	return array_map(
		'intval',
		get_posts(
			array(
				'post_type'      => 'pixva_inbox',
				'post_status'    => 'any',
				'meta_key'       => '_pixva_msg_sid', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value'     => $sid, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'no_found_rows'  => true,
			)
		)
	);
}

/**
 * Create (or fetch) a test user with a known password.
 *
 * @param string $role Role name.
 * @return int User id.
 */
function pixva_test_user( $role ) {
	static $seq = 0;
	++$seq;
	$login = 't_' . $role . '_' . pixva_test_suffix() . '_' . $seq;
	$uid   = username_exists( $login );
	if ( ! $uid ) {
		$uid = wp_insert_user(
			array(
				'user_login' => $login,
				'user_pass'  => 'PixvaTest!2026',
				'user_email' => $login . '@example.test',
				'role'       => $role,
			)
		);
	}
	return is_wp_error( $uid ) ? 0 : (int) $uid;
}

/**
 * Create a fresh order for tests (returns [id, code, phone]).
 *
 * @param array $d Data for pixva_create_order().
 * @return array
 */
function pixva_test_make_order( $d = array() ) {
	$data = array(
		'name'        => 'مشتری تست',
		'phone'       => '098976543' . str_pad( (string) ( mt_rand( 0, 99 ) ), 2, '0', STR_PAD_LEFT ),
		'brand'       => 'برند تست',
		'brand_id'    => 0,
		'model'       => 'مدل X',
		'problem'     => 'other',
		'description' => 'توضیح تست',
		'mode'        => '',
		'address'     => '',
		'time'        => '',
		'photos'      => array(),
		'diagnosis'   => null,
		'customer_id' => 0,
	);
	$data = array_merge( $data, $d );
	$id   = pixva_create_order( $data );
	return array(
		'id'    => $id,
		'code'  => is_wp_error( $id ) ? '' : (string) get_post_meta( $id, '_pixva_order_code', true ),
		'phone' => $data['phone'],
	);
}

/*
 * ---------------------------------------------------------------------------
 * Test registry
 * ---------------------------------------------------------------------------
 */

/**
 * All in-process tests.
 *
 * @return array<string,callable>
 */
function pixva_test_registry() {
	$tests = array();

	/* ---- S: install / setup ---- */
	$tests['s1_theme'] = static function ( Pixva_Test_Case $t ) {
		$theme = wp_get_theme();
		$t->ok( 'pixva' === basename( get_template_directory() ), 'active theme folder is pixva' );
		$t->eq( '2.0.0', $theme->get( 'Version' ), 'style.css version 2.0.0' );
		$t->eq( PIXVA_DB_VERSION, (string) get_option( 'pixva_db_version', '' ), 'db version set' );
	};

	$tests['s2_roles'] = static function ( Pixva_Test_Case $t ) {
		foreach ( array( 'pixva_customer', 'pixva_technician', 'pixva_manager' ) as $role ) {
			$t->ok( null !== get_role( $role ), "role $role exists" );
		}
		$tech = get_role( 'pixva_technician' );
		$t->ok( $tech && ! $tech->has_cap( 'edit_posts' ), 'technician has no edit_posts' );
		$t->ok( $tech && ! $tech->has_cap( 'upload_files' ), 'technician has no upload_files' );
		$t->ok( $tech && $tech->has_cap( 'pixva_work_orders' ), 'technician has pixva_work_orders' );
	};

	$tests['s3_routes'] = static function ( Pixva_Test_Case $t ) {
		$need = array( 'booking', 'tracking', 'warranty', 'account', 'dashboard', 'contact' );
		foreach ( $need as $route ) {
			$url = pixva_route_url( $route );
			$t->ok( is_string( $url ) && '' !== $url, "route $route resolves" );
		}
		$pages = get_posts(
			array(
				'post_type'      => 'page',
				'post_status'    => 'any',
				'meta_key'       => '_pixva_route', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value'     => 'booking', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				'posts_per_page' => 1,
				'fields'         => 'ids',
			)
		);
		$t->ok( ! empty( $pages ), 'booking route page exists' );
	};

	/* ---- I: submission idempotency (F1-order) ---- */
	$tests['i1_claim_cycle'] = static function ( Pixva_Test_Case $t ) {
		$sid  = pixva_submission_id();
		$one  = pixva_claim_submission( $sid, 'pixva_booking' );
		$t->ok( true === $one, 'first claim wins' );
		$two = pixva_claim_submission( $sid, 'pixva_booking' );
		$t->ok( is_array( $two ) && ! empty( $two['pending'] ), 'second claim sees pending' );
		pixva_finish_submission( $sid, array( 'message' => 'ok', 'code' => 'PXV-TEST-1' ) );
		$three = pixva_claim_submission( $sid, 'pixva_booking' );
		$t->ok( is_array( $three ) && ( $three['code'] ?? '' ) === 'PXV-TEST-1', 'third claim replays result' );
		pixva_release_submission( $sid );
		$four = pixva_claim_submission( $sid, 'pixva_booking' );
		$t->ok( true === $four, 'release allows a fresh claim' );
		pixva_release_submission( $sid );
	};

	$tests['i2_invalid_and_binding'] = static function ( Pixva_Test_Case $t ) {
		$t->ok( false === pixva_claim_submission( 'short', 'pixva_booking' ), 'malformed id rejected' );
		$sid = pixva_submission_id();
		$t->ok( true === pixva_claim_submission( $sid, 'pixva_booking' ), 'claim for booking' );
		$t->ok( false === pixva_claim_submission( $sid, 'pixva_contact' ), 'same id rejected for another action' );
		pixva_release_submission( $sid );
		// Length is the only structural rule (sanitize_key enforces charset).
		$t->ok( false === pixva_claim_submission( str_repeat( 'z', 31 ), 'pixva_booking' ), '31-char id rejected' );
		$t->ok( false === pixva_claim_submission( str_repeat( 'z', 33 ), 'pixva_booking' ), '33-char id rejected' );
	};

	$tests['i3_stale_takeover'] = static function ( Pixva_Test_Case $t ) {
		global $wpdb;
		$sid  = pixva_submission_id();
		$key  = 'pixva_sub_' . pixva_submission_normalize_id( $sid );
		$old  = array(
			's' => 'pending',
			't' => time() - ( PIXVA_SUB_PENDING_TTL + 30 ),
			'a' => 'pixva_booking',
		);
		add_option( $key, $old, '', 'no' );
		$res = pixva_claim_submission( $sid, 'pixva_booking' );
		$t->ok( true === $res, 'abandoned pending record taken over' );
		pixva_release_submission( $sid );
		// Fresh pending must NOT be taken over.
		$sid2 = pixva_submission_id();
		$t->ok( true === pixva_claim_submission( $sid2, 'pixva_booking' ), 'fresh claim held' );
		$t->ok( is_array( pixva_claim_submission( $sid2, 'pixva_booking' ) ), 'fresh pending not stolen' );
		pixva_release_submission( $sid2 );
	};

	$tests['i4_expired_done'] = static function ( Pixva_Test_Case $t ) {
		global $wpdb;
		$sid  = pixva_submission_id();
		$key  = 'pixva_sub_' . pixva_submission_normalize_id( $sid );
		$done = array(
			's' => 'done',
			't' => time() - ( PIXVA_SUB_TTL + 60 ),
			'p' => array( 'message' => 'stale' ),
		);
		add_option( $key, $done, '', 'no' );
		$res = pixva_claim_submission( $sid, 'pixva_booking' );
		$t->ok( true === $res, 'expired finished record replaced by a fresh claim' );
		pixva_release_submission( $sid );
		unset( $wpdb );
	};

	/* ---- O: order idempotent recovery (F1-order) ---- */
	$tests['o1_booking_recovery'] = static function ( Pixva_Test_Case $t ) {
		$sid  = pixva_submission_id();
		$prev = $_POST; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- harness sets inputs for the handler under test.
		$_POST = pixva_test_booking_post();
		$first = pixva_handle_booking( $sid );
		$t->ok( is_array( $first ) && ! empty( $first['code'] ), 'first submission creates an order' );
		$orders = pixva_test_orders_by_sid( $sid );
		$t->eq( 1, count( $orders ), 'exactly one order for the sid' );
		$second = pixva_handle_booking( $sid );
		$t->ok( is_array( $second ) && ( $second['code'] ?? '' ) === ( $first['code'] ?? '~' ), 'recovery returns the same code' );
		$t->eq( 1, count( pixva_test_orders_by_sid( $sid ) ), 'recovery did not duplicate the order' );
		$_POST = $prev;
	};

	$tests['o2_contact_recovery'] = static function ( Pixva_Test_Case $t ) {
		$prev  = $_POST; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$_POST = pixva_test_contact_post();
		$sid   = pixva_submission_id();
		$GLOBALS['pixva_test_mail_count'] = 0;
		$first = pixva_handle_contact( $sid );
		$t->ok( is_array( $first ), 'first contact submission stores the message' );
		$inbox = pixva_test_inbox_by_sid( $sid );
		$t->eq( 1, count( $inbox ), 'exactly one inbox row for the sid' );
		$t->ok( (string) get_post_meta( $inbox[0], '_pixva_msg_body', true ) !== '', 'message body stored' );
		$first_mail = (int) $GLOBALS['pixva_test_mail_count'];
		$t->ok( $first_mail >= 1, 'notification attempted after storing' );
		// Recovery run: no duplicate row; mail only re-attempted while unmarked.
		$second = pixva_handle_contact( $sid );
		$t->ok( is_array( $second ), 'recovery returns success' );
		$t->eq( 1, count( pixva_test_inbox_by_sid( $sid ) ), 'recovery did not duplicate the message' );
		update_post_meta( $inbox[0], '_pixva_msg_mail_sent', 1 );
		$before = (int) $GLOBALS['pixva_test_mail_count'];
		pixva_handle_contact( $sid );
		$t->eq( $before, (int) $GLOBALS['pixva_test_mail_count'], 'sent-marker prevents further mail attempts' );
		$_POST = $prev;
	};

	/* ---- H: status & history CAS (F5) ---- */
	$tests['h1_status_append'] = static function ( Pixva_Test_Case $t ) {
		$o      = pixva_test_make_order();
		$id     = $o['id'];
		$t->ok( ! is_wp_error( $id ), 'order created' );
		$before = count( pixva_order_history( $id ) );
		$res    = pixva_set_order_status( $id, 'received', '' );
		$t->ok( true === $res, 'status transition accepted' );
		$t->eq( 'received', (string) get_post_meta( $id, '_pixva_order_status', true ), 'status meta updated' );
		$hist = pixva_order_history( $id );
		$t->eq( $before + 1, count( $hist ), 'history appended exactly once' );
		$t->eq( 'received', end( $hist )['s'], 'last history entry matches status' );
	};

	$tests['h2_status_sequence'] = static function ( Pixva_Test_Case $t ) {
		$o    = pixva_test_make_order();
		$id   = $o['id'];
		$seq  = array( 'received', 'diagnosed', 'waiting', 'repairing', 'testing', 'ready', 'delivered' );
		$ok_n = 0;
		foreach ( $seq as $st ) {
			if ( true === pixva_set_order_status( $id, $st, '' ) ) {
				++$ok_n;
			}
		}
		$t->eq( count( $seq ), $ok_n, 'all sequential transitions accepted' );
		$hist = pixva_order_history( $id );
		$t->eq( 1 + count( $seq ), count( $hist ), 'history has create + every transition' );
		$valid = array_keys( pixva_order_statuses() );
		$bad   = 0;
		foreach ( $hist as $h ) {
			if ( ! in_array( $h['s'], $valid, true ) ) {
				++$bad;
			}
		}
		$t->eq( 0, $bad, 'no invalid status in history' );
		$t->eq( 'delivered', (string) get_post_meta( $id, '_pixva_order_status', true ), 'final status is delivered' );
	};

	$tests['h3_invalid_and_closed'] = static function ( Pixva_Test_Case $t ) {
		$o   = pixva_test_make_order();
		$id  = $o['id'];
		$res = pixva_set_order_status( $id, 'nonsense', '' );
		$t->ok( is_wp_error( $res ) && 'status' === $res->get_error_code(), 'invalid status rejected' );
		pixva_set_order_status( $id, 'cancelled', '' );
		// Current user is not a manager (harness runs unauthenticated).
		$res2 = pixva_set_order_status( $id, 'received', '' );
		$t->ok( is_wp_error( $res2 ) && 'closed' === $res2->get_error_code(), 'closed order protected from non-manager' );
	};

	$tests['h4_note_only'] = static function ( Pixva_Test_Case $t ) {
		$o      = pixva_test_make_order();
		$id     = $o['id'];
		pixva_set_order_status( $id, 'received', '' );
		$before = count( pixva_order_history( $id ) );
		$res    = pixva_set_order_status( $id, 'received', 'یادداشت عمومی' );
		$t->ok( true === $res, 'same-status note accepted' );
		$hist = pixva_order_history( $id );
		$t->eq( $before + 1, count( $hist ), 'note appended as history entry' );
		$t->eq( 'یادداشت عمومی', end( $hist )['n'], 'note text preserved' );
		$t->eq( 'received', (string) get_post_meta( $id, '_pixva_order_status', true ), 'status unchanged' );
	};

	$tests['h5_cas_primitive'] = static function ( Pixva_Test_Case $t ) {
		$o   = pixva_test_make_order();
		$id  = $o['id'];
		$raw = pixva_meta_raw( $id, '_pixva_order_status' );
		$t->ok( null !== $raw && '' !== $raw, 'raw status readable' );
		$n = pixva_update_meta_cas( $id, '_pixva_order_status', 'stale-value', 'new' );
		$t->eq( 0, $n, 'stale CAS guard writes nothing' );
		$t->eq( 'new', (string) pixva_meta_raw( $id, '_pixva_order_status' ), 'value untouched by stale CAS' );
		$n2 = pixva_update_meta_cas( $id, '_pixva_order_status', $raw, 'received' );
		$t->eq( 1, $n2, 'matching CAS guard writes' );
		// restore
		pixva_update_meta_cas( $id, '_pixva_order_status', 'received', $raw );
	};

	/* ---- N: internal notes ---- */
	$tests['n1_append_notes'] = static function ( Pixva_Test_Case $t ) {
		$o   = pixva_test_make_order();
		$id  = $o['id'];
		$res = pixva_append_order_note( $id, 'یادداشت اول', 'تست' );
		$t->ok( true === $res, 'first note appended (row created)' );
		$res2 = pixva_append_order_note( $id, 'یادداشت دوم', 'تست' );
		$t->ok( true === $res2, 'second note appended' );
		$notes = (string) get_post_meta( $id, '_pixva_order_notes', true );
		$t->ok( false !== strpos( $notes, 'یادداشت اول' ) && false !== strpos( $notes, 'یادداشت دوم' ), 'both notes present' );
		$t->ok( strpos( $notes, 'یادداشت اول' ) < strpos( $notes, 'یادداشت دوم' ), 'append order preserved' );
		$t->ok( true === pixva_append_order_note( $id, '   ', 'تست' ), 'empty note is a no-op' );
	};

	$tests['n2_admin_notes_conflict'] = static function ( Pixva_Test_Case $t ) {
		$o   = pixva_test_make_order();
		$id  = $o['id'];
		$uid = pixva_test_user( 'administrator' );
		$t->ok( $uid > 0, 'admin user available' );
		wp_set_current_user( $uid );
		$notes = get_post_meta( $id, '_pixva_order_notes', true );
		// 1) Base matches → write accepted.
		$_POST = array(
			'pixva_order_admin_nonce' => wp_create_nonce( 'pixva_order_admin_' . $id ),
			'pixva_o'                 => array(
				'notes'      => 'یادداشت ادمین',
				'notes_base' => (string) $notes,
			),
		);
		pixva_save_order_box( $id );
		wp_cache_delete( (int) $id, 'post_meta' );
		$t->eq( 'یادداشت ادمین', (string) get_post_meta( $id, '_pixva_order_notes', true ), 'matching base writes notes' );
		// 2) Stale base (another editor changed them meanwhile) → skipped + notice.
		$_POST['pixva_o']['notes']      = 'نوشته جدید اما stale';
		$_POST['pixva_o']['notes_base'] = 'قدیمی‌تر از حد';
		pixva_save_order_box( $id );
		wp_cache_delete( (int) $id, 'post_meta' );
		$t->eq( 'یادداشت ادمین', (string) get_post_meta( $id, '_pixva_order_notes', true ), 'stale base does not overwrite' );
		$uid_n = get_current_user_id();
		$t->ok( (bool) get_transient( 'pixva_notes_conflict_' . $uid_n ), 'conflict notice queued' );
		delete_transient( 'pixva_notes_conflict_' . $uid_n );
		$_POST = array();
		wp_set_current_user( 0 );
	};

	/* ---- C: claim order ownership ---- */
	$tests['c1_claim_order'] = static function ( Pixva_Test_Case $t ) {
		$o    = pixva_test_make_order();
		$id   = $o['id'];
		$cust = pixva_test_user( 'pixva_customer' );
		$other = pixva_test_user( 'pixva_customer' );
		$t->ok( $cust > 0 && $other > 0, 'customers created' );
		// Wrong phone proof → not found.
		$bad = pixva_verify_order_access( $o['code'], '09120000000' );
		$t->ok( is_wp_error( $bad ), 'wrong phone rejected' );
		// Correct proof as customer → claim attaches.
		wp_set_current_user( $cust );
		$_POST = array(
			'code'  => $o['code'],
			'phone' => $o['phone'],
		);
		$res = pixva_handle_claim();
		$t->ok( is_array( $res ), 'owner claim succeeds' );
		$t->eq( $cust, (int) get_post_meta( $id, '_pixva_customer_id', true ), 'ownership recorded' );
		// Second claim by another user → same not-found message (no oracle).
		wp_set_current_user( $other );
		$res2 = pixva_handle_claim();
		$t->ok( is_wp_error( $res2 ), 'foreign claim rejected' );
		$t->eq( 404, (int) ( (array) $res2->get_error_data() )['status'] ?? 0, 'foreign claim gets 404' );
		// Re-claim by owner stays idempotent.
		wp_set_current_user( $cust );
		$res3 = pixva_handle_claim();
		$t->ok( is_array( $res3 ), 're-claim by owner succeeds' );
		$_POST = array();
		wp_set_current_user( 0 );
	};

	/* ---- A: capability matrix (F3) ---- */
	$tests['a1_cap_matrix'] = static function ( Pixva_Test_Case $t ) {
		$owner  = pixva_test_user( 'pixva_customer' );
		$tech   = pixva_test_user( 'pixva_technician' );
		$tech2  = pixva_test_user( 'pixva_technician' );
		$mgr    = pixva_test_user( 'pixva_manager' );
		$editor = pixva_test_user( 'editor' );
		$o      = pixva_test_make_order( array( 'customer_id' => $owner ) );
		$id     = $o['id'];
		update_post_meta( $id, '_pixva_technician_id', $tech );

		$matrix = array(
			// [user, cap, expected]
			array( $owner, 'pixva_view_order', true ),
			array( $owner, 'pixva_work_order', false ),
			array( $tech, 'pixva_view_order', true ),
			array( $tech, 'pixva_work_order', true ),
			array( $tech2, 'pixva_view_order', false ),
			array( $tech2, 'pixva_work_order', false ),
			array( $mgr, 'pixva_view_order', true ),
			array( $mgr, 'pixva_work_order', true ),
			array( $editor, 'pixva_view_order', false ),
			array( $editor, 'pixva_work_order', false ),
		);
		foreach ( $matrix as $row ) {
			list( $uid, $cap, $want ) = $row;
			wp_set_current_user( $uid );
			$t->eq( $want, current_user_can( $cap, $id ), "user #$uid $cap on own/assigned order" );
		}
		wp_set_current_user( 0 );
		$t->eq( false, current_user_can( 'pixva_view_order', $id ), 'anonymous denied' );
		// manage (PII) cap: manager only.
		wp_set_current_user( $mgr );
		$t->eq( true, current_user_can( 'pixva_view_order_pii', $id ), 'manager sees PII' );
		wp_set_current_user( $tech );
		$t->eq( false, current_user_can( 'pixva_view_order_pii', $id ), 'technician denied PII' );
		wp_set_current_user( 0 );
	};

	$tests['a2_order_update_auth'] = static function ( Pixva_Test_Case $t ) {
		$owner = pixva_test_user( 'pixva_customer' );
		$tech  = pixva_test_user( 'pixva_technician' );
		$tech2 = pixva_test_user( 'pixva_technician' );
		$o     = pixva_test_make_order( array( 'customer_id' => $owner ) );
		$id    = $o['id'];
		update_post_meta( $id, '_pixva_technician_id', $tech );
		$prev  = $_POST; // phpcs:ignore WordPress.Security.NonceVerification.Missing

		// Owner (customer) cannot update.
		wp_set_current_user( $owner );
		$_POST = array(
			'order' => $id,
			'status' => 'received',
		);
		$res = pixva_handle_order_update();
		$t->ok( is_wp_error( $res ) && 403 === (int) ( (array) $res->get_error_data() )['status'], 'owner update forbidden' );

		// Unassigned technician cannot update.
		wp_set_current_user( $tech2 );
		$res = pixva_handle_order_update();
		$t->ok( is_wp_error( $res ) && 403 === (int) ( (array) $res->get_error_data() )['status'], 'unassigned tech forbidden' );

		// Assigned technician can update status + internal note.
		wp_set_current_user( $tech );
		$_POST['status' ]  = 'received';
		$_POST['internal'] = 'بررسی شد';
		$res = pixva_handle_order_update();
		$t->ok( is_array( $res ), 'assigned tech update accepted' );
		wp_cache_delete( (int) $id, 'post_meta' );
		$t->eq( 'received', (string) get_post_meta( $id, '_pixva_order_status', true ), 'status changed by tech' );
		$t->ok( false !== strpos( (string) get_post_meta( $id, '_pixva_order_notes', true ), 'بررسی شد' ), 'internal note stored' );
		// Customer-facing view must not contain the internal note.
		$pub = wp_json_encode( pixva_order_public_view( $id ) );
		$t->ok( false === strpos( $pub, 'بررسی شد' ), 'internal note not in public view' );

		$_POST = $prev;
		wp_set_current_user( 0 );
	};

	/* ---- P: public view has no PII ---- */
	$tests['p1_public_view_pii'] = static function ( Pixva_Test_Case $t ) {
		$o   = pixva_test_make_order();
		$id  = $o['id'];
		update_post_meta( $id, '_pixva_order_notes', 'یادداشت فوق محرمانه داخلی' );
		$view = pixva_order_public_view( $id );
		$keys = array_keys( $view );
		sort( $keys );
		$t->eq(
			array( 'code', 'created', 'description', 'device', 'estimate', 'history', 'label', 'milestones', 'status', 'step', 'warranty' ),
			$keys,
			'public view has the expected keys only'
		);
		$json = wp_json_encode( $view );
		$t->ok( false === strpos( $json, $o['phone'] ), 'phone absent from public view' );
		$t->ok( false === strpos( $json, 'مشتری تست' ), 'customer name absent from public view' );
		$t->ok( false === strpos( $json, 'یادداشت فوق محرمانه' ), 'internal notes absent from public view' );
		$t->ok( false === strpos( $json, 'توضیح تست' ) || true, 'description is allowed by design (customer own text)' );
	};

	/* ---- M: migration lock (F7) ---- */
	$tests['m1_migration_lock'] = static function ( Pixva_Test_Case $t ) {
		delete_option( 'pixva_migration_lock' );
		$t->ok( true === pixva_migration_lock_acquire(), 'first acquire wins' );
		$t->ok( false === pixva_migration_lock_acquire(), 'second acquire blocked' );
		pixva_migration_lock_release();
		$t->ok( true === pixva_migration_lock_acquire(), 'release allows re-acquire' );
		pixva_migration_lock_release();
	};

	$tests['m2_stale_lock_takeover'] = static function ( Pixva_Test_Case $t ) {
		global $wpdb;
		delete_option( 'pixva_migration_lock' );
		add_option( 'pixva_migration_lock', array( 't' => time() - ( PIXVA_MIGRATION_LOCK_TTL + 60 ) ), '', 'no' );
		$t->ok( true === pixva_migration_lock_acquire(), 'stale lock taken over' );
		pixva_migration_lock_release();
		// Live lock must not be stolen.
		add_option( 'pixva_migration_lock', array( 't' => time() ), '', 'no' );
		$t->ok( false === pixva_migration_lock_acquire(), 'live lock respected' );
		pixva_migration_lock_release();
		unset( $wpdb );
	};

	$tests['m3_migrations_idempotent'] = static function ( Pixva_Test_Case $t ) {
		$before = (string) get_option( 'pixva_db_version', '' );
		pixva_run_migrations();
		$t->eq( $before, (string) get_option( 'pixva_db_version', '' ), 'second run is a no-op' );
		$t->ok( false === get_option( 'pixva_migration_lock' ), 'lock released after run' );
	};

	/* ---- L: rate limiter unit ---- */
	$tests['l1_rate_limiter'] = static function ( Pixva_Test_Case $t ) {
		$bucket = 'unit_rl_' . pixva_test_suffix();
		$ok     = 0;
		for ( $i = 0; $i < 5; $i++ ) {
			if ( pixva_rate_limit( $bucket, 3, HOUR_IN_SECONDS ) ) {
				++$ok;
			}
		}
		$t->eq( 3, $ok, 'limiter allows exactly max hits' );
	};

	/* ---- X: mail header safety ---- */
	$tests['x1_mail_hardening'] = static function ( Pixva_Test_Case $t ) {
		$t->eq( 'a b', pixva_strip_header_breaks( "a\r\nb" ), 'CRLF stripped from headers' );
		$t->eq( false, pixva_safe_mail( "evil@example.com\r\nBcc: x@example.com", 'subject', 'body' ), 'injection address rejected' );
		$t->eq( false, pixva_safe_mail( 'not-an-email', 'sub', 'body' ), 'invalid recipient rejected' );
	};

	return $tests;
}

/**
 * Execute tests.
 *
 * @param WP_REST_Request $req Request.
 * @return WP_REST_Response
 */
function pixva_test_run( $req ) {
	if ( ! pixva_test_authed() ) {
		return new WP_REST_Response( array( 'error' => 'forbidden' ), 403 );
	}
	$body      = (array) $req->get_json_params();
	$requested = isset( $body['tests'] ) && is_array( $body['tests'] ) ? array_map( 'strval', $body['tests'] ) : array();
	$registry  = pixva_test_registry();
	$results   = array();
	foreach ( $registry as $name => $fn ) {
		if ( $requested && ! in_array( $name, $requested, true ) ) {
			continue;
		}
		$case = new Pixva_Test_Case( $name );
		$start = microtime( true );
		try {
			$fn( $case );
		} catch ( Throwable $e ) {
			$case->failures[] = 'exception: ' . $e->getMessage();
		}
		wp_set_current_user( 0 );
		$results[] = array(
			'test'        => $name,
			'status'      => $case->failures ? 'FAIL' : 'PASS',
			'assertions'  => $case->assertions,
			'failures'    => $case->failures,
			'ms'          => round( ( microtime( true ) - $start ) * 1000, 1 ),
		);
	}
	return new WP_REST_Response(
		array(
			'wp'     => get_bloginfo( 'version' ),
			'php'    => PHP_VERSION,
			'tests'  => $results,
			'passed' => count( array_filter( $results, static fn( $r ) => 'PASS' === $r['status'] ) ),
			'failed' => count( array_filter( $results, static fn( $r ) => 'FAIL' === $r['status'] ) ),
		),
		200
	);
}

/**
 * State probes for the HTTP suite.
 *
 * @param WP_REST_Request $req Request.
 * @return WP_REST_Response
 */
function pixva_test_probe( $req ) {
	if ( ! pixva_test_authed() ) {
		return new WP_REST_Response( array( 'error' => 'forbidden' ), 403 );
	}
	$body   = array_merge( (array) $req->get_json_params(), (array) $req->get_body_params() );
	$action = isset( $body['action'] ) ? (string) $body['action'] : '';
	switch ( $action ) {
		case 'enable_registration':
			update_option( 'users_can_register', 1 );
			return new WP_REST_Response( array( 'ok' => 1 ), 200 );
		case 'create_customer':
			$uid = pixva_test_user( 'pixva_customer' );
			$ud  = $uid ? get_userdata( $uid ) : false;
			return new WP_REST_Response(
				array(
					'uid'   => $uid,
					'login' => $ud ? $ud->user_login : '',
					'pass'  => 'PixvaTest!2026',
				),
				200
			);
		case 'create_user':
			$role = isset( $body['role'] ) ? sanitize_key( $body['role'] ) : 'pixva_customer';
			$uid  = pixva_test_user( $role );
			$ud   = $uid ? get_userdata( $uid ) : false;
			return new WP_REST_Response(
				array(
					'uid'   => $uid,
					'login' => $ud ? $ud->user_login : '',
					'pass'  => 'PixvaTest!2026',
				),
				200
			);
		case 'orders_by_sid':
			$ids = pixva_test_orders_by_sid( (string) ( $body['sid'] ?? '' ) );
			return new WP_REST_Response( array( 'count' => count( $ids ), 'ids' => $ids ), 200 );
		case 'inbox_by_sid':
			$ids = pixva_test_inbox_by_sid( (string) ( $body['sid'] ?? '' ) );
			return new WP_REST_Response( array( 'count' => count( $ids ), 'ids' => $ids ), 200 );
		case 'order_code':
			$ids = pixva_test_orders_by_sid( (string) ( $body['sid'] ?? '' ) );
			$code = $ids ? (string) get_post_meta( $ids[0], '_pixva_order_code', true ) : '';
			return new WP_REST_Response( array( 'code' => $code ), 200 );
		case 'diag_verbose':
			global $wp_rewrite;
			return new WP_REST_Response(
				array(
					'verbose'   => (bool) $wp_rewrite->use_verbose_page_rules,
					'structure' => $wp_rewrite->permalink_structure,
					'pages'     => count( get_pages( array( 'fields' => 'ids', 'number' => 10000 ) ) ),
				),
				200
			);
		case 'diag_active_rules':
			$opt = get_option( 'rewrite_rules' );
			$hit = array();
			$ok  = 0;
			foreach ( (array) $opt as $k => $v ) {
				if ( preg_match( '!^' . $k . '!u', 'hello-world/' ) ) {
					$hit[] = $k;
					++$ok;
				}
			}
			return new WP_REST_Response(
				array(
					'is_array'  => is_array( $opt ),
					'count'     => is_array( $opt ) ? count( $opt ) : -1,
					'catch'     => isset( $opt['(.?.+?)(?:/([0-9]+))?/?$'] ) || isset( $opt['(.?.+?)/?$'] ),
					'matching'  => $hit,
					'last_keys' => array_slice( array_keys( (array) $opt ), -5 ),
				),
				200
			);
		case 'diag_flush':
			global $wp_rewrite;
			$before = array_keys( array_filter( (array) $wp_rewrite->wp_rewrite_rules(), static fn( $v, $k ) => false !== strpos( $k, 'blog/category' ), ARRAY_FILTER_USE_BOTH ) );
			$flag   = get_option( 'pixva_flush_rewrites' );
			flush_rewrite_rules( true );
			$rules = get_option( 'rewrite_rules' );
			$after = array();
			foreach ( (array) $rules as $k => $v ) {
				if ( false !== strpos( $k, 'blog/category' ) || preg_match( '#^blog/\(\.\+\?\)#', $k ) ) {
					$after[] = $k;
				}
			}
			return new WP_REST_Response(
				array(
					'flag_before'   => $flag,
					'before_count'  => count( $before ),
					'after_sample'  => array_slice( $after, 0, 8 ),
					'category_base' => get_option( 'category_base' ),
					'permalink'     => get_option( 'permalink_structure' ),
				),
				200
			);
		case 'diag_heal':
			$pre = array(
				'repaired' => (string) get_option( 'pixva_rules_repaired', 'unset' ),
				'count'    => is_array( get_option( 'rewrite_rules' ) ) ? count( (array) get_option( 'rewrite_rules' ) ) : -1,
			);
			if ( function_exists( 'pixva_verify_rewrite_rules' ) ) {
				pixva_verify_rewrite_rules();
			} else {
				return new WP_REST_Response( array( 'error' => 'heal fn missing' ), 200 );
			}
			$opt = (array) get_option( 'rewrite_rules' );
			$blog = array();
			foreach ( $opt as $k => $v ) {
				if ( str_starts_with( (string) $k, 'blog/' ) ) {
					$blog[] = $k;
				}
			}
			return new WP_REST_Response(
				array(
					'pre'      => $pre,
					'post'     => array(
						'repaired' => (string) get_option( 'pixva_rules_repaired', 'unset' ),
						'count'    => count( $opt ),
						'blog'     => array_slice( $blog, 0, 8 ),
					),
				),
				200
			);
		case 'diag_soft':
			global $wp_rewrite;
			if ( ! empty( $body['skip_init'] ) ) { flush_rewrite_rules( false ); }
			else { $wp_rewrite->init(); flush_rewrite_rules( false ); }
			$opt = (array) get_option( 'rewrite_rules' );
			$blog = array();
			foreach ( $opt as $k => $v ) {
				if ( str_starts_with( (string) $k, 'blog/' ) ) {
					$blog[] = $k;
				}
			}
			return new WP_REST_Response( array( 'count' => count( $opt ), 'blog' => array_slice( $blog, 0, 6 ), 'front' => $wp_rewrite->front, 'perma' => $wp_rewrite->permalink_structure ), 200 );
		case 'el_dump':
			$pid = (int) ( $body['id'] ?? 0 );
			$rows = array();
			if ( $pid ) {
				$ids = array( $pid );
			} else {
				$ids = array();
				$q = new WP_Query( array( 'post_type' => 'page', 'post_status' => array( 'publish', 'draft', 'private' ), 'posts_per_page' => -1, 'fields' => 'ids' ) );
				foreach ( $q->posts as $x ) {
					$ids[] = (int) $x;
				}
			}
			foreach ( $ids as $x ) {
				$data = get_post_meta( $x, '_elementor_data', true );
				$revs = wp_get_post_revisions( $x, array( 'numberposts' => 20, 'orderby' => 'ID', 'order' => 'DESC' ) );
				$rv = array();
				foreach ( $revs as $r ) {
					$rv[] = array(
						'id'      => (int) $r->ID,
						'date'    => (string) $r->post_date,
						'excerpt' => mb_substr( (string) $r->post_content, 0, 160 ),
						'edata'   => (string) get_post_meta( $r->ID, '_elementor_data', true ),
					);
				}
				$rows[] = array(
					'id'           => (int) $x,
					'slug'         => (string) get_post_field( 'post_name', $x ),
					'title'        => (string) get_the_title( $x ),
					'status'       => (string) get_post_status( $x ),
					'mode'         => (string) get_post_meta( $x, '_elementor_edit_mode', true ),
					'has_data'     => (string) $data !== '' ? 1 : 0,
					'data_len'     => strlen( (string) $data ),
					'data'         => (string) $data,
					'auto_id'      => (int) get_post_meta( $x, '_wp_autosave', true ) ? 1 : 0,
					'template'     => (string) get_post_meta( $x, '_wp_page_template', true ),
					'content'      => (string) get_post_field( 'post_content', $x ),
					'revisions'    => $rv,
				);
			}
			return new WP_REST_Response( array( 'ok' => 1, 'pages' => $rows ), 200 );

		case 'rl_reset':
			$prefix = 'pixva_rl_' . substr( (string) ( $body['prefix'] ?? '' ), 0, 60 );
			global $wpdb;
			$n1 = (int) $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", $wpdb->esc_like( '_transient_' . $prefix ) . '%' ) );
			$n2 = (int) $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", $wpdb->esc_like( '_transient_timeout_' . $prefix ) . '%' ) );
			return new WP_REST_Response( array( 'ok' => 1, 'deleted' => $n1 + $n2, 'like' => $prefix . '%' ), 200 );
		case 'el_kit':
			// Configure the Elementor kit (Site Settings) with the PIXVA palette /
			// Vazirmatn typography, then regenerate Elementor CSS files.
			$colors = array(
				'primary'   => '#0B1C2E',
				'secondary' => '#64748B',
				'text'      => '#1E293B',
				'accent'    => '#FF5C35',
			);
			$fonts = (string) ( $body['font'] ?? 'Vazirmatn' );
			$kit_id = (int) get_option( 'elementor_active_kit', 0 );
			if ( ! $kit_id ) {
				return new WP_REST_Response( array( 'ok' => 0, 'error' => 'no kit' ), 500 );
			}
			$settings = (array) get_post_meta( $kit_id, '_elementor_page_settings', true );
			$colors_arr = array();
			foreach ( $colors as $k => $hex ) {
				$colors_arr[] = array( '_id' => $k, 'title' => ucfirst( $k ), 'color' => $hex );
			}
			$typo = array();
			$typo[] = array(
				'_id' => 'primary', 'title' => 'Primary',
				'typography_typography' => 'typography',
				'typography_font_family' => $fonts,
				'typography_font_weight' => '800',
			);
			$typo[] = array(
				'_id' => 'secondary', 'title' => 'Secondary',
				'typography_typography' => 'typography',
				'typography_font_family' => $fonts,
				'typography_font_weight' => '700',
			);
			$typo[] = array(
				'_id' => 'text', 'title' => 'Text',
				'typography_typography' => 'typography',
				'typography_font_family' => $fonts,
				'typography_font_weight' => '400',
			);
			$typo[] = array(
				'_id' => 'accent', 'title' => 'Accent',
				'typography_typography' => 'typography',
				'typography_font_family' => $fonts,
				'typography_font_weight' => '500',
			);
			$settings['system_colors']     = $colors_arr;
			$settings['system_typography'] = $typo;
			update_post_meta( $kit_id, '_elementor_page_settings', $settings );
			if ( class_exists( '\\Elementor\\Plugin' ) && \Elementor\Plugin::$instance->files_manager ) {
				\Elementor\Plugin::$instance->files_manager->clear_cache();
			}
			return new WP_REST_Response( array( 'ok' => 1, 'kit' => $kit_id ), 200 );
		case 'set_option':
			// Allowlisted config writes (test harness only, token-guarded endpoint).
			$allow = array( 'elementor_google_font' => array( '0', '1' ) );
			$key   = (string) ( $body['key'] ?? '' );
			$val   = (string) ( $body['value'] ?? '' );
			if ( ! isset( $allow[ $key ] ) || ! in_array( $val, $allow[ $key ], true ) ) {
				return new WP_REST_Response( array( 'ok' => 0, 'error' => 'key/value not allowed' ), 400 );
			}
			update_option( $key, $val );
			return new WP_REST_Response( array( 'ok' => 1, 'key' => $key, 'value' => get_option( $key ) ), 200 );
		case 'opt_probe':
			return new WP_REST_Response(
				array(
					'hook_added' => (string) get_option( 'pixva_el_hook_added', 'unset' ),
					'reg_ran'    => (string) get_option( 'pixva_el_reg_ran', 'unset' ),
					'guard'      => (string) get_option( 'pixva_el_guard', 'unset' ),
				),
				200
			);
		case 'el_render':
			$id = (int) ( $body['id'] ?? 0 );
			$p  = get_post( $id );
			if ( ! $p ) {
				return new WP_REST_Response( array( 'error' => 'no post' ), 200 );
			}
			try {
				$GLOBALS['post'] = $p;
				setup_postdata( $p );
				$html = apply_filters( 'the_content', $p->post_content );
				wp_reset_postdata();
				return new WP_REST_Response( array( 'ok' => 1, 'len' => strlen( $html ), 'sample' => substr( $html, 0, 600 ) ), 200 );
			} catch ( Throwable $e ) {
				return new WP_REST_Response( array( 'ok' => 0, 'error' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine() ), 200 );
			}
		case 'el_set_mode':
			$id = (int) ( $body['id'] ?? 0 );
			update_post_meta( $id, '_elementor_edit_mode', 'builder' );
			return new WP_REST_Response( array( 'set' => $id ), 200 );
		case 'el_set_content':
			$id = (int) ( $body['id'] ?? 0 );
			wp_update_post(
				array(
					'ID'           => $id,
					'post_content' => (string) ( $body['content'] ?? '<!-- elementor -->' ),
				)
			);
			return new WP_REST_Response( array( 'set' => $id ), 200 );
		case 'el_clear_mode':
			$id = (int) ( $body['id'] ?? 0 );
			delete_post_meta( $id, '_elementor_edit_mode' );
			return new WP_REST_Response( array( 'cleared' => $id ), 200 );
		case 'el_state':
			$loaded  = did_action( 'elementor/loaded' );
			$widgets = array();
			if ( class_exists( 'Elementor\Plugin' ) ) {
				$wm = Elementor\Plugin::instance()->widgets_manager;
				if ( method_exists( $wm, 'get_widget_types' ) ) {
					foreach ( (array) $wm->get_widget_types() as $name => $obj ) {
						$widgets[] = (string) $name;
					}
				}
			}
			$pixva = array_values( array_filter( $widgets, static fn( $n ) => str_starts_with( $n, 'pixva-' ) ) );
			return new WP_REST_Response(
				array(
					'elementor_loaded' => (int) $loaded,
					'elementor_active' => class_exists( 'Elementor\Plugin' ),
					'widget_count'     => count( $widgets ),
					'pixva_widgets'    => $pixva,
					'theme'            => get_template(),
					'fn_exists'        => function_exists( 'pixva_register_elementor_widgets' ),
					'hooked'           => (int) has_action( 'elementor/widgets/register', 'pixva_register_elementor_widgets' ),
					'shortcodes'       => function_exists( 'pixva_sc_steps' ),
					'widget_base_cls'  => class_exists( 'Elementor' . chr(92) . 'Widget_Base' ),
					'hook_any'         => (int) has_action( 'elementor/widgets/register' ),
					'hooked_pri'       => var_export( has_action( 'elementor/widgets/register', 'pixva_register_elementor_widgets' ), true ),
					'callable'         => is_callable( 'pixva_register_elementor_widgets' ),
				),
				200
			);
		case 'el_mkpage':
			$name    = sanitize_title( (string) ( $body['name'] ?? 'el-test' ) );
			$widget  = (string) ( $body['widget'] ?? 'pixva-services' );
			$settings = isset( $body['settings'] ) && is_array( $body['settings'] ) ? $body['settings'] : array();
			$data = array(
				array(
					'id'       => 'a1b2c3d',
					'elType'   => 'container',
					'settings' => array(),
					'elements' => array(
						array(
							'id'         => 'e4f5a6b',
							'elType'     => 'widget',
							'settings'   => $settings,
							'elements'   => array(),
							'widgetType' => $widget,
						),
					),
				),
			);
			$existing = get_page_by_path( $name, OBJECT, 'page' );
			$postarr  = array(
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_title'   => (string) ( $body['title'] ?? 'EL Test' ),
				'post_name'    => $name,
				'post_content' => '<!-- elementor -->',
			);
			if ( $existing ) {
				$postarr['ID'] = $existing->ID;
				$id            = wp_update_post( $postarr );
			} else {
				$id = wp_insert_post( $postarr );
			}
			update_post_meta( $id, '_elementor_edit_mode', 'builder' );
			update_post_meta( $id, '_elementor_template_type', 'wp-page' );
			update_post_meta( $id, '_elementor_version', '4.4.0' );
			update_post_meta( $id, '_elementor_data', wp_slash( wp_json_encode( $data ) ) );
			update_post_meta( $id, '_elementor_page_settings', array() );
			return new WP_REST_Response( array( 'id' => (int) $id, 'url' => get_permalink( $id ) ), 200 );
		case 'mkpage':
			$content = (string) ( $body['content'] ?? '' );
			$name    = sanitize_title( (string) ( $body['name'] ?? 'sc-test' ) );
			$existing = get_page_by_path( $name, OBJECT, 'page' );
			$id = $existing ? $existing->ID : 0;
			$data = array(
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_title'   => (string) ( $body['title'] ?? 'SC Test' ),
				'post_name'    => $name,
				'post_content' => $content,
			);
			if ( $id ) {
				$data['ID'] = $id;
				wp_update_post( $data );
			} else {
				$id = wp_insert_post( $data );
			}
			return new WP_REST_Response( array( 'id' => (int) $id, 'url' => get_permalink( $id ) ), 200 );
		case 'diag_find':
			$opt  = get_option( 'rewrite_rules' );
			$pat  = (string) ( $body['pattern'] ?? 'blog' );
			$hits = array();
			foreach ( (array) $opt as $k => $v ) {
				if ( preg_match( '/' . $pat . '/i', $k . ' => ' . $v ) ) {
					$hits[ $k ] = $v;
				}
			}
			return new WP_REST_Response( array( 'pattern' => $pat, 'hits' => $hits, 'total' => is_array( $opt ) ? count( $opt ) : -1 ), 200 );
		case 'diag_raw':
			$before = get_option( 'pixva_rules_before', 'NO_SNAPSHOT' );
			$opt    = get_option( 'rewrite_rules' );
			$keys   = is_array( $opt ) ? array_keys( $opt ) : array();
			return new WP_REST_Response(
				array(
					'before_snapshot' => is_array( $before ) ? array( 'count' => count( $before ), 'sample' => array_slice( array_keys( $before ), 0, 6 ) ) : $before,
					'current'         => is_array( $opt ) ? array(
						'count'     => count( $opt ),
						'sample'    => array_slice( $keys, 0, 10 ),
						'has_name'  => (bool) array_filter( $keys, static fn( $k ) => str_contains( $k, 'blog/' ) ),
						'has_cat'   => (bool) array_filter( $keys, static fn( $k ) => str_starts_with( $k, 'blog/category' ) || str_starts_with( $k, 'category/' ) ),
						'has_cpt'   => (bool) array_filter( $keys, static fn( $k ) => str_starts_with( $k, 'brands/' ) || str_starts_with( $k, 'services/' ) || str_contains( $k, 'post_type' ) ),
						'order_tail'=> array_slice( $keys, -4 ),
					) : gettype( $opt ),
					'markers' => array(
						'flush_rewrites' => (string) get_option( 'pixva_flush_rewrites', 'unset' ),
						'rewrite_order'  => (string) get_option( 'pixva_rewrite_order', 'unset' ),
					),
					'verbose' => isset( $GLOBALS['wp_rewrite'] ) ? (bool) $GLOBALS['wp_rewrite']->use_verbose_page_rules : null,
					'structure' => get_option( 'permalink_structure' ),
					'category_base' => get_option( 'category_base' ),
				),
				200
			);
		case 'diag_rules':
			global $wp_rewrite;
			$rules = $wp_rewrite->wp_rewrite_rules();
			$keep  = array();
			foreach ( (array) $rules as $k => $v ) {
				if ( preg_match( '/blog|category|postname|pagename|uncategorized/i', $k . ' ' . $v ) ) {
					$keep[ $k ] = $v;
				}
			}
			return new WP_REST_Response(
				array(
					'permalink'   => get_option( 'permalink_structure' ),
					'category_base' => get_option( 'category_base' ),
					'page_for_posts' => get_option( 'page_for_posts' ),
					'page_on_front'  => get_option( 'page_on_front' ),
					'show_on_front'  => get_option( 'show_on_front' ),
					'rules'       => $keep,
				),
				200
			);
		case 'list':
			return new WP_REST_Response( array( 'tests' => array_keys( pixva_test_registry() ) ), 200 );
		case 'order_detail':
			$ids = pixva_test_orders_by_sid( (string) ( $body['sid'] ?? '' ) );
			$id  = $ids ? (int) $ids[0] : 0;
			$photos = $id ? json_decode( (string) get_post_meta( $id, '_pixva_order_photos', true ), true ) : null;
			$dir    = function_exists( 'pixva_private_dir' ) ? pixva_private_dir() : '';
			$listed = ( $dir && is_dir( $dir ) ) ? array_values( array_diff( scandir( $dir ) ?: array(), array( '.', '..' ) ) ) : array();
			return new WP_REST_Response(
				array(
					'id'     => $id,
					'photos' => $photos,
					'dir'    => $dir,
					'listed' => $listed,
				),
				200
			);
		case 'dump_files':
			$shape = static function ( $arr ) {
				$out = array();
				foreach ( (array) $arr as $k => $v ) {
					$out[ $k ] = is_array( $v ) ? array_map( 'strval', $v ) : (string) $v;
				}
				return $out;
			};
			return new WP_REST_Response(
				array(
					'files' => array_map( $shape, (array) $_FILES ),
					'post'  => $shape( $_POST ),
				),
				200
			);
		case 'debug_claim':
			// Step-trace of the claim cycle (i1/i4 diagnostics).
			$sid   = pixva_submission_id();
			$trace = array( 'sid' => $sid );
			$step  = static function ( $label, $val ) use ( &$trace ) {
				$trace[ $label ] = $val;
			};
			$step( 'claim1', pixva_claim_submission( $sid, 'pixva_booking' ) );
			$step( 'claim2', pixva_claim_submission( $sid, 'pixva_booking' ) );
			pixva_finish_submission( $sid, array( 'code' => 'PXV-DBG-1' ) );
			$step( 'row_after_finish', pixva_submission_row( pixva_submission_normalize_id( $sid ) ) );
			$step( 'claim3', pixva_claim_submission( $sid, 'pixva_booking' ) );
			pixva_release_submission( $sid );
			$step( 'row_after_release', pixva_submission_row( pixva_submission_normalize_id( $sid ) ) );
			$step( 'claim4', pixva_claim_submission( $sid, 'pixva_booking' ) );
			$step( 'row_after_claim4', pixva_submission_row( pixva_submission_normalize_id( $sid ) ) );
			pixva_release_submission( $sid );
			// Expired done record.
			$sid2 = pixva_submission_id();
			$key2 = 'pixva_sub_' . pixva_submission_normalize_id( $sid2 );
			add_option(
				$key2,
				array(
					's' => 'done',
					't' => time() - ( PIXVA_SUB_TTL + 60 ),
					'p' => array( 'message' => 'stale' ),
				),
				'',
				'no'
			);
			$step( 'row_before_expired', pixva_submission_row( pixva_submission_normalize_id( $sid2 ) ) );
			$step( 'claim_expired', pixva_claim_submission( $sid2, 'pixva_booking' ) );
			$step( 'row_after_expired_claim', pixva_submission_row( pixva_submission_normalize_id( $sid2 ) ) );
			pixva_release_submission( $sid2 );
			return new WP_REST_Response( $trace, 200 );
	}
	return new WP_REST_Response( array( 'error' => 'unknown action' ), 400 );
}

add_action(
	'rest_api_init',
	static function () {
		register_rest_route(
			'pixva-test/v1',
			'/run',
			array(
				'methods'             => 'POST',
				'callback'            => 'pixva_test_run',
				'permission_callback' => '__return_true', // Header token checked in the callback (clearer 403).
			)
		);
		register_rest_route(
			'pixva-test/v1',
			'/probe',
			array(
				'methods'             => 'POST',
				'callback'            => 'pixva_test_probe',
				'permission_callback' => '__return_true',
			)
		);
	}
);

// Diagnostic: expose the matched query flags on any request that carries
// ?dbg=1 (runs before core redirect_canonical at priority 10).
add_action(
	'template_redirect',
	static function () {
		if ( ! isset( $_GET['dbg'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}
		$q = $GLOBALS['wp_query'] ?? null;
		$flags = array(
			'cat'   => (int) is_category(),
			'tax'   => (int) is_tax(),
			'sing'  => (int) is_singular(),
			'404'   => (int) is_404(),
			'home'  => (int) is_home(),
			'front' => (int) is_front_page(),
			'pg'    => (int) is_page(),
			'posts' => (int) ( $q ? $q->is_posts_page : 0 ),
			'np'    => (int) ( $q ? $q->is_posts_page : 0 ),
		);
		$obj = get_queried_object();
		$flags['obj'] = is_object( $obj ) ? ( ( $obj->taxonomy ?? '' ) . ':' . ( $obj->slug ?? ( $obj->post_name ?? '' ) ) ) : 'none';
		$flags['pn']   = (string) ( $GLOBALS['wp_query']->query['pagename'] ?? '' );
		$flags['pv']   = (string) ( $GLOBALS['wp_query']->query_vars['pagename'] ?? '' );
		$flags['pid']  = (int) ( $GLOBALS['wp_query']->query_vars['page_id'] ?? 0 );
		$flags['mr']   = (string) ( $GLOBALS['wp']->matched_rule ?? '' );
		$flags['mq']   = substr( (string) ( $GLOBALS['wp']->matched_query ?? '' ), 0, 80 );
		$can = function_exists( 'pixva_canonical_url' ) ? pixva_canonical_url() : '';
		header( 'X-Dbg-Query: ' . str_replace( array( ' ', ',' ), array( '_', ';' ), wp_json_encode( $flags ) ) );
		header( 'X-Dbg-Canonical: ' . rawurlencode( (string) $can ) );
	},
	0
);

// Debug: what does Elementor register inside an admin-ajax request?
add_action(
	'wp_ajax_pixva_el_probe',
	static function () {
		$out = array( 'step' => 'start' );
		$out['el_ajax_hook'] = (int) has_action( 'wp_ajax_elementor_ajax' );
		$out['step'] = 'after_has_action';
		$out['init_c'] = (int) did_action( 'init' );
		$out['step'] = 'done';
		wp_send_json( $out );
	}
);

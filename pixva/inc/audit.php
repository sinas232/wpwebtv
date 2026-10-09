<?php
/**
 * Read-only consistency audit for submissions, reservations, orders and private photos.
 *
 * This module NEVER writes or deletes. It lists inconsistent ids and statuses so
 * staff can decide what to do (see docs/pixva-staging-checklist.md, section
 * "Audit and conflict resolution"). There is no automatic repair and no cleanup:
 * a file is only reported, because its owner cannot be verified once an order
 * has been deleted.
 *
 * Usage (WP-CLI only, read-only):  wp pixva audit [--format=json]
 *
 * Layers:
 *  - pixva_audit_build()   pure function over snapshots (unit-tested, no DB).
 *  - pixva_audit_collect() reads wp_options, the order posts and the private
 *                          directory, then calls pixva_audit_build().
 *
 * @package pixva
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Collect a read-only snapshot from the database and the private directory.
 *
 * @param string $private_dir Absolute path of the private photo directory ('' = none).
 * @return array{submissions:array,reservations:array,orders:array,files:array}
 */
function pixva_audit_collect( $private_dir ) {
	global $wpdb;
	$snap = array(
		'submissions'  => array(),
		'reservations' => array(),
		'orders'       => array(),
		'files'        => array(),
	);

	$rows = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- audit reads raw rows.
		$wpdb->prepare( "SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s", $wpdb->esc_like( 'pixva_sub_' ) . '%', $wpdb->esc_like( 'pixva_ord_' ) . '%' ) // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	);
	foreach ( (array) $rows as $row ) {
		$name = (string) $row->option_name;
		if ( 0 === strpos( $name, 'pixva_sub_' ) ) {
			$snap['submissions'][ substr( $name, 10 ) ] = (string) $row->option_value;
		} elseif ( 0 === strpos( $name, 'pixva_ord_' ) ) {
			$snap['reservations'][ substr( $name, 10 ) ] = (string) $row->option_value;
		}
	}

	$posts = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- audit reads raw rows.
		$wpdb->prepare( "SELECT ID, post_name, post_status FROM {$wpdb->posts} WHERE post_type = %s", 'pixva_orders' ) // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	);
	foreach ( (array) $posts as $post ) {
		$id               = (int) $post->ID;
		$snap['orders'][] = array(
			'id'     => $id,
			'slug'   => (string) $post->post_name,
			'code'   => (string) get_post_meta( $id, '_pixva_order_code', true ),
			'status' => (string) get_post_meta( $id, '_pixva_order_status', true ),
			'photos' => pixva_audit_decode_list( get_post_meta( $id, '_pixva_order_photos', true ) ),
		);
	}

	if ( '' !== (string) $private_dir && is_dir( $private_dir ) ) {
		foreach ( (array) scandir( $private_dir ) as $file ) {
			if ( '.' === $file || '..' === $file || 'index.php' === $file || '.htaccess' === $file || ! is_file( $private_dir . '/' . $file ) ) {
				continue;
			}
			$snap['files'][] = $file;
		}
	}
	return $snap;
}

/**
 * Resolve the private photo directory WITHOUT creating it or writing .htaccess
 * (pixva_private_dir() does both, which an audit must never do).
 *
 * @return string Absolute path, or '' when uploads are unavailable.
 */
function pixva_audit_private_dir() {
	$uploads = wp_upload_dir( null, false );
	if ( ! empty( $uploads['error'] ) ) {
		return '';
	}
	return trailingslashit( $uploads['basedir'] ) . 'pixva-private';
}

/**
 * Decode a JSON list meta value to a list of strings.
 *
 * @param mixed $raw Meta value.
 * @return string[]
 */
function pixva_audit_decode_list( $raw ) {
	$list = json_decode( (string) $raw, true );
	if ( ! is_array( $list ) ) {
		return array();
	}
	return array_values( array_filter( array_map( 'strval', $list ), 'strlen' ) );
}

/**
 * Pure audit over snapshots. No I/O, no writes.
 *
 * @param array $snap Snapshot: submissions, reservations, orders, files (see pixva_audit_collect()).
 * @param int   $now  Current unix time.
 * @return array{findings:array,summary:array,photo_map:array}
 */
function pixva_audit_build( array $snap, $now ) {
	$findings = array();
	$add      = static function ( $code, $severity, $kind, $id, $detail ) use ( &$findings ) {
		$findings[] = array(
			'code'     => $code,
			'severity' => $severity,
			'object'   => $kind,
			'id'       => (string) $id,
			'detail'   => $detail,
		);
	};

	$orders_by_id = array();
	$by_sid       = array();
	$by_code      = array();
	$photo_owner  = array();
	foreach ( $snap['orders'] as $o ) {
		$orders_by_id[ $o['id'] ] = $o;
		// WordPress appends -2, -3 to a colliding slug: group on the 32-hex prefix.
		$sid = preg_match( '/^([0-9a-f]{32})(?:-\d+)?$/', (string) $o['slug'], $m ) ? $m[1] : '';
		if ( '' === $sid ) {
			$add( 'order_without_sid', 'info', 'order', $o['id'], 'No submission id in the slug (created in admin or by a legacy version). Expected for those orders.' );
		} else {
			$by_sid[ $sid ][] = $o['id'];
		}
		if ( '' === $o['code'] ) {
			$add( 'order_missing_code', 'error', 'order', $o['id'], 'Order has no _pixva_order_code, so the customer cannot track it.' );
		} else {
			$by_code[ $o['code'] ][] = $o['id'];
		}
		foreach ( $o['photos'] as $file ) {
			$photo_owner[ $file ][] = $o['id'];
		}
	}

	foreach ( $by_code as $code => $ids ) {
		if ( count( $ids ) > 1 ) {
			$add( 'order_code_duplicate', 'error', 'order', implode( ',', $ids ), 'Tracking code ' . $code . ' is used by more than one order; lookup is ambiguous.' );
		}
	}
	foreach ( $by_sid as $sid => $ids ) {
		if ( count( $ids ) > 1 ) {
			$add( 'submission_duplicate_orders', 'error', 'submission', $sid, 'Orders ' . implode( ',', $ids ) . ' share one submission id. Run the placement path again to remove unlinked duplicates, or resolve by hand (see checklist).' );
		}
	}

	$linked_order_ids = array();
	foreach ( $snap['reservations'] as $sid => $raw ) {
		$row = json_decode( (string) $raw, true );
		if ( ! is_array( $row ) ) {
			$add( 'reservation_unreadable', 'error', 'reservation', $sid, 'Reservation value is not valid JSON; the next placement attempt will take it over.' );
			continue;
		}
		$state = (string) ( $row['s'] ?? '' );
		$age   = $now - (int) ( $row['t'] ?? 0 );
		if ( 'linked' === $state ) {
			$oid = (int) ( $row['o'] ?? 0 );
			if ( ! isset( $orders_by_id[ $oid ] ) ) {
				$add( 'reservation_linked_order_missing', 'error', 'reservation', $sid, 'Linked to order ' . $oid . ', which does not exist. The next attempt takes the reservation over.' );
				continue;
			}
			$linked_order_ids[ $oid ] = true;
			if ( ! in_array( $oid, $by_sid[ $sid ] ?? array(), true ) ) {
				$add( 'reservation_linked_order_sid_mismatch', 'error', 'reservation', $sid, 'Linked order ' . $oid . ' is not named by this submission id.' );
			}
		} elseif ( 'creating' === $state ) {
			$has_order = ! empty( $by_sid[ $sid ] );
			if ( $age > PIXVA_CLAIM_PENDING_TTL ) {
				$add( 'reservation_creating_stale', 'warn', 'reservation', $sid, $has_order ? 'Placement stopped after the order was inserted but before the link. The next attempt adopts that order.' : 'Placement stopped before the order was inserted. The next attempt inserts the order.' );
			} else {
				$add( 'reservation_creating_active', 'info', 'reservation', $sid, 'Placement in progress (younger than the claim TTL).' );
			}
		} else {
			$add( 'reservation_unknown_state', 'error', 'reservation', $sid, 'Unknown reservation state "' . $state . '".' );
		}
	}

	foreach ( $snap['submissions'] as $sid => $raw ) {
		$row = json_decode( (string) $raw, true );
		if ( ! is_array( $row ) ) {
			$add( 'claim_unreadable', 'error', 'submission', $sid, 'Claim value is not valid JSON; it is treated as dead after its TTL.' );
			continue;
		}
		$age = $now - (int) ( $row['t'] ?? 0 );
		if ( 'pending' === ( $row['s'] ?? '' ) && $age > PIXVA_CLAIM_PENDING_TTL ) {
			$add( 'claim_pending_expired', 'warn', 'submission', $sid, 'Pending claim past its TTL. A resubmission with this id recovers it (no manual action needed unless the customer reports a problem).' );
		}
	}

	foreach ( $by_sid as $sid => $ids ) {
		if ( ! isset( $snap['reservations'][ $sid ] ) ) {
			$add( 'order_without_reservation', 'info', 'submission', $sid, 'Orders ' . implode( ',', $ids ) . ' have a submission id but no reservation row (legacy or manually changed). Not an error on its own.' );
		}
	}
	foreach ( $orders_by_id as $id => $o ) {
		if ( ! isset( $linked_order_ids[ $id ] ) && '' !== (string) $o['slug'] && preg_match( '/^[0-9a-f]{32}/', (string) $o['slug'] ) && isset( $snap['reservations'][ substr( (string) $o['slug'], 0, 32 ) ] ) ) {
			$add( 'order_not_linked', 'warn', 'order', $id, 'The reservation for this submission does not link to this order. Check the duplicate and linked-order findings before changing anything.' );
		}
	}

	$on_disk = array_fill_keys( $snap['files'], true );
	foreach ( $photo_owner as $file => $ids ) {
		if ( ! isset( $on_disk[ $file ] ) ) {
			$add( 'photo_reference_missing', 'error', 'photo', $file, 'Referenced by order(s) ' . implode( ',', $ids ) . ' but the file is not in the private directory.' );
		}
		if ( count( $ids ) > 1 ) {
			$add( 'photo_shared', 'error', 'photo', $file, 'Referenced by more than one order: ' . implode( ',', $ids ) . '.' );
		}
	}
	foreach ( $snap['files'] as $file ) {
		if ( ! isset( $photo_owner[ $file ] ) ) {
			$add( 'photo_unreferenced', 'warn', 'photo', $file, 'No order references this file. Its owner cannot be verified, so it is reported only and never deleted automatically.' );
		}
	}

	$summary = array();
	foreach ( $findings as $f ) {
		$summary[ $f['severity'] ] = ( $summary[ $f['severity'] ] ?? 0 ) + 1;
	}
	ksort( $summary );

	$photo_map = array();
	foreach ( $photo_owner as $file => $ids ) {
		$photo_map[ $file ] = array_values( array_unique( $ids ) );
	}
	ksort( $photo_map );

	return array(
		'findings'  => $findings,
		'summary'   => $summary,
		'photo_map' => $photo_map,
	);
}

/**
 * Run the audit against the live site (read-only).
 *
 * @return array Same shape as pixva_audit_build(), plus 'generated_at'.
 */
function pixva_audit_run() {
	$dir                 = pixva_audit_private_dir();
	$out                 = pixva_audit_build( pixva_audit_collect( $dir ), pixva_now() );
	$out['generated_at'] = gmdate( 'c', pixva_now() );
	return $out;
}

if ( defined( 'WP_CLI' ) && WP_CLI && class_exists( 'WP_CLI' ) ) {
	/**
	 * Read-only audit of submissions, reservations, orders and private photos.
	 *
	 * ## OPTIONS
	 *
	 * [--format=<format>]
	 * : table (default) or json.
	 *
	 * @param array $args       Unused.
	 * @param array $assoc_args Options.
	 */
	WP_CLI::add_command(
		'pixva audit',
		static function ( $args, $assoc_args ) {
			$report = pixva_audit_run();
			if ( 'json' === ( $assoc_args['format'] ?? 'table' ) ) {
				WP_CLI::line( (string) wp_json_encode( $report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) );
				return;
			}
			WP_CLI::line( 'Findings: ' . count( $report['findings'] ) . ' ' . wp_json_encode( $report['summary'] ) );
			foreach ( $report['findings'] as $f ) {
				WP_CLI::line( sprintf( '[%s] %s %s %s: %s', $f['severity'], $f['code'], $f['object'], $f['id'], $f['detail'] ) );
			}
		}
	);
}

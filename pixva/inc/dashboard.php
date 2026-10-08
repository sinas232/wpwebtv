<?php
/**
 * Role-aware operations dashboard at /dashboard/ (§15, §50).
 *
 * Panels (real data only, no placeholder numbers):
 * - manager/admin : order counts by status, unassigned queue, recent
 *                   orders, warranties expiring in 30 days, unread inbox.
 * - technician    : orders assigned to them (phone masked) + status form.
 * - editor/admin  : content health — required fields missing, thin
 *                   problem terms, models without brand, drafts.
 *
 * @package Pixva
 * @since   2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Order ids by meta query helper.
 *
 * @param array $args Extra WP_Query args.
 * @return int[]
 */
function pixva_order_query( $args ) {
	return array_map(
		'intval',
		get_posts(
			array_merge(
				array(
					'post_type'        => 'pixva_orders',
					'post_status'      => array( 'private', 'publish' ),
					'posts_per_page'   => 20,
					'orderby'          => 'date',
					'order'            => 'DESC',
					'fields'           => 'ids',
					'no_found_rows'    => true,
					'suppress_filters' => true,
				),
				$args
			)
		)
	);
}

/**
 * Count of orders per status (single grouped query).
 *
 * @return array<string,int>
 */
function pixva_order_status_counts() {
	global $wpdb;
	$rows = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- dashboard aggregate, staff-only.
		$wpdb->prepare(
			"SELECT pm.meta_value AS s, COUNT(*) AS c FROM {$wpdb->postmeta} pm INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id WHERE p.post_type = %s AND p.post_status IN ('private','publish') AND pm.meta_key = %s GROUP BY pm.meta_value",
			'pixva_orders',
			'_pixva_order_status'
		)
	);
	$out  = array_fill_keys( array_keys( pixva_order_statuses() ), 0 );
	foreach ( (array) $rows as $r ) {
		if ( isset( $out[ $r->s ] ) ) {
			$out[ $r->s ] = (int) $r->c;
		}
	}
	return $out;
}

/**
 * Open (not delivered/cancelled) orders assigned to a technician.
 *
 * @param int $user_id Technician.
 * @return int[]
 */
function pixva_technician_orders( $user_id ) {
	return pixva_order_query(
		array(
			'posts_per_page' => 50,
			'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				'relation' => 'AND',
				array(
		'key'   => '_pixva_technician_id',
		'value' => (int) $user_id,
			),
				array(
			'key'     => '_pixva_order_status',
			'value'   => array( 'delivered', 'cancelled' ),
			'compare' => 'NOT IN',
			),
			),
		)
	);
}

/**
 * Open orders without technician.
 *
 * @return int[]
 */
function pixva_unassigned_orders() {
	return pixva_order_query(
		array(
			'meta_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				'relation' => 'AND',
				array(
		'key'     => '_pixva_technician_id',
		'compare' => 'NOT EXISTS',
			),
				array(
			'key'     => '_pixva_order_status',
			'value'   => array( 'delivered', 'cancelled' ),
			'compare' => 'NOT IN',
			),
			),
		)
	);
}

/**
 * Warranties ending within N days.
 *
 * @param int $days Days.
 * @return int[]
 */
function pixva_expiring_warranties( $days = 30 ) {
	return pixva_order_query(
		array(
			'meta_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				array(
					'key'     => '_pixva_warranty_until',
					'value'   => array( wp_date( 'Y-m-d' ), wp_date( 'Y-m-d', time() + $days * DAY_IN_SECONDS ) ),
					'compare' => 'BETWEEN',
					'type'    => 'DATE',
				),
			),
		)
	);
}

/**
 * Content health report for editors (§44).
 *
 * @return array<int,array{label:string,items:array<int,array{title:string,url:string}>}>
 */
function pixva_content_health() {
	$report = array();
	foreach ( array( 'tv_services', 'tv_brands', 'tv_model', 'pixva_error', 'repair_cases' ) as $type ) {
		$required = array_keys( array_filter( pixva_meta_fields_for( $type ), static fn( $d ) => ! empty( $d['required'] ) ) );
		if ( ! $required ) {
			continue;
		}
		$items = array();
		foreach ( get_posts(
			array(
				'post_type'      => $type,
				'post_status'    => 'publish',
				'posts_per_page' => 200,
				'no_found_rows'  => true,
			)
		) as $p ) {
			foreach ( $required as $key ) {
				if ( '' === (string) get_post_meta( $p->ID, $key, true ) || '0' === (string) get_post_meta( $p->ID, $key, true ) ) {
					$items[] = array(
						'title' => get_the_title( $p ),
						'url'   => (string) get_edit_post_link( $p->ID, 'raw' ),
					);
					break;
				}
			}
		}
		if ( $items ) {
			$obj      = get_post_type_object( $type );
			$report[] = array(
				/* translators: %s: content type. */
				'label' => sprintf( __( '%s با فیلد ضروری خالی', 'pixva' ), $obj ? $obj->labels->name : $type ),
				'items' => $items,
			);
		}
	}
	$thin = array();
	foreach ( get_terms(
		array(
			'taxonomy'   => 'tv_problem',
			'hide_empty' => false,
		)
	) as $t ) {
		if ( ! is_wp_error( $t ) && pixva_strlen( wp_strip_all_tags( (string) $t->description ) ) < 80 ) {
			$thin[] = array(
				'title' => $t->name,
				'url'   => (string) get_edit_term_link( $t->term_id, 'tv_problem' ),
			);
		}
	}
	if ( $thin ) {
		$report[] = array(
			'label' => __( 'مشکل‌های بدون توضیح کافی (noindex تا تکمیل)', 'pixva' ),
			'items' => $thin,
		);
	}
	return $report;
}

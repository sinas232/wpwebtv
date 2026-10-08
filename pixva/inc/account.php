<?php
/**
 * Customer account (§14).
 *
 * /account/ (overview), /account/repairs/, /account/warranty/,
 * /account/profile/ share page-templates/account.php. All views query by
 * `_pixva_customer_id = current user` — never by a user-editable phone.
 *
 * @package Pixva
 * @since   2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Orders owned by a user, newest first.
 *
 * @param int $user_id User.
 * @param int $limit   Max.
 * @return int[]
 */
function pixva_customer_orders( $user_id, $limit = 50 ) {
	if ( ! $user_id ) {
		return array();
	}
	return array_map(
		'intval',
		get_posts(
			array(
				'post_type'        => 'pixva_orders',
				'post_status'      => array( 'private', 'publish' ),
				'meta_key'         => '_pixva_customer_id', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value'       => (int) $user_id, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				'posts_per_page'   => (int) $limit,
				'orderby'          => 'date',
				'order'            => 'DESC',
				'fields'           => 'ids',
				'no_found_rows'    => true,
				'suppress_filters' => true,
			)
		)
	);
}

/**
 * Private, never-cached responses for account and dashboard routes.
 *
 * @return void
 */
function pixva_private_route_headers() {
	$route = pixva_current_route();
	if ( in_array( $route, array( 'account', 'account_repairs', 'account_warranty', 'account_profile', 'dashboard' ), true ) ) {
		nocache_headers();
		header( 'X-Robots-Tag: noindex, nofollow' );
		if ( 'dashboard' === $route && is_user_logged_in() && ! current_user_can( 'pixva_view_dashboard' ) ) {
			status_header( 403 );
		}
	}
}
add_action( 'template_redirect', 'pixva_private_route_headers', 20 );

/**
 * Login redirect: customers → account, staff with dashboard → dashboard.
 *
 * @param string           $redirect  Target.
 * @param string           $requested Requested.
 * @param WP_User|WP_Error $user      User.
 * @return string
 */
function pixva_login_redirect( $redirect, $requested, $user ) {
	if ( ! $user instanceof WP_User ) {
		return $redirect;
	}
	if ( $requested && false === strpos( $requested, 'wp-admin' ) ) {
		return $redirect;
	}
	if ( user_can( $user, 'edit_posts' ) || user_can( $user, 'pixva_manage_orders' ) ) {
		return $redirect;
	}
	return user_can( $user, 'pixva_work_orders' ) ? pixva_route_url( 'dashboard' ) : pixva_route_url( 'account' );
}
add_filter( 'login_redirect', 'pixva_login_redirect', 10, 3 );

/**
 * Flag a login so the next page view emits the account_login analytics event.
 *
 * @param string  $login Login.
 * @param WP_User $user  User.
 * @return void
 */
function pixva_flag_login_event( $login, $user ) {
	update_user_meta( $user->ID, '_pixva_pending_event', 'account_login' );
}
add_action( 'wp_login', 'pixva_flag_login_event', 10, 2 );

/**
 * Account navigation items.
 *
 * @return array<string,array{0:string,1:string}>
 */
function pixva_account_nav() {
	$items = array(
		'overview' => array( __( 'پیشخوان', 'pixva' ), pixva_route_url( 'account' ) ),
		'repairs'  => array( __( 'تعمیرهای من', 'pixva' ), pixva_route_url( 'account_repairs' ) ),
		'warranty' => array( __( 'گارانتی', 'pixva' ), pixva_route_url( 'account_warranty' ) ),
		'profile'  => array( __( 'مشخصات', 'pixva' ), pixva_route_url( 'account_profile' ) ),
	);
	if ( current_user_can( 'pixva_view_dashboard' ) ) {
		$items['dashboard'] = array( __( 'داشبورد کاری', 'pixva' ), pixva_route_url( 'dashboard' ) );
	}
	return $items;
}

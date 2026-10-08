<?php
/**
 * Roles & capabilities (§14, §15, §31, §50).
 *
 * Personal data (repair orders, customer messages, uploaded photos) is
 * protected by dedicated capabilities instead of the generic "post" type.
 * Editors manage public content only and receive NO PII capability.
 *
 * Roles
 * - pixva_customer   : own repairs, warranty, profile (front-end only).
 * - pixva_technician : front-end dashboard; sees and updates ONLY orders
 *                      assigned to them; phone numbers masked.
 * - pixva_manager    : full order + message management, business settings.
 * - administrator    : everything.
 * - editor           : content + redirects + content-health dashboard.
 *
 * Meta capabilities resolved in pixva_map_meta_cap():
 * - pixva_view_order (order_id)   customer owner, assigned tech, or manager.
 * - pixva_work_order (order_id)   assigned technician or manager.
 * - pixva_view_order_pii (order)  manager/admin only (full phone, notes).
 *
 * @package Pixva
 * @since   2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Primitive capabilities for the order post type (capability_type pixva_order).
 *
 * @return string[]
 */
function pixva_order_primitive_caps() {
	return array(
		'edit_pixva_orders',
		'edit_others_pixva_orders',
		'edit_private_pixva_orders',
		'edit_published_pixva_orders',
		'publish_pixva_orders',
		'read_private_pixva_orders',
		'delete_pixva_orders',
		'delete_others_pixva_orders',
		'delete_private_pixva_orders',
		'delete_published_pixva_orders',
		'create_pixva_orders',
	);
}

/**
 * Primitive capabilities for the message post type (capability_type pixva_message).
 *
 * @return string[]
 */
function pixva_message_primitive_caps() {
	return array(
		'edit_pixva_messages',
		'edit_others_pixva_messages',
		'edit_private_pixva_messages',
		'edit_published_pixva_messages',
		'read_private_pixva_messages',
		'delete_pixva_messages',
		'delete_others_pixva_messages',
		'delete_private_pixva_messages',
		'delete_published_pixva_messages',
	);
}

/**
 * Capability map per role. Single source used by install and upgrade.
 *
 * @return array<string,string[]>
 */
function pixva_role_caps() {
	$orders   = pixva_order_primitive_caps();
	$messages = pixva_message_primitive_caps();
	$settings = array( 'pixva_manage_settings', 'pixva_view_dashboard', 'pixva_manage_orders', 'pixva_view_order_pii', 'pixva_export_orders' );
	return array(
		'administrator'    => array_merge( $orders, $messages, $settings, array( 'pixva_manage_redirects', 'pixva_view_content_health' ) ),
		'pixva_manager'    => array_merge( array( 'read' ), $orders, $messages, $settings ),
		'pixva_technician' => array( 'read', 'pixva_view_dashboard', 'pixva_work_orders' ),
		'pixva_customer'   => array( 'read' ),
		'editor'           => array( 'pixva_view_dashboard', 'pixva_manage_redirects', 'pixva_view_content_health' ),
	);
}

/**
 * Create/refresh roles and grant capabilities. Idempotent; runs on theme
 * switch and on version upgrade (see migration.php).
 *
 * @return void
 */
function pixva_install_roles() {
	$labels = array(
		'pixva_customer'   => __( 'مشتری پیکسوا', 'pixva' ),
		'pixva_technician' => __( 'تکنسین پیکسوا', 'pixva' ),
		'pixva_manager'    => __( 'مدیر پذیرش پیکسوا', 'pixva' ),
	);
	foreach ( pixva_role_caps() as $role_name => $caps ) {
		$role = get_role( $role_name );
		if ( ! $role && isset( $labels[ $role_name ] ) ) {
			$role = add_role( $role_name, $labels[ $role_name ], array( 'read' => true ) );
		}
		if ( ! $role ) {
			continue;
		}
		foreach ( $caps as $cap ) {
			if ( ! $role->has_cap( $cap ) ) {
				$role->add_cap( $cap );
			}
		}
	}

	// v1.x gave technicians edit_posts/upload_files (could publish blog posts): revoke.
	$tech = get_role( 'pixva_technician' );
	if ( $tech ) {
		foreach ( array( 'edit_posts', 'upload_files' ) as $cap ) {
			if ( $tech->has_cap( $cap ) ) {
				$tech->remove_cap( $cap );
			}
		}
	}
}

/**
 * Resolve PIXVA meta capabilities.
 *
 * @param string[] $caps    Required primitive caps.
 * @param string   $cap     Requested cap.
 * @param int      $user_id User.
 * @param array    $args    Extra args (order id first).
 * @return string[]
 */
function pixva_map_meta_cap( $caps, $cap, $user_id, $args ) {
	if ( ! in_array( $cap, array( 'pixva_view_order', 'pixva_work_order' ), true ) ) {
		return $caps;
	}
	$order_id = isset( $args[0] ) ? (int) $args[0] : 0;
	$order    = $order_id ? get_post( $order_id ) : null;
	if ( ! $order || 'pixva_orders' !== $order->post_type || ! $user_id ) {
		return array( 'do_not_allow' );
	}
	if ( user_can( $user_id, 'pixva_manage_orders' ) ) {
		return array( 'pixva_manage_orders' );
	}
	$tech_id = (int) get_post_meta( $order_id, '_pixva_technician_id', true );
	if ( $tech_id && $tech_id === (int) $user_id && user_can( $user_id, 'pixva_work_orders' ) ) {
		return array( 'pixva_work_orders' );
	}
	if ( 'pixva_view_order' === $cap ) {
		$owner = (int) get_post_meta( $order_id, '_pixva_customer_id', true );
		if ( $owner && $owner === (int) $user_id ) {
			return array( 'read' );
		}
	}
	return array( 'do_not_allow' );
}
add_filter( 'map_meta_cap', 'pixva_map_meta_cap', 10, 4 );

/**
 * Role of the current user for UI decisions (not for authorisation).
 *
 * @param int $user_id User id (0 = current).
 * @return string manager|technician|editor|customer|guest
 */
function pixva_user_kind( $user_id = 0 ) {
	$user_id = $user_id ? (int) $user_id : get_current_user_id();
	if ( ! $user_id ) {
		return 'guest';
	}
	if ( user_can( $user_id, 'pixva_manage_orders' ) ) {
		return 'manager';
	}
	if ( user_can( $user_id, 'pixva_work_orders' ) ) {
		return 'technician';
	}
	if ( user_can( $user_id, 'pixva_view_content_health' ) ) {
		return 'editor';
	}
	return 'customer';
}

/**
 * Keep customers and technicians out of wp-admin (they have front-end
 * screens). AJAX/admin-post requests are allowed through.
 *
 * @return void
 */
function pixva_restrict_admin_area() {
	if ( wp_doing_ajax() || ( defined( 'DOING_CRON' ) && DOING_CRON ) ) {
		return;
	}
	$script = isset( $_SERVER['SCRIPT_NAME'] ) ? basename( sanitize_text_field( wp_unslash( $_SERVER['SCRIPT_NAME'] ) ) ) : '';
	if ( in_array( $script, array( 'admin-post.php', 'async-upload.php' ), true ) ) {
		return;
	}
	if ( is_user_logged_in() && ! current_user_can( 'edit_posts' ) && ! current_user_can( 'pixva_manage_orders' ) ) {
		$kind = pixva_user_kind();
		wp_safe_redirect( 'technician' === $kind ? pixva_route_url( 'dashboard' ) : pixva_route_url( 'account' ) );
		exit;
	}
}
add_action( 'admin_init', 'pixva_restrict_admin_area' );

/**
 * Hide the admin bar for customers/technicians on the front end.
 *
 * @param bool $show Show.
 * @return bool
 */
function pixva_admin_bar_visibility( $show ) {
	if ( is_user_logged_in() && ! current_user_can( 'edit_posts' ) && ! current_user_can( 'pixva_manage_orders' ) ) {
		return false;
	}
	return $show;
}
add_filter( 'show_admin_bar', 'pixva_admin_bar_visibility' );

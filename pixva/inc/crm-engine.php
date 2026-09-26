<?php
/**
 * موتور اتوماسیون چرخه تعمیرات و CRM پیکسوا (inc/crm-engine.php)
 *
 * این پرونده «منطق» سامانه است (بدون خروجی بصری):
 *  1. نقش‌های کاربردی: pixva_manager (دیسپچر) و pixva_technician (تعمیرکار) + قابلیت‌های اختصاصی.
 *  2. خط لوله وضعیت سفارش: در انتظار بررسی ← تخصیص به تعمیرکار ← در حال تعمیر ← تست کیفیت ← آماده تحویل ← تحویل شد.
 *  3. تخصیص سفارش به تعمیرکار، گزارش فنی (قطعات، زمان، دستمزد) و صدور گارانتی دیجیتال.
 *  4. درگاه پیامک واقعی (کاوه‌نگار / SMS.ir / ملی‌پیامک) با هوک‌های قابل توسعه.
 *  5. اندپوینت‌های REST (pixva/v1/crm/*) و AJAX برای پنل‌ها.
 *  6. گزارش فعالیت (Activity Log) هر پرونده برای حسابرسی داخلی.
 *
 * رابط کاربری پنل‌ها در inc/crm-dispatcher.php (مدیر) و inc/crm-technician.php (تعمیرکار)
 * و موتور گارانتی/هولوگرام در inc/crm-warranty.php قرار دارد.
 *
 * @package Pixva
 * @since   1.4.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
 * ---------------------------------------------------------------------------
 * ۱) نقش‌ها و قابلیت‌ها
 * ---------------------------------------------------------------------------
 */

if ( ! function_exists( 'pixva_crm_capability_map' ) ) {
	/**
	 * نگاشت قابلیت‌های اختصاصی CRM به نقش‌ها.
	 *
	 * @return array<string, string[]>
	 */
	function pixva_crm_capability_map() {
		return array(
			'administrator'    => array(
				'pixva_view_all_orders',
				'pixva_dispatch_orders',
				'pixva_edit_any_order',
				'pixva_issue_warranty',
				'pixva_manage_crm',
				'pixva_view_crm_panel',
			),
			'pixva_manager'    => array(
				'pixva_view_all_orders',
				'pixva_dispatch_orders',
				'pixva_edit_any_order',
				'pixva_issue_warranty',
				'pixva_manage_crm',
				'pixva_view_crm_panel',
			),
			'pixva_technician' => array(
				'pixva_edit_assigned_order',
				'pixva_issue_warranty',
				'pixva_view_crm_panel',
			),
		);
	}
}

if ( ! function_exists( 'pixva_crm_register_roles' ) ) {
	/**
	 * ثبت نقش مدیر عملیات (دیسپچر) و همگام‌سازی قابلیت‌های CRM روی نقش‌ها.
	 *
	 * @return void
	 */
	function pixva_crm_register_roles() {
		add_role(
			'pixva_manager',
			__( 'مدیر عملیات / دیسپچر پیکسوا', 'pixva' ),
			array(
				'read'         => true,
				'edit_posts'   => true,
				'delete_posts' => false,
				'upload_files' => true,
			)
		);

		pixva_crm_sync_capabilities();
	}
}
add_action( 'init', 'pixva_crm_register_roles', 20 );

if ( ! function_exists( 'pixva_crm_sync_capabilities' ) ) {
	/**
	 * اعمال قابلیت‌های CRM روی نقش‌های موجود (پس از نصب/به‌روزرسانی پوسته).
	 *
	 * @return void
	 */
	function pixva_crm_sync_capabilities() {
		foreach ( pixva_crm_capability_map() as $role_key => $caps ) {
			$role = get_role( $role_key );
			if ( ! $role ) {
				continue;
			}
			foreach ( $caps as $cap ) {
				$role->add_cap( $cap );
			}
		}

		// نقش تعمیرکار حداقل‌های پایه را هم داشته باشد.
		$technician = get_role( 'pixva_technician' );
		if ( $technician ) {
			$technician->add_cap( 'read' );
			$technician->add_cap( 'upload_files' );
		}
	}
}
add_action( 'after_switch_theme', 'pixva_crm_sync_capabilities' );
add_action( 'pixva_after_activation', 'pixva_crm_sync_capabilities' );

if ( ! function_exists( 'pixva_crm_map_meta_caps' ) ) {
	/**
	 * قابلیت‌های وابسته به پرونده: تعمیرکار فقط روی پرونده‌های تخصیص‌یافته به خودش.
	 *
	 * @param string[] $caps    قابلیت‌های درخواست‌شده.
	 * @param string   $cap     نام قابلیت.
	 * @param int      $user_id شناسه کاربر.
	 * @param array    $args    آرگومان‌ها (args[0] = شناسه پرونده).
	 * @return string[]
	 */
	function pixva_crm_map_meta_caps( $caps, $cap, $user_id, $args ) {
		if ( 'pixva_edit_order' !== $cap && 'pixva_issue_order_warranty' !== $cap ) {
			return $caps;
		}

		$order_id = isset( $args[0] ) ? (int) $args[0] : 0;
		if ( ! $order_id ) {
			return array( 'do_not_allow' );
		}

		$user = get_userdata( $user_id );
		if ( ! $user ) {
			return array( 'do_not_allow' );
		}

		if ( $user->has_cap( 'pixva_edit_any_order' ) ) {
			return array( 'read' );
		}

		$assigned = (int) get_post_meta( $order_id, '_pixva_order_technician', true );
		if ( $assigned && $assigned === (int) $user_id && $user->has_cap( 'pixva_edit_assigned_order' ) ) {
			return array( 'read' );
		}

		return array( 'do_not_allow' );
	}
}
add_filter( 'map_meta_cap', 'pixva_crm_map_meta_caps', 10, 4 );

if ( ! function_exists( 'pixva_crm_can_dispatch' ) ) {
	/**
	 * آیا کاربر جاری اجازه تخصیص/تغییر وضعیت هر پرونده را دارد؟
	 *
	 * @return bool
	 */
	function pixva_crm_can_dispatch() {
		return current_user_can( 'pixva_dispatch_orders' );
	}
}

if ( ! function_exists( 'pixva_crm_can_work_on' ) ) {
	/**
	 * آیا کاربر جاری روی این پرونده اجازه کار دارد؟
	 *
	 * @param int $order_id شناسه پرونده.
	 * @return bool
	 */
	function pixva_crm_can_work_on( $order_id ) {
		return current_user_can( 'pixva_edit_order', (int) $order_id );
	}
}

/*
 * ---------------------------------------------------------------------------
 * ۲) خط لوله وضعیت‌ها
 * ---------------------------------------------------------------------------
 */

if ( ! function_exists( 'pixva_crm_statuses' ) ) {
	/**
	 * خط لوله شش‌مرحله‌ای چرخه تعمیر (جایگزین تایم‌لاین قدیمی).
	 *
	 * @return array<string, string>
	 */
	function pixva_crm_statuses() {
		return array(
			'pending'   => __( 'در انتظار بررسی', 'pixva' ),
			'assigned'  => __( 'تخصیص به تعمیرکار', 'pixva' ),
			'repairing' => __( 'در حال تعمیر', 'pixva' ),
			'qc'        => __( 'تست کیفیت', 'pixva' ),
			'ready'     => __( 'آماده تحویل', 'pixva' ),
			'delivered' => __( 'تحویل شد', 'pixva' ),
		);
	}
}

if ( ! function_exists( 'pixva_crm_status_legacy_map' ) ) {
	/**
	 * نگاشت وضعیت‌های قدیمی پرونده‌های موجود به خط لوله جدید.
	 *
	 * @return array<string, string>
	 */
	function pixva_crm_status_legacy_map() {
		return array(
			'received'  => 'pending',
			'diagnosed' => 'assigned',
			'parts'     => 'repairing',
			'repairing' => 'repairing',
			'testing'   => 'qc',
			'ready'     => 'ready',
		);
	}
}

if ( ! function_exists( 'pixva_crm_normalize_status' ) ) {
	/**
	 * نرمال‌سازی کلید وضعیت (سازگاری با داده‌های قدیمی).
	 *
	 * @param string $status کلید وضعیت.
	 * @return string
	 */
	function pixva_crm_normalize_status( $status ) {
		$status   = (string) $status;
		$legacy   = pixva_crm_status_legacy_map();
		$statuses = pixva_crm_statuses();

		if ( isset( $statuses[ $status ] ) ) {
			return $status;
		}
		if ( isset( $legacy[ $status ] ) ) {
			return $legacy[ $status ];
		}
		return 'pending';
	}
}

if ( ! function_exists( 'pixva_crm_replace_order_statuses' ) ) {
	/**
	 * جایگزینی تایم‌لاین قدیمی پرونده با خط لوله CRM.
	 *
	 * @param array $statuses وضعیت‌های ثبت‌شده در پوسته.
	 * @return array<string, string>
	 */
	function pixva_crm_replace_order_statuses( $statuses ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
		return pixva_crm_statuses();
	}
}
add_filter( 'pixva_order_statuses', 'pixva_crm_replace_order_statuses', 20 );

if ( ! function_exists( 'pixva_crm_status_label' ) ) {
	/**
	 * برچسب فارسی یک وضعیت.
	 *
	 * @param string $status کلید وضعیت.
	 * @return string
	 */
	function pixva_crm_status_label( $status ) {
		$statuses = pixva_crm_statuses();
		$key      = pixva_crm_normalize_status( $status );
		return isset( $statuses[ $key ] ) ? $statuses[ $key ] : $key;
	}
}

if ( ! function_exists( 'pixva_crm_transitions' ) ) {
	/**
	 * گذارهای مجاز وضعیت (جلوگرد؛ پرش فقط برای مدیر).
	 *
	 * @return array<string, string[]>
	 */
	function pixva_crm_transitions() {
		return array(
			'pending'   => array( 'assigned' ),
			'assigned'  => array( 'repairing', 'pending' ),
			'repairing' => array( 'qc', 'assigned' ),
			'qc'        => array( 'ready', 'repairing' ),
			'ready'     => array( 'delivered', 'qc' ),
			'delivered' => array(),
		);
	}
}

if ( ! function_exists( 'pixva_crm_can_transition' ) ) {
	/**
	 * آیا تغییر وضعیت مجاز است؟
	 *
	 * @param string $from وضعیت فعلی.
	 * @param string $to   وضعیت مقصد.
	 * @return bool
	 */
	function pixva_crm_can_transition( $from, $to ) {
		$from = pixva_crm_normalize_status( $from );
		$to   = pixva_crm_normalize_status( $to );

		if ( $from === $to ) {
			return true;
		}

		$map = pixva_crm_transitions();

		// مدیر عملیات می‌تواند وضعیت را آزادانه جابه‌جا کند (اصلاح خطای انسانی).
		if ( pixva_crm_can_dispatch() ) {
			return isset( $map[ $to ] );
		}

		return isset( $map[ $from ] ) && in_array( $to, $map[ $from ], true );
	}
}

/*
 * ---------------------------------------------------------------------------
 * ۳) داده پرونده، گزارش فعالیت و تعمیرکاران
 * ---------------------------------------------------------------------------
 */

if ( ! function_exists( 'pixva_crm_order' ) ) {
	/**
	 * بسته داده کامل یک پرونده (متای پایه + CRM + گزارش + گارانتی).
	 *
	 * @param int|WP_Post $order پرونده یا شناسه آن.
	 * @return array<string, mixed>|null
	 */
	function pixva_crm_order( $order ) {
		$order = $order instanceof WP_Post ? $order : get_post( (int) $order );
		if ( ! $order instanceof WP_Post || 'pixva_orders' !== $order->post_type ) {
			return null;
		}

		$id       = (int) $order->ID;
		$status   = pixva_crm_normalize_status( get_post_meta( $id, '_pixva_order_status', true ) );
		$customer = get_post_meta( $id, '_pixva_order_customer', true );
		$report   = get_post_meta( $id, '_pixva_order_report', true );
		$warranty = get_post_meta( $id, '_pixva_order_warranty', true );

		$customer = is_array( $customer ) ? $customer : array();
		$report   = is_array( $report ) ? $report : array();
		$warranty = is_array( $warranty ) ? $warranty : array();

		$technician = (int) get_post_meta( $id, '_pixva_order_technician', true );

		return array(
			'id'         => $id,
			'code'       => (string) get_post_meta( $id, '_pixva_order_code', true ),
			'phone'      => (string) get_post_meta( $id, '_pixva_order_phone', true ),
			'status'     => $status,
			'statusLabel'=> pixva_crm_status_label( $status ),
			'brand'      => (string) get_post_meta( $id, '_pixva_order_brand', true ),
			'model'      => (string) get_post_meta( $id, '_pixva_order_model', true ),
			'problem'    => (string) get_post_meta( $id, '_pixva_order_problem', true ),
			'estimate'   => (string) get_post_meta( $id, '_pixva_order_estimate', true ),
			'notes'      => (string) get_post_meta( $id, '_pixva_order_notes', true ),
			'name'       => (string) get_post_meta( $id, '_pixva_order_name', true ),
			'created'    => (int) get_post_timestamp( $order ),
			'technician' => $technician,
			'techName'   => $technician ? pixva_crm_technician_label( $technician ) : '',
			'assignedAt' => (int) get_post_meta( $id, '_pixva_order_assigned_at', true ),
			'source'     => (string) get_post_meta( $id, '_pixva_order_source', true ),
			'customer'   => wp_parse_args(
				$customer,
				array(
					'name'     => '',
					'phone'    => '',
					'address'  => '',
					'zone'     => '',
					'preferred'=> '',
					'notes'    => '',
				)
			),
			'report'     => pixva_crm_report_shape( $report ),
			'warranty'   => pixva_crm_warranty_shape( $warranty ),
			'activity'   => pixva_crm_activity( $id ),
		);
	}
}

if ( ! function_exists( 'pixva_crm_report_shape' ) ) {
	/**
	 * شکل استاندارد گزارش فنی تعمیرکار.
	 *
	 * @param array $report داده خام.
	 * @return array<string, mixed>
	 */
	function pixva_crm_report_shape( $report ) {
		$report = is_array( $report ) ? $report : array();
		$parts  = isset( $report['parts'] ) && is_array( $report['parts'] ) ? $report['parts'] : array();

		$clean_parts = array();
		foreach ( $parts as $part ) {
			if ( ! is_array( $part ) ) {
				continue;
			}
			$clean_parts[] = array(
				'name'   => isset( $part['name'] ) ? (string) $part['name'] : '',
				'spec'   => isset( $part['spec'] ) ? (string) $part['spec'] : '',
				'serial' => isset( $part['serial'] ) ? (string) $part['serial'] : '',
				'qty'    => isset( $part['qty'] ) ? max( 1, (int) $part['qty'] ) : 1,
				'price'  => isset( $part['price'] ) ? max( 0, (int) $part['price'] ) : 0,
			);
		}

		return array(
			'parts'     => $clean_parts,
			'diagnosis' => isset( $report['diagnosis'] ) ? (string) $report['diagnosis'] : '',
			'actions'   => isset( $report['actions'] ) ? (string) $report['actions'] : '',
			'minutes'   => isset( $report['minutes'] ) ? max( 0, (int) $report['minutes'] ) : 0,
			'labor'     => isset( $report['labor'] ) ? max( 0, (int) $report['labor'] ) : 0,
			'partCost'  => isset( $report['partCost'] ) ? max( 0, (int) $report['partCost'] ) : 0,
			'total'     => isset( $report['total'] ) ? max( 0, (int) $report['total'] ) : 0,
			'qcPassed'  => ! empty( $report['qcPassed'] ),
			'savedAt'   => isset( $report['savedAt'] ) ? (int) $report['savedAt'] : 0,
			'savedBy'   => isset( $report['savedBy'] ) ? (int) $report['savedBy'] : 0,
		);
	}
}

if ( ! function_exists( 'pixva_crm_warranty_shape' ) ) {
	/**
	 * شکل استاندارد رکورد گارانتی دیجیتال.
	 *
	 * @param array $warranty داده خام.
	 * @return array<string, mixed>
	 */
	function pixva_crm_warranty_shape( $warranty ) {
		$warranty = is_array( $warranty ) ? $warranty : array();
		return array(
			'serial'     => isset( $warranty['serial'] ) ? (string) $warranty['serial'] : '',
			'issuedAt'   => isset( $warranty['issuedAt'] ) ? (int) $warranty['issuedAt'] : 0,
			'expiresAt'  => isset( $warranty['expiresAt'] ) ? (int) $warranty['expiresAt'] : 0,
			'days'       => isset( $warranty['days'] ) ? (int) $warranty['days'] : 0,
			'technician' => isset( $warranty['technician'] ) ? (int) $warranty['technician'] : 0,
			'techName'   => isset( $warranty['techName'] ) ? (string) $warranty['techName'] : '',
			'hash'       => isset( $warranty['hash'] ) ? (string) $warranty['hash'] : '',
			'covers'     => isset( $warranty['covers'] ) && is_array( $warranty['covers'] ) ? $warranty['covers'] : array(),
			'verifyUrl'  => isset( $warranty['verifyUrl'] ) ? (string) $warranty['verifyUrl'] : '',
		);
	}
}

if ( ! function_exists( 'pixva_crm_log' ) ) {
	/**
	 * ثبت یک رویداد در گزارش فعالیت پرونده.
	 *
	 * @param int    $order_id شناسه پرونده.
	 * @param string $event    کلید رویداد.
	 * @param string $label    شرح فارسی.
	 * @param array  $meta     داده تکمیلی.
	 * @return void
	 */
	function pixva_crm_log( $order_id, $event, $label, $meta = array() ) {
		$order_id = (int) $order_id;
		if ( ! $order_id ) {
			return;
		}

		$log   = get_post_meta( $order_id, '_pixva_order_activity', true );
		$log   = is_array( $log ) ? $log : array();
		$user  = get_current_user_id();
		$name  = $user ? pixva_crm_user_label( $user ) : __( 'سامانه خودکار', 'pixva' );

		$log[] = array(
			'at'    => time(),
			'by'    => $user,
			'who'   => $name,
			'event' => (string) $event,
			'label' => (string) $label,
			'meta'  => is_array( $meta ) ? $meta : array(),
		);

		// سقف ۱۲۰ رویداد برای هر پرونده (جلوگیری از رشد بی‌رویه متا).
		if ( count( $log ) > 120 ) {
			$log = array_slice( $log, -120 );
		}

		update_post_meta( $order_id, '_pixva_order_activity', $log );

		/**
		 * هوک ثبت رویداد پرونده.
		 *
		 * @param int    $order_id شناسه پرونده.
		 * @param string $event    کلید رویداد.
		 * @param array  $meta     داده تکمیلی.
		 */
		do_action( 'pixva_crm_logged', $order_id, $event, $meta );
	}
}

if ( ! function_exists( 'pixva_crm_activity' ) ) {
	/**
	 * گزارش فعالیت پرونده (جدیدترین ابتدا).
	 *
	 * @param int $order_id شناسه پرونده.
	 * @return array<int, array<string, mixed>>
	 */
	function pixva_crm_activity( $order_id ) {
		$log = get_post_meta( (int) $order_id, '_pixva_order_activity', true );
		if ( ! is_array( $log ) ) {
			return array();
		}
		return array_reverse( $log );
	}
}

if ( ! function_exists( 'pixva_crm_user_label' ) ) {
	/**
	 * نام نمایشی یک کاربر (ترجیحاً نام کامل).
	 *
	 * @param int $user_id شناسه کاربر.
	 * @return string
	 */
	function pixva_crm_user_label( $user_id ) {
		$user = get_userdata( (int) $user_id );
		if ( ! $user ) {
			return '';
		}
		$name = trim( $user->first_name . ' ' . $user->last_name );
		if ( '' === $name ) {
			$name = $user->display_name;
		}
		return (string) $name;
	}
}

if ( ! function_exists( 'pixva_crm_technician_label' ) ) {
	/**
	 * نام تعمیرکار به‌همراه شناسه کاربری.
	 *
	 * @param int $user_id شناسه کاربر.
	 * @return string
	 */
	function pixva_crm_technician_label( $user_id ) {
		$label = pixva_crm_user_label( $user_id );
		if ( '' === $label ) {
			return __( 'تعمیرکار نامشخص', 'pixva' );
		}
		return $label;
	}
}

if ( ! function_exists( 'pixva_crm_technicians' ) ) {
	/**
	 * فهرست تعمیرکاران و مدیران عملیات قابل تخصیص.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	function pixva_crm_technicians() {
		$roles = array( 'pixva_technician', 'pixva_manager' );

		/**
		 * فیلتر نقش‌های مجاز برای تخصیص پرونده.
		 *
		 * @param string[] $roles کلید نقش‌ها.
		 */
		$roles = apply_filters( 'pixva_crm_technician_roles', $roles );

		$users = get_users(
			array(
				'role__in' => $roles,
				'orderby'  => 'display_name',
				'order'    => 'ASC',
				'number'   => 200,
			)
		);

		$list = array();
		foreach ( $users as $user ) {
			$id = (int) $user->ID;

			// مدیران فقط در صورتی فهرست شوند که نقش تعمیرکار هم داشته باشند.
			if ( ! in_array( 'pixva_technician', (array) $user->roles, true ) && ! current_user_can( 'pixva_dispatch_orders' ) ) {
				continue;
			}

			$list[ $id ] = array(
				'id'       => $id,
				'name'     => pixva_crm_user_label( $id ),
				'login'    => $user->user_login,
				'phone'    => (string) get_user_meta( $id, 'pixva_tech_phone', true ),
				'skill'    => (string) get_user_meta( $id, 'pixva_tech_skill', true ),
				'openJobs' => pixva_crm_technician_load( $id ),
			);
		}

		/**
		 * فیلتر فهرست تعمیرکاران قابل تخصیص.
		 *
		 * @param array $list فهرست تعمیرکاران.
		 */
		return apply_filters( 'pixva_crm_technicians', $list );
	}
}

if ( ! function_exists( 'pixva_crm_technician_load' ) ) {
	/**
	 * شمار پرونده‌های باز (تخصیص‌یافته و ناتمام) یک تعمیرکار.
	 *
	 * @param int $user_id شناسه تعمیرکار.
	 * @return int
	 */
	function pixva_crm_technician_load( $user_id ) {
		$user_id = (int) $user_id;
		if ( ! $user_id ) {
			return 0;
		}

		$open = array( 'assigned', 'repairing', 'qc' );
		$ids  = get_posts(
			array(
				'post_type'      => 'pixva_orders',
				'post_status'    => array( 'private', 'publish' ),
				'posts_per_page' => 200,
				'fields'         => 'ids',
				'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					'relation' => 'AND',
					array(
						'key'   => '_pixva_order_technician',
						'value' => $user_id,
					),
					array(
						'key'     => '_pixva_order_status',
						'value'   => array_merge( $open, array_keys( pixva_crm_status_legacy_map() ) ),
						'compare' => 'IN',
					),
				),
			)
		);

		$count = 0;
		foreach ( (array) $ids as $id ) {
			$status = pixva_crm_normalize_status( get_post_meta( (int) $id, '_pixva_order_status', true ) );
			if ( in_array( $status, $open, true ) ) {
				$count++;
			}
		}

		return $count;
	}
}

if ( ! function_exists( 'pixva_crm_orders' ) ) {
	/**
	 * پرس‌وجوی پرونده‌ها با فیلتر وضعیت/تعمیرکار/جست‌وجو.
	 *
	 * @param array $args گزینه‌ها: status, technician, search, limit, page, orderby.
	 * @return array{items: WP_Post[], total: int, pages: int}
	 */
	function pixva_crm_orders( $args = array() ) {
		$args = wp_parse_args(
			$args,
			array(
				'status'     => '',
				'technician' => 0,
				'search'     => '',
				'limit'      => 20,
				'page'       => 1,
				'orderby'    => 'date',
			)
		);

		$limit = max( 1, min( 100, (int) $args['limit'] ) );
		$page  = max( 1, (int) $args['page'] );

		$meta = array();

		if ( '' !== $args['status'] ) {
			$keys   = array_keys( pixva_crm_statuses() );
			$target = pixva_crm_normalize_status( $args['status'] );
			// وضعیت‌های قدیمی معادل هم در پرس‌وجو لحاظ می‌شوند.
			$values = array( $target );
			foreach ( pixva_crm_status_legacy_map() as $legacy => $mapped ) {
				if ( $mapped === $target && ! in_array( $legacy, $values, true ) ) {
					$values[] = $legacy;
				}
			}
			unset( $keys );
			$meta[] = array(
				'key'     => '_pixva_order_status',
				'value'   => $values,
				'compare' => 'IN',
			);
		}

		if ( (int) $args['technician'] > 0 ) {
			$meta[] = array(
				'key'   => '_pixva_order_technician',
				'value' => (int) $args['technician'],
			);
		}

		$query = new WP_Query(
			array(
				'post_type'      => 'pixva_orders',
				'post_status'    => array( 'private', 'publish' ),
				'posts_per_page' => $limit,
				'paged'          => $page,
				'orderby'        => $args['orderby'],
				'order'          => 'DESC',
				's'              => (string) $args['search'],
				'meta_query'     => $meta ? $meta : '', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				'no_found_rows'  => false,
			)
		);

		return array(
			'items' => $query->posts,
			'total' => (int) $query->found_posts,
			'pages' => (int) $query->max_num_pages,
			'page'  => $page,
		);
	}
}

if ( ! function_exists( 'pixva_crm_stats' ) ) {
	/**
	 * آمار زنده کارگاه برای پنل مدیر و بخش شفافیت لندینگ.
	 *
	 * @return array<string, mixed>
	 */
	function pixva_crm_stats() {
		$statuses = pixva_crm_statuses();
		$counts   = array();
		foreach ( array_keys( $statuses ) as $key ) {
			$counts[ $key ] = 0;
		}

		$revenue   = 0;
		$minutes   = 0;
		$withReport = 0;
		$warranties = 0;

		$posts = get_posts(
			array(
				'post_type'      => 'pixva_orders',
				'post_status'    => array( 'private', 'publish' ),
				'posts_per_page' => 500,
				'fields'         => 'ids',
				'orderby'        => 'date',
				'order'          => 'DESC',
			)
		);

		foreach ( (array) $posts as $id ) {
			$status = pixva_crm_normalize_status( get_post_meta( (int) $id, '_pixva_order_status', true ) );
			if ( isset( $counts[ $status ] ) ) {
				$counts[ $status ]++;
			}

			$report = get_post_meta( (int) $id, '_pixva_order_report', true );
			if ( is_array( $report ) && ! empty( $report['savedAt'] ) ) {
				$shaped = pixva_crm_report_shape( $report );
				$withReport++;
				$revenue += (int) $shaped['total'];
				$minutes += (int) $shaped['minutes'];
			}

			$warranty = get_post_meta( (int) $id, '_pixva_order_warranty', true );
			if ( is_array( $warranty ) && ! empty( $warranty['serial'] ) ) {
				$warranties++;
			}
		}

		return array(
			'total'       => count( $posts ),
			'counts'      => $counts,
			'revenue'     => $revenue,
			'avgMinutes'  => $withReport ? (int) round( $minutes / $withReport ) : 0,
			'reports'     => $withReport,
			'warranties'  => $warranties,
			'technicians' => count( pixva_crm_technicians() ),
			'unassigned'  => isset( $counts['pending'] ) ? $counts['pending'] : 0,
		);
	}
}

/*
 * ---------------------------------------------------------------------------
 * ۴) عملیات اصلی: تخصیص، تغییر وضعیت، گزارش فنی، صدور گارانتی
 * ---------------------------------------------------------------------------
 */

if ( ! function_exists( 'pixva_crm_assign' ) ) {
	/**
	 * تخصیص پرونده به یک تعمیرکار.
	 *
	 * @param int $order_id شناسه پرونده.
	 * @param int $tech_id  شناسه تعمیرکار (۰ = لغو تخصیص).
	 * @return array<string, mixed>|WP_Error
	 */
	function pixva_crm_assign( $order_id, $tech_id ) {
		$order_id = (int) $order_id;
		$tech_id  = (int) $tech_id;

		if ( ! pixva_crm_can_dispatch() ) {
			return new WP_Error( 'pixva_crm_forbidden', __( 'اجازه تخصیص پرونده را ندارید.', 'pixva' ), array( 'status' => 403 ) );
		}

		$order = get_post( $order_id );
		if ( ! $order instanceof WP_Post || 'pixva_orders' !== $order->post_type ) {
			return new WP_Error( 'pixva_crm_missing', __( 'پرونده پیدا نشد.', 'pixva' ), array( 'status' => 404 ) );
		}

		if ( $tech_id ) {
			$technicians = pixva_crm_technicians();
			if ( ! isset( $technicians[ $tech_id ] ) ) {
				return new WP_Error( 'pixva_crm_bad_tech', __( 'تعمیرکار انتخاب‌شده معتبر نیست.', 'pixva' ), array( 'status' => 422 ) );
			}
		}

		update_post_meta( $order_id, '_pixva_order_technician', $tech_id );
		update_post_meta( $order_id, '_pixva_order_assigned_at', $tech_id ? time() : 0 );

		$current = pixva_crm_normalize_status( get_post_meta( $order_id, '_pixva_order_status', true ) );
		if ( $tech_id && in_array( $current, array( 'pending' ), true ) ) {
			pixva_crm_set_status( $order_id, 'assigned', __( 'تخصیص به تعمیرکار از پنل دیسپچ', 'pixva' ) );
		} elseif ( ! $tech_id ) {
			pixva_crm_log( $order_id, 'unassigned', __( 'تخصیص پرونده لغو شد.', 'pixva' ) );
		} else {
			pixva_crm_log(
				$order_id,
				'reassigned',
				sprintf(
					/* translators: %s: نام تعمیرکار */
					__( 'پرونده به «%s» تخصیص داده شد.', 'pixva' ),
					pixva_crm_technician_label( $tech_id )
				)
			);
		}

		if ( $tech_id ) {
			pixva_crm_notify( 'assigned', $order_id );
		}

		/**
		 * هوک پس از تخصیص پرونده.
		 *
		 * @param int $order_id شناسه پرونده.
		 * @param int $tech_id  شناسه تعمیرکار.
		 */
		do_action( 'pixva_crm_assigned', $order_id, $tech_id );

		return pixva_crm_order( $order_id );
	}
}

if ( ! function_exists( 'pixva_crm_set_status' ) ) {
	/**
	 * تغییر وضعیت پرونده با اعتبارسنجی گذار، ثبت زمان و اطلاع‌رسانی.
	 *
	 * @param int    $order_id شناسه پرونده.
	 * @param string $status   وضعیت مقصد.
	 * @param string $note     یادداشت داخلی.
	 * @return array<string, mixed>|WP_Error
	 */
	function pixva_crm_set_status( $order_id, $status, $note = '' ) {
		$order_id = (int) $order_id;
		$status   = pixva_crm_normalize_status( $status );

		$order = get_post( $order_id );
		if ( ! $order instanceof WP_Post || 'pixva_orders' !== $order->post_type ) {
			return new WP_Error( 'pixva_crm_missing', __( 'پرونده پیدا نشد.', 'pixva' ), array( 'status' => 404 ) );
		}

		$current = pixva_crm_normalize_status( get_post_meta( $order_id, '_pixva_order_status', true ) );

		if ( $current !== $status && ! pixva_crm_can_transition( $current, $status ) ) {
			return new WP_Error(
				'pixva_crm_transition',
				sprintf(
					/* translators: 1: وضعیت فعلی، 2: وضعیت مقصد */
					__( 'تغییر وضعیت از «%1$s» به «%2$s» مجاز نیست.', 'pixva' ),
					pixva_crm_status_label( $current ),
					pixva_crm_status_label( $status )
				),
				array( 'status' => 422 )
			);
		}

		update_post_meta( $order_id, '_pixva_order_status', $status );

		// زمان‌بندی مراحل (JSON) برای تایم‌لاین مشتری.
		$steps = function_exists( 'pixva_order_steps' ) ? pixva_order_steps( $order ) : array();
		if ( ! isset( $steps[ $status ] ) ) {
			$steps[ $status ] = time();
		}
		update_post_meta( $order_id, '_pixva_order_steps', wp_json_encode( $steps ) );

		if ( '' !== $note ) {
			update_post_meta( $order_id, '_pixva_order_notes', sanitize_textarea_field( $note ) );
		}

		pixva_crm_log(
			$order_id,
			'status:' . $status,
			sprintf(
				/* translators: %s: برچسب وضعیت */
				__( 'وضعیت پرونده به «%s» تغییر کرد.', 'pixva' ),
				pixva_crm_status_label( $status )
			)
		);

		// تحویل دستگاه = مبنای شروع گارانتی.
		if ( 'delivered' === $status ) {
			update_post_meta( $order_id, '_pixva_order_delivered', time() );
		}

		pixva_crm_notify( 'status_' . $status, $order_id );

		/**
		 * هوک تغییر وضعیت پرونده.
		 *
		 * @param int    $order_id شناسه پرونده.
		 * @param string $status   وضعیت جدید.
		 * @param string $current  وضعیت قبلی.
		 */
		do_action( 'pixva_crm_status_changed', $order_id, $status, $current );

		return pixva_crm_order( $order_id );
	}
}

if ( ! function_exists( 'pixva_crm_save_report' ) ) {
	/**
	 * ذخیره گزارش فنی تعمیرکار (قطعات، شرح، زمان، دستمزد).
	 *
	 * @param int   $order_id شناسه پرونده.
	 * @param array $data     داده فرم گزارش.
	 * @return array<string, mixed>|WP_Error
	 */
	function pixva_crm_save_report( $order_id, $data ) {
		$order_id = (int) $order_id;

		if ( ! pixva_crm_can_work_on( $order_id ) ) {
			return new WP_Error( 'pixva_crm_forbidden', __( 'این پرونده به شما تخصیص داده نشده است.', 'pixva' ), array( 'status' => 403 ) );
		}

		$data = is_array( $data ) ? $data : array();

		$parts_raw = isset( $data['parts'] ) && is_array( $data['parts'] ) ? $data['parts'] : array();
		$parts     = array();
		foreach ( $parts_raw as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			$name = isset( $row['name'] ) ? sanitize_text_field( wp_unslash( $row['name'] ) ) : '';
			if ( '' === $name ) {
				continue;
			}
			$parts[] = array(
				'name'   => $name,
				'spec'   => isset( $row['spec'] ) ? sanitize_text_field( wp_unslash( $row['spec'] ) ) : '',
				'serial' => isset( $row['serial'] ) ? sanitize_text_field( wp_unslash( $row['serial'] ) ) : '',
				'qty'    => isset( $row['qty'] ) ? max( 1, (int) $row['qty'] ) : 1,
				'price'  => isset( $row['price'] ) ? max( 0, (int) $row['price'] ) : 0,
			);
		}

		$part_cost = 0;
		foreach ( $parts as $part ) {
			$part_cost += (int) $part['price'] * (int) $part['qty'];
		}

		$labor = isset( $data['labor'] ) ? max( 0, (int) $data['labor'] ) : 0;

		$report = array(
			'parts'     => $parts,
			'diagnosis' => isset( $data['diagnosis'] ) ? sanitize_textarea_field( wp_unslash( $data['diagnosis'] ) ) : '',
			'actions'   => isset( $data['actions'] ) ? sanitize_textarea_field( wp_unslash( $data['actions'] ) ) : '',
			'minutes'   => isset( $data['minutes'] ) ? max( 0, (int) $data['minutes'] ) : 0,
			'labor'     => $labor,
			'partCost'  => $part_cost,
			'total'     => $part_cost + $labor,
			'qcPassed'  => ! empty( $data['qc_passed'] ),
			'savedAt'   => time(),
			'savedBy'   => get_current_user_id(),
		);

		if ( '' === $report['diagnosis'] && empty( $parts ) ) {
			return new WP_Error( 'pixva_crm_empty_report', __( 'حداقل شرح عیب یا یک قطعه تعویضی را ثبت کنید.', 'pixva' ), array( 'status' => 422 ) );
		}

		update_post_meta( $order_id, '_pixva_order_report', $report );

		// ثبت خودکار زمان‌صرف‌شده در پرونده برای گزارش‌گیری.
		if ( $report['minutes'] > 0 ) {
			update_post_meta( $order_id, '_pixva_order_minutes', $report['minutes'] );
		}
		update_post_meta( $order_id, '_pixva_order_total', $report['total'] );

		pixva_crm_log(
			$order_id,
			'report',
			sprintf(
				/* translators: 1: تعداد قطعه، 2: مجموع هزینه */
				__( 'گزارش فنی ثبت شد (%1$s قطعه، مجموع %2$s تومان).', 'pixva' ),
				pixva_fa_num( (string) count( $parts ) ),
				function_exists( 'pixva_price' ) ? pixva_price( $report['total'] ) : pixva_fa_num( (string) $report['total'] )
			)
		);

		$current = pixva_crm_normalize_status( get_post_meta( $order_id, '_pixva_order_status', true ) );
		if ( in_array( $current, array( 'assigned' ), true ) ) {
			pixva_crm_set_status( $order_id, 'repairing', '' );
		}

		/**
		 * هوک ثبت گزارش فنی.
		 *
		 * @param int   $order_id شناسه پرونده.
		 * @param array $report   گزارش نرمال‌شده.
		 */
		do_action( 'pixva_crm_report_saved', $order_id, $report );

		return pixva_crm_order( $order_id );
	}
}

if ( ! function_exists( 'pixva_crm_issue_warranty' ) ) {
	/**
	 * تأیید نهایی گزارش و صدور گارانتی دیجیتال (سریال، انقضا، هش اصالت).
	 *
	 * @param int $order_id شناسه پرونده.
	 * @return array<string, mixed>|WP_Error
	 */
	function pixva_crm_issue_warranty( $order_id ) {
		$order_id = (int) $order_id;

		if ( ! current_user_can( 'pixva_issue_order_warranty', $order_id ) ) {
			return new WP_Error( 'pixva_crm_forbidden', __( 'اجازه صدور گارانتی این پرونده را ندارید.', 'pixva' ), array( 'status' => 403 ) );
		}

		$report = get_post_meta( $order_id, '_pixva_order_report', true );
		if ( ! is_array( $report ) || empty( $report['savedAt'] ) ) {
			return new WP_Error( 'pixva_crm_no_report', __( 'ابتدا گزارش فنی را ثبت کنید.', 'pixva' ), array( 'status' => 422 ) );
		}

		$existing = get_post_meta( $order_id, '_pixva_order_warranty', true );
		if ( is_array( $existing ) && ! empty( $existing['serial'] ) ) {
			return new WP_Error( 'pixva_crm_duplicate', __( 'گارانتی این پرونده قبلاً صادر شده است.', 'pixva' ), array( 'status' => 409 ) );
		}

		$days    = function_exists( 'pixva_warranty_days' ) ? (int) pixva_warranty_days() : 180;
		$issued  = time();
		$expires = strtotime( '+' . $days . ' days', $issued );
		$code    = (string) get_post_meta( $order_id, '_pixva_order_code', true );
		$phone   = (string) get_post_meta( $order_id, '_pixva_order_phone', true );
		$service = (string) get_post_meta( $order_id, '_pixva_order_problem', true );
		$tech    = (int) get_post_meta( $order_id, '_pixva_order_technician', true );

		$serial = 'PXV-G-' . gmdate( 'ymd', $issued ) . '-' . strtoupper( substr( wp_hash( $order_id . '|' . $issued . '|' . wp_rand() ), 2, 6 ) );
		$hash   = function_exists( 'pixva_warranty_hash' )
			? pixva_warranty_hash( $serial, $phone, $service, $expires )
			: hash( 'sha256', $serial . '|' . $phone . '|' . $expires );

		$covers = array();
		$shaped = pixva_crm_report_shape( $report );
		foreach ( $shaped['parts'] as $part ) {
			$covers[] = trim( $part['name'] . ' ' . $part['spec'] );
		}
		if ( empty( $covers ) ) {
			$covers[] = $service;
		}

		$verify = add_query_arg(
			array(
				'code'     => rawurlencode( $code ),
				'warranty' => rawurlencode( $serial ),
				'h'        => rawurlencode( substr( $hash, 0, 16 ) ),
			),
			function_exists( 'pixva_page_url' ) ? pixva_page_url( 'tracking' ) : home_url( '/' )
		);

		$warranty = array(
			'serial'     => $serial,
			'issuedAt'   => $issued,
			'expiresAt'  => $expires,
			'days'       => $days,
			'technician' => $tech,
			'techName'   => $tech ? pixva_crm_technician_label( $tech ) : __( 'کارگاه مرکزی پیکسوا', 'pixva' ),
			'hash'       => $hash,
			'covers'     => array_values( array_filter( $covers ) ),
			'verifyUrl'  => $verify,
			'issuedBy'   => get_current_user_id(),
		);

		update_post_meta( $order_id, '_pixva_order_warranty', $warranty );
		update_post_meta( $order_id, '_pixva_order_warranty_serial', $serial );

		$current = pixva_crm_normalize_status( get_post_meta( $order_id, '_pixva_order_status', true ) );
		if ( 'repairing' === $current ) {
			pixva_crm_set_status( $order_id, 'qc', '' );
			pixva_crm_set_status( $order_id, 'ready', '' );
		} elseif ( 'qc' === $current ) {
			pixva_crm_set_status( $order_id, 'ready', '' );
		}

		pixva_crm_log(
			$order_id,
			'warranty',
			sprintf(
				/* translators: 1: سریال گارانتی، 2: تعداد روز */
				__( 'گارانتی دیجیتال %1$s به مدت %2$s روز صادر شد.', 'pixva' ),
				$serial,
				pixva_fa_num( (string) $days )
			)
		);

		pixva_crm_notify( 'warranty_issued', $order_id );

		/**
		 * هوک صدور گارانتی دیجیتال.
		 *
		 * @param int   $order_id شناسه پرونده.
		 * @param array $warranty رکورد گارانتی.
		 */
		do_action( 'pixva_crm_warranty_issued', $order_id, $warranty );

		return pixva_crm_order( $order_id );
	}
}

if ( ! function_exists( 'pixva_crm_warranty_of' ) ) {
	/**
	 * بازیابی گارانتی یک پرونده (یا جست‌وجو با سریال).
	 *
	 * @param int|string $order_or_serial شناسه پرونده یا سریال گارانتی.
	 * @return array<string, mixed>|null
	 */
	function pixva_crm_warranty_of( $order_or_serial ) {
		if ( is_numeric( $order_or_serial ) ) {
			$warranty = get_post_meta( (int) $order_or_serial, '_pixva_order_warranty', true );
			return is_array( $warranty ) && ! empty( $warranty['serial'] ) ? pixva_crm_warranty_shape( $warranty ) : null;
		}

		$serial = strtoupper( sanitize_text_field( (string) $order_or_serial ) );
		if ( '' === $serial ) {
			return null;
		}

		$ids = get_posts(
			array(
				'post_type'      => 'pixva_orders',
				'post_status'    => array( 'private', 'publish' ),
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					array(
						'key'   => '_pixva_order_warranty_serial',
						'value' => $serial,
					),
				),
			)
		);

		if ( empty( $ids ) ) {
			return null;
		}

		$warranty = get_post_meta( (int) $ids[0], '_pixva_order_warranty', true );
		return is_array( $warranty ) ? pixva_crm_warranty_shape( $warranty ) : null;
	}
}

if ( ! function_exists( 'pixva_crm_save_customer' ) ) {
	/**
	 * ذخیره اطلاعات مشتری ثبت‌شده در جادوگر سفارش.
	 *
	 * @param int   $order_id شناسه پرونده.
	 * @param array $data     داده مشتری.
	 * @return void
	 */
	function pixva_crm_save_customer( $order_id, $data ) {
		$customer = array(
			'name'      => isset( $data['name'] ) ? sanitize_text_field( wp_unslash( $data['name'] ) ) : '',
			'phone'     => isset( $data['phone'] ) ? pixva_normalize_mobile( $data['phone'] ) : '',
			'address'   => isset( $data['address'] ) ? sanitize_textarea_field( wp_unslash( $data['address'] ) ) : '',
			'zone'      => isset( $data['zone'] ) ? sanitize_text_field( wp_unslash( $data['zone'] ) ) : '',
			'preferred' => isset( $data['preferred'] ) ? sanitize_text_field( wp_unslash( $data['preferred'] ) ) : '',
			'notes'     => isset( $data['notes'] ) ? sanitize_textarea_field( wp_unslash( $data['notes'] ) ) : '',
			'device'    => isset( $data['device'] ) ? sanitize_text_field( wp_unslash( $data['device'] ) ) : '',
		);

		update_post_meta( (int) $order_id, '_pixva_order_customer', $customer );

		if ( '' !== $customer['name'] ) {
			update_post_meta( (int) $order_id, '_pixva_order_name', $customer['name'] );
		}
	}
}

/*
 * ---------------------------------------------------------------------------
 * ۵) پیامک و اطلاع‌رسانی
 * ---------------------------------------------------------------------------
 */

if ( ! function_exists( 'pixva_sms_options' ) ) {
	/**
	 * تنظیمات درگاه پیامک از مرکز کنترل.
	 *
	 * @return array<string, mixed>
	 */
	function pixva_sms_options() {
		$opts = function_exists( 'pixva_control_options' ) ? pixva_control_options() : array();
		return array(
			'provider' => isset( $opts['sms_provider'] ) ? (string) $opts['sms_provider'] : 'none',
			'api_key'  => isset( $opts['sms_api_key'] ) ? (string) $opts['sms_api_key'] : '',
			'sender'   => isset( $opts['sms_sender'] ) ? (string) $opts['sms_sender'] : '',
			'template' => isset( $opts['sms_template_status'] ) ? (string) $opts['sms_template_status'] : '',
		);
	}
}

if ( ! function_exists( 'pixva_send_sms' ) ) {
	/**
	 * ارسال پیامک از طریق درگاه انتخاب‌شده در مرکز کنترل.
	 *
	 * @param string $to      شماره مقصد.
	 * @param string $message متن پیام.
	 * @param string $context کلید رویداد (order_created, assigned, ...).
	 * @return bool|WP_Error
	 */
	function pixva_send_sms( $to, $message, $context = '' ) {
		$to      = pixva_normalize_mobile( $to );
		$message = trim( (string) $message );

		if ( '' === $to || '' === $message ) {
			return new WP_Error( 'pixva_sms_empty', __( 'شماره یا متن پیامک خالی است.', 'pixva' ) );
		}

		/**
		 * فیلتر متن پیامک پیش از ارسال.
		 *
		 * @param string $message متن پیام.
		 * @param string $to      شماره مقصد.
		 * @param string $context کلید رویداد.
		 */
		$message = (string) apply_filters( 'pixva_sms_message', $message, $to, $context );

		$opts = pixva_sms_options();

		/**
		 * هوک پیش از ارسال: بازگرداندن مقدار غیر null ارسال را لغو می‌کند
		 * (برای اتصال به سرویس پیامکی دلخواه یا صف ارسال).
		 *
		 * @param bool|null $pre     نتیجه پیشاپیش.
		 * @param string    $to      شماره مقصد.
		 * @param string    $message متن پیام.
		 * @param string    $context کلید رویداد.
		 */
		$pre = apply_filters( 'pixva_pre_send_sms', null, $to, $message, $context );
		if ( null !== $pre ) {
			do_action( 'pixva_sms_sent', $to, $message, $context, (bool) $pre );
			return (bool) $pre;
		}

		if ( 'none' === $opts['provider'] || '' === $opts['api_key'] ) {
			// بدون درگاه فعال: پیام در گزارش پرونده ثبت می‌شود تا مدیر دستی ارسال کند.
			pixva_crm_log_sms( $to, $message, $context, 'queued' );

			/**
			 * هوک پیامک در صف (بدون درگاه فعال).
			 *
			 * @param string $to      شماره مقصد.
			 * @param string $message متن پیام.
			 * @param string $context کلید رویداد.
			 */
			do_action( 'pixva_sms_queued', $to, $message, $context );
			return false;
		}

		$result = pixva_sms_dispatch( $opts, $to, $message );
		pixva_crm_log_sms( $to, $message, $context, $result ? 'sent' : 'failed' );

		do_action( 'pixva_sms_sent', $to, $message, $context, $result );

		return $result;
	}
}

if ( ! function_exists( 'pixva_sms_dispatch' ) ) {
	/**
	 * فراخوانی HTTP درگاه پیامک انتخاب‌شده.
	 *
	 * @param array  $opts    تنظیمات درگاه.
	 * @param string $to      شماره مقصد.
	 * @param string $message متن پیام.
	 * @return bool
	 */
	function pixva_sms_dispatch( $opts, $to, $message ) {
		$provider = (string) $opts['provider'];
		$api_key  = (string) $opts['api_key'];
		$sender   = (string) $opts['sender'];

		switch ( $provider ) {
			case 'kavenegar':
				$url  = 'https://api.kavenegar.com/v1/' . rawurlencode( $api_key ) . '/sms/send.json';
				$body = array(
					'sender'   => $sender,
					'receptor' => $to,
					'message'  => $message,
				);
				$ok   = array( 200, 201 );
				break;

			case 'smsir':
				$url  = 'https://api.sms.ir/v1/send/verify';
				$body = array(
					'mobile' => $to,
					'templateId' => $opts['template'],
					'parameters' => array(
						array( 'name' => 'TEXT', 'value' => $message ),
					),
				);
				$ok   = array( 200 );
				break;

			case 'melipayamak':
				$url  = 'https://restapi.payamak.com/api/v2/send/verify';
				$body = array(
					'username' => $sender,
					'password' => $api_key,
					'mobile'   => $to,
					'text'     => $message,
				);
				$ok   = array( 200 );
				break;

			default:
				return false;
		}

		$response = wp_remote_post(
			$url,
			array(
				'timeout' => 12,
				'headers' => array(
					'Content-Type'  => 'application/json',
					'Accept'        => 'application/json',
					'Authorization' => 'smsir' === $provider ? 'Bearer ' . $api_key : '',
				),
				'body'    => wp_json_encode( $body ),
			)
		);

		if ( is_wp_error( $response ) ) {
			return false;
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		return in_array( $code, $ok, true );
	}
}

if ( ! function_exists( 'pixva_crm_log_sms' ) ) {
	/**
	 * ثبت پیامک‌های ارسالی در گزینه سایت (۵۰ مورد آخر) برای حسابرسی.
	 *
	 * @param string $to      شماره.
	 * @param string $message متن.
	 * @param string $context رویداد.
	 * @param string $state   وضعیت (sent|failed|queued).
	 * @return void
	 */
	function pixva_crm_log_sms( $to, $message, $context, $state ) {
		$log   = get_option( 'pixva_sms_log', array() );
		$log   = is_array( $log ) ? $log : array();
		$log[] = array(
			'at'      => time(),
			'to'      => $to,
			'message' => mb_substr( $message, 0, 240 ),
			'context' => $context,
			'state'   => $state,
		);
		if ( count( $log ) > 50 ) {
			$log = array_slice( $log, -50 );
		}
		update_option( 'pixva_sms_log', $log, false );
	}
}

if ( ! function_exists( 'pixva_crm_sms_text' ) ) {
	/**
	 * ساخت متن پیامک هر رویداد (قابل فیلتر و ویرایش از مرکز کنترل).
	 *
	 * @param string $event کلید رویداد.
	 * @param array  $order بسته داده پرونده.
	 * @return string
	 */
	function pixva_crm_sms_text( $event, $order ) {
		$brand = (string) get_bloginfo( 'name' );
		$code  = $order['code'];
		$label = $order['statusLabel'];

		$messages = array(
			'order_created'   => sprintf(
				/* translators: 1: نام سایت، 2: کد پیگیری */
				__( '%1$s: سفارش تعمیر شما ثبت شد. کد پیگیری: %2$s — برای مشاهده وضعیت به بخش پیگیری سایت مراجعه کنید.', 'pixva' ),
				$brand,
				$code
			),
			'assigned'        => sprintf(
				/* translators: 1: نام سایت، 2: کد پیگیری، 3: نام تعمیرکار */
				__( '%1$s: پرونده %2$s به تعمیرکار «%3$s» تخصیص یافت. به‌زودی برای هماهنگی تماس گرفته می‌شود.', 'pixva' ),
				$brand,
				$code,
				$order['techName']
			),
			'status_repairing'=> sprintf(
				/* translators: 1: نام سایت، 2: کد پیگیری */
				__( '%1$s: تعمیر دستگاه شما (پرونده %2$s) آغاز شد.', 'pixva' ),
				$brand,
				$code
			),
			'status_qc'       => sprintf(
				/* translators: 1: نام سایت، 2: کد پیگیری */
				__( '%1$s: دستگاه شما (پرونده %2$s) در مرحله تست کیفیت است.', 'pixva' ),
				$brand,
				$code
			),
			'status_ready'    => sprintf(
				/* translators: 1: نام سایت، 2: کد پیگیری */
				__( '%1$s: دستگاه شما آماده تحویل است (پرونده %2$s).', 'pixva' ),
				$brand,
				$code
			),
			'warranty_issued' => sprintf(
				/* translators: 1: نام سایت، 2: سریال گارانتی، 3: تعداد روز، 4: تاریخ انقضا */
				__( '%1$s: گارانتی دیجیتال شما صادر شد. سریال %2$s — اعتبار %3$s روز تا %4$s.', 'pixva' ),
				$brand,
				$order['warranty']['serial'],
				pixva_fa_num( (string) $order['warranty']['days'] ),
				pixva_fa_num( wp_date( 'Y/m/d', $order['warranty']['expiresAt'] ) )
			),
		);

		$text = isset( $messages[ $event ] ) ? $messages[ $event ] : '';

		/**
		 * فیلتر متن پیامک هر رویداد CRM.
		 *
		 * @param string $text  متن پیام.
		 * @param string $event کلید رویداد.
		 * @param array  $order بسته داده پرونده.
		 */
		return (string) apply_filters( 'pixva_crm_sms_text', $text, $event, $order );
	}
}

if ( ! function_exists( 'pixva_crm_notify' ) ) {
	/**
	 * اطلاع‌رسانی رویداد پرونده: پیامک مشتری + ایمیل مدیر/تعمیرکار.
	 *
	 * @param string $event    کلید رویداد.
	 * @param int    $order_id شناسه پرونده.
	 * @return void
	 */
	function pixva_crm_notify( $event, $order_id ) {
		$order = pixva_crm_order( $order_id );
		if ( ! $order ) {
			return;
		}

		/**
		 * فیلتر رویدادهای دارای پیامک.
		 *
		 * @param string[] $events کلید رویدادهایی که پیامک می‌شوند.
		 */
		$sms_events = apply_filters(
			'pixva_crm_sms_events',
			array( 'order_created', 'assigned', 'status_repairing', 'status_qc', 'status_ready', 'warranty_issued' )
		);

		if ( in_array( $event, $sms_events, true ) && ! empty( $order['phone'] ) ) {
			$text = pixva_crm_sms_text( $event, $order );
			if ( '' !== $text ) {
				pixva_send_sms( $order['phone'], $text, $event );
			}
		}

		// اطلاع به تعمیرکار تخصیص‌یافته (ایمیل داخلی).
		if ( 'assigned' === $event && $order['technician'] ) {
			$user = get_userdata( $order['technician'] );
			if ( $user && $user->user_email ) {
				wp_mail(
					$user->user_email,
					sprintf(
						/* translators: %s: کد پیگیری */
						__( 'پرونده تعمیر %s به شما تخصیص یافت', 'pixva' ),
						$order['code']
					),
					sprintf(
						"پرونده: %s\nدستگاه: %s %s\nشرح: %s\nبرآورد: %s\nپنل تعمیرکار: %s\n",
						$order['code'],
						$order['brand'],
						$order['model'],
						$order['problem'],
						$order['estimate'],
						pixva_crm_panel_url()
					)
				);
			}
		}

		/**
		 * هوک اطلاع‌رسانی رویداد پرونده (برای اتصال به سرویس‌های خارجی).
		 *
		 * @param string $event کلید رویداد.
		 * @param array  $order بسته داده پرونده.
		 */
		do_action( 'pixva_crm_notify', $event, $order );
	}
}

if ( ! function_exists( 'pixva_crm_panel_url' ) ) {
	/**
	 * نشانی پنل تعمیرکار (برگه با قالب پنل یا صفحه ورود).
	 *
	 * @return string
	 */
	function pixva_crm_panel_url() {
		$page = get_page_by_path( 'technician' );
		if ( $page instanceof WP_Post ) {
			return (string) get_permalink( $page );
		}

		return wp_login_url( (string) home_url( '/technician/' ) );
	}
}

/*
 * ---------------------------------------------------------------------------
 * ۶) هوک روی ثبت پرونده جدید (جادوگر/محاسبه‌گر)
 * ---------------------------------------------------------------------------
 */

if ( ! function_exists( 'pixva_crm_on_order_created' ) ) {
	/**
	 * پس از ساخت پرونده: ثبت منبع، رویداد آغازین و پیامک کد پیگیری.
	 *
	 * @param int    $order_id شناسه پرونده.
	 * @param string $code     کد پیگیری.
	 * @param array  $data     داده ورودی.
	 * @return void
	 */
	function pixva_crm_on_order_created( $order_id, $code, $data ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		$order_id = (int) $order_id;
		if ( ! $order_id ) {
			return;
		}

		if ( '' === (string) get_post_meta( $order_id, '_pixva_order_status', true ) ) {
			update_post_meta( $order_id, '_pixva_order_status', 'pending' );
		}

		pixva_crm_log(
			$order_id,
			'created',
			sprintf(
				/* translators: %s: کد پیگیری */
				__( 'پرونده با کد %s ثبت شد و در صف بررسی قرار گرفت.', 'pixva' ),
				(string) $code
			)
		);

		pixva_crm_notify( 'order_created', $order_id );
	}
}
add_action( 'pixva_order_created', 'pixva_crm_on_order_created', 10, 3 );

if ( ! function_exists( 'pixva_crm_daily_maintenance' ) ) {
	/**
	 * نگهداری روزانه: یادآوری پرونده‌های راکد و گارانتی‌های در آستانه انقضا.
	 *
	 * روی کرون موجود پوسته (pixva_daily_warranty_check) سوار می‌شود.
	 *
	 * @return void
	 */
	function pixva_crm_daily_maintenance() {
		$stale   = array();
		$expiring = 0;

		$posts = get_posts(
			array(
				'post_type'      => 'pixva_orders',
				'post_status'    => array( 'private', 'publish' ),
				'posts_per_page' => 200,
				'fields'         => 'ids',
			)
		);

		foreach ( (array) $posts as $id ) {
			$status   = pixva_crm_normalize_status( get_post_meta( (int) $id, '_pixva_order_status', true ) );
			$created  = (int) get_post_timestamp( (int) $id );
			$assigned = (int) get_post_meta( (int) $id, '_pixva_order_assigned_at', true );

			if ( 'pending' === $status && $created && ( time() - $created ) > 2 * DAY_IN_SECONDS ) {
				$stale[] = (string) get_post_meta( (int) $id, '_pixva_order_code', true );
			}
			if ( 'assigned' === $status && $assigned && ( time() - $assigned ) > 3 * DAY_IN_SECONDS ) {
				$stale[] = (string) get_post_meta( (int) $id, '_pixva_order_code', true );
			}

			$warranty = get_post_meta( (int) $id, '_pixva_order_warranty', true );
			if ( is_array( $warranty ) && ! empty( $warranty['expiresAt'] ) ) {
				$left = (int) $warranty['expiresAt'] - time();
				if ( $left > 0 && $left < 8 * DAY_IN_SECONDS ) {
					$expiring++;
				}
			}
		}

		update_option( 'pixva_crm_last_maintenance', time(), false );

		if ( ! empty( $stale ) ) {
			$admin_email = get_option( 'admin_email' );
			wp_mail(
				$admin_email,
				__( 'پرونده‌های راکد در صف تعمیر پیکسوا', 'pixva' ),
				sprintf(
					/* translators: %s: فهرست کدها */
					__( "پرونده‌های زیر بیش از حد مجاز بدون تعیین تکلیف مانده‌اند:\n%s", 'pixva' ),
					implode( "\n", array_slice( $stale, 0, 30 ) )
				)
			);
		}

		if ( $expiring ) {
			update_option( 'pixva_crm_expiring_warranties', $expiring, false );
		}
	}
}
add_action( 'pixva_daily_warranty_check', 'pixva_crm_daily_maintenance', 20 );

/*
 * ---------------------------------------------------------------------------
 * ۷) ثبت سفارش از جادوگر (AJAX عمومی)
 * ---------------------------------------------------------------------------
 */

if ( ! function_exists( 'pixva_crm_ajax_create_order' ) ) {
	/**
	 * AJAX عمومی: ثبت سفارش تعمیر از جادوگر چندمرحله‌ای.
	 *
	 * @return void
	 */
	function pixva_crm_ajax_create_order() {
		pixva_ajax_guard( 'pixva_crm_nonce', 'crm_order', 8, HOUR_IN_SECONDS );

		$phone = pixva_normalize_mobile( pixva_get_post_var( 'phone' ) );
		if ( ! pixva_is_valid_iranian_mobile( $phone ) ) {
			pixva_ajax_error( __( 'شماره همراه معتبر نیست. نمونه درست: ۰۹۱۲۱۲۳۴۵۶۷', 'pixva' ), 422 );
		}

		$name = pixva_get_post_var( 'name' );
		if ( '' === $name || pixva_strlen( $name ) < 2 ) {
			pixva_ajax_error( __( 'نام و نام خانوادگی را کامل وارد کنید.', 'pixva' ), 422 );
		}

		$brand   = sanitize_key( pixva_get_post_var( 'brand' ) );
		$tech    = sanitize_key( pixva_get_post_var( 'tech' ) );
		$size    = sanitize_key( pixva_get_post_var( 'size' ) );
		$problem = sanitize_key( pixva_get_post_var( 'problem' ) );
		$model   = pixva_get_post_var( 'model' );

		$labels = function_exists( 'pixva_calculator_labels' ) ? pixva_calculator_labels() : array();
		$pick   = function ( $group, $key ) use ( $labels ) {
			return isset( $labels[ $group ][ $key ] ) ? $labels[ $group ][ $key ] : $key;
		};

		$summary = sprintf(
			/* translators: 1: برند، 2: سایز، 3: تکنولوژی، 4: مشکل */
			__( '%1$s %2$s %3$s — %4$s', 'pixva' ),
			$pick( 'brand', $brand ),
			$pick( 'size', $size ),
			$pick( 'tech', $tech ),
			$pick( 'problem', $problem )
		);

		$estimate = pixva_calculate_estimate( $brand, $tech, $size, $problem );
		if ( null === $estimate ) {
			pixva_ajax_error( __( 'مشخصات دستگاه کامل یا معتبر نیست.', 'pixva' ), 422 );
		}

		if ( ! empty( $estimate['panel_replacement'] ) && function_exists( 'pixva_panel_replacement_warning' ) ) {
			$range = pixva_panel_replacement_warning();
		} else {
			$range = sprintf(
				/* translators: 1: حداقل، 2: حداکثر */
				__( '%1$s تا %2$s تومان', 'pixva' ),
				pixva_price( $estimate['min'] ),
				pixva_price( $estimate['max'] )
			);
		}

		$result = pixva_create_order(
			array(
				'phone'    => $phone,
				'brand'    => $pick( 'brand', $brand ),
				'model'    => $model,
				'problem'  => $summary,
				'estimate' => $range,
			)
		);

		if ( empty( $result['id'] ) ) {
			pixva_ajax_error( __( 'ثبت سفارش انجام نشد؛ لطفاً دوباره تلاش کنید.', 'pixva' ), 500 );
		}

		$order_id = (int) $result['id'];

		update_post_meta( $order_id, '_pixva_order_status', 'pending' );
		update_post_meta( $order_id, '_pixva_order_source', 'wizard' );
		update_post_meta( $order_id, '_pixva_order_size', $size );
		update_post_meta( $order_id, '_pixva_order_tech_key', $tech );
		update_post_meta( $order_id, '_pixva_order_problem_key', $problem );

		pixva_crm_save_customer(
			$order_id,
			array(
				'name'      => $name,
				'phone'     => $phone,
				'address'   => pixva_get_post_textarea( 'address' ),
				'zone'      => pixva_get_post_var( 'zone' ),
				'preferred' => pixva_get_post_var( 'preferred' ),
				'notes'     => pixva_get_post_textarea( 'notes' ),
				'device'    => $summary,
			)
		);

		if ( function_exists( 'pixva_notify_admin' ) ) {
			pixva_notify_admin(
				sprintf(
					/* translators: %s: کد پیگیری */
					__( 'سفارش جدید در صف دیسپچ: %s', 'pixva' ),
					$result['code']
				),
				sprintf(
					"کد پیگیری: %s\nنام: %s\nشماره: %s\nدستگاه: %s\nبرآورد: %s\nپنل دیسپچ: %s\n",
					$result['code'],
					$name,
					$phone,
					$summary,
					$range,
					admin_url( 'admin.php?page=pixva-dispatch' )
				)
			);
		}

		/**
		 * هوک ثبت سفارش از جادوگر CRM.
		 *
		 * @param int    $order_id شناسه پرونده.
		 * @param string $code     کد پیگیری.
		 */
		do_action( 'pixva_crm_order_created', $order_id, $result['code'] );

		pixva_ajax_success(
			array(
				'code'     => $result['code'],
				'message'  => sprintf(
					/* translators: %s: کد پیگیری */
					__( 'سفارش شما ثبت شد. کد پیگیری: %s', 'pixva' ),
					$result['code']
				),
				'estimate' => $range,
				'status'   => pixva_crm_status_label( 'pending' ),
				'trackUrl' => function_exists( 'pixva_page_url' ) ? pixva_page_url( 'tracking' ) : home_url( '/' ),
			)
		);
	}
}

if ( ! function_exists( 'pixva_crm_ajax_assign' ) ) {
	/**
	 * AJAX پنل مدیر: تخصیص پرونده به تعمیرکار.
	 *
	 * @return void
	 */
	function pixva_crm_ajax_assign() {
		pixva_ajax_guard( 'pixva_crm_nonce', 'crm_admin', 120, HOUR_IN_SECONDS );

		$order_id = (int) pixva_get_post_var( 'order_id' );
		$tech_id  = (int) pixva_get_post_var( 'technician' );

		$result = pixva_crm_assign( $order_id, $tech_id );
		if ( is_wp_error( $result ) ) {
			pixva_ajax_error( $result->get_error_message(), 400 );
		}

		pixva_ajax_success(
			array(
				'message' => $tech_id
					? sprintf(
						/* translators: %s: نام تعمیرکار */
						__( 'پرونده به «%s» تخصیص یافت.', 'pixva' ),
						pixva_crm_technician_label( $tech_id )
					)
					: __( 'تخصیص پرونده لغو شد.', 'pixva' ),
				'order'   => pixva_crm_public_order( $result ),
			)
		);
	}
}

if ( ! function_exists( 'pixva_crm_ajax_status' ) ) {
	/**
	 * AJAX پنل‌ها: تغییر وضعیت پرونده.
	 *
	 * @return void
	 */
	function pixva_crm_ajax_status() {
		pixva_ajax_guard( 'pixva_crm_nonce', 'crm_admin', 200, HOUR_IN_SECONDS );

		$order_id = (int) pixva_get_post_var( 'order_id' );
		$status   = sanitize_key( pixva_get_post_var( 'status' ) );
		$note     = pixva_get_post_textarea( 'note' );

		if ( ! pixva_crm_can_work_on( $order_id ) ) {
			pixva_ajax_error( __( 'اجازه ویرایش این پرونده را ندارید.', 'pixva' ), 403 );
		}

		$result = pixva_crm_set_status( $order_id, $status, $note );
		if ( is_wp_error( $result ) ) {
			pixva_ajax_error( $result->get_error_message(), 400 );
		}

		pixva_ajax_success(
			array(
				'message' => sprintf(
					/* translators: %s: وضعیت جدید */
					__( 'وضعیت به «%s» تغییر کرد.', 'pixva' ),
					pixva_crm_status_label( $status )
				),
				'order'   => pixva_crm_public_order( $result ),
			)
		);
	}
}

if ( ! function_exists( 'pixva_crm_ajax_report' ) ) {
	/**
	 * AJAX پنل تعمیرکار: ذخیره گزارش فنی.
	 *
	 * @return void
	 */
	function pixva_crm_ajax_report() {
		pixva_ajax_guard( 'pixva_crm_nonce', 'crm_report', 60, HOUR_IN_SECONDS );

		$order_id = (int) pixva_get_post_var( 'order_id' );
		$raw      = isset( $_POST['parts'] ) ? wp_unslash( $_POST['parts'] ) : array(); // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$parts    = is_array( $raw ) ? $raw : array();

		$result = pixva_crm_save_report(
			$order_id,
			array(
				'parts'     => $parts,
				'diagnosis' => pixva_get_post_textarea( 'diagnosis' ),
				'actions'   => pixva_get_post_textarea( 'actions' ),
				'minutes'   => (int) pixva_get_post_var( 'minutes' ),
				'labor'     => (int) pixva_get_post_var( 'labor' ),
				'qc_passed' => ! empty( $_POST['qc_passed'] ), // phpcs:ignore WordPress.Security.NonceVerification.Missing
			)
		);

		if ( is_wp_error( $result ) ) {
			pixva_ajax_error( $result->get_error_message(), 400 );
		}

		pixva_ajax_success(
			array(
				'message' => __( 'گزارش فنی ذخیره شد.', 'pixva' ),
				'order'   => pixva_crm_public_order( $result ),
			)
		);
	}
}

if ( ! function_exists( 'pixva_crm_ajax_warranty' ) ) {
	/**
	 * AJAX پنل تعمیرکار: تأیید نهایی و صدور گارانتی دیجیتال.
	 *
	 * @return void
	 */
	function pixva_crm_ajax_warranty() {
		pixva_ajax_guard( 'pixva_crm_nonce', 'crm_report', 40, HOUR_IN_SECONDS );

		$order_id = (int) pixva_get_post_var( 'order_id' );
		$result   = pixva_crm_issue_warranty( $order_id );

		if ( is_wp_error( $result ) ) {
			pixva_ajax_error( $result->get_error_message(), 400 );
		}

		$warranty = $result['warranty'];

		pixva_ajax_success(
			array(
				'message'  => sprintf(
					/* translators: %s: سریال گارانتی */
					__( 'گارانتی دیجیتال صادر شد: %s', 'pixva' ),
					$warranty['serial']
				),
				'order'    => pixva_crm_public_order( $result ),
				'warranty' => $warranty,
				'cardUrl'  => add_query_arg( array( 'warranty' => rawurlencode( $warranty['serial'] ) ), pixva_crm_panel_url() ),
			)
		);
	}
}

if ( ! function_exists( 'pixva_crm_public_order' ) ) {
	/**
	 * نسخه امن بسته پرونده برای پاسخ JSON (بدون داده‌های داخلی حساس).
	 *
	 * @param array $order بسته پرونده.
	 * @return array<string, mixed>
	 */
	function pixva_crm_public_order( $order ) {
		$report   = $order['report'];
		$warranty = $order['warranty'];

		return array(
			'id'         => $order['id'],
			'code'       => $order['code'],
			'status'     => $order['status'],
			'statusLabel'=> $order['statusLabel'],
			'device'     => trim( $order['brand'] . ' ' . $order['model'] ),
			'problem'    => $order['problem'],
			'estimate'   => $order['estimate'],
			'technician' => $order['technician'],
			'techName'   => $order['techName'],
			'partsCount' => count( $report['parts'] ),
			'minutes'    => $report['minutes'],
			'labor'      => $report['labor'],
			'partCost'   => $report['partCost'],
			'total'      => $report['total'],
			'totalLabel' => function_exists( 'pixva_price' ) ? pixva_price( $report['total'] ) : (string) $report['total'],
			'qcPassed'   => $report['qcPassed'],
			'warranty'   => $warranty['serial']
				? array(
					'serial'    => $warranty['serial'],
					'days'      => $warranty['days'],
					'expires'   => pixva_fa_num( wp_date( 'Y/m/d', $warranty['expiresAt'] ) ),
					'techName'  => $warranty['techName'],
					'verifyUrl' => $warranty['verifyUrl'],
				)
				: null,
			'activity'   => array_slice(
				array_map(
					static function ( $row ) {
						return array(
							'at'    => pixva_fa_num( wp_date( 'Y/m/d H:i', (int) $row['at'] ) ),
							'who'   => $row['who'],
							'label' => $row['label'],
						);
					},
					$order['activity']
				),
				0,
				8
			),
		);
	}
}

if ( ! function_exists( 'pixva_crm_register_ajax' ) ) {
	/**
	 * ثبت هوک‌های AJAX موتور CRM.
	 *
	 * @return void
	 */
	function pixva_crm_register_ajax() {
		$public = array(
			'pixva_crm_create_order' => 'pixva_crm_ajax_create_order',
		);
		foreach ( $public as $action => $callback ) {
			add_action( 'wp_ajax_' . $action, $callback );
			add_action( 'wp_ajax_nopriv_' . $action, $callback );
		}

		$private = array(
			'pixva_crm_assign'   => 'pixva_crm_ajax_assign',
			'pixva_crm_status'   => 'pixva_crm_ajax_status',
			'pixva_crm_report'   => 'pixva_crm_ajax_report',
			'pixva_crm_warranty' => 'pixva_crm_ajax_warranty',
		);
		foreach ( $private as $action => $callback ) {
			add_action( 'wp_ajax_' . $action, $callback );
		}
	}
}
add_action( 'init', 'pixva_crm_register_ajax' );

/*
 * ---------------------------------------------------------------------------
 * ۸) REST API (pixva/v1/crm/*)
 * ---------------------------------------------------------------------------
 */

if ( ! function_exists( 'pixva_crm_register_rest' ) ) {
	/**
	 * ثبت اندپوینت‌های REST موتور CRM.
	 *
	 * @return void
	 */
	function pixva_crm_register_rest() {
		register_rest_route(
			'pixva/v1',
			'/crm/orders',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => 'pixva_crm_rest_orders',
					'permission_callback' => static function () {
						return current_user_can( 'pixva_view_crm_panel' );
					},
					'args'                => array(
						'status'     => array( 'type' => 'string' ),
						'technician' => array( 'type' => 'integer' ),
						'search'     => array( 'type' => 'string' ),
						'limit'      => array( 'type' => 'integer' ),
						'page'       => array( 'type' => 'integer' ),
					),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => 'pixva_crm_rest_create_order',
					'permission_callback' => '__return_true',
				),
			)
		);

		register_rest_route(
			'pixva/v1',
			'/crm/orders/(?P<id>\d+)',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => 'pixva_crm_rest_order',
				'permission_callback' => static function ( $request ) {
					return pixva_crm_can_work_on( (int) $request['id'] );
				},
			)
		);

		register_rest_route(
			'pixva/v1',
			'/crm/orders/(?P<id>\d+)/assign',
			array(
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => 'pixva_crm_rest_assign',
				'permission_callback' => 'pixva_crm_can_dispatch',
			)
		);

		register_rest_route(
			'pixva/v1',
			'/crm/orders/(?P<id>\d+)/status',
			array(
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => 'pixva_crm_rest_status',
				'permission_callback' => static function ( $request ) {
					return pixva_crm_can_work_on( (int) $request['id'] );
				},
			)
		);

		register_rest_route(
			'pixva/v1',
			'/crm/orders/(?P<id>\d+)/report',
			array(
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => 'pixva_crm_rest_report',
				'permission_callback' => static function ( $request ) {
					return pixva_crm_can_work_on( (int) $request['id'] );
				},
			)
		);

		register_rest_route(
			'pixva/v1',
			'/crm/orders/(?P<id>\d+)/warranty',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => 'pixva_crm_rest_warranty',
				'permission_callback' => static function ( $request ) {
					return current_user_can( 'pixva_issue_order_warranty', (int) $request['id'] );
				},
			)
		);

		register_rest_route(
			'pixva/v1',
			'/crm/stats',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => static function () {
					return rest_ensure_response( pixva_crm_stats() );
				},
				'permission_callback' => 'pixva_crm_can_dispatch',
			)
		);

		register_rest_route(
			'pixva/v1',
			'/crm/technicians',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => static function () {
					return rest_ensure_response( array_values( pixva_crm_technicians() ) );
				},
				'permission_callback' => 'pixva_crm_can_dispatch',
			)
		);

		register_rest_route(
			'pixva/v1',
			'/crm/warranty/(?P<serial>[A-Za-z0-9\-]+)',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => 'pixva_crm_rest_verify_warranty',
				'permission_callback' => '__return_true',
			)
		);
	}
}
add_action( 'rest_api_init', 'pixva_crm_register_rest' );

if ( ! function_exists( 'pixva_crm_rest_orders' ) ) {
	/**
	 * REST: فهرست پرونده‌ها (دیسپچر یا تعمیرکار).
	 *
	 * @param WP_REST_Request $request درخواست.
	 * @return WP_REST_Response
	 */
	function pixva_crm_rest_orders( $request ) {
		$args = array(
			'status'     => (string) $request->get_param( 'status' ),
			'search'     => (string) $request->get_param( 'search' ),
			'limit'      => (int) $request->get_param( 'limit' ) ?: 20,
			'page'       => (int) $request->get_param( 'page' ) ?: 1,
			'technician' => (int) $request->get_param( 'technician' ),
		);

		// تعمیرکار فقط پرونده‌های خودش را می‌بیند.
		if ( ! pixva_crm_can_dispatch() ) {
			$args['technician'] = get_current_user_id();
		}

		$result = pixva_crm_orders( $args );
		$items  = array();
		foreach ( $result['items'] as $post ) {
			$order = pixva_crm_order( $post );
			if ( $order ) {
				$items[] = pixva_crm_public_order( $order );
			}
		}

		return rest_ensure_response(
			array(
				'items' => $items,
				'total' => $result['total'],
				'pages' => $result['pages'],
			)
		);
	}
}

if ( ! function_exists( 'pixva_crm_rest_order' ) ) {
	/**
	 * REST: جزئیات یک پرونده.
	 *
	 * @param WP_REST_Request $request درخواست.
	 * @return WP_REST_Response|WP_Error
	 */
	function pixva_crm_rest_order( $request ) {
		$order = pixva_crm_order( (int) $request['id'] );
		if ( ! $order ) {
			return new WP_Error( 'pixva_crm_missing', __( 'پرونده پیدا نشد.', 'pixva' ), array( 'status' => 404 ) );
		}
		return rest_ensure_response( pixva_crm_public_order( $order ) );
	}
}

if ( ! function_exists( 'pixva_crm_rest_assign' ) ) {
	/**
	 * REST: تخصیص پرونده.
	 *
	 * @param WP_REST_Request $request درخواست.
	 * @return WP_REST_Response|WP_Error
	 */
	function pixva_crm_rest_assign( $request ) {
		$result = pixva_crm_assign( (int) $request['id'], (int) $request->get_param( 'technician' ) );
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		return rest_ensure_response( pixva_crm_public_order( $result ) );
	}
}

if ( ! function_exists( 'pixva_crm_rest_status' ) ) {
	/**
	 * REST: تغییر وضعیت پرونده.
	 *
	 * @param WP_REST_Request $request درخواست.
	 * @return WP_REST_Response|WP_Error
	 */
	function pixva_crm_rest_status( $request ) {
		$result = pixva_crm_set_status( (int) $request['id'], (string) $request->get_param( 'status' ), (string) $request->get_param( 'note' ) );
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		return rest_ensure_response( pixva_crm_public_order( $result ) );
	}
}

if ( ! function_exists( 'pixva_crm_rest_report' ) ) {
	/**
	 * REST: ذخیره گزارش فنی.
	 *
	 * @param WP_REST_Request $request درخواست.
	 * @return WP_REST_Response|WP_Error
	 */
	function pixva_crm_rest_report( $request ) {
		$params = $request->get_params();
		$result = pixva_crm_save_report( (int) $request['id'], $params );
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		return rest_ensure_response( pixva_crm_public_order( $result ) );
	}
}

if ( ! function_exists( 'pixva_crm_rest_warranty' ) ) {
	/**
	 * REST: صدور گارانتی دیجیتال.
	 *
	 * @param WP_REST_Request $request درخواست.
	 * @return WP_REST_Response|WP_Error
	 */
	function pixva_crm_rest_warranty( $request ) {
		$result = pixva_crm_issue_warranty( (int) $request['id'] );
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		return rest_ensure_response(
			array(
				'order'    => pixva_crm_public_order( $result ),
				'warranty' => $result['warranty'],
			)
		);
	}
}

if ( ! function_exists( 'pixva_crm_rest_create_order' ) ) {
	/**
	 * REST: ثبت سفارش عمومی (با نرخ‌محدود و nonce).
	 *
	 * @param WP_REST_Request $request درخواست.
	 * @return WP_REST_Response|WP_Error
	 */
	function pixva_crm_rest_create_order( $request ) {
		if ( ! pixva_rate_limit( 'crm_rest_order', 8, HOUR_IN_SECONDS ) ) {
			return new WP_Error( 'pixva_crm_rate', __( 'تعداد درخواست‌ها بیش از حد مجاز است.', 'pixva' ), array( 'status' => 429 ) );
		}

		$phone = pixva_normalize_mobile( (string) $request->get_param( 'phone' ) );
		if ( ! pixva_is_valid_iranian_mobile( $phone ) ) {
			return new WP_Error( 'pixva_crm_phone', __( 'شماره همراه معتبر نیست.', 'pixva' ), array( 'status' => 422 ) );
		}

		$name = sanitize_text_field( (string) $request->get_param( 'name' ) );
		if ( pixva_strlen( $name ) < 2 ) {
			return new WP_Error( 'pixva_crm_name', __( 'نام را کامل وارد کنید.', 'pixva' ), array( 'status' => 422 ) );
		}

		$brand   = sanitize_key( (string) $request->get_param( 'brand' ) );
		$tech    = sanitize_key( (string) $request->get_param( 'tech' ) );
		$size    = sanitize_key( (string) $request->get_param( 'size' ) );
		$problem = sanitize_key( (string) $request->get_param( 'problem' ) );

		$estimate = pixva_calculate_estimate( $brand, $tech, $size, $problem );
		if ( null === $estimate ) {
			return new WP_Error( 'pixva_crm_estimate', __( 'مشخصات دستگاه معتبر نیست.', 'pixva' ), array( 'status' => 422 ) );
		}

		$labels = function_exists( 'pixva_calculator_labels' ) ? pixva_calculator_labels() : array();
		$pick   = static function ( $group, $key ) use ( $labels ) {
			return isset( $labels[ $group ][ $key ] ) ? $labels[ $group ][ $key ] : $key;
		};

		$range = sprintf(
			/* translators: 1: حداقل، 2: حداکثر */
			__( '%1$s تا %2$s تومان', 'pixva' ),
			pixva_price( $estimate['min'] ),
			pixva_price( $estimate['max'] )
		);

		$result = pixva_create_order(
			array(
				'phone'    => $phone,
				'brand'    => $pick( 'brand', $brand ),
				'model'    => sanitize_text_field( (string) $request->get_param( 'model' ) ),
				'problem'  => sprintf(
					/* translators: 1: برند، 2: سایز، 3: تکنولوژی، 4: مشکل */
					__( '%1$s %2$s %3$s — %4$s', 'pixva' ),
					$pick( 'brand', $brand ),
					$pick( 'size', $size ),
					$pick( 'tech', $tech ),
					$pick( 'problem', $problem )
				),
				'estimate' => $range,
			)
		);

		if ( empty( $result['id'] ) ) {
			return new WP_Error( 'pixva_crm_create', __( 'ثبت سفارش ناموفق بود.', 'pixva' ), array( 'status' => 500 ) );
		}

		update_post_meta( (int) $result['id'], '_pixva_order_source', 'rest' );
		update_post_meta( (int) $result['id'], '_pixva_order_status', 'pending' );
		pixva_crm_save_customer(
			(int) $result['id'],
			array(
				'name'    => $name,
				'phone'   => $phone,
				'address' => sanitize_textarea_field( (string) $request->get_param( 'address' ) ),
				'notes'   => sanitize_textarea_field( (string) $request->get_param( 'notes' ) ),
			)
		);

		return rest_ensure_response(
			array(
				'code'     => $result['code'],
				'estimate' => $range,
				'status'   => pixva_crm_status_label( 'pending' ),
			)
		);
	}
}

if ( ! function_exists( 'pixva_crm_rest_verify_warranty' ) ) {
	/**
	 * REST: استعلام اصالت گارانتی با سریال (عمومی).
	 *
	 * @param WP_REST_Request $request درخواست.
	 * @return WP_REST_Response|WP_Error
	 */
	function pixva_crm_rest_verify_warranty( $request ) {
		$serial   = strtoupper( sanitize_text_field( (string) $request['serial'] ) );
		$warranty = pixva_crm_warranty_of( $serial );

		if ( ! $warranty ) {
			return new WP_Error( 'pixva_crm_warranty_missing', __( 'گارانتی‌ای با این سریال پیدا نشد.', 'pixva' ), array( 'status' => 404 ) );
		}

		$now    = time();
		$active = $warranty['expiresAt'] > $now;

		return rest_ensure_response(
			array(
				'serial'     => $warranty['serial'],
				'valid'      => $active,
				'days'       => $warranty['days'],
				'issuedAt'   => pixva_fa_num( wp_date( 'Y/m/d', $warranty['issuedAt'] ) ),
				'expiresAt'  => pixva_fa_num( wp_date( 'Y/m/d', $warranty['expiresAt'] ) ),
				'technician' => $warranty['techName'],
				'covers'     => $warranty['covers'],
				'fingerprint'=> strtoupper( substr( (string) $warranty['hash'], 0, 16 ) ),
				'state'      => $active
					? __( 'گارانتی معتبر است.', 'pixva' )
					: __( 'مهلت این گارانتی به پایان رسیده است.', 'pixva' ),
			)
		);
	}
}

/*
 * ---------------------------------------------------------------------------
 * ۹) ابزارهای کمکی UI (برچسب‌ها و گزینه‌ها)
 * ---------------------------------------------------------------------------
 */

if ( ! function_exists( 'pixva_crm_status_options' ) ) {
	/**
	 * گزینه‌های <select> وضعیت‌ها.
	 *
	 * @param string $selected وضعیت انتخاب‌شده.
	 * @return string
	 */
	function pixva_crm_status_options( $selected = '' ) {
		$selected = pixva_crm_normalize_status( $selected );
		$html     = '';
		foreach ( pixva_crm_statuses() as $key => $label ) {
			$html .= '<option value="' . esc_attr( $key ) . '" ' . selected( $selected, $key, false ) . '>' . esc_html( $label ) . '</option>';
		}
		return $html;
	}
}

if ( ! function_exists( 'pixva_crm_technician_options' ) ) {
	/**
	 * گزینه‌های <select> تعمیرکاران با نشان بار کاری.
	 *
	 * @param int $selected شناسه انتخاب‌شده.
	 * @return string
	 */
	function pixva_crm_technician_options( $selected = 0 ) {
		$selected = (int) $selected;
		$html     = '<option value="0">' . esc_html__( '— تخصیص نداده —', 'pixva' ) . '</option>';

		foreach ( pixva_crm_technicians() as $tech ) {
			$label = sprintf(
				/* translators: 1: نام تعمیرکار، 2: تخصص، 3: پرونده‌های باز */
				__( '%1$s%2$s (%3$s پرونده باز)', 'pixva' ),
				$tech['name'],
				'' !== $tech['skill'] ? ' — ' . $tech['skill'] : '',
				pixva_fa_num( (string) $tech['openJobs'] )
			);
			$html .= '<option value="' . esc_attr( (string) $tech['id'] ) . '" ' . selected( $selected, $tech['id'], false ) . '>' . esc_html( $label ) . '</option>';
		}

		return $html;
	}
}

if ( ! function_exists( 'pixva_crm_nonce' ) ) {
	/**
	 * nonce مشترک موتور CRM.
	 *
	 * @return string
	 */
	function pixva_crm_nonce() {
		return wp_create_nonce( 'pixva_crm_nonce' );
	}
}

if ( ! function_exists( 'pixva_crm_localize' ) ) {
	/**
	 * داده‌های موردنیاز crm-engine.js.
	 *
	 * @return array<string, mixed>
	 */
	function pixva_crm_localize() {
		return array(
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'restUrl' => esc_url_raw( rest_url( 'pixva/v1' ) ),
			'nonce'   => pixva_crm_nonce(),
			'panelUrl'=> pixva_crm_panel_url(),
			'homeUrl' => esc_url_raw( home_url( '/' ) ),
			'canDispatch' => pixva_crm_can_dispatch(),
			'statuses'    => pixva_crm_statuses(),
			'i18n'        => array(
				'sending'   => __( 'در حال ارسال…', 'pixva' ),
				'saved'     => __( 'ذخیره شد.', 'pixva' ),
				'error'     => __( 'خطایی رخ داد؛ دوباره تلاش کنید.', 'pixva' ),
				'confirmWarranty' => __( 'گارانتی دیجیتال صادر و وضعیت پرونده «آماده تحویل» شود؟', 'pixva' ),
				'copied'    => __( 'کپی شد.', 'pixva' ),
				'printed'   => __( 'پیش از چاپ، هدر و فوتر مرورگر را غیرفعال نکنید.', 'pixva' ),
				'addPart'   => __( 'افزودن قطعه', 'pixva' ),
				'removePart'=> __( 'حذف', 'pixva' ),
			),
		);
	}
}

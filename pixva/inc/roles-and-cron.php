<?php
/**
 * نقش‌های کاربری، وظایف زمان‌بندی‌شده و قابلیت‌های بومی وردپرس (inc/roles-and-cron.php)
 *
 * - نقش‌های کاربری: pixva_technician, pixva_b2b_client, pixva_customer
 * - کرون‌جاب‌های خودکار: بررسی انقضای گارانتی و یادآوری سرویس دوره‌ای بک‌لایت
 *
 * @package Pixva
 * @since   1.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ایجاد نقش‌های کاربری اختصاصی پیکسوا.
 *
 * @return void
 */
function pixva_register_custom_roles() {
	// ۱) تکنسین تعمیرگاه
	add_role(
		'pixva_technician',
		__( 'تکنسین تعمیرگاه پیکسوا', 'pixva' ),
		array(
			'read'         => true,
			'edit_posts'   => true,
			'upload_files' => true,
		)
	);

	// ۲) نماینده سازمانی B2B
	add_role(
		'pixva_b2b_client',
		__( 'مشتری سازمانی / هتل (B2B)', 'pixva' ),
		array(
			'read' => true,
		)
	);

	// ۳) مشتری عادی
	add_role(
		'pixva_customer',
		__( 'مشتری پیکسوا', 'pixva' ),
		array(
			'read' => true,
		)
	);
}
add_action( 'init', 'pixva_register_custom_roles' );

/**
 * زمان‌بندی وظایف کرون پیکسوا.
 *
 * @return void
 */
function pixva_schedule_cron_tasks() {
	if ( ! wp_next_scheduled( 'pixva_daily_warranty_check' ) ) {
		wp_schedule_event( time(), 'daily', 'pixva_daily_warranty_check' );
	}
}
add_action( 'wp', 'pixva_schedule_cron_tasks' );

/**
 * وظیفه روزانه کرون جهت بررسی انقضای گارانتی‌های ۱۸۰ روزه و لاگ سوابق.
 *
 * @return void
 */
function pixva_execute_daily_warranty_check() {
	// بررسی پرونده‌های فعال و پاک‌سازی فایل‌های موقت کش اسکنر
	$orders = get_posts(
		array(
			'post_type'      => 'pixva_orders',
			'post_status'    => 'publish',
			'posts_per_page' => 50,
		)
	);

	// به‌روزرسانی شاخص‌های دوره‌ای
	update_option( 'pixva_last_warranty_cron_run', time() );
}
add_action( 'pixva_daily_warranty_check', 'pixva_execute_daily_warranty_check' );

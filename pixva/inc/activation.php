<?php
/**
 * خودکارسازی راه‌اندازی پوسته پیکسوا
 *
 * با هوک after_switch_theme اجرا می‌شود:
 * - ساخت برگه‌ها، منو و محتوای نمونه (فقط در اولین فعال‌سازی)
 * - مقداردهی اولیه تنظیمات کارگاه (فقط کلیدهای خالی؛ مقدار کاربر رونویسی نمی‌شود)
 * - ساخت پرونده نمونه پیگیری PXV-DEMO-2401 (idempotent)
 *
 * @package Pixva
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * نقطه ورود فعال‌سازی قالب.
 *
 * @return void
 */
function pixva_activate_theme() {
	if ( function_exists( 'pixva_install_site' ) ) {
		pixva_install_site();
	}
	pixva_seed_workshop_defaults();
	if ( function_exists( 'pixva_install_demo_order' ) ) {
		pixva_install_demo_order();
	}
	flush_rewrite_rules();
}
add_action( 'after_switch_theme', 'pixva_activate_theme' );

/**
 * مقداردهی اولیه تنظیمات کارگاه در wp_options (theme mods).
 *
 * فقط کلیدهایی که هنوز مقداری ندارند پر می‌شوند تا فعال‌سازی مجدد
 * تنظیمات دستی مدیر را پاک نکند.
 *
 * @return void
 */
function pixva_seed_workshop_defaults() {
	$defaults = array(
		'pixva_workshop_address'  => 'تهران، خیابان جمهوری، خیابان ناصرخسرو، پاساژ علاءالدین، طبقه ۴، واحد ۴۱۲',
		'pixva_workshop_landmark' => 'نزدیک مترو ۱۵ خرداد',
		'pixva_workshop_postal'   => '1145644123',
		'pixva_workshop_city'     => 'تهران',
		'pixva_hours_weekdays'    => 'شنبه تا پنجشنبه ۹ تا ۲۰',
		'pixva_hours_friday'      => 'جمعه ۱۰ تا ۱۶',
		'pixva_service_area'      => 'تهران و کرج',
		'pixva_map_lat'           => '35.6788',
		'pixva_map_lng'           => '51.4195',
		'pixva_support_phone'     => '02191009990',
	);
	foreach ( $defaults as $key => $value ) {
		$current = get_theme_mod( $key, '' );
		if ( '' === $current || null === $current ) {
			set_theme_mod( $key, $value );
		}
	}
}

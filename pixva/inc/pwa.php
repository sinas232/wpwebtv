<?php
/**
 * پشتیبانی وب‌اپ (PWA) — Master Specification v25.0 (inc/pwa.php)
 *
 * - تولید پویای مانیفست وب‌اپ (JSON) بدون فایل ثابت در ریشه سایت
 * - افزودن theme-color، تگ‌های نصب iOS و لینک مانیفست به <head>
 * - در اختیار گذاشتن پیام حالت آفلاین برای بنر جاوااسکریپتی
 *
 * همه رفتارها با کلید pwa_enable در مرکز کنترل روشن/خاموش می‌شوند.
 *
 * @package Pixva
 * @since   1.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'pixva_pwa_enabled' ) ) {
	/**
	 * آیا وب‌اپ فعال است؟
	 *
	 * @return bool
	 */
	function pixva_pwa_enabled() {
		$options = function_exists( 'pixva_control_options' ) ? pixva_control_options() : array();
		$enabled = ! empty( $options['pwa_enable'] );

		return (bool) apply_filters( 'pixva_pwa_enabled', $enabled );
	}
}

if ( ! function_exists( 'pixva_pwa_offline_message' ) ) {
	/**
	 * پیام حالت آفلاین.
	 *
	 * @return string
	 */
	function pixva_pwa_offline_message() {
		$options = function_exists( 'pixva_control_options' ) ? pixva_control_options() : array();
		$message = isset( $options['pwa_offline_message'] ) ? (string) $options['pwa_offline_message'] : '';

		if ( '' === trim( $message ) ) {
			$message = __( 'اتصال شما قطع است؛ ابزارهای محاسبه و پایگاه کدهای خطا همچنان کار می‌کنند.', 'pixva' );
		}

		return (string) apply_filters( 'pixva_pwa_offline_message', $message );
	}
}

if ( ! function_exists( 'pixva_pwa_manifest_url' ) ) {
	/**
	 * نشانی مانیفست پویا.
	 *
	 * @return string
	 */
	function pixva_pwa_manifest_url() {
		return add_query_arg( 'pixva_manifest', '1', home_url( '/' ) );
	}
}

if ( ! function_exists( 'pixva_pwa_icons' ) ) {
	/**
	 * آیکون‌های مانیفست از تصویر قالب و لوگوی SVG.
	 *
	 * @return array<int, array<string, string>>
	 */
	function pixva_pwa_icons() {
		$icons = array();
		$file  = PIXVA_DIR . '/screenshot.png';

		if ( is_readable( $file ) ) {
			$size  = function_exists( 'getimagesize' ) ? @getimagesize( $file ) : false; // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			$dims  = is_array( $size ) && ! empty( $size[0] ) ? $size[0] . 'x' . $size[1] : '1200x900';
			$icons[] = array(
				'src'     => PIXVA_URI . '/screenshot.png',
				'sizes'   => $dims,
				'type'    => 'image/png',
				'purpose' => 'any',
			);
		}

		$svg = PIXVA_DIR . '/assets/images/logo-mark.svg';
		if ( is_readable( $svg ) ) {
			$icons[] = array(
				'src'     => PIXVA_URI . '/assets/images/logo-mark.svg',
				'sizes'   => 'any',
				'type'    => 'image/svg+xml',
				'purpose' => 'maskable',
			);
		}

		$site_icon = function_exists( 'get_site_icon_url' ) ? get_site_icon_url( 512 ) : '';
		if ( $site_icon ) {
			array_unshift(
				$icons,
				array(
					'src'     => $site_icon,
					'sizes'   => '512x512',
					'type'    => 'image/png',
					'purpose' => 'any',
				)
			);
		}

		return apply_filters( 'pixva_pwa_icons', $icons );
	}
}

if ( ! function_exists( 'pixva_pwa_manifest' ) ) {
	/**
	 * ساخت آرایه مانیفست وب‌اپ.
	 *
	 * @return array<string, mixed>
	 */
	function pixva_pwa_manifest() {
		$name  = get_bloginfo( 'name' );
		$short = $name;
		if ( function_exists( 'mb_substr' ) ) {
			$short = mb_substr( $name, 0, 12 );
		} else {
			$short = substr( $name, 0, 12 );
		}

		$manifest = array(
			'name'             => $name ? $name : __( 'پیکسوا — تعمیر تخصصی تلویزیون', 'pixva' ),
			'short_name'       => $short ? $short : 'Pixva',
			'description'      => get_bloginfo( 'description' ) ? get_bloginfo( 'description' ) : __( 'عیب‌یابی هوشمند، نرخ‌نامه شفاف، پیگیری تعمیر و گارانتی دیجیتال تلویزیون.', 'pixva' ),
			'lang'             => get_locale(),
			'dir'              => is_rtl() ? 'rtl' : 'ltr',
			'start_url'        => home_url( '/' ),
			'scope'            => home_url( '/' ),
			'display'          => 'standalone',
			'orientation'      => 'portrait',
			'background_color' => '#F7FAFC',
			'theme_color'      => '#176B87',
			'categories'       => array( 'business', 'utilities', 'productivity' ),
			'icons'            => pixva_pwa_icons(),
			'shortcuts'        => array(),
		);

		if ( function_exists( 'pixva_hubs' ) ) {
			foreach ( pixva_hubs() as $hub ) {
				$manifest['shortcuts'][] = array(
					'name' => isset( $hub['title'] ) ? $hub['title'] : '',
					'url'  => function_exists( 'pixva_hub_url' ) ? pixva_hub_url( $hub['slug'] ) : home_url( '/' ),
				);
			}
		}

		return apply_filters( 'pixva_pwa_manifest', $manifest );
	}
}

/**
 * خروجی مانیفست به‌صورت JSON وقتی پارامتر pixva_manifest درخواست شود.
 *
 * @return void
 */
function pixva_pwa_maybe_serve_manifest() {
	if ( ! isset( $_GET['pixva_manifest'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return;
	}

	if ( ! pixva_pwa_enabled() ) {
		status_header( 404 );
		exit;
	}

	nocache_headers();
	header( 'Content-Type: application/manifest+json; charset=UTF-8' );
	header( 'Cache-Control: public, max-age=3600' );

	echo wp_json_encode( pixva_pwa_manifest(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
	exit;
}
add_action( 'template_redirect', 'pixva_pwa_maybe_serve_manifest', 1 );
add_action( 'parse_request', 'pixva_pwa_maybe_serve_manifest', 1 );

/**
 * افزودن تگ‌های وب‌اپ به <head>.
 *
 * @return void
 */
function pixva_pwa_head() {
	if ( ! pixva_pwa_enabled() ) {
		return;
	}

	$name = get_bloginfo( 'name' );
	?>
	<link rel="manifest" href="<?php echo esc_url( pixva_pwa_manifest_url() ); ?>">
	<meta name="theme-color" content="#176B87">
	<meta name="mobile-web-app-capable" content="yes">
	<meta name="apple-mobile-web-app-capable" content="yes">
	<meta name="apple-mobile-web-app-status-bar-style" content="default">
	<meta name="apple-mobile-web-app-title" content="<?php echo esc_attr( $name ? $name : 'Pixva' ); ?>">
	<meta name="application-name" content="<?php echo esc_attr( $name ? $name : 'Pixva' ); ?>">
	<meta name="msapplication-TileColor" content="#123B4A">
	<?php
}
add_action( 'wp_head', 'pixva_pwa_head', 2 );

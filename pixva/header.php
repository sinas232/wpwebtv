<?php
/**
 * Site header (v2.2.0).
 *
 * One header, two compositions driven by CSS:
 *  - desktop (≥ 900px): brand lockup · primary navigation with active rule ·
 *    utility links · primary CTA;
 *  - mobile (< 900px): compact bar (lockup · quick booking · menu) and a
 *    full-height sheet with large touch rows, grouped by intent.
 *
 * JS contract (assets/js/app.js): .site-header (is-scrolled), .nav-toggle
 * (aria-expanded, unhidden by JS), #site-nav (is-open).
 *
 * @package Pixva
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$pixva_is_in = is_user_logged_in();
?><!doctype html>
<html <?php language_attributes(); ?> class="no-js">
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="theme-color" content="#0B1C2E">
<?php wp_print_inline_script_tag( "document.documentElement.className=document.documentElement.className.replace('no-js','js');" ); ?>
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="skip-link" href="#content"><?php esc_html_e( 'رفتن به محتوای اصلی', 'pixva' ); ?></a>
<header class="site-header" id="site-header">
	<div class="container site-header__bar">
		<?php pixva_logo(); ?>

		<button class="nav-toggle" type="button" aria-expanded="false" aria-controls="site-nav" hidden>
			<span class="nav-toggle__icon" aria-hidden="true"><span></span><span></span><span></span></span>
			<span class="screen-reader-text"><?php esc_html_e( 'منو', 'pixva' ); ?></span>
		</button>

		<nav class="site-nav" id="site-nav" aria-label="<?php esc_attr_e( 'منوی اصلی', 'pixva' ); ?>">
			<div class="site-nav__sheet-head">
				<span class="site-nav__sheet-title"><?php esc_html_e( 'منوی پیکسوا', 'pixva' ); ?></span>
			</div>

			<div class="site-nav__primary">
				<p class="site-nav__group-title"><?php esc_html_e( 'بخش‌ها', 'pixva' ); ?></p>
				<?php pixva_nav( 'primary', 'primary-menu' ); ?>
			</div>

			<div class="site-nav__quick" role="group" aria-label="<?php esc_attr_e( 'دسترسی سریع', 'pixva' ); ?>">
				<a class="site-nav__quick-item site-nav__quick-item--main" data-track="cta_click" data-track-label="diagnosis" data-track-location="header_menu" href="<?php echo esc_url( pixva_route_url( 'diagnosis' ) ); ?>">
					<span class="site-nav__quick-icon" aria-hidden="true"><?php echo wp_kses( pixva_icon( 'pulse' ), pixva_svg_allowed() ); ?></span>
					<span class="site-nav__quick-text"><b><?php esc_html_e( 'تشخیص آنلاین', 'pixva' ); ?></b><small><?php esc_html_e( 'ابزار اصلی · رایگان', 'pixva' ); ?></small></span>
				</a>
				<a class="site-nav__quick-item" href="<?php echo esc_url( pixva_route_url( 'tracking' ) ); ?>">
					<span class="site-nav__quick-icon" aria-hidden="true"><?php echo wp_kses( pixva_icon( 'route' ), pixva_svg_allowed() ); ?></span>
					<span class="site-nav__quick-text"><b><?php esc_html_e( 'پیگیری درخواست', 'pixva' ); ?></b><small><?php esc_html_e( 'با کد پیگیری', 'pixva' ); ?></small></span>
				</a>
				<a class="site-nav__quick-item" href="<?php echo esc_url( pixva_route_url( 'account' ) ); ?>">
					<span class="site-nav__quick-icon" aria-hidden="true"><?php echo wp_kses( pixva_icon( 'user' ), pixva_svg_allowed() ); ?></span>
					<span class="site-nav__quick-text"><b><?php echo $pixva_is_in ? esc_html__( 'حساب من', 'pixva' ) : esc_html__( 'ورود به حساب', 'pixva' ); ?></b><small><?php esc_html_e( 'درخواست‌ها و گارانتی', 'pixva' ); ?></small></span>
				</a>
			</div>

			<div class="site-nav__actions">
				<a class="btn btn--accent site-nav__cta" data-track="cta_click" data-track-label="booking" data-track-location="header" href="<?php echo esc_url( pixva_route_url( 'booking' ) ); ?>"><?php esc_html_e( 'ثبت درخواست تعمیر', 'pixva' ); ?></a>
			</div>
		</nav>

		<a class="site-header__m-cta btn btn--accent btn--sm" data-track="cta_click" data-track-label="booking" data-track-location="header_mobile" href="<?php echo esc_url( pixva_route_url( 'booking' ) ); ?>"><?php esc_html_e( 'درخواست تعمیر', 'pixva' ); ?></a>
	</div>
</header>

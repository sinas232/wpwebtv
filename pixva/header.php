<?php
/**
 * Site header: skip link, brand, primary navigation (disclosure on mobile),
 * booking CTA. Phone appears only when configured (§35).
 *
 * @package Pixva
 * @since   2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
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
<a class="skip-link" href="#main"><?php esc_html_e( 'رفتن به محتوای اصلی', 'pixva' ); ?></a>
<header class="site-header">
	<div class="container site-header__bar">
		<?php pixva_logo(); ?>
		<button class="nav-toggle" type="button" aria-expanded="false" aria-controls="site-nav" hidden>
			<span class="nav-toggle__open"><?php echo wp_kses( pixva_icon( 'menu' ), pixva_svg_allowed() ); ?></span>
			<span class="nav-toggle__close"><?php echo wp_kses( pixva_icon( 'close' ), pixva_svg_allowed() ); ?></span>
			<span class="screen-reader-text"><?php esc_html_e( 'منو', 'pixva' ); ?></span>
		</button>
		<nav class="site-nav" id="site-nav" aria-label="<?php esc_attr_e( 'منوی اصلی', 'pixva' ); ?>">
			<?php pixva_nav( 'primary', 'primary-menu' ); ?>
			<div class="site-nav__actions">
				<a class="icon-link" href="<?php echo esc_url( pixva_route_url( 'tracking' ) ); ?>"><?php echo wp_kses( pixva_icon( 'track' ), pixva_svg_allowed() ); ?><span><?php esc_html_e( 'پیگیری', 'pixva' ); ?></span></a>
				<a class="icon-link" href="<?php echo esc_url( pixva_route_url( 'account' ) ); ?>"><?php echo wp_kses( pixva_icon( 'user' ), pixva_svg_allowed() ); ?><span><?php echo is_user_logged_in() ? esc_html__( 'حساب من', 'pixva' ) : esc_html__( 'ورود', 'pixva' ); ?></span></a>
				<a class="btn btn--accent btn--sm" data-track="cta_click" data-track-label="booking" data-track-location="header" href="<?php echo esc_url( pixva_route_url( 'booking' ) ); ?>"><?php esc_html_e( 'ثبت درخواست تعمیر', 'pixva' ); ?></a>
			</div>
		</nav>
	</div>
</header>

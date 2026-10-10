<?php
/**
 * PIXVA widgets for the Elementor editor (free Elementor).
 *
 * Registration is deferred to Elementor's own `elementor/widgets/register`
 * action because Elementor 4.x loads Widget_Base lazily — checking for it at
 * theme-load time always fails. The class definitions live next door in
 * elementor-widgets-classes.php and are included from the callback, so this
 * file is safe to load with Elementor inactive (nothing hooks, nothing
 * fatals).
 *
 * Every widget delegates to the theme's existing render functions and real
 * data sources — business logic stays in the secure WP layer.
 *
 * @package Pixva
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register every PIXVA widget with Elementor (v3 register_widget_type and
 * v4 register both supported).
 *
 * @param object $manager Elementor widgets manager.
 * @return void
 */
function pixva_register_elementor_widgets( $manager ) {
	if ( ! class_exists( '\Elementor\Widget_Base' ) ) {
		return;
	}
	if ( ! class_exists( 'Pixva_Elementor_Widget' ) ) {
		require_once __DIR__ . '/elementor-widgets-classes.php';
	}
	$widgets = array(
		new Pixva_Elementor_Hero(),
		new Pixva_Elementor_Services(),
		new Pixva_Elementor_Brands(),
		new Pixva_Elementor_Problems(),
		new Pixva_Elementor_Tools(),
		new Pixva_Elementor_Steps(),
		new Pixva_Elementor_FAQ(),
		new Pixva_Elementor_CTA(),
		new Pixva_Elementor_Contact(),
		new Pixva_Elementor_Notice(),
		new Pixva_Elementor_Breadcrumbs(),
		new Pixva_Elementor_Posts(),
		new Pixva_Elementor_Booking(),
		new Pixva_Elementor_Tracking(),
		new Pixva_Elementor_Warranty(),
		new Pixva_Elementor_ContactForm(),
		new Pixva_Elementor_ServiceModes(),
	);
	foreach ( $widgets as $widget ) {
		if ( method_exists( $manager, 'register' ) ) {
			$manager->register( $widget );
		} else {
			$manager->register_widget_type( $widget );
		}
	}
}
add_action( 'elementor/widgets/register', 'pixva_register_elementor_widgets', 20 );

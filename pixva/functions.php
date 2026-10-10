<?php
/**
 * PIXVA theme bootstrap.
 *
 * This file only defines constants and loads modules in dependency order.
 * Every module is self-contained and documented in docs/architecture.md.
 *
 * @package Pixva
 * @since   2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'PIXVA_VERSION', '2.0.0' );
define( 'PIXVA_DIR', get_template_directory() );
define( 'PIXVA_URI', get_template_directory_uri() );

/*
 * Load order matters:
 * helpers → security → business claims → capabilities → meta framework →
 * content model → routes → domain modules → presentation → admin → migration.
 */
$pixva_modules = array(
	'helpers',
	'security',
	'business-claims',
	'capabilities',
	'meta-fields',
	'content-model',
	'routes',
	'redirects',
	'repairs',
	'pricing',
	'diagnosis',
	'forms',
	'account',
	'dashboard',
	'rest',
	'analytics',
	'seo',
	'schema',
	'template-tags',
	'setup',
	'admin',
	'migration',
	'elementor',
	'elementor-widgets',
	'shortcodes',
	'announcement',
);

foreach ( $pixva_modules as $pixva_module ) {
	require_once PIXVA_DIR . '/inc/' . $pixva_module . '.php';
}
unset( $pixva_modules, $pixva_module );

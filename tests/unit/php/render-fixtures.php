<?php
/**
 * Render the server fragments that the browser inserts (order view, warranty
 * view, calculator result) with hostile values, and write them as JSON fixtures
 * for tests/unit/js/safe-html-fixtures.test.cjs.
 *
 * Escaping: WordPress's esc_html()/esc_attr() are replaced by equivalent stubs
 * (htmlspecialchars with ENT_QUOTES). wp_kses() is an identity stub, because the
 * only input it sees here is the theme's own SVG icon markup. This is a model
 * of WordPress escaping, not WordPress itself.
 *
 * Run: php tests/unit/php/render-fixtures.php  (writes tests/unit/js/fixtures/render.json)
 */

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}
if ( ! defined( 'HOUR_IN_SECONDS' ) ) {
	define( 'HOUR_IN_SECONDS', 3600 );
}

function esc_html( $s ) {
	return htmlspecialchars( (string) $s, ENT_QUOTES, 'UTF-8' );
}
function esc_attr( $s ) {
	return htmlspecialchars( (string) $s, ENT_QUOTES, 'UTF-8' );
}
function esc_url( $s ) {
	return (string) $s;
}
function esc_html__( $s, $d = '' ) {
	return esc_html( $s );
}
function esc_attr__( $s, $d = '' ) {
	return esc_attr( $s );
}
function __( $s, $d = '' ) {
	return (string) $s;
}
function _x( $s, $c, $d = '' ) {
	return (string) $s;
}
function wp_kses( $html, $allowed ) {
	return (string) $html;
}
function wp_json_encode( $v, $o = 0, $d = 512 ) {
	return json_encode( $v, $o, $d );
}
function apply_filters( $tag, $value, ...$args ) {
	return $value;
}
function add_action( ...$args ) {
}
function add_filter( ...$args ) {
}
function add_shortcode( ...$args ) {
}
function get_option( $name, $default = false ) {
	return $default;
}
function wp_date( $f, $t = null ) {
	return '2026-10-09';
}

$root = dirname( __DIR__, 3 ) . '/pixva/inc/';
require_once $root . 'helpers.php';
require_once $root . 'pricing.php';
require_once $root . 'template-tags.php';

$hostile = '<img src=x onerror="alert(1)"><svg onload="alert(2)"></svg>"\'><script>alert(3)</script>';
$view    = array(
	'code'        => 'PXV-ABC-123',
	'status'      => 'received',
	'label'       => 'دستگاه تحویل گرفته شد ' . $hostile,
	'device'      => 'تلویزیون ' . $hostile,
	'description' => $hostile,
	'estimate'    => '۱۲۰٬۰۰۰ تومان ' . $hostile,
	'step'        => 2,
	'milestones'  => array(
		1 => 'ثبت ' . $hostile,
		2 => 'تحویل',
		3 => 'تعمیر',
	),
	'warranty'    => array(
		'state' => 'active',
		'until' => '2027-01-01',
		'left'  => 80,
	),
	'history'     => array(
		array(
			'iso'   => '2026-10-09T10:00:00Z" onclick="alert(4)',
			'date'  => '۱۴۰۵/۰۷/۱۷',
			'label' => 'تغییر ' . $hostile,
			'note'  => $hostile,
		),
	),
	'created'     => '۱۴۰۵/۰۷/۱۷ ' . $hostile,
);

$warranty = array(
	'code'     => 'PXV-ABC-123' . $hostile,
	'device'   => 'Samsung ' . $hostile,
	'warranty' => array(
		'state' => 'expired',
		'until' => '2026-01-01',
		'left'  => 0,
	),
);

$calc = array(
	'available' => true,
	'label'     => 'نمایشگر ' . $hostile,
	'min'       => 100000,
	'max'       => 200000,
	'currency'  => 'تومان',
);

$out = array();
ob_start();
pixva_order_view( $view );
$out['order_view'] = ob_get_clean();

ob_start();
pixva_warranty_view( $warranty );
$out['warranty_view'] = ob_get_clean();

ob_start();
pixva_calc_result( $calc );
$out['calc_result'] = ob_get_clean();

$dir = __DIR__ . '/../../unit/js/fixtures';
if ( ! is_dir( $dir ) ) {
	mkdir( $dir, 0775, true );
}
file_put_contents( $dir . '/render.json', json_encode( $out, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT ) . "\n" );
echo 'fixtures: ' . count( $out ) . "\n";

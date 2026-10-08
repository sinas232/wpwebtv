<?php
/**
 * Provider-agnostic analytics (§39).
 *
 * The theme never loads a tracking vendor. `pixva.track(name, props)` in
 * assets/js/app.js pushes `{event: 'pixva_' + name, ...props}` to
 * `window.dataLayer` and dispatches a `pixva:track` CustomEvent, so any tag
 * manager or plugin can consume events. Props are allow-listed in JS and
 * must never contain PII (no names, phones, emails, codes, free text).
 *
 * Event catalogue (also used by the JS allow-list):
 *
 * @package Pixva
 * @since   2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Allowed events.
 *
 * @return string[]
 */
function pixva_analytics_events() {
	return array(
		'search',
		'diagnosis_started',
		'diagnosis_completed',
		'diagnosis_abandoned',
		'price_calculator_started',
		'price_calculator_completed',
		'pixel_test_started',
		'error_code_search',
		'booking_started',
		'booking_submitted',
		'booking_failed',
		'tracking_viewed',
		'warranty_lookup',
		'account_login',
		'account_registration',
		'cta_click',
	);
}

/**
 * Allowed property keys (values are coerced to short strings/numbers in JS).
 *
 * @return string[]
 */
function pixva_analytics_props() {
	return array( 'problem', 'step', 'level', 'result', 'location', 'label', 'route', 'results', 'available', 'pattern', 'brand_known', 'has_photos', 'reason', 'status' );
}

/**
 * Events queued server-side for the next page view (e.g. after login).
 *
 * @return string[]
 */
function pixva_consume_pending_events() {
	$uid = get_current_user_id();
	if ( ! $uid ) {
		return array();
	}
	$event = (string) get_user_meta( $uid, '_pixva_pending_event', true );
	if ( '' === $event ) {
		return array();
	}
	delete_user_meta( $uid, '_pixva_pending_event' );
	return in_array( $event, pixva_analytics_events(), true ) ? array( $event ) : array();
}

/**
 * Search event: emitted on search result pages (count only, no query text).
 *
 * @return array
 */
function pixva_page_events() {
	$events = array();
	foreach ( pixva_consume_pending_events() as $e ) {
		$events[] = array(
			'name'  => $e,
			'props' => array(),
		);
	}
	if ( is_search() ) {
		global $wp_query;
		$events[] = array(
			'name'  => 'search',
			'props' => array( 'results' => (int) $wp_query->found_posts ),
		);
	}
	return $events;
}

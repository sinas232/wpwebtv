<?php
/**
 * Diagnosis engine (§06, §07).
 *
 * Flow: brand → model → problem → symptoms → extra info → possible
 * diagnosis → estimate (only if configured) → action → booking.
 *
 * All evaluation is server-side (pixva_diagnose) and shared by the no-JS
 * GET flow (page-templates/diagnosis.php) and the REST endpoint
 * (POST /pixva/v1/diagnosis). The knowledge base is inc/data/diagnosis-rules.php.
 *
 * @package Pixva
 * @since   2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Problem rules (filterable, cached per request).
 *
 * @return array<string,array>
 */
function pixva_diagnosis_problems() {
	static $rules = null;
	if ( null === $rules ) {
		$rules = (array) apply_filters( 'pixva_diagnosis_rules', require PIXVA_DIR . '/inc/data/diagnosis-rules.php' );
	}
	return $rules;
}

/**
 * TV age options for the extra-info step.
 *
 * @return array<string,string>
 */
function pixva_diagnosis_ages() {
	return array(
		'new' => __( 'کمتر از ۲ سال', 'pixva' ),
		'mid' => __( '۲ تا ۵ سال', 'pixva' ),
		'old' => __( 'بیشتر از ۵ سال', 'pixva' ),
		''    => __( 'نمی‌دانم', 'pixva' ),
	);
}

/**
 * Level labels.
 *
 * @return array<string,string>
 */
function pixva_diagnosis_levels() {
	return array(
		'likely'   => __( 'محتمل', 'pixva' ),
		'possible' => __( 'ممکن', 'pixva' ),
		'inspect'  => __( 'نیازمند بررسی', 'pixva' ),
	);
}

/**
 * Published brands for selectors: id => title.
 *
 * @return array<int,string>
 */
function pixva_brand_choices() {
	$out = array();
	foreach ( get_posts(
		array(
			'post_type'      => 'tv_brands',
			'post_status'    => 'publish',
			'posts_per_page' => 200,
			'orderby'        => 'title',
			'order'          => 'ASC',
			'no_found_rows'  => true,
		)
	) as $p ) {
		$out[ (int) $p->ID ] = pixva_brand_label( $p->ID );
	}
	asort( $out );
	return $out;
}

/**
 * Short brand name for selects and device labels. Brand pages are often
 * titled for SEO («تعمیر تلویزیون ال‌جی»); the label drops that prefix
 * without changing the post title.
 *
 * @param int $brand_id Brand post.
 * @return string
 */
function pixva_brand_label( $brand_id ) {
	$title = wp_strip_all_tags( get_the_title( $brand_id ) );
	$short = trim( (string) preg_replace( '/^(?:تعمیرات|تعمیر)\s+(?:تلویزیون|تی\s*وی|TV)\s+/u', '', $title ) );
	return '' !== $short ? $short : $title;
}

/**
 * Published model titles of a brand (for suggestions).
 *
 * @param int $brand_id Brand.
 * @return array<int,string>
 */
function pixva_brand_models( $brand_id ) {
	if ( ! $brand_id ) {
		return array();
	}
	$out = array();
	$q   = get_posts(
		array(
			'post_type'      => 'tv_model',
			'post_status'    => 'publish',
			'posts_per_page' => 200,
			'orderby'        => 'title',
			'order'          => 'ASC',
			'no_found_rows'  => true,
			'meta_key'       => '_pixva_brand_id', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'meta_value'     => (int) $brand_id, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
		)
	);
	foreach ( $q as $p ) {
		$code                = (string) get_post_meta( $p->ID, '_pixva_model_code', true );
		$out[ (int) $p->ID ] = '' !== $code ? $code : get_the_title( $p );
	}
	return $out;
}

/**
 * Published service post by slug (for cause → service links).
 *
 * @param string $slug Slug.
 * @return WP_Post|null
 */
function pixva_service_by_slug( $slug ) {
	if ( '' === $slug ) {
		return null;
	}
	$p = get_page_by_path( $slug, OBJECT, 'tv_services' );
	return ( $p && 'publish' === $p->post_status ) ? $p : null;
}

/**
 * Validate raw diagnosis input.
 *
 * @param array $in Raw input: brand (id|other), model, problem, symptoms[], age, size.
 * @return array|WP_Error Clean input.
 */
function pixva_diagnosis_input( $in ) {
	$problems = pixva_diagnosis_problems();
	$problem  = sanitize_key( (string) ( $in['problem'] ?? '' ) );
	if ( ! isset( $problems[ $problem ] ) ) {
		return new WP_Error(
			'problem',
			__( 'لطفاً مشکل اصلی را انتخاب کنید.', 'pixva' ),
			array(
				'status' => 400,
				'field'  => 'problem',
			)
		);
	}
	$brand_id = absint( $in['brand'] ?? 0 );
	if ( $brand_id && 'tv_brands' !== get_post_type( $brand_id ) ) {
		$brand_id = 0;
	}
	$symptoms = array_values( array_intersect( array_map( 'sanitize_key', (array) ( $in['symptoms'] ?? array() ) ), array_keys( $problems[ $problem ]['symptoms'] ) ) );
	$age      = sanitize_key( (string) ( $in['age'] ?? '' ) );
	$size     = sanitize_key( (string) ( $in['size'] ?? '' ) );
	return array(
		'brand'    => $brand_id,
		'model'    => pixva_substr( sanitize_text_field( (string) ( $in['model'] ?? '' ) ), 0, 60 ),
		'problem'  => $problem,
		'symptoms' => $symptoms,
		'age'      => array_key_exists( $age, pixva_diagnosis_ages() ) ? $age : '',
		'size'     => isset( pixva_pricing()['sizes'][ $size ] ) ? $size : '',
	);
}

/**
 * Evaluate the rules.
 *
 * @param array $in Clean input from pixva_diagnosis_input().
 * @return array Result payload (safe for public output).
 */
function pixva_diagnose( $in ) {
	$rule   = pixva_diagnosis_problems()[ $in['problem'] ];
	$levels = pixva_diagnosis_levels();
	$scored = array();
	foreach ( $rule['causes'] as $key => $c ) {
		list( $label, $desc, $base, $boost, $service, $old ) = array_pad( $c, 6, 0 );
		$score = (float) $base;
		foreach ( $in['symptoms'] as $s ) {
			$score += (float) ( $boost[ $s ] ?? 0 );
		}
		if ( 'old' === $in['age'] ) {
			$score += (float) $old;
		}
		$scored[ $key ] = array(
			'key'     => $key,
			'label'   => $label,
			'desc'    => $desc,
			'score'   => $score,
			'service' => (string) $service,
		);
	}
	uasort( $scored, static fn( $a, $b ) => $b['score'] <=> $a['score'] );
	$causes = array();
	$i      = 0;
	foreach ( $scored as $c ) {
		if ( $i >= 3 ) {
			break;
		}
		if ( 0 === $i && $c['score'] >= 5 ) {
			$level = 'likely';
		} elseif ( $c['score'] >= 3 ) {
			$level = 'possible';
		} else {
			$level = 'inspect';
		}
		$svc      = pixva_service_by_slug( $c['service'] );
		$causes[] = array(
			'key'         => $c['key'],
			'label'       => $c['label'],
			'desc'        => $c['desc'],
			'level'       => $level,
			'level_label' => $levels[ $level ],
			'service'     => $svc ? array(
				'title' => get_the_title( $svc ),
				'url'   => get_permalink( $svc ),
				'id'    => (int) $svc->ID,
			) : null,
		);
		++$i;
	}

	$danger = ! empty( $rule['always_danger'] ) || (bool) array_intersect( $in['symptoms'], (array) ( $rule['danger'] ?? array() ) );

	// Estimate only when the top cause links to a service with a configured price row.
	$estimate = null;
	$top_svc  = $causes[0]['service']['id'] ?? 0;
	if ( $top_svc ) {
		$key = (string) get_post_meta( $top_svc, '_pixva_service_pricing', true );
		if ( '' !== $key ) {
			$e = pixva_estimate( $key, $in['size'], $in['brand'] );
			if ( $e['available'] ) {
				$estimate = array(
					'label'      => $e['label'],
					'range'      => pixva_format_range( $e['min'], $e['max'], $e['currency'] ),
					'disclaimer' => $e['disclaimer'],
					'updated'    => $e['updated'],
				);
			}
		}
	}

	$brand_title = $in['brand'] ? pixva_brand_label( $in['brand'] ) : '';
	$booking     = pixva_route_url(
		'booking',
		array_filter(
			array(
				'problem'  => $in['problem'],
				'brand'    => $in['brand'] ? (string) $in['brand'] : '',
				'model'    => $in['model'],
				'symptoms' => implode( ',', $in['symptoms'] ),
				'age'      => $in['age'],
				'from'     => 'diagnosis',
			)
		)
	);

	return array(
		'problem'     => array(
			'key'   => $in['problem'],
			'label' => $rule['label'],
		),
		'device'      => trim( $brand_title . ' ' . $in['model'] ),
		'causes'      => $causes,
		'danger'      => $danger,
		'safe'        => array_values( (array) $rule['safe'] ),
		'estimate'    => $estimate,
		'inspection'  => pixva_has_claim( 'inspection_fee' ) ? (string) pixva_claim( 'inspection_fee' ) : '',
		'error_codes' => ( 'blink' === $in['problem'] ) ? pixva_route_url( 'error_codes', $in['brand'] ? array( 'brand' => (string) $in['brand'] ) : array() ) : '',
		'booking_url' => $booking,
		'disclaimer'  => __( 'این نتیجه بر اساس پاسخ‌های شما و الگوهای رایج خرابی است و جای بررسی تکنسین را نمی‌گیرد. تشخیص قطعی فقط پس از بازدید دستگاه ممکن است.', 'pixva' ),
	);
}

/**
 * Public, JSON-safe rules for the progressive-enhancement wizard
 * (labels and symptoms only; scoring stays on the server).
 *
 * @return array
 */
function pixva_diagnosis_client_data() {
	$problems = array();
	foreach ( pixva_diagnosis_problems() as $key => $p ) {
		$problems[ $key ] = array(
			'label'    => $p['label'],
			'desc'     => $p['desc'],
			'symptoms' => $p['symptoms'],
		);
	}
	return $problems;
}

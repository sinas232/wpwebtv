<?php
/**
 * Structured data (§20). One JSON-LD @graph per page, built only from data
 * that exists. Never: AggregateRating, Review, Offer/price, invented
 * opening hours, invented address or phone.
 *
 * | context        | types                                              |
 * |----------------|----------------------------------------------------|
 * | every page     | WebSite, Organization (name/url/logo/sameAs only)  |
 * | business data  | LocalBusiness — only if address + city + phone set |
 * | non-front      | BreadcrumbList                                     |
 * | post           | BlogPosting                                        |
 * | error code     | TechArticle                                        |
 * | service        | Service (provider = Organization/LocalBusiness)    |
 * | FAQ content    | FAQPage (from real Q&A pairs only)                 |
 * | tools          | WebApplication (free, no rating)                   |
 * | archives       | CollectionPage                                     |
 *
 * @package Pixva
 * @since   2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Build the graph for the current request.
 *
 * @return array
 */
function pixva_schema_graph() {
	$home  = home_url( '/' );
	$org   = $home . '#organization';
	$graph = array();

	$graph[] = array(
		'@type'           => 'WebSite',
		'@id'             => $home . '#website',
		'url'             => $home,
		'name'            => get_bloginfo( 'name' ),
		'inLanguage'      => 'fa-IR',
		'publisher'       => array( '@id' => $org ),
		'potentialAction' => array(
			'@type'       => 'SearchAction',
			'target'      => home_url( '/?s={search_term_string}' ),
			'query-input' => 'required name=search_term_string',
		),
	);

	$organization = array(
		'@type' => pixva_has_local_business() ? 'LocalBusiness' : 'Organization',
		'@id'   => $org,
		'name'  => pixva_business_name(),
		'url'   => $home,
	);
	$logo_id      = (int) get_theme_mod( 'custom_logo' );
	if ( $logo_id && wp_get_attachment_image_url( $logo_id, 'full' ) ) {
		$organization['logo'] = wp_get_attachment_image_url( $logo_id, 'full' );
	}
	$same = array_values( array_map( static fn( $s ) => $s[1], pixva_social_links() ) );
	if ( $same ) {
		$organization['sameAs'] = $same;
	}
	if ( pixva_has_local_business() ) {
		$organization['address']   = array_filter(
			array(
				'@type'           => 'PostalAddress',
				'streetAddress'   => (string) pixva_claim( 'address' ),
				'addressLocality' => (string) pixva_claim( 'city' ),
				'addressRegion'   => (string) pixva_claim( 'region' ),
				'postalCode'      => (string) pixva_claim( 'postal_code' ),
				'addressCountry'  => 'IR',
			)
		);
		$organization['telephone'] = pixva_primary_phone();
		if ( pixva_has_claim( 'latitude' ) && pixva_has_claim( 'longitude' ) ) {
			$organization['geo'] = array(
				'@type'     => 'GeoCoordinates',
				'latitude'  => (float) pixva_claim( 'latitude' ),
				'longitude' => (float) pixva_claim( 'longitude' ),
			);
		}
		if ( pixva_has_claim( 'map_url' ) ) {
			$organization['hasMap'] = (string) pixva_claim( 'map_url' );
		}
		if ( pixva_has_claim( 'service_area' ) ) {
			$organization['areaServed'] = (string) pixva_claim( 'service_area' );
		}
	}
	if ( pixva_has_claim( 'email' ) ) {
		$organization['email'] = (string) pixva_claim( 'email' );
	}
	$graph[] = $organization;

	if ( ! is_front_page() ) {
		$crumbs = pixva_breadcrumb_items();
		if ( count( $crumbs ) > 1 ) {
			$list = array();
			foreach ( $crumbs as $i => $c ) {
				$item = array(
					'@type'    => 'ListItem',
					'position' => $i + 1,
					'name'     => $c['title'],
				);
				if ( '' !== $c['url'] ) {
					$item['item'] = $c['url'];
				}
				$list[] = $item;
			}
			$graph[] = array(
				'@type'           => 'BreadcrumbList',
				'@id'             => pixva_canonical_url() . '#breadcrumb',
				'itemListElement' => $list,
			);
		}
	}

	if ( is_singular() ) {
		$post = get_queried_object();
		$url  = get_permalink( $post );
		$img  = has_post_thumbnail( $post ) ? get_the_post_thumbnail_url( $post, 'large' ) : '';
		if ( 'post' === $post->post_type || 'pixva_error' === $post->post_type ) {
			$article = array(
				'@type'            => 'post' === $post->post_type ? 'BlogPosting' : 'TechArticle',
				'@id'              => $url . '#article',
				'headline'         => get_the_title( $post ),
				'datePublished'    => get_post_time( 'c', true, $post ),
				'dateModified'     => get_post_modified_time( 'c', true, $post ),
				'mainEntityOfPage' => $url,
				'inLanguage'       => 'fa-IR',
				'publisher'        => array( '@id' => $org ),
				'author'           => array(
					'@type' => 'Person',
					'name'  => get_the_author_meta( 'display_name', (int) $post->post_author ),
				),
			);
			if ( $img ) {
				$article['image'] = $img;
			}
			$graph[] = $article;
		}
		if ( 'tv_services' === $post->post_type ) {
			$service = array(
				'@type'       => 'Service',
				'@id'         => $url . '#service',
				'name'        => get_the_title( $post ),
				'serviceType' => get_the_title( $post ),
				'url'         => $url,
				'provider'    => array( '@id' => $org ),
				'description' => pixva_meta_description(),
			);
			if ( pixva_has_claim( 'service_area' ) ) {
				$service['areaServed'] = (string) pixva_claim( 'service_area' );
			}
			$graph[] = $service;
		}
		$pairs = array();
		if ( 'tv_services' === $post->post_type ) {
			$pairs = pixva_meta_faq_pairs( $post->ID, '_pixva_service_faq' );
		} elseif ( 'post' === $post->post_type ) {
			$pairs = pixva_meta_faq_pairs( $post->ID, '_pixva_post_faq' );
		}
		if ( 'faq' === pixva_current_route() ) {
			foreach ( pixva_faq_items() as $f ) {
				$pairs[] = array(
					'q' => $f['q'],
					'a' => wp_strip_all_tags( $f['a'] ),
				);
			}
		}
		if ( $pairs ) {
			$graph[] = array(
				'@type'      => 'FAQPage',
				'@id'        => $url . '#faq',
				'mainEntity' => array_map(
					static fn( $p ) => array(
						'@type'          => 'Question',
						'name'           => $p['q'],
						'acceptedAnswer' => array(
							'@type' => 'Answer',
							'text'  => $p['a'],
						),
					),
					$pairs
				),
			);
		}
		$route = pixva_current_route();
		if ( $route && 'WebApplication' === ( pixva_routes()[ $route ]['schema'] ?? '' ) ) {
			$graph[] = array(
				'@type'               => 'WebApplication',
				'@id'                 => $url . '#app',
				'name'                => get_the_title( $post ),
				'url'                 => $url,
				'applicationCategory' => 'UtilitiesApplication',
				'operatingSystem'     => 'Any',
				'isAccessibleForFree' => true,
				'inLanguage'          => 'fa-IR',
				'publisher'           => array( '@id' => $org ),
			);
		}
	} elseif ( is_post_type_archive() || is_category() || is_tax( 'tv_problem' ) || is_home() ) {
		$graph[] = array(
			'@type'      => 'CollectionPage',
			'@id'        => pixva_canonical_url() . '#collection',
			'url'        => pixva_canonical_url(),
			'name'       => wp_get_document_title(),
			'inLanguage' => 'fa-IR',
			'isPartOf'   => array( '@id' => $home . '#website' ),
		);
	}
	return (array) apply_filters( 'pixva_schema_graph', $graph );
}

/**
 * Print JSON-LD (skipped on non-indexable pages and when an SEO plugin owns schema).
 *
 * @return void
 */
function pixva_print_schema() {
	if ( pixva_seo_plugin_active() || ! pixva_is_indexable() ) {
		return;
	}
	$json = wp_json_encode(
		array(
			'@context' => 'https://schema.org',
			'@graph'   => pixva_schema_graph(),
		),
		JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP
	);
	if ( $json ) {
		echo '<script type="application/ld+json">' . $json . '</script>' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON with HEX_TAG.
	}
}
add_action( 'wp_head', 'pixva_print_schema', 30 );

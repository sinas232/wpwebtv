<?php
/**
 * اسکیماهای JSON-LD و متاهای اجتماعی قالب پیکسوا
 *
 * - LocalBusiness / RepairService برای صفحه اصلی و تماس
 * - TechArticle برای مقالات
 * - FAQPage برای برگه و مقاله‌های دارای پرسش متداول
 * - BreadcrumbList برای مسیر صفحات
 * - Service برای تک‌خدمت
 *
 * @package Pixva
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * چاپ یک گراف JSON-LD امن.
 *
 * @param array $graph گره‌های اسکیما.
 * @return void
 */
function pixva_print_schema_graph( $graph ) {
	$graph = array_values( array_filter( $graph ) );
	if ( empty( $graph ) ) {
		return;
	}

	$payload = array(
		'@context' => 'https://schema.org',
		'@graph'   => $graph,
	);

	echo '<script type="application/ld+json">' . wp_json_encode(
		$payload,
		JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
	) . '</script>' . "\n";
}

/**
 * شناسه پایدار کسب‌وکار برای ارجاع بین گره‌ها.
 *
 * @return string
 */
function pixva_schema_business_id() {
	return trailingslashit( home_url( '/' ) ) . '#repair-service';
}

/**
 * تصویر شاخص یا تصویر پیش‌فرض قالب.
 *
 * @param int $post_id شناسه نوشته. صفر یعنی تصویر پیش‌فرض.
 * @return string
 */
function pixva_schema_image( $post_id = 0 ) {
	if ( $post_id && has_post_thumbnail( $post_id ) ) {
		$url = get_the_post_thumbnail_url( $post_id, 'full' );
		if ( $url ) {
			return $url;
		}
	}
	return PIXVA_URI . '/assets/images/hero-workshop.jpg';
}

/**
 * گره LocalBusiness + RepairService.
 *
 * @return array
 */
function pixva_schema_local_business() {
	$phone   = (string) pixva_option( 'pixva_support_phone', '02191009990' );
	$address = (string) pixva_option( 'pixva_workshop_address', '' );
	$logo    = (string) pixva_option( 'pixva_logo_light', '' );
	if ( '' === $logo ) {
		$logo = pixva_schema_image( 0 );
	}

	$same_as = array();
	foreach ( array( 'instagram', 'telegram', 'whatsapp', 'linkedin', 'youtube' ) as $network ) {
		$url = (string) pixva_option( 'pixva_social_' . $network, '' );
		if ( '' !== $url ) {
			$same_as[] = $url;
		}
	}

	$node = array(
		'@type'                     => array( 'LocalBusiness', 'RepairService' ),
		'@id'                       => pixva_schema_business_id(),
		'name'                      => get_bloginfo( 'name' ),
		'description'               => get_bloginfo( 'description' ) ? get_bloginfo( 'description' ) : __( 'مرکز تخصصی تعمیر تلویزیون و نمایشگر پیکسوا', 'pixva' ),
		'url'                       => home_url( '/' ),
		'image'                     => pixva_schema_image( 0 ),
		'logo'                      => $logo,
		'telephone'                 => $phone,
		'priceRange'                => '$$',
		'currenciesAccepted'        => 'IRR',
		'address'                   => array(
			'@type'           => 'PostalAddress',
			'streetAddress'   => $address,
			'addressLocality' => 'تهران',
			'addressCountry'  => 'IR',
		),
		'areaServed'                => array(
			'@type' => 'City',
			'name'  => 'تهران',
		),
		'openingHoursSpecification' => array(
			array(
				'@type'     => 'OpeningHoursSpecification',
				'dayOfWeek' => array( 'Saturday', 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday' ),
				'opens'     => '09:00',
				'closes'    => '20:00',
			),
			array(
				'@type'     => 'OpeningHoursSpecification',
				'dayOfWeek' => 'Friday',
				'opens'     => '10:00',
				'closes'    => '16:00',
			),
		),
	);

	if ( ! empty( $same_as ) ) {
		$node['sameAs'] = $same_as;
	}

	return $node;
}

/**
 * گره TechArticle برای مقاله وبلاگ.
 *
 * @return array
 */
function pixva_schema_tech_article() {
	$post_id = get_the_ID();
	$author  = get_the_author_meta( 'display_name', (int) get_post_field( 'post_author', $post_id ) );
	$diff    = (string) get_post_meta( $post_id, '_pixva_post_difficulty', true );
	$levels  = array(
		'easy'   => 'Beginner',
		'medium' => 'Expert',
		'hard'   => 'Expert',
	);

	$node = array(
		'@type'            => 'TechArticle',
		'@id'              => get_permalink( $post_id ) . '#article',
		'headline'         => get_the_title( $post_id ),
		'description'      => has_excerpt( $post_id ) ? get_the_excerpt( $post_id ) : wp_trim_words( wp_strip_all_tags( (string) get_post_field( 'post_content', $post_id ) ), 32 ),
		'image'            => pixva_schema_image( $post_id ),
		'datePublished'    => get_the_date( 'c', $post_id ),
		'dateModified'     => get_the_modified_date( 'c', $post_id ),
		'inLanguage'       => 'fa-IR',
		'mainEntityOfPage' => get_permalink( $post_id ),
		'author'           => array(
			'@type' => 'Person',
			'name'  => $author ? $author : get_bloginfo( 'name' ),
		),
		'publisher'        => array(
			'@id' => pixva_schema_business_id(),
		),
		'proficiencyLevel' => isset( $levels[ $diff ] ) ? $levels[ $diff ] : 'Expert',
	);

	$tools = (string) get_post_meta( $post_id, '_pixva_post_tools', true );
	if ( '' !== $tools ) {
		$node['dependencies'] = $tools;
	}

	return $node;
}

/**
 * گره Service برای تک‌خدمت.
 *
 * @return array
 */
function pixva_schema_service() {
	$post_id = get_the_ID();
	return array(
		'@type'       => 'Service',
		'@id'         => get_permalink( $post_id ) . '#service',
		'name'        => get_the_title( $post_id ),
		'description' => has_excerpt( $post_id ) ? get_the_excerpt( $post_id ) : wp_trim_words( wp_strip_all_tags( (string) get_post_field( 'post_content', $post_id ) ), 36 ),
		'url'         => get_permalink( $post_id ),
		'image'       => pixva_schema_image( $post_id ),
		'provider'    => array(
			'@id' => pixva_schema_business_id(),
		),
		'areaServed'  => 'تهران',
		'serviceType' => __( 'تعمیر تلویزیون و نمایشگر', 'pixva' ),
	);
}

/**
 * گره FAQPage.
 *
 * @param array $items پرسش و پاسخ‌ها.
 * @return array
 */
function pixva_schema_faq( $items ) {
	$entities = array();
	foreach ( $items as $item ) {
		if ( empty( $item['q'] ) || empty( $item['a'] ) ) {
			continue;
		}
		$entities[] = array(
			'@type'          => 'Question',
			'name'           => $item['q'],
			'acceptedAnswer' => array(
				'@type' => 'Answer',
				'text'  => $item['a'],
			),
		);
	}

	return array(
		'@type'      => 'FAQPage',
		'@id'        => ( is_singular() ? get_permalink() : home_url( '/' ) ) . '#faq',
		'mainEntity' => $entities,
	);
}

/**
 * گره BreadcrumbList.
 *
 * @param array $crumbs مسیر.
 * @return array
 */
function pixva_schema_breadcrumbs( $crumbs ) {
	$elements = array();
	$position = 1;
	foreach ( $crumbs as $crumb ) {
		$item = array(
			'@type'    => 'ListItem',
			'position' => $position,
			'name'     => $crumb['name'],
		);
		if ( ! empty( $crumb['url'] ) ) {
			$item['item'] = $crumb['url'];
		}
		$elements[] = $item;
		++$position;
	}

	return array(
		'@type'           => 'BreadcrumbList',
		'@id'             => ( is_singular() ? get_permalink() : home_url( add_query_arg( array() ) ) ) . '#breadcrumb',
		'itemListElement' => $elements,
	);
}

/**
 * خروجی اسکیما در سربرگ.
 *
 * @return void
 */
function pixva_output_schema() {
	if ( is_admin() ) {
		return;
	}

	$graph = array();

	if ( is_front_page() || is_page_template( 'page-templates/page-contact.php' ) ) {
		$graph[] = pixva_schema_local_business();
	}

	if ( is_singular( 'post' ) ) {
		$graph[] = pixva_schema_tech_article();
	}

	if ( is_singular( 'tv_services' ) ) {
		$graph[] = pixva_schema_service();
	}

	if ( function_exists( 'pixva_current_faq_items' ) ) {
		$faq = pixva_current_faq_items();
		if ( count( $faq ) > 0 ) {
			$graph[] = pixva_schema_faq( $faq );
		}
	}

	if ( ! is_front_page() && function_exists( 'pixva_get_breadcrumbs' ) ) {
		$crumbs = pixva_get_breadcrumbs();
		if ( count( $crumbs ) > 1 ) {
			$graph[] = pixva_schema_breadcrumbs( $crumbs );
		}
	}

	pixva_print_schema_graph( $graph );
}
add_action( 'wp_head', 'pixva_output_schema', 20 );

/**
 * متاهای Open Graph و Twitter برای اشتراک‌گذاری.
 *
 * @return void
 */
function pixva_output_social_meta() {
	if ( is_admin() ) {
		return;
	}

	$title = wp_get_document_title();
	$desc  = get_bloginfo( 'description' );
	$url   = home_url( '/' );
	$image = pixva_schema_image( 0 );
	$type  = 'website';

	if ( is_singular() ) {
		$post_id = get_the_ID();
		$url     = get_permalink( $post_id );
		$image   = pixva_schema_image( $post_id );
		$type    = is_singular( 'post' ) ? 'article' : 'website';
		if ( has_excerpt( $post_id ) ) {
			$desc = get_the_excerpt( $post_id );
		} else {
			$desc = wp_trim_words( wp_strip_all_tags( (string) get_post_field( 'post_content', $post_id ) ), 28, '…' );
		}
	} elseif ( is_category() || is_tag() || is_tax() ) {
		$term = get_queried_object();
		if ( $term instanceof WP_Term ) {
			$url  = get_term_link( $term );
			$desc = $term->description ? $term->description : $term->name;
			if ( is_wp_error( $url ) ) {
				$url = home_url( '/' );
			}
		}
	}

	$desc = wp_strip_all_tags( (string) $desc );

	echo '<meta property="og:locale" content="fa_IR">' . "\n";
	echo '<meta property="og:site_name" content="' . esc_attr( get_bloginfo( 'name' ) ) . '">' . "\n";
	echo '<meta property="og:type" content="' . esc_attr( $type ) . '">' . "\n";
	echo '<meta property="og:title" content="' . esc_attr( $title ) . '">' . "\n";
	echo '<meta property="og:description" content="' . esc_attr( $desc ) . '">' . "\n";
	echo '<meta property="og:url" content="' . esc_url( $url ) . '">' . "\n";
	echo '<meta property="og:image" content="' . esc_url( $image ) . '">' . "\n";
	echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
	echo '<meta name="twitter:title" content="' . esc_attr( $title ) . '">' . "\n";
	echo '<meta name="twitter:description" content="' . esc_attr( $desc ) . '">' . "\n";
	echo '<meta name="twitter:image" content="' . esc_url( $image ) . '">' . "\n";
}
add_action( 'wp_head', 'pixva_output_social_meta', 4 );

<?php
/**
 * Classic shortcodes mirroring the PIXVA Elementor widgets, so every section
 * is editable in the block/classic editor and inside any page builder without
 * depending on Elementor. All renderers delegate to the theme's existing
 * functions — one source of truth for markup and data.
 *
 * @package Pixva
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Services shortcode.
 *
 * @param array $atts Attributes.
 * @return string
 */
function pixva_sc_services( $atts ) {
	$atts  = shortcode_atts(
		array(
			'title' => __( 'خدمات تعمیر', 'pixva' ),
			'count' => '6',
			'more'  => '1',
			'link'  => '',
			'lead'  => '',
		),
		$atts,
		'pixva-services'
	);
	$posts = get_posts(
		array(
			'post_type'      => 'tv_services',
			'post_status'    => 'publish',
			'posts_per_page' => (int) $atts['count'] > 0 ? (int) $atts['count'] : -1,
			'orderby'        => array(
				'menu_order' => 'ASC',
				'title'      => 'ASC',
			),
			'no_found_rows'  => true,
		)
	);
	$more  = '' !== $atts['link'] ? $atts['link'] : ( '1' === $atts['more'] ? pixva_route_url( 'services' ) : '' );
	ob_start();
	pixva_section_open( 'sc-services', $atts['title'], $more, $atts['lead']);
	if ( $posts ) {
		pixva_card_grid( $posts );
	}
	pixva_section_close();
	return ob_get_clean();
}
add_shortcode( 'pixva_services', 'pixva_sc_services' );

/**
 * Brands shortcode.
 *
 * @param array $atts Attributes.
 * @return string
 */
function pixva_sc_brands( $atts ) {
	$atts  = shortcode_atts(
		array(
			'title' => __( 'برندها', 'pixva' ),
			'count' => '24',
			'more'  => '1',
			'link'  => '',
			'lead'  => '',
		),
		$atts,
		'pixva-brands'
	);
	$posts = get_posts(
		array(
			'post_type'      => 'tv_brands',
			'post_status'    => 'publish',
			'posts_per_page' => (int) $atts['count'] > 0 ? (int) $atts['count'] : -1,
			'orderby'        => array(
				'menu_order' => 'ASC',
				'title'      => 'ASC',
			),
			'no_found_rows'  => true,
		)
	);
	$more  = '' !== $atts['link'] ? $atts['link'] : ( '1' === $atts['more'] ? pixva_route_url( 'brands' ) : '' );
	ob_start();
	pixva_section_open( 'sc-brands', $atts['title'], $more, $atts['lead']);
	if ( $posts ) {
		echo '<ul class="chips chips--lg">';
		foreach ( $posts as $b ) {
			$en = (string) get_post_meta( $b->ID, '_pixva_brand_en', true );
			echo '<li><a class="chip" href="' . esc_url( get_permalink( $b ) ) . '">' . esc_html( trim( preg_replace( '/^تعمیر\s+(تلویزیون\s+)?/u', '', get_the_title( $b ) ) ) ) . ( '' !== $en ? ' <span lang="en" dir="ltr">' . esc_html( $en ) . '</span>' : '' ) . '</a></li>';
		}
		echo '</ul>';
	}
	pixva_section_close();
	return ob_get_clean();
}
add_shortcode( 'pixva_brands', 'pixva_sc_brands' );

/**
 * Problem tiles shortcode.
 *
 * @param array $atts Attributes.
 * @return string
 */
function pixva_sc_problems( $atts ) {
	$atts = shortcode_atts(
		array(
			'title' => __( 'مشکل رایج خود را انتخاب کنید', 'pixva' ),
			'limit' => '8',
			'more'  => '1',
			'link'  => '',
			'lead'  => '',
		),
		$atts,
		'pixva-problems'
	);
	$more  = '' !== $atts['link'] ? $atts['link'] : ( '1' === $atts['more'] ? pixva_route_url( 'problems' ) : '' );
	ob_start();
	pixva_section_open( 'sc-problems', $atts['title'], $more, $atts['lead']);
	if ( ! pixva_problem_tiles( (int) $atts['limit'] ) ) {
		$problems = array_slice( pixva_diagnosis_problems(), 0, (int) $atts['limit'], true );
		if ( $problems ) {
			echo '<ul class="grid grid--tiles">';
			foreach ( $problems as $k => $p ) {
				echo '<li class="tile"><a href="' . esc_url( pixva_route_url( 'diagnosis', array( 'problem' => $k, 'step' => '1' ) ) ) . '"><span class="tile__title">' . esc_html( $p['label'] ) . '</span><span class="tile__text">' . esc_html( $p['desc'] ) . '</span></a></li>';
			}
			echo '</ul>';
		}
	}
	pixva_section_close();
	return ob_get_clean();
}
add_shortcode( 'pixva_problems', 'pixva_sc_problems' );

/**
 * Tools shortcode.
 *
 * @param array $atts Attributes.
 * @return string
 */
function pixva_sc_tools( $atts ) {
	$atts = shortcode_atts(
		array(
			'title' => __( 'ابزارهای رایگان عیب‌یابی', 'pixva' ),
			'more'  => '1',
			'link'  => '',
			'lead'  => '',
		),
		$atts,
		'pixva-tools'
	);
	$more  = '' !== $atts['link'] ? $atts['link'] : ( '1' === $atts['more'] ? pixva_route_url( 'tools' ) : '' );
	ob_start();
	pixva_section_open( 'sc-tools', $atts['title'], $more, $atts['lead']);
	pixva_tool_cards();
	pixva_section_close();
	return ob_get_clean();
}
add_shortcode( 'pixva_tools', 'pixva_sc_tools' );

/**
 * Process steps shortcode (same four real stages as the home page).
 *
 * @param array $atts Attributes.
 * @return string
 */
function pixva_sc_steps( $atts ) {
	$atts = shortcode_atts(
		array(
			'title' => __( 'روند کار چطور است؟', 'pixva' ),
			'more'  => '0',
			'link'  => '',
			'lead'  => '',
		),
		$atts,
		'pixva-steps'
	);
	$more  = '' !== $atts['link'] ? $atts['link'] : ( '1' === $atts['more'] ? pixva_route_url( 'repair' ) : '' );
	$items = array(
		array( __( 'ثبت درخواست', 'pixva' ), __( 'مشخصات دستگاه و ایراد را ثبت می‌کنید و کد پیگیری می‌گیرید.', 'pixva' ) ),
		array( __( 'بررسی و اعلام هزینه', 'pixva' ), __( 'پس از کارشناسی، علت خرابی و هزینه پیش از شروع کار به شما اعلام می‌شود.', 'pixva' ) ),
		array( __( 'تعمیر با تأیید شما', 'pixva' ), __( 'تعمیر فقط بعد از تأیید هزینه انجام می‌شود.', 'pixva' ) ),
		array( __( 'پیگیری و تحویل', 'pixva' ), __( 'وضعیت هر مرحله را با کد پیگیری آنلاین می‌بینید.', 'pixva' ) ),
	);
	ob_start();
	pixva_section_open( 'sc-steps', $atts['title'], $more, $atts['lead']);
	echo '<ol class="steps">';
	foreach ( $items as $it ) {
		echo '<li class="steps__item"><h3>' . esc_html( $it[0] ) . '</h3><p>' . esc_html( $it[1] ) . '</p></li>';
	}
	echo '</ol>';
	pixva_section_close();
	return ob_get_clean();
}
add_shortcode( 'pixva_steps', 'pixva_sc_steps' );

/**
 * FAQ shortcode.
 *
 * @param array $atts Attributes.
 * @return string
 */
function pixva_sc_faq( $atts ) {
	$atts  = shortcode_atts(
		array(
			'title' => __( 'پرسش‌های متداول', 'pixva' ),
			'topic' => 'general',
			'count' => '6',
			'more'  => '1',
			'link'  => '',
			'lead'  => '',
		),
		$atts,
		'pixva-faq'
	);
	$items = pixva_faq_items( $atts['topic'] );
	$more  = '' !== $atts['link'] ? $atts['link'] : ( '1' === $atts['more'] ? pixva_route_url( 'faq' ) : '' );
	ob_start();
	pixva_section_open( 'sc-faq', $atts['title'], $more, $atts['lead']);
	pixva_faq_list( array_slice( $items, 0, (int) $atts['count'] ), true );
	pixva_section_close();
	return ob_get_clean();
}
add_shortcode( 'pixva_faq', 'pixva_sc_faq' );

/**
 * CTA banner shortcode.
 *
 * @param array $atts Attributes.
 * @return string
 */
function pixva_sc_cta( $atts ) {
	$atts = shortcode_atts(
		array(
			'title' => '',
			'text'  => '',
		),
		$atts,
		'pixva-cta'
	);
	ob_start();
	pixva_cta_box( $atts['title'], $atts['text'], 'sc_cta' );
	return ob_get_clean();
}
add_shortcode( 'pixva_cta', 'pixva_sc_cta' );

/**
 * Contact details shortcode (real claims only).
 *
 * @return string
 */
function pixva_sc_contact() {
	ob_start();
	pixva_contact_details();
	return ob_get_clean();
}
add_shortcode( 'pixva_contact', 'pixva_sc_contact' );

/**
 * Breadcrumbs shortcode (theme schema-aware output).
 *
 * @return string
 */
function pixva_sc_breadcrumbs() {
	ob_start();
	pixva_breadcrumbs();
	return ob_get_clean();
}
add_shortcode( 'pixva_breadcrumbs', 'pixva_sc_breadcrumbs' );

/**
 * Recent posts / cases shortcode.
 *
 * @param array $atts Attributes.
 * @return string
 */
function pixva_sc_posts( $atts ) {
	$atts  = shortcode_atts(
		array(
			'type'  => 'post',
			'title' => __( 'از مجله پیکسوا', 'pixva' ),
			'count' => '3',
			'more'  => '1',
			'link'  => '',
			'lead'  => '',
		),
		$atts,
		'pixva-posts'
	);
	$posts = get_posts(
		array(
			'post_type'           => 'repair_cases' === $atts['type'] ? 'repair_cases' : 'post',
			'post_status'         => 'publish',
			'posts_per_page'      => (int) $atts['count'],
			'no_found_rows'       => true,
			'ignore_sticky_posts' => true,
		)
	);
	$more  = '' !== $atts['link'] ? $atts['link'] : ( '1' === $atts['more'] ? pixva_route_url( 'repair_cases' === $atts['type'] ? 'portfolio' : 'blog' ) : '' );
	ob_start();
	pixva_section_open( 'sc-posts', $atts['title'], $more, $atts['lead']);
	if ( $posts ) {
		pixva_card_grid( $posts );
	}
	pixva_section_close();
	return ob_get_clean();
}
add_shortcode( 'pixva_posts', 'pixva_sc_posts' );

/**
 * Booking form shortcode (full §11 pipeline).
 *
 * @param array $atts Attributes.
 * @return string
 */
function pixva_sc_booking( $atts ) {
	$atts = shortcode_atts(
		array(
			'faq' => '1',
		),
		$atts,
		'pixva-booking-form'
	);
	ob_start();
	pixva_booking_form_block();
	if ( '1' === $atts['faq'] ) {
		$faq = pixva_faq_items( 'booking' );
		if ( $faq ) {
			echo '<section class="section--tight" aria-labelledby="bk-faq-sc"><h2 id="bk-faq-sc">' . esc_html__( 'پرسش‌های رایج', 'pixva' ) . '</h2>';
			pixva_faq_list( $faq, true );
			echo '</section>';
		}
	}
	return ob_get_clean();
}
add_shortcode( 'pixva_booking_form', 'pixva_sc_booking' );

/**
 * Tracking lookup shortcode.
 *
 * @return string
 */
function pixva_sc_tracking() {
	$result = pixva_handle_page_lookup( 'pixva_track' );
	ob_start();
	?>
	<div class="pixva-lookup">
		<?php pixva_lookup_form( 'pixva_track', 'track', __( 'نمایش وضعیت', 'pixva' ), 'tracking_viewed' ); ?>
		<div class="lookup__result" data-lookup-result aria-live="polite" tabindex="-1">
			<?php if ( is_wp_error( $result ) ) : ?>
				<?php pixva_notice( 'error', $result->get_error_message(), '', true ); ?>
			<?php elseif ( is_array( $result ) ) : ?>
				<div data-track-view="tracking_viewed"><?php pixva_order_view( $result ); ?></div>
			<?php endif; ?>
		</div>
	</div>
	<?php
	return ob_get_clean();
}
add_shortcode( 'pixva_tracking_form', 'pixva_sc_tracking' );

/**
 * Warranty lookup shortcode.
 *
 * @return string
 */
function pixva_sc_warranty() {
	$result = pixva_handle_page_lookup( 'pixva_warranty' );
	ob_start();
	?>
	<div class="pixva-lookup">
		<?php pixva_lookup_form( 'pixva_warranty', 'warranty', __( 'استعلام', 'pixva' ), 'warranty_lookup' ); ?>
		<div class="lookup__result" data-lookup-result aria-live="polite" tabindex="-1">
			<?php if ( is_wp_error( $result ) ) : ?>
				<?php pixva_notice( 'error', $result->get_error_message(), '', true ); ?>
			<?php elseif ( is_array( $result ) ) : ?>
				<div data-track-view="warranty_lookup"><?php pixva_warranty_view( $result ); ?></div>
			<?php endif; ?>
		</div>
	</div>
	<?php
	return ob_get_clean();
}
add_shortcode( 'pixva_warranty_form', 'pixva_sc_warranty' );

/**
 * Announcement bar shortcode (option-driven; prints nothing when unset).
 *
 * @param array $atts Attributes (text override).
 * @return string
 */
function pixva_sc_notice( $atts ) {
	$atts    = shortcode_atts( array( 'text' => '', 'link' => '' ), $atts, 'pixva-notice' );
	$stored  = get_option( 'pixva_announcement', array() );
	$text    = '' !== $atts['text'] ? $atts['text'] : ( is_array( $stored ) ? (string) ( $stored['text'] ?? '' ) : '' );
	$url     = '' !== $atts['link'] ? $atts['link'] : ( is_array( $stored ) ? (string) ( $stored['url'] ?? '' ) : '' );
	if ( '' === $text ) {
		return '';
	}
	$out  = '<div class="pixva-notice-bar" role="status"><div class="container"><p>' . esc_html( $text );
	if ( '' !== $url ) {
		$out .= ' <a href="' . esc_url( $url ) . '">' . esc_html__( 'بیشتر بدانید', 'pixva' ) . '</a>';
	}
	$out .= '</p></div></div>';
	return $out;
}
add_shortcode( 'pixva_notice', 'pixva_sc_notice' );

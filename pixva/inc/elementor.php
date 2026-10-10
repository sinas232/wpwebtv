<?php
/**
 * Elementor integration (free Elementor; Pro-only features are documented,
 * never required).
 *
 * Everything in this module is guarded: with Elementor inactive the theme
 * behaves exactly as before and no Elementor class is ever referenced at
 * runtime (no fatals). Registered hooks only fire when Elementor fires them.
 *
 * Provides:
 *  - a "PIXVA" widget category (inc/elementor-widgets.php),
 *  - Theme Builder locations (header/footer/main) so Elementor Pro Theme
 *    Builder can overlay them when Pro is present — the classic
 *    header.php/footer.php remain the default,
 *  - front-page takeover: when the static front page is edited with
 *    Elementor, only its content is rendered (the classic sections are
 *    skipped) so what is saved in the editor is exactly what visitors get.
 *
 * @package Pixva
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Whether Elementor (free) is loaded right now.
 *
 * @return bool
 */
function pixva_elementor_active() {
	return did_action( 'elementor/loaded' ) && class_exists( '\Elementor\Plugin' );
}

/**
 * Widget category shown in the Elementor panel.
 *
 * @param \Elementor\Elements_Manager $elements_manager Elements manager.
 * @return void
 */
function pixva_elementor_categories( $elements_manager ) {
	$elements_manager->add_category(
		'pixva',
		array(
			'title' => __( 'PIXVA', 'pixva' ),
			'icon'  => 'eicon-tv',
		)
	);
}
add_action( 'elementor/elements/categories_registered', 'pixva_elementor_categories' );

/**
 * Theme locations for Elementor Theme Builder (requires Pro to place
 * templates; harmless without it).
 *
 * @param object $manager Theme locations manager.
 * @return void
 */
function pixva_elementor_locations( $manager ) {
	if ( is_object( $manager ) && method_exists( $manager, 'register_all_core_location' ) ) {
		$manager->register_all_core_location();
	}
}
add_action( 'elementor/themes/register_locations', 'pixva_elementor_locations' );

/**
 * Whether the front page is being edited/built with Elementor.
 *
 * @return bool
 */
function pixva_front_page_is_elementor() {
	$front = (int) get_option( 'page_on_front' );
	if ( ! $front ) {
		return false;
	}
	// Elementor stores "builder"; older datasets used "elementor".
	$mode = (string) get_post_meta( $front, '_elementor_edit_mode', true );
	if ( ! in_array( $mode, array( 'builder', 'elementor' ), true ) ) {
		return false;
	}
	// Require actual saved data — a bare "builder" flag with no document
	// must not blank the front page.
	$data = get_post_meta( $front, '_elementor_data', true );
	return is_string( $data ) && '' !== trim( $data ) && '[]' !== trim( $data );
}

/**
 * Whether the current singular page renders an Elementor-built document.
 *
 * Used by the page templates as a takeover gate: when true the template
 * renders ONLY the theme chrome + the Elementor document (the classic
 * sections are skipped), so what the editor saves is exactly what visitors
 * see. With Elementor inactive or the flag/data absent, every template keeps
 * its classic behavior untouched.
 *
 * @return bool
 */
function pixva_elementor_takeover() {
	if ( ! pixva_elementor_active() || ! is_singular( 'page' ) ) {
		return false;
	}
	$id = (int) get_queried_object_id();
	if ( ! $id ) {
		return false;
	}
	$mode = (string) get_post_meta( $id, '_elementor_edit_mode', true );
	if ( ! in_array( $mode, array( 'builder', 'elementor' ), true ) ) {
		return false;
	}
	$data = get_post_meta( $id, '_elementor_data', true );
	return is_string( $data ) && '' !== trim( $data ) && '[]' !== trim( $data );
}

/**
 * Render an Elementor-built page behind the theme's standard chrome.
 *
 * The document supplies every section below the page header; the header
 * itself (breadcrumbs + H1 + optional lead) keeps the classic markup so the
 * visual identity and breadcrumb contract stay identical to classic pages.
 *
 * @param string $lead Optional lead text under the H1.
 * @return void
 */
function pixva_elementor_render_page( $lead = '' ) {
	get_header();
	while ( have_posts() ) :
		the_post();
		?>
		<main id="main" class="site-main">
			<?php pixva_page_header( get_the_title(), $lead ); ?>
			<?php the_content(); ?>
		</main>
		<?php
	endwhile;
	get_footer();
}

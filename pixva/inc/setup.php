<?php
/**
 * Theme setup, assets and install routine (§27–§30, §64).
 *
 * Assets: one stylesheet pair (style.css tokens/base + app.css components)
 * and one small core script (app.js). Feature scripts are loaded only on
 * the routes that need them, deferred, no jQuery, no frameworks.
 *
 * @package Pixva
 * @since   2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Theme supports & menus.
 *
 * @return void
 */
function pixva_setup() {
	load_theme_textdomain( 'pixva', PIXVA_DIR . '/languages' );
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'html5', array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script', 'navigation-widgets' ) );
	add_theme_support(
		'custom-logo',
		array(
			'height'      => 48,
			'width'       => 160,
			'flex-width'  => true,
			'flex-height' => true,
		)
	);
	add_theme_support( 'editor-styles' );
	add_theme_support( 'wp-block-styles' );
	add_editor_style( array( 'assets/css/editor.css' ) );
	register_nav_menus(
		array(
			'primary' => __( 'منوی اصلی', 'pixva' ),
			'footer'  => __( 'منوی فوتر', 'pixva' ),
		)
	);
	set_post_thumbnail_size( 768, 432, true );
}
add_action( 'after_setup_theme', 'pixva_setup' );

/**
 * Content width.
 *
 * @return void
 */
function pixva_content_width() {
	$GLOBALS['content_width'] = 760;
}
add_action( 'after_setup_theme', 'pixva_content_width', 0 );

/**
 * Enqueue styles and scripts.
 *
 * @return void
 */
function pixva_assets() {
	$v = PIXVA_VERSION;
	wp_enqueue_style( 'pixva-style', get_stylesheet_uri(), array(), $v );
	wp_enqueue_style( 'pixva-app', PIXVA_URI . '/assets/css/app.css', array( 'pixva-style' ), $v );

	wp_enqueue_script(
		'pixva-app',
		PIXVA_URI . '/assets/js/app.js',
		array(),
		$v,
		array(
			'strategy'  => 'defer',
			'in_footer' => true,
		)
	);
	wp_localize_script(
		'pixva-app',
		'PIXVA',
		array(
			'ajax'    => admin_url( 'admin-ajax.php' ),
			'rest'    => esc_url_raw( rest_url( 'pixva/v1/' ) ),
			'nonce'   => is_user_logged_in() ? wp_create_nonce( 'wp_rest' ) : '',
			'events'  => pixva_analytics_events(),
			'props'   => pixva_analytics_props(),
			'pending' => pixva_page_events(),
			'route'   => pixva_current_route(),
			'i18n'    => array(
				'sending'   => __( 'در حال ارسال…', 'pixva' ),
				'loading'   => __( 'در حال بارگذاری…', 'pixva' ),
				'error'     => __( 'ارتباط برقرار نشد. اتصال اینترنت را بررسی کنید و دوباره تلاش کنید.', 'pixva' ),
				'fixErrors' => __( 'لطفاً خطاهای مشخص‌شده را برطرف کنید.', 'pixva' ),
				'menu'      => __( 'منو', 'pixva' ),
				'close'     => __( 'بستن', 'pixva' ),
				'required'  => __( 'این فیلد لازم است.', 'pixva' ),
			),
		)
	);

	$route   = pixva_current_route();
	$feature = array(
		'diagnosis'        => 'diagnosis',
		'price_calculator' => 'calculator',
		'pixel_test'       => 'pixel-test',
		'tracking'         => 'lookup',
		'warranty'         => 'lookup',
	);
	if ( isset( $feature[ $route ] ) ) {
		wp_enqueue_script(
			'pixva-' . $feature[ $route ],
			PIXVA_URI . '/assets/js/' . $feature[ $route ] . '.js',
			array( 'pixva-app' ),
			$v,
			array(
				'strategy'  => 'defer',
				'in_footer' => true,
			)
		);
		if ( 'diagnosis' === $route ) {
			wp_add_inline_script( 'pixva-diagnosis', 'window.PIXVA_DIAG=' . wp_json_encode( array( 'problems' => pixva_diagnosis_client_data() ) ) . ';', 'before' );
		}
	}
	if ( is_post_type_archive( 'pixva_error' ) ) {
		wp_enqueue_script(
			'pixva-lookup',
			PIXVA_URI . '/assets/js/lookup.js',
			array( 'pixva-app' ),
			$v,
			array(
				'strategy'  => 'defer',
				'in_footer' => true,
			)
		);
	}
	if ( is_singular( 'repair_cases' ) && get_post_meta( get_queried_object_id(), '_pixva_case_before', true ) && get_post_meta( get_queried_object_id(), '_pixva_case_after', true ) ) {
		wp_enqueue_script(
			'pixva-before-after',
			PIXVA_URI . '/assets/js/before-after.js',
			array(),
			$v,
			array(
				'strategy'  => 'defer',
				'in_footer' => true,
			)
		);
	}
	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}

	// No block library CSS for classic templates where blocks are not used heavily
	// is NOT removed: editors may use blocks in content (§37).
}
add_action( 'wp_enqueue_scripts', 'pixva_assets' );

/**
 * Drop core block-library CSS on pages that contain no block markup.
 * PIXVA templates are classic PHP (no blocks); measured 100% of the 113 KB
 * block-library stylesheet unused on the homepage, tools and services routes.
 * Pages whose content (singular post, or any post in the loop) has blocks keep it.
 * theme.json / global-styles is intentionally left untouched.
 *
 * @return void
 */
function pixva_trim_block_styles() {
	$queried = get_queried_object();
	if ( $queried instanceof WP_Post && has_blocks( $queried->post_content ) ) {
		return;
	}
	$loop = isset( $GLOBALS['wp_query'] ) && is_array( $GLOBALS['wp_query']->posts ) ? $GLOBALS['wp_query']->posts : array();
	foreach ( $loop as $item ) {
		if ( $item instanceof WP_Post && has_blocks( $item->post_content ) ) {
			return;
		}
	}
	wp_dequeue_style( 'wp-block-library' );
	wp_dequeue_style( 'wp-block-library-theme' );
	wp_dequeue_style( 'classic-theme-styles' );
}
add_action( 'wp_enqueue_scripts', 'pixva_trim_block_styles', 100 );

/**
 * Preload the single variable font file used above the fold.
 *
 * @return void
 */
function pixva_preload_font() {
	echo '<link rel="preload" href="' . esc_url( PIXVA_URI . '/assets/fonts/vazirmatn-variable.woff2' ) . '" as="font" type="font/woff2" crossorigin>' . "\n";
}
add_action( 'wp_head', 'pixva_preload_font', 1 );

/**
 * SVG favicon fallback (brand mark) when no Site Icon is configured.
 *
 * @return void
 */
function pixva_favicon_fallback() {
	if ( ! has_site_icon() ) {
		echo '<link rel="icon" href="' . esc_url( PIXVA_URI . '/assets/images/logo-mark.svg' ) . '" type="image/svg+xml">' . "\n";
	}
}
add_action( 'wp_head', 'pixva_favicon_fallback', 2 );

/**
 * Remove emoji scripts (perf).
 *
 * @return void
 */
function pixva_disable_emojis() {
	remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
	remove_action( 'wp_print_styles', 'print_emoji_styles' );
	remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
	remove_action( 'admin_print_styles', 'print_emoji_styles' );
}
add_action( 'init', 'pixva_disable_emojis' );

/**
 * Admin assets for meta-box image pickers.
 *
 * @param string $hook Hook.
 * @return void
 */
function pixva_admin_assets( $hook ) {
	if ( in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
		wp_enqueue_media();
		wp_enqueue_script( 'pixva-admin', PIXVA_URI . '/assets/js/admin.js', array(), PIXVA_VERSION, true );
	}
	wp_enqueue_style( 'pixva-admin', PIXVA_URI . '/assets/css/admin.css', array(), PIXVA_VERSION );
}
add_action( 'admin_enqueue_scripts', 'pixva_admin_assets' );

/**
 * Body classes for route styling.
 *
 * @param string[] $classes Classes.
 * @return string[]
 */
function pixva_body_class( $classes ) {
	$route = pixva_current_route();
	if ( $route ) {
		$classes[] = 'route-' . sanitize_html_class( str_replace( '_', '-', $route ) );
	}
	return $classes;
}
add_filter( 'body_class', 'pixva_body_class' );

/**
 * Excerpt length/more.
 *
 * @return int
 */
function pixva_excerpt_length() {
	return 28;
}
add_filter( 'excerpt_length', 'pixva_excerpt_length' );
add_filter(
	'excerpt_more',
	static function () {
		return '…';
	}
);

/**
 * Search: include public PIXVA types; exclude pages that are system tools
 * without content (account/dashboard).
 *
 * @param WP_Query $q Query.
 * @return void
 */
function pixva_search_scope( $q ) {
	if ( is_admin() || ! $q->is_main_query() || ! $q->is_search() ) {
		return;
	}
	$q->set( 'post_type', array( 'post', 'page', 'tv_services', 'tv_brands', 'tv_model', 'pixva_error', 'repair_cases' ) );
	$exclude = array();
	foreach ( array( 'account', 'account_repairs', 'account_warranty', 'account_profile', 'dashboard' ) as $r ) {
		$id = pixva_route_page_id( $r );
		if ( $id ) {
			$exclude[] = $id;
		}
	}
	$q->set( 'post__not_in', $exclude );
	$q->set( 'posts_per_page', 12 );
}
add_action( 'pre_get_posts', 'pixva_search_scope' );

/**
 * Archive page sizes.
 *
 * @param WP_Query $q Query.
 * @return void
 */
function pixva_archive_sizes( $q ) {
	if ( is_admin() || ! $q->is_main_query() ) {
		return;
	}
	if ( $q->is_post_type_archive( array( 'tv_services', 'tv_brands' ) ) ) {
		$q->set( 'posts_per_page', 48 );
		$q->set(
			'orderby',
			array(
				'menu_order' => 'ASC',
				'title'      => 'ASC',
			)
		);
	}
	if ( $q->is_post_type_archive( 'pixva_error' ) ) {
		$q->set( 'posts_per_page', 24 );
		$q->set( 'orderby', 'title' );
		$q->set( 'order', 'ASC' );
		$brand = absint( pixva_get_request_var( 'brand', '0' ) );
		$sev   = sanitize_key( pixva_get_request_var( 'severity' ) );
		$text  = pixva_get_request_var( 'q' );
		$meta  = array();
		if ( $brand ) {
			$meta[] = array(
				'key'   => '_pixva_err_brand_id',
				'value' => $brand,
			);
		}
		if ( $sev && isset( pixva_severity_levels()[ $sev ] ) ) {
			$meta[] = array(
				'key'   => '_pixva_err_severity',
				'value' => $sev,
			);
		}
		if ( $meta ) {
			$q->set( 'meta_query', $meta );
		}
		if ( '' !== $text ) {
			$ids = pixva_error_code_query( $text, $brand, 200 )->posts;
			$q->set( 'post__in', $ids ? wp_list_pluck( $ids, 'ID' ) : array( 0 ) );
		}
	}
}
add_action( 'pre_get_posts', 'pixva_archive_sizes' );

/**
 * Install / refresh: roles, route pages, permalinks, reading settings.
 * Runs on theme activation and on version upgrade (migration.php).
 *
 * @return void
 */
function pixva_install() {
	pixva_install_roles();
	pixva_register_content_model();
	pixva_ensure_route_pages();
	if ( '/blog/%postname%/' !== get_option( 'permalink_structure' ) ) {
		update_option( 'pixva_previous_permalink', (string) get_option( 'permalink_structure' ), false );
		update_option( 'permalink_structure', '/blog/%postname%/' );
	}
	update_option( 'category_base', 'blog/category' );
	update_option( 'wp_attachment_pages_enabled', 0 );
	update_option( 'pixva_flush_rewrites', 1 );
}

/**
 * Activation hook.
 *
 * @return void
 */
function pixva_on_switch_theme() {
	// Migrations run pixva_install() themselves, after moving legacy pages.
	if ( version_compare( (string) get_option( 'pixva_db_version', '0' ), PIXVA_DB_VERSION, '<' ) ) {
		pixva_run_migrations();
	} else {
		pixva_install();
	}
}
add_action( 'after_switch_theme', 'pixva_on_switch_theme' );

/**
 * Flush rewrites once after install/upgrade (late init, after all rules exist).
 *
 * @return void
 */
function pixva_maybe_flush_rewrites() {
	if ( get_option( 'pixva_flush_rewrites' ) ) {
		delete_option( 'pixva_flush_rewrites' );
		flush_rewrite_rules( false );
	}
}
add_action( 'init', 'pixva_maybe_flush_rewrites', 99 );

/**
 * Whether the current request renders the Persian-only front end.
 *
 * @return bool
 */
function pixva_is_front_request() {
	return ! is_admin() && ! in_array( $GLOBALS['pagenow'] ?? '', array( 'wp-login.php', 'wp-register.php' ), true );
}

/**
 * The PIXVA front end is Persian-only (all UI strings, schema inLanguage and
 * og:locale are fa-IR), but core only sets dir="rtl" when the fa_IR core
 * translation is installed — which often cannot be downloaded. Without this
 * the whole UI renders left-to-right and screen readers announce Persian as
 * English (WCAG 3.1.1). Admin and login keep the site's own locale.
 *
 * @return void
 */
function pixva_front_text_direction() {
	global $wp_locale;
	if ( pixva_is_front_request() && $wp_locale instanceof WP_Locale ) {
		$wp_locale->text_direction = 'rtl';
	}
}
add_action( 'after_setup_theme', 'pixva_front_text_direction', 0 );

/**
 * Document language for the Persian-only front end.
 *
 * @param string $output lang/dir attributes.
 * @return string
 */
function pixva_language_attributes( $output ) {
	if ( ! pixva_is_front_request() || 0 === strpos( determine_locale(), 'fa' ) ) {
		return $output;
	}
	$output = preg_replace( '/\blang="[^"]*"/', 'lang="fa-IR"', $output );
	return false === strpos( $output, 'dir=' ) ? $output . ' dir="rtl"' : $output;
}
add_filter( 'language_attributes', 'pixva_language_attributes' );

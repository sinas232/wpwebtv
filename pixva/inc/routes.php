<?php
/**
 * Route registry (§05, §42).
 *
 * Page-backed routes are WordPress pages identified by the `_pixva_route`
 * meta key (stable even if an editor renames the slug). Archive-backed
 * routes are post type archives. The registry is the single source for:
 * creating pages on install/upgrade, resolving URLs (pixva_route_url),
 * template selection, indexability and the route matrix in docs/routes.md.
 *
 * @package Pixva
 * @since   2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Route definitions.
 *
 * Format: key => [
 *   path      : canonical path,
 *   source    : page | archive | home | front,
 *   title     : page title for creation,
 *   parent    : parent route key (pages),
 *   template  : page template file (pages),
 *   index     : bool, indexable,
 *   schema    : primary schema type,
 *   nav       : bool, include in primary nav fallback,
 * ]
 *
 * @return array<string,array>
 */
function pixva_routes() {
	$r = array(
		'home'              => array(
			'path'     => '/',
			'source'   => 'front',
			'title'    => __( 'خانه', 'pixva' ),
			'template' => '',
			'index'    => true,
			'schema'   => 'WebSite',
		),
		'services'          => array(
			'path'      => '/services/',
			'source'    => 'archive',
			'post_type' => 'tv_services',
			'title'     => __( 'خدمات تعمیر', 'pixva' ),
			'index'     => true,
			'schema'    => 'CollectionPage',
			'nav'       => true,
		),
		'brands'            => array(
			'path'      => '/brands/',
			'source'    => 'archive',
			'post_type' => 'tv_brands',
			'title'     => __( 'برندها', 'pixva' ),
			'index'     => true,
			'schema'    => 'CollectionPage',
			'nav'       => true,
		),
		'problems'          => array(
			'path'     => '/problems/',
			'source'   => 'page',
			'title'    => __( 'مشکلات رایج تلویزیون', 'pixva' ),
			'template' => 'page-templates/problems.php',
			'index'    => true,
			'schema'   => 'CollectionPage',
			'nav'      => true,
		),
		'tools'             => array(
			'path'     => '/tools/',
			'source'   => 'page',
			'title'    => __( 'ابزارها', 'pixva' ),
			'template' => 'page-templates/tools.php',
			'index'    => true,
			'schema'   => 'CollectionPage',
			'nav'      => true,
		),
		'diagnosis'         => array(
			'path'     => '/tools/diagnosis/',
			'source'   => 'page',
			'parent'   => 'tools',
			'slug'     => 'diagnosis',
			'title'    => __( 'تشخیص آنلاین ایراد تلویزیون', 'pixva' ),
			'template' => 'page-templates/diagnosis.php',
			'index'    => true,
			'schema'   => 'WebApplication',
		),
		'price_calculator'  => array(
			'path'     => '/tools/price-calculator/',
			'source'   => 'page',
			'parent'   => 'tools',
			'slug'     => 'price-calculator',
			'title'    => __( 'برآورد هزینه تعمیر', 'pixva' ),
			'template' => 'page-templates/price-calculator.php',
			'index'    => true,
			'schema'   => 'WebApplication',
		),
		'pixel_test'        => array(
			'path'     => '/tools/pixel-test/',
			'source'   => 'page',
			'parent'   => 'tools',
			'slug'     => 'pixel-test',
			'title'    => __( 'تست پیکسل سوخته و رنگ', 'pixva' ),
			'template' => 'page-templates/pixel-test.php',
			'index'    => true,
			'schema'   => 'WebApplication',
		),
		'tools_error_codes' => array(
			'path'   => '/tools/error-codes/',
			'source' => 'redirect',
			'target' => 'error_codes',
			'title'  => __( 'کدهای خطا', 'pixva' ),
			'index'  => false,
			'schema' => '',
		),
		'error_codes'       => array(
			'path'      => '/error-codes/',
			'source'    => 'archive',
			'post_type' => 'pixva_error',
			'title'     => __( 'کدهای خطای تلویزیون', 'pixva' ),
			'index'     => true,
			'schema'    => 'CollectionPage',
		),
		'repair'            => array(
			'path'     => '/repair/',
			'source'   => 'page',
			'title'    => __( 'تعمیر تلویزیون', 'pixva' ),
			'template' => 'page-templates/repair.php',
			'index'    => true,
			'schema'   => 'WebPage',
		),
		'booking'           => array(
			'path'     => '/repair/book/',
			'source'   => 'page',
			'parent'   => 'repair',
			'slug'     => 'book',
			'title'    => __( 'ثبت درخواست تعمیر', 'pixva' ),
			'template' => 'page-templates/booking.php',
			'index'    => true,
			'schema'   => 'WebPage',
		),
		'tracking'          => array(
			'path'     => '/tracking/',
			'source'   => 'page',
			'title'    => __( 'پیگیری تعمیر', 'pixva' ),
			'template' => 'page-templates/tracking.php',
			'index'    => true,
			'schema'   => 'WebPage',
		),
		'warranty'          => array(
			'path'     => '/warranty/',
			'source'   => 'page',
			'title'    => __( 'گارانتی تعمیر', 'pixva' ),
			'template' => 'page-templates/warranty.php',
			'index'    => true,
			'schema'   => 'WebPage',
		),
		'portfolio'         => array(
			'path'      => '/portfolio/',
			'source'    => 'archive',
			'post_type' => 'repair_cases',
			'title'     => __( 'نمونه‌کارها', 'pixva' ),
			'index'     => true,
			'schema'    => 'CollectionPage',
		),
		'blog'              => array(
			'path'     => '/blog/',
			'source'   => 'home',
			'title'    => __( 'مجله', 'pixva' ),
			'template' => '',
			'index'    => true,
			'schema'   => 'Blog',
			'nav'      => true,
		),
		'about'             => array(
			'path'     => '/about/',
			'source'   => 'page',
			'title'    => __( 'درباره ما', 'pixva' ),
			'template' => 'page-templates/about.php',
			'index'    => true,
			'schema'   => 'AboutPage',
		),
		'contact'           => array(
			'path'     => '/contact/',
			'source'   => 'page',
			'title'    => __( 'تماس با ما', 'pixva' ),
			'template' => 'page-templates/contact.php',
			'index'    => true,
			'schema'   => 'ContactPage',
		),
		'faq'               => array(
			'path'     => '/faq/',
			'source'   => 'page',
			'title'    => __( 'پرسش‌های متداول', 'pixva' ),
			'template' => 'page-templates/faq.php',
			'index'    => true,
			'schema'   => 'FAQPage',
		),
		'account'           => array(
			'path'     => '/account/',
			'source'   => 'page',
			'title'    => __( 'حساب کاربری', 'pixva' ),
			'template' => 'page-templates/account.php',
			'index'    => false,
			'schema'   => '',
		),
		'account_repairs'   => array(
			'path'     => '/account/repairs/',
			'source'   => 'page',
			'parent'   => 'account',
			'slug'     => 'repairs',
			'title'    => __( 'تعمیرهای من', 'pixva' ),
			'template' => 'page-templates/account.php',
			'index'    => false,
			'schema'   => '',
		),
		'account_warranty'  => array(
			'path'     => '/account/warranty/',
			'source'   => 'page',
			'parent'   => 'account',
			'slug'     => 'warranty',
			'title'    => __( 'گارانتی‌های من', 'pixva' ),
			'template' => 'page-templates/account.php',
			'index'    => false,
			'schema'   => '',
		),
		'account_profile'   => array(
			'path'     => '/account/profile/',
			'source'   => 'page',
			'parent'   => 'account',
			'slug'     => 'profile',
			'title'    => __( 'مشخصات من', 'pixva' ),
			'template' => 'page-templates/account.php',
			'index'    => false,
			'schema'   => '',
		),
		'dashboard'         => array(
			'path'     => '/dashboard/',
			'source'   => 'page',
			'title'    => __( 'داشبورد', 'pixva' ),
			'template' => 'page-templates/dashboard.php',
			'index'    => false,
			'schema'   => '',
		),
	);
	foreach ( $r as $key => $def ) {
		if ( empty( $def['slug'] ) ) {
			$r[ $key ]['slug'] = trim( basename( $def['path'] ), '/' );
		}
	}
	return $r;
}

/**
 * Page id that backs a page route (0 when missing).
 *
 * @param string $key Route key.
 * @return int
 */
function pixva_route_page_id( $key ) {
	static $cache = array();
	if ( isset( $cache[ $key ] ) ) {
		return $cache[ $key ];
	}
	if ( 'home' === $key ) {
		return (int) get_option( 'page_on_front' );
	}
	if ( 'blog' === $key ) {
		return (int) get_option( 'page_for_posts' );
	}
	$ids           = get_posts(
		array(
			'post_type'        => 'page',
			'post_status'      => array( 'publish', 'private' ),
			'meta_key'         => '_pixva_route', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- small indexed lookup, cached.
			'meta_value'       => $key, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			'posts_per_page'   => 1,
			'fields'           => 'ids',
			'no_found_rows'    => true,
			'suppress_filters' => true,
		)
	);
	$cache[ $key ] = $ids ? (int) $ids[0] : 0;
	return $cache[ $key ];
}

/**
 * Canonical URL of a route (always resolvable; falls back to the defined
 * path so links never break even before the page exists).
 *
 * @param string $key   Route key.
 * @param array  $query Optional query args.
 * @return string
 */
function pixva_route_url( $key, $query = array() ) {
	$routes = pixva_routes();
	if ( ! isset( $routes[ $key ] ) ) {
		return home_url( '/' );
	}
	$def = $routes[ $key ];
	if ( 'redirect' === $def['source'] ) {
		return pixva_route_url( $def['target'], $query );
	}
	$url = '';
	if ( 'archive' === $def['source'] ) {
		$url = (string) get_post_type_archive_link( $def['post_type'] );
	} elseif ( 'front' === $def['source'] ) {
		$url = home_url( '/' );
	} else {
		$id  = pixva_route_page_id( $key );
		$url = $id ? (string) get_permalink( $id ) : '';
	}
	if ( '' === $url ) {
		$url = home_url( $def['path'] );
	}
	return $query ? add_query_arg( array_map( 'rawurlencode', $query ), $url ) : $url;
}

/**
 * Route key of the current request ('' when not a registered route).
 *
 * @return string
 */
function pixva_current_route() {
	if ( is_front_page() ) {
		return 'home';
	}
	if ( is_home() ) {
		return 'blog';
	}
	if ( is_post_type_archive() ) {
		foreach ( pixva_routes() as $key => $def ) {
			if ( 'archive' === $def['source'] && is_post_type_archive( $def['post_type'] ) ) {
				return $key;
			}
		}
	}
	if ( is_page() ) {
		$route = (string) get_post_meta( get_queried_object_id(), '_pixva_route', true );
		return isset( pixva_routes()[ $route ] ) ? $route : '';
	}
	return '';
}

/**
 * Force the registry template for route pages, regardless of what the
 * page's _wp_page_template says (prevents a mis-set template from
 * breaking a system route). Account sub-pages share one template.
 *
 * @param string $template Template path.
 * @return string
 */
function pixva_route_template( $template ) {
	if ( ! is_page() ) {
		return $template;
	}
	$route = (string) get_post_meta( get_queried_object_id(), '_pixva_route', true );
	$def   = pixva_routes()[ $route ] ?? null;
	if ( $def && ! empty( $def['template'] ) ) {
		$file = locate_template( $def['template'] );
		if ( $file ) {
			return $file;
		}
	}
	return $template;
}
add_filter( 'template_include', 'pixva_route_template', 20 );

/**
 * Create or adopt pages for all page routes, set front/blog pages and the
 * permalink structure. Idempotent. Never overwrites editor content.
 *
 * Adoption order: page with matching _pixva_route → published page at the
 * exact path → create new.
 *
 * @return array<string,int> route => page id
 */
function pixva_ensure_route_pages() {
	$routes = pixva_routes();
	$ids    = array();

	// Front page.
	$front = (int) get_option( 'page_on_front' );
	if ( ! $front || 'page' !== get_post_type( $front ) || 'publish' !== get_post_status( $front ) ) {
		$front = pixva_find_or_create_page( 'home', $routes['home']['title'], 'home', 0 );
		update_option( 'page_on_front', $front );
	}
	update_option( 'show_on_front', 'page' );
	update_post_meta( $front, '_pixva_route', 'home' );
	$ids['home'] = $front;

	// Posts page (/blog/).
	$blog = (int) get_option( 'page_for_posts' );
	if ( ! $blog || 'page' !== get_post_type( $blog ) || 'publish' !== get_post_status( $blog ) || $blog === $front ) {
		$blog = pixva_find_or_create_page( 'blog', $routes['blog']['title'], 'blog', 0 );
		update_option( 'page_for_posts', $blog );
	}
	if ( 'blog' !== get_post_field( 'post_name', $blog ) || 0 !== (int) get_post_field( 'post_parent', $blog ) ) {
		wp_update_post(
			array(
				'ID'          => $blog,
				'post_name'   => 'blog',
				'post_parent' => 0,
			)
		);
	}
	update_post_meta( $blog, '_pixva_route', 'blog' );
	$ids['blog'] = $blog;

	// Page routes, parents first (registry order guarantees parents precede children).
	foreach ( $routes as $key => $def ) {
		if ( 'page' !== $def['source'] ) {
			continue;
		}
		$parent = ! empty( $def['parent'] ) ? (int) ( $ids[ $def['parent'] ] ?? 0 ) : 0;
		$id     = pixva_find_or_create_page( $def['slug'], $def['title'], $key, $parent, $def['path'] );
		if ( ! $id ) {
			continue;
		}
		$post = get_post( $id );
		$upd  = array( 'ID' => $id );
		if ( (int) $post->post_parent !== $parent ) {
			$upd['post_parent'] = $parent;
		}
		if ( $post->post_name !== $def['slug'] ) {
			$upd['post_name'] = $def['slug'];
		}
		if ( 'publish' !== $post->post_status ) {
			$upd['post_status'] = 'publish';
		}
		if ( count( $upd ) > 1 ) {
			wp_update_post( $upd );
		}
		update_post_meta( $id, '_pixva_route', $key );
		update_post_meta( $id, '_wp_page_template', $def['template'] );
		$ids[ $key ] = $id;
	}
	return $ids;
}

/**
 * Find a page by route meta or exact path, or create it.
 *
 * @param string $slug   Slug.
 * @param string $title  Title.
 * @param string $route  Route key.
 * @param int    $parent_id Parent id.
 * @param string $path   Expected path.
 * @return int
 */
function pixva_find_or_create_page( $slug, $title, $route, $parent_id, $path = '' ) {
	$by_meta = get_posts(
		array(
			'post_type'        => 'page',
			'post_status'      => array( 'publish', 'draft', 'private', 'pending' ),
			'meta_key'         => '_pixva_route', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'meta_value'       => $route, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			'posts_per_page'   => 1,
			'fields'           => 'ids',
			'suppress_filters' => true,
		)
	);
	if ( $by_meta ) {
		return (int) $by_meta[0];
	}
	$existing = get_page_by_path( trim( $path ? $path : $slug, '/' ), OBJECT, 'page' );
	if ( $existing && 'trash' !== $existing->post_status ) {
		return (int) $existing->ID;
	}
	$id = wp_insert_post(
		array(
			'post_type'      => 'page',
			'post_status'    => 'publish',
			'post_title'     => $title,
			'post_name'      => $slug,
			'post_parent'    => (int) $parent_id,
			'post_content'   => '',
			'comment_status' => 'closed',
			'ping_status'    => 'closed',
		),
		true
	);
	return is_wp_error( $id ) ? 0 : (int) $id;
}

/**
 * Account sub-route of the current page: dashboard|repairs|warranty|profile.
 *
 * @return string
 */
function pixva_account_section() {
	$route = is_page() ? (string) get_post_meta( get_queried_object_id(), '_pixva_route', true ) : '';
	$map   = array(
		'account'          => 'overview',
		'account_repairs'  => 'repairs',
		'account_warranty' => 'warranty',
		'account_profile'  => 'profile',
	);
	return $map[ $route ] ?? 'overview';
}

/**
 * Static first-level rule for /blog/page/N/ so blog pagination resolves to
 * the posts page (the /blog/%postname%/ structure would otherwise map it to
 * a generic paged query, which WordPress serves as the static front page).
 *
 * @return void
 */
function pixva_blog_pagination_rule() {
	add_rewrite_rule( '^blog/page/?([0-9]{1,})/?$', 'index.php?pagename=blog&paged=$matches[1]', 'top' );
}
add_action( 'init', 'pixva_blog_pagination_rule', 6 );

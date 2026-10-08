<?php
/**
 * Central SEO layer (§19, §21, §22, §48, §52).
 *
 * One function decides indexability (pixva_is_indexable) and one decides
 * the canonical URL (pixva_canonical_url); titles, robots meta, sitemaps
 * and OG tags all read from them, so they can never disagree.
 *
 * Indexing rules (§48)
 * - noindex: search, 404/410, account/dashboard, any page rendered with
 *   result/query parameters, tag/date archives, empty archives, thin
 *   problem terms (<80 chars description and no items), error codes
 *   without meaning, services without body or symptoms, per-post
 *   "noindex" flag, posts whose path is the source of an active redirect.
 * - canonical: clean URL without query args; paginated archives
 *   self-canonical (page N); singular paginated content keeps its page.
 * - sitemaps: WordPress core sitemaps (paginated, 2000 URLs/page),
 *   filtered to indexable URLs only.
 *
 * Plugin compatibility: if Yoast SEO, Rank Math or SEOPress is active,
 * this layer only keeps the sitemap/robots filters and stops printing
 * head tags (no duplicate metadata).
 *
 * @package Pixva
 * @since   2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Whether a dedicated SEO plugin owns head metadata.
 *
 * @return bool
 */
function pixva_seo_plugin_active() {
	return defined( 'WPSEO_VERSION' ) || defined( 'RANK_MATH_VERSION' ) || defined( 'SEOPRESS_VERSION' );
}

/**
 * Query args that turn a page into a non-canonical "result" view.
 *
 * @return string[]
 */
function pixva_result_query_args() {
	return array( 'problem', 'brand', 'model', 'symptoms', 'age', 'size', 'service', 'step', 'code', 'pixva_r', 'from', 'q', 'severity', 'redirect_to' );
}

/**
 * Whether the request carries result parameters.
 *
 * @return bool
 */
function pixva_has_result_params() {
	foreach ( pixva_result_query_args() as $arg ) {
		if ( isset( $_GET[ $arg ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read only.
			return true;
		}
	}
	return false;
}

/**
 * Thin-content checks per post.
 *
 * @param WP_Post $post Post.
 * @return bool
 */
function pixva_post_is_thin( $post ) {
	switch ( $post->post_type ) {
		case 'pixva_error':
			return '' === trim( (string) get_post_meta( $post->ID, '_pixva_err_meaning', true ) );
		case 'tv_services':
			return pixva_strlen( wp_strip_all_tags( $post->post_content ) ) < 200 && ! get_post_meta( $post->ID, '_pixva_service_symptoms', true );
		case 'tv_brands':
		case 'tv_model':
			return pixva_strlen( wp_strip_all_tags( $post->post_content ) ) < 200 && ! get_post_meta( $post->ID, '_pixva_model_issues', true ) && ! pixva_brand_has_children( $post );
		case 'post':
			// Stub articles (e.g. WordPress' default "Hello world") stay out of the index.
			return pixva_strlen( wp_strip_all_tags( strip_shortcodes( $post->post_content ) ) ) < 300;
		case 'repair_cases':
			return pixva_strlen( wp_strip_all_tags( $post->post_content ) ) < 100 && ! get_post_meta( $post->ID, '_pixva_case_after', true );
	}
	return false;
}

/**
 * Whether a brand has published models or error codes (makes it a hub).
 *
 * @param WP_Post $post Brand or model.
 * @return bool
 */
function pixva_brand_has_children( $post ) {
	if ( 'tv_brands' !== $post->post_type ) {
		return false;
	}
	foreach ( array(
		'tv_model'    => '_pixva_brand_id',
		'pixva_error' => '_pixva_err_brand_id',
	) as $type => $key ) {
		$found = get_posts(
			array(
				'post_type'      => $type,
				'post_status'    => 'publish',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'no_found_rows'  => true,
				'meta_key'       => $key,
				'meta_value'     => (int) $post->ID,
			)
		); // phpcs:ignore WordPress.DB.SlowDBQuery
		if ( $found ) {
			return true;
		}
	}
	return false;
}

/**
 * Whether a problem term is thin.
 *
 * @param WP_Term $term Term.
 * @return bool
 */
function pixva_term_is_thin( $term ) {
	if ( pixva_strlen( wp_strip_all_tags( (string) $term->description ) ) >= 80 || (int) $term->count >= 1 ) {
		return false;
	}
	// The hub also renders its own symptom / safe-check lists (taxonomy-tv_problem.php).
	$lines = count( pixva_term_lines( $term->term_id, '_pixva_problem_symptoms' ) ) + count( pixva_term_lines( $term->term_id, '_pixva_problem_safe' ) );
	return $lines < 3;
}

/**
 * Active redirect sources (normalised paths).
 *
 * @return string[]
 */
function pixva_redirect_sources() {
	$out = array();
	foreach ( pixva_get_redirects() as $r ) {
		if ( ! empty( $r['active'] ) ) {
			$out[] = $r['source'];
		}
	}
	return $out;
}

/**
 * Single indexability decision for the current request.
 *
 * @return bool
 */
function pixva_is_indexable() {
	if ( ! get_option( 'blog_public' ) || is_search() || is_404() || ! empty( $GLOBALS['pixva_is_gone'] ) || is_tag() || is_date() || is_author() || is_attachment() || is_preview() ) {
		return false;
	}
	if ( pixva_has_result_params() ) {
		return false;
	}
	$route = pixva_current_route();
	if ( $route && empty( pixva_routes()[ $route ]['index'] ) ) {
		return false;
	}
	if ( is_singular() ) {
		$post = get_queried_object();
		if ( ! $post instanceof WP_Post || 'publish' !== $post->post_status || get_post_meta( $post->ID, '_pixva_seo_noindex', true ) || pixva_post_is_thin( $post ) ) {
			return false;
		}
		if ( in_array( pixva_post_path( $post ), pixva_redirect_sources(), true ) ) {
			return false;
		}
	}
	if ( is_tax( 'tv_problem' ) && pixva_term_is_thin( get_queried_object() ) ) {
		return false;
	}
	if ( ( is_post_type_archive() || is_category() || is_home() ) && ! have_posts() ) {
		return false;
	}
	return (bool) apply_filters( 'pixva_is_indexable', true );
}

/**
 * Canonical URL of the current request ('' when none applies).
 *
 * @return string
 */
function pixva_canonical_url() {
	$paged = max( 1, (int) get_query_var( 'paged' ) );
	$url   = '';
	if ( is_front_page() ) {
		$url = home_url( '/' );
	} elseif ( is_home() ) {
		$url = pixva_route_url( 'blog' );
	} elseif ( is_singular() ) {
		$url  = (string) get_permalink( get_queried_object_id() );
		$page = max( 1, (int) get_query_var( 'page' ) );
		if ( $page > 1 ) {
			$url = trailingslashit( $url ) . user_trailingslashit( (string) $page, 'single_paged' );
		}
		return $url;
	} elseif ( is_post_type_archive() ) {
		$url = (string) get_post_type_archive_link( (string) get_query_var( 'post_type' ) );
	} elseif ( is_category() || is_tax() ) {
		$term = get_queried_object();
		$link = $term instanceof WP_Term ? get_term_link( $term ) : '';
		$url  = is_wp_error( $link ) ? '' : (string) $link;
	}
	if ( '' !== $url && $paged > 1 ) {
		$url = trailingslashit( $url ) . user_trailingslashit( 'page/' . $paged, 'paged' );
	}
	return $url;
}

/**
 * Robots directives (core wp_robots API).
 *
 * @param array $robots Directives.
 * @return array
 */
function pixva_wp_robots( $robots ) {
	if ( is_admin() ) {
		return $robots;
	}
	if ( ! pixva_is_indexable() ) {
		$robots['noindex'] = true;
		$robots['follow']  = true;
		unset( $robots['max-image-preview'] );
	} else {
		$robots['max-image-preview'] = 'large';
	}
	return $robots;
}
add_filter( 'wp_robots', 'pixva_wp_robots', 20 );

/**
 * Default description for system routes.
 *
 * @param string $route Route key.
 * @return string
 */
function pixva_route_description( $route ) {
	$d = array(
		'services'         => __( 'فهرست خدمات تعمیر تلویزیون، نشانه‌های هر ایراد و مراحل انجام کار.', 'pixva' ),
		'brands'           => __( 'راهنمای برندهای تلویزیون، مدل‌ها و کدهای خطای هر برند.', 'pixva' ),
		'problems'         => __( 'مشکلات رایج تلویزیون، نشانه‌ها و بررسی‌های ایمنی که خودتان می‌توانید انجام دهید.', 'pixva' ),
		'tools'            => __( 'ابزارهای آنلاین تشخیص ایراد، برآورد هزینه، تست پیکسل و جست‌وجوی کد خطای تلویزیون.', 'pixva' ),
		'diagnosis'        => __( 'با چند پرسش ساده، علت‌های محتمل ایراد تلویزیون خود را ببینید و در صورت نیاز درخواست تعمیر ثبت کنید.', 'pixva' ),
		'price_calculator' => __( 'برآورد هزینه تعمیر تلویزیون بر اساس نوع ایراد، اندازه و برند.', 'pixva' ),
		'pixel_test'       => __( 'تست تمام‌صفحه پیکسل سوخته، یکنواختی نور و رنگ برای تلویزیون و مانیتور.', 'pixva' ),
		'error_codes'      => __( 'معنی کدهای خطا و الگوی چشمک چراغ پاور تلویزیون به تفکیک برند، با بررسی‌های ایمن.', 'pixva' ),
		'repair'           => __( 'روند تعمیر تلویزیون از ثبت درخواست تا تحویل و پیگیری آنلاین.', 'pixva' ),
		'booking'          => __( 'فرم ثبت درخواست تعمیر تلویزیون و دریافت کد پیگیری.', 'pixva' ),
		'tracking'         => __( 'پیگیری آنلاین وضعیت تعمیر تلویزیون با کد پیگیری و شماره همراه.', 'pixva' ),
		'warranty'         => __( 'شرایط گارانتی تعمیر و استعلام وضعیت گارانتی دستگاه.', 'pixva' ),
		'portfolio'        => __( 'نمونه‌کارهای مستند تعمیر تلویزیون.', 'pixva' ),
		'blog'             => __( 'مقاله‌های آموزشی درباره ایرادهای تلویزیون، نگهداری و تعمیر.', 'pixva' ),
		'faq'              => __( 'پاسخ پرسش‌های رایج درباره ثبت درخواست، هزینه، پیگیری و گارانتی تعمیر.', 'pixva' ),
		'contact'          => __( 'راه‌های ارتباط و فرم تماس.', 'pixva' ),
	);
	return $d[ $route ] ?? '';
}

/**
 * Meta description for the current request.
 *
 * @return string
 */
function pixva_meta_description() {
	$desc = '';
	if ( is_singular() ) {
		$id   = get_queried_object_id();
		$desc = (string) get_post_meta( $id, '_pixva_seo_desc', true );
		if ( '' === $desc && 'pixva_error' === get_post_type( $id ) ) {
			$desc = (string) get_post_meta( $id, '_pixva_err_meaning', true );
		}
		if ( '' === $desc && has_excerpt( $id ) ) {
			$desc = get_the_excerpt( $id );
		}
		if ( '' === $desc ) {
			$desc = wp_strip_all_tags( strip_shortcodes( (string) get_post_field( 'post_content', $id ) ) );
		}
	} elseif ( is_category() || is_tax() ) {
		$desc = wp_strip_all_tags( term_description() );
	}
	$route = pixva_current_route();
	if ( '' === trim( $desc ) && $route ) {
		$desc = pixva_route_description( $route );
	}
	if ( '' === trim( $desc ) && is_front_page() ) {
		$desc = (string) ( pixva_claim( 'tagline' ) ? pixva_claim( 'tagline' ) : get_bloginfo( 'description' ) );
	}
	if ( '' === trim( $desc ) && is_front_page() ) {
		$desc = pixva_front_lead();
	}
	$desc = trim( preg_replace( '/\s+/u', ' ', $desc ) );
	return pixva_strlen( $desc ) > 160 ? rtrim( pixva_substr( $desc, 0, 157 ) ) . '…' : $desc;
}

/**
 * Title parts: custom SEO title, route titles for archives.
 *
 * @param array $parts Parts.
 * @return array
 */
function pixva_title_parts( $parts ) {
	if ( is_singular() ) {
		$custom = (string) get_post_meta( get_queried_object_id(), '_pixva_seo_title', true );
		if ( '' !== $custom ) {
			$parts['title'] = $custom;
		}
	} elseif ( is_post_type_archive() ) {
		$route = pixva_current_route();
		if ( $route ) {
			$parts['title'] = pixva_routes()[ $route ]['title'];
		}
	} elseif ( is_404() && ! empty( $GLOBALS['pixva_is_gone'] ) ) {
		$parts['title'] = __( 'این صفحه حذف شده است', 'pixva' );
	} elseif ( is_404() ) {
		$parts['title'] = __( 'صفحه پیدا نشد', 'pixva' );
	} elseif ( is_search() ) {
		/* translators: %s: search query. */
		$parts['title'] = sprintf( __( 'نتایج جست‌وجو برای «%s»', 'pixva' ), get_search_query( false ) );
	}
	if ( ( is_front_page() || ( is_home() && ! get_option( 'page_for_posts' ) ) ) && empty( $parts['tagline'] ) && '' === trim( (string) get_bloginfo( 'description' ) ) ) {
		// Describes what the site is (no claim); replaced by the real tagline once set.
		$parts['tagline'] = __( 'تشخیص و تعمیر تلویزیون', 'pixva' );
	}
	if ( ! empty( $parts['page'] ) ) {
		/* translators: %s: page number. */
		$parts['page'] = sprintf( __( 'صفحه %s', 'pixva' ), pixva_fa_num( max( (int) get_query_var( 'paged' ), (int) get_query_var( 'page' ) ) ) );
	}
	return $parts;
}
add_filter( 'document_title_parts', 'pixva_title_parts' );

/**
 * Print canonical, description and social tags.
 *
 * @return void
 */
function pixva_print_seo_head() {
	if ( pixva_seo_plugin_active() ) {
		return;
	}
	$desc      = pixva_meta_description();
	$canonical = pixva_is_indexable() ? pixva_canonical_url() : '';
	if ( '' === $canonical && pixva_has_result_params() ) {
		// Parameter views point to the clean route URL.
		$route     = pixva_current_route();
		$canonical = $route ? pixva_route_url( $route ) : '';
	}
	if ( '' !== $desc ) {
		echo '<meta name="description" content="' . esc_attr( $desc ) . '">' . "\n";
	}
	if ( '' !== $canonical ) {
		echo '<link rel="canonical" href="' . esc_url( $canonical ) . '">' . "\n";
	}
	$title = wp_get_document_title();
	$type  = is_singular( 'post' ) ? 'article' : 'website';
	$image = '';
	if ( is_singular() && has_post_thumbnail() ) {
		$image = (string) get_the_post_thumbnail_url( get_queried_object_id(), 'large' );
	}
	echo '<meta property="og:locale" content="fa_IR">' . "\n";
	echo '<meta property="og:type" content="' . esc_attr( $type ) . '">' . "\n";
	echo '<meta property="og:title" content="' . esc_attr( $title ) . '">' . "\n";
	echo '<meta property="og:site_name" content="' . esc_attr( get_bloginfo( 'name' ) ) . '">' . "\n";
	if ( '' !== $desc ) {
		echo '<meta property="og:description" content="' . esc_attr( $desc ) . '">' . "\n";
	}
	if ( '' !== $canonical ) {
		echo '<meta property="og:url" content="' . esc_url( $canonical ) . '">' . "\n";
	}
	if ( '' !== $image ) {
		echo '<meta property="og:image" content="' . esc_url( $image ) . '">' . "\n";
	}
	echo '<meta name="twitter:card" content="' . ( $image ? 'summary_large_image' : 'summary' ) . '">' . "\n";
	if ( is_singular( 'post' ) ) {
		echo '<meta property="article:published_time" content="' . esc_attr( get_the_date( 'c' ) ) . '">' . "\n";
		echo '<meta property="article:modified_time" content="' . esc_attr( get_the_modified_date( 'c' ) ) . '">' . "\n";
	}
}
add_action( 'wp_head', 'pixva_print_seo_head', 2 );

/**
 * Core prints its own canonical for singulars; ours covers all views.
 *
 * @return void
 */
function pixva_remove_core_canonical() {
	if ( ! pixva_seo_plugin_active() ) {
		remove_action( 'wp_head', 'rel_canonical' );
	}
	remove_action( 'wp_head', 'wp_shortlink_wp_head' );
}
add_action( 'template_redirect', 'pixva_remove_core_canonical' );

/*
 * ---------------------------------------------------------------------------
 * Per-post SEO fields (central metadata, §52)
 * ---------------------------------------------------------------------------
 */

/**
 * Add SEO fields to every public type through the meta framework.
 *
 * @param array $schema Schema.
 * @return array
 */
function pixva_seo_meta_schema( $schema ) {
	foreach ( array( 'post', 'page', 'tv_services', 'tv_brands', 'tv_model', 'pixva_error', 'repair_cases' ) as $type ) {
		$schema[ $type ]['seo'] = array(
			'title'   => __( 'سئو', 'pixva' ),
			'context' => 'side',
			'fields'  => array(
				'_pixva_seo_title'   => array(
					'type'  => 'text',
					'label' => __( 'عنوان سئو', 'pixva' ),
					'help'  => __( 'خالی = عنوان نوشته.', 'pixva' ),
				),
				'_pixva_seo_desc'    => array(
					'type'  => 'textarea',
					'label' => __( 'توضیح متا', 'pixva' ),
					'help'  => __( 'حدود ۱۵۰ نویسه.', 'pixva' ),
				),
				'_pixva_seo_noindex' => array(
					'type'  => 'checkbox',
					'label' => __( 'در نتایج جست‌وجو نمایش داده نشود', 'pixva' ),
				),
			),
		);
	}
	return $schema;
}
add_filter( 'pixva_meta_schema', 'pixva_seo_meta_schema', 20 );

/*
 * ---------------------------------------------------------------------------
 * Sitemaps (§21) & robots.txt (§22)
 * ---------------------------------------------------------------------------
 */

/**
 * Only indexable public types.
 *
 * @param array $types Types.
 * @return array
 */
function pixva_sitemap_post_types( $types ) {
	unset( $types['attachment'], $types['pixva_faq'] );
	return $types;
}
add_filter( 'wp_sitemaps_post_types', 'pixva_sitemap_post_types' );

/**
 * Only category and problem taxonomies.
 *
 * @param array $taxes Taxonomies.
 * @return array
 */
function pixva_sitemap_taxonomies( $taxes ) {
	return array_intersect_key( $taxes, array_flip( array( 'category', 'tv_problem' ) ) );
}
add_filter( 'wp_sitemaps_taxonomies', 'pixva_sitemap_taxonomies' );

/**
 * No user sitemaps (author archives are disabled).
 *
 * @param mixed  $provider Provider.
 * @param string $name     Name.
 * @return mixed
 */
function pixva_sitemap_providers( $provider, $name ) {
	return 'users' === $name ? false : $provider;
}
add_filter( 'wp_sitemaps_add_provider', 'pixva_sitemap_providers', 10, 2 );

/**
 * IDs of published posts of a type that must not appear in sitemaps
 * (noindex, thin, redirect source, or a non-indexable route page).
 *
 * Computed in batches without an upper cap and cached until content,
 * redirects or gone paths change (see pixva_flush_sitemap_cache()).
 *
 * @param string $post_type Post type.
 * @return int[]
 */
function pixva_sitemap_excluded_ids( $post_type ) {
	$cache = get_transient( 'pixva_sitemap_excluded' );
	$cache = is_array( $cache ) ? $cache : array();
	if ( isset( $cache[ $post_type ] ) && is_array( $cache[ $post_type ] ) ) {
		return $cache[ $post_type ];
	}
	$exclude = array();
	$sources = pixva_redirect_sources();
	$routes  = pixva_routes();
	$page    = 1;
	do {
		$batch = get_posts(
			array(
				'post_type'        => $post_type,
				'post_status'      => 'publish',
				'posts_per_page'   => 100,
				'paged'            => $page,
				'orderby'          => 'ID',
				'order'            => 'ASC',
				'no_found_rows'    => true,
				'suppress_filters' => true,
			)
		);
		foreach ( $batch as $p ) {
			if ( get_post_meta( $p->ID, '_pixva_seo_noindex', true ) || pixva_post_is_thin( $p ) || in_array( pixva_post_path( $p ), $sources, true ) ) {
				$exclude[] = (int) $p->ID;
				continue;
			}
			if ( 'page' === $post_type ) {
				$route = (string) get_post_meta( $p->ID, '_pixva_route', true );
				if ( '' !== $route && isset( $routes[ $route ] ) && empty( $routes[ $route ]['index'] ) ) {
					$exclude[] = (int) $p->ID;
				}
			}
		}
		++$page;
		$batch_size = count( $batch );
	} while ( 100 === $batch_size );
	$cache[ $post_type ] = $exclude;
	set_transient( 'pixva_sitemap_excluded', $cache, DAY_IN_SECONDS );
	return $exclude;
}

/**
 * Invalidate the sitemap exclusion cache.
 *
 * @return void
 */
function pixva_flush_sitemap_cache() {
	delete_transient( 'pixva_sitemap_excluded' );
}
foreach ( array( 'save_post', 'deleted_post', 'trashed_post', 'untrashed_post', 'added_post_meta', 'updated_post_meta', 'deleted_post_meta', 'update_option_pixva_redirects', 'update_option_pixva_gone_paths', 'add_option_pixva_redirects', 'add_option_pixva_gone_paths', 'switch_theme' ) as $pixva_hook ) {
	add_action( $pixva_hook, 'pixva_flush_sitemap_cache' );
}
unset( $pixva_hook );

/**
 * Exclude non-indexable posts from sitemaps.
 *
 * @param array  $args      Query args.
 * @param string $post_type Type.
 * @return array
 */
function pixva_sitemap_post_args( $args, $post_type ) {
	$args['post__not_in'] = array_values( array_unique( array_filter( array_merge( (array) ( $args['post__not_in'] ?? array() ), pixva_sitemap_excluded_ids( $post_type ) ) ) ) ); // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_post__not_in -- bounded to non-indexable IDs.
	return $args;
}
add_filter( 'wp_sitemaps_posts_query_args', 'pixva_sitemap_post_args', 10, 2 );

/**
 * Exclude thin problem terms and empty categories.
 *
 * @param array  $args     Args.
 * @param string $taxonomy Taxonomy.
 * @return array
 */
function pixva_sitemap_tax_args( $args, $taxonomy ) {
	if ( 'tv_problem' === $taxonomy ) {
		$exclude = array();
		foreach ( get_terms(
			array(
				'taxonomy'   => 'tv_problem',
				'hide_empty' => false,
			)
		) as $t ) {
			if ( ! is_wp_error( $t ) && pixva_term_is_thin( $t ) ) {
				$exclude[] = $t->term_id;
			}
		}
		$args['exclude']    = $exclude;
		$args['hide_empty'] = false;
	}
	return $args;
}
add_filter( 'wp_sitemaps_taxonomies_query_args', 'pixva_sitemap_tax_args', 10, 2 );

/**
 * Rules for robots.txt: only wp-admin is disallowed; CSS/JS/uploads stay crawlable.
 *
 * @param string $output Core output.
 * @param bool   $is_public Whether the site is public.
 * @return string
 */
function pixva_robots_txt( $output, $is_public ) {
	if ( ! $is_public ) {
		return "User-agent: *\nDisallow: /\n";
	}
	$lines   = array( 'User-agent: *', 'Disallow: /wp-admin/', 'Allow: /wp-admin/admin-ajax.php', '' );
	$lines[] = 'Sitemap: ' . home_url( '/wp-sitemap.xml' );
	return implode( "\n", $lines ) . "\n";
}
add_filter( 'robots_txt', 'pixva_robots_txt', 20, 2 );

/*
 * ---------------------------------------------------------------------------
 * Archive route sitemap provider (services, brands, error codes, portfolio,
 * tools pages are pages already). Only archives with published items.
 * ---------------------------------------------------------------------------
 */

/**
 * Register the archives provider.
 *
 * @return void
 */
function pixva_register_archive_sitemap() {
	if ( ! class_exists( 'WP_Sitemaps_Provider' ) ) {
		return;
	}
	require_once PIXVA_DIR . '/inc/class-pixva-archive-sitemap.php';
	wp_register_sitemap_provider( 'pixvaarchives', new Pixva_Archive_Sitemap() );
}
add_action( 'init', 'pixva_register_archive_sitemap', 30 );

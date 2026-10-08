<?php
/**
 * Content model (§16, §17, §37, §38, §41).
 *
 * Public entities
 * | entity        | type          | URL                          |
 * |---------------|---------------|------------------------------|
 * | service       | tv_services   | /services/{service}/         |
 * | brand         | tv_brands     | /brands/{brand}/             |
 * | model         | tv_model      | /brands/{brand}/{model}/     |
 * | problem       | tv_problem tx | /problems/{problem}/         |
 * | error code    | pixva_error   | /error-codes/{code}/         |
 * | portfolio     | repair_cases  | /portfolio/{case}/           |
 * | article       | post          | /blog/{article}/             |
 * | category      | category tx   | /blog/category/{category}/   |
 * | FAQ           | pixva_faq     | rendered on /faq/ (no single)|
 *
 * Private entities (custom capabilities, never public)
 * | repair order  | pixva_orders  | front-end via /account/, /dashboard/ |
 * | message       | pixva_inbox   | wp-admin only                |
 * | part          | pixva_part    | wp-admin only (internal list)|
 *
 * Collision resolutions (§41)
 * - Warranty is an attribute of a repair order (start/end/source), not a
 *   separate post type; the warranty *policy* is a business claim.
 * - Business claims are one option (singular data), not a post type.
 * - Problems are a taxonomy so services, cases, error codes and articles
 *   can share them; /problems/ is a hub page.
 * - "Parts" are internal (no public inventory/prices → no fake stock, §34);
 *   v1.x public /parts/* URLs return 410.
 * - v1.x "branches" had no legitimate content → not registered, 410.
 * - v1.x tv_tech taxonomy kept for data but non-public (thin archives).
 *
 * Slugs are stable and migration-friendly; v1.x slugs (cases, problem,
 * error-database) are 301-redirected in redirects.php.
 *
 * @package Pixva
 * @since   2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register post types and taxonomies.
 *
 * @return void
 */
function pixva_register_content_model() {
	$public_base = array(
		'public'        => true,
		'show_in_rest'  => true,
		'menu_position' => 21,
		'supports'      => array( 'title', 'editor', 'excerpt', 'thumbnail', 'revisions', 'page-attributes' ),
	);

	register_post_type(
		'tv_services',
		array_merge(
			$public_base,
			array(
				'labels'      => pixva_cpt_labels( __( 'خدمات', 'pixva' ), __( 'خدمت', 'pixva' ) ),
				'menu_icon'   => 'dashicons-admin-tools',
				'has_archive' => 'services',
				'rewrite'     => array(
					'slug'       => 'services',
					'with_front' => false,
					'feeds'      => false,
				),
			)
		)
	);

	register_post_type(
		'tv_brands',
		array_merge(
			$public_base,
			array(
				'labels'      => pixva_cpt_labels( __( 'برندها', 'pixva' ), __( 'برند', 'pixva' ) ),
				'menu_icon'   => 'dashicons-tag',
				'has_archive' => 'brands',
				'rewrite'     => array(
					'slug'       => 'brands',
					'with_front' => false,
					'feeds'      => false,
				),
			)
		)
	);

	register_post_type(
		'tv_model',
		array_merge(
			$public_base,
			array(
				'labels'      => pixva_cpt_labels( __( 'مدل‌ها', 'pixva' ), __( 'مدل', 'pixva' ) ),
				'menu_icon'   => 'dashicons-desktop',
				'has_archive' => false,
				'rewrite'     => false, // Built by pixva_model_rewrite() as /brands/{brand}/{model}/.
				'query_var'   => 'tv_model',
				'supports'    => array( 'title', 'editor', 'excerpt', 'thumbnail', 'revisions' ),
			)
		)
	);

	register_post_type(
		'pixva_error',
		array_merge(
			$public_base,
			array(
				'labels'      => pixva_cpt_labels( __( 'کدهای خطا', 'pixva' ), __( 'کد خطا', 'pixva' ) ),
				'menu_icon'   => 'dashicons-warning',
				'has_archive' => 'error-codes',
				'rewrite'     => array(
					'slug'       => 'error-codes',
					'with_front' => false,
					'feeds'      => false,
				),
				'supports'    => array( 'title', 'editor', 'excerpt', 'revisions' ),
			)
		)
	);

	register_post_type(
		'repair_cases',
		array_merge(
			$public_base,
			array(
				'labels'      => pixva_cpt_labels( __( 'نمونه‌کارها', 'pixva' ), __( 'نمونه‌کار', 'pixva' ) ),
				'menu_icon'   => 'dashicons-format-gallery',
				'has_archive' => 'portfolio',
				'rewrite'     => array(
					'slug'       => 'portfolio',
					'with_front' => false,
					'feeds'      => false,
				),
			)
		)
	);

	register_post_type(
		'pixva_faq',
		array(
			'labels'              => pixva_cpt_labels( __( 'پرسش‌های متداول', 'pixva' ), __( 'پرسش', 'pixva' ) ),
			'public'              => false,
			'show_ui'             => true,
			'show_in_rest'        => true,
			'publicly_queryable'  => false,
			'exclude_from_search' => true,
			'has_archive'         => false,
			'rewrite'             => false,
			'menu_icon'           => 'dashicons-editor-help',
			'menu_position'       => 22,
			'supports'            => array( 'title', 'editor', 'page-attributes', 'revisions' ),
		)
	);

	register_post_type(
		'pixva_orders',
		array(
			'labels'              => pixva_cpt_labels( __( 'پرونده‌های تعمیر', 'pixva' ), __( 'پرونده تعمیر', 'pixva' ) ),
			'public'              => false,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'show_in_rest'        => false,
			'publicly_queryable'  => false,
			'exclude_from_search' => true,
			'has_archive'         => false,
			'rewrite'             => false,
			'query_var'           => false,
			'menu_icon'           => 'dashicons-clipboard',
			'menu_position'       => 3,
			'supports'            => array( 'title' ),
			'capability_type'     => array( 'pixva_order', 'pixva_orders' ),
			'map_meta_cap'        => true,
			'capabilities'        => array( 'create_posts' => 'create_pixva_orders' ),
		)
	);

	register_post_type(
		'pixva_inbox',
		array(
			'labels'              => pixva_cpt_labels( __( 'پیام‌ها', 'pixva' ), __( 'پیام', 'pixva' ) ),
			'public'              => false,
			'show_ui'             => true,
			'show_in_rest'        => false,
			'publicly_queryable'  => false,
			'exclude_from_search' => true,
			'rewrite'             => false,
			'query_var'           => false,
			'menu_icon'           => 'dashicons-email',
			'menu_position'       => 4,
			'supports'            => array( 'title' ),
			'capability_type'     => array( 'pixva_message', 'pixva_messages' ),
			'map_meta_cap'        => true,
			'capabilities'        => array( 'create_posts' => 'do_not_allow' ),
		)
	);

	register_post_type(
		'pixva_part',
		array(
			'labels'              => pixva_cpt_labels( __( 'قطعات (داخلی)', 'pixva' ), __( 'قطعه', 'pixva' ) ),
			'public'              => false,
			'show_ui'             => true,
			'show_in_menu'        => 'edit.php?post_type=pixva_orders',
			'show_in_rest'        => false,
			'publicly_queryable'  => false,
			'exclude_from_search' => true,
			'rewrite'             => false,
			'query_var'           => false,
			'supports'            => array( 'title' ),
			'capability_type'     => array( 'pixva_order', 'pixva_orders' ),
			'map_meta_cap'        => true,
		)
	);

	register_taxonomy(
		'tv_problem',
		array( 'tv_services', 'repair_cases', 'pixva_error', 'post', 'tv_model' ),
		array(
			'labels'            => array(
				'name'          => __( 'مشکلات', 'pixva' ),
				'singular_name' => __( 'مشکل', 'pixva' ),
				'add_new_item'  => __( 'افزودن مشکل', 'pixva' ),
				'edit_item'     => __( 'ویرایش مشکل', 'pixva' ),
				'search_items'  => __( 'جست‌وجوی مشکل', 'pixva' ),
			),
			'public'            => true,
			'hierarchical'      => true,
			'show_in_rest'      => true,
			'show_admin_column' => true,
			'rewrite'           => array(
				'slug'         => 'problems',
				'with_front'   => false,
				'hierarchical' => false,
			),
		)
	);

	register_taxonomy(
		'tv_tech',
		array( 'tv_model', 'repair_cases' ),
		array(
			'labels'             => array(
				'name'          => __( 'فناوری پنل', 'pixva' ),
				'singular_name' => __( 'فناوری پنل', 'pixva' ),
			),
			'public'             => false,
			'publicly_queryable' => false,
			'show_ui'            => true,
			'show_in_rest'       => true,
			'hierarchical'       => true,
			'rewrite'            => false,
			'query_var'          => false,
			'show_admin_column'  => true,
		)
	);
}
add_action( 'init', 'pixva_register_content_model', 5 );

/**
 * Standard labels.
 *
 * @param string $plural   Plural.
 * @param string $singular Singular.
 * @return array
 */
function pixva_cpt_labels( $plural, $singular ) {
	return array(
		'name'               => $plural,
		'singular_name'      => $singular,
		'menu_name'          => $plural,
		/* translators: %s: singular label. */
		'add_new_item'       => sprintf( __( 'افزودن %s', 'pixva' ), $singular ),
		'add_new'            => __( 'افزودن', 'pixva' ),
		/* translators: %s: singular label. */
		'edit_item'          => sprintf( __( 'ویرایش %s', 'pixva' ), $singular ),
		/* translators: %s: singular label. */
		'view_item'          => sprintf( __( 'مشاهده %s', 'pixva' ), $singular ),
		'all_items'          => $plural,
		/* translators: %s: plural label. */
		'search_items'       => sprintf( __( 'جست‌وجو در %s', 'pixva' ), $plural ),
		'not_found'          => __( 'موردی پیدا نشد.', 'pixva' ),
		'not_found_in_trash' => __( 'موردی در زباله‌دان نیست.', 'pixva' ),
	);
}

/*
 * ---------------------------------------------------------------------------
 * Model URLs: /brands/{brand}/{model}/
 * ---------------------------------------------------------------------------
 */

/**
 * Rewrite rule for models (registered before the brand permastruct so it
 * wins; reserved segments are excluded so brand feeds/pagination keep working).
 *
 * @return void
 */
function pixva_model_rewrite() {
	add_rewrite_tag( '%pixva_brand%', '([^/]+)' );
	add_rewrite_rule( '^brands/([^/]+)/(?!page/|feed/|embed/|trackback/)([^/]+)/?$', 'index.php?post_type=tv_model&tv_model=$matches[2]&pixva_brand=$matches[1]', 'top' );
}
add_action( 'init', 'pixva_model_rewrite', 6 );

/**
 * Brand post of a model (published only), or null.
 *
 * @param int $model_id Model id.
 * @return WP_Post|null
 */
function pixva_model_brand( $model_id ) {
	$brand_id = (int) get_post_meta( (int) $model_id, '_pixva_brand_id', true );
	$brand    = $brand_id ? get_post( $brand_id ) : null;
	return ( $brand && 'tv_brands' === $brand->post_type ) ? $brand : null;
}

/**
 * Permalink for models.
 *
 * @param string  $link Link.
 * @param WP_Post $post Post.
 * @return string
 */
function pixva_model_permalink( $link, $post ) {
	if ( 'tv_model' !== $post->post_type ) {
		return $link;
	}
	$brand = pixva_model_brand( $post->ID );
	if ( ! $brand || ! get_option( 'permalink_structure' ) || '' === $post->post_name ) {
		return add_query_arg(
			array(
				'post_type' => 'tv_model',
				'p'         => $post->ID,
			),
			home_url( '/' )
		);
	}
	return home_url( user_trailingslashit( 'brands/' . $brand->post_name . '/' . $post->post_name ) );
}
add_filter( 'post_type_link', 'pixva_model_permalink', 10, 2 );

/**
 * Validate the brand segment of a model URL: wrong brand → one 301 to the
 * canonical URL; model without brand → 404 (no orphan duplicate pages).
 *
 * @return void
 */
function pixva_model_canonical_guard() {
	if ( ! is_singular( 'tv_model' ) ) {
		return;
	}
	$post  = get_queried_object();
	$brand = pixva_model_brand( $post->ID );
	if ( ! $brand ) {
		if ( ! current_user_can( 'edit_post', $post->ID ) ) {
			global $wp_query;
			$wp_query->set_404();
			status_header( 404 );
		}
		return;
	}
	$seg = (string) get_query_var( 'pixva_brand' );
	if ( '' !== $seg && rawurldecode( $seg ) !== $brand->post_name && $seg !== $brand->post_name ) {
		wp_safe_redirect( get_permalink( $post ), 301 );
		exit;
	}
}
add_action( 'template_redirect', 'pixva_model_canonical_guard', 5 );

/*
 * ---------------------------------------------------------------------------
 * Field schemas
 * ---------------------------------------------------------------------------
 */

/**
 * Severity scale for error codes.
 *
 * @return array<string,string>
 */
function pixva_severity_levels() {
	return array(
		'info'     => __( 'اطلاع‌رسانی', 'pixva' ),
		'low'      => __( 'کم', 'pixva' ),
		'medium'   => __( 'متوسط', 'pixva' ),
		'high'     => __( 'زیاد', 'pixva' ),
		'critical' => __( 'بحرانی — دستگاه را خاموش و از برق جدا کنید', 'pixva' ),
	);
}

/**
 * Panel technology options for models.
 *
 * @return array<string,string>
 */
function pixva_panel_types() {
	return array(
		'led'    => 'LED / LCD',
		'qled'   => 'QLED',
		'oled'   => 'OLED',
		'mini'   => 'Mini LED',
		'plasma' => __( 'پلاسما', 'pixva' ),
		'other'  => __( 'سایر', 'pixva' ),
	);
}

/**
 * FAQ topics.
 *
 * @return array<string,string>
 */
function pixva_faq_topics() {
	return array(
		'general'  => __( 'عمومی', 'pixva' ),
		'booking'  => __( 'ثبت درخواست', 'pixva' ),
		'pricing'  => __( 'هزینه', 'pixva' ),
		'tracking' => __( 'پیگیری', 'pixva' ),
		'warranty' => __( 'گارانتی', 'pixva' ),
	);
}

/**
 * Declare field schemas for public content types.
 *
 * @param array $schema Schema.
 * @return array
 */
function pixva_content_meta_schema( $schema ) {
	$schema['tv_services']  = array(
		'service' => array(
			'title'  => __( 'جزئیات خدمت', 'pixva' ),
			'intro'  => __( 'فقط اطلاعات واقعی و قابل ارائه را وارد کنید. فیلدهای خالی در سایت نمایش داده نمی‌شوند.', 'pixva' ),
			'fields' => array(
				'_pixva_service_symptoms' => array(
					'type'     => 'lines',
					'label'    => __( 'نشانه‌هایی که این خدمت را لازم می‌کند (هر خط یک مورد)', 'pixva' ),
					'public'   => true,
					'required' => true,
				),
				'_pixva_service_process'  => array(
					'type'   => 'lines',
					'label'  => __( 'مراحل انجام کار (هر خط یک مرحله)', 'pixva' ),
					'public' => true,
				),
				'_pixva_service_time'     => array(
					'type'   => 'text',
					'label'  => __( 'زمان معمول انجام', 'pixva' ),
					'help'   => __( 'فقط اگر برآورد واقعی دارید؛ مثل «پس از کارشناسی اعلام می‌شود».', 'pixva' ),
					'public' => true,
				),
				'_pixva_service_pricing'  => array(
					'type'    => 'select',
					'label'   => __( 'ردیف قیمت در ماشین‌حساب', 'pixva' ),
					'options' => function_exists( 'pixva_pricing_service_options' ) ? pixva_pricing_service_options() : array(),
					'help'    => __( 'برای نمایش بازه قیمت تنظیم‌شده در «پیکسوا ← قیمت‌گذاری».', 'pixva' ),
				),
				'_pixva_service_faq'      => array(
					'type'   => 'lines',
					'label'  => __( 'پرسش و پاسخ (هر خط: پرسش | پاسخ)', 'pixva' ),
					'public' => true,
				),
			),
		),
	);
	$schema['tv_brands']    = array(
		'brand' => array(
			'title'  => __( 'جزئیات برند', 'pixva' ),
			'fields' => array(
				'_pixva_brand_en'   => array(
					'type'   => 'text',
					'label'  => __( 'نام لاتین', 'pixva' ),
					'public' => true,
				),
				'_pixva_brand_logo' => array(
					'type'  => 'image',
					'label' => __( 'لوگو', 'pixva' ),
					'help'  => __( 'فقط اگر حق استفاده دارید.', 'pixva' ),
				),
			),
		),
	);
	$schema['tv_model']     = array(
		'model' => array(
			'title'  => __( 'مشخصات مدل', 'pixva' ),
			'fields' => array(
				'_pixva_brand_id'     => array(
					'type'      => 'post',
					'post_type' => 'tv_brands',
					'label'     => __( 'برند', 'pixva' ),
					'required'  => true,
					'help'      => __( 'بدون برند، صفحه مدل منتشر نمی‌شود (۴۰۴).', 'pixva' ),
					'public'    => true,
				),
				'_pixva_model_code'   => array(
					'type'     => 'text',
					'label'    => __( 'کد مدل دقیق', 'pixva' ),
					'required' => true,
					'public'   => true,
				),
				'_pixva_model_size'   => array(
					'type'   => 'int',
					'label'  => __( 'اندازه (اینچ)', 'pixva' ),
					'public' => true,
				),
				'_pixva_model_panel'  => array(
					'type'    => 'select',
					'label'   => __( 'فناوری پنل', 'pixva' ),
					'options' => pixva_panel_types(),
					'public'  => true,
				),
				'_pixva_model_year'   => array(
					'type'   => 'int',
					'label'  => __( 'سال عرضه', 'pixva' ),
					'public' => true,
				),
				'_pixva_model_issues' => array(
					'type'   => 'lines',
					'label'  => __( 'ایرادهای مستند این مدل (هر خط یک مورد)', 'pixva' ),
					'help'   => __( 'فقط مواردی که در کارگاه دیده یا از منبع معتبر تأیید شده است.', 'pixva' ),
					'public' => true,
				),
			),
		),
	);
	$schema['pixva_error']  = array(
		'error' => array(
			'title'  => __( 'اطلاعات کد خطا (§۰۹)', 'pixva' ),
			'intro'  => __( 'در «بررسی‌های ایمن» فقط کارهایی بنویسید که بدون باز کردن دستگاه انجام می‌شود. هرگز دستور کار با برق یا باز کردن قاب ننویسید.', 'pixva' ),
			'fields' => array(
				'_pixva_err_code'     => array(
					'type'     => 'text',
					'label'    => __( 'کد یا الگوی خطا', 'pixva' ),
					'help'     => __( 'مثلاً «۵ بار چشمک» یا «E-203».', 'pixva' ),
					'required' => true,
					'public'   => true,
				),
				'_pixva_err_brand_id' => array(
					'type'      => 'post',
					'post_type' => 'tv_brands',
					'label'     => __( 'برند', 'pixva' ),
					'required'  => true,
					'public'    => true,
				),
				'_pixva_err_models'   => array(
					'type'      => 'posts',
					'post_type' => 'tv_model',
					'label'     => __( 'مدل‌های مرتبط', 'pixva' ),
					'public'    => true,
				),
				'_pixva_err_meaning'  => array(
					'type'     => 'textarea',
					'label'    => __( 'معنی', 'pixva' ),
					'required' => true,
					'public'   => true,
				),
				'_pixva_err_symptoms' => array(
					'type'   => 'lines',
					'label'  => __( 'نشانه‌ها', 'pixva' ),
					'public' => true,
				),
				'_pixva_err_causes'   => array(
					'type'   => 'lines',
					'label'  => __( 'علت‌های محتمل', 'pixva' ),
					'public' => true,
				),
				'_pixva_err_severity' => array(
					'type'     => 'select',
					'label'    => __( 'شدت', 'pixva' ),
					'options'  => pixva_severity_levels(),
					'required' => true,
					'public'   => true,
				),
				'_pixva_err_safe'     => array(
					'type'   => 'lines',
					'label'  => __( 'بررسی‌های ایمن کاربر', 'pixva' ),
					'public' => true,
				),
				'_pixva_err_pro'      => array(
					'type'   => 'textarea',
					'label'  => __( 'اقدام تخصصی (کار تکنسین)', 'pixva' ),
					'public' => true,
				),
				'_pixva_err_service'  => array(
					'type'      => 'post',
					'post_type' => 'tv_services',
					'label'     => __( 'خدمت مرتبط', 'pixva' ),
					'public'    => true,
				),
				'_pixva_err_article'  => array(
					'type'      => 'post',
					'post_type' => 'post',
					'label'     => __( 'مقاله مرتبط', 'pixva' ),
					'public'    => true,
				),
			),
		),
	);
	$schema['repair_cases'] = array(
		'case' => array(
			'title'  => __( 'مستندات نمونه‌کار', 'pixva' ),
			'intro'  => __( 'فقط کار واقعی انجام‌شده با تصاویر خودتان. از تصاویر استوک یا ساختگی استفاده نکنید.', 'pixva' ),
			'fields' => array(
				'_pixva_case_before'   => array(
					'type'  => 'image',
					'label' => __( 'تصویر قبل از تعمیر', 'pixva' ),
				),
				'_pixva_case_after'    => array(
					'type'  => 'image',
					'label' => __( 'تصویر بعد از تعمیر', 'pixva' ),
				),
				'_pixva_case_brand_id' => array(
					'type'      => 'post',
					'post_type' => 'tv_brands',
					'label'     => __( 'برند', 'pixva' ),
					'public'    => true,
				),
				'_pixva_case_model'    => array(
					'type'   => 'text',
					'label'  => __( 'مدل', 'pixva' ),
					'public' => true,
				),
				'_pixva_case_service'  => array(
					'type'      => 'post',
					'post_type' => 'tv_services',
					'label'     => __( 'خدمت انجام‌شده', 'pixva' ),
					'public'    => true,
				),
				'_pixva_case_parts'    => array(
					'type'   => 'text',
					'label'  => __( 'قطعات استفاده‌شده', 'pixva' ),
					'public' => true,
				),
				'_pixva_case_duration' => array(
					'type'   => 'text',
					'label'  => __( 'مدت واقعی انجام کار', 'pixva' ),
					'public' => true,
				),
			),
		),
	);
	$schema['post']         = array(
		'article' => array(
			'title'   => __( 'اطلاعات تکمیلی مقاله', 'pixva' ),
			'context' => 'normal',
			'fields'  => array(
				'_pixva_post_brand'      => array(
					'type'      => 'post',
					'post_type' => 'tv_brands',
					'label'     => __( 'برند مرتبط', 'pixva' ),
				),
				'_pixva_post_service'    => array(
					'type'      => 'post',
					'post_type' => 'tv_services',
					'label'     => __( 'خدمت مرتبط', 'pixva' ),
				),
				'_pixva_post_difficulty' => array(
					'type'    => 'select',
					'label'   => __( 'سطح', 'pixva' ),
					'options' => array(
						'easy'   => __( 'مقدماتی', 'pixva' ),
						'medium' => __( 'متوسط', 'pixva' ),
						'hard'   => __( 'تخصصی', 'pixva' ),
					),
				),
				'_pixva_post_faq'        => array(
					'type'  => 'lines',
					'label' => __( 'پرسش و پاسخ (هر خط: پرسش | پاسخ)', 'pixva' ),
				),
				'_pixva_reviewed_by'     => array(
					'type'  => 'text',
					'label' => __( 'بازبینی فنی توسط', 'pixva' ),
					'help'  => __( 'نام واقعی فرد بازبین؛ خالی = نمایش داده نمی‌شود.', 'pixva' ),
				),
			),
		),
	);
	$schema['pixva_faq']    = array(
		'faq' => array(
			'title'   => __( 'دسته پرسش', 'pixva' ),
			'context' => 'side',
			'fields'  => array(
				'_pixva_faq_topic' => array(
					'type'    => 'select',
					'label'   => __( 'موضوع', 'pixva' ),
					'options' => pixva_faq_topics(),
				),
			),
		),
	);
	$schema['pixva_part']   = array(
		'part' => array(
			'title'  => __( 'مشخصات قطعه', 'pixva' ),
			'fields' => array(
				'_pixva_part_number' => array(
					'type'  => 'text',
					'label' => __( 'شماره فنی', 'pixva' ),
				),
				'_pixva_part_fits'   => array(
					'type'  => 'textarea',
					'label' => __( 'مدل‌های سازگار', 'pixva' ),
				),
			),
		),
	);
	return $schema;
}
add_filter( 'pixva_meta_schema', 'pixva_content_meta_schema' );

/**
 * "question | answer" lines → pairs.
 *
 * @param int    $post_id Post.
 * @param string $key     Meta key.
 * @return array<int,array{q:string,a:string}>
 */
function pixva_meta_faq_pairs( $post_id, $key ) {
	$out = array();
	foreach ( pixva_meta_lines( $post_id, $key ) as $line ) {
		$parts = array_map( 'trim', explode( '|', $line, 2 ) );
		if ( 2 === count( $parts ) && '' !== $parts[0] && '' !== $parts[1] ) {
			$out[] = array(
				'q' => $parts[0],
				'a' => $parts[1],
			);
		}
	}
	return $out;
}

/*
 * ---------------------------------------------------------------------------
 * Problem term meta
 * ---------------------------------------------------------------------------
 */

/**
 * Problem term fields.
 *
 * @return array<string,array>
 */
function pixva_problem_term_fields() {
	return array(
		'_pixva_problem_symptoms' => array( 'lines', __( 'نشانه‌ها (هر خط یک مورد)', 'pixva' ) ),
		'_pixva_problem_safe'     => array( 'lines', __( 'بررسی‌های ایمن کاربر (هر خط یک مورد، بدون باز کردن دستگاه)', 'pixva' ) ),
		'_pixva_problem_service'  => array( 'post', __( 'خدمت اصلی مرتبط', 'pixva' ) ),
		'_pixva_problem_diag'     => array( 'select', __( 'کلید عیب‌یابی در ابزار تشخیص', 'pixva' ) ),
	);
}

/**
 * Render term fields on add/edit screens.
 *
 * @param WP_Term|string $term Term (edit) or taxonomy (add).
 * @return void
 */
function pixva_problem_term_form( $term ) {
	$editing = $term instanceof WP_Term;
	wp_nonce_field( 'pixva_problem_term', 'pixva_problem_nonce' );
	foreach ( pixva_problem_term_fields() as $key => $def ) {
		$value = $editing ? get_term_meta( $term->term_id, $key, true ) : '';
		$id    = 'pixva-' . sanitize_html_class( $key );
		ob_start();
		if ( 'lines' === $def[0] ) {
			echo '<textarea id="' . esc_attr( $id ) . '" name="' . esc_attr( $key ) . '" rows="4" class="large-text">' . esc_textarea( (string) $value ) . '</textarea>';
		} elseif ( 'post' === $def[0] ) {
			echo '<select id="' . esc_attr( $id ) . '" name="' . esc_attr( $key ) . '"><option value="0">' . esc_html__( '— هیچ —', 'pixva' ) . '</option>';
			foreach ( pixva_meta_post_choices( 'tv_services' ) as $pid => $title ) {
				echo '<option value="' . esc_attr( $pid ) . '" ' . selected( (int) $value, $pid, false ) . '>' . esc_html( $title ) . '</option>';
			}
			echo '</select>';
		} else {
			echo '<select id="' . esc_attr( $id ) . '" name="' . esc_attr( $key ) . '"><option value="">' . esc_html__( '— هیچ —', 'pixva' ) . '</option>';
			foreach ( pixva_diagnosis_problems() as $pkey => $p ) {
				echo '<option value="' . esc_attr( $pkey ) . '" ' . selected( (string) $value, $pkey, false ) . '>' . esc_html( $p['label'] ) . '</option>';
			}
			echo '</select>';
		}
		$field = ob_get_clean();
		if ( $editing ) {
			echo '<tr class="form-field"><th scope="row"><label for="' . esc_attr( $id ) . '">' . esc_html( $def[1] ) . '</label></th><td>' . $field . '</td></tr>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built from escaped parts.
		} else {
			echo '<div class="form-field"><label for="' . esc_attr( $id ) . '">' . esc_html( $def[1] ) . '</label>' . $field . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
	}
}
add_action( 'tv_problem_add_form_fields', 'pixva_problem_term_form' );
add_action( 'tv_problem_edit_form_fields', 'pixva_problem_term_form' );

/**
 * Save term fields.
 *
 * @param int $term_id Term id.
 * @return void
 */
function pixva_problem_term_save( $term_id ) {
	if ( ! isset( $_POST['pixva_problem_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['pixva_problem_nonce'] ) ), 'pixva_problem_term' ) ) {
		return;
	}
	if ( ! current_user_can( 'edit_term', $term_id ) ) {
		return;
	}
	foreach ( pixva_problem_term_fields() as $key => $def ) {
		$raw = isset( $_POST[ $key ] ) ? wp_unslash( $_POST[ $key ] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized below.
		if ( 'lines' === $def[0] ) {
			$val = sanitize_textarea_field( (string) $raw );
		} elseif ( 'post' === $def[0] ) {
			$val = absint( $raw );
		} else {
			$val = sanitize_key( (string) $raw );
			$val = array_key_exists( $val, pixva_diagnosis_problems() ) ? $val : '';
		}
		if ( empty( $val ) ) {
			delete_term_meta( $term_id, $key );
		} else {
			update_term_meta( $term_id, $key, $val );
		}
	}
}
add_action( 'created_tv_problem', 'pixva_problem_term_save' );
add_action( 'edited_tv_problem', 'pixva_problem_term_save' );

/**
 * Lines from a term meta field.
 *
 * @param int    $term_id Term id.
 * @param string $key     Key.
 * @return string[]
 */
function pixva_term_lines( $term_id, $key ) {
	return array_values( array_filter( array_map( 'trim', preg_split( '/\r?\n/', (string) get_term_meta( (int) $term_id, $key, true ) ) ) ) );
}

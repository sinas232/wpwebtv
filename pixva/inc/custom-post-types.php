<?php
/**
 * ثبت پست‌تایپ‌ها، تاکسونومی‌ها و متافیلدهای اختصاصی قالب پیکسوا
 *
 * - tv_services : خدمات تخصصی تعمیر
 * - tv_brands   : برندهای تلویزیون
 * - repair_cases: نمونه‌کارهای واقعی (قبل/بعد)
 * - pixva_orders: پرونده‌های تعمیر (سامانه پیگیری — داخلی، برون‌ریزی نمی‌شود)
 * - pixva_inbox : پیام‌های فرم تماس (داخلی)
 * - tv_problem  : تاکسونومی نوع خرابی
 * - tv_tech     : تاکسونومی تکنولوژی صفحه
 *
 * @package Pixva
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
 * ---------------------------------------------------------------------------
 * ۱) پست‌تایپ‌ها
 * ---------------------------------------------------------------------------
 */

/**
 * ثبت همه پست‌تایپ‌ها و تاکسونومی‌ها.
 *
 * @return void
 */
function pixva_register_content_types() {
	// خدمات تخصصی.
	register_post_type(
		'tv_services',
		array(
			'labels'        => array(
				'name'          => esc_html__( 'خدمات تخصصی', 'pixva' ),
				'singular_name' => esc_html__( 'خدمت', 'pixva' ),
				'add_new_item'  => esc_html__( 'افزودن خدمت جدید', 'pixva' ),
				'edit_item'     => esc_html__( 'ویرایش خدمت', 'pixva' ),
				'menu_name'     => esc_html__( 'خدمات تعمیر', 'pixva' ),
			),
			'public'        => true,
			'menu_icon'     => 'dashicons-desktop',
			'menu_position' => 21,
			'supports'      => array( 'title', 'editor', 'thumbnail', 'excerpt', 'revisions' ),
			'has_archive'   => true,
			'rewrite'       => array(
				'slug'       => 'services',
				'with_front' => false,
			),
			'show_in_rest'  => true,
		)
	);

	// برندها.
	register_post_type(
		'tv_brands',
		array(
			'labels'        => array(
				'name'          => esc_html__( 'برندهای تلویزیون', 'pixva' ),
				'singular_name' => esc_html__( 'برند', 'pixva' ),
				'add_new_item'  => esc_html__( 'افزودن برند جدید', 'pixva' ),
				'edit_item'     => esc_html__( 'ویرایش برند', 'pixva' ),
				'menu_name'     => esc_html__( 'برندها', 'pixva' ),
			),
			'public'        => true,
			'menu_icon'     => 'dashicons-awards',
			'menu_position' => 22,
			'supports'      => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
			'has_archive'   => true,
			'rewrite'       => array(
				'slug'       => 'brands',
				'with_front' => false,
			),
			'show_in_rest'  => true,
		)
	);

	// نمونه‌کارها.
	register_post_type(
		'repair_cases',
		array(
			'labels'        => array(
				'name'          => esc_html__( 'نمونه‌کارهای تعمیر', 'pixva' ),
				'singular_name' => esc_html__( 'نمونه‌کار', 'pixva' ),
				'add_new_item'  => esc_html__( 'افزودن نمونه‌کار', 'pixva' ),
				'edit_item'     => esc_html__( 'ویرایش نمونه‌کار', 'pixva' ),
				'menu_name'     => esc_html__( 'نمونه‌کارها', 'pixva' ),
			),
			'public'        => true,
			'menu_icon'     => 'dashicons-format-gallery',
			'menu_position' => 23,
			'supports'      => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
			'has_archive'   => true,
			'rewrite'       => array(
				'slug'       => 'cases',
				'with_front' => false,
			),
			'show_in_rest'  => true,
		)
	);

	// پرونده‌های تعمیر (سامانه پیگیری) — داخلی و بدون نمای عمومی.
	register_post_type(
		'pixva_orders',
		array(
			'labels'              => array(
				'name'          => esc_html__( 'پرونده‌های تعمیر', 'pixva' ),
				'singular_name' => esc_html__( 'پرونده تعمیر', 'pixva' ),
				'add_new_item'  => esc_html__( 'ثبت پرونده جدید', 'pixva' ),
				'edit_item'     => esc_html__( 'ویرایش پرونده', 'pixva' ),
				'menu_name'     => esc_html__( 'پیگیری تعمیرات', 'pixva' ),
			),
			'public'              => false,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'menu_icon'           => 'dashicons-clipboard',
			'menu_position'       => 24,
			'supports'            => array( 'title' ),
			'exclude_from_search' => true,
			'publicly_queryable'  => false,
			'show_in_rest'        => false,
			'capability_type'     => 'post',
		)
	);

	// صندوق پیام‌های فرم تماس — داخلی.
	register_post_type(
		'pixva_inbox',
		array(
			'labels'              => array(
				'name'          => esc_html__( 'پیام‌های تماس', 'pixva' ),
				'singular_name' => esc_html__( 'پیام', 'pixva' ),
				'menu_name'     => esc_html__( 'صندوق تماس', 'pixva' ),
				'edit_item'     => esc_html__( 'مشاهده پیام', 'pixva' ),
			),
			'public'              => false,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'menu_icon'           => 'dashicons-email-alt',
			'menu_position'       => 25,
			'supports'            => array( 'title' ),
			'exclude_from_search' => true,
			'publicly_queryable'  => false,
			'show_in_rest'        => false,
		)
	);

	// انبار قطعات فابریک (pixva_part)
	register_post_type(
		'pixva_part',
		array(
			'labels'        => array(
				'name'          => esc_html__( 'انبار قطعات فابریک', 'pixva' ),
				'singular_name' => esc_html__( 'قطعه', 'pixva' ),
				'add_new_item'  => esc_html__( 'ثبت قطعه جدید', 'pixva' ),
				'edit_item'     => esc_html__( 'ویرایش قطعه', 'pixva' ),
				'menu_name'     => esc_html__( 'انبار قطعات', 'pixva' ),
			),
			'public'        => true,
			'menu_icon'     => 'dashicons-hammer',
			'menu_position' => 26,
			'supports'      => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
			'has_archive'   => true,
			'rewrite'       => array( 'slug' => 'parts', 'with_front' => false ),
			'show_in_rest'  => true,
		)
	);

	// کدهای خطا و دیاگنوستیک (pixva_error)
	register_post_type(
		'pixva_error',
		array(
			'labels'        => array(
				'name'          => esc_html__( 'پایگاه کدهای خطا', 'pixva' ),
				'singular_name' => esc_html__( 'کد خطا', 'pixva' ),
				'add_new_item'  => esc_html__( 'افزودن کد خطای جدید', 'pixva' ),
				'edit_item'     => esc_html__( 'ویرایش کد خطا', 'pixva' ),
				'menu_name'     => esc_html__( 'کدهای خطا (CPT)', 'pixva' ),
			),
			'public'        => true,
			'menu_icon'     => 'dashicons-warning',
			'menu_position' => 27,
			'supports'      => array( 'title', 'editor', 'excerpt' ),
			'has_archive'   => true,
			'rewrite'       => array( 'slug' => 'error-database', 'with_front' => false ),
			'show_in_rest'  => true,
		)
	);

	// شعب و تکنسین‌ها (pixva_branch)
	register_post_type(
		'pixva_branch',
		array(
			'labels'        => array(
				'name'          => esc_html__( 'شعب و تکنسین‌ها', 'pixva' ),
				'singular_name' => esc_html__( 'شعبه/تکنسین', 'pixva' ),
				'add_new_item'  => esc_html__( 'افزودن شعبه/تکنسین', 'pixva' ),
				'edit_item'     => esc_html__( 'ویرایش شعبه', 'pixva' ),
				'menu_name'     => esc_html__( 'شعب و تکنسین‌ها', 'pixva' ),
			),
			'public'        => true,
			'menu_icon'     => 'dashicons-location',
			'menu_position' => 28,
			'supports'      => array( 'title', 'editor', 'thumbnail' ),
			'has_archive'   => true,
			'rewrite'       => array( 'slug' => 'branches', 'with_front' => false ),
			'show_in_rest'  => true,
		)
	);

	// گزارش‌های تعمیر و دانش فنی کارگاه (pixva_repair)
	register_post_type(
		'pixva_repair',
		array(
			'labels'        => array(
				'name'          => esc_html__( 'گزارش‌های تعمیر', 'pixva' ),
				'singular_name' => esc_html__( 'گزارش تعمیر', 'pixva' ),
				'add_new_item'  => esc_html__( 'ثبت گزارش تعمیر جدید', 'pixva' ),
				'edit_item'     => esc_html__( 'ویرایش گزارش تعمیر', 'pixva' ),
				'all_items'     => esc_html__( 'همه گزارش‌ها', 'pixva' ),
				'search_items'  => esc_html__( 'جست‌وجوی گزارش تعمیر', 'pixva' ),
				'not_found'     => esc_html__( 'گزارشی یافت نشد.', 'pixva' ),
				'menu_name'     => esc_html__( 'گزارش‌های تعمیر', 'pixva' ),
			),
			'description'   => esc_html__( 'مستندسازی فنی هر پرونده تعمیر: علائم، عیب‌یابی، قطعه تعویضی، مدت تعمیر و بازه هزینه.', 'pixva' ),
			'public'        => true,
			'menu_icon'     => 'dashicons-clipboard',
			'menu_position' => 29,
			'supports'      => array( 'title', 'editor', 'excerpt', 'thumbnail', 'custom-fields', 'revisions' ),
			'has_archive'   => true,
			'rewrite'       => array( 'slug' => 'repairs', 'with_front' => false ),
			'show_in_rest'  => true,
		)
	);

	// نظرات مشتریان (pixva_review) — منبع داینامیک بخش «نظرات» و ویجت المنتور.
	register_post_type(
		'pixva_review',
		array(
			'labels'        => array(
				'name'          => esc_html__( 'نظرات مشتریان', 'pixva' ),
				'singular_name' => esc_html__( 'نظر مشتری', 'pixva' ),
				'add_new_item'  => esc_html__( 'افزودن نظر مشتری', 'pixva' ),
				'edit_item'     => esc_html__( 'ویرایش نظر', 'pixva' ),
				'all_items'     => esc_html__( 'همه نظرات', 'pixva' ),
				'menu_name'     => esc_html__( 'نظرات مشتریان', 'pixva' ),
			),
			'description'   => esc_html__( 'روایت واقعی مشتریان: عنوان = نام مشتری، محتوا = متن نظر، خلاصه = خدمت/شهر.', 'pixva' ),
			'public'        => false,
			'show_ui'       => true,
			'menu_icon'     => 'dashicons-star-filled',
			'menu_position' => 30,
			'supports'      => array( 'title', 'editor', 'excerpt', 'thumbnail', 'page-attributes' ),
			'show_in_rest'  => true,
		)
	);

	// تاکسونومی نوع خرابی.
	register_taxonomy(
		'tv_problem',
		array( 'post', 'tv_services', 'repair_cases', 'pixva_repair' ),
		array(
			'labels'            => array(
				'name'          => esc_html__( 'انواع خرابی', 'pixva' ),
				'singular_name' => esc_html__( 'نوع خرابی', 'pixva' ),
				'menu_name'     => esc_html__( 'خرابی‌ها', 'pixva' ),
			),
			'public'            => true,
			'hierarchical'      => true,
			'show_in_rest'      => true,
			'rewrite'           => array(
				'slug'       => 'problem',
				'with_front' => false,
			),
			'show_admin_column' => true,
		)
	);

	// تاکسونومی تکنولوژی صفحه.
	register_taxonomy(
		'tv_tech',
		array( 'post', 'tv_services', 'tv_brands', 'repair_cases', 'pixva_repair' ),
		array(
			'labels'            => array(
				'name'          => esc_html__( 'تکنولوژی صفحه', 'pixva' ),
				'singular_name' => esc_html__( 'تکنولوژی', 'pixva' ),
				'menu_name'     => esc_html__( 'تکنولوژی‌ها', 'pixva' ),
			),
			'public'            => true,
			'hierarchical'      => true,
			'show_in_rest'      => true,
			'rewrite'           => array(
				'slug'       => 'tech',
				'with_front' => false,
			),
			'show_admin_column' => true,
		)
	);
}
add_action( 'init', 'pixva_register_content_types' );

/*
 * ---------------------------------------------------------------------------
 * ۲) متافیلدها (ثبت رسمی با register_post_meta)
 * ---------------------------------------------------------------------------
 */

/**
 * ثبت متافیلدها.
 *
 * @return void
 */
function pixva_register_meta() {
	// نمونه‌کارها.
	$case_meta = array(
		'_pixva_case_before'   => array(
			'type'  => 'integer',
			'label' => esc_html__( 'شناسه تصویر قبل از تعمیر', 'pixva' ),
		),
		'_pixva_case_after'    => array(
			'type'  => 'integer',
			'label' => esc_html__( 'شناسه تصویر بعد از تعمیر', 'pixva' ),
		),
		'_pixva_case_parts'    => array(
			'type'  => 'string',
			'label' => esc_html__( 'قطعات تعویض‌شده', 'pixva' ),
		),
		'_pixva_case_model'    => array(
			'type'  => 'string',
			'label' => esc_html__( 'مدل دستگاه', 'pixva' ),
		),
		'_pixva_case_duration' => array(
			'type'  => 'string',
			'label' => esc_html__( 'زمان صرف‌شده', 'pixva' ),
		),
	);
	$review_meta = array(
		'_pixva_review_role'     => array(
			'type'  => 'string',
			'label' => esc_html__( 'خدمت یا شهر', 'pixva' ),
		),
		'_pixva_review_rating'   => array(
			'type'  => 'integer',
			'label' => esc_html__( 'امتیاز از ۵', 'pixva' ),
		),
		'_pixva_review_featured' => array(
			'type'  => 'integer',
			'label' => esc_html__( 'نمایش در صفحه اصلی', 'pixva' ),
		),
	);
	foreach ( $review_meta as $key => $args ) {
		register_post_meta(
			'pixva_review',
			$key,
			array(
				'type'              => $args['type'],
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => 'integer' === $args['type'] ? 'absint' : 'sanitize_text_field',
				'auth_callback'     => static function () {
					return current_user_can( 'edit_posts' );
				},
			)
		);
	}

	foreach ( $case_meta as $key => $args ) {
		register_post_meta(
			'repair_cases',
			$key,
			array(
				'type'              => $args['type'],
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => 'integer' === $args['type'] ? 'absint' : 'sanitize_text_field',
				'auth_callback'     => static function () {
					return current_user_can( 'edit_posts' );
				},
			)
		);
	}

	// پرونده‌های تعمیر.
	$order_meta = array(
		'_pixva_order_code'     => 'string',
		'_pixva_order_phone'    => 'string',
		'_pixva_order_status'   => 'string',
		'_pixva_order_brand'    => 'string',
		'_pixva_order_model'    => 'string',
		'_pixva_order_problem'  => 'string',
		'_pixva_order_estimate' => 'string',
		'_pixva_order_steps'    => 'string', // JSON زمان‌بندی مراحل.
		'_pixva_order_notes'    => 'string',
	);
	foreach ( $order_meta as $key => $type ) {
		register_post_meta(
			'pixva_orders',
			$key,
			array(
				'type'              => $type,
				'single'            => true,
				'show_in_rest'      => false,
				'sanitize_callback' => 'sanitize_text_field',
				'auth_callback'     => static function () {
					return current_user_can( 'manage_options' );
				},
			)
		);
	}

	// متافیلدهای مقاله (باکس مشخصات عیب‌یابی و سوالات متداول).
	register_post_meta(
		'post',
		'_pixva_post_difficulty',
		array(
			'type'              => 'string',
			'single'            => true,
			'show_in_rest'      => true,
			'sanitize_callback' => 'sanitize_text_field',
			'auth_callback'     => static function () {
				return current_user_can( 'edit_posts' );
			},
		)
	);
	register_post_meta(
		'post',
		'_pixva_post_tools',
		array(
			'type'              => 'string',
			'single'            => true,
			'show_in_rest'      => true,
			'sanitize_callback' => 'sanitize_text_field',
			'auth_callback'     => static function () {
				return current_user_can( 'edit_posts' );
			},
		)
	);
	register_post_meta(
		'post',
		'_pixva_post_brand',
		array(
			'type'              => 'string',
			'single'            => true,
			'show_in_rest'      => true,
			'sanitize_callback' => 'sanitize_text_field',
			'auth_callback'     => static function () {
				return current_user_can( 'edit_posts' );
			},
		)
	);
	register_post_meta(
		'post',
		'_pixva_post_faq',
		array(
			'type'              => 'string',
			'single'            => true,
			'show_in_rest'      => true,
			'sanitize_callback' => 'sanitize_textarea_field',
			'auth_callback'     => static function () {
				return current_user_can( 'edit_posts' );
			},
		)
	);
}
add_action( 'init', 'pixva_register_meta' );

/*
 * ---------------------------------------------------------------------------
 * ۳) جعبه‌های متا (Meta Box) پیشخوان
 * ---------------------------------------------------------------------------
 */

/**
 * ثبت متاباکس‌ها.
 *
 * @return void
 */
function pixva_add_meta_boxes() {
	add_meta_box( 'pixva_case_meta', esc_html__( 'مشخصات نمونه‌کار', 'pixva' ), 'pixva_render_case_meta_box', 'repair_cases', 'normal', 'high' );
	add_meta_box( 'pixva_order_meta', esc_html__( 'وضعیت پرونده تعمیر', 'pixva' ), 'pixva_render_order_meta_box', 'pixva_orders', 'normal', 'high' );
	add_meta_box( 'pixva_post_meta', esc_html__( 'مشخصات عیب‌یابی و سوالات متداول', 'pixva' ), 'pixva_render_post_meta_box', 'post', 'normal', 'high' );
	add_meta_box( 'pixva_inbox_meta', esc_html__( 'متن پیام', 'pixva' ), 'pixva_render_inbox_meta_box', 'pixva_inbox', 'normal', 'high' );
	add_meta_box( 'pixva_review_meta', esc_html__( 'مشخصات نظر مشتری', 'pixva' ), 'pixva_render_review_meta_box', 'pixva_review', 'normal', 'high' );
}
add_action( 'add_meta_boxes', 'pixva_add_meta_boxes' );

/**
 * متاباکس نمونه‌کار.
 *
 * @param WP_Post $post پرونده جاری.
 * @return void
 */
function pixva_render_case_meta_box( $post ) {
	wp_nonce_field( 'pixva_case_meta', 'pixva_case_nonce' );
	$before   = (int) get_post_meta( $post->ID, '_pixva_case_before', true );
	$after    = (int) get_post_meta( $post->ID, '_pixva_case_after', true );
	$parts    = (string) get_post_meta( $post->ID, '_pixva_case_parts', true );
	$model    = (string) get_post_meta( $post->ID, '_pixva_case_model', true );
	$duration = (string) get_post_meta( $post->ID, '_pixva_case_duration', true );
	?>
	<p>
		<label for="pixva_case_before"><?php esc_html_e( 'شناسه تصویر قبل از تعمیر (پیوست):', 'pixva' ); ?></label>
		<input type="number" min="0" id="pixva_case_before" name="pixva_case_before" value="<?php echo esc_attr( $before ); ?>" class="regular-text">
	</p>
	<p>
		<label for="pixva_case_after"><?php esc_html_e( 'شناسه تصویر بعد از تعمیر (پیوست):', 'pixva' ); ?></label>
		<input type="number" min="0" id="pixva_case_after" name="pixva_case_after" value="<?php echo esc_attr( $after ); ?>" class="regular-text">
	</p>
	<p>
		<label for="pixva_case_parts"><?php esc_html_e( 'قطعات تعویض‌شده (با ویرگول جدا کنید):', 'pixva' ); ?></label>
		<input type="text" id="pixva_case_parts" name="pixva_case_parts" value="<?php echo esc_attr( $parts ); ?>" class="widefat">
	</p>
	<p>
		<label for="pixva_case_model"><?php esc_html_e( 'مدل دستگاه:', 'pixva' ); ?></label>
		<input type="text" id="pixva_case_model" name="pixva_case_model" value="<?php echo esc_attr( $model ); ?>" class="widefat">
	</p>
	<p>
		<label for="pixva_case_duration"><?php esc_html_e( 'زمان صرف‌شده (مثلاً ۳ ساعت کاری):', 'pixva' ); ?></label>
		<input type="text" id="pixva_case_duration" name="pixva_case_duration" value="<?php echo esc_attr( $duration ); ?>" class="widefat">
	</p>
	<?php
}

/**
 * متاباکس نظر مشتری.
 *
 * @param WP_Post $post پرونده جاری.
 * @return void
 */
function pixva_render_review_meta_box( $post ) {
	wp_nonce_field( 'pixva_review_meta', 'pixva_review_nonce' );
	$role     = (string) get_post_meta( $post->ID, '_pixva_review_role', true );
	$rating   = (int) get_post_meta( $post->ID, '_pixva_review_rating', true );
	$featured = (int) get_post_meta( $post->ID, '_pixva_review_featured', true );
	?>
	<p>
		<label for="pixva_review_role"><?php esc_html_e( 'خدمت یا شهر (زیر نام مشتری):', 'pixva' ); ?></label>
		<input type="text" id="pixva_review_role" name="pixva_review_role" value="<?php echo esc_attr( $role ); ?>" class="widefat">
	</p>
	<p>
		<label for="pixva_review_rating"><?php esc_html_e( 'امتیاز (۱ تا ۵):', 'pixva' ); ?></label>
		<input type="number" min="1" max="5" step="1" id="pixva_review_rating" name="pixva_review_rating" value="<?php echo esc_attr( $rating ? $rating : 5 ); ?>" class="small-text">
	</p>
	<p>
		<label for="pixva_review_featured">
			<input type="checkbox" id="pixva_review_featured" name="pixva_review_featured" value="1" <?php checked( 1, $featured ); ?>>
			<?php esc_html_e( 'در صفحه اصلی نمایش داده شود', 'pixva' ); ?>
		</label>
	</p>
	<?php
}

/**
 * ذخیره متافیلدهای نظر مشتری.
 *
 * @param int $post_id شناسه پرونده.
 * @return void
 */
function pixva_save_review_meta( $post_id ) {
	if ( ! isset( $_POST['pixva_review_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['pixva_review_nonce'] ) ), 'pixva_review_meta' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	update_post_meta(
		$post_id,
		'_pixva_review_role',
		isset( $_POST['pixva_review_role'] ) ? sanitize_text_field( wp_unslash( $_POST['pixva_review_role'] ) ) : ''
	);
	update_post_meta(
		$post_id,
		'_pixva_review_rating',
		isset( $_POST['pixva_review_rating'] ) ? min( 5, max( 1, absint( wp_unslash( $_POST['pixva_review_rating'] ) ) ) ) : 5
	);
	update_post_meta(
		$post_id,
		'_pixva_review_featured',
		empty( $_POST['pixva_review_featured'] ) ? 0 : 1
	);
}
add_action( 'save_post_pixva_review', 'pixva_save_review_meta' );

/**
 * گزینه‌های وضعیت پرونده تعمیر (تایم‌لاین شش‌مرحله‌ای).
 *
 * @return array<string, string>
 */
function pixva_order_statuses() {
	return array(
		'received'  => esc_html__( 'دریافت دستگاه', 'pixva' ),
		'diagnosed' => esc_html__( 'عیب‌یابی اولیه', 'pixva' ),
		'parts'     => esc_html__( 'تامین قطعه', 'pixva' ),
		'repairing' => esc_html__( 'در حال تعمیر', 'pixva' ),
		'testing'   => esc_html__( 'تست نهایی', 'pixva' ),
		'ready'     => esc_html__( 'آماده تحویل', 'pixva' ),
	);
}

/**
 * متاباکس پرونده تعمیر.
 *
 * @param WP_Post $post پرونده جاری.
 * @return void
 */
function pixva_render_order_meta_box( $post ) {
	wp_nonce_field( 'pixva_order_meta', 'pixva_order_nonce' );
	$statuses = pixva_order_statuses();
	$status   = (string) get_post_meta( $post->ID, '_pixva_order_status', true );
	$phone    = (string) get_post_meta( $post->ID, '_pixva_order_phone', true );
	$code     = (string) get_post_meta( $post->ID, '_pixva_order_code', true );
	$brand    = (string) get_post_meta( $post->ID, '_pixva_order_brand', true );
	$model    = (string) get_post_meta( $post->ID, '_pixva_order_model', true );
	$problem  = (string) get_post_meta( $post->ID, '_pixva_order_problem', true );
	$estimate = (string) get_post_meta( $post->ID, '_pixva_order_estimate', true );
	$notes    = (string) get_post_meta( $post->ID, '_pixva_order_notes', true );
	?>
	<p>
		<label for="pixva_order_code"><?php esc_html_e( 'کد پیگیری:', 'pixva' ); ?></label>
		<input type="text" id="pixva_order_code" name="pixva_order_code" value="<?php echo esc_attr( $code ); ?>" class="regular-text" readonly>
	</p>
	<p>
		<label for="pixva_order_phone"><?php esc_html_e( 'شماره همراه مشتری:', 'pixva' ); ?></label>
		<input type="text" id="pixva_order_phone" name="pixva_order_phone" value="<?php echo esc_attr( $phone ); ?>" class="regular-text">
	</p>
	<p>
		<label for="pixva_order_status"><?php esc_html_e( 'وضعیت فعلی:', 'pixva' ); ?></label>
		<select id="pixva_order_status" name="pixva_order_status">
			<?php foreach ( $statuses as $key => $label ) : ?>
				<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $status, $key ); ?>><?php echo esc_html( $label ); ?></option>
			<?php endforeach; ?>
		</select>
	</p>
	<p>
		<label for="pixva_order_brand"><?php esc_html_e( 'برند:', 'pixva' ); ?></label>
		<input type="text" id="pixva_order_brand" name="pixva_order_brand" value="<?php echo esc_attr( $brand ); ?>" class="regular-text">
	</p>
	<p>
		<label for="pixva_order_model"><?php esc_html_e( 'مدل:', 'pixva' ); ?></label>
		<input type="text" id="pixva_order_model" name="pixva_order_model" value="<?php echo esc_attr( $model ); ?>" class="regular-text">
	</p>
	<p>
		<label for="pixva_order_problem"><?php esc_html_e( 'شرح مشکل:', 'pixva' ); ?></label>
		<input type="text" id="pixva_order_problem" name="pixva_order_problem" value="<?php echo esc_attr( $problem ); ?>" class="widefat">
	</p>
	<p>
		<label for="pixva_order_estimate"><?php esc_html_e( 'تخمین هزینه (تومان):', 'pixva' ); ?></label>
		<input type="text" id="pixva_order_estimate" name="pixva_order_estimate" value="<?php echo esc_attr( $estimate ); ?>" class="regular-text">
	</p>
	<p>
		<label for="pixva_order_notes"><?php esc_html_e( 'یادداشت داخلی:', 'pixva' ); ?></label>
		<textarea id="pixva_order_notes" name="pixva_order_notes" rows="3" class="widefat"><?php echo esc_textarea( $notes ); ?></textarea>
	</p>
	<?php
}

/**
 * متاباکس مقاله.
 *
 * @param WP_Post $post پرونده جاری.
 * @return void
 */
function pixva_render_post_meta_box( $post ) {
	wp_nonce_field( 'pixva_post_meta', 'pixva_post_meta_nonce' );
	$difficulty = (string) get_post_meta( $post->ID, '_pixva_post_difficulty', true );
	$tools      = (string) get_post_meta( $post->ID, '_pixva_post_tools', true );
	$brand      = (string) get_post_meta( $post->ID, '_pixva_post_brand', true );
	$faq        = (string) get_post_meta( $post->ID, '_pixva_post_faq', true );
	$levels     = array(
		'easy'   => esc_html__( 'آسان', 'pixva' ),
		'medium' => esc_html__( 'متوسط', 'pixva' ),
		'hard'   => esc_html__( 'تخصصی', 'pixva' ),
	);
	?>
	<p>
		<label for="pixva_post_difficulty"><?php esc_html_e( 'سطح سختی:', 'pixva' ); ?></label>
		<select id="pixva_post_difficulty" name="pixva_post_difficulty">
			<option value=""><?php esc_html_e( '— انتخاب نکنید —', 'pixva' ); ?></option>
			<?php foreach ( $levels as $key => $label ) : ?>
				<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $difficulty, $key ); ?>><?php echo esc_html( $label ); ?></option>
			<?php endforeach; ?>
		</select>
	</p>
	<p>
		<label for="pixva_post_brand"><?php esc_html_e( 'برند مرتبط:', 'pixva' ); ?></label>
		<input type="text" id="pixva_post_brand" name="pixva_post_brand" value="<?php echo esc_attr( $brand ); ?>" class="regular-text">
	</p>
	<p>
		<label for="pixva_post_tools"><?php esc_html_e( 'ابزار مورد نیاز (با ویرگول جدا کنید):', 'pixva' ); ?></label>
		<input type="text" id="pixva_post_tools" name="pixva_post_tools" value="<?php echo esc_attr( $tools ); ?>" class="widefat">
	</p>
	<p>
		<label for="pixva_post_faq"><?php esc_html_e( 'سوالات متداول (هر خط: سوال | پاسخ):', 'pixva' ); ?></label>
		<textarea id="pixva_post_faq" name="pixva_post_faq" rows="5" class="widefat" placeholder="<?php esc_attr_e( 'هزینه تعویض بک‌لایت چقدر است؟ | بسته به سایز پنل از ۱ تا ۲.۵ میلیون تومان.', 'pixva' ); ?>"><?php echo esc_textarea( $faq ); ?></textarea>
		<span class="description"><?php esc_html_e( 'این سوالات با اسکیما FAQPage نیز خروجی گرفته می‌شوند.', 'pixva' ); ?></span>
	</p>
	<?php
}

/*
 * ---------------------------------------------------------------------------
 * ۴) ذخیره‌سازی امن متاباکس‌ها
 * ---------------------------------------------------------------------------
 */

/**
 * ذخیره متافیلدهای نمونه‌کار.
 *
 * @param int $post_id شناسه پرونده.
 * @return void
 */
function pixva_save_case_meta( $post_id ) {
	if ( ! isset( $_POST['pixva_case_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['pixva_case_nonce'] ) ), 'pixva_case_meta' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	$fields = array(
		'pixva_case_before'   => '_pixva_case_before',
		'pixva_case_after'    => '_pixva_case_after',
		'pixva_case_parts'    => '_pixva_case_parts',
		'pixva_case_model'    => '_pixva_case_model',
		'pixva_case_duration' => '_pixva_case_duration',
	);
	foreach ( $fields as $field => $meta_key ) {
		if ( ! isset( $_POST[ $field ] ) ) {
			continue;
		}
		$raw   = wp_unslash( $_POST[ $field ] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- در ادامه sanitize می‌شود.
		$value = str_ends_with( $meta_key, 'before' ) || str_ends_with( $meta_key, 'after' ) ? absint( $raw ) : sanitize_text_field( $raw );
		update_post_meta( $post_id, $meta_key, $value );
	}
}
add_action( 'save_post_repair_cases', 'pixva_save_case_meta' );

/**
 * ذخیره متافیلدهای پرونده تعمیر + ثبت زمان تغییر وضعیت.
 *
 * @param int $post_id شناسه پرونده.
 * @return void
 */
function pixva_save_order_meta( $post_id ) {
	if ( ! isset( $_POST['pixva_order_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['pixva_order_nonce'] ) ), 'pixva_order_meta' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	$statuses = array_keys( pixva_order_statuses() );
	$status   = isset( $_POST['pixva_order_status'] ) ? sanitize_key( wp_unslash( $_POST['pixva_order_status'] ) ) : 'received';
	if ( ! in_array( $status, $statuses, true ) ) {
		$status = 'received';
	}

	$old_status = (string) get_post_meta( $post_id, '_pixva_order_status', true );
	update_post_meta( $post_id, '_pixva_order_status', $status );

	// ثبت یا به‌روزرسانی زمان مرحله در تاریخچه.
	$steps = json_decode( (string) get_post_meta( $post_id, '_pixva_order_steps', true ), true );
	if ( ! is_array( $steps ) ) {
		$steps = array();
	}
	if ( $old_status !== $status || ! isset( $steps[ $status ] ) ) {
		$steps[ $status ] = time();
		update_post_meta( $post_id, '_pixva_order_steps', wp_json_encode( $steps ) );
	}

	$simple = array(
		'pixva_order_phone'    => '_pixva_order_phone',
		'pixva_order_brand'    => '_pixva_order_brand',
		'pixva_order_model'    => '_pixva_order_model',
		'pixva_order_problem'  => '_pixva_order_problem',
		'pixva_order_estimate' => '_pixva_order_estimate',
		'pixva_order_notes'    => '_pixva_order_notes',
	);
	foreach ( $simple as $field => $meta_key ) {
		if ( isset( $_POST[ $field ] ) ) {
			update_post_meta( $post_id, $meta_key, sanitize_text_field( wp_unslash( $_POST[ $field ] ) ) );
		}
	}
}
add_action( 'save_post_pixva_orders', 'pixva_save_order_meta' );

/**
 * ذخیره متافیلدهای مقاله.
 *
 * @param int $post_id شناسه پرونده.
 * @return void
 */
function pixva_save_post_meta( $post_id ) {
	if ( ! isset( $_POST['pixva_post_meta_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['pixva_post_meta_nonce'] ) ), 'pixva_post_meta' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	$difficulty = isset( $_POST['pixva_post_difficulty'] ) ? sanitize_key( wp_unslash( $_POST['pixva_post_difficulty'] ) ) : '';
	if ( ! in_array( $difficulty, array( 'easy', 'medium', 'hard' ), true ) ) {
		$difficulty = '';
	}
	update_post_meta( $post_id, '_pixva_post_difficulty', $difficulty );
	update_post_meta( $post_id, '_pixva_post_brand', isset( $_POST['pixva_post_brand'] ) ? sanitize_text_field( wp_unslash( $_POST['pixva_post_brand'] ) ) : '' );
	update_post_meta( $post_id, '_pixva_post_tools', isset( $_POST['pixva_post_tools'] ) ? sanitize_text_field( wp_unslash( $_POST['pixva_post_tools'] ) ) : '' );
	update_post_meta( $post_id, '_pixva_post_faq', isset( $_POST['pixva_post_faq'] ) ? sanitize_textarea_field( wp_unslash( $_POST['pixva_post_faq'] ) ) : '' );
}
add_action( 'save_post_post', 'pixva_save_post_meta' );

/*
 * ---------------------------------------------------------------------------
 * ۵) ابزارهای کمکی دامنه محتوا
 * ---------------------------------------------------------------------------
 */

if ( ! function_exists( 'pixva_create_order' ) ) {
	/**
	 * ساخت پرونده تعمیر جدید و بازگرداندن کد پیگیری.
	 *
	 * @param array $data داده‌های سفارش (brand, model, problem, phone, estimate).
	 * @return array{code: string, id: int}
	 */
	function pixva_create_order( $data ) {
		$code = 'PXV-' . gmdate( 'ym' ) . '-' . wp_rand( 1000, 9999 );
		$id   = wp_insert_post(
			array(
				'post_type'   => 'pixva_orders',
				'post_title'  => sprintf(
				/* translators: %s: کد پیگیری */
					esc_html__( 'پرونده تعمیر %s', 'pixva' ),
					$code
				),
				'post_status' => 'private',
				'meta_input'  => array(
					'_pixva_order_code'     => $code,
					'_pixva_order_phone'    => pixva_normalize_mobile( $data['phone'] ),
					'_pixva_order_status'   => 'received',
					'_pixva_order_brand'    => sanitize_text_field( $data['brand'] ),
					'_pixva_order_model'    => sanitize_text_field( $data['model'] ),
					'_pixva_order_problem'  => sanitize_text_field( $data['problem'] ),
					'_pixva_order_estimate' => sanitize_text_field( $data['estimate'] ),
					'_pixva_order_steps'    => wp_json_encode( array( 'received' => time() ) ),
				),
			),
			true
		);

		if ( is_wp_error( $id ) ) {
			return array(
				'code' => '',
				'id'   => 0,
			);
		}
		return array(
			'code' => $code,
			'id'   => (int) $id,
		);
	}
}

if ( ! function_exists( 'pixva_find_order' ) ) {
	/**
	 * جست‌وجوی پرونده تعمیر با تطابق «هم‌زمان» کد پیگیری و شماره همراه.
	 *
	 * استعلام فقط با شماره همراه (یا فقط با کد) ممنوع است؛ هر دو مقدار الزامی
	 * و هر دو باید دقیقاً با متای پرونده یکی باشند.
	 *
	 * @param string $code  کد پیگیری (فرمت PXV-...).
	 * @param string $phone شماره همراه.
	 * @return WP_Post|null
	 */
	function pixva_find_order( $code, $phone ) {
		$code  = (string) $code;
		$phone = pixva_normalize_mobile( $phone );

		if ( '' === $code || '' === $phone ) {
			return null;
		}

		$args = array(
			'post_type'      => 'pixva_orders',
			'post_status'    => array( 'private', 'publish' ),
			'posts_per_page' => 1,
			'orderby'        => 'date',
			'order'          => 'DESC',
			'fields'         => 'ids',
			'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				'relation' => 'AND',
				array(
					'key'   => '_pixva_order_code',
					'value' => $code,
				),
				array(
					'key'   => '_pixva_order_phone',
					'value' => $phone,
				),
			),
		);

		$ids = get_posts( $args );
		if ( empty( $ids ) ) {
			return null;
		}
		return get_post( (int) $ids[0] );
	}
}

if ( ! function_exists( 'pixva_find_order_by_code' ) ) {
	/**
	 * جست‌وجوی داخلی پرونده فقط با کد پیگیری (برای پیشخوان/نصب — نه استعلام عمومی).
	 *
	 * @param string $code کد پیگیری.
	 * @return WP_Post|null
	 */
	function pixva_find_order_by_code( $code ) {
		$code = (string) $code;
		if ( '' === $code ) {
			return null;
		}
		$ids = get_posts(
			array(
				'post_type'      => 'pixva_orders',
				'post_status'    => array( 'private', 'publish' ),
				'posts_per_page' => 1,
				'orderby'        => 'date',
				'order'          => 'DESC',
				'fields'         => 'ids',
				'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					array(
						'key'   => '_pixva_order_code',
						'value' => $code,
					),
				),
			)
		);
		if ( empty( $ids ) ) {
			return null;
		}
		return get_post( (int) $ids[0] );
	}
}

/*
 * ---------------------------------------------------------------------------
 * ۳) انبار قطعات، کدهای خطا، شعبه‌ها و گزارش‌های تعمیر (v25.0)
 * ---------------------------------------------------------------------------
 */

if ( ! function_exists( 'pixva_hub_meta_schema' ) ) {
	/**
	 * تعریف فیلدهای متاباکس برای CPTهای هاب.
	 *
	 * @return array<string, array<string, array<string, mixed>>>
	 */
	function pixva_hub_meta_schema() {
		$brands  = function_exists( 'pixva_brand_catalog' ) ? pixva_brand_catalog() : array();
		$sizes   = function_exists( 'pixva_size_catalog' ) ? pixva_size_catalog() : array();
		$service = function_exists( 'pixva_problem_catalog' ) ? pixva_problem_catalog() : array();
		$zones   = function_exists( 'pixva_zone_catalog' ) ? pixva_zone_catalog() : array();

		return array(
			'pixva_part'   => array(
				'_pixva_part_serial'   => array( 'label' => __( 'شماره سریال قطعه', 'pixva' ), 'type' => 'text', 'hint' => __( 'سریال چاپ‌شده روی قطعه؛ مبنای استعلام اصالت در ابزار ۲۲ و ۳۰.', 'pixva' ) ),
				'_pixva_part_sku'      => array( 'label' => __( 'کد فنی (SKU)', 'pixva' ), 'type' => 'text' ),
				'_pixva_part_brand'    => array( 'label' => __( 'برند', 'pixva' ), 'type' => 'select', 'options' => wp_list_pluck( $brands, 'fa' ) ),
				'_pixva_part_qty'      => array( 'label' => __( 'موجودی انبار', 'pixva' ), 'type' => 'number' ),
				'_pixva_part_price'    => array( 'label' => __( 'قیمت (تومان)', 'pixva' ), 'type' => 'number' ),
				'_pixva_part_warranty' => array( 'label' => __( 'گارانتی (روز)', 'pixva' ), 'type' => 'number', 'hint' => __( 'پیش‌فرض کارگاه ۱۸۰ روز است.', 'pixva' ) ),
				'_pixva_part_batch'    => array( 'label' => __( 'کد پارتی ورود', 'pixva' ), 'type' => 'text' ),
				'_pixva_part_origin'   => array( 'label' => __( 'کشور سازنده', 'pixva' ), 'type' => 'text' ),
				'_pixva_part_status'   => array(
					'label'   => __( 'وضعیت قطعه', 'pixva' ),
					'type'    => 'select',
					'options' => array(
						'original' => __( 'فابریک (اورجینال)', 'pixva' ),
						'oem'      => __( 'OEM سازگار', 'pixva' ),
						'refurb'   => __( 'بازسازی‌شده کارگاهی', 'pixva' ),
					),
				),
				'_pixva_part_sizes'    => array( 'label' => __( 'سایزهای سازگار (با ویرگول)', 'pixva' ), 'type' => 'text' ),
			),
			'pixva_error'  => array(
				'_pixva_error_code'   => array( 'label' => __( 'کد خطا', 'pixva' ), 'type' => 'text', 'hint' => __( 'مثلاً E:20 یا ۶ چشمک چراغ استندبای.', 'pixva' ) ),
				'_pixva_error_brand'  => array( 'label' => __( 'برند', 'pixva' ), 'type' => 'select', 'options' => wp_list_pluck( $brands, 'fa' ) ),
				'_pixva_error_level'  => array(
					'label'   => __( 'سطح خطر', 'pixva' ),
					'type'    => 'select',
					'options' => array(
						'info'     => __( 'اطلاع‌رسانی', 'pixva' ),
						'warning'  => __( 'هشدار', 'pixva' ),
						'danger'   => __( 'خطرناک — نیاز به اعزام', 'pixva' ),
						'critical' => __( 'بحرانی — قطع برق فوری', 'pixva' ),
					),
				),
				'_pixva_error_part'   => array( 'label' => __( 'قطعه درگیر', 'pixva' ), 'type' => 'select', 'options' => $service ),
				'_pixva_error_blinks' => array( 'label' => __( 'تعداد چشمک چراغ (اختیاری)', 'pixva' ), 'type' => 'number' ),
			),
			'pixva_branch' => array(
				'_pixva_branch_address'   => array( 'label' => __( 'نشانی کامل', 'pixva' ), 'type' => 'textarea' ),
				'_pixva_branch_phone'     => array( 'label' => __( 'شماره تماس', 'pixva' ), 'type' => 'text' ),
				'_pixva_branch_hours'     => array( 'label' => __( 'ساعات کاری', 'pixva' ), 'type' => 'text' ),
				'_pixva_branch_zones'     => array( 'label' => __( 'مناطق پوششی', 'pixva' ), 'type' => 'multiselect', 'options' => wp_list_pluck( $zones, 'label' ) ),
				'_pixva_branch_map'       => array( 'label' => __( 'پیوند نقشه', 'pixva' ), 'type' => 'url' ),
				'_pixva_branch_technician' => array( 'label' => __( 'تکنسین مسئول', 'pixva' ), 'type' => 'text' ),
			),
			'pixva_repair' => array(
				'_pixva_repair_brand'    => array( 'label' => __( 'برند دستگاه', 'pixva' ), 'type' => 'select', 'options' => wp_list_pluck( $brands, 'fa' ) ),
				'_pixva_repair_size'     => array( 'label' => __( 'سایز پنل', 'pixva' ), 'type' => 'select', 'options' => $sizes ),
				'_pixva_repair_service'  => array( 'label' => __( 'خدمت انجام‌شده', 'pixva' ), 'type' => 'select', 'options' => $service ),
				'_pixva_repair_duration' => array( 'label' => __( 'مدت تعمیر', 'pixva' ), 'type' => 'text', 'hint' => __( 'مثلاً ۴ ساعت کاری.', 'pixva' ) ),
				'_pixva_repair_warranty' => array( 'label' => __( 'گارانتی (روز)', 'pixva' ), 'type' => 'number' ),
				'_pixva_repair_cost_min' => array( 'label' => __( 'حداقل هزینه (تومان)', 'pixva' ), 'type' => 'number' ),
				'_pixva_repair_cost_max' => array( 'label' => __( 'حداکثر هزینه (تومان)', 'pixva' ), 'type' => 'number' ),
				'_pixva_repair_difficulty' => array(
					'label'   => __( 'سطح دشواری', 'pixva' ),
					'type'    => 'select',
					'options' => array(
						'easy'     => __( 'ساده', 'pixva' ),
						'medium'   => __( 'متوسط', 'pixva' ),
						'advanced' => __( 'پیشرفته (بندینگ/BGA)', 'pixva' ),
					),
				),
				'_pixva_repair_parts'    => array( 'label' => __( 'قطعات تعویض‌شده', 'pixva' ), 'type' => 'text' ),
			),
		);
	}
}

if ( ! function_exists( 'pixva_register_hub_meta' ) ) {
	/**
	 * ثبت رسمی متافیلدهای CPTهای هاب برای REST و گوتنبرگ.
	 *
	 * @return void
	 */
	function pixva_register_hub_meta() {
		foreach ( pixva_hub_meta_schema() as $post_type => $fields ) {
			foreach ( $fields as $key => $field ) {
				register_post_meta(
					$post_type,
					$key,
					array(
						'type'         => 'number' === $field['type'] ? 'integer' : 'string',
						'description'  => $field['label'],
						'single'       => true,
						'show_in_rest' => true,
						'auth_callback' => static function () {
							return current_user_can( 'edit_posts' );
						},
					)
				);
			}
		}
	}
	add_action( 'init', 'pixva_register_hub_meta', 20 );
}

if ( ! function_exists( 'pixva_add_hub_meta_boxes' ) ) {
	/**
	 * افزودن متاباکس مشخصات به CPTهای هاب.
	 *
	 * @return void
	 */
	function pixva_add_hub_meta_boxes() {
		foreach ( array_keys( pixva_hub_meta_schema() ) as $post_type ) {
			add_meta_box(
				'pixva_hub_meta_' . $post_type,
				esc_html__( 'مشخصات فنی پیکسوا', 'pixva' ),
				'pixva_render_hub_meta_box',
				$post_type,
				'normal',
				'high'
			);
		}
	}
	add_action( 'add_meta_boxes', 'pixva_add_hub_meta_boxes' );
}

if ( ! function_exists( 'pixva_metabox_styles' ) ) {
	/**
	 * استایل سبک متاباکس‌های پیکسوا در پیشخوان.
	 *
	 * @return void
	 */
	function pixva_metabox_styles() {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		if ( ! $screen || ! in_array( $screen->post_type, array( 'pixva_part', 'pixva_error', 'pixva_branch', 'pixva_repair', 'pixva_orders', 'repair_cases', 'post' ), true ) ) {
			return;
		}
		?>
		<style>
			.pixva-metabox{display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:6px 18px}
			.pixva-metabox p{margin:0 0 10px}
			.pixva-metabox label{display:block;margin-bottom:4px}
			.pixva-metabox label strong{font-size:12.5px;color:rgb(18,59,74)}
			.pixva-metabox .description,.pixva-metabox small{display:block;margin-top:4px;font-size:11.5px;color:rgb(91,122,135);line-height:1.8}
			.pixva-metabox select[multiple]{min-height:110px}
		</style>
		<?php
	}
}
add_action( 'admin_head-post.php', 'pixva_metabox_styles' );
add_action( 'admin_head-post-new.php', 'pixva_metabox_styles' );

if ( ! function_exists( 'pixva_render_hub_meta_box' ) ) {
	/**
	 * رندر متاباکس مشخصات فنی.
	 *
	 * @param WP_Post $post پرونده جاری.
	 * @return void
	 */
	function pixva_render_hub_meta_box( $post ) {
		$schema = pixva_hub_meta_schema();
		if ( ! isset( $schema[ $post->post_type ] ) ) {
			return;
		}

		wp_nonce_field( 'pixva_hub_meta', 'pixva_hub_meta_nonce' );

		echo '<div class="pixva-metabox">';
		foreach ( $schema[ $post->post_type ] as $key => $field ) {
			$value = get_post_meta( $post->ID, $key, true );
			echo '<p><label for="' . esc_attr( $key ) . '"><strong>' . esc_html( $field['label'] ) . '</strong></label>';

			if ( 'select' === $field['type'] ) {
				echo '<select id="' . esc_attr( $key ) . '" name="' . esc_attr( $key ) . '" class="widefat">';
				echo '<option value="">' . esc_html__( '— انتخاب کنید —', 'pixva' ) . '</option>';
				foreach ( (array) $field['options'] as $option_key => $option_label ) {
					echo '<option value="' . esc_attr( $option_key ) . '"' . selected( (string) $value, (string) $option_key, false ) . '>' . esc_html( $option_label ) . '</option>';
				}
				echo '</select>';
			} elseif ( 'multiselect' === $field['type'] ) {
				$selected = is_array( $value ) ? $value : array_filter( array_map( 'trim', explode( ',', (string) $value ) ) );
				echo '<select id="' . esc_attr( $key ) . '" name="' . esc_attr( $key ) . '[]" class="widefat" multiple size="6">';
				foreach ( (array) $field['options'] as $option_key => $option_label ) {
					echo '<option value="' . esc_attr( $option_key ) . '"' . ( in_array( (string) $option_key, $selected, true ) ? ' selected' : '' ) . '>' . esc_html( $option_label ) . '</option>';
				}
				echo '</select>';
			} elseif ( 'textarea' === $field['type'] ) {
				echo '<textarea id="' . esc_attr( $key ) . '" name="' . esc_attr( $key ) . '" class="widefat" rows="3">' . esc_textarea( (string) $value ) . '</textarea>';
			} elseif ( 'number' === $field['type'] ) {
				echo '<input type="number" step="1" min="0" id="' . esc_attr( $key ) . '" name="' . esc_attr( $key ) . '" class="widefat" value="' . esc_attr( (string) $value ) . '">';
			} else {
				$type = 'url' === $field['type'] ? 'url' : 'text';
				echo '<input type="' . esc_attr( $type ) . '" id="' . esc_attr( $key ) . '" name="' . esc_attr( $key ) . '" class="widefat" value="' . esc_attr( (string) $value ) . '">';
			}

			if ( ! empty( $field['hint'] ) ) {
				echo '<span class="description">' . esc_html( $field['hint'] ) . '</span>';
			}
			echo '</p>';
		}
		echo '</div>';
	}
}

if ( ! function_exists( 'pixva_save_hub_meta' ) ) {
	/**
	 * ذخیره متادیتای متاباکس هاب‌ها.
	 *
	 * @param int $post_id شناسه پرونده.
	 * @return void
	 */
	function pixva_save_hub_meta( $post_id ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! isset( $_POST['pixva_hub_meta_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['pixva_hub_meta_nonce'] ) ), 'pixva_hub_meta' ) ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$post_type = get_post_type( $post_id );
		$schema    = pixva_hub_meta_schema();
		if ( ! isset( $schema[ $post_type ] ) ) {
			return;
		}

		foreach ( $schema[ $post_type ] as $key => $field ) {
			if ( 'multiselect' === $field['type'] ) {
				$raw = isset( $_POST[ $key ] ) ? (array) wp_unslash( $_POST[ $key ] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
				update_post_meta( $post_id, $key, array_values( array_filter( array_map( 'sanitize_key', $raw ) ) ) );
				continue;
			}

			if ( ! isset( $_POST[ $key ] ) ) {
				update_post_meta( $post_id, $key, '' );
				continue;
			}

			$raw = wp_unslash( $_POST[ $key ] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput

			if ( 'number' === $field['type'] ) {
				update_post_meta( $post_id, $key, absint( $raw ) );
			} elseif ( 'textarea' === $field['type'] ) {
				update_post_meta( $post_id, $key, sanitize_textarea_field( $raw ) );
			} elseif ( 'url' === $field['type'] ) {
				update_post_meta( $post_id, $key, esc_url_raw( $raw ) );
			} elseif ( 'select' === $field['type'] ) {
				update_post_meta( $post_id, $key, sanitize_key( $raw ) );
			} else {
				update_post_meta( $post_id, $key, sanitize_text_field( $raw ) );
			}
		}
	}
	add_action( 'save_post', 'pixva_save_hub_meta' );
}

if ( ! function_exists( 'pixva_part_records' ) ) {
	/**
	 * رکوردهای انبار قطعات.
	 *
	 * اولویت با رکوردهای واقعی CPT «انبار قطعات فابریک» است؛ اگر مدیر هنوز
	 * رکوردی ثبت نکرده باشد، کاتالوگ مرجع کارگاه (inc/tool-data.php) برگردانده
	 * می‌شود تا ابزارها هیچ‌گاه خالی نمانند.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	function pixva_part_records() {
		$posts = get_posts(
			array(
				'post_type'      => 'pixva_part',
				'post_status'    => 'publish',
				'posts_per_page' => 120,
				'orderby'        => 'title',
				'order'          => 'ASC',
				'no_found_rows'  => true,
			)
		);

		$items = array();
		if ( ! empty( $posts ) ) {
			foreach ( $posts as $post ) {
				$items[] = array(
					'name'     => get_the_title( $post ),
					'sku'      => (string) get_post_meta( $post->ID, '_pixva_part_sku', true ),
					'brand'    => (string) get_post_meta( $post->ID, '_pixva_part_brand', true ),
					'qty'      => (int) get_post_meta( $post->ID, '_pixva_part_qty', true ),
					'price'    => (int) get_post_meta( $post->ID, '_pixva_part_price', true ),
					'warranty' => (int) get_post_meta( $post->ID, '_pixva_part_warranty', true ) ?: pixva_warranty_days(),
					'serial'   => (string) get_post_meta( $post->ID, '_pixva_part_serial', true ),
					'status'   => (string) get_post_meta( $post->ID, '_pixva_part_status', true ),
					'source'   => 'cpt',
				);
			}
			update_option( 'pixva_stock_updated', time(), false );
			return $items;
		}

		foreach ( pixva_stock_catalog() as $row ) {
			$items[] = array(
				'name'     => $row['name'],
				'sku'      => $row['sku'],
				'brand'    => $row['brand'],
				'qty'      => (int) $row['qty'],
				'price'    => (int) $row['price'],
				'warranty' => (int) $row['warranty'],
				'serial'   => '',
				'status'   => 'original',
				'source'   => 'defaults',
			);
		}

		return $items;
	}
}

if ( ! function_exists( 'pixva_find_part_by_serial' ) ) {
	/**
	 * یافتن قطعه با شماره سریال (تطابق کامل یا انتهای سریال).
	 *
	 * @param string $serial شماره سریال.
	 * @return array<string, mixed>|null
	 */
	function pixva_find_part_by_serial( $serial ) {
		$serial = strtoupper( preg_replace( '/[^A-Za-z0-9\-\/]/', '', (string) $serial ) );
		if ( '' === $serial ) {
			return null;
		}

		$posts = get_posts(
			array(
				'post_type'      => 'pixva_part',
				'post_status'    => 'publish',
				'posts_per_page' => 1,
				'no_found_rows'  => true,
				'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					'relation' => 'OR',
					array(
						'key'     => '_pixva_part_serial',
						'value'   => $serial,
						'compare' => '=',
					),
					array(
						'key'     => '_pixva_part_batch',
						'value'   => $serial,
						'compare' => '=',
					),
				),
			)
		);

		if ( empty( $posts ) ) {
			return null;
		}

		$post          = $posts[0];
		$status_labels = array(
			'original' => __( 'فابریک (اورجینال) — ثبت‌شده در انبار مرکزی پیکسوا', 'pixva' ),
			'oem'      => __( 'OEM سازگار — تأییدشده توسط واحد فنی پیکسوا', 'pixva' ),
			'refurb'   => __( 'بازسازی‌شده کارگاهی با گارانتی کتبی', 'pixva' ),
		);
		$status        = (string) get_post_meta( $post->ID, '_pixva_part_status', true );

		return array(
			'id'       => (int) $post->ID,
			'name'     => get_the_title( $post ),
			'sku'      => (string) get_post_meta( $post->ID, '_pixva_part_sku', true ),
			'brand'    => (string) get_post_meta( $post->ID, '_pixva_part_brand', true ),
			'qty'      => (int) get_post_meta( $post->ID, '_pixva_part_qty', true ),
			'price'    => (int) get_post_meta( $post->ID, '_pixva_part_price', true ),
			'warranty' => (int) get_post_meta( $post->ID, '_pixva_part_warranty', true ) ?: pixva_warranty_days(),
			'serial'   => (string) get_post_meta( $post->ID, '_pixva_part_serial', true ),
			'batch'    => (string) get_post_meta( $post->ID, '_pixva_part_batch', true ),
			'origin'   => (string) get_post_meta( $post->ID, '_pixva_part_origin', true ),
			'status'   => isset( $status_labels[ $status ] ) ? $status_labels[ $status ] : $status_labels['original'],
		);
	}
}

if ( ! function_exists( 'pixva_repair_records' ) ) {
	/**
	 * گزارش‌های تعمیر ثبت‌شده (دانش فنی کارگاه).
	 *
	 * @param array $args فیلترها: brand، service، limit.
	 * @return array<int, array<string, mixed>>
	 */
	function pixva_repair_records( $args = array() ) {
		$args = wp_parse_args(
			$args,
			array(
				'brand'   => '',
				'service' => '',
				'limit'   => 12,
			)
		);

		$query = array(
			'post_type'      => 'pixva_repair',
			'post_status'    => 'publish',
			'posts_per_page' => max( 1, min( 60, (int) $args['limit'] ) ),
			'orderby'        => 'date',
			'order'          => 'DESC',
			'no_found_rows'  => true,
		);

		if ( '' !== $args['brand'] ) {
			$query['meta_query'][] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				'key'   => '_pixva_repair_brand',
				'value' => sanitize_key( $args['brand'] ),
			);
		}
		if ( '' !== $args['service'] ) {
			$query['meta_query'][] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				'key'   => '_pixva_repair_service',
				'value' => sanitize_key( $args['service'] ),
			);
		}

		$posts   = get_posts( $query );
		$brands  = function_exists( 'pixva_brand_catalog' ) ? pixva_brand_catalog() : array();
		$records = array();

		foreach ( $posts as $post ) {
			$brand_key = (string) get_post_meta( $post->ID, '_pixva_repair_brand', true );
			$records[] = array(
				'id'         => (int) $post->ID,
				'title'      => get_the_title( $post ),
				'excerpt'    => get_the_excerpt( $post ),
				'url'        => get_permalink( $post ),
				'brand'      => isset( $brands[ $brand_key ] ) ? $brands[ $brand_key ]['fa'] : $brand_key,
				'size'       => (string) get_post_meta( $post->ID, '_pixva_repair_size', true ),
				'service'    => (string) get_post_meta( $post->ID, '_pixva_repair_service', true ),
				'duration'   => (string) get_post_meta( $post->ID, '_pixva_repair_duration', true ),
				'warranty'   => (int) get_post_meta( $post->ID, '_pixva_repair_warranty', true ),
				'cost_min'   => (int) get_post_meta( $post->ID, '_pixva_repair_cost_min', true ),
				'cost_max'   => (int) get_post_meta( $post->ID, '_pixva_repair_cost_max', true ),
				'difficulty' => (string) get_post_meta( $post->ID, '_pixva_repair_difficulty', true ),
				'parts'      => (string) get_post_meta( $post->ID, '_pixva_repair_parts', true ),
			);
		}

		return $records;
	}
}

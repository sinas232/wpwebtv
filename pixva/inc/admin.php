<?php
/**
 * Admin hub "پیکسوا" (§47).
 *
 * Screens
 * - Overview     : setup checklist from real state (claims, pricing,
 *                  warranty policy, route pages, content counts).
 * - Business     : business claims (Settings API, pixva_manage_settings).
 * - Pricing      : configurable price table (Settings API).
 * - Redirects    : redirect manager (pixva_manage_redirects).
 * - Migration    : log of what the v2 upgrade changed (read only).
 *
 * @package Pixva
 * @since   2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register menu pages.
 *
 * @return void
 */
function pixva_admin_menu() {
	$cap = current_user_can( 'pixva_manage_settings' ) ? 'pixva_manage_settings' : 'pixva_manage_redirects';
	add_menu_page( __( 'پیکسوا', 'pixva' ), __( 'پیکسوا', 'pixva' ), $cap, 'pixva', 'pixva_admin_overview', 'dashicons-desktop', 2 );
	add_submenu_page( 'pixva', __( 'وضعیت راه‌اندازی', 'pixva' ), __( 'وضعیت راه‌اندازی', 'pixva' ), $cap, 'pixva', 'pixva_admin_overview' );
	// Settings hub right after the overview: it is the index of every real
	// settings screen, so it must be the second stop in the menu.
	add_submenu_page( 'pixva', __( 'تنظیمات و راهنما', 'pixva' ), __( 'تنظیمات و راهنما', 'pixva' ), 'pixva_view_dashboard', 'pixva-hub', 'pixva_admin_hub' );
	add_submenu_page( 'pixva', __( 'اطلاعات کسب‌وکار', 'pixva' ), __( 'اطلاعات کسب‌وکار', 'pixva' ), 'pixva_manage_settings', 'pixva-business', 'pixva_admin_business' );
	add_submenu_page( 'pixva', __( 'قیمت‌گذاری', 'pixva' ), __( 'قیمت‌گذاری', 'pixva' ), 'pixva_manage_settings', 'pixva-pricing', 'pixva_admin_pricing' );
	add_submenu_page( 'pixva', __( 'انتقال‌ها (ریدایرکت)', 'pixva' ), __( 'انتقال‌ها', 'pixva' ), 'pixva_manage_redirects', 'pixva-redirects', 'pixva_admin_redirects' );
	add_submenu_page( 'pixva', __( 'گزارش ارتقا', 'pixva' ), __( 'گزارش ارتقا', 'pixva' ), 'pixva_manage_settings', 'pixva-migration', 'pixva_admin_migration' );
}
add_action( 'admin_menu', 'pixva_admin_menu' );

/**
 * Settings API registration.
 *
 * @return void
 */
function pixva_admin_settings() {
	register_setting(
		'pixva_business',
		'pixva_business_claims',
		array(
			'type'              => 'array',
			'sanitize_callback' => 'pixva_sanitize_claims',
			'default'           => array(),
		)
	);
	register_setting(
		'pixva_pricing',
		'pixva_pricing',
		array(
			'type'              => 'array',
			'sanitize_callback' => 'pixva_sanitize_pricing',
			'default'           => array(),
		)
	);
}
add_action( 'admin_init', 'pixva_admin_settings' );

add_filter(
	'option_page_capability_pixva_business',
	static function () {
		return 'pixva_manage_settings';
	}
);
add_filter(
	'option_page_capability_pixva_pricing',
	static function () {
		return 'pixva_manage_settings';
	}
);

/**
 * Overview / checklist.
 *
 * @return void
 */
function pixva_admin_overview() {
	$checks = array(
		array( pixva_has_claim( 'phone' ) || pixva_has_claim( 'mobile' ) || pixva_has_claim( 'email' ), __( 'حداقل یک راه تماس ثبت شده است', 'pixva' ), admin_url( 'admin.php?page=pixva-business' ) ),
		array( pixva_has_local_business(), __( 'نشانی، شهر و تلفن ثبت شده (برای اسکیمای LocalBusiness)', 'pixva' ), admin_url( 'admin.php?page=pixva-business' ) ),
		array( (bool) pixva_service_modes(), __( 'شیوه‌های تحویل دستگاه مشخص شده است', 'pixva' ), admin_url( 'admin.php?page=pixva-business' ) ),
		array( pixva_warranty_policy_days() > 0, __( 'سیاست گارانتی با مدت مشخص تنظیم شده است', 'pixva' ), admin_url( 'admin.php?page=pixva-business' ) ),
		array( pixva_pricing_active(), __( 'قیمت‌گذاری آنلاین فعال است (در غیر این صورت «پس از کارشناسی» نمایش داده می‌شود)', 'pixva' ), admin_url( 'admin.php?page=pixva-pricing' ) ),
		array( (bool) get_privacy_policy_url(), __( 'صفحه حریم خصوصی منتشر شده است', 'pixva' ), admin_url( 'options-privacy.php' ) ),
		array( ! pixva_core_sample_content(), __( 'محتوای نمونه وردپرس (Hello world / Sample Page) حذف یا بازنویسی شده است', 'pixva' ), admin_url( 'edit.php?post_type=page' ) ),
	);
	echo '<div class="wrap pixva-admin"><h1>' . esc_html__( 'پیکسوا — وضعیت راه‌اندازی', 'pixva' ) . '</h1>';
	echo '<p>' . esc_html__( 'هر مورد فقط وقتی در سایت نمایش داده می‌شود که اطلاعات واقعی آن را وارد کرده باشید. هیچ داده پیش‌فرض ساختگی وجود ندارد.', 'pixva' ) . '</p>';
	pixva_admin_stat_cards();

	// Quick actions: one-click shortcuts to the screens staff use daily.
	echo '<div class="pixva-quick-actions">';
	$pixva_shortcuts = array();
	if ( current_user_can( 'pixva_manage_orders' ) ) {
		$pixva_shortcuts[] = array( admin_url( 'edit.php?post_type=pixva_orders' ), __( 'مدیریت درخواست‌ها', 'pixva' ), 'dashicons-clipboard' );
	}
	if ( current_user_can( 'edit_pixva_messages' ) ) {
		$pixva_shortcuts[] = array( admin_url( 'edit.php?post_type=pixva_inbox' ), __( 'پیام‌های رسیده', 'pixva' ), 'dashicons-email-alt' );
	}
	if ( current_user_can( 'pixva_view_dashboard' ) ) {
		$pixva_shortcuts[] = array( admin_url( 'admin.php?page=pixva-hub' ), __( 'تنظیمات و راهنما', 'pixva' ), 'dashicons-admin-settings-alt' );
	}
	if ( current_user_can( 'pixva_manage_settings' ) ) {
		$pixva_shortcuts[] = array( admin_url( 'admin.php?page=pixva-business' ), __( 'اطلاعات کسب‌وکار', 'pixva' ), 'dashicons-building' );
	}
	$pixva_front_edit = pixva_hub_elementor_url();
	if ( $pixva_front_edit && current_user_can( 'edit_pages' ) ) {
		$pixva_shortcuts[] = array( $pixva_front_edit, __( 'ویرایش صفحه اصلی', 'pixva' ), 'dashicons-edit' );
	}
	foreach ( $pixva_shortcuts as $pixva_sc ) {
		echo '<a class="pixva-quick-action" href="' . esc_url( $pixva_sc[0] ) . '"><span class="dashicons ' . esc_attr( $pixva_sc[2] ) . '" aria-hidden="true"></span><span>' . esc_html( $pixva_sc[1] ) . '</span></a>';
	}
	echo '</div>';

	// Latest orders that still need follow-up (real DB rows, capability-scoped).
	if ( current_user_can( 'pixva_manage_orders' ) ) {
		$pixva_ids = pixva_order_query(
			array(
				'posts_per_page' => 5,
				'orderby'        => 'date',
				'order'          => 'DESC',
			)
		);
		$pixva_pending = $pixva_ids ? get_posts(
			array(
				'post_type'      => 'pixva_orders',
				'post__in'       => $pixva_ids,
				'post_status'    => array( 'private', 'publish' ),
				'orderby'        => 'date',
				'order'          => 'DESC',
				'posts_per_page' => 5,
			)
		) : array();
		echo '<div class="pixva-panel pixva-panel--orders"><h2>' . esc_html__( 'آخرین سفارش‌ها (نیازمند پیگیری)', 'pixva' ) . ' <a class="pixva-panel__more" href="' . esc_url( admin_url( 'edit.php?post_type=pixva_orders' ) ) . '">' . esc_html__( 'مشاهده همه', 'pixva' ) . '</a></h2>';
		if ( $pixva_pending ) {
			echo '<table class="widefat striped"><thead><tr><th>' . esc_html__( 'کد سفارش', 'pixva' ) . '</th><th>' . esc_html__( 'مشتری', 'pixva' ) . '</th><th>' . esc_html__( 'دستگاه', 'pixva' ) . '</th><th>' . esc_html__( 'وضعیت', 'pixva' ) . '</th><th>' . esc_html__( 'تاریخ ثبت', 'pixva' ) . '</th></tr></thead><tbody>';
			foreach ( $pixva_pending as $pixva_o ) {
				$pixva_st = (string) get_post_meta( $pixva_o->ID, '_pixva_order_status', true );
				$pixva_lb = pixva_order_statuses()[ $pixva_st ]['label'] ?? ( $pixva_st ? $pixva_st : __( 'جدید', 'pixva' ) );
				$pixva_nm = (string) get_post_meta( $pixva_o->ID, '_pixva_order_name', true );
				$pixva_dv = trim( (string) get_post_meta( $pixva_o->ID, '_pixva_order_brand', true ) . ' ' . (string) get_post_meta( $pixva_o->ID, '_pixva_order_model', true ) );
				echo '<tr><td><a href="' . esc_url( get_edit_post_link( $pixva_o->ID ) ) . '"><code dir="ltr">' . esc_html( $pixva_o->post_title ) . '</code></a></td>';
				echo '<td>' . esc_html( $pixva_nm ? $pixva_nm : '—' ) . '</td>';
				echo '<td>' . esc_html( $pixva_dv ? $pixva_dv : '—' ) . '</td>';
				echo '<td><span class="pixva-badge pixva-badge--' . esc_attr( $pixva_st ? $pixva_st : 'new' ) . '">' . esc_html( $pixva_lb ) . '</span></td>';
				$pixva_ts = strtotime( (string) $pixva_o->post_date );
				echo '<td>' . esc_html( pixva_fa_num( $pixva_ts ? wp_date( 'Y/m/j', $pixva_ts ) : (string) $pixva_o->post_date ) ) . '</td></tr>';
			}
			echo '</tbody></table>';
		} else {
			echo '<div class="pixva-empty"><p>' . esc_html__( 'هنوز سفارشی ثبت نشده است.', 'pixva' ) . '</p><p class="description">' . esc_html__( 'سفارش‌های جدید از فرم‌های سایت (ثبت درخواست/پیگیری/گارانتی) اینجا نمایش داده می‌شوند.', 'pixva' ) . '</p></div>';
		}
		echo '</div>';
	}

	echo '<div class="pixva-panel"><h2>' . esc_html__( 'فهرست بررسی', 'pixva' ) . '</h2><ul class="pixva-checklist">';
	foreach ( $checks as $c ) {
		echo '<li class="' . ( $c[0] ? 'is-done' : 'is-todo' ) . '"><span class="dashicons ' . ( $c[0] ? 'dashicons-yes-alt' : 'dashicons-marker' ) . '" aria-hidden="true"></span> <a href="' . esc_url( $c[2] ) . '">' . esc_html( $c[1] ) . '</a> <span class="screen-reader-text">' . esc_html( $c[0] ? __( 'انجام شده', 'pixva' ) : __( 'انجام نشده', 'pixva' ) ) . '</span></li>';
	}
	echo '</ul></div>';
	echo '<div class="pixva-panel"><h2>' . esc_html__( 'محتوا', 'pixva' ) . '</h2><table class="widefat striped"><thead><tr><th>' . esc_html__( 'نوع', 'pixva' ) . '</th><th>' . esc_html__( 'منتشرشده', 'pixva' ) . '</th><th>' . esc_html__( 'پیش‌نویس', 'pixva' ) . '</th></tr></thead><tbody>';
	foreach ( array( 'tv_services', 'tv_brands', 'tv_model', 'pixva_error', 'repair_cases', 'pixva_faq', 'post' ) as $type ) {
		$o = get_post_type_object( $type );
		$c = wp_count_posts( $type );
		echo '<tr><td><a href="' . esc_url( admin_url( 'edit.php?post_type=' . $type ) ) . '">' . esc_html( $o ? $o->labels->name : $type ) . '</a></td><td>' . esc_html( pixva_fa_num( (int) ( $c->publish ?? 0 ) ) ) . '</td><td>' . esc_html( pixva_fa_num( (int) ( $c->draft ?? 0 ) ) ) . '</td></tr>';
	}
	echo '</tbody></table></div>';
	echo '<div class="pixva-panel"><h2>' . esc_html__( 'صفحه‌های سیستمی', 'pixva' ) . '</h2><table class="widefat striped"><thead><tr><th>' . esc_html__( 'مسیر', 'pixva' ) . '</th><th>' . esc_html__( 'منبع', 'pixva' ) . '</th><th>' . esc_html__( 'ایندکس', 'pixva' ) . '</th></tr></thead><tbody>';
	foreach ( pixva_routes() as $key => $def ) {
		$ok = 'page' !== $def['source'] || pixva_route_page_id( $key );
		echo '<tr><td><code dir="ltr">' . esc_html( $def['path'] ) . '</code> ' . ( $ok ? '' : '<strong>' . esc_html__( '(صفحه وجود ندارد — تم را دوباره فعال کنید)', 'pixva' ) . '</strong>' ) . '</td><td>' . esc_html( $def['source'] ) . '</td><td>' . ( $def['index'] ? '✓' : '—' ) . '</td></tr>';
	}
	echo '</tbody></table></div></div>';
}

/**
 * Business claims screen.
 *
 * @return void
 */
function pixva_admin_business() {
	if ( ! current_user_can( 'pixva_manage_settings' ) ) {
		wp_die( esc_html__( 'دسترسی مجاز نیست.', 'pixva' ) );
	}
	$claims = pixva_claims();
	echo '<div class="wrap pixva-admin"><h1>' . esc_html__( 'اطلاعات کسب‌وکار', 'pixva' ) . '</h1>';
	echo '<p>' . esc_html__( 'فقط اطلاعات واقعی و قابل اثبات وارد کنید. هر فیلد خالی در سایت، اسکیما و فوتر نمایش داده نمی‌شود.', 'pixva' ) . '</p>';
	settings_errors();
	echo '<form method="post" action="options.php">';
	settings_fields( 'pixva_business' );
	foreach ( pixva_claim_groups() as $group => $title ) {
		echo '<h2>' . esc_html( $title ) . '</h2><table class="form-table" role="presentation"><tbody>';
		foreach ( pixva_claim_fields() as $key => $def ) {
			if ( $def[2] !== $group ) {
				continue;
			}
			$id    = 'pixva-claim-' . $key;
			$name  = 'pixva_business_claims[' . $key . ']';
			$value = $claims[ $key ];
			echo '<tr><th scope="row"><label for="' . esc_attr( $id ) . '">' . esc_html( $def[1] ) . '</label></th><td>';
			switch ( $def[0] ) {
				case 'bool':
					echo '<input type="checkbox" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" value="1" ' . checked( (bool) $value, true, false ) . '>';
					break;
				case 'textarea':
				case 'lines':
					echo '<textarea class="large-text" rows="3" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '">' . esc_textarea( (string) $value ) . '</textarea>';
					break;
				case 'html':
					wp_editor(
						(string) $value,
						$id,
						array(
							'textarea_name' => $name,
							'textarea_rows' => 5,
							'media_buttons' => false,
							'teeny'         => true,
						)
					);
					break;
				default:
					$type = array(
						'email' => 'email',
						'url'   => 'url',
						'int'   => 'number',
						'tel'   => 'tel',
					)[ $def[0] ] ?? 'text';
					$ltr  = in_array( $def[0], array( 'email', 'url', 'tel', 'float', 'int' ), true ) ? ' dir="ltr"' : '';
					echo '<input class="regular-text" type="' . esc_attr( $type ) . '"' . $ltr . ' id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( (string) $value ) . '">'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static attribute.
			}
			if ( '' !== $def[3] ) {
				echo '<p class="description">' . esc_html( $def[3] ) . '</p>';
			}
			echo '</td></tr>';
		}
		echo '</tbody></table>';
	}
	submit_button();
	echo '</form></div>';
}

/**
 * Pricing screen.
 *
 * @return void
 */
function pixva_admin_pricing() {
	if ( ! current_user_can( 'pixva_manage_settings' ) ) {
		wp_die( esc_html__( 'دسترسی مجاز نیست.', 'pixva' ) );
	}
	$p = pixva_pricing();
	echo '<div class="wrap pixva-admin"><h1>' . esc_html__( 'قیمت‌گذاری', 'pixva' ) . '</h1>';
	settings_errors();
	if ( get_option( 'pixva_pricing_legacy_imported' ) && empty( $p['enabled'] ) ) {
		echo '<div class="notice notice-warning"><p>' . esc_html__( 'قیمت‌های نسخه قبلی به‌صورت غیرفعال وارد شدند. آن‌ها را بازبینی کنید؛ تا زمانی که «فعال» را نزنید، در سایت نمایش داده نمی‌شوند.', 'pixva' ) . '</p></div>';
	}
	echo '<p>' . esc_html__( 'قیمت پیش‌فرض وجود ندارد. تا وقتی قیمت‌گذاری فعال نشده یا برای خدمتی بازه ثبت نشده، به کاربر گفته می‌شود هزینه پس از کارشناسی اعلام می‌شود و امکان ثبت درخواست وجود دارد.', 'pixva' ) . '</p>';
	echo '<form method="post" action="options.php">';
	settings_fields( 'pixva_pricing' );
	echo '<table class="form-table" role="presentation"><tbody>';
	echo '<tr><th scope="row">' . esc_html__( 'وضعیت', 'pixva' ) . '</th><td><label><input type="checkbox" name="pixva_pricing[enabled]" value="1" ' . checked( ! empty( $p['enabled'] ), true, false ) . '> ' . esc_html__( 'برآورد آنلاین فعال باشد', 'pixva' ) . '</label></td></tr>';
	echo '<tr><th scope="row"><label for="pixva-cur">' . esc_html__( 'واحد پول', 'pixva' ) . '</label></th><td><input id="pixva-cur" name="pixva_pricing[currency]" value="' . esc_attr( $p['currency'] ) . '"></td></tr>';
	echo '<tr><th scope="row">' . esc_html__( 'هزینه کارشناسی', 'pixva' ) . '</th><td><label>' . esc_html__( 'از', 'pixva' ) . ' <input type="number" min="0" dir="ltr" name="pixva_pricing[inspection][min]" value="' . esc_attr( (string) $p['inspection']['min'] ) . '"></label> <label>' . esc_html__( 'تا', 'pixva' ) . ' <input type="number" min="0" dir="ltr" name="pixva_pricing[inspection][max]" value="' . esc_attr( (string) $p['inspection']['max'] ) . '"></label><p class="description">' . esc_html__( 'صفر = نمایش داده نمی‌شود.', 'pixva' ) . '</p></td></tr>';
	echo '<tr><th scope="row"><label for="pixva-disc">' . esc_html__( 'توضیح زیر هر برآورد', 'pixva' ) . '</label></th><td><textarea id="pixva-disc" class="large-text" rows="2" name="pixva_pricing[disclaimer]">' . esc_textarea( $p['disclaimer'] ) . '</textarea></td></tr>';
	echo '</tbody></table>';

	echo '<h2>' . esc_html__( 'خدمات و بازه قیمت', 'pixva' ) . '</h2><table class="widefat striped pixva-rows"><thead><tr><th>' . esc_html__( 'شناسه (لاتین)', 'pixva' ) . '</th><th>' . esc_html__( 'عنوان', 'pixva' ) . '</th><th>' . esc_html__( 'حداقل', 'pixva' ) . '</th><th>' . esc_html__( 'حداکثر', 'pixva' ) . '</th><th>' . esc_html__( 'ضریب اندازه', 'pixva' ) . '</th></tr></thead><tbody>';
	$rows = $p['services'];
	$i    = 0;
	foreach ( $rows + array(
		''   => null,
		' '  => null,
		'  ' => null,
	) as $key => $row ) {
		$row = $row ? $row : array(
			'label' => '',
			'min'   => '',
			'max'   => '',
			'sized' => false,
		);
		echo '<tr><td><input dir="ltr" size="10" aria-label="' . esc_attr__( 'شناسه', 'pixva' ) . '" name="pixva_pricing[services][' . (int) $i . '][key]" value="' . esc_attr( trim( (string) $key ) ) . '"></td>';
		echo '<td><input aria-label="' . esc_attr__( 'عنوان', 'pixva' ) . '" name="pixva_pricing[services][' . (int) $i . '][label]" value="' . esc_attr( $row['label'] ) . '"></td>';
		echo '<td><input type="number" min="0" dir="ltr" aria-label="' . esc_attr__( 'حداقل', 'pixva' ) . '" name="pixva_pricing[services][' . (int) $i . '][min]" value="' . esc_attr( (string) $row['min'] ) . '"></td>';
		echo '<td><input type="number" min="0" dir="ltr" aria-label="' . esc_attr__( 'حداکثر', 'pixva' ) . '" name="pixva_pricing[services][' . (int) $i . '][max]" value="' . esc_attr( (string) $row['max'] ) . '"></td>';
		echo '<td><input type="checkbox" aria-label="' . esc_attr__( 'اعمال ضریب اندازه', 'pixva' ) . '" name="pixva_pricing[services][' . (int) $i . '][sized]" value="1" ' . checked( ! empty( $row['sized'] ), true, false ) . '></td></tr>';
		++$i;
	}
	echo '</tbody></table><p class="description">' . esc_html__( 'برای حذف، عنوان را خالی کنید. برای افزودن ردیف بیشتر، ذخیره کنید تا ردیف خالی تازه اضافه شود.', 'pixva' ) . '</p>';

	echo '<h2>' . esc_html__( 'ضریب اندازه صفحه', 'pixva' ) . '</h2><table class="widefat striped pixva-rows"><thead><tr><th>' . esc_html__( 'شناسه', 'pixva' ) . '</th><th>' . esc_html__( 'عنوان (مثل «۴۳ تا ۵۵ اینچ»)', 'pixva' ) . '</th><th>' . esc_html__( 'ضریب', 'pixva' ) . '</th></tr></thead><tbody>';
	$i = 0;
	foreach ( $p['sizes'] + array(
		''  => null,
		' ' => null,
	) as $key => $row ) {
		$row = $row ? $row : array(
			'label'  => '',
			'factor' => '',
		);
		echo '<tr><td><input dir="ltr" size="10" aria-label="' . esc_attr__( 'شناسه', 'pixva' ) . '" name="pixva_pricing[sizes][' . (int) $i . '][key]" value="' . esc_attr( trim( (string) $key ) ) . '"></td><td><input aria-label="' . esc_attr__( 'عنوان', 'pixva' ) . '" name="pixva_pricing[sizes][' . (int) $i . '][label]" value="' . esc_attr( $row['label'] ) . '"></td><td><input dir="ltr" size="5" aria-label="' . esc_attr__( 'ضریب', 'pixva' ) . '" name="pixva_pricing[sizes][' . (int) $i . '][factor]" value="' . esc_attr( (string) $row['factor'] ) . '"></td></tr>';
		++$i;
	}
	echo '</tbody></table>';

	$brands = pixva_brand_choices();
	if ( $brands ) {
		echo '<h2>' . esc_html__( 'ضریب برند (اختیاری)', 'pixva' ) . '</h2><table class="widefat striped"><tbody>';
		foreach ( $brands as $id => $title ) {
			echo '<tr><td><label for="pixva-bf-' . (int) $id . '">' . esc_html( $title ) . '</label></td><td><input id="pixva-bf-' . (int) $id . '" dir="ltr" size="5" name="pixva_pricing[brands][' . (int) $id . ']" value="' . esc_attr( isset( $p['brands'][ $id ] ) ? (string) $p['brands'][ $id ] : '' ) . '" placeholder="1"></td></tr>';
		}
		echo '</tbody></table>';
	}
	submit_button();
	echo '</form></div>';
}

/**
 * Handle redirect add/toggle/delete.
 *
 * @return void
 */
function pixva_admin_redirect_action() {
	if ( ! current_user_can( 'pixva_manage_redirects' ) ) {
		wp_die( esc_html__( 'دسترسی مجاز نیست.', 'pixva' ), '', array( 'response' => 403 ) );
	}
	check_admin_referer( 'pixva_redirects' );
	$rules = pixva_get_redirects();
	$op    = sanitize_key( pixva_get_post_var( 'op' ) );
	$msg   = 'saved';
	if ( 'add' === $op ) {
		$res = pixva_redirect_upsert(
			array(
				'source'      => pixva_get_post_var( 'source' ),
				'destination' => pixva_get_post_var( 'destination' ),
				'status'      => (int) pixva_get_post_var( 'status' ),
				'notes'       => pixva_get_post_var( 'notes' ),
				'active'      => true,
			),
			$rules
		);
		if ( is_wp_error( $res ) ) {
			set_transient( 'pixva_redirect_error_' . get_current_user_id(), $res->get_error_message(), 60 );
			$msg = 'error';
		}
	} elseif ( in_array( $op, array( 'toggle', 'delete' ), true ) ) {
		$id = sanitize_key( pixva_get_post_var( 'id' ) );
		foreach ( $rules as $i => $r ) {
			if ( $r['id'] !== $id ) {
				continue;
			}
			if ( 'delete' === $op ) {
				unset( $rules[ $i ] );
			} elseif ( empty( $r['active'] ) ) {
				$tmp = $rules;
				unset( $tmp[ $i ] );
				$r['active'] = true;
				$res         = pixva_redirect_upsert( $r, $tmp );
				if ( is_wp_error( $res ) ) {
					set_transient( 'pixva_redirect_error_' . get_current_user_id(), $res->get_error_message(), 60 );
					$msg = 'error';
				} else {
					$rules = $tmp;
				}
			} else {
				$rules[ $i ]['active'] = false;
			}
		}
	}
	if ( 'error' !== $msg ) {
		update_option( 'pixva_redirects', array_values( $rules ), false );
	}
	wp_safe_redirect( admin_url( 'admin.php?page=pixva-redirects&msg=' . $msg ) );
	exit;
}
add_action( 'admin_post_pixva_redirects', 'pixva_admin_redirect_action' );

/**
 * Redirects screen.
 *
 * @return void
 */
function pixva_admin_redirects() {
	if ( ! current_user_can( 'pixva_manage_redirects' ) ) {
		wp_die( esc_html__( 'دسترسی مجاز نیست.', 'pixva' ) );
	}
	echo '<div class="wrap pixva-admin"><h1>' . esc_html__( 'انتقال‌ها (ریدایرکت و ۴۱۰)', 'pixva' ) . '</h1>';
	$msg = sanitize_key( pixva_get_request_var( 'msg' ) );
	if ( 'saved' === $msg ) {
		echo '<div class="notice notice-success"><p>' . esc_html__( 'ذخیره شد.', 'pixva' ) . '</p></div>';
	} elseif ( 'error' === $msg ) {
		$err = (string) get_transient( 'pixva_redirect_error_' . get_current_user_id() );
		echo '<div class="notice notice-error"><p>' . esc_html( $err ? $err : __( 'خطا در ذخیره.', 'pixva' ) ) . '</p></div>';
	}
	echo '<p>' . esc_html__( 'هر انتقال یک‌مرحله‌ای است: اگر مقصد خودش منتقل شده باشد، به مقصد نهایی وصل می‌شود و حلقه‌ها رد می‌شوند.', 'pixva' ) . '</p>';
	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" class="pixva-redirect-form"><input type="hidden" name="action" value="pixva_redirects"><input type="hidden" name="op" value="add">';
	wp_nonce_field( 'pixva_redirects' );
	echo '<table class="form-table" role="presentation"><tbody>';
	echo '<tr><th><label for="pr-src">' . esc_html__( 'مبدأ (مسیر)', 'pixva' ) . '</label></th><td><input id="pr-src" dir="ltr" class="regular-text" name="source" placeholder="/old-path/" required></td></tr>';
	echo '<tr><th><label for="pr-dst">' . esc_html__( 'مقصد', 'pixva' ) . '</label></th><td><input id="pr-dst" dir="ltr" class="regular-text" name="destination" placeholder="/new-path/"><p class="description">' . esc_html__( 'برای ۴۱۰ خالی بگذارید.', 'pixva' ) . '</p></td></tr>';
	echo '<tr><th><label for="pr-st">' . esc_html__( 'نوع', 'pixva' ) . '</label></th><td><select id="pr-st" name="status">';
	foreach ( pixva_redirect_statuses() as $code => $label ) {
		echo '<option value="' . esc_attr( (string) $code ) . '">' . esc_html( $label ) . '</option>';
	}
	echo '</select></td></tr>';
	echo '<tr><th><label for="pr-notes">' . esc_html__( 'یادداشت', 'pixva' ) . '</label></th><td><input id="pr-notes" class="regular-text" name="notes"></td></tr>';
	echo '</tbody></table>';
	submit_button( __( 'افزودن انتقال', 'pixva' ) );
	echo '</form>';

	$rules = pixva_get_redirects();
	echo '<h2>' . esc_html__( 'قوانین ثبت‌شده', 'pixva' ) . '</h2>';
	if ( ! $rules ) {
		echo '<p>' . esc_html__( 'هنوز انتقالی ثبت نشده است.', 'pixva' ) . '</p>';
	} else {
		echo '<table class="widefat striped"><thead><tr><th>' . esc_html__( 'مبدأ', 'pixva' ) . '</th><th>' . esc_html__( 'مقصد', 'pixva' ) . '</th><th>' . esc_html__( 'کد', 'pixva' ) . '</th><th>' . esc_html__( 'تاریخ', 'pixva' ) . '</th><th>' . esc_html__( 'یادداشت', 'pixva' ) . '</th><th>' . esc_html__( 'وضعیت', 'pixva' ) . '</th><th></th></tr></thead><tbody>';
		foreach ( $rules as $r ) {
			echo '<tr><td><code dir="ltr">' . esc_html( $r['source'] ) . '</code></td><td><code dir="ltr">' . esc_html( $r['destination'] ? $r['destination'] : '—' ) . '</code></td><td>' . esc_html( (string) $r['status'] ) . '</td><td>' . esc_html( pixva_format_date( (int) $r['created'] ) ) . '</td><td>' . esc_html( $r['notes'] ) . '</td><td>' . ( $r['active'] ? esc_html__( 'فعال', 'pixva' ) : esc_html__( 'غیرفعال', 'pixva' ) ) . '</td><td>';
			foreach ( array(
				'toggle' => $r['active'] ? __( 'غیرفعال کن', 'pixva' ) : __( 'فعال کن', 'pixva' ),
				'delete' => __( 'حذف', 'pixva' ),
			) as $op => $label ) {
				echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" style="display:inline"><input type="hidden" name="action" value="pixva_redirects"><input type="hidden" name="op" value="' . esc_attr( $op ) . '"><input type="hidden" name="id" value="' . esc_attr( $r['id'] ) . '">';
				wp_nonce_field( 'pixva_redirects' );
				echo '<button type="submit" class="button-link' . ( 'delete' === $op ? ' button-link-delete' : '' ) . '">' . esc_html( $label ) . '</button></form> ';
			}
			echo '</td></tr>';
		}
		echo '</tbody></table>';
	}
	echo '<h2>' . esc_html__( 'انتقال‌های سیستمی نسخه قبل', 'pixva' ) . '</h2><table class="widefat striped"><tbody>';
	foreach ( pixva_system_redirects() as $from => $route ) {
		echo '<tr><td><code dir="ltr">' . esc_html( $from ) . '</code></td><td>301 → <code dir="ltr">' . esc_html( pixva_routes()[ $route ]['path'] ) . '</code></td></tr>';
	}
	foreach ( pixva_system_prefix_redirects() as $from => $to ) {
		echo '<tr><td><code dir="ltr">' . esc_html( $from ) . '*</code></td><td>301 → <code dir="ltr">' . esc_html( $to ) . '*</code></td></tr>';
	}
	foreach ( pixva_system_gone()['prefix'] as $g ) {
		echo '<tr><td><code dir="ltr">' . esc_html( $g ) . '*</code></td><td>410</td></tr>';
	}
	echo '</tbody></table></div>';
}

/**
 * Migration log screen.
 *
 * @return void
 */
function pixva_admin_migration() {
	if ( ! current_user_can( 'pixva_manage_settings' ) ) {
		wp_die( esc_html__( 'دسترسی مجاز نیست.', 'pixva' ) );
	}
	$log = get_option( 'pixva_migration_log', array() );
	echo '<div class="wrap pixva-admin"><h1>' . esc_html__( 'گزارش ارتقا به نسخه ۲', 'pixva' ) . '</h1>';
	if ( ! $log ) {
		echo '<p>' . esc_html__( 'ارتقایی انجام نشده یا تغییری لازم نبوده است.', 'pixva' ) . '</p></div>';
		return;
	}
	echo '<p>' . esc_html__( 'هیچ محتوایی حذف نشده است؛ موارد ساختگی یا قدیمی به پیش‌نویس منتقل شده‌اند و قابل بازگردانی هستند.', 'pixva' ) . '</p><table class="widefat striped"><thead><tr><th>' . esc_html__( 'اقدام', 'pixva' ) . '</th><th>' . esc_html__( 'مورد', 'pixva' ) . '</th><th>' . esc_html__( 'دلیل', 'pixva' ) . '</th></tr></thead><tbody>';
	foreach ( (array) $log as $row ) {
		$link = ! empty( $row['id'] ) ? get_edit_post_link( (int) $row['id'] ) : '';
		echo '<tr><td>' . esc_html( $row['action'] ?? '' ) . '</td><td>' . ( $link ? '<a href="' . esc_url( $link ) . '">' . esc_html( $row['item'] ?? '' ) . '</a>' : esc_html( $row['item'] ?? '' ) ) . '</td><td>' . esc_html( $row['reason'] ?? '' ) . '</td></tr>';
	}
	echo '</tbody></table></div>';
}

/**
 * One gentle admin notice while essential claims are missing.
 *
 * @return void
 */
function pixva_admin_setup_notice() {
	$screen = get_current_screen();
	if ( ! $screen || 'dashboard' !== $screen->id || ! current_user_can( 'pixva_manage_settings' ) ) {
		return;
	}
	if ( pixva_has_claim( 'phone' ) || pixva_has_claim( 'mobile' ) || pixva_has_claim( 'email' ) ) {
		return;
	}
	echo '<div class="notice notice-info"><p>' . esc_html__( 'پیکسوا: هنوز راه تماسی ثبت نشده است؛ دکمه تماس و اطلاعات فوتر تا آن زمان نمایش داده نمی‌شوند.', 'pixva' ) . ' <a href="' . esc_url( admin_url( 'admin.php?page=pixva-business' ) ) . '">' . esc_html__( 'تکمیل اطلاعات', 'pixva' ) . '</a></p></div>';
}
add_action( 'admin_notices', 'pixva_admin_setup_notice' );

/**
 * Published WordPress install samples that were never edited (the theme
 * never deletes content; it only reports them).
 *
 * @return int[] Post IDs.
 */
function pixva_core_sample_content() {
	$ids = array();
	foreach ( array( array( 'hello-world', 'post' ), array( 'sample-page', 'page' ) ) as $sample ) {
		$p = get_page_by_path( $sample[0], OBJECT, $sample[1] );
		if ( $p && 'publish' === $p->post_status && $p->post_date === $p->post_modified ) {
			$ids[] = (int) $p->ID;
		}
	}
	return $ids;
}

/*
 * ---------------------------------------------------------------------------
 * Phase 4: real-data stat cards, WP dashboard widgets, settings hub.
 * Every figure comes from the live DB; capability checks run server-side.
 * ---------------------------------------------------------------------------
 */

/**
 * Stat cards for the PIXVA admin overview (capability-scoped).
 *
 * @return void
 */
function pixva_admin_stat_cards() {
	$can_orders  = current_user_can( 'pixva_manage_orders' );
	$can_messages = current_user_can( 'edit_pixva_messages' );
	if ( ! $can_orders && ! $can_messages ) {
		return;
	}
	echo '<div class="pixva-stat-cards">';
	if ( $can_orders ) {
		$counts = pixva_order_status_counts();
		$status = pixva_order_statuses();
		$total  = array_sum( $counts );
		$new7   = count(
			pixva_order_query(
				array(
					'posts_per_page' => 100,
					'date_query'     => array(
						array(
							'after' => '7 days ago',
						),
					),
				)
			)
		);
		echo '<div class="pixva-stat-card"><span class="pixva-stat-card__label">' . esc_html__( 'همه درخواست‌ها', 'pixva' ) . '</span><span class="pixva-stat-card__value">' . esc_html( pixva_fa_num( $total ) ) . '</span><a href="' . esc_url( admin_url( 'edit.php?post_type=pixva_orders' ) ) . '">' . esc_html__( 'مدیریت درخواست‌ها', 'pixva' ) . '</a></div>';
		echo '<div class="pixva-stat-card"><span class="pixva-stat-card__label">' . esc_html__( 'ثبت‌شده در ۷ روز اخیر', 'pixva' ) . '</span><span class="pixva-stat-card__value">' . esc_html( pixva_fa_num( $new7 ) ) . '</span></div>';
		$open = 0;
		foreach ( $counts as $k => $c ) {
			if ( ! in_array( $k, array( 'delivered', 'cancelled' ), true ) ) {
				$open += $c;
			}
		}
		echo '<div class="pixva-stat-card"><span class="pixva-stat-card__label">' . esc_html__( 'در جریان (بسته‌نشده)', 'pixva' ) . '</span><span class="pixva-stat-card__value">' . esc_html( pixva_fa_num( $open ) ) . '</span></div>';
	}
	if ( $can_messages ) {
		$unread = (int) wp_count_posts( 'pixva_inbox' )->private + (int) wp_count_posts( 'pixva_inbox' )->publish;
		echo '<div class="pixva-stat-card"><span class="pixva-stat-card__label">' . esc_html__( 'پیام‌های رسیده', 'pixva' ) . '</span><span class="pixva-stat-card__value">' . esc_html( pixva_fa_num( $unread ) ) . '</span><a href="' . esc_url( admin_url( 'edit.php?post_type=pixva_inbox' ) ) . '">' . esc_html__( 'خواندن پیام‌ها', 'pixva' ) . '</a></div>';
	}
	echo '</div>';
}

/**
 * Settings hub: single categorized entry point that links to the real
 * settings screens (no duplicated/conflicting fields — brand identity and
 * colors/fonts stay in WordPress «هویت سایت» and Elementor «تنظیمات سایت»).
 *
 * @return void
 */
function pixva_admin_hub() {
	if ( ! current_user_can( 'pixva_view_dashboard' ) ) {
		wp_die( esc_html__( 'دسترسی غیرمجاز.', 'pixva' ) );
	}
	$a = pixva_announcement_get();
	?>
	<div class="wrap pixva-admin">
		<h1><?php esc_html_e( 'پیکسوا — تنظیمات و راهنما', 'pixva' ); ?></h1>
		<p><?php esc_html_e( 'هر دسته به صفحه واقعی خودش می‌رود؛ هیچ تنظیمی در دو جای متناقض تکرار نشده است.', 'pixva' ); ?></p>

		<h2><?php esc_html_e( 'شروع سریع', 'pixva' ); ?></h2>
		<ol class="pixva-hub-steps">
			<li><?php echo wp_kses_post( sprintf( /* translators: link to business settings. */ __( '%s — راه تماس، نشانی، ساعت کاری و سیاست گارانتی را واقعی وارد کنید.', 'pixva' ), '<a href="' . esc_url( admin_url( 'admin.php?page=pixva-business' ) ) . '">' . esc_html__( 'اطلاعات کسب‌وکار', 'pixva' ) . '</a>' ) ); ?></li>
			<li><?php echo wp_kses_post( sprintf( /* translators: link to content list. */ __( '%s — خدمات، برندها و پرسش‌های متداول را منتشر کنید (بدون محتوای ساختگی).', 'pixva' ), '<a href="' . esc_url( admin_url( 'edit.php?post_type=tv_services' ) ) . '">' . esc_html__( 'فهرست محتوا', 'pixva' ) . '</a>' ) ); ?></li>
			<li><?php echo wp_kses_post( sprintf( /* translators: link to front page editor. */ __( '%s — صفحه اول را با ویجت‌های PIXVA یا Elementor بچینید.', 'pixva' ), '<a href="' . esc_url( pixva_hub_elementor_url() ) . '">' . esc_html__( 'ویرایش صفحه اول', 'pixva' ) . '</a>' ) ); ?></li>
			<li><?php echo wp_kses_post( sprintf( /* translators: link to booking page. */ __( '%s — یک درخواست آزمایشی ثبت و کد پیگیری را در صفحه پیگیری تست کنید.', 'pixva' ), '<a href="' . esc_url( pixva_route_url( 'booking' ) ) . '">' . esc_html__( 'صفحه رزرو', 'pixva' ) . '</a>' ) ); ?></li>
		</ol>

		<h2><?php esc_html_e( 'دسته‌های تنظیمات', 'pixva' ); ?></h2>
		<div class="pixva-hub-grid">
			<div class="pixva-hub-card"><h3><?php esc_html_e( 'کسب‌وکار و محتوا', 'pixva' ); ?></h3>
				<ul>
					<li><a href="<?php echo esc_url( admin_url( 'admin.php?page=pixva-business' ) ); ?>"><?php esc_html_e( 'اطلاعات کسب‌وکار', 'pixva' ); ?></a></li>
					<li><a href="<?php echo esc_url( admin_url( 'admin.php?page=pixva-pricing' ) ); ?>"><?php esc_html_e( 'قیمت‌گذاری', 'pixva' ); ?></a></li>
					<li><a href="<?php echo esc_url( admin_url( 'edit.php?post_type=pixva_faq' ) ); ?>"><?php esc_html_e( 'پرسش‌های متداول', 'pixva' ); ?></a></li>
					<li><a href="<?php echo esc_url( admin_url( 'edit.php?post_type=tv_services' ) ); ?>"><?php esc_html_e( 'خدمات و برندها', 'pixva' ); ?></a></li>
				</ul>
			</div>
			<div class="pixva-hub-card"><h3><?php esc_html_e( 'ظاهر و ویرایش بصری', 'pixva' ); ?></h3>
				<ul>
					<li><a href="<?php echo esc_url( pixva_hub_elementor_url() ); ?>"><?php esc_html_e( 'ویرایشگر صفحه اول (Elementor در صورت فعال بودن)', 'pixva' ); ?></a></li>
					<li><a href="<?php echo esc_url( admin_url( 'options-general.php' ) ); ?>"><?php esc_html_e( 'هویت سایت: نام، توصیف، لوگو (وردپرس)', 'pixva' ); ?></a></li>
					<li><span class="description"><?php esc_html_e( 'رنگ‌ها و فونت‌ها: «تنظیمات سایت» داخل ویرایشگر Elementor + theme.json قالب (یک منبع واحد؛ بدون تکرار).', 'pixva' ); ?></span></li>
				</ul>
			</div>
			<div class="pixva-hub-card"><h3><?php esc_html_e( 'فرم‌ها و اعلان‌ها', 'pixva' ); ?></h3>
				<ul>
					<li><a href="<?php echo esc_url( admin_url( 'admin.php?page=pixva#pixva-announcement' ) ); ?>"><?php esc_html_e( 'نوار اعلان سایت', 'pixva' ); ?></a></li>
					<li><a href="<?php echo esc_url( admin_url( 'edit.php?post_type=pixva_inbox' ) ); ?>"><?php esc_html_e( 'پیام‌های رسیده', 'pixva' ); ?></a></li>
					<li><span class="description"><?php esc_html_e( 'گیرنده ایمیل اعلان از گزینه «مدیر» در تنظیمات عمومی وردپرس خوانده می‌شود.', 'pixva' ); ?></span></li>
				</ul>
			</div>
			<div class="pixva-hub-card"><h3><?php esc_html_e( 'سیستم و سلامت', 'pixva' ); ?></h3>
				<ul>
					<li><a href="<?php echo esc_url( admin_url( 'admin.php?page=pixva' ) ); ?>"><?php esc_html_e( 'وضعیت راه‌اندازی و چک‌لیست', 'pixva' ); ?></a></li>
					<li><a href="<?php echo esc_url( admin_url( 'admin.php?page=pixva-redirects' ) ); ?>"><?php esc_html_e( 'انتقال‌ها (ریدایرکت)', 'pixva' ); ?></a></li>
					<li><a href="<?php echo esc_url( admin_url( 'admin.php?page=pixva-migration' ) ); ?>"><?php esc_html_e( 'گزارش ارتقا', 'pixva' ); ?></a></li>
					<li><a href="<?php echo esc_url( admin_url( 'options-permalink.php' ) ); ?>"><?php esc_html_e( 'ساختار پیوندها', 'pixva' ); ?></a></li>
				</ul>
			</div>
		</div>

		<?php if ( current_user_can( 'pixva_manage_settings' ) ) : ?>
		<h2 id="pixva-announcement"><?php esc_html_e( 'نوار اعلان سایت', 'pixva' ); ?></h2>
		<p class="description"><?php esc_html_e( 'متن کوتاهی که بالای همه صفحه‌ها نمایش داده می‌شود. خالی = بدون اعلان. این متن هرگز ساخته نمی‌شود؛ فقط شما آن را می‌نویسید.', 'pixva' ); ?></p>
		<form method="post" action="<?php echo esc_url( admin_url( 'options.php' ) ); ?>">
			<?php settings_fields( 'pixva_announcement' ); ?>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="pixva-ann-enabled"><?php esc_html_e( 'فعال', 'pixva' ); ?></label></th>
					<td><input type="checkbox" id="pixva-ann-enabled" name="pixva_announcement[enabled]" value="1" <?php checked( $a['enabled'], 1 ); ?>></td>
				</tr>
				<tr>
					<th scope="row"><label for="pixva-ann-text"><?php esc_html_e( 'متن اعلان', 'pixva' ); ?></label></th>
					<td><textarea id="pixva-ann-text" name="pixva_announcement[text]" rows="3" class="large-text"><?php echo esc_textarea( $a['text'] ); ?></textarea></td>
				</tr>
				<tr>
					<th scope="row"><label for="pixva-ann-url"><?php esc_html_e( 'لینک (اختیاری)', 'pixva' ); ?></label></th>
					<td><input type="url" id="pixva-ann-url" name="pixva_announcement[url]" class="large-text" dir="ltr" value="<?php echo esc_attr( $a['url'] ); ?>"></td>
				</tr>
			</table>
			<?php submit_button( __( 'ذخیره اعلان', 'pixva' ) ); ?>
		</form>
		<?php endif; ?>
	</div>
	<?php
}

/**
 * Best editor URL for the front page (Elementor when active, else WP editor).
 *
 * @return string
 */
function pixva_hub_elementor_url() {
	$front = (int) get_option( 'page_on_front' );
	if ( $front ) {
		$url = get_edit_post_link( $front, 'raw' );
		if ( $url ) {
			return ( pixva_elementor_active() ) ? add_query_arg( 'action', 'elementor', $url ) : $url;
		}
	}
	return admin_url( 'edit.php?post_type=page' );
}

/**
 * WP admin home dashboard widgets (capability-scoped; no PII beyond what
 * each role is allowed to see).
 *
 * @return void
 */
function pixva_dashboard_widgets() {
	if ( current_user_can( 'pixva_view_dashboard' ) ) {
		wp_add_dashboard_widget( 'pixva_dashboard_health', __( 'پیکسوا — سلامت پیکربندی', 'pixva' ), 'pixva_dashboard_widget_health' );
	}
	if ( current_user_can( 'pixva_manage_orders' ) ) {
		wp_add_dashboard_widget( 'pixva_dashboard_orders', __( 'پیکسوا — آخرین درخواست‌ها', 'pixva' ), 'pixva_dashboard_widget_orders' );
	}
	if ( current_user_can( 'edit_pixva_messages' ) ) {
		wp_add_dashboard_widget( 'pixva_dashboard_messages', __( 'پیکسوا — پیام‌های جدید', 'pixva' ), 'pixva_dashboard_widget_messages' );
	}
}
add_action( 'wp_dashboard_setup', 'pixva_dashboard_widgets' );

/**
 * Config-health widget (mirrors the overview checklist, real state only).
 *
 * @return void
 */
function pixva_dashboard_widget_health() {
	$rows = array(
		array( pixva_has_claim( 'phone' ) || pixva_has_claim( 'mobile' ) || pixva_has_claim( 'email' ), __( 'راه تماس ثبت شده', 'pixva' ) ),
		array( (bool) pixva_service_modes(), __( 'شیوه‌های تحویل دستگاه', 'pixva' ) ),
		array( pixva_warranty_policy_days() > 0, __( 'سیاست گارانتی', 'pixva' ) ),
		array( (bool) get_privacy_policy_url(), __( 'صفحه حریم خصوصی', 'pixva' ) ),
		array( ! pixva_core_sample_content(), __( 'بدون محتوای نمونه وردپرس', 'pixva' ) ),
	);
	echo '<ul class="pixva-dash-health">';
	foreach ( $rows as $r ) {
		echo '<li>' . ( $r[0] ? '<span class="dashicons dashicons-yes-alt" aria-hidden="true"></span>' : '<span class="dashicons dashicons-warning" aria-hidden="true"></span>' ) . ' ' . esc_html( $r[1] ) . '</li>';
	}
	echo '</ul><p><a href="' . esc_url( admin_url( 'admin.php?page=pixva' ) ) . '">' . esc_html__( 'چک‌لیست کامل', 'pixva' ) . '</a></p>';
}

/**
 * Recent-orders widget (codes/status only — no customer PII here).
 *
 * @return void
 */
function pixva_dashboard_widget_orders() {
	$ids     = pixva_order_query( array( 'posts_per_page' => 5 ) );
	$counts  = pixva_order_status_counts();
	$status  = pixva_order_statuses();
	echo '<p>';
	foreach ( $counts as $k => $c ) {
		if ( $c > 0 && isset( $status[ $k ] ) ) {
			echo '<span class="pixva-chip">' . esc_html( $status[ $k ]['label'] ) . ' — ' . esc_html( pixva_fa_num( $c ) ) . '</span> ';
		}
	}
	echo '</p>';
	if ( ! $ids ) {
		echo '<p>' . esc_html__( 'هنوز درخواستی ثبت نشده است.', 'pixva' ) . '</p>';
		return;
	}
	echo '<ul>';
	foreach ( $ids as $id ) {
		$p    = get_post( $id );
		$st   = (string) get_post_meta( $id, '_pixva_order_status', true );
		$label = isset( $status[ $st ] ) ? $status[ $st ]['label'] : $st;
		echo '<li><a href="' . esc_url( get_edit_post_link( $id ) ) . '">' . esc_html( get_the_title( $p ) ) . '</a> — <span class="pixva-chip">' . esc_html( $label ) . '</span> <span class="description">' . esc_html( get_the_date( '', $p ) ) . '</span></li>';
	}
	echo '</ul><p><a href="' . esc_url( admin_url( 'edit.php?post_type=pixva_orders' ) ) . '">' . esc_html__( 'همه درخواست‌ها', 'pixva' ) . '</a></p>';
}

/**
 * New-messages widget (titles + dates for authorized staff only).
 *
 * @return void
 */
function pixva_dashboard_widget_messages() {
	$items = get_posts(
		array(
			'post_type'      => 'pixva_inbox',
			'post_status'    => array( 'private', 'publish' ),
			'posts_per_page' => 5,
			'orderby'        => 'date',
			'order'          => 'DESC',
		)
	);
	if ( ! $items ) {
		echo '<p>' . esc_html__( 'پیام جدیدی نیست.', 'pixva' ) . '</p>';
		return;
	}
	echo '<ul>';
	foreach ( $items as $m ) {
		echo '<li><a href="' . esc_url( get_edit_post_link( $m->ID ) ) . '">' . esc_html( get_the_title( $m ) ) . '</a> <span class="description">' . esc_html( get_the_date( '', $m ) ) . '</span></li>';
	}
	echo '</ul><p><a href="' . esc_url( admin_url( 'edit.php?post_type=pixva_inbox' ) ) . '">' . esc_html__( 'همه پیام‌ها', 'pixva' ) . '</a></p>';
}

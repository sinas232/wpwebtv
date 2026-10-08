<?php
/**
 * لایه محصول پیکسوا: تشخیص، درخواست، پیگیری، گارانتی، حساب مشتری، میز تکنسین.
 *
 * داده ساختگی تولید نمی‌شود. اگر پرونده‌ای نباشد، حالت خالی نشان داده می‌شود.
 *
 * @package Pixva
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * کارت‌های یابنده مشکل. کلید قیمت باید در موتور برآورد وجود داشته باشد.
 *
 * @return array<int, array<string, string>>
 */
function pixva_finder_items() {
	return array(
		array( 'key' => 'no_power', 'icon' => 'bolt', 'title' => __( 'روشن نمی‌شود', 'pixva' ), 'text' => __( 'اول برق و چراغ پاور. برد تغذیه را خودتان باز نکنید.', 'pixva' ) ),
		array( 'key' => 'no_picture', 'icon' => 'sun', 'title' => __( 'تصویر ندارد', 'pixva' ), 'text' => __( 'اگر صدا هست، معمولاً نور پس‌زمینه است نه تعویض پنل.', 'pixva' ) ),
		array( 'key' => 'no_sound', 'icon' => 'sound', 'title' => __( 'صدا ندارد', 'pixva' ), 'text' => __( 'برد صدا، فلت اسپیکر یا تنظیم پنل. اول همان مسیر.', 'pixva' ) ),
		array( 'key' => 'backlight', 'icon' => 'sun', 'title' => __( 'تصویر تاریک است', 'pixva' ), 'text' => __( 'هاله و تاریکی موضعی جدا از خط پنل بررسی می‌شود.', 'pixva' ) ),
		array( 'key' => 'lines', 'icon' => 'panel', 'title' => __( 'خطوط روی تصویر', 'pixva' ), 'text' => __( 'اگر شیشه سالم باشد، بندینگ مسیر قابل ضمانت است.', 'pixva' ) ),
		array( 'key' => 'blink', 'icon' => 'bolt', 'title' => __( 'خاموش و روشن می‌شود', 'pixva' ), 'text' => __( 'تعداد چشمک را بشمارید. تغذیه ولتاژ خطرناک دارد.', 'pixva' ) ),
		array( 'key' => 'panel', 'icon' => 'panel', 'title' => __( 'پیکسل سوخته', 'pixva' ), 'text' => __( 'تستر رنگ مشخص می‌کند پیکسل است یا نور پس‌زمینه.', 'pixva' ) ),
		array( 'key' => 'mainboard', 'icon' => 'cpu', 'title' => __( 'HDMI مشکل دارد', 'pixva' ), 'text' => __( 'کابل و ورودی را عوض کنید. اگر هیچ ورودی نیامد، مین‌برد است.', 'pixva' ) ),
		array( 'key' => 'mainboard', 'icon' => 'sound', 'title' => __( 'ریموت کار نمی‌کند', 'pixva' ), 'text' => __( 'اول باتری و سنسور جلو. اگر دکمه دستگاه هم مرده باشد، برد است.', 'pixva' ) ),
		array( 'key' => 'mainboard', 'icon' => 'cpu', 'title' => __( 'اتصال اینترنت مشکل دارد', 'pixva' ), 'text' => __( 'وای‌فای و شبکه جدا از خرابی تصویر بررسی می‌شود.', 'pixva' ) ),
	);
}

/**
 * آدرس صفحه یک مشکل. اگر اصطلاح وجود داشته باشد همان، وگرنه تشخیص.
 *
 * @param string $key کلید مشکل.
 * @return string
 */
function pixva_problem_url( $key ) {
	$key  = sanitize_key( $key );
	$term = get_term_by( 'slug', $key, 'tv_problem' );
	if ( $term instanceof WP_Term ) {
		$link = get_term_link( $term );
		if ( ! is_wp_error( $link ) ) {
			return $link;
		}
	}
	return add_query_arg( 'problem', $key, pixva_page_url( 'diagnosis' ) );
}

/**
 * راهنمای قابل دفاع برای صفحه مشکل. قیمت اینجا نیست.
 *
 * @param string $key کلید.
 * @return array{check:string,avoid:string}
 */
function pixva_problem_guidance( $key ) {
	$map = array(
		'no_power'   => array(
			'check' => __( 'دوشاخه، محافظ و چراغ پاور را ببینید. تعداد چشمک را بشمارید.', 'pixva' ),
			'avoid' => __( 'برد پاور را باز نکنید. ولتاژ آن خطرناک است.', 'pixva' ),
		),
		'no_picture' => array(
			'check' => __( 'با چراغ‌قوه نزدیک صفحه ببینید تصویر خیلی کم‌رنگ هست یا نه.', 'pixva' ),
			'avoid' => __( 'پنل را به‌خاطر صفحه سیاه تعویض نکنید.', 'pixva' ),
		),
		'lines'      => array(
			'check' => __( 'ببینید خط با ضربه به قاب تکان می‌خورد یا ثابت است.', 'pixva' ),
			'avoid' => __( 'فلت پنل را خودتان نکشید.', 'pixva' ),
		),
	);
	$key = sanitize_key( $key );
	return isset( $map[ $key ] ) ? $map[ $key ] : array(
		'check' => __( 'برند، سایز و علامت را دقیق بنویسید تا مسیر تعمیر کوتاه شود.', 'pixva' ),
		'avoid' => __( 'قبل از تشخیص، قطعه عوض نکنید.', 'pixva' ),
	);
}

/**
 * آیا کاربر میز تعمیر را می‌بیند؟
 *
 * @return bool
 */
function pixva_user_can_repair_desk() {
	if ( ! is_user_logged_in() ) {
		return false;
	}
	$user = wp_get_current_user();
	return current_user_can( 'edit_posts' ) && ( current_user_can( 'manage_options' ) || in_array( 'pixva_technician', (array) $user->roles, true ) );
}

/**
 * پرونده‌های یک شماره.
 *
 * @param string $phone شماره نرمال‌شده.
 * @return WP_Post[]
 */
function pixva_orders_for_phone( $phone ) {
	$phone = pixva_normalize_mobile( $phone );
	if ( '' === $phone ) {
		return array();
	}
	return get_posts(
		array(
			'post_type'      => 'pixva_orders',
			'post_status'    => array( 'private', 'publish' ),
			'posts_per_page' => 20,
			'orderby'        => 'date',
			'order'          => 'DESC',
			'meta_key'       => '_pixva_order_phone', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'meta_value'     => $phone, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
		)
	);
}

/**
 * تاریخ پایان گارانتی یک پرونده، اگر ثبت شده باشد.
 *
 * @param int $order_id شناسه.
 * @return int
 */
function pixva_order_warranty_until( $order_id ) {
	return (int) get_post_meta( $order_id, '_pixva_warranty_until', true );
}

/**
 * اگر وضعیت به آماده یا تحویل برسد و گارانتی خالی باشد، ۱۸۰ روز ثبت می‌شود.
 *
 * @param int    $order_id شناسه.
 * @param string $status   وضعیت جدید.
 * @return void
 */
function pixva_maybe_open_warranty( $order_id, $status ) {
	if ( ! in_array( $status, array( 'ready', 'delivered' ), true ) ) {
		return;
	}
	if ( pixva_order_warranty_until( $order_id ) ) {
		return;
	}
	$start = time();
	update_post_meta( $order_id, '_pixva_warranty_start', $start );
	update_post_meta( $order_id, '_pixva_warranty_until', $start + ( 180 * DAY_IN_SECONDS ) );
}

/**
 * به‌روزرسانی وضعیت و زمان مرحله.
 *
 * @param int    $order_id شناسه.
 * @param string $status   کلید وضعیت.
 * @return bool
 */
function pixva_set_order_status( $order_id, $status ) {
	$statuses = pixva_order_statuses();
	if ( ! isset( $statuses[ $status ] ) ) {
		return false;
	}
	update_post_meta( $order_id, '_pixva_order_status', $status );
	$steps = json_decode( (string) get_post_meta( $order_id, '_pixva_order_steps', true ), true );
	if ( ! is_array( $steps ) ) {
		$steps = array();
	}
	$steps[ $status ] = time();
	update_post_meta( $order_id, '_pixva_order_steps', wp_json_encode( $steps ) );
	pixva_maybe_open_warranty( $order_id, $status );
	return true;
}

/**
 * استعلام گارانتی. مثل پیگیری، کد و شماره هر دو لازم است.
 *
 * @return void
 */
function pixva_ajax_warranty_lookup() {
	pixva_ajax_guard( 'pixva_tracking_nonce', 'warranty', 20, HOUR_IN_SECONDS );
	$code  = strtoupper( preg_replace( '/[^A-Z0-9\\-]/', '', pixva_get_post_var( 'code' ) ) );
	$phone = pixva_normalize_mobile( pixva_get_post_var( 'phone' ) );
	if ( ! preg_match( '/^PXV-[A-Z0-9]+(?:-[A-Z0-9]+)*$/', $code ) || ! pixva_is_valid_iranian_mobile( $phone ) ) {
		pixva_ajax_error( __( 'کد پیگیری و شماره همراه ثبت‌شده را دقیق وارد کنید.', 'pixva' ), 422 );
	}
	$order = pixva_find_order( $code, $phone );
	if ( ! $order instanceof WP_Post ) {
		pixva_ajax_error( __( 'پرونده‌ای با این مشخصات پیدا نشد.', 'pixva' ), 404 );
	}
	$until = pixva_order_warranty_until( $order->ID );
	pixva_ajax_success(
		array(
			'found'    => true,
			'code'     => (string) get_post_meta( $order->ID, '_pixva_order_code', true ),
			'device'   => trim( get_post_meta( $order->ID, '_pixva_order_brand', true ) . ' ' . get_post_meta( $order->ID, '_pixva_order_model', true ) ),
			'service'  => (string) get_post_meta( $order->ID, '_pixva_order_problem', true ),
			'issued'   => $until > 0,
			'valid'    => $until > time(),
			'until'    => $until ? pixva_fa_num( wp_date( 'Y/m/d', $until ) ) : '',
			'message'  => $until
				? ( $until > time()
					? __( 'گارانتی این پرونده هنوز معتبر است.', 'pixva' )
					: __( 'مهلت گارانتی این پرونده تمام شده است.', 'pixva' ) )
				: __( 'برگه گارانتی هنوز در این پرونده ثبت نشده. بعد از آماده‌شدن دستگاه، ۱۸۰ روز در پرونده نوشته می‌شود.', 'pixva' ),
		)
	);
}

/**
 * تصویر اختیاری پرونده. فقط اگر کد و شماره با همان پرونده جور باشد.
 *
 * @return void
 */
function pixva_ajax_order_photo() {
	pixva_ajax_guard( 'pixva_order_nonce', 'order_photo', 8, HOUR_IN_SECONDS );
	$code  = strtoupper( preg_replace( '/[^A-Z0-9\\-]/', '', pixva_get_post_var( 'code' ) ) );
	$phone = pixva_normalize_mobile( pixva_get_post_var( 'phone' ) );
	$order = pixva_find_order( $code, $phone );
	if ( ! $order instanceof WP_Post ) {
		pixva_ajax_error( __( 'پرونده برای پیوست تصویر پیدا نشد.', 'pixva' ), 404 );
	}
	if ( empty( $_FILES['photo']['name'] ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		pixva_ajax_error( __( 'تصویری انتخاب نشده است.', 'pixva' ), 422 );
	}
	$file = $_FILES['photo']; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	if ( ! empty( $file['size'] ) && (int) $file['size'] > 4 * MB_IN_BYTES ) {
		pixva_ajax_error( __( 'حجم تصویر باید کمتر از ۴ مگابایت باشد.', 'pixva' ), 422 );
	}
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';
	$allowed = array( 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp' );
	$upload  = wp_handle_upload( $file, array( 'test_form' => false, 'mimes' => $allowed ) );
	if ( isset( $upload['error'] ) ) {
		pixva_ajax_error( __( 'تصویر ذخیره نشد. فقط JPG، PNG یا WEBP بفرستید.', 'pixva' ), 422 );
	}
	$attachment = wp_insert_attachment(
		array(
			'post_mime_type' => $upload['type'],
			'post_title'     => sanitize_file_name( basename( $upload['file'] ) ),
			'post_status'    => 'private',
			'post_parent'    => $order->ID,
		),
		$upload['file'],
		$order->ID,
		true
	);
	if ( is_wp_error( $attachment ) ) {
		pixva_ajax_error( __( 'پیوست پرونده ساخته نشد.', 'pixva' ), 500 );
	}
	$photos   = get_post_meta( $order->ID, '_pixva_order_photos', true );
	$photos   = is_array( $photos ) ? $photos : array();
	$photos[] = (int) $attachment;
	update_post_meta( $order->ID, '_pixva_order_photos', $photos );
	pixva_ajax_success( array( 'message' => __( 'تصویر به پرونده اضافه شد.', 'pixva' ) ) );
}

/**
 * تغییر وضعیت از میز تکنسین.
 *
 * @return void
 */
function pixva_handle_tech_status() {
	if ( ! pixva_user_can_repair_desk() ) {
		wp_die( esc_html__( 'دسترسی میز تعمیر را ندارید.', 'pixva' ) );
	}
	check_admin_referer( 'pixva_tech_status' );
	$order_id = isset( $_POST['order_id'] ) ? (int) $_POST['order_id'] : 0;
	$status   = isset( $_POST['status'] ) ? sanitize_key( wp_unslash( $_POST['status'] ) ) : '';
	$note     = isset( $_POST['note'] ) ? sanitize_textarea_field( wp_unslash( $_POST['note'] ) ) : '';
	$order    = get_post( $order_id );
	if ( ! $order instanceof WP_Post || 'pixva_orders' !== $order->post_type ) {
		wp_safe_redirect( pixva_page_url( 'technician' ) );
		exit;
	}
	pixva_set_order_status( $order_id, $status );
	if ( '' !== $note ) {
		update_post_meta( $order_id, '_pixva_order_notes', $note );
	}
	wp_safe_redirect( add_query_arg( 'updated', '1', pixva_page_url( 'technician' ) ) );
	exit;
}
add_action( 'admin_post_pixva_tech_status', 'pixva_handle_tech_status' );

/**
 * ذخیره شماره و دستگاه در حساب مشتری.
 *
 * @return void
 */
function pixva_handle_account_save() {
	if ( ! is_user_logged_in() ) {
		wp_die( esc_html__( 'ابتدا وارد شوید.', 'pixva' ) );
	}
	check_admin_referer( 'pixva_account_save' );
	$user_id = get_current_user_id();
	$phone   = pixva_normalize_mobile( isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '' );
	if ( '' !== $phone && ! pixva_is_valid_iranian_mobile( $phone ) ) {
		wp_safe_redirect( add_query_arg( 'account_error', 'phone', pixva_page_url( 'account' ) ) );
		exit;
	}
	if ( '' !== $phone ) {
		update_user_meta( $user_id, 'pixva_phone', $phone );
	}
	$brand = isset( $_POST['device_brand'] ) ? sanitize_text_field( wp_unslash( $_POST['device_brand'] ) ) : '';
	$model = isset( $_POST['device_model'] ) ? sanitize_text_field( wp_unslash( $_POST['device_model'] ) ) : '';
	if ( '' !== $brand && '' !== $model ) {
		$devices   = get_user_meta( $user_id, 'pixva_devices', true );
		$devices   = is_array( $devices ) ? $devices : array();
		$devices[] = array(
			'brand' => $brand,
			'model' => $model,
			'size'  => isset( $_POST['device_size'] ) ? sanitize_text_field( wp_unslash( $_POST['device_size'] ) ) : '',
		);
		update_user_meta( $user_id, 'pixva_devices', array_slice( $devices, -8 ) );
	}
	wp_safe_redirect( pixva_page_url( 'account' ) );
	exit;
}
add_action( 'admin_post_pixva_account_save', 'pixva_handle_account_save' );

/**
 * مسیرهای خوانا، بدون شکستن آدرس‌های قبلی.
 *
 * @return void
 */
function pixva_platform_rewrites() {
	add_rewrite_rule( '^problems/?$', 'index.php?pagename=problems', 'top' );
	add_rewrite_rule( '^problems/([^/]+)/?$', 'index.php?tv_problem=$matches[1]', 'top' );
	add_rewrite_rule( '^tools/diagnosis/?$', 'index.php?pagename=diagnosis', 'top' );
	add_rewrite_rule( '^tools/price-calculator/?$', 'index.php?pagename=calculator', 'top' );
	add_rewrite_rule( '^tools/error-codes/?$', 'index.php?pagename=error-codes', 'top' );
	add_rewrite_rule( '^portfolio/?$', 'index.php?post_type=repair_cases', 'top' );
}
add_action( 'init', 'pixva_platform_rewrites' );

/**
 * ویزارد تشخیص.
 *
 * @return void
 */
function pixva_render_diagnosis() {
	$labels  = pixva_calculator_labels();
	$preset  = isset( $_GET['problem'] ) ? sanitize_key( wp_unslash( $_GET['problem'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$brands  = $labels['brand'];
	$problems = pixva_problem_catalog();
	?>
	<div class="px-wizard" data-px-diagnosis data-preset-problem="<?php echo esc_attr( $preset ); ?>">
		<p class="px-wizard__progress" data-progress><?php esc_html_e( '۱ / ۴', 'pixva' ); ?></p>
		<div data-step data-key="brand">
			<h3><?php esc_html_e( 'برند دستگاه', 'pixva' ); ?></h3>
			<div class="pixva-choice-grid">
				<?php foreach ( array_slice( $brands, 0, 12, true ) as $key => $label ) : ?>
					<button type="button" data-choice data-key="brand" data-value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></button>
				<?php endforeach; ?>
			</div>
		</div>
		<div data-step data-key="problem" hidden>
			<h3><?php esc_html_e( 'مشکل چیست؟', 'pixva' ); ?></h3>
			<div class="pixva-choice-grid">
				<?php foreach ( $problems as $key => $label ) : ?>
					<button type="button" data-choice data-key="problem" data-value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></button>
				<?php endforeach; ?>
			</div>
		</div>
		<div data-step data-key="size" hidden>
			<h3><?php esc_html_e( 'سایز و نوع صفحه', 'pixva' ); ?></h3>
			<div class="pixva-choice-grid">
				<?php foreach ( array( '32', '43', '50', '55', '65', '75' ) as $size ) : ?>
					<button type="button" data-choice data-key="size" data-value="<?php echo esc_attr( $size ); ?>"><?php echo esc_html( pixva_fa_num( $size ) . ' ' . __( 'اینچ', 'pixva' ) ); ?></button>
				<?php endforeach; ?>
			</div>
			<div class="pixva-choice-grid">
				<?php foreach ( pixva_tech_catalog() as $key => $label ) : ?>
					<button type="button" data-choice data-key="tech" data-value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></button>
				<?php endforeach; ?>
			</div>
		</div>
		<div data-step hidden>
			<h3><?php esc_html_e( 'مدل را اگر می‌دانید بنویسید', 'pixva' ); ?></h3>
			<label class="pixva-field"><span><?php esc_html_e( 'مدل', 'pixva' ); ?></span><input type="text" data-model placeholder="55AU7000"></label>
			<p class="pixva-muted"><?php esc_html_e( 'این تشخیص نهایی نیست. قیمت، بازه کارگاه است و بعد از بررسی دستگاه قطعی می‌شود.', 'pixva' ); ?></p>
			<button type="button" class="pixva-btn pixva-btn--cta" data-estimate><?php esc_html_e( 'دیدن برآورد', 'pixva' ); ?></button>
			<div data-result hidden></div>
		</div>
		<div class="px-wizard__nav">
			<button type="button" class="pixva-btn px-btn--ghost" data-back><?php esc_html_e( 'بازگشت', 'pixva' ); ?></button>
			<button type="button" class="pixva-btn pixva-btn--primary" data-next><?php esc_html_e( 'بعدی', 'pixva' ); ?></button>
		</div>
	</div>
	<?php
}

/**
 * فرم چندمرحله‌ای درخواست تعمیر.
 *
 * @return void
 */
function pixva_render_booking() {
	$labels = pixva_calculator_labels();
	$get    = static function ( $key ) {
		return isset( $_GET[ $key ] ) ? sanitize_text_field( wp_unslash( $_GET[ $key ] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	};
	?>
	<form class="px-wizard" data-px-booking novalidate>
		<?php pixva_honeypot_field(); ?>
		<p class="px-wizard__progress" data-progress><?php esc_html_e( '۱ / ۳', 'pixva' ); ?></p>
		<div data-step>
			<h3><?php esc_html_e( 'دستگاه', 'pixva' ); ?></h3>
			<label class="pixva-field"><span><?php esc_html_e( 'برند', 'pixva' ); ?></span>
				<select name="brand">
					<option value=""><?php esc_html_e( 'انتخاب کنید', 'pixva' ); ?></option>
					<?php foreach ( $labels['brand'] as $key => $label ) : ?>
						<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $get( 'brand' ), $key ); ?>><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select>
			</label>
			<label class="pixva-field"><span><?php esc_html_e( 'مشکل', 'pixva' ); ?></span>
				<select name="problem">
					<?php foreach ( $labels['problem'] as $key => $label ) : ?>
						<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $get( 'problem' ), $key ); ?>><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select>
			</label>
			<div class="px-split">
				<label class="pixva-field"><span><?php esc_html_e( 'سایز', 'pixva' ); ?></span>
					<select name="size">
						<?php foreach ( array( '32', '43', '50', '55', '65', '75' ) as $size ) : ?>
							<option value="<?php echo esc_attr( $size ); ?>" <?php selected( $get( 'size' ) ? $get( 'size' ) : '55', $size ); ?>><?php echo esc_html( pixva_fa_num( $size ) ); ?></option>
						<?php endforeach; ?>
					</select>
				</label>
				<label class="pixva-field"><span><?php esc_html_e( 'صفحه', 'pixva' ); ?></span>
					<select name="tech">
						<?php foreach ( pixva_tech_catalog() as $key => $label ) : ?>
							<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $get( 'tech' ) ? $get( 'tech' ) : 'led', $key ); ?>><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>
				</label>
			</div>
			<label class="pixva-field"><span><?php esc_html_e( 'مدل', 'pixva' ); ?></span><input type="text" name="model" value="<?php echo esc_attr( $get( 'model' ) ); ?>"></label>
		</div>
		<div data-step hidden>
			<h3><?php esc_html_e( 'هماهنگی', 'pixva' ); ?></h3>
			<label class="pixva-field"><span><?php esc_html_e( 'نام', 'pixva' ); ?></span><input type="text" name="customer_name" autocomplete="name"></label>
			<label class="pixva-field"><span><?php esc_html_e( 'شماره همراه', 'pixva' ); ?></span><input type="tel" name="phone" inputmode="numeric" autocomplete="tel" placeholder="09xxxxxxxxx"></label>
			<label class="pixva-field"><span><?php esc_html_e( 'محل', 'pixva' ); ?></span>
				<select name="location">
					<option value="workshop"><?php esc_html_e( 'مراجعه به کارگاه علاءالدین', 'pixva' ); ?></option>
					<option value="pickup"><?php esc_html_e( 'جمع‌آوری در تهران', 'pixva' ); ?></option>
				</select>
			</label>
			<label class="pixva-field"><span><?php esc_html_e( 'زمان ترجیحی', 'pixva' ); ?></span><input type="text" name="preferred_time" placeholder="<?php esc_attr_e( 'مثلاً فردا صبح', 'pixva' ); ?>"></label>
		</div>
		<div data-step hidden>
			<h3><?php esc_html_e( 'شرح و تصویر', 'pixva' ); ?></h3>
			<label class="pixva-field"><span><?php esc_html_e( 'یادداشت', 'pixva' ); ?></span><textarea name="notes" rows="4" placeholder="<?php esc_attr_e( 'علامت را دقیق بنویسید.', 'pixva' ); ?>"></textarea></label>
			<label class="pixva-field"><span><?php esc_html_e( 'تصویر دستگاه، مدل یا خطا', 'pixva' ); ?></span><input type="file" name="photo" accept="image/jpeg,image/png,image/webp"></label>
			<p class="pixva-muted"><?php esc_html_e( 'قیمت نهایی پس از بررسی دستگاه مشخص می‌شود. اطلاعات شما فقط برای همین پرونده استفاده می‌شود.', 'pixva' ); ?></p>
			<button class="pixva-btn pixva-btn--cta" type="submit"><?php esc_html_e( 'ثبت درخواست تعمیر', 'pixva' ); ?></button>
		</div>
		<p class="pixva-notice" data-book-msg hidden></p>
		<div class="px-wizard__nav">
			<button type="button" class="pixva-btn px-btn--ghost" data-back><?php esc_html_e( 'بازگشت', 'pixva' ); ?></button>
			<button type="button" class="pixva-btn pixva-btn--primary" data-next><?php esc_html_e( 'بعدی', 'pixva' ); ?></button>
		</div>
	</form>
	<?php
}

/**
 * فرم گارانتی.
 *
 * @return void
 */
function pixva_render_warranty_form() {
	?>
	<form class="px-track" data-px-warranty>
		<?php pixva_honeypot_field(); ?>
		<input class="pixva-input" type="text" name="code" placeholder="<?php esc_attr_e( 'کد پیگیری', 'pixva' ); ?>" required>
		<input class="pixva-input" type="tel" name="phone" placeholder="<?php esc_attr_e( 'شماره همراه', 'pixva' ); ?>" required>
		<button class="pixva-btn pixva-btn--primary" type="submit"><?php esc_html_e( 'بررسی گارانتی', 'pixva' ); ?></button>
		<p class="pixva-notice" data-warranty-msg hidden></p>
	</form>
	<?php
}

/**
 * حساب مشتری. بدون کارت نمونه.
 *
 * @return void
 */
function pixva_render_account() {
	if ( ! is_user_logged_in() ) {
		echo '<div class="pixva-card px-account">';
		echo '<h2>' . esc_html__( 'ورود به حساب', 'pixva' ) . '</h2>';
		echo '<p>' . esc_html__( 'اگر حساب ندارید، با کد پیگیری می‌توانید وضعیت و گارانتی را ببینید. حساب جعلی ساخته نمی‌شود.', 'pixva' ) . '</p>';
		wp_login_form( array( 'redirect' => pixva_page_url( 'account' ) ) );
		echo '<p><a href="' . esc_url( pixva_page_url( 'tracking' ) ) . '">' . esc_html__( 'پیگیری بدون حساب', 'pixva' ) . '</a></p>';
		echo '</div>';
		return;
	}
	$user    = wp_get_current_user();
	$phone   = (string) get_user_meta( $user->ID, 'pixva_phone', true );
	$devices = get_user_meta( $user->ID, 'pixva_devices', true );
	$devices = is_array( $devices ) ? $devices : array();
	$orders  = pixva_orders_for_phone( $phone );
	?>
	<div class="px-account">
		<?php if ( isset( $_GET['account_error'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
			<p class="pixva-notice pixva-notice--error"><?php esc_html_e( 'شماره موبایل را به‌صورت 09xxxxxxxxx وارد کنید.', 'pixva' ); ?></p>
		<?php endif; ?>
		<section class="pixva-card">
			<h2><?php echo esc_html( $user->display_name ); ?></h2>
			<p><?php esc_html_e( 'پرونده‌ها فقط با شماره همراه ثبت‌شده در حساب پیدا می‌شوند.', 'pixva' ); ?></p>
			<?php if ( ! $orders ) : ?>
				<p class="pixva-notice pixva-notice--info"><?php esc_html_e( 'هنوز سابقه‌ای برای این شماره ثبت نشده.', 'pixva' ); ?></p>
				<a class="pixva-btn pixva-btn--cta" href="<?php echo esc_url( pixva_page_url( 'repair' ) ); ?>"><?php esc_html_e( 'درخواست تعمیر', 'pixva' ); ?></a>
			<?php else : ?>
				<ul class="px-order-list">
					<?php foreach ( $orders as $order ) : ?>
						<?php
						$status = (string) get_post_meta( $order->ID, '_pixva_order_status', true );
						$labels = pixva_order_statuses();
						?>
						<li>
							<strong><?php echo esc_html( (string) get_post_meta( $order->ID, '_pixva_order_code', true ) ); ?></strong>
							<span><?php echo esc_html( isset( $labels[ $status ] ) ? $labels[ $status ] : $status ); ?></span>
							<small><?php echo esc_html( (string) get_post_meta( $order->ID, '_pixva_order_problem', true ) ); ?></small>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</section>
		<section class="pixva-card">
			<h3><?php esc_html_e( 'دستگاه‌های ذخیره‌شده', 'pixva' ); ?></h3>
			<?php if ( ! $devices ) : ?>
				<p><?php esc_html_e( 'هنوز دستگاهی ذخیره نشده.', 'pixva' ); ?></p>
			<?php else : ?>
				<ul>
					<?php foreach ( $devices as $device ) : ?>
						<li><?php echo esc_html( trim( ( $device['brand'] ?? '' ) . ' ' . ( $device['size'] ?? '' ) . ' ' . ( $device['model'] ?? '' ) ) ); ?></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<?php wp_nonce_field( 'pixva_account_save' ); ?>
				<input type="hidden" name="action" value="pixva_account_save">
				<label class="pixva-field"><span><?php esc_html_e( 'شماره همراه پرونده‌ها', 'pixva' ); ?></span><input type="tel" name="phone" value="<?php echo esc_attr( $phone ); ?>" placeholder="09xxxxxxxxx"></label>
				<div class="px-split">
					<label class="pixva-field"><span><?php esc_html_e( 'برند', 'pixva' ); ?></span><input type="text" name="device_brand"></label>
					<label class="pixva-field"><span><?php esc_html_e( 'مدل', 'pixva' ); ?></span><input type="text" name="device_model"></label>
				</div>
				<button class="pixva-btn pixva-btn--primary" type="submit"><?php esc_html_e( 'ذخیره', 'pixva' ); ?></button>
			</form>
		</section>
	</div>
	<?php
}

/**
 * میز تکنسین.
 *
 * @return void
 */
function pixva_render_technician_desk() {
	if ( ! is_user_logged_in() ) {
		echo '<div class="pixva-card">';
		wp_login_form( array( 'redirect' => pixva_page_url( 'technician' ) ) );
		echo '</div>';
		return;
	}
	if ( ! pixva_user_can_repair_desk() ) {
		echo '<p class="pixva-notice pixva-notice--error">' . esc_html__( 'این میز فقط برای تکنسین و مدیر است.', 'pixva' ) . '</p>';
		return;
	}
	$orders   = get_posts(
		array(
			'post_type'      => 'pixva_orders',
			'post_status'    => array( 'private', 'publish' ),
			'posts_per_page' => 30,
			'orderby'        => 'modified',
			'order'          => 'DESC',
		)
	);
	$statuses = pixva_order_statuses();
	if ( isset( $_GET['updated'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		echo '<p class="pixva-notice pixva-notice--success">' . esc_html__( 'وضعیت پرونده ذخیره شد.', 'pixva' ) . '</p>';
	}
	if ( ! $orders ) {
		echo '<p class="pixva-notice pixva-notice--info">' . esc_html__( 'پرونده بازی وجود ندارد.', 'pixva' ) . '</p>';
		return;
	}
	echo '<div class="px-desk">';
	foreach ( $orders as $order ) {
		$status = (string) get_post_meta( $order->ID, '_pixva_order_status', true );
		$code   = (string) get_post_meta( $order->ID, '_pixva_order_code', true );
		if ( 0 === strpos( $code, 'PXV-DEMO' ) ) {
			continue;
		}
		echo '<form class="pixva-card" method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		wp_nonce_field( 'pixva_tech_status' );
		echo '<input type="hidden" name="action" value="pixva_tech_status">';
		echo '<input type="hidden" name="order_id" value="' . esc_attr( (string) $order->ID ) . '">';
		echo '<h3>' . esc_html( $code ) . '</h3>';
		echo '<p>' . esc_html( (string) get_post_meta( $order->ID, '_pixva_order_problem', true ) ) . '</p>';
		echo '<p class="pixva-muted">' . esc_html( pixva_mask_phone( (string) get_post_meta( $order->ID, '_pixva_order_phone', true ) ) ) . '</p>';
		echo '<label class="pixva-field"><span>' . esc_html__( 'وضعیت', 'pixva' ) . '</span><select name="status">';
		foreach ( $statuses as $key => $label ) {
			echo '<option value="' . esc_attr( $key ) . '" ' . selected( $status, $key, false ) . '>' . esc_html( $label ) . '</option>';
		}
		echo '</select></label>';
		echo '<label class="pixva-field"><span>' . esc_html__( 'یادداشت داخلی', 'pixva' ) . '</span><textarea name="note" rows="2">' . esc_textarea( (string) get_post_meta( $order->ID, '_pixva_order_notes', true ) ) . '</textarea></label>';
		echo '<button class="pixva-btn pixva-btn--primary" type="submit">' . esc_html__( 'ذخیره وضعیت', 'pixva' ) . '</button>';
		echo '</form>';
	}
	echo '</div>';
}

/**
 * ثبت اکشن‌های ایجکس محصول.
 *
 * @return void
 */
function pixva_register_platform_ajax() {
	add_action( 'wp_ajax_pixva_warranty_lookup', 'pixva_ajax_warranty_lookup' );
	add_action( 'wp_ajax_nopriv_pixva_warranty_lookup', 'pixva_ajax_warranty_lookup' );
	add_action( 'wp_ajax_pixva_order_photo', 'pixva_ajax_order_photo' );
	add_action( 'wp_ajax_nopriv_pixva_order_photo', 'pixva_ajax_order_photo' );
}
add_action( 'init', 'pixva_register_platform_ajax' );

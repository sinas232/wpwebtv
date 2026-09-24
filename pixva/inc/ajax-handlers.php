<?php
/**
 * پردازش درخواست‌های AJAX قالب پیکسوا
 *
 * اکشن‌ها:
 * - pixva_get_estimate  تخمین هزینه سمت سرور
 * - pixva_submit_order  ثبت نوبت و پرونده تعمیر
 * - pixva_track_order   استعلام وضعیت پرونده
 * - pixva_contact_form  فرم تماس
 *
 * همه اکشن‌ها nonce، honeypot و محدودیت نرخ دارند.
 * منطق قیمت فقط سمت سرور است و در جاوااسکریپت تکرار نمی‌شود.
 *
 * @package Pixva
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ثبت هوک‌های AJAX برای مهمان و کاربر واردشده.
 *
 * @return void
 */
function pixva_register_ajax_actions() {
	$actions = array(
		'pixva_get_estimate' => 'pixva_ajax_get_estimate',
		'pixva_submit_order' => 'pixva_ajax_submit_order',
		'pixva_track_order'  => 'pixva_ajax_track_order',
		'pixva_contact_form' => 'pixva_ajax_contact_form',
	);

	foreach ( $actions as $action => $callback ) {
		add_action( 'wp_ajax_' . $action, $callback );
		add_action( 'wp_ajax_nopriv_' . $action, $callback );
	}
}
add_action( 'init', 'pixva_register_ajax_actions' );

/**
 * گارد مشترک AJAX: nonce، honeypot و rate limit.
 *
 * @param string $nonce_action نام nonce.
 * @param string $rate_action  کلید محدودیت نرخ.
 * @param int    $max          سقف درخواست.
 * @param int    $window       بازه زمانی به ثانیه.
 * @return void
 */
function pixva_ajax_guard( $nonce_action, $rate_action, $max, $window ) {
	check_ajax_referer( $nonce_action, 'nonce' );

	if ( ! pixva_honeypot_passed() ) {
		pixva_ajax_error( __( 'درخواست نامعتبر است.', 'pixva' ), 400 );
	}

	if ( ! pixva_rate_limit( $rate_action, $max, $window ) ) {
		pixva_ajax_error( __( 'تعداد درخواست‌ها بیش از حد مجاز است. لطفاً کمی بعد دوباره تلاش کنید.', 'pixva' ), 429 );
	}
}

/**
 * خواندن متن چندخطی POST پس از اعتبارسنجی nonce.
 *
 * @param string $key نام فیلد.
 * @return string
 */
function pixva_get_post_textarea( $key ) {
	if ( ! isset( $_POST[ $key ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- فراخوان قبلاً nonce را بررسی کرده است.
		return '';
	}
	return sanitize_textarea_field( wp_unslash( $_POST[ $key ] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
}

/**
 * AJAX: تخمین هزینه تعمیر.
 *
 * @return void
 */
function pixva_ajax_get_estimate() {
	pixva_ajax_guard( 'pixva_calculator_nonce', 'estimate', 40, HOUR_IN_SECONDS );

	$brand    = sanitize_key( pixva_get_post_var( 'brand' ) );
	$tech     = sanitize_key( pixva_get_post_var( 'tech' ) );
	$size     = sanitize_key( pixva_get_post_var( 'size' ) );
	$problem  = sanitize_key( pixva_get_post_var( 'problem' ) );

	// تعویض کامل پنل قیمت ندارد؛ فقط هشدار خارج از جدول برمی‌گردد.
	if ( 'panel_replace' === $problem ) {
		$warn_labels = pixva_calculator_labels();
		pixva_ajax_success(
			array(
				'min'          => 0,
				'max'          => 0,
				'minFormatted' => '',
				'maxFormatted' => '',
				'days'         => '',
					'brand'        => isset( $warn_labels['brand'][ $brand ] ) ? $warn_labels['brand'][ $brand ] : $brand,
				'tech'         => isset( $warn_labels['tech'][ $tech ] ) ? $warn_labels['tech'][ $tech ] : $tech,
				'size'         => isset( $warn_labels['size'][ $size ] ) ? $warn_labels['size'][ $size ] : $size,
				'problem'      => isset( $warn_labels['problem'][ $problem ] ) ? $warn_labels['problem'][ $problem ] : $problem,
				'panelWarning' => pixva_panel_replace_warning(),
				'disclaimer'   => pixva_panel_replace_warning(),
			)
		);
	}

	$estimate = pixva_calculate_estimate( $brand, $tech, $size, $problem );

	if ( null === $estimate ) {
		pixva_ajax_error( __( 'گزینه‌های انتخاب‌شده معتبر نیست.', 'pixva' ), 422 );
	}

	$labels = pixva_calculator_labels();

	pixva_ajax_success(
		array(
			'min'          => $estimate['min'],
			'max'          => $estimate['max'],
			'minFormatted' => pixva_price( $estimate['min'] ),
			'maxFormatted' => pixva_price( $estimate['max'] ),
			'days'         => pixva_fa_num( $estimate['days'] ),
			'brand'        => isset( $labels['brand'][ $brand ] ) ? $labels['brand'][ $brand ] : $brand,
			'tech'         => isset( $labels['tech'][ $tech ] ) ? $labels['tech'][ $tech ] : $tech,
			'size'         => isset( $labels['size'][ $size ] ) ? $labels['size'][ $size ] : $size,
		'problem'      => isset( $labels['problem'][ $problem ] ) ? $labels['problem'][ $problem ] : $problem,
		'disclaimer'   => __( 'این مبلغ برآورد کارگاهی سطح ۱۴۰۵ است و پس از عیب‌یابی حضوری قطعی می‌شود. تعویض کامل پنل خارج از جدول محاسبه بوده و اغلب از ۱۰ میلیون تومان شروع می‌شود. هزینه کارشناسی ۱۸۰ تا ۳۵۰ هزار تومان جداگانه است.', 'pixva' ),
		)
	);
}

/**
 * AJAX: ثبت نوبت تعمیر و ساخت پرونده پیگیری.
 *
 * @return void
 */
function pixva_ajax_submit_order() {
	pixva_ajax_guard( 'pixva_order_nonce', 'order', 6, HOUR_IN_SECONDS );

	$phone = pixva_normalize_mobile( pixva_get_post_var( 'phone' ) );
	if ( ! pixva_is_valid_iranian_mobile( $phone ) ) {
		pixva_ajax_error( __( 'شماره همراه معتبر نیست. نمونه درست: ۰۹۱۲۱۲۳۴۵۶۷', 'pixva' ), 422 );
	}

	$brand   = sanitize_key( pixva_get_post_var( 'brand' ) );
	$tech    = sanitize_key( pixva_get_post_var( 'tech' ) );
	$size    = sanitize_key( pixva_get_post_var( 'size' ) );
	$problem = sanitize_key( pixva_get_post_var( 'problem' ) );
	$name    = pixva_get_post_var( 'customer_name' );
	$model   = pixva_get_post_var( 'model' );

	if ( '' !== $name && pixva_strlen( $name ) < 2 ) {
		pixva_ajax_error( __( 'نام را کامل‌تر وارد کنید.', 'pixva' ), 422 );
	}

	$estimate = pixva_calculate_estimate( $brand, $tech, $size, $problem );
	if ( null === $estimate ) {
		pixva_ajax_error( __( 'ابتدا برآورد معتبر دریافت کنید.', 'pixva' ), 422 );
	}

	$labels  = pixva_calculator_labels();
	$summary = sprintf(
		/* translators: 1: برند، 2: سایز، 3: تکنولوژی، 4: مشکل */
		__( '%1$s %2$s %3$s — %4$s', 'pixva' ),
		isset( $labels['brand'][ $brand ] ) ? $labels['brand'][ $brand ] : $brand,
		isset( $labels['size'][ $size ] ) ? $labels['size'][ $size ] : $size,
		isset( $labels['tech'][ $tech ] ) ? $labels['tech'][ $tech ] : $tech,
		isset( $labels['problem'][ $problem ] ) ? $labels['problem'][ $problem ] : $problem
	);

	$range = sprintf(
		/* translators: 1: حداقل قیمت، 2: حداکثر قیمت */
		__( '%1$s تا %2$s تومان', 'pixva' ),
		pixva_price( $estimate['min'] ),
		pixva_price( $estimate['max'] )
	);

	$result = pixva_create_order(
		array(
			'phone'    => $phone,
			'brand'    => isset( $labels['brand'][ $brand ] ) ? $labels['brand'][ $brand ] : $brand,
			'model'    => $model,
			'problem'  => $summary,
			'estimate' => $range,
		)
	);

	if ( empty( $result['id'] ) ) {
		pixva_ajax_error( __( 'ثبت نوبت انجام نشد. لطفاً با پشتیبانی تماس بگیرید.', 'pixva' ), 500 );
	}

	if ( '' !== $name ) {
		update_post_meta( (int) $result['id'], '_pixva_order_name', $name );
	}

	pixva_notify_admin(
		sprintf(
			/* translators: %s: کد پیگیری */
			__( 'نوبت جدید پیکسوا: %s', 'pixva' ),
			$result['code']
		),
		sprintf(
			"کد پیگیری: %s\nنام: %s\nشماره: %s\nشرح: %s\nبرآورد: %s\n",
			$result['code'],
			$name,
			$phone,
			$summary,
			$range
		)
	);

	pixva_ajax_success(
		array(
			'code'     => $result['code'],
			'message'  => sprintf(
				/* translators: %s: کد پیگیری */
				__( 'نوبت شما ثبت شد. کد پیگیری را نگه دارید: %s', 'pixva' ),
				$result['code']
			),
			'trackUrl' => pixva_page_url( 'tracking' ),
		)
	);
}

/**
 * AJAX: استعلام وضعیت پرونده تعمیر.
 *
 * @return void
 */
function pixva_ajax_track_order() {
	pixva_ajax_guard( 'pixva_tracking_nonce', 'tracking', 20, HOUR_IN_SECONDS );

	$code  = strtoupper( pixva_get_post_var( 'code' ) );
	$code  = (string) preg_replace( '/[^A-Z0-9\-]/', '', (string) $code );
	$phone = pixva_normalize_mobile( pixva_get_post_var( 'phone' ) );

	// پیگیری فقط با هر دو مشخصه: کد مطابق PXV-... به‌علاوه شماره همان پرونده.
	// پیگیری فقط با شماره ممنوع است.
	if ( '' === $code || '' === $phone ) {
		pixva_ajax_error( __( 'کد پیگیری و شماره همراه همان پرونده، هر دو لازم است.', 'pixva' ), 422 );
	}

	if ( 1 !== preg_match( '/^PXV-[A-Z0-9\-]+$/', $code ) ) {
		pixva_ajax_error( __( 'قالب کد پیگیری معتبر نیست. نمونه: PXV-DEMO-2401', 'pixva' ), 422 );
	}

	if ( ! pixva_is_valid_iranian_mobile( $phone ) ) {
		pixva_ajax_error( __( 'شماره همراه معتبر نیست.', 'pixva' ), 422 );
	}

	$order = pixva_find_order( $code, '' );

	if ( $order instanceof WP_Post ) {
		$stored = (string) get_post_meta( $order->ID, '_pixva_order_phone', true );
		if ( $phone !== $stored ) {
			$order = null;
		}
	}

	if ( ! $order instanceof WP_Post ) {
		pixva_ajax_error( __( 'پرونده‌ای با این مشخصات پیدا نشد. کد را دقیق وارد کنید یا با پشتیبانی تماس بگیرید.', 'pixva' ), 404 );
	}

	$statuses      = pixva_order_statuses();
	$keys          = array_keys( $statuses );
	$status        = (string) get_post_meta( $order->ID, '_pixva_order_status', true );
	$current_index = array_search( $status, $keys, true );

	if ( false === $current_index ) {
		$current_index = 0;
		$status        = 'received';
	}

	$steps_time = json_decode( (string) get_post_meta( $order->ID, '_pixva_order_steps', true ), true );
	if ( ! is_array( $steps_time ) ) {
		$steps_time = array();
	}

	$timeline = array();
	foreach ( $keys as $index => $key ) {
		$state = 'upcoming';
		if ( $index < $current_index ) {
			$state = 'done';
		} elseif ( $index === $current_index ) {
			$state = 'current';
		}
		$timestamp  = isset( $steps_time[ $key ] ) ? (int) $steps_time[ $key ] : 0;
		$timeline[] = array(
			'key'   => $key,
			'label' => $statuses[ $key ],
			'state' => $state,
			'date'  => $timestamp ? pixva_fa_num( wp_date( 'Y/m/d H:i', $timestamp ) ) : '',
		);
	}

	pixva_ajax_success(
		array(
			'code'        => (string) get_post_meta( $order->ID, '_pixva_order_code', true ),
			'status'      => $status,
			'statusLabel' => isset( $statuses[ $status ] ) ? $statuses[ $status ] : '',
			'brand'       => (string) get_post_meta( $order->ID, '_pixva_order_brand', true ),
			'model'       => (string) get_post_meta( $order->ID, '_pixva_order_model', true ),
			'problem'     => (string) get_post_meta( $order->ID, '_pixva_order_problem', true ),
			'estimate'    => (string) get_post_meta( $order->ID, '_pixva_order_estimate', true ),
			'phoneMask'   => pixva_mask_phone( (string) get_post_meta( $order->ID, '_pixva_order_phone', true ) ),
			'timeline'    => $timeline,
			'updated'     => pixva_fa_num( wp_date( 'Y/m/d', strtotime( $order->post_modified ) ) ),
		)
	);
}

/**
 * AJAX: فرم تماس با ما.
 *
 * @return void
 */
function pixva_ajax_contact_form() {
	pixva_ajax_guard( 'pixva_contact_nonce', 'contact', 8, HOUR_IN_SECONDS );

	$name    = pixva_get_post_var( 'customer_name' );
	$phone   = pixva_normalize_mobile( pixva_get_post_var( 'phone' ) );
	$email   = sanitize_email( pixva_get_post_var( 'email' ) );
	$message = pixva_get_post_textarea( 'message' );

	if ( pixva_strlen( $name ) < 2 ) {
		pixva_ajax_error( __( 'نام را کامل وارد کنید.', 'pixva' ), 422 );
	}
	if ( ! pixva_is_valid_iranian_mobile( $phone ) ) {
		pixva_ajax_error( __( 'شماره همراه معتبر نیست.', 'pixva' ), 422 );
	}
	if ( '' !== $email && ! is_email( $email ) ) {
		pixva_ajax_error( __( 'ایمیل معتبر نیست.', 'pixva' ), 422 );
	}
	if ( pixva_strlen( $message ) < 10 ) {
		pixva_ajax_error( __( 'متن پیام خیلی کوتاه است.', 'pixva' ), 422 );
	}
	if ( pixva_strlen( $message ) > 2000 ) {
		pixva_ajax_error( __( 'متن پیام بیش از حد طولانی است.', 'pixva' ), 422 );
	}

	$post_id = wp_insert_post(
		array(
			'post_type'    => 'pixva_inbox',
			'post_status'  => 'private',
			'post_title'   => sprintf(
				/* translators: 1: نام، 2: شماره */
				__( 'پیام %1$s — %2$s', 'pixva' ),
				$name,
				$phone
			),
			'post_content' => $message,
			'meta_input'   => array(
				'_pixva_inbox_name'  => $name,
				'_pixva_inbox_phone' => $phone,
				'_pixva_inbox_email' => $email,
			),
		),
		true
	);

	if ( is_wp_error( $post_id ) ) {
		pixva_ajax_error( __( 'ارسال پیام انجام نشد. لطفاً تلفنی تماس بگیرید.', 'pixva' ), 500 );
	}

	pixva_notify_admin(
		sprintf(
			/* translators: %s: نام فرستنده */
			__( 'پیام جدید از فرم تماس پیکسوا: %s', 'pixva' ),
			$name
		),
		sprintf( "نام: %s\nتلفن: %s\nایمیل: %s\n\n%s", $name, $phone, $email, $message )
	);

	pixva_ajax_success(
		array(
			'message' => __( 'پیام شما ثبت شد. کارشناسان پیکسوا به‌زودی تماس می‌گیرند.', 'pixva' ),
		)
	);
}

/**
 * ایمیل اطلاع‌رسانی به مدیر. شکست ایمیل نباید درخواست کاربر را خراب کند.
 *
 * @param string $subject موضوع.
 * @param string $body    متن.
 * @return void
 */
function pixva_notify_admin( $subject, $body ) {
	$admin = sanitize_email( (string) get_option( 'admin_email' ) );
	if ( ! is_email( $admin ) ) {
		return;
	}
	// جلوگیری از تزریق سربرگ: حذف خط جدید از موضوع. ایمیل کاربر وارد سربرگ نمی‌شود.
	$subject = str_replace( array( "\r", "\n" ), '', (string) $subject );
	$subject = trim( wp_strip_all_tags( $subject ) );
	if ( '' === $subject ) {
		$subject = __( 'اطلاع‌رسانی پیکسوا', 'pixva' );
	}
	wp_mail( $admin, $subject, (string) $body );
}

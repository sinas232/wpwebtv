<?php
/**
 * پردازش درخواست‌های AJAX قالب پیکسوا
 *
 * اکشن‌ها:
 * - pixva_get_estimate  تخمین هزینه سمت سرور (فرمول فقط در inc/pricing-engine.php)
 * - pixva_submit_order  ثبت نوبت و پرونده تعمیر
 * - pixva_track_device  استعلام وضعیت پرونده (الزام هم‌زمان کد PXV و شماره همراه)
 * - pixva_contact_form  فرم تماس
 * - pixva_warranty_card    صدور کارت گارانتی دیجیتال با هش SHA-256 (ابزار ۱۶)
 * - pixva_client_hub       فهرست پرونده‌های یک شماره همراه (ابزار ۲۷)
 * - pixva_dispatch_request درخواست اعزام پیک/تکنسین (ابزار ۱۷)
 * - pixva_booking_request  رزرو نوبت با پیش‌فاکتور دیجیتال (ابزار ۲۶)
 * - pixva_quick_quote      استعلام سریع تصویری با تحلیل AI (ابزار ۲۰)
 * - pixva_tool_request     فرم‌های هاب‌ها: B2B، بیمه، اشتراک، بازخورد، یادآور، پیامک، سرویس در محل
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
		'pixva_get_estimate'     => 'pixva_ajax_get_estimate',
		'pixva_submit_order'     => 'pixva_ajax_submit_order',
		'pixva_track_device'     => 'pixva_ajax_track_device',
		'pixva_track_order'      => 'pixva_ajax_track_device', // نام قدیمی برای سازگاری.
		'pixva_contact_form'     => 'pixva_ajax_contact_form',
		'pixva_warranty_card'    => 'pixva_ajax_warranty_card',
		'pixva_client_hub'       => 'pixva_ajax_client_hub',
		'pixva_dispatch_request' => 'pixva_ajax_dispatch_request',
		'pixva_booking_request'  => 'pixva_ajax_booking_request',
		'pixva_quick_quote'      => 'pixva_ajax_quick_quote',
		'pixva_tool_request'     => 'pixva_ajax_tool_request',
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
 * AJAX: تخمین هزینه تعمیر (خروجی موتور قیمت سمت سرور).
 *
 * @return void
 */
function pixva_ajax_get_estimate() {
	pixva_ajax_guard( 'pixva_calculator_nonce', 'estimate', 40, HOUR_IN_SECONDS );

	$brand    = sanitize_key( pixva_get_post_var( 'brand' ) );
	$tech     = sanitize_key( pixva_get_post_var( 'tech' ) );
	$size     = sanitize_key( pixva_get_post_var( 'size' ) );
	$problem  = sanitize_key( pixva_get_post_var( 'problem' ) );
	$estimate = pixva_calculate_estimate( $brand, $tech, $size, $problem );

	if ( null === $estimate ) {
		pixva_ajax_error( __( 'گزینه‌های انتخاب‌شده معتبر نیست.', 'pixva' ), 422 );
	}

	$labels = pixva_calculator_labels();

	// تعویض کامل پنل: فقط پیام هشدار، بدون بازه قیمت جدول.
	if ( ! empty( $estimate['panel_replacement'] ) ) {
		pixva_ajax_success(
			array(
				'min'              => 0,
				'max'              => 0,
				'minFormatted'     => '',
				'maxFormatted'     => '',
				'days'             => '',
				'panelReplacement' => true,
				'warning'          => $estimate['warning'],
				'brand'            => isset( $labels['brand'][ $brand ] ) ? $labels['brand'][ $brand ] : $brand,
				'tech'             => isset( $labels['tech'][ $tech ] ) ? $labels['tech'][ $tech ] : $tech,
				'size'             => isset( $labels['size'][ $size ] ) ? $labels['size'][ $size ] : $size,
				'problem'          => isset( $labels['problem'][ $problem ] ) ? $labels['problem'][ $problem ] : $problem,
				'disclaimer'       => __( 'برای تعویض کامل پنل، قیمت پس از بازدید کارشناس و تأیید شما اعلام می‌شود.', 'pixva' ),
			)
		);
	}

	pixva_ajax_success(
		array(
			'min'              => $estimate['min'],
			'max'              => $estimate['max'],
			'minFormatted'     => pixva_price( $estimate['min'] ),
			'maxFormatted'     => pixva_price( $estimate['max'] ),
			'days'             => pixva_fa_num( $estimate['days'] ),
			'panelReplacement' => false,
			'warning'          => '',
			'brand'            => isset( $labels['brand'][ $brand ] ) ? $labels['brand'][ $brand ] : $brand,
			'tech'             => isset( $labels['tech'][ $tech ] ) ? $labels['tech'][ $tech ] : $tech,
			'size'             => isset( $labels['size'][ $size ] ) ? $labels['size'][ $size ] : $size,
			'problem'          => isset( $labels['problem'][ $problem ] ) ? $labels['problem'][ $problem ] : $problem,
			'disclaimer'       => __( 'این مبلغ برآورد کارگاهی است و پس از عیب‌یابی حضوری قطعی می‌شود. ایاب‌وذهاب و قطعه کمیاب ممکن است جداگانه محاسبه شود.', 'pixva' ),
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

	if ( ! empty( $estimate['panel_replacement'] ) ) {
		$range = pixva_panel_replacement_warning();
	} else {
		$range = sprintf(
			/* translators: 1: حداقل قیمت، 2: حداکثر قیمت */
			__( '%1$s تا %2$s تومان', 'pixva' ),
			pixva_price( $estimate['min'] ),
			pixva_price( $estimate['max'] )
		);
	}

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
 * AJAX: استعلام وضعیت دستگاه (pixva_track_device).
 *
 * شرط صحت استعلام: تطابق «هم‌زمان» کد پیگیری با فرمت PXV-... و شماره همراه.
 * استعلام فقط با شماره همراه (یا فقط با کد) اکیداً ممنوع است.
 *
 * @return void
 */
function pixva_ajax_track_device() {
	pixva_ajax_guard( 'pixva_tracking_nonce', 'tracking', 20, HOUR_IN_SECONDS );

	$code  = strtoupper( pixva_get_post_var( 'code' ) );
	$code  = preg_replace( '/[^A-Z0-9\-]/', '', $code );
	$phone = pixva_normalize_mobile( pixva_get_post_var( 'phone' ) );

	// هر دو مقدار الزامی است؛ استعلام بدون کد یا بدون شماره پذیرفته نمی‌شود.
	if ( '' === $code || '' === $phone ) {
		pixva_ajax_error( __( 'برای استعلام، هم کد پیگیری و هم شماره همراه ثبت‌شده لازم است.', 'pixva' ), 422 );
	}

	// کد باید با فرمت PXV-... باشد.
	if ( ! preg_match( '/^PXV-[A-Z0-9]+(?:-[A-Z0-9]+)*$/', $code ) ) {
		pixva_ajax_error( __( 'فرمت کد پیگیری درست نیست. کد باید با PXV- شروع شود؛ مثلاً PXV-2401.', 'pixva' ), 422 );
	}

	if ( ! pixva_is_valid_iranian_mobile( $phone ) ) {
		pixva_ajax_error( __( 'شماره همراه معتبر نیست.', 'pixva' ), 422 );
	}

	// تطابق هم‌زمان کد و شماره در دیتابیس (هر دو متا باید یکی باشند).
	$order = pixva_find_order( $code, $phone );

	if ( ! $order instanceof WP_Post ) {
		pixva_ajax_error( __( 'پرونده‌ای با این مشخصات پیدا نشد. کد و شماره همراه باید هر دو دقیقاً همان چیزی باشند که هنگام پذیرش ثبت شده است.', 'pixva' ), 404 );
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

	$public = pixva_order_public_data( $order );

	pixva_ajax_success(
		array_merge(
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
			),
			$public
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
 * ایمیل اطلاع‌رسانی به مدیر (بدون امکان Header Injection).
 * شکست ایمیل نباید درخواست کاربر را خراب کند.
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
	pixva_safe_mail( $admin, $subject, $body );
}

/* ------------------------------------------------------------------------
   ابزارهای v25.0: داده عمومی پرونده، کارت گارانتی و فرم‌های هاب‌ها
   ------------------------------------------------------------------------ */

if ( ! function_exists( 'pixva_warranty_days' ) ) {
	/**
	 * مدت گارانتی کتبی خدمات (روز).
	 *
	 * @return int
	 */
	function pixva_warranty_days() {
		$options = function_exists( 'pixva_control_options' ) ? pixva_control_options() : array();
		$days    = isset( $options['warranty_days'] ) ? (int) $options['warranty_days'] : 0;

		if ( $days < 30 || $days > 730 ) {
			$days = 180;
		}

		return (int) apply_filters( 'pixva_warranty_days', $days );
	}
}

if ( ! function_exists( 'pixva_order_steps' ) ) {
	/**
	 * خواندن زمان ثبت هر مرحله پرونده.
	 *
	 * @param WP_Post $order پرونده.
	 * @return array<string, int>
	 */
	function pixva_order_steps( $order ) {
		$steps = json_decode( (string) get_post_meta( $order->ID, '_pixva_order_steps', true ), true );
		return is_array( $steps ) ? array_map( 'intval', $steps ) : array();
	}
}

if ( ! function_exists( 'pixva_order_delivered_timestamp' ) ) {
	/**
	 * زمان تحویل دستگاه به مشتری (مبنای شروع گارانتی).
	 *
	 * @param WP_Post $order پرونده.
	 * @return int
	 */
	function pixva_order_delivered_timestamp( $order ) {
		$steps    = pixva_order_steps( $order );
		$delivered = (int) get_post_meta( $order->ID, '_pixva_order_delivered', true );

		if ( $delivered ) {
			return $delivered;
		}
		if ( ! empty( $steps['delivered'] ) ) {
			return (int) $steps['delivered'];
		}
		if ( ! empty( $steps['ready'] ) ) {
			return (int) $steps['ready'];
		}
		if ( ! empty( $steps['testing'] ) ) {
			return (int) $steps['testing'];
		}
		if ( ! empty( $steps['qc'] ) ) {
			return (int) $steps['qc'];
		}

		$modified = strtotime( (string) $order->post_modified );
		return $modified ? (int) $modified : (int) current_time( 'timestamp' );
	}
}

if ( ! function_exists( 'pixva_order_public_data' ) ) {
	/**
	 * داده عمومی یک پرونده برای ابزارهای پیگیری، کارت گارانتی و گزارش.
	 *
	 * @param WP_Post $order پرونده.
	 * @return array<string, mixed>
	 */
	function pixva_order_public_data( $order ) {
		$brand    = (string) get_post_meta( $order->ID, '_pixva_order_brand', true );
		$model    = (string) get_post_meta( $order->ID, '_pixva_order_model', true );
		$problem  = (string) get_post_meta( $order->ID, '_pixva_order_problem', true );
		$statuses = pixva_order_statuses();
		$status   = (string) get_post_meta( $order->ID, '_pixva_order_status', true );
		if ( function_exists( 'pixva_crm_normalize_status' ) ) {
			$status = pixva_crm_normalize_status( $status );
		}
		$keys     = array_keys( $statuses );
		$index    = array_search( $status, $keys, true );

		if ( false === $index ) {
			$index  = 0;
			$status = 'received';
		}

		$steps     = pixva_order_steps( $order );
		$timeline  = array();
		foreach ( $keys as $position => $key ) {
			$state = 'upcoming';
			if ( $position < $index ) {
				$state = 'done';
			} elseif ( $position === $index ) {
				$state = 'current';
			}
			$timestamp  = isset( $steps[ $key ] ) ? (int) $steps[ $key ] : 0;
			$timeline[] = array(
				'key'   => $key,
				'label' => $statuses[ $key ],
				'state' => $state,
				'date'  => $timestamp ? pixva_fa_num( wp_date( 'Y/m/d H:i', $timestamp ) ) : '',
			);
		}

		$days      = pixva_warranty_days();
		$delivered = pixva_order_delivered_timestamp( $order );
		$expires   = strtotime( '+' . $days . ' days', $delivered );

		$device = trim( $brand . ' ' . $model );

		return array(
			'device'       => '' !== $device ? $device : __( 'تلویزیون (مشخصات ثبت‌نشده)', 'pixva' ),
			'problemLabel' => $problem,
			'warranty'     => sprintf(
				/* translators: 1: تعداد روز گارانتی، 2: تاریخ پایان */
				__( 'گارانتی کتبی %1$s روزه تا %2$s', 'pixva' ),
				pixva_fa_num( (string) $days ),
				pixva_fa_num( wp_date( 'Y/m/d', $expires ) )
			),
			'warrantyDays' => $days,
			'delivered'    => pixva_fa_num( wp_date( 'Y/m/d', $delivered ) ),
			'expires'      => pixva_fa_num( wp_date( 'Y/m/d', $expires ) ),
			'timeline'     => $timeline,
			'service'      => isset( $statuses[ $status ] ) ? $statuses[ $status ] : '',
		);
	}
}

if ( ! function_exists( 'pixva_warranty_hash' ) ) {
	/**
	 * هش SHA-256 اعتبارسنجی کارت گارانتی.
	 *
	 * هش از داده واقعی پرونده با کلید محلی وردپرس ساخته می‌شود تا بدون دسترسی به
	 * دیتابیس قابل جعل نباشد و در همان زمان برای استعلام اصالت کافی باشد.
	 *
	 * @param string $code      کد پیگیری.
	 * @param string $phone     شماره همراه ثبت‌شده.
	 * @param string $service   خدمت انجام‌شده.
	 * @param string $expires   تاریخ پایان گارانتی.
	 * @return string
	 */
	function pixva_warranty_hash( $code, $phone, $service, $expires ) {
		$payload = implode( '|', array( $code, $phone, $service, $expires ) );

		return hash_hmac( 'sha256', $payload, wp_salt( 'auth' ) );
	}
}

/**
 * AJAX: صدور کارت گارانتی دیجیتال (ابزار ۱۶).
 *
 * @return void
 */
function pixva_ajax_warranty_card() {
	pixva_ajax_guard( 'pixva_tracking_nonce', 'tracking', 20, HOUR_IN_SECONDS );

	$code  = strtoupper( preg_replace( '/[^A-Za-z0-9\-]/', '', pixva_get_post_var( 'code' ) ) );
	$phone = pixva_normalize_mobile( pixva_get_post_var( 'phone' ) );

	if ( '' === $code || '' === $phone ) {
		pixva_ajax_error( __( 'برای صدور کارت گارانتی، کد پیگیری و شماره همراه لازم است.', 'pixva' ), 422 );
	}
	if ( ! pixva_is_valid_iranian_mobile( $phone ) ) {
		pixva_ajax_error( __( 'شماره همراه معتبر نیست.', 'pixva' ), 422 );
	}

	$order = pixva_find_order( $code, $phone );
	if ( ! $order instanceof WP_Post ) {
		pixva_ajax_error( __( 'پرونده‌ای با این کد و شماره همراه پیدا نشد.', 'pixva' ), 404 );
	}

	$public = pixva_order_public_data( $order );
	$status = (string) get_post_meta( $order->ID, '_pixva_order_status', true );

	if ( ! in_array( $status, array( 'ready', 'testing' ), true ) ) {
		pixva_ajax_error(
			__( 'کارت گارانتی فقط پس از تحویل دستگاه صادر می‌شود. وضعیت فعلی پرونده: ', 'pixva' ) . $public['service'],
			422
		);
	}

	$code_value = (string) get_post_meta( $order->ID, '_pixva_order_code', true );
	$service    = (string) get_post_meta( $order->ID, '_pixva_order_problem', true );

	pixva_ajax_success(
		array(
			'message' => __( 'کارت گارانتی صادر شد. هش اعتبارسنجی برای استعلام اصالت کارت کاربرد دارد.', 'pixva' ),
			'card'    => array(
				'code'      => $code_value,
				'device'    => $public['device'],
				'service'   => $service,
				'delivered' => $public['delivered'],
				'expires'   => $public['expires'],
				'days'      => $public['warrantyDays'],
				'hash'      => pixva_warranty_hash( $code_value, $phone, $service, $public['expires'] ),
			),
		)
	);
}

if ( ! function_exists( 'pixva_find_orders_by_phone' ) ) {
	/**
	 * فهرست پرونده‌های یک شماره همراه (برای هاب مشتریان).
	 *
	 * @param string $phone شماره همراه نرمال‌شده.
	 * @param int    $limit سقف تعداد.
	 * @return WP_Post[]
	 */
	function pixva_find_orders_by_phone( $phone, $limit = 12 ) {
		$phone = pixva_normalize_mobile( $phone );
		if ( '' === $phone ) {
			return array();
		}

		$posts = get_posts(
			array(
				'post_type'      => 'pixva_orders',
				'post_status'    => array( 'private', 'publish' ),
				'posts_per_page' => max( 1, min( 30, (int) $limit ) ),
				'orderby'        => 'date',
				'order'          => 'DESC',
				'meta_key'       => '_pixva_order_phone', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value'     => $phone, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			)
		);

		return is_array( $posts ) ? $posts : array();
	}
}

/**
 * AJAX: هاب مشتریان — همه پرونده‌های یک شماره همراه (ابزار ۲۷).
 *
 * @return void
 */
function pixva_ajax_client_hub() {
	pixva_ajax_guard( 'pixva_tracking_nonce', 'hub', 20, HOUR_IN_SECONDS );

	$phone = pixva_normalize_mobile( pixva_get_post_var( 'phone' ) );
	if ( ! pixva_is_valid_iranian_mobile( $phone ) ) {
		pixva_ajax_error( __( 'شماره همراه معتبر نیست. نمونه درست: ۰۹۱۲۱۲۳۴۵۶۷', 'pixva' ), 422 );
	}

	$orders  = pixva_find_orders_by_phone( $phone );
	$records = array();

	foreach ( $orders as $order ) {
		$public   = pixva_order_public_data( $order );
		$steps    = pixva_order_steps( $order );
		$expires  = isset( $steps['ready'] ) ? strtotime( '+' . $public['warrantyDays'] . ' days', (int) $steps['ready'] ) : 0;
		$records[] = array(
			'code'     => (string) get_post_meta( $order->ID, '_pixva_order_code', true ),
			'device'   => $public['device'],
			'problem'  => $public['problemLabel'],
			'status'   => $public['service'],
			'estimate' => (string) get_post_meta( $order->ID, '_pixva_order_estimate', true ),
			'warranty' => $expires ? pixva_fa_num( wp_date( 'Y/m/d', $expires ) ) : '',
			'updated'  => pixva_fa_num( wp_date( 'Y/m/d', strtotime( $order->post_modified ) ) ),
		);
	}

	if ( empty( $records ) ) {
		pixva_ajax_success(
			array(
				'message' => __( 'پرونده‌ای با این شماره همراه ثبت نشده است. اگر پیش‌تر پذیرش انجام داده‌اید، با همان شماره‌ای که در رسید درج شده است جست‌وجو کنید.', 'pixva' ),
				'records' => array(),
			)
		);
	}

	pixva_ajax_success(
		array(
			'message' => sprintf(
				/* translators: %s: تعداد پرونده */
				__( '%s پرونده برای این شماره همراه پیدا شد.', 'pixva' ),
				pixva_fa_num( (string) count( $records ) )
			),
			'records' => $records,
		)
	);
}

if ( ! function_exists( 'pixva_request_kind_labels' ) ) {
	/**
	 * انواع درخواست پشتیبانی‌شده در فرم‌های هاب‌ها.
	 *
	 * @return array<string, string>
	 */
	function pixva_request_kind_labels() {
		return array(
			'b2b'          => __( 'قرارداد سازمانی / هتل (B2B)', 'pixva' ),
			'review'       => __( 'ثبت بازخورد مشتری', 'pixva' ),
			'reminder'     => __( 'یادآور سرویس دوره‌ای', 'pixva' ),
			'sms'          => __( 'تنظیم اطلاع‌رسانی پیامکی', 'pixva' ),
			'onsite'       => __( 'درخواست سرویس در محل', 'pixva' ),
			'insurance'    => __( 'گزارش خسارت بیمه', 'pixva' ),
			'subscription' => __( 'ثبت‌نام اشتراک پیکسوا پلاس', 'pixva' ),
			'dispatch'     => __( 'درخواست اعزام پیک/تکنسین', 'pixva' ),
			'parts'        => __( 'درخواست تأمین قطعه', 'pixva' ),
		);
	}
}

if ( ! function_exists( 'pixva_create_inbox' ) ) {
	/**
	 * ثبت پیام/درخواست در صندوق داخلی و اطلاع به مدیر.
	 *
	 * @param string $title   عنوان.
	 * @param string $content متن کامل.
	 * @param array  $meta    متادیتای اختیاری.
	 * @return int
	 */
	function pixva_create_inbox( $title, $content, $meta = array() ) {
		$post_id = wp_insert_post(
			array(
				'post_type'    => 'pixva_inbox',
				'post_status'  => 'private',
				'post_title'   => $title,
				'post_content' => $content,
				'meta_input'   => $meta,
			),
			true
		);

		return is_wp_error( $post_id ) ? 0 : (int) $post_id;
	}
}

/**
 * AJAX: درخواست اعزام پیک یا تکنسین (ابزار ۱۷ و ۴۷).
 *
 * @return void
 */
function pixva_ajax_dispatch_request() {
	pixva_ajax_guard( 'pixva_order_nonce', 'dispatch', 8, HOUR_IN_SECONDS );

	$name  = pixva_get_post_var( 'customer_name' );
	$phone = pixva_normalize_mobile( pixva_get_post_var( 'phone' ) );
	$zone  = sanitize_key( pixva_get_post_var( 'zone' ) );
	$kind  = sanitize_key( pixva_get_post_var( 'kind' ) );

	if ( pixva_strlen( $name ) < 2 ) {
		pixva_ajax_error( __( 'نام و نام خانوادگی را کامل وارد کنید.', 'pixva' ), 422 );
	}
	if ( ! pixva_is_valid_iranian_mobile( $phone ) ) {
		pixva_ajax_error( __( 'شماره همراه معتبر نیست. نمونه درست: ۰۹۱۲۱۲۳۴۵۶۷', 'pixva' ), 422 );
	}

	$zones = function_exists( 'pixva_zone_catalog' ) ? pixva_zone_catalog() : array();
	$kinds = array(
		'pickup'    => __( 'پیک جمع‌آوری دستگاه', 'pixva' ),
		'onsite'    => __( 'تکنسین در محل', 'pixva' ),
		'emergency' => __( 'اعزام اورژانسی زیر ۲ ساعت', 'pixva' ),
	);

	if ( ! isset( $kinds[ $kind ] ) ) {
		$kind = 'pickup';
	}

	$zone_label = isset( $zones[ $zone ]['label'] ) ? $zones[ $zone ]['label'] : __( 'منطقه نامشخص', 'pixva' );
	$eta        = isset( $zones[ $zone ]['eta'] ) ? $zones[ $zone ]['eta'] : '';

	$result = pixva_create_order(
		array(
			'phone'    => $phone,
			'brand'    => '',
			'model'    => '',
			'problem'  => $kinds[ $kind ] . ' — ' . $zone_label,
			'estimate' => __( 'پس از عیب‌یابی اعلام می‌شود', 'pixva' ),
		)
	);

	if ( empty( $result['id'] ) ) {
		pixva_ajax_error( __( 'ثبت درخواست انجام نشد. لطفاً با پشتیبانی تماس بگیرید.', 'pixva' ), 500 );
	}

	update_post_meta( (int) $result['id'], '_pixva_order_name', $name );
	update_post_meta( (int) $result['id'], '_pixva_request_kind', $kind );
	update_post_meta( (int) $result['id'], '_pixva_request_zone', $zone );

	pixva_notify_admin(
		sprintf(
			/* translators: 1: نوع اعزام، 2: کد پیگیری */
			__( 'درخواست اعزام %1$s — %2$s', 'pixva' ),
			$kinds[ $kind ],
			$result['code']
		),
		sprintf(
			"کد پیگیری: %s\nنوع اعزام: %s\nنام: %s\nشماره: %s\nمنطقه: %s\nبازه اعزام: %s\n",
			$result['code'],
			$kinds[ $kind ],
			$name,
			$phone,
			$zone_label,
			$eta
		)
	);

	pixva_ajax_success(
		array(
			'code'    => $result['code'],
			'message' => sprintf(
				/* translators: 1: نوع اعزام، 2: منطقه، 3: بازه زمانی */
				__( 'درخواست %1$s برای %2$s ثبت شد. بازه اعزام: %3$s', 'pixva' ),
				$kinds[ $kind ],
				$zone_label,
				'' !== $eta ? $eta : __( 'کمتر از ۲ ساعت در ساعات کاری', 'pixva' )
			),
		)
	);
}

/**
 * AJAX: رزرو نوبت با پیش‌فاکتور دیجیتال (ابزار ۲۶).
 *
 * @return void
 */
function pixva_ajax_booking_request() {
	pixva_ajax_guard( 'pixva_order_nonce', 'booking', 8, HOUR_IN_SECONDS );

	$name  = pixva_get_post_var( 'customer_name' );
	$phone = pixva_normalize_mobile( pixva_get_post_var( 'phone' ) );
	$brand = sanitize_key( pixva_get_post_var( 'brand' ) );
	$size  = sanitize_key( pixva_get_post_var( 'size' ) );
	$tech  = sanitize_key( pixva_get_post_var( 'tech' ) );
	$problem = sanitize_key( pixva_get_post_var( 'problem' ) );
	$date  = sanitize_text_field( pixva_get_post_var( 'booking_date' ) );
	$slot  = sanitize_text_field( pixva_get_post_var( 'slot' ) );

	if ( pixva_strlen( $name ) < 2 ) {
		pixva_ajax_error( __( 'نام را کامل وارد کنید.', 'pixva' ), 422 );
	}
	if ( ! pixva_is_valid_iranian_mobile( $phone ) ) {
		pixva_ajax_error( __( 'شماره همراه معتبر نیست.', 'pixva' ), 422 );
	}
	if ( '' === $date ) {
		pixva_ajax_error( __( 'تاریخ مراجعه را انتخاب کنید.', 'pixva' ), 422 );
	}

	$estimate = pixva_calculate_estimate( $brand, '' !== $tech ? $tech : 'led', $size, $problem );
	if ( null === $estimate ) {
		pixva_ajax_error( __( 'برای ثبت نوبت، انتخاب برند، سایز و نوع خرابی الزامی است.', 'pixva' ), 422 );
	}

	$labels = pixva_calculator_labels();
	if ( ! empty( $estimate['panel_replacement'] ) ) {
		$range = pixva_panel_replacement_warning();
	} else {
		$range = sprintf(
			/* translators: 1: حداقل، 2: حداکثر */
			__( '%1$s تا %2$s تومان', 'pixva' ),
			pixva_price( $estimate['min'] ),
			pixva_price( $estimate['max'] )
		);
	}

	$summary = sprintf(
		/* translators: 1: برند، 2: سایز، 3: مشکل، 4: تاریخ، 5: بازه زمانی */
		__( '%1$s %2$s — %3$s | نوبت %4$s %5$s', 'pixva' ),
		isset( $labels['brand'][ $brand ] ) ? $labels['brand'][ $brand ] : $brand,
		isset( $labels['size'][ $size ] ) ? $labels['size'][ $size ] : $size,
		isset( $labels['problem'][ $problem ] ) ? $labels['problem'][ $problem ] : $problem,
		$date,
		$slot
	);

	$result = pixva_create_order(
		array(
			'phone'    => $phone,
			'brand'    => isset( $labels['brand'][ $brand ] ) ? $labels['brand'][ $brand ] : $brand,
			'model'    => $slot,
			'problem'  => $summary,
			'estimate' => $range,
		)
	);

	if ( empty( $result['id'] ) ) {
		pixva_ajax_error( __( 'ثبت نوبت انجام نشد.', 'pixva' ), 500 );
	}

	update_post_meta( (int) $result['id'], '_pixva_order_name', $name );
	update_post_meta( (int) $result['id'], '_pixva_request_kind', 'booking' );
	update_post_meta( (int) $result['id'], '_pixva_booking_date', $date );
	update_post_meta( (int) $result['id'], '_pixva_booking_slot', $slot );

	pixva_notify_admin(
		sprintf(
			/* translators: %s: کد پیگیری */
			__( 'نوبت رزروشده جدید: %s', 'pixva' ),
			$result['code']
		),
		sprintf(
			"کد پیگیری: %s\nنام: %s\nشماره: %s\nشرح: %s\nپیش‌فاکتور: %s\n",
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
			'estimate' => $range,
			'message'  => sprintf(
				/* translators: 1: تاریخ، 2: بازه زمانی */
				__( 'نوبت شما برای %1$s در بازه %2$s ثبت شد.', 'pixva' ),
				pixva_fa_num( $date ),
				pixva_fa_num( $slot )
			),
		)
	);
}

if ( ! function_exists( 'pixva_handle_tool_upload' ) ) {
	/**
	 * پردازش امن تصویر ارسالی در استعلام سریع.
	 *
	 * فقط JPEG/PNG/WebP تا سقف ۶ مگابایت پذیرفته می‌شود؛ در صورت نبود فایل،
	 * بدون خطا ادامه می‌دهیم.
	 *
	 * @param string $field نام فیلد فایل.
	 * @return array{path: string, file: string, error: string}
	 */
	function pixva_handle_tool_upload( $field = 'photo' ) {
		$empty = array(
			'path'  => '',
			'file'  => '',
			'error' => '',
		);

		if ( empty( $_FILES[ $field ] ) || empty( $_FILES[ $field ]['name'] ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
			return $empty;
		}

		$size = isset( $_FILES[ $field ]['size'] ) ? (int) $_FILES[ $field ]['size'] : 0; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		if ( $size > 6 * MB_IN_BYTES ) {
			$empty['error'] = __( 'حجم تصویر بیش از ۶ مگابایت است.', 'pixva' );
			return $empty;
		}

		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		$upload = wp_handle_upload(
			$_FILES[ $field ], // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
			array(
				'test_form' => false,
				'mimes'     => array(
					'jpg|jpeg' => 'image/jpeg',
					'png'      => 'image/png',
					'webp'     => 'image/webp',
				),
			)
		);

		if ( isset( $upload['error'] ) ) {
			$empty['error'] = sanitize_text_field( $upload['error'] );
			return $empty;
		}
		if ( empty( $upload['file'] ) ) {
			$empty['error'] = __( 'بارگذاری تصویر ناموفق بود.', 'pixva' );
			return $empty;
		}

		return array(
			'path'  => $upload['file'],
			'file'  => basename( $upload['file'] ),
			'error' => '',
		);
	}
}

/**
 * AJAX: استعلام سریع تصویری (ابزار ۲۰).
 *
 * پرونده با کد پیگیری ثبت می‌شود و اگر کلید Gemini تنظیم شده باشد، تحلیل واقعی
 * تصویر هم انجام و در پاسخ برگردانده می‌شود.
 *
 * @return void
 */
function pixva_ajax_quick_quote() {
	pixva_ajax_guard( 'pixva_order_nonce', 'quote', 6, HOUR_IN_SECONDS );

	$name    = pixva_get_post_var( 'customer_name' );
	$phone   = pixva_normalize_mobile( pixva_get_post_var( 'phone' ) );
	$brand   = sanitize_key( pixva_get_post_var( 'brand' ) );
	$size    = sanitize_key( pixva_get_post_var( 'size' ) );
	$problem = sanitize_key( pixva_get_post_var( 'problem' ) );

	if ( pixva_strlen( $name ) < 2 ) {
		pixva_ajax_error( __( 'نام را کامل وارد کنید.', 'pixva' ), 422 );
	}
	if ( ! pixva_is_valid_iranian_mobile( $phone ) ) {
		pixva_ajax_error( __( 'شماره همراه معتبر نیست.', 'pixva' ), 422 );
	}

	$upload = pixva_handle_tool_upload( 'photo' );
	if ( '' !== $upload['error'] ) {
		pixva_ajax_error( $upload['error'], 422 );
	}

	$labels = pixva_calculator_labels();
	$summary = sprintf(
		/* translators: 1: برند، 2: سایز، 3: مشکل */
		__( 'استعلام سریع: %1$s %2$s — %3$s', 'pixva' ),
		isset( $labels['brand'][ $brand ] ) ? $labels['brand'][ $brand ] : $brand,
		isset( $labels['size'][ $size ] ) ? $labels['size'][ $size ] : $size,
		isset( $labels['problem'][ $problem ] ) ? $labels['problem'][ $problem ] : $problem
	);

	$estimate = pixva_calculate_estimate( $brand, 'led', $size, $problem );
	$range    = '';
	if ( is_array( $estimate ) ) {
		$range = ! empty( $estimate['panel_replacement'] )
			? pixva_panel_replacement_warning()
			: sprintf(
				/* translators: 1: حداقل، 2: حداکثر */
				__( '%1$s تا %2$s تومان', 'pixva' ),
				pixva_price( $estimate['min'] ),
				pixva_price( $estimate['max'] )
			);
	}

	$result = pixva_create_order(
		array(
			'phone'    => $phone,
			'brand'    => isset( $labels['brand'][ $brand ] ) ? $labels['brand'][ $brand ] : $brand,
			'model'    => isset( $labels['size'][ $size ] ) ? $labels['size'][ $size ] : $size,
			'problem'  => $summary,
			'estimate' => $range,
		)
	);

	if ( empty( $result['id'] ) ) {
		pixva_ajax_error( __( 'ثبت استعلام انجام نشد.', 'pixva' ), 500 );
	}

	update_post_meta( (int) $result['id'], '_pixva_order_name', $name );
	update_post_meta( (int) $result['id'], '_pixva_request_kind', 'quote' );
	if ( '' !== $upload['file'] ) {
		update_post_meta( (int) $result['id'], '_pixva_quote_photo', $upload['file'] );
	}

	$analysis = '';
	if ( '' !== $upload['path'] && function_exists( 'pixva_ai_is_configured' ) && pixva_ai_is_configured() && function_exists( 'pixva_ai_analyze' ) ) {
		$response = pixva_ai_analyze(
			$summary,
			array( $upload['path'] ),
			array(
				'brand'   => $brand,
				'size'    => $size,
				'problem' => $problem,
			)
		);
		if ( is_array( $response ) && ! empty( $response['reply'] ) ) {
			$analysis = sanitize_textarea_field( $response['reply'] );
			update_post_meta( (int) $result['id'], '_pixva_ai_analysis', $analysis );
		}
	}

	pixva_notify_admin(
		sprintf(
			/* translators: %s: کد پیگیری */
			__( 'استعلام سریع تصویری: %s', 'pixva' ),
			$result['code']
		),
		sprintf(
			"کد پیگیری: %s\nنام: %s\nشماره: %s\nشرح: %s\nبرآورد: %s\nتصویر: %s\n",
			$result['code'],
			$name,
			$phone,
			$summary,
			$range,
			'' !== $upload['file'] ? $upload['file'] : 'بدون تصویر'
		)
	);

	pixva_ajax_success(
		array(
			'code'     => $result['code'],
			'estimate' => $range,
			'analysis' => $analysis,
			'message'  => __( 'استعلام شما ثبت شد و کارشناس پیکسوا حداکثر تا ۳۰ دقیقه در ساعات کاری تماس می‌گیرد.', 'pixva' ),
		)
	);
}

/**
 * AJAX: فرم‌های عمومی هاب‌ها (B2B، بیمه، اشتراک، بازخورد، یادآور، پیامک، سرویس در محل).
 *
 * @return void
 */
function pixva_ajax_tool_request() {
	pixva_ajax_guard( 'pixva_nonce', 'tool', 10, HOUR_IN_SECONDS );

	$kind   = sanitize_key( pixva_get_post_var( 'kind' ) );
	$kinds  = pixva_request_kind_labels();
	$phone  = pixva_normalize_mobile( pixva_get_post_var( 'phone' ) );
	$name   = pixva_get_post_var( 'customer_name' );
	$fields = array();

	if ( ! isset( $kinds[ $kind ] ) ) {
		pixva_ajax_error( __( 'نوع درخواست نامعتبر است.', 'pixva' ), 422 );
	}

	$collect = array( 'organization', 'devices', 'code', 'message', 'service_date', 'hours', 'zone', 'address', 'plan', 'policy', 'rating', 'stages' );
	foreach ( $collect as $key ) {
		$value = isset( $_POST[ $key ] ) ? wp_unslash( $_POST[ $key ] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( is_array( $value ) ) {
			$value = implode( '، ', array_map( 'sanitize_text_field', $value ) );
		}
		$fields[ $key ] = sanitize_textarea_field( (string) $value );
	}

	$needs_phone = in_array( $kind, array( 'b2b', 'onsite', 'subscription', 'reminder', 'sms' ), true );
	if ( $needs_phone && ! pixva_is_valid_iranian_mobile( $phone ) ) {
		pixva_ajax_error( __( 'شماره همراه معتبر نیست. نمونه درست: ۰۹۱۲۱۲۳۴۵۶۷', 'pixva' ), 422 );
	}

	if ( in_array( $kind, array( 'b2b', 'onsite', 'subscription', 'insurance' ), true ) && pixva_strlen( $name ) < 2 ) {
		pixva_ajax_error( __( 'نام و نام خانوادگی را کامل وارد کنید.', 'pixva' ), 422 );
	}

	if ( 'review' === $kind && pixva_strlen( $fields['message'] ) < 10 ) {
		pixva_ajax_error( __( 'لطفاً تجربه خود را در چند جمله کامل بنویسید.', 'pixva' ), 422 );
	}

	if ( 'insurance' === $kind && '' === $fields['policy'] ) {
		pixva_ajax_error( __( 'شماره بیمه‌نامه را وارد کنید.', 'pixva' ), 422 );
	}

	$lines = array(
		'نوع درخواست: ' . $kinds[ $kind ],
	);
	if ( '' !== $name ) {
		$lines[] = 'نام: ' . $name;
	}
	if ( '' !== $phone ) {
		$lines[] = 'شماره همراه: ' . $phone;
	}
	$labels = array(
		'organization' => 'سازمان',
		'devices'      => 'تعداد دستگاه',
		'code'         => 'کد پیگیری مرتبط',
		'message'      => 'شرح',
		'service_date' => 'تاریخ سرویس قبلی',
		'hours'        => 'ساعات کارکرد',
		'zone'         => 'منطقه',
		'address'      => 'نشانی',
		'plan'         => 'اشتراک انتخابی',
		'policy'       => 'شماره بیمه‌نامه',
		'rating'       => 'امتیاز',
		'stages'       => 'مراحل اطلاع‌رسانی',
	);
	foreach ( $labels as $key => $label ) {
		if ( '' !== $fields[ $key ] ) {
			$lines[] = $label . ': ' . $fields[ $key ];
		}
	}
	$lines[] = 'IP: ' . pixva_client_ip();

	$content = implode( "\n", $lines );
	$title   = sprintf(
		/* translators: 1: نوع درخواست، 2: نام یا شماره */
		__( '%1$s — %2$s', 'pixva' ),
		$kinds[ $kind ],
		'' !== $name ? $name : $phone
	);

	$post_id = pixva_create_inbox(
		$title,
		$content,
		array(
			'_pixva_request_kind'  => $kind,
			'_pixva_request_phone' => $phone,
		)
	);

	if ( ! $post_id ) {
		pixva_ajax_error( __( 'ثبت درخواست انجام نشد. لطفاً دوباره تلاش کنید.', 'pixva' ), 500 );
	}

	pixva_notify_admin( $title, $content );

	$messages = array(
		'b2b'          => __( 'درخواست قرارداد سازمانی ثبت شد. کارشناس B2B پیکسوا برای تنظیم پیش‌نویس قرارداد و بازدید از دستگاه‌ها تماس می‌گیرد.', 'pixva' ),
		'review'       => __( 'بازخورد شما ثبت شد. پس از تطبیق با پرونده واقعی و تأیید کارگاه در بخش نظرات منتشر می‌شود.', 'pixva' ),
		'reminder'     => __( 'یادآور سرویس دوره‌ای ثبت شد؛ ۳ روز قبل از سررسید پیامک ارسال می‌شود.', 'pixva' ),
		'sms'          => __( 'ترجیح اطلاع‌رسانی پیامکی ذخیره شد.', 'pixva' ),
		'onsite'       => __( 'درخواست سرویس در محل ثبت شد. هماهنگی زمان اعزام تلفنی انجام می‌شود.', 'pixva' ),
		'insurance'    => __( 'گزارش خسارت بیمه ثبت شد. کارشناسی رسمی و صدور فاکتور با سربرگ برای شرکت بیمه انجام می‌شود.', 'pixva' ),
		'subscription' => __( 'ثبت‌نام اشتراک دریافت شد. قرارداد سالانه و فاکتور رسمی پس از تماس کارشناس صادر می‌گردد.', 'pixva' ),
		'dispatch'     => __( 'درخواست اعزام ثبت شد.', 'pixva' ),
		'parts'        => __( 'درخواست تأمین قطعه ثبت شد. موجودی انبار بررسی و نتیجه اعلام می‌شود.', 'pixva' ),
	);

	pixva_ajax_success(
		array(
			'message' => isset( $messages[ $kind ] ) ? $messages[ $kind ] : __( 'درخواست شما ثبت شد.', 'pixva' ),
		)
	);
}

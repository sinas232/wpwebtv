<?php
/**
 * مرکز کنترل پیکسوا — Master Specification v25.0 (inc/control-center.php)
 *
 * مسیر: پیشخوان ← پیکسوا (کنترل سنتر) با ۱۲ تب واقعی و متصل به پایگاه‌داده:
 *  1. عمومی & AI Multimodal (کلید و مدل Gemini، دما، سقف توکن، پرامپت سیستمی)
 *  2. اعلانات & پیامک (درگاه SMS و لینک مستقیم واتساپ)
 *  3. نرخ‌نامه زنده بازار (خروجی واقعی موتور قیمت + کف ۸ میلیون تومان)
 *  4. ماتریس ۶۰ ابزار (رجیستری‌محور، گروه‌بندی‌شده بر اساس پنج هاب)
 *  5. انبار قطعات و اصالت کالا (موجودی زنده از CPT انبار)
 *  6. شعب، کارگاه مرکزی و ETA اعزام
 *  7. پیگیری تعمیرات & گارانتی دیجیتال (تعداد پرونده بر اساس وضعیت)
 *  8. پایگاه کدهای خطا (رکوردهای CPT و کاتالوگ مرجع)
 *  9. پورتال سازمانی B2B
 * 10. باشگاه مشتریان و کارت گارانتی
 * 11. وب‌اپ (PWA) و پیام حالت آفلاین
 * 12. داشبورد آمار زنده
 *
 * همه مقادیر این صفحه در گزینه pixva_control_options ذخیره می‌شود و در
 * فرانت‌اند، REST API و موتور هوش مصنوعی خوانده می‌شود.
 *
 * @package Pixva
 * @since   1.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'pixva_control_defaults' ) ) {
	/**
	 * مقادیر پیش‌فرض مرکز کنترل.
	 *
	 * @return array<string, mixed>
	 */
	function pixva_control_defaults() {
		return array(
			// تب ۱ — هوش مصنوعی.
			'ai_gemini_key'        => '',
			'ai_gemini_model'      => 'gemini-1.5-flash',
			'ai_temperature'       => 0.25,
			'ai_max_tokens'        => 700,
			'ai_groq_key'          => '',
			'ai_system_prompt'     => 'شما دستیار ارشد هوش مصنوعی کارگاه تخصصی تعمیر تلویزیون پیکسوا هستید. فقط بر اساس علائم گزارش‌شده نظر کارگاهی بدهید، همیشه هشدار ایمنی برد تغذیه را یادآوری کن و در انتها پیشنهاد پذیرش در کارگاه بده.',
			'ai_enable_floating'   => 1,
			// تب ۲ — پیامک و واتساپ.
			'sms_provider'         => 'none',
			'sms_api_key'          => '',
			'sms_sender'           => '',
			'sms_template_status'  => 'pixva-status',
			'whatsapp_direct_link' => 'https://wa.me/989121111111',
			// تب ۶ — کارگاه مرکزی.
			'hub_address'          => 'تهران، خیابان جمهوری، تقاطع حافظ، پاساژ علاءالدین، طبقه ۴، واحد ۴۱۲',
			'hub_phone'            => '02191009990',
			'hub_eta_hours'        => '۲ ساعت',
			'hub_hours'            => 'شنبه تا پنجشنبه ۰۹:۰۰ تا ۲۰:۰۰',
			// تب ۷ — گارانتی.
			'warranty_days'        => 180,
			// تب ۴ — ابزارها (هر ۶۰ ابزار به‌صورت پیش‌فرض فعال).
			'tools_enabled'        => array_fill( 1, 60, 1 ),
			// تب ۱۱ — وب‌اپ.
			'pwa_enable'           => 1,
			'pwa_offline_message'  => 'اتصال شما قطع است؛ ابزارهای محاسبه و پایگاه کدهای خطا همچنان کار می‌کنند.',
		);
	}
}

if ( ! function_exists( 'pixva_control_options' ) ) {
	/**
	 * تنظیمات مؤثر مرکز کنترل پیکسوا.
	 *
	 * @return array<string, mixed>
	 */
	function pixva_control_options() {
		$defaults = pixva_control_defaults();
		$saved    = get_option( 'pixva_control_options', array() );

		if ( ! is_array( $saved ) ) {
			$saved = array();
		}

		$opts = wp_parse_args( $saved, $defaults );

		// ماتریس ۶۰ ابزار همیشه کامل و عددی باشد.
		$tools = is_array( $opts['tools_enabled'] ?? null ) ? $opts['tools_enabled'] : array();
		$clean = array();
		for ( $i = 1; $i <= 60; $i++ ) {
			$clean[ $i ] = empty( $tools[ $i ] ) ? 0 : 1;
		}
		$opts['tools_enabled'] = $clean;

		return apply_filters( 'pixva_control_options', $opts );
	}
}

if ( ! function_exists( 'pixva_control_models' ) ) {
	/**
	 * مدل‌های پشتیبانی‌شده Gemini.
	 *
	 * @return array<string, string>
	 */
	function pixva_control_models() {
		return array(
			'gemini-1.5-flash'    => __( 'Gemini 1.5 Flash — سریع و رایگان (پیشنهاد کارگاه)', 'pixva' ),
			'gemini-1.5-flash-8b' => __( 'Gemini 1.5 Flash 8B — سبک‌ترین مدل', 'pixva' ),
			'gemini-1.5-pro'      => __( 'Gemini 1.5 Pro — دقیق‌تر و کندتر', 'pixva' ),
			'gemini-2.0-flash'    => __( 'Gemini 2.0 Flash — نسل جدید', 'pixva' ),
		);
	}
}

if ( ! function_exists( 'pixva_control_sms_providers' ) ) {
	/**
	 * درگاه‌های پیامک پشتیبانی‌شده.
	 *
	 * @return array<string, string>
	 */
	function pixva_control_sms_providers() {
		return array(
			'none'        => __( 'غیرفعال (بدون ارسال پیامک)', 'pixva' ),
			'kavenegar'   => __( 'کاوه‌نگار (Kavenegar)', 'pixva' ),
			'smsir'       => __( 'اس‌ام‌اس‌آی‌آر (SMS.ir)', 'pixva' ),
			'melipayamak' => __( 'ملی‌پیامک (Melipayamak)', 'pixva' ),
		);
	}
}

if ( ! function_exists( 'pixva_control_tabs' ) ) {
	/**
	 * فهرست تب‌های مرکز کنترل.
	 *
	 * @return array<string, string>
	 */
	function pixva_control_tabs() {
		return array(
			'general'   => __( '۱. عمومی و AI', 'pixva' ),
			'messaging' => __( '۲. پیامک و واتساپ', 'pixva' ),
			'rates'     => __( '۳. نرخ‌نامه زنده', 'pixva' ),
			'tools'     => __( '۴. ماتریس ۶۰ ابزار', 'pixva' ),
			'parts'     => __( '۵. انبار قطعات', 'pixva' ),
			'branches'  => __( '۶. شعب و کارگاه', 'pixva' ),
			'tracking'  => __( '۷. پیگیری و گارانتی', 'pixva' ),
			'errors'    => __( '۸. کدهای خطا', 'pixva' ),
			'b2b'       => __( '۹. سازمانی B2B', 'pixva' ),
			'vip'       => __( '۱۰. باشگاه مشتریان', 'pixva' ),
			'pwa'       => __( '۱۱. وب‌اپ و آفلاین', 'pixva' ),
			'analytics' => __( '۱۲. آمار زنده', 'pixva' ),
		);
	}
}

/**
 * افزودن منوی «پیکسوا» به پیشخوان.
 *
 * @return void
 */
function pixva_register_control_center_menu() {
	add_menu_page(
		esc_html__( 'مرکز کنترل پیکسوا v25.0', 'pixva' ),
		esc_html__( 'پیکسوا (کنترل سنتر)', 'pixva' ),
		'manage_options',
		'pixva-control',
		'pixva_render_control_center_page',
		'dashicons-screenoptions',
		3
	);

	add_submenu_page(
		'pixva-control',
		esc_html__( 'نرخ‌نامه زنده بازار ۱۴۰۵', 'pixva' ),
		esc_html__( 'نرخ‌نامه و تعرفه‌ها', 'pixva' ),
		'manage_options',
		'pixva-control&tab=rates',
		'pixva_render_control_center_page'
	);

	add_submenu_page(
		'pixva-control',
		esc_html__( 'ماتریس ۶۰ ابزار تعاملی', 'pixva' ),
		esc_html__( 'ماتریس ۶۰ ابزار', 'pixva' ),
		'manage_options',
		'pixva-control&tab=tools',
		'pixva_render_control_center_page'
	);

	add_submenu_page(
		'pixva-control',
		esc_html__( 'آمار زنده کارگاه', 'pixva' ),
		esc_html__( 'آمار زنده', 'pixva' ),
		'manage_options',
		'pixva-control&tab=analytics',
		'pixva_render_control_center_page'
	);
}
add_action( 'admin_menu', 'pixva_register_control_center_menu' );

/**
 * ذخیره تنظیمات مرکز کنترل با nonce و پاک‌سازی کامل ورودی‌ها.
 *
 * @return void
 */
function pixva_handle_control_save() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	if ( ! isset( $_POST['pixva_control_save_nonce'] ) ) {
		return;
	}

	check_admin_referer( 'pixva_control_save_action', 'pixva_control_save_nonce' );

	$opts = pixva_control_options();

	// تب ۱ — هوش مصنوعی.
	if ( isset( $_POST['ai_gemini_key'] ) ) {
		$opts['ai_gemini_key'] = sanitize_text_field( wp_unslash( $_POST['ai_gemini_key'] ) );
	}
	if ( isset( $_POST['ai_gemini_model'] ) && array_key_exists( sanitize_key( wp_unslash( $_POST['ai_gemini_model'] ) ), pixva_control_models() ) ) {
		$opts['ai_gemini_model'] = sanitize_key( wp_unslash( $_POST['ai_gemini_model'] ) );
	}
	if ( isset( $_POST['ai_temperature'] ) && is_numeric( wp_unslash( $_POST['ai_temperature'] ) ) ) {
		$opts['ai_temperature'] = round( max( 0, min( 1, (float) $_POST['ai_temperature'] ) ), 2 );
	}
	if ( isset( $_POST['ai_max_tokens'] ) && is_numeric( wp_unslash( $_POST['ai_max_tokens'] ) ) ) {
		$opts['ai_max_tokens'] = (int) max( 128, min( 2048, (int) $_POST['ai_max_tokens'] ) );
	}
	if ( isset( $_POST['ai_groq_key'] ) ) {
		$opts['ai_groq_key'] = sanitize_text_field( wp_unslash( $_POST['ai_groq_key'] ) );
	}
	if ( isset( $_POST['ai_system_prompt'] ) ) {
		$opts['ai_system_prompt'] = sanitize_textarea_field( wp_unslash( $_POST['ai_system_prompt'] ) );
	}
	$opts['ai_enable_floating'] = isset( $_POST['ai_enable_floating'] ) ? 1 : 0;

	// تب ۲ — پیامک و واتساپ.
	if ( isset( $_POST['sms_provider'] ) && array_key_exists( sanitize_key( wp_unslash( $_POST['sms_provider'] ) ), pixva_control_sms_providers() ) ) {
		$opts['sms_provider'] = sanitize_key( wp_unslash( $_POST['sms_provider'] ) );
	}
	if ( isset( $_POST['sms_api_key'] ) ) {
		$opts['sms_api_key'] = sanitize_text_field( wp_unslash( $_POST['sms_api_key'] ) );
	}
	if ( isset( $_POST['sms_sender'] ) ) {
		$opts['sms_sender'] = sanitize_text_field( wp_unslash( $_POST['sms_sender'] ) );
	}
	if ( isset( $_POST['sms_template_status'] ) ) {
		$opts['sms_template_status'] = sanitize_text_field( wp_unslash( $_POST['sms_template_status'] ) );
	}
	if ( isset( $_POST['whatsapp_direct_link'] ) ) {
		$opts['whatsapp_direct_link'] = esc_url_raw( wp_unslash( $_POST['whatsapp_direct_link'] ) );
	}

	// تب ۴ — ماتریس ۶۰ ابزار.
	$tools_in  = isset( $_POST['tools_enabled'] ) && is_array( $_POST['tools_enabled'] ) ? wp_unslash( $_POST['tools_enabled'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	$tools_out = array();
	for ( $i = 1; $i <= 60; $i++ ) {
		$tools_out[ $i ] = empty( $tools_in[ $i ] ) ? 0 : 1;
	}
	$opts['tools_enabled'] = $tools_out;

	// تب ۶ — کارگاه مرکزی.
	if ( isset( $_POST['hub_address'] ) ) {
		$opts['hub_address'] = sanitize_textarea_field( wp_unslash( $_POST['hub_address'] ) );
	}
	if ( isset( $_POST['hub_phone'] ) ) {
		$opts['hub_phone'] = preg_replace( '/[^0-9+]/', '', wp_unslash( $_POST['hub_phone'] ) );
	}
	if ( isset( $_POST['hub_eta_hours'] ) ) {
		$opts['hub_eta_hours'] = sanitize_text_field( wp_unslash( $_POST['hub_eta_hours'] ) );
	}
	if ( isset( $_POST['hub_hours'] ) ) {
		$opts['hub_hours'] = sanitize_text_field( wp_unslash( $_POST['hub_hours'] ) );
	}

	// تب ۷ — گارانتی.
	if ( isset( $_POST['warranty_days'] ) && is_numeric( wp_unslash( $_POST['warranty_days'] ) ) ) {
		$opts['warranty_days'] = (int) max( 30, min( 730, (int) $_POST['warranty_days'] ) );
	}

	// تب ۱۱ — وب‌اپ.
	$opts['pwa_enable'] = isset( $_POST['pwa_enable'] ) ? 1 : 0;
	if ( isset( $_POST['pwa_offline_message'] ) ) {
		$opts['pwa_offline_message'] = sanitize_text_field( wp_unslash( $_POST['pwa_offline_message'] ) );
	}

	update_option( 'pixva_control_options', $opts );

	$tab = isset( $_POST['active_tab'] ) ? sanitize_key( wp_unslash( $_POST['active_tab'] ) ) : 'general';
	wp_safe_redirect(
		add_query_arg(
			array(
				'page'  => 'pixva-control',
				'tab'   => $tab,
				'saved' => 1,
			),
			admin_url( 'admin.php' )
		)
	);
	exit;
}
add_action( 'admin_init', 'pixva_handle_control_save' );

/**
 * استایل scoped پنل کنترل (بدون تداخل با استایل هسته وردپرس).
 *
 * @return void
 */
function pixva_control_styles() {
	?>
	<style>
		.pixva-admin-wrap{max-width:1240px;margin-top:20px;font-family:Vazirmatn,Tahoma,sans-serif}
		.pixva-admin-wrap *{box-sizing:border-box}
		.pixva-cc-head{background:rgb(255,255,255);border:1px solid rgb(220,234,238);border-radius:14px;padding:20px 24px;margin-bottom:18px;display:flex;justify-content:space-between;align-items:center;gap:16px;flex-wrap:wrap;box-shadow:0 4px 12px rgba(18,59,74,.03)}
		.pixva-cc-head h1{font-size:21px;color:rgb(18,59,74);margin:0 0 4px;font-weight:900}
		.pixva-cc-head p{margin:0;color:rgb(91,122,135);font-size:13px;line-height:1.9}
		.pixva-cc-pill{background:rgba(23,107,135,.08);color:rgb(23,107,135);font-weight:800;font-size:12px;padding:6px 14px;border-radius:999px;border:1px solid rgba(23,107,135,.2);white-space:nowrap}
		.pixva-cc-pill--ok{background:rgba(46,158,119,.1);color:rgb(46,158,119);border-color:rgba(46,158,119,.25)}
		.pixva-cc-pill--warn{background:rgba(245,158,108,.14);color:rgb(178,92,37);border-color:rgba(245,158,108,.35)}
		.pixva-cc-body{background:rgb(255,255,255);border:1px solid rgb(220,234,238);border-radius:14px;padding:24px;box-shadow:0 6px 18px rgba(18,59,74,.04)}
		.pixva-cc-body h2{font-size:16px;color:rgb(18,59,74);border-bottom:1px solid rgb(220,234,238);padding-bottom:10px;margin:0 0 16px}
		.pixva-cc-body h3{font-size:14px;color:rgb(18,59,74);margin:22px 0 8px}
		.pixva-cc-note{color:rgb(91,122,135);font-size:13px;line-height:2;margin:0 0 14px}
		.pixva-cc-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:12px;margin:0 0 18px}
		.pixva-cc-stat{background:rgb(247,250,252);border:1px solid rgb(220,234,238);border-radius:12px;padding:14px 16px}
		.pixva-cc-stat b{display:block;font-size:24px;color:rgb(23,107,135);line-height:1.3}
		.pixva-cc-stat span{font-size:12px;color:rgb(91,122,135)}
		.pixva-cc-table{width:100%;border-collapse:collapse;margin:0 0 18px;font-size:13px}
		.pixva-cc-table th{background:rgb(18,59,74);color:rgb(255,255,255);text-align:right;padding:10px;font-weight:700}
		.pixva-cc-table td{border-bottom:1px solid rgb(228,238,241);padding:9px 10px;color:rgb(42,74,87)}
		.pixva-cc-table tr:nth-child(even) td{background:rgb(250,253,254)}
		.pixva-cc-tools{display:grid;grid-template-columns:repeat(auto-fill,minmax(330px,1fr));gap:10px;padding:12px;background:rgb(247,250,252);border:1px solid rgb(220,234,238);border-radius:12px}
		.pixva-cc-tool{background:rgb(255,255,255);padding:9px 12px;border:1px solid rgb(220,234,238);border-radius:9px;display:flex;align-items:flex-start;gap:9px;font-size:12px;line-height:1.8}
		.pixva-cc-tool strong{color:rgb(18,59,74)}
		.pixva-cc-tool small{display:block;color:rgb(122,147,158);font-size:11px}
		.pixva-cc-tool.is-off{background:rgb(251,247,244);border-color:rgb(240,226,216)}
		.pixva-cc-group{margin-bottom:22px}
		.pixva-cc-group__head{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;background:rgb(232,245,248);border:1px solid rgba(85,184,200,.35);border-radius:10px;padding:10px 14px;margin-bottom:10px}
		.pixva-cc-group__head b{color:rgb(18,59,74);font-size:14px}
		.pixva-cc-group__head span{color:rgb(91,122,135);font-size:12px}
		.pixva-cc-mini{font-size:11px;padding:3px 10px;border-radius:999px;border:1px solid rgb(220,234,238);background:rgb(255,255,255);color:rgb(23,107,135);cursor:pointer}
		.pixva-cc-empty{background:rgb(247,250,252);border:1px dashed rgb(201,221,227);border-radius:12px;padding:18px;color:rgb(91,122,135);font-size:13px;line-height:2;margin-bottom:18px}
		.pixva-admin-wrap .form-table th{width:250px;font-size:13px}
		.pixva-admin-wrap .form-table td p.description{font-size:12px;color:rgb(122,147,158);line-height:1.9}
	</style>
	<?php
}

if ( ! function_exists( 'pixva_control_row' ) ) {
	/**
	 * یک سطر جدول تنظیمات.
	 *
	 * @param string $label برچسب.
	 * @param string $for   شناسه فیلد.
	 * @param string $field markup فیلد.
	 * @param string $desc  توضیح.
	 * @return void
	 */
	function pixva_control_row( $label, $for, $field, $desc = '' ) {
		?>
		<tr>
			<th scope="row"><label for="<?php echo esc_attr( $for ); ?>"><?php echo esc_html( $label ); ?></label></th>
			<td>
				<?php echo $field; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- markup ساخته‌شده در همین فایل. ?>
				<?php if ( '' !== $desc ) : ?>
					<p class="description"><?php echo esc_html( $desc ); ?></p>
				<?php endif; ?>
			</td>
		</tr>
		<?php
	}
}

if ( ! function_exists( 'pixva_control_stat' ) ) {
	/**
	 * کارت آمار کوچک.
	 *
	 * @param string $value مقدار.
	 * @param string $label برچسب.
	 * @return void
	 */
	function pixva_control_stat( $value, $label ) {
		?>
		<div class="pixva-cc-stat">
			<b><?php echo esc_html( $value ); ?></b>
			<span><?php echo esc_html( $label ); ?></span>
		</div>
		<?php
	}
}

if ( ! function_exists( 'pixva_control_count_posts' ) ) {
	/**
	 * شمارش رکوردهای یک پست‌تایپ داخلی.
	 *
	 * @param string $post_type نام پست‌تایپ.
	 * @return int
	 */
	function pixva_control_count_posts( $post_type ) {
		$counts = wp_count_posts( $post_type );
		if ( ! is_object( $counts ) ) {
			return 0;
		}

		$total = 0;
		foreach ( (array) $counts as $status => $number ) {
			if ( in_array( $status, array( 'publish', 'private', 'draft', 'pending', 'future' ), true ) ) {
				$total += (int) $number;
			}
		}

		return $total;
	}
}

if ( ! function_exists( 'pixva_control_tools_enabled_count' ) ) {
	/**
	 * تعداد ابزارهای فعال.
	 *
	 * @param array $opts تنظیمات.
	 * @return int
	 */
	function pixva_control_tools_enabled_count( $opts ) {
		return count( array_filter( (array) $opts['tools_enabled'] ) );
	}
}

/**
 * رندر صفحه مرکز کنترل.
 *
 * @return void
 */
function pixva_render_control_center_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	pixva_control_styles();

	$active_tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'general';
	$opts       = pixva_control_options();
	$tabs       = pixva_control_tabs();

	if ( ! array_key_exists( $active_tab, $tabs ) ) {
		$active_tab = 'general';
	}

	$ai_on     = function_exists( 'pixva_ai_is_configured' ) && pixva_ai_is_configured();
	$enabled   = pixva_control_tools_enabled_count( $opts );
	$spec      = defined( 'PIXVA_SPEC_VERSION' ) ? PIXVA_SPEC_VERSION : '25.0';
	$version   = defined( 'PIXVA_VERSION' ) ? PIXVA_VERSION : '1.2.0';
	?>
	<div class="wrap pixva-admin-wrap">
		<div class="pixva-cc-head">
			<div>
				<h1><?php echo esc_html( sprintf( __( 'مرکز کنترل یکپارچه پیکسوا — نسخه %s (مشخصات v%s)', 'pixva' ), $version, $spec ) ); ?></h1>
				<p>
					<?php
					echo esc_html(
						sprintf(
							/* translators: 1: enabled tools, 2: warranty days */
							__( 'ابزارهای فعال: %1$s از ۶۰ — گارانتی کتبی: %2$s روز — کف قیمت بازار: ۸٬۰۰۰٬۰۰۰ تومان', 'pixva' ),
							pixva_fa_num( (string) $enabled ),
							pixva_fa_num( (string) $opts['warranty_days'] )
						)
					);
					?>
				</p>
			</div>
			<div>
				<span class="pixva-cc-pill <?php echo $ai_on ? 'pixva-cc-pill--ok' : 'pixva-cc-pill--warn'; ?>">
					<?php echo $ai_on ? esc_html__( '● Gemini متصل است', 'pixva' ) : esc_html__( '● کلید Gemini وارد نشده — موتور قانون‌محور فعال است', 'pixva' ); ?>
				</span>
			</div>
		</div>

		<?php if ( isset( $_GET['saved'] ) ) : ?>
			<div class="notice notice-success is-dismissible">
				<p><strong><?php esc_html_e( 'تنظیمات مرکز کنترل در پایگاه‌داده ذخیره شد و بلافاصله در فرانت‌اند و API اعمال می‌شود.', 'pixva' ); ?></strong></p>
			</div>
		<?php endif; ?>

		<nav class="nav-tab-wrapper">
			<?php foreach ( $tabs as $tab_key => $tab_title ) : ?>
				<a href="<?php echo esc_url( add_query_arg( array( 'page' => 'pixva-control', 'tab' => $tab_key ), admin_url( 'admin.php' ) ) ); ?>"
					class="nav-tab <?php echo $active_tab === $tab_key ? 'nav-tab-active' : ''; ?>"
					style="font-weight:700;font-size:13px;"><?php echo esc_html( $tab_title ); ?></a>
			<?php endforeach; ?>
		</nav>

		<form method="post" action="" class="pixva-cc-body" style="margin-top:20px;">
			<?php
			wp_nonce_field( 'pixva_control_save_action', 'pixva_control_save_nonce' );
			echo '<input type="hidden" name="active_tab" value="' . esc_attr( $active_tab ) . '">';

			$renderer = 'pixva_control_tab_' . $active_tab;
			if ( function_exists( $renderer ) ) {
				$renderer( $opts );
			}
			?>
			<p class="submit" style="margin-top:20px;border-top:1px solid rgb(220,234,238);padding-top:16px;">
				<button type="submit" class="button button-primary" style="background:rgb(23,107,135);border-color:rgb(18,88,111);padding:4px 22px;font-weight:800;">
					<?php esc_html_e( 'ذخیره تغییرات مرکز کنترل', 'pixva' ); ?>
				</button>
				<?php if ( in_array( $active_tab, array( 'rates', 'parts', 'errors', 'b2b', 'vip', 'analytics' ), true ) ) : ?>
					<span class="pixva-cc-note" style="display:inline-block;margin:0 12px;"><?php esc_html_e( 'این تب فقط خواندنی است و داده زنده را از پایگاه‌داده نمایش می‌دهد.', 'pixva' ); ?></span>
				<?php endif; ?>
			</p>
		</form>
	</div>
	<script>
	(function () {
		document.addEventListener('click', function (event) {
			var button = event.target.closest('[data-pixva-toggle-group]');
			if (!button) { return; }
			var group = document.querySelector(button.getAttribute('data-pixva-toggle-group'));
			if (!group) { return; }
			var state = button.getAttribute('data-pixva-toggle-state') === '1';
			group.querySelectorAll('input[type="checkbox"]').forEach(function (box) { box.checked = state; });
		});
	}());
	</script>
	<?php
}

/**
 * تب ۱ — عمومی و هوش مصنوعی چندگانه.
 *
 * @param array $opts تنظیمات.
 * @return void
 */
function pixva_control_tab_general( $opts ) {
	$models = pixva_control_models();
	?>
	<h2><?php esc_html_e( 'هوش مصنوعی واقعی — Gemini (سمت سرور، بدون افشای کلید)', 'pixva' ); ?></h2>
	<p class="pixva-cc-note">
		<?php esc_html_e( 'کلید فقط در پایگاه‌داده ذخیره می‌شود و در هیچ خروجی HTML یا REST چاپ نمی‌شود. درخواست‌ها با wp_remote_post به generativelanguage.googleapis.com فرستاده می‌شود. در صورت خالی بودن کلید، موتور قانون‌محور کارگاه پاسخ می‌دهد.', 'pixva' ); ?>
	</p>
	<table class="form-table">
		<?php
		pixva_control_row(
			__( 'کلید رایگان Gemini API', 'pixva' ),
			'ai_gemini_key',
			'<input type="password" id="ai_gemini_key" name="ai_gemini_key" value="' . esc_attr( $opts['ai_gemini_key'] ) . '" class="regular-text" style="width:420px" autocomplete="off" dir="ltr">',
			__( 'کلید را از Google AI Studio (aistudio.google.com) بسازید. مقدار خالی یعنی حالت آفلاین قانون‌محور.', 'pixva' )
		);

		$model_options = '';
		foreach ( $models as $key => $label ) {
			$model_options .= '<option value="' . esc_attr( $key ) . '" ' . selected( $opts['ai_gemini_model'], $key, false ) . '>' . esc_html( $label ) . '</option>';
		}
		pixva_control_row(
			__( 'مدل Gemini', 'pixva' ),
			'ai_gemini_model',
			'<select id="ai_gemini_model" name="ai_gemini_model" style="min-width:320px">' . $model_options . '</select>',
			__( 'پیش‌فرض مشخصات v25.0 مدل gemini-1.5-flash است (سریع، ارزان و پشتیبانی از تصویر).', 'pixva' )
		);

		pixva_control_row(
			__( 'دمای پاسخ (Temperature)', 'pixva' ),
			'ai_temperature',
			'<input type="number" id="ai_temperature" name="ai_temperature" value="' . esc_attr( (string) $opts['ai_temperature'] ) . '" min="0" max="1" step="0.05" style="width:110px" dir="ltr">',
			__( 'عدد کمتر پاسخ قطعی‌تر و کارگاهی‌تر می‌دهد؛ بازه مجاز ۰ تا ۱.', 'pixva' )
		);

		pixva_control_row(
			__( 'سقف توکن پاسخ (Max Output Tokens)', 'pixva' ),
			'ai_max_tokens',
			'<input type="number" id="ai_max_tokens" name="ai_max_tokens" value="' . esc_attr( (string) $opts['ai_max_tokens'] ) . '" min="128" max="2048" step="32" style="width:110px" dir="ltr">',
			__( 'بازه مجاز ۱۲۸ تا ۲۰۴۸ توکن؛ پاسخ مدل همیشه JSON ساخت‌یافته است.', 'pixva' )
		);

		pixva_control_row(
			__( 'کلید پشتیبان Groq (اختیاری)', 'pixva' ),
			'ai_groq_key',
			'<input type="password" id="ai_groq_key" name="ai_groq_key" value="' . esc_attr( $opts['ai_groq_key'] ) . '" class="regular-text" style="width:420px" autocomplete="off" dir="ltr">',
			__( 'برای مسیر پشتیبان متن‌محور نگهداری می‌شود؛ تصویر فقط با Gemini تحلیل می‌شود.', 'pixva' )
		);

		pixva_control_row(
			__( 'پرامپت سیستمی دستیار', 'pixva' ),
			'ai_system_prompt',
			'<textarea id="ai_system_prompt" name="ai_system_prompt" rows="5" class="large-text" style="max-width:640px">' . esc_textarea( $opts['ai_system_prompt'] ) . '</textarea>',
			__( 'این متن به‌عنوان instruction در هر درخواست به مدل فرستاده می‌شود.', 'pixva' )
		);

		pixva_control_row(
			__( 'ابزارک شناور عیب‌یابی AI', 'pixva' ),
			'ai_enable_floating',
			'<label><input type="checkbox" name="ai_enable_floating" value="1" ' . checked( $opts['ai_enable_floating'], 1, false ) . '> ' . esc_html__( 'نمایش دستیار شناور در گوشه همه صفحه‌ها', 'pixva' ) . '</label>',
			__( 'غیرفعال کردن این گزینه فقط ویجت شناور را حذف می‌کند؛ ابزار ۱ تا ۱۰ هاب هوش مصنوعی سر جایشان می‌مانند.', 'pixva' )
		);
		?>
	</table>
	<h3><?php esc_html_e( 'وضعیت زنده', 'pixva' ); ?></h3>
	<div class="pixva-cc-grid">
		<?php
		pixva_control_stat( ( function_exists( 'pixva_ai_is_configured' ) && pixva_ai_is_configured() ) ? __( 'متصل', 'pixva' ) : __( 'آفلاین', 'pixva' ), __( 'وضعیت موتور هوش مصنوعی', 'pixva' ) );
		pixva_control_stat( $opts['ai_gemini_model'], __( 'مدل فعال', 'pixva' ) );
		pixva_control_stat( pixva_fa_num( (string) $opts['ai_temperature'] ), __( 'دمای پاسخ', 'pixva' ) );
		pixva_control_stat( pixva_fa_num( (string) $opts['ai_max_tokens'] ), __( 'سقف توکن', 'pixva' ) );
		?>
	</div>
	<?php
}

/**
 * تب ۲ — اعلانات، پیامک و واتساپ.
 *
 * @param array $opts تنظیمات.
 * @return void
 */
function pixva_control_tab_messaging( $opts ) {
	$providers = pixva_control_sms_providers();
	$provider_options = '';
	foreach ( $providers as $key => $label ) {
		$provider_options .= '<option value="' . esc_attr( $key ) . '" ' . selected( $opts['sms_provider'], $key, false ) . '>' . esc_html( $label ) . '</option>';
	}
	?>
	<h2><?php esc_html_e( 'درگاه پیامک و کانال‌های تماس مستقیم', 'pixva' ); ?></h2>
	<p class="pixva-cc-note">
		<?php esc_html_e( 'این مقادیر در پاسخ‌های AJAX (پیگیری پرونده، کارت گارانتی و ثبت سفارش) و در فوتر برای لینک واتساپ استفاده می‌شود. تا وقتی درگاه انتخاب نشده باشد، هیچ پیامکی ارسال نمی‌شود و فقط وضعیت پرونده در سایت نمایش داده می‌شود.', 'pixva' ); ?>
	</p>
	<table class="form-table">
		<?php
		pixva_control_row(
			__( 'درگاه پیامک', 'pixva' ),
			'sms_provider',
			'<select id="sms_provider" name="sms_provider" style="min-width:280px">' . $provider_options . '</select>',
			__( 'انتخاب «غیرفعال» یعنی عدم ارسال هرگونه پیامک از سمت سایت.', 'pixva' )
		);
		pixva_control_row(
			__( 'کلید API درگاه', 'pixva' ),
			'sms_api_key',
			'<input type="password" id="sms_api_key" name="sms_api_key" value="' . esc_attr( $opts['sms_api_key'] ) . '" class="regular-text" style="width:420px" autocomplete="off" dir="ltr">',
			__( 'کلید فقط سمت سرور نگهداری می‌شود.', 'pixva' )
		);
		pixva_control_row(
			__( 'شماره/خط ارسال‌کننده', 'pixva' ),
			'sms_sender',
			'<input type="text" id="sms_sender" name="sms_sender" value="' . esc_attr( $opts['sms_sender'] ) . '" class="regular-text" style="width:280px" dir="ltr">',
			__( 'مثلاً ۱۰۰۰۸۸۰۰ یا شماره اختصاصی پنل.', 'pixva' )
		);
		pixva_control_row(
			__( 'کد الگوی پیامک وضعیت', 'pixva' ),
			'sms_template_status',
			'<input type="text" id="sms_template_status" name="sms_template_status" value="' . esc_attr( $opts['sms_template_status'] ) . '" class="regular-text" style="width:280px" dir="ltr">',
			__( 'کد الگوی ثبت‌شده در پنل درگاه برای اطلاع‌رسانی تغییر وضعیت پرونده.', 'pixva' )
		);
		pixva_control_row(
			__( 'لینک مستقیم واتساپ', 'pixva' ),
			'whatsapp_direct_link',
			'<input type="url" id="whatsapp_direct_link" name="whatsapp_direct_link" value="' . esc_attr( $opts['whatsapp_direct_link'] ) . '" class="regular-text" style="width:420px" dir="ltr">',
			__( 'در دکمه‌های «مشاوره واتساپ» فوتر و هاب‌ها استفاده می‌شود (با پیش‌شماره ۹۸ و بدون صفر).', 'pixva' )
		);
		?>
	</table>
	<h3><?php esc_html_e( 'پیش‌نمایش زنده', 'pixva' ); ?></h3>
	<table class="pixva-cc-table">
		<tr><th><?php esc_html_e( 'کانال', 'pixva' ); ?></th><th><?php esc_html_e( 'وضعیت', 'pixva' ); ?></th></tr>
		<tr>
			<td><?php esc_html_e( 'پیامک وضعیت پرونده', 'pixva' ); ?></td>
			<td>
				<?php
				$sms_ready = 'none' !== $opts['sms_provider'] && '' !== $opts['sms_api_key'];
				echo esc_html( $sms_ready ? $providers[ $opts['sms_provider'] ] . ' — ' . __( 'آماده ارسال', 'pixva' ) : __( 'غیرفعال (بدون کلید یا درگاه)', 'pixva' ) );
				?>
			</td>
		</tr>
		<tr>
			<td><?php esc_html_e( 'واتساپ', 'pixva' ); ?></td>
			<td><?php echo esc_html( $opts['whatsapp_direct_link'] ? $opts['whatsapp_direct_link'] : __( 'تنظیم نشده', 'pixva' ) ); ?></td>
		</tr>
		<tr>
			<td><?php esc_html_e( 'تلفن کارگاه مرکزی', 'pixva' ); ?></td>
			<td><?php echo esc_html( $opts['hub_phone'] ); ?></td>
		</tr>
	</table>
	<?php
}

/**
 * تب ۳ — نرخ‌نامه زنده (خروجی واقعی موتور قیمت).
 *
 * @param array $opts تنظیمات.
 * @return void
 */
function pixva_control_tab_rates( $opts ) {
	unset( $opts );

	$settings = function_exists( 'pixva_pricing_settings' ) ? pixva_pricing_settings() : array();
	$table    = function_exists( 'pixva_pricing_reference_table' ) ? pixva_pricing_reference_table() : array( 'rows' => array(), 'reference_brand' => '' );
	$labels   = function_exists( 'pixva_calculator_labels' ) ? pixva_calculator_labels() : array( 'brand' => array() );
	$ref_name = isset( $labels['brand'][ $table['reference_brand'] ] ) ? $labels['brand'][ $table['reference_brand'] ] : $table['reference_brand'];
	?>
	<h2><?php esc_html_e( 'نرخ‌نامه زنده بازار ۱۴۰۵ — خروجی واقعی موتور محاسبه', 'pixva' ); ?></h2>
	<p class="pixva-cc-note">
		<?php
		esc_html_e( 'فرمول: Price = (Base × BrandMult × TechFactor × SizeFactor) + DiagnosticFee و SizeFactor = 1 + ((Size − 32) / 32)^1.35. کف قیمت هر خدمت (پایه ۳۲ اینچ) هرگز شکسته نمی‌شود؛ برآورد بک‌لایت هیچ‌گاه زیر ۸٬۰۰۰٬۰۰۰ تومان اعلام نمی‌گردد.', 'pixva' );
		?>
	</p>
	<div class="pixva-cc-grid">
		<?php
		pixva_control_stat( pixva_fa_num( number_format_i18n( (float) ( $settings['global_multiplier'] ?? 1 ) ) ), __( 'ضریب کلی نرخ', 'pixva' ) );
		pixva_control_stat( pixva_fa_num( number_format_i18n( (float) ( $settings['diagnostic_min'] ?? 0 ) ) ), __( 'حداقل هزینه کارشناسی (تومان)', 'pixva' ) );
		pixva_control_stat( pixva_fa_num( number_format_i18n( (float) ( $settings['diagnostic_max'] ?? 0 ) ) ), __( 'حداکثر هزینه کارشناسی (تومان)', 'pixva' ) );
		pixva_control_stat( pixva_fa_num( (string) count( $settings['brands'] ?? array() ) ), __( 'برند با ضریب اختصاصی', 'pixva' ) );
		pixva_control_stat( esc_html( $ref_name ), __( 'برند مرجع جدول', 'pixva' ) );
		?>
	</div>
	<table class="pixva-cc-table">
		<thead>
			<tr>
				<th><?php esc_html_e( 'خدمت تخصصی', 'pixva' ); ?></th>
				<th><?php esc_html_e( 'کف ۳۲ اینچ', 'pixva' ); ?></th>
				<th><?php esc_html_e( '۵۵ اینچ (محاسبه‌شده)', 'pixva' ); ?></th>
				<th><?php esc_html_e( '۶۵ اینچ', 'pixva' ); ?></th>
				<th><?php esc_html_e( '۷۵ اینچ', 'pixva' ); ?></th>
				<th><?php esc_html_e( '۸۵ اینچ', 'pixva' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php if ( empty( $table['rows'] ) ) : ?>
				<tr><td colspan="6"><?php esc_html_e( 'داده‌ای برای نمایش وجود ندارد.', 'pixva' ); ?></td></tr>
			<?php else : ?>
				<?php foreach ( $table['rows'] as $row ) : ?>
					<tr>
						<td><strong><?php echo esc_html( $row['title'] ); ?></strong></td>
						<td><?php echo esc_html( pixva_fa_num( number_format_i18n( (float) $row['floor'] ) ) ); ?></td>
						<?php foreach ( array( '55', '65', '75', '85' ) as $size ) : ?>
							<?php $cell = isset( $row['computed'][ $size ] ) ? $row['computed'][ $size ] : array( 0, 0 ); ?>
							<td>
								<?php
								echo esc_html(
									pixva_fa_num( number_format_i18n( (float) $cell[0] ) ) . ' — ' .
									pixva_fa_num( number_format_i18n( (float) $cell[1] ) )
								);
								?>
							</td>
						<?php endforeach; ?>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
		</tbody>
	</table>
	<p class="pixva-cc-note">
		<?php esc_html_e( 'همه مبالغ به تومان و گرد شده به پله ۵۰٬۰۰۰ تومان است. ویرایش پایه هر خدمت و ضریب تک‌تک برندها در صفحه نرخ‌نامه انجام می‌شود تا موتور قیمت، REST API و محاسبه‌گر فرانت‌اند هم‌زمان به‌روز بمانند.', 'pixva' ); ?>
	</p>
	<p>
		<a class="button button-primary" href="<?php echo esc_url( admin_url( 'admin.php?page=pixva-pricing' ) ); ?>"><?php esc_html_e( 'ویرایش پایه خدمات و ضریب ۲۴ برند', 'pixva' ); ?></a>
		<a class="button" href="<?php echo esc_url( pixva_page_url( 'rates' ) ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'مشاهده صفحه عمومی نرخ‌نامه', 'pixva' ); ?></a>
	</p>
	<?php
}

/**
 * تب ۴ — ماتریس ۶۰ ابزار (رجیستری‌محور، گروه‌بندی بر اساس هاب).
 *
 * @param array $opts تنظیمات.
 * @return void
 */
function pixva_control_tab_tools( $opts ) {
	$registry = function_exists( 'pixva_tools_registry' ) ? pixva_tools_registry() : array();
	$hubs     = function_exists( 'pixva_hubs' ) ? pixva_hubs() : array();
	?>
	<h2><?php esc_html_e( 'ماتریس کلید فعال‌سازی ۶۰ ابزار تعاملی', 'pixva' ); ?></h2>
	<p class="pixva-cc-note">
		<?php esc_html_e( 'هر ابزار غیرفعال در فرانت‌اند، شورت‌کد، ویجت المنتور و REST API با پیام «این ابزار موقتاً غیرفعال است» جایگزین می‌شود؛ هیچ خروجی خالی یا خطای جاوااسکریپت تولید نمی‌گردد.', 'pixva' ); ?>
	</p>
	<?php foreach ( $hubs as $hub_key => $hub ) : ?>
		<?php
		$ids = range( (int) $hub['from'], (int) $hub['to'] );
		$on  = 0;
		foreach ( $ids as $id ) {
			$on += empty( $opts['tools_enabled'][ $id ] ) ? 0 : 1;
		}
		$group_id = 'pixva-tools-' . $hub['slug'];
		?>
		<div class="pixva-cc-group">
			<div class="pixva-cc-group__head">
				<div>
					<b><?php echo esc_html( sprintf( __( 'هاب %s — %s', 'pixva' ), pixva_fa_num( (string) $hub['no'] ), $hub['title'] ) ); ?></b>
					<span><?php echo esc_html( sprintf( __( 'ابزار %s تا %s', 'pixva' ), pixva_fa_num( (string) $hub['from'] ), pixva_fa_num( (string) $hub['to'] ) ) ); ?></span>
				</div>
				<div>
					<span style="margin-inline-end:10px;"><?php echo esc_html( sprintf( __( '%s فعال از %s', 'pixva' ), pixva_fa_num( (string) $on ), pixva_fa_num( (string) count( $ids ) ) ) ); ?></span>
					<button type="button" class="pixva-cc-mini" data-pixva-toggle-group="#<?php echo esc_attr( $group_id ); ?>" data-pixva-toggle-state="1"><?php esc_html_e( 'فعال‌سازی همه', 'pixva' ); ?></button>
					<button type="button" class="pixva-cc-mini" data-pixva-toggle-group="#<?php echo esc_attr( $group_id ); ?>" data-pixva-toggle-state="0"><?php esc_html_e( 'غیرفعال‌سازی همه', 'pixva' ); ?></button>
					<a class="pixva-cc-mini" style="text-decoration:none" href="<?php echo esc_url( function_exists( 'pixva_hub_url' ) ? pixva_hub_url( $hub_key ) : pixva_page_url( $hub_key ) ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'دیدن هاب', 'pixva' ); ?></a>
				</div>
			</div>
			<div class="pixva-cc-tools" id="<?php echo esc_attr( $group_id ); ?>">
				<?php foreach ( $ids as $id ) : ?>
					<?php
					$tool  = isset( $registry[ $id ] ) ? $registry[ $id ] : array();
					$is_on = empty( $opts['tools_enabled'][ $id ] ) ? false : true;
					?>
					<label class="pixva-cc-tool <?php echo $is_on ? '' : 'is-off'; ?>">
						<input type="checkbox" name="tools_enabled[<?php echo esc_attr( $id ); ?>]" value="1" <?php checked( $is_on ); ?>>
						<span>
							<strong><?php echo esc_html( pixva_fa_num( (string) $id ) . '. ' . ( isset( $tool['title'] ) ? $tool['title'] : '' ) ); ?></strong>
							<small><?php echo esc_html( isset( $tool['en'] ) ? $tool['en'] : '' ); ?> · [pixva_tool id=<?php echo esc_attr( $id ); ?>]</small>
						</span>
					</label>
				<?php endforeach; ?>
			</div>
		</div>
	<?php endforeach; ?>
	<?php
}

/**
 * تب ۵ — انبار قطعات و اصالت کالا.
 *
 * @param array $opts تنظیمات.
 * @return void
 */
function pixva_control_tab_parts( $opts ) {
	unset( $opts );

	$records  = function_exists( 'pixva_part_records' ) ? pixva_part_records() : array();
	$total    = count( $records );
	$qty      = 0;
	$low      = 0;
	$value    = 0;
	foreach ( $records as $row ) {
		$qty   += (int) ( $row['qty'] ?? 0 );
		$value += (int) ( $row['qty'] ?? 0 ) * (int) ( $row['price'] ?? 0 );
		if ( (int) ( $row['qty'] ?? 0 ) < 5 ) {
			++$low;
		}
	}
	$cpt_count = pixva_control_count_posts( 'pixva_part' );
	?>
	<h2><?php esc_html_e( 'انبار مرکزی قطعات فابریک', 'pixva' ); ?></h2>
	<p class="pixva-cc-note">
		<?php esc_html_e( 'ابزارهای ۱۴ (استعلام موجودی)، ۲۲ (اصالت با سریال) و ۳۰ (هولوگرام اصالت) مستقیماً از همین رکوردها می‌خوانند. در نبود رکورد ثبت‌شده، کاتالوگ مرجع کارگاه نمایش داده می‌شود.', 'pixva' ); ?>
	</p>
	<div class="pixva-cc-grid">
		<?php
		pixva_control_stat( pixva_fa_num( (string) $cpt_count ), __( 'قطعه ثبت‌شده در پیشخوان', 'pixva' ) );
		pixva_control_stat( pixva_fa_num( (string) $total ), __( 'ردیف موجودی مؤثر', 'pixva' ) );
		pixva_control_stat( pixva_fa_num( number_format_i18n( (float) $qty ) ), __( 'مجموع موجودی انبار', 'pixva' ) );
		pixva_control_stat( pixva_fa_num( (string) $low ), __( 'ردیف با موجودی بحرانی (زیر ۵)', 'pixva' ) );
		pixva_control_stat( pixva_fa_num( number_format_i18n( (float) $value ) ), __( 'ارزش ریالی موجودی (تومان)', 'pixva' ) );
		?>
	</div>
	<?php if ( 0 === $cpt_count ) : ?>
		<div class="pixva-cc-empty">
			<?php esc_html_e( 'هنوز قطعه‌ای در انبار ثبت نشده است. برای فعال شدن استعلام اصالت با شماره سریال، هر قطعه را با کد فنی، برند، موجودی، قیمت، مدت گارانتی و سریال ثبت کنید.', 'pixva' ); ?>
		</div>
	<?php endif; ?>
	<?php if ( $total ) : ?>
		<table class="pixva-cc-table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'قطعه', 'pixva' ); ?></th>
					<th><?php esc_html_e( 'کد فنی', 'pixva' ); ?></th>
					<th><?php esc_html_e( 'برند', 'pixva' ); ?></th>
					<th><?php esc_html_e( 'موجودی', 'pixva' ); ?></th>
					<th><?php esc_html_e( 'قیمت (تومان)', 'pixva' ); ?></th>
					<th><?php esc_html_e( 'گارانتی', 'pixva' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( array_slice( $records, 0, 20 ) as $row ) : ?>
					<tr>
						<td><?php echo esc_html( $row['name'] ?? '' ); ?></td>
						<td dir="ltr"><?php echo esc_html( $row['sku'] ?? '' ); ?></td>
						<td><?php echo esc_html( $row['brand'] ?? '' ); ?></td>
						<td><?php echo esc_html( pixva_fa_num( (string) ( $row['qty'] ?? 0 ) ) ); ?></td>
						<td><?php echo esc_html( pixva_fa_num( number_format_i18n( (float) ( $row['price'] ?? 0 ) ) ) ); ?></td>
						<td><?php echo esc_html( pixva_fa_num( (string) ( $row['warranty'] ?? 0 ) ) . ' ' . esc_html__( 'روز', 'pixva' ) ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php endif; ?>
	<p>
		<a class="button button-primary" href="<?php echo esc_url( admin_url( 'edit.php?post_type=pixva_part' ) ); ?>"><?php esc_html_e( 'مدیریت انبار قطعات', 'pixva' ); ?></a>
		<a class="button" href="<?php echo esc_url( admin_url( 'post-new.php?post_type=pixva_part' ) ); ?>"><?php esc_html_e( 'ثبت قطعه جدید', 'pixva' ); ?></a>
		<a class="button" href="<?php echo esc_url( pixva_page_url( 'parts-stock' ) ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'صفحه عمومی انبار', 'pixva' ); ?></a>
	</p>
	<?php
}

/**
 * تب ۶ — شعب، کارگاه مرکزی و ETA.
 *
 * @param array $opts تنظیمات.
 * @return void
 */
function pixva_control_tab_branches( $opts ) {
	$branches = function_exists( 'pixva_branches' ) ? pixva_branches() : array();
	$cpt      = pixva_control_count_posts( 'pixva_branch' );
	?>
	<h2><?php esc_html_e( 'کارگاه مرکزی، شعب و زمان اعزام', 'pixva' ); ?></h2>
	<p class="pixva-cc-note">
		<?php esc_html_e( 'این مقادیر در هدر (نشان زمان اعزام)، فوتر (آدرس و QR)، صفحه سازمانی و ابزار ۴۷ (رزرو سرویس در محل) استفاده می‌شود.', 'pixva' ); ?>
	</p>
	<table class="form-table">
		<?php
		pixva_control_row(
			__( 'آدرس کارگاه مرکزی', 'pixva' ),
			'hub_address',
			'<textarea id="hub_address" name="hub_address" rows="2" class="large-text" style="max-width:640px">' . esc_textarea( $opts['hub_address'] ) . '</textarea>',
			__( 'در ستون سوم فوتر و در اسکیمای LocalBusiness نیز چاپ می‌شود.', 'pixva' )
		);
		pixva_control_row(
			__( 'تلفن کارگاه مرکزی', 'pixva' ),
			'hub_phone',
			'<input type="text" id="hub_phone" name="hub_phone" value="' . esc_attr( $opts['hub_phone'] ) . '" class="regular-text" style="width:240px" dir="ltr">',
			__( 'فقط رقم و علامت +؛ به‌صورت لینک tel: قابل کلیک نمایش داده می‌شود.', 'pixva' )
		);
		pixva_control_row(
			__( 'زمان اعزام تکنسین (ETA)', 'pixva' ),
			'hub_eta_hours',
			'<input type="text" id="hub_eta_hours" name="hub_eta_hours" value="' . esc_attr( $opts['hub_eta_hours'] ) . '" class="regular-text" style="width:240px">',
			__( 'مثلاً «۲ ساعت» — در هدر، بنر اعزام اورژانسی و ابزار ۱۹ چاپ می‌شود.', 'pixva' )
		);
		pixva_control_row(
			__( 'ساعات کاری کارگاه', 'pixva' ),
			'hub_hours',
			'<input type="text" id="hub_hours" name="hub_hours" value="' . esc_attr( $opts['hub_hours'] ) . '" class="regular-text" style="width:320px">',
			__( 'در فوتر و کارت‌های شعبه نمایش داده می‌شود.', 'pixva' )
		);
		?>
	</table>
	<h3><?php esc_html_e( 'شعب مؤثر (اولویت با رکوردهای پیشخوان)', 'pixva' ); ?></h3>
	<div class="pixva-cc-grid">
		<?php
		pixva_control_stat( pixva_fa_num( (string) $cpt ), __( 'شعبه ثبت‌شده در پیشخوان', 'pixva' ) );
		pixva_control_stat( pixva_fa_num( (string) count( $branches ) ), __( 'شعبه نمایش‌داده‌شده در سایت', 'pixva' ) );
		?>
	</div>
	<?php if ( 0 === $cpt ) : ?>
		<div class="pixva-cc-empty"><?php esc_html_e( 'رکوردی ثبت نشده؛ چهار واحد پیش‌فرض کارگاه (مرکزی، شمال، شرق، غرب و جنوب تهران) نمایش داده می‌شود.', 'pixva' ); ?></div>
	<?php endif; ?>
	<table class="pixva-cc-table">
		<thead>
			<tr>
				<th><?php esc_html_e( 'شعبه / واحد', 'pixva' ); ?></th>
				<th><?php esc_html_e( 'نوع فعالیت', 'pixva' ); ?></th>
				<th><?php esc_html_e( 'پوشش', 'pixva' ); ?></th>
				<th><?php esc_html_e( 'تلفن', 'pixva' ); ?></th>
				<th><?php esc_html_e( 'ساعات', 'pixva' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( $branches as $branch ) : ?>
				<tr>
					<td><strong><?php echo esc_html( $branch['name'] ?? '' ); ?></strong><br><span style="color:rgb(122,147,158);font-size:11px;"><?php echo esc_html( $branch['address'] ?? '' ); ?></span></td>
					<td><?php echo esc_html( $branch['type'] ?? '' ); ?></td>
					<td><?php echo esc_html( pixva_fa_num( (string) count( (array) ( $branch['zones'] ?? array() ) ) ) . ' ' . esc_html__( 'منطقه', 'pixva' ) ); ?></td>
					<td dir="ltr"><?php echo esc_html( $branch['phone'] ?? '' ); ?></td>
					<td><?php echo esc_html( $branch['hours'] ?? '' ); ?></td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
	<p>
		<a class="button button-primary" href="<?php echo esc_url( admin_url( 'edit.php?post_type=pixva_branch' ) ); ?>"><?php esc_html_e( 'مدیریت شعب', 'pixva' ); ?></a>
		<a class="button" href="<?php echo esc_url( admin_url( 'post-new.php?post_type=pixva_branch' ) ); ?>"><?php esc_html_e( 'افزودن شعبه', 'pixva' ); ?></a>
	</p>
	<?php
}

if ( ! function_exists( 'pixva_control_mask_phone' ) ) {
	/**
	 * پوشش شماره همراه برای نمایش در پیشخوان.
	 *
	 * @param string $phone شماره.
	 * @return string
	 */
	function pixva_control_mask_phone( $phone ) {
		$phone = preg_replace( '/[^0-9]/', '', (string) $phone );
		if ( strlen( $phone ) < 7 ) {
			return $phone;
		}
		return str_repeat( '•', strlen( $phone ) - 4 ) . substr( $phone, -4 );
	}
}

if ( ! function_exists( 'pixva_control_recent_orders' ) ) {
	/**
	 * خواندن پرونده‌های اخیر تعمیر.
	 *
	 * @param int $limit سقف تعداد.
	 * @return WP_Post[]
	 */
	function pixva_control_recent_orders( $limit = 200 ) {
		$posts = get_posts(
			array(
				'post_type'      => 'pixva_orders',
				'post_status'    => array( 'private', 'publish' ),
				'posts_per_page' => max( 1, (int) $limit ),
				'orderby'        => 'date',
				'order'          => 'DESC',
				'no_found_rows'  => true,
			)
		);

		return is_array( $posts ) ? $posts : array();
	}
}

/**
 * تب ۷ — پیگیری تعمیرات و گارانتی دیجیتال.
 *
 * @param array $opts تنظیمات.
 * @return void
 */
function pixva_control_tab_tracking( $opts ) {
	$statuses = function_exists( 'pixva_order_statuses' ) ? pixva_order_statuses() : array();
	$orders   = pixva_control_recent_orders();
	$counts   = array_fill_keys( array_keys( $statuses ), 0 );
	$ready    = 0;

	foreach ( $orders as $order ) {
		$status = (string) get_post_meta( $order->ID, '_pixva_order_status', true );
		if ( array_key_exists( $status, $counts ) ) {
			++$counts[ $status ];
		}
		if ( in_array( $status, array( 'ready', 'testing' ), true ) ) {
			++$ready;
		}
	}
	?>
	<h2><?php esc_html_e( 'پیگیری پرونده و کارت گارانتی دیجیتال', 'pixva' ); ?></h2>
	<p class="pixva-cc-note">
		<?php esc_html_e( 'کارت گارانتی فقط برای پرونده‌هایی صادر می‌شود که وضعیتشان «تست نهایی» یا «آماده تحویل» باشد؛ هش کارت از HMAC-SHA256 روی کد، شماره همراه، خدمت و تاریخ انقضا با کلید wp_salt ساخته می‌شود.', 'pixva' ); ?>
	</p>
	<table class="form-table">
		<?php
		pixva_control_row(
			__( 'مدت گارانتی کتبی (روز)', 'pixva' ),
			'warranty_days',
			'<input type="number" id="warranty_days" name="warranty_days" value="' . esc_attr( (string) $opts['warranty_days'] ) . '" min="30" max="730" step="1" style="width:120px" dir="ltr">',
			__( 'بازه مجاز ۳۰ تا ۷۳۰ روز. این مقدار در بنرهای سایت، کارت گارانتی، REST API و ابزار ۳۵ (یادآور سرویس) استفاده می‌شود.', 'pixva' )
		);
		?>
	</table>
	<h3><?php esc_html_e( 'تایم‌لاین زنده پرونده‌ها', 'pixva' ); ?></h3>
	<div class="pixva-cc-grid">
		<?php
		pixva_control_stat( pixva_fa_num( (string) count( $orders ) ), __( 'کل پرونده‌ها', 'pixva' ) );
		foreach ( $statuses as $key => $label ) {
			pixva_control_stat( pixva_fa_num( (string) $counts[ $key ] ), $label );
		}
		pixva_control_stat( pixva_fa_num( (string) $ready ), __( 'واجد شرایط صدور کارت گارانتی', 'pixva' ) );
		?>
	</div>
	<?php if ( $orders ) : ?>
		<table class="pixva-cc-table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'کد پیگیری', 'pixva' ); ?></th>
					<th><?php esc_html_e( 'پرونده', 'pixva' ); ?></th>
					<th><?php esc_html_e( 'همراه', 'pixva' ); ?></th>
					<th><?php esc_html_e( 'وضعیت', 'pixva' ); ?></th>
					<th><?php esc_html_e( 'تاریخ ثبت', 'pixva' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( array_slice( $orders, 0, 8 ) as $order ) : ?>
					<?php
					$code   = (string) get_post_meta( $order->ID, '_pixva_order_code', true );
					$phone  = (string) get_post_meta( $order->ID, '_pixva_order_phone', true );
					$status = (string) get_post_meta( $order->ID, '_pixva_order_status', true );
					?>
					<tr>
						<td dir="ltr"><?php echo esc_html( $code ? $code : '—' ); ?></td>
						<td><a href="<?php echo esc_url( get_edit_post_link( $order->ID ) ); ?>"><?php echo esc_html( get_the_title( $order ) ); ?></a></td>
						<td dir="ltr"><?php echo esc_html( pixva_control_mask_phone( $phone ) ); ?></td>
						<td><?php echo esc_html( isset( $statuses[ $status ] ) ? $statuses[ $status ] : __( 'نامشخص', 'pixva' ) ); ?></td>
						<td><?php echo esc_html( function_exists( 'pixva_format_date' ) ? pixva_format_date( $order->post_date ) : mysql2date( 'Y/m/d', $order->post_date ) ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php else : ?>
		<div class="pixva-cc-empty"><?php esc_html_e( 'هنوز پرونده‌ای ثبت نشده است. نخستین درخواست از فرم «استعلام سریع قیمت» یا ابزار ۲۱ (ثبت سفارش) ساخته می‌شود.', 'pixva' ); ?></div>
	<?php endif; ?>
	<p>
		<a class="button button-primary" href="<?php echo esc_url( admin_url( 'edit.php?post_type=pixva_orders' ) ); ?>"><?php esc_html_e( 'مدیریت همه پرونده‌ها', 'pixva' ); ?></a>
		<a class="button" href="<?php echo esc_url( function_exists( 'pixva_hub_url' ) ? pixva_hub_url( 'tracking-warranty' ) : pixva_page_url( 'tracking' ) ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'هاب ۳ — پیگیری و گارانتی', 'pixva' ); ?></a>
	</p>
	<?php
}

/**
 * تب ۸ — پایگاه کدهای خطا.
 *
 * @param array $opts تنظیمات.
 * @return void
 */
function pixva_control_tab_errors( $opts ) {
	unset( $opts );

	$catalog = function_exists( 'pixva_error_code_catalog' ) ? pixva_error_code_catalog() : array();
	$cpt     = pixva_control_count_posts( 'pixva_error' );
	$brands  = array();
	foreach ( $catalog as $row ) {
		$brand = isset( $row[0] ) ? (string) $row[0] : '';
		if ( '' === $brand ) {
			continue;
		}
		$brands[ $brand ] = isset( $brands[ $brand ] ) ? $brands[ $brand ] + 1 : 1;
	}
	$labels = function_exists( 'pixva_calculator_labels' ) ? pixva_calculator_labels() : array( 'brand' => array() );
	?>
	<h2><?php esc_html_e( 'پایگاه زنده کدهای خطا و چشمک چراغ', 'pixva' ); ?></h2>
	<p class="pixva-cc-note">
		<?php esc_html_e( 'کدهای ثبت‌شده در پیشخوان بر کاتالوگ مرجع اولویت دارند و در صفحه هاب ۴، ابزار ۳۱ و REST endpoint /pixva/v1/errors نمایش داده می‌شوند.', 'pixva' ); ?>
	</p>
	<div class="pixva-cc-grid">
		<?php
		pixva_control_stat( pixva_fa_num( (string) $cpt ), __( 'کد خطای ثبت‌شده در پیشخوان', 'pixva' ) );
		pixva_control_stat( pixva_fa_num( (string) count( $catalog ) ), __( 'الگوی مرجع کارگاه', 'pixva' ) );
		pixva_control_stat( pixva_fa_num( (string) count( $brands ) ), __( 'برند پوشش‌داده‌شده', 'pixva' ) );
		?>
	</div>
	<table class="pixva-cc-table">
		<thead>
			<tr><th><?php esc_html_e( 'برند', 'pixva' ); ?></th><th><?php esc_html_e( 'تعداد الگو در کاتالوگ مرجع', 'pixva' ); ?></th></tr>
		</thead>
		<tbody>
			<?php foreach ( $brands as $brand => $count ) : ?>
				<tr>
					<td><?php echo esc_html( isset( $labels['brand'][ $brand ] ) ? $labels['brand'][ $brand ] : $brand ); ?></td>
					<td><?php echo esc_html( pixva_fa_num( (string) $count ) ); ?></td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
	<p>
		<a class="button button-primary" href="<?php echo esc_url( admin_url( 'edit.php?post_type=pixva_error' ) ); ?>"><?php esc_html_e( 'مدیریت کدهای خطا', 'pixva' ); ?></a>
		<a class="button" href="<?php echo esc_url( admin_url( 'post-new.php?post_type=pixva_error' ) ); ?>"><?php esc_html_e( 'افزودن کد خطا', 'pixva' ); ?></a>
		<a class="button" href="<?php echo esc_url( function_exists( 'pixva_hub_url' ) ? pixva_hub_url( 'error-codes' ) : pixva_page_url( 'error-codes' ) ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'هاب ۴ — کدهای خطا', 'pixva' ); ?></a>
	</p>
	<?php
}

/**
 * تب ۹ — پورتال سازمانی B2B.
 *
 * @param array $opts تنظیمات.
 * @return void
 */
function pixva_control_tab_b2b( $opts ) {
	$registry  = function_exists( 'pixva_tools_registry' ) ? pixva_tools_registry() : array();
	$corporate = array( 21, 47, 52, 53, 54, 55, 60 );
	?>
	<h2><?php esc_html_e( 'پورتال خدمات سازمانی، هتل‌ها و ارگان‌ها', 'pixva' ); ?></h2>
	<p class="pixva-cc-note">
		<?php esc_html_e( 'درخواست‌های B2B از ابزار ۲۱ به‌صورت پرونده واقعی در پیشخوان ثبت می‌شود و با همان تایم‌لاین شش‌مرحله‌ای پیگیری می‌گردد. اطلاعات تماس از تب «شعب و کارگاه» خوانده می‌شود.', 'pixva' ); ?>
	</p>
	<div class="pixva-cc-grid">
		<?php
		pixva_control_stat( esc_html( $opts['hub_phone'] ), __( 'تلفن واحد سازمانی', 'pixva' ) );
		pixva_control_stat( esc_html( $opts['hub_eta_hours'] ), __( 'زمان اعزام تیم', 'pixva' ) );
		pixva_control_stat( pixva_fa_num( (string) $opts['warranty_days'] ) . ' ' . __( 'روز', 'pixva' ), __( 'گارانتی قراردادهای سازمانی', 'pixva' ) );
		?>
	</div>
	<table class="pixva-cc-table">
		<thead>
			<tr>
				<th><?php esc_html_e( 'ابزار سازمانی', 'pixva' ); ?></th>
				<th><?php esc_html_e( 'وضعیت', 'pixva' ); ?></th>
				<th><?php esc_html_e( 'شورت‌کد', 'pixva' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( $corporate as $id ) : ?>
				<?php $tool = isset( $registry[ $id ] ) ? $registry[ $id ] : array(); ?>
				<tr>
					<td><strong><?php echo esc_html( pixva_fa_num( (string) $id ) . '. ' . ( $tool['title'] ?? '' ) ); ?></strong><br><span style="color:rgb(122,147,158);font-size:11px;"><?php echo esc_html( $tool['summary'] ?? '' ); ?></span></td>
					<td>
						<?php if ( empty( $opts['tools_enabled'][ $id ] ) ) : ?>
							<span class="pixva-cc-pill pixva-cc-pill--warn"><?php esc_html_e( 'غیرفعال', 'pixva' ); ?></span>
						<?php else : ?>
							<span class="pixva-cc-pill pixva-cc-pill--ok"><?php esc_html_e( 'فعال', 'pixva' ); ?></span>
						<?php endif; ?>
					</td>
					<td dir="ltr">[pixva_tool id=<?php echo esc_attr( $id ); ?>]</td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
	<p>
		<a class="button button-primary" href="<?php echo esc_url( pixva_page_url( 'b2b' ) ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'صفحه خدمات سازمانی', 'pixva' ); ?></a>
		<a class="button" href="<?php echo esc_url( function_exists( 'pixva_hub_url' ) ? pixva_hub_url( 'parts-b2b' ) : pixva_page_url( 'parts-b2b' ) ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'هاب ۵ — قطعات و B2B', 'pixva' ); ?></a>
	</p>
	<?php
}

/**
 * تب ۱۰ — باشگاه مشتریان و کارت گارانتی.
 *
 * @param array $opts تنظیمات.
 * @return void
 */
function pixva_control_tab_vip( $opts ) {
	$orders   = pixva_control_recent_orders();
	$eligible = 0;
	$phones   = array();
	foreach ( $orders as $order ) {
		$status = (string) get_post_meta( $order->ID, '_pixva_order_status', true );
		if ( in_array( $status, array( 'ready', 'testing' ), true ) ) {
			++$eligible;
		}
		$phone = preg_replace( '/[^0-9]/', '', (string) get_post_meta( $order->ID, '_pixva_order_phone', true ) );
		if ( '' !== $phone ) {
			$phones[ $phone ] = true;
		}
	}
	?>
	<h2><?php esc_html_e( 'باشگاه مشتریان و گارانتی دیجیتال', 'pixva' ); ?></h2>
	<p class="pixva-cc-note">
		<?php esc_html_e( 'پنل مشتریان با شماره همراه همه پرونده‌های همان شخص را فهرست می‌کند (ابزار ۲۷) و کارت گارانتی با هش اعتبارسنجی صادر می‌کند (ابزار ۱۶). هیچ داده نمونه‌ای از پیش ساخته نمی‌شود.', 'pixva' ); ?>
	</p>
	<div class="pixva-cc-grid">
		<?php
		pixva_control_stat( pixva_fa_num( (string) count( $phones ) ), __( 'مشتری یکتا', 'pixva' ) );
		pixva_control_stat( pixva_fa_num( (string) count( $orders ) ), __( 'پرونده ثبت‌شده', 'pixva' ) );
		pixva_control_stat( pixva_fa_num( (string) $eligible ), __( 'کارت گارانتی قابل صدور', 'pixva' ) );
		pixva_control_stat( pixva_fa_num( (string) $opts['warranty_days'] ), __( 'روز گارانتی کتبی', 'pixva' ) );
		?>
	</div>
	<p>
		<a class="button button-primary" href="<?php echo esc_url( pixva_page_url( 'client-hub' ) ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'پنل مشتریان و گارانتی دیجیتال', 'pixva' ); ?></a>
		<a class="button" href="<?php echo esc_url( function_exists( 'pixva_hub_url' ) ? pixva_hub_url( 'tracking-warranty' ) : pixva_page_url( 'tracking' ) ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'هاب پیگیری و گارانتی', 'pixva' ); ?></a>
	</p>
	<?php
}

/**
 * تب ۱۱ — وب‌اپ (PWA) و پیام آفلاین.
 *
 * @param array $opts تنظیمات.
 * @return void
 */
function pixva_control_tab_pwa( $opts ) {
	$manifest_url = add_query_arg( 'pixva_manifest', '1', home_url( '/' ) );
	?>
	<h2><?php esc_html_e( 'وب‌اپ پیکسوا و حالت آفلاین', 'pixva' ); ?></h2>
	<p class="pixva-cc-note">
		<?php esc_html_e( 'با فعال بودن این گزینه، مانیفست وب‌اپ به‌صورت پویا تولید و به <head> اضافه می‌شود (installable در کروم و اندروید، Add to Home Screen در iOS) و در قطعی اینترنت، بنر پیام آفلاین بالای ابزارهای محاسبه نمایش داده می‌شود.', 'pixva' ); ?>
	</p>
	<table class="form-table">
		<?php
		pixva_control_row(
			__( 'فعال‌سازی وب‌اپ', 'pixva' ),
			'pwa_enable',
			'<label><input type="checkbox" name="pwa_enable" value="1" ' . checked( $opts['pwa_enable'], 1, false ) . '> ' . esc_html__( 'افزودن مانیفست، theme-color و تگ‌های نصب وب‌اپ', 'pixva' ) . '</label>',
			''
		);
		pixva_control_row(
			__( 'پیام حالت آفلاین', 'pixva' ),
			'pwa_offline_message',
			'<input type="text" id="pwa_offline_message" name="pwa_offline_message" value="' . esc_attr( $opts['pwa_offline_message'] ) . '" class="large-text" style="max-width:640px">',
			__( 'این متن در بنر آفلاین و در ابزار ۵۹ (نصب وب‌اپ) نمایش داده می‌شود.', 'pixva' )
		);
		?>
	</table>
	<h3><?php esc_html_e( 'نشانی مانیفست', 'pixva' ); ?></h3>
	<p><code dir="ltr"><?php echo esc_html( $manifest_url ); ?></code></p>
	<p>
		<a class="button" href="<?php echo esc_url( $manifest_url ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'بازکردن مانیفست JSON', 'pixva' ); ?></a>
	</p>
	<?php
}

/**
 * تب ۱۲ — داشبورد آمار زنده.
 *
 * @param array $opts تنظیمات.
 * @return void
 */
function pixva_control_tab_analytics( $opts ) {
	$types = array(
		'pixva_orders'  => __( 'پرونده تعمیر', 'pixva' ),
		'pixva_part'    => __( 'قطعه انبار', 'pixva' ),
		'pixva_error'   => __( 'کد خطا', 'pixva' ),
		'pixva_branch'  => __( 'شعبه', 'pixva' ),
		'pixva_repair'  => __( 'نمونه‌کار تعمیر', 'pixva' ),
		'pixva_inbox'   => __( 'پیام دریافتی', 'pixva' ),
		'tv_services'   => __( 'خدمت', 'pixva' ),
		'tv_brands'     => __( 'برند', 'pixva' ),
		'repair_cases'  => __( 'پرونده نمونه‌کار', 'pixva' ),
		'post'          => __( 'مقاله مجله', 'pixva' ),
		'page'          => __( 'برگه', 'pixva' ),
	);
	?>
	<h2><?php esc_html_e( 'داشبورد آمار زنده کارگاه', 'pixva' ); ?></h2>
	<p class="pixva-cc-note"><?php esc_html_e( 'همه اعداد مستقیماً از پایگاه‌داده خوانده می‌شوند و هیچ داده نمونه‌ای در آن‌ها لحاظ نشده است.', 'pixva' ); ?></p>
	<div class="pixva-cc-grid">
		<?php
		foreach ( $types as $type => $label ) {
			pixva_control_stat( pixva_fa_num( number_format_i18n( (float) pixva_control_count_posts( $type ) ) ), $label );
		}
		pixva_control_stat( pixva_fa_num( (string) pixva_control_tools_enabled_count( $opts ) ) . ' / ۶۰', __( 'ابزار تعاملی فعال', 'pixva' ) );
		?>
	</div>
	<h3><?php esc_html_e( 'وضعیت سلامت سامانه', 'pixva' ); ?></h3>
	<table class="pixva-cc-table">
		<thead>
			<tr><th><?php esc_html_e( 'جزء', 'pixva' ); ?></th><th><?php esc_html_e( 'وضعیت', 'pixva' ); ?></th></tr>
		</thead>
		<tbody>
			<tr>
				<td><?php esc_html_e( 'موتور هوش مصنوعی Gemini', 'pixva' ); ?></td>
				<td>
					<?php if ( function_exists( 'pixva_ai_is_configured' ) && pixva_ai_is_configured() ) : ?>
						<span class="pixva-cc-pill pixva-cc-pill--ok"><?php echo esc_html( $opts['ai_gemini_model'] ); ?></span>
					<?php else : ?>
						<span class="pixva-cc-pill pixva-cc-pill--warn"><?php esc_html_e( 'کلید وارد نشده — موتور قانون‌محور', 'pixva' ); ?></span>
					<?php endif; ?>
				</td>
			</tr>
			<tr>
				<td><?php esc_html_e( 'درگاه پیامک', 'pixva' ); ?></td>
				<td>
					<?php
					$providers = pixva_control_sms_providers();
					$sms_ready = 'none' !== $opts['sms_provider'] && '' !== $opts['sms_api_key'];
					echo '<span class="pixva-cc-pill ' . ( $sms_ready ? 'pixva-cc-pill--ok' : 'pixva-cc-pill--warn' ) . '">' . esc_html( $sms_ready ? $providers[ $opts['sms_provider'] ] : __( 'غیرفعال', 'pixva' ) ) . '</span>';
					?>
				</td>
			</tr>
			<tr>
				<td><?php esc_html_e( 'مانیفست وب‌اپ', 'pixva' ); ?></td>
				<td><span class="pixva-cc-pill <?php echo empty( $opts['pwa_enable'] ) ? 'pixva-cc-pill--warn' : 'pixva-cc-pill--ok'; ?>"><?php echo empty( $opts['pwa_enable'] ) ? esc_html__( 'غیرفعال', 'pixva' ) : esc_html__( 'فعال', 'pixva' ); ?></span></td>
			</tr>
			<tr>
				<td><?php esc_html_e( 'المنتور', 'pixva' ); ?></td>
				<td><span class="pixva-cc-pill <?php echo did_action( 'elementor/loaded' ) ? 'pixva-cc-pill--ok' : 'pixva-cc-pill--warn'; ?>"><?php echo did_action( 'elementor/loaded' ) ? esc_html__( 'فعال — ۶۰ ویجت ثبت شده', 'pixva' ) : esc_html__( 'نصب نیست — شورت‌کدها فعال‌اند', 'pixva' ); ?></span></td>
			</tr>
			<tr>
				<td><?php esc_html_e( 'کف قیمت بازار', 'pixva' ); ?></td>
				<td><?php echo esc_html( pixva_fa_num( number_format_i18n( 8000000 ) ) . ' ' . esc_html__( 'تومان (قفل‌شده در موتور قیمت)', 'pixva' ) ); ?></td>
			</tr>
			<tr>
				<td><?php esc_html_e( 'نسخه مشخصات', 'pixva' ); ?></td>
				<td><?php echo esc_html( 'v' . ( defined( 'PIXVA_SPEC_VERSION' ) ? PIXVA_SPEC_VERSION : '25.0' ) ); ?></td>
			</tr>
		</tbody>
	</table>
	<p>
		<a class="button button-primary" href="<?php echo esc_url( admin_url( 'edit.php?post_type=pixva_orders' ) ); ?>"><?php esc_html_e( 'پرونده‌های تعمیر', 'pixva' ); ?></a>
		<a class="button" href="<?php echo esc_url( admin_url( 'edit.php?post_type=pixva_inbox' ) ); ?>"><?php esc_html_e( 'صندوق پیام‌ها', 'pixva' ); ?></a>
		<a class="button" href="<?php echo esc_url( rest_url( 'pixva/v1/hubs' ) ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'REST API هاب‌ها', 'pixva' ); ?></a>
	</p>
	<?php
}

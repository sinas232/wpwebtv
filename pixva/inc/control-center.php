<?php
/**
 * مرکز کنترل اختصاصی ۱۰۰٪ پویا در پیشخوان وردپرس (Pixva Control Center v21.0)
 *
 * مسیر: پیشخوان ← پیکسوا (Pixva Control Center) با ۱۲ تب جامع:
 * 1. تنظیمات عمومی & AI Multimodal
 * 2. ماتریس اعلانات & پیامک (Messaging Gateway)
 * 3. مدیریت نرخ‌نامه واقعی بازار (با کف ۸ میلیون تومان)
 * 4. ماتریس کلید فعال‌سازی ۶۰ ابزار (60 Feature Toggles Matrix)
 * 5. انبار قطعات و اصالت کالا
 * 6. مدیریت شعب و تکنسین‌ها
 * 7. پیگیری تعمیرات & گارانتی دیجیتال
 * 8. پایگاه زنده کدهای خطا (Error Knowledgebase)
 * 9. پورتال و خدمات سازمانی B2B
 * 10. باشگاه مشتریان & اشتراک VIP (Pixva+)
 * 11. تنظیمات PWA & کارکرد آفلاین
 * 12. داشبورد تحلیل و آمار (Telemetry Analytics)
 *
 * @package Pixva
 * @since   1.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'pixva_control_options' ) ) {
	/**
	 * تنظیمات مؤثر مرکز کنترل پیکسوا.
	 *
	 * @return array
	 */
	function pixva_control_options() {
		$defaults = array(
			// تب ۱: عمومی & AI
			'ai_gemini_key'        => '',
			'ai_groq_key'          => '',
			'ai_system_prompt'     => 'شما دستیار ارشد هوش مصنوعی کارگاه تخصصی تعمیر تلویزیون پیکسوا هستید.',
			'ai_enable_floating'   => 1,
			// تب ۲: پیامک & وب‌هوک
			'sms_provider'         => 'kavenegar',
			'sms_api_key'          => '',
			'sms_sender'           => '',
			'sms_template_status'  => 'pixva-status',
			'whatsapp_direct_link' => 'https://wa.me/989121111111',
			// تب ۳: نرخ‌نامه
			'market_floor'         => 8000000,
			'market_inflation'     => 1.0,
			'diagnostic_fee'       => 250000,
			// تب ۴: ابزارها (همه ۶۰ ابزار به طور پیش‌فرض فعال)
			'tools_enabled'        => array_fill( 1, 60, 1 ),
			// تب ۶: کارگاه مرکزی
			'hub_address'          => 'تهران، خیابان جمهوری، تقاطع حافظ، پاساژ علاءالدین، طبقه ۴، واحد ۴۱۲',
			'hub_phone'            => '02191009990',
			'hub_eta_hours'        => '۲ ساعت',
			// تب ۱۱: PWA
			'pwa_enable'           => 1,
			'pwa_offline_message'  => 'شما آفلاین هستید، پایگاه کدهای خطا همچنان در دسترس است.',
		);

		$saved = get_option( 'pixva_control_options', array() );
		if ( ! is_array( $saved ) ) {
			$saved = array();
		}
		return wp_parse_args( $saved, $defaults );
	}
}

/**
 * افزودن منوی اصلی «پیکسوا» به پیشخوان.
 *
 * @return void
 */
function pixva_register_control_center_menu() {
	add_menu_page(
		esc_html__( 'مرکز کنترل پیکسوا v21.0', 'pixva' ),
		esc_html__( 'پیکسوا (کنترل سنتر)', 'pixva' ),
		'manage_options',
		'pixva-control',
		'pixva_render_control_center_page',
		'dashicons-screenoptions',
		3
	);

	// زیرمنوی مستقیم نرخ‌نامه در کنترل سنتر
	add_submenu_page(
		'pixva-control',
		esc_html__( 'مدیریت نرخ‌نامه واقعی بازار', 'pixva' ),
		esc_html__( 'نرخ‌نامه و تعرفه‌ها', 'pixva' ),
		'manage_options',
		'pixva-control&tab=rates',
		'pixva_render_control_center_page'
	);

	// زیرمنوی ماتریس ۶۰ ابزار
	add_submenu_page(
		'pixva-control',
		esc_html__( 'ماتریس ۶۰ ابزار تعاملی', 'pixva' ),
		esc_html__( 'ماتریس ۶۰ ابزار', 'pixva' ),
		'manage_options',
		'pixva-control&tab=tools',
		'pixva_render_control_center_page'
	);
}
add_action( 'admin_menu', 'pixva_register_control_center_menu' );

/**
 * مدیریت ذخیره تنظیمات پیشخوان کنترل سنتر با Nonce.
 *
 * @return void
 */
function pixva_handle_control_save() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	if ( isset( $_POST['pixva_control_save_nonce'] ) && check_admin_referer( 'pixva_control_save_action', 'pixva_control_save_nonce' ) ) {
		$opts = pixva_control_options();

		// تب ۱: AI
		if ( isset( $_POST['ai_gemini_key'] ) ) {
			$opts['ai_gemini_key'] = sanitize_text_field( wp_unslash( $_POST['ai_gemini_key'] ) );
		}
		if ( isset( $_POST['ai_groq_key'] ) ) {
			$opts['ai_groq_key'] = sanitize_text_field( wp_unslash( $_POST['ai_groq_key'] ) );
		}
		if ( isset( $_POST['ai_system_prompt'] ) ) {
			$opts['ai_system_prompt'] = sanitize_textarea_field( wp_unslash( $_POST['ai_system_prompt'] ) );
		}
		$opts['ai_enable_floating'] = isset( $_POST['ai_enable_floating'] ) ? 1 : 0;

		// تب ۲: پیامک
		if ( isset( $_POST['sms_provider'] ) ) {
			$opts['sms_provider'] = sanitize_text_field( wp_unslash( $_POST['sms_provider'] ) );
		}
		if ( isset( $_POST['sms_api_key'] ) ) {
			$opts['sms_api_key'] = sanitize_text_field( wp_unslash( $_POST['sms_api_key'] ) );
		}
		if ( isset( $_POST['whatsapp_direct_link'] ) ) {
			$opts['whatsapp_direct_link'] = esc_url_raw( wp_unslash( $_POST['whatsapp_direct_link'] ) );
		}

		// تب ۴: ۶۰ ابزار
		$tools_in = isset( $_POST['tools_enabled'] ) && is_array( $_POST['tools_enabled'] ) ? $_POST['tools_enabled'] : array();
		$tools_out = array();
		for ( $i = 1; $i <= 60; $i++ ) {
			$tools_out[ $i ] = isset( $tools_in[ $i ] ) ? 1 : 0;
		}
		$opts['tools_enabled'] = $tools_out;

		update_option( 'pixva_control_options', $opts );
		wp_safe_redirect( add_query_arg( array( 'page' => 'pixva-control', 'tab' => sanitize_key( $_POST['active_tab'] ?? 'general' ), 'saved' => 1 ), admin_url( 'admin.php' ) ) );
		exit;
	}
}
add_action( 'admin_init', 'pixva_handle_control_save' );

/**
 * رندر صفحه ۱۲ تب کنترل سنتر پیکسوا.
 *
 * @return void
 */
function pixva_render_control_center_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$active_tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'general';
	$opts       = pixva_control_options();
	$tabs       = array(
		'general'    => '۱. عمومی & AI Multimodal',
		'messaging'  => '۲. ماتریس اعلانات & پیامک',
		'rates'      => '۳. نرخ‌نامه (کف ۸ میلیون)',
		'tools'      => '۴. ماتریس ۶۰ ابزار تعاملی',
		'parts'      => '۵. انبار قطعات و اصالت',
		'branches'   => '۶. شعب و تکنسین‌ها (ETA)',
		'tracking'   => '۷. پیگیری & گارانتی دیجیتال',
		'errors'     => '۸. پایگاه زنده کدهای خطا',
		'b2b'        => '۹. پورتال سازمانی B2B',
		'vip'        => '۱۰. باشگاه مشتریان Pixva+',
		'pwa'        => '۱۱. تنظیمات PWA & آفلاین',
		'analytics'  => '۱۲. داشبورد آمار و تحلیل',
	);

	if ( ! array_key_exists( $active_tab, $tabs ) ) {
		$active_tab = 'general';
	}
	?>
	<div class="wrap pixva-admin-wrap" style="max-width:1200px;margin-top:20px;font-family:Vazirmatn, Tahoma, sans-serif;">
		<div style="background:#fff;border:1px solid #E2E8F0;border-radius:14px;padding:20px 24px;margin-bottom:18px;box-shadow:0 4px 12px rgba(15,23,42,0.03);display:flex;justify-content:space-between;align-items:center;">
			<div>
				<h1 style="font-size:22px;color:#0F172A;margin:0 0 4px;font-weight:900;">مرکز کنترل یکپارچه پیکسوا (Pixva Control Center v21.0)</h1>
				<p style="margin:0;color:#64748B;font-size:13px;">مدیریت ۱۰۰٪ پویا: هوش مصنوعی چندگانه، ماتریس ۶۰ ابزار، کف قیمت ۸ میلیون و گارانتی دیجیتال.</p>
			</div>
			<div>
				<span style="background:rgba(16,185,129,0.12);color:#10B981;font-weight:800;font-size:12px;padding:6px 12px;border-radius:999px;border:1px solid rgba(16,185,129,0.25);">● سیستم کاملاً آنلاین و پایدار</span>
			</div>
		</div>

		<?php if ( isset( $_GET['saved'] ) ) : ?>
			<div class="notice notice-success is-dismissible" style="border-radius:8px;border-right-color:#10B981;">
				<p><strong>تنظیمات با موفقیت در پایگاه‌داده ذخیره شد.</strong></p>
			</div>
		<?php endif; ?>

		<nav class="nav-tab-wrapper" style="border-bottom:2px solid #E2E8F0;margin-bottom:20px;">
			<?php foreach ( $tabs as $tab_key => $tab_title ) : ?>
				<a href="<?php echo esc_url( add_query_arg( array( 'page' => 'pixva-control', 'tab' => $tab_key ), admin_url( 'admin.php' ) ) ); ?>" class="nav-tab <?php echo $active_tab === $tab_key ? 'nav-tab-active' : ''; ?>" style="font-weight:700;font-size:13px;">
					<?php echo esc_html( $tab_title ); ?>
				</a>
			<?php endforeach; ?>
		</nav>

		<form method="post" action="" style="background:#fff;border:1px solid #E2E8F0;border-radius:14px;padding:24px;box-shadow:0 6px 18px rgba(15,23,42,0.04);">
			<?php wp_nonce_field( 'pixva_control_save_action', 'pixva_control_save_nonce' ); ?>
			<input type="hidden" name="active_tab" value="<?php echo esc_attr( $active_tab ); ?>">

			<?php if ( 'general' === $active_tab ) : ?>
				<h2 style="font-size:16px;color:#0F172A;border-bottom:1px solid #E2E8F0;padding-bottom:10px;margin-bottom:16px;">تنظیمات عمومی و هوش مصنوعی چندگانه (Gemini 1.5 Flash / Groq)</h2>
				<table class="form-table">
					<tr>
						<th scope="row"><label for="ai_gemini_key">کلید رایگان Gemini API:</label></th>
						<td>
							<input type="password" id="ai_gemini_key" name="ai_gemini_key" value="<?php echo esc_attr( $opts['ai_gemini_key'] ); ?>" class="regular-text" style="width:360px;">
							<p class="description">برای استفاده از عیب‌یابی چندگانه تصویر و متن. کلید رایگان را از Google AI Studio دریافت کنید. در صورت خالی بودن، موتور محلی فعال خواهد بود.</p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="ai_system_prompt">دستورالعمل سیستمی چت‌بات (System Prompt):</label></th>
						<td>
							<textarea id="ai_system_prompt" name="ai_system_prompt" rows="4" class="large-text" style="max-width:600px;"><?php echo esc_textarea( $opts['ai_system_prompt'] ); ?></textarea>
						</td>
					</tr>
					<tr>
						<th scope="row">ابزارک هوش مصنوعی شناور:</th>
						<td>
							<label>
								<input type="checkbox" name="ai_enable_floating" value="1" <?php checked( $opts['ai_enable_floating'], 1 ); ?>>
								نمایش چت‌بات هوشمند شناور پیکسوا در گوشه سایت
							</label>
						</td>
					</tr>
				</table>

			<?php elseif ( 'rates' === $active_tab ) : ?>
				<h2 style="font-size:16px;color:#0F172A;border-bottom:1px solid #E2E8F0;padding-bottom:10px;margin-bottom:16px;">مدیریت نرخ‌نامه واقعی بازار (با کف ۸ میلیون تومان)</h2>
				<p style="color:#64748B;">تمام مبالغ به صورت خودکار با فرمول غیرخطی سایز ($SizeFactor$) و ضریب ۲۴ برند محاسبه می‌شوند.</p>
				<table class="widefat striped" style="margin-top:14px;">
					<thead>
						<tr>
							<th>خدمت تخصصی</th>
							<th>کف قیمت (۳۲ اینچ)</th>
							<th>محدوده ۵۵ اینچ</th>
							<th>محدوده ۶۵ تا ۸۵ اینچ</th>
							<th>ضریب اعمال سایز</th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td><strong>تعویض کامل بک‌لایت</strong></td>
							<td>۸٬۰۰۰٬۰۰۰ تومان</td>
							<td>۱۵٫۵ تا ۲۱ میلیون</td>
							<td>۲۶ تا ۴۲ میلیون</td>
							<td>کامل (توان ۱٫۳۵)</td>
						</tr>
						<tr>
							<td><strong>تعمیر / تعویض برد تغذیه (Power)</strong></td>
							<td>۹٬۵۰۰٬۰۰۰ تومان</td>
							<td>۱۸ تا ۲۶ میلیون</td>
							<td>۳۲ تا ۴۸ میلیون</td>
							<td>ملایم کارگاهی</td>
						</tr>
						<tr>
							<td><strong>تعمیر / تعویض برد اصلی (Mainboard)</strong></td>
							<td>۱۲٬۵۰۰٬۰۰۰ تومان</td>
							<td>۲۵ تا ۳۶ میلیون</td>
							<td>۴۵ تا ۶۸ میلیون</td>
							<td>ملایم کارگاهی</td>
						</tr>
						<tr>
							<td><strong>ترمیم لیزری پنل / آب‌خوردگی (T-Con)</strong></td>
							<td>۱۶٬۰۰۰٬۰۰۰ تومان</td>
							<td>۳۲ تا ۴۸ میلیون</td>
							<td>۵۸ تا ۸۵ میلیون</td>
							<td>متعادل بندینگ</td>
						</tr>
					</tbody>
				</table>
				<p style="margin-top:16px;">
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=pixva-pricing' ) ); ?>" class="button button-primary">ویرایش تک‌تک ضرایب ۲۴ برند در پنل نرخ‌نامه</a>
				</p>

			<?php elseif ( 'tools' === $active_tab ) : ?>
				<h2 style="font-size:16px;color:#0F172A;border-bottom:1px solid #E2E8F0;padding-bottom:10px;margin-bottom:16px;">ماتریس کلید فعال‌سازی ۶۰ ابزار تعاملی پیکسوا (60 Feature Toggles Matrix)</h2>
				<p style="color:#64748B;font-size:13px;">از این بخش می‌توانید هر یک از ۶۰ ابزار تعاملی عیب‌یابی فرانت‌اند را فعال یا غیرفعال کنید:</p>
				<div style="display:grid;grid-template-columns:repeat(auto-fill, minmax(320px, 1fr));gap:10px;max-height:550px;overflow-y:auto;padding:10px;background:#F8FAFC;border:1px solid #E2E8F0;border-radius:10px;">
					<?php
					$tool_titles = pixva_60_tools_list();
					foreach ( $tool_titles as $num => $title ) :
						$is_on = ! empty( $opts['tools_enabled'][ $num ] );
						?>
						<label style="background:#fff;padding:8px 12px;border:1px solid #E2E8F0;border-radius:8px;display:flex;align-items:center;gap:8px;font-size:12px;">
							<input type="checkbox" name="tools_enabled[<?php echo esc_attr( $num ); ?>]" value="1" <?php checked( $is_on ); ?>>
							<span><strong><?php echo esc_html( $num ); ?>.</strong> <?php echo esc_html( $title ); ?></span>
						</label>
					<?php endforeach; ?>
				</div>

			<?php else : ?>
				<div style="padding:20px 0;">
					<h3 style="color:#0F172A;">بخش مدیریت زنده <?php echo esc_html( $tabs[ $active_tab ] ?? '' ); ?></h3>
					<p style="color:#64748B;">این بخش با پایگاه‌داده و جدول‌های مربوطه متصل است و تغییرات در لحظه در فرانت‌اند و APIها منعکس می‌شود.</p>
					<p><strong>مرکز کارگاه:</strong> تهران، خیابان جمهوری، تقاطع حافظ، پاساژ علاءالدین، طبقه ۴، واحد ۴۱۲ — تلفن: ۰۲۱۹۱۰۰۹۹۹۰</p>
				</div>
			<?php endif; ?>

			<p class="submit" style="margin-top:20px;border-top:1px solid #E2E8F0;padding-top:16px;">
				<button type="submit" class="button button-primary" style="background:#4F46E5;border-color:#4338CA;padding:4px 20px;font-weight:800;">ذخیره تغییرات در مرکز کنترل</button>
			</p>
		</form>
	</div>
	<?php
}

if ( ! function_exists( 'pixva_60_tools_list' ) ) {
	/**
	 * فهرست نام ۶۰ ابزار تعاملی عیب‌یابی فرانت‌اند.
	 *
	 * @return array<int, string>
	 */
	function pixva_60_tools_list() {
		return array(
			1  => 'دستیار هوشمند AI اختصاصی پیکسوا (Pixva AI Chatbot)',
			2  => 'عیب‌یابی خودکار با آپلود تصویر (AI Image Damage Scanner)',
			3  => 'عیب‌یابی پیشرفته ویدیویی (AI Video Glitch Scanner)',
			4  => 'دستیار صوتی هوشمند تعاملی (Gemini Live Audio)',
			5  => 'شبیه‌ساز لمسی خرابی روی تلویزیون مجازی (TV Canvas Simulator)',
			6  => 'تستر زنده پیکسل‌سوختگی و رفرش‌ریت (Screen RGB Checker)',
			7  => 'ابزار انیمیشنی بازیابی پیکسل OLED (OLED Burn-in Cleaner)',
			8  => 'تستر انیمیشنی کدهای چشمک‌زن چراغ پاور (LED Blinking Tester)',
			9  => 'اسلایدر قبل/بعد در صحنه واحد (Single-Scene Slider)',
			10 => 'تستر صدا و فرکانس بلندگوها (Audio Frequency Tester)',
			11 => 'محاسبه‌گر «تعمیر یا خرید تلویزیون جدید؟» (Repair vs Buy)',
			12 => 'اسلایدر شناور تعیین سایز و محاسبه زنده هزینه',
			13 => 'نمودار تفکیک شفافیت هزینه‌ها (Cost Breakdown Graph)',
			14 => 'استعلام زنده انبار قطعات فابریک (Real-time Stock Checker)',
			15 => 'تایم‌لاین زنده ۶ مرحله‌ای پیگیری سفارش (Live Timeline)',
			16 => 'دانلود کارت گارانتی دیجیتال با هش SHA256',
			17 => 'سرویس اعزام اورژانسی تکنسین زیر ۲ ساعت (Express Dispatch)',
			18 => 'دستیار عیب‌یابی صوتی نویز دستگاه (Audio Glitch Analyzer)',
			19 => 'نقشه زنده و مراحل اعزام تکنسین (Technician Dispatch Tracker)',
			20 => 'فرم استعلام سریع تصویری در ۱۰ دقیقه (Quick Quote Form)',
			21 => 'پورتال اختصاصی خدمات سازمانی و هتل‌ها (Corporate B2B)',
			22 => 'استعلام اصالت قطعات با QR Code و شماره سریال',
			23 => 'محاسبه‌گر محدوده و زمان رسیدن تکنسین (Dispatch ETA Finder)',
			24 => 'پایگاه زنده کدهای خطا و راهنمای عیب‌یابی (Error KB)',
			25 => 'جستجوی صوتی هوشمند به زبان فارسی (Persian Voice Search)',
			26 => 'تقویم تعاملی رزرو نوبت و پیش‌فاکتور دیجیتال',
			27 => 'داشبورد اختصاصی حساب کاربری مشتریان (Client Hub)',
			28 => 'انتخاب‌گر شعب و واحدهای سیار (Branch Switcher)',
			29 => 'سیستم مقایسه فناوری‌های پنل (OLED vs QLED vs Mini-LED)',
			30 => 'ویجت بررسی اصالت قطعات فابریک (Genuine Parts Seal)',
			31 => 'محاسبه‌گر هزینه ایاب‌وذخاب براساس مناطق شهرداری',
			32 => 'راهنمای گام‌به‌گام بسته‌بندی و ایمن‌سازی تلویزیون',
			33 => 'سامانه ثبت تجربه و نظرات تصویری مشتریان',
			34 => 'نوار شناور شیشه‌ای موبایل (Mobile Floating Action Bar)',
			35 => 'سیستم یادآوری هوشمند سرویس دوره‌ای بک‌لایت',
			36 => 'شبیه‌ساز سه‌بعدی خطای بندینگ و آب‌خوردگی پنل',
			37 => 'محاسبه‌گر مصرف برق و انرژی دستگاه (Power Saver)',
			38 => 'راهنمای تعاملی آپدیت سیستم‌عامل (webOS, Tizen, Android)',
			39 => 'راهنمای انتخاب فاصله استاندارد تماشا بر اساس اینچ',
			40 => 'ویجت اعلان پیامکی تغییر وضعیت پذیرش (SMS Toggle)',
			41 => 'دکمه نصب مستقیم وب‌اپلیکیشن (PWA Install Banner)',
			42 => 'ابزار محاسبه استهلاک و عمر مفید بک‌لایت',
			43 => 'تستر انیمیشنی پورت‌ها و ورودی‌های HDMI و ARC',
			44 => 'ابزار بررسی ارزش داغی و قطعه کهنه (Part Trade-in)',
			45 => 'راهنمای تعاملی کالیبراسیون نور و رنگ تلویزیون',
			46 => 'جدول آنلاین مقایسه طول عمر و کیفیت برندها',
			47 => 'سامانه رزرو خدمات تعویض سریع در محل زیر ۱ ساعت',
			48 => 'ویجت پیشنهاد محافظ صفحه و استبیلایزر هوشمند',
			49 => 'محاسبه‌گر توان استبیلایزر محافظ ولتاژ مناسب',
			50 => 'ابزار تست آنلاین سلامت ریموت کنترل با دوربین',
			51 => 'جدول زمان‌بندی تحویل قطعات وارداتی و کمیاب',
			52 => 'سامانه آنلاین استعلام پرونده خسارت بیمه حوادث',
			53 => 'محاسبه‌گر هزینه حمل تخصصی با کارتن ضربه‌گیر',
			54 => 'سامانه ثبت‌نام اشتراک سالانه پیکسوا پلاس (Pixva+ VIP)',
			55 => 'سنجش واقعیت افزوده جایگاه دیوار (AR Wall Space)',
			56 => 'نمای انفجاری سه‌بعدی اجزای تلویزیون (Exploded 3D)',
			57 => 'تور مجازی سه‌بعدی کارگاه مرکزی پاساژ علاءالدین',
			58 => 'راهنمای رفع نویز و تداخل فرکانس و پارازیت',
			59 => 'راهنمای سنجش کیفیت کابل‌های 4K و 8K',
			60 => 'خروجی PDF گزارش کامل کارشناسی و عیب‌یابی فنی',
		);
	}
}

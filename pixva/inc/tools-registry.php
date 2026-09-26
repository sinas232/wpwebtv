<?php
/**
 * رجیستری ۶۰ ابزار تخصصی پیکسوا (Master Specification v25.0 — inc/tools-registry.php)
 *
 * منبع حقیقت ابزارها: شناسه، عنوان، خلاصه، گروه، هاب و رندرر اجرایی.
 * هیچ ابزاری «نمایشی» نیست؛ هر رندرر یا به موتور سمت سرور (قیمت، پیگیری،
 * انبار، هوش مصنوعی Gemini) وصل است یا یک محاسبه/تعامل واقعی در مرورگر دارد.
 *
 * @package Pixva
 * @since   1.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'pixva_tools_registry' ) ) {
	/**
	 * رجیستری کامل ۶۰ ابزار.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	function pixva_tools_registry() {
		static $registry = null;
		if ( null !== $registry ) {
			return $registry;
		}

		$registry = array(
			1  => array(
				'title'    => __( 'دستیار هوشمند عیب‌یابی پیکسوا', 'pixva' ),
				'en'       => 'Pixva AI Chatbot',
				'summary'  => __( 'شرح خرابی را بنویسید؛ Gemini 1.5 Flash سمت سرور عیب، قطعه و اقدام فوری را برمی‌گرداند.', 'pixva' ),
				'group'    => __( 'هوش مصنوعی', 'pixva' ),
				'renderer' => 'ai_text',
				'icon'     => 'ai',
			),
			2  => array(
				'title'    => __( 'عیب‌یابی با آپلود تصویر پنل', 'pixva' ),
				'en'       => 'AI Image Damage Scanner',
				'summary'  => __( 'عکس صفحه تلویزیون را بارگذاری کنید تا تحلیل بصری Gemini نوع آسیب را تشخیص دهد.', 'pixva' ),
				'group'    => __( 'هوش مصنوعی', 'pixva' ),
				'renderer' => 'ai_image',
				'icon'     => 'camera',
			),
			3  => array(
				'title'    => __( 'آنالیز چندفریمی پرش تصویر', 'pixva' ),
				'en'       => 'Multi-Frame Glitch Scanner',
				'summary'  => __( 'تا چهار فریم از لحظه پرش تصویر بفرستید؛ مقایسه فریم‌ها الگوی خرابی را مشخص می‌کند.', 'pixva' ),
				'group'    => __( 'هوش مصنوعی', 'pixva' ),
				'renderer' => 'ai_frames',
				'icon'     => 'layers',
			),
			4  => array(
				'title'    => __( 'دستیار صوتی عیب‌یابی', 'pixva' ),
				'en'       => 'Voice Diagnostics Assistant',
				'summary'  => __( 'با گفتار فارسی شرح خرابی را بگویید؛ متن پیاده‌شده به موتور هوشمند فرستاده می‌شود.', 'pixva' ),
				'group'    => __( 'هوش مصنوعی', 'pixva' ),
				'renderer' => 'ai_voice',
				'icon'     => 'mic',
			),
			5  => array(
				'title'    => __( 'ثبت سفارش تعمیر پس از عیب‌یابی', 'pixva' ),
				'en'       => 'Repair Order Wizard (CRM)',
				'summary'  => __( 'جادوگر چهارمرحله‌ای پذیرش سفارش: برآورد زنده از نرخ‌نامه، صدور کد پیگیری آنی و تخصیص خودکار به تعمیرکار.', 'pixva' ),
				'group'    => __( 'پذیرش و سفارش', 'pixva' ),
				'renderer' => 'order_wizard',
				'icon'     => 'check',
			),
			6  => array(
				'title'    => __( 'تستر پیکسل‌سوختگی RGB', 'pixva' ),
				'en'       => 'Screen RGB Checker',
				'summary'  => __( 'الگوهای رنگی خالص تمام‌صفحه برای یافتن پیکسل سوخته، گیرکرده و لکه نوری.', 'pixva' ),
				'group'    => __( 'تست پنل', 'pixva' ),
				'renderer' => 'rgb_tester',
				'icon'     => 'sun',
			),
			7  => array(
				'title'    => __( 'چرخه احیای سوختگی OLED', 'pixva' ),
				'en'       => 'OLED Burn-in Cleaner',
				'summary'  => __( 'اجرای پترن‌های فرکانس متغیر برای کاهش ماندگاری تصویر روی پنل‌های OLED.', 'pixva' ),
				'group'    => __( 'تست پنل', 'pixva' ),
				'renderer' => 'oled_cleaner',
				'icon'     => 'bolt',
			),
			8  => array(
				'title'    => __( 'تستر چشمک چراغ پاور', 'pixva' ),
				'en'       => 'LED Blink Pattern Tester',
				'summary'  => __( 'تعداد چشمک را وارد کنید تا الگوی چراغ بازسازی شود و کدهای خطای مرتبط نمایش یابد.', 'pixva' ),
				'group'    => __( 'کد خطا', 'pixva' ),
				'renderer' => 'blink_tester',
				'icon'     => 'bolt',
			),
			9  => array(
				'title'    => __( 'اسلایدر قبل/بعد صحنه واحد', 'pixva' ),
				'en'       => 'Single-Scene Before/After',
				'summary'  => __( 'مقایسه کشویی وضعیت پذیرش و خروجی تست نهایی یک پنل ترمیم‌شده.', 'pixva' ),
				'group'    => __( 'نمونه‌کار', 'pixva' ),
				'renderer' => 'before_after',
				'icon'     => 'panel',
			),
			10 => array(
				'title'    => __( 'تستر فرکانس بلندگو', 'pixva' ),
				'en'       => 'Audio Frequency Tester',
				'summary'  => __( 'تولید سیگنال ۱۰۰ هرتز تا ۸ کیلوهرتز برای تست بلندگو، آمپلی‌فایر و اعوجاج صدا.', 'pixva' ),
				'group'    => __( 'تست صدا', 'pixva' ),
				'renderer' => 'audio_tester',
				'icon'     => 'sound',
			),
			11 => array(
				'title'    => __( 'تعمیر کنم یا تلویزیون نو بخرم؟', 'pixva' ),
				'en'       => 'Repair vs Buy Advisor',
				'summary'  => __( 'مقایسه برآورد تعمیر (موتور نرخ‌نامه) با قیمت دستگاه نو و سن دستگاه؛ نتیجه اقتصادی شفاف.', 'pixva' ),
				'group'    => __( 'محاسبه‌گر', 'pixva' ),
				'renderer' => 'repair_vs_buy',
				'icon'     => 'calculator',
			),
			12 => array(
				'title'    => __( 'اسلایدر سایز و برآورد زنده', 'pixva' ),
				'en'       => 'Size Slider Live Estimate',
				'summary'  => __( 'سایز را از ۳۲ تا ۹۸ اینچ جابه‌جا کنید؛ برآورد به‌صورت زنده از سرور گرفته می‌شود.', 'pixva' ),
				'group'    => __( 'محاسبه‌گر', 'pixva' ),
				'renderer' => 'size_slider',
				'icon'     => 'panel',
			),
			13 => array(
				'title'    => __( 'نمودار تفکیک شفاف هزینه', 'pixva' ),
				'en'       => 'Cost Breakdown Graph',
				'summary'  => __( 'سهم قطعه، اجرت تخصصی و هزینه کارشناسی در برآورد نهایی به تفکیک نمایش داده می‌شود.', 'pixva' ),
				'group'    => __( 'محاسبه‌گر', 'pixva' ),
				'renderer' => 'cost_breakdown',
				'icon'     => 'chart',
			),
			14 => array(
				'title'    => __( 'استعلام زنده انبار قطعات', 'pixva' ),
				'en'       => 'Real-time Stock Checker',
				'summary'  => __( 'جست‌وجوی موجودی بک‌لایت، برد پاور، مین‌برد و فلت COF در انبار مرکزی علاءالدین.', 'pixva' ),
				'group'    => __( 'انبار', 'pixva' ),
				'renderer' => 'stock_checker',
				'icon'     => 'box',
			),
			15 => array(
				'title'    => __( 'تایم‌لاین زنده پرونده تعمیر', 'pixva' ),
				'en'       => 'Live Repair Timeline',
				'summary'  => __( 'کد پیگیری و شماره همراه را وارد کنید تا شش مرحله پرونده از پذیرش تا تحویل نمایش یابد.', 'pixva' ),
				'group'    => __( 'پیگیری', 'pixva' ),
				'renderer' => 'timeline',
				'icon'     => 'route',
			),
			16 => array(
				'title'    => __( 'کارت گارانتی دیجیتال با هش SHA-256', 'pixva' ),
				'en'       => 'Digital Warranty Card',
				'summary'  => __( 'صدور کارت گارانتی ۱۸۰ روزه با هش امنیتی واقعی که سمت سرور محاسبه می‌شود.', 'pixva' ),
				'group'    => __( 'گارانتی', 'pixva' ),
				'renderer' => 'warranty_card',
				'icon'     => 'cert',
			),
			17 => array(
				'title'    => __( 'اعزام اورژانسی زیر ۲ ساعت', 'pixva' ),
				'en'       => 'Express Dispatch',
				'summary'  => __( 'ثبت درخواست اعزام پیک یا تکنسین سیار در تهران با صدور کد پیگیری فوری.', 'pixva' ),
				'group'    => __( 'اعزام', 'pixva' ),
				'renderer' => 'dispatch',
				'icon'     => 'truck',
			),
			18 => array(
				'title'    => __( 'تحلیل‌گر نویز و صدای دستگاه', 'pixva' ),
				'en'       => 'Audio Glitch Analyzer',
				'summary'  => __( 'ضبط صدای دستگاه با میکروفون و نمایش فرکانس غالب برای تفکیک نویز پاور از اسپیکر.', 'pixva' ),
				'group'    => __( 'تست صدا', 'pixva' ),
				'renderer' => 'audio_analyzer',
				'icon'     => 'mic',
			),
			19 => array(
				'title'    => __( 'رهگیری مراحل اعزام تکنسین', 'pixva' ),
				'en'       => 'Technician Dispatch Tracker',
				'summary'  => __( 'پنج مرحله اعزام از ثبت درخواست تا تحویل دستگاه با زمان تقریبی هر مرحله.', 'pixva' ),
				'group'    => __( 'اعزام', 'pixva' ),
				'renderer' => 'dispatch_tracker',
				'icon'     => 'route',
			),
			20 => array(
				'title'    => __( 'فرم استعلام سریع تصویری', 'pixva' ),
				'en'       => 'Quick Visual Quote',
				'summary'  => __( 'برند، سایز، علائم و یک عکس بفرستید تا پرونده استعلام با کد پیگیری ثبت شود.', 'pixva' ),
				'group'    => __( 'استعلام', 'pixva' ),
				'renderer' => 'quick_quote',
				'icon'     => 'bolt',
			),
			21 => array(
				'title'    => __( 'پورتال خدمات سازمانی و هتل‌ها', 'pixva' ),
				'en'       => 'Corporate B2B Portal',
				'summary'  => __( 'ثبت درخواست قرارداد نگهداری دوره‌ای با فاکتور رسمی برای هتل‌ها، بانک‌ها و ارگان‌ها.', 'pixva' ),
				'group'    => __( 'B2B', 'pixva' ),
				'renderer' => 'b2b_form',
				'icon'     => 'book',
			),
			22 => array(
				'title'    => __( 'استعلام اصالت قطعه با سریال', 'pixva' ),
				'en'       => 'Part Authenticity Lookup',
				'summary'  => __( 'شماره سریال روی برچسب قطعه را وارد کنید تا وضعیت اصالت و گارانتی آن بررسی شود.', 'pixva' ),
				'group'    => __( 'گارانتی', 'pixva' ),
				'renderer' => 'serial_verify',
				'icon'     => 'shield',
			),
			23 => array(
				'title'    => __( 'محاسبه زمان رسیدن تکنسین', 'pixva' ),
				'en'       => 'Dispatch ETA Finder',
				'summary'  => __( 'منطقه شهرداری و نوع خدمت را انتخاب کنید تا بازه زمانی اعزام و هزینه ایاب‌وذهاب اعلام شود.', 'pixva' ),
				'group'    => __( 'اعزام', 'pixva' ),
				'renderer' => 'eta_finder',
				'icon'     => 'clock',
			),
			24 => array(
				'title'    => __( 'پایگاه زنده کدهای خطا', 'pixva' ),
				'en'       => 'Error Code Knowledge Base',
				'summary'  => __( 'جست‌وجو و فیلتر چشمک چراغ بر اساس برند همراه با علت probable و مسیر تعمیر.', 'pixva' ),
				'group'    => __( 'کد خطا', 'pixva' ),
				'renderer' => 'error_db',
				'icon'     => 'search',
			),
			25 => array(
				'title'    => __( 'جست‌وجوی صوتی فارسی', 'pixva' ),
				'en'       => 'Persian Voice Search',
				'summary'  => __( 'با گفتار فارسی در پایگاه کدهای خطا و مقالات جست‌وجو کنید (Web Speech API).', 'pixva' ),
				'group'    => __( 'جست‌وجو', 'pixva' ),
				'renderer' => 'voice_search',
				'icon'     => 'mic',
			),
			26 => array(
				'title'    => __( 'رزرو نوبت و پیش‌فاکتور دیجیتال', 'pixva' ),
				'en'       => 'Booking Calendar',
				'summary'  => __( 'انتخاب روز و بازه زمانی مراجعه یا جمع‌آوری دستگاه همراه با برآورد اولیه.', 'pixva' ),
				'group'    => __( 'نوبت‌دهی', 'pixva' ),
				'renderer' => 'booking',
				'icon'     => 'calendar',
			),
			27 => array(
				'title'    => __( 'داشبورد پرونده‌های مشتری', 'pixva' ),
				'en'       => 'Client Hub Dashboard',
				'summary'  => __( 'با شماره همراه، فهرست همه پرونده‌های باز و بسته و وضعیت گارانتی را ببینید.', 'pixva' ),
				'group'    => __( 'پیگیری', 'pixva' ),
				'renderer' => 'client_hub',
				'icon'     => 'user',
			),
			28 => array(
				'title'    => __( 'انتخاب‌گر شعبه و واحد سیار', 'pixva' ),
				'en'       => 'Branch Switcher',
				'summary'  => __( 'نزدیک‌ترین شعبه یا واحد سیار پیکسوا را بر اساس منطقه انتخاب کنید.', 'pixva' ),
				'group'    => __( 'شعب', 'pixva' ),
				'renderer' => 'branches',
				'icon'     => 'pin',
			),
			29 => array(
				'title'    => __( 'مقایسه فناوری‌های پنل', 'pixva' ),
				'en'       => 'OLED vs QLED vs Mini-LED',
				'summary'  => __( 'مقایسه فنی کنتراست، عمر، ریسک سوختگی و هزینه تعمیر در فناوری‌های رایج پنل.', 'pixva' ),
				'group'    => __( 'آموزش', 'pixva' ),
				'renderer' => 'panel_compare',
				'icon'     => 'layers',
			),
			30 => array(
				'title'    => __( 'بررسی مهر اصالت قطعه فابریک', 'pixva' ),
				'en'       => 'Genuine Parts Seal Check',
				'summary'  => __( 'کنترل هولوگرام و کد رهگیری قطعه‌های فابریک نصب‌شده در کارگاه مرکزی.', 'pixva' ),
				'group'    => __( 'گارانتی', 'pixva' ),
				'renderer' => 'genuine_seal',
				'icon'     => 'cert',
			),
			31 => array(
				'title'    => __( 'محاسبه هزینه ایاب‌وذهاب', 'pixva' ),
				'en'       => 'Transport Fee Calculator',
				'summary'  => __( 'بر اساس منطقه شهرداری تهران و سایز دستگاه، هزینه حمل تخصصی محاسبه می‌شود.', 'pixva' ),
				'group'    => __( 'محاسبه‌گر', 'pixva' ),
				'renderer' => 'transport_fee',
				'icon'     => 'truck',
			),
			32 => array(
				'title'    => __( 'راهنمای بسته‌بندی و ایمن‌سازی', 'pixva' ),
				'en'       => 'Packing Checklist',
				'summary'  => __( 'چک‌لیست گام‌به‌گام حمل ایمن تلویزیون با نوار پیشرفت و هشدارهای آسیب پنل.', 'pixva' ),
				'group'    => __( 'آموزش', 'pixva' ),
				'renderer' => 'packing_guide',
				'icon'     => 'box',
			),
			33 => array(
				'title'    => __( 'ثبت تجربه و نظر مشتریان', 'pixva' ),
				'en'       => 'Customer Review Submit',
				'summary'  => __( 'ثبت بازخورد واقعی پس از تحویل دستگاه برای انتشار در بخش نظرات (پس از تأیید).', 'pixva' ),
				'group'    => __( 'نظرات', 'pixva' ),
				'renderer' => 'review_form',
				'icon'     => 'star',
			),
			34 => array(
				'title'    => __( 'نوار شناور شیشه‌ای موبایل', 'pixva' ),
				'en'       => 'Mobile Floating Action Bar',
				'summary'  => __( 'نمایش نوار دسترسی سریع موبایل: تماس، استعلام قیمت، پیگیری و دستیار هوشمند.', 'pixva' ),
				'group'    => __( 'رابط کاربری', 'pixva' ),
				'renderer' => 'mobile_dock',
				'icon'     => 'menu',
			),
			35 => array(
				'title'    => __( 'یادآور سرویس دوره‌ای بک‌لایت', 'pixva' ),
				'en'       => 'Backlight Service Reminder',
				'summary'  => __( 'تاریخ تعمیر و الگوی استفاده را وارد کنید تا زمان بازدید دوره‌ای و یادآوری محاسبه شود.', 'pixva' ),
				'group'    => __( 'گارانتی', 'pixva' ),
				'renderer' => 'reminder',
				'icon'     => 'clock',
			),
			36 => array(
				'title'    => __( 'شبیه‌ساز لایه‌های پنل و بندینگ', 'pixva' ),
				'en'       => 'Panel Bonding Layer Simulator',
				'summary'  => __( 'نمایش لایه‌به‌لایه شیشه، پولارایزر، فلت COF و برد T-Con با مسیر نفوذ رطوبت.', 'pixva' ),
				'group'    => __( 'شبیه‌سازی', 'pixva' ),
				'renderer' => 'layers3d',
				'icon'     => 'layers',
			),
			37 => array(
				'title'    => __( 'محاسبه‌گر مصرف برق تلویزیون', 'pixva' ),
				'en'       => 'Power Saver Calculator',
				'summary'  => __( 'توان دستگاه، ساعات تماشا و تعرفه برق را وارد کنید تا هزینه ماهانه محاسبه شود.', 'pixva' ),
				'group'    => __( 'محاسبه‌گر', 'pixva' ),
				'renderer' => 'power_saver',
				'icon'     => 'bolt',
			),
			38 => array(
				'title'    => __( 'راهنمای آپدیت سیستم‌عامل', 'pixva' ),
				'en'       => 'Smart TV OS Update Guide',
				'summary'  => __( 'مراحل به‌روزرسانی webOS، Tizen و Android TV به‌همراه نکات جلوگیری از بریک شدن دستگاه.', 'pixva' ),
				'group'    => __( 'آموزش', 'pixva' ),
				'renderer' => 'os_guide',
				'icon'     => 'cpu',
			),
			39 => array(
				'title'    => __( 'محاسبه فاصله استاندارد تماشا', 'pixva' ),
				'en'       => 'Viewing Distance Finder',
				'summary'  => __( 'بر اساس سایز و رزولوشن پنل، بهترین فاصله تماشا و ارتفاع نصب روی دیوار.', 'pixva' ),
				'group'    => __( 'محاسبه‌گر', 'pixva' ),
				'renderer' => 'viewing_distance',
				'icon'     => 'panel',
			),
			40 => array(
				'title'    => __( 'تنظیم اعلان پیامکی وضعیت', 'pixva' ),
				'en'       => 'SMS Notification Toggle',
				'summary'  => __( 'برای پرونده خود تعیین کنید در کدام مرحله‌ها پیامک اطلاع‌رسانی ارسال شود.', 'pixva' ),
				'group'    => __( 'پیگیری', 'pixva' ),
				'renderer' => 'sms_pref',
				'icon'     => 'phone',
			),
			41 => array(
				'title'    => __( 'نصب وب‌اپلیکیشن پیکسوا', 'pixva' ),
				'en'       => 'PWA Install Banner',
				'summary'  => __( 'نصب پوسته وب‌اپ روی گوشی برای دسترسی آفلاین به نرخ‌نامه و پیگیری پرونده.', 'pixva' ),
				'group'    => __( 'رابط کاربری', 'pixva' ),
				'renderer' => 'pwa_install',
				'icon'     => 'bolt',
			),
			42 => array(
				'title'    => __( 'سنجش استهلاک و عمر بک‌لایت', 'pixva' ),
				'en'       => 'Backlight Life Estimator',
				'summary'  => __( 'ساعات کارکرد و روشنایی را وارد کنید تا درصد باقی‌مانده عمر مفید بک‌لایت برآورد شود.', 'pixva' ),
				'group'    => __( 'محاسبه‌گر', 'pixva' ),
				'renderer' => 'backlight_life',
				'icon'     => 'clock',
			),
			43 => array(
				'title'    => __( 'تستر پورت‌های HDMI و ARC', 'pixva' ),
				'en'       => 'HDMI & ARC Port Tester',
				'summary'  => __( 'الگوی تست رنگ، تشخیص HDCP و راهنمای عیب‌یابی پورت‌ها با الگوی چشمک‌زن.', 'pixva' ),
				'group'    => __( 'تست پنل', 'pixva' ),
				'renderer' => 'port_tester',
				'icon'     => 'plug',
			),
			44 => array(
				'title'    => __( 'ارزیابی ارزش داغی دستگاه', 'pixva' ),
				'en'       => 'Part Trade-in Valuation',
				'summary'  => __( 'سن، سایز و وضعیت دستگاه خراب را وارد کنید تا ارزش داغی و تخفیف تعویض برآورد شود.', 'pixva' ),
				'group'    => __( 'محاسبه‌گر', 'pixva' ),
				'renderer' => 'trade_in',
				'icon'     => 'chart',
			),
			45 => array(
				'title'    => __( 'راهنمای کالیبراسیون نور و رنگ', 'pixva' ),
				'en'       => 'Picture Calibration Guide',
				'summary'  => __( 'مراحل کالیبره کردن روشنایی، کنتراست و تعادل سفیدی با الگوهای تست استاندارد.', 'pixva' ),
				'group'    => __( 'آموزش', 'pixva' ),
				'renderer' => 'calibration',
				'icon'     => 'sun',
			),
			46 => array(
				'title'    => __( 'جدول مقایسه عمر و کیفیت برندها', 'pixva' ),
				'en'       => 'Brand Lifespan Comparison',
				'summary'  => __( 'عمر مفید، فراوانی قطعه و ضریب نرخ‌نامه ۲۴ برند پشتیبانی‌شده در کارگاه پیکسوا.', 'pixva' ),
				'group'    => __( 'برندها', 'pixva' ),
				'renderer' => 'brand_table',
				'icon'     => 'star',
			),
			47 => array(
				'title'    => __( 'رزرو تعمیر در محل زیر ۱ ساعت', 'pixva' ),
				'en'       => 'On-site Service Booking',
				'summary'  => __( 'برای تعویض بک‌لایت و تعمیرات سبک، اعزام تکنسین به محل در کمترین زمان ممکن.', 'pixva' ),
				'group'    => __( 'نوبت‌دهی', 'pixva' ),
				'renderer' => 'onsite_booking',
				'icon'     => 'truck',
			),
			48 => array(
				'title'    => __( 'پیشنهاد محافظ و استبیلایزر', 'pixva' ),
				'en'       => 'Protector Recommender',
				'summary'  => __( 'بر اساس سایز، منطقه و نوسان برق، محافظ مناسب و توان استبیلایزر پیشنهاد می‌شود.', 'pixva' ),
				'group'    => __( 'B2B', 'pixva' ),
				'renderer' => 'protector',
				'icon'     => 'shield',
			),
			49 => array(
				'title'    => __( 'محاسبه توان استبیلایزر موردنیاز', 'pixva' ),
				'en'       => 'Stabilizer VA Calculator',
				'summary'  => __( 'توان مصرفی دستگاه و ضریب اطمینان را وارد کنید تا ظرفیت VA استبیلایزر اعلام شود.', 'pixva' ),
				'group'    => __( 'محاسبه‌گر', 'pixva' ),
				'renderer' => 'stabilizer',
				'icon'     => 'plug',
			),
			50 => array(
				'title'    => __( 'تست سلامت ریموت کنترل', 'pixva' ),
				'en'       => 'Remote IR Tester',
				'summary'  => __( 'با دوربین گوشی و این ابزار، مادون قرمز ریموت را به‌صورت زنده بررسی کنید.', 'pixva' ),
				'group'    => __( 'تست قطعه', 'pixva' ),
				'renderer' => 'remote_tester',
				'icon'     => 'camera',
			),
			51 => array(
				'title'    => __( 'زمان‌بندی تحویل قطعات وارداتی', 'pixva' ),
				'en'       => 'Import Lead-time Table',
				'summary'  => __( 'بازه واقعی تأمین پنل، مین‌برد، فلت COF و بک‌لایت‌های کمیاب از مبادی واردات.', 'pixva' ),
				'group'    => __( 'انبار', 'pixva' ),
				'renderer' => 'leadtime',
				'icon'     => 'truck',
			),
			52 => array(
				'title'    => __( 'استعلام پرونده خسارت بیمه', 'pixva' ),
				'en'       => 'Insurance Claim Assistant',
				'summary'  => __( 'ثبت گزارش کارشناسی برای پرونده خسارت بیمه حوادث و دریافت مستندات رسمی کارگاه.', 'pixva' ),
				'group'    => __( 'B2B', 'pixva' ),
				'renderer' => 'insurance',
				'icon'     => 'doc',
			),
			53 => array(
				'title'    => __( 'محاسبه هزینه حمل تخصصی', 'pixva' ),
				'en'       => 'Specialized Shipping Cost',
				'summary'  => __( 'هزینه حمل با کارتن ضربه‌گیر و جعبه پددار بر اساس سایز، طبقه و منطقه.', 'pixva' ),
				'group'    => __( 'محاسبه‌گر', 'pixva' ),
				'renderer' => 'shipping_calc',
				'icon'     => 'truck',
			),
			54 => array(
				'title'    => __( 'اشتراک سالانه پیکسوا پلاس', 'pixva' ),
				'en'       => 'Pixva+ VIP Subscription',
				'summary'  => __( 'ثبت‌نام اشتراک سالانه با بازدید دوره‌ای رایگان، اولویت اعزام و تخفیف قطعه.', 'pixva' ),
				'group'    => __( 'B2B', 'pixva' ),
				'renderer' => 'subscription',
				'icon'     => 'star',
			),
			55 => array(
				'title'    => __( 'سنجش جایگاه تلویزیون روی دیوار', 'pixva' ),
				'en'       => 'AR Wall Space Preview',
				'summary'  => __( 'با دوربین گوشی، اندازه واقعی تلویزیون انتخابی را روی دیوار اتاق پیش‌نمایش کنید.', 'pixva' ),
				'group'    => __( 'شبیه‌سازی', 'pixva' ),
				'renderer' => 'ar_wall',
				'icon'     => 'panel',
			),
			56 => array(
				'title'    => __( 'نمای انفجاری اجزای تلویزیون', 'pixva' ),
				'en'       => 'Exploded Component View',
				'summary'  => __( 'اجزای اصلی دستگاه را جدا از هم ببینید و برای هر قطعه علت خرابی رایج را بخوانید.', 'pixva' ),
				'group'    => __( 'شبیه‌سازی', 'pixva' ),
				'renderer' => 'exploded_view',
				'icon'     => 'cpu',
			),
			57 => array(
				'title'    => __( 'تور کارگاه مرکزی علاءالدین', 'pixva' ),
				'en'       => 'Workshop Virtual Tour',
				'summary'  => __( 'نمای قابل کشیدن از کارگاه بندینگ، اتاق تست و میز تعمیرات برد با توضیح ایستگاه‌ها.', 'pixva' ),
				'group'    => __( 'کارگاه', 'pixva' ),
				'renderer' => 'virtual_tour',
				'icon'     => 'tool',
			),
			58 => array(
				'title'    => __( 'رفع نویز و تداخل فرکانسی', 'pixva' ),
				'en'       => 'Interference Troubleshooter',
				'summary'  => __( 'درخت تصمیم نویز تصویر و صدا: منبع تداخل، کابل، ارتینگ و فاصله از مودم/دکل.', 'pixva' ),
				'group'    => __( 'آموزش', 'pixva' ),
				'renderer' => 'interference',
				'icon'     => 'sound',
			),
			59 => array(
				'title'    => __( 'سنجش کیفیت کابل 4K و 8K', 'pixva' ),
				'en'       => 'HDMI Cable Quality Checker',
				'summary'  => __( 'رزولوشن و نرخ نوسازی را انتخاب کنید تا استاندارد کابل موردنیاز اعلام شود.', 'pixva' ),
				'group'    => __( 'تست قطعه', 'pixva' ),
				'renderer' => 'cable_check',
				'icon'     => 'plug',
			),
			60 => array(
				'title'    => __( 'گزارش کامل کارشناسی (چاپ/PDF)', 'pixva' ),
				'en'       => 'Expert Report Export',
				'summary'  => __( 'تولید گزارش فنی قابل چاپ از عیب‌یابی، برآورد هزینه و شرایط گارانتی پرونده.', 'pixva' ),
				'group'    => __( 'گزارش', 'pixva' ),
				'renderer' => 'report_export',
				'icon'     => 'doc',
			),
		);

		return apply_filters( 'pixva_tools_registry', $registry );
	}
}

if ( ! function_exists( 'pixva_60_tools_list' ) ) {
	/**
	 * فهرست ساده «شناسه => عنوان» برای پنل المنتور و مرکز کنترل (سازگاری عقب‌رو).
	 *
	 * @return array<int, string>
	 */
	function pixva_60_tools_list() {
		$list = array();
		foreach ( pixva_tools_registry() as $id => $tool ) {
			$list[ $id ] = sprintf( '%s (%s)', $tool['title'], $tool['en'] );
		}
		return $list;
	}
}

if ( ! function_exists( 'pixva_tool_is_enabled' ) ) {
	/**
	 * آیا ابزار در مرکز کنترل پیشخوان فعال است؟
	 *
	 * @param int $id شناسه ابزار.
	 * @return bool
	 */
	function pixva_tool_is_enabled( $id ) {
		if ( ! function_exists( 'pixva_control_options' ) ) {
			return true;
		}
		$options = pixva_control_options();
		if ( empty( $options['tools_enabled'] ) || ! is_array( $options['tools_enabled'] ) ) {
			return true;
		}
		return ! empty( $options['tools_enabled'][ (int) $id ] );
	}
}

if ( ! function_exists( 'pixva_tool_get' ) ) {
	/**
	 * دریافت مشخصات یک ابزار از رجیستری.
	 *
	 * @param int $id شناسه ابزار.
	 * @return array|null
	 */
	function pixva_tool_get( $id ) {
		$id       = (int) $id;
		$registry = pixva_tools_registry();
		return isset( $registry[ $id ] ) ? $registry[ $id ] : null;
	}
}

if ( ! function_exists( 'pixva_render_tool_by_id' ) ) {
	/**
	 * رندر یک ابزار بر اساس شناسه (۱ تا ۶۰).
	 *
	 * @param int    $id      شناسه ابزار.
	 * @param string $context حالت خروجی: section (پیش‌فرض) یا inline (المنتور/شورت‌کد).
	 * @return string HTML خروجی.
	 */
	function pixva_render_tool_by_id( $id, $context = 'section' ) {
		$id   = (int) $id;
		$tool = pixva_tool_get( $id );
		if ( ! $tool ) {
			return '';
		}
		if ( ! in_array( $context, array( 'section', 'inline' ), true ) ) {
			$context = 'section';
		}

		// عنوان/توضیح دلخواه از پنل المنتور (فیلتر ثبت‌شده توسط ویجت).
		$override = apply_filters( 'pixva_tool_override_' . $id, null );
		if ( is_array( $override ) ) {
			if ( isset( $override['title'] ) && '' !== $override['title'] ) {
				$tool['title'] = $override['title'];
			}
			if ( isset( $override['summary'] ) && '' !== $override['summary'] ) {
				$tool['summary'] = $override['summary'];
			}
		}

		// کلید فعال‌سازی ابزار از مرکز کنترل پیشخوان (ماتریس ۶۰ ابزار).
		if ( ! pixva_tool_is_enabled( $id ) ) {
			return '';
		}

		$renderer = 'pixva_tool_render_' . $tool['renderer'];
		ob_start();

		pixva_tool_shell_open( $id, $tool, $context );

		if ( function_exists( $renderer ) ) {
			call_user_func( $renderer, $id, $tool );
		} else {
			pixva_tool_render_unavailable( $id, $tool );
		}

		pixva_tool_shell_close( $id, $tool, $context );

		return (string) ob_get_clean();
	}
}

if ( ! function_exists( 'pixva_tool_shell_open' ) ) {
	/**
	 * باز کردن پوسته استاندارد ابزار.
	 *
	 * @param int    $id      شناسه ابزار.
	 * @param array  $tool    رکورد رجیستری.
	 * @param string $context section|inline.
	 * @return void
	 */
	function pixva_tool_shell_open( $id, $tool, $context = 'section' ) {
		$hub = pixva_hub_by_tool( $id );
		if ( 'section' === $context ) {
			echo '<section class="pixva-section pixva-tool-section" id="pixva-tool-' . esc_attr( $id ) . '"><div class="pixva-container">';
			echo '<div class="pixva-section-head pixva-reveal">';
			echo '<span class="pixva-badge pixva-badge--brand">' . esc_html( sprintf( __( 'ابزار %1$s از ۶۰ · %2$s', 'pixva' ), pixva_fa_num( (string) $id ), $hub ? $hub['title'] : '' ) ) . '</span>';
			echo '<h2>' . esc_html( $tool['title'] ) . '</h2>';
			echo '<p>' . esc_html( $tool['summary'] ) . '</p>';
			echo '</div>';
		}

		echo '<div class="pixva-tool-shell pixva-reveal" data-pixva-tool="' . esc_attr( $id ) . '" data-tool-kind="' . esc_attr( $tool['renderer'] ) . '">';
		if ( 'inline' === $context ) {
			echo '<div class="pixva-tool-shell__head">';
			echo '<div><span class="pixva-badge pixva-badge--accent">' . esc_html( sprintf( __( 'ابزار %s', 'pixva' ), pixva_fa_num( (string) $id ) ) ) . '</span>';
			echo '<h3>' . esc_html( $tool['title'] ) . '</h3>';
			echo '<p>' . esc_html( $tool['summary'] ) . '</p></div></div>';
		}
		echo '<div class="pixva-tool-body" data-tool-body>';
	}
}

if ( ! function_exists( 'pixva_tool_shell_close' ) ) {
	/**
	 * بستن پوسته استاندارد ابزار.
	 *
	 * @param int    $id      شناسه ابزار.
	 * @param array  $tool    رکورد رجیستری.
	 * @param string $context section|inline.
	 * @return void
	 */
	function pixva_tool_shell_close( $id, $tool, $context = 'section' ) {
		echo '</div>'; // .pixva-tool-body
		echo '<p class="pixva-tool-output" data-tool-output hidden></p>';
		echo '</div>'; // .pixva-tool-shell
		if ( 'section' === $context ) {
			echo '</div></section>';
		}
	}
}

if ( ! function_exists( 'pixva_tool_render_unavailable' ) ) {
	/**
	 * پیام نبود رندرر (هرگز داده تصادفی تولید نمی‌کند).
	 *
	 * @param int   $id   شناسه ابزار.
	 * @param array $tool رکورد رجیستری.
	 * @return void
	 */
	function pixva_tool_render_unavailable( $id, $tool ) {
		echo '<p class="pixva-notice pixva-notice--info">' . esc_html( sprintf( __( 'رندرر ابزار %s در این نسخه ثبت نشده است؛ از مدیر سایت بخواهید پوسته را به‌روزرسانی کند.', 'pixva' ), $id ) ) . '</p>';
	}
}

if ( ! function_exists( 'pixva_tool_field' ) ) {
	/**
	 * ساخت یک فیلد استاندارد برای ابزارها.
	 *
	 * @param string $id      شناسه فیلد.
	 * @param string $label   برچسب.
	 * @param string $type    نوع ورودی.
	 * @param array  $args    گزینه‌ها (placeholder, value, options, min, max, step, accept, name).
	 * @return void
	 */
	function pixva_tool_field( $id, $label, $type = 'text', $args = array() ) {
		$args = wp_parse_args(
			$args,
			array(
				'name'        => $id,
				'placeholder' => '',
				'value'       => '',
				'options'     => array(),
				'min'         => '',
				'max'         => '',
				'step'        => '',
				'accept'      => '',
				'required'    => false,
				'inputmode'   => '',
			)
		);
		echo '<div class="pixva-field">';
		echo '<label for="' . esc_attr( $id ) . '">' . esc_html( $label ) . '</label>';
		$attrs = ' id="' . esc_attr( $id ) . '" name="' . esc_attr( $args['name'] ) . '" class="pixva-input"';
		if ( '' !== $args['placeholder'] ) {
			$attrs .= ' placeholder="' . esc_attr( $args['placeholder'] ) . '"';
		}
		if ( '' !== $args['min'] ) {
			$attrs .= ' min="' . esc_attr( $args['min'] ) . '"';
		}
		if ( '' !== $args['max'] ) {
			$attrs .= ' max="' . esc_attr( $args['max'] ) . '"';
		}
		if ( '' !== $args['step'] ) {
			$attrs .= ' step="' . esc_attr( $args['step'] ) . '"';
		}
		if ( '' !== $args['accept'] ) {
			$attrs .= ' accept="' . esc_attr( $args['accept'] ) . '"';
		}
		if ( '' !== $args['inputmode'] ) {
			$attrs .= ' inputmode="' . esc_attr( $args['inputmode'] ) . '"';
		}
		if ( $args['required'] ) {
			$attrs .= ' required';
		}

		if ( 'select' === $type ) {
			echo '<select' . $attrs . '>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			foreach ( $args['options'] as $value => $text ) {
				echo '<option value="' . esc_attr( $value ) . '">' . esc_html( $text ) . '</option>';
			}
			echo '</select>';
		} elseif ( 'textarea' === $type ) {
			echo '<textarea' . $attrs . ' rows="3">' . esc_textarea( $args['value'] ) . '</textarea>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		} else {
			echo '<input type="' . esc_attr( $type ) . '"' . $attrs . ' value="' . esc_attr( $args['value'] ) . '">'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
		echo '</div>';
	}
}

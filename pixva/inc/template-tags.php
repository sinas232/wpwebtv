<?php
/**
 * توابع نمایشی و کاتالوگ‌های قالب پیکسوا
 *
 * کاتالوگ برند، مشکل، تکنولوژی و کدهای خطا منبع مشترک قالب و ایجکس است.
 *
 * @package Pixva
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// کاتالوگ‌ها.

/**
 * برندهای پشتیبانی‌شده محاسبه‌گر (۲۴ برند). کلیدها با موتور قیمت یکی است.
 *
 * @return array<string, array{fa:string,en:string}>
 */
function pixva_brand_catalog() {
	$brands = array(
		'samsung'   => array(
			'fa' => 'سامسونگ',
			'en' => 'Samsung',
		),
		'lg'        => array(
			'fa' => 'ال‌جی',
			'en' => 'LG',
		),
		'sony'      => array(
			'fa' => 'سونی',
			'en' => 'Sony',
		),
		'snowa'     => array(
			'fa' => 'اسنوا',
			'en' => 'Snowa',
		),
		'xvision'   => array(
			'fa' => 'ایکس‌ویژن',
			'en' => 'X.Vision',
		),
		'gplus'     => array(
			'fa' => 'جی‌پلاس',
			'en' => 'Gplus',
		),
		'tcl'       => array(
			'fa' => 'تی‌سی‌ال',
			'en' => 'TCL',
		),
		'hisense'   => array(
			'fa' => 'هایسنس',
			'en' => 'Hisense',
		),
		'xiaomi'    => array(
			'fa' => 'شیائومی',
			'en' => 'Xiaomi',
		),
		'panasonic' => array(
			'fa' => 'پاناسونیک',
			'en' => 'Panasonic',
		),
		'philips'   => array(
			'fa' => 'فیلیپس',
			'en' => 'Philips',
		),
		'sharp'     => array(
			'fa' => 'شارپ',
			'en' => 'Sharp',
		),
		'toshiba'   => array(
			'fa' => 'توشیبا',
			'en' => 'Toshiba',
		),
		'haier'     => array(
			'fa' => 'های‌یر',
			'en' => 'Haier',
		),
		'daewoo'    => array(
			'fa' => 'دوو',
			'en' => 'Daewoo',
		),
		'jvc'       => array(
			'fa' => 'جی‌وی‌سی',
			'en' => 'JVC',
		),
		'hitachi'   => array(
			'fa' => 'هیتاچی',
			'en' => 'Hitachi',
		),
		'sanyo'     => array(
			'fa' => 'سانیو',
			'en' => 'Sanyo',
		),
		'grundig'   => array(
			'fa' => 'گروندیگ',
			'en' => 'Grundig',
		),
		'vestel'    => array(
			'fa' => 'وستل',
			'en' => 'Vestel',
		),
		'skyworth'  => array(
			'fa' => 'اسکای‌ورث',
			'en' => 'Skyworth',
		),
		'konka'     => array(
			'fa' => 'کونکا',
			'en' => 'Konka',
		),
		'changhong' => array(
			'fa' => 'چانگ‌هونگ',
			'en' => 'Changhong',
		),
		'marshal'   => array(
			'fa' => 'مارشال',
			'en' => 'Marshal',
		),
	);
	return apply_filters( 'pixva_brand_catalog', $brands );
}

/**
 * انواع خرابی محاسبه‌گر.
 *
 * @return array<string, string>
 */
function pixva_problem_catalog() {
	$problems = array(
		'no_picture'    => __( 'بی‌تصویری (صدا دارد)', 'pixva' ),
		'lines'         => __( 'خطوط عمودی یا افقی', 'pixva' ),
		'no_power'      => __( 'خاموشی کامل', 'pixva' ),
		'no_sound'      => __( 'قطع صدا', 'pixva' ),
		'blink'         => __( 'چشمک زدن چراغ پاور', 'pixva' ),
		'water'         => __( 'آب‌خوردگی پنل', 'pixva' ),
		'backlight'     => __( 'تعویض بک‌لایت', 'pixva' ),
		'panel'         => __( 'تعمیر پنل با بندینگ', 'pixva' ),
		'panel_replace' => __( 'تعویض کامل پنل', 'pixva' ),
		'mainboard'     => __( 'تعمیر برد اصلی', 'pixva' ),
		'powerboard'    => __( 'تعمیر برد پاور', 'pixva' ),
	);
	return apply_filters( 'pixva_problem_catalog', $problems );
}

/**
 * تکنولوژی صفحه.
 *
 * @return array<string, string>
 */
function pixva_tech_catalog() {
	return array(
		'led'      => 'LED',
		'qled'     => 'QLED',
		'oled'     => 'OLED',
		'plasma'   => 'Plasma',
		'microled' => 'MicroLED',
	);
}

/**
 * سایزهای رایج اینچ (منبع: جدول ضریب سایز موتور قیمت).
 *
 * @return array<string, string>
 */
function pixva_size_catalog() {
	$sizes = array();
	foreach ( array_keys( pixva_pricing_size_factors() ) as $size ) {
		$sizes[ $size ] = sprintf(
			/* translators: %s: سایز اینچ */
			__( '%s اینچ', 'pixva' ),
			pixva_fa_num( $size )
		);
	}
	return $sizes;
}

/**
 * برچسب‌های تخت برای پاسخ ایجکس.
 *
 * @return array<string, array<string, string>>
 */
function pixva_calculator_labels() {
	$brands = array();
	foreach ( pixva_brand_catalog() as $key => $brand ) {
		$brands[ $key ] = $brand['fa'];
	}
	return array(
		'brand'   => $brands,
		'problem' => pixva_problem_catalog(),
		'tech'    => pixva_tech_catalog(),
		'size'    => pixva_size_catalog(),
	);
}

/**
 * خدمات پیش‌فرض وقتی هنوز خدمتی در پیشخوان ثبت نشده است.
 *
 * @return array<int, array<string, string>>
 */
function pixva_service_fallbacks() {
	return array(
		array(
			'key'   => 'backlight',
			'title' => __( 'تعویض بک‌لایت', 'pixva' ),
			'text'  => __( 'رفع تاریکی موضعی، هاله و خاموشی نور پس‌زمینه در پنل‌های LED و QLED با نوار نور اصلی.', 'pixva' ),
			'icon'  => 'sun',
		),
		array(
			'key'   => 'panel',
			'title' => __( 'تعمیر پنل با دستگاه بندینگ', 'pixva' ),
			'text'  => __( 'ترمیم خطوط عمودی و افقی با بندینگ COF، بدون تعویض کامل پنل تا جایی که شیشه سالم باشد.', 'pixva' ),
			'icon'  => 'panel',
		),
		array(
			'key'   => 'mainboard',
			'title' => __( 'تعمیر برد اصلی', 'pixva' ),
			'text'  => __( 'عیب‌یابی مین‌برد، HDMI، وای‌فای و بخش پردازش تصویر با اسیلوسکوپ و پروگرامر.', 'pixva' ),
			'icon'  => 'cpu',
		),
		array(
			'key'   => 'powerboard',
			'title' => __( 'تعمیر برد پاور', 'pixva' ),
			'text'  => __( 'رفع چشمک چراغ، خاموشی کامل و صدای جرقه برد تغذیه با قطعه هم‌تراز و تست بار.', 'pixva' ),
			'icon'  => 'bolt',
		),
		array(
			'key'   => 'water',
			'title' => __( 'رفع آب‌خوردگی', 'pixva' ),
			'text'  => __( 'شست‌وشوی برد، خشک‌کردن کنترل‌شده و تعویض مسیرهای اکسیدشده پس از نفوذ مایع.', 'pixva' ),
			'icon'  => 'drop',
		),
		array(
			'key'   => 'no_sound',
			'title' => __( 'تعمیر صدا و اسپیکر', 'pixva' ),
			'text'  => __( 'تشخیص قطع صدا از برد صدا، فلت اسپیکر یا تنظیمات پنل و تعمیر همان بخش.', 'pixva' ),
			'icon'  => 'sound',
		),
	);
}

/**
 * پرسش‌های متداول پیش‌فرض برگه FAQ.
 *
 * @return array<int, array{q:string,a:string}>
 */
function pixva_default_faqs() {
	$faqs = array(
		array(
			'q' => __( 'هزینه تعمیر قبل از باز کردن دستگاه قطعی است؟', 'pixva' ),
			'a' => __( 'خیر. محاسبه‌گر پیکسوا یک بازه واقعی کارگاهی می‌دهد. مبلغ نهایی بعد از عیب‌یابی و تأیید شما ثبت می‌شود و بدون هماهنگی قطعه‌ای تعویض نمی‌گردد.', 'pixva' ),
		),
		array(
			'q' => __( 'گارانتی تعمیرات چقدر است؟', 'pixva' ),
			'a' => __( 'تعمیرات برد و بک‌لایت ۱۸۰ روز ضمانت کتبی دارد. تعمیر پنل، به‌دلیل ماهیت شیشه، ضمانت عملکرد خط تعمیرشده را دارد و سوختگی پیکسلی جدید را پوشش نمی‌دهد.', 'pixva' ),
		),
		array(
			'q' => __( 'آیا دستگاه از منزل جمع می‌شود؟', 'pixva' ),
			'a' => __( 'در تهران ارسال و دریافت با هماهنگی پشتیبانی انجام می‌شود. برای شهرستان، دستگاه را با بسته‌بندی یونولیت به کارگاه بفرستید یا از باربری طرف قرارداد استفاده کنید.', 'pixva' ),
		),
		array(
			'q' => __( 'آب‌خوردگی همیشه قابل تعمیر است؟', 'pixva' ),
			'a' => __( 'اگر مایع فقط به فلت یا برد رسیده باشد معمولاً بله. اگر به لایه سلول پنل نفوذ کرده باشد، تعمیر اقتصادی نیست و قبل از هر هزینه‌ای به شما گفته می‌شود.', 'pixva' ),
		),
		array(
			'q' => __( 'خطوط عمودی یعنی پنل سوخته است؟', 'pixva' ),
			'a' => __( 'نه همیشه. بخش زیادی از خطوط از قطع فلت COF یا آی‌سی تایمینگ است و با دستگاه بندینگ ترمیم می‌شود. شکستگی شیشه قابل بندینگ نیست.', 'pixva' ),
		),
		array(
			'q' => __( 'چشمک زدن چراغ پاور را خودمان می‌توانیم رفع کنیم؟', 'pixva' ),
			'a' => __( 'یک‌بار برق را قطع کنید، کابل HDMI را جدا کنید و دو دقیقه صبر کنید. اگر چشمک ماند، الگو را در پایگاه کدهای خطا تطبیق دهید و دستگاه را باز نکنید؛ برد پاور ولتاژ خطرناک دارد.', 'pixva' ),
		),
		array(
			'q' => __( 'قطعه تعویضی اصلی است؟', 'pixva' ),
			'a' => __( 'برای بک‌لایت و آی‌سی‌های رایج از قطعه درجه‌یک هم‌مشخصات استفاده می‌شود. اگر فقط نمونه استوک موجود باشد، قبل از نصب به شما اعلام و تفاوت قیمت شفاف می‌شود.', 'pixva' ),
		),
		array(
			'q' => __( 'تعمیر OLED با LED فرق دارد؟', 'pixva' ),
			'a' => __( 'بله. OLED بک‌لایت ندارد و سوختگی پیکسل یا خطاهای پنل آن مسیر جداگانه‌ای دارد. به همین دلیل در محاسبه‌گر ضریب تکنولوژی جدا اعمال می‌شود.', 'pixva' ),
		),
		array(
			'q' => __( 'چطور وضعیت دستگاه را پیگیری کنم؟', 'pixva' ),
			'a' => __( 'بعد از ثبت نوبت یک کد مانند PXV-2509-1234 دریافت می‌کنید. در برگه پیگیری، کد یا شماره همراه را وارد کنید تا تایم‌لاین شش‌مرحله‌ای را ببینید.', 'pixva' ),
		),
		array(
			'q' => __( 'ساعت کاری کارگاه چیست؟', 'pixva' ),
			'a' => __( 'شنبه تا پنجشنبه ۹ تا ۲۰ و جمعه‌ها ۱۰ تا ۱۶. پذیرش حضوری تا یک ساعت قبل از پایان وقت انجام می‌شود.', 'pixva' ),
		),
	);
	return apply_filters( 'pixva_default_faqs', $faqs );
}

/**
 * روایت‌های نمونه کارگاه. اسکیما Review عمداً ساخته نمی‌شود تا نظر ساختگی به گوگل اعلام نشود.
 *
 * @return array<int, array<string, string>>
 */
function pixva_testimonials() {
	$items = array(
		array(
			'quote' => __( 'خطوط عمودی ۵۵ اینچ سامسونگ را بدون تعویض پنل بستند. عصر همان روز تصویر یکدست شد و برگه گارانتی هم دادند.', 'pixva' ),
			'name'  => __( 'مهدی ر.', 'pixva' ),
			'role'  => __( 'تعمیر پنل، تهران', 'pixva' ),
		),
		array(
			'quote' => __( 'چراغ پاور ال‌جی سه بار چشمک می‌زد. برد تغذیه تعمیر شد، نه تعویض کامل. هزینه از برآورد سایت کمتر درآمد.', 'pixva' ),
			'name'  => __( 'سارا ک.', 'pixva' ),
			'role'  => __( 'برد پاور، کرج', 'pixva' ),
		),
		array(
			'quote' => __( 'پیگیری آنلاین واقعاً کار می‌کرد. از مرحله تأمین قطعه تا آماده تحویل را با همان کد پیامکی دیدم.', 'pixva' ),
			'name'  => __( 'حمید ن.', 'pixva' ),
			'role'  => __( 'تعویض بک‌لایت، تهران', 'pixva' ),
		),
	);
	return apply_filters( 'pixva_testimonials', $items );
}

/**
 * پایگاه کدهای خطا و چشمک چراغ پاور.
 *
 * این داده‌ها راهنمای اولیه کارگاه است، نه سرویس‌منوال رسمی سازنده.
 *
 * @return array<int, array<string, mixed>>
 */
function pixva_error_code_catalog() {
	$rows = array(
		array( 'sony', 2, '2 چشمک', __( 'خطای محافظت مین‌برد', 'pixva' ), __( 'تلویزیون روشن نمی‌ماند و چراغ دو بار تکرار می‌شود.', 'pixva' ), __( 'اغلب اتصال کوتاه در مین‌برد یا کابل پنل. برد را جدا و ولتاژهای آماده‌به‌کار را اندازه بگیرید.', 'pixva' ) ),
		array( 'sony', 3, '3 چشمک', __( 'مشکل برد تغذیه', 'pixva' ), __( 'سه چشمک متوالی و مکث؛ تصویر نمی‌آید.', 'pixva' ), __( 'در کارگاه پیکسوا این الگو معمولاً برد پاور یا رگولاتور ولتاژ پنل است. خازن‌های بادکرده و اپتوکوپلر را بررسی کنید.', 'pixva' ) ),
		array( 'sony', 4, '4 چشمک', __( 'خطای بک‌لایت یا اینورتر', 'pixva' ), __( 'صدا هست، تصویر نیست، چهار چشمک.', 'pixva' ), __( 'نوار LED یا درایور نور پس‌زمینه. با چراغ‌قوه روی پنل ببینید تصویر کم‌رنگ هست یا نه.', 'pixva' ) ),
		array( 'sony', 5, '5 چشمک', __( 'خطای T-CON یا پنل', 'pixva' ), __( 'پنج چشمک، گاهی تصویر نصفه.', 'pixva' ), __( 'برد تایمینگ و فلت‌ها. اگر شیشه ضربه دارد، بندینگ اقتصادی نیست.', 'pixva' ) ),
		array( 'sony', 6, '6 چشمک', __( 'اتصال کوتاه نور پس‌زمینه', 'pixva' ), __( 'شش چشمک و قطع محافظتی.', 'pixva' ), __( 'یک رشته LED اتصال کوتاه شده. نوار معیوب باید جدا شود، نه اینکه فیوز پل شود.', 'pixva' ) ),
		array( 'sony', 8, '8 چشمک', __( 'محافظت بخش صدا', 'pixva' ), __( 'تصویر هست، صدا نیست و چراغ هشت بار چشمک می‌زند.', 'pixva' ), __( 'اسپیکر اتصال کوتاه یا آی‌سی صدا. اسپیکر را جدا کنید و دوباره تست بگیرید.', 'pixva' ) ),
		array( 'lg', 2, '2 چشمک', __( 'خطای برد تغذیه ال‌جی', 'pixva' ), __( 'دو چشمک قرمز روی استندبای.', 'pixva' ), __( 'ولتاژ اصلی برد پاور خارج از محدوده است. قبل از تعویض کامل، قطعات حفاظتی را تست کنید.', 'pixva' ) ),
		array( 'lg', 3, '3 چشمک', __( 'خطای مین‌برد', 'pixva' ), __( 'سه چشمک، گاهی لوگو نمی‌آید.', 'pixva' ), __( 'مین‌برد یا حافظه بوت. به‌روزرسانی firmware فقط بعد از پایدار شدن ولتاژ انجام شود.', 'pixva' ) ),
		array( 'lg', 4, '4 چشمک', __( 'خطای برد تایمینگ', 'pixva' ), __( 'چهار چشمک و تصویر بریده‌بریده.', 'pixva' ), __( 'T-CON یا فلت LVDS. جا زدن دوباره فلت گاهی کافی است، اما اکسید بودن مسیر را پنهان نکنید.', 'pixva' ) ),
		array( 'lg', 5, '5 چشمک', __( 'خطای پنل OLED/LED', 'pixva' ), __( 'پنج چشمک پس از لوگوی کوتاه.', 'pixva' ), __( 'در OLED اغلب خطای خود پنل است. در LED ابتدا بک‌لایت و سپس سلول بررسی شود.', 'pixva' ) ),
		array( 'lg', 6, '6 چشمک', __( 'بک‌لایت یا درایور LED', 'pixva' ), __( 'شش چشمک، صفحه سیاه با صدا.', 'pixva' ), __( 'درایور LED روی پاور یا نوار نور. تست با منبع محدودکننده جریان انجام شود.', 'pixva' ) ),
		array( 'samsung', 2, '2 چشمک', __( 'محافظت برد پاور سامسونگ', 'pixva' ), __( 'چراغ پاور دو بار چشمک می‌زند و دستگاه برنمی‌گردد.', 'pixva' ), __( 'اتصال کوتاه خروجی. برد پنل را جدا کنید؛ اگر چشمک قطع شد مشکل از سمت پنل یا بک‌لایت است.', 'pixva' ) ),
		array( 'samsung', 3, '3 چشمک', __( 'خطای مین‌برد سامسونگ', 'pixva' ), __( 'سه چشمک پس از قطع و وصل برق.', 'pixva' ), __( 'مین‌برد، بخش eMMC یا ولتاژ آماده‌به‌کار. یک‌بار کابل One Connect را در مدل‌های فریم جدا رزیت کنید.', 'pixva' ) ),
		array( 'samsung', 4, '4 چشمک', __( 'پنل یا فلت COF', 'pixva' ), __( 'چهار چشمک همراه با خطوط یا تصویر نصفه.', 'pixva' ), __( 'کاندید بندینگ. اگر ضربه فیزیکی روی شیشه است، به مشتری احتمال تعویض پنل را بگویید.', 'pixva' ) ),
		array( 'samsung', 5, '5 چشمک', __( 'بک‌لایت سامسونگ', 'pixva' ), __( 'پنج چشمک و تاریکی صفحه.', 'pixva' ), __( 'نوار LED یا درایور. در QLED مسیر نور متفاوت است و ضریب قیمت بالاتر دارد.', 'pixva' ) ),
		array( 'samsung', 6, '6 چشمک', __( 'اتصال کوتاه عمومی', 'pixva' ), __( 'شش چشمک سریع و خاموشی.', 'pixva' ), __( 'فیوز را پل نکنید. منبع اتصال کوتاه را با اهم‌متر پیدا کنید.', 'pixva' ) ),
		array( 'samsung', 0, 'CHECK SIGNAL', __( 'نبود سیگنال ورودی', 'pixva' ), __( 'عبارت CHECK SIGNAL یا No Signal روی صفحه.', 'pixva' ), __( 'اول کابل و منبع HDMI. اگر منوی داخلی هم نمی‌آید، مشکل از مین‌برد است نه از رسیور.', 'pixva' ) ),
		array( 'tcl', 2, '2 چشمک', __( 'برد پاور تی‌سی‌ال', 'pixva' ), __( 'دو چشمک و ماندن در استندبای.', 'pixva' ), __( 'خازن‌های اولیه و آی‌سی PWM. این بردها اغلب قابل تعمیرند و نیاز به تعویض کامل ندارند.', 'pixva' ) ),
		array( 'tcl', 3, '3 چشمک', __( 'مین‌برد اندرویدی', 'pixva' ), __( 'سه چشمک، گیر کردن روی لوگوی اندروید.', 'pixva' ), __( 'بوت‌لودر یا حافظه. ریست کارخانه فقط وقتی ولتاژ پایدار است جواب می‌دهد.', 'pixva' ) ),
		array( 'tcl', 4, '4 چشمک', __( 'بک‌لایت تی‌سی‌ال', 'pixva' ), __( 'چهار چشمک، صفحه سیاه.', 'pixva' ), __( 'رشته LED سوخته. تعداد چشمک را با ریموت خاموش و فقط از روی پنل بشمارید.', 'pixva' ) ),
		array( 'tcl', 5, '5 چشمک', __( 'خطای پنل', 'pixva' ), __( 'پنج چشمک و خطوط رنگی.', 'pixva' ), __( 'فلت یا T-CON. برای تصمیم بندینگ، عکس نزدیک از خطوط لازم است.', 'pixva' ) ),
		array( 'hisense', 2, '2 چشمک', __( 'محافظت تغذیه هایسنس', 'pixva' ), __( 'دو چشمک کوتاه.', 'pixva' ), __( 'برد پاور یا بار غیرعادی بک‌لایت. خروجی‌ها را بی‌بار تست کنید.', 'pixva' ) ),
		array( 'hisense', 3, '3 چشمک', __( 'مین‌برد هایسنس', 'pixva' ), __( 'سه چشمک و ریست پیاپی.', 'pixva' ), __( 'بخش وای‌فای یا حافظه. اگر از مسیر USB بوت نمی‌شود، مین‌برد تعویض یا تعمیر پایه‌ای می‌خواهد.', 'pixva' ) ),
		array( 'hisense', 4, '4 چشمک', __( 'T-CON هایسنس', 'pixva' ), __( 'چهار چشمک، تصویر شطرنجی.', 'pixva' ), __( 'برد تایمینگ. تعویض برد ارزان‌تر از پنل است و باید اول تست شود.', 'pixva' ) ),
		array( 'hisense', 6, '6 چشمک', __( 'حرارت یا فن', 'pixva' ), __( 'شش چشمک پس از چند دقیقه کار.', 'pixva' ), __( 'سنسور دما یا تهویه مسدود. دستگاه را در باکس بسته روشن نگذارید.', 'pixva' ) ),
		array( 'snowa', 2, '2 چشمک', __( 'برد پاور اسنوا', 'pixva' ), __( 'دو چشمک و بوی خازن.', 'pixva' ), __( 'خازن اولیه بادکرده شایع است. قطعه هم‌ظرفیت و هم‌ولتاژ جایگزین شود.', 'pixva' ) ),
		array( 'snowa', 3, '3 چشمک', __( 'مین‌برد اسنوا', 'pixva' ), __( 'سه چشمک، منو نمی‌آید.', 'pixva' ), __( 'مین‌برد یا فلش. پروگرام فقط با دامپ سالم همان شاسی انجام شود.', 'pixva' ) ),
		array( 'snowa', 4, '4 چشمک', __( 'بک‌لایت اسنوا', 'pixva' ), __( 'چهار چشمک، نور زمینه‌ی تکه‌تکه.', 'pixva' ), __( 'نوار LED. در سایزهای ۵۵ به بالا معمولاً بیش از یک نوار درگیر است.', 'pixva' ) ),
		array( 'snowa', 5, '5 چشمک', __( 'فلت پنل', 'pixva' ), __( 'پنج چشمک و خط عمودی ثابت.', 'pixva' ), __( 'کاندید بندینگ COF. ضربه و نم را قبل از پذیرش قیمت اعلام کنید.', 'pixva' ) ),
		array( 'xvision', 2, '2 چشمک', __( 'تغذیه ایکس‌ویژن', 'pixva' ), __( 'دو چشمک پایدار.', 'pixva' ), __( 'برد پاور. مسیر مشابه شاسی‌های ایرانی هم‌نسل است و قطعه معمولاً موجود است.', 'pixva' ) ),
		array( 'xvision', 3, '3 چشمک', __( 'مین‌برد ایکس‌ویژن', 'pixva' ), __( 'سه چشمک پس از نوسان برق.', 'pixva' ), __( 'محافظ ولتاژ نذاشتن شایع‌ترین علت سوختن این برد است. ورودی برق کارگاه باید پایدار باشد.', 'pixva' ) ),
		array( 'xvision', 4, '4 چشمک', __( 'نور پس‌زمینه', 'pixva' ), __( 'چهار چشمک، صدا بدون تصویر.', 'pixva' ), __( 'تست چراغ‌قوه را انجام دهید. اگر سایه تصویر دیدید، بک‌لایت است نه پنل.', 'pixva' ) ),
		array( 'xvision', 5, '5 چشمک', __( 'خط پنل', 'pixva' ), __( 'پنج چشمک و نوار رنگی کناره.', 'pixva' ), __( 'فلت کنار پنل. حرارت موضعی خانگی آسیب را بیشتر می‌کند؛ دستگاه را نبازید.', 'pixva' ) ),
		array( 'gplus', 2, '2 چشمک', __( 'برد پاور جی‌پلاس', 'pixva' ), __( 'دو چشمک و صدای تیک رله.', 'pixva' ), __( 'رله یا خازن ثانویه. اگر رله می‌چسبد، برد را روی میز بدون قاب تست نکنید.', 'pixva' ) ),
		array( 'gplus', 3, '3 چشمک', __( 'مین‌برد جی‌پلاس', 'pixva' ), __( 'سه چشمک، گیرکردن روی لوگو.', 'pixva' ), __( 'firmware یا eMMC. ریست با کلید پنل را یک‌بار امتحان کنید، بعد برد را باز کنید.', 'pixva' ) ),
		array( 'gplus', 4, '4 چشمک', __( 'بک‌لایت جی‌پلاس', 'pixva' ), __( 'چهار چشمک آهسته.', 'pixva' ), __( 'درایور LED روی پاور یا نوار. تعویض نوار باید با ولتاژ و جریان هم‌خوان انجام شود.', 'pixva' ) ),
		array( 'gplus', 5, '5 چشمک', __( 'خطوط پنل', 'pixva' ), __( 'پنج چشمک همراه با خط افقی پهن.', 'pixva' ), __( 'احتمال بندینگ یا T-CON. عکس تمام‌صفحه از خطوط برای برآورد دقیق لازم است.', 'pixva' ) ),
		array( 'lg', 0, 'OLED Care', __( 'نگهداری پنل OLED', 'pixva' ), __( 'پیام OLED Care یا Pixel Refresher وسط کار ظاهر می‌شود.', 'pixva' ), __( 'این خطا نیست؛ چرخه جبران پیکسل است. اگر هر بار گیر کرد، مین‌برد و دمای محیط را بررسی کنید.', 'pixva' ) ),
		array( 'sony', 0, '2 blink standby', __( 'استندبای غیرعادی', 'pixva' ), __( 'چراغ استندبای کند چشمک می‌زند ولی الگوهای سرویس نیست.', 'pixva' ), __( 'ریموت یا برد IR. باتری ریموت و نور محیط را حذف کنید، بعد برد گیرنده را تست کنید.', 'pixva' ) ),
	);

	$catalog = array();
	foreach ( $rows as $row ) {
		$catalog[] = array(
			'brand'   => $row[0],
			'blinks'  => (int) $row[1],
			'code'    => $row[2],
			'title'   => $row[3],
			'symptom' => $row[4],
			'action'  => $row[5],
		);
	}
	return apply_filters( 'pixva_error_code_catalog', $catalog );
}

// آیکون‌های ایستا.

/**
 * مجوزهای kses برای آیکون SVG ایستا.
 *
 * @param string $svg نشانه‌گذاری.
 * @return string
 */
function pixva_kses_svg( $svg ) {
	return wp_kses(
		$svg,
		array(
			'span'     => array(
				'class'       => true,
				'aria-hidden' => true,
			),
			'svg'      => array(
				'viewbox'         => true,
				'viewBox'         => true,
				'fill'            => true,
				'stroke'          => true,
				'stroke-width'    => true,
				'stroke-linecap'  => true,
				'stroke-linejoin' => true,
				'aria-hidden'     => true,
				'xmlns'           => true,
			),
			'path'     => array(
				'd'               => true,
				'fill'            => true,
				'stroke'          => true,
				'stroke-width'    => true,
				'stroke-linecap'  => true,
				'stroke-linejoin' => true,
			),
			'circle'   => array(
				'cx'           => true,
				'cy'           => true,
				'r'            => true,
				'fill'         => true,
				'stroke'       => true,
				'stroke-width' => true,
			),
			'rect'     => array(
				'x'      => true,
				'y'      => true,
				'width'  => true,
				'height' => true,
				'rx'     => true,
				'fill'   => true,
			),
			'polyline' => array(
				'points'          => true,
				'fill'            => true,
				'stroke'          => true,
				'stroke-width'    => true,
				'stroke-linecap'  => true,
				'stroke-linejoin' => true,
			),
			'line'     => array(
				'x1'             => true,
				'y1'             => true,
				'x2'             => true,
				'y2'             => true,
				'stroke'         => true,
				'stroke-width'   => true,
				'stroke-linecap' => true,
			),
		)
	);
}

/**
 * آیکون خطی ۲۴ پیکسلی.
 *
 * @param string $name نام آیکون.
 * @return string
 */
function pixva_icon( $name ) {
	$common = 'viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"';
	$paths  = array(
		'phone'    => '<path d="M7 3.5h3.2l1.2 3-2 1.2a12.5 12.5 0 0 0 6 6l1.2-2 3 1.2V16a2 2 0 0 1-2.2 2A16.5 16.5 0 0 1 5 7.7 2 2 0 0 1 7 3.5z"/>',
		'whatsapp' => '<path d="M5 19l1.2-3.4A8 8 0 1 1 8.4 18L5 19z"/><path d="M9 10c.2 2 2.2 3.6 4 4"/>',
		'menu'     => '<path d="M4 7h16M4 12h16M4 17h12"/>',
		'close'    => '<path d="M6 6l12 12M18 6L6 18"/>',
		'search'   => '<circle cx="11" cy="11" r="6"/><path d="M16 16l4 4"/>',
		'clock'    => '<circle cx="12" cy="12" r="8"/><path d="M12 8v5l3 2"/>',
		'shield'   => '<path d="M12 3l7 3v6c0 4.5-3 7-7 9-4-2-7-4.5-7-9V6l7-3z"/>',
		'bolt'     => '<path d="M13 2L5 14h7l-1 8 8-12h-7l1-8z"/>',
		'pin'      => '<path d="M12 21s6-5.2 6-10a6 6 0 1 0-12 0c0 4.8 6 10 6 10z"/><circle cx="12" cy="11" r="2"/>',
		'check'    => '<path d="M5 12.5l4.2 4.2L19 7"/>',
		'arrow'    => '<path d="M15 6l-6 6 6 6"/>',
		'chevron'  => '<path d="M6 9l6 6 6-6"/>',
		'tool'     => '<path d="M14 7a4 4 0 0 0-5.7 5.3L4 16.6 7.4 20l4.3-4.3A4 4 0 0 0 17 10l-3 1-1-1 1-3z"/>',
		'sun'      => '<circle cx="12" cy="12" r="3.2"/><path d="M12 3v2M12 19v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M3 12h2M19 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/>',
		'panel'    => '<rect x="3" y="5" width="18" height="12" rx="2"/><path d="M8 21h8M12 17v4"/>',
		'cpu'      => '<rect x="6" y="6" width="12" height="12" rx="2"/><path d="M9 3v3M12 3v3M15 3v3M9 18v3M12 18v3M15 18v3M3 9h3M3 12h3M3 15h3M18 9h3M18 12h3M18 15h3"/>',
		'drop'     => '<path d="M12 3s6 7 6 11a6 6 0 0 1-12 0c0-4 6-11 6-11z"/>',
		'sound'    => '<path d="M4 10h3l4-3v10l-4-3H4zM16 9a4 4 0 0 1 0 6"/>',
		'star'     => '<path d="M12 3.5l2.2 4.6 5 .7-3.6 3.5.9 5.1L12 15.8 7.5 17.4l.9-5.1L4.8 8.8l5-.7z"/>',
		'truck'    => '<path d="M3 7h11v8H3zM14 10h4l3 3v2h-7z"/><circle cx="7" cy="17.5" r="1.5"/><circle cx="17" cy="17.5" r="1.5"/>',
		'cert'     => '<circle cx="12" cy="10" r="5"/><path d="M9 14.5L8 20l4-2 4 2-1-5.5"/>',
		'user'     => '<circle cx="12" cy="8" r="3"/><path d="M5 19c1.4-3 3.8-4.5 7-4.5S17.6 16 19 19"/>',
		'book'     => '<path d="M5 5.5A3.5 3.5 0 0 1 8.5 4H20v15H8.5A3.5 3.5 0 0 0 5 22.5z"/><path d="M5 5.5V22"/>',
		'ai'       => '<path d="M12 3.5l1.6 3.9 3.9 1.6-3.9 1.6L12 14.5l-1.6-3.9L6.5 9l3.9-1.6z"/><path d="M18.5 15.5l.8 2 2 .8-2 .8-.8 2-.8-2-2-.8 2-.8z"/><path d="M5.5 14l.6 1.6 1.6.6-1.6.6L5.5 18.4 4.9 16.8 3.3 16.2l1.6-.6z"/>',
		'camera'   => '<path d="M4 8h3l1.5-2h7L17 8h3v11H4z"/><circle cx="12" cy="13" r="3.4"/>',
		'mic'      => '<rect x="9" y="3" width="6" height="11" rx="3"/><path d="M5.5 11.5a6.5 6.5 0 0 0 13 0M12 18v3"/>',
		'layers'   => '<path d="M12 3l9 5-9 5-9-5 9-5z"/><path d="M3 13l9 5 9-5"/><path d="M3 17l9 5 9-5"/>',
		'chart'    => '<path d="M4 20V6M4 20h16"/><path d="M8 20v-6M12.5 20V9M17 20v-4"/>',
		'box'      => '<path d="M3.5 7.5L12 3l8.5 4.5v9L12 21l-8.5-4.5z"/><path d="M3.5 7.5L12 12l8.5-4.5M12 12v9"/>',
		'route'    => '<circle cx="6" cy="6" r="2.4"/><circle cx="18" cy="18" r="2.4"/><path d="M8.4 6H14a3.5 3.5 0 0 1 0 7H9a3.5 3.5 0 0 0 0 7h6.6"/>',
		'calendar' => '<rect x="3.5" y="5.5" width="17" height="15" rx="2.5"/><path d="M8 3.5v4M16 3.5v4M3.5 10.5h17"/>',
		'plug'     => '<path d="M9 3v5M15 3v5"/><path d="M6.5 8h11v3a5.5 5.5 0 0 1-11 0z"/><path d="M12 16.5V21"/>',
		'doc'      => '<path d="M6.5 3.5h7L18 8v12.5H6.5z"/><path d="M13 3.5V8h5"/><path d="M9 12h6M9 15.5h6"/>',
		'calculator' => '<rect x="5" y="3" width="14" height="18" rx="2.5"/><path d="M8 7h8M8 11.5h2M12 11.5h2M16 11.5h0M8 15.5h2M12 15.5h2M16 15.5h0"/>',
	);

	if ( ! isset( $paths[ $name ] ) ) {
		return '';
	}

	return pixva_kses_svg( '<span class="pixva-icon"><svg ' . $common . '>' . $paths[ $name ] . '</svg></span>' );
}

// هدر، فوتر و لینک‌ها.

/**
 * لینک تلفن.
 *
 * @param string $phone شماره.
 * @return string
 */
function pixva_tel_href( $phone ) {
	$clean = preg_replace( '/[^0-9+]/', '', (string) $phone );
	return 'tel:' . $clean;
}

/**
 * شماره پشتیبانی هدر.
 *
 * @return string
 */
function pixva_support_phone() {
	return (string) pixva_option( 'pixva_support_phone', '02191009990' );
}

/**
 * شماره‌های فوتر.
 *
 * @return array<int, string>
 */
function pixva_footer_phones() {
	$raw = (string) pixva_option( 'pixva_footer_phones', '02191009990,09120000000' );
	$out = array();
	foreach ( explode( ',', $raw ) as $phone ) {
		$phone = trim( $phone );
		if ( '' !== $phone ) {
			$out[] = $phone;
		}
	}
	return $out;
}

/**
 * لینک واتساپ.
 *
 * @param string $text متن آماده.
 * @return string
 */
function pixva_whatsapp_url( $text = '' ) {
	$number = preg_replace( '/[^0-9]/', '', (string) pixva_option( 'pixva_whatsapp_number', '989120000000' ) );
	$url    = 'https://wa.me/' . $number;
	if ( '' !== $text ) {
		$url .= '?text=' . rawurlencode( $text );
	}
	return $url;
}

/**
 * شبکه‌های اجتماعی پر شده.
 *
 * @return array<string, string>
 */
function pixva_social_links() {
	$map = array(
		'instagram' => __( 'اینستاگرام', 'pixva' ),
		'telegram'  => __( 'تلگرام', 'pixva' ),
		'whatsapp'  => __( 'واتساپ', 'pixva' ),
		'linkedin'  => __( 'لینکدین', 'pixva' ),
		'youtube'   => __( 'یوتیوب', 'pixva' ),
	);
	$out = array();
	foreach ( $map as $key => $label ) {
		$url = (string) pixva_option( 'pixva_social_' . $key, '' );
		if ( '' !== $url ) {
			$out[ $key ] = array(
				'label' => $label,
				'url'   => $url,
			);
		}
	}
	return $out;
}

/**
 * آدرس برگه بر اساس نامک، با کش درون‌درخواستی.
 *
 * @param string $slug نامک.
 * @return string
 */
function pixva_page_url( $slug ) {
	static $cache = array();
	$slug         = sanitize_title( $slug );
	if ( isset( $cache[ $slug ] ) ) {
		return $cache[ $slug ];
	}
	$page = get_page_by_path( $slug );
	if ( $page instanceof WP_Post ) {
		$cache[ $slug ] = get_permalink( $page );
	} else {
		$cache[ $slug ] = home_url( '/' . $slug . '/' );
	}
	return $cache[ $slug ];
}

/**
 * آدرس مجله.
 *
 * @return string
 */
function pixva_blog_url() {
	$posts_page = (int) get_option( 'page_for_posts' );
	if ( $posts_page ) {
		$link = get_permalink( $posts_page );
		if ( $link ) {
			return $link;
		}
	}
	return pixva_page_url( 'blog' );
}

/**
 * لوگوی سایت. variant برابر light یعنی لوگوی روشن برای هدر تیره.
 *
 * @param string $variant light|dark.
 * @return void
 */
function pixva_the_logo( $variant = 'light' ) {
	$key = 'light' === $variant ? 'pixva_logo_light' : 'pixva_logo_dark';
	$url = (string) pixva_option( $key, '' );
	echo '<span class="pixva-logo pixva-logo--' . esc_attr( $variant ) . '">';
	if ( '' !== $url ) {
		echo '<img class="pixva-logo__img" src="' . esc_url( $url ) . '" alt="' . esc_attr( get_bloginfo( 'name' ) ) . '" width="168" height="48">';
	} elseif ( 'dark' === $variant && has_custom_logo() ) {
		the_custom_logo();
	} else {
		echo '<img class="pixva-logo__mark" src="' . esc_url( PIXVA_URI . '/assets/images/logo-mark.svg' ) . '" alt="" width="42" height="42">';
		echo '<span class="pixva-logo__text"><strong>' . esc_html( get_bloginfo( 'name' ) ) . '</strong><small>' . esc_html__( 'تعمیر تخصصی نمایشگر', 'pixva' ) . '</small></span>';
	}
	echo '</span>';
}

/**
 * منوی جایگزین وقتی منویی به جایگاه وصل نشده است.
 *
 * @return void
 */
/**
 * پیشوند شناسه آیتم منو تا خروجی هدر و کشو تکراری نشود.
 *
 * @param string $id   شناسه.
 * @param object $item آیتم.
 * @param object $args آرگومان منو.
 * @return string
 */
function pixva_nav_item_id( $id, $item, $args ) {
	if ( ! empty( $args->pixva_id_prefix ) ) {
		$base = $id ? $id : 'menu-item-' . (int) $item->ID;
		return $args->pixva_id_prefix . $base;
	}
	return $id;
}
add_filter( 'nav_menu_item_id', 'pixva_nav_item_id', 10, 3 );

/**
 * منوی جایگزین وقتی منویی به جایگاه وصل نشده است.
 *
 * @return void
 */
function pixva_fallback_menu() {
	$items = array(
		home_url( '/' )                             => __( 'خانه', 'pixva' ),
		get_post_type_archive_link( 'tv_services' ) => __( 'خدمات', 'pixva' ),
		get_post_type_archive_link( 'tv_brands' )   => __( 'برندها', 'pixva' ),
		pixva_page_url( 'calculator' )              => __( 'محاسبه هزینه', 'pixva' ),
		pixva_page_url( 'rates' )                   => __( 'نرخ‌نامه', 'pixva' ),
		pixva_page_url( 'tracking' )                => __( 'پیگیری تعمیر', 'pixva' ),
		pixva_page_url( 'error-codes' )             => __( 'کدهای خطا', 'pixva' ),
		pixva_blog_url()                            => __( 'مجله', 'pixva' ),
		pixva_page_url( 'contact' )                 => __( 'تماس', 'pixva' ),
	);
	echo '<nav class="pixva-nav" aria-label="' . esc_attr__( 'منوی اصلی', 'pixva' ) . '"><ul class="pixva-nav__list">';
	foreach ( $items as $url => $label ) {
		if ( ! is_string( $url ) || '' === $url ) {
			continue;
		}
		echo '<li class="menu-item"><a href="' . esc_url( $url ) . '">' . esc_html( $label ) . '</a></li>';
	}
	echo '</ul></nav>';
}

/**
 * نمادهای اعتماد فوتر.
 *
 * @return void
 */
function pixva_trust_badges() {
	$items = array(
		array(
			'url'   => (string) pixva_option( 'pixva_enamad_url', '' ),
			'image' => (string) pixva_option( 'pixva_enamad_image', '' ),
			'label' => __( 'نماد اعتماد الکترونیکی', 'pixva' ),
		),
		array(
			'url'   => (string) pixva_option( 'pixva_samandehi_url', '' ),
			'image' => (string) pixva_option( 'pixva_samandehi_image', '' ),
			'label' => __( 'نشان ساماندهی', 'pixva' ),
		),
	);
	$any   = false;
	foreach ( $items as $item ) {
		if ( '' === $item['url'] && '' === $item['image'] ) {
			continue;
		}
		$any = true;
		echo '<a class="pixva-trust" href="' . esc_url( $item['url'] ? $item['url'] : '#' ) . '" target="_blank" rel="noopener noreferrer">';
		if ( '' !== $item['image'] ) {
			echo '<img src="' . esc_url( $item['image'] ) . '" alt="' . esc_attr( $item['label'] ) . '" width="72" height="80" loading="lazy">';
		} else {
			echo esc_html( $item['label'] );
		}
		echo '</a>';
	}
	if ( ! $any ) {
		echo '<p class="pixva-muted pixva-trust-note">' . esc_html__( 'جای نماد اعتماد و ساماندهی از سفارشی‌ساز قابل تنظیم است.', 'pixva' ) . '</p>';
	}
}

// مسیر راهنما و صفحه‌بندی.

/**
 * مسیر راهنمای صفحه جاری.
 *
 * @return array<int, array{name:string,url:string}>
 */
function pixva_get_breadcrumbs() {
	$crumbs = array(
		array(
			'name' => __( 'خانه', 'pixva' ),
			'url'  => home_url( '/' ),
		),
	);

	if ( is_front_page() ) {
		return $crumbs;
	}

	if ( is_singular( 'post' ) ) {
		$crumbs[] = array(
			'name' => __( 'مجله', 'pixva' ),
			'url'  => pixva_blog_url(),
		);
		$cats     = get_the_category();
		if ( ! empty( $cats ) ) {
			$crumbs[] = array(
				'name' => $cats[0]->name,
				'url'  => get_category_link( $cats[0] ),
			);
		}
		$crumbs[] = array(
			'name' => get_the_title(),
			'url'  => get_permalink(),
		);
	} elseif ( is_singular() ) {
		$post_type = get_post_type();
		if ( $post_type && 'page' !== $post_type ) {
			$obj = get_post_type_object( $post_type );
			if ( $obj && $obj->has_archive ) {
				$crumbs[] = array(
					'name' => $obj->labels->name,
					'url'  => get_post_type_archive_link( $post_type ),
				);
			}
		} elseif ( is_page() ) {
			$ancestors = array_reverse( get_post_ancestors( get_the_ID() ) );
			foreach ( $ancestors as $ancestor ) {
				$crumbs[] = array(
					'name' => get_the_title( $ancestor ),
					'url'  => get_permalink( $ancestor ),
				);
			}
		}
		$crumbs[] = array(
			'name' => get_the_title(),
			'url'  => get_permalink(),
		);
	} elseif ( is_category() || is_tag() || is_tax() ) {
		$term = get_queried_object();
		if ( $term instanceof WP_Term ) {
			$crumbs[] = array(
				'name' => $term->name,
				'url'  => get_term_link( $term ),
			);
		}
	} elseif ( is_post_type_archive() ) {
		$obj      = get_queried_object();
		$crumbs[] = array(
			'name' => $obj instanceof WP_Post_Type ? $obj->labels->name : __( 'آرشیو', 'pixva' ),
			'url'  => get_post_type_archive_link( get_query_var( 'post_type' ) ),
		);
	} elseif ( is_search() ) {
		$crumbs[] = array(
			'name' => __( 'نتایج جستجو', 'pixva' ),
			'url'  => get_search_link(),
		);
	} elseif ( is_404() ) {
		$crumbs[] = array(
			'name' => __( 'صفحه پیدا نشد', 'pixva' ),
			'url'  => '',
		);
	}

	return $crumbs;
}

/**
 * چاپ مسیر راهنما.
 *
 * @return void
 */
function pixva_breadcrumbs() {
	if ( is_front_page() ) {
		return;
	}
	$crumbs = pixva_get_breadcrumbs();
	if ( count( $crumbs ) < 2 ) {
		return;
	}
	echo '<nav class="pixva-crumbs" aria-label="' . esc_attr__( 'مسیر صفحه', 'pixva' ) . '"><ol>';
	$last = count( $crumbs ) - 1;
	foreach ( $crumbs as $index => $crumb ) {
		echo '<li>';
		if ( $index !== $last && ! empty( $crumb['url'] ) && ! is_wp_error( $crumb['url'] ) ) {
			echo '<a href="' . esc_url( $crumb['url'] ) . '">' . esc_html( $crumb['name'] ) . '</a>';
		} else {
			echo '<span aria-current="page">' . esc_html( $crumb['name'] ) . '</span>';
		}
		echo '</li>';
	}
	echo '</ol></nav>';
}

/**
 * صفحه‌بندی آرشیو.
 *
 * @return void
 */
function pixva_pagination() {
	$links = paginate_links(
		array(
			'type'      => 'list',
			'prev_text' => __( 'قبلی', 'pixva' ),
			'next_text' => __( 'بعدی', 'pixva' ),
			'mid_size'  => 1,
		)
	);
	if ( ! $links ) {
		return;
	}
	echo '<nav class="pixva-pagination" aria-label="' . esc_attr__( 'صفحه‌بندی', 'pixva' ) . '">' . wp_kses_post( $links ) . '</nav>';
}

/**
 * سربرگ داخلی صفحات.
 *
 * @param string $title    عنوان.
 * @param string $subtitle زیرعنوان.
 * @param string $class    کلاس افزوده (مثلاً pixva-hub-hero برای صفحه‌های هاب).
 * @return void
 */
function pixva_page_hero( $title, $subtitle = '', $class = '' ) {
	$classes = 'pixva-page-hero' . ( '' !== $class ? ' ' . $class : '' );
	?>
	<header class="<?php echo esc_attr( $classes ); ?>">
		<div class="pixva-container">
			<?php pixva_breadcrumbs(); ?>
			<h1><?php echo esc_html( $title ); ?></h1>
			<?php if ( '' !== $subtitle ) : ?>
				<p><?php echo esc_html( $subtitle ); ?></p>
			<?php endif; ?>
		</div>
	</header>
	<?php
}

// کارت‌ها و مقاله.

/**
 * برچسب سطح سختی.
 *
 * @param string $key کلید.
 * @return string
 */
function pixva_difficulty_label( $key ) {
	$levels = array(
		'easy'   => __( 'آسان', 'pixva' ),
		'medium' => __( 'متوسط', 'pixva' ),
		'hard'   => __( 'تخصصی', 'pixva' ),
	);
	return isset( $levels[ $key ] ) ? $levels[ $key ] : '';
}

/**
 * تبدیل میلادی به جلالی. مستقل از بسته زبان وردپرس.
 *
 * @param int $gy سال میلادی.
 * @param int $gm ماه میلادی.
 * @param int $gd روز میلادی.
 * @return array{0:int,1:int,2:int}
 */
function pixva_gregorian_to_jalali( $gy, $gm, $gd ) {
	$g_d_m = array( 0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334 );
	$gy2   = ( $gm > 2 ) ? ( $gy + 1 ) : $gy;
	$days  = 355666 + ( 365 * $gy ) + (int) ( ( $gy2 + 3 ) / 4 ) - (int) ( ( $gy2 + 99 ) / 100 ) + (int) ( ( $gy2 + 399 ) / 400 ) + $gd + $g_d_m[ $gm - 1 ];
	$jy    = -1595 + ( 33 * (int) ( $days / 12053 ) );
	$days %= 12053;
	$jy   += 4 * (int) ( $days / 1461 );
	$days %= 1461;
	if ( $days > 365 ) {
		--$days;
		$jy   += (int) ( $days / 365 );
		$days %= 365;
	}
	if ( $days < 186 ) {
		$jm = 1 + (int) ( $days / 31 );
		$jd = 1 + ( $days % 31 );
	} else {
		$jm = 7 + (int) ( ( $days - 186 ) / 30 );
		$jd = 1 + ( ( $days - 186 ) % 30 );
	}
	return array( $jy, $jm, $jd );
}

/**
 * تاریخ جلالی با ارقام فارسی.
 *
 * @param int $timestamp زمان یونیکس.
 * @return string
 */
function pixva_format_date( $timestamp ) {
	$timestamp = (int) $timestamp;
	if ( $timestamp <= 0 ) {
		return '';
	}
	$months = array(
		1  => 'فروردین',
		2  => 'اردیبهشت',
		3  => 'خرداد',
		4  => 'تیر',
		5  => 'مرداد',
		6  => 'شهریور',
		7  => 'مهر',
		8  => 'آبان',
		9  => 'آذر',
		10 => 'دی',
		11 => 'بهمن',
		12 => 'اسفند',
	);
	$parts  = pixva_gregorian_to_jalali( (int) wp_date( 'Y', $timestamp ), (int) wp_date( 'n', $timestamp ), (int) wp_date( 'j', $timestamp ) );
	$month  = isset( $months[ $parts[1] ] ) ? $months[ $parts[1] ] : '';
	return pixva_fa_num( $parts[2] . ' ' . $month . ' ' . $parts[0] );
}

/**
 * فیلدهای فارسی فرم دیدگاه.
 *
 * @param array $fields فیلدهای پیش‌فرض.
 * @return array
 */
function pixva_comment_form_fields( $fields ) {
	$fields['author'] = '<div class="pixva-field"><label for="author">' . esc_html__( 'نام', 'pixva' ) . '</label><input id="author" name="author" type="text" required></div>';
	$fields['email']  = '<div class="pixva-field"><label for="email">' . esc_html__( 'ایمیل', 'pixva' ) . '</label><input id="email" name="email" type="email" required></div>';
	unset( $fields['url'] );
	$fields['cookies'] = '<p class="comment-form-cookies-consent"><label><input type="checkbox" name="wp-comment-cookies-consent" value="yes"> ' . esc_html__( 'نام و ایمیل را برای دیدگاه بعدی ذخیره کن.', 'pixva' ) . '</label></p>';
	return $fields;
}
add_filter( 'comment_form_default_fields', 'pixva_comment_form_fields' );

/**
 * چاپ یک دیدگاه.
 *
 * @param WP_Comment $comment دیدگاه.
 * @param array      $args    آرگومان‌ها.
 * @param int        $depth   عمق.
 * @return void
 */
function pixva_comment_item( $comment, $args, $depth ) {
	$initial = (int) $comment->user_id ? pixva_author_initial( (int) $comment->user_id ) : 'م';
	?>
	<li id="comment-<?php comment_ID(); ?>" <?php comment_class( 'pixva-comment' ); ?>>
		<article>
			<header class="pixva-comment__head">
				<span class="pixva-author__mark" aria-hidden="true"><?php echo esc_html( $initial ); ?></span>
				<div>
					<strong><?php comment_author(); ?></strong>
					<time datetime="<?php echo esc_attr( get_comment_date( 'c' ) ); ?>"><?php echo esc_html( pixva_format_date( (int) get_comment_date( 'U' ) ) ); ?></time>
				</div>
			</header>
			<div class="entry-content">
				<?php if ( '0' === $comment->comment_approved ) : ?>
					<p class="pixva-notice pixva-notice--info"><?php esc_html_e( 'دیدگاه شما پس از تأیید نمایش داده می‌شود.', 'pixva' ); ?></p>
				<?php endif; ?>
				<?php comment_text(); ?>
			</div>
			<?php
			comment_reply_link(
				array_merge(
					$args,
					array(
						'depth'     => $depth,
						'max_depth' => $args['max_depth'],
					)
				)
			);
			?>
		</article>
	<?php
}

/**
 * حرف اول نام نویسنده برای آواتار محلی.
 *
 * @param int $author_id شناسه کاربر.
 * @return string
 */
function pixva_author_initial( $author_id ) {
	$name = (string) get_the_author_meta( 'display_name', $author_id );
	if ( '' === $name ) {
		return 'پ';
	}
	if ( function_exists( 'mb_substr' ) ) {
		return mb_substr( $name, 0, 1 );
	}
	return substr( $name, 0, 1 );
}

/**
 * کارت نوشته.
 *
 * @param int $post_id شناسه. صفر یعنی نوشته جاری حلقه.
 * @return void
 */
function pixva_post_card( $post_id = 0 ) {
	$post_id = $post_id ? (int) $post_id : get_the_ID();
	if ( ! $post_id ) {
		return;
	}
	$permalink = get_permalink( $post_id );
	$cats      = get_the_category( $post_id );
	?>
	<article class="pixva-card pixva-post-card">
		<a class="pixva-post-card__media" href="<?php echo esc_url( $permalink ); ?>" tabindex="-1" aria-hidden="true">
			<?php
			if ( has_post_thumbnail( $post_id ) ) {
				echo get_the_post_thumbnail( $post_id, 'pixva-card', array( 'alt' => esc_attr( get_the_title( $post_id ) ) ) );
			} else {
				echo '<span class="pixva-post-card__placeholder">' . pixva_icon( 'panel' ) . '</span>';
			}
			?>
		</a>
		<div class="pixva-post-card__body">
			<div class="pixva-post-card__meta">
				<?php if ( ! empty( $cats ) ) : ?>
					<a href="<?php echo esc_url( get_category_link( $cats[0] ) ); ?>"><?php echo esc_html( $cats[0]->name ); ?></a>
				<?php endif; ?>
				<time datetime="<?php echo esc_attr( get_the_date( 'c', $post_id ) ); ?>"><?php echo esc_html( pixva_format_date( get_post_timestamp( $post_id ) ) ); ?></time>
			</div>
			<h3><a href="<?php echo esc_url( $permalink ); ?>"><?php echo esc_html( get_the_title( $post_id ) ); ?></a></h3>
			<p><?php echo esc_html( wp_trim_words( get_the_excerpt( $post_id ), 22 ) ); ?></p>
		</div>
	</article>
	<?php
}

/**
 * تجزیه سوالات متداول ذخیره‌شده.
 *
 * @param string $raw متن «سوال | پاسخ» در هر خط.
 * @return array<int, array{q:string,a:string}>
 */
function pixva_parse_faq( $raw ) {
	$items = array();
	$lines = preg_split( '/\r\n|\r|\n/', (string) $raw );
	if ( ! is_array( $lines ) ) {
		return $items;
	}
	foreach ( $lines as $line ) {
		$line = trim( $line );
		if ( '' === $line || false === strpos( $line, '|' ) ) {
			continue;
		}
		$parts = array_map( 'trim', explode( '|', $line, 2 ) );
		if ( '' === $parts[0] || '' === $parts[1] ) {
			continue;
		}
		$items[] = array(
			'q' => $parts[0],
			'a' => $parts[1],
		);
	}
	return $items;
}

/**
 * پرسش‌های صفحه جاری برای نمایش و اسکیما.
 *
 * @return array<int, array{q:string,a:string}>
 */
function pixva_current_faq_items() {
	if ( is_singular( 'post' ) ) {
		return pixva_parse_faq( (string) get_post_meta( get_the_ID(), '_pixva_post_faq', true ) );
	}
	if ( is_page_template( 'page-templates/page-faq.php' ) ) {
		return pixva_default_faqs();
	}
	return array();
}

/**
 * آکاردئون پرسش‌های متداول.
 *
 * @param array  $items  آیتم‌ها.
 * @param string $prefix پیشوند شناسه.
 * @return void
 */
function pixva_render_faq( $items, $prefix = 'faq' ) {
	if ( empty( $items ) ) {
		return;
	}
	echo '<div class="pixva-faq" data-pixva-faq>';
	$index = 1;
	foreach ( $items as $item ) {
		$id = $prefix . '-' . $index;
		echo '<div class="pixva-faq__item">';
		echo '<h3 class="pixva-faq__heading"><button type="button" class="pixva-faq__q" aria-expanded="false" aria-controls="' . esc_attr( $id ) . '">';
		echo '<span>' . esc_html( $item['q'] ) . '</span>';
		echo '<span class="pixva-faq__icon" aria-hidden="true"></span>';
		echo '</button></h3>';
		echo '<div id="' . esc_attr( $id ) . '" class="pixva-faq__a"><p>' . esc_html( $item['a'] ) . '</p></div>';
		echo '</div>';
		++$index;
	}
	echo '</div>';
}

/**
 * باکس مشخصات عیب‌یابی بالای مقاله.
 *
 * @param int $post_id شناسه نوشته.
 * @return void
 */
function pixva_diagnostics_box( $post_id ) {
	$content    = (string) get_post_field( 'post_content', $post_id );
	$minutes    = pixva_reading_time( $content );
	$difficulty = pixva_difficulty_label( (string) get_post_meta( $post_id, '_pixva_post_difficulty', true ) );
	$brand      = (string) get_post_meta( $post_id, '_pixva_post_brand', true );
	$tools      = (string) get_post_meta( $post_id, '_pixva_post_tools', true );
	$problems   = get_the_terms( $post_id, 'tv_problem' );
	?>
	<aside class="pixva-diag pixva-glass">
		<ul>
			<li>
				<?php echo pixva_icon( 'clock' ); ?>
				<span><?php esc_html_e( 'زمان مطالعه', 'pixva' ); ?></span>
				<strong><?php echo esc_html( pixva_fa_num( $minutes ) . ' ' . __( 'دقیقه', 'pixva' ) ); ?></strong>
			</li>
			<li>
				<?php echo pixva_icon( 'tool' ); ?>
				<span><?php esc_html_e( 'سطح سختی', 'pixva' ); ?></span>
				<strong><?php echo esc_html( $difficulty ? $difficulty : __( 'عمومی', 'pixva' ) ); ?></strong>
			</li>
			<li>
				<?php echo pixva_icon( 'panel' ); ?>
				<span><?php esc_html_e( 'برند مرتبط', 'pixva' ); ?></span>
				<strong><?php echo esc_html( $brand ? $brand : __( 'همه برندها', 'pixva' ) ); ?></strong>
			</li>
			<li>
				<?php echo pixva_icon( 'cpu' ); ?>
				<span><?php esc_html_e( 'ابزار لازم', 'pixva' ); ?></span>
				<strong><?php echo esc_html( $tools ? $tools : __( 'عیب‌یابی چشمی', 'pixva' ) ); ?></strong>
			</li>
		</ul>
		<?php if ( ! empty( $problems ) && ! is_wp_error( $problems ) ) : ?>
			<div class="pixva-diag__tags">
				<?php foreach ( $problems as $problem ) : ?>
					<a href="<?php echo esc_url( get_term_link( $problem ) ); ?>"><?php echo esc_html( $problem->name ); ?></a>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</aside>
	<?php
}

/**
 * باکس فراخوان داخل مقاله. اگر $echo false باشد رشته برمی‌گرداند.
 *
 * @param bool $echo چاپ شود یا نه.
 * @return string
 */
function pixva_cta_box( $echo = true ) {
	ob_start();
	?>
	<aside class="pixva-cta-box">
		<div>
			<span class="pixva-badge pixva-badge--cta"><?php esc_html_e( 'برآورد فوری', 'pixva' ); ?></span>
			<h2><?php esc_html_e( 'استعلام سریع هزینه تعمیر این مشکل', 'pixva' ); ?></h2>
			<p><?php esc_html_e( 'برند، سایز و نوع خرابی را بگویید تا بازه قیمت و زمان کارگاه را همان لحظه ببینید.', 'pixva' ); ?></p>
		</div>
		<a class="pixva-btn pixva-btn--cta pixva-btn--bolt" href="<?php echo esc_url( pixva_page_url( 'calculator' ) ); ?>"><?php esc_html_e( 'محاسبه هزینه تعمیر', 'pixva' ); ?></a>
	</aside>
	<?php
	$html = (string) ob_get_clean();
	if ( $echo ) {
		echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- قطعات داخلی همگی escape شده‌اند.
		return '';
	}
	return $html;
}

/**
 * درج CTA بعد از پاراگراف اول محتوا.
 *
 * @param string $html محتوای فیلترشده.
 * @return string
 */
function pixva_inject_cta( $html ) {
	$cta = pixva_cta_box( false );
	$pos = stripos( $html, '</p>' );
	if ( false === $pos ) {
		return $cta . $html;
	}
	$pos += 4;
	return substr( $html, 0, $pos ) . $cta . substr( $html, $pos );
}

/**
 * باکس نویسنده / تکنسین.
 *
 * @return void
 */
function pixva_author_box() {
	$author_id = (int) get_the_author_meta( 'ID' );
	$bio       = get_the_author_meta( 'description', $author_id );
	if ( '' === trim( (string) $bio ) ) {
		$bio = __( 'این مطلب توسط تیم فنی کارگاه پیکسوا، بر اساس پرونده‌های واقعی تعمیر پنل و برد، نوشته شده است.', 'pixva' );
	}
	?>
	<aside class="pixva-author pixva-card">
		<span class="pixva-author__avatar pixva-author__mark" aria-hidden="true"><?php echo esc_html( pixva_author_initial( $author_id ) ); ?></span>
		<div>
			<span class="pixva-muted"><?php esc_html_e( 'نویسنده فنی', 'pixva' ); ?></span>
			<strong><?php echo esc_html( get_the_author() ); ?></strong>
			<p><?php echo esc_html( $bio ); ?></p>
		</div>
	</aside>
	<?php
}

/**
 * مقالات مرتبط بر اساس نوع خرابی.
 *
 * @param int $post_id شناسه نوشته.
 * @return void
 */
function pixva_related_posts( $post_id ) {
	$terms = wp_get_post_terms( $post_id, 'tv_problem', array( 'fields' => 'ids' ) );
	$args  = array(
		'post_type'           => 'post',
		'posts_per_page'      => 3,
		'post__not_in'        => array( $post_id ),
		'ignore_sticky_posts' => true,
		'no_found_rows'       => true,
	);
	if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
		$args['tax_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
			array(
				'taxonomy' => 'tv_problem',
				'field'    => 'term_id',
				'terms'    => $terms,
			),
		);
	}
	$related = get_posts( $args );
	if ( empty( $related ) ) {
		return;
	}
	echo '<section class="pixva-related"><h2>' . esc_html__( 'مقاله‌های مرتبط با همین خرابی', 'pixva' ) . '</h2><div class="pixva-grid pixva-grid--3">';
	foreach ( $related as $related_post ) {
		pixva_post_card( $related_post->ID );
	}
	echo '</div></section>';
}

// محاسبه‌گر و قبل/بعد.

/**
 * فرم چندمرحله‌ای محاسبه‌گر.
 *
 * @param array $args preset_brand, preset_problem, compact.
 * @return void
 */
function pixva_render_calculator( $args = array() ) {
	$args           = wp_parse_args(
		$args,
		array(
			'preset_brand'   => '',
			'preset_problem' => '',
			'compact'        => false,
		)
	);
	$preset_brand   = sanitize_key( (string) $args['preset_brand'] );
	$preset_problem = sanitize_key( (string) $args['preset_problem'] );
	if ( isset( $_GET['brand'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$preset_brand = sanitize_key( wp_unslash( $_GET['brand'] ) );
	}
	if ( isset( $_GET['problem'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$preset_problem = sanitize_key( wp_unslash( $_GET['problem'] ) );
	}
	?>
	<form class="pixva-calc<?php echo $args['compact'] ? ' pixva-calc--compact' : ''; ?>" data-pixva-calc data-preset-brand="<?php echo esc_attr( $preset_brand ); ?>" data-preset-problem="<?php echo esc_attr( $preset_problem ); ?>" novalidate>
		<?php pixva_honeypot_field(); ?>
		<input type="hidden" name="brand" value="">
		<input type="hidden" name="tech" value="">
		<input type="hidden" name="size" value="">
		<input type="hidden" name="problem" value="">

		<ol class="pixva-calc__progress" aria-label="<?php esc_attr_e( 'مراحل محاسبه', 'pixva' ); ?>">
			<li data-progress="1" class="is-current"><?php esc_html_e( 'برند', 'pixva' ); ?></li>
			<li data-progress="2"><?php esc_html_e( 'تکنولوژی و سایز', 'pixva' ); ?></li>
			<li data-progress="3"><?php esc_html_e( 'نوع مشکل', 'pixva' ); ?></li>
			<li data-progress="4"><?php esc_html_e( 'برآورد', 'pixva' ); ?></li>
		</ol>

		<div class="pixva-calc__step is-active" data-step="1">
			<h3><?php esc_html_e( 'برند تلویزیون را انتخاب کنید', 'pixva' ); ?></h3>
			<div class="pixva-choice-grid pixva-choice-grid--brand">
				<?php foreach ( pixva_brand_catalog() as $key => $brand ) : ?>
					<button type="button" class="pixva-choice" data-group="brand" data-value="<?php echo esc_attr( $key ); ?>">
						<span class="pixva-latin pixva-choice__en"><?php echo esc_html( $brand['en'] ); ?></span>
						<span><?php echo esc_html( $brand['fa'] ); ?></span>
					</button>
				<?php endforeach; ?>
			</div>
			<div class="pixva-calc__nav">
				<button type="button" class="pixva-btn pixva-btn--primary" data-next disabled><?php esc_html_e( 'ادامه', 'pixva' ); ?></button>
			</div>
		</div>

		<div class="pixva-calc__step" data-step="2" hidden>
			<h3><?php esc_html_e( 'تکنولوژی صفحه', 'pixva' ); ?></h3>
			<div class="pixva-choice-grid">
				<?php foreach ( pixva_tech_catalog() as $key => $label ) : ?>
					<button type="button" class="pixva-choice" data-group="tech" data-value="<?php echo esc_attr( $key ); ?>">
						<span class="pixva-latin"><?php echo esc_html( $label ); ?></span>
					</button>
				<?php endforeach; ?>
			</div>
			<h3><?php esc_html_e( 'سایز', 'pixva' ); ?></h3>
			<div class="pixva-choice-grid pixva-choice-grid--size">
				<?php foreach ( pixva_size_catalog() as $key => $label ) : ?>
					<button type="button" class="pixva-choice" data-group="size" data-value="<?php echo esc_attr( $key ); ?>">
						<span><?php echo esc_html( $label ); ?></span>
					</button>
				<?php endforeach; ?>
			</div>
			<div class="pixva-calc__nav">
				<button type="button" class="pixva-btn pixva-btn--ghost" data-prev><?php esc_html_e( 'بازگشت', 'pixva' ); ?></button>
				<button type="button" class="pixva-btn pixva-btn--primary" data-next disabled><?php esc_html_e( 'ادامه', 'pixva' ); ?></button>
			</div>
		</div>

		<div class="pixva-calc__step" data-step="3" hidden>
			<h3><?php esc_html_e( 'مشکل را نزدیک‌ترین گزینه انتخاب کنید', 'pixva' ); ?></h3>
			<div class="pixva-choice-grid pixva-choice-grid--problem">
				<?php foreach ( pixva_problem_catalog() as $key => $label ) : ?>
					<button type="button" class="pixva-choice" data-group="problem" data-value="<?php echo esc_attr( $key ); ?>">
						<span><?php echo esc_html( $label ); ?></span>
					</button>
				<?php endforeach; ?>
			</div>
			<div class="pixva-calc__nav">
				<button type="button" class="pixva-btn pixva-btn--ghost" data-prev><?php esc_html_e( 'بازگشت', 'pixva' ); ?></button>
				<button type="button" class="pixva-btn pixva-btn--cta pixva-btn--bolt" data-estimate disabled><?php esc_html_e( 'محاسبه برآورد', 'pixva' ); ?></button>
			</div>
		</div>

		<div class="pixva-calc__step" data-step="4" hidden>
			<div class="pixva-calc__result" data-calc-result>
				<p class="pixva-muted" data-summary></p>
				<p class="pixva-notice pixva-notice--warning" data-calc-warning hidden></p>
				<p class="pixva-calc__price" data-price-row><strong data-price>—</strong> <span><?php esc_html_e( 'تومان', 'pixva' ); ?></span></p>
				<p class="pixva-calc__days"><?php echo pixva_icon( 'clock' ); ?><span data-days></span></p>
				<p class="pixva-calc__note" data-disclaimer></p>
			</div>
			<div class="pixva-calc__order" data-calc-order>
				<h3><?php esc_html_e( 'ثبت شماره برای هماهنگی نوبت', 'pixva' ); ?></h3>
				<div class="pixva-grid pixva-grid--2">
					<div class="pixva-field">
						<label for="pixva-order-name"><?php esc_html_e( 'نام', 'pixva' ); ?></label>
						<input type="text" id="pixva-order-name" name="customer_name" autocomplete="name" maxlength="80">
					</div>
					<div class="pixva-field">
						<label for="pixva-order-phone"><?php esc_html_e( 'شماره همراه', 'pixva' ); ?></label>
						<input type="tel" id="pixva-order-phone" name="phone" inputmode="numeric" autocomplete="tel" placeholder="0912xxxxxxx" required>
					</div>
				</div>
				<div class="pixva-field">
					<label for="pixva-order-model"><?php esc_html_e( 'مدل دستگاه (اختیاری)', 'pixva' ); ?></label>
					<input type="text" id="pixva-order-model" name="model" maxlength="80" placeholder="UA55AU7000">
				</div>
				<button type="submit" class="pixva-btn pixva-btn--cta pixva-btn--bolt"><?php esc_html_e( 'ثبت نوبت و دریافت کد پیگیری', 'pixva' ); ?></button>
				<p class="pixva-notice" data-order-msg hidden></p>
			</div>
			<div class="pixva-calc__nav">
				<button type="button" class="pixva-btn pixva-btn--ghost" data-prev><?php esc_html_e( 'اصلاح انتخاب‌ها', 'pixva' ); ?></button>
			</div>
		</div>
		<p class="pixva-notice pixva-notice--error" data-calc-error hidden></p>
	</form>
	<?php
}

/**
 * اسلایدر قبل و بعد.
 *
 * @param string $before آدرس تصویر قبل.
 * @param string $after  آدرس تصویر بعد.
 * @param string $alt    متن جایگزین.
 * @return void
 */
function pixva_render_before_after( $before, $after, $alt = '' ) {
	if ( '' === $before || '' === $after ) {
		return;
	}
	$alt = $alt ? $alt : __( 'مقایسه پنل قبل و بعد از تعمیر', 'pixva' );
	?>
	<figure class="pixva-ba" data-pixva-ba>
		<img class="pixva-ba__after" src="<?php echo esc_url( $after ); ?>" alt="<?php echo esc_attr( $alt ); ?>" width="1200" height="675">
		<div class="pixva-ba__before">
			<img src="<?php echo esc_url( $before ); ?>" alt="" width="1200" height="675">
		</div>
		<span class="pixva-ba__handle" aria-hidden="true"></span>
		<span class="pixva-ba__label pixva-ba__label--before"><?php esc_html_e( 'قبل از تعمیر', 'pixva' ); ?></span>
		<span class="pixva-ba__label pixva-ba__label--after"><?php esc_html_e( 'بعد از تعمیر', 'pixva' ); ?></span>
		<input class="pixva-ba__range" type="range" min="0" max="100" value="58" aria-label="<?php esc_attr_e( 'کشیدن خط مقایسه قبل و بعد', 'pixva' ); ?>">
	</figure>
	<?php
}

/**
 * تصویر قبل/بعد یک نمونه‌کار، با بازگشت به تصاویر قالب.
 *
 * @param int $post_id شناسه نمونه‌کار.
 * @return array{before:string,after:string}
 */
function pixva_case_images( $post_id ) {
	$before_id = (int) get_post_meta( $post_id, '_pixva_case_before', true );
	$after_id  = (int) get_post_meta( $post_id, '_pixva_case_after', true );
	$before    = $before_id ? wp_get_attachment_image_url( $before_id, 'pixva-ba' ) : '';
	$after     = $after_id ? wp_get_attachment_image_url( $after_id, 'pixva-ba' ) : '';
	if ( ! $before ) {
		$before = PIXVA_URI . '/assets/images/panel-before.jpg';
	}
	if ( ! $after ) {
		$after = PIXVA_URI . '/assets/images/panel-after.jpg';
	}
	return array(
		'before' => $before,
		'after'  => $after,
	);
}

/**
 * پایگاه کد خطا را چاپ می‌کند.
 *
 * @return void
 */
function pixva_render_error_database() {
	$brands = pixva_brand_catalog();
	$rows   = pixva_error_code_catalog();
	?>
	<div class="pixva-errors" data-pixva-errors>
		<div class="pixva-errors__filters pixva-card">
			<div class="pixva-field">
				<label for="pixva-filter-brand"><?php esc_html_e( 'برند', 'pixva' ); ?></label>
				<select id="pixva-filter-brand" data-filter-brand>
					<option value=""><?php esc_html_e( 'همه برندها', 'pixva' ); ?></option>
					<?php foreach ( $brands as $key => $brand ) : ?>
						<option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $brand['fa'] ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>
			<div class="pixva-field">
				<label for="pixva-filter-blink"><?php esc_html_e( 'تعداد چشمک', 'pixva' ); ?></label>
				<select id="pixva-filter-blink" data-filter-blink>
					<option value=""><?php esc_html_e( 'همه الگوها', 'pixva' ); ?></option>
					<?php foreach ( array( 2, 3, 4, 5, 6, 7, 8 ) as $count ) : ?>
						<option value="<?php echo esc_attr( (string) $count ); ?>"><?php echo esc_html( pixva_fa_num( $count ) . ' ' . __( 'چشمک', 'pixva' ) ); ?></option>
					<?php endforeach; ?>
					<option value="0"><?php esc_html_e( 'پیام روی صفحه', 'pixva' ); ?></option>
				</select>
			</div>
			<div class="pixva-field">
				<label for="pixva-filter-q"><?php esc_html_e( 'جستجو در شرح', 'pixva' ); ?></label>
				<input type="search" id="pixva-filter-q" data-filter-q placeholder="<?php esc_attr_e( 'مثلاً برد تغذیه', 'pixva' ); ?>">
			</div>
			<p class="pixva-errors__count"><span data-count><?php echo esc_html( pixva_fa_num( count( $rows ) ) ); ?></span> <?php esc_html_e( 'مورد', 'pixva' ); ?></p>
		</div>
		<div class="pixva-errors__grid">
			<?php foreach ( $rows as $row ) : ?>
				<?php
				$brand_fa = isset( $brands[ $row['brand'] ] ) ? $brands[ $row['brand'] ]['fa'] : $row['brand'];
				$brand_en = isset( $brands[ $row['brand'] ] ) ? $brands[ $row['brand'] ]['en'] : $row['brand'];
				?>
				<article class="pixva-card pixva-error-card" data-brand="<?php echo esc_attr( $row['brand'] ); ?>" data-blinks="<?php echo esc_attr( (string) $row['blinks'] ); ?>">
					<header>
						<span class="pixva-badge"><?php echo esc_html( $brand_fa ); ?></span>
						<strong class="pixva-latin pixva-error-code__code"><?php echo esc_html( $row['code'] ); ?></strong>
					</header>
					<h3><?php echo esc_html( $row['title'] ); ?></h3>
					<p><?php echo esc_html( $row['symptom'] ); ?></p>
					<p class="pixva-error-card__action"><span><?php esc_html_e( 'اقدام کارگاه', 'pixva' ); ?></span> <?php echo esc_html( $row['action'] ); ?></p>
					<p class="pixva-latin pixva-muted"><?php echo esc_html( $brand_en ); ?></p>
				</article>
			<?php endforeach; ?>
		</div>
		<p class="pixva-notice pixva-notice--info" data-empty hidden><?php esc_html_e( 'موردی با این فیلتر پیدا نشد. تعداد چشمک را دوباره بشمارید یا برند را روی همه بگذارید.', 'pixva' ); ?></p>
	</div>
	<?php
}

/**
 * جدول مرجع نرخ‌نامه بازار ۱۴۰۵ (مستر اسپک v25.0).
 *
 * دو بخش دارد:
 * ۱) بازه‌های اعلام‌شده بازار برای چهار خدمت اصلی در سایزهای ۵۵ و ۶۵ تا ۸۵ اینچ.
 * ۲) خروجی واقعی موتور نرخ‌نامه برای برند مرجع بازار در همان سایزها، تا مشتری
 *    ببیند برآورد آنلاین دقیقاً از کدام فرمول می‌آید.
 *
 * @return void
 */
function pixva_render_rates_reference() {
	if ( ! function_exists( 'pixva_pricing_market_bands' ) ) {
		return;
	}

	$bands    = pixva_pricing_market_bands();
	$settings = pixva_pricing_settings();
	$labels   = pixva_calculator_labels();
	$reference = function_exists( 'pixva_pricing_reference_table' ) ? pixva_pricing_reference_table() : array();
	$ref_brand = isset( $reference['reference_brand'] ) ? $reference['reference_brand'] : 'samsung';
	$ref_label = isset( $labels['brand'][ $ref_brand ] ) ? $labels['brand'][ $ref_brand ] : $ref_brand;
	$computed  = array();
	if ( isset( $reference['rows'] ) ) {
		foreach ( $reference['rows'] as $row ) {
			$computed[ $row['title'] ] = $row['computed'];
		}
	}
	?>
	<section class="pixva-card pixva-rates-reference">
		<span class="pixva-badge pixva-badge--brand"><?php esc_html_e( 'نرخ‌نامه مصوب بازار ۱۴۰۵', 'pixva' ); ?></span>
		<h2><?php esc_html_e( 'بازه قیمت چهار خدمت اصلی', 'pixva' ); ?></h2>
		<p class="pixva-muted"><?php esc_html_e( 'کف قیمت برای سایز ۳۲ اینچ اعلام می‌شود و بازه سایزهای بزرگ‌تر بر اساس فرمول ضریب سایز محاسبه می‌گردد.', 'pixva' ); ?></p>

		<div class="pixva-table-scroll">
			<table class="pixva-rates-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'خدمت', 'pixva' ); ?></th>
						<th><?php esc_html_e( 'کف ۳۲ اینچ', 'pixva' ); ?></th>
						<th><?php esc_html_e( 'بازه ۵۵ اینچ', 'pixva' ); ?></th>
						<th><?php esc_html_e( 'بازه ۶۵ تا ۸۵ اینچ', 'pixva' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $bands as $band ) : ?>
						<tr>
							<td>
								<strong><?php echo esc_html( $band['title'] ); ?></strong>
								<small class="pixva-muted"><?php echo esc_html( $band['note'] ); ?></small>
							</td>
							<td class="pixva-latin"><?php echo esc_html( pixva_price( (int) $band['floor'] ) ); ?></td>
							<td class="pixva-latin"><?php echo esc_html( pixva_price( (int) $band['size_55'][0] ) . ' – ' . pixva_price( (int) $band['size_55'][1] ) ); ?></td>
							<td class="pixva-latin"><?php echo esc_html( pixva_price( (int) $band['size_65'][0] ) . ' – ' . pixva_price( (int) $band['size_65'][1] ) ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>

		<h3><?php esc_html_e( 'خروجی زنده موتور نرخ‌نامه', 'pixva' ); ?></h3>
		<p class="pixva-muted">
			<?php
			echo esc_html(
				sprintf(
					/* translators: %s: برند مرجع */
					__( 'برآورد زیر برای برند مرجع بازار (%s) و تکنولوژی LED از فرمول نرخ‌نامه محاسبه شده است؛ با انتخاب برند دیگر در محاسبه‌گر، ضریب برند تغییر می‌کند.', 'pixva' ),
					$ref_label
				)
			);
			?>
		</p>
		<div class="pixva-table-scroll">
			<table class="pixva-rates-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'خدمت', 'pixva' ); ?></th>
						<?php foreach ( array( '55', '65', '75', '85' ) as $size ) : ?>
							<th><?php echo esc_html( sprintf( __( '%s اینچ', 'pixva' ), pixva_fa_num( $size ) ) ); ?></th>
						<?php endforeach; ?>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $bands as $band ) : ?>
						<?php $row = isset( $computed[ $band['title'] ] ) ? $computed[ $band['title'] ] : array(); ?>
						<tr>
							<td><strong><?php echo esc_html( $band['title'] ); ?></strong></td>
							<?php foreach ( array( '55', '65', '75', '85' ) as $size ) : ?>
								<td class="pixva-latin">
									<?php
									if ( isset( $row[ $size ] ) && (int) $row[ $size ][1] > 0 ) {
										echo esc_html( pixva_price( (int) $row[ $size ][0] ) . ' – ' . pixva_price( (int) $row[ $size ][1] ) );
									} else {
										echo esc_html( '—' );
									}
									?>
								</td>
							<?php endforeach; ?>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>

		<h3><?php esc_html_e( 'ضریب برندها در نرخ‌نامه', 'pixva' ); ?></h3>
		<ul class="pixva-brand-multipliers">
			<?php foreach ( $settings['brands'] as $key => $multiplier ) : ?>
				<li>
					<span><?php echo esc_html( isset( $labels['brand'][ $key ] ) ? $labels['brand'][ $key ] : $key ); ?></span>
					<b class="pixva-latin"><?php echo esc_html( pixva_fa_num( number_format_i18n( (float) $multiplier, 2 ) ) ); ?></b>
				</li>
			<?php endforeach; ?>
		</ul>
		<p class="pixva-muted"><?php esc_html_e( 'فرمول: قیمت = (پایه خدمت × ضریب برند × ضریب تکنولوژی × ضریب سایز) + هزینه کارشناسی. ضریب سایز = ۱ + ((سایز − ۳۲) ÷ ۳۲)^۱٫۳۵ و قیمت هیچ‌گاه از کف نرخ‌نامه کمتر نمی‌شود.', 'pixva' ); ?></p>
	</section>
	<?php
}

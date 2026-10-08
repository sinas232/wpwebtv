<?php
/**
 * Diagnosis knowledge base (§07).
 *
 * General, brand-independent TV fault knowledge. Every result is
 * probabilistic ("likely / possible / needs inspection") and paired with a
 * reminder that only a technician's inspection is definitive. Safe checks
 * are restricted to actions that need no tools and never involve opening
 * the case or touching internal parts (§09 safety rule).
 *
 * Structure:
 *  problem_key => [
 *    label, desc,
 *    symptoms => [key => label],
 *    danger   => [symptom keys that trigger a stop-using warning],
 *    always_danger => bool,
 *    causes   => [key => [label, desc, base, boost => [symptom => weight], service, old => weight]],
 *    safe     => [safe check strings],
 *  ]
 * `service` is the slug of a tv_services post to link (if published).
 * `old` is a boost applied when the TV is older than five years.
 *
 * Extend or override with the `pixva_diagnosis_rules` filter.
 *
 * @package Pixva
 * @since   2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return array(
	'no_power'   => array(
		'label'    => __( 'کاملاً خاموش است', 'pixva' ),
		'desc'     => __( 'با زدن دکمه پاور هیچ واکنشی نشان نمی‌دهد.', 'pixva' ),
		'symptoms' => array(
			'led_off'       => __( 'چراغ پاور هم روشن نمی‌شود', 'pixva' ),
			'click_sound'   => __( 'صدای تق یا کلیک از دستگاه شنیده می‌شود', 'pixva' ),
			'after_surge'   => __( 'بعد از نوسان یا قطع و وصل برق رخ داد', 'pixva' ),
			'gradual'       => __( 'قبلاً گاهی دیر روشن می‌شد', 'pixva' ),
			'burning_smell' => __( 'بوی سوختگی یا دود حس شد', 'pixva' ),
		),
		'danger'   => array( 'burning_smell' ),
		'causes'   => array(
			'power_board' => array(
				__( 'ایراد برد پاور (منبع تغذیه)', 'pixva' ),
				__( 'شایع‌ترین علت خاموشی کامل؛ معمولاً پس از نوسان برق یا فرسودگی خازن‌ها.', 'pixva' ),
				3,
				array(
					'after_surge'   => 2,
					'burning_smell' => 2,
					'gradual'       => 2,
					'led_off'       => 1,
				),
				'powerboard',
				1,
			),
			'external'    => array( __( 'کابل برق، پریز یا چندراهی', 'pixva' ), __( 'پیش از هر چیز باید مسیر برق بیرونی بررسی شود.', 'pixva' ), 2, array( 'led_off' => 1 ), '', 0 ),
			'standby'     => array( __( 'مدار آماده‌به‌کار روی برد اصلی', 'pixva' ), __( 'وقتی برق می‌رسد ولی فرمان روشن شدن صادر نمی‌شود.', 'pixva' ), 1, array( 'click_sound' => 3 ), 'mainboard', 0 ),
		),
		'safe'     => array(
			__( 'پریز را با یک وسیله دیگر امتحان کنید.', 'pixva' ),
			__( 'اگر از چندراهی یا محافظ استفاده می‌کنید، دستگاه را مستقیم به پریز دیواری وصل کنید.', 'pixva' ),
			__( 'دوشاخه را یک دقیقه از برق بکشید و دوباره وصل کنید.', 'pixva' ),
			__( 'اگر کابل برق جداشدنی است و کابل سالم مشابهی دارید، آن را امتحان کنید.', 'pixva' ),
		),
	),
	'no_picture' => array(
		'label'    => __( 'صدا دارد ولی تصویر ندارد', 'pixva' ),
		'desc'     => __( 'صدای برنامه شنیده می‌شود اما صفحه سیاه است.', 'pixva' ),
		'symptoms' => array(
			'faint_image'    => __( 'با تاباندن چراغ‌قوه از نزدیک، تصویر کم‌رنگ دیده می‌شود', 'pixva' ),
			'flash_then_off' => __( 'تصویر لحظه‌ای می‌آید و می‌رود', 'pixva' ),
			'menu_visible'   => __( 'منوی خود تلویزیون دیده می‌شود ولی تصویر ورودی نه', 'pixva' ),
			'no_logo'        => __( 'لوگوی شروع هم نمایش داده نمی‌شود', 'pixva' ),
		),
		'causes'   => array(
			'backlight' => array(
				__( 'خرابی بک‌لایت (نور پس‌زمینه)', 'pixva' ),
				__( 'اگر تصویر با چراغ‌قوه دیده شود، پنل سالم است و نور پشت آن خاموش است.', 'pixva' ),
				3,
				array(
					'faint_image'    => 4,
					'flash_then_off' => 2,
					'no_logo'        => 1,
				),
				'backlight',
				1,
			),
			'tcon'      => array( __( 'برد تی‌کان یا برد اصلی', 'pixva' ), __( 'سیگنال تصویر به پنل نمی‌رسد.', 'pixva' ), 2, array( 'no_logo' => 2 ), 'mainboard', 0 ),
			'source'    => array( __( 'منبع سیگنال یا تنظیمات ورودی', 'pixva' ), __( 'وقتی منوی تلویزیون دیده می‌شود، ایراد معمولاً از ورودی یا دستگاه متصل است.', 'pixva' ), 1, array( 'menu_visible' => 5 ), '', 0 ),
			'panel'     => array( __( 'ایراد پنل', 'pixva' ), __( 'کمتر شایع؛ فقط با بررسی دقیق مشخص می‌شود.', 'pixva' ), 1, array(), 'panel', 0 ),
		),
		'safe'     => array(
			__( 'در اتاق تاریک، چراغ‌قوه گوشی را از فاصله نزدیک به صفحه بتابانید و ببینید تصویر کم‌رنگی دیده می‌شود یا نه.', 'pixva' ),
			__( 'ورودی (Source) را عوض کنید و یک دستگاه دیگر را با کابل HDMI دیگری امتحان کنید.', 'pixva' ),
			__( 'حالت صرفه‌جویی انرژی یا «خاموش کردن صفحه» را در تنظیمات غیرفعال کنید.', 'pixva' ),
		),
	),
	'lines'      => array(
		'label'    => __( 'خطوط عمودی یا افقی روی تصویر', 'pixva' ),
		'desc'     => __( 'خط‌های رنگی، سیاه یا سفید ثابت روی صفحه.', 'pixva' ),
		'symptoms' => array(
			'lines_in_menu'   => __( 'خطوط روی منوی خود تلویزیون هم دیده می‌شود', 'pixva' ),
			'lines_one_input' => __( 'خطوط فقط روی یک ورودی یا یک دستگاه دیده می‌شود', 'pixva' ),
			'temp_change'     => __( 'با گرم شدن دستگاه، خطوط کم یا زیاد می‌شود', 'pixva' ),
			'half_screen'     => __( 'نیمی از صفحه تیره یا تصویر تکراری است', 'pixva' ),
			'after_impact'    => __( 'بعد از ضربه یا جابه‌جایی شروع شد', 'pixva' ),
		),
		'causes'   => array(
			'bonding' => array(
				__( 'جداشدن اتصال فلت پنل (نیازمند باندینگ)', 'pixva' ),
				__( 'شایع‌ترین علت خطوط ثابت؛ اتصال‌های لبه پنل با گرما و زمان ضعیف می‌شوند.', 'pixva' ),
				3,
				array(
					'temp_change'   => 3,
					'lines_in_menu' => 1,
				),
				'panel',
				1,
			),
			'tcon'    => array(
				__( 'برد تی‌کان', 'pixva' ),
				__( 'به‌خصوص وقتی نیمی از صفحه ایراد دارد.', 'pixva' ),
				2,
				array(
					'half_screen'   => 3,
					'lines_in_menu' => 1,
				),
				'mainboard',
				0,
			),
			'damage'  => array( __( 'آسیب فیزیکی پنل', 'pixva' ), __( 'ضربه می‌تواند لایه‌های پنل را بشکند؛ تعمیر همیشه به‌صرفه نیست.', 'pixva' ), 1, array( 'after_impact' => 5 ), 'panel', 0 ),
			'signal'  => array( __( 'سیگنال یا کابل ورودی', 'pixva' ), __( 'اگر خطوط فقط روی یک ورودی است، تلویزیون احتمالاً سالم است.', 'pixva' ), 1, array( 'lines_one_input' => 6 ), '', 0 ),
		),
		'safe'     => array(
			__( 'منوی تنظیمات تلویزیون را باز کنید: اگر خطوط روی منو هم هست، ایراد از خود تلویزیون است.', 'pixva' ),
			__( 'یک ورودی یا کابل دیگر را امتحان کنید.', 'pixva' ),
			__( 'اگر تلویزیون «تست تصویر» داخلی دارد (در منوی پشتیبانی)، آن را اجرا کنید.', 'pixva' ),
			__( 'روی صفحه فشار نیاورید؛ فشار می‌تواند آسیب را بیشتر کند.', 'pixva' ),
		),
	),
	'dim_dark'   => array(
		'label'    => __( 'تصویر تیره، کم‌نور یا نور ناهماهنگ', 'pixva' ),
		'desc'     => __( 'روشنایی کم شده یا بخش‌هایی از صفحه تیره‌تر است.', 'pixva' ),
		'symptoms' => array(
			'dark_patches'   => __( 'لکه‌های تیره یا روشن‌تر در بخشی از صفحه', 'pixva' ),
			'color_cast'     => __( 'ته‌رنگ آبی یا بنفش روی تصویر', 'pixva' ),
			'flicker'        => __( 'نور صفحه سوسو می‌زند', 'pixva' ),
			'dims_over_time' => __( 'پس از چند دقیقه کم‌نور می‌شود', 'pixva' ),
		),
		'causes'   => array(
			'backlight' => array(
				__( 'فرسودگی یا خرابی بخشی از بک‌لایت', 'pixva' ),
				__( 'لامپ‌های LED با فرسودگی کم‌نور و آبی‌رنگ می‌شوند یا بخشی از ردیف‌ها خاموش می‌شود.', 'pixva' ),
				3,
				array(
					'dark_patches'   => 2,
					'color_cast'     => 3,
					'dims_over_time' => 1,
				),
				'backlight',
				2,
			),
			'power'     => array(
				__( 'تغذیه بک‌لایت روی برد پاور', 'pixva' ),
				__( 'سوسو زدن معمولاً از ناپایداری تغذیه است.', 'pixva' ),
				1,
				array(
					'flicker'        => 3,
					'dims_over_time' => 1,
				),
				'powerboard',
				0,
			),
			'settings'  => array( __( 'تنظیمات تصویر یا حسگر نور محیط', 'pixva' ), __( 'حالت صرفه‌جویی و حسگر نور می‌توانند روشنایی را کم کنند.', 'pixva' ), 1, array(), '', 0 ),
		),
		'safe'     => array(
			__( 'تنظیمات تصویر را به حالت پیش‌فرض برگردانید.', 'pixva' ),
			__( 'صرفه‌جویی انرژی و حسگر نور محیط را خاموش کنید.', 'pixva' ),
			__( 'با ابزار تست پیکسل، صفحه سفید و خاکستری را نگاه کنید تا محل لکه‌ها مشخص شود.', 'pixva' ),
		),
	),
	'blink'      => array(
		'label'    => __( 'چراغ پاور چشمک می‌زند و روشن نمی‌شود', 'pixva' ),
		'desc'     => __( 'دستگاه وارد حالت محافظت شده است.', 'pixva' ),
		'symptoms' => array(
			'blink_pattern' => __( 'تعداد چشمک‌ها هر بار یکسان است', 'pixva' ),
			'relay_click'   => __( 'صدای کلیک تکرار می‌شود', 'pixva' ),
			'after_surge'   => __( 'بعد از نوسان برق شروع شد', 'pixva' ),
			'brief_picture' => __( 'تصویر یا لوگو لحظه‌ای دیده می‌شود و خاموش می‌شود', 'pixva' ),
		),
		'causes'   => array(
			'power_board' => array(
				__( 'برد پاور', 'pixva' ),
				__( 'محافظ دستگاه به‌خاطر ولتاژ نامناسب فعال شده است.', 'pixva' ),
				2,
				array(
					'after_surge' => 2,
					'relay_click' => 1,
				),
				'powerboard',
				1,
			),
			'backlight'   => array(
				__( 'قطعی در مسیر بک‌لایت', 'pixva' ),
				__( 'در بسیاری از مدل‌ها، قطعی LED باعث خاموشی محافظتی می‌شود.', 'pixva' ),
				2,
				array(
					'brief_picture' => 3,
					'blink_pattern' => 1,
				),
				'backlight',
				1,
			),
			'mainboard'   => array( __( 'برد اصلی', 'pixva' ), __( 'الگوی چشمک معمولاً کد خطای برد را نشان می‌دهد.', 'pixva' ), 2, array( 'blink_pattern' => 1 ), 'mainboard', 0 ),
		),
		'safe'     => array(
			__( 'تعداد چشمک‌ها را بشمارید و در «کدهای خطا» برای برند خود جست‌وجو کنید.', 'pixva' ),
			__( 'دوشاخه را یک دقیقه از برق بکشید و مستقیم به پریز دیواری وصل کنید.', 'pixva' ),
		),
	),
	'no_sound'   => array(
		'label'    => __( 'تصویر دارد ولی صدا ندارد یا صدا خراب است', 'pixva' ),
		'desc'     => __( 'صدا قطع، ضعیف، خش‌دار یا بریده‌بریده است.', 'pixva' ),
		'symptoms' => array(
			'all_inputs'     => __( 'روی همه ورودی‌ها و برنامه‌ها صدا نیست', 'pixva' ),
			'external_works' => __( 'با هدفون یا اسپیکر بیرونی صدا دارد', 'pixva' ),
			'distorted'      => __( 'صدا خش‌دار یا گرفته است', 'pixva' ),
		),
		'causes'   => array(
			'settings' => array( __( 'تنظیمات خروجی صدا', 'pixva' ), __( 'خروجی ممکن است روی ساندبار، ARC یا بی‌صدا مانده باشد.', 'pixva' ), 2, array(), '', 0 ),
			'amp'      => array( __( 'آمپلی‌فایر صدا روی برد اصلی', 'pixva' ), __( 'وقتی روی همه ورودی‌ها صدا نیست.', 'pixva' ), 2, array( 'all_inputs' => 2 ), 'mainboard', 0 ),
			'speakers' => array(
				__( 'بلندگوها یا اتصال آن‌ها', 'pixva' ),
				__( 'اگر خروجی بیرونی سالم است، بلندگوی داخلی مشکوک است.', 'pixva' ),
				1,
				array(
					'distorted'      => 3,
					'external_works' => 3,
				),
				'',
				0,
			),
		),
		'safe'     => array(
			__( 'دکمه بی‌صدا و میزان صدا را بررسی کنید.', 'pixva' ),
			__( 'در تنظیمات صدا، خروجی را روی «بلندگوی تلویزیون» بگذارید و ساندبار یا کابل نوری را جدا کنید.', 'pixva' ),
			__( 'یک ورودی یا برنامه دیگر را امتحان کنید.', 'pixva' ),
		),
	),
	'spots'      => array(
		'label'    => __( 'لکه، نقطه یا پیکسل معیوب', 'pixva' ),
		'desc'     => __( 'نقاط رنگی ثابت، لکه روشن یا هاله روی صفحه.', 'pixva' ),
		'symptoms' => array(
			'fixed_dots'     => __( 'نقطه‌های کوچک ثابت سیاه یا رنگی', 'pixva' ),
			'bright_spots'   => __( 'نقطه یا هاله روشن، به‌ویژه در تصویر تیره', 'pixva' ),
			'spreading'      => __( 'لکه در حال بزرگ‌تر شدن است', 'pixva' ),
			'after_pressure' => __( 'بعد از فشار یا ضربه دیده شد', 'pixva' ),
		),
		'causes'   => array(
			'pixel'  => array( __( 'پیکسل سوخته یا گیرکرده', 'pixva' ), __( 'ایراد خود پنل؛ پیکسل گیرکرده گاهی خودبه‌خود برطرف می‌شود.', 'pixva' ), 2, array( 'fixed_dots' => 3 ), 'panel', 0 ),
			'lens'   => array( __( 'جابه‌جایی یا آسیب لنز بک‌لایت', 'pixva' ), __( 'هاله‌های روشن دایره‌ای معمولاً از لنزهای پشت پنل است.', 'pixva' ), 1, array( 'bright_spots' => 4 ), 'backlight', 1 ),
			'damage' => array(
				__( 'آسیب لایه‌های پنل', 'pixva' ),
				__( 'لکه رو به گسترش یا پس از ضربه نشانه آسیب فیزیکی است.', 'pixva' ),
				1,
				array(
					'spreading'      => 3,
					'after_pressure' => 3,
				),
				'panel',
				0,
			),
		),
		'safe'     => array(
			__( 'با ابزار تست پیکسل، رنگ‌های تک را تمام‌صفحه ببینید تا نوع و محل نقاط مشخص شود.', 'pixva' ),
			__( 'صفحه را فقط با دستمال میکروفایبر خشک تمیز کنید.', 'pixva' ),
		),
	),
	'water'      => array(
		'label'         => __( 'آب یا مایع روی دستگاه ریخته', 'pixva' ),
		'desc'          => __( 'نفوذ مایع به داخل تلویزیون.', 'pixva' ),
		'always_danger' => true,
		'symptoms'      => array(
			'still_on'     => __( 'دستگاه هنوز روشن می‌شود', 'pixva' ),
			'smell'        => __( 'بو یا دود حس شد', 'pixva' ),
			'screen_stain' => __( 'لکه یا تغییر رنگ روی صفحه', 'pixva' ),
		),
		'danger'        => array( 'smell' ),
		'causes'        => array(
			'corrosion' => array( __( 'اکسید شدن یا اتصال کوتاه بردها', 'pixva' ), __( 'رطوبت حتی اگر دستگاه روشن شود، به‌مرور بردها را خراب می‌کند.', 'pixva' ), 3, array( 'still_on' => 1 ), 'water', 0 ),
			'panel'     => array( __( 'نفوذ مایع به لایه‌های پنل', 'pixva' ), __( 'لکه روی صفحه یعنی مایع بین لایه‌ها رسیده است.', 'pixva' ), 1, array( 'screen_stain' => 4 ), 'panel', 0 ),
			'power'     => array( __( 'آسیب برد پاور', 'pixva' ), __( 'بو یا دود نشانه اتصال کوتاه در تغذیه است.', 'pixva' ), 1, array( 'smell' => 3 ), 'powerboard', 0 ),
		),
		'safe'          => array(
			__( 'فوراً دوشاخه را بکشید و دستگاه را روشن نکنید.', 'pixva' ),
			__( 'از سشوار یا بخاری برای خشک کردن استفاده نکنید.', 'pixva' ),
			__( 'دستگاه را ایستاده نگه دارید و هرچه زودتر برای بررسی اقدام کنید.', 'pixva' ),
		),
	),
	'physical'   => array(
		'label'    => __( 'شکستگی یا ضربه به صفحه', 'pixva' ),
		'desc'     => __( 'صفحه ترک خورده یا پس از ضربه تصویر خراب شده است.', 'pixva' ),
		'symptoms' => array(
			'visible_crack' => __( 'ترک یا شکستگی دیده می‌شود', 'pixva' ),
			'ink_blot'      => __( 'لکه‌های جوهری یا خطوط رنگی پراکنده', 'pixva' ),
		),
		'danger'   => array( 'visible_crack' ),
		'causes'   => array(
			'panel_damage' => array(
				__( 'آسیب پنل', 'pixva' ),
				__( 'پنل شکسته تعمیر نمی‌شود و باید تعویض شود؛ صرفه اقتصادی آن پس از بررسی و استعلام قطعه مشخص می‌شود.', 'pixva' ),
				5,
				array(
					'visible_crack' => 2,
					'ink_blot'      => 2,
				),
				'panel',
				0,
			),
		),
		'safe'     => array(
			__( 'اگر شیشه یا صفحه ترک دارد، دستگاه را روشن نکنید.', 'pixva' ),
			__( 'تلویزیون را ایستاده جابه‌جا کنید و روی صفحه فشار نیاورید.', 'pixva' ),
		),
	),
	'restart'    => array(
		'label'    => __( 'خودبه‌خود خاموش و روشن می‌شود', 'pixva' ),
		'desc'     => __( 'روی لوگو گیر می‌کند یا در حین تماشا خاموش می‌شود.', 'pixva' ),
		'symptoms' => array(
			'logo_loop'    => __( 'روی لوگو می‌ماند و دوباره راه‌اندازی می‌شود', 'pixva' ),
			'random_off'   => __( 'در حین تماشا خاموش می‌شود', 'pixva' ),
			'after_update' => __( 'بعد از به‌روزرسانی نرم‌افزار شروع شد', 'pixva' ),
		),
		'causes'   => array(
			'firmware' => array(
				__( 'نرم‌افزار یا حافظه برد اصلی', 'pixva' ),
				__( 'گیر کردن روی لوگو معمولاً از حافظه یا نرم‌افزار است.', 'pixva' ),
				2,
				array(
					'logo_loop'    => 3,
					'after_update' => 3,
				),
				'mainboard',
				0,
			),
			'power'    => array( __( 'ناپایداری برد پاور', 'pixva' ), __( 'خاموشی ناگهانی هنگام کار.', 'pixva' ), 2, array( 'random_off' => 2 ), 'powerboard', 1 ),
			'settings' => array( __( 'تایمر خواب، خاموشی خودکار یا HDMI-CEC', 'pixva' ), __( 'برخی تنظیمات یا دستگاه‌های متصل تلویزیون را خاموش می‌کنند.', 'pixva' ), 1, array( 'random_off' => 1 ), '', 0 ),
		),
		'safe'     => array(
			__( 'تایمر خواب و خاموشی خودکار را در تنظیمات بررسی کنید.', 'pixva' ),
			__( 'همه دستگاه‌های HDMI و فلش USB را جدا کنید و دوباره امتحان کنید.', 'pixva' ),
			__( 'اگر منو باز می‌شود، از مسیر رسمی تنظیمات نرم‌افزار را به‌روزرسانی کنید.', 'pixva' ),
		),
	),
	'smart'      => array(
		'label'    => __( 'ایراد نرم‌افزار، اینترنت یا برنامه‌ها', 'pixva' ),
		'desc'     => __( 'وای‌فای وصل نمی‌شود، برنامه‌ها بسته می‌شوند یا کند است.', 'pixva' ),
		'symptoms' => array(
			'no_wifi'    => __( 'به وای‌فای وصل نمی‌شود یا شبکه‌ای پیدا نمی‌کند', 'pixva' ),
			'apps_crash' => __( 'برنامه‌ها بسته می‌شوند', 'pixva' ),
			'slow'       => __( 'منو و برنامه‌ها بسیار کند هستند', 'pixva' ),
		),
		'causes'   => array(
			'software' => array( __( 'نرم‌افزار سیستم یا برنامه‌ها', 'pixva' ), __( 'اغلب با به‌روزرسانی یا بازنشانی برطرف می‌شود.', 'pixva' ), 3, array( 'apps_crash' => 1 ), '', 0 ),
			'network'  => array( __( 'تنظیمات شبکه یا مودم', 'pixva' ), __( 'ابتدا مودم و تنظیمات شبکه بررسی شود.', 'pixva' ), 1, array( 'no_wifi' => 3 ), '', 0 ),
			'storage'  => array(
				__( 'حافظه داخلی پر یا فرسوده', 'pixva' ),
				__( 'کندی شدید و بسته شدن برنامه‌ها نشانه ضعف حافظه است.', 'pixva' ),
				1,
				array(
					'slow'       => 2,
					'apps_crash' => 1,
				),
				'mainboard',
				1,
			),
			'wifi_hw'  => array( __( 'ماژول وای‌فای', 'pixva' ), __( 'اگر هیچ شبکه‌ای دیده نمی‌شود.', 'pixva' ), 1, array( 'no_wifi' => 1 ), 'mainboard', 0 ),
		),
		'safe'     => array(
			__( 'مودم و تلویزیون را خاموش و روشن کنید.', 'pixva' ),
			__( 'حافظه موقت برنامه را پاک یا برنامه را دوباره نصب کنید.', 'pixva' ),
			__( 'نرم‌افزار را از مسیر رسمی به‌روزرسانی کنید. بازنشانی کارخانه همه تنظیمات را پاک می‌کند؛ آخرین راه است.', 'pixva' ),
		),
	),
	'remote'     => array(
		'label'    => __( 'کنترل یا دکمه‌ها کار نمی‌کند', 'pixva' ),
		'desc'     => __( 'دستگاه به ریموت یا دکمه‌ها پاسخ نمی‌دهد.', 'pixva' ),
		'symptoms' => array(
			'buttons_work' => __( 'دکمه‌های روی خود تلویزیون کار می‌کند', 'pixva' ),
			'no_ir'        => __( 'نور ریموت در دوربین گوشی دیده نمی‌شود', 'pixva' ),
		),
		'causes'   => array(
			'remote'   => array(
				__( 'ریموت یا باتری', 'pixva' ),
				__( 'شایع‌ترین علت؛ ارزان و ساده.', 'pixva' ),
				2,
				array(
					'buttons_work' => 2,
					'no_ir'        => 3,
				),
				'',
				0,
			),
			'receiver' => array( __( 'گیرنده مادون‌قرمز تلویزیون', 'pixva' ), __( 'وقتی ریموت سالم است ولی تلویزیون فرمان نمی‌گیرد.', 'pixva' ), 1, array( 'buttons_work' => 1 ), 'mainboard', 0 ),
		),
		'safe'     => array(
			__( 'باتری‌ها را عوض کنید.', 'pixva' ),
			__( 'دوربین گوشی را جلوی سر ریموت بگیرید و دکمه‌ای را بزنید: اگر نوری دیده نشود، ریموت ایراد دارد.', 'pixva' ),
			__( 'جلوی گیرنده تلویزیون را از هر مانعی خالی کنید.', 'pixva' ),
		),
	),
);

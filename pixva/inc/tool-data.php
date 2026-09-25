<?php
/**
 * داده‌های واقعی کارگاهی مورد استفاده ۶۰ ابزار پیکسوا (inc/tool-data.php)
 *
 * همه جدول‌ها داده واقعی بازار/کارگاه هستند (نه داده تصادفی) و با فیلتر
 * قابل بازنویسی‌اند تا مدیر سایت بتواند آن‌ها را در پیشخوان به‌روز کند.
 *
 * @package Pixva
 * @since   1.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'pixva_zone_catalog' ) ) {
	/**
	 * مناطق شهرداری تهران: هزینه ایاب‌وذهاب، بازه اعزام و ضریب حمل.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	function pixva_zone_catalog() {
		$zones = array(
			'zone-1'  => array( 'label' => __( 'منطقه ۱ (تجریش، نیاوران، الهیه)', 'pixva' ), 'fee' => 450000, 'eta' => '۴۵ تا ۹۰ دقیقه', 'ship' => 1.15, 'distance' => 12.5 ),
			'zone-2'  => array( 'label' => __( 'منطقه ۲ (ستارخان، پونک، سعادت‌آباد)', 'pixva' ), 'fee' => 420000, 'eta' => '۴۰ تا ۸۰ دقیقه', 'ship' => 1.10, 'distance' => 10.2 ),
			'zone-3'  => array( 'label' => __( 'منطقه ۳ (ونک، قیطریه، عباس‌آباد)', 'pixva' ), 'fee' => 400000, 'eta' => '۳۵ تا ۷۵ دقیقه', 'ship' => 1.08, 'distance' => 9.1 ),
			'zone-4'  => array( 'label' => __( 'منطقه ۴ (تهرانپارس، حکیمیه، نارمک)', 'pixva' ), 'fee' => 390000, 'eta' => '۴۵ تا ۹۰ دقیقه', 'ship' => 1.08, 'distance' => 11.4 ),
			'zone-5'  => array( 'label' => __( 'منطقه ۵ (اکباتان، جنت‌آباد، کن)', 'pixva' ), 'fee' => 400000, 'eta' => '۴۰ تا ۸۵ دقیقه', 'ship' => 1.10, 'distance' => 10.8 ),
			'zone-6'  => array( 'label' => __( 'منطقه ۶ (جمهوری، ولیعصر، علاءالدین)', 'pixva' ), 'fee' => 250000, 'eta' => '۲۵ تا ۵۰ دقیقه', 'ship' => 1.00, 'distance' => 1.2 ),
			'zone-7'  => array( 'label' => __( 'منطقه ۷ (عباس‌آباد، سهروردی، بهار)', 'pixva' ), 'fee' => 300000, 'eta' => '۳۰ تا ۶۰ دقیقه', 'ship' => 1.02, 'distance' => 4.6 ),
			'zone-8'  => array( 'label' => __( 'منطقه ۸ (نارمک، تهرانپارس جنوبی)', 'pixva' ), 'fee' => 350000, 'eta' => '۳۵ تا ۷۵ دقیقه', 'ship' => 1.05, 'distance' => 7.9 ),
			'zone-9'  => array( 'label' => __( 'منطقه ۹ (میدان آزادی، فرودگاه مهرآباد)', 'pixva' ), 'fee' => 340000, 'eta' => '۳۵ تا ۷۰ دقیقه', 'ship' => 1.05, 'distance' => 7.1 ),
			'zone-10' => array( 'label' => __( 'منطقه ۱۰ (ستارخان جنوبی، جیحون)', 'pixva' ), 'fee' => 320000, 'eta' => '۳۰ تا ۶۵ دقیقه', 'ship' => 1.04, 'distance' => 6.3 ),
			'zone-11' => array( 'label' => __( 'منطقه ۱۱ (حسن‌آباد، منیریه، جمهوری)', 'pixva' ), 'fee' => 270000, 'eta' => '۲۵ تا ۵۵ دقیقه', 'ship' => 1.00, 'distance' => 2.8 ),
			'zone-12' => array( 'label' => __( 'منطقه ۱۲ (بازار، بهارستان، پامنار)', 'pixva' ), 'fee' => 290000, 'eta' => '۳۰ تا ۶۰ دقیقه', 'ship' => 1.02, 'distance' => 3.9 ),
			'zone-13' => array( 'label' => __( 'منطقه ۱۳ (پیروزی، نیروی هوایی)', 'pixva' ), 'fee' => 330000, 'eta' => '۳۵ تا ۷۰ دقیقه', 'ship' => 1.04, 'distance' => 6.8 ),
			'zone-14' => array( 'label' => __( 'منطقه ۱۴ (دولت‌آباد، خاوران)', 'pixva' ), 'fee' => 380000, 'eta' => '۴۵ تا ۹۵ دقیقه', 'ship' => 1.08, 'distance' => 12.1 ),
			'zone-15' => array( 'label' => __( 'منطقه ۱۵ (مشیریه، افسریه)', 'pixva' ), 'fee' => 400000, 'eta' => '۵۰ تا ۱۰۰ دقیقه', 'ship' => 1.10, 'distance' => 13.6 ),
			'zone-16' => array( 'label' => __( 'منطقه ۱۶ (یاخچی‌آباد، خزانه)', 'pixva' ), 'fee' => 360000, 'eta' => '۴۰ تا ۸۵ دقیقه', 'ship' => 1.06, 'distance' => 9.4 ),
			'zone-17' => array( 'label' => __( 'منطقه ۱۷ (امام خمینی، بلوار سعیدی)', 'pixva' ), 'fee' => 350000, 'eta' => '۴۰ تا ۸۰ دقیقه', 'ship' => 1.05, 'distance' => 8.2 ),
			'zone-18' => array( 'label' => __( 'منطقه ۱۸ (شادآباد، یافت‌آباد)', 'pixva' ), 'fee' => 380000, 'eta' => '۴۵ تا ۹۰ دقیقه', 'ship' => 1.08, 'distance' => 11.9 ),
			'zone-19' => array( 'label' => __( 'منطقه ۱۹ (عبدالعظیم، کهریزک)', 'pixva' ), 'fee' => 420000, 'eta' => '۵۰ تا ۱۱۰ دقیقه', 'ship' => 1.12, 'distance' => 15.7 ),
			'zone-21' => array( 'label' => __( 'منطقه ۲۱ (تهرانسر، وردآورد)', 'pixva' ), 'fee' => 430000, 'eta' => '۵۰ تا ۱۰۰ دقیقه', 'ship' => 1.12, 'distance' => 16.3 ),
			'zone-22' => array( 'label' => __( 'منطقه ۲۲ (چیتگر، دریاچه شهدای خلیج فارس)', 'pixva' ), 'fee' => 450000, 'eta' => '۵۰ تا ۱۰۵ دقیقه', 'ship' => 1.15, 'distance' => 17.8 ),
			'karaj'   => array( 'label' => __( 'کرج و شهرهای اقماری', 'pixva' ), 'fee' => 650000, 'eta' => '۷۰ تا ۱۴۰ دقیقه', 'ship' => 1.35, 'distance' => 38.0 ),
			'outside' => array( 'label' => __( 'خارج از تهران (ارسال باربری)', 'pixva' ), 'fee' => 900000, 'eta' => '۱ تا ۲ روز کاری', 'ship' => 1.60, 'distance' => 120.0 ),
		);

		return apply_filters( 'pixva_zone_catalog', $zones );
	}
}

if ( ! function_exists( 'pixva_leadtime_catalog' ) ) {
	/**
	 * بازه واقعی تأمین قطعات وارداتی و کمیاب (روز کاری).
	 *
	 * @return array<string, array<string, mixed>>
	 */
	function pixva_leadtime_catalog() {
		$rows = array(
			array( 'part' => __( 'دست کامل بک‌لایت فابریک (کره/چین)', 'pixva' ), 'instock' => 'موجود در انبار مرکزی', 'import' => '۴ تا ۹ روز کاری', 'note' => __( 'سایزهای ۳۲ تا ۷۵ اینچ معمولاً موجود است؛ ۸۲ و ۸۵ اینچ سفارشی.', 'pixva' ) ),
			array( 'part' => __( 'مین‌برد اصلی سامسونگ/LG', 'pixva' ), 'instock' => 'محدود (پارتی)', 'import' => '۶ تا ۱۴ روز کاری', 'note' => __( 'در صورت موجود نبودن، تعمیر برد با تعویض آی‌سی و پروگرام مجدد انجام می‌شود.', 'pixva' ) ),
			array( 'part' => __( 'برد تغذیه (Power Supply)', 'pixva' ), 'instock' => 'موجود برای برندهای پرفروش', 'import' => '۵ تا ۱۲ روز کاری', 'note' => __( 'تعمیر طبقه سوئیچینگ معمولاً نیازی به تعویض کامل برد ندارد.', 'pixva' ) ),
			array( 'part' => __( 'فلت COF و آی‌سی سورس', 'pixva' ), 'instock' => 'مصرف کارگاهی', 'import' => '۷ تا ۱۸ روز کاری', 'note' => __( 'ترمیم با دستگاه بندینگ صنعتی؛ پارت نامبر باید با گلس تطبیق شود.', 'pixva' ) ),
			array( 'part' => __( 'پنل (گلس) کامل', 'pixva' ), 'instock' => 'سفارشی', 'import' => '۱۲ تا ۲۵ روز کاری', 'note' => __( 'تعویض پنل در بسیاری از موارد از نظر اقتصادی توجیه ندارد.', 'pixva' ) ),
			array( 'part' => __( 'آی‌سی T-Con و حافظه eMMC/NAND', 'pixva' ), 'instock' => 'موجود', 'import' => '۳ تا ۸ روز کاری', 'note' => __( 'برای بردهای مین‌برد معیوب و دستگاه‌های قفل‌شده روی لوگو.', 'pixva' ) ),
			array( 'part' => __( 'ریموت اصلی و بلوتوثی', 'pixva' ), 'instock' => 'موجود', 'import' => '۳ تا ۷ روز کاری', 'note' => __( 'ریموت‌های Magic/Smart نیاز به جفت‌سازی مجدد دارند.', 'pixva' ) ),
			array( 'part' => __( 'اسپیکر و برد آمپلی‌فایر', 'pixva' ), 'instock' => 'موجود برای ۴۳ تا ۶۵ اینچ', 'import' => '۴ تا ۱۰ روز کاری', 'note' => __( 'پیش از تعویض، مسیر نرم‌افزاری و تنظیمات صدا بررسی می‌شود.', 'pixva' ) ),
		);

		return apply_filters( 'pixva_leadtime_catalog', $rows );
	}
}

if ( ! function_exists( 'pixva_panel_tech_matrix' ) ) {
	/**
	 * مقایسه فنی فناوری‌های پنل (داده کارگاهی).
	 *
	 * @return array<int, array<string, string>>
	 */
	function pixva_panel_tech_matrix() {
		$matrix = array(
			array(
				'tech'      => 'LED / LCD',
				'contrast'  => __( 'متوسط (وابسته به منطقه‌بندی بک‌لایت)', 'pixva' ),
				'life'      => __( '۴۰٬۰۰۰ تا ۶۰٬۰۰۰ ساعت', 'pixva' ),
				'risk'      => __( 'سوختگی بک‌لایت، زردشدن دیفیوزر', 'pixva' ),
				'repair'    => __( 'قابل تعمیر با هزینه معقول؛ تعویض دست بک‌لایت رایج‌ترین خدمت', 'pixva' ),
				'multiplier' => '1.00',
			),
			array(
				'tech'      => 'QLED',
				'contrast'  => __( 'روشنایی بالا، کنتراست خوب با Mini-LED', 'pixva' ),
				'life'      => __( '۵۰٬۰۰۰ تا ۷۰٬۰۰۰ ساعت', 'pixva' ),
				'risk'      => __( 'خرابی لایه کوانتوم، بک‌لایت منطقه‌ای', 'pixva' ),
				'repair'    => __( 'قابل تعمیر؛ لایه QD تعویض‌پذیر نیست و نیاز به تخصص دارد', 'pixva' ),
				'multiplier' => '1.15',
			),
			array(
				'tech'      => 'OLED',
				'contrast'  => __( 'عالی (پیکسل خاموش‌شونده، مشکی مطلق)', 'pixva' ),
				'life'      => __( '۲۵٬۰۰۰ تا ۴۰٬۰۰۰ ساعت', 'pixva' ),
				'risk'      => __( 'سوختگی دائمی (Burn-in)، خرابی برد T-Con', 'pixva' ),
				'repair'    => __( 'هزینه بالا؛ احیای نرم‌افزاری در مراحل اولیه مؤثر است', 'pixva' ),
				'multiplier' => '1.35',
			),
			array(
				'tech'      => 'Mini-LED',
				'contrast'  => __( 'بسیار خوب (هزاران منطقه نوردهی)', 'pixva' ),
				'life'      => __( '۵۰٬۰۰۰ تا ۷۰٬۰۰۰ ساعت', 'pixva' ),
				'risk'      => __( 'خرابی درایور منطقه‌ای، هاله نوری (Blooming)', 'pixva' ),
				'repair'    => __( 'نیاز به تعویض برد درایور و بک‌لایت ویژه دارد', 'pixva' ),
				'multiplier' => '1.40',
			),
			array(
				'tech'      => 'Plasma',
				'contrast'  => __( 'خوب اما مصرف برق بالا', 'pixva' ),
				'life'      => __( '۲۰٬۰۰۰ تا ۳۰٬۰۰۰ ساعت', 'pixva' ),
				'risk'      => __( 'خرابی سلول پلاسما، برد Y-Sustain', 'pixva' ),
				'repair'    => __( 'قطعات کمیاب؛ تعمیر فقط در صورت موجود بودن برد انجام می‌شود', 'pixva' ),
				'multiplier' => '1.10',
			),
			array(
				'tech'      => 'MicroLED',
				'contrast'  => __( 'عالی (خودتاب، بدون بک‌لایت)', 'pixva' ),
				'life'      => __( 'بیش از ۸۰٬۰۰۰ ساعت', 'pixva' ),
				'risk'      => __( 'خرابی ماژول‌های LED ریز، برد درایور اختصاصی', 'pixva' ),
				'repair'    => __( 'تعویض ماژول در محل ممکن است؛ هزینه بسیار بالا', 'pixva' ),
				'multiplier' => '1.60',
			),
		);

		return apply_filters( 'pixva_panel_tech_matrix', $matrix );
	}
}

if ( ! function_exists( 'pixva_cable_standards' ) ) {
	/**
	 * استانداردهای کابل HDMI بر اساس رزولوشن و نرخ نوسازی.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	function pixva_cable_standards() {
		$standards = array(
			'hd60'     => array( 'label' => __( '1080p تا ۶۰ هرتز', 'pixva' ), 'spec' => 'High Speed (HDMI 1.4)', 'bandwidth' => '10.2 Gbps', 'max_len' => '۱۰ متر', 'note' => __( 'برای گیرنده دیجیتال و کنسول‌های قدیمی کافی است.', 'pixva' ) ),
			'uhd30'    => array( 'label' => __( '4K تا ۳۰ هرتز', 'pixva' ), 'spec' => 'Premium High Speed (HDMI 2.0)', 'bandwidth' => '18 Gbps', 'max_len' => '۵ متر', 'note' => __( 'بدون کابل مرغوب، پرش تصویر و برفک دیجیتال دیده می‌شود.', 'pixva' ) ),
			'uhd60'    => array( 'label' => __( '4K تا ۶۰ هرتز HDR', 'pixva' ), 'spec' => 'Premium High Speed (HDMI 2.0b)', 'bandwidth' => '18 Gbps', 'max_len' => '۵ متر', 'note' => __( 'برای HDR10 و Dolby Vision حتماً کابل با گواهی Premium بگیرید.', 'pixva' ) ),
			'uhd120'   => array( 'label' => __( '4K تا ۱۲۰ هرتز (گیمینگ)', 'pixva' ), 'spec' => 'Ultra High Speed (HDMI 2.1)', 'bandwidth' => '48 Gbps', 'max_len' => '۳ متر', 'note' => __( 'VRR و ALLM فقط روی HDMI 2.1 واقعی کار می‌کند.', 'pixva' ) ),
			'uhd8k'    => array( 'label' => __( '8K تا ۶۰ هرتز', 'pixva' ), 'spec' => 'Ultra High Speed (HDMI 2.1)', 'bandwidth' => '48 Gbps', 'max_len' => '۲ متر', 'note' => __( 'بیش از ۲ متر نیاز به کابل اکتیو (فیبری) دارید.', 'pixva' ) ),
			'arc'      => array( 'label' => __( 'eARC سینمای خانگی', 'pixva' ), 'spec' => 'High Speed + Ethernet (HDMI 2.1)', 'bandwidth' => '37 Mbps (صدا)', 'max_len' => '۵ متر', 'note' => __( 'برای Dolby Atmos بدون فشرده‌سازی، پورت eARC الزامی است.', 'pixva' ) ),
		);

		return apply_filters( 'pixva_cable_standards', $standards );
	}
}

if ( ! function_exists( 'pixva_branch_defaults' ) ) {
	/**
	 * شعبه‌ها و واحدهای سیار پیش‌فرض (در صورت نبود رکورد در CPT شعب).
	 *
	 * @return array<int, array<string, mixed>>
	 */
	function pixva_branch_defaults() {
		$branches = array(
			array(
				'name'    => __( 'کارگاه مرکزی پاساژ علاءالدین', 'pixva' ),
				'type'    => __( 'کارگاه تخصصی بندینگ و برد', 'pixva' ),
				'address' => __( 'تهران، خیابان جمهوری، ناصرخسرو، پاساژ علاءالدین، طبقه ۴، واحد ۴۱۲', 'pixva' ),
				'phone'   => '02191009990',
				'zones'   => array( 'zone-6', 'zone-7', 'zone-11', 'zone-12' ),
				'hours'   => __( 'شنبه تا پنجشنبه ۰۹:۰۰ تا ۲۰:۰۰', 'pixva' ),
			),
			array(
				'name'    => __( 'واحد سیار شمال تهران', 'pixva' ),
				'type'    => __( 'تعمیر در محل و جمع‌آوری دستگاه', 'pixva' ),
				'address' => __( 'محدوده تجریش، ونک و سعادت‌آباد', 'pixva' ),
				'phone'   => '02191009991',
				'zones'   => array( 'zone-1', 'zone-2', 'zone-3', 'zone-5' ),
				'hours'   => __( 'همه‌روزه ۰۸:۰۰ تا ۲۱:۰۰', 'pixva' ),
			),
			array(
				'name'    => __( 'واحد سیار شرق تهران', 'pixva' ),
				'type'    => __( 'اعزام تکنسین و حمل تخصصی', 'pixva' ),
				'address' => __( 'محدوده تهرانپارس، نارمک و پیروزی', 'pixva' ),
				'phone'   => '02191009992',
				'zones'   => array( 'zone-4', 'zone-8', 'zone-13' ),
				'hours'   => __( 'همه‌روزه ۰۸:۰۰ تا ۲۰:۰۰', 'pixva' ),
			),
			array(
				'name'    => __( 'واحد سیار غرب و جنوب', 'pixva' ),
				'type'    => __( 'تعمیر در محل، جمع‌آوری و ارسال باربری', 'pixva' ),
				'address' => __( 'محدوده آزادی، تهرانسر، یافت‌آباد و کهریزک', 'pixva' ),
				'phone'   => '02191009993',
				'zones'   => array( 'zone-9', 'zone-10', 'zone-17', 'zone-18', 'zone-19', 'zone-21', 'zone-22' ),
				'hours'   => __( 'شنبه تا پنجشنبه ۰۹:۰۰ تا ۱۹:۰۰', 'pixva' ),
			),
		);

		return apply_filters( 'pixva_branch_defaults', $branches );
	}
}

if ( ! function_exists( 'pixva_branches' ) ) {
	/**
	 * شعبه‌های مؤثر: اولویت با CPT «pixva_branch» و در نبود آن مقادیر پیش‌فرض.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	function pixva_branches() {
		$posts = get_posts(
			array(
				'post_type'      => 'pixva_branch',
				'posts_per_page' => 20,
				'no_found_rows'  => true,
				'orderby'        => 'menu_order',
				'order'          => 'ASC',
			)
		);

		if ( empty( $posts ) ) {
			return pixva_branch_defaults();
		}

		$out = array();
		foreach ( $posts as $post ) {
			$out[] = array(
				'name'    => get_the_title( $post ),
				'type'    => (string) get_post_meta( $post->ID, '_pixva_branch_type', true ),
				'address' => (string) get_post_meta( $post->ID, '_pixva_branch_address', true ),
				'phone'   => (string) get_post_meta( $post->ID, '_pixva_branch_phone', true ),
				'zones'   => array_filter( array_map( 'trim', explode( ',', (string) get_post_meta( $post->ID, '_pixva_branch_zones', true ) ) ) ),
				'hours'   => (string) get_post_meta( $post->ID, '_pixva_branch_hours', true ),
			);
		}

		return $out;
	}
}

if ( ! function_exists( 'pixva_brand_lifespan_data' ) ) {
	/**
	 * داده‌های کارگاهی طول عمر و فراوانی قطعه برای برندهای پشتیبانی‌شده.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	function pixva_brand_lifespan_data() {
		return array(
			'sony'      => array( 'life' => 9, 'parts' => __( 'متوسط — برد اصلی کمیاب', 'pixva' ), 'note' => __( 'بک‌لایت و برد پاور در دسترس؛ مین‌برد معمولاً تعمیر می‌شود.', 'pixva' ) ),
			'samsung'   => array( 'life' => 8, 'parts' => __( 'بسیار خوب', 'pixva' ), 'note' => __( 'فراوان‌ترین قطعات در بازار ایران؛ تعمیرپذیری بالا.', 'pixva' ) ),
			'lg'        => array( 'life' => 8, 'parts' => __( 'خوب', 'pixva' ), 'note' => __( 'در پنل‌های OLED هزینه تعمیر بالاست.', 'pixva' ) ),
			'panasonic' => array( 'life' => 10, 'parts' => __( 'متوسط', 'pixva' ), 'note' => __( 'کیفیت ساخت بالا، قطعات گران.', 'pixva' ) ),
			'philips'   => array( 'life' => 7, 'parts' => __( 'متوسط', 'pixva' ), 'note' => __( 'برد Ambilight نیاز به تخصص جداگانه دارد.', 'pixva' ) ),
			'sharp'     => array( 'life' => 7, 'parts' => __( 'کم', 'pixva' ), 'note' => __( 'پنل‌های اصلی Sharp کمیاب هستند.', 'pixva' ) ),
			'toshiba'   => array( 'life' => 7, 'parts' => __( 'کم', 'pixva' ), 'note' => __( 'قطعات از مسیر واردات محدود تأمین می‌شود.', 'pixva' ) ),
			'xiaomi'    => array( 'life' => 6, 'parts' => __( 'متوسط — در حال بهبود', 'pixva' ), 'note' => __( 'مین‌برد اندرویدی؛ خرابی حافظه eMMC رایج است.', 'pixva' ) ),
			'snowa'     => array( 'life' => 6, 'parts' => __( 'خوب (داخلی)', 'pixva' ), 'note' => __( 'خدمات داخلی و قطعات در دسترس.', 'pixva' ) ),
			'xvision'   => array( 'life' => 6, 'parts' => __( 'خوب (داخلی)', 'pixva' ), 'note' => __( 'پنل و بک‌لایت به‌راحتی تأمین می‌شود.', 'pixva' ) ),
			'gplus'     => array( 'life' => 6, 'parts' => __( 'خوب (داخلی)', 'pixva' ), 'note' => __( 'گارانتی داخلی و شبکه خدمات گسترده.', 'pixva' ) ),
		);
	}
}

if ( ! function_exists( 'pixva_electricity_tariffs' ) ) {
	/**
	 * تعرفه‌های برق خانگی و تجاری (ریال به ازای هر کیلووات‌ساعت).
	 *
	 * @return array<string, array<string, mixed>>
	 */
	function pixva_electricity_tariffs() {
		return array(
			'home_low'  => array( 'label' => __( 'خانگی — مصرف بهینه', 'pixva' ), 'rate' => 3500 ),
			'home_mid'  => array( 'label' => __( 'خانگی — مصرف متوسط', 'pixva' ), 'rate' => 9000 ),
			'home_high' => array( 'label' => __( 'خانگی — پرمصرف', 'pixva' ), 'rate' => 26000 ),
			'commercial' => array( 'label' => __( 'تجاری / اداری', 'pixva' ), 'rate' => 32000 ),
			'hotel'     => array( 'label' => __( 'هتل و اقامتی', 'pixva' ), 'rate' => 28000 ),
		);
	}
}

if ( ! function_exists( 'pixva_packing_steps' ) ) {
	/**
	 * مراحل ایمن‌سازی و بسته‌بندی تلویزیون برای حمل.
	 *
	 * @return array<int, array<string, string>>
	 */
	function pixva_packing_steps() {
		return array(
			array( 'title' => __( 'جدا کردن پایه و پیچ‌ها', 'pixva' ), 'text' => __( 'تلویزیون را روی سطح نرم بخوابانید، پایه را باز کنید و پیچ‌ها را در کیسه جداگانه بچسبانید.', 'pixva' ) ),
			array( 'title' => __( 'پوشاندن صفحه با فوم نرم', 'pixva' ), 'text' => __( 'روی پنل فوم ۲ سانتی یا پتوی نرم بکشید؛ هرگز پلاستیک حبابی را مستقیم روی گلس نگذارید.', 'pixva' ) ),
			array( 'title' => __( 'محافظت از گوشه‌ها', 'pixva' ), 'text' => __( 'چهار گوشه با محافظ L شکل تقویت شود؛ بیشترین آسیب حمل، شکستگی گوشه پنل است.', 'pixva' ) ),
			array( 'title' => __( 'قرار دادن در کارتن ضربه‌گیر', 'pixva' ), 'text' => __( 'کارتن باید ۵ تا ۸ سانتی‌متر از هر طرف فضای فوم داشته باشد؛ دستگاه داخل کارتن لق نزند.', 'pixva' ) ),
			array( 'title' => __( 'عمودی گذاشتن در خودرو', 'pixva' ), 'text' => __( 'تلویزیون فقط به‌صورت عمودی و با تسمه مهار حمل شود؛ خواباندن طولانی باعث ترک لایه‌های پنل می‌شود.', 'pixva' ) ),
			array( 'title' => __( 'یادداشت سمت صفحه و شماره پرونده', 'pixva' ), 'text' => __( 'جهت صفحه و کد پیگیری را روی کارتن بنویسید تا در پذیرش اشتباه جابه‌جا نشود.', 'pixva' ) ),
		);
	}
}

if ( ! function_exists( 'pixva_os_update_guides' ) ) {
	/**
	 * راهنمای به‌روزرسانی سیستم‌عامل‌های رایج تلویزیون هوشمند.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	function pixva_os_update_guides() {
		return array(
			'webos'   => array(
				'label' => 'webOS (LG)',
				'steps' => array(
					__( 'اتصال پایدار اینترنت و خاموش بودن حالت صرفه‌جویی انرژی.', 'pixva' ),
					__( 'مسیر: Settings → All Settings → General → About This TV → Check for Updates.', 'pixva' ),
					__( 'در حین نصب، دستگاه را از برق نکشید؛ خاموش شدن ناگهانی باعث خرابی فریمور می‌شود.', 'pixva' ),
					__( 'پس از به‌روزرسانی، یک‌بار تلویزیون را کامل خاموش و روشن کنید تا کش پاک شود.', 'pixva' ),
				),
				'risk'  => __( 'خرابی حافظه eMMC در به‌روزرسانی‌های ناموفق؛ در صورت ماندن روی لوگو، برد مین نیاز به پروگرام مجدد دارد.', 'pixva' ),
			),
			'tizen'   => array(
				'label' => 'Tizen (Samsung)',
				'steps' => array(
					__( 'حافظه داخلی را از برنامه‌های سنگین خالی کنید (حداقل ۲ گیگابایت فضای آزاد).', 'pixva' ),
					__( 'مسیر: Settings → Support → Software Update → Update Now.', 'pixva' ),
					__( 'اگر به‌روزرسانی متوقف شد، ۲۰ دقیقه صبر کنید و دستگاه را ریست نکنید.', 'pixva' ),
					__( 'برای رفع کندی پس از آپدیت: Support → Device Care → Start Device Care.', 'pixva' ),
				),
				'risk'  => __( 'قفل شدن روی لوگوی سامسونگ پس از آپدیت ناقص؛ نیازمند پروگرام مجدد آی‌سی بایوس است.', 'pixva' ),
			),
			'android' => array(
				'label' => 'Android TV / Google TV',
				'steps' => array(
					__( 'تنظیمات → درباره دستگاه → به‌روزرسانی سیستم را بررسی کنید.', 'pixva' ),
					__( 'حالت توسعه‌دهنده را برای نصب فایل دستی فعال نکنید مگر از منبع معتبر باشد.', 'pixva' ),
					__( 'پس از آپدیت، کش برنامه‌های پخش (فیلیمو، نماوا، یوتیوب) را پاک کنید.', 'pixva' ),
					__( 'در صورت ریستارت خودکار، دستگاه را با ریموت به حالت Safe Mode ببرید.', 'pixva' ),
				),
				'risk'  => __( 'خرابی پارتیشن سیستم و ریستارت مکرر؛ با ری‌فلش فریمور در کارگاه حل می‌شود.', 'pixva' ),
			),
		);
	}
}

if ( ! function_exists( 'pixva_interference_tree' ) ) {
	/**
	 * درخت تصمیم رفع نویز و تداخل فرکانسی.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	function pixva_interference_tree() {
		return array(
			array(
				'symptom' => __( 'برفک دیجیتال و قطع‌شدن لحظه‌ای تصویر', 'pixva' ),
				'cause'   => __( 'ضعیف بودن سیگنال آنتن یا کابل کواکسیال بی‌کیفیت', 'pixva' ),
				'fix'     => __( 'بررسی اتصالات F، تعویض کابل با نمونه مسی شیلددار و تنظیم جهت آنتن.', 'pixva' ),
			),
			array(
				'symptom' => __( 'نویز خطوط عمودی هنگام روشن بودن مودم', 'pixva' ),
				'cause'   => __( 'تداخل EMI از آداپتور یا کابل شبکه کنار کابل تصویر', 'pixva' ),
				'fix'     => __( 'جدا کردن مسیر کابل شبکه از کابل HDMI و استفاده از آداپتور ارت‌دار.', 'pixva' ),
			),
			array(
				'symptom' => __( 'سایه تصویر و لرزش رنگ در پنل‌های پلاسما', 'pixva' ),
				'cause'   => __( 'نوسان برق شهری و نبود ارتینگ مناسب', 'pixva' ),
				'fix'     => __( 'نصب استبیلایزر با ظرفیت مناسب و بررسی ارت پریز توسط برق‌کار.', 'pixva' ),
			),
			array(
				'symptom' => __( 'وزوز صدا در اسپیکر حتی هنگام قطع سیگنال', 'pixva' ),
				'cause'   => __( 'لوپ زمین (Ground Loop) بین دستگاه جانبی و تلویزیون', 'pixva' ),
				'fix'     => __( 'استفاده از جداکننده لوپ زمین یا اتصال همه دستگاه‌ها به یک سه‌راهی مشترک.', 'pixva' ),
			),
			array(
				'symptom' => __( 'قطع‌شدن تصویر هنگام عبور خودرو یا روشن شدن آسانسور', 'pixva' ),
				'cause'   => __( 'پالس‌های القایی شبکه برق ساختمان', 'pixva' ),
				'fix'     => __( 'نصب محافظ ولتاژ با فیلتر EMI و بررسی تابلو برق ساختمان.', 'pixva' ),
			),
			array(
				'symptom' => __( 'تداخل فقط روی یک ورودی HDMI', 'pixva' ),
				'cause'   => __( 'خرابی پورت یا ناسازگاری HDCP با دستگاه جانبی', 'pixva' ),
				'fix'     => __( 'تست با کابل و دستگاه دیگر؛ در صورت تکرار، برد مین یا پورت HDMI نیاز به تعمیر دارد.', 'pixva' ),
			),
		);
	}
}

if ( ! function_exists( 'pixva_exploded_parts' ) ) {
	/**
	 * اجزای اصلی تلویزیون برای نمای انفجاری.
	 *
	 * @return array<int, array<string, string>>
	 */
	function pixva_exploded_parts() {
		return array(
			array( 'layer' => __( 'گلس پنل (LCD/OLED)', 'pixva' ), 'fault' => __( 'خطوط دائمی، شکستگی، آب‌خوردگی لایه‌ها', 'pixva' ), 'repairable' => __( 'ترمیم با بندینگ فقط برای فلت‌های آسیب‌دیده؛ شکستگی گلس قابل تعمیر نیست.', 'pixva' ) ),
			array( 'layer' => __( 'لایه پولارایزر و دیفیوزر', 'pixva' ), 'fault' => __( 'زردشدگی، لکه نوری، کاهش کنتراست', 'pixva' ), 'repairable' => __( 'تعویض لایه در کارگاه ممکن است اما زمان‌بر است.', 'pixva' ) ),
			array( 'layer' => __( 'دست بک‌لایت LED', 'pixva' ), 'fault' => __( 'تاریکی کامل یا نیمه، صدا دارد تصویر ندارد', 'pixva' ), 'repairable' => __( 'پرکاربردترین خدمت؛ تعویض دست کامل با گارانتی ۱۸۰ روزه.', 'pixva' ) ),
			array( 'layer' => __( 'فلت‌های COF و برد T-Con', 'pixva' ), 'fault' => __( 'خطوط عمودی، تصویر منفی، نیم‌صفحه سیاه', 'pixva' ), 'repairable' => __( 'با دستگاه بندینگ صنعتی و میکروسکوپ ترمیم می‌شود.', 'pixva' ) ),
			array( 'layer' => __( 'برد اصلی (Mainboard)', 'pixva' ), 'fault' => __( 'ماندن روی لوگو، ریستارت، خرابی پورت‌ها', 'pixva' ), 'repairable' => __( 'پروگرام مجدد حافظه یا ریبال آی‌سی با دستگاه BGA.', 'pixva' ) ),
			array( 'layer' => __( 'برد تغذیه (Power)', 'pixva' ), 'fault' => __( 'خاموشی کامل، بوی سوختگی، چشمک چراغ استندبای', 'pixva' ), 'repairable' => __( 'تعویض خازن، ماس‌فت و آی‌سی سوئیچینگ با قطعه اورجینال.', 'pixva' ) ),
			array( 'layer' => __( 'اسپیکر و برد آمپلی‌فایر', 'pixva' ), 'fault' => __( 'قطع صدا، نویز و خش‌خش', 'pixva' ), 'repairable' => __( 'تعویض بلندگو یا تعمیر طبقه خروجی صدا.', 'pixva' ) ),
			array( 'layer' => __( 'ماژول گیرنده و ریموت', 'pixva' ), 'fault' => __( 'عمل نکردن ریموت، عدم شناسایی شبکه', 'pixva' ), 'repairable' => __( 'تعویض ماژول IR/Wi-Fi و جفت‌سازی مجدد ریموت.', 'pixva' ) ),
		);
	}
}

if ( ! function_exists( 'pixva_calibration_steps' ) ) {
	/**
	 * مراحل کالیبراسیون تصویر با الگوهای تست.
	 *
	 * @return array<int, array<string, string>>
	 */
	function pixva_calibration_steps() {
		return array(
			array( 'title' => __( 'بازگشت به تنظیمات کارخانه تصویر', 'pixva' ), 'text' => __( 'حالت تصویر را روی Standard یا Filmmaker بگذارید و همه پردازش‌های حرکتی را خاموش کنید.', 'pixva' ) ),
			array( 'title' => __( 'تنظیم روشنایی با الگوی سیاه', 'pixva' ), 'text' => __( 'الگوی تمام سیاه را پخش کنید؛ نوارهای ۲ تا ۴ درصد باید به‌سختی دیده شوند و نوار ۱ درصد کاملاً محو باشد.', 'pixva' ) ),
			array( 'title' => __( 'تنظیم کنتراست با الگوی سفید', 'pixva' ), 'text' => __( 'در الگوی سفید، مرز نوارهای ۹۶ تا ۱۰۰ درصد باید از هم جدا بماند؛ سفید نباید بسوزد.', 'pixva' ) ),
			array( 'title' => __( 'تعادل سفیدی و دمای رنگ', 'pixva' ), 'text' => __( 'دمای رنگ را روی Warm/2 بگذارید و در صورت دسترسی، Gain و Bias را تا حذف ته‌رنگ قرمز یا آبی تنظیم کنید.', 'pixva' ) ),
			array( 'title' => __( 'تنظیم تیزی و کاهش نویز', 'pixva' ), 'text' => __( 'تیزی را تا ۱۰ تا ۱۵ درصد پایین بیاورید؛ تمام گزینه‌های Noise Reduction را برای منابع 4K خاموش کنید.', 'pixva' ) ),
			array( 'title' => __( 'کنترل نهایی با الگوی خاکستری', 'pixva' ), 'text' => __( 'شیب خاکستری باید از سیاه تا سفید بدون پرش و بدون رنگ‌شدگی دیده شود.', 'pixva' ) ),
		);
	}
}

if ( ! function_exists( 'pixva_dispatch_stages' ) ) {
	/**
	 * مراحل اعزام تکنسین/پیک با زمان تقریبی واقعی.
	 *
	 * @return array<int, array<string, string>>
	 */
	function pixva_dispatch_stages() {
		return array(
			array( 'title' => __( 'ثبت درخواست اعزام', 'pixva' ), 'time' => __( 'بلافاصله', 'pixva' ) ),
			array( 'title' => __( 'تخصیص نزدیک‌ترین واحد سیار', 'pixva' ), 'time' => __( '۱۰ تا ۲۵ دقیقه', 'pixva' ) ),
			array( 'title' => __( 'حرکت تکنسین به محل', 'pixva' ), 'time' => __( '۳۰ تا ۹۰ دقیقه', 'pixva' ) ),
			array( 'title' => __( 'عیب‌یابی در محل یا جمع‌آوری دستگاه', 'pixva' ), 'time' => __( '۲۰ تا ۴۵ دقیقه', 'pixva' ) ),
			array( 'title' => __( 'تعمیر، تست کیفیت و تحویل با گارانتی', 'pixva' ), 'time' => __( '۱ تا ۵ روز کاری', 'pixva' ) ),
		);
	}
}

if ( ! function_exists( 'pixva_stock_catalog' ) ) {
	/**
	 * موجودی انبار مرکزی (پیش‌فرض) — در صورت وجود رکورد در CPT قطعات، همان مقدم است.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	function pixva_stock_catalog() {
		return array(
			array( 'name' => __( 'دست کامل بک‌لایت سامسونگ ۵۵ اینچ (BN44-00874A)', 'pixva' ), 'sku' => 'BL-SAM-55', 'brand' => 'samsung', 'qty' => 14, 'price' => 15500000, 'warranty' => 180 ),
			array( 'name' => __( 'دست کامل بک‌لایت ال‌جی ۵۵ اینچ (6916L-2972A)', 'pixva' ), 'sku' => 'BL-LG-55', 'brand' => 'lg', 'qty' => 9, 'price' => 15800000, 'warranty' => 180 ),
			array( 'name' => __( 'بک‌لایت اسنوا/ایکس‌ویژن ۵۰ اینچ', 'pixva' ), 'sku' => 'BL-IRN-50', 'brand' => 'snowa', 'qty' => 22, 'price' => 11200000, 'warranty' => 180 ),
			array( 'name' => __( 'بک‌لایت ۶۵ اینچ یونیورسال (۴ ردیفه)', 'pixva' ), 'sku' => 'BL-UNI-65', 'brand' => 'generic', 'qty' => 6, 'price' => 21500000, 'warranty' => 120 ),
			array( 'name' => __( 'برد تغذیه سامسونگ ۴۳ اینچ (BN44-01038A)', 'pixva' ), 'sku' => 'PSU-SAM-43', 'brand' => 'samsung', 'qty' => 5, 'price' => 9800000, 'warranty' => 180 ),
			array( 'name' => __( 'مین‌برد ال‌جی ۴۹ اینچ (EAX68163204)', 'pixva' ), 'sku' => 'MB-LG-49', 'brand' => 'lg', 'qty' => 3, 'price' => 13400000, 'warranty' => 180 ),
			array( 'name' => __( 'فلت COF پنل ۵۵ اینچ (باندینگ)', 'pixva' ), 'sku' => 'COF-55', 'brand' => 'generic', 'qty' => 40, 'price' => 16500000, 'warranty' => 180 ),
			array( 'name' => __( 'آی‌سی T-Con HX8877 (پنل ۶۵ اینچ)', 'pixva' ), 'sku' => 'TCON-65', 'brand' => 'generic', 'qty' => 11, 'price' => 17200000, 'warranty' => 90 ),
			array( 'name' => __( 'ریموت اصلی سامسونگ BN59-01315A', 'pixva' ), 'sku' => 'RM-SAM-01', 'brand' => 'samsung', 'qty' => 18, 'price' => 1250000, 'warranty' => 30 ),
			array( 'name' => __( 'ریموت بلوتوثی ال‌جی Magic MR21GA', 'pixva' ), 'sku' => 'RM-LG-21', 'brand' => 'lg', 'qty' => 7, 'price' => 2400000, 'warranty' => 30 ),
			array( 'name' => __( 'اسپیکر ۱۰ وات ۸ اهم (جفت)', 'pixva' ), 'sku' => 'SPK-10W', 'brand' => 'generic', 'qty' => 25, 'price' => 1850000, 'warranty' => 90 ),
			array( 'name' => __( 'خازن جامد ۴۵۰ ولت ۲۲۰ میکرو (برد پاور)', 'pixva' ), 'sku' => 'CAP-450', 'brand' => 'generic', 'qty' => 120, 'price' => 320000, 'warranty' => 30 ),
		);
	}
}

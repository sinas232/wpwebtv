<?php
/**
 * نصب اولیه قالب پیکسوا
 *
 * برگه‌ها، منو، پیوندهای یکتا و محتوای نمونه فقط در اولین فعال‌سازی ساخته می‌شوند.
 * محتوای واقعی کاربر در فعال‌سازی‌های بعدی دست نمی‌خورد.
 *
 * @package Pixva
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * نقطه ورود فعال‌سازی قالب.
 *
 * @return void
 */
function pixva_on_switch_theme() {
	pixva_install_site();
	flush_rewrite_rules();
}
add_action( 'after_switch_theme', 'pixva_on_switch_theme' );

/**
 * نصب برگه، منو و نمونه محتوا.
 *
 * @return void
 */
function pixva_install_site() {
	if ( get_option( 'pixva_installed' ) ) {
		return;
	}

	pixva_install_identity();
	$pages = pixva_install_pages();
	pixva_install_menu( $pages );
	pixva_install_reading( $pages );
	pixva_install_terms();
	pixva_install_sample_content();
	pixva_install_demo_order();

	update_option( 'pixva_installed', 1 );
	update_option( 'pixva_show_setup_notice', 1 );
}

/**
 * نام سایت را فقط اگر هنوز پیش‌فرض وردپرس است عوض می‌کند.
 *
 * @return void
 */
function pixva_install_identity() {
	$defaults = array( 'WordPress', 'وردپرس', 'وبلاگ من', 'My WordPress', 'My WordPress Website' );
	if ( in_array( get_option( 'blogname' ), $defaults, true ) ) {
		update_option( 'blogname', 'پیکسوا' );
	}
	if ( '' === (string) get_option( 'blogdescription' ) || in_array( get_option( 'blogdescription' ), array( 'Just another WordPress site', 'یک سایت وردپرسی دیگر' ), true ) ) {
		update_option( 'blogdescription', 'مرکز تخصصی تعمیر تلویزیون و نمایشگر' );
	}
	if ( '' === (string) get_option( 'permalink_structure' ) ) {
		update_option( 'permalink_structure', '/%postname%/' );
	}
	update_option( 'timezone_string', get_option( 'timezone_string' ) ? get_option( 'timezone_string' ) : 'Asia/Tehran' );
}

/**
 * ساخت برگه‌های لازم قالب.
 *
 * @return array<string, int>
 */
function pixva_install_pages() {
	$definitions = array(
		'home'        => array( 'خانه', '' ),
		'blog'        => array( 'مجله تخصصی', '' ),
		'calculator'  => array( 'محاسبه هزینه تعمیر', 'page-templates/page-calculator.php' ),
		'tracking'    => array( 'پیگیری وضعیت تعمیر', 'page-templates/page-tracking.php' ),
		'error-codes' => array( 'کدهای خطا و چشمک چراغ', 'page-templates/page-error-codes.php' ),
		'about'       => array( 'درباره پیکسوا', 'page-templates/page-about.php' ),
		'contact'     => array( 'تماس با ما', 'page-templates/page-contact.php' ),
		'faq'         => array( 'سوالات متداول', 'page-templates/page-faq.php' ),
	);
	$ids         = array();
	foreach ( $definitions as $slug => $item ) {
		$existing = get_page_by_path( $slug );
		if ( $existing instanceof WP_Post ) {
			$ids[ $slug ] = (int) $existing->ID;
			if ( '' !== $item[1] ) {
				update_post_meta( $existing->ID, '_wp_page_template', $item[1] );
			}
			continue;
		}
		$page_id = wp_insert_post(
			array(
				'post_title'   => $item[0],
				'post_name'    => $slug,
				'post_status'  => 'publish',
				'post_type'    => 'page',
				'post_content' => '',
			)
		);
		if ( ! is_wp_error( $page_id ) && $page_id ) {
			$ids[ $slug ] = (int) $page_id;
			if ( '' !== $item[1] ) {
				update_post_meta( $page_id, '_wp_page_template', $item[1] );
			}
		}
	}
	return $ids;
}

/**
 * منوی اصلی، فقط اگر جایگاهی خالی باشد.
 *
 * @param array $pages شناسه برگه‌ها.
 * @return void
 */
function pixva_install_menu( $pages ) {
	$locations = get_theme_mod( 'nav_menu_locations', array() );
	if ( ! empty( $locations['primary'] ) ) {
		return;
	}
	$menu_id = wp_create_nav_menu( 'منوی پیکسوا' );
	if ( is_wp_error( $menu_id ) ) {
		return;
	}
	$items = array(
		array(
			'title' => 'خانه',
			'type'  => 'custom',
			'url'   => home_url( '/' ),
		),
		array(
			'title' => 'خدمات',
			'type'  => 'custom',
			'url'   => get_post_type_archive_link( 'tv_services' ),
		),
		array(
			'title' => 'برندها',
			'type'  => 'custom',
			'url'   => get_post_type_archive_link( 'tv_brands' ),
		),
	);
	foreach ( array( 'calculator', 'tracking', 'error-codes', 'blog', 'about', 'contact' ) as $slug ) {
		if ( empty( $pages[ $slug ] ) ) {
			continue;
		}
		$short_titles = array(
			'calculator'  => 'محاسبه هزینه',
			'tracking'    => 'پیگیری',
			'error-codes' => 'کدهای خطا',
			'blog'        => 'مجله',
			'about'       => 'درباره ما',
			'contact'     => 'تماس',
		);
		$items[]      = array(
			'title' => isset( $short_titles[ $slug ] ) ? $short_titles[ $slug ] : get_the_title( $pages[ $slug ] ),
			'type'  => 'page',
			'id'    => $pages[ $slug ],
		);
	}
	foreach ( $items as $item ) {
		if ( 'page' === $item['type'] ) {
			wp_update_nav_menu_item(
				$menu_id,
				0,
				array(
					'menu-item-title'     => $item['title'],
					'menu-item-object'    => 'page',
					'menu-item-object-id' => $item['id'],
					'menu-item-type'      => 'post_type',
					'menu-item-status'    => 'publish',
				)
			);
		} else {
			wp_update_nav_menu_item(
				$menu_id,
				0,
				array(
					'menu-item-title'  => $item['title'],
					'menu-item-url'    => $item['url'],
					'menu-item-type'   => 'custom',
					'menu-item-status' => 'publish',
				)
			);
		}
	}
	$locations['primary'] = (int) $menu_id;
	if ( empty( $locations['footer'] ) ) {
		$locations['footer'] = (int) $menu_id;
	}
	set_theme_mod( 'nav_menu_locations', $locations );
}

/**
 * اگر سایت هنوز صفحه ایستا ندارد، خانه و مجله را تنظیم می‌کند.
 *
 * @param array $pages شناسه برگه‌ها.
 * @return void
 */
function pixva_install_reading( $pages ) {
	if ( (int) get_option( 'page_on_front' ) > 0 ) {
		return;
	}
	$counts = wp_count_posts( 'post' );
	if ( isset( $counts->publish ) && (int) $counts->publish > 0 ) {
		return;
	}
	if ( ! empty( $pages['home'] ) ) {
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', (int) $pages['home'] );
	}
	if ( ! empty( $pages['blog'] ) && ! (int) get_option( 'page_for_posts' ) ) {
		update_option( 'page_for_posts', (int) $pages['blog'] );
	}
}

/**
 * ساخت ترم‌های خرابی و تکنولوژی.
 *
 * @return void
 */
function pixva_install_terms() {
	$problems = array(
		'no-picture' => 'بی‌تصویری',
		'lines'      => 'خطوط عمودی و افقی',
		'no-power'   => 'خاموشی کامل',
		'no-sound'   => 'قطع صدا',
		'blink'      => 'چشمک زدن چراغ',
	);
	$techs    = array(
		'led'      => 'LED',
		'qled'     => 'QLED',
		'oled'     => 'OLED',
		'plasma'   => 'Plasma',
		'microled' => 'MicroLED',
	);
	foreach ( $problems as $slug => $name ) {
		if ( ! term_exists( $slug, 'tv_problem' ) ) {
			wp_insert_term( $name, 'tv_problem', array( 'slug' => $slug ) );
		}
	}
	foreach ( $techs as $slug => $name ) {
		if ( ! term_exists( $slug, 'tv_tech' ) ) {
			wp_insert_term( $name, 'tv_tech', array( 'slug' => $slug ) );
		}
	}
	if ( ! term_exists( 'diagnostics', 'category' ) ) {
		wp_insert_term( 'عیب‌یابی', 'category', array( 'slug' => 'diagnostics' ) );
	}
}

/**
 * درون‌ریزی تصویر قالب به کتابخانه رسانه.
 *
 * @param string $relative مسیر نسبت به پوشه قالب.
 * @param string $title    عنوان پیوست.
 * @return int
 */
function pixva_import_theme_image( $relative, $title ) {
	$map = get_option( 'pixva_imported_images', array() );
	if ( ! is_array( $map ) ) {
		$map = array();
	}
	if ( ! empty( $map[ $relative ] ) && get_post( (int) $map[ $relative ] ) ) {
		return (int) $map[ $relative ];
	}
	$path = PIXVA_DIR . '/' . ltrim( $relative, '/' );
	if ( ! file_exists( $path ) ) {
		return 0;
	}
	require_once ABSPATH . 'wp-admin/includes/image.php';
	require_once ABSPATH . 'wp-admin/includes/file.php';
	$contents = file_get_contents( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- فایل محلی قالب است.
	if ( false === $contents ) {
		return 0;
	}
	$upload = wp_upload_bits( basename( $path ), null, $contents );
	if ( ! empty( $upload['error'] ) ) {
		return 0;
	}
	$filetype  = wp_check_filetype( $upload['file'] );
	$attach_id = wp_insert_attachment(
		array(
			'post_mime_type' => $filetype['type'],
			'post_title'     => $title,
			'post_status'    => 'inherit',
		),
		$upload['file']
	);
	if ( is_wp_error( $attach_id ) || ! $attach_id ) {
		return 0;
	}
	wp_update_attachment_metadata( $attach_id, wp_generate_attachment_metadata( $attach_id, $upload['file'] ) );
	$map[ $relative ] = (int) $attach_id;
	update_option( 'pixva_imported_images', $map );
	return (int) $attach_id;
}

/**
 * محتوای نمونه خدمات، برند، نمونه‌کار و مقاله.
 *
 * @return void
 */
function pixva_install_sample_content() {
	if ( get_option( 'pixva_sample_content' ) ) {
		return;
	}
	$services = array(
		'backlight'  => array( 'تعویض بک‌لایت', 'رفع تاریکی، هاله و خاموشی نور پس‌زمینه بدون تعویض پنل.' ),
		'panel'      => array( 'تعمیر پنل با دستگاه بندینگ', 'ترمیم خطوط عمودی و افقی وقتی شیشه سالم است.' ),
		'mainboard'  => array( 'تعمیر برد اصلی', 'عیب‌یابی مین‌برد، HDMI و بوت نشدن سیستم.' ),
		'powerboard' => array( 'تعمیر برد پاور', 'رفع چشمک چراغ، خاموشی کامل و صدای جرقه تغذیه.' ),
		'water'      => array( 'رفع آب‌خوردگی', 'شست‌وشو و ترمیم برد پس از نفوذ مایع، اگر سلول پنل سالم باشد.' ),
	);
	foreach ( $services as $slug => $item ) {
		if ( get_page_by_path( $slug, OBJECT, 'tv_services' ) ) {
			continue;
		}
		wp_insert_post(
			array(
				'post_type'    => 'tv_services',
				'post_status'  => 'publish',
				'post_name'    => $slug,
				'post_title'   => $item[0],
				'post_excerpt' => $item[1],
				'post_content' => '<p>' . $item[1] . '</p><h2>چه زمانی این خدمت به‌صرفه است؟</h2><p>وقتی علامت خرابی با این مسیر هم‌خوان باشد و هزینه از تعویض پنل کمتر دربیاید. قبل از شروع، بازه قیمت اعلام و تأیید می‌شود.</p><h2>گارانتی</h2><p>برد و بک‌لایت ۱۸۰ روز ضمانت کتبی دارند. نتیجه بندینگ روی همان خط تعمیرشده تضمین می‌شود.</p>',
			)
		);
	}

	foreach ( pixva_brand_catalog() as $slug => $brand ) {
		if ( get_page_by_path( $slug, OBJECT, 'tv_brands' ) ) {
			continue;
		}
		wp_insert_post(
			array(
				'post_type'    => 'tv_brands',
				'post_status'  => 'publish',
				'post_name'    => $slug,
				'post_title'   => 'تعمیر تلویزیون ' . $brand['fa'],
				'post_excerpt' => 'پذیرش ' . $brand['en'] . ' در کارگاه پیکسوا؛ از برد تغذیه تا بندینگ پنل.',
				'post_content' => '<p>تلویزیون‌های ' . $brand['fa'] . ' (' . $brand['en'] . ') در پیکسوا با توجه به شاسی و الگوی چشمک همان برند عیب‌یابی می‌شوند.</p><h2>خرابی‌های شایع</h2><ul><li>صفحه سیاه با صدا</li><li>خطوط عمودی یا افقی</li><li>چشمک چراغ پاور</li><li>خاموشی بعد از نوسان برق</li></ul><h2>نکته پذیرش</h2><p>مدل دقیق پشت دستگاه و تعداد چشمک را قبل از آوردن یادداشت کنید تا قطعه از قبل بررسی شود.</p>',
			)
		);
	}

	$before = pixva_import_theme_image( 'assets/images/panel-before.jpg', 'پنل قبل از تعمیر' );
	$after  = pixva_import_theme_image( 'assets/images/panel-after.jpg', 'پنل بعد از تعمیر' );
	if ( ! get_page_by_path( 'samsung-55-lines', OBJECT, 'repair_cases' ) ) {
		$case_id = wp_insert_post(
			array(
				'post_type'    => 'repair_cases',
				'post_status'  => 'publish',
				'post_name'    => 'samsung-55-lines',
				'post_title'   => 'رفع خطوط عمودی سامسونگ ۵۵ اینچ',
				'post_excerpt' => 'بندینگ فلت COF بدون تعویض پنل. تصویر تست نهایی یکدست شد.',
				'post_content' => '<p>دستگاه با خطوط عمودی ثابت پذیرش شد. شیشه ضربه نداشت و مسیر فلت کنار پنل قطع شده بود.</p><h2>کار انجام‌شده</h2><p>بندینگ COF، تست حرارت دو ساعته و الگوی خاکستری. پنل تعویض نشد.</p>',
			)
		);
		if ( ! is_wp_error( $case_id ) && $case_id ) {
			update_post_meta( $case_id, '_pixva_case_before', $before );
			update_post_meta( $case_id, '_pixva_case_after', $after );
			update_post_meta( $case_id, '_pixva_case_model', 'Samsung 55 inch' );
			update_post_meta( $case_id, '_pixva_case_parts', 'فلت COF' );
			update_post_meta( $case_id, '_pixva_case_duration', '۴ ساعت کاری' );
			wp_set_object_terms( $case_id, array( 'lines' ), 'tv_problem' );
			wp_set_object_terms( $case_id, array( 'led' ), 'tv_tech' );
			if ( $after ) {
				set_post_thumbnail( $case_id, $after );
			}
		}
	}

	pixva_install_sample_posts( $after );
	update_option( 'pixva_sample_content', 1 );
}

/**
 * دو مقاله نمونه با سرتیتر، FAQ و متای عیب‌یابی.
 *
 * @param int $thumb شناسه تصویر شاخص.
 * @return void
 */
function pixva_install_sample_posts( $thumb ) {
	$posts = array(
		array(
			'slug'       => 'no-picture-has-sound',
			'title'      => 'چرا تلویزیون صدا دارد ولی تصویر ندارد؟',
			'excerpt'    => 'صفحه سیاه با صدا معمولاً بک‌لایت یا برد تغذیه نور است، نه لزوماً سوختن پنل.',
			'difficulty' => 'medium',
			'brand'      => 'سامسونگ، ال‌جی، اسنوا',
			'tools'      => 'چراغ‌قوه، مولتی‌متر',
			'problem'    => 'no-picture',
			'tech'       => 'led',
			'faq'        => "صفحه کاملاً سیاه است؛ پنل سوخته؟ | اگر با چراغ‌قوه سایه تصویر دیده شود پنل زنده است و نور پس‌زمینه قطع شده.\nخودمان می‌توانیم بک‌لایت را عوض کنیم؟ | ولتاژ درایور LED خطرناک است و نوار اشتباه سایز، پنل را خط می‌اندازد.",
			'content'    => '<p>وقتی صدا هست و تصویر نیست، اول مسیر نور را از مسیر پردازش جدا کنید. این کار جلوی تعویض بی‌دلیل پنل را می‌گیرد.</p><h2>تست چراغ‌قوه</h2><p>در اتاق تاریک، نور چراغ‌قوه را مایل به صفحه بگیرید. اگر سایه منو یا تصویر را دیدید، سلول پنل زنده است و مشکل از بک‌لایت یا درایور آن است.</p><h3>اگر هیچ سایه‌ای نیست</h3><p>احتمال T-CON، فلت یا مین‌برد بیشتر می‌شود. در این حالت تعداد چشمک چراغ پاور را بشمارید و با پایگاه کدهای خطا تطبیق دهید.</p><h2>چه وقت دستگاه را باز نکنید</h2><p>برد پاور حتی بعد از کشیدن دو شاخه هم بار نگه می‌دارد. اگر به اندازه‌گیری ولتاژ مسلط نیستید، همان علائم را برای کارگاه بفرستید.</p><h2>برآورد پیکسوا</h2><p>تعویض بک‌لایت در سایزهای رایج معمولاً یک تا دو روز کاری زمان می‌برد. OLED این مسیر را ندارد و باید جدا بررسی شود.</p>',
		),
		array(
			'slug'       => 'sony-blink-power-board',
			'title'      => 'سه بار چشمک زدن چراغ سونی یعنی چیست؟',
			'excerpt'    => 'در راهنمای کارگاهی پیکسوا، سه چشمک سونی اغلب به برد تغذیه برمی‌گردد.',
			'difficulty' => 'easy',
			'brand'      => 'سونی',
			'tools'      => 'شمارش چشمک، جدا کردن HDMI',
			'problem'    => 'blink',
			'tech'       => 'led',
			'faq'        => "چشمک را چطور دقیق بشمارم؟ | دستگاه را از برق بکشید، دو دقیقه صبر کنید، دوباره وصل کنید و فقط الگوی تکرارشونده را بشمارید.\nآیا سه چشمک همیشه برد پاور است؟ | در بیشتر شاسی‌های رایج بله، اما اتصال کوتاه سمت پنل هم می‌تواند همان محافظت را فعال کند.",
			'content'    => '<p>چراغ پاور سونی اگر سه بار چشمک بزند و مکث کند، کارگاه پیکسوا اول برد تغذیه را بی‌بار تست می‌کند.</p><h2>قبل از آوردن دستگاه</h2><p>کابل HDMI و آنتن را جدا کنید. یک‌بار برق را کامل قطع کنید. اگر الگو ماند، مشکل از رسیور نیست.</p><h3>الگوهای نزدیک</h3><p>چهار چشمک بیشتر به نور پس‌زمینه و پنج چشمک به تایمینگ یا پنل نزدیک است. عدد را با ریموت روشن‌شده قاطی نکنید.</p><h2>چرا خودتان فیوز را پل نکنید</h2><p>فیوز سوخته علت نیست، نشانه اتصال کوتاه است. پل کردن فیوز برد را می‌سوزاند و گارانتی تعمیر را از بین می‌برد.</p>',
		),
	);

	foreach ( $posts as $item ) {
		if ( get_page_by_path( $item['slug'], OBJECT, 'post' ) ) {
			continue;
		}
		$post_id = wp_insert_post(
			array(
				'post_type'    => 'post',
				'post_status'  => 'publish',
				'post_name'    => $item['slug'],
				'post_title'   => $item['title'],
				'post_excerpt' => $item['excerpt'],
				'post_content' => $item['content'],
			)
		);
		if ( is_wp_error( $post_id ) || ! $post_id ) {
			continue;
		}
		update_post_meta( $post_id, '_pixva_post_difficulty', $item['difficulty'] );
		update_post_meta( $post_id, '_pixva_post_brand', $item['brand'] );
		update_post_meta( $post_id, '_pixva_post_tools', $item['tools'] );
		update_post_meta( $post_id, '_pixva_post_faq', $item['faq'] );
		wp_set_object_terms( $post_id, array( $item['problem'] ), 'tv_problem' );
		wp_set_object_terms( $post_id, array( $item['tech'] ), 'tv_tech' );
		wp_set_object_terms( $post_id, array( 'diagnostics' ), 'category' );
		if ( $thumb ) {
			set_post_thumbnail( $post_id, $thumb );
		}
	}
}

/**
 * یک پرونده نمونه برای آزمون سامانه پیگیری.
 *
 * @return void
 */
function pixva_install_demo_order() {
	$existing = pixva_find_order( 'PXV-DEMO-2401', '' );
	if ( $existing instanceof WP_Post ) {
		return;
	}
	$result = pixva_create_order(
		array(
			'phone'    => '09121111111',
			'brand'    => 'سامسونگ',
			'model'    => '55AU7000',
			'problem'  => 'سامسونگ ۵۵ اینچ LED — خطوط عمودی یا افقی',
			'estimate' => '۱٬۶۰۰٬۰۰۰ تا ۴٬۳۰۰٬۰۰۰ تومان',
		)
	);
	if ( empty( $result['id'] ) ) {
		return;
	}
	update_post_meta( $result['id'], '_pixva_order_code', 'PXV-DEMO-2401' );
	update_post_meta( $result['id'], '_pixva_order_name', 'پرونده نمونه' );
	update_post_meta( $result['id'], '_pixva_order_status', 'repairing' );
	update_post_meta(
		$result['id'],
		'_pixva_order_steps',
		wp_json_encode(
			array(
				'received'  => time() - 3 * DAY_IN_SECONDS,
				'diagnosed' => time() - 2 * DAY_IN_SECONDS,
				'parts'     => time() - DAY_IN_SECONDS,
				'repairing' => time(),
			)
		)
	);
	wp_update_post(
		array(
			'ID'         => $result['id'],
			'post_title' => 'پرونده نمونه PXV-DEMO-2401',
		)
	);
}

/**
 * اعلان یک‌باره پیشخوان پس از نصب.
 *
 * @return void
 */
function pixva_setup_admin_notice() {
	if ( ! current_user_can( 'manage_options' ) || ! get_option( 'pixva_show_setup_notice' ) ) {
		return;
	}
	if ( isset( $_GET['pixva_dismiss_notice'] ) && check_admin_referer( 'pixva_dismiss_notice' ) ) {
		delete_option( 'pixva_show_setup_notice' );
		return;
	}
	$url = wp_nonce_url( add_query_arg( 'pixva_dismiss_notice', '1' ), 'pixva_dismiss_notice' );
	echo '<div class="notice notice-info"><p>';
	echo esc_html__( 'پیکسوا برگه‌ها، منو و محتوای نمونه را ساخت. پرونده آزمایشی پیگیری کد PXV-DEMO-2401 و شماره ۰۹۱۲۱۱۱۱۱۱۱ است. قبل از استفاده واقعی، نمونه را حذف کنید.', 'pixva' );
	echo ' <a href="' . esc_url( $url ) . '">' . esc_html__( 'متوجه شدم', 'pixva' ) . '</a></p></div>';
}
add_action( 'admin_notices', 'pixva_setup_admin_notice' );

/**
 * اگر نام سایت هنوز پیش‌فرض محیط آزمایشی است، آن را به پیکسوا عوض می‌کند.
 *
 * @return void
 */
/**
 * اگر مجله به عنوان برگه نوشته‌ها تنظیم نشده، آن را وصل می‌کند.
 *
 * @return void
 */
function pixva_ensure_posts_page() {
	if ( get_option( 'pixva_reading_checked' ) ) {
		return;
	}
	$home = get_page_by_path( 'home' );
	$blog = get_page_by_path( 'blog' );
	if ( $blog instanceof WP_Post && ! (int) get_option( 'page_for_posts' ) ) {
		if ( 'posts' === get_option( 'show_on_front' ) && $home instanceof WP_Post ) {
			update_option( 'show_on_front', 'page' );
			update_option( 'page_on_front', (int) $home->ID );
		}
		if ( 'page' === get_option( 'show_on_front' ) ) {
			update_option( 'page_for_posts', (int) $blog->ID );
		}
	}
	$hello = get_page_by_path( 'hello-world', OBJECT, 'post' );
	if ( $hello instanceof WP_Post && 'Hello world!' === $hello->post_title ) {
		wp_trash_post( $hello->ID );
	}
	update_option( 'pixva_reading_checked', 1 );
}
add_action( 'init', 'pixva_ensure_posts_page', 20 );

/**
 * عنوان‌های بلند منوی نصب‌شده را کوتاه می‌کند تا هدر نشکند.
 *
 * @return void
 */
function pixva_shorten_nav_labels() {
	if ( get_option( 'pixva_nav_short' ) ) {
		return;
	}
	$short = array(
		'calculator'  => 'محاسبه هزینه',
		'tracking'    => 'پیگیری',
		'error-codes' => 'کدهای خطا',
		'blog'        => 'مجله',
		'about'       => 'درباره ما',
		'contact'     => 'تماس',
		'home'        => 'خانه',
	);
	$menus = wp_get_nav_menus();
	if ( is_array( $menus ) ) {
		foreach ( $menus as $menu ) {
			$items = wp_get_nav_menu_items( $menu->term_id );
			if ( ! is_array( $items ) ) {
				continue;
			}
			foreach ( $items as $item ) {
				$path = trim( (string) wp_parse_url( $item->url, PHP_URL_PATH ), '/' );
				if ( isset( $short[ $path ] ) ) {
					wp_update_post(
						array(
							'ID'         => (int) $item->ID,
							'post_title' => $short[ $path ],
						)
					);
				}
			}
		}
	}
	update_option( 'pixva_nav_short', 1 );
}
add_action( 'init', 'pixva_shorten_nav_labels', 30 );

/**
 * اگر نام سایت هنوز پیش‌فرض محیط آزمایشی است، آن را به پیکسوا عوض می‌کند.
 *
 * @return void
 */
function pixva_fix_default_site_name() {
	if ( get_option( 'pixva_identity_checked' ) ) {
		return;
	}
	$defaults = array( 'WordPress', 'وردپرس', 'وبلاگ من', 'My WordPress', 'My WordPress Website' );
	if ( in_array( get_option( 'blogname' ), $defaults, true ) ) {
		update_option( 'blogname', 'پیکسوا' );
	}
	update_option( 'pixva_identity_checked', 1 );
}
add_action( 'init', 'pixva_fix_default_site_name' );

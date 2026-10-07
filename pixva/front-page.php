<?php
/**
 * صفحه اصلی پیکسوا
 *
 * ترتیب و نمایش سکشن‌ها از سفارشی‌ساز خوانده می‌شود.
 *
 * @package Pixva
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>
<main id="content">
	<?php
	foreach ( pixva_active_home_sections() as $pixva_section ) {
		$pixva_callback = 'pixva_home_' . $pixva_section;
		if ( function_exists( $pixva_callback ) ) {
			call_user_func( $pixva_callback );
		}
	}
	?>
</main>
<?php
get_footer();

/**
 * هیروی صفحه اصلی.
 *
 * @return void
 */
function pixva_home_hero() {
	$title  = (string) pixva_option( 'pixva_hero_title', __( 'تعمیر تلویزیون، بدون تعویض بی‌دلیل پنل', 'pixva' ) );
	$lead   = (string) pixva_option( 'pixva_hero_subtitle', __( 'قاب را باز می‌کنیم، مسیر ارزان‌تر را اول امتحان می‌کنیم، و هزینه را قبل از تعویض قطعه می‌نویسیم. گارانتی کتبی ۱۸۰ روز است.', 'pixva' ) );
	$phone  = pixva_support_phone();
	$base   = trailingslashit( PIXVA_URI ) . 'assets/images/';
	$frames = array(
		array(
			'src' => $base . 'bench-1.jpg',
			'cap' => __( 'باز کردن قاب پشتی', 'pixva' ),
		),
		array(
			'src' => $base . 'bench-2.jpg',
			'cap' => __( 'بک‌لایت و بندینگ فلت', 'pixva' ),
		),
		array(
			'src' => $base . 'bench-3.jpg',
			'cap' => __( 'تست تصویر قبل از تحویل', 'pixva' ),
		),
	);
	?>
	<section class="px-hero" id="hero">
		<div class="pixva-container px-hero__grid">
			<div class="px-hero__copy">
				<p class="px-kicker"><?php esc_html_e( 'کارگاه تخصصی · علاءالدین', 'pixva' ); ?></p>
				<h1><?php echo esc_html( $title ); ?></h1>
				<p class="px-lead"><?php echo esc_html( $lead ); ?></p>
				<div class="px-hero__actions">
					<a class="pixva-btn pixva-btn--cta" href="#pricing"><?php esc_html_e( 'برآورد هزینه', 'pixva' ); ?></a>
					<a class="pixva-btn px-btn--ghost" href="<?php echo esc_url( pixva_tel_href( $phone ) ); ?>"><?php esc_html_e( 'تماس با کارگاه', 'pixva' ); ?></a>
				</div>
				<dl class="px-facts">
					<div>
						<dt><?php esc_html_e( 'گارانتی کتبی', 'pixva' ); ?></dt>
						<dd><?php echo esc_html( pixva_fa_num( '180' ) . ' ' . __( 'روز', 'pixva' ) ); ?></dd>
					</div>
					<div>
						<dt><?php esc_html_e( 'سابقه کارگاه', 'pixva' ); ?></dt>
						<dd><?php echo esc_html( pixva_fa_num( '12' ) . ' ' . __( 'سال', 'pixva' ) ); ?></dd>
					</div>
					<div>
						<dt><?php esc_html_e( 'پیک تهران', 'pixva' ); ?></dt>
						<dd><?php esc_html_e( 'جمع‌آوری و تحویل', 'pixva' ); ?></dd>
					</div>
				</dl>
			</div>
			<figure class="px-film" data-pixva-film aria-label="<?php esc_attr_e( 'سه نما از تعمیر تلویزیون در کارگاه: باز کردن قاب، بندینگ بک‌لایت، تست تصویر', 'pixva' ); ?>">
				<?php foreach ( $frames as $index => $frame ) : ?>
					<img
						src="<?php echo esc_url( $frame['src'] ); ?>"
						alt=""
						width="928"
						height="1152"
						<?php echo 0 === $index ? 'fetchpriority="high"' : ''; ?>
					>
				<?php endforeach; ?>
				<figcaption class="px-film__caps" aria-hidden="true">
					<?php foreach ( $frames as $index => $frame ) : ?>
						<span><?php echo esc_html( pixva_fa_num( sprintf( '%02d', $index + 1 ) ) . '  ' . $frame['cap'] ); ?></span>
					<?php endforeach; ?>
				</figcaption>
				<div class="px-film__progress" aria-hidden="true"><span></span><span></span><span></span></div>
			</figure>
		</div>
	</section>
	<?php
}

/**
 * محاسبه‌گر صفحه اصلی.
 *
 * @return void
 */
function pixva_home_calculator() {
	?>
	<section class="pixva-section px-estimate" id="calculator">
		<div class="pixva-container px-estimate__grid" id="pricing">
			<div>
				<span class="pixva-badge"><?php esc_html_e( 'برآورد', 'pixva' ); ?></span>
				<h2><?php esc_html_e( 'هزینه را قبل از باز کردن دستگاه ببینید', 'pixva' ); ?></h2>
				<p><?php esc_html_e( 'برند، سایز و نوع خرابی را انتخاب کنید. عدد روی سرور حساب می‌شود. اگر پنل باید عوض شود، همان‌جا نوشته می‌شود.', 'pixva' ); ?></p>
				<ul class="px-estimate__notes">
					<li><?php esc_html_e( 'اول بندینگ و بک‌لایت، آخر تعویض پنل', 'pixva' ); ?></li>
					<li><?php esc_html_e( 'هزینه قطعی بعد از عیب‌یابی، با تأیید شما', 'pixva' ); ?></li>
					<li><?php esc_html_e( 'گارانتی کتبی ۱۸۰ روز برای برد و بک‌لایت', 'pixva' ); ?></li>
				</ul>
			</div>
			<div class="px-estimate__form">
				<?php pixva_render_calculator( array( 'compact' => true ) ); ?>
			</div>
		</div>
	</section>
	<?php
}

/**
 * خدمات تخصصی.
 *
 * @return void
 */
function pixva_home_services() {
	$services = get_posts(
		array(
			'post_type'      => 'tv_services',
			'posts_per_page' => 3,
			'no_found_rows'  => true,
		)
	);
	?>
	<section class="pixva-section pixva-section--alt pixva-home-services" id="services">
		<div class="pixva-container">
			<div class="pixva-section-head">
				<span class="pixva-badge"><?php esc_html_e( 'خدمات', 'pixva' ); ?></span>
				<h2><?php esc_html_e( 'تعمیر همان‌جایی که خرابی است', 'pixva' ); ?></h2>
				<p><?php esc_html_e( 'پنل را بی‌دلیل تعویض نمی‌کنیم. اول مسیر ارزان‌تر و قابل ضمانت بررسی می‌شود.', 'pixva' ); ?></p>
			</div>
			<div class="pixva-grid pixva-grid--3">
				<?php if ( ! empty( $services ) ) : ?>
					<?php foreach ( $services as $service ) : ?>
						<a class="pixva-card pixva-service-card" href="<?php echo esc_url( get_permalink( $service ) ); ?>">
							<?php
							if ( has_post_thumbnail( $service ) ) {
								echo get_the_post_thumbnail( $service, 'pixva-card' );
							} else {
								echo pixva_icon( 'tool' );
							}
							?>
							<h3><?php echo esc_html( get_the_title( $service ) ); ?></h3>
							<p><?php echo esc_html( get_the_excerpt( $service ) ); ?></p>
						</a>
					<?php endforeach; ?>
				<?php else : ?>
					<?php foreach ( array_slice( pixva_service_fallbacks(), 0, 3 ) as $service ) : ?>
						<a class="pixva-card pixva-service-card" href="<?php echo esc_url( add_query_arg( 'problem', $service['key'], pixva_page_url( 'calculator' ) ) ); ?>">
							<?php echo pixva_icon( $service['icon'] ); ?>
							<h3><?php echo esc_html( $service['title'] ); ?></h3>
							<p><?php echo esc_html( $service['text'] ); ?></p>
						</a>
					<?php endforeach; ?>
				<?php endif; ?>
			</div>
			<p class="px-more"><a href="<?php echo esc_url( pixva_page_url( 'calculator' ) ); ?>"><?php esc_html_e( 'برآورد برای بقیه خرابی‌ها', 'pixva' ); ?></a></p>
		</div>
	</section>
	<?php
}

/**
 * اسلایدر قبل و بعد.
 *
 * @return void
 */
function pixva_home_before_after() {
	$case   = get_posts(
		array(
			'post_type'      => 'repair_cases',
			'posts_per_page' => 1,
			'no_found_rows'  => true,
		)
	);
	$images = array(
		'before' => PIXVA_URI . '/assets/images/panel-before.jpg',
		'after'  => PIXVA_URI . '/assets/images/panel-after.jpg',
	);
	$title  = __( 'یک پنل خط‌دار، بدون تعویض شیشه', 'pixva' );
	$text   = __( 'خط جداکننده را بکشید. تصویر چپ وضعیت پذیرش است و تصویر راست خروجی تست نهایی کارگاه.', 'pixva' );
	if ( ! empty( $case ) ) {
		$images = pixva_case_images( $case[0]->ID );
		$title  = get_the_title( $case[0] );
		$text   = get_the_excerpt( $case[0] );
	}
	?>
	<section class="pixva-section">
		<div class="pixva-container pixva-grid pixva-grid--2" style="align-items:center">
			<div>
				<span class="pixva-badge pixva-badge--cta"><?php esc_html_e( 'نمونه واقعی', 'pixva' ); ?></span>
				<h2><?php echo esc_html( $title ); ?></h2>
				<p class="pixva-muted"><?php echo esc_html( $text ); ?></p>
				<a class="pixva-btn pixva-btn--primary" href="<?php echo esc_url( get_post_type_archive_link( 'repair_cases' ) ); ?>"><?php esc_html_e( 'همه نمونه‌کارها', 'pixva' ); ?></a>
			</div>
			<?php pixva_render_before_after( $images['before'], $images['after'], $title ); ?>
		</div>
	</section>
	<?php
}

/**
 * شبکه برندها.
 *
 * @return void
 */
function pixva_home_brands() {
	$posts = get_posts(
		array(
			'post_type'      => 'tv_brands',
			'posts_per_page' => 8,
			'no_found_rows'  => true,
		)
	);
	?>
	<section class="pixva-section pixva-section--alt">
		<div class="pixva-container">
			<div class="pixva-section-head">
				<span class="pixva-badge"><?php esc_html_e( 'برندها', 'pixva' ); ?></span>
				<h2><?php esc_html_e( 'از سامسونگ و سونی تا اسنوا و جی‌پلاس', 'pixva' ); ?></h2>
			</div>
			<div class="pixva-grid pixva-grid--4">
				<?php if ( ! empty( $posts ) ) : ?>
					<?php foreach ( $posts as $brand ) : ?>
						<a class="pixva-card pixva-brand-tile" href="<?php echo esc_url( get_permalink( $brand ) ); ?>">
							<strong><?php echo esc_html( get_the_title( $brand ) ); ?></strong>
						</a>
					<?php endforeach; ?>
				<?php else : ?>
					<?php foreach ( pixva_brand_catalog() as $key => $brand ) : ?>
						<a class="pixva-card pixva-brand-tile" href="<?php echo esc_url( add_query_arg( 'brand', $key, pixva_page_url( 'calculator' ) ) ); ?>">
							<span class="pixva-latin pixva-brand-tile__en"><?php echo esc_html( $brand['en'] ); ?></span>
							<span><?php echo esc_html( $brand['fa'] ); ?></span>
						</a>
					<?php endforeach; ?>
				<?php endif; ?>
			</div>
		</div>
	</section>
	<?php
}

/**
 * روایت مشتریان. بدون اسکیما Review.
 *
 * @return void
 */
function pixva_home_testimonials() {
	?>
	<section class="pixva-section">
		<div class="pixva-container">
			<div class="pixva-section-head">
				<span class="pixva-badge"><?php esc_html_e( 'از زبان مشتری', 'pixva' ); ?></span>
				<h2><?php esc_html_e( 'تعمیر را با نتیجه می‌سنجیم', 'pixva' ); ?></h2>
			</div>
			<div class="pixva-grid pixva-grid--3">
				<?php foreach ( pixva_testimonials() as $item ) : ?>
					<blockquote class="pixva-card pixva-quote">
						<div class="pixva-stars" aria-hidden="true">★★★★★</div>
						<p><?php echo esc_html( $item['quote'] ); ?></p>
						<footer><strong><?php echo esc_html( $item['name'] ); ?></strong> · <?php echo esc_html( $item['role'] ); ?></footer>
					</blockquote>
				<?php endforeach; ?>
			</div>
		</div>
	</section>
	<?php
}

/**
 * مسیر پذیرش تا تحویل.
 *
 * @return void
 */
function pixva_home_process() {
	$steps = array(
		array(
			'title' => __( 'ثبت علائم', 'pixva' ),
			'text'  => __( 'برند، سایز، مدل و تعداد چشمک را در محاسبه‌گر یا واتساپ بفرستید. اگر ضربه یا آب‌خوردگی بوده همان اول بگویید.', 'pixva' ),
		),
		array(
			'title' => __( 'برآورد و نوبت', 'pixva' ),
			'text'  => __( 'بازه قیمت روی سرور حساب می‌شود. با ثبت شماره، کد پیگیری می‌گیرید و جمع‌آوری در تهران هماهنگ می‌شود.', 'pixva' ),
		),
		array(
			'title' => __( 'عیب‌یابی کارگاه', 'pixva' ),
			'text'  => __( 'مسیر تغذیه، بک‌لایت، مین‌برد و پنل جدا تست می‌شود. قبل از تعویض قطعه، هزینه قطعی را تأیید می‌کنید.', 'pixva' ),
		),
		array(
			'title' => __( 'تست و گارانتی', 'pixva' ),
			'text'  => __( 'تحویل با الگوی رنگ و خاکستری، به‌همراه برگه ۱۸۰ روزه برای برد و بک‌لایت. وضعیت را آنلاین پیگیری می‌کنید.', 'pixva' ),
		),
	);
	?>
	<section class="pixva-section" id="process">
		<div class="pixva-container">
			<div class="pixva-section-head">
				<span class="pixva-badge"><?php esc_html_e( 'مسیر کار', 'pixva' ); ?></span>
				<h2><?php esc_html_e( 'از تماس تا تحویل، چهار مرحله شفاف', 'pixva' ); ?></h2>
				<p><?php esc_html_e( 'دستگاه بی‌دلیل باز نمی‌شود و قطعه‌ای بدون تأیید شما عوض نمی‌شود.', 'pixva' ); ?></p>
			</div>
			<ol class="pixva-steps pixva-steps--home">
				<?php foreach ( $steps as $step ) : ?>
					<li>
						<strong><?php echo esc_html( $step['title'] ); ?></strong>
						<p><?php echo esc_html( $step['text'] ); ?></p>
					</li>
				<?php endforeach; ?>
			</ol>
		</div>
	</section>
	<?php
}

/**
 * میان‌بر پایگاه کدهای خطا.
 *
 * @return void
 */
function pixva_home_errors() {
	$brands = pixva_brand_catalog();
	$rows   = array_slice( pixva_error_code_catalog(), 0, 4 );
	?>
	<section class="pixva-section pixva-section--alt" id="error-codes">
		<div class="pixva-container">
			<div class="pixva-section-head">
				<span class="pixva-badge"><?php esc_html_e( 'کدهای خطا', 'pixva' ); ?></span>
				<h2><?php esc_html_e( 'چشمک چراغ را قبل از باز کردن دستگاه بشمارید', 'pixva' ); ?></h2>
				<p><?php esc_html_e( 'این راهنمای کارگاهی است، نه سرویس‌منوال رسمی. برد پاور ولتاژ خطرناک دارد.', 'pixva' ); ?></p>
			</div>
			<div class="pixva-grid pixva-grid--2">
				<?php foreach ( $rows as $row ) : ?>
					<?php
					$brand_name = isset( $brands[ $row['brand'] ] ) ? $brands[ $row['brand'] ]['fa'] : $row['brand'];
					$blinks     = $row['blinks'] > 0
						? pixva_fa_num( (string) $row['blinks'] ) . ' ' . __( 'چشمک', 'pixva' )
						: $row['code'];
					?>
					<article class="pixva-card pixva-error-teaser">
						<p class="pixva-kicker"><?php echo esc_html( $brand_name . ' · ' . $blinks ); ?></p>
						<h3><?php echo esc_html( $row['title'] ); ?></h3>
						<p><?php echo esc_html( $row['symptom'] ); ?></p>
					</article>
				<?php endforeach; ?>
			</div>
			<p style="text-align:center;margin-top:1.4rem">
				<a class="pixva-btn pixva-btn--primary" href="<?php echo esc_url( pixva_page_url( 'error-codes' ) ); ?>"><?php esc_html_e( 'پایگاه کامل کدهای خطا', 'pixva' ); ?></a>
			</p>
		</div>
	</section>
	<?php
}

/**
 * سوالات متداول صفحه اصلی.
 *
 * @return void
 */
function pixva_home_faq() {
	?>
	<section class="pixva-section" id="faq">
		<div class="pixva-container" style="max-width:860px">
			<div class="pixva-section-head">
				<span class="pixva-badge"><?php esc_html_e( 'پرسش‌های رایج', 'pixva' ); ?></span>
				<h2><?php esc_html_e( 'قبل از آوردن دستگاه، این‌ها را بدانید', 'pixva' ); ?></h2>
			</div>
			<?php pixva_render_faq( array_slice( pixva_default_faqs(), 0, 6 ), 'home-faq' ); ?>
			<p style="text-align:center;margin-top:1.4rem">
				<a class="pixva-btn pixva-btn--ghost" href="<?php echo esc_url( pixva_page_url( 'faq' ) ); ?>"><?php esc_html_e( 'همه سوالات', 'pixva' ); ?></a>
			</p>
		</div>
	</section>
	<?php
}

/**
 * مجله تخصصی.
 *
 * @return void
 */
function pixva_home_blog() {
	$posts = get_posts(
		array(
			'post_type'      => 'post',
			'posts_per_page' => 3,
			'no_found_rows'  => true,
		)
	);
	?>
	<section class="pixva-section pixva-section--alt">
		<div class="pixva-container">
			<div class="pixva-section-head">
				<span class="pixva-badge"><?php esc_html_e( 'مجله', 'pixva' ); ?></span>
				<h2><?php esc_html_e( 'قبل از آوردن دستگاه، این‌ها را بخوانید', 'pixva' ); ?></h2>
			</div>
			<?php if ( ! empty( $posts ) ) : ?>
				<div class="pixva-grid pixva-grid--3">
					<?php foreach ( $posts as $pixva_post ) : ?>
						<?php pixva_post_card( $pixva_post->ID ); ?>
					<?php endforeach; ?>
				</div>
			<?php else : ?>
				<p class="pixva-notice pixva-notice--info"><?php esc_html_e( 'هنوز مقاله‌ای منتشر نشده است. اولین مطلب را از پیشخوان اضافه کنید.', 'pixva' ); ?></p>
			<?php endif; ?>
			<p style="text-align:center;margin-top:1.4rem"><a class="pixva-btn pixva-btn--ghost" href="<?php echo esc_url( pixva_blog_url() ); ?>"><?php esc_html_e( 'همه مقاله‌ها', 'pixva' ); ?></a></p>
		</div>
	</section>
	<?php
}

/**
 * سکشن شبیه‌ساز لمسی تلویزیون مجازی.
 *
 * @return void
 */
function pixva_home_tv_simulator() {
	if ( function_exists( 'pixva_render_tv_canvas_simulator' ) ) {
		pixva_render_tv_canvas_simulator();
	}
}

/**
 * سکشن تستر زنده پیکسل‌سوختگی و احیاکننده OLED.
 *
 * @return void
 */
function pixva_home_screen_tester() {
	if ( function_exists( 'pixva_render_screen_rgb_tester' ) ) {
		pixva_render_screen_rgb_tester();
	}
}

/**
 * سکشن هاب اعزام اورژانسی و پیگیری آنلاین.
 *
 * @return void
 */
function pixva_home_dispatch_hub() {
	if ( function_exists( 'pixva_render_dispatch_and_warranty_hub' ) ) {
		pixva_render_dispatch_and_warranty_hub();
	}
}

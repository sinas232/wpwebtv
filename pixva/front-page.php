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
	$title = (string) pixva_option( 'pixva_hero_title', __( 'تلویزیونت خراب شده؟ تخصصی تعمیرش می‌کنیم.', 'pixva' ) );
	$lead  = (string) pixva_option( 'pixva_hero_subtitle', __( 'تشخیص دقیق، تعمیر تخصصی و گارانتی کتبی برای انواع تلویزیون. هزینه نهایی بعد از بررسی دستگاه نوشته می‌شود.', 'pixva' ) );
	$phone = pixva_support_phone();
	$raw   = preg_replace( '/\D+/', '', $phone );
	$pretty = $phone;
	if ( is_string( $raw ) && 11 === strlen( $raw ) && 0 === strpos( $raw, '021' ) ) {
		$pretty = substr( $raw, 0, 3 ) . ' ' . substr( $raw, 3, 4 ) . ' ' . substr( $raw, 7 );
	}
	?>
	<section class="px-hero" id="hero">
		<div class="pixva-container px-hero__grid">
			<div class="px-stage" data-pixva-tv-stage>
				<div class="px-poster" aria-hidden="true"><div class="px-poster__set"></div></div>
				<canvas data-pixva-tv aria-label="<?php esc_attr_e( 'تلویزیون سه‌بعدی. با اشاره، پنل جلو باز می‌شود و برد، بک‌لایت و کانکتورها دیده می‌شوند.', 'pixva' ); ?>"></canvas>
				<p class="px-stage__hint" data-tv-hint hidden><?php esc_html_e( 'نشانگر را ببرید تا پنل باز شود', 'pixva' ); ?></p>
			</div>
			<div class="px-hero__copy">
				<p class="px-kicker"><?php esc_html_e( 'مرکز تخصصی پیکسوا · علاءالدین', 'pixva' ); ?></p>
				<h1><?php echo wp_kses( str_replace( '؟ ', '؟<br>', $title ), array( 'br' => array() ) ); ?></h1>
				<p class="px-lead"><?php echo esc_html( $lead ); ?></p>
				<a class="px-phone" href="<?php echo esc_url( pixva_tel_href( $phone ) ); ?>">
					<small><?php esc_html_e( 'تماس مستقیم', 'pixva' ); ?></small>
					<?php echo esc_html( pixva_fa_num( $pretty ) ); ?>
				</a>
				<div class="px-hero__actions">
					<a class="pixva-btn pixva-btn--cta" href="<?php echo esc_url( pixva_page_url( 'contact' ) ); ?>"><?php esc_html_e( 'درخواست تعمیر', 'pixva' ); ?></a>
					<a class="pixva-btn px-btn--ghost" href="#tools"><?php esc_html_e( 'استعلام هزینه', 'pixva' ); ?></a>
				</div>
				<dl class="px-metrics">
					<div>
						<dt><?php esc_html_e( 'گارانتی کتبی', 'pixva' ); ?></dt>
						<dd><?php echo esc_html( pixva_fa_num( '180' ) . ' ' . __( 'روز', 'pixva' ) ); ?></dd>
					</div>
					<div>
						<dt><?php esc_html_e( 'تجربه تعمیر', 'pixva' ); ?></dt>
						<dd><?php echo esc_html( pixva_fa_num( '12' ) . ' ' . __( 'سال', 'pixva' ) ); ?></dd>
					</div>
					<div>
						<dt><?php esc_html_e( 'پوشش تهران', 'pixva' ); ?></dt>
						<dd><?php esc_html_e( 'جمع‌آوری و تحویل', 'pixva' ); ?></dd>
					</div>
				</dl>
			</div>
		</div>
	</section>
	<?php
}

/**
 * مشکلات رایج، با مسیر کوتاه به برآورد.
 *
 * @return void
 */
function pixva_home_problems() {
	$items = array(
		array( 'key' => 'powerboard', 'icon' => 'bolt', 'title' => __( 'روشن نمی‌شود', 'pixva' ), 'text' => __( 'اول تغذیه و چشمک پاور. برد پاور را خودتان باز نکنید.', 'pixva' ) ),
		array( 'key' => 'backlight', 'icon' => 'sun', 'title' => __( 'تصویر ندارد', 'pixva' ), 'text' => __( 'صدا هست و صفحه سیاه است؟ معمولاً بک‌لایت است، نه تعویض پنل.', 'pixva' ) ),
		array( 'key' => 'no_sound', 'icon' => 'sound', 'title' => __( 'صدا ندارد', 'pixva' ), 'text' => __( 'برد صدا، فلت اسپیکر یا تنظیم پنل. اول همان مسیر تست می‌شود.', 'pixva' ) ),
		array( 'key' => 'backlight', 'icon' => 'sun', 'title' => __( 'تصویر تاریک است', 'pixva' ), 'text' => __( 'هاله یا تاریکی موضعی را جدا از خط پنل بررسی می‌کنیم.', 'pixva' ) ),
		array( 'key' => 'panel', 'icon' => 'panel', 'title' => __( 'خطوط روی تصویر', 'pixva' ), 'text' => __( 'اگر شیشه سالم باشد، بندینگ مسیر ارزان‌تر و قابل ضمانت است.', 'pixva' ) ),
		array( 'key' => 'powerboard', 'icon' => 'bolt', 'title' => __( 'خاموش و روشن می‌شود', 'pixva' ), 'text' => __( 'قطع و وصل تصویر یا دستگاه، از تغذیه و مین‌برد جدا تست می‌شود.', 'pixva' ) ),
		array( 'key' => 'panel', 'icon' => 'panel', 'title' => __( 'پیکسل سوخته', 'pixva' ), 'text' => __( 'با تستر رنگ مشخص می‌شود پیکسل است یا نور پس‌زمینه.', 'pixva' ) ),
		array( 'key' => 'mainboard', 'icon' => 'cpu', 'title' => __( 'HDMI یا اینترنت', 'pixva' ), 'text' => __( 'ورودی، وای‌فای و پردازش تصویر قبل از تعویض برد بررسی می‌شود.', 'pixva' ) ),
	);
	?>
	<section class="pixva-section" id="problems">
		<div class="pixva-container">
			<div class="pixva-section-head">
				<span class="pixva-badge"><?php esc_html_e( 'علائم رایج', 'pixva' ); ?></span>
				<h2><?php esc_html_e( 'مشکل تلویزیونت چیه؟', 'pixva' ); ?></h2>
				<p><?php esc_html_e( 'مشکل را انتخاب کن تا مستقیم به برآورد همان خرابی بروی. عدد روی سرور حساب می‌شود.', 'pixva' ); ?></p>
			</div>
			<div class="pixva-grid pixva-grid--4">
				<?php foreach ( $items as $item ) : ?>
					<a class="pixva-card px-problem" href="<?php echo esc_url( add_query_arg( 'problem', $item['key'], home_url( '/' ) ) ); ?>#tools">
						<span class="px-ico"><?php echo pixva_icon( $item['icon'] ); ?></span>
						<h3><?php echo esc_html( $item['title'] ); ?></h3>
						<p><?php echo esc_html( $item['text'] ); ?></p>
					</a>
				<?php endforeach; ?>
			</div>
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
							<span class="px-ico"><?php echo pixva_icon( $service['icon'] ); ?></span>
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
 * چرا پیکسوا. فقط ادعاهای قابل دفاع، بدون آمار ساختگی.
 *
 * @return void
 */
function pixva_home_why() {
	$points = array(
		array( __( 'هزینه قبل از تعویض', 'pixva' ), __( 'بازه قیمت روی سرور حساب می‌شود. قطعه بدون تأیید شما عوض نمی‌شود.', 'pixva' ) ),
		array( __( 'گارانتی کتبی ۱۸۰ روز', 'pixva' ), __( 'برای برد و بک‌لایت برگه کتبی می‌دهیم، نه یک جمله در گفتگو.', 'pixva' ) ),
		array( __( 'کارگاه در علاءالدین', 'pixva' ), __( 'آدرس، تلفن و پیک تهران مشخص است. تعمیر گمنام نیست.', 'pixva' ) ),
	);
	?>
	<section class="pixva-section" id="why">
		<div class="pixva-container">
			<div class="pixva-section-head">
				<span class="pixva-badge"><?php esc_html_e( 'اعتماد', 'pixva' ); ?></span>
				<h2><?php esc_html_e( 'اینجا جای حدس نیست', 'pixva' ); ?></h2>
			</div>
			<div class="pixva-grid pixva-grid--3">
				<?php foreach ( $points as $point ) : ?>
					<article class="pixva-card">
						<h3><?php echo esc_html( $point[0] ); ?></h3>
						<p><?php echo esc_html( $point[1] ); ?></p>
					</article>
				<?php endforeach; ?>
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
			<ol class="px-flow">
				<?php foreach ( $steps as $step ) : ?>
					<li>
						<span class="px-flow__mark" aria-hidden="true"></span>
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
 * ابزارهای تعاملی در یک پوسته اپ: برآورد، تستر، کد خطا، پیگیری، شبیه‌ساز.
 *
 * @return void
 */
function pixva_home_tools() {
	$phone = pixva_support_phone();
	$brands = pixva_brand_catalog();
	$rows   = array_slice( pixva_error_code_catalog(), 0, 4 );
	?>
	<section class="pixva-section pixva-section--alt px-tools" id="tools">
		<div class="pixva-container">
			<div class="pixva-section-head">
				<span class="pixva-badge"><?php esc_html_e( 'ابزارهای پیکسوا', 'pixva' ); ?></span>
				<h2><?php esc_html_e( 'قبل از آوردن دستگاه، خودتان ببینید', 'pixva' ); ?></h2>
				<p><?php esc_html_e( 'برآورد قیمت، تست پیکسل، کد چشمک و پیگیری تعمیر. همان ابزارها، در یک صفحه تمیز.', 'pixva' ); ?></p>
			</div>
			<div class="px-tools__shell" data-px-tools>
				<div class="px-tools__nav" role="tablist" aria-label="<?php esc_attr_e( 'ابزارها', 'pixva' ); ?>">
					<button type="button" role="tab" data-tool="calc" class="is-on" aria-selected="true"><?php esc_html_e( 'برآورد هزینه', 'pixva' ); ?></button>
					<button type="button" role="tab" data-tool="rgb" aria-selected="false"><?php esc_html_e( 'تستر پیکسل', 'pixva' ); ?></button>
					<button type="button" role="tab" data-tool="errors" aria-selected="false"><?php esc_html_e( 'کد خطا', 'pixva' ); ?></button>
					<button type="button" role="tab" data-tool="track" aria-selected="false"><?php esc_html_e( 'پیگیری', 'pixva' ); ?></button>
					<button type="button" role="tab" data-tool="sim" aria-selected="false"><?php esc_html_e( 'شبیه‌ساز', 'pixva' ); ?></button>
				</div>
				<div class="px-tools__pane is-on" data-pane="calc" id="pricing">
					<?php pixva_render_calculator( array( 'compact' => true ) ); ?>
				</div>
				<div class="px-tools__pane" data-pane="rgb" hidden>
					<div class="pixva-card pixva-rgb-tester" data-rgb-tester>
						<div class="pixva-rgb-controls">
							<button type="button" class="pixva-btn pixva-btn--ghost-dark" data-color="#ff0000"><?php esc_html_e( 'قرمز', 'pixva' ); ?></button>
							<button type="button" class="pixva-btn pixva-btn--ghost-dark" data-color="#00ff00"><?php esc_html_e( 'سبز', 'pixva' ); ?></button>
							<button type="button" class="pixva-btn pixva-btn--ghost-dark" data-color="#0000ff"><?php esc_html_e( 'آبی', 'pixva' ); ?></button>
							<button type="button" class="pixva-btn pixva-btn--ghost-dark" data-color="#ffffff"><?php esc_html_e( 'سفید', 'pixva' ); ?></button>
							<button type="button" class="pixva-btn pixva-btn--ghost-dark" data-color="#000000"><?php esc_html_e( 'مشکی', 'pixva' ); ?></button>
							<button type="button" class="pixva-btn pixva-btn--cta" data-oled-cleaner><?php esc_html_e( 'چرخه احیای OLED', 'pixva' ); ?></button>
						</div>
						<div class="pixva-rgb-canvas" data-rgb-canvas style="background:#0B1C2E;min-height:180px;border-radius:12px;display:grid;place-items:center;">
							<span style="color:#d7e0ea;"><?php esc_html_e( 'یک رنگ را انتخاب کنید تا پیکسل گیرکرده دیده شود.', 'pixva' ); ?></span>
						</div>
					</div>
				</div>
				<div class="px-tools__pane" data-pane="errors" hidden>
					<div class="pixva-grid pixva-grid--2">
						<?php foreach ( $rows as $row ) : ?>
							<?php
							$brand_name = isset( $brands[ $row['brand'] ] ) ? $brands[ $row['brand'] ]['fa'] : $row['brand'];
							$blinks     = $row['blinks'] > 0
								? pixva_fa_num( (string) $row['blinks'] ) . ' ' . __( 'چشمک', 'pixva' )
								: $row['code'];
							?>
							<article class="pixva-card">
								<p class="px-kicker"><?php echo esc_html( $brand_name . ' · ' . $blinks ); ?></p>
								<h3><?php echo esc_html( $row['title'] ); ?></h3>
								<p><?php echo esc_html( $row['symptom'] ); ?></p>
							</article>
						<?php endforeach; ?>
					</div>
					<p class="px-more"><a href="<?php echo esc_url( pixva_page_url( 'error-codes' ) ); ?>"><?php esc_html_e( 'پایگاه کامل کدهای خطا', 'pixva' ); ?></a></p>
				</div>
				<div class="px-tools__pane" data-pane="track" hidden>
					<h3><?php esc_html_e( 'وضعیت تعمیر را همین حالا ببینید', 'pixva' ); ?></h3>
					<p><?php esc_html_e( 'کد رهگیری و شماره همراهی که هنگام پذیرش ثبت شده را وارد کنید.', 'pixva' ); ?></p>
					<form action="<?php echo esc_url( pixva_page_url( 'tracking' ) ); ?>" method="get" class="px-track">
						<input class="pixva-input" type="text" name="code" placeholder="<?php esc_attr_e( 'کد رهگیری', 'pixva' ); ?>">
						<input class="pixva-input" type="tel" name="phone" placeholder="<?php esc_attr_e( 'شماره همراه', 'pixva' ); ?>">
						<button class="pixva-btn pixva-btn--primary" type="submit"><?php esc_html_e( 'پیگیری', 'pixva' ); ?></button>
					</form>
					<p class="px-more"><a href="<?php echo esc_url( pixva_tel_href( $phone ) ); ?>"><?php esc_html_e( 'اگر کد ندارید، با کارگاه تماس بگیرید', 'pixva' ); ?></a></p>
				</div>
				<div class="px-tools__pane" data-pane="sim" hidden>
					<div class="pixva-card pixva-simulator-card" data-tv-simulator>
						<div class="pixva-sim-tv">
							<div class="pixva-sim-screen">
								<div class="pixva-sim-hotspot" data-part="backlight" style="top:25%;left:25%;"><span><?php esc_html_e( 'بک‌لایت', 'pixva' ); ?></span></div>
								<div class="pixva-sim-hotspot" data-part="water" style="bottom:15%;left:50%;"><span><?php esc_html_e( 'فلت COF', 'pixva' ); ?></span></div>
								<div class="pixva-sim-hotspot" data-part="mainboard" style="top:30%;right:15%;"><span><?php esc_html_e( 'مین‌برد', 'pixva' ); ?></span></div>
								<div class="pixva-sim-hotspot" data-part="powerboard" style="bottom:25%;right:20%;"><span><?php esc_html_e( 'برد پاور', 'pixva' ); ?></span></div>
								<div class="pixva-sim-display-msg" data-sim-screen-msg><p><?php esc_html_e( 'یک بخش را لمس کنید', 'pixva' ); ?></p></div>
							</div>
						</div>
						<div class="pixva-sim-info">
							<span class="pixva-badge" data-sim-tag><?php esc_html_e( 'آماده', 'pixva' ); ?></span>
							<h3 data-sim-title><?php esc_html_e( 'عیب محتمل هر بخش را قبل از باز کردن ببینید', 'pixva' ); ?></h3>
							<p data-sim-desc><?php esc_html_e( 'این شبیه‌ساز جای تشخیص نهایی کارگاه را نمی‌گیرد.', 'pixva' ); ?></p>
							<div class="pixva-sim-price-box" data-sim-price-box hidden>
								<span class="pixva-muted"><?php esc_html_e( 'کف قیمت قطعه و دستمزد', 'pixva' ); ?></span>
								<strong data-sim-price></strong>
							</div>
						</div>
					</div>
				</div>
			</div>
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

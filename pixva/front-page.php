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
 * هیروی صفحه اصلی (v25.0): هاله‌های نورانی آرام، ماکت تلویزیون با اسکن‌لاین
 * و شمارنده‌های متحرک آمار کارگاه.
 *
 * @return void
 */
function pixva_home_hero() {
	$title = (string) pixva_option( 'pixva_hero_title', __( 'تعمیر تخصصی تلویزیون با گارانتی کتبی ۱۸۰ روزه', 'pixva' ) );
	$lead  = (string) pixva_option(
		'pixva_hero_subtitle',
		__( 'کارگاه مرکزی پیکسوا در پاساژ علاءالدین؛ تعمیر پنل، بک‌لایت، برد پاور و مین‌برد با قطعات فابریک، دستگاه بندینگ صنعتی و تست نهایی با الگوهای کالیبراسیون.', 'pixva' )
	);
	$eta   = function_exists( 'pixva_control_options' ) ? pixva_control_options() : array();
	$hours = isset( $eta['hub_eta_hours'] ) ? $eta['hub_eta_hours'] : '۲ ساعت';
	?>
	<section class="pixva-hero pixva-hero--light">
		<div class="pixva-hero__aurora" aria-hidden="true">
			<span class="pixva-aurora-blob pixva-aurora-blob--a"></span>
			<span class="pixva-aurora-blob pixva-aurora-blob--b"></span>
			<span class="pixva-aurora-blob pixva-aurora-blob--c"></span>
		</div>

		<div class="pixva-container pixva-hero__grid">
			<div class="pixva-hero__content">
				<div class="pixva-hero__badges">
					<span class="pixva-badge pixva-badge--brand"><?php esc_html_e( 'مرکز تخصصی بندینگ و برد', 'pixva' ); ?></span>
					<span class="pixva-badge pixva-badge--success"><?php echo esc_html( sprintf( __( 'گارانتی کتبی %s روزه', 'pixva' ), pixva_fa_num( (string) pixva_warranty_days() ) ) ); ?></span>
					<span class="pixva-badge pixva-badge--accent"><?php esc_html_e( 'قطعه فابریک با هولوگرام اصالت', 'pixva' ); ?></span>
				</div>

				<h1><?php echo esc_html( $title ); ?></h1>
				<p class="pixva-hero__lead"><?php echo esc_html( $lead ); ?></p>

				<div class="pixva-hero__actions">
					<a class="pixva-btn pixva-btn--cta pixva-btn--shimmer" href="#quick-calc"><?php esc_html_e( 'استعلام سریع قیمت', 'pixva' ); ?></a>
					<a class="pixva-btn pixva-btn--ghost-dark" href="<?php echo esc_url( pixva_hub_url( 'ai-diagnostics' ) ); ?>"><?php esc_html_e( 'عیب‌یابی با هوش مصنوعی', 'pixva' ); ?></a>
				</div>

				<div class="pixva-stats">
					<div class="pixva-stat">
						<strong data-count-to="15" data-count-suffix="+">۰</strong>
						<span><?php esc_html_e( 'سال سابقه کارگاهی', 'pixva' ); ?></span>
					</div>
					<div class="pixva-stat">
						<strong data-count-to="8500" data-count-suffix="+">۰</strong>
						<span><?php esc_html_e( 'دستگاه تعمیرشده', 'pixva' ); ?></span>
					</div>
					<div class="pixva-stat">
						<strong data-count-to="<?php echo esc_attr( pixva_warranty_days() ); ?>">۰</strong>
						<span><?php esc_html_e( 'روز گارانتی کتبی', 'pixva' ); ?></span>
					</div>
					<div class="pixva-stat">
						<strong data-count-to="60">۰</strong>
						<span><?php esc_html_e( 'ابزار تخصصی آنلاین', 'pixva' ); ?></span>
					</div>
				</div>
			</div>

			<div class="pixva-hero__visual">
				<div class="pixva-tv-mockup">
					<span class="pixva-scanline" aria-hidden="true"></span>
					<div class="pixva-tv-mockup__bezel">
						<div class="pixva-tv-mockup__ui">
							<strong><?php esc_html_e( 'تست نهایی کارگاه', 'pixva' ); ?></strong>
							<span><?php esc_html_e( 'الگوی RGB · شیب خاکستری · یکنواختی بک‌لایت', 'pixva' ); ?></span>
							<div class="pixva-tv-mockup__bars" aria-hidden="true">
								<i></i><i></i><i></i><i></i><i></i><i></i>
							</div>
						</div>
					</div>
					<span class="pixva-tv-mockup__stand" aria-hidden="true"></span>
				</div>

				<span class="pixva-hero__chip pixva-hero__chip--top">
					<?php echo pixva_icon( 'truck' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<span><?php echo esc_html( sprintf( __( 'اعزام اورژانسی زیر %s', 'pixva' ), $hours ) ); ?></span>
				</span>
				<span class="pixva-hero__chip pixva-hero__chip--bottom">
					<?php echo pixva_icon( 'shield' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<span><?php esc_html_e( 'فاکتور رسمی و رسید کتبی', 'pixva' ); ?></span>
				</span>
			</div>
		</div>
	</section>
	<?php
}

/**
 * ویجت استعلام قیمت (بلافاصله پس از هیرو).
 *
 * @return void
 */
function pixva_home_quote() {
	?>
	<section class="pixva-quote-widget" id="quick-calc">
		<div class="pixva-container">
			<div class="pixva-quote-widget__card pixva-glass">
				<div class="pixva-quote-widget__head">
					<div>
						<span class="pixva-badge pixva-badge--brand"><?php esc_html_e( 'نرخ‌نامه مصوب بازار ۱۴۰۵', 'pixva' ); ?></span>
						<h2><?php esc_html_e( 'هزینه تعمیر را همین‌جا ببینید', 'pixva' ); ?></h2>
						<p><?php esc_html_e( 'برند، تکنولوژی، سایز و نوع خرابی را انتخاب کنید؛ بازه قیمت سمت سرور و بر پایه فرمول نرخ‌نامه محاسبه می‌شود.', 'pixva' ); ?></p>
					</div>
					<a class="pixva-btn pixva-btn--ghost-dark" href="<?php echo esc_url( pixva_page_url( 'rates' ) ); ?>"><?php esc_html_e( 'جدول کامل نرخ‌نامه', 'pixva' ); ?></a>
				</div>
				<?php pixva_render_calculator( array( 'compact' => true ) ); ?>
			</div>
		</div>
	</section>
	<?php
}

/**
 * مسیر پنج‌مرحله‌ای تعمیر با نقطه نورانی.
 *
 * @return void
 */
function pixva_home_journey() {
	$steps = array(
		array(
			'icon'  => 'phone',
			'title' => __( 'ثبت علائم و استعلام', 'pixva' ),
			'text'  => __( 'برند، سایز و علائم را در محاسبه‌گر یا دستیار هوشمند وارد کنید تا بازه قیمت و زمان تعمیر اعلام شود.', 'pixva' ),
		),
		array(
			'icon'  => 'truck',
			'title' => __( 'اعزام پیک ضدضربه', 'pixva' ),
			'text'  => __( 'جمع‌آوری دستگاه با جعبه پددار استاندارد در تهران و صدور رسید کتبی با مهر کارگاه.', 'pixva' ),
		),
		array(
			'icon'  => 'tool',
			'title' => __( 'عیب‌یابی کارگاهی', 'pixva' ),
			'text'  => __( 'تست جداگانه مسیر تغذیه، بک‌لایت، مین‌برد و پنل؛ هزینه قطعی پیش از تعویض هر قطعه تأیید شما می‌رسد.', 'pixva' ),
		),
		array(
			'icon'  => 'bolt',
			'title' => __( 'تعمیر با قطعه فابریک', 'pixva' ),
			'text'  => __( 'تعویض دست کامل بک‌لایت، بندینگ فلت COF یا ریبال آی‌سی با دستگاه BGA و قطعه دارای هولوگرام اصالت.', 'pixva' ),
		),
		array(
			'icon'  => 'shield',
			'title' => __( 'تست نهایی و گارانتی', 'pixva' ),
			'text'  => __( 'تحویل پس از اجرای الگوهای کالیبراسیون، همراه با کارت گارانتی دیجیتال و امکان پیگیری آنلاین پرونده.', 'pixva' ),
		),
	);
	?>
	<section class="pixva-section" id="journey">
		<div class="pixva-container">
			<div class="pixva-section-head">
				<span class="pixva-badge"><?php esc_html_e( 'مسیر تعمیر', 'pixva' ); ?></span>
				<h2><?php esc_html_e( 'از تماس تا تحویل، پنج مرحله شفاف', 'pixva' ); ?></h2>
				<p><?php esc_html_e( 'دستگاه بی‌دلیل باز نمی‌شود و هیچ قطعه‌ای بدون تأیید شما تعویض نمی‌گردد.', 'pixva' ); ?></p>
			</div>
			<div class="pixva-journey" data-pixva-journey>
				<span class="pixva-journey__track" aria-hidden="true"><span class="pixva-journey__spark"></span></span>
				<ol class="pixva-journey__steps">
					<?php foreach ( $steps as $index => $step ) : ?>
						<li class="pixva-journey__step">
							<span class="pixva-journey__dot"><?php echo pixva_icon( $step['icon'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
							<span>
								<span class="pixva-journey__no"><?php echo esc_html( pixva_fa_num( str_pad( (string) ( $index + 1 ), 2, '0', STR_PAD_LEFT ) ) ); ?></span>
								<strong><?php echo esc_html( $step['title'] ); ?></strong>
								<p><?php echo esc_html( $step['text'] ); ?></p>
							</span>
						</li>
					<?php endforeach; ?>
				</ol>
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
	<section class="pixva-section" id="calculator">
		<div class="pixva-container">
			<div class="pixva-section-head">
				<span class="pixva-badge"><?php esc_html_e( 'برآورد شفاف', 'pixva' ); ?></span>
				<h2><?php esc_html_e( 'هزینه تعمیر را قبل از آوردن دستگاه ببینید', 'pixva' ); ?></h2>
				<p><?php esc_html_e( 'برند، تکنولوژی، سایز و نوع خرابی را انتخاب کنید. بازه قیمت روی سرور محاسبه می‌شود، نه داخل مرورگر.', 'pixva' ); ?></p>
			</div>
			<?php pixva_render_calculator( array( 'compact' => true ) ); ?>
		</div>
	</section>
	<?php
}

/**
 * چهار کارت خدمت اصلی صفحه نخست.
 *
 * @return void
 */
function pixva_home_services() {
	$services = get_posts(
		array(
			'post_type'      => 'tv_services',
			'posts_per_page' => 4,
			'no_found_rows'  => true,
		)
	);
	$fallbacks = array_slice( pixva_service_fallbacks(), 0, 4 );
	?>
	<section class="pixva-section pixva-section--alt pixva-home-services" id="services">
		<div class="pixva-container">
			<div class="pixva-section-head">
				<span class="pixva-badge"><?php esc_html_e( 'خدمات تخصصی', 'pixva' ); ?></span>
				<h2><?php esc_html_e( 'تعمیر همان‌جایی که خرابی است', 'pixva' ); ?></h2>
				<p><?php esc_html_e( 'پنل را بی‌دلیل تعویض نمی‌کنیم؛ اول مسیر ارزان‌تر و قابل ضمانت بررسی می‌شود.', 'pixva' ); ?></p>
			</div>
			<div class="pixva-grid pixva-grid--4">
				<?php if ( ! empty( $services ) ) : ?>
					<?php foreach ( $services as $service ) : ?>
						<a class="pixva-card pixva-service-card pixva-reveal" href="<?php echo esc_url( get_permalink( $service ) ); ?>">
							<?php
							if ( has_post_thumbnail( $service ) ) {
								echo get_the_post_thumbnail( $service, 'pixva-card' );
							} else {
								echo pixva_icon( 'tool' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							}
							?>
							<h3><?php echo esc_html( get_the_title( $service ) ); ?></h3>
							<p><?php echo esc_html( get_the_excerpt( $service ) ); ?></p>
						</a>
					<?php endforeach; ?>
				<?php else : ?>
					<?php foreach ( $fallbacks as $service ) : ?>
						<a class="pixva-card pixva-service-card pixva-reveal" href="<?php echo esc_url( add_query_arg( 'problem', $service['key'], pixva_page_url( 'calculator' ) ) ); ?>">
							<?php echo pixva_icon( $service['icon'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							<h3><?php echo esc_html( $service['title'] ); ?></h3>
							<p><?php echo esc_html( $service['text'] ); ?></p>
							<span class="pixva-service-card__cta"><?php esc_html_e( 'برآورد هزینه این خدمت', 'pixva' ); ?></span>
						</a>
					<?php endforeach; ?>
				<?php endif; ?>
			</div>
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
					<blockquote class="pixva-card pixva-quote pixva-reveal">
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
	<section class="pixva-section pixva-section--alt pixva-reveal" id="error-codes">
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

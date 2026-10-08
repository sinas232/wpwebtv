<?php
/**
 * Front page: the product story — problem → tools → services → process →
 * brands → proof → knowledge → CTA. Every block is driven by real CMS
 * content; nothing is fabricated. v2.1.0 recomposed the presentation only
 * (hero visual, bento, editorial rows, journey band, brand wall).
 *
 * @package Pixva
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$pixva_services = pixva_posts( 'tv_services', 6 );
$pixva_brands   = pixva_posts( 'tv_brands', 12 );
$pixva_cases    = pixva_posts( 'repair_cases', 4 );
$pixva_posts    = pixva_posts( 'post', 4 );
$pixva_faq      = pixva_faq_items( 6 );

/**
 * Brand + model labels for a repair case (chips / meta).
 *
 * @param WP_Post|int $case Case post.
 * @return string[]
 */
$pixva_case_labels = static function ( $case ) {
	$model_id = (int) get_post_meta( $case->ID, '_pixva_case_model', true );
	if ( ! $model_id ) {
		return array();
	}
	return array_filter(
		array(
			pixva_model_brand( $model_id ),
			get_the_title( $model_id ),
		)
	);
};
?>
<main id="content" class="site-main">

	<?php /* Hero — dark diagnostic composition with a live device visual. */ ?>
	<section class="hero band band--deep band--grid" aria-labelledby="hero-title">
		<div class="container hero__grid">
			<div class="hero__text">
				<p class="eyebrow"><?php esc_html_e( 'پلتفرم تشخیص و تعمیر تلویزیون', 'pixva' ); ?></p>
				<h1 class="hero__title" id="hero-title">
					<?php
					/* The page title ("خانه") is a navigation label, not a headline. */
					$pixva_h1 = apply_filters( 'pixva_front_h1', __( 'تشخیص و تعمیر تلویزیون، شفاف و قابل پیگیری', 'pixva' ) );
					echo wp_kses( nl2br( esc_html( $pixva_h1 ) ), array( 'br' => array() ) );
					?>
				</h1>
				<p class="hero__lead"><?php echo esc_html( pixva_front_lead() ); ?></p>

				<form class="hero__quick" method="get" action="<?php echo esc_url( pixva_route_url( 'diagnosis' ) ); ?>" data-track-view="diagnosis_started">
					<label for="hero-problem"><?php echo wp_kses( pixva_icon( 'pulse' ), pixva_svg_allowed() ); ?><?php esc_html_e( 'تلویزیون شما چه مشکلی دارد؟', 'pixva' ); ?></label>
					<div class="input-group">
						<select id="hero-problem" name="problem">
							<option value=""><?php esc_html_e( 'انتخاب مشکل…', 'pixva' ); ?></option>
							<?php foreach ( pixva_diagnosis_problems() as $pixva_k => $pixva_p ) : ?>
								<option value="<?php echo esc_attr( $pixva_k ); ?>"><?php echo esc_html( $pixva_p['label'] ); ?></option>
							<?php endforeach; ?>
						</select>
						<button class="btn btn--accent" type="submit" data-track="cta_click" data-track-label="diagnosis" data-track-location="hero_quick"><?php esc_html_e( 'شروع تشخیص', 'pixva' ); ?></button>
					</div>
				</form>

				<ul class="hero__actions">
					<li><a class="btn btn--ghost-light" data-track="cta_click" data-track-label="booking" data-track-location="hero" href="<?php echo esc_url( pixva_route_url( 'booking' ) ); ?>"><?php esc_html_e( 'ثبت درخواست تعمیر', 'pixva' ); ?></a></li>
					<li><a class="btn btn--link-light" data-track="cta_click" data-track-label="tracking" data-track-location="hero" href="<?php echo esc_url( pixva_route_url( 'tracking' ) ); ?>"><?php esc_html_e( 'پیگیری درخواست', 'pixva' ); ?></a></li>
				</ul>
			</div>

			<div class="hero__visual" aria-hidden="true">
				<div class="device">
					<div class="device__frame">
						<div class="device__screen">
							<div class="device__grid"></div>
							<svg class="device__wave" viewBox="0 0 200 60" fill="none" preserveAspectRatio="none"><path d="M0 32 H44 L56 32 66 10 80 52 92 20 102 32 H200" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
							<svg class="device__wave device__wave-b" viewBox="0 0 200 60" fill="none" preserveAspectRatio="none"><path d="M0 36 H70 L80 36 88 24 98 46 106 32 114 36 H200" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
							<span class="device__dot device__dot--1"></span>
							<span class="device__dot device__dot--2"></span>
							<span class="device__dot device__dot--3"></span>
							<div class="device__scan"></div>
							<div class="device__hud">
								<span class="device__chip">HDMI 1</span>
								<span class="device__chip device__chip--ok"><span class="device__led"></span><?php esc_html_e( 'تصویر سالم', 'pixva' ); ?></span>
							</div>
						</div>
						<div class="device__logo">PIXVA · DIAGNOSTIC</div>
					</div>
					<div class="device__stand"></div>
					<div class="device__base"></div>
				</div>
				<div class="float-chip float-chip--a">
					<span class="float-chip__icon"><?php echo wp_kses( pixva_icon( 'pulse' ), pixva_svg_allowed() ); ?></span>
					<span><span class="float-chip__title"><?php esc_html_e( 'تشخیص آنلاین', 'pixva' ); ?></span><span class="float-chip__text"><?php esc_html_e( 'چند پرسش ساده', 'pixva' ); ?></span></span>
				</div>
				<div class="float-chip float-chip--b">
					<span class="float-chip__icon"><?php echo wp_kses( pixva_icon( 'calc' ), pixva_svg_allowed() ); ?></span>
					<span><span class="float-chip__title"><?php esc_html_e( 'برآورد هزینه', 'pixva' ); ?></span><span class="float-chip__text"><?php esc_html_e( 'فقط با قیمت‌گذاری واقعی', 'pixva' ); ?></span></span>
				</div>
				<div class="float-chip float-chip--c">
					<span class="float-chip__icon"><?php echo wp_kses( pixva_icon( 'grid' ), pixva_svg_allowed() ); ?></span>
					<span><span class="float-chip__title"><?php esc_html_e( 'تست پیکسل', 'pixva' ); ?></span><span class="float-chip__text"><?php esc_html_e( 'تمام‌صفحه و رایگان', 'pixva' ); ?></span></span>
				</div>
			</div>
		</div>
	</section>

	<?php if ( have_posts() ) : ?>
		<?php while ( have_posts() ) : ?>
			<?php
			the_post();
			$pixva_content = get_the_content();
			?>
			<?php if ( '' !== trim( wp_strip_all_tags( strip_shortcodes( $pixva_content ) ) ) ) : ?>
				<section class="section--tight">
					<div class="container container--narrow entry-content">
						<?php the_content(); ?>
					</div>
				</section>
			<?php endif; ?>
		<?php endwhile; ?>
	<?php endif; ?>

	<?php /* Problem selector — rich entry into the diagnosis wizard. */ ?>
	<section class="section" aria-labelledby="home-problems">
		<div class="container">
			<div class="section__head">
				<div class="section__head-main">
					<p class="eyebrow"><?php esc_html_e( 'عیب‌یابی', 'pixva' ); ?></p>
					<h2 class="section__title" id="home-problems"><?php esc_html_e( 'مشکل تلویزیونت کجاست؟', 'pixva' ); ?></h2>
					<p class="section__lead"><?php esc_html_e( 'یکی از مشکلات رایج را انتخاب کن؛ ابزار تشخیص، علت‌های محتمل را با درجه اطمینان نمایش می‌دهد.', 'pixva' ); ?></p>
				</div>
				<a class="link-more" href="<?php echo esc_url( pixva_route_url( 'problems' ) ); ?>"><?php esc_html_e( 'همه مشکلات', 'pixva' ); ?><?php echo wp_kses( pixva_icon( 'arrow' ), pixva_svg_allowed() ); ?></a>
			</div>
			<?php pixva_problem_selector( 8 ); ?>
		</div>
	</section>

	<?php /* Tools — bento composition with one dominant card. */ ?>
	<section class="section section--white" aria-labelledby="home-tools">
		<div class="container">
			<div class="section__head">
				<div class="section__head-main">
					<p class="eyebrow"><?php esc_html_e( 'ابزارهای رایگان', 'pixva' ); ?></p>
					<h2 class="section__title" id="home-tools"><?php esc_html_e( 'ابزارهای آنلاین عیب‌یابی', 'pixva' ); ?></h2>
					<p class="section__lead"><?php esc_html_e( 'چهار ابزار مستقل برای تشخیص، برآورد، تست صفحه و جست‌وجوی کدهای خطا.', 'pixva' ); ?></p>
				</div>
				<a class="link-more" href="<?php echo esc_url( pixva_route_url( 'tools' ) ); ?>"><?php esc_html_e( 'همه ابزارها', 'pixva' ); ?><?php echo wp_kses( pixva_icon( 'arrow' ), pixva_svg_allowed() ); ?></a>
			</div>
			<?php pixva_tool_cards(); ?>
		</div>
	</section>

	<?php /* Services — one featured panel + compact rows (hierarchy). */ ?>
	<?php if ( $pixva_services ) : ?>
		<section class="section" aria-labelledby="home-services">
			<div class="container">
				<div class="section__head">
					<div class="section__head-main">
						<p class="eyebrow"><?php esc_html_e( 'خدمات تعمیر', 'pixva' ); ?></p>
						<h2 class="section__title" id="home-services"><?php esc_html_e( 'تعمیر تخصصی تلویزیون', 'pixva' ); ?></h2>
						<p class="section__lead"><?php esc_html_e( 'از تعویض پنل و تعمیر برد تا رفع ایرادات نرم‌افزاری؛ متناسب با برند و مدل دستگاه.', 'pixva' ); ?></p>
					</div>
					<a class="link-more" href="<?php echo esc_url( pixva_route_url( 'services' ) ); ?>"><?php esc_html_e( 'همه خدمات', 'pixva' ); ?><?php echo wp_kses( pixva_icon( 'arrow' ), pixva_svg_allowed() ); ?></a>
				</div>
				<?php pixva_service_feature( $pixva_services[0] ); ?>
				<?php pixva_service_rows( array_slice( $pixva_services, 1 ) ); ?>
			</div>
		</section>
	<?php endif; ?>

	<?php /* Journey — process on a dark band. */ ?>
	<?php
	pixva_journey_section(
		'home-journey',
		__( 'از تشخیص تا تحویل، همه‌چیز شفاف است', 'pixva' ),
		array(
			array(
				'icon'  => 'search',
				'title' => __( 'مشکلت را انتخاب کن', 'pixva' ),
				'text'  => __( 'از میان مشکلات رایج، نزدیک‌ترین گزینه را انتخاب می‌کنی؛ تشخیص آنلاین رایگان است.', 'pixva' ),
			),
			array(
				'icon'  => 'tv',
				'title' => __( 'دستگاهت را معرفی کن', 'pixva' ),
				'text'  => __( 'برند، مدل و سن تلویزیون؛ هرچه دقیق‌تر وارد کنی، تحلیل دقیق‌تر است.', 'pixva' ),
			),
			array(
				'icon'  => 'pulse',
				'title' => __( 'نتیجه را ببین', 'pixva' ),
				'text'  => __( 'علت‌های محتمل با درجه اطمینان و فهرست بررسی‌های ایمن نمایش داده می‌شوند.', 'pixva' ),
			),
			array(
				'icon'  => 'route',
				'title' => __( 'ثبت یا پیگیری کن', 'pixva' ),
				'text'  => __( 'درخواست تعمیر ثبت می‌کنی و با کد اختصاصی، وضعیت آن را آنلاین دنبال می‌کنی.', 'pixva' ),
			),
		),
		__( 'چهار گام ساده و شفاف، از اولین بررسی تا تحویل دستگاه.', 'pixva' )
	);
	?>

	<?php /* Brand wall — typographic tiles from real brand data. */ ?>
	<?php if ( $pixva_brands ) : ?>
		<section class="section section--white" aria-labelledby="home-brands">
			<div class="container">
				<div class="section__head">
					<div class="section__head-main">
						<p class="eyebrow"><?php esc_html_e( 'برندها', 'pixva' ); ?></p>
						<h2 class="section__title" id="home-brands"><?php esc_html_e( 'تعمیر همه برندهای تلویزیون', 'pixva' ); ?></h2>
						<p class="section__lead"><?php esc_html_e( 'برندهایی که تحت پوشش تعمیر هستند؛ برند و مدل دستگاهت را پیدا کن و مستقیم به صفحه همان مدل برو.', 'pixva' ); ?></p>
					</div>
					<a class="link-more" href="<?php echo esc_url( pixva_route_url( 'brands' ) ); ?>"><?php esc_html_e( 'همه برندها', 'pixva' ); ?><?php echo wp_kses( pixva_icon( 'arrow' ), pixva_svg_allowed() ); ?></a>
				</div>
				<?php pixva_brand_wall( $pixva_brands ); ?>
			</div>
		</section>
	<?php endif; ?>

	<?php /* Portfolio — featured case + rows (editorial, not a card grid). */ ?>
	<?php if ( $pixva_cases ) : ?>
		<section class="section" aria-labelledby="home-cases">
			<div class="container">
				<div class="section__head">
					<div class="section__head-main">
						<p class="eyebrow"><?php esc_html_e( 'نمونه‌کارها', 'pixva' ); ?></p>
						<h2 class="section__title" id="home-cases"><?php esc_html_e( 'تعمیرهای واقعی، مستندسازی‌شده', 'pixva' ); ?></h2>
						<p class="section__lead"><?php esc_html_e( 'پرونده‌هایی از تعمیرهای انجام‌شده؛ قبل و بعد را مقایسه کن.', 'pixva' ); ?></p>
					</div>
					<a class="link-more" href="<?php echo esc_url( pixva_route_url( 'portfolio' ) ); ?>"><?php esc_html_e( 'همه نمونه‌کارها', 'pixva' ); ?><?php echo wp_kses( pixva_icon( 'arrow' ), pixva_svg_allowed() ); ?></a>
				</div>
				<?php
				$pixva_case = $pixva_cases[0];
				pixva_feature_card(
					$pixva_case,
					__( 'نمونه‌کار', 'pixva' ),
					pixva_format_date( get_post_time( 'U', true, $pixva_case ) ),
					$pixva_case_labels( $pixva_case )
				);
				pixva_rows(
					array_slice( $pixva_cases, 1 ),
					static function ( $p ) use ( $pixva_case_labels ) {
						return implode( ' · ', $pixva_case_labels( $p ) );
					}
				);
				?>
			</div>
		</section>
	<?php endif; ?>

	<?php /* Blog — featured article + rows. */ ?>
	<?php if ( $pixva_posts ) : ?>
		<section class="section section--white" aria-labelledby="home-blog">
			<div class="container">
				<div class="section__head">
					<div class="section__head-main">
						<p class="eyebrow"><?php esc_html_e( 'مجله', 'pixva' ); ?></p>
						<h2 class="section__title" id="home-blog"><?php esc_html_e( 'راهنمای عیب‌یابی و نگهداری', 'pixva' ); ?></h2>
						<p class="section__lead"><?php esc_html_e( 'مقاله‌های کاربردی درباره تشخیص، نگهداری و انتخاب تلویزیون.', 'pixva' ); ?></p>
					</div>
					<a class="link-more" href="<?php echo esc_url( pixva_route_url( 'blog' ) ); ?>"><?php esc_html_e( 'همه مقالات', 'pixva' ); ?><?php echo wp_kses( pixva_icon( 'arrow' ), pixva_svg_allowed() ); ?></a>
				</div>
				<?php
				$pixva_post = $pixva_posts[0];
				$pixva_cats = get_the_category( $pixva_post->ID );
				pixva_feature_card(
					$pixva_post,
					$pixva_cats ? $pixva_cats[0]->name : __( 'مقاله', 'pixva' ),
					pixva_format_date( get_post_time( 'U', true, $pixva_post ) ),
					$pixva_cats ? array( $pixva_cats[0]->name ) : array()
				);
				pixva_rows(
					array_slice( $pixva_posts, 1 ),
					static function ( $p ) {
						return pixva_format_date( get_post_time( 'U', true, $p ) );
					}
				);
				?>
			</div>
		</section>
	<?php endif; ?>

	<?php /* FAQ — split layout with a sticky intro. */ ?>
	<?php if ( $pixva_faq ) : ?>
		<section class="section" aria-labelledby="home-faq">
			<div class="container faq-layout">
				<div class="faq-layout__side">
					<p class="eyebrow"><?php esc_html_e( 'پرسش‌های متداول', 'pixva' ); ?></p>
					<h2 class="section__title" id="home-faq"><?php esc_html_e( 'سؤالات رایج درباره تعمیر تلویزیون', 'pixva' ); ?></h2>
					<p class="section__lead"><?php esc_html_e( 'اگر پاسخ سؤالت را پیدا نکردی، با ما تماس بگیر یا درخواست ثبت کن.', 'pixva' ); ?></p>
					<p><a class="link-more" href="<?php echo esc_url( pixva_route_url( 'faq' ) ); ?>"><?php esc_html_e( 'همه پرسش‌ها', 'pixva' ); ?><?php echo wp_kses( pixva_icon( 'arrow' ), pixva_svg_allowed() ); ?></a></p>
				</div>
				<div class="faq-layout__main">
					<?php pixva_faq_list( $pixva_faq ); ?>
				</div>
			</div>
		</section>
	<?php endif; ?>

	<section class="section--tight">
		<div class="container">
			<?php pixva_cta_box( '', '', 'front_page' ); ?>
		</div>
	</section>
</main>
<?php
get_footer();

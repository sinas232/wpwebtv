<?php
/**
 * Front page (v2.2.0 composition).
 *
 * Order: hero (diagnostic stage) → problem selector → tools (diagnosis as the
 * flagship) → services hierarchy → process timeline → brand wall → portfolio
 * (featured case or honest empty state) → journal (featured + supporting) →
 * trust (product facts only) → FAQ → closing CTA.
 *
 * Every block is driven by real CMS content or a real product fact. Nothing is
 * fabricated: no statistics, reviews, prices or guarantees.
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
$pixva_warranty = (int) pixva_warranty_policy_days();

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

/** Shared section head: eyebrow, title, lead, optional link. */
$pixva_head = static function ( $eyebrow, $id, $title, $lead, $link_url = '', $link_text = '' ) {
	echo '<header class="hx-head">';
	echo '<div class="hx-head__main">';
	echo '<p class="hx-eyebrow">' . esc_html( $eyebrow ) . '</p>';
	echo '<h2 class="hx-title" id="' . esc_attr( $id ) . '">' . esc_html( $title ) . '</h2>';
	if ( '' !== $lead ) {
		echo '<p class="hx-lead">' . esc_html( $lead ) . '</p>';
	}
	echo '</div>';
	if ( '' !== $link_url ) {
		echo '<a class="hx-more" href="' . esc_url( $link_url ) . '">' . esc_html( $link_text ) . wp_kses( pixva_icon( 'arrow' ), pixva_svg_allowed() ) . '</a>';
	}
	echo '</header>';
};
?>
<main id="content" class="site-main hx">

	<?php /* ---------- 1. HERO: diagnostic stage ---------- */ ?>
	<section class="hx-hero band band--deep band--grid" aria-labelledby="hero-title">
		<div class="container hx-hero__grid">
			<div class="hx-hero__copy">
				<p class="hx-kicker"><span class="hx-kicker__dot" aria-hidden="true"></span><?php esc_html_e( 'پلتفرم تشخیص و تعمیر تلویزیون', 'pixva' ); ?></p>
				<h1 class="hx-hero__title" id="hero-title">
					<?php
					$pixva_h1 = apply_filters( 'pixva_front_h1', __( "تلویزیونت کجا خراب است؟\nاول تشخیص بده، بعد تعمیر کن.", 'pixva' ) );
					echo wp_kses( nl2br( esc_html( $pixva_h1 ) ), array( 'br' => array() ) );
					?>
				</h1>
				<p class="hx-hero__lead"><?php echo esc_html( pixva_front_lead() ); ?></p>

				<form class="hx-quick" method="get" action="<?php echo esc_url( pixva_route_url( 'diagnosis' ) ); ?>" data-track-view="diagnosis_started">
					<label class="hx-quick__label" for="hero-problem"><?php echo wp_kses( pixva_icon( 'pulse' ), pixva_svg_allowed() ); ?><?php esc_html_e( 'تلویزیون شما چه مشکلی دارد؟', 'pixva' ); ?></label>
					<div class="hx-quick__row">
						<select id="hero-problem" name="problem">
							<option value=""><?php esc_html_e( 'انتخاب مشکل…', 'pixva' ); ?></option>
							<?php foreach ( pixva_diagnosis_problems() as $pixva_k => $pixva_p ) : ?>
								<option value="<?php echo esc_attr( $pixva_k ); ?>"><?php echo esc_html( $pixva_p['label'] ); ?></option>
							<?php endforeach; ?>
						</select>
						<button class="btn btn--accent hx-quick__submit" type="submit" data-track="cta_click" data-track-label="diagnosis" data-track-location="hero_quick"><?php esc_html_e( 'شروع تشخیص', 'pixva' ); ?></button>
					</div>
				</form>

				<ul class="hx-hero__actions">
					<li><a class="btn btn--ghost-light" data-track="cta_click" data-track-label="booking" data-track-location="hero" href="<?php echo esc_url( pixva_route_url( 'booking' ) ); ?>"><?php esc_html_e( 'ثبت درخواست تعمیر', 'pixva' ); ?></a></li>
					<li><a class="hx-textlink" data-track="cta_click" data-track-label="tracking" data-track-location="hero" href="<?php echo esc_url( pixva_route_url( 'tracking' ) ); ?>"><?php esc_html_e( 'پیگیری درخواست با کد', 'pixva' ); ?><?php echo wp_kses( pixva_icon( 'arrow' ), pixva_svg_allowed() ); ?></a></li>
				</ul>
			</div>

			<div class="hx-hero__stage" aria-hidden="true">
				<div class="hx-stage__halo"></div>
				<div class="device">
					<div class="device__frame">
						<div class="device__screen">
							<div class="device__grid"></div>
							<svg class="device__wave" viewBox="0 0 200 60" fill="none" preserveAspectRatio="none"><path d="M0 32 H44 L56 32 66 10 80 52 92 20 102 32 H200" /></svg>
							<svg class="device__wave device__wave-b" viewBox="0 0 200 60" fill="none" preserveAspectRatio="none"><path d="M0 36 H70 L80 36 88 24 98 46 110 36 H200" /></svg>
							<span class="device__dot device__dot--1"></span>
							<span class="device__dot device__dot--2"></span>
							<span class="device__dot device__dot--3"></span>
							<div class="device__scan"></div>
							<div class="device__hud">
								<span class="device__chip">HDMI 1</span>
								<span class="device__chip device__chip--ok"><span class="device__led"></span><?php esc_html_e( 'نمایش زنده', 'pixva' ); ?></span>
							</div>
						</div>
						<div class="device__logo">PIXVA · DIAGNOSTIC</div>
					</div>
					<div class="device__stand"></div>
					<div class="device__base"></div>
				</div>

				<div class="hx-readout">
					<p class="hx-readout__head"><span><?php esc_html_e( 'نمونه نمایشی', 'pixva' ); ?></span><small><?php esc_html_e( 'نتیجه واقعی را در ابزار ببینید', 'pixva' ); ?></small></p>
					<ol class="hx-readout__list">
						<li><span><?php esc_html_e( 'تغذیه برق', 'pixva' ); ?></span><i class="is-ok"></i></li>
						<li><span><?php esc_html_e( 'سیگنال ورودی', 'pixva' ); ?></span><i class="is-warn"></i></li>
						<li><span><?php esc_html_e( 'پنل نمایش', 'pixva' ); ?></span><i class="is-idle"></i></li>
					</ol>
				</div>

				<div class="hx-chip hx-chip--a">
					<span class="hx-chip__icon"><?php echo wp_kses( pixva_icon( 'pulse' ), pixva_svg_allowed() ); ?></span>
					<span><b><?php esc_html_e( 'تشخیص آنلاین', 'pixva' ); ?></b><small><?php esc_html_e( 'چند پرسش ساده', 'pixva' ); ?></small></span>
				</div>
				<div class="hx-chip hx-chip--b">
					<span class="hx-chip__icon"><?php echo wp_kses( pixva_icon( 'calc' ), pixva_svg_allowed() ); ?></span>
					<span><b><?php esc_html_e( 'برآورد هزینه', 'pixva' ); ?></b><small><?php esc_html_e( 'بر اساس نوع تعمیر', 'pixva' ); ?></small></span>
				</div>
			</div>
		</div>
	</section>

	<?php if ( have_posts() ) : ?>
		<?php
		while ( have_posts() ) :
			the_post();
			$pixva_content = get_the_content();
			if ( '' !== trim( wp_strip_all_tags( strip_shortcodes( $pixva_content ) ) ) ) :
				?>
				<section class="hx-intro">
					<div class="container container--narrow entry-content"><?php the_content(); ?></div>
				</section>
				<?php
			endif;
		endwhile;
		?>
	<?php endif; ?>

	<?php /* ---------- 2. PROBLEM SELECTOR ---------- */ ?>
	<section class="hx-problems" aria-labelledby="home-problems">
		<div class="container hx-split">
			<div class="hx-split__side">
				<p class="hx-eyebrow"><?php esc_html_e( 'عیب‌یابی', 'pixva' ); ?></p>
				<h2 class="hx-title" id="home-problems"><?php esc_html_e( 'مشکل تلویزیونت کجاست؟', 'pixva' ); ?></h2>
				<p class="hx-lead"><?php esc_html_e( 'یکی از مشکلات رایج را انتخاب کن. ابزار تشخیص، علت‌های محتمل را با درجه اطمینان نشان می‌دهد.', 'pixva' ); ?></p>
				<a class="hx-more" href="<?php echo esc_url( pixva_route_url( 'problems' ) ); ?>"><?php esc_html_e( 'همه مشکلات', 'pixva' ); ?><?php echo wp_kses( pixva_icon( 'arrow' ), pixva_svg_allowed() ); ?></a>
			</div>
			<div class="hx-split__main">
				<?php pixva_problem_selector( 8 ); ?>
			</div>
		</div>
	</section>

	<?php /* ---------- 3. TOOLS: diagnosis is the flagship ---------- */ ?>
	<section class="hx-tools" aria-labelledby="home-tools">
		<div class="container">
			<?php $pixva_head( __( 'ابزارهای رایگان', 'pixva' ), 'home-tools', __( 'از تشخیص شروع کن', 'pixva' ), __( 'ابزار اصلی تشخیص است؛ سه ابزار دیگر برای بررسی‌های مشخص کنار آن قرار دارند.', 'pixva' ), pixva_route_url( 'tools' ), __( 'همه ابزارها', 'pixva' ) ); ?>
			<?php pixva_tool_cards(); ?>
		</div>
	</section>

	<?php /* ---------- 4. SERVICES: hierarchy (feature + rows) ---------- */ ?>
	<?php if ( $pixva_services ) : ?>
		<section class="hx-services" aria-labelledby="home-services">
			<div class="container">
				<?php $pixva_head( __( 'خدمات تعمیر', 'pixva' ), 'home-services', __( 'تعمیر تخصصی تلویزیون', 'pixva' ), __( 'از تعویض پنل و تعمیر برد تا رفع ایرادات نرم‌افزاری؛ متناسب با برند و مدل دستگاه.', 'pixva' ), pixva_route_url( 'services' ), __( 'همه خدمات', 'pixva' ) ); ?>
				<?php pixva_service_feature( $pixva_services[0] ); ?>
				<?php pixva_service_rows( array_slice( $pixva_services, 1 ) ); ?>
			</div>
		</section>
	<?php endif; ?>

	<?php /* ---------- 5. PROCESS: timeline / journey ---------- */ ?>
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

	<?php /* ---------- 6. BRAND WALL (real CMS brands only) ---------- */ ?>
	<?php if ( $pixva_brands ) : ?>
		<section class="hx-brands" aria-labelledby="home-brands">
			<div class="container">
				<?php $pixva_head( __( 'برندها', 'pixva' ), 'home-brands', __( 'تعمیر همه برندهای تلویزیون', 'pixva' ), __( 'برندهایی که تحت پوشش تعمیر هستند؛ برند و مدل دستگاهت را پیدا کن و مستقیم به صفحه همان مدل برو.', 'pixva' ), pixva_route_url( 'brands' ), __( 'همه برندها', 'pixva' ) ); ?>
				<?php pixva_brand_wall( $pixva_brands ); ?>
			</div>
		</section>
	<?php endif; ?>

	<?php /* ---------- 7. PORTFOLIO: featured case, or honest empty state ---------- */ ?>
	<section class="hx-cases" aria-labelledby="home-cases">
		<div class="container">
			<?php $pixva_head( __( 'نمونه‌کارها', 'pixva' ), 'home-cases', __( 'تعمیرهای واقعی، مستندسازی‌شده', 'pixva' ), __( 'پرونده‌هایی از تعمیرهای انجام‌شده؛ قبل و بعد را مقایسه کن.', 'pixva' ), pixva_route_url( 'portfolio' ), __( 'همه نمونه‌کارها', 'pixva' ) ); ?>
			<?php if ( $pixva_cases ) : ?>
				<div class="hx-cases__grid">
					<div class="hx-cases__feature">
						<?php
						$pixva_case = $pixva_cases[0];
						pixva_feature_card(
							$pixva_case,
							__( 'نمونه‌کار', 'pixva' ),
							pixva_format_date( get_post_time( 'U', true, $pixva_case ) ),
							$pixva_case_labels( $pixva_case )
						);
						?>
					</div>
					<div class="hx-cases__rest">
						<?php
						pixva_rows(
							array_slice( $pixva_cases, 1 ),
							static function ( $p ) use ( $pixva_case_labels ) {
								return implode( ' · ', $pixva_case_labels( $p ) );
							}
						);
						?>
					</div>
				</div>
			<?php else : ?>
				<div class="hx-cases__empty">
					<p class="hx-cases__badge"><?php esc_html_e( 'بدون داده ساختگی', 'pixva' ); ?></p>
					<p class="hx-cases__title"><?php esc_html_e( 'نمونه‌کار منتشرشده‌ای هنوز ثبت نشده است', 'pixva' ); ?></p>
					<p class="hx-cases__text"><?php esc_html_e( 'پرونده‌های واقعی تعمیر پس از تأیید مدیر سایت در این بخش نمایش داده می‌شوند. تا آن زمان تصویر یا نتیجه‌ای نمایش داده نمی‌شود.', 'pixva' ); ?></p>
					<a class="btn btn--ghost" href="<?php echo esc_url( pixva_route_url( 'booking' ) ); ?>"><?php esc_html_e( 'ثبت درخواست تعمیر', 'pixva' ); ?></a>
				</div>
			<?php endif; ?>
		</div>
	</section>

	<?php /* ---------- 8. JOURNAL: featured article + supporting ---------- */ ?>
	<?php if ( $pixva_posts ) : ?>
		<section class="hx-journal" aria-labelledby="home-blog">
			<div class="container">
				<?php $pixva_head( __( 'مجله', 'pixva' ), 'home-blog', __( 'راهنمای عیب‌یابی و نگهداری', 'pixva' ), __( 'مقاله‌های کاربردی درباره تشخیص، نگهداری و انتخاب تلویزیون.', 'pixva' ), pixva_route_url( 'blog' ), __( 'همه مقالات', 'pixva' ) ); ?>
				<div class="hx-journal__grid">
					<div class="hx-journal__feature">
						<?php
						$pixva_post = $pixva_posts[0];
						$pixva_cats = get_the_category( $pixva_post->ID );
						pixva_feature_card(
							$pixva_post,
							$pixva_cats ? $pixva_cats[0]->name : __( 'مقاله', 'pixva' ),
							pixva_format_date( get_post_time( 'U', true, $pixva_post ) ),
							$pixva_cats ? array( $pixva_cats[0]->name ) : array()
						);
						?>
					</div>
					<div class="hx-journal__rest">
						<?php
						pixva_rows(
							array_slice( $pixva_posts, 1 ),
							static function ( $p ) {
								return pixva_format_date( get_post_time( 'U', true, $p ) );
							}
						);
						?>
					</div>
				</div>
			</div>
		</section>
	<?php endif; ?>

	<?php /* ---------- 9. TRUST: product facts only (no statistics) ---------- */ ?>
	<section class="hx-trust" aria-labelledby="home-trust">
		<div class="container">
			<h2 class="hx-trust__title" id="home-trust"><?php esc_html_e( 'هر درخواست، یک کد و یک مسیر روشن دارد', 'pixva' ); ?></h2>
			<ol class="hx-trust__list">
				<li>
					<span class="hx-trust__n" aria-hidden="true">01</span>
					<h3><?php esc_html_e( 'کد پیگیری اختصاصی', 'pixva' ); ?></h3>
					<p><?php esc_html_e( 'بعد از ثبت، کد درخواست نمایش داده می‌شود. با همان کد و شماره همراه، وضعیت را در صفحه پیگیری ببینید.', 'pixva' ); ?></p>
				</li>
				<li>
					<span class="hx-trust__n" aria-hidden="true">02</span>
					<h3><?php esc_html_e( 'تشخیص پیش از ثبت', 'pixva' ); ?></h3>
					<p><?php esc_html_e( 'ابزار تشخیص علت‌های محتمل و بررسی‌های ایمن را نشان می‌دهد، پیش از آنکه درخواستی ثبت کنید.', 'pixva' ); ?></p>
				</li>
				<li>
					<span class="hx-trust__n" aria-hidden="true">03</span>
					<h3><?php esc_html_e( 'گارانتی', 'pixva' ); ?></h3>
					<p>
						<?php
						if ( $pixva_warranty > 0 ) {
							/* translators: %s: number of warranty days. */
							echo esc_html( sprintf( __( 'مدت گارانتی تعمیر: %s روز. جزئیات در صفحه گارانتی.', 'pixva' ), pixva_fa_num( $pixva_warranty ) ) );
						} else {
							esc_html_e( 'مدت و شرایط گارانتی هر تعمیر در صفحه گارانتی اعلام می‌شود.', 'pixva' );
						}
						?>
					</p>
				</li>
			</ol>
		</div>
	</section>

	<?php /* ---------- 10. FAQ ---------- */ ?>
	<?php if ( $pixva_faq ) : ?>
		<section class="hx-faq" aria-labelledby="home-faq">
			<div class="container hx-split">
				<div class="hx-split__side">
					<p class="hx-eyebrow"><?php esc_html_e( 'پرسش‌های متداول', 'pixva' ); ?></p>
					<h2 class="hx-title" id="home-faq"><?php esc_html_e( 'سؤالات رایج درباره تعمیر تلویزیون', 'pixva' ); ?></h2>
					<p class="hx-lead"><?php esc_html_e( 'اگر پاسخ سؤالت را پیدا نکردی، با ما تماس بگیر یا درخواست ثبت کن.', 'pixva' ); ?></p>
					<a class="hx-more" href="<?php echo esc_url( pixva_route_url( 'faq' ) ); ?>"><?php esc_html_e( 'همه پرسش‌ها', 'pixva' ); ?><?php echo wp_kses( pixva_icon( 'arrow' ), pixva_svg_allowed() ); ?></a>
				</div>
				<div class="hx-split__main">
					<?php pixva_faq_list( $pixva_faq ); ?>
				</div>
			</div>
		</section>
	<?php endif; ?>

	<?php /* ---------- 11. FINAL CTA ---------- */ ?>
	<section class="hx-closing">
		<div class="container">
			<?php pixva_cta_box( '', '', 'front_page' ); ?>
		</div>
	</section>
</main>
<?php
get_footer();

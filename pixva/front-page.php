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
<main id="main" class="site-main hx">

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

	<?php
	/* ---------- 3. TOOLS: Diagnosis is the flagship; three secondary tools with lower weight ---------- */
	$pixva_tools = pixva_tools();
	$pixva_diag  = $pixva_tools['diagnosis'];
	?>
	<section class="hx-tools" aria-labelledby="home-tools">
		<div class="container">
			<header class="hx-head">
				<div class="hx-head__main">
					<p class="hx-eyebrow"><?php esc_html_e( 'ابزارهای رایگان', 'pixva' ); ?></p>
					<h2 class="hx-title" id="home-tools"><?php esc_html_e( 'از تشخیص شروع کن', 'pixva' ); ?></h2>
					<p class="hx-lead"><?php esc_html_e( 'ابزار اصلی تشخیص است؛ سه ابزار دیگر برای بررسی‌های مشخص کنار آن قرار دارند.', 'pixva' ); ?></p>
				</div>
				<a class="hx-more" href="<?php echo esc_url( pixva_route_url( 'tools' ) ); ?>"><?php esc_html_e( 'همه ابزارها', 'pixva' ); ?><?php echo wp_kses( pixva_icon( 'arrow' ), pixva_svg_allowed() ); ?></a>
			</header>

			<div class="hx-tools__grid">
				<article class="hx-flagship">
					<p class="hx-flagship__kicker"><?php esc_html_e( 'ابزار اصلی', 'pixva' ); ?></p>
					<h3 class="hx-flagship__title"><a href="<?php echo esc_url( $pixva_diag['url'] ); ?>"><?php echo esc_html( $pixva_diag['title'] ); ?></a></h3>
					<p class="hx-flagship__text"><?php echo esc_html( $pixva_diag['desc'] ); ?></p>
					<ol class="hx-flagship__steps" aria-label="<?php esc_attr_e( 'مراحل تشخیص', 'pixva' ); ?>">
						<li><span><?php esc_html_e( 'دستگاه', 'pixva' ); ?></span></li>
						<li><span><?php esc_html_e( 'نشانه‌ها', 'pixva' ); ?></span></li>
						<li><span><?php esc_html_e( 'نتیجه با درجه اطمینان', 'pixva' ); ?></span></li>
					</ol>
					<a class="btn btn--accent btn--lg hx-flagship__cta" href="<?php echo esc_url( $pixva_diag['url'] ); ?>" data-track="cta_click" data-track-label="diagnosis" data-track-location="home_tools"><?php esc_html_e( 'شروع تشخیص رایگان', 'pixva' ); ?><?php echo wp_kses( pixva_icon( 'arrow' ), pixva_svg_allowed() ); ?></a>
					<svg class="hx-flagship__art" viewBox="0 0 320 120" fill="none" aria-hidden="true" focusable="false"><path d="M0 84 H70 L86 84 102 24 124 102 142 52 156 84 H320" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/></svg>
				</article>

				<ul class="hx-tools__side">
					<?php
					$pixva_secondary = array(
						array( 'price_calculator', 'hx-tool--calc' ),
						array( 'pixel_test', 'hx-tool--pixel' ),
					);
					foreach ( $pixva_secondary as $pixva_item ) :
						$pixva_t = $pixva_tools[ $pixva_item[0] ];
						?>
						<li class="hx-tool <?php echo esc_attr( $pixva_item[1] ); ?>">
							<a href="<?php echo esc_url( $pixva_t['url'] ); ?>">
								<span class="hx-tool__icon" aria-hidden="true"><?php echo wp_kses( pixva_icon( $pixva_t['icon'] ), pixva_svg_allowed() ); ?></span>
								<span class="hx-tool__body">
									<span class="hx-tool__title"><?php echo esc_html( $pixva_t['title'] ); ?></span>
									<span class="hx-tool__text"><?php echo esc_html( $pixva_t['desc'] ); ?></span>
								</span>
								<span class="hx-tool__go" aria-hidden="true"><?php echo wp_kses( pixva_icon( 'arrow' ), pixva_svg_allowed() ); ?></span>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>

				<?php $pixva_t = $pixva_tools['error_codes']; ?>
				<a class="hx-tool hx-tool--codes" href="<?php echo esc_url( $pixva_t['url'] ); ?>">
					<span class="hx-tool__icon" aria-hidden="true"><?php echo wp_kses( pixva_icon( $pixva_t['icon'] ), pixva_svg_allowed() ); ?></span>
					<span class="hx-tool__body">
						<span class="hx-tool__title"><?php echo esc_html( $pixva_t['title'] ); ?></span>
						<span class="hx-tool__text"><?php echo esc_html( $pixva_t['desc'] ); ?></span>
					</span>
					<span class="hx-tool__go" aria-hidden="true"><?php echo wp_kses( pixva_icon( 'arrow' ), pixva_svg_allowed() ); ?></span>
				</a>
			</div>
		</div>
	</section>

	<?php /* ---------- 4. SERVICES: one lead service + numbered list ---------- */ ?>
	<?php if ( $pixva_services ) : ?>
		<section class="hx-services" aria-labelledby="home-services">
			<div class="container">
				<header class="hx-head">
					<div class="hx-head__main">
						<p class="hx-eyebrow"><?php esc_html_e( 'خدمات تعمیر', 'pixva' ); ?></p>
						<h2 class="hx-title" id="home-services"><?php esc_html_e( 'تعمیر تخصصی تلویزیون', 'pixva' ); ?></h2>
						<p class="hx-lead"><?php esc_html_e( 'از تعویض پنل و تعمیر برد تا رفع ایرادات نرم‌افزاری؛ متناسب با برند و مدل دستگاه.', 'pixva' ); ?></p>
					</div>
					<a class="hx-more" href="<?php echo esc_url( pixva_route_url( 'services' ) ); ?>"><?php esc_html_e( 'همه خدمات', 'pixva' ); ?><?php echo wp_kses( pixva_icon( 'arrow' ), pixva_svg_allowed() ); ?></a>
				</header>

				<div class="hx-services__grid">
					<?php $pixva_lead = $pixva_services[0]; ?>
					<article class="hx-svc-lead">
						<?php if ( has_post_thumbnail( $pixva_lead ) ) : ?>
							<div class="hx-svc-lead__media"><?php echo get_the_post_thumbnail( $pixva_lead, 'large', array( 'loading' => 'lazy', 'decoding' => 'async', 'alt' => '' ) ); ?></div>
						<?php endif; ?>
						<p class="hx-svc-lead__kicker"><?php esc_html_e( 'خدمت اصلی', 'pixva' ); ?></p>
						<h3 class="hx-svc-lead__title"><a href="<?php echo esc_url( get_permalink( $pixva_lead ) ); ?>"><?php echo esc_html( get_the_title( $pixva_lead ) ); ?></a></h3>
						<?php $pixva_lead_ex = pixva_plain_excerpt( $pixva_lead, 30 ); ?>
						<?php if ( '' !== trim( $pixva_lead_ex ) ) : ?>
							<p class="hx-svc-lead__text"><?php echo esc_html( $pixva_lead_ex ); ?></p>
						<?php endif; ?>
						<a class="hx-more" href="<?php echo esc_url( get_permalink( $pixva_lead ) ); ?>"><?php esc_html_e( 'مشاهده خدمت', 'pixva' ); ?><?php echo wp_kses( pixva_icon( 'arrow' ), pixva_svg_allowed() ); ?></a>
					</article>

					<?php $pixva_rest = array_slice( $pixva_services, 1 ); ?>
					<?php if ( $pixva_rest ) : ?>
						<ol class="hx-svc-list">
							<?php foreach ( $pixva_rest as $pixva_i => $pixva_sv ) : ?>
								<li>
									<a href="<?php echo esc_url( get_permalink( $pixva_sv ) ); ?>">
										<span class="hx-svc-list__num" aria-hidden="true"><?php echo esc_html( pixva_fa_num( sprintf( '%02d', $pixva_i + 2 ) ) ); ?></span>
										<span class="hx-svc-list__body">
											<span class="hx-svc-list__title"><?php echo esc_html( get_the_title( $pixva_sv ) ); ?></span>
											<?php $pixva_ex = pixva_plain_excerpt( $pixva_sv, 14 ); ?>
											<?php if ( '' !== trim( $pixva_ex ) ) : ?>
												<span class="hx-svc-list__text"><?php echo esc_html( $pixva_ex ); ?></span>
											<?php endif; ?>
										</span>
										<span class="hx-svc-list__go" aria-hidden="true"><?php echo wp_kses( pixva_icon( 'arrow' ), pixva_svg_allowed() ); ?></span>
									</a>
								</li>
							<?php endforeach; ?>
						</ol>
					<?php endif; ?>
				</div>
			</div>
		</section>
	<?php endif; ?>

	<?php /* ---------- 5. PROCESS: a real journey with connected stages ---------- */ ?>
	<section class="hx-journey" aria-labelledby="home-journey">
		<div class="container">
			<header class="hx-head">
				<div class="hx-head__main">
					<p class="hx-eyebrow"><?php esc_html_e( 'روند کار', 'pixva' ); ?></p>
					<h2 class="hx-title" id="home-journey"><?php esc_html_e( 'از تشخیص تا تحویل، همه‌چیز شفاف است', 'pixva' ); ?></h2>
					<p class="hx-lead"><?php esc_html_e( 'چهار گام ساده و شفاف، از اولین بررسی تا تحویل دستگاه.', 'pixva' ); ?></p>
				</div>
			</header>
			<ol class="hx-journey__track">
				<li class="hx-journey__stage">
					<span class="hx-journey__node" aria-hidden="true"><?php echo wp_kses( pixva_icon( 'search' ), pixva_svg_allowed() ); ?></span>
					<span class="hx-journey__no"><?php esc_html_e( 'گام ۱', 'pixva' ); ?></span>
					<h3><?php esc_html_e( 'مشکلت را انتخاب کن', 'pixva' ); ?></h3>
					<p><?php esc_html_e( 'از میان مشکلات رایج، نزدیک‌ترین گزینه را انتخاب می‌کنی؛ تشخیص آنلاین رایگان است.', 'pixva' ); ?></p>
				</li>
				<li class="hx-journey__stage">
					<span class="hx-journey__node" aria-hidden="true"><?php echo wp_kses( pixva_icon( 'tv' ), pixva_svg_allowed() ); ?></span>
					<span class="hx-journey__no"><?php esc_html_e( 'گام ۲', 'pixva' ); ?></span>
					<h3><?php esc_html_e( 'دستگاهت را معرفی کن', 'pixva' ); ?></h3>
					<p><?php esc_html_e( 'برند، مدل و سن تلویزیون؛ هرچه دقیق‌تر وارد کنی، تحلیل دقیق‌تر است.', 'pixva' ); ?></p>
				</li>
				<li class="hx-journey__stage">
					<span class="hx-journey__node" aria-hidden="true"><?php echo wp_kses( pixva_icon( 'pulse' ), pixva_svg_allowed() ); ?></span>
					<span class="hx-journey__no"><?php esc_html_e( 'گام ۳', 'pixva' ); ?></span>
					<h3><?php esc_html_e( 'نتیجه را ببین', 'pixva' ); ?></h3>
					<p><?php esc_html_e( 'علت‌های محتمل با درجه اطمینان و فهرست بررسی‌های ایمن نمایش داده می‌شوند.', 'pixva' ); ?></p>
				</li>
				<li class="hx-journey__stage">
					<span class="hx-journey__node" aria-hidden="true"><?php echo wp_kses( pixva_icon( 'route' ), pixva_svg_allowed() ); ?></span>
					<span class="hx-journey__no"><?php esc_html_e( 'گام ۴', 'pixva' ); ?></span>
					<h3><?php esc_html_e( 'ثبت یا پیگیری کن', 'pixva' ); ?></h3>
					<p><?php esc_html_e( 'درخواست تعمیر ثبت می‌کنی و با کد اختصاصی، وضعیت آن را آنلاین دنبال می‌کنی.', 'pixva' ); ?></p>
				</li>
			</ol>
		</div>
	</section>

	<?php /* ---------- 6. BRAND WALL: real CMS brands only ---------- */ ?>
	<?php if ( $pixva_brands ) : ?>
		<section class="hx-brands" aria-labelledby="home-brands">
			<div class="container">
				<header class="hx-head">
					<div class="hx-head__main">
						<p class="hx-eyebrow"><?php esc_html_e( 'برندها', 'pixva' ); ?></p>
						<h2 class="hx-title" id="home-brands"><?php esc_html_e( 'تعمیر همه برندهای تلویزیون', 'pixva' ); ?></h2>
						<p class="hx-lead"><?php esc_html_e( 'برند و مدل دستگاهت را پیدا کن و مستقیم به صفحه همان مدل برو.', 'pixva' ); ?></p>
					</div>
					<a class="hx-more" href="<?php echo esc_url( pixva_route_url( 'brands' ) ); ?>"><?php esc_html_e( 'همه برندها', 'pixva' ); ?><?php echo wp_kses( pixva_icon( 'arrow' ), pixva_svg_allowed() ); ?></a>
				</header>
				<ul class="hx-wall">
					<?php foreach ( $pixva_brands as $pixva_b ) : ?>
						<?php
						$pixva_en   = (string) get_post_meta( $pixva_b->ID, '_pixva_brand_en', true );
						$pixva_logo = absint( get_post_meta( $pixva_b->ID, '_pixva_brand_logo', true ) );
						$pixva_name = trim( (string) preg_replace( '/^تعمیر\s+(تلویزیون\s+)?/u', '', get_the_title( $pixva_b ) ) );
						?>
						<li class="hx-wall__tile">
							<a href="<?php echo esc_url( get_permalink( $pixva_b ) ); ?>">
								<?php if ( $pixva_logo ) : ?>
									<?php echo wp_get_attachment_image( $pixva_logo, 'thumbnail', false, array( 'alt' => '', 'loading' => 'lazy', 'class' => 'hx-wall__logo' ) ); ?>
								<?php endif; ?>
								<span class="hx-wall__name"><?php echo esc_html( $pixva_name ); ?></span>
								<?php if ( '' !== $pixva_en ) : ?>
									<span class="hx-wall__en" lang="en" dir="ltr"><?php echo esc_html( $pixva_en ); ?></span>
								<?php endif; ?>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
		</section>
	<?php endif; ?>

	<?php /* ---------- 7. PORTFOLIO: featured case, or honest placeholder composition ---------- */ ?>
	<section class="hx-cases" aria-labelledby="home-cases">
		<div class="container">
			<header class="hx-head">
				<div class="hx-head__main">
					<p class="hx-eyebrow"><?php esc_html_e( 'نمونه‌کارها', 'pixva' ); ?></p>
					<h2 class="hx-title" id="home-cases"><?php esc_html_e( 'تعمیرهای واقعی، مستندسازی‌شده', 'pixva' ); ?></h2>
					<p class="hx-lead"><?php esc_html_e( 'پرونده‌هایی از تعمیرهای انجام‌شده؛ قبل و بعد را مقایسه کن.', 'pixva' ); ?></p>
				</div>
				<a class="hx-more" href="<?php echo esc_url( pixva_route_url( 'portfolio' ) ); ?>"><?php esc_html_e( 'همه نمونه‌کارها', 'pixva' ); ?><?php echo wp_kses( pixva_icon( 'arrow' ), pixva_svg_allowed() ); ?></a>
			</header>

			<?php if ( $pixva_cases ) : ?>
				<?php $pixva_case = $pixva_cases[0]; ?>
				<div class="hx-cases__grid">
					<article class="hx-case-lead">
						<div class="hx-case-lead__media">
							<?php if ( has_post_thumbnail( $pixva_case ) ) : ?>
								<?php echo get_the_post_thumbnail( $pixva_case, 'large', array( 'loading' => 'lazy', 'decoding' => 'async', 'alt' => '' ) ); ?>
							<?php else : ?>
								<span class="hx-case-lead__icon" aria-hidden="true"><?php echo wp_kses( pixva_icon( 'picture' ), pixva_svg_allowed() ); ?></span>
							<?php endif; ?>
						</div>
						<div class="hx-case-lead__body">
							<p class="hx-case-lead__meta"><?php echo esc_html( implode( ' · ', array_filter( array_merge( $pixva_case_labels( $pixva_case ), array( pixva_format_date( get_post_time( 'U', true, $pixva_case ) ) ) ) ) ) ); ?></p>
							<h3 class="hx-case-lead__title"><a href="<?php echo esc_url( get_permalink( $pixva_case ) ); ?>"><?php echo esc_html( get_the_title( $pixva_case ) ); ?></a></h3>
						</div>
					</article>
					<?php if ( count( $pixva_cases ) > 1 ) : ?>
						<ol class="hx-case-list">
							<?php foreach ( array_slice( $pixva_cases, 1 ) as $pixva_c ) : ?>
								<li><a href="<?php echo esc_url( get_permalink( $pixva_c ) ); ?>"><span class="hx-case-list__title"><?php echo esc_html( get_the_title( $pixva_c ) ); ?></span><span class="hx-case-list__meta"><?php echo esc_html( implode( ' · ', $pixva_case_labels( $pixva_c ) ) ); ?></span></a></li>
							<?php endforeach; ?>
						</ol>
					<?php endif; ?>
				</div>
			<?php else : ?>
				<div class="hx-cases__placeholder">
					<div class="hx-cases__frame" aria-hidden="true">
						<span class="hx-cases__frame-label"><?php esc_html_e( 'قبل', 'pixva' ); ?></span>
						<span class="hx-cases__frame-label hx-cases__frame-label--after"><?php esc_html_e( 'بعد', 'pixva' ); ?></span>
						<svg viewBox="0 0 240 140" fill="none"><rect x="8" y="8" width="224" height="124" rx="10" stroke="currentColor" stroke-dasharray="6 6"/><path d="M40 104 L92 56 L124 84 L156 52 L200 104" stroke="currentColor" stroke-width="2.5" stroke-linejoin="round"/><circle cx="170" cy="40" r="8" fill="currentColor"/></svg>
					</div>
					<div class="hx-cases__copy">
						<p class="hx-cases__badge"><?php esc_html_e( 'در انتظار پرونده‌های منتشرشده', 'pixva' ); ?></p>
						<h3 class="hx-cases__title"><?php esc_html_e( 'نمونه‌کار واقعی هنوز منتشر نشده است', 'pixva' ); ?></h3>
						<p class="hx-cases__text"><?php esc_html_e( 'پرونده‌های واقعی تعمیر، پس از تأیید مدیر سایت و با رضایت مشتری، در این بخش نمایش داده می‌شوند. تا آن زمان تصویر یا نتیجه‌ای نمایش داده نمی‌شود.', 'pixva' ); ?></p>
						<a class="btn btn--primary" href="<?php echo esc_url( pixva_route_url( 'booking' ) ); ?>"><?php esc_html_e( 'ثبت درخواست تعمیر', 'pixva' ); ?></a>
					</div>
				</div>
			<?php endif; ?>
		</div>
	</section>

	<?php /* ---------- 8. JOURNAL: editorial lead + text list (no card grid) ---------- */ ?>
	<?php if ( $pixva_posts ) : ?>
		<section class="hx-journal" aria-labelledby="home-blog">
			<div class="container">
				<header class="hx-head">
					<div class="hx-head__main">
						<p class="hx-eyebrow"><?php esc_html_e( 'مجله', 'pixva' ); ?></p>
						<h2 class="hx-title" id="home-blog"><?php esc_html_e( 'راهنمای عیب‌یابی و نگهداری', 'pixva' ); ?></h2>
						<p class="hx-lead"><?php esc_html_e( 'مقاله‌های کاربردی درباره تشخیص، نگهداری و انتخاب تلویزیون.', 'pixva' ); ?></p>
					</div>
					<a class="hx-more" href="<?php echo esc_url( pixva_route_url( 'blog' ) ); ?>"><?php esc_html_e( 'همه مقالات', 'pixva' ); ?><?php echo wp_kses( pixva_icon( 'arrow' ), pixva_svg_allowed() ); ?></a>
				</header>
				<div class="hx-journal__grid">
					<?php $pixva_art = $pixva_posts[0]; ?>
					<?php $pixva_cats = get_the_category( $pixva_art->ID ); ?>
					<article class="hx-article-lead">
						<p class="hx-article-lead__kicker"><?php echo esc_html( $pixva_cats ? $pixva_cats[0]->name : __( 'مقاله', 'pixva' ) ); ?></p>
						<h3 class="hx-article-lead__title"><a href="<?php echo esc_url( get_permalink( $pixva_art ) ); ?>"><?php echo esc_html( get_the_title( $pixva_art ) ); ?></a></h3>
						<?php $pixva_art_ex = pixva_plain_excerpt( $pixva_art, 32 ); ?>
						<?php if ( '' !== trim( $pixva_art_ex ) ) : ?>
							<p class="hx-article-lead__text"><?php echo esc_html( $pixva_art_ex ); ?></p>
						<?php endif; ?>
						<p class="hx-article-lead__date"><time datetime="<?php echo esc_attr( get_the_date( 'c', $pixva_art ) ); ?>"><?php echo esc_html( pixva_format_date( get_post_time( 'U', true, $pixva_art ) ) ); ?></time></p>
					</article>
					<?php if ( count( $pixva_posts ) > 1 ) : ?>
						<ol class="hx-article-list">
							<?php foreach ( array_slice( $pixva_posts, 1 ) as $pixva_a ) : ?>
								<li>
									<a href="<?php echo esc_url( get_permalink( $pixva_a ) ); ?>">
										<time datetime="<?php echo esc_attr( get_the_date( 'c', $pixva_a ) ); ?>"><?php echo esc_html( pixva_format_date( get_post_time( 'U', true, $pixva_a ) ) ); ?></time>
										<span class="hx-article-list__title"><?php echo esc_html( get_the_title( $pixva_a ) ); ?></span>
									</a>
								</li>
							<?php endforeach; ?>
						</ol>
					<?php endif; ?>
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
					<span class="hx-trust__n" aria-hidden="true">۰۱</span>
					<h3><?php esc_html_e( 'کد پیگیری اختصاصی', 'pixva' ); ?></h3>
					<p><?php esc_html_e( 'بعد از ثبت، کد درخواست نمایش داده می‌شود. با همان کد و شماره همراه، وضعیت را در صفحه پیگیری ببینید.', 'pixva' ); ?></p>
				</li>
				<li>
					<span class="hx-trust__n" aria-hidden="true">۰۲</span>
					<h3><?php esc_html_e( 'تشخیص پیش از ثبت', 'pixva' ); ?></h3>
					<p><?php esc_html_e( 'ابزار تشخیص علت‌های محتمل و بررسی‌های ایمن را نشان می‌دهد، پیش از آنکه درخواستی ثبت کنید.', 'pixva' ); ?></p>
				</li>
				<li>
					<span class="hx-trust__n" aria-hidden="true">۰۳</span>
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
	<?php $pixva_phone = pixva_primary_phone(); ?>
	<section class="hx-closing" aria-labelledby="home-cta">
		<div class="container">
			<div class="hx-cta">
				<div class="hx-cta__copy">
					<h2 class="hx-cta__title" id="home-cta"><?php esc_html_e( 'مطمئن نیستید ایراد از کجاست؟', 'pixva' ); ?></h2>
					<p class="hx-cta__text"><?php esc_html_e( 'اول با ابزار تشخیص علت‌های محتمل را ببینید، یا مستقیم درخواست بررسی ثبت کنید.', 'pixva' ); ?></p>
				</div>
				<div class="hx-cta__actions">
					<a class="btn btn--accent btn--lg" href="<?php echo esc_url( pixva_route_url( 'booking' ) ); ?>" data-track="cta_click" data-track-label="booking" data-track-location="front_page"><?php esc_html_e( 'ثبت درخواست تعمیر', 'pixva' ); ?></a>
					<a class="btn btn--ghost-light btn--lg" href="<?php echo esc_url( pixva_route_url( 'diagnosis' ) ); ?>" data-track="cta_click" data-track-label="diagnosis" data-track-location="front_page"><?php esc_html_e( 'شروع تشخیص', 'pixva' ); ?></a>
					<?php if ( '' !== $pixva_phone ) : ?>
						<a class="hx-cta__phone" href="<?php echo esc_url( pixva_tel_href( $pixva_phone ) ); ?>" data-track="cta_click" data-track-label="phone" data-track-location="front_page"><?php echo esc_html( $pixva_phone ); ?></a>
					<?php endif; ?>
				</div>
			</div>
		</div>
	</section>

</main>
<?php
get_footer();

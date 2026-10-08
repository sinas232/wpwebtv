<?php
/**
 * Front page. Every section renders only when real content exists
 * (§34–§36): no testimonials, statistics, ratings or phantom features.
 *
 * @package Pixva
 * @since   2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
get_header();
$pixva_front    = get_post( (int) get_option( 'page_on_front' ) );
$pixva_lead     = pixva_front_lead();
$pixva_problems = pixva_diagnosis_problems();
?>
<main id="main" class="site-main">
	<section class="hero" aria-labelledby="hero-title">
		<div class="container hero__grid">
			<div class="hero__text">
				<p class="eyebrow"><?php echo esc_html( pixva_business_name() ); ?></p>
				<h1 class="hero__title" id="hero-title"><?php echo esc_html( ( $pixva_front && 'خانه' !== $pixva_front->post_title && 'Home' !== $pixva_front->post_title ) ? get_the_title( $pixva_front ) : __( 'تشخیص و تعمیر تلویزیون، شفاف و قابل پیگیری', 'pixva' ) ); ?></h1>
				<p class="hero__lead"><?php echo esc_html( $pixva_lead ); ?></p>
				<form class="hero__quick" method="get" action="<?php echo esc_url( pixva_route_url( 'diagnosis' ) ); ?>" data-track-submit="cta_click" data-track-label="diagnosis" data-track-location="home_hero">
					<label for="hero-problem"><?php esc_html_e( 'تلویزیون شما چه مشکلی دارد؟', 'pixva' ); ?></label>
					<div class="input-group">
						<select id="hero-problem" name="problem">
							<option value=""><?php esc_html_e( 'انتخاب کنید…', 'pixva' ); ?></option>
							<?php foreach ( $pixva_problems as $pixva_k => $pixva_p ) : ?>
								<option value="<?php echo esc_attr( $pixva_k ); ?>"><?php echo esc_html( $pixva_p['label'] ); ?></option>
							<?php endforeach; ?>
						</select>
						<input type="hidden" name="step" value="1">
						<button class="btn btn--accent" type="submit"><?php esc_html_e( 'شروع تشخیص', 'pixva' ); ?></button>
					</div>
				</form>
				<p class="hero__actions">
					<a class="btn btn--ghost-light" data-track="cta_click" data-track-label="booking" data-track-location="home_hero" href="<?php echo esc_url( pixva_route_url( 'booking' ) ); ?>"><?php esc_html_e( 'ثبت درخواست تعمیر', 'pixva' ); ?></a>
					<a class="btn btn--link-light" href="<?php echo esc_url( pixva_route_url( 'tracking' ) ); ?>"><?php esc_html_e( 'پیگیری درخواست', 'pixva' ); ?></a>
				</p>
			</div>
			<div class="hero__visual" aria-hidden="true">
				<div class="tv-mock"><div class="tv-mock__screen"><span></span><span></span><span></span><span></span><span></span><span></span></div><div class="tv-mock__stand"></div></div>
			</div>
		</div>
	</section>

	<?php
	if ( $pixva_front && '' !== trim( $pixva_front->post_content ) ) {
		echo '<section class="section"><div class="container entry-content">';
		echo apply_filters( 'the_content', $pixva_front->post_content ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped,WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- core content filter.
		echo '</div></section>';
	}
	?>

	<?php pixva_section_open( 'home-problems', __( 'مشکل رایج خود را انتخاب کنید', 'pixva' ), pixva_route_url( 'problems' ) ); ?>
		<?php if ( ! pixva_problem_tiles( 8 ) ) : ?>
			<ul class="grid grid--tiles">
				<?php foreach ( array_slice( $pixva_problems, 0, 8, true ) as $pixva_k => $pixva_p ) : ?>
					<li class="tile"><a href="
					<?php
					echo esc_url(
						pixva_route_url(
							'diagnosis',
							array(
								'problem' => $pixva_k,
								'step'    => '1',
							)
						)
					);
					?>
												"><span class="tile__title"><?php echo esc_html( $pixva_p['label'] ); ?></span><span class="tile__text"><?php echo esc_html( $pixva_p['desc'] ); ?></span></a></li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	<?php pixva_section_close(); ?>

	<?php pixva_section_open( 'home-tools', __( 'ابزارهای رایگان عیب‌یابی', 'pixva' ), pixva_route_url( 'tools' ) ); ?>
		<?php pixva_tool_cards(); ?>
	<?php pixva_section_close(); ?>

	<?php
	$pixva_services = get_posts(
		array(
			'post_type'      => 'tv_services',
			'post_status'    => 'publish',
			'posts_per_page' => 6,
			'orderby'        => array(
				'menu_order' => 'ASC',
				'title'      => 'ASC',
			),
			'no_found_rows'  => true,
		)
	);
	if ( $pixva_services ) :
		pixva_section_open( 'home-services', __( 'خدمات تعمیر', 'pixva' ), pixva_route_url( 'services' ) );
		pixva_card_grid( $pixva_services );
		pixva_section_close();
	endif;
	?>

	<?php pixva_section_open( 'home-process', __( 'روند کار چطور است؟', 'pixva' ) ); ?>
		<ol class="steps">
			<li class="steps__item"><h3><?php esc_html_e( 'ثبت درخواست', 'pixva' ); ?></h3><p><?php esc_html_e( 'مشخصات دستگاه و ایراد را ثبت می‌کنید و کد پیگیری می‌گیرید.', 'pixva' ); ?></p></li>
			<li class="steps__item"><h3><?php esc_html_e( 'بررسی و اعلام هزینه', 'pixva' ); ?></h3><p><?php esc_html_e( 'پس از کارشناسی، علت خرابی و هزینه پیش از شروع کار به شما اعلام می‌شود.', 'pixva' ); ?></p></li>
			<li class="steps__item"><h3><?php esc_html_e( 'تعمیر با تأیید شما', 'pixva' ); ?></h3><p><?php esc_html_e( 'تعمیر فقط بعد از تأیید هزینه انجام می‌شود.', 'pixva' ); ?></p></li>
			<li class="steps__item"><h3><?php esc_html_e( 'پیگیری و تحویل', 'pixva' ); ?></h3><p><?php esc_html_e( 'وضعیت هر مرحله را با کد پیگیری آنلاین می‌بینید.', 'pixva' ); ?></p></li>
		</ol>
	<?php pixva_section_close(); ?>

	<?php
	$pixva_brands = get_posts(
		array(
			'post_type'      => 'tv_brands',
			'post_status'    => 'publish',
			'posts_per_page' => 24,
			'orderby'        => array(
				'menu_order' => 'ASC',
				'title'      => 'ASC',
			),
			'no_found_rows'  => true,
		)
	);
	if ( $pixva_brands ) :
		pixva_section_open( 'home-brands', __( 'برندها', 'pixva' ), pixva_route_url( 'brands' ) );
		echo '<ul class="chips chips--lg">';
		foreach ( $pixva_brands as $pixva_b ) {
			$pixva_en = (string) get_post_meta( $pixva_b->ID, '_pixva_brand_en', true );
			echo '<li><a class="chip" href="' . esc_url( get_permalink( $pixva_b ) ) . '">' . esc_html( trim( preg_replace( '/^تعمیر\s+(تلویزیون\s+)?/u', '', get_the_title( $pixva_b ) ) ) ) . ( '' !== $pixva_en ? ' <span lang="en" dir="ltr">' . esc_html( $pixva_en ) . '</span>' : '' ) . '</a></li>';
		}
		echo '</ul>';
		pixva_section_close();
	endif;

	$pixva_cases = get_posts(
		array(
			'post_type'      => 'repair_cases',
			'post_status'    => 'publish',
			'posts_per_page' => 3,
			'no_found_rows'  => true,
		)
	);
	if ( $pixva_cases ) :
		pixva_section_open( 'home-cases', __( 'نمونه‌کارهای اخیر', 'pixva' ), pixva_route_url( 'portfolio' ) );
		pixva_card_grid( $pixva_cases );
		pixva_section_close();
	endif;

	$pixva_posts = get_posts(
		array(
			'post_type'           => 'post',
			'post_status'         => 'publish',
			'posts_per_page'      => 3,
			'no_found_rows'       => true,
			'ignore_sticky_posts' => true,
		)
	);
	if ( $pixva_posts ) :
		pixva_section_open( 'home-blog', __( 'از مجله پیکسوا', 'pixva' ), pixva_route_url( 'blog' ) );
		pixva_card_grid( $pixva_posts );
		pixva_section_close();
	endif;

	$pixva_faq = pixva_faq_items( 'general' );
	if ( $pixva_faq ) :
		pixva_section_open( 'home-faq', __( 'پرسش‌های متداول', 'pixva' ), pixva_route_url( 'faq' ) );
		pixva_faq_list( array_slice( $pixva_faq, 0, 6 ), true );
		pixva_section_close();
	endif;
	?>

	<div class="container"><?php pixva_cta_box( '', '', 'home_footer' ); ?></div>
</main>
<?php
get_footer();

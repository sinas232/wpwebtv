<?php
/**
 * هدر قالب پیکسوا — نسخه ۴٫۰٫۰ (Master Prompt v14 / Bento & SaaS)
 *
 * ساختار تازه: نوار باریک ابسیدین + ناوبری قرصی شناور شیشه‌ای
 * (max-width 1000px، چسبان و وسط‌چین با backdrop-filter) + دروئر
 * تمام‌صفحه موبایل با کنترل‌های لمسی ۴۸px.
 *
 * جایگاه header با Elementor Theme Builder کاملاً قابل بازنویسی است.
 *
 * @package Pixva
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$pixva_phone    = function_exists( 'pixva_support_phone' ) ? pixva_support_phone() : '';
$pixva_cta_text = (string) pixva_option( 'pixva_header_cta_text', __( 'رزرو فوری', 'pixva' ) );
$pixva_cta_url  = (string) pixva_option( 'pixva_header_cta_url', is_front_page() ? '#booking' : pixva_page_url( 'calculator' ) );
$pixva_cta_on   = (bool) pixva_option( 'pixva_header_cta_enabled', true );
$pixva_topbar   = (string) pixva_option( 'pixva_topbar_text', __( 'اعزام تکنسین زیر ۲ ساعت | گارانتی کتبی | قطعات فابریک با هولوگرام اصالت', 'pixva' ) );
$pixva_control  = function_exists( 'pixva_control_options' ) ? pixva_control_options() : array();
$pixva_eta      = isset( $pixva_control['hub_eta_hours'] ) ? $pixva_control['hub_eta_hours'] : '۲ ساعت';
$pixva_days     = (string) pixva_option( 'pixva_working_hours', __( 'شنبه تا پنجشنبه ۹ تا ۲۰', 'pixva' ) );
$pixva_hours    = sprintf(
	/* translators: 1: روزهای کاری، 2: بازه زمانی اعزام */
	__( '%1$s | اعزام اورژانسی زیر %2$s', 'pixva' ),
	$pixva_days,
	$pixva_eta
);
$pixva_brand_color = (string) pixva_option( 'pixva_brand_color', '#4F46E5' );
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?> class="<?php echo esc_attr( function_exists( 'pixva_html_class_attr' ) ? pixva_html_class_attr() : 'no-js' ); ?>">
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="theme-color" content="<?php echo esc_attr( $pixva_brand_color ); ?>">
	<script>document.documentElement.className = document.documentElement.className.replace( /\bno-js\b/, 'js' );</script>
	<link rel="profile" href="https://gmpg.org/xfn/11">
	<?php
	if ( function_exists( 'pixva_preload_font' ) ) {
		pixva_preload_font();
	}
	wp_head();
	?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="skip-link" href="#content"><?php esc_html_e( 'پرش به محتوا', 'pixva' ); ?></a>

<?php
/*
 * لایه ۴٫۰٫۰: اگر قالب Theme Builder المنتور برای جایگاه header وجود داشته
 * باشد همان چاپ می‌شود؛ در غیر این صورت هدر قرصی شناور پوسته رندر می‌شود
 * (wp_head/body_class و هوک‌های حیاتی همیشه سالم می‌مانند).
 */
if ( ! function_exists( 'pixva_elementor_location' ) || ! pixva_elementor_location( 'header' ) ) :
?>

<?php if ( pixva_option( 'pixva_topbar_enabled', true ) ) : ?>
	<div class="bx-topline">
		<div class="bx-wrap bx-topline__inner">
			<p class="bx-topline__item">
				<?php echo pixva_bento_icons( 'bolt' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<span><?php echo esc_html( $pixva_topbar ); ?></span>
			</p>
			<p class="bx-topline__item bx-topline__item--hours">
				<?php echo pixva_bento_icons( 'clock' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<span><?php echo esc_html( $pixva_hours ); ?></span>
			</p>
		</div>
	</div>
<?php endif; ?>

<header class="bx-header" data-bx-header>
	<div class="bx-navpill">
		<a class="bx-navpill__logo" href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home" aria-label="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>">
			<span class="bx-logo-desktop"><?php pixva_the_logo( 'dark' ); ?></span>
			<span class="bx-logo-mobile">
				<?php
				if ( function_exists( 'pixva_mobile_logo_html' ) && pixva_mobile_logo_html() ) {
					echo pixva_mobile_logo_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				} else {
					pixva_the_logo( 'dark' );
				}
				?>
			</span>
		</a>

		<nav class="bx-navpill__nav" aria-label="<?php esc_attr_e( 'ناوبری اصلی', 'pixva' ); ?>">
			<?php
			wp_nav_menu(
				array(
					'theme_location' => 'primary',
					'container'        => false,
					'menu_class'       => 'bx-nav__menu',
					'depth'            => 1,
					'fallback_cb'      => 'pixva_bento_nav_fallback',
				)
			);
			?>
		</nav>

		<div class="bx-navpill__actions">
			<?php if ( '' !== $pixva_phone ) : ?>
				<a class="bx-call-chip" href="<?php echo esc_url( pixva_tel_href( $pixva_phone ) ); ?>">
					<?php echo pixva_bento_icons( 'phone' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<span><?php echo esc_html( pixva_fa_num( $pixva_phone ) ); ?></span>
				</a>
			<?php endif; ?>
			<?php if ( $pixva_cta_on ) : ?>
				<a class="bx-btn bx-btn--accent bx-btn--sm" href="<?php echo pixva_bento_url( $pixva_cta_url ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>">
					<?php echo pixva_bento_icons( 'bolt' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<span><?php echo esc_html( $pixva_cta_text ); ?></span>
				</a>
			<?php endif; ?>
			<button type="button" class="bx-burger" data-bx-burger aria-expanded="false" aria-controls="bx-drawer" aria-label="<?php esc_attr_e( 'باز کردن منو', 'pixva' ); ?>">
				<?php echo pixva_bento_icons( 'menu' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</button>
		</div>
	</div>
</header>

<div id="bx-drawer" class="bx-drawer" data-bx-drawer aria-hidden="true" aria-label="<?php esc_attr_e( 'منوی تمام‌صفحه', 'pixva' ); ?>">
	<div class="bx-drawer__head">
		<?php pixva_the_logo( 'light' ); ?>
		<button type="button" class="bx-drawer__close" data-bx-drawer-close aria-label="<?php esc_attr_e( 'بستن منو', 'pixva' ); ?>">
			<?php echo pixva_bento_icons( 'close' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</button>
	</div>

	<div class="bx-drawer__body">
		<nav aria-label="<?php esc_attr_e( 'هاب‌های تخصصی', 'pixva' ); ?>">
			<p class="bx-drawer__label"><?php esc_html_e( 'هاب‌های تخصصی', 'pixva' ); ?></p>
			<ul class="bx-drawer__links">
				<?php
				if ( function_exists( 'pixva_hubs' ) ) :
					foreach ( pixva_hubs() as $pixva_hub ) :
						?>
						<li>
							<a class="bx-drawer__link" href="<?php echo esc_url( pixva_hub_url( $pixva_hub['slug'] ) ); ?>">
								<?php echo pixva_bento_icons( $pixva_hub['icon'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								<span>
									<?php echo esc_html( $pixva_hub['title'] ); ?>
									<small><?php echo esc_html( $pixva_hub['short'] ); ?></small>
								</span>
							</a>
						</li>
					<?php endforeach; ?>
				<?php endif; ?>
				<li>
					<a class="bx-drawer__link" href="<?php echo esc_url( pixva_blog_url() ); ?>">
						<?php echo pixva_bento_icons( 'book' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<span><?php esc_html_e( 'مجله تعمیرات', 'pixva' ); ?></span>
					</a>
				</li>
			</ul>
		</nav>

		<div class="bx-drawer__actions">
			<a class="bx-btn bx-btn--accent" href="<?php echo pixva_bento_url( $pixva_cta_url ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>">
				<?php echo pixva_bento_icons( 'bolt' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<span><?php echo esc_html( $pixva_cta_text ); ?></span>
			</a>
			<?php if ( '' !== $pixva_phone ) : ?>
				<a class="bx-btn bx-btn--on-dark" href="<?php echo esc_url( pixva_tel_href( $pixva_phone ) ); ?>">
					<?php echo pixva_bento_icons( 'phone' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<span><?php echo esc_html( pixva_fa_num( $pixva_phone ) ); ?></span>
				</a>
			<?php endif; ?>
			<a class="bx-btn bx-btn--on-dark" href="<?php echo esc_url( pixva_whatsapp_url( __( 'سلام، برای تعمیر تلویزیون مشاوره فوری می‌خواهم.', 'pixva' ) ) ); ?>" target="_blank" rel="noopener noreferrer">
				<?php echo pixva_bento_icons( 'whatsapp' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<span><?php esc_html_e( 'واتساپ کارگاه', 'pixva' ); ?></span>
			</a>
		</div>

		<p class="bx-drawer__note"><?php echo esc_html( $pixva_hours ); ?></p>
	</div>
</div>

<?php endif; /* پایان جایگاه header — لایه ۴٫۰٫۰ */ ?>

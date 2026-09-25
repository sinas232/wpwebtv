<?php
/**
 * هدر قالب پیکسوا — Master Specification v25.0 (Calm Premium)
 *
 * ساختار: نوار اطلاع‌رسانی باریک، هدر چسبان شیشه‌ای با مگامنوی پنج هاب،
 * دکمه مرجانی «استعلام سریع قیمت» و دروئر موبایل با آکاردئون هاب‌ها.
 *
 * @package Pixva
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$pixva_phone    = pixva_support_phone();
$pixva_cta_text = (string) pixva_option( 'pixva_header_cta_text', __( 'استعلام سریع قیمت', 'pixva' ) );
$pixva_cta_url  = (string) pixva_option( 'pixva_header_cta_url', pixva_page_url( 'calculator' ) );
$pixva_topbar   = (string) pixva_option( 'pixva_topbar_text', __( 'اعزام تکنسین در تهران زیر ۲ ساعت | گارانتی کتبی ۱۸۰ روزه | قطعات فابریک با هولوگرام اصالت', 'pixva' ) );
$pixva_control  = function_exists( 'pixva_control_options' ) ? pixva_control_options() : array();
$pixva_eta      = isset( $pixva_control['hub_eta_hours'] ) ? $pixva_control['hub_eta_hours'] : '۲ ساعت';
$pixva_hours    = sprintf(
	/* translators: %s: بازه زمانی اعزام */
	__( 'شنبه تا پنجشنبه ۹ تا ۲۰ | اعزام اورژانسی زیر %s', 'pixva' ),
	$pixva_eta
);
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?> class="no-js">
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="theme-color" content="#123B4A">
	<script>document.documentElement.className = document.documentElement.className.replace( /\bno-js\b/, 'js' );</script>
	<link rel="profile" href="https://gmpg.org/xfn/11">
	<?php pixva_preload_font(); ?>
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="skip-link" href="#content"><?php esc_html_e( 'پرش به محتوا', 'pixva' ); ?></a>

<?php if ( pixva_option( 'pixva_topbar_enabled', true ) ) : ?>
	<div class="pixva-topbar">
		<div class="pixva-container pixva-topbar__inner">
			<p class="pixva-topbar__text">
				<?php echo pixva_icon( 'bolt' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<span><?php echo esc_html( $pixva_topbar ); ?></span>
			</p>
			<p class="pixva-topbar__hours">
				<?php echo pixva_icon( 'clock' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<span><?php echo esc_html( $pixva_hours ); ?></span>
			</p>
		</div>
	</div>
<?php endif; ?>

<header class="pixva-header pixva-header--glass" data-pixva-header>
	<div class="pixva-container pixva-header__inner">
		<a class="pixva-logo-link" href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home">
			<?php pixva_the_logo( 'dark' ); ?>
		</a>

		<?php pixva_render_mega_menu( 'mega' ); ?>

		<div class="pixva-header__actions">
			<a class="pixva-header-phone" href="<?php echo esc_url( pixva_tel_href( $pixva_phone ) ); ?>">
				<?php echo pixva_icon( 'phone' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<span data-phone-text><?php echo esc_html( pixva_fa_num( $pixva_phone ) ); ?></span>
			</a>
			<a class="pixva-btn pixva-btn--cta pixva-btn--sm pixva-btn--shimmer pixva-header-cta" href="<?php echo esc_url( $pixva_cta_url ); ?>">
				<?php echo pixva_icon( 'bolt' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<span><?php echo esc_html( $pixva_cta_text ); ?></span>
			</a>
			<button type="button" class="pixva-burger" data-pixva-burger aria-expanded="false" aria-controls="pixva-drawer" aria-label="<?php esc_attr_e( 'باز کردن منو', 'pixva' ); ?>">
				<?php echo pixva_icon( 'menu' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</button>
		</div>
	</div>
</header>

<div class="pixva-overlay" data-pixva-overlay></div>
<div id="pixva-drawer" class="pixva-drawer" data-pixva-drawer aria-hidden="true">
	<div class="pixva-drawer__head">
		<?php pixva_the_logo( 'dark' ); ?>
		<button type="button" class="pixva-burger" data-pixva-drawer-close aria-label="<?php esc_attr_e( 'بستن منو', 'pixva' ); ?>">
			<?php echo pixva_icon( 'close' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</button>
	</div>

	<?php pixva_render_hub_accordions( 'drawer-hub' ); ?>

	<div class="pixva-drawer__extra">
		<?php
		wp_nav_menu(
			array(
				'theme_location'  => 'primary',
				'container'       => 'nav',
				'container_class' => 'pixva-nav pixva-nav--drawer',
				'menu_class'      => 'pixva-nav__list',
				'fallback_cb'     => false,
				'depth'           => 1,
				'pixva_id_prefix' => 'drawer-',
			)
		);
		?>
	</div>

	<div class="pixva-drawer__actions">
		<a class="pixva-btn pixva-btn--cta" href="<?php echo esc_url( $pixva_cta_url ); ?>"><?php echo esc_html( $pixva_cta_text ); ?></a>
		<a class="pixva-btn pixva-btn--ghost-dark" href="<?php echo esc_url( pixva_tel_href( $pixva_phone ) ); ?>"><?php esc_html_e( 'تماس با کارگاه', 'pixva' ); ?></a>
	</div>
</div>

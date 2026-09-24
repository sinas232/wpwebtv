<?php
/**
 * هدر قالب پیکسوا
 *
 * @package Pixva
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$pixva_phone = pixva_support_phone();
$pixva_cta   = (string) pixva_option( 'pixva_header_cta_text', __( 'درخواست مشاوره رایگان', 'pixva' ) );
$pixva_cta_u = (string) pixva_option( 'pixva_header_cta_url', '/contact/' );
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
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
				<?php echo pixva_icon( 'bolt' ); ?>
				<span><?php echo esc_html( (string) pixva_option( 'pixva_topbar_text', __( 'ارسال رایگان دستگاه در تهران | گارانتی ۱۸۰ روزه تعمیرات', 'pixva' ) ) ); ?></span>
			</p>
			<p class="pixva-topbar__hours"><?php echo esc_html( pixva_fa_num( (string) pixva_option( 'pixva_hours_weekdays', 'شنبه تا پنجشنبه ۹ تا ۲۰' ) ) ); ?></p>
		</div>
	</div>
<?php endif; ?>

<header class="pixva-header">
	<div class="pixva-container pixva-header__inner">
		<a class="pixva-logo-link" href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home">
			<?php pixva_the_logo( 'light' ); ?>
		</a>

		<?php
		wp_nav_menu(
			array(
				'theme_location'       => 'primary',
				'container'            => 'nav',
				'container_class'      => 'pixva-nav',
				'container_aria_label' => esc_attr__( 'منوی اصلی', 'pixva' ),
				'menu_class'           => 'pixva-nav__list',
				'fallback_cb'          => 'pixva_fallback_menu',
				'depth'                => 2,
			)
		);
		?>

		<div class="pixva-header__actions">
			<a class="pixva-header-phone" href="<?php echo esc_url( pixva_tel_href( $pixva_phone ) ); ?>">
				<?php echo pixva_icon( 'phone' ); ?>
				<span data-phone-text><?php echo esc_html( pixva_fa_num( $pixva_phone ) ); ?></span>
			</a>
			<a class="pixva-btn pixva-btn--cta pixva-btn--sm pixva-btn--bolt pixva-header-cta" href="<?php echo esc_url( $pixva_cta_u ); ?>"><?php echo esc_html( $pixva_cta ); ?></a>
			<button type="button" class="pixva-burger" data-pixva-burger aria-expanded="false" aria-controls="pixva-drawer" aria-label="<?php esc_attr_e( 'باز کردن منو', 'pixva' ); ?>">
				<?php echo pixva_icon( 'menu' ); ?>
			</button>
		</div>
	</div>
</header>

<div class="pixva-overlay" data-pixva-overlay></div>
<div id="pixva-drawer" class="pixva-drawer" data-pixva-drawer aria-hidden="true">
	<div class="pixva-drawer__head">
		<?php pixva_the_logo( 'light' ); ?>
		<button type="button" class="pixva-burger" data-pixva-drawer-close aria-label="<?php esc_attr_e( 'بستن منو', 'pixva' ); ?>">
			<?php echo pixva_icon( 'close' ); ?>
		</button>
	</div>
	<?php
	wp_nav_menu(
		array(
			'theme_location'  => 'primary',
			'container'       => 'nav',
			'container_class' => 'pixva-nav',
			'menu_class'      => 'pixva-nav__list',
			'fallback_cb'     => 'pixva_fallback_menu',
			'depth'           => 2,
			'pixva_id_prefix' => 'drawer-',
		)
	);
	?>
	<a class="pixva-btn pixva-btn--cta" href="<?php echo esc_url( pixva_tel_href( $pixva_phone ) ); ?>"><?php esc_html_e( 'تماس با کارگاه', 'pixva' ); ?></a>
</div>

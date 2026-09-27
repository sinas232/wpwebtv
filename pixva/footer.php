<?php
/**
 * فوتر قالب پیکسوا — نسخه ۴٫۰٫۰ (Bento & SaaS)
 *
 * فوتر ابسیدین چهارستونه: برند و گواهی‌ها، هاب‌های تخصصی، تماس اورژانسی
 * و نمادهای اعتماد + QR استعلام گارانتی. جایگاه footer با Theme Builder
 * المنتور قابل بازنویسی است؛ داک اقدام موبایل، چت‌بات و wp_footer همیشه
 * از پوسته رندر می‌شوند (بیرون جایگاه).
 *
 * @package Pixva
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$pixva_phone   = function_exists( 'pixva_support_phone' ) ? pixva_support_phone() : '';
$pixva_control = function_exists( 'pixva_control_options' ) ? pixva_control_options() : array();
$pixva_address = isset( $pixva_control['hub_address'] )
	? $pixva_control['hub_address']
	: (string) pixva_option( 'pixva_workshop_address', __( 'تهران، خیابان جمهوری، تقاطع حافظ، پاساژ علاءالدین، طبقه ۴، واحد ۴۱۲', 'pixva' ) );
$pixva_eta     = isset( $pixva_control['hub_eta_hours'] ) ? $pixva_control['hub_eta_hours'] : '۲ ساعت';
$pixva_qr_url  = (string) pixva_option( 'pixva_footer_qr_url', pixva_page_url( 'tracking' ) );

/*
 * مقصد «درخواست تعمیر» در داک موبایل: در صفحه اصلی لنگر موتور رزرو بنتو و
 * در سایر صفحه‌ها برگه محاسبه‌گر (بدون لنگر مرده).
 */
$pixva_dock_booking_url  = pixva_page_url( 'calculator' );
$pixva_dock_booking_text = (string) pixva_option( 'pixva_dock_booking_text', __( 'درخواست تعمیرکار', 'pixva' ) );
if ( is_front_page() ) {
	$pixva_dock_booking_url = (string) pixva_option( 'pixva_hero_cta_link', '#booking' );
}

$pixva_footer_from_builder = function_exists( 'pixva_elementor_location' ) && pixva_elementor_location( 'footer' );
?>
<?php if ( ! $pixva_footer_from_builder ) : ?>
<footer class="bx-footer">
	<?php if ( is_active_sidebar( 'footer-widgets' ) ) : ?>
		<div class="bx-wrap bx-footer__widgets">
			<?php dynamic_sidebar( 'footer-widgets' ); ?>
		</div>
	<?php endif; ?>

	<div class="bx-wrap bx-footer__grid">

		<section class="bx-footer__col bx-footer__brand">
			<?php pixva_the_logo( 'light' ); ?>
			<p><?php echo esc_html( get_bloginfo( 'description' ) ? get_bloginfo( 'description' ) : __( 'مرکز فوق‌تخصصی تعمیر تلویزیون و نمایشگر؛ پنل، بک‌لایت، برد پاور و مین‌برد با قطعات فابریک و گارانتی کتبی.', 'pixva' ) ); ?></p>
			<div class="bx-footer__cert">
				<span><?php echo pixva_bento_icons( 'shield' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><span><?php esc_html_e( 'گواهی کسب اتحادیه الکترونیک تهران', 'pixva' ); ?></span></span>
				<span><?php echo pixva_bento_icons( 'check' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><span><?php echo esc_html( sprintf( __( 'گارانتی کتبی %s روزه خدمات', 'pixva' ), pixva_fa_num( (string) pixva_warranty_days() ) ) ); ?></span></span>
			</div>
			<div class="bx-socials">
				<?php $pixva_socials = pixva_social_links(); ?>
				<?php foreach ( $pixva_socials as $item ) : ?>
					<a href="<?php echo esc_url( $item['url'] ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $item['label'] ); ?></a>
				<?php endforeach; ?>
			</div>
		</section>

		<section class="bx-footer__col">
			<h2><?php esc_html_e( 'هاب‌های تخصصی', 'pixva' ); ?></h2>
			<ul class="bx-footer__links">
				<?php foreach ( pixva_hubs() as $hub ) : ?>
					<li>
						<a href="<?php echo esc_url( pixva_hub_url( $hub['slug'] ) ); ?>">
							<?php echo pixva_bento_icons( $hub['icon'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							<span>
								<strong><?php echo esc_html( $hub['short'] ); ?></strong>
								<small><?php echo esc_html( sprintf( __( 'ابزار %d تا %d', 'pixva' ), (int) $hub['from'], (int) $hub['to'] ) ); ?></small>
							</span>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		</section>

		<section class="bx-footer__col">
			<h2><?php esc_html_e( 'تماس اورژانسی', 'pixva' ); ?></h2>
			<div class="bx-footer__contact">
				<?php foreach ( pixva_footer_phones() as $phone ) : ?>
					<a href="<?php echo esc_url( pixva_tel_href( $phone ) ); ?>">
						<?php echo pixva_bento_icons( 'phone' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<span><?php echo esc_html( pixva_fa_num( $phone ) ); ?></span>
					</a>
				<?php endforeach; ?>
				<a href="<?php echo esc_url( pixva_whatsapp_url( __( 'سلام، برای تعمیر تلویزیون مشاوره فوری می‌خواهم.', 'pixva' ) ) ); ?>" target="_blank" rel="noopener noreferrer">
					<?php echo pixva_bento_icons( 'whatsapp' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<span><?php esc_html_e( 'واتساپ کارگاه', 'pixva' ); ?></span>
				</a>
				<p>
					<?php echo pixva_bento_icons( 'clock' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<span><?php echo esc_html( sprintf( __( '%s — اعزام اورژانسی زیر %s', 'pixva' ), (string) pixva_option( 'pixva_working_hours', __( 'شنبه تا پنجشنبه ۹ تا ۲۰', 'pixva' ) ), $pixva_eta ) ); ?></span>
				</p>
				<address>
					<?php echo pixva_bento_icons( 'pin' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<span><?php echo esc_html( $pixva_address ); ?></span>
				</address>
			</div>
		</section>

		<section class="bx-footer__col">
			<h2><?php esc_html_e( 'اعتماد و استعلام اصالت', 'pixva' ); ?></h2>
			<div class="bx-trust-row"><?php pixva_trust_badges(); ?></div>
			<div class="bx-qr" data-pixva-qr data-qr-value="<?php echo esc_attr( $pixva_qr_url ); ?>">
				<span class="bx-qr__code" data-qr-target aria-hidden="true"></span>
				<p>
					<strong><?php esc_html_e( 'استعلام گارانتی با QR', 'pixva' ); ?></strong>
					<span><?php esc_html_e( 'کد پیگیری و شماره همراه را در صفحه پیگیری وارد کنید تا وضعیت پرونده و اعتبار گارانتی نمایش داده شود.', 'pixva' ); ?></span>
					<a href="<?php echo esc_url( $pixva_qr_url ); ?>"><?php esc_html_e( 'باز کردن صفحه پیگیری', 'pixva' ); ?></a>
				</p>
			</div>
		</section>
	</div>

	<div class="bx-wrap bx-footer__base">
		<p class="bx-copyright"><?php echo esc_html( (string) pixva_option( 'pixva_copyright', __( '© تمامی حقوق برای مرکز تخصصی تعمیرات پیکسوا محفوظ است.', 'pixva' ) ) ); ?></p>
		<p><?php echo esc_html( sprintf( __( 'نسخه %s — نرخ‌نامه مصوب بازار ۱۴۰۵', 'pixva' ), pixva_fa_num( PIXVA_VERSION ) ) ); ?></p>
	</div>
</footer>
<?php endif; /* پایان جایگاه footer — لایه ۴٫۰٫۰ */ ?>

<nav class="bx-dock" aria-label="<?php esc_attr_e( 'نوار اقدام سریع موبایل', 'pixva' ); ?>">
	<?php if ( '' !== $pixva_phone ) : ?>
		<a href="<?php echo esc_url( pixva_tel_href( $pixva_phone ) ); ?>" class="bx-dock__item">
			<?php echo pixva_bento_icons( 'phone' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<span><?php esc_html_e( 'تماس', 'pixva' ); ?></span>
		</a>
	<?php endif; ?>
	<a href="<?php echo pixva_bento_url( $pixva_dock_booking_url ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>" class="bx-dock__item bx-dock__item--cta">
		<?php echo pixva_bento_icons( 'bolt' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<span><?php echo esc_html( $pixva_dock_booking_text ); ?></span>
	</a>
	<a href="<?php echo esc_url( pixva_page_url( 'tracking' ) ); ?>" class="bx-dock__item">
		<?php echo pixva_bento_icons( 'search' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<span><?php esc_html_e( 'پیگیری', 'pixva' ); ?></span>
	</a>
	<a href="<?php echo esc_url( pixva_whatsapp_url( __( 'سلام، برای تعمیر تلویزیون مشاوره می‌خواهم.', 'pixva' ) ) ); ?>" target="_blank" rel="noopener noreferrer" class="bx-dock__item">
		<?php echo pixva_bento_icons( 'whatsapp' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<span><?php esc_html_e( 'واتساپ', 'pixva' ); ?></span>
	</a>
</nav>

<?php
if ( function_exists( 'pixva_render_ai_chatbot_widget' ) ) {
	pixva_render_ai_chatbot_widget();
}
wp_footer();
?>
</body>
</html>

<?php
/**
 * فوتر قالب پیکسوا — Master Specification v25.0
 *
 * فوتر چهارستونه روی زمینه سرمه‌ای (#123B4A):
 * ۱) لوگو و گواهی‌ها  ۲) پنج هاب تخصصی  ۳) تماس اورژانسی و نشانی  ۴) نمادهای اعتماد و QR
 *
 * @package Pixva
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$pixva_phone   = pixva_support_phone();
$pixva_control = function_exists( 'pixva_control_options' ) ? pixva_control_options() : array();
$pixva_address = isset( $pixva_control['hub_address'] )
	? $pixva_control['hub_address']
	: (string) pixva_option( 'pixva_workshop_address', __( 'تهران، خیابان جمهوری، تقاطع حافظ، پاساژ علاءالدین، طبقه ۴، واحد ۴۱۲', 'pixva' ) );
$pixva_eta     = isset( $pixva_control['hub_eta_hours'] ) ? $pixva_control['hub_eta_hours'] : '۲ ساعت';
$pixva_qr_url  = (string) pixva_option( 'pixva_footer_qr_url', pixva_page_url( 'tracking' ) );
?>
<footer class="pixva-footer">
	<div class="pixva-container pixva-footer__grid">

		<section class="pixva-footer__col">
			<?php pixva_the_logo( 'light' ); ?>
			<p><?php echo esc_html( get_bloginfo( 'description' ) ? get_bloginfo( 'description' ) : __( 'مرکز تخصصی تعمیر تلویزیون و نمایشگر؛ پنل، بک‌لایت، برد پاور و مین‌برد با قطعات فابریک.', 'pixva' ) ); ?></p>
			<div class="pixva-footer__cert">
				<span><?php echo pixva_icon( 'cert' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><span><?php esc_html_e( 'گواهی کسب اتحادیه الکترونیک تهران', 'pixva' ); ?></span></span>
				<span><?php echo pixva_icon( 'shield' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><span><?php echo esc_html( sprintf( __( 'گارانتی کتبی %s روزه خدمات', 'pixva' ), pixva_fa_num( (string) pixva_warranty_days() ) ) ); ?></span></span>
			</div>
			<div class="pixva-socials">
				<?php $pixva_socials = pixva_social_links(); ?>
				<?php foreach ( $pixva_socials as $item ) : ?>
					<a href="<?php echo esc_url( $item['url'] ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $item['label'] ); ?></a>
				<?php endforeach; ?>
				<?php if ( empty( $pixva_socials ) ) : ?>
					<span class="pixva-muted"><?php esc_html_e( 'لینک شبکه‌ها از سفارشی‌ساز یا مرکز کنترل تنظیم می‌شود.', 'pixva' ); ?></span>
				<?php endif; ?>
			</div>
		</section>

		<section class="pixva-footer__col">
			<h2><?php esc_html_e( 'پنج هاب تخصصی پیکسوا', 'pixva' ); ?></h2>
			<ul class="pixva-footer__links">
				<?php foreach ( pixva_hubs() as $hub ) : ?>
					<li>
						<a href="<?php echo esc_url( pixva_hub_url( $hub['slug'] ) ); ?>">
							<?php echo pixva_icon( $hub['icon'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							<span>
								<strong><?php echo esc_html( $hub['title'] ); ?></strong>
								<small><?php echo esc_html( sprintf( __( 'ابزار %d تا %d', 'pixva' ), (int) $hub['from'], (int) $hub['to'] ) ); ?></small>
							</span>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		</section>

		<section class="pixva-footer__col">
			<h2><?php esc_html_e( 'تماس اورژانسی و نشانی کارگاه', 'pixva' ); ?></h2>
			<div class="pixva-footer__emergency">
				<?php foreach ( pixva_footer_phones() as $phone ) : ?>
					<a href="<?php echo esc_url( pixva_tel_href( $phone ) ); ?>">
						<?php echo pixva_icon( 'phone' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<span><?php echo esc_html( pixva_fa_num( $phone ) ); ?></span>
					</a>
				<?php endforeach; ?>
				<a href="<?php echo esc_url( pixva_whatsapp_url( __( 'سلام، برای تعمیر تلویزیون مشاوره فوری می‌خواهم.', 'pixva' ) ) ); ?>" target="_blank" rel="noopener noreferrer">
					<?php echo pixva_icon( 'whatsapp' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<span><?php esc_html_e( 'واتساپ کارگاه', 'pixva' ); ?></span>
				</a>
				<p class="pixva-footer__hours">
					<?php echo pixva_icon( 'clock' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<span><?php echo esc_html( sprintf( __( 'شنبه تا پنجشنبه ۹ تا ۲۰ — اعزام اورژانسی زیر %s', 'pixva' ), $pixva_eta ) ); ?></span>
				</p>
			</div>
			<address class="pixva-footer__address">
				<?php echo pixva_icon( 'pin' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<span><?php echo esc_html( $pixva_address ); ?></span>
			</address>
		</section>

		<section class="pixva-footer__col">
			<h2><?php esc_html_e( 'نمادهای اعتماد و استعلام اصالت', 'pixva' ); ?></h2>
			<div class="pixva-trust-row"><?php pixva_trust_badges(); ?></div>
			<div class="pixva-qr-card" data-pixva-qr data-qr-value="<?php echo esc_attr( $pixva_qr_url ); ?>">
				<span class="pixva-qr-card__code" data-qr-target aria-hidden="true"></span>
				<p>
					<strong><?php esc_html_e( 'استعلام گارانتی با QR', 'pixva' ); ?></strong>
					<span><?php esc_html_e( 'کد پیگیری و شماره همراه خود را در صفحه پیگیری وارد کنید تا وضعیت پرونده و اعتبار گارانتی نمایش داده شود.', 'pixva' ); ?></span>
					<a href="<?php echo esc_url( $pixva_qr_url ); ?>"><?php esc_html_e( 'باز کردن صفحه پیگیری', 'pixva' ); ?></a>
				</p>
			</div>
		</section>
	</div>

	<div class="pixva-container pixva-footer__base">
		<p class="pixva-copyright"><?php echo esc_html( (string) pixva_option( 'pixva_copyright', __( '© تمامی حقوق برای مرکز تخصصی تعمیرات پیکسوا محفوظ است.', 'pixva' ) ) ); ?></p>
		<p><?php echo esc_html( sprintf( __( 'نسخه %s — نرخ‌نامه مصوب بازار ۱۴۰۵', 'pixva' ), pixva_fa_num( PIXVA_VERSION ) ) ); ?></p>
	</div>
</footer>

<nav class="pixva-mobile-dock" aria-label="<?php esc_attr_e( 'نوار دسترسی سریع موبایل', 'pixva' ); ?>">
	<a href="<?php echo esc_url( pixva_tel_href( $pixva_phone ) ); ?>" class="pixva-mobile-dock__item">
		<?php echo pixva_icon( 'phone' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<span><?php esc_html_e( 'تماس', 'pixva' ); ?></span>
	</a>
	<a href="<?php echo esc_url( pixva_page_url( 'calculator' ) ); ?>" class="pixva-mobile-dock__item is-cta">
		<?php echo pixva_icon( 'bolt' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<span><?php esc_html_e( 'استعلام قیمت', 'pixva' ); ?></span>
	</a>
	<a href="<?php echo esc_url( pixva_page_url( 'tracking' ) ); ?>" class="pixva-mobile-dock__item">
		<?php echo pixva_icon( 'search' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<span><?php esc_html_e( 'پیگیری', 'pixva' ); ?></span>
	</a>
	<a href="<?php echo esc_url( pixva_whatsapp_url( __( 'سلام، برای تعمیر تلویزیون مشاوره می‌خواهم.', 'pixva' ) ) ); ?>" target="_blank" rel="noopener noreferrer" class="pixva-mobile-dock__item">
		<?php echo pixva_icon( 'whatsapp' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<span><?php esc_html_e( 'واتساپ', 'pixva' ); ?></span>
	</a>
</nav>

<?php
if ( function_exists( 'pixva_render_ai_chatbot_widget' ) ) {
	pixva_render_ai_chatbot_widget();
}
?>
<?php wp_footer(); ?>
</body>
</html>

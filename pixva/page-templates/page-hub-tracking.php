<?php
/**
 * Template Name: هاب ۳: پیگیری و گارانتی
 * Template Post Type: page
 *
 * ابزارهای ۲۱ تا ۳۰: پورتال B2B، استعلام اصالت سریال، زمان‌سنج اعزام،
 * پایگاه کدهای خطا، جست‌وجوی صوتی، رزرو نوبت، هاب مشتریان، شعبه‌ها،
 * مقایسه پنل و بررسی هولوگرام اصالت.
 *
 * @package Pixva
 * @since   1.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$pixva_hub   = 'tracking-warranty';
$pixva_phone = pixva_support_phone();
?>
<main id="content">
	<?php
	pixva_page_hero(
		__( 'هاب ۳ — پیگیری پرونده و گارانتی کتبی', 'pixva' ),
		sprintf(
			/* translators: %s: مدت گارانتی */
			__( 'پیگیری زنده تایم‌لاین شش‌مرحله‌ای تعمیر، صدور کارت گارانتی دیجیتال با هش SHA-256 و استعلام اصالت قطعات فابریک. گارانتی کتبی %s روزه.', 'pixva' ),
			pixva_fa_num( (string) pixva_warranty_days() )
		),
		'pixva-hub-hero'
	);
	?>

	<section class="pixva-section" id="hub-track">
		<div class="pixva-container">
			<div class="pixva-grid pixva-grid--2">
				<div class="pixva-card">
					<span class="pixva-badge pixva-badge--brand"><?php esc_html_e( 'پیگیری زنده', 'pixva' ); ?></span>
					<h2><?php esc_html_e( 'وضعیت پرونده تعمیر', 'pixva' ); ?></h2>
					<p class="pixva-muted"><?php esc_html_e( 'کد پیگیری و شماره همراه ثبت‌شده هنگام پذیرش را وارد کنید. برای حفظ حریم خصوصی، استعلام فقط با تطابق هم‌زمان هر دو مقدار ممکن است.', 'pixva' ); ?></p>
					<form action="<?php echo esc_url( pixva_page_url( 'tracking' ) ); ?>" method="get" class="pixva-tool-row">
						<input type="text" name="code" class="pixva-input" placeholder="<?php esc_attr_e( 'کد پیگیری (PXV-…)', 'pixva' ); ?>" aria-label="<?php esc_attr_e( 'کد پیگیری', 'pixva' ); ?>" required>
						<input type="tel" name="phone" class="pixva-input" inputmode="numeric" placeholder="<?php esc_attr_e( 'شماره همراه', 'pixva' ); ?>" aria-label="<?php esc_attr_e( 'شماره همراه', 'pixva' ); ?>" required>
						<button type="submit" class="pixva-btn pixva-btn--primary"><?php esc_html_e( 'نمایش تایم‌لاین', 'pixva' ); ?></button>
					</form>
				</div>

				<div class="pixva-card">
					<span class="pixva-badge pixva-badge--cta"><?php esc_html_e( 'پشتیبانی فوری', 'pixva' ); ?></span>
					<h2><?php esc_html_e( 'تماس با واحد پیگیری', 'pixva' ); ?></h2>
					<p class="pixva-muted"><?php esc_html_e( 'اگر کد پیگیری در دسترس نیست، با شماره کارگاه تماس بگیرید تا پرونده با شماره همراه پیدا شود.', 'pixva' ); ?></p>
					<div class="pixva-tool-row">
						<a class="pixva-btn pixva-btn--ghost-dark" href="<?php echo esc_url( pixva_tel_href( $pixva_phone ) ); ?>"><?php echo pixva_icon( 'phone' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php echo esc_html( pixva_fa_num( $pixva_phone ) ); ?></a>
						<a class="pixva-btn pixva-btn--ghost-dark" href="<?php echo esc_url( pixva_whatsapp_url( __( 'سلام، درخواست پیگیری وضعیت تعمیر تلویزیون دارم.', 'pixva' ) ) ); ?>" target="_blank" rel="noopener noreferrer"><?php echo pixva_icon( 'whatsapp' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php esc_html_e( 'واتساپ کارگاه', 'pixva' ); ?></a>
					</div>
					<div class="pixva-qr-card" data-pixva-qr data-qr-value="<?php echo esc_attr( pixva_page_url( 'tracking' ) ); ?>">
						<span class="pixva-qr-card__code" data-qr-target aria-hidden="true"></span>
						<p>
							<strong><?php esc_html_e( 'استعلام با QR', 'pixva' ); ?></strong>
							<span><?php esc_html_e( 'با دوربین گوشی این کد را اسکن کنید تا مستقیماً به صفحه پیگیری پرونده بروید.', 'pixva' ); ?></span>
						</p>
					</div>
				</div>
			</div>
		</div>
	</section>

	<?php
	pixva_render_hub_index( $pixva_hub );
	pixva_render_hub_tools( $pixva_hub );

	while ( have_posts() ) :
		the_post();
		if ( get_the_content() ) {
			echo '<div class="pixva-container pixva-content">';
			the_content();
			echo '</div>';
		}
	endwhile;
	?>
</main>
<?php
get_footer();

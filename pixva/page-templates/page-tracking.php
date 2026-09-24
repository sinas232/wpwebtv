<?php
/**
 * Template Name: پیگیری وضعیت تعمیر
 * Template Post Type: page
 *
 * @package Pixva
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
$statuses = pixva_order_statuses();
?>
<main id="content">
	<?php pixva_page_hero( __( 'پیگیری آنلاین وضعیت تعمیر', 'pixva' ), __( 'کد پیگیری و شماره همراه همان پرونده را با هم وارد کنید. هر دو لازم است و پیگیری فقط با شماره ممکن نیست.', 'pixva' ) ); ?>
	<div class="pixva-container pixva-content">
		<form class="pixva-card pixva-track-form" data-pixva-track novalidate>
			<?php pixva_honeypot_field(); ?>
			<div class="pixva-field">
				<label for="pixva-track-code"><?php esc_html_e( 'کد پیگیری (PXV-...)', 'pixva' ); ?></label>
				<input type="text" id="pixva-track-code" name="code" class="pixva-latin" placeholder="PXV-DEMO-2401" autocomplete="off" required pattern="PXV-[A-Z0-9-]+">
			</div>
			<div class="pixva-field">
				<label for="pixva-track-phone"><?php esc_html_e( 'شماره همراه همان پرونده', 'pixva' ); ?></label>
				<input type="tel" id="pixva-track-phone" name="phone" inputmode="numeric" placeholder="0912xxxxxxx" autocomplete="tel" required>
			</div>
			<button class="pixva-btn pixva-btn--cta pixva-btn--bolt" type="submit"><?php esc_html_e( 'استعلام', 'pixva' ); ?></button>
			<p class="pixva-notice pixva-notice--error" data-track-msg hidden></p>
		</form>
		<p class="pixva-notice pixva-notice--info"><?php esc_html_e( 'نمونه دمو: کد PXV-DEMO-2401 با شماره ۰۹۱۲۱۱۱۱۱۱۱. هر دو را با هم وارد کنید.', 'pixva' ); ?></p>
		<div data-track-result></div>

		<section style="margin-top:2rem">
			<h2><?php esc_html_e( 'این شش مرحله یعنی چه؟', 'pixva' ); ?></h2>
			<p class="pixva-muted"><?php esc_html_e( 'بعد از استعلام، مرحله فعلی دستگاه تو روی این مسیر روشن می‌شود. این‌جا معنی هر مرحله را می‌بینی تا ندانی «تأمین قطعه» یعنی چه و چرا طول می‌کشد.', 'pixva' ); ?></p>
			<?php
$stage_texts = array(
				__( 'دستگاه رسیده و کد خورده؛ نوبت عیب‌یابی است.', 'pixva' ),
				__( 'علت خرابی پیدا و هزینه قطعی مشخص شده؛ منتظر تأیید توست.', 'pixva' ),
				__( 'قطعه لازم سفارش داده شده؛ این مرحله به موجودی بازار بستگی دارد.', 'pixva' ),
				__( 'تعمیر روی میز در حال انجام است؛ برد یا پنل زیر دست تکنسین است.', 'pixva' ),
				__( 'تعمیر تمام شده و دستگاه زیر تست حرارت و تصویر است.', 'pixva' ),
				__( 'تست پاس شده؛ دستگاه با برگه گارانتی آماده تحویل است.', 'pixva' ),
			);
			?>
			<ol class="pixva-timeline">
				<?php $index = 1; foreach ( $statuses as $label ) : ?>
					<li>
						<span class="pixva-timeline__dot"><?php echo esc_html( pixva_fa_num( (string) $index ) ); ?></span>
						<div>
							<h3><?php echo esc_html( $label ); ?></h3>
							<p><?php echo esc_html( isset( $stage_texts[ $index - 1 ] ) ? $stage_texts[ $index - 1 ] : '' ); ?></p>
						</div>
					</li>
					<?php ++$index; ?>
				<?php endforeach; ?>
			</ol>
		</section>
		<section style="margin-top:1.5rem">
			<?php pixva_cta_box(); ?>
		</section>
	</div>
</main>
<?php
get_footer();

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
	<?php pixva_page_hero( __( 'پیگیری آنلاین وضعیت تعمیر', 'pixva' ), __( 'برای حفظ امنیت پرونده، استعلام فقط با تطابق هم‌زمان کد پیگیری (PXV-...) و شماره همراه ثبت‌شده انجام می‌شود.', 'pixva' ) ); ?>
	<div class="pixva-container pixva-content">
		<form class="pixva-card pixva-track-form" data-pixva-track novalidate>
			<?php pixva_honeypot_field(); ?>
			<div class="pixva-field">
				<label for="pixva-track-code"><?php esc_html_e( 'کد پیگیری (PXV-...)', 'pixva' ); ?></label>
				<input type="text" id="pixva-track-code" name="code" class="pixva-latin" placeholder="PXV-2509-1234" autocomplete="off" required>
			</div>
			<div class="pixva-field">
				<label for="pixva-track-phone"><?php esc_html_e( 'شماره همراه ثبت‌شده', 'pixva' ); ?></label>
				<input type="tel" id="pixva-track-phone" name="phone" inputmode="numeric" placeholder="0912xxxxxxx" autocomplete="tel" required>
			</div>
			<p class="pixva-muted"><?php esc_html_e( 'هر دو فیلد الزامی است؛ استعلام فقط با شماره همراه ممکن نیست.', 'pixva' ); ?></p>
			<button class="pixva-btn pixva-btn--cta pixva-btn--bolt" type="submit"><?php esc_html_e( 'استعلام', 'pixva' ); ?></button>
			<p class="pixva-notice pixva-notice--error" data-track-msg hidden></p>
		</form>
		<div data-track-result></div>

		<section style="margin-top:2rem">
			<h2><?php esc_html_e( 'مراحل کارگاه', 'pixva' ); ?></h2>
			<ol class="pixva-timeline">
				<?php $index = 1; foreach ( $statuses as $label ) : ?>
					<li>
						<span class="pixva-timeline__dot"><?php echo esc_html( pixva_fa_num( (string) $index ) ); ?></span>
						<div>
							<h3><?php echo esc_html( $label ); ?></h3>
							<p><?php esc_html_e( 'پس از استعلام، مرحله فعلی دستگاه شما روی این مسیر روشن می‌شود.', 'pixva' ); ?></p>
						</div>
					</li>
					<?php ++$index; ?>
				<?php endforeach; ?>
			</ol>
		</section>
	</div>
</main>
<?php
get_footer();

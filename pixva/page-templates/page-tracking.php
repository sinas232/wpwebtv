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

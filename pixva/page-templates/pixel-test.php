<?php
/**
 * Template Name: PIXVA — تست پیکسل
 *
 * Dead/stuck pixel and uniformity test (§10). Works without JS through
 * :target panels; pixel-test.js adds a fullscreen viewer (keyboard:
 * arrows/space = next colour, Esc = exit). No flashing or auto-cycling.
 *
 * @package Pixva
 * @since   2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
get_header();
$pixva_colors = array(
	'black'  => __( 'سیاه', 'pixva' ),
	'white'  => __( 'سفید', 'pixva' ),
	'red'    => __( 'قرمز', 'pixva' ),
	'green'  => __( 'سبز', 'pixva' ),
	'blue'   => __( 'آبی', 'pixva' ),
	'gray'   => __( 'خاکستری', 'pixva' ),
	'grad-h' => __( 'گرادیان افقی', 'pixva' ),
	'grad-v' => __( 'گرادیان عمودی', 'pixva' ),
);
?>
<main id="main" class="site-main px-page">
	<?php pixva_page_header( get_the_title(), pixva_route_description( 'pixel_test' ) ); ?>
	<div class="container section px">
		<?php pixva_page_intro(); ?>

		<section class="px-tool" data-pixel-test aria-labelledby="px-h">
			<header class="px-tool__head">
				<div>
					<p class="px-kicker"><?php esc_html_e( 'ابزار بررسی نمایشگر', 'pixva' ); ?></p>
					<h2 id="px-h" class="px-h2"><?php esc_html_e( 'یک رنگ را انتخاب کنید', 'pixva' ); ?></h2>
				</div>
				<ol class="px-steps" aria-label="<?php esc_attr_e( 'روش انجام تست', 'pixva' ); ?>">
					<li><?php esc_html_e( 'روی تلویزیون باز کنید', 'pixva' ); ?></li>
					<li><?php esc_html_e( 'رنگ و تمام‌صفحه', 'pixva' ); ?></li>
					<li><?php esc_html_e( 'نقطه و لکه را بگردید', 'pixva' ); ?></li>
				</ol>
			</header>

			<ul class="px-palette" role="list">
				<?php $pixva_i = 0; ?>
				<?php foreach ( $pixva_colors as $pixva_k => $pixva_label ) : ?>
					<?php ++$pixva_i; ?>
					<li>
						<a class="swatch px--<?php echo esc_attr( $pixva_k ); ?>" href="#px-<?php echo esc_attr( $pixva_k ); ?>" data-color="<?php echo esc_attr( $pixva_k ); ?>">
							<span class="swatch__chip" aria-hidden="true"></span>
							<span class="swatch__meta">
								<span class="swatch__no"><?php echo esc_html( pixva_fa_num( sprintf( '%02d', $pixva_i ) ) ); ?></span>
								<span class="swatch__label"><?php echo esc_html( $pixva_label ); ?></span>
							</span>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>

			<div class="px-control" data-pixel-js hidden>
				<button type="button" class="btn btn--primary btn--lg" data-pixel-fullscreen><?php echo wp_kses( pixva_icon( 'expand' ), pixva_svg_allowed() ); ?> <?php esc_html_e( 'شروع تست تمام‌صفحه', 'pixva' ); ?></button>
				<p class="field__help"><?php esc_html_e( 'در حالت تمام‌صفحه: کلیک، فاصله، Enter یا ← = رنگ بعدی؛ → = رنگ قبلی؛ Esc = خروج.', 'pixva' ); ?></p>
			</div>

			<div class="px-monitor">
				<div class="px-monitor__screen pixel-test__stage">
					<?php foreach ( $pixva_colors as $pixva_k => $pixva_label ) : ?>
						<div class="px-panel px--<?php echo esc_attr( $pixva_k ); ?>" id="px-<?php echo esc_attr( $pixva_k ); ?>" role="img" aria-label="<?php echo esc_attr( $pixva_label ); ?>"></div>
					<?php endforeach; ?>
					<p class="pixel-test__hint px-monitor__hint"><?php esc_html_e( 'یک رنگ را انتخاب کنید تا اینجا نمایش داده شود.', 'pixva' ); ?></p>
				</div>
				<p class="px-monitor__cap"><?php esc_html_e( 'پیش‌نمایش داخل صفحه؛ برای تست دقیق‌تر از تمام‌صفحه استفاده کنید.', 'pixva' ); ?></p>
			</div>
		</section>

		<section class="px-read" aria-labelledby="px-read">
			<h2 id="px-read" class="px-h2"><?php esc_html_e( 'نتیجه را چطور تفسیر کنیم؟', 'pixva' ); ?></h2>
			<dl class="px-guide">
				<div class="px-guide__item">
					<dt><?php esc_html_e( 'نقطه سیاه روی زمینه روشن', 'pixva' ); ?></dt>
					<dd><?php esc_html_e( 'احتمالاً پیکسل سوخته (مرده) است و معمولاً با نرم‌افزار برطرف نمی‌شود.', 'pixva' ); ?></dd>
				</div>
				<div class="px-guide__item">
					<dt><?php esc_html_e( 'نقطه رنگی ثابت روی زمینه تیره', 'pixva' ); ?></dt>
					<dd><?php esc_html_e( 'احتمالاً پیکسل گیرکرده است؛ گاهی پس از چند ساعت کار خودبه‌خود برطرف می‌شود.', 'pixva' ); ?></dd>
				</div>
				<div class="px-guide__item">
					<dt><?php esc_html_e( 'لکه یا هاله روشن روی زمینه سیاه', 'pixva' ); ?></dt>
					<dd><?php esc_html_e( 'می‌تواند نشتی نور یا ایراد بک‌لایت باشد و بررسی کارشناس لازم است.', 'pixva' ); ?></dd>
				</div>
				<div class="px-guide__item">
					<dt><?php esc_html_e( 'خط عمودی یا افقی', 'pixva' ); ?></dt>
					<dd><?php esc_html_e( 'معمولاً به پنل یا اتصالات آن مربوط است؛ دستگاه را باز نکنید.', 'pixva' ); ?></dd>
				</div>
			</dl>
			<p class="px-next"><a class="btn btn--ghost" href="<?php echo esc_url( pixva_route_url( 'diagnosis' ) ); ?>" data-track="cta_click" data-track-label="diagnosis" data-track-location="pixel_test"><?php esc_html_e( 'ادامه با تشخیص آنلاین', 'pixva' ); ?></a></p>
		</section>

		<?php pixva_notice( 'warning', __( 'اگر به نور یا رنگ‌های تند حساس هستید، تست را در نور محیط انجام دهید و بین رنگ‌ها مکث کنید. این ابزار هیچ‌وقت به‌صورت خودکار چشمک نمی‌زند.', 'pixva' ) ); ?>
	</div>
	<div class="container section--tight"><?php pixva_cta_box( '', '', 'pixel_test' ); ?></div>
</main>
<?php
get_footer();

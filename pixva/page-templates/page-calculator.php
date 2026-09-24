<?php
/**
 * Template Name: محاسبه‌گر هزینه تعمیر
 * Template Post Type: page
 *
 * برگه اختصاصی محاسبه‌گر هوشمند هزینه تعمیر.
 *
 * @package Pixva
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>
<main id="content">
	<?php pixva_page_hero( __( 'محاسبه‌گر هزینه تعمیر تلویزیون', 'pixva' ), __( 'سه مرحله: برند، تکنولوژی و سایز، نوع مشکل. بعد از برآورد، شماره را بگذارید تا نوبت و کد پیگیری ساخته شود.', 'pixva' ) ); ?>
	<div class="pixva-container pixva-content">
		<div class="pixva-grid pixva-grid--2" style="align-items:start">
			<div>
				<?php pixva_render_calculator(); ?>
				<?php
				while ( have_posts() ) :
					the_post();
					if ( get_the_content() ) {
						echo '<div class="entry-content" style="margin-top:1.2rem">';
						the_content();
						echo '</div>';
					}
				endwhile;
				?>
			</div>
			<aside>
				<section class="pixva-card">
					<h2><?php esc_html_e( 'این برآورد چه چیزی نیست؟', 'pixva' ); ?></h2>
					<ul>
						<li><?php esc_html_e( 'فاکتور قطعی قبل از دیدن دستگاه نیست.', 'pixva' ); ?></li>
						<li><?php esc_html_e( 'قطعه کمیاب یا پنل شکسته می‌تواند خارج از بازه باشد و قبل از شروع گفته می‌شود.', 'pixva' ); ?></li>
						<li><?php esc_html_e( 'ضریب OLED و سایزهای بالای ۶۵ اینچ بالاتر است چون ریسک و قطعه فرق می‌کند.', 'pixva' ); ?></li>
					</ul>
				</section>
				<section class="pixva-card">
					<h2><?php esc_html_e( 'بعد از ثبت شماره', 'pixva' ); ?></h2>
					<p><?php esc_html_e( 'یک کد پیگیری می‌گیرید. وضعیت دستگاه از دریافت تا آماده تحویل در سامانه پیگیری دیده می‌شود.', 'pixva' ); ?></p>
					<a class="pixva-btn pixva-btn--ghost pixva-btn--sm" href="<?php echo esc_url( pixva_page_url( 'tracking' ) ); ?>"><?php esc_html_e( 'رفتن به پیگیری', 'pixva' ); ?></a>
				</section>
			</aside>
		</div>
	</div>
</main>
<?php
get_footer();

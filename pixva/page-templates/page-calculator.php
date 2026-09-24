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
	<?php pixva_page_hero( __( 'هزینه تعمیر تلویزیونت را خودت حساب کن', 'pixva' ), __( 'سه قدم: برند، تکنولوژی و سایز، بعد نوع خرابی. قیمت از نرخ‌نامه ۱۴۰۵ می‌آید — همان عددی که داخل کارگاه هم مبناست، نه یک عدد تبلیغاتی.', 'pixva' ) ); ?>
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
					<h2><?php esc_html_e( 'این عدد از کجا می‌آید؟', 'pixva' ); ?></h2>
					<p><?php esc_html_e( 'هر خدمت یک کف و سقف پایه دارد؛ بعد ضریب برند، تکنولوژی و سایز روی آن اعمال می‌شود. برای برد و صدا، اثر سایز را کم کرده‌ایم تا تعمیر برد ۶۵ اینچ منصفانه بماند.', 'pixva' ); ?></p>
					<p><a class="pixva-btn pixva-btn--ghost pixva-btn--sm" href="<?php echo esc_url( pixva_page_url( 'rates' ) ); ?>"><?php esc_html_e( 'دیدن نرخ‌نامه کامل', 'pixva' ); ?></a></p>
				</section>
				<section class="pixva-card">
					<h2><?php esc_html_e( 'صادقانه بگوییم', 'pixva' ); ?></h2>
					<ul>
						<li><?php esc_html_e( 'این بازه است، نه فاکتور قطعی؛ عدد نهایی بعد از عیب‌یابی و با تأیید تو ثبت می‌شود.', 'pixva' ); ?></li>
						<li><?php esc_html_e( 'تعویض کامل پنل خارج از جدول است و اغلب از ۱۰ میلیون شروع می‌شود.', 'pixva' ); ?></li>
						<li><?php esc_html_e( 'کارشناسی حضوری ۱۸۰ تا ۳۵۰ هزار تومان است و اگر تعمیر نصرفد، فقط همین را می‌پردازی.', 'pixva' ); ?></li>
					</ul>
				</section>
				<section class="pixva-card">
					<h2><?php esc_html_e( 'بعد از ثبت شماره چه می‌شود؟', 'pixva' ); ?></h2>
					<p><?php esc_html_e( 'فوراً یک کد پیگیری می‌گیری؛ همان کد، کلید تایم‌لاین شش‌مرحله‌ای توست. زنگ می‌زنیم، هماهنگ می‌کنیم، و در تهران جمع‌آوری را هماهنگ می‌کنیم.', 'pixva' ); ?></p>
					<a class="pixva-btn pixva-btn--ghost pixva-btn--sm" href="<?php echo esc_url( pixva_page_url( 'tracking' ) ); ?>"><?php esc_html_e( 'رفتن به پیگیری', 'pixva' ); ?></a>
				</section>
			</aside>
		</div>
	</div>
</main>
<?php
get_footer();

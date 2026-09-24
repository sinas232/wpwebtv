<?php
/**
 * Template Name: هاب ۵: انبار قطعات و خدمات سازمانی B2B
 * Template Post Type: page
 *
 * @package Pixva
 * @since   1.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>
<main id="content">
	<?php pixva_page_hero( __( 'هاب ۵: انبار قطعات فابریک و پورتال سازمانی B2B', 'pixva' ), __( 'استعلام زنده موجودی بردهای اصلی، پاور و بک‌لایت‌های اورجینال به همراه ثبت درخواست‌های پشتیبانی هتل‌ها و ارگان‌ها.', 'pixva' ) ); ?>
	<div class="pixva-container pixva-content">
		<div class="pixva-grid pixva-grid--2" style="gap:1.5rem;margin-bottom:2rem;">
			<div class="pixva-card">
				<span class="pixva-badge pixva-badge--brand"><?php esc_html_e( 'انبار مرکزی', 'pixva' ); ?></span>
				<h3 style="color:#0F172A;"><?php esc_html_e( 'استعلام فوری قطعات فابریک', 'pixva' ); ?></h3>
				<p class="pixva-muted"><?php esc_html_e( 'دست کامل بک‌لایت‌های با گارانتی ۱۸۰ روزه کتبی و هولوگرام اصالت.', 'pixva' ); ?></p>
				<a href="<?php echo esc_url( pixva_page_url( 'parts-stock' ) ); ?>" class="pixva-btn pixva-btn--primary"><?php esc_html_e( 'ورود به انبار قطعات', 'pixva' ); ?></a>
			</div>
			<div class="pixva-card">
				<span class="pixva-badge pixva-badge--cta"><?php esc_html_e( 'خدمات هتل‌ها و ارگان‌ها', 'pixva' ); ?></span>
				<h3 style="color:#0F172A;"><?php esc_html_e( 'پورتال سازمانی B2B', 'pixva' ); ?></h3>
				<p class="pixva-muted"><?php esc_html_e( 'عقد قراردادهای رسمی نگهداری دوره‌ای با فاکتور رسمی معتبر.', 'pixva' ); ?></p>
				<a href="<?php echo esc_url( pixva_page_url( 'b2b' ) ); ?>" class="pixva-btn pixva-btn--cta"><?php esc_html_e( 'ورود به پورتال سازمانی', 'pixva' ); ?></a>
			</div>
		</div>
	</div>
</main>
<?php
get_footer();

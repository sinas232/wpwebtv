<?php
/**
 * Template Name: درباره پیکسوا
 * Template Post Type: page
 *
 * @package Pixva
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>
<main id="content">
	<?php pixva_page_hero( __( 'درباره پیکسوا', 'pixva' ), __( 'کارگاه تخصصی تعمیر پنل، بک‌لایت و برد تلویزیون. تصمیم تعمیر یا تعویض را با عدد و عکس، نه با حدس، می‌گیریم.', 'pixva' ) ); ?>
	<div class="pixva-container pixva-content">
		<div class="pixva-grid pixva-grid--2" style="align-items:center;margin-bottom:2rem">
			<img src="<?php echo esc_url( PIXVA_URI . '/assets/images/bonding-lab.jpg' ); ?>" alt="<?php esc_attr_e( 'دستگاه بندینگ پنل در کارگاه پیکسوا', 'pixva' ); ?>" width="960" height="640" style="border-radius:22px">
			<div class="entry-content">
				<h2><?php esc_html_e( 'تجهیز کارگاه', 'pixva' ); ?></h2>
				<ul>
					<li><?php esc_html_e( 'دستگاه بندینگ COF برای ترمیم فلت پنل', 'pixva' ); ?></li>
					<li><?php esc_html_e( 'میکروسکوپ و هیتر برای مسیرهای برد', 'pixva' ); ?></li>
					<li><?php esc_html_e( 'اسیلوسکوپ و منبع تغذیه محدودکننده جریان', 'pixva' ); ?></li>
					<li><?php esc_html_e( 'پروگرامر حافظه مین‌بردهای رایج', 'pixva' ); ?></li>
					<li><?php esc_html_e( 'میز تست تصویر برای تحویل با الگوی رنگ و خاکستری', 'pixva' ); ?></li>
				</ul>
			</div>
		</div>

		<h2><?php esc_html_e( 'مسیر پذیرش تا تحویل', 'pixva' ); ?></h2>
		<ol class="pixva-steps">
			<li><strong><?php esc_html_e( 'ثبت علائم', 'pixva' ); ?></strong><p><?php esc_html_e( 'برند، مدل، تعداد چشمک و عکس صفحه. اگر آب‌خوردگی یا ضربه هست همان اول گفته شود.', 'pixva' ); ?></p></li>
			<li><strong><?php esc_html_e( 'عیب‌یابی', 'pixva' ); ?></strong><p><?php esc_html_e( 'جدا کردن مسیر تغذیه، پنل و مین‌برد تا مشخص شود کدام بخش واقعاً خراب است.', 'pixva' ); ?></p></li>
			<li><strong><?php esc_html_e( 'اعلام هزینه', 'pixva' ); ?></strong><p><?php esc_html_e( 'قبل از تعویض قطعه، بازه قطعی و زمان را تأیید می‌کنید. بدون تأیید کاری انجام نمی‌شود.', 'pixva' ); ?></p></li>
			<li><strong><?php esc_html_e( 'تست و گارانتی', 'pixva' ); ?></strong><p><?php esc_html_e( 'تست حرارت و تصویر، سپس برگه ۱۸۰ روزه برای برد و بک‌لایت.', 'pixva' ); ?></p></li>
		</ol>

		<div style="margin-top:1.5rem">
			<?php pixva_render_before_after( PIXVA_URI . '/assets/images/panel-before.jpg', PIXVA_URI . '/assets/images/panel-after.jpg', __( 'نمونه ترمیم خط پنل', 'pixva' ) ); ?>
		</div>

		<?php
		while ( have_posts() ) :
			the_post();
			if ( get_the_content() ) {
				echo '<div class="entry-content" style="margin-top:1.5rem">';
				the_content();
				echo '</div>';
			}
		endwhile;
		?>
	</div>
</main>
<?php
get_footer();

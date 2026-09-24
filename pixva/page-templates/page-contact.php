<?php
/**
 * Template Name: تماس با ما
 * Template Post Type: page
 *
 * @package Pixva
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
$address = pixva_workshop_full_address();
$map_src = pixva_map_embed_url();
$map_link = pixva_map_link_url();
$hours_week = (string) pixva_option( 'pixva_hours_weekdays', 'شنبه تا پنجشنبه ۹ تا ۲۰' );
$hours_fri = (string) pixva_option( 'pixva_hours_friday', 'جمعه ۱۰ تا ۱۶' );
$city = (string) pixva_option( 'pixva_workshop_city', 'تهران' );
$postal = (string) pixva_option( 'pixva_workshop_postal', '1145644123' );
$area = (string) pixva_option( 'pixva_service_area', 'تهران و کرج' );
?>
<main id="content">
	<?php pixva_page_hero( __( 'تماس با کارگاه پیکسوا', 'pixva' ), __( 'برای مشاوره، هماهنگی جمع‌آوری دستگاه یا پیگیری حضوری.', 'pixva' ) ); ?>
	<div class="pixva-container pixva-content">
		<div class="pixva-contact-grid">
			<section class="pixva-card">
				<h2><?php esc_html_e( 'راه‌های ارتباط', 'pixva' ); ?></h2>
				<ul class="pixva-info-list">
					<li><?php echo pixva_icon( 'pin' ); ?><span><?php echo esc_html( $address ); ?></span></li>
					<li><?php echo pixva_icon( 'pin' ); ?><span><?php echo esc_html( $city . ' — ' . __( 'کدپستی', 'pixva' ) . ' ' . pixva_fa_num( $postal ) ); ?></span></li>
					<li><?php echo pixva_icon( 'truck' ); ?><span><?php esc_html_e( 'محدوده اعزام:', 'pixva' ); ?> <?php echo esc_html( $area ); ?></span></li>
					<?php foreach ( pixva_footer_phones() as $phone ) : ?>
						<li><?php echo pixva_icon( 'phone' ); ?><a href="<?php echo esc_url( pixva_tel_href( $phone ) ); ?>"><?php echo esc_html( pixva_fa_num( $phone ) ); ?></a></li>
					<?php endforeach; ?>
					<li><?php echo pixva_icon( 'whatsapp' ); ?><a href="<?php echo esc_url( pixva_whatsapp_url() ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'پیام در واتساپ', 'pixva' ); ?></a></li>
				</ul>
				<table class="pixva-hours">
					<tbody>
						<tr><td><?php esc_html_e( 'شنبه تا پنجشنبه', 'pixva' ); ?></td><td><?php echo esc_html( pixva_fa_num( $hours_week ) ); ?></td></tr>
						<tr><td><?php esc_html_e( 'جمعه', 'pixva' ); ?></td><td><?php echo esc_html( pixva_fa_num( $hours_fri ) ); ?></td></tr>
					</tbody>
				</table>
				<iframe class="pixva-map" title="<?php esc_attr_e( 'نقشه کارگاه پیکسوا', 'pixva' ); ?>" loading="lazy" referrerpolicy="no-referrer-when-downgrade" src="<?php echo esc_url( $map_src ); ?>"></iframe>
				<p><a href="<?php echo esc_url( $map_link ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'باز کردن نقشه در صفحه جدا', 'pixva' ); ?></a></p>
			</section>
			<section class="pixva-card">
				<h2><?php esc_html_e( 'فرم پیام', 'pixva' ); ?></h2>
				<form data-pixva-contact novalidate>
					<?php pixva_honeypot_field(); ?>
					<div class="pixva-field">
						<label for="pixva-contact-name"><?php esc_html_e( 'نام', 'pixva' ); ?></label>
						<input type="text" id="pixva-contact-name" name="customer_name" required autocomplete="name">
					</div>
					<div class="pixva-field">
						<label for="pixva-contact-phone"><?php esc_html_e( 'شماره همراه', 'pixva' ); ?></label>
						<input type="tel" id="pixva-contact-phone" name="phone" required inputmode="numeric" autocomplete="tel">
					</div>
					<div class="pixva-field">
						<label for="pixva-contact-email"><?php esc_html_e( 'ایمیل (اختیاری)', 'pixva' ); ?></label>
						<input type="email" id="pixva-contact-email" name="email" autocomplete="email">
					</div>
					<div class="pixva-field">
						<label for="pixva-contact-message"><?php esc_html_e( 'شرح مشکل', 'pixva' ); ?></label>
						<textarea id="pixva-contact-message" name="message" rows="5" required placeholder="<?php esc_attr_e( 'برند، سایز و علامت خرابی را بنویسید.', 'pixva' ); ?>"></textarea>
					</div>
					<button class="pixva-btn pixva-btn--cta pixva-btn--bolt" type="submit"><?php esc_html_e( 'ارسال پیام', 'pixva' ); ?></button>
					<p class="pixva-notice" data-contact-msg hidden></p>
				</form>
			</section>
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

<?php
/**
 * Template Name: خدمات سازمانی و B2B
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
	<?php pixva_page_hero( __( 'پورتال خدمات سازمانی، ارگان‌ها و هتل‌ها (B2B)', 'pixva' ), __( 'قرارداد رسمی نگهداری و تعمیرات دوره‌ای انواع نمایشگرها و تلویزیون‌های هتلی با فاکتور رسمی معتبر و گارانتی کتبی ۱۸۰ روزه.', 'pixva' ) ); ?>
	<div class="pixva-container pixva-content">
		<div class="pixva-grid pixva-grid--2" style="gap:2rem;margin-bottom:2rem;">
			<div class="pixva-card">
				<span class="pixva-badge pixva-badge--brand"><?php esc_html_e( 'مزایای همکاری سازمانی', 'pixva' ); ?></span>
				<h3 style="margin:0.8rem 0;color:#0F172A;"><?php esc_html_e( 'پشتیبانی ویژه هتل‌ها و شرکت‌ها', 'pixva' ); ?></h3>
				<ul style="line-height:2.2;padding-right:1.2rem;color:#334155;">
					<li>تأمین دست کامل بک‌لایت و بردهای فابریک با قیمت عمده کارگاهی</li>
					<li>اعزام تیم تکنسین‌های کارگاه مرکزی پاساژ علاءالدین زیر ۲ ساعت در تهران</li>
					<li>صدور فاکتور رسمی به همراه شناسه یکتا جهت سامانه‌های مالی</li>
					<li>چک‌اپ و سرویس دوره‌ای رایگان هر ۶ ماه یک‌بار برای کلیه دستگاه‌ها</li>
				</ul>
			</div>
			<div class="pixva-card">
				<span class="pixva-badge pixva-badge--cta"><?php esc_html_e( 'ثبت درخواست استعلام B2B', 'pixva' ); ?></span>
				<h3 style="margin:0.8rem 0;color:#0F172A;"><?php esc_html_e( 'فرم تماس مستقیم مدیریت سازمانی', 'pixva' ); ?></h3>
				<form action="" method="post" style="display:flex;flex-direction:column;gap:12px;">
					<input type="text" name="org_name" placeholder="نام سازمان / هتل / شرکت" class="pixva-input" required>
					<input type="text" name="contact_person" placeholder="نام مسئول هماهنگی" class="pixva-input" required>
					<input type="tel" name="phone" placeholder="شماره تماس مستقیم" class="pixva-input" required>
					<textarea name="details" rows="3" placeholder="تعداد تلویزیون‌ها، مدل‌ها یا شرح خرابی" class="pixva-input"></textarea>
					<button type="button" class="pixva-btn pixva-btn--cta" onclick="alert('درخواست سازمانی شما ثبت شد؛ کارشناس امور هتل‌ها ظرف ۳۰ دقیقه با شما تماس خواهد گرفت.');">ثبت استعلام سازمانی</button>
				</form>
			</div>
		</div>
	</div>
</main>
<?php
get_footer();

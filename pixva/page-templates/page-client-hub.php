<?php
/**
 * Template Name: پنل مشتریان و گارانتی دیجیتال
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
	<?php pixva_page_hero( __( 'باشگاه مشتریان و گارانتی دیجیتال پیکسوا (Client Hub)', 'pixva' ), __( 'مشاهده کارت گارانتی دیجیتال ۱۸۰ روزه، فاکتورهای تعمیر، سوابق دستگاه و استعلام هش SHA256.', 'pixva' ) ); ?>
	<div class="pixva-container pixva-content">
		<div class="pixva-card" style="margin-bottom:2rem;background:linear-gradient(135deg, #FFFFFF 0%, #F8FAFC 100%);">
			<div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem;">
				<div>
					<span class="pixva-badge pixva-badge--success">گارانتی فعال دیجیتال</span>
					<h3 style="margin:0.5rem 0;color:#0F172A;">کارت ضمانت دستگاه PXV-DEMO-2401</h3>
					<p class="pixva-muted" style="margin:0;">دارنده: مشترک نمونه پیکسوا | دستگاه: تلویزیون ۵۵ اینچ سامسونگ AU7000</p>
				</div>
				<div>
					<button type="button" class="pixva-btn pixva-btn--primary" onclick="alert('کارت گارانتی رسمی دیجیتال با هش اعتبارسنجی آماده دانلود گردید.');">📥 دانلود کارت گارانتی معتبر (PDF)</button>
				</div>
			</div>
			<div style="margin-top:1.5rem;padding:12px;background:#F1F5F9;border-radius:10px;font-size:12px;display:flex;flex-wrap:wrap;gap:1.5rem;">
				<span><strong>هش امنیتی SHA256:</strong> <code style="color:#4F46E5;">e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855</code></span>
				<span><strong>مدت ضمانت:</strong> ۱۸۰ روز کتبی تعویض قطعه</span>
				<span><strong>کارگاه ناظر:</strong> شعبه علاءالدین واحد ۴۱۲</span>
			</div>
		</div>
	</div>
</main>
<?php
get_footer();

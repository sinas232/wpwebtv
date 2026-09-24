=== پیکسوا ===
Contributors: pixva
Requires at least: 6.5
Tested up to: 7.1
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Tags: custom-logo, custom-menu, featured-images, translation-ready, blog

قالب اختصاصی مرکز تعمیر تلویزیون و نمایشگر پیکسوا. راست‌چین، بدون jQuery و بدون وابستگی به افزونه.

== Description ==

محاسبه‌گر هزینه تعمیر، پیگیری آنلاین، پایگاه کدهای خطا، اسلایدر قبل/بعد، مجله سئومحور و اسکیما JSON-LD.

موتور قیمت فقط سمت سرور (PHP) کار می‌کند و نرخ‌نامه از پیشخوان ← پیگیری تعمیرات ← نرخ‌نامه قابل تنظیم است.

فونت وزیرمتن (Vazirmatn) با مجوز OFL در assets/fonts قرار دارد. متن مجوز: assets/fonts/OFL.txt

== Installation ==

1. پوشه pixva را در wp-content/themes آپلود کنید یا فایل zip را از پیشخوان نصب کنید.
2. قالب را فعال کنید.
3. در اولین فعال‌سازی، برگه‌ها (خانه، مجله، محاسبه هزینه، پیگیری، کدهای خطا، درباره ما، تماس، سوالات متداول و نرخ‌نامه) و منو ساخته می‌شوند.
4. شماره تماس، لوگو و نماد اعتماد را از سفارشی‌ساز تنظیم کنید.

== Changelog ==

= 1.2.0 =
* افزودن ماژول inc/activation.php: ساخت خودکار برگه نرخ‌نامه، تایم‌لاین کامل پرونده دمو (PXV-DEMO-2401) و مقداردهی تنظیمات کارگاه در wp_options.
* افزودن موتور قیمت inc/pricing-engine.php با فرمول (Base × BrandMultiplier × SizeFactor) + DiagnosticFee و کنترل ضریب سایز ضعیف برای برد پاور، برد اصلی و صدا.
* هشدار تعویض کامل پنل (خارج از جدول محاسبه).
* افزودن پنل نرخ‌نامه در پیشخوان (Settings API) با ضریب کلی، هزینه کارشناسی، قیمت پایه خدمات، ضریب ۲۴ برند و دکمه Reset to Defaults.
* استعلام پیگیری با اکشن pixva_track_device و الزام تطابق هم‌زمان کد PXV و شماره همراه.
* سخت‌سازی امنیتی: DISALLOW_FILE_EDIT، مسدودسازی /wp/v2/users برای مهمان، بستن آرشیو نویسندگان برای غیرمدیر، سربرگ‌های nosniff و Referrer-Policy، جلوگیری از Header Injection در ایمیل.

= 1.0.0 =
* انتشار اول قالب پیکسوا.

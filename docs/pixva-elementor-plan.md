# نقشه اجرایی — تبدیل PIXVA به قالب سازگار Elementor + ارتقای پنل مدیریت

تاریخ: 2026-10-10 — وضعیت: در حال اجرا (این سند پیش از شروع تغییرات نوشته شده و در پایان با نتایج تکمیل می‌شود)

## ۱) کشف وضعیت (مرحله ۱)

| مورد | یافته واقعی |
|------|--------------|
| مخزن/برچ | `sinas232/wpwebtv` — برچ فعلی جلسه `arena/77115f4b-wpwebtv` (برچ خواسته‌شده `feature/pixva-elementor-admin` به دلیل قید ردیابی جلسه قابل استفاده نیست — در گزارش نهایی شفاف اعلام می‌شود) |
| تغییرات ثبت‌نشده | اصلاحات امنیتی/idempotency/rewrite از کار قبلی (uncommitted) — حفظ می‌شوند و در همین کار commit می‌شوند |
| Elementor در قالب | هیچ کدی وجود ندارد؛ مدارهای قدیمی v1.x («پشتیبانی Elementor حذف شد» — §72 دفتر شکاف) با دستور مالک در این نوبت **نادیده گرفته می‌شود** (تصمیم جدید مالک) |
| افزونه Elementor Pro | تجاری/غیرقابل تهیه در این محیط → **NOT TESTED**؛ وابستگی‌ها مستند می‌شوند |
| Elementor رایگان | از GitHub رسمی: tag `4.4.0-latest-1791553354` از codeload دانلود شد (۶۰MB، بدون assetهای build) → در حال build از سورس با npm (registry مجاز) |
| ساختار قالب | قالب کلاسیک: `front-page.php` + ۱۴ page-template + CPT/REST/فرم‌های اختصاصی؛ **هیچ shortcode ثبت‌شده‌ای وجود ندارد** |
| نقطه اتصال موجود | `front-page.php` از قبل `the_content` صفحه اول را رندر می‌کند (محتوای Elementor نمایان می‌شود) اما بخش‌های پیش‌فرض همیشه زیر آن رندر می‌شوند |
| توابع قابل استفاده مجدد | `pixva_section_open/close`, `pixva_card_grid`, `pixva_faq_list`, `pixva_cta_box`, `pixva_tool_cards`, `pixva_problem_tiles`, `pixva_front_lead`, `pixva_mask_phone`, `pixva_route_url`… |
| پنل مدیریت | `inc/admin.php`: داشبورد/چک‌لیست «راه‌اندازی» موجود (`pixva_admin_overview`) + صفحات business/pricing/redirects/migration + `admin.css/admin.js` موجود |

## ۲) فهرست فایل‌های درگیر

### جدید
- `pixva/inc/elementor.php` — بارگذاری امن (بدون Elementor ⇒ بدون Fatal)، دسته ویجت، Theme Locations، سوییچ‌های قالب
- `pixva/inc/elementor-widgets.php` — کلاس پایه و ویجت‌های PIXVA (بدون Elementor ⇒ بارگذاری نمی‌شود)
- `pixva/inc/shortcodes.php` — معادل کلاسیک هر بخش (برای ویرایشگر کلاسیک و هر page builder)
- `docs/pixva-editability.md` — جدول «قابلیت / محل ویرایش / نیاز به Pro / محدودیت»

### تغییر (با احتیاط)
- `pixva/functions.php` — افزودن ماژول‌های جدید به لیست بارگذاری
- `pixva/front-page.php` — اگر صفحه اول با Elementor ساخته شده باشد، فقط محتوا رندر شود (رفتار پیش‌فرض بدون تغییر)
- `pixva/page.php` — بررسی/هم‌سویی با ویرایش محتوا (اگر از قبل `the_content` دارد، بدون تغییر)
- `pixva/inc/admin.php` — کارت‌های آمار **واقعی** داشبورد، هاب تنظیمات، دشبورد ویجت‌های WP با capability
- `pixva/assets/css/admin.css` — تقویت RTL/برند (فقط افزودن، بدون حذف)
- `pixva/page-templates/booking.php|tracking.php|warranty.php` — (فقط در صورت نیاز) استخراج تابع رندر فرم برای استفاده در ویجت — با حفظ nonce/PRG و تست رگرسیون
- `docs/pixva-gap-ledger.md/…` — ثبت نادیده‌گرفته‌شدن تصمیم §72 با دستور مالک (در doc جدید)

### به‌روزرسانی مستندات
- `docs/pixva-editability.md` (جدول نهایی + وضعیت Pro)

## ۳) طرح پیاده‌سازی (فاز‌ها)

**F — Elementor**
1. ماژول `elementor.php`: هوک `elementor/loaded` + `elementor/elements/categories_registered` (دسته «PIXVA») + `elementor/themes/register_locations` (header/footer/main برای Theme Builder در صورت وجود Pro) + سوییچ front-page.
2. ویجت‌ها (فقط جایی که داده/منطق واقعی دارد): Hero، خدمات (`tv_services`)، برندها (`tv_brands`)، مراحل، فرم رزرو/رهگیری/گارانتی، FAQ (`pixva_faq`)، CTA، اطلاعات تماس (داده واقعی business claims)، نوار اعلان، Breadcrumbs، مقالات، عنوان بخش — هرکدام با کنترل‌های محتوا/طراحی/چیدمان/ریسپانسیو معقول.
3. Shortcodes هم‌ارز برای همه موارد بالا.
4. بدون Elementor: فعال‌سازی قالب، همه مسیرها، فرم‌ها — بدون هیچ خطای Fatal (تست جدا).

**A — پنل مدیریت**
5. کارت‌های آمار واقعی روی `pixva_admin_overview` (شمارش سفارش‌ها از CPT واقعی، پیام‌های خوانده‌نشده، وضعیت سلامت پیکربندی) + ویجت‌های داشبورد WP برای نقش‌های مجاز.
6. هاب «تنظیمات PIXVA» با منوهای دسته‌بندی‌شده که به صفحات واقعی موجود لینک می‌شود؛ تنظیمات جدید فقط جایی که واقعاً غایب است (نوار اعلان). رنگ/فونت/لوگو → Elementor Site Settings / هویت سایت وردپرس (بدون دو محل متناقض).

**V — ویرایش‌پذیری**
7. جدول کامل در `docs/pixva-editability.md`.

**T — تست (اجباری)**
8. lint PHP/JS؛ نصب/فعال‌سازی؛ Elementor روشن/خاموش؛ رندر صفحه ویرایش‌شده Elementor از سمت سرور؛ ذخیره از طریق API دکمه ذخیره وردپرس/Elementor (ajax)؛ فرم‌ها/رهگیری/گارانتی؛ امنیت endpointها؛ داشبورد/نقش‌ها؛ SEO؛ ZIP.
9. موارد غیرقابل اجرا در این محیط صریحاً NOT TESTED اعلام می‌شوند: تعامل بصری editor (مرورگر)، Elementor Pro، Nginx/Apache واقعی، MySQL واقعی، SMTP واقعی، داده‌های CrUX.

## ۴) ریسک‌های شناخته‌شده

- build Elementor از سورس ممکن است با Node 22 (engines=24) شکست بخورد → در گزارش اعلام می‌شود.
- استخراج فرم رزرو از template باید با تست رگرسیون POST واقعی همراه شود (هارنس موجود).
- ذخیره‌سازی Elementor `_elementor_data` باید escape/permission خود Elementor را حفظ کند (کنترل سمت سرور Elementor دست‌نخورده می‌ماند).
- لوگوی/رنگ دوباره تعریف نمی‌شود: مرجع واحد رنگ = theme.json + Elementor Site Settings.

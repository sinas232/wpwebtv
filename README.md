# پیکسوا (pixva) — v2.0.0

قالب/پلتفرم وردپرس برای تشخیص، تعمیر، دانش، ثبت درخواست، پیگیری و گارانتی تعمیر تلویزیون. راست‌چین، بدون jQuery، بدون فریم‌ورک JS و بدون افزونه.

- پوشه قابل نصب: [`pixva/`](pixva/)
- بسته نصب: [`dist/pixva.zip`](dist/pixva.zip) (فقط شامل پوشه `pixva`)
- مستندات معماری: [`docs/pixva-architecture.md`](docs/pixva-architecture.md)
- ماتریس ردیابی §01–§77: [`docs/pixva-traceability.md`](docs/pixva-traceability.md)
- ماتریس QA: [`docs/pixva-qa-matrix.md`](docs/pixva-qa-matrix.md)
- دفتر شکاف‌ها: [`docs/pixva-gap-ledger.json`](docs/pixva-gap-ledger.json)

## نصب

1. `dist/pixva.zip` را از **نمایش ← پوسته‌ها ← افزودن ← بارگذاری پوسته** نصب و فعال کنید.
2. با فعال‌سازی، صفحه‌های مسیرهای اصلی ساخته یا وصل می‌شوند و پیوند یکتا `/blog/%postname%/` می‌شود.
3. **هیچ داده نمونه یا ساختگی ساخته نمی‌شود.** تلفن، نشانی، ساعات کاری، شیوه‌های پذیرش، سیاست گارانتی و قیمت‌گذاری را فقط با اطلاعات واقعی از منوی **پیکسوا** وارد کنید؛ هر مقدار خالی در سایت نمایش داده نمی‌شود.

## ابزارهای توسعه

| فرمان | کاربرد |
| --- | --- |
| `python3 tools/php_lint.py` | بررسی نحو همه فایل‌های PHP |
| `python3 tools/class_audit.py` | کلاس‌های استفاده‌شده بدون CSS و CSS بدون استفاده |
| `node --check pixva/assets/js/*.js` | بررسی نحو JS |
| `phpcs --standard=pixva/phpcs.xml pixva` | استانداردهای کدنویسی وردپرس (WPCS 3.x) |
| `python3 tools/gen_docs.py` | تولید ماتریس ردیابی، دفتر شکاف‌ها و ماتریس QA از یک منبع داده |
| `tools/blueprint.json` | Blueprint برای WordPress Playground (فعال‌سازی پوسته) |

جزئیات آزمون‌های اجراشده و موارد وابسته به مرورگر در `docs/pixva-qa-matrix.md` آمده است.

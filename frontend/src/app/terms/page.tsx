import type { Metadata } from "next";
import { PageHero } from "@/components/page-parts";
import { pageMetadata } from "@/lib/seo";

export const metadata: Metadata = pageMetadata({
  title: "شرایط استفاده",
  description: "شرایط استفاده از سایت و خدمات تعمیر تلویزیون.",
  path: "/terms",
});

export default function TermsPage() {
  return (
    <>
      <PageHero eyebrow="شرایط" title="شرایط استفاده" lead="پیش‌نویس. پیش از انتشار باید توسط مشاور حقوقی بازبینی و نهایی شود." crumbs={[{ name: "شرایط استفاده", href: "/terms" }]} />
      <section className="section">
        <div className="container">
          <article className="prose">
            <h2>تشخیص آنلاین</h2>
            <p>نتیجه‌ی بخش تشخیص تعاملی احتمالی است و جایگزین بررسی تکنسین نیست.</p>
            <h2>هزینه و ضمانت</h2>
            <p>هزینه‌ی بررسی و تعمیر پیش از شروع کار اعلام می‌شود و بدون تأیید شما انجام نمی‌شود. شرایط ضمانت هر تعمیر پیش از ثبت آن اعلام می‌شود.</p>
            <h2>اطلاعات نمایشی</h2>
            <p>نمونه‌های قبل و بعد، و هر محتوای نمایشی که با عبارت «نمونه» مشخص شده، تصویر کار واقعی نیستند.</p>
            <h2>مسئولیت کاربر</h2>
            <p>اطلاعات وارد شده باید درست باشند. تلویزیونی که دچار دود، بوی سوختگی یا آب‌خوردگی شده، باید پیش از هر اقدامی از برق جدا شود.</p>
          </article>
        </div>
      </section>
    </>
  );
}

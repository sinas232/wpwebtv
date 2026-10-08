import type { Metadata } from "next";
import { FaqList } from "@/components/ui";
import { FinalBand, PageHero } from "@/components/page-parts";
import { getProblems, getServices } from "@/lib/cms";
import { pageMetadata } from "@/lib/seo";
import type { FaqItem } from "@/lib/content/types";

export const metadata: Metadata = pageMetadata({
  title: "پرسش‌های رایج تعمیر تلویزیون",
  description: "پاسخ پرسش‌های رایج درباره‌ی تعمیر تلویزیون در محل، هزینه، پیگیری درخواست و حریم خصوصی.",
  path: "/faq",
});

const GENERAL: FaqItem[] = [
  { q: "تشخیص آنلاین قطعی است؟", a: "نه. تشخیص آنلاین فقط یک سرنخ احتمالی است. تشخیص قطعی نیاز به بررسی تکنسین دارد." },
  { q: "آیا قبل از تعمیر هزینه اعلام می‌شود؟", a: "بله. هزینه‌ی بررسی و تعمیر را پیش از شروع کار اعلام می‌کنیم و بدون تأیید شما کاری را شروع نمی‌کنیم." },
  { q: "هزینه‌ی رفت‌وآمد برای تعمیر در محل چقدر است؟", a: "این هزینه بر اساس منطقه محاسبه و پیش از اعزام اعلام می‌شود. فعلاً عدد ثابتی منتشر نشده است." },
  { q: "چطور درخواستم را پیگیری کنم؟", a: "پس از ثبت درخواست، یک کد پیگیری نمایش داده می‌شود. با همان کد و شماره موبایل، صفحه‌ی پیگیری وضعیت را نشان می‌دهد." },
  { q: "عکس‌ها و ویدئوهایم کجا استفاده می‌شوند؟", a: "فقط برای بررسی مشکل توسط تکنسین و کارشناس استفاده می‌شوند و به‌صورت عمومی منتشر نمی‌شوند. جزئیات در سیاست حریم خصوصی آمده است." },
  { q: "آیا در همه‌ی شهرها خدمت می‌دهید؟", a: "پوشش هر شهر پس از تأیید اعلام می‌شود. وضعیت شهرها در صفحه‌ی شهرها مشخص است." },
];

export default async function FaqPage() {
  const [services, problems] = await Promise.all([getServices(), getProblems()]);
  const serviceFaq = services.flatMap((s) => s.faq);
  const problemFaq = problems.flatMap((p) => p.faq);
  const all = [...GENERAL, ...serviceFaq, ...problemFaq];
  // Deduplicate by question so schema stays clean.
  const unique = Array.from(new Map(all.map((i) => [i.q, i])).values());

  return (
    <>
      <PageHero eyebrow="پرسش‌های رایج" title="پاسخ‌های کوتاه و صادقانه" lead="اگر پاسخ سؤال خود را پیدا نکردید، از طریق درخواست یا صفحه‌ی تماس بپرسید." crumbs={[{ name: "پرسش‌ها", href: "/faq" }]} />
      <section className="section">
        <div className="container" style={{ maxWidth: 860 }}>
          <FaqList items={unique} />
        </div>
      </section>
      <FinalBand title="جواب سؤالت را پیدا نکردی؟" text="سؤال یا مشکلت را بنویس؛ ما جواب می‌دهیم." />
    </>
  );
}

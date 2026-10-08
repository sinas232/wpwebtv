import type { Metadata } from "next";
import Link from "next/link";
import { BookingWizard } from "@/components/booking";
import { PageHero } from "@/components/page-parts";
import { getCities } from "@/lib/cms";
import { pageMetadata } from "@/lib/seo";
import { site } from "@/lib/site";

export const metadata: Metadata = {
  ...pageMetadata({
    title: "درخواست تعمیر تلویزیون",
    description: "درخواست تعمیر تلویزیون در چند مرحله‌ی ساده؛ مشکل، برند، عکس و زمان مراجعه را ثبت کن.",
    path: "/booking",
  }),
  robots: { index: true, follow: true },
};

export default async function BookingPage() {
  const cities = await getCities();
  return (
    <>
      <PageHero
        eyebrow="درخواست تعمیر"
        title="درخواستت رو در چند قدم ثبت کن"
        lead="هر مرحله یک سؤال دارد. اطلاعات بیشتر، هماهنگی سریع‌تر است. هزینه قبل از شروع تعمیر اعلام می‌شود."
        crumbs={[{ name: "درخواست تعمیر", href: "/booking" }]}
      />
      <section className="section" style={{ paddingBlockStart: "var(--s-5)" }}>
        <div className="container two-col">
          <BookingWizard cities={cities.map((c) => ({ slug: c.slug, name: c.name }))} />
          <aside className="side-panel" aria-label="بعد از ثبت درخواست">
            <p style={{ fontWeight: 900, margin: 0 }}>بعد از ثبت درخواست چه می‌شود؟</p>
            <ol className="check-list" style={{ listStyle: "decimal", paddingInlineStart: "1.2rem" }}>
              <li>کد پیگیری نمایش داده می‌شود.</li>
              <li>کارشناس درخواست و عکس‌ها را بررسی می‌کند.</li>
              <li>برای هماهنگی زمان مراجعه تماس گرفته می‌شود.</li>
              <li>هزینه پیش از تعمیر اعلام و با تأیید شما انجام می‌شود.</li>
            </ol>
            {site.phone ? (
              <a href={`tel:${site.phone}`} className="btn btn--ghost" data-track="phone_click" data-track-label="booking_side_phone">
                تماس تلفنی
              </a>
            ) : null}
            <p className="muted" style={{ margin: 0, fontSize: "0.9rem" }}>
              سؤالی دارید؟ <Link href="/faq">پرسش‌های رایج</Link> را ببینید.
            </p>
          </aside>
        </div>
      </section>
    </>
  );
}

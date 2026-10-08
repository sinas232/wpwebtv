import type { Metadata } from "next";
import { TrackingForm } from "@/components/tracking";
import { PageHero } from "@/components/page-parts";
import { pageMetadata } from "@/lib/seo";

export const metadata: Metadata = pageMetadata({
  title: "پیگیری درخواست تعمیر",
  description: "وضعیت درخواست تعمیر تلویزیون را با کد پیگیری و شماره موبایل ببینید.",
  path: "/track",
  noindex: true,
});

export default function TrackPage() {
  return (
    <>
      <PageHero eyebrow="پیگیری" title="وضعیت درخواستت" lead="کد پیگیری و شماره موبایل ثبت‌شده را وارد کن." crumbs={[{ name: "پیگیری", href: "/track" }]} />
      <section className="section">
        <div className="container">
          <TrackingForm />
        </div>
      </section>
    </>
  );
}

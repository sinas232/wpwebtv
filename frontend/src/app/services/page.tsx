import type { Metadata } from "next";
import Link from "next/link";
import { PageHero, FinalBand } from "@/components/page-parts";
import { ServiceCards } from "@/components/home/sections";
import { getServices } from "@/lib/cms";
import { pageMetadata } from "@/lib/seo";

export const metadata: Metadata = pageMetadata({
  title: "خدمات تعمیر تلویزیون",
  description: "خدمات تعمیر تلویزیون: بک‌لایت، مین‌برد، پاور، T-Con، HDMI، Smart TV، تصویر، صدا و تعمیر در محل.",
  path: "/services",
});

export default async function ServicesPage() {
  const services = await getServices();
  return (
    <>
      <PageHero
        eyebrow="خدمات"
        title="تعمیر تلویزیون، قطعه به قطعه"
        lead="هر خدمت را جدا توضیح داده‌ایم تا بدانی کار چیست، چه زمانی لازم است و هزینه‌اش چگونه اعلام می‌شود."
        crumbs={[{ name: "خدمات", href: "/services" }]}
      />
      <section className="section">
        <div className="container">
          <ServiceCards services={services} />
          <p style={{ marginBlockStart: "var(--s-5)" }} className="muted">
            نوع خدمت مشخص نیست؟ از <Link href="/diagnose">تشخیص مشکل</Link> شروع کنید.
          </p>
        </div>
      </section>
      <FinalBand title="خدمت مناسب را پیدا نکردی؟" text="مشکل را بنویس؛ کارشناس مناسب را پیشنهاد می‌دهد." />
    </>
  );
}

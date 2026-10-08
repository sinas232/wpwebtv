import type { Metadata } from "next";
import { FinalBand, PageHero } from "@/components/page-parts";
import { CityCards } from "@/components/home/sections";
import { getCities } from "@/lib/cms";
import { pageMetadata } from "@/lib/seo";

export const metadata: Metadata = pageMetadata({
  title: "شهرها و مناطق خدمت",
  description: "شهرهایی که تعمیر تلویزیون در محل برای آن‌ها بررسی می‌شود و وضعیت پوشش هر شهر.",
  path: "/cities",
});

export default async function CitiesPage() {
  const cities = await getCities();
  return (
    <>
      <PageHero
        eyebrow="منطقه‌ی خدمت"
        title="کجا تعمیر در محل داریم؟"
        lead="وضعیت پوشش هر شهر را اینجا می‌بینی. شهری که هنوز تأیید نشده، با عنوان «در انتظار تأیید» مشخص است."
        crumbs={[{ name: "شهرها", href: "/cities" }]}
      />
      <section className="section">
        <div className="container">
          <CityCards cities={cities} />
        </div>
      </section>
      <FinalBand title="شهرت را پیدا نکردی؟" text="درخواست را با نام شهر ثبت کن تا بررسی شود." />
    </>
  );
}

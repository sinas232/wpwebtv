import type { Metadata } from "next";
import Link from "next/link";
import { FinalBand, PageHero } from "@/components/page-parts";
import { getBrands } from "@/lib/cms";
import { pageMetadata } from "@/lib/seo";

export const metadata: Metadata = pageMetadata({
  title: "تعمیر تلویزیون بر اساس برند",
  description: "صفحه‌ی تعمیر تلویزیون برای سامسونگ، ال‌جی، سونی، TCL، هایسنس و سایر برندها با مشکلات رایج هر برند.",
  path: "/brands",
});

export default async function BrandsPage() {
  const brands = await getBrands();
  return (
    <>
      <PageHero
        eyebrow="برندها"
        title="تعمیر تلویزیون بر اساس برند"
        lead="هر برند مشکلات رایج خودش را دارد. برند تلویزیونت را انتخاب کن تا راهنما و درخواست مرتبط را ببینی."
        crumbs={[{ name: "برندها", href: "/brands" }]}
      />
      <section className="section">
        <div className="container">
          <ul className="brand-wall" aria-label="فهرست برندها">
            {brands.map((b) => (
              <li key={b.slug}>
                <Link href={`/brands/${b.slug}`}>
                  <span className="brand-wall__name">{b.name}</span>
                  <span className="brand-wall__latin">{b.latin}</span>
                </Link>
              </li>
            ))}
          </ul>
        </div>
      </section>
      <FinalBand title="برندت در فهرست نیست؟" text="درخواست را ثبت کن و برند و مدل را بنویس." />
    </>
  );
}

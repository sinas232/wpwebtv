import type { Metadata } from "next";
import { notFound } from "next/navigation";
import Link from "next/link";
import { CtaRow, JsonLd, Icon } from "@/components/ui";
import { FinalBand, PageHero, RelatedLinks } from "@/components/page-parts";
import { getBrand, getBrands, getProblem } from "@/lib/cms";
import { breadcrumbLd, pageMetadata, serviceLd } from "@/lib/seo";

type Params = { slug: string };

export async function generateStaticParams() {
  return (await getBrands()).map((b) => ({ slug: b.slug }));
}

export async function generateMetadata({ params }: { params: Promise<Params> }): Promise<Metadata> {
  const { slug } = await params;
  const b = await getBrand(slug);
  if (!b) return { title: "برند پیدا نشد", robots: { index: false } };
  return pageMetadata({
    title: `تعمیر تلویزیون ${b.name} (${b.latin})`,
    description: `تعمیر تلویزیون ${b.name}: مشکلات رایج، راهنمای اولیه و درخواست تعمیر. ${b.intro.slice(0, 80)}`,
    path: `/brands/${b.slug}`,
  });
}

export default async function BrandPage({ params }: { params: Promise<Params> }) {
  const { slug } = await params;
  const brand = await getBrand(slug);
  if (!brand) notFound();
  const problems = (await Promise.all(brand.relatedProblems.map((p) => getProblem(p)))).filter((p): p is NonNullable<typeof p> => Boolean(p));

  return (
    <>
      <PageHero
        eyebrow="برند"
        title={`تعمیر تلویزیون ${brand.name}`}
        lead={brand.intro}
        crumbs={[
          { name: "برندها", href: "/brands" },
          { name: brand.name, href: `/brands/${brand.slug}` },
        ]}
      />

      <section className="section">
        <div className="container two-col">
          <article className="prose">
            <h2>مشکلات رایج تلویزیون‌های {brand.name}</h2>
            <ul className="check-list">
              {brand.commonIssues.map((i) => (
                <li key={i}>{i}</li>
              ))}
            </ul>

            <h2>پیش از تماس، این موارد را آماده کن</h2>
            <ul>
              <li>مدل دقیق را از برچسب پشت تلویزیون یادداشت کن (مثلاً عدد و حروف کنار «Model»).</li>
              <li>عکس برچسب پشت دستگاه و یک ویدئوی کوتاه از مشکل بفرست.</li>
              <li>بنویس چه زمانی مشکل شروع شد و آیا بعد از قطعی برق یا ضربه بود.</li>
            </ul>
            <p className="muted">
              قطعه‌ی سازگار به مدل وابسته است. بدون مدل دقیق، تشخیص قطعی ممکن نیست و تکنسین بعد از بررسی توضیح می‌دهد.
            </p>
          </article>

          <aside className="side-panel" aria-label="درخواست">
            <p style={{ fontWeight: 900, margin: 0 }}>تعمیر تلویزیون {brand.name} را درخواست کن</p>
            <p className="muted" style={{ margin: 0, fontSize: "0.95rem" }}>
              برند از پیش انتخاب می‌شود؛ فقط مشکل و منطقه را کامل کن.
            </p>
            <Link href={`/booking?brand=${encodeURIComponent(brand.name)}`} className="btn btn--primary">
              درخواست تعمیر {brand.name}
              <Icon name="arrow" size={18} />
            </Link>
            <CtaRow primaryLabel="درخواست تعمیر" secondaryLabel="مشکل تلویزیونم رو پیدا کن" />
          </aside>
        </div>
        <div className="container">
          <RelatedLinks title="مشکلات مرتبط" links={problems.map((p) => ({ href: `/problems/${p.slug}`, label: p.title }))} />
        </div>
      </section>

      <FinalBand title={`تلویزیون ${brand.name} خراب است؟`} text="درخواست را ثبت کن؛ هزینه پیش از شروع تعمیر اعلام می‌شود." />

      <JsonLd data={serviceLd({ name: `تعمیر تلویزیون ${brand.name}`, description: brand.intro, path: `/brands/${brand.slug}` })} />
      <JsonLd data={breadcrumbLd([{ name: "خانه", path: "/" }, { name: "برندها", path: "/brands" }, { name: brand.name, path: `/brands/${brand.slug}` }])} />
    </>
  );
}

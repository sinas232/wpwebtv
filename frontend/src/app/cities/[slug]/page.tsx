import type { Metadata } from "next";
import { notFound } from "next/navigation";
import Link from "next/link";
import { CtaRow, JsonLd, Icon } from "@/components/ui";
import { FinalBand, PageHero, RelatedLinks } from "@/components/page-parts";
import { getCities, getCity, getProblems, getServices } from "@/lib/cms";
import { breadcrumbLd, pageMetadata } from "@/lib/seo";

type Params = { slug: string };

export async function generateStaticParams() {
  return (await getCities()).map((c) => ({ slug: c.slug }));
}

export async function generateMetadata({ params }: { params: Promise<Params> }): Promise<Metadata> {
  const { slug } = await params;
  const c = await getCity(slug);
  if (!c) return { title: "شهر پیدا نشد", robots: { index: false } };
  return pageMetadata({
    title: `تعمیر تلویزیون در ${c.name}`,
    description: `تعمیر تلویزیون در ${c.name} در محل: درخواست، تشخیص اولیه و هماهنگی مراجعه تکنسین.`,
    path: `/cities/${c.slug}`,
    // Coverage is not confirmed yet: keep unconfirmed city pages out of the index.
    noindex: c.coverage !== "confirmed",
  });
}

export default async function CityPage({ params }: { params: Promise<Params> }) {
  const { slug } = await params;
  const city = await getCity(slug);
  if (!city) notFound();
  const [problems, services] = await Promise.all([getProblems(), getServices()]);
  const confirmed = city.coverage === "confirmed";

  return (
    <>
      <PageHero
        eyebrow="منطقه‌ی خدمت"
        title={`تعمیر تلویزیون در ${city.name}`}
        lead={city.intro}
        crumbs={[
          { name: "شهرها", href: "/cities" },
          { name: city.name, href: `/cities/${city.slug}` },
        ]}
      />
      <section className="section">
        <div className="container two-col">
          <article className="prose">
            {confirmed ? (
              <p className="notice notice--success" role="status">پوشش خدمت در {city.name} تأیید شده است.</p>
            ) : (
              <p className="notice" role="status">
                پوشش خدمت در {city.name} هنوز توسط کسب‌وکار تأیید نشده است. درخواست شما ثبت می‌شود و زمان مراجعه پس از بررسی منطقه اعلام می‌شود.
              </p>
            )}

            <h2>پیش از مراجعه تکنسین در {city.name}</h2>
            <ul className="check-list">
              {city.visitNotes.map((n) => (
                <li key={n}>{n}</li>
              ))}
            </ul>

            <h2>چه کارهایی را در منزل می‌توانید انجام دهید؟</h2>
            <p>
              قبل از مراجعه، کابل برق و پریز را بررسی کنید و تلویزیون را از یک پریز سالم وصل کنید. اگر دستگاه بوی سوختگی یا دود دارد، آن را خاموش کنید و از برق بکشید.
            </p>
          </article>

          <aside className="side-panel" aria-label="درخواست">
            <p style={{ fontWeight: 900, margin: 0 }}>درخواست تعمیر در {city.name}</p>
            <Link href={`/booking?city=${encodeURIComponent(city.name)}`} className="btn btn--primary">
              درخواست تعمیر
              <Icon name="arrow" size={18} />
            </Link>
            <CtaRow primaryHref="/diagnose" primaryLabel="تشخیص مشکل" secondaryHref="/track" secondaryLabel="پیگیری درخواست" />
          </aside>
        </div>
        <div className="container">
          <RelatedLinks title="مشکلات رایج" links={problems.slice(0, 6).map((p) => ({ href: `/problems/${p.slug}`, label: p.title }))} />
          <RelatedLinks title="خدمات" links={services.slice(0, 6).map((s) => ({ href: `/services/${s.slug}`, label: s.title }))} />
        </div>
      </section>

      <FinalBand title={`تلویزیونت در ${city.name} خراب است؟`} text="مشکل را بنویس؛ هماهنگی و هزینه پیش از شروع کار اعلام می‌شود." />

      <JsonLd data={breadcrumbLd([{ name: "خانه", path: "/" }, { name: "شهرها", path: "/cities" }, { name: city.name, path: `/cities/${city.slug}` }])} />
    </>
  );
}

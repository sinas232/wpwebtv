import type { Metadata } from "next";
import { notFound } from "next/navigation";
import Link from "next/link";
import { FaqList, JsonLd, PriceNote, Complexity, CtaRow } from "@/components/ui";
import { FinalBand, PageHero, RelatedLinks } from "@/components/page-parts";
import { getProblem, getProblems, getService, getServices } from "@/lib/cms";
import { pageMetadata, serviceLd, breadcrumbLd } from "@/lib/seo";

type Params = { slug: string };

export async function generateStaticParams() {
  return (await getServices()).map((s) => ({ slug: s.slug }));
}

export async function generateMetadata({ params }: { params: Promise<Params> }): Promise<Metadata> {
  const { slug } = await params;
  const s = await getService(slug);
  if (!s) return { title: "خدمت پیدا نشد", robots: { index: false } };
  return pageMetadata({ title: `${s.title} | خدمات`, description: s.summary, path: `/services/${s.slug}` });
}

export default async function ServicePage({ params }: { params: Promise<Params> }) {
  const { slug } = await params;
  const service = await getService(slug);
  if (!service) notFound();
  const related = (await Promise.all(service.relatedProblems.map((p) => getProblem(p)))).filter((p): p is NonNullable<typeof p> => Boolean(p));
  const allProblems = await getProblems();

  return (
    <>
      <PageHero
        eyebrow="خدمت تعمیر"
        title={service.title}
        lead={service.summary}
        crumbs={[
          { name: "خدمات", href: "/services" },
          { name: service.title, href: `/services/${service.slug}` },
        ]}
      >
        <div className="btn-row" style={{ alignItems: "center", marginBlockStart: "var(--s-4)" }}>
          <Complexity level={service.complexity} />
          <PriceNote price={service.price} />
        </div>
      </PageHero>

      <section className="section">
        <div className="container two-col">
          <article className="prose">
            {service.body.map((p, i) => (
              <p key={i} style={{ fontSize: "1.05rem" }}>
                {p}
              </p>
            ))}

            <h2>روند کار</h2>
            <ol>
              <li>ثبت درخواست و توضیح علائم</li>
              <li>بررسی اولیه و هماهنگی زمان مراجعه</li>
              <li>اعلام تشخیص احتمالی و هزینه‌ی تقریبی؛ شروع کار با تأیید شما</li>
              <li>تعمیر، تست نهایی و تحویل با فاکتور</li>
            </ol>

            {service.faq.length ? (
              <>
                <h2>پرسش‌های رایج</h2>
                <FaqList items={service.faq} />
              </>
            ) : null}
          </article>

          <aside className="side-panel" aria-label="اقدام سریع">
            <p style={{ fontWeight: 900, margin: 0 }}>درخواست {service.title}</p>
            <p className="muted" style={{ margin: 0, fontSize: "0.95rem" }}>
              مشکل را ثبت کن. تکنسین پیش از مراجعه با تو هماهنگ می‌کند و هزینه را پیش از شروع اعلام می‌کند.
            </p>
            <CtaRow />
          </aside>
        </div>
        <div className="container">
          <RelatedLinks
            title="مشکلات مرتبط"
            links={related.map((p) => ({ href: `/problems/${p.slug}`, label: p.title }))}
          />
          {!related.length && allProblems.length ? (
            <p className="muted" style={{ marginBlockStart: "var(--s-5)" }}>
              مشکل خود را در <Link href="/problems">فهرست مشکلات</Link> پیدا کنید.
            </p>
          ) : null}
        </div>
      </section>

      <FinalBand title="آماده‌ای؟" text="درخواست را ثبت کن؛ هزینه پیش از شروع تعمیر اعلام می‌شود." />

      <JsonLd data={serviceLd({ name: service.title, description: service.summary, path: `/services/${service.slug}` })} />
      <JsonLd data={breadcrumbLd([{ name: "خانه", path: "/" }, { name: "خدمات", path: "/services" }, { name: service.title, path: `/services/${service.slug}` }])} />
    </>
  );
}

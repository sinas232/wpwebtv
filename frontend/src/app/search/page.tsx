import type { Metadata } from "next";
import Link from "next/link";
import { EmptyState } from "@/components/ui";
import { PageHero } from "@/components/page-parts";
import { getBlogPosts, getBrands, getProblems, getServices } from "@/lib/cms";
import { pageMetadata } from "@/lib/seo";

export const metadata: Metadata = pageMetadata({
  title: "جستجو",
  description: "جستجو در خدمات، مشکلات، برندها و مقاله‌های سایت.",
  path: "/search",
  noindex: true,
});

/** Normalises Persian/Arabic letters and whitespace so "ي" and "ی" match. */
function norm(s: string): string {
  return s.replace(/ي/g, "ی").replace(/ك/g, "ک").replace(/\u200c/g, " ").replace(/\s+/g, " ").trim().toLowerCase();
}

export default async function SearchPage({ searchParams }: { searchParams: Promise<{ q?: string }> }) {
  const { q = "" } = await searchParams;
  const query = norm(q.slice(0, 100));
  const [services, problems, brands, posts] = await Promise.all([getServices(), getProblems(), getBrands(), getBlogPosts()]);

  const match = (...fields: string[]) => query.length > 0 && fields.some((f) => norm(f).includes(query));

  const results = [
    ...services.filter((s) => match(s.title, s.summary)).map((s) => ({ href: `/services/${s.slug}`, label: s.title, kind: "خدمت" })),
    ...problems.filter((p) => match(p.title, p.summary)).map((p) => ({ href: `/problems/${p.slug}`, label: p.title, kind: "مشکل" })),
    ...brands.filter((b) => match(b.name, b.latin)).map((b) => ({ href: `/brands/${b.slug}`, label: `تعمیر تلویزیون ${b.name}`, kind: "برند" })),
    ...posts.filter((p) => match(p.title, p.excerpt)).map((p) => ({ href: `/blog/${p.slug}`, label: p.title, kind: "مقاله" })),
  ];

  return (
    <>
      <PageHero eyebrow="جستجو" title="جستجو در سایت" crumbs={[{ name: "جستجو", href: "/search" }]}>
        <form action="/search" role="search" className="search-form" aria-label="جستجوی سایت">
          <label htmlFor="q" className="sr-only">
            عبارت جستجو
          </label>
          <input id="q" name="q" type="search" className="input" defaultValue={q} placeholder="مثلاً بک‌لایت، HDMI، سامسونگ" />
          <button type="submit" className="btn btn--primary">
            جستجو
          </button>
        </form>
      </PageHero>
      <section className="section">
        <div className="container">
          {!query ? (
            <p className="muted">عبارتی برای جستجو وارد کنید.</p>
          ) : results.length === 0 ? (
            <EmptyState title="نتیجه‌ای پیدا نشد" text="عبارت دیگری امتحان کنید یا از تشخیص تعاملی شروع کنید.">
              <Link href="/diagnose" className="btn btn--primary">
                تشخیص مشکل
              </Link>
            </EmptyState>
          ) : (
            <>
              <p className="muted" role="status">
                {results.length} نتیجه
              </p>
              <ul className="grid grid--2" style={{ listStyle: "none", padding: 0, margin: 0 }}>
                {results.map((r) => (
                  <li key={r.href}>
                    <Link href={r.href} className="card" style={{ flexDirection: "row", justifyContent: "space-between", alignItems: "center" }}>
                      <span style={{ fontWeight: 700 }}>{r.label}</span>
                      <span className="tag">{r.kind}</span>
                    </Link>
                  </li>
                ))}
              </ul>
            </>
          )}
        </div>
      </section>
    </>
  );
}

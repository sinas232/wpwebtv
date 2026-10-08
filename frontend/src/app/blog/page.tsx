import type { Metadata } from "next";
import Link from "next/link";
import { PageHero } from "@/components/page-parts";
import { faDate } from "@/lib/format";
import { getBlogPosts } from "@/lib/cms";
import { pageMetadata } from "@/lib/seo";

export const metadata: Metadata = pageMetadata({
  title: "مقاله‌های تعمیر تلویزیون",
  description: "راهنمای عملی برای عیب‌یابی تلویزیون، آماده‌سازی قبل از تعمیر و تشخیص مشکلات رایج.",
  path: "/blog",
});

export default async function BlogPage() {
  const posts = await getBlogPosts();
  const categories = Array.from(new Set(posts.map((p) => p.category)));
  return (
    <>
      <PageHero eyebrow="مقاله‌ها" title="راهنمای عملی برای تلویزیونت" lead="مقاله‌هایی که واقعاً کمک می‌کنند: چه کاری در خانه ایمن است و کی باید تکنسین را خبر کنی." crumbs={[{ name: "مقاله‌ها", href: "/blog" }]} />
      <section className="section">
        <div className="container">
          {categories.length ? (
            <ul className="faq" style={{ display: "flex", flexWrap: "wrap", gap: "var(--s-2)", listStyle: "none", padding: 0, marginBlockEnd: "var(--s-5)" }} aria-label="دسته‌ها">
              {categories.map((c) => (
                <li key={c}>
                  <span className="tag">{c}</span>
                </li>
              ))}
            </ul>
          ) : null}
          <ul className="grid grid--2" style={{ listStyle: "none", padding: 0, margin: 0 }}>
            {posts.map((p) => (
              <li key={p.slug}>
                <Link href={`/blog/${p.slug}`} className="card" style={{ height: "100%" }}>
                  <span className="tag" style={{ alignSelf: "flex-start" }}>{p.category}</span>
                  <h2 className="card__title" style={{ fontSize: "1.2rem" }}>{p.title}</h2>
                  <p className="problem-card__desc">{p.excerpt}</p>
                  <div className="card__meta">
                    <span>{p.author}</span>
                    {p.publishedAt ? <time dateTime={p.publishedAt}>{faDate(p.publishedAt)}</time> : null}
                  </div>
                </Link>
              </li>
            ))}
          </ul>
          {posts.length === 0 ? <p className="empty">هنوز مقاله‌ای منتشر نشده است.</p> : null}
        </div>
      </section>
    </>
  );
}

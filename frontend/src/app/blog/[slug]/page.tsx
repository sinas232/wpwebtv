import type { Metadata } from "next";
import { notFound } from "next/navigation";
import Link from "next/link";
import { Breadcrumbs, JsonLd } from "@/components/ui";
import { FinalBand, RelatedLinks } from "@/components/page-parts";
import { faDate } from "@/lib/format";
import { getBlogPost, getBlogPosts, getProblem } from "@/lib/cms";
import { articleLd, breadcrumbLd, pageMetadata } from "@/lib/seo";

type Params = { slug: string };

export async function generateStaticParams() {
  return (await getBlogPosts()).map((p) => ({ slug: p.slug }));
}

export async function generateMetadata({ params }: { params: Promise<Params> }): Promise<Metadata> {
  const { slug } = await params;
  const post = await getBlogPost(slug);
  if (!post) return { title: "مقاله پیدا نشد", robots: { index: false } };
  return pageMetadata({ title: post.title, description: post.excerpt, path: `/blog/${post.slug}` });
}

export default async function BlogPostPage({ params }: { params: Promise<Params> }) {
  const { slug } = await params;
  const post = await getBlogPost(slug);
  if (!post) notFound();
  const problems = (await Promise.all(post.relatedProblems.map((p) => getProblem(p)))).filter((p): p is NonNullable<typeof p> => Boolean(p));

  return (
    <>
      <section className="page-hero">
        <div className="container">
          <Breadcrumbs items={[{ name: "مقاله‌ها", href: "/blog" }, { name: post.title, href: `/blog/${post.slug}` }]} />
          <p className="eyebrow">{post.category}</p>
          <h1 style={{ fontSize: "clamp(1.9rem, 1.3rem + 2.4vw, 3rem)" }}>{post.title}</h1>
          <p className="muted" style={{ margin: 0 }}>
            {post.author}
            {post.publishedAt ? (
              <>
                {" · "}
                <time dateTime={post.publishedAt}>{faDate(post.publishedAt)}</time>
              </>
            ) : null}
          </p>
        </div>
      </section>

      <section className="section">
        <div className="container">
          <article className="prose">
            <p className="lead" style={{ maxWidth: "none" }}>{post.excerpt}</p>
            {post.body.map((p, i) => (
              <p key={i}>{p}</p>
            ))}
          </article>
          <RelatedLinks title="راهنمای مرتبط" links={problems.map((p) => ({ href: `/problems/${p.slug}`, label: p.title }))} />
          <p style={{ marginBlockStart: "var(--s-5)" }}>
            <Link href="/blog" className="btn btn--ghost">
              همه‌ی مقاله‌ها
            </Link>
          </p>
        </div>
      </section>

      <FinalBand title="مشکل این تلویزیون را بررسی کنیم؟" text="با تشخیص تعاملی شروع کن یا درخواست تعمیر ثبت کن." />

      <JsonLd data={articleLd({ title: post.title, description: post.excerpt, path: `/blog/${post.slug}`, publishedAt: post.publishedAt, author: post.author })} />
      <JsonLd data={breadcrumbLd([{ name: "خانه", path: "/" }, { name: "مقاله‌ها", path: "/blog" }, { name: post.title, path: `/blog/${post.slug}` }])} />
    </>
  );
}

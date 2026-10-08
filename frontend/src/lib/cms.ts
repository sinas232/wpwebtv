/**
 * Content access layer. WordPress is the headless CMS when WP_API_URL is set;
 * otherwise (or when the CMS is unreachable) the local seed is used. The
 * function signatures stay the same either way, so pages never change when the
 * source changes.
 *
 * Mapping rules: CMS records are matched to seed records by slug. Fields the
 * CMS does not provide (for example price, coverage) keep the seed or the safe
 * default, so the site never shows data that nobody entered.
 */
import { site } from "@/lib/site";
import * as seed from "@/lib/content/seed";
import type { BlogPost, Brand, City, Problem, Service } from "@/lib/content/types";

type WpPost = {
  slug: string;
  title?: { rendered?: string };
  excerpt?: { rendered?: string };
  content?: { rendered?: string };
  date?: string;
};

const REVALIDATE = 3600;

function stripHtml(html: string | undefined): string {
  return (html ?? "")
    .replace(/<script[\s\S]*?<\/script>/gi, "")
    .replace(/<style[\s\S]*?<\/style>/gi, "")
    .replace(/<\/p>/gi, "\n")
    .replace(/<[^>]+>/g, "")
    .replace(/&nbsp;/g, " ")
    .replace(/&amp;/g, "&")
    .replace(/&quot;/g, '"')
    .replace(/&#8217;|&rsquo;/g, "’")
    .replace(/&#8211;|&ndash;/g, "–")
    .replace(/[ \t]+/g, " ")
    .trim();
}

function paragraphs(html: string | undefined): string[] {
  return stripHtml(html)
    .split(/\n+/)
    .map((p) => p.trim())
    .filter(Boolean);
}

async function wpList(restBase: string): Promise<WpPost[] | null> {
  if (!site.cmsUrl) return null;
  try {
    const url = `${site.cmsUrl}/wp-json/wp/v2/${restBase}?per_page=100&_fields=slug,title,excerpt,content,date`;
    const res = await fetch(url, { next: { revalidate: REVALIDATE } });
    if (!res.ok) return null;
    const data = (await res.json()) as unknown;
    return Array.isArray(data) ? (data as WpPost[]) : null;
  } catch {
    return null;
  }
}

export async function getServices(): Promise<Service[]> {
  const remote = await wpList("tv_services");
  if (!remote) return seed.services;
  const bySlug = new Map(remote.map((p) => [p.slug, p]));
  return seed.services.map((s) => {
    const p = bySlug.get(s.slug);
    if (!p) return s;
    const body = paragraphs(p.content?.rendered);
    return {
      ...s,
      title: stripHtml(p.title?.rendered) || s.title,
      summary: stripHtml(p.excerpt?.rendered) || s.summary,
      body: body.length ? body : s.body,
    };
  });
}

export async function getService(slug: string): Promise<Service | undefined> {
  return (await getServices()).find((s) => s.slug === slug);
}

export async function getBrands(): Promise<Brand[]> {
  const remote = await wpList("tv_brands");
  if (!remote) return seed.brands;
  const bySlug = new Map(remote.map((p) => [p.slug, p]));
  return seed.brands.map((b) => {
    const p = bySlug.get(b.slug);
    if (!p) return b;
    const intro = stripHtml(p.excerpt?.rendered);
    return { ...b, intro: intro || b.intro };
  });
}

export async function getBrand(slug: string): Promise<Brand | undefined> {
  return (await getBrands()).find((b) => b.slug === slug);
}

/** Problems and cities are local until a CMS taxonomy/CPT is mapped (see README). */
export async function getProblems(): Promise<Problem[]> {
  return seed.problems;
}

export async function getProblem(slug: string): Promise<Problem | undefined> {
  return seed.problems.find((p) => p.slug === slug);
}

export async function getCities(): Promise<City[]> {
  return seed.cities;
}

export async function getCity(slug: string): Promise<City | undefined> {
  return seed.cities.find((c) => c.slug === slug);
}

export async function getBlogPosts(): Promise<BlogPost[]> {
  const remote = await wpList("posts");
  if (!remote || remote.length === 0) return seed.blogPosts;
  return remote.map((p) => ({
    slug: p.slug,
    title: stripHtml(p.title?.rendered),
    excerpt: stripHtml(p.excerpt?.rendered),
    category: "مقاله",
    author: "تیم تخصصی",
    publishedAt: (p.date ?? "").slice(0, 10),
    body: paragraphs(p.content?.rendered),
    relatedProblems: [],
  }));
}

export async function getBlogPost(slug: string): Promise<BlogPost | undefined> {
  return (await getBlogPosts()).find((p) => p.slug === slug);
}

/** Where content came from, for the admin/debug footer only. */
export function contentSource(): "cms" | "seed" {
  return site.cmsUrl ? "cms" : "seed";
}

import type { MetadataRoute } from "next";
import { getBlogPosts, getBrands, getCities, getProblems, getServices } from "@/lib/cms";
import { site } from "@/lib/site";

/**
 * Sitemap. Only confirmed city pages are listed; unconfirmed cities are
 * noindex and must not be advertised to search engines.
 */
export default async function sitemap(): Promise<MetadataRoute.Sitemap> {
  const now = new Date();
  const [services, brands, problems, cities, posts] = await Promise.all([
    getServices(),
    getBrands(),
    getProblems(),
    getCities(),
    getBlogPosts(),
  ]);

  const fixed = ["/", "/diagnose", "/services", "/brands", "/problems", "/cities", "/booking", "/faq", "/contact", "/blog", "/privacy", "/terms"];

  return [
    ...fixed.map((path) => ({ url: `${site.url}${path}`, lastModified: now, changeFrequency: "weekly" as const, priority: path === "/" ? 1 : 0.7 })),
    ...services.map((s) => ({ url: `${site.url}/services/${s.slug}`, lastModified: now, changeFrequency: "monthly" as const, priority: 0.8 })),
    ...brands.map((b) => ({ url: `${site.url}/brands/${b.slug}`, lastModified: now, changeFrequency: "monthly" as const, priority: 0.7 })),
    ...problems.map((p) => ({ url: `${site.url}/problems/${p.slug}`, lastModified: now, changeFrequency: "monthly" as const, priority: 0.8 })),
    ...cities
      .filter((c) => c.coverage === "confirmed")
      .map((c) => ({ url: `${site.url}/cities/${c.slug}`, lastModified: now, changeFrequency: "monthly" as const, priority: 0.7 })),
    ...posts.map((p) => ({ url: `${site.url}/blog/${p.slug}`, lastModified: p.publishedAt ? new Date(p.publishedAt) : now, changeFrequency: "yearly" as const, priority: 0.5 })),
  ];
}

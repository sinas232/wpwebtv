import type { Metadata } from "next";
import { site } from "@/lib/site";

/** Builds page metadata with canonical and Open Graph (fa_IR). */
export function pageMetadata(opts: {
  title: string;
  description: string;
  path: string;
  noindex?: boolean;
}): Metadata {
  const canonical = `${site.url}${opts.path}`;
  return {
    title: opts.title,
    description: opts.description,
    alternates: { canonical },
    openGraph: {
      type: "website",
      locale: site.locale,
      siteName: site.name,
      title: opts.title,
      description: opts.description,
      url: canonical,
    },
    robots: opts.noindex ? { index: false, follow: false } : { index: true, follow: true },
  };
}

export type Crumb = { name: string; path: string };

export function breadcrumbLd(crumbs: Crumb[]) {
  return {
    "@context": "https://schema.org",
    "@type": "BreadcrumbList",
    itemListElement: crumbs.map((c, i) => ({
      "@type": "ListItem",
      position: i + 1,
      name: c.name,
      item: `${site.url}${c.path}`,
    })),
  };
}

export function faqLd(items: { q: string; a: string }[]) {
  return {
    "@context": "https://schema.org",
    "@type": "FAQPage",
    mainEntity: items.map((i) => ({
      "@type": "Question",
      name: i.q,
      acceptedAnswer: { "@type": "Answer", text: i.a },
    })),
  };
}

/**
 * Service schema. Provider is the brand; no offers/prices unless the business
 * has approved a price (passed in explicitly by the caller).
 */
export function serviceLd(opts: { name: string; description: string; path: string; areaName?: string }) {
  return {
    "@context": "https://schema.org",
    "@type": "Service",
    name: opts.name,
    description: opts.description,
    url: `${site.url}${opts.path}`,
    serviceType: "تعمیر تلویزیون",
    provider: { "@type": "LocalBusiness", name: site.name, url: site.url },
    ...(opts.areaName ? { areaServed: { "@type": "City", name: opts.areaName } } : {}),
    inLanguage: "fa-IR",
  };
}

/**
 * LocalBusiness schema. Only includes fields that are configured. Phone is
 * included only when NEXT_PUBLIC_PHONE is set.
 */
export function localBusinessLd() {
  const ld: Record<string, unknown> = {
    "@context": "https://schema.org",
    "@type": "LocalBusiness",
    name: site.name,
    url: site.url,
    description: site.description,
    inLanguage: "fa-IR",
  };
  if (site.phone) ld.telephone = site.phone;
  if (site.email) ld.email = site.email;
  return ld;
}

export function articleLd(opts: { title: string; description: string; path: string; publishedAt: string; author: string }) {
  return {
    "@context": "https://schema.org",
    "@type": "Article",
    headline: opts.title,
    description: opts.description,
    url: `${site.url}${opts.path}`,
    datePublished: opts.publishedAt,
    author: { "@type": "Organization", name: opts.author },
    publisher: { "@type": "Organization", name: site.name },
    inLanguage: "fa-IR",
  };
}

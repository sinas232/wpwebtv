import Link from "next/link";
import type { ReactNode } from "react";
import { Breadcrumbs, CtaRow, Icon, JsonLd, PriceNote, Complexity } from "@/components/ui";
import { breadcrumbLd, type Crumb } from "@/lib/seo";
import type { Complexity as ComplexityLevel } from "@/lib/content/types";
import type { PriceDisplay } from "@/lib/site";

/** Shared hero for inner pages. One H1, optional breadcrumbs, optional lead. */
export function PageHero({
  title,
  lead,
  crumbs,
  eyebrow,
  children,
}: {
  title: string;
  lead?: ReactNode;
  crumbs?: { name: string; href: string }[];
  eyebrow?: string;
  children?: ReactNode;
}) {
  return (
    <section className="page-hero">
      <div className="container">
        {crumbs ? (
          <>
            <Breadcrumbs items={crumbs} />
            {crumbs.length ? (
              <JsonLd data={breadcrumbLd([{ name: "خانه", path: "/" }, ...crumbs.map((c) => ({ name: c.name, path: c.href } as Crumb))])} />
            ) : null}
          </>
        ) : null}
        {eyebrow ? <p className="eyebrow">{eyebrow}</p> : null}
        <h1>{title}</h1>
        {lead ? <p className="lead">{lead}</p> : null}
        {children}
      </div>
    </section>
  );
}

export function SafetyNote({ children }: { children: ReactNode }) {
  return (
    <aside className="notice" role="note" style={{ borderInlineStart: "3px solid #ffb86b" }}>
      <p style={{ margin: 0, fontWeight: 700 }}>نکته‌ی ایمنی</p>
      <p style={{ margin: "var(--s-1) 0 0" }}>{children}</p>
    </aside>
  );
}

export function QuickFacts({ complexity, price }: { complexity: ComplexityLevel; price: PriceDisplay }) {
  return (
    <div className="btn-row" style={{ alignItems: "center", gap: "var(--s-3)" }}>
      <Complexity level={complexity} />
      <PriceNote price={price} />
    </div>
  );
}

export function FinalBand({ title, text }: { title: string; text: string }) {
  return (
    <section className="section section--tight" style={{ borderBlockStart: "1px solid var(--line-dark)" }}>
      <div className="container">
        <h2 style={{ fontSize: "clamp(1.5rem, 1.1rem + 1.2vw, 2.2rem)" }}>{title}</h2>
        <p className="lead">{text}</p>
        <CtaRow />
      </div>
    </section>
  );
}

export function RelatedLinks({ title, links }: { title: string; links: { href: string; label: string }[] }) {
  if (!links.length) return null;
  return (
    <nav aria-label={title} style={{ marginBlockStart: "var(--s-7)" }}>
      <h2 style={{ fontSize: "1.3rem" }}>{title}</h2>
      <ul className="grid grid--3" style={{ listStyle: "none", padding: 0, margin: 0 }}>
        {links.map((l) => (
          <li key={l.href}>
            <Link href={l.href} className="card" style={{ flexDirection: "row", alignItems: "center", justifyContent: "space-between" }}>
              <span style={{ fontWeight: 700 }}>{l.label}</span>
              <Icon name="arrow" size={18} />
            </Link>
          </li>
        ))}
      </ul>
    </nav>
  );
}

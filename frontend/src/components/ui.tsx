import type { ReactNode } from "react";
import Link from "next/link";
import { complexityLabel, faNum } from "@/lib/format";
import type { Complexity, FaqItem } from "@/lib/content/types";
import type { PriceDisplay } from "@/lib/site";
import { faqLd } from "@/lib/seo";

/* ---------- Icons: single-stroke, 24px grid, currentColor ---------- */

const ICONS = {
  screen: <path d="M3 5h18v11H3zM8 20h8M12 16v4" />,
  sound: <path d="M4 9v6h4l5 4V5L8 9zM16 9a4 4 0 0 1 0 6" />,
  dim: <path d="M12 3v2M12 19v2M4.2 4.2l1.4 1.4M18.4 18.4l1.4 1.4M3 12h2M19 12h2M4.2 19.8l1.4-1.4M18.4 5.6l1.4-1.4M12 8a4 4 0 1 0 0 8 4 4 0 0 0 0-8z" />,
  power: <path d="M12 3v8M6.3 6.3a8 8 0 1 0 11.4 0" />,
  reboot: <path d="M4 12a8 8 0 0 1 14-5.3L20 9M20 4v5h-5M20 12a8 8 0 0 1-14 5.3L4 15M4 20v-5h5" />,
  hdmi: <path d="M5 8h14v6l-2 3H7l-2-3zM9 8v3M15 8v3" />,
  wifi: <path d="M2 9a15 15 0 0 1 20 0M5 12.5a10 10 0 0 1 14 0M8.5 16a5 5 0 0 1 7 0M12 19.5h.01" />,
  other: <path d="M9 9a3 3 0 1 1 4 2.8c-.7.3-1 .9-1 1.7M12 17.5h.01M12 3a9 9 0 1 0 0 18 9 9 0 0 0 0-18z" />,
  track: <path d="M12 7v5l3 2M12 3a9 9 0 1 0 0 18 9 9 0 0 0 0-18z" />,
  shield: <path d="M12 3l7 3v6c0 4.5-3 7.5-7 9-4-1.5-7-4.5-7-9V6z" />,
  pulse: <path d="M3 12h4l2-5 4 10 2-5h6" />,
  board: <path d="M4 5h16v14H4zM8 9h2M8 13h8M14 9h2" />,
  panel: <path d="M3 4h18v12H3zM7 20h10M12 16v4M6 8l3 3 4-4" />,
  phone: <path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2" />,
  arrow: <path d="M5 12h14M13 6l6 6-6 6" />,
  check: <path d="M5 12.5l4.5 4.5L19 7.5" />,
  close: <path d="M6 6l12 12M18 6L6 18" />,
  menu: <path d="M4 7h16M4 12h16M4 17h16" />,
  quote: <path d="M7 17c-1.5 0-3-1-3-3.5C4 10 6 8 9 7M18 17c-1.5 0-3-1-3-3.5C15 10 17 8 20 7" />,
  tools: <path d="M14.7 6.3a4 4 0 0 0 5 5L21 13l-8 8-3-3-3 3-1-1 3-3-3-3 8-8z" />,
  calendar: <path d="M4 6h16v14H4zM4 10h16M8 3v4M16 3v4" />,
  pin: <path d="M12 21s-7-6-7-11a7 7 0 0 1 14 0c0 5-7 11-7 11zM12 12a2 2 0 1 0 0-4 2 2 0 0 0 0 4z" />,
} as const;

export type IconName = keyof typeof ICONS;

export function Icon({ name, size = 24, className }: { name: IconName; size?: number; className?: string }) {
  return (
    <svg
      width={size}
      height={size}
      viewBox="0 0 24 24"
      fill="none"
      stroke="currentColor"
      strokeWidth={1.75}
      strokeLinecap="round"
      strokeLinejoin="round"
      aria-hidden="true"
      focusable="false"
      className={className}
    >
      {ICONS[name]}
    </svg>
  );
}

/* ---------- Layout helpers ---------- */

export function SectionHead({
  eyebrow,
  title,
  lead,
  id,
  light = false,
}: {
  eyebrow?: string;
  title: string;
  lead?: ReactNode;
  id?: string;
  light?: boolean;
}) {
  return (
    <header className="section-head" data-light={light || undefined}>
      {eyebrow ? <p className="eyebrow">{eyebrow}</p> : null}
      <h2 id={id}>{title}</h2>
      {lead ? <p className="lead">{lead}</p> : null}
    </header>
  );
}

export function Complexity({ level }: { level: Complexity }) {
  const n = level === "low" ? 1 : level === "medium" ? 2 : 3;
  return (
    <span className="complexity">
      <span className="complexity__bars" aria-hidden="true">
        {[1, 2, 3].map((i) => (
          <span key={i} data-on={i <= n ? "true" : "false"} />
        ))}
      </span>
      {complexityLabel[level]}
    </span>
  );
}

/** Price policy: never shows a number that the business has not approved. */
export function PriceNote({ price }: { price: PriceDisplay }) {
  if (price.mode === "needs_review") {
    return <span className="price-note">هزینه: نیازمند بررسی</span>;
  }
  const range = price.from && price.to ? `از ${faNum(price.from)} تا ${faNum(price.to)} تومان` : price.label;
  return <span className="price-note">{range}</span>;
}

export function Breadcrumbs({ items }: { items: { name: string; href: string }[] }) {
  return (
    <nav aria-label="مسیر صفحه" className="breadcrumbs">
      <ol>
        <li>
          <Link href="/">خانه</Link>
        </li>
        {items.map((item, i) => (
          <li key={item.href} aria-current={i === items.length - 1 ? "page" : undefined}>
            {i === items.length - 1 ? item.name : <Link href={item.href}>{item.name}</Link>}
          </li>
        ))}
      </ol>
    </nav>
  );
}

export function FaqList({ items, withSchema = true }: { items: FaqItem[]; withSchema?: boolean }) {
  if (!items.length) return null;
  return (
    <>
      <div className="faq">
        {items.map((item) => (
          <details key={item.q}>
            <summary>{item.q}</summary>
            <p>{item.a}</p>
          </details>
        ))}
      </div>
      {withSchema ? <JsonLd data={faqLd(items)} /> : null}
    </>
  );
}

export function JsonLd({ data }: { data: unknown }) {
  return <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(data).replace(/</g, "\\u003c") }} />;
}

export function EmptyState({ title, text, children }: { title: string; text: string; children?: ReactNode }) {
  return (
    <div className="empty" role="status">
      <p style={{ fontWeight: 800, color: "var(--paper)", marginBottom: "var(--s-2)" }}>{title}</p>
      <p style={{ margin: 0 }}>{text}</p>
      {children ? <div style={{ marginBlockStart: "var(--s-4)" }}>{children}</div> : null}
    </div>
  );
}

/** Scroll-reveal wrapper. Without JS the content is visible; with JS it animates once when in view. */
export function Reveal({ children, delay = 0, className }: { children: ReactNode; delay?: number; className?: string }) {
  return (
    <div className={`reveal ${className ?? ""}`} data-reveal="pending" style={{ ["--d" as string]: `${delay}ms` }} data-reveal-observe="">
      {children}
    </div>
  );
}

export function CtaRow({ primaryHref = "/booking", primaryLabel = "درخواست تعمیر", secondaryHref = "/diagnose", secondaryLabel = "مشکل تلویزیونم رو پیدا کن" }: { primaryHref?: string; primaryLabel?: string; secondaryHref?: string; secondaryLabel?: string }) {
  return (
    <div className="btn-row">
      <Link href={primaryHref} className="btn btn--primary" data-track="cta_click" data-track-label={primaryLabel}>
        {primaryLabel}
        <Icon name="arrow" size={18} />
      </Link>
      <Link href={secondaryHref} className="btn btn--ghost" data-track="cta_click" data-track-label={secondaryLabel}>
        {secondaryLabel}
      </Link>
    </div>
  );
}

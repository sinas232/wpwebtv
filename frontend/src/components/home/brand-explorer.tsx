"use client";

import Link from "next/link";
import { useState } from "react";
import { track } from "@/lib/analytics";
import type { Brand } from "@/lib/content/types";
import { TvFallback } from "@/components/tv/TvStage";

/** Brand chooser: selecting a brand previews its TV and links to its repair page. */
export function BrandExplorer({ brands }: { brands: Brand[] }) {
  const [selected, setSelected] = useState<Brand | null>(brands[0] ?? null);

  if (!brands.length) return null;

  return (
    <div className="two-col" style={{ alignItems: "stretch" }}>
      <ul className="brand-wall" aria-label="انتخاب برند">
        {brands.map((b) => (
          <li key={b.slug}>
            <button
              type="button"
              aria-pressed={selected?.slug === b.slug}
              onClick={() => {
                setSelected(b);
                track("brand_selected", { brand: b.slug });
              }}
              style={{ width: "100%", textAlign: "start", font: "inherit", color: "inherit", cursor: "pointer", minHeight: 112, padding: "var(--s-4)", borderRadius: "var(--r-md)", border: "1px solid var(--line-dark)", background: selected?.slug === b.slug ? "color-mix(in srgb, var(--accent) 14%, var(--ink-800))" : "var(--surface-dark)", display: "flex", flexDirection: "column", justifyContent: "center", gap: 2 }}
            >
              <span className="brand-wall__name">{b.name}</span>
              <span className="brand-wall__latin">{b.latin}</span>
            </button>
          </li>
        ))}
      </ul>

      {selected ? (
        <div className="side-panel" aria-live="polite">
          <div style={{ aspectRatio: "16 / 10", position: "relative", borderRadius: "var(--r-md)", overflow: "hidden", border: "1px solid var(--line-dark)" }}>
            <TvFallback screen="fluid" />
          </div>
          <p style={{ fontSize: "1.3rem", fontWeight: 900, margin: 0 }}>تعمیر تلویزیون {selected.name}</p>
          <p className="muted" style={{ margin: 0 }}>{selected.intro}</p>
          <ul className="check-list">
            {selected.commonIssues.map((i) => (
              <li key={i}>{i}</li>
            ))}
          </ul>
          <div className="btn-row">
            <Link href={`/brands/${selected.slug}`} className="btn btn--primary">
              صفحه‌ی تعمیر {selected.name}
            </Link>
            <Link href={`/booking?brand=${encodeURIComponent(selected.name)}`} className="btn btn--ghost">
              درخواست تعمیر
            </Link>
          </div>
        </div>
      ) : null}
    </div>
  );
}

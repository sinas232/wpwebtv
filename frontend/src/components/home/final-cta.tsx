"use client";

import Link from "next/link";
import { useRef, useState, useEffect } from "react";
import { TvStage } from "@/components/tv/TvStage";
import { useInView } from "@/components/hooks";
import { site } from "@/lib/site";

/** The TV switches on the first time this section enters the viewport. */
export function FinalCta() {
  const ref = useRef<HTMLElement>(null);
  const seen = useInView(ref, { once: true, rootMargin: "0px 0px -20% 0px" });
  const [on, setOn] = useState(false);
  useEffect(() => {
    if (!seen) return;
    const t = window.setTimeout(() => setOn(true), 350);
    return () => window.clearTimeout(t);
  }, [seen]);

  return (
    <section ref={ref} className="section" aria-labelledby="final-title" style={{ borderBlockStart: "1px solid var(--line-dark)" }}>
      <div className="container two-col" style={{ alignItems: "center" }}>
        <div>
          <p className="eyebrow">آماده‌ایم</p>
          <h2 id="final-title" style={{ fontSize: "clamp(2rem, 1.3rem + 2.6vw, 3.4rem)" }}>
            تلویزیونت رو دوباره زنده کنیم.
          </h2>
          <p className="lead">مشکلش رو بگو؛ بقیه‌ش با ما.</p>
          <div className="btn-row" style={{ marginBlockStart: "var(--s-5)" }}>
            <Link href="/booking" className="btn btn--primary" data-track="cta_click" data-track-label="final_booking">
              درخواست تعمیر
            </Link>
            {site.phone ? (
              <a href={`tel:${site.phone}`} className="btn btn--ghost" data-track="cta_click" data-track-label="final_phone">
                تماس فوری
              </a>
            ) : (
              <Link href="/contact" className="btn btn--ghost" data-track="cta_click" data-track-label="final_contact">
                تماس فوری
              </Link>
            )}
          </div>
        </div>
        <div style={{ position: "relative", aspectRatio: "4 / 3" }}>
          <TvStage label={on ? "تلویزیون روشن شده" : "تلویزیون خاموش، با ورود به این بخش روشن می‌شود"} screen={on ? "fluid" : "idle"} highlight={[]} interactive={false} />
        </div>
      </div>
    </section>
  );
}

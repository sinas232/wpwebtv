"use client";

import { useEffect, useRef, useState } from "react";
import { TvStage } from "@/components/tv/TvStage";
import { useReducedMotion } from "@/components/hooks";
import { track } from "@/lib/analytics";
import type { PartId } from "@/lib/diagnosis";

export const PARTS: { id: PartId; label: string; text: string }[] = [
  { id: "display", label: "پنل نمایش", text: "لایه‌ای که تصویر را نمایش می‌دهد. خرابی کامل پنل معمولاً با ترک، خط یا لکه‌ی دائمی دیده می‌شود." },
  { id: "backlight", label: "بک‌لایت (LED)", text: "یکی از رایج‌ترین بخش‌های خراب‌شونده در تلویزیون‌های LED. وقتی نوارهای LED ضعیف یا خاموش شوند، تصویر تاریک یا کم‌نور می‌شود." },
  { id: "mainboard", label: "مین‌برد", text: "مغز تلویزیون؛ پردازش تصویر، صدا و ورودی‌ها روی این برد انجام می‌شود." },
  { id: "powerboard", label: "پاور (برد تغذیه)", text: "ولتاژ مورد نیاز بخش‌های دیگر را تأمین می‌کند. روشن نشدن یا خاموش شدن ناگهانی اغلب به این برد برمی‌گردد." },
  { id: "tcon", label: "T-Con", text: "سیگنال تصویر را به ردیف‌ها و ستون‌های پنل می‌رساند. خطوط و نصف‌شدن تصویر ممکن است از این بخش باشد." },
  { id: "speakers", label: "بلندگوها", text: "صدای تلویزیون از این بخش خارج می‌شود. اگر تصویر هست و صدا نیست، این بخش و مسیر صدا بررسی می‌شوند." },
  { id: "wifi", label: "ماژول Wi-Fi", text: "اتصال بی‌سیم و امکانات Smart TV را فراهم می‌کند. قطع شدن شبکه گاهی از این ماژول است." },
  { id: "hdmi", label: "پورت HDMI", text: "ورودی تصویر از دستگاه‌های دیگر. اگر فقط با جابه‌جایی کابل تصویر قطع می‌شود، پورت یا کابل را بررسی کنید." },
];

export function ExplodedTv() {
  const sectionRef = useRef<HTMLElement>(null);
  const explode = useRef(0);
  const reduced = useReducedMotion();
  const [selected, setSelected] = useState<PartId | null>("backlight");
  const [progressLabel, setProgressLabel] = useState(0);

  useEffect(() => {
    if (reduced || !sectionRef.current) {
      explode.current = reduced ? 1 : 0;
      return;
    }
    let cleanup: (() => void) | undefined;
    let cancelled = false;
    (async () => {
      const gsapMod = await import("gsap");
      const stMod = await import("gsap/ScrollTrigger");
      if (cancelled || !sectionRef.current) return;
      gsapMod.gsap.registerPlugin(stMod.ScrollTrigger);
      const st = stMod.ScrollTrigger.create({
        trigger: sectionRef.current,
        start: "top top",
        end: "bottom bottom",
        scrub: 0.6,
        onUpdate: (self) => {
          explode.current = self.progress;
          setProgressLabel(Math.round(self.progress * 100));
        },
      });
      cleanup = () => st.kill();
    })();
    return () => {
      cancelled = true;
      cleanup?.();
    };
  }, [reduced]);

  const info = PARTS.find((p) => p.id === selected);

  function select(id: PartId) {
    setSelected(id);
    track("exploded_part_selected", { part: id });
  }

  return (
    <section ref={sectionRef} className="exploded" aria-labelledby="exploded-title" style={{ minHeight: reduced ? undefined : "240vh" }}>
      <div className="container" style={{ position: reduced ? "relative" : "sticky", top: "var(--header-h)", paddingBlock: "var(--s-8)" }}>
        <div className="exploded__grid">
          <div className="exploded__stage">
            <p className="exploded__hint" aria-hidden="true">
              {reduced ? "نمای باز شده" : progressLabel < 8 ? "با اسکرول، تلویزیون باز می‌شود" : `باز شدگی ${progressLabel}٪`}
            </p>
            <TvStage
              label="تلویزیون سه‌بعدی که با اسکرول از هم جدا می‌شود"
              screen="fluid"
              highlight={selected ? [selected] : []}
              explode={explode}
              selectedPart={selected}
              onPartSelect={select}
              interactive
            />
          </div>

          <div>
            <p className="eyebrow">تلویزیون از نزدیک</p>
            <h2 id="exploded-title" style={{ fontSize: "clamp(1.6rem, 1.1rem + 1.6vw, 2.4rem)" }}>
              قطعات تلویزیون را ببینید
            </h2>
            <p className="lead" style={{ fontSize: "1rem" }}>
              هر قطعه را انتخاب کنید تا بدانید کار آن چیست و خرابی‌اش چه علائمی دارد.
            </p>
            <ul className="part-list" aria-label="قطعات تلویزیون">
              {PARTS.map((p) => (
                <li key={p.id}>
                  <button type="button" className="part-btn" aria-pressed={selected === p.id} onClick={() => select(p.id)}>
                    {p.label}
                  </button>
                </li>
              ))}
            </ul>
            {info ? (
              <div className="part-info" role="status" key={info.id}>
                <p className="part-info__title">{info.label}</p>
                <p className="part-info__text">{info.text}</p>
              </div>
            ) : null}
          </div>
        </div>
      </div>
    </section>
  );
}

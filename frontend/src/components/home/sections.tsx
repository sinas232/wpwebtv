import Link from "next/link";
import { Complexity, EmptyState, Icon, SectionHead, type IconName } from "@/components/ui";
import { PriceNote } from "@/components/ui";
import { faNum } from "@/lib/format";
import type { City, Problem, Service } from "@/lib/content/types";
import { proof, trustPillars } from "@/lib/site";

const PROBLEM_ICON: Record<string, IconName> = {
  "no-picture": "screen",
  "no-sound": "sound",
  backlight: "dim",
  "tv-wont-turn-on": "power",
  "auto-reboot": "reboot",
  hdmi: "hdmi",
  "smart-tv": "wifi",
};

const SERVICE_ICON: Record<string, IconName> = {
  "tv-repair": "tools",
  "on-site-repair": "pin",
  "backlight-repair": "dim",
  "mainboard-repair": "board",
  "power-board-repair": "power",
  "tcon-repair": "panel",
  "hdmi-repair": "hdmi",
  "smart-tv-repair": "wifi",
  "picture-repair": "screen",
  "sound-repair": "sound",
  "wont-turn-on-repair": "power",
  "water-damage-repair": "shield",
  "parts-replacement": "tools",
  "screen-replacement": "screen",
};

export function ProblemCards({ problems, compact = false }: { problems: Problem[]; compact?: boolean }) {
  return (
    <ul className="grid grid--3" style={{ listStyle: "none", padding: 0, margin: 0 }}>
      {problems.map((p) => (
        <li key={p.slug}>
          <Link href={`/problems/${p.slug}`} className="card" data-track="problem_selected" data-track-label={p.slug} style={{ height: "100%" }}>
            <span className="card__icon" aria-hidden="true">
              <Icon name={PROBLEM_ICON[p.slug] ?? "other"} />
            </span>
            <h3 className="card__title">{p.title}</h3>
            {compact ? null : <p className="problem-card__desc">{p.summary}</p>}
            <div className="card__meta">
              <Complexity level={p.complexity} />
              <span className="tag">راهنمای کامل</span>
            </div>
          </Link>
        </li>
      ))}
    </ul>
  );
}

export function ServiceCards({ services }: { services: Service[] }) {
  return (
    <ul className="grid grid--3" style={{ listStyle: "none", padding: 0, margin: 0 }}>
      {services.map((s) => (
        <li key={s.slug}>
          <Link href={`/services/${s.slug}`} className="card" style={{ height: "100%" }}>
            <span className="card__icon" aria-hidden="true">
              <Icon name={SERVICE_ICON[s.slug] ?? "tools"} />
            </span>
            <h3 className="card__title">{s.title}</h3>
            <p className="problem-card__desc">{s.summary}</p>
            <div className="card__meta">
              <Complexity level={s.complexity} />
              <PriceNote price={s.price} />
            </div>
          </Link>
        </li>
      ))}
    </ul>
  );
}

/**
 * Trust: capability statements are always shown (they describe how the service
 * works). Numbers, certifications and reviews appear only when the business
 * supplies real values in site.ts.
 */
export function TrustBlock() {
  const hasProof = proof.yearsActive !== null || proof.repairsCompleted !== null || proof.certifications.length > 0;
  return (
    <div>
      <ul className="grid grid--3" style={{ listStyle: "none", padding: 0, margin: 0 }}>
        {trustPillars.map((t) => (
          <li key={t.id} className="card">
            <span className="card__icon" aria-hidden="true">
              <Icon name={t.id === "guarantee" ? "shield" : t.id === "quote" ? "calendar" : t.id === "parts" ? "tools" : t.id === "support" ? "phone" : t.id === "onsite" ? "pin" : "check"} />
            </span>
            <h3 className="card__title">{t.title}</h3>
            <p className="problem-card__desc">{t.text}</p>
          </li>
        ))}
      </ul>
      {hasProof ? (
        <dl className="grid grid--3" style={{ marginBlockStart: "var(--s-5)" }}>
          {proof.yearsActive !== null ? (
            <div className="card">
              <dt className="muted">سابقه‌ی فعالیت</dt>
              <dd style={{ margin: 0, fontSize: "1.6rem", fontWeight: 900 }}>{faNum(proof.yearsActive)} سال</dd>
            </div>
          ) : null}
          {proof.repairsCompleted !== null ? (
            <div className="card">
              <dt className="muted">تعمیرهای انجام‌شده</dt>
              <dd style={{ margin: 0, fontSize: "1.6rem", fontWeight: 900 }}>{faNum(proof.repairsCompleted)}</dd>
            </div>
          ) : null}
          {proof.certifications.length ? (
            <div className="card">
              <dt className="muted">گواهینامه‌ها</dt>
              <dd style={{ margin: 0 }}>{proof.certifications.join("، ")}</dd>
            </div>
          ) : null}
        </dl>
      ) : null}
    </div>
  );
}

export function ReviewsBlock() {
  if (!proof.reviews.length) {
    return (
      <EmptyState title="نظرات مشتریان به‌زودی" text="نظرات واقعی مشتریان، پس از ثبت و تأیید، در این بخش نمایش داده می‌شود. تا آن زمان نظری ساخته یا نمایش داده نمی‌شود." />
    );
  }
  return (
    <ul className="grid grid--3" style={{ listStyle: "none", padding: 0, margin: 0 }}>
      {proof.reviews.map((r) => (
        <li key={r.name + r.text} className="card">
          <span className="card__icon" aria-hidden="true">
            <Icon name="quote" />
          </span>
          <p style={{ margin: 0 }}>{r.text}</p>
          <p className="card__meta" style={{ marginBlockStart: "auto" }}>
            <span>{r.name}</span>
            <span>{r.city}</span>
            <span aria-label={`امتیاز ${faNum(r.rating)} از ۵`}>{faNum(r.rating)} / ۵</span>
          </p>
        </li>
      ))}
    </ul>
  );
}

export function CityCards({ cities }: { cities: City[] }) {
  return (
    <ul className="grid grid--2" style={{ listStyle: "none", padding: 0, margin: 0 }}>
      {cities.map((c) => (
        <li key={c.slug}>
          <Link href={`/cities/${c.slug}`} className="card city-card" data-status={c.coverage} style={{ height: "100%" }}>
            <h3 className="card__title">تعمیر تلویزیون در {c.name}</h3>
            <p className="problem-card__desc">{c.intro}</p>
            <div className="card__meta">
              <span className="tag">{c.coverage === "confirmed" ? "پوشش تأییدشده" : "پوشش در انتظار تأیید"}</span>
            </div>
          </Link>
        </li>
      ))}
    </ul>
  );
}

export function SectionTitle(props: { eyebrow?: string; title: string; lead?: string; id?: string; light?: boolean }) {
  return <SectionHead {...props} />;
}

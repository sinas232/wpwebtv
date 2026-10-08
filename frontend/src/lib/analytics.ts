/**
 * Client analytics. Pushes to window.dataLayer (GTM / GA4 read it) and posts
 * whitelisted events to /api/events. Never sends free text or personal data;
 * the server route rejects unknown event names.
 */

export type EventName =
  | "cta_click"
  | "phone_click"
  | "whatsapp_click"
  | "telegram_click"
  | "form_start"
  | "form_step"
  | "diagnosis_start"
  | "diagnosis_symptom"
  | "diagnosis_complete"
  | "brand_selected"
  | "problem_selected"
  | "city_viewed"
  | "exploded_part_selected"
  | "tracking_lookup";

type Props = Record<string, string | number | boolean>;

declare global {
  interface Window {
    dataLayer?: Array<Record<string, unknown>>;
  }
}

export function track(name: EventName, props: Props = {}): void {
  if (typeof window === "undefined") return;
  const path = window.location.pathname;
  window.dataLayer = window.dataLayer ?? [];
  window.dataLayer.push({ event: name, ...props, path });

  // Fire-and-forget. A failed analytics call must never affect the user flow.
  const body = JSON.stringify({ name, props, path });
  try {
    if (navigator.sendBeacon) {
      navigator.sendBeacon("/api/events", new Blob([body], { type: "application/json" }));
    } else {
      void fetch("/api/events", { method: "POST", headers: { "content-type": "application/json" }, body, keepalive: true });
    }
  } catch {
    /* ignore */
  }
}

/** UTM and source capture. Stored for the session so booking keeps the original source. */
export function captureAttribution(search: string): Record<string, string> {
  const params = new URLSearchParams(search);
  const out: Record<string, string> = {};
  for (const key of ["utm_source", "utm_medium", "utm_campaign", "utm_term", "utm_content"]) {
    const v = params.get(key);
    if (v) out[key] = v.slice(0, 100);
  }
  try {
    if (Object.keys(out).length) sessionStorage.setItem("tvd_attr", JSON.stringify(out));
    const stored = sessionStorage.getItem("tvd_attr");
    return stored ? { ...(JSON.parse(stored) as Record<string, string>), ...out } : out;
  } catch {
    return out;
  }
}

export function readAttribution(): Record<string, string> {
  try {
    const stored = sessionStorage.getItem("tvd_attr");
    return stored ? (JSON.parse(stored) as Record<string, string>) : {};
  } catch {
    return {};
  }
}

/**
 * Site-wide configuration. Anything that is a business fact (phone, coverage,
 * guarantee, certifications, prices) is `null` or `needs_review` until the
 * business supplies it. Nothing here is invented: the UI renders a safe
 * fallback when a value is missing.
 */

const env = (key: string): string | null => {
  const value = process.env[key];
  return value && value.trim() !== "" ? value.trim() : null;
};

export const site = {
  /** Working brand name. Replace with the approved name via NEXT_PUBLIC_BRAND_NAME. */
  name: env("NEXT_PUBLIC_BRAND_NAME") ?? "تی‌وی دکتر",
  description:
    "تعمیر تخصصی تلویزیون در محل، با تکنسین متخصص. تشخیص اولیه‌ی آنلاین و درخواست تعمیر در چند قدم.",
  url: (env("NEXT_PUBLIC_SITE_URL") ?? "http://localhost:3000").replace(/\/$/, ""),
  locale: "fa_IR",
  lang: "fa",
  dir: "rtl" as const,
  /** Business contact channels. Null means the channel is not configured and is not shown. */
  phone: env("NEXT_PUBLIC_PHONE"),
  whatsapp: env("NEXT_PUBLIC_WHATSAPP"),
  telegram: env("NEXT_PUBLIC_TELEGRAM"),
  email: env("NEXT_PUBLIC_EMAIL"),
  /** Analytics. GTM is loaded only when an ID is configured. */
  gtmId: env("NEXT_PUBLIC_GTM_ID"),
  gaId: env("NEXT_PUBLIC_GA_ID"),
  /** Optional WordPress headless CMS base, e.g. https://cms.example.ir */
  cmsUrl: env("WP_API_URL"),
  /** Optional CRM / automation webhook that receives every lead as JSON. */
  leadWebhookUrl: env("LEAD_WEBHOOK_URL"),
} as const;

/** Trust statements shown as capabilities. Numeric or verifiable proof is gated below. */
export const trustPillars = [
  { id: "technician", title: "تکنسین متخصص", text: "بررسی و تعمیر توسط تکنسین آموزش‌دیده‌ی تلویزیون." },
  { id: "onsite", title: "تعمیر در محل", text: "در صورت امکان، بررسی در محل شما انجام می‌شود." },
  { id: "quote", title: "اعلام هزینه قبل از تعمیر", text: "هزینه را قبل از شروع تعمیر و با تأیید شما اعلام می‌کنیم." },
  { id: "guarantee", title: "ضمانت تعمیر", text: "شرایط ضمانت هر تعمیر را پیش از ثبت آن برایتان مشخص می‌کنیم." },
  { id: "parts", title: "قطعات باکیفیت", text: "منبع و نوع قطعه در فاکتور مشخص می‌شود." },
  { id: "support", title: "پشتیبانی", text: "برای پیگیری درخواست، کد پیگیری و راه ارتباطی در اختیار شماست." },
] as const;

/**
 * Proof points (years, customer count, certifications, awards, reviews) are
 * rendered only when the business supplies real values. Default: all null.
 */
export const proof = {
  yearsActive: null as number | null,
  repairsCompleted: null as number | null,
  certifications: [] as string[],
  reviews: [] as { name: string; city: string; text: string; rating: number }[],
};

/** Price display policy. Values come from CMS; default is "needs review". */
export type PriceDisplay =
  | { mode: "needs_review" }
  | { mode: "approximate"; label: string; from?: string; to?: string };

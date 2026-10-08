/**
 * Lead model and validation. Pure module (no fs, no network) so it can be
 * unit-tested and shared by the booking UI and the API route.
 *
 * Data contract for CRM / SMS / scheduling integrations lives in `Lead`.
 * Technician-side fields (assignment, visit time, repair report, parts cost,
 * invoice) are declared here as the architecture target; they stay empty
 * until a technician workflow writes them. No values are fabricated.
 */

import { validateHandoff, type DiagnosisHandoff } from "./diagnosis.ts";

export const PROBLEM_OPTIONS = [
  { id: "no_picture", label: "تصویر ندارم" },
  { id: "no_sound", label: "صدا ندارم" },
  { id: "dark_screen", label: "صفحه تاریکه" },
  { id: "no_power", label: "روشن نمی‌شه" },
  { id: "auto_reboot", label: "خاموش و روشن می‌شه" },
  { id: "hdmi", label: "HDMI مشکل داره" },
  { id: "smart_wifi", label: "Smart TV / Wi-Fi مشکل داره" },
  { id: "other", label: "مشکل دیگری دارم" },
] as const;

export const BRAND_OPTIONS = [
  "سامسونگ", "ال‌جی", "سونی", "تی‌سی‌ال", "هایسنس", "ایکس‌ویژن", "جی‌پلاس",
  "اسنوا", "فیلیپس", "پاناسونیک", "سایر برندها",
] as const;

export const SIZE_OPTIONS = ["تا ۳۲ اینچ", "۴۰ تا ۴۳ اینچ", "۵۰ تا ۵۵ اینچ", "۶۵ اینچ و بیشتر", "نمی‌دانم"] as const;

export const TIME_OPTIONS = ["امروز بعدازظهر", "فردا صبح", "فردا بعدازظهر", "روز دیگر (در توضیحات بنویسید)"] as const;

export const MEDIA_KINDS = ["front_photo", "label_photo", "problem_photo", "problem_video"] as const;
export type MediaKind = (typeof MEDIA_KINDS)[number];

export const MEDIA_LIMITS = {
  photoMaxBytes: 8 * 1024 * 1024,
  videoMaxBytes: 50 * 1024 * 1024,
  totalMaxBytes: 80 * 1024 * 1024,
  maxFiles: 6,
  photoTypes: ["image/jpeg", "image/png", "image/webp", "image/heic"],
  videoTypes: ["video/mp4", "video/quicktime", "video/webm"],
} as const;

export type LeadStatus =
  | "received"
  | "reviewing"
  | "technician_assigned"
  | "visit_scheduled"
  | "dispatched"
  | "in_repair"
  | "completed";

export const STATUS_ORDER: LeadStatus[] = [
  "received",
  "reviewing",
  "technician_assigned",
  "visit_scheduled",
  "dispatched",
  "in_repair",
  "completed",
];

export const STATUS_LABEL: Record<LeadStatus, string> = {
  received: "درخواست ثبت شد",
  reviewing: "کارشناس در حال بررسی است",
  technician_assigned: "تکنسین تعیین شد",
  visit_scheduled: "زمان مراجعه مشخص شد",
  dispatched: "تکنسین اعزام شد",
  in_repair: "در حال تعمیر",
  completed: "تعمیر تکمیل شد",
};

export type Utm = {
  source?: string;
  medium?: string;
  campaign?: string;
  term?: string;
  content?: string;
};

export type LeadMedia = {
  kind: MediaKind;
  name: string;
  size: number;
  mime: string;
  storedAs: string | null;
};

/** Full lead record. Fields below `technician` are written only by the technician workflow. */
export type Lead = {
  id: string;
  trackingCode: string;
  createdAt: string;
  status: LeadStatus;
  name: string;
  mobile: string;
  city: string;
  area: string;
  address: string;
  brand: string;
  model: string;
  size: string;
  problem: string;
  description: string;
  standbyLight: "yes" | "no" | "unknown";
  preferredTime: string;
  source: string;
  utm: Utm;
  landingPage: string;
  media: LeadMedia[];
  /** Answers from TV Detective, when the customer came from the diagnostic flow. */
  diagnosis: DiagnosisHandoff | null;
  technician: {
    assignedTo: string | null;
    visitAt: string | null;
  };
  repair: {
    report: string | null;
    parts: { name: string; cost: number | null }[];
    invoiceId: string | null;
  };
};

export type LeadInput = Omit<Lead, "id" | "trackingCode" | "createdAt" | "status" | "media" | "technician" | "repair"> & {
  media: LeadMedia[];
};

export type ValidationResult = { ok: true; value: Omit<LeadInput, "media"> } | { ok: false; errors: Record<string, string> };

/** Normalises Persian and Arabic digits and separators to ASCII. */
export function normalizeDigits(input: string): string {
  return input
    .replace(/[۰-۹]/g, (d) => String(d.charCodeAt(0) - 0x06f0))
    .replace(/[٠-٩]/g, (d) => String(d.charCodeAt(0) - 0x0660))
    .replace(/[٬،]/g, ",")
    .replace(/٫/g, ".");
}

/** Iranian mobile: 09XXXXXXXXX after digit normalisation and separator removal. */
export function normalizeMobile(input: string): string | null {
  const cleaned = normalizeDigits(input).replace(/[\s\-()]/g, "");
  const local = cleaned.startsWith("+98") ? `0${cleaned.slice(3)}` : cleaned.startsWith("98") && cleaned.length === 12 ? `0${cleaned.slice(2)}` : cleaned;
  return /^09\d{9}$/.test(local) ? local : null;
}

const MAX_TEXT = 1000;

function str(v: unknown, max = 200): string {
  return typeof v === "string" ? v.trim().slice(0, max) : "";
}

/** Validates the text fields of a booking. Media is validated separately. */
export function validateLeadFields(raw: Record<string, unknown>): ValidationResult {
  const errors: Record<string, string> = {};
  const name = str(raw.name, 80);
  const mobileRaw = str(raw.mobile, 30);
  const mobile = normalizeMobile(mobileRaw);
  const problem = str(raw.problem, 40);
  const city = str(raw.city, 60);
  const area = str(raw.area, 80);
  const address = str(raw.address, 400);
  const brand = str(raw.brand, 60);
  const model = str(raw.model, 80);
  const size = str(raw.size, 40);
  const description = str(raw.description, MAX_TEXT);
  const preferredTime = str(raw.preferredTime, 80);
  const standbyRaw = str(raw.standbyLight, 10);
  const standbyLight: "yes" | "no" | "unknown" = standbyRaw === "yes" || standbyRaw === "no" ? standbyRaw : "unknown";

  if (!PROBLEM_OPTIONS.some((p) => p.id === problem)) errors.problem = "لطفاً مشکل تلویزیون را انتخاب کنید.";
  if (!BRAND_OPTIONS.includes(brand as (typeof BRAND_OPTIONS)[number])) errors.brand = "لطفاً برند را انتخاب کنید.";
  if (!city) errors.city = "شهر را وارد کنید.";
  if (!area) errors.area = "منطقه را وارد کنید.";
  if (address.length < 10) errors.address = "آدرس را کامل‌تر بنویسید (حداقل ۱۰ نویسه).";
  if (!SIZE_OPTIONS.includes(size as (typeof SIZE_OPTIONS)[number])) errors.size = "اندازه‌ی تلویزیون را انتخاب کنید.";
  if (!preferredTime) errors.preferredTime = "زمان مناسب مراجعه را انتخاب کنید.";
  if (name.length < 2) errors.name = "نام خود را وارد کنید.";
  if (!mobile) errors.mobile = "شماره موبایل معتبر وارد کنید (مثلاً ۰۹۱۲۳۴۵۶۷۸۹).";
  if (problem === "other" && description.length < 10) errors.description = "برای «مشکل دیگر»، توضیح کوتاهی بنویسید.";

  if (Object.keys(errors).length) return { ok: false, errors };
  return {
    ok: true,
    value: {
      name,
      mobile: mobile as string,
      city,
      area,
      address,
      brand,
      model,
      size,
      problem,
      description,
      standbyLight,
      preferredTime,
      source: str(raw.source, 80) || "direct",
      utm: {
        source: str(raw.utm_source, 100) || undefined,
        medium: str(raw.utm_medium, 100) || undefined,
        campaign: str(raw.utm_campaign, 100) || undefined,
        term: str(raw.utm_term, 100) || undefined,
        content: str(raw.utm_content, 100) || undefined,
      },
      landingPage: str(raw.landingPage, 300),
      diagnosis: parseDiagnosis(raw.diagnosis),
    },
  };
}

/** The diagnosis field arrives as a JSON string. Invalid input is dropped, never trusted. */
function parseDiagnosis(v: unknown): DiagnosisHandoff | null {
  if (typeof v !== "string" || v.length === 0 || v.length > 1000) return null;
  try {
    return validateHandoff(JSON.parse(v));
  } catch {
    return null;
  }
}

export type FileMeta = { kind: string; name: string; size: number; mime: string };

/** Validates media metadata before any bytes are stored. */
export function validateMedia(files: FileMeta[]): { ok: true } | { ok: false; error: string } {
  if (files.length > MEDIA_LIMITS.maxFiles) return { ok: false, error: `حداکثر ${MEDIA_LIMITS.maxFiles} فایل مجاز است.` };
  let total = 0;
  for (const f of files) {
    if (!MEDIA_KINDS.includes(f.kind as MediaKind)) return { ok: false, error: "نوع فایل نامعتبر است." };
    total += f.size;
    if (f.kind === "problem_video") {
      if (!(MEDIA_LIMITS.videoTypes as readonly string[]).includes(f.mime)) return { ok: false, error: "ویدئو باید MP4، MOV یا WebM باشد." };
      if (f.size > MEDIA_LIMITS.videoMaxBytes) return { ok: false, error: "حجم ویدئو بیشتر از ۵۰ مگابایت است." };
    } else {
      if (!(MEDIA_LIMITS.photoTypes as readonly string[]).includes(f.mime)) return { ok: false, error: "عکس باید JPG، PNG، WebP یا HEIC باشد." };
      if (f.size > MEDIA_LIMITS.photoMaxBytes) return { ok: false, error: "حجم هر عکس بیشتر از ۸ مگابایت است." };
    }
  }
  if (total > MEDIA_LIMITS.totalMaxBytes) return { ok: false, error: "مجموع حجم فایل‌ها بیشتر از ۸۰ مگابایت است." };
  return { ok: true };
}

/** Customer-facing tracking code. No ambiguous characters (0/O, 1/I). */
const CODE_ALPHABET = "ABCDEFGHJKLMNPQRSTUVWXYZ23456789";
export function makeTrackingCode(random: () => number = Math.random): string {
  let s = "";
  for (let i = 0; i < 6; i++) s += CODE_ALPHABET[Math.floor(random() * CODE_ALPHABET.length)];
  return `TV-${s}`;
}

export function isTrackingCode(v: string): boolean {
  return /^TV-[A-Z2-9]{6}$/.test(v.trim().toUpperCase());
}

/** What a customer may see on the public tracking page. No address, phone or media. */
export function publicTrackingView(lead: Lead) {
  const index = STATUS_ORDER.indexOf(lead.status);
  return {
    trackingCode: lead.trackingCode,
    createdAt: lead.createdAt,
    steps: STATUS_ORDER.map((status, i) => ({
      status,
      label: STATUS_LABEL[status],
      done: i <= index,
      current: i === index,
    })),
    visitAt: lead.technician.visitAt,
  };
}

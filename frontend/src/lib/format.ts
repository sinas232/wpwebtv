const FA_DIGITS = "۰۱۲۳۴۵۶۷۸۹";

/** Converts ASCII digits to Persian digits. */
export function faNum(value: number | string): string {
  return String(value).replace(/[0-9]/g, (d) => FA_DIGITS[Number(d)]);
}

const fmt = new Intl.DateTimeFormat("fa-IR-u-ca-persian", { year: "numeric", month: "long", day: "numeric" });

/** Formats an ISO date in the Persian (Jalali) calendar. Safe on server and client. */
export function faDate(iso: string): string {
  const d = new Date(iso);
  if (Number.isNaN(d.getTime())) return "";
  return fmt.format(d);
}

export function faDateTime(iso: string): string {
  const d = new Date(iso);
  if (Number.isNaN(d.getTime())) return "";
  return new Intl.DateTimeFormat("fa-IR-u-ca-persian", {
    year: "numeric",
    month: "long",
    day: "numeric",
    hour: "2-digit",
    minute: "2-digit",
    timeZone: "Asia/Tehran",
  }).format(d);
}

export const complexityLabel = {
  low: "پیچیدگی کم",
  medium: "پیچیدگی متوسط",
  high: "پیچیدگی بالا",
} as const;

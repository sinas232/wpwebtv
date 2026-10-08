/**
 * TV Detective logic. Pure functions, no framework or DOM dependencies.
 *
 * Every symptom has its own question set. Every outcome is a *likely* cause,
 * never a confirmed one: the UI must always show DISCLAIMER next to a result,
 * and the wording uses "احتمال" / "ممکن است".
 *
 * Flow per symptom:
 *   Problem → Questions → Visual reaction → Likely cause → Safe advice → CTA
 */

export const DISCLAIMER = "تشخیص قطعی نیاز به بررسی تکنسین دارد.";

export type SymptomId =
  | "no_picture"
  | "no_sound"
  | "dark_screen"
  | "no_power"
  | "auto_reboot"
  | "hdmi"
  | "smart_wifi"
  | "other";

/** Which 3D part the TV highlights. */
export type PartId =
  | "display"
  | "backlight"
  | "mainboard"
  | "powerboard"
  | "tcon"
  | "speakers"
  | "wifi"
  | "hdmi";

/** Visual state the 3D TV enters. */
export type ScreenMode = "fluid" | "black" | "dim" | "boot" | "nosignal" | "wifi" | "idle";

export type SymptomVisual = {
  id: SymptomId;
  label: string;
  icon: string;
  screen: ScreenMode;
  highlight: PartId[];
  /** Short, hedged explanation shown under the TV. */
  note: string;
};

export const SYMPTOMS: SymptomVisual[] = [
  { id: "no_picture", label: "تصویر ندارم", icon: "📺", screen: "black", highlight: ["display", "tcon"], note: "صدا باشد و تصویر نباشد، معمولاً از بک‌لایت، پنل یا T-Con است." },
  { id: "no_sound", label: "صدا ندارم", icon: "🔇", screen: "fluid", highlight: ["speakers", "mainboard"], note: "صدا ممکن است از بلندگو، آمپلی‌فایر یا مسیر صدا قطع شده باشد." },
  { id: "dark_screen", label: "صفحه تاریکه", icon: "💡", screen: "dim", highlight: ["backlight", "powerboard"], note: "تصویر کم‌نور احتمالاً از بک‌لایت یا منبع تغذیه‌ی آن است." },
  { id: "no_power", label: "روشن نمی‌شم", icon: "⚡", screen: "idle", highlight: ["powerboard"], note: "روشن نشدن معمولاً به برد تغذیه یا کابل برق برمی‌گردد." },
  { id: "auto_reboot", label: "خودم خاموش روشن می‌شم", icon: "🔄", screen: "boot", highlight: ["powerboard", "mainboard"], note: "ریست خودکار اغلب از برد تغذیه یا حفاظت دستگاه است." },
  { id: "hdmi", label: "HDMI مشکل داره", icon: "🔌", screen: "nosignal", highlight: ["hdmi"], note: "خطای HDMI اغلب از کابل یا خود پورت است و قابل امتحان با کابل دیگر است." },
  { id: "smart_wifi", label: "Smart TV / Wi-Fi", icon: "📶", screen: "wifi", highlight: ["wifi", "mainboard"], note: "بیشتر مشکلات Smart TV نرم‌افزاری‌اند؛ اگر ادامه داشت، ماژول Wi-Fi بررسی می‌شود." },
  { id: "other", label: "یه مشکل دیگه دارم", icon: "🤔", screen: "idle", highlight: [], note: "برای مشکل خاص‌تر، توضیح و عکس یا ویدئو بفرستید تا تکنسین بررسی کند." },
];

export function getSymptom(id: string): SymptomVisual | undefined {
  return SYMPTOMS.find((s) => s.id === id);
}

/** Problem → 3D state for the TV Detective intro. */
export function visualForSymptom(id: string): SymptomVisual {
  return getSymptom(id) ?? SYMPTOMS[SYMPTOMS.length - 1];
}

/* ------------------------------------------------------------------ */
/* Causes                                                              */
/* ------------------------------------------------------------------ */

export const CAUSE_IDS = [
  "power_supply",
  "backlight",
  "panel_or_tcon",
  "sound_path",
  "speakers",
  "mainboard",
  "mainboard_or_power",
  "hdmi_cable",
  "hdmi_port",
  "hdmi_board",
  "wifi_module",
  "software",
  "intermittent",
  "undetermined",
] as const;
export type CauseId = (typeof CAUSE_IDS)[number];

export type Complexity = "low" | "medium" | "high";

type CauseInfo = {
  headline: string;
  explanation: string;
  part: PartId | null;
  complexity: Complexity;
  visual: { screen: ScreenMode; highlight: PartId[] };
};

/** Hedged wording only. Keep every headline starting with "احتمال". */
export const CAUSES: Record<CauseId, CauseInfo> = {
  power_supply: {
    headline: "احتمال مشکل: برد تغذیه یا کابل برق",
    explanation: "چراغ استندبای روشن نمی‌شود. این علامت معمولاً به برد تغذیه یا کابل و پریز برق برمی‌گردد.",
    part: "powerboard",
    complexity: "medium",
    visual: { screen: "idle", highlight: ["powerboard"] },
  },
  backlight: {
    headline: "احتمال مشکل: بک‌لایت",
    explanation: "تلویزیون روشن است و صدا دارد، اما تصویر کم‌نور یا تقریباً سیاه است. این علامت‌ها احتمالاً به بک‌لایت (نور پشت پنل) مربوط‌اند.",
    part: "backlight",
    complexity: "high",
    visual: { screen: "dim", highlight: ["backlight"] },
  },
  panel_or_tcon: {
    headline: "احتمال مشکل: پنل یا برد T-Con",
    explanation: "صدا دارد و صفحه سیاه است، حتی وقتی با نور قوی نگاه می‌کنید. علت احتمالی پنل یا T-Con است؛ بدون بررسی نمی‌توان یکی را قطعی کرد.",
    part: "tcon",
    complexity: "high",
    visual: { screen: "black", highlight: ["display", "tcon"] },
  },
  sound_path: {
    headline: "احتمال مشکل: مسیر صدا روی مین‌برد",
    explanation: "تصویر دارد اما صدا ندارد و خروجی صدای جانبی هم صدایی نمی‌دهد. ممکن است آمپلی‌فایر یا مسیر صدا روی مین‌برد مشکل داشته باشد.",
    part: "mainboard",
    complexity: "medium",
    visual: { screen: "fluid", highlight: ["mainboard"] },
  },
  speakers: {
    headline: "احتمال مشکل: بلندگو یا سیم‌کشی آن",
    explanation: "خروجی صدای جانبی صدا دارد اما بلندگوهای داخلی ساکت‌اند. ممکن است بلندگو یا اتصال آن مشکل داشته باشد.",
    part: "speakers",
    complexity: "low",
    visual: { screen: "fluid", highlight: ["speakers"] },
  },
  mainboard: {
    headline: "احتمال مشکل: مین‌برد",
    explanation: "نه تصویر و نه صدا دیده نمی‌شود. مین‌برد یا بخشی از مسیر پردازش تصویر و صدا محتمل‌ترین علت است.",
    part: "mainboard",
    complexity: "high",
    visual: { screen: "black", highlight: ["mainboard"] },
  },
  mainboard_or_power: {
    headline: "احتمال مشکل: مین‌برد یا برد تغذیه",
    explanation: "چراغ استندبای روشن است اما دستگاه روشن نمی‌شود. علت احتمالی مین‌برد یا برد تغذیه است؛ کنترل از راه دور هم باید امتحان شود.",
    part: "mainboard",
    complexity: "medium",
    visual: { screen: "idle", highlight: ["mainboard", "powerboard"] },
  },
  hdmi_cable: {
    headline: "احتمال مشکل: کابل HDMI",
    explanation: "کابل HDMI محتمل‌ترین علت است. اول با یک کابل سالم دیگر امتحان کنید؛ اگر مشکل رفع شد، کابل قبلی علت بوده است.",
    part: "hdmi",
    complexity: "low",
    visual: { screen: "nosignal", highlight: ["hdmi"] },
  },
  hdmi_port: {
    headline: "احتمال مشکل: پورت HDMI",
    explanation: "کابل دیگر هم مشکل را نشان می‌دهد، اما روی ورودی دیگر تلویزیون کار می‌کند. احتمالاً پورت HDMI آسیب دیده است.",
    part: "hdmi",
    complexity: "medium",
    visual: { screen: "nosignal", highlight: ["hdmi"] },
  },
  hdmi_board: {
    headline: "احتمال مشکل: مسیر HDMI روی برد",
    explanation: "کابل و ورودی‌های دیگر هر دو مشکل دارند. احتمالاً مسیر ورودی تصویر روی برد اصلی است.",
    part: "hdmi",
    complexity: "high",
    visual: { screen: "nosignal", highlight: ["hdmi", "mainboard"] },
  },
  wifi_module: {
    headline: "احتمال مشکل: ماژول Wi-Fi",
    explanation: "تلویزیون شبکه‌ها را پیدا نمی‌کند. اگر تنظیمات و روتر درست است، ماژول Wi-Fi یا آنتن آن محتمل است.",
    part: "wifi",
    complexity: "medium",
    visual: { screen: "wifi", highlight: ["wifi"] },
  },
  software: {
    headline: "احتمال مشکل: نرم‌افزار یا اپلیکیشن",
    explanation: "مشکل فقط در اپلیکیشن‌ها یا منوهای نرم‌افزاری دیده می‌شود. معمولاً با به‌روزرسانی یا بازنشانی تنظیمات شبکه برطرف می‌شود.",
    part: null,
    complexity: "low",
    visual: { screen: "wifi", highlight: [] },
  },
  intermittent: {
    headline: "احتمال مشکل: خرابی متناوب",
    explanation: "علائم گاهی رخ می‌دهد و الگوی ثابتی ندارد. زمان دقیق رخ دادن و عکس یا ویدئو به تکنسین کمک زیادی می‌کند.",
    part: "mainboard",
    complexity: "medium",
    visual: { screen: "fluid", highlight: ["mainboard"] },
  },
  undetermined: {
    headline: "سرنخ کافی برای تعیین قطعه نداریم",
    explanation: "پاسخ‌های شما برای تعیین قطعه کافی نیست. با توضیح بیشتر و عکس یا ویدئو، تکنسین بررسی می‌کند.",
    part: null,
    complexity: "medium",
    visual: { screen: "fluid", highlight: [] },
  },
};

/* ------------------------------------------------------------------ */
/* Per-symptom questions and decision rules                            */
/* ------------------------------------------------------------------ */

export type Option = { value: string; label: string };
export type Question = { id: string; question: string; help: string; options: Option[] };

export type Outcome = {
  cause: CauseId;
  /** Safe, non-invasive steps. Never instructs opening the device. */
  advice: string[];
  /** Escalation banner, for example when smoke or burning smell is reported. */
  urgent?: string;
};

type SymptomFlow = {
  questions: Question[];
  decide: (a: Record<string, string>) => Outcome;
};

const YES_NO: Option[] = [
  { value: "yes", label: "بله" },
  { value: "no", label: "نه" },
];

const SAFE_BASE = [
  "تلویزیون را از برق بکشید و هرگز پشت پنل یا برد را باز نکنید؛ ولتاژ داخلی خطرناک است.",
  "شماره مدل را از برچسب پشت دستگاه یادداشت کنید تا تکنسین سریع‌تر بررسی کند.",
];

const SMOKE_URGENT = "اگر دود، بوی سوختگی یا جرقه دیدید، فوراً تلویزیون را از برق جدا کنید و تا بررسی تکنسین دوباره روشنش نکنید.";

export const SYMPTOM_FLOWS: Record<Exclude<SymptomId, "other">, SymptomFlow> = {
  no_picture: {
    questions: [
      { id: "sound", question: "وقتی تلویزیون روشن است، صدا می‌شنوی؟", help: "صدای منو، برنامه یا هر صدای دیگری از دستگاه.", options: YES_NO },
      { id: "faint", question: "با نور قوی به صفحه نگاه کن. تصویر کم‌رنگ دیده می‌شود؟", help: "در اتاق روشن یا با چراغ قوه، کمی به صفحه نزدیک شو.", options: YES_NO },
    ],
    decide: (a) => {
      if (a.sound === "no") return { cause: "mainboard", advice: [...SAFE_BASE, "کابل برق و پریز را با وسیله‌ی دیگری امتحان کنید."] };
      if (a.faint === "yes") return { cause: "backlight", advice: [...SAFE_BASE, "اگر منو را با نور قوی می‌بینید، احتمال بک‌لایت بیشتر است."] };
      return { cause: "panel_or_tcon", advice: [...SAFE_BASE, "اگر تصویر کاملاً سیاه است، بررسی پنل یا T-Con لازم است."] };
    },
  },

  no_sound: {
    questions: [
      { id: "picture", question: "تصویر دارد؟", help: "اگر تصویر و منو دیده می‌شود، «بله» را بزن.", options: YES_NO },
      { id: "external", question: "اگر به سیستم صوتی یا هدفون وصل است، صدا از آن می‌آید؟", help: "خروجی صدای جانبی یا بلوتوث. اگر وصل نیست، «نه» را بزن.", options: YES_NO },
    ],
    decide: (a) => {
      if (a.picture === "no") return { cause: "mainboard", advice: [...SAFE_BASE, "صدای تلویزیون بدون تصویر هم قطع است؛ مشکل از مین‌برد احتمال بیشتری دارد."] };
      if (a.external === "yes") return { cause: "speakers", advice: [...SAFE_BASE, "بلندگوهای داخلی و سیم‌کشی آن‌ها را بررسی کنید؛ صدای ترمز یا سوت هنگام روشن شدن را یادداشت کنید."] };
      return { cause: "sound_path", advice: [...SAFE_BASE, "تنظیمات صدا را بررسی کنید: حالت «بی‌صدا» و خروجی صدا را یک بار امتحان کنید."] };
    },
  },

  dark_screen: {
    questions: [
      { id: "standby", question: "چراغ استندبای روشن می‌شود؟", help: "چراغ کوچک روی قاب یا زیر صفحه.", options: YES_NO },
      { id: "faint", question: "با نور قوی، منو یا تصویر کم‌رنگ دیده می‌شود؟", help: "کمی به صفحه نزدیک شو و زاویه را عوض کن.", options: YES_NO },
    ],
    decide: (a) => {
      if (a.standby === "no") return { cause: "power_supply", advice: [...SAFE_BASE, "کابل برق و پریز را با وسیله‌ی دیگری امتحان کنید."] };
      if (a.faint === "yes") return { cause: "backlight", advice: [...SAFE_BASE, "اگر منو کم‌رنگ دیده می‌شود، احتمال بک‌لایت بالاست؛ پنل را لمس نکنید."] };
      return { cause: "panel_or_tcon", advice: [...SAFE_BASE, "صفحه کاملاً سیاه با صدای سالم، بررسی پنل یا T-Con را لازم می‌کند."] };
    },
  },

  no_power: {
    questions: [
      { id: "standby", question: "با دکمه‌ی روشن یا ریموت، چراغ استندبای روشن می‌شود؟", help: "چراغ کوچک زیر صفحه یا روی قاب.", options: YES_NO },
      { id: "outlet", question: "کابل برق را به پریز دیگری یا با وسیله‌ی دیگری امتحان کرده‌ای؟", help: "اگر امتحان نکرده‌ای، «نه» را بزن.", options: YES_NO },
    ],
    decide: (a) => {
      if (a.standby === "yes") return { cause: "mainboard_or_power", advice: [...SAFE_BASE, "ریموت را با باتری تازه امتحان کنید و دکمه‌ی روشن روی قاب را بزنید."] };
      return { cause: "power_supply", advice: [...SAFE_BASE, a.outlet === "yes" ? "پریز دیگر هم مشکل دارد؛ برق ساختمان یا فیوز را بررسی کنید." : "کابل برق و پریز را با وسیله‌ی دیگری امتحان کنید."] };
    },
  },

  auto_reboot: {
    questions: [
      { id: "standby", question: "موقع خاموش و روشن شدن، چراغ استندبای روشن می‌ماند؟", help: "اگر چراغ کاملاً خاموش می‌شود، «نه» را بزن.", options: YES_NO },
      { id: "smell", question: "موقع گرم شدن، بوی سوختگی، دود یا جرقه دیده‌ای؟", help: "اگر هیچ‌کدام نبود، «نه» را بزن.", options: YES_NO },
    ],
    decide: (a) => {
      if (a.smell === "yes") {
        return { cause: "power_supply", urgent: SMOKE_URGENT, advice: [...SAFE_BASE, "تا بررسی تکنسین از دستگاه استفاده نکنید."] };
      }
      if (a.standby === "yes") return { cause: "mainboard", advice: [...SAFE_BASE, "قبل از بررسی، تلویزیون را چند دقیقه بدون برق نگه دارید و دوباره امتحان کنید."] };
      return { cause: "power_supply", advice: [...SAFE_BASE, "کابل برق را محکم کنید و از پریز مستقیم بدون افزایش استفاده کنید."] };
    },
  },

  hdmi: {
    questions: [
      { id: "cable", question: "با یک کابل HDMI دیگر امتحان کرده‌ای؟", help: "اگر کابل دیگری نداری، «نه» را بزن.", options: YES_NO },
      { id: "port", question: "کابل فعلی را به ورودی HDMI دیگر تلویزیون وصل کرده‌ای؟", help: "اگر امتحان نکرده‌ای، «نه» را بزن.", options: YES_NO },
    ],
    decide: (a) => {
      if (a.cable !== "yes") return { cause: "hdmi_cable", advice: ["اول با یک کابل HDMI دیگر امتحان کنید؛ کابل خراب شایع‌ترین علت است.", ...SAFE_BASE] };
      if (a.port === "yes") return { cause: "hdmi_board", advice: [...SAFE_BASE, "منبع تصویر را هم با یک تلویزیون دیگر امتحان کنید تا مشخص شود مشکل از منبع است یا تلویزیون."] };
      return { cause: "hdmi_port", advice: [...SAFE_BASE, "ورودی HDMI دیگر را امتحان نکرده‌اید؛ اگر آن هم کار نکرد، مشکل احتمالاً از مسیر ورودی روی برد است."] };
    },
  },

  smart_wifi: {
    questions: [
      { id: "scope", question: "مشکل فقط در اپلیکیشن‌ها یا منوی Smart است، یا کل تلویزیون؟", help: "اگر تلویزیون عادی کار می‌کند و فقط اپ‌ها مشکل دارند، «فقط اپ‌ها» را بزن.", options: [{ value: "apps", label: "فقط اپ‌ها" }, { value: "system", label: "کل تلویزیون" }] },
      { id: "networks", question: "تلویزیون لیست شبکه‌های Wi-Fi را نشان می‌دهد؟", help: "در بخش تنظیمات شبکه. اگر اصلاً شبکه‌ای نمی‌بیند، «نه» را بزن.", options: YES_NO },
    ],
    decide: (a) => {
      if (a.scope === "apps") return { cause: "software", advice: ["تلویزیون و اپلیکیشن‌ها را به‌روز کنید.", "تنظیمات شبکه را بازنشانی کنید و دوباره به Wi-Fi وصل شوید.", "بازنشانی کارخانه‌ای را فقط در آخر و پس از پشتیبان‌گیری از تنظیمات انجام دهید."] };
      if (a.networks === "no") return { cause: "wifi_module", advice: ["روتر را یک بار راه‌اندازی مجدد کنید.", "اگر تلویزیون هیچ شبکه‌ای را نمی‌بیند، ماژول Wi-Fi احتمالاً مشکل دارد."] };
      return { cause: "software", advice: ["تنظیمات شبکه را بازنشانی کنید و رمز Wi-Fi را دوباره وارد کنید.", "اگر ادامه داشت، بررسی ماژول یا برد اصلی لازم است."] };
    },
  },
};

export type FlowAnswers = Record<string, string>;

export function questionsFor(symptom: SymptomId): Question[] {
  if (symptom === "other") return [];
  return SYMPTOM_FLOWS[symptom].questions;
}

/** Returns the outcome for a symptom once all its questions are answered. */
export function runDiagnosis(symptom: SymptomId, answers: FlowAnswers): Outcome | null {
  if (symptom === "other") return { cause: "undetermined", advice: [...SAFE_BASE, "توضیح مشکل و عکس یا ویدئو به تکنسین کمک می‌کند."] };
  const flow = SYMPTOM_FLOWS[symptom];
  const complete = flow.questions.every((q) => q.options.some((o) => o.value === answers[q.id]));
  if (!complete) return null;
  return flow.decide(answers);
}

/* ------------------------------------------------------------------ */
/* Handoff to booking                                                  */
/* ------------------------------------------------------------------ */

export type DiagnosisHandoff = {
  symptom: SymptomId;
  cause: CauseId;
  answers: FlowAnswers;
};

function toBase64Url(text: string): string {
  const bytes = new TextEncoder().encode(text);
  let bin = "";
  bytes.forEach((b) => (bin += String.fromCharCode(b)));
  return btoa(bin).replace(/\+/g, "-").replace(/\//g, "_").replace(/=+$/, "");
}

function fromBase64Url(raw: string): string {
  const b64 = raw.replace(/-/g, "+").replace(/_/g, "/").padEnd(Math.ceil(raw.length / 4) * 4, "=");
  const bin = atob(b64);
  const bytes = Uint8Array.from(bin, (c) => c.charCodeAt(0));
  return new TextDecoder("utf-8", { fatal: true }).decode(bytes);
}

/** Base64url encodes the handoff so it survives URLs without raw JSON in them. Works in browsers and Node. */
export function encodeHandoff(h: DiagnosisHandoff): string {
  return toBase64Url(JSON.stringify({ s: h.symptom, c: h.cause, a: h.answers }));
}

/** Validates an untrusted handoff object against the whitelists. Returns null for anything unexpected. */
export function validateHandoff(data: unknown): DiagnosisHandoff | null {
  if (!data || typeof data !== "object") return null;
  const d = data as { symptom?: unknown; cause?: unknown; answers?: unknown };
  const symptom = SYMPTOMS.find((s) => s.id === d.symptom)?.id;
  const cause = CAUSE_IDS.find((c) => c === d.cause);
  if (!symptom || !cause || !d.answers || typeof d.answers !== "object") return null;
  const answers: FlowAnswers = {};
  const allowedQuestionIds = new Set(questionsFor(symptom).map((q) => q.id));
  for (const [k, v] of Object.entries(d.answers as Record<string, unknown>)) {
    if (!allowedQuestionIds.has(k) || typeof v !== "string") return null;
    const allowed = questionsFor(symptom).find((q) => q.id === k)?.options.some((o) => o.value === v);
    if (!allowed) return null;
    answers[k] = v;
  }
  return { symptom, cause, answers };
}

/** Decodes the `diag` URL parameter produced by encodeHandoff. */
export function decodeHandoff(raw: string | null | undefined): DiagnosisHandoff | null {
  if (!raw || raw.length > 600) return null;
  let data: unknown;
  try {
    data = JSON.parse(fromBase64Url(raw));
  } catch {
    return null;
  }
  const d = (data ?? {}) as { s?: unknown; c?: unknown; a?: unknown };
  return validateHandoff({ symptom: d.s, cause: d.c, answers: d.a });
}

/** Short Persian summary shown on the booking page and stored with the lead. */
export function handoffSummary(h: DiagnosisHandoff): string {
  return `${CAUSES[h.cause].headline} (${SYMPTOMS.find((s) => s.id === h.symptom)?.label ?? h.symptom})`;
}

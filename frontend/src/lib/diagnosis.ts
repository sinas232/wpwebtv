/**
 * TV Detective logic. Pure functions, no framework or DOM dependencies.
 *
 * Every output is a *likely* cause, never a confirmed one. The UI must always
 * show DISCLAIMER next to a result. Wording uses "احتمالاً / ممکن است".
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

/** Which 3D part the TV highlights for a symptom. */
export type PartId =
  | "display"
  | "backlight"
  | "mainboard"
  | "powerboard"
  | "tcon"
  | "speakers"
  | "wifi"
  | "hdmi";

/** Visual state the 3D TV enters for a symptom. */
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

export type Answer = "yes" | "no";
export type PictureAnswer = "black" | "dim" | "normal";

export type FlowAnswers = {
  standby?: Answer;
  sound?: Answer;
  picture?: PictureAnswer;
  persistent?: Answer;
};

export type Candidate = "powerboard" | "backlight" | "panel_or_tcon" | "sound_path" | "mainboard_or_intermittent" | "undetermined";

export type FlowResult = {
  candidate: Candidate;
  partLabel: string;
  part: PartId | null;
  headline: string;
  explanation: string;
  complexity: "low" | "medium" | "high";
  /** Whether a technician visit is recommended. Always true for flow results. */
  technicianRecommended: true;
};

const CANDIDATES: Record<Candidate, Omit<FlowResult, "candidate" | "technicianRecommended">> = {
  powerboard: {
    partLabel: "برد تغذیه (پاور)",
    part: "powerboard",
    headline: "احتمال مشکل: برد تغذیه",
    explanation: "چراغ استندبای روشن نمی‌شود؛ این معمولاً به برد تغذیه یا کابل برق برمی‌گردد. این مشکل معمولاً قابل بررسی و تعمیر است.",
    complexity: "medium",
  },
  backlight: {
    partLabel: "بک‌لایت",
    part: "backlight",
    headline: "احتمال مشکل: Backlight",
    explanation: "تلویزیون روشن است، صدا دارد و تصویر کم‌نور است. این علامت‌ها احتمالاً به بک‌لایت (نور پشت پنل) مربوط‌اند. این مشکل معمولاً قابل تعمیره.",
    complexity: "high",
  },
  panel_or_tcon: {
    partLabel: "پنل یا برد T-Con",
    part: "tcon",
    headline: "احتمال مشکل: پنل یا T-Con",
    explanation: "صدا دارد و صفحه کاملاً سیاه است. علت احتمالی پنل یا برد T-Con است؛ بدون بررسی نمی‌توان یکی را قطعی کرد.",
    complexity: "high",
  },
  sound_path: {
    partLabel: "مسیر صدا",
    part: "speakers",
    headline: "احتمال مشکل: مسیر صدا",
    explanation: "تلویزیون روشن است اما صدا ندارد. ممکن است بلندگو، آمپلی‌فایر یا مسیر صدا روی مین‌برد مشکل داشته باشد.",
    complexity: "low",
  },
  mainboard_or_intermittent: {
    partLabel: "مین‌برد یا خرابی متناوب",
    part: "mainboard",
    headline: "احتمال مشکل: مین‌برد یا خرابی متناوب",
    explanation: "علائم به‌صورت متناوب یا نامشخص‌اند. زمان دقیق رخ دادن مشکل برای تکنسین مفید است.",
    complexity: "medium",
  },
  undetermined: {
    partLabel: "نامشخص",
    part: null,
    headline: "سرنخ کافی برای تعیین قطعه نداریم",
    explanation: "پاسخ‌های شما برای تعیین قطعه کافی نیست. با توضیح بیشتر و عکس یا ویدئو، تکنسین بررسی می‌کند.",
    complexity: "medium",
  },
};

/** Maps the answers to the most likely cause. Order of checks matters. */
export function evaluateFlow(a: FlowAnswers): FlowResult {
  let candidate: Candidate = "undetermined";
  if (a.standby === "no") {
    candidate = "powerboard";
  } else if (a.sound === "no") {
    candidate = "sound_path";
  } else if (a.picture === "dim") {
    candidate = "backlight";
  } else if (a.picture === "black") {
    candidate = "panel_or_tcon";
  } else if (a.picture === "normal") {
    candidate = "mainboard_or_intermittent";
  }
  return { candidate, ...CANDIDATES[candidate], technicianRecommended: true };
}

export const FLOW_STEPS = [
  {
    id: "standby" as const,
    question: "وقتی تلویزیون رو روشن می‌کنی، چراغ Standby روشن میشه؟",
    help: "چراغ کوچک روی قاب تلویزیون یا زیر صفحه.",
    options: [{ value: "yes", label: "بله" }, { value: "no", label: "نه" }],
  },
  {
    id: "sound" as const,
    question: "صدای تلویزیون رو می‌شنوی؟",
    help: "صدای منو، برنامه یا هر صدای دیگری از دستگاه.",
    options: [{ value: "yes", label: "بله" }, { value: "no", label: "نه" }],
  },
  {
    id: "picture" as const,
    question: "صفحه کاملاً سیاهه یا تصویر خیلی کم‌رنگه؟",
    help: "اگر تصویر دارید، با یک چراغ قوی هم امتحان کنید.",
    options: [
      { value: "black", label: "کاملاً سیاه" },
      { value: "dim", label: "کم‌رنگ" },
      { value: "normal", label: "تصویر عادی است" },
    ],
  },
  {
    id: "persistent" as const,
    question: "آیا مشکل دائمی است؟",
    help: "اگر گاهی رخ می‌دهد، «نه» را بزنید.",
    options: [{ value: "yes", label: "دائمی است" }, { value: "no", label: "گاهی" }],
  },
];

/** Problem → 3D state for the TV Detective intro (kept separate from the flow). */
export function visualForSymptom(id: string): SymptomVisual {
  return getSymptom(id) ?? SYMPTOMS[SYMPTOMS.length - 1];
}

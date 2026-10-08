"use client";

import Link from "next/link";
import { useEffect, useId, useMemo, useRef, useState, type FormEvent } from "react";
import { Icon } from "@/components/ui";
import { captureAttribution, readAttribution, track } from "@/lib/analytics";
import { postLead } from "@/lib/client-api";
import { decodeHandoff, handoffSummary, type DiagnosisHandoff } from "@/lib/diagnosis";
import {
  BRAND_OPTIONS,
  MEDIA_LIMITS,
  PROBLEM_OPTIONS,
  SIZE_OPTIONS,
  TIME_OPTIONS,
  validateMedia,
} from "@/lib/leads";

type Media = { id: number; kind: "front_photo" | "label_photo" | "problem_photo" | "problem_video"; file: File; url: string };

type Form = {
  problem: string;
  standbyLight: "yes" | "no" | "unknown";
  brand: string;
  model: string;
  size: string;
  description: string;
  city: string;
  area: string;
  address: string;
  preferredTime: string;
  name: string;
  mobile: string;
};

const EMPTY: Form = {
  problem: "",
  standbyLight: "unknown",
  brand: "",
  model: "",
  size: "",
  description: "",
  city: "",
  area: "",
  address: "",
  preferredTime: "",
  name: "",
  mobile: "",
};

/** Screen → fields it owns. Used to jump back to the screen that has an error. */
const SCREENS = [
  { id: "problem", title: "مشکل تلویزیون", fields: ["problem"] },
  { id: "brand", title: "برند و مدل", fields: ["brand", "model"] },
  { id: "size", title: "اندازه", fields: ["size"] },
  { id: "details", title: "توضیحات", fields: ["description"] },
  { id: "media", title: "عکس و ویدئو", fields: [] },
  { id: "place", title: "شهر و آدرس", fields: ["city", "area", "address"] },
  { id: "time", title: "زمان مراجعه", fields: ["preferredTime"] },
  { id: "contact", title: "نام و موبایل", fields: ["name", "mobile"] },
] as const;

const PROBLEM_HINT: Record<string, string> = {
  no_power: "اگر چراغ استندبای روشن می‌شود یا نه، در بخش بعدی بگویید.",
};

export function BookingWizard({ cities }: { cities: { slug: string; name: string }[] }) {
  const [form, setForm] = useState<Form>(EMPTY);
  const [screen, setScreen] = useState(0);
  const [errors, setErrors] = useState<Record<string, string>>({});
  const [media, setMedia] = useState<Media[]>([]);
  const [submitting, setSubmitting] = useState(false);
  const [uploadPct, setUploadPct] = useState<number | null>(null);
  const [handoff, setHandoff] = useState<DiagnosisHandoff | null>(null);
  const [serverError, setServerError] = useState<string | null>(null);
  const [trackingCode, setTrackingCode] = useState<string | null>(null);
  const [started, setStarted] = useState(false);
  const mediaId = useRef(0);
  const headingRef = useRef<HTMLHeadingElement>(null);
  const mediaRef = useRef(media);
  mediaRef.current = media;

  // Prefill from the diagnostic link (?problem=...) and capture UTM attribution once.
  useEffect(() => {
    const params = new URLSearchParams(window.location.search);
    const problem = params.get("problem");
    if (problem && PROBLEM_OPTIONS.some((p) => p.id === problem)) {
      setForm((f) => ({ ...f, problem }));
    }
    const diag = decodeHandoff(params.get("diag"));
    if (diag) {
      setHandoff(diag);
      setForm((f) => ({ ...f, problem: diag.symptom }));
    }
    const city = params.get("city");
    if (city) {
      setForm((f) => ({ ...f, city }));
    }
    const brand = params.get("brand");
    if (brand && BRAND_OPTIONS.includes(brand as (typeof BRAND_OPTIONS)[number])) {
      setForm((f) => ({ ...f, brand }));
    }
    captureAttribution(window.location.search);
    try {
      if (!sessionStorage.getItem("tvd_landing")) sessionStorage.setItem("tvd_landing", window.location.pathname + window.location.search);
    } catch {
      /* storage unavailable */
    }
  }, []);

  useEffect(() => {
    return () => mediaRef.current.forEach((m) => URL.revokeObjectURL(m.url));
  }, []);

  useEffect(() => {
    headingRef.current?.focus();
  }, [screen]);

  function set<K extends keyof Form>(key: K, value: Form[K]) {
    if (!started) {
      setStarted(true);
      track("form_start");
    }
    setForm((f) => ({ ...f, [key]: value }));
    setErrors((e) => {
      if (!e[key]) return e;
      const { [key]: _removed, ...rest } = e;
      return rest;
    });
  }

  function validateScreen(index: number): Record<string, string> {
    const f = form;
    const e: Record<string, string> = {};
    switch (SCREENS[index].id) {
      case "problem":
        if (!f.problem) e.problem = "یک مشکل را انتخاب کنید.";
        break;
      case "brand":
        if (!f.brand) e.brand = "برند تلویزیون را انتخاب کنید.";
        break;
      case "size":
        if (!f.size) e.size = "اندازه‌ی تقریبی را انتخاب کنید.";
        break;
      case "details":
        if (f.problem === "other" && f.description.trim().length < 10) e.description = "برای «مشکل دیگر»، توضیح کوتاهی بنویسید.";
        break;
      case "place":
        if (!f.city) e.city = "شهر را انتخاب کنید.";
        if (!f.area.trim()) e.area = "منطقه را وارد کنید.";
        if (f.address.trim().length < 10) e.address = "آدرس را کامل‌تر بنویسید (حداقل ۱۰ نویسه).";
        break;
      case "time":
        if (!f.preferredTime) e.preferredTime = "زمان مناسب را انتخاب کنید.";
        break;
      case "contact":
        if (f.name.trim().length < 2) e.name = "نام خود را وارد کنید.";
        if (!/^(\+98|0)?9\d{9}$/.test(f.mobile.replace(/[\s-]/g, "").replace(/[۰-۹]/g, (d) => String(d.charCodeAt(0) - 0x06f0)))) {
          e.mobile = "شماره موبایل معتبر وارد کنید (مثلاً ۰۹۱۲۳۴۵۶۷۸۹).";
        }
        break;
    }
    return e;
  }

  function next() {
    const e = validateScreen(screen);
    setErrors(e);
    if (Object.keys(e).length) return;
    track("form_step", { step: screen + 1, screen: SCREENS[screen].id });
    setScreen((s) => Math.min(s + 1, SCREENS.length - 1));
  }

  function back() {
    setErrors({});
    setScreen((s) => Math.max(s - 1, 0));
  }

  function addMedia(kind: Media["kind"], files: FileList | null) {
    if (!files || files.length === 0) return;
    const incoming = Array.from(files).map((file) => {
      mediaId.current += 1;
      return { id: mediaId.current, kind, file, url: URL.createObjectURL(file) };
    });
    const combined = [...media.filter((m) => m.kind !== kind || kind === "problem_photo"), ...incoming];
    const check = validateMedia(combined.map((m) => ({ kind: m.kind, name: m.file.name, size: m.file.size, mime: m.file.type })));
    if (!check.ok) {
      incoming.forEach((m) => URL.revokeObjectURL(m.url));
      setServerError(check.error);
      return;
    }
    setServerError(null);
    setMedia((prev) => {
      // Single-file kinds replace the previous file; problem photos accumulate.
      const kept = kind === "problem_photo" ? prev : prev.filter((m) => m.kind !== kind);
      prev.filter((m) => m.kind === kind && kind !== "problem_photo").forEach((m) => URL.revokeObjectURL(m.url));
      return [...kept, ...incoming];
    });
  }

  function removeMedia(id: number) {
    setMedia((prev) => {
      const target = prev.find((m) => m.id === id);
      if (target) URL.revokeObjectURL(target.url);
      return prev.filter((m) => m.id !== id);
    });
  }

  async function submit(e: FormEvent<HTMLFormElement>) {
    e.preventDefault();
    const local = validateScreen(SCREENS.length - 1);
    setErrors(local);
    if (Object.keys(local).length) return;
    setSubmitting(true);
    setServerError(null);

    const fd = new FormData(e.currentTarget);
    fd.set("problem", form.problem);
    fd.set("standbyLight", form.standbyLight);
    fd.set("brand", form.brand);
    fd.set("model", form.model);
    fd.set("size", form.size);
    fd.set("description", form.description);
    fd.set("city", form.city);
    fd.set("area", form.area);
    fd.set("address", form.address);
    fd.set("preferredTime", form.preferredTime);
    fd.set("name", form.name);
    fd.set("mobile", form.mobile);
    fd.set("landingPage", (() => {
      try {
        return sessionStorage.getItem("tvd_landing") ?? window.location.pathname;
      } catch {
        return window.location.pathname;
      }
    })());
    const attribution = readAttribution();
    for (const [k, v] of Object.entries(attribution)) fd.set(k, v);
    fd.set("source", attribution.utm_source ?? document.referrer ?? "direct");
    if (handoff) fd.set("diagnosis", JSON.stringify(handoff));
    media.forEach((m) => {
      fd.append("media", m.file, m.file.name);
      fd.append("mediaKind", m.kind);
    });

    try {
      setUploadPct(0);
      const { status, data } = await postLead(fd, (f) => setUploadPct(Math.round(f * 100)));
      if (status >= 200 && status < 300 && data.ok && data.trackingCode) {
        setTrackingCode(data.trackingCode);
        track("form_step", { step: SCREENS.length, screen: "submitted" });
        return;
      }
      if (data.errors) {
        setErrors(data.errors);
        const firstBad = SCREENS.findIndex((s) => Object.keys(data.errors ?? {}).some((k) => (s.fields as readonly string[]).includes(k)));
        if (firstBad >= 0) setScreen(firstBad);
      }
      setServerError(data.error ?? (status === 429 ? "تعداد درخواست‌ها زیاد است. کمی بعد دوباره تلاش کنید." : "ثبت درخواست انجام نشد. لطفاً دوباره تلاش کنید یا از طریق صفحه‌ی تماس اقدام کنید."));
      if (status >= 500) setServerError("سامانه‌ی ثبت در این لحظه پاسخ نمی‌دهد. اطلاعات شما در مرورگر باقی مانده است؛ دوباره تلاش کنید.");
    } catch {
      setServerError("اتصال برقرار نشد یا آپلود دیر تمام شد. اینترنت خود را بررسی کنید و دوباره تلاش کنید؛ فایل‌های بزرگ ممکن است زمان بیشتری ببرند.");
    } finally {
      setSubmitting(false);
      setUploadPct(null);
    }
  }

  const progress = useMemo(() => Math.round(((screen + 1) / SCREENS.length) * 100), [screen]);

  if (trackingCode) {
    return (
      <div className="notice notice--success" role="status" style={{ padding: "var(--s-6)" }}>
        <p style={{ fontWeight: 900, fontSize: "1.3rem" }}>درخواستت ثبت شد ✓</p>
        <p>کد پیگیری خود را ذخیره کنید. با این کد و شماره موبایل می‌توانید وضعیت را ببینید.</p>
        <p className="tracking-code" aria-label={`کد پیگیری: ${trackingCode.split("").join(" ")}`}>{trackingCode}</p>
        <p className="muted" style={{ fontSize: "0.92rem" }}>
          کارشناس درخواست را بررسی می‌کند و برای هماهنگی زمان مراجعه با شما تماس می‌گیرد.
        </p>
        <div className="btn-row" style={{ marginBlockStart: "var(--s-4)" }}>
          <Link href="/track" className="btn btn--primary">پیگیری درخواست</Link>
          <Link href="/" className="btn btn--ghost">بازگشت به صفحه اصلی</Link>
        </div>
      </div>
    );
  }

  const current = SCREENS[screen];
  const isLast = screen === SCREENS.length - 1;
  const hint = PROBLEM_HINT[form.problem];

  return (
    <form className="form form-card" onSubmit={submit} noValidate aria-labelledby="booking-step-title">
      {handoff ? (
        <div className="notice" role="status" style={{ marginBlockEnd: "var(--s-4)" }}>
          <p style={{ margin: 0, fontWeight: 800 }}>نتیجه‌ی تشخیص همراه درخواست ثبت می‌شود</p>
          <p style={{ margin: "var(--s-1) 0 0" }}>{handoffSummary(handoff)}</p>
          <p className="muted" style={{ margin: "var(--s-1) 0 0", fontSize: "0.9rem" }}>
            این یک سرنخ احتمالی است؛ تشخیص قطعی با بررسی تکنسین انجام می‌شود.
          </p>
        </div>
      ) : null}

      <div className="steps-bar" aria-live="polite">
        <span>
          مرحله {screen + 1} از {SCREENS.length}
        </span>
        <span className="steps-bar__track" aria-hidden="true">
          <span className="steps-bar__fill" style={{ width: `${progress}%` }} />
        </span>
      </div>

      <h2 id="booking-step-title" ref={headingRef} tabIndex={-1} style={{ fontSize: "clamp(1.4rem, 1.1rem + 1vw, 1.9rem)", outline: "none" }}>
        {current.title}
      </h2>

      {/* Honeypot: hidden from people, visible to bots. */}
      <div className="honeypot" aria-hidden="true">
        <label>
          وب‌سایت
          <input name="website" tabIndex={-1} autoComplete="off" />
        </label>
      </div>

      {current.id === "problem" ? (
        <fieldset className="choice-grid">
          <legend>مشکل اصلی تلویزیون را انتخاب کنید</legend>
          {PROBLEM_OPTIONS.map((p) => (
            <label key={p.id} className="choice">
              <input type="radio" name="problem" value={p.id} checked={form.problem === p.id} onChange={() => set("problem", p.id)} />
              <span>{p.label}</span>
            </label>
          ))}
          {errors.problem ? <p className="field__error" role="alert">{errors.problem}</p> : null}
        </fieldset>
      ) : null}

      {current.id === "brand" ? (
        <>
          <fieldset className="choice-grid">
            <legend>برند تلویزیون</legend>
            {BRAND_OPTIONS.map((b) => (
              <label key={b} className="choice">
                <input type="radio" name="brand" value={b} checked={form.brand === b} onChange={() => set("brand", b)} />
                <span>{b}</span>
              </label>
            ))}
          </fieldset>
          {errors.brand ? <p className="field__error" role="alert">{errors.brand}</p> : null}
          <div className="field">
            <label htmlFor="model">مدل (اختیاری)</label>
            <input id="model" name="model" className="input" value={form.model} onChange={(e) => set("model", e.target.value)} placeholder="مثلاً 55AU7000" dir="ltr" />
            <p className="field__hint">مدل روی برچسب پشت تلویزیون یا در صفحه‌ی مشخصات نوشته شده است.</p>
          </div>
        </>
      ) : null}

      {current.id === "size" ? (
        <fieldset className="choice-grid">
          <legend>اندازه‌ی تقریبی تلویزیون</legend>
          {SIZE_OPTIONS.map((s) => (
            <label key={s} className="choice">
              <input type="radio" name="size" value={s} checked={form.size === s} onChange={() => set("size", s)} />
              <span>{s}</span>
            </label>
          ))}
          {errors.size ? <p className="field__error" role="alert">{errors.size}</p> : null}
        </fieldset>
      ) : null}

      {current.id === "details" ? (
        <>
          <fieldset className="choice-grid">
            <legend>آیا چراغ استندبای (Standby) روشن می‌شود؟</legend>
            {[
              { v: "yes", l: "بله" },
              { v: "no", l: "نه" },
              { v: "unknown", l: "نمی‌دانم" },
            ].map((o) => (
              <label key={o.v} className="choice">
                <input type="radio" name="standbyLight" value={o.v} checked={form.standbyLight === o.v} onChange={() => set("standbyLight", o.v as Form["standbyLight"])} />
                <span>{o.l}</span>
              </label>
            ))}
          </fieldset>
          <div className="field">
            <label htmlFor="description">توضیحات {form.problem === "other" ? "(الزامی)" : "(اختیاری)"}</label>
            <textarea
              id="description"
              name="description"
              className="textarea"
              value={form.description}
              onChange={(e) => set("description", e.target.value)}
              aria-invalid={Boolean(errors.description)}
              aria-describedby={errors.description ? "description-error" : "description-hint"}
              maxLength={1000}
            />
            <p id="description-hint" className="field__hint">
              {hint ?? "هر چه دقیق‌تر بنویسید، تکنسین سریع‌تر تشخیص می‌دهد: چه زمانی شروع شد، آیا بعد از قطعی برق بود، چه صدا یا چراغی دیده می‌شود."}
            </p>
            {errors.description ? <p id="description-error" className="field__error" role="alert">{errors.description}</p> : null}
          </div>
        </>
      ) : null}

      {current.id === "media" ? (
        <div style={{ display: "grid", gap: "var(--s-4)" }}>
          <p className="muted" style={{ margin: 0 }}>
            این مرحله اختیاری است، اما عکس و ویدئو کمک می‌کند تکنسین پیش از مراجعه بداند با چه دستگاهی روبه‌رو است.
          </p>
          <MediaInput label="عکس جلوی تلویزیون" hint="JPG، PNG، WebP یا HEIC تا ۸ مگابایت" accept="image/*" multiple={false} onFiles={(f) => addMedia("front_photo", f)} items={media.filter((m) => m.kind === "front_photo")} onRemove={removeMedia} />
          <MediaInput label="عکس برچسب پشت تلویزیون (مدل و سریال)" hint="نزدیک و واضح؛ برای تشخیص قطعه‌ی درست مهم است" accept="image/*" multiple={false} onFiles={(f) => addMedia("label_photo", f)} items={media.filter((m) => m.kind === "label_photo")} onRemove={removeMedia} />
          <MediaInput label="عکس از مشکل (حداکثر ۴ عکس)" hint="مثلاً خطوط، لکه‌ها یا صفحه‌ی سیاه" accept="image/*" multiple onFiles={(f) => addMedia("problem_photo", f)} items={media.filter((m) => m.kind === "problem_photo")} onRemove={removeMedia} />
          <MediaInput label="ویدئوی کوتاه از مشکل" hint="MP4، MOV یا WebM تا ۵۰ مگابایت؛ ۱۰ تا ۲۰ ثانیه کافی است" accept="video/*" multiple={false} onFiles={(f) => addMedia("problem_video", f)} items={media.filter((m) => m.kind === "problem_video")} onRemove={removeMedia} />
          <p className="field__hint">حداکثر {MEDIA_LIMITS.maxFiles} فایل و مجموع ۸۰ مگابایت.</p>
        </div>
      ) : null}

      {current.id === "place" ? (
        <>
          <div className="field">
            <label htmlFor="city">شهر</label>
            <select id="city" name="city" className="select" value={form.city} onChange={(e) => set("city", e.target.value)} aria-invalid={Boolean(errors.city)}>
              <option value="">انتخاب کنید</option>
              {cities.map((c) => (
                <option key={c.slug} value={c.name}>{c.name}</option>
              ))}
              <option value="سایر">سایر شهرها</option>
            </select>
            {errors.city ? <p className="field__error" role="alert">{errors.city}</p> : null}
          </div>
          <div className="field">
            <label htmlFor="area">منطقه یا محله</label>
            <input id="area" name="area" className="input" value={form.area} onChange={(e) => set("area", e.target.value)} aria-invalid={Boolean(errors.area)} />
            {errors.area ? <p className="field__error" role="alert">{errors.area}</p> : null}
          </div>
          <div className="field">
            <label htmlFor="address">آدرس کامل</label>
            <textarea id="address" name="address" className="textarea" value={form.address} onChange={(e) => set("address", e.target.value)} aria-invalid={Boolean(errors.address)} maxLength={400} />
            <p className="field__hint">آدرس فقط برای هماهنگی مراجعه استفاده می‌شود.</p>
            {errors.address ? <p className="field__error" role="alert">{errors.address}</p> : null}
          </div>
        </>
      ) : null}

      {current.id === "time" ? (
        <fieldset className="choice-grid">
          <legend>زمان مناسب مراجعه</legend>
          {TIME_OPTIONS.map((t) => (
            <label key={t} className="choice">
              <input type="radio" name="preferredTime" value={t} checked={form.preferredTime === t} onChange={() => set("preferredTime", t)} />
              <span>{t}</span>
            </label>
          ))}
          <p className="field__hint" style={{ gridColumn: "1 / -1" }}>زمان قطعی پس از هماهنگی با کارشناس اعلام می‌شود.</p>
          {errors.preferredTime ? <p className="field__error" role="alert">{errors.preferredTime}</p> : null}
        </fieldset>
      ) : null}

      {current.id === "contact" ? (
        <>
          <div className="field">
            <label htmlFor="name">نام و نام خانوادگی</label>
            <input id="name" name="name" className="input" autoComplete="name" value={form.name} onChange={(e) => set("name", e.target.value)} aria-invalid={Boolean(errors.name)} />
            {errors.name ? <p className="field__error" role="alert">{errors.name}</p> : null}
          </div>
          <div className="field">
            <label htmlFor="mobile">شماره موبایل</label>
            <input id="mobile" name="mobile" className="input" inputMode="tel" autoComplete="tel" dir="ltr" placeholder="09123456789" value={form.mobile} onChange={(e) => set("mobile", e.target.value)} aria-invalid={Boolean(errors.mobile)} aria-describedby="mobile-hint" />
            <p id="mobile-hint" className="field__hint">برای پیگیری و هماهنگی زمان مراجعه استفاده می‌شود.</p>
            {errors.mobile ? <p className="field__error" role="alert">{errors.mobile}</p> : null}
          </div>
          <div className="notice">
            <p style={{ margin: 0, fontSize: "0.92rem" }}>
              با ثبت درخواست، با <Link href="/privacy">سیاست حریم خصوصی</Link> موافقت می‌کنید. هزینه‌ی بررسی و تعمیر پیش از شروع کار اعلام می‌شود.
            </p>
          </div>
        </>
      ) : null}

      {serverError ? (
        <div className="notice notice--error" role="alert">
          {serverError}
        </div>
      ) : null}

      <div className="btn-row" style={{ justifyContent: "space-between" }}>
        <button type="button" className="btn btn--ghost" onClick={back} disabled={screen === 0 || submitting}>
          <span style={{ display: "inline-flex", transform: "scaleX(-1)" }}>
            <Icon name="arrow" size={18} />
          </span>
          مرحله قبل
        </button>
        {isLast ? (
          <>
          {uploadPct !== null ? (
            <span style={{ display: "grid", gap: "var(--s-1)", minWidth: 160 }} role="status">
              <progress value={uploadPct} max={100} aria-label="پیشرفت ارسال" style={{ width: "100%" }} />
              <span className="muted" style={{ fontSize: "0.85rem" }}>
                در حال ارسال… {uploadPct}٪
              </span>
            </span>
          ) : null}
          <button type="submit" className="btn btn--primary" disabled={submitting}>
            {submitting ? "در حال ثبت…" : "ثبت درخواست"}
          </button>
          </>
        ) : (
          <button type="button" className="btn btn--primary" onClick={next}>
            ادامه
            <Icon name="arrow" size={18} />
          </button>
        )}
      </div>
    </form>
  );
}

function MediaInput({
  label,
  hint,
  accept,
  multiple,
  onFiles,
  items,
  onRemove,
}: {
  label: string;
  hint: string;
  accept: string;
  multiple: boolean;
  onFiles: (files: FileList | null) => void;
  items: Media[];
  onRemove: (id: number) => void;
}) {
  const id = useId();
  return (
    <div className="upload">
      <label htmlFor={id} className="field__label">
        {label}
      </label>
      <p className="field__hint" style={{ margin: 0 }}>{hint}</p>
      <input id={id} type="file" accept={accept} multiple={multiple} onChange={(e) => onFiles(e.target.files)} />
      {items.length ? (
        <ul className="upload-list" aria-label={`فایل‌های ${label}`}>
          {items.map((m) => (
            <li key={m.id}>
              {m.kind === "problem_video" ? (
                <span>ویدئو: {m.file.name.slice(0, 18)}</span>
              ) : (
                // eslint-disable-next-line @next/next/no-img-element
                <img src={m.url} alt={`پیش‌نمایش ${label}`} />
              )}
              <button type="button" className="btn btn--ghost" style={{ position: "absolute", inset: "auto 4px 4px auto", minHeight: 30, padding: "0 8px", fontSize: "0.75rem", background: "var(--ink-950)" }} onClick={() => onRemove(m.id)}>
                حذف
              </button>
            </li>
          ))}
        </ul>
      ) : null}
    </div>
  );
}

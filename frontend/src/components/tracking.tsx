"use client";

import { useState, type FormEvent } from "react";
import { track } from "@/lib/analytics";
import { faDateTime } from "@/lib/format";
import type { publicTrackingView } from "@/lib/leads";

type View = ReturnType<typeof publicTrackingView>;

export function TrackingForm() {
  const [code, setCode] = useState("");
  const [mobile, setMobile] = useState("");
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [view, setView] = useState<View | null>(null);

  async function onSubmit(e: FormEvent<HTMLFormElement>) {
    e.preventDefault();
    setError(null);
    setView(null);
    if (!code.trim() || !mobile.trim()) {
      setError("کد پیگیری و شماره موبایل را وارد کنید.");
      return;
    }
    setLoading(true);
    track("tracking_lookup");
    try {
      const res = await fetch("/api/tracking", {
        method: "POST",
        headers: { "content-type": "application/json" },
        body: JSON.stringify({ code, mobile }),
      });
      const data = (await res.json()) as { ok: boolean; tracking?: View; error?: string };
      if (data.ok && data.tracking) setView(data.tracking);
      else setError(data.error ?? "اطلاعاتی پیدا نشد.");
    } catch {
      setError("اتصال برقرار نشد. دوباره تلاش کنید.");
    } finally {
      setLoading(false);
    }
  }

  return (
    <div className="two-col">
      <div>
        <form className="form form-card" onSubmit={onSubmit} noValidate>
          <div className="field">
            <label htmlFor="code">کد پیگیری</label>
            <input id="code" name="code" className="input" dir="ltr" placeholder="TV-XXXXXX" autoCapitalize="characters" value={code} onChange={(e) => setCode(e.target.value)} />
            <p className="field__hint">کدی که پس از ثبت درخواست نمایش داده شد.</p>
          </div>
          <div className="field">
            <label htmlFor="track-mobile">شماره موبایل ثبت‌شده</label>
            <input id="track-mobile" name="mobile" className="input" dir="ltr" inputMode="tel" autoComplete="tel" placeholder="09123456789" value={mobile} onChange={(e) => setMobile(e.target.value)} />
          </div>
          {error ? (
            <div className="notice notice--error" role="alert">
              {error}
            </div>
          ) : null}
          <button type="submit" className="btn btn--primary" disabled={loading}>
            {loading ? "در حال بررسی…" : "نمایش وضعیت"}
          </button>
        </form>
      </div>

      <aside className="side-panel" aria-live="polite">
        {view ? (
          <>
            <p className="eyebrow" style={{ margin: 0 }}>
              کد {view.trackingCode}
            </p>
            <p className="muted" style={{ margin: 0, fontSize: "0.9rem" }}>
              ثبت‌شده در {faDateTime(view.createdAt)}
            </p>
            <ol className="status-list">
              {view.steps.map((s) => (
                <li key={s.status} data-done={s.done} data-current={s.current}>
                  <span className="status-list__dot" aria-hidden="true">
                    {s.done ? "✓" : ""}
                  </span>
                  <span>
                    {s.label}
                    {s.current ? <span className="sr-only"> (وضعیت فعلی)</span> : null}
                  </span>
                </li>
              ))}
            </ol>
            {view.visitAt ? <p style={{ marginBlockStart: "var(--s-3)" }}>زمان مراجعه: {faDateTime(view.visitAt)}</p> : null}
          </>
        ) : (
          <>
            <p style={{ fontWeight: 800, margin: 0 }}>وضعیت درخواست شما</p>
            <p className="muted" style={{ margin: 0 }}>
              پس از وارد کردن کد و شماره، مراحل بررسی و اعزام اینجا نمایش داده می‌شود.
            </p>
          </>
        )}
      </aside>
    </div>
  );
}

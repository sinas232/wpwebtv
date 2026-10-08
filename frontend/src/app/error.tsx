"use client";

import Link from "next/link";
import { useEffect } from "react";

/** Route-level error boundary. Never shows raw error text to visitors. */
export default function ErrorPage({ error, reset }: { error: Error & { digest?: string }; reset: () => void }) {
  useEffect(() => {
    // Hook point for error monitoring (e.g. Sentry) once a DSN is configured.
    console.error(error);
  }, [error]);

  return (
    <section className="section" style={{ minHeight: "60svh", display: "grid", alignContent: "center" }}>
      <div className="container">
        <div className="notice notice--error" role="alert">
          <p style={{ margin: 0, fontWeight: 900, fontSize: "1.2rem" }}>مشکلی پیش آمد</p>
          <p style={{ margin: "var(--s-2) 0 0" }}>صفحه بارگذاری نشد. دوباره تلاش کنید؛ اگر ادامه داشت، از صفحه‌ی اصلی شروع کنید.</p>
        </div>
        <div className="btn-row" style={{ marginBlockStart: "var(--s-4)" }}>
          <button type="button" className="btn btn--primary" onClick={() => reset()}>
            تلاش دوباره
          </button>
          <Link href="/" className="btn btn--ghost">
            صفحه اصلی
          </Link>
        </div>
      </div>
    </section>
  );
}

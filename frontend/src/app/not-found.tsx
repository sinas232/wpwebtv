import type { Metadata } from "next";
import Link from "next/link";
import { Icon } from "@/components/ui";

export const metadata: Metadata = { title: "صفحه پیدا نشد", robots: { index: false, follow: true } };

export default function NotFound() {
  return (
    <section className="section" style={{ minHeight: "70svh", display: "grid", alignContent: "center" }}>
      <div className="container" style={{ textAlign: "center" }}>
        <p style={{ fontSize: "clamp(4rem, 3rem + 6vw, 7rem)", fontWeight: 900, margin: 0, lineHeight: 1, color: "var(--accent)" }} aria-hidden="true">
          404
        </p>
        <h1 style={{ marginBlockStart: "var(--s-4)" }}>این صفحه پیدا نشد</h1>
        <p className="lead" style={{ marginInline: "auto" }}>
          شاید آدرس تغییر کرده باشد. از یکی از مسیرهای زیر ادامه بده.
        </p>
        <div className="btn-row" style={{ justifyContent: "center", marginBlockStart: "var(--s-5)" }}>
          <Link href="/" className="btn btn--primary">
            صفحه اصلی
            <Icon name="arrow" size={18} />
          </Link>
          <Link href="/diagnose" className="btn btn--ghost">
            تشخیص مشکل
          </Link>
          <Link href="/booking" className="btn btn--ghost">
            درخواست تعمیر
          </Link>
        </div>
      </div>
    </section>
  );
}

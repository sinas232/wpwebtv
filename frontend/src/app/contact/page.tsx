import type { Metadata } from "next";
import Link from "next/link";
import { PageHero } from "@/components/page-parts";
import { Icon } from "@/components/ui";
import { pageMetadata } from "@/lib/seo";
import { site } from "@/lib/site";

export const metadata: Metadata = pageMetadata({
  title: "تماس با ما",
  description: "راه‌های ارتباط با تیم تعمیر تلویزیون و ثبت درخواست.",
  path: "/contact",
});

export default function ContactPage() {
  const channels = [
    site.phone ? { href: `tel:${site.phone}`, label: "تماس تلفنی", value: site.phone, icon: "phone" as const } : null,
    site.whatsapp ? { href: `https://wa.me/${site.whatsapp.replace(/\D/g, "")}`, label: "واتساپ", value: site.whatsapp, icon: "phone" as const } : null,
    site.telegram ? { href: `https://t.me/${site.telegram.replace(/^@/, "")}`, label: "تلگرام", value: site.telegram, icon: "phone" as const } : null,
    site.email ? { href: `mailto:${site.email}`, label: "ایمیل", value: site.email, icon: "phone" as const } : null,
  ].filter((c): c is NonNullable<typeof c> => Boolean(c));

  return (
    <>
      <PageHero eyebrow="تماس" title="با ما در ارتباط باش" lead="سریع‌ترین راه، ثبت درخواست است. اگر کانال تماس مستقیم داری، اینجا می‌بینی." crumbs={[{ name: "تماس با ما", href: "/contact" }]} />
      <section className="section">
        <div className="container two-col">
          <div>
            {channels.length ? (
              <ul className="grid grid--2" style={{ listStyle: "none", padding: 0, margin: 0 }}>
                {channels.map((c) => (
                  <li key={c.label}>
                    <a href={c.href} className="card" data-track={c.label === "تماس تلفنی" ? "phone_click" : undefined} data-track-label={c.label} rel="noopener">
                      <span className="card__icon" aria-hidden="true">
                        <Icon name={c.icon} />
                      </span>
                      <span className="card__title">{c.label}</span>
                      <span className="muted" dir="ltr" style={{ textAlign: "start" }}>{c.value}</span>
                    </a>
                  </li>
                ))}
              </ul>
            ) : (
              <div className="notice" role="status">
                <p style={{ margin: 0, fontWeight: 700 }}>کانال تماس مستقیم هنوز تنظیم نشده است.</p>
                <p style={{ margin: "var(--s-2) 0 0" }}>لطفاً از فرم درخواست تعمیر استفاده کنید؛ درخواست شما بررسی و با شما هماهنگ می‌شود.</p>
              </div>
            )}
          </div>
          <aside className="side-panel" aria-label="درخواست تعمیر">
            <p style={{ fontWeight: 900, margin: 0 }}>درخواست تعمیر ثبت کن</p>
            <p className="muted" style={{ margin: 0 }}>برای مشکل تلویزیون، فرم چند مرحله‌ای سریع‌ترین راه است.</p>
            <Link href="/booking" className="btn btn--primary">درخواست تعمیر</Link>
          </aside>
        </div>
      </section>
    </>
  );
}

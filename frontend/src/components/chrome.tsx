"use client";

import Link from "next/link";
import { usePathname } from "next/navigation";
import { useEffect, useState } from "react";
import { Icon } from "@/components/ui";
import { useReducedMotion } from "@/components/hooks";
import { track, type EventName } from "@/lib/analytics";
import { site } from "@/lib/site";

const NAV = [
  { href: "/services", label: "خدمات" },
  { href: "/problems", label: "مشکلات" },
  { href: "/brands", label: "برندها" },
  { href: "/cities", label: "شهرها" },
  { href: "/track", label: "پیگیری" },
  { href: "/blog", label: "مقاله‌ها" },
  { href: "/faq", label: "پرسش‌ها" },
];

export function SiteHeader() {
  const pathname = usePathname();
  const [scrolled, setScrolled] = useState(false);
  const [open, setOpen] = useState(false);

  useEffect(() => {
    const onScroll = () => setScrolled(window.scrollY > 8);
    onScroll();
    window.addEventListener("scroll", onScroll, { passive: true });
    return () => window.removeEventListener("scroll", onScroll);
  }, []);

  useEffect(() => setOpen(false), [pathname]);

  return (
    <header className="site-header" data-scrolled={scrolled}>
      <div className="container site-header__inner">
        <Link href="/" className="brand" aria-label={`${site.name} — صفحه اصلی`}>
          <span className="brand__mark" aria-hidden="true">TV</span>
          <span>{site.name}</span>
        </Link>

        <nav className="nav" aria-label="ناوبری اصلی">
          {NAV.map((item) => (
            <Link key={item.href} href={item.href} aria-current={pathname.startsWith(item.href) ? "page" : undefined}>
              {item.label}
            </Link>
          ))}
        </nav>

        <div style={{ display: "flex", gap: "var(--s-2)", alignItems: "center" }}>
          <Link href="/booking" className="btn btn--primary header-cta" data-track="cta_click" data-track-label="header_booking">
            درخواست تعمیر
          </Link>
          <button
            type="button"
            className="menu-toggle"
            aria-expanded={open}
            aria-controls="mobile-nav"
            aria-label={open ? "بستن منو" : "باز کردن منو"}
            onClick={() => setOpen((v) => !v)}
          >
            <Icon name={open ? "close" : "menu"} />
          </button>
        </div>
      </div>

      <nav id="mobile-nav" className="mobile-nav" aria-label="منوی موبایل" data-open={open} hidden={!open}>
        {[{ href: "/diagnose", label: "تشخیص مشکل تلویزیون" }, { href: "/booking", label: "درخواست تعمیر" }, ...NAV, { href: "/contact", label: "تماس با ما" }].map(
          (item) => (
            <Link key={item.href} href={item.href}>
              {item.label}
            </Link>
          ),
        )}
      </nav>
    </header>
  );
}

export function SiteFooter() {
  return (
    <footer className="site-footer">
      <div className="container">
        <div className="footer-grid">
          <div>
            <p className="brand" style={{ marginBlockEnd: "var(--s-3)" }}>
              <span className="brand__mark" aria-hidden="true">TV</span>
              <span>{site.name}</span>
            </p>
            <p style={{ maxWidth: "42ch" }}>{site.description}</p>
            <p className="muted" style={{ fontSize: "0.85rem" }}>
              نام برند موقت است و پیش از انتشار با نام رسمی جایگزین می‌شود.
            </p>
          </div>
          <nav aria-label="خدمات">
            <h2>خدمات</h2>
            <ul>
              <li><Link href="/services/tv-repair">تعمیر تلویزیون</Link></li>
              <li><Link href="/services/on-site-repair">تعمیر در محل</Link></li>
              <li><Link href="/services/backlight-repair">تعمیر بک‌لایت</Link></li>
              <li><Link href="/services/hdmi-repair">تعمیر HDMI</Link></li>
            </ul>
          </nav>
          <nav aria-label="مشکلات رایج">
            <h2>مشکلات رایج</h2>
            <ul>
              <li><Link href="/problems/no-picture">صدا دارد تصویر ندارد</Link></li>
              <li><Link href="/problems/backlight">بک‌لایت خراب است</Link></li>
              <li><Link href="/problems/tv-wont-turn-on">روشن نمی‌شود</Link></li>
              <li><Link href="/problems/smart-tv">Smart TV و Wi-Fi</Link></li>
            </ul>
          </nav>
          <nav aria-label="سایت">
            <h2>سایت</h2>
            <ul>
              <li><Link href="/cities">شهرها و مناطق</Link></li>
              <li><Link href="/brands">برندها</Link></li>
              <li><Link href="/faq">پرسش‌های رایج</Link></li>
              <li><Link href="/contact">تماس با ما</Link></li>
              <li><Link href="/privacy">حریم خصوصی</Link></li>
              <li><Link href="/terms">شرایط استفاده</Link></li>
            </ul>
          </nav>
        </div>
        <div className="footer-bottom">
          <span>© {new Date().getFullYear()} {site.name}. تمام حقوق محفوظ است.</span>
        </div>
      </div>
    </footer>
  );
}

/** Sticky one-handed CTA on small screens. Hidden on booking itself. */
export function MobileCta() {
  const pathname = usePathname();
  const [visible, setVisible] = useState(false);
  useEffect(() => {
    const onScroll = () => setVisible(window.scrollY > 320);
    onScroll();
    window.addEventListener("scroll", onScroll, { passive: true });
    return () => window.removeEventListener("scroll", onScroll);
  }, []);
  if (pathname.startsWith("/booking")) return null;
  return (
    <div className="mobile-cta" data-visible={visible} aria-hidden={!visible}>
      <Link href="/diagnose" className="btn btn--ghost" tabIndex={visible ? 0 : -1} data-track="cta_click" data-track-label="mobile_diagnose">
        تشخیص
      </Link>
      <Link href="/booking" className="btn btn--primary" tabIndex={visible ? 0 : -1} data-track="cta_click" data-track-label="mobile_booking">
        درخواست تعمیر
      </Link>
    </div>
  );
}

/**
 * Global behaviours: reveal-on-scroll observer, delegated click tracking via
 * data-track attributes, and Lenis + GSAP ScrollTrigger smooth scrolling
 * (skipped entirely under prefers-reduced-motion).
 */
export function GlobalBehaviors() {
  const reduced = useReducedMotion();
  const pathname = usePathname();

  useEffect(() => {
    document.documentElement.classList.add("js");

    const targets = Array.from(document.querySelectorAll<HTMLElement>("[data-reveal-observe]"));
    if (reduced || typeof IntersectionObserver === "undefined") {
      targets.forEach((el) => (el.dataset.reveal = "done"));
    } else {
      const io = new IntersectionObserver(
        (entries) => {
          for (const entry of entries) {
            if (entry.isIntersecting) {
              (entry.target as HTMLElement).dataset.reveal = "done";
              io.unobserve(entry.target);
            }
          }
        },
        { threshold: 0.12, rootMargin: "0px 0px -6% 0px" },
      );
      targets.forEach((el) => io.observe(el));
      return () => io.disconnect();
    }
  }, [reduced, pathname]);

  useEffect(() => {
    const onClick = (e: MouseEvent) => {
      const el = (e.target as HTMLElement).closest<HTMLElement>("[data-track]");
      if (!el) return;
      const name = el.dataset.track as EventName;
      const label = el.dataset.trackLabel ?? el.textContent?.trim().slice(0, 60) ?? "";
      const href = el.getAttribute("href") ?? "";
      if (href.startsWith("tel:")) track("phone_click", { label });
      else if (href.includes("wa.me")) track("whatsapp_click", { label });
      else if (href.includes("t.me")) track("telegram_click", { label });
      else if (name) track(name, { label });
    };
    document.addEventListener("click", onClick);
    return () => document.removeEventListener("click", onClick);
  }, []);

  return null;
}

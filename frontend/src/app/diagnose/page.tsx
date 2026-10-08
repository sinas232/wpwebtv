import type { Metadata } from "next";
import Link from "next/link";
import { Detective } from "@/components/diagnostic";
import { PageHero, FinalBand } from "@/components/page-parts";
import { pageMetadata } from "@/lib/seo";
import { DISCLAIMER } from "@/lib/diagnosis";

export const metadata: Metadata = pageMetadata({
  title: "تشخیص مشکل تلویزیون",
  description: "تشخیص تعاملی مشکل تلویزیون: مشکل را انتخاب کن، چند سؤال ساده بپرس و سرنخ احتمالی را ببین.",
  path: "/diagnose",
});

export default function DiagnosePage() {
  return (
    <>
      <PageHero
        eyebrow="تشخیص تعاملی"
        title="ببینیم تلویزیونت چی میگه"
        lead={`مشکل را انتخاب کن و چند سؤال کوتاه جواب بده. نتیجه احتمالی است. ${DISCLAIMER}`}
        crumbs={[{ name: "تشخیص مشکل", href: "/diagnose" }]}
      />
      <section className="section">
        <div className="container">
          <Detective />
          <p className="muted" style={{ marginBlockStart: "var(--s-6)" }}>
            می‌خواهی مشکل را دقیق‌تر ببینی؟ <Link href="/problems">راهنمای مشکلات رایج</Link> را بخوان.
          </p>
        </div>
      </section>
      <FinalBand title="سرنخ را پیدا کردی؟" text="درخواست بررسی بده تا تکنسین قطعی‌اش کند." />
    </>
  );
}

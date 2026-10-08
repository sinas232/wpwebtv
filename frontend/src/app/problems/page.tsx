import type { Metadata } from "next";
import { FinalBand, PageHero } from "@/components/page-parts";
import { ProblemCards } from "@/components/home/sections";
import { getProblems } from "@/lib/cms";
import { pageMetadata } from "@/lib/seo";

export const metadata: Metadata = pageMetadata({
  title: "مشکلات رایج تلویزیون و راه‌حل ایمن",
  description: "علائم، علل احتمالی و کارهای ایمن برای تلویزیونی که تصویر، صدا یا روشن شدن ندارد؛ و زمان لازم بودن تعمیرکار.",
  path: "/problems",
});

export default async function ProblemsPage() {
  const problems = await getProblems();
  return (
    <>
      <PageHero
        eyebrow="مشکلات رایج"
        title="مشکل تلویزیونت را پیدا کن"
        lead="برای هر مشکل، علائم، علل احتمالی، کارهای ایمن و زمانی که باید تکنسین بیاید را نوشته‌ایم. تشخیص قطعی فقط با بررسی تکنسین ممکن است."
        crumbs={[{ name: "مشکلات", href: "/problems" }]}
      />
      <section className="section">
        <div className="container">
          <ProblemCards problems={problems} />
        </div>
      </section>
      <FinalBand title="مشکل در فهرست نیست؟" text="با تشخیص تعاملی شروع کن یا مستقیم درخواست بده." />
    </>
  );
}

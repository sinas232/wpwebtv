import type { Metadata } from "next";
import Link from "next/link";
import { Detective } from "@/components/diagnostic";
import { ExplodedTv } from "@/components/exploded";
import { BrandExplorer } from "@/components/home/brand-explorer";
import { FinalCta } from "@/components/home/final-cta";
import { Hero } from "@/components/home/hero";
import { ProcessTimeline } from "@/components/home/timeline";
import { BeforeAfter } from "@/components/home/before-after";
import { CityCards, ProblemCards, ReviewsBlock, ServiceCards, TrustBlock } from "@/components/home/sections";
import { Reveal, SectionHead } from "@/components/ui";
import { getBrands, getCities, getProblems, getServices } from "@/lib/cms";
import { pageMetadata } from "@/lib/seo";

export const metadata: Metadata = pageMetadata({
  title: "تعمیر تلویزیون در محل | تشخیص آنلاین و درخواست تعمیر",
  description: "تعمیر تخصصی تلویزیون در محل با تکنسین متخصص. مشکل تلویزیونت را در چند قدم بررسی کن و درخواست تعمیر ثبت کن.",
  path: "/",
});

export default async function HomePage() {
  const [brands, problems, services, cities] = await Promise.all([getBrands(), getProblems(), getServices(), getCities()]);

  return (
    <>
      <Hero />

      <section id="diagnose-section" className="section" aria-labelledby="diagnose-title">
        <div className="container">
          <Reveal>
            <SectionHead
              id="diagnose-title"
              eyebrow="تشخیص تعاملی"
              title="ببینیم تلویزیونت چی میگه"
              lead="مشکل را انتخاب کن. تلویزیون سه‌بعدی واکنش نشان می‌دهد و با چند سؤال، سرنخ را پیدا می‌کنیم. این تشخیص احتمالی است، نه قطعی."
            />
            <Detective />
          </Reveal>
        </div>
      </section>

      <ExplodedTv />

      <section className="section" aria-labelledby="brands-title">
        <div className="container">
          <Reveal>
            <SectionHead id="brands-title" eyebrow="برندها" title="برند تلویزیونت را انتخاب کن" lead="برای هر برند، صفحه‌ی اختصاصی با مشکلات رایج و اطلاعات مدل داریم." />
            <BrandExplorer brands={brands} />
          </Reveal>
        </div>
      </section>

      <section className="section section--light" aria-labelledby="problems-title">
        <div className="container">
          <Reveal>
            <SectionHead id="problems-title" eyebrow="مشکلات رایج" title="کدام مشکل را داری؟" lead="برای هر مشکل: علائم، علل احتمالی، کارهای ایمن و زمانی که باید تکنسین بیاید." light />
            <ProblemCards problems={problems.slice(0, 6)} />
            <p style={{ marginBlockStart: "var(--s-5)" }}>
              <Link href="/problems" className="btn btn--light-ghost">
                همه‌ی مشکلات
              </Link>
            </p>
          </Reveal>
        </div>
      </section>

      <section className="section" aria-labelledby="services-title">
        <div className="container">
          <Reveal>
            <SectionHead id="services-title" eyebrow="خدمات" title="تعمیر، از تشخیص تا تحویل" lead="هر خدمت صفحه‌ی اختصاصی دارد با توضیح کار، پیچیدگی تقریبی و وضعیت هزینه." />
            <ServiceCards services={services.slice(0, 6)} />
          </Reveal>
        </div>
      </section>

      <section className="section" aria-labelledby="process-title" style={{ borderBlockStart: "1px solid var(--line-dark)" }}>
        <div className="container">
          <Reveal>
            <SectionHead id="process-title" eyebrow="فرایند" title="از درخواست تا تحویل" lead="هر مرحله را می‌بینی؛ کار بدون تأیید تو جلو نمی‌رود." />
          </Reveal>
          <ProcessTimeline />
          <p className="timeline-finale">و حالا... دوباره وقت فیلم دیدنه 🍿</p>
        </div>
      </section>

      <section className="section section--light" aria-labelledby="ba-title">
        <div className="container two-col" style={{ alignItems: "center" }}>
          <Reveal>
            <SectionHead id="ba-title" eyebrow="قبل و بعد" title="مشکل را با یک کشیدن ببین" lead="با نوار وسط، وضعیت قبل و بعد از تعمیر را مقایسه کن." light />
            <BeforeAfter before="تصویر تاریک" after="تصویر صحیح" caption="نمونه‌ی نمایشی. تصاویر واقعی کارهای انجام‌شده جایگزین می‌شود." />
          </Reveal>
          <Reveal delay={120}>
            <BeforeAfter before="خطوط روی تصویر" after="تصویر سالم" caption="نمونه‌ی نمایشی. تصاویر واقعی کارهای انجام‌شده جایگزین می‌شود." />
          </Reveal>
        </div>
      </section>

      <section className="section" aria-labelledby="trust-title">
        <div className="container">
          <Reveal>
            <SectionHead id="trust-title" eyebrow="اعتماد" title="چرا با ما؟ بدون ادعای بزرگ" lead="این‌ها روش کار ماست. عدد و گواهی و نظری را فقط وقتی نشان می‌دهیم که واقعی باشد." />
            <TrustBlock />
          </Reveal>
        </div>
      </section>

      <section className="section section--tight" aria-labelledby="reviews-title">
        <div className="container">
          <Reveal>
            <SectionHead id="reviews-title" eyebrow="نظرات" title="تجربه‌ی مشتریان" />
            <ReviewsBlock />
          </Reveal>
        </div>
      </section>

      <section className="section" aria-labelledby="cities-title">
        <div className="container">
          <Reveal>
            <SectionHead id="cities-title" eyebrow="منطقه‌ی خدمت" title="کجا خدمت می‌دهیم" lead="پوشش هر شهر پس از تأیید کسب‌وکار اعلام می‌شود. تا آن زمان، درخواست ثبت می‌کنی و ما هماهنگی را انجام می‌دهیم." />
            <CityCards cities={cities} />
          </Reveal>
        </div>
      </section>

      <FinalCta />
    </>
  );
}

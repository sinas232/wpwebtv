import type { Metadata } from "next";
import { notFound } from "next/navigation";
import Link from "next/link";
import { CtaRow, FaqList, JsonLd, Complexity, PriceNote } from "@/components/ui";
import { FinalBand, PageHero, RelatedLinks, SafetyNote } from "@/components/page-parts";
import { getProblem, getProblems, getService } from "@/lib/cms";
import { breadcrumbLd, pageMetadata } from "@/lib/seo";

type Params = { slug: string };

/** Problem page → booking symptom id (same ids as the diagnostic and booking form). */
const PROBLEM_TO_SYMPTOM: Record<string, string> = {
  "no-picture": "no_picture",
  "no-sound": "no_sound",
  backlight: "dark_screen",
  "tv-wont-turn-on": "no_power",
  "auto-reboot": "auto_reboot",
  hdmi: "hdmi",
  "smart-tv": "smart_wifi",
};

export async function generateStaticParams() {
  return (await getProblems()).map((p) => ({ slug: p.slug }));
}

export async function generateMetadata({ params }: { params: Promise<Params> }): Promise<Metadata> {
  const { slug } = await params;
  const p = await getProblem(slug);
  if (!p) return { title: "مشکل پیدا نشد", robots: { index: false } };
  return pageMetadata({ title: p.title, description: p.summary, path: `/problems/${p.slug}` });
}

export default async function ProblemPage({ params }: { params: Promise<Params> }) {
  const { slug } = await params;
  const problem = await getProblem(slug);
  if (!problem) notFound();
  const services = (await Promise.all(problem.relatedServices.map((s) => getService(s)))).filter((s): s is NonNullable<typeof s> => Boolean(s));

  return (
    <>
      <PageHero
        eyebrow="راهنمای مشکل"
        title={problem.title}
        lead={problem.summary}
        crumbs={[
          { name: "مشکلات", href: "/problems" },
          { name: problem.title, href: `/problems/${problem.slug}` },
        ]}
      >
        <div className="btn-row" style={{ alignItems: "center", marginBlockStart: "var(--s-4)" }}>
          <Complexity level={problem.complexity} />
          <PriceNote price={problem.price} />
        </div>
      </PageHero>

      <section className="section">
        <div className="container two-col">
          <article className="prose">
            <h2>نشانه‌ها</h2>
            <ul className="check-list">
              {problem.signs.map((s) => (
                <li key={s}>{s}</li>
              ))}
            </ul>

            <h2>علل احتمالی</h2>
            <p className="muted">این فهرست احتمالی است؛ کدام علت درست است فقط با بررسی تکنسین مشخص می‌شود.</p>
            <ul>
              {problem.causes.map((c) => (
                <li key={c}>{c}</li>
              ))}
            </ul>

            <h2>کارهای ساده و ایمن</h2>
            <ol>
              {problem.safeSteps.map((s) => (
                <li key={s}>{s}</li>
              ))}
            </ol>
            <SafetyNote>تلویزیون را باز نکنید و پشت پنل یا برد دست نزنید. بخش‌های داخلی ولتاژ بالا دارند؛ حتی بعد از خاموش کردن.</SafetyNote>

            <h2>چه زمانی تعمیرکار لازم است؟</h2>
            <ul className="warn-list check-list">
              {problem.callTechnician.map((c) => (
                <li key={c}>{c}</li>
              ))}
            </ul>

            <h2>پرسش‌های رایج</h2>
            <FaqList items={problem.faq} />
            {problem.faq.length === 0 ? (
              <p className="muted">پرسش دیگری دارید؟ <Link href="/faq">فهرست کامل پرسش‌ها</Link> را ببینید.</p>
            ) : null}
          </article>

          <aside className="side-panel" aria-label="درخواست تعمیر">
            <p style={{ fontWeight: 900, margin: 0 }}>با این مشکل، تشخیص را به تکنسین بسپار</p>
            <p className="muted" style={{ margin: 0, fontSize: "0.95rem" }}>
              مشکل از پیش انتخاب می‌شود. عکس برچسب و ویدئوی کوتاه کمک می‌کند تشخیص سریع‌تر باشد.
            </p>
            <Link href={`/booking?problem=${PROBLEM_TO_SYMPTOM[problem.slug] ?? "other"}`} className="btn btn--primary">
              درخواست بررسی این مشکل
            </Link>
            <CtaRow primaryHref="/diagnose" primaryLabel="تشخیص تعاملی" secondaryHref="/booking" secondaryLabel="درخواست تعمیر" />
          </aside>
        </div>
        <div className="container">
          <RelatedLinks title="خدمات مرتبط" links={services.map((s) => ({ href: `/services/${s.slug}`, label: s.title }))} />
        </div>
      </section>

      <FinalBand title="هنوز مطمئن نیستی؟" text="توضیح و عکس را بفرست؛ تکنسین پیش از مراجعه بررسی می‌کند." />

      <JsonLd data={breadcrumbLd([{ name: "خانه", path: "/" }, { name: "مشکلات", path: "/problems" }, { name: problem.title, path: `/problems/${problem.slug}` }])} />
    </>
  );
}

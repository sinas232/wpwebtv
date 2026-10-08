import Link from "next/link";
import { TvStage } from "@/components/tv/TvStage";
import { Icon } from "@/components/ui";

export function Hero() {
  return (
    <section className="hero" aria-labelledby="hero-title">
      <div className="container hero__grid">
        <div className="hero__copy">
          <p className="eyebrow">تعمیر تلویزیون در محل</p>
          <h1 id="hero-title">تلویزیونت یه چیزی میگه...</h1>
          <p className="hero__sub">تعمیر تخصصی تلویزیون، با تکنسین متخصص و اعلام هزینه پیش از شروع کار</p>
          <div className="btn-row">
            <Link href="/diagnose" className="btn btn--primary" data-track="cta_click" data-track-label="hero_diagnose">
              مشکل تلویزیونم رو پیدا کن
              <Icon name="arrow" size={18} />
            </Link>
            <Link href="/booking" className="btn btn--ghost" data-track="cta_click" data-track-label="hero_booking">
              درخواست تعمیر
            </Link>
          </div>
          <ul className="hero-proof" aria-label="ویژگی‌های خدمات">
            <li>تکنسین متخصص</li>
            <li>تعمیر در محل</li>
            <li>اعلام هزینه قبل از تعمیر</li>
          </ul>
        </div>

        <div className="hero__stage">
          <TvStage label="تلویزیون سه‌بعدی با صفحه‌ی متحرک؛ با حرکت ماوس یا لمس واکنش نشان می‌دهد" screen="fluid" highlight={[]} interactive />
        </div>
      </div>

      <a href="#diagnose-section" className="hero__scroll" aria-label="اسکرول به بخش تشخیص">
        <span>ادامه</span>
        <span className="hero__scroll-dot" aria-hidden="true" />
      </a>
    </section>
  );
}

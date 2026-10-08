"use client";

import { useEffect, useRef, useState } from "react";

export const PROCESS = [
  { title: "درخواست تعمیر", text: "مشکل و مدل را در چند قدم ثبت می‌کنید." },
  { title: "تماس کارشناسان", text: "کارشناس درخواست را بررسی و با شما هماهنگ می‌کند." },
  { title: "بررسی مشکل", text: "علائم و عکس‌ها بررسی می‌شوند تا قطعه‌ی احتمالی مشخص شود." },
  { title: "اعزام تکنسین", text: "تکنسین در زمان هماهنگ‌شده به محل شما می‌آید." },
  { title: "عیب‌یابی", text: "با آزمایش‌های منظم، خرابی دقیق پیدا می‌شود." },
  { title: "تعمیر", text: "پیش از تعمیر، هزینه و قطعه را با شما در میان می‌گذاریم." },
  { title: "تست نهایی", text: "تلویزیون پیش از تحویل، از نظر تصویر، صدا و اتصال آزمایش می‌شود." },
  { title: "تحویل تلویزیون", text: "دستگاه تحویل داده می‌شود و فاکتور و شرایط ضمانت در اختیار شماست." },
];

/** Each step lights up once when it enters the viewport. Visible without JS. */
export function ProcessTimeline() {
  const refs = useRef<(HTMLLIElement | null)[]>([]);
  const [seen, setSeen] = useState<Set<number>>(new Set());

  useEffect(() => {
    if (typeof IntersectionObserver === "undefined") {
      setSeen(new Set(PROCESS.map((_, i) => i)));
      return;
    }
    const io = new IntersectionObserver(
      (entries) => {
        for (const entry of entries) {
          if (!entry.isIntersecting) continue;
          const i = Number((entry.target as HTMLElement).dataset.index);
          setSeen((prev) => new Set(prev).add(i));
          io.unobserve(entry.target);
        }
      },
      { threshold: 0.4 },
    );
    refs.current.forEach((el) => el && io.observe(el));
    return () => io.disconnect();
  }, []);

  return (
    <ol className="timeline" aria-label="مراحل تعمیر">
      {PROCESS.map((step, i) => (
        <li
          key={step.title}
          ref={(el) => {
            refs.current[i] = el;
          }}
          data-index={i}
          data-in={seen.has(i)}
          style={{ transitionDelay: `${i * 60}ms` }}
        >
          <span className="timeline__num" aria-hidden="true">
            {i + 1}
          </span>
          <div>
            <h3 className="timeline__title">{step.title}</h3>
            <p className="timeline__text">{step.text}</p>
          </div>
        </li>
      ))}
    </ol>
  );
}

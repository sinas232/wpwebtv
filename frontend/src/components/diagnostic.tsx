"use client";

import Link from "next/link";
import { useEffect, useRef, useState } from "react";
import { TvStage } from "@/components/tv/TvStage";
import { track } from "@/lib/analytics";
import {
  CAUSES,
  DISCLAIMER,
  SYMPTOMS,
  encodeHandoff,
  getSymptom,
  questionsFor,
  runDiagnosis,
  type FlowAnswers,
  type PartId,
  type ScreenMode,
  type SymptomId,
} from "@/lib/diagnosis";
import { complexityLabel } from "@/lib/format";
import { Icon } from "@/components/ui";

/** SVG icons instead of emoji: emoji fonts are missing on some devices and render as boxes. */
const SYMPTOM_ICON: Record<SymptomId, Parameters<typeof Icon>[0]["name"]> = {
  no_picture: "screen",
  no_sound: "sound",
  dark_screen: "dim",
  no_power: "power",
  auto_reboot: "reboot",
  hdmi: "hdmi",
  smart_wifi: "wifi",
  other: "other",
};

const SYMPTOM_TO_PROBLEM: Partial<Record<SymptomId, string>> = {
  no_picture: "no-picture",
  no_sound: "no-sound",
  dark_screen: "backlight",
  no_power: "tv-wont-turn-on",
  auto_reboot: "auto-reboot",
  hdmi: "hdmi",
  smart_wifi: "smart-tv",
};

export function Detective({ compact = false }: { compact?: boolean }) {
  const [symptom, setSymptom] = useState<SymptomId | null>(null);
  const [answers, setAnswers] = useState<FlowAnswers>({});
  const [step, setStep] = useState(0);
  const questionRef = useRef<HTMLHeadingElement>(null);

  const sym = symptom ? getSymptom(symptom) : undefined;
  const isOther = symptom === "other";
  const questions = symptom ? questionsFor(symptom) : [];
  const outcome = symptom ? runDiagnosis(symptom, answers) : null;
  const flowDone = !!outcome;
  const current = questions[step];

  // Visual: symptom state first, then the likely cause once the flow is complete.
  const live: { screen: ScreenMode; highlight: PartId[] } = !symptom
    ? { screen: "idle", highlight: [] }
    : outcome
      ? CAUSES[outcome.cause].visual
      : { screen: sym?.screen ?? "idle", highlight: sym?.highlight ?? [] };

  useEffect(() => {
    if (symptom && !flowDone) questionRef.current?.focus();
  }, [step, symptom, flowDone]);

  function pickSymptom(id: SymptomId) {
    setSymptom(id);
    setAnswers({});
    setStep(0);
    track("diagnosis_symptom", { symptom: id });
    if (id !== "other") track("diagnosis_start", { symptom: id });
  }

  function answer(questionId: string, value: string) {
    const next = { ...answers, [questionId]: value };
    setAnswers(next);
    const nextStep = step + 1;
    setStep(nextStep);
    track("diagnosis_answer", { symptom: symptom ?? "none", question: questionId, answer: value, step: nextStep });
    if (symptom && runDiagnosis(symptom, next)) {
      const done = runDiagnosis(symptom, next);
      track("diagnosis_complete", { symptom, cause: done?.cause ?? "none" });
    }
  }

  function reset() {
    setSymptom(null);
    setAnswers({});
    setStep(0);
  }

  const problemSlug = symptom ? SYMPTOM_TO_PROBLEM[symptom] : undefined;
  const bookingHref =
    symptom && outcome
      ? `/booking?${new URLSearchParams({
          problem: symptom,
          diag: encodeHandoff({ symptom, cause: outcome.cause, answers }),
        }).toString()}`
      : `/booking?problem=${symptom ?? "other"}`;

  return (
    <div className="detective" data-compact={compact || undefined}>
      <div className="detective__stage" aria-live="polite">
        <TvStage
          label={sym ? `تلویزیون سه‌بعدی: ${sym.label}` : "تلویزیون سه‌بعدی در حالت آماده‌باش"}
          screen={live.screen}
          highlight={live.highlight}
          interactive
        />
        <p className="detective__caption">
          {!symptom
            ? "مشکلت رو انتخاب کن تا تلویزیون رو بررسی کنیم."
            : isOther
              ? "برای مشکل خاص‌تر، توضیح و عکس یا ویدئو بفرست."
              : flowDone && outcome
                ? CAUSES[outcome.cause].headline
                : sym?.note}
        </p>
      </div>

      <div>
        <h3 style={{ fontSize: "1.25rem" }}>مشکل تلویزیونت چیه؟</h3>
        <ul className="symptom-grid" aria-label="انتخاب مشکل">
          {SYMPTOMS.map((s) => (
            <li key={s.id}>
              <button type="button" className="symptom" aria-pressed={symptom === s.id} onClick={() => pickSymptom(s.id)}>
                <span className="symptom__icon" aria-hidden="true">
                  <Icon name={SYMPTOM_ICON[s.id]} size={22} />
                </span>
                {s.label}
              </button>
            </li>
          ))}
        </ul>

        {symptom && isOther ? (
          <div className="result" role="status">
            <p className="result__title">مشکل خاص‌تر؟ توضیح بده.</p>
            <p className="muted">هر چه دقیق‌تر بنویسید، تکنسین سریع‌تر تشخیص می‌دهد.</p>
            <div className="btn-row" style={{ marginBlockStart: "var(--s-3)" }}>
              <Link href="/booking?problem=other" className="btn btn--primary">
                درخواست بررسی توسط تکنسین
              </Link>
              <button type="button" className="btn btn--ghost" onClick={reset}>
                شروع دوباره
              </button>
            </div>
          </div>
        ) : null}

        {symptom && !isOther && current && !flowDone ? (
          <section className="flow" aria-labelledby="flow-q">
            <div className="flow__progress" aria-hidden="true">
              {questions.map((_, i) => (
                <span key={i} data-done={i < step ? "true" : "false"} />
              ))}
            </div>
            <p className="muted" style={{ fontSize: "0.9rem", margin: 0 }}>
              سؤال {step + 1} از {questions.length}
            </p>
            <h4 id="flow-q" ref={questionRef} tabIndex={-1} className="flow__question" style={{ outline: "none" }}>
              {current.question}
            </h4>
            <p className="muted" style={{ fontSize: "0.9rem" }}>
              {current.help}
            </p>
            <div className="flow__options" role="group" aria-labelledby="flow-q">
              {current.options.map((opt) => (
                <button key={opt.value} type="button" className="btn btn--ghost" onClick={() => answer(current.id, opt.value)}>
                  {opt.label}
                </button>
              ))}
            </div>
            <button type="button" className="btn btn--ghost" style={{ alignSelf: "flex-start", fontSize: "0.9rem" }} onClick={reset}>
              انصراف و انتخاب دوباره
            </button>
          </section>
        ) : null}

        {symptom && !isOther && flowDone && outcome ? (
          <section className="result" aria-labelledby="result-title">
            {outcome.urgent ? (
              <div className="notice notice--error" role="alert" style={{ marginBlockEnd: "var(--s-3)" }}>
                <strong>{outcome.urgent}</strong>
              </div>
            ) : null}
            <p className="result__title" id="result-title">
              سرنخ احتمالی
            </p>
            <p style={{ fontSize: "1.1rem", fontWeight: 800, margin: "var(--s-2) 0" }}>{CAUSES[outcome.cause].headline}</p>
            <p>{CAUSES[outcome.cause].explanation}</p>
            <p className="result__disclaimer">{DISCLAIMER}</p>
            <p className="muted" style={{ fontSize: "0.88rem" }}>
              پیچیدگی تقریبی: {complexityLabel[CAUSES[outcome.cause].complexity]}
            </p>
            <h5 style={{ marginBlock: "var(--s-3) var(--s-2)" }}>کارهای ایمن که می‌توانی انجام بدهی</h5>
            <ul className="check-list">
              {outcome.advice.map((a, i) => (
                <li key={i}>{a}</li>
              ))}
            </ul>
            <div className="btn-row" style={{ marginBlockStart: "var(--s-4)" }}>
              <Link href={bookingHref} className="btn btn--primary" data-track="cta_click" data-track-label="diagnosis_result_booking">
                درخواست بررسی توسط تکنسین
                <Icon name="arrow" size={18} />
              </Link>
              {problemSlug ? (
                <Link href={`/problems/${problemSlug}`} className="btn btn--ghost">
                  راهنمای این مشکل
                </Link>
              ) : null}
              <button type="button" className="btn btn--ghost" onClick={reset}>
                شروع دوباره
              </button>
            </div>
            <p className="muted" style={{ fontSize: "0.88rem", marginBlockStart: "var(--s-3)" }}>
              با ادامه، پاسخ‌های این بخش همراه درخواست شما ثبت می‌شود تا لازم نباشد دوباره توضیح بدهید.
            </p>
          </section>
        ) : null}
      </div>
    </div>
  );
}

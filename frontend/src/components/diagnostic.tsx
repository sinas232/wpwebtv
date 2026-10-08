"use client";

import Link from "next/link";
import { useEffect, useRef, useState } from "react";
import { TvStage } from "@/components/tv/TvStage";
import { track } from "@/lib/analytics";
import {
  DISCLAIMER,
  FLOW_STEPS,
  SYMPTOMS,
  evaluateFlow,
  getSymptom,
  type Candidate,
  type FlowAnswers,
  type PartId,
  type ScreenMode,
  type SymptomId,
} from "@/lib/diagnosis";
import { complexityLabel } from "@/lib/format";
import { Icon } from "@/components/ui";

/** How the TV reacts to each likely cause. */
const CAUSE_VISUAL: Record<Candidate, { screen: ScreenMode; highlight: PartId[] }> = {
  powerboard: { screen: "idle", highlight: ["powerboard"] },
  sound_path: { screen: "fluid", highlight: ["speakers"] },
  backlight: { screen: "dim", highlight: ["backlight"] },
  panel_or_tcon: { screen: "black", highlight: ["display", "tcon"] },
  mainboard_or_intermittent: { screen: "fluid", highlight: ["mainboard"] },
  undetermined: { screen: "fluid", highlight: [] },
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
  const flowDone = step >= FLOW_STEPS.length;

  // Live visual: starts from the chosen symptom, then follows the evidence so far.
  const result = evaluateFlow(answers);
  const live = !symptom
    ? { screen: "idle" as ScreenMode, highlight: [] as PartId[] }
    : sym && Object.keys(answers).length === 0
      ? { screen: sym.screen, highlight: sym.highlight }
      : CAUSE_VISUAL[result.candidate];

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

  function answer(key: keyof FlowAnswers, value: string) {
    const next = { ...answers, [key]: value } as FlowAnswers;
    setAnswers(next);
    const nextStep = step + 1;
    setStep(nextStep);
    track("diagnosis_symptom", { step: nextStep, answer: value });
    if (nextStep === FLOW_STEPS.length) {
      track("diagnosis_complete", { candidate: evaluateFlow(next).candidate });
    }
  }

  function reset() {
    setSymptom(null);
    setAnswers({});
    setStep(0);
  }

  const problemSlug = symptom ? SYMPTOM_TO_PROBLEM[symptom] : undefined;
  const bookingHref = `/booking?${new URLSearchParams({
    ...(symptom ? { problem: symptom } : {}),
    ...(result.part ? { part: result.part } : {}),
  }).toString()}`;

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
                  {s.icon}
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
              <Link href="/booking?problem=other" className="btn btn--primary">درخواست بررسی توسط تکنسین</Link>
              <button type="button" className="btn btn--ghost" onClick={reset}>شروع دوباره</button>
            </div>
          </div>
        ) : null}

        {symptom && !isOther && !flowDone ? (
          <section className="flow" aria-labelledby="flow-q">
            <div className="flow__progress" aria-hidden="true">
              {FLOW_STEPS.map((_, i) => (
                <span key={i} data-done={i < step ? "true" : "false"} />
              ))}
            </div>
            <p className="muted" style={{ fontSize: "0.9rem", margin: 0 }}>
              {step < 3 ? `سرنخ ${step + 1} از ۳` : "تأیید نهایی"}
            </p>
            <h4 id="flow-q" ref={questionRef} tabIndex={-1} className="flow__question" style={{ outline: "none" }}>
              {FLOW_STEPS[step].question}
            </h4>
            <p className="muted" style={{ fontSize: "0.9rem" }}>{FLOW_STEPS[step].help}</p>
            <div className="flow__options" role="group" aria-labelledby="flow-q">
              {FLOW_STEPS[step].options.map((opt) => (
                <button
                  key={opt.value}
                  type="button"
                  className="btn btn--ghost"
                  onClick={() => answer(FLOW_STEPS[step].id, opt.value)}
                >
                  {opt.label}
                </button>
              ))}
            </div>
          </section>
        ) : null}

        {symptom && !isOther && flowDone ? (
          <section className="result" aria-labelledby="result-title">
            <p className="result__title" id="result-title">🎯 سرنخش رو پیدا کردیم</p>
            <p style={{ fontSize: "1.1rem", fontWeight: 800, margin: "var(--s-2) 0" }}>{result.headline}</p>
            <p>{result.explanation}</p>
            <p className="result__disclaimer">{DISCLAIMER}</p>
            <p className="muted" style={{ fontSize: "0.88rem" }}>
              پیچیدگی تقریبی: {complexityLabel[result.complexity]}
              {answers.persistent === "no" ? "، مشکل گاهی رخ می‌دهد؛ زمان دقیقش را در درخواست بنویسید." : ""}
            </p>
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
          </section>
        ) : null}
      </div>
    </div>
  );
}

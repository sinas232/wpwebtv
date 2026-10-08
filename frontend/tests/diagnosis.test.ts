import { test } from "node:test";
import assert from "node:assert/strict";
import {
  CAUSES,
  CAUSE_IDS,
  DISCLAIMER,
  SYMPTOMS,
  decodeHandoff,
  encodeHandoff,
  handoffSummary,
  questionsFor,
  runDiagnosis,
  type SymptomId,
} from "../src/lib/diagnosis.ts";

const SYMPTOM_IDS = SYMPTOMS.map((s) => s.id);
const FLOW_SYMPTOMS = SYMPTOM_IDS.filter((id): id is Exclude<SymptomId, "other"> => id !== "other");

/** Every combination of answers for a symptom's questions. */
function allAnswerSets(symptom: SymptomId): Record<string, string>[] {
  const qs = questionsFor(symptom);
  let sets: Record<string, string>[] = [{}];
  for (const q of qs) {
    const next: Record<string, string>[] = [];
    for (const s of sets) for (const o of q.options) next.push({ ...s, [q.id]: o.value });
    sets = next;
  }
  return sets;
}

test("every symptom except 'other' has 2 to 3 questions, each with unique options", () => {
  for (const id of FLOW_SYMPTOMS) {
    const qs = questionsFor(id);
    assert.ok(qs.length >= 2 && qs.length <= 3, `${id} question count`);
    for (const q of qs) {
      assert.ok(q.options.length >= 2, `${id}.${q.id} needs options`);
      const values = q.options.map((o) => o.value);
      assert.equal(new Set(values).size, values.length, `${id}.${q.id} duplicate values`);
    }
  }
});

test("incomplete answers never produce an outcome", () => {
  for (const id of FLOW_SYMPTOMS) {
    assert.equal(runDiagnosis(id, {}), null, `${id} with no answers`);
  }
});

test("every answer combination for every symptom yields a valid, hedged outcome", () => {
  for (const id of FLOW_SYMPTOMS) {
    for (const answers of allAnswerSets(id)) {
      const out = runDiagnosis(id, answers);
      assert.ok(out, `${id} ${JSON.stringify(answers)} should complete`);
      assert.ok((CAUSE_IDS as readonly string[]).includes(out.cause), "known cause");
      const info = CAUSES[out.cause];
      assert.ok(info.headline.length > 0 && info.explanation.length > 0);
      // Never presented as confirmed.
      assert.ok(!/قطعی است|قطعاً خراب|حتماً خراب/.test(info.headline + info.explanation), `hedged wording for ${out.cause}`);
      assert.ok(out.advice.length >= 2, "at least two safe advice items");
    }
  }
});

test("safe advice never tells the customer to open the device", () => {
  for (const id of FLOW_SYMPTOMS) {
    for (const answers of allAnswerSets(id)) {
      const out = runDiagnosis(id, answers)!;
      for (const line of out.advice) {
        if (/باز کن|باز کنید|درِ|پیچ‌ها را/.test(line)) {
          assert.ok(/نکن|نکنید/.test(line), `unsafe advice: ${line}`);
        }
      }
    }
  }
});

test("smoke or burning smell during reboot triggers an urgent safety banner", () => {
  const out = runDiagnosis("auto_reboot", { standby: "yes", smell: "yes" })!;
  assert.ok(out.urgent && out.urgent.includes("جدا کنید"));
  const calm = runDiagnosis("auto_reboot", { standby: "yes", smell: "no" })!;
  assert.equal(calm.urgent, undefined);
});

test("known routing: no sound with no picture points to mainboard", () => {
  assert.equal(runDiagnosis("no_picture", { sound: "no", faint: "yes" })!.cause, "mainboard");
});

test("known routing: sound present and faint picture points to backlight", () => {
  assert.equal(runDiagnosis("no_picture", { sound: "yes", faint: "yes" })!.cause, "backlight");
  assert.equal(runDiagnosis("dark_screen", { standby: "yes", faint: "yes" })!.cause, "backlight");
});

test("known routing: no standby light points to power supply", () => {
  assert.equal(runDiagnosis("dark_screen", { standby: "no", faint: "yes" })!.cause, "power_supply");
  assert.equal(runDiagnosis("no_power", { standby: "no", outlet: "no" })!.cause, "power_supply");
});

test("known routing: HDMI tests run cable first, then port, then board", () => {
  assert.equal(runDiagnosis("hdmi", { cable: "no", port: "no" })!.cause, "hdmi_cable");
  assert.equal(runDiagnosis("hdmi", { cable: "yes", port: "no" })!.cause, "hdmi_port");
  assert.equal(runDiagnosis("hdmi", { cable: "yes", port: "yes" })!.cause, "hdmi_board");
});

test("known routing: Wi-Fi scope and network list", () => {
  assert.equal(runDiagnosis("smart_wifi", { scope: "apps", networks: "yes" })!.cause, "software");
  assert.equal(runDiagnosis("smart_wifi", { scope: "system", networks: "no" })!.cause, "wifi_module");
});

test("'other' goes straight to an undetermined outcome with photo guidance", () => {
  const out = runDiagnosis("other", {})!;
  assert.equal(out.cause, "undetermined");
  assert.ok(out.advice.some((a) => a.includes("عکس")));
});

test("disclaimer names the technician requirement", () => {
  assert.ok(DISCLAIMER.includes("نیاز به بررسی تکنسین"));
});

test("handoff round-trips without loss", () => {
  const h = { symptom: "hdmi" as const, cause: "hdmi_port" as const, answers: { cable: "yes", port: "no" } };
  const encoded = encodeHandoff(h);
  assert.match(encoded, /^[A-Za-z0-9_-]+$/, "URL-safe characters only");
  assert.deepEqual(decodeHandoff(encoded), h);
  assert.ok(handoffSummary(h).includes("پورت HDMI"));
});

test("handoff rejects tampered or unexpected payloads", () => {
  const bad = (obj: unknown) => Buffer.from(JSON.stringify(obj), "utf8").toString("base64url");
  assert.equal(decodeHandoff(null), null);
  assert.equal(decodeHandoff(""), null);
  assert.equal(decodeHandoff("!!!not-base64!!!"), null);
  assert.equal(decodeHandoff("x".repeat(700)), null, "oversized");
  assert.equal(decodeHandoff(bad({ s: "evil", c: "hdmi_port", a: {} })), null, "unknown symptom");
  assert.equal(decodeHandoff(bad({ s: "hdmi", c: "rm_rf", a: {} })), null, "unknown cause");
  assert.equal(decodeHandoff(bad({ s: "hdmi", c: "hdmi_port", a: { nope: "yes" } })), null, "unknown question");
  assert.equal(decodeHandoff(bad({ s: "hdmi", c: "hdmi_port", a: { cable: "maybe" } })), null, "unknown answer");
  assert.equal(decodeHandoff(bad({ s: "hdmi", c: "hdmi_port", a: { cable: 5 } })), null, "non-string answer");
});

test("regression: the booking form's JSON shape validates (symptom/cause/answers)", async () => {
  const { validateHandoff } = await import("../src/lib/diagnosis.ts");
  const full = { symptom: "hdmi", cause: "hdmi_port", answers: { cable: "yes", port: "no" } };
  assert.deepEqual(validateHandoff(full), full);
  assert.equal(validateHandoff({ symptom: "hdmi", cause: "hdmi_port", answers: { cable: "maybe" } }), null);
});

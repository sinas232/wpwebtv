import { test } from "node:test";
import assert from "node:assert/strict";
import { DISCLAIMER, evaluateFlow, FLOW_STEPS, SYMPTOMS } from "../src/lib/diagnosis.ts";
import {
  isTrackingCode,
  makeTrackingCode,
  normalizeDigits,
  normalizeMobile,
  validateLeadFields,
  validateMedia,
} from "../src/lib/leads.ts";

test("flow: standby off points to power board, always with technician recommendation", () => {
  const r = evaluateFlow({ standby: "no" });
  assert.equal(r.candidate, "powerboard");
  assert.equal(r.part, "powerboard");
  assert.equal(r.technicianRecommended, true);
});

test("flow: dim picture with sound points to backlight and uses hedged wording", () => {
  const r = evaluateFlow({ standby: "yes", sound: "yes", picture: "dim" });
  assert.equal(r.candidate, "backlight");
  assert.match(r.headline, /احتمال/);
  assert.doesNotMatch(r.explanation, /قطعاً|حتماً/);
});

test("flow: black picture with sound points to panel or T-Con", () => {
  assert.equal(evaluateFlow({ standby: "yes", sound: "yes", picture: "black" }).candidate, "panel_or_tcon");
});

test("flow: no sound is handled before picture checks", () => {
  assert.equal(evaluateFlow({ standby: "yes", sound: "no", picture: "dim" }).candidate, "sound_path");
});

test("flow: incomplete answers do not produce a confident diagnosis", () => {
  assert.equal(evaluateFlow({}).candidate, "undetermined");
});

test("every symptom has a visual state and a hedged note", () => {
  assert.equal(SYMPTOMS.length, 8);
  for (const s of SYMPTOMS) {
    assert.ok(s.label.length > 0);
    assert.ok(s.note.length > 0);
  }
  assert.ok(DISCLAIMER.includes("نیاز به بررسی تکنسین"));
});

test("flow has the four steps from the brief", () => {
  assert.deepEqual(
    FLOW_STEPS.map((s) => s.id),
    ["standby", "sound", "picture", "persistent"],
  );
});

test("Persian and Arabic digits normalise to ASCII", () => {
  assert.equal(normalizeDigits("۰۹۱۲۳۴۵۶۷۸۹"), "09123456789");
  assert.equal(normalizeDigits("٠٩١٢"), "0912");
});

test("mobile: accepts Persian digits, separators and +98 prefix", () => {
  assert.equal(normalizeMobile("۰۹۱۲ ۳۴۵ ۶۷۸۹"), "09123456789");
  assert.equal(normalizeMobile("+98 912-345-6789"), "09123456789");
  assert.equal(normalizeMobile("0212345678"), null);
  assert.equal(normalizeMobile("123"), null);
});

const validBooking = {
  problem: "no_picture",
  brand: "سامسونگ",
  city: "تهران",
  area: "سعادت‌آباد",
  address: "تهران، سعادت‌آباد، خیابان نمونه ۱۲",
  size: "۵۰ تا ۵۵ اینچ",
  preferredTime: "فردا صبح",
  name: "علی",
  mobile: "۰۹۱۲۳۴۵۶۷۸۹",
};

test("lead: a complete booking validates and normalises the mobile", () => {
  const r = validateLeadFields(validBooking);
  assert.equal(r.ok, true);
  if (r.ok) assert.equal(r.value.mobile, "09123456789");
});

test("lead: missing required fields produce Persian per-field errors", () => {
  const r = validateLeadFields({ ...validBooking, mobile: "123", name: "" });
  assert.equal(r.ok, false);
  if (!r.ok) {
    assert.ok(r.errors.mobile);
    assert.ok(r.errors.name);
  }
});

test("lead: 'other problem' requires a description", () => {
  const r = validateLeadFields({ ...validBooking, problem: "other", description: "" });
  assert.equal(r.ok, false);
  if (!r.ok) assert.ok(r.errors.description);
});

test("lead: unknown brand is rejected", () => {
  const r = validateLeadFields({ ...validBooking, brand: "ناشناخته" });
  assert.equal(r.ok, false);
});

test("media: limits per type and total are enforced", () => {
  assert.equal(validateMedia([{ kind: "front_photo", name: "a.jpg", size: 1000, mime: "image/jpeg" }]).ok, true);
  assert.equal(validateMedia([{ kind: "front_photo", name: "a.pdf", size: 1000, mime: "application/pdf" }]).ok, false);
  assert.equal(validateMedia([{ kind: "problem_video", name: "v.mp4", size: 60 * 1024 * 1024, mime: "video/mp4" }]).ok, false);
  const seven = Array.from({ length: 7 }, () => ({ kind: "problem_photo", name: "p.jpg", size: 1, mime: "image/jpeg" }));
  assert.equal(validateMedia(seven).ok, false);
});

test("tracking code: format and alphabet (no 0/O/1/I)", () => {
  for (let i = 0; i < 50; i++) {
    const code = makeTrackingCode();
    assert.ok(isTrackingCode(code), code);
    assert.doesNotMatch(code, /[01IO]/);
  }
  assert.equal(isTrackingCode("tv-abcdef"), true);
  assert.equal(isTrackingCode("TV-ABC"), false);
});

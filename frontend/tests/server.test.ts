import { test } from "node:test";
import assert from "node:assert/strict";
import { mkdtemp, rm, readFile } from "node:fs/promises";
import { tmpdir } from "node:os";
import path from "node:path";
import { createHmac } from "node:crypto";
import { loadConfig, assertStorageConfigured, ConfigError } from "../src/lib/server/config.ts";
import { detectType, validateSignature } from "../src/lib/server/file-signature.ts";
import { deliverLeadWebhook, signPayload } from "../src/lib/server/crm-webhook.ts";
import { FileLeadRepository } from "../src/lib/server/lead-repository.ts";
import { objectKey, safeFileSuffix } from "../src/lib/server/media-storage.ts";
import { isLimited } from "../src/lib/server/rate-limit.ts";
import { redact } from "../src/lib/server/log.ts";
import { NullSmsProvider, SMS_TEMPLATES } from "../src/lib/server/sms.ts";
import type { Lead } from "../src/lib/leads.ts";

const bytesOf = (...xs: number[]) => Uint8Array.from(xs);
const ascii = (s: string) => Uint8Array.from(Buffer.from(s, "latin1"));
const JPEG = new Uint8Array([0xff, 0xd8, 0xff, 0xe0, 0, 0x10, 0x4a, 0x46, 0x49, 0x46, 0, 1]);
const PNG = new Uint8Array([0x89, 0x50, 0x4e, 0x47, 0x0d, 0x0a, 0x1a, 0x0a, 0, 0, 0, 0x0d]);
const WEBP = new Uint8Array([...ascii("RIFF"), 0, 0, 0, 0, ...ascii("WEBP")]);
const HEIC = new Uint8Array([0, 0, 0, 0x18, ...ascii("ftyp"), ...ascii("heic")]);
const MP4 = new Uint8Array([0, 0, 0, 0x18, ...ascii("ftyp"), ...ascii("isom")]);
const MOV = new Uint8Array([0, 0, 0, 0x14, ...ascii("ftyp"), ...ascii("qt  ")]);
const WEBM = new Uint8Array([0x1a, 0x45, 0xdf, 0xa3, 0, 0, 0, 0, 0, 0, 0, 0]);

test("file signatures: real photos are detected regardless of claimed MIME", () => {
  assert.equal(detectType(JPEG), "jpeg");
  assert.equal(detectType(PNG), "png");
  assert.equal(detectType(WEBP), "webp");
  assert.equal(detectType(HEIC), "heic");
});

test("file signatures: video containers are detected", () => {
  assert.equal(detectType(MP4), "mp4");
  assert.equal(detectType(MOV), "mov");
  assert.equal(detectType(WEBM), "webm");
});

test("file signatures: an executable or text file renamed to .jpg is rejected", () => {
  const exe = new TextEncoder().encode("MZ\x90\x00 this is not an image at all");
  const r = validateSignature(exe, "photo");
  assert.equal(r.ok, false);
});

test("file signatures: a video uploaded as a photo (and vice versa) is rejected", () => {
  assert.equal(validateSignature(MP4, "photo").ok, false);
  assert.equal(validateSignature(JPEG, "video").ok, false);
  assert.equal(validateSignature(WEBM, "video").ok, true);
});

test("file signatures: short buffers are rejected without throwing", () => {
  assert.equal(detectType(bytesOf(0xff, 0xd8)), null);
  assert.equal(validateSignature(new Uint8Array(0), "photo").ok, false);
});

test("object keys: traversal and odd names are sanitised; lead id must be a UUID", () => {
  assert.equal(safeFileSuffix("../../etc/passwd"), "_.._etc_passwd");
  assert.ok(!safeFileSuffix("../../etc/passwd").includes("/"), "no path separators survive");
  assert.ok(!safeFileSuffix("a b/c\\d.jpg").includes("/"));
  assert.equal(objectKey("uploads", "123e4567-e89b-42d3-a456-426614174000", 2, "my photo.jpg"), "uploads/123e4567-e89b-42d3-a456-426614174000/2-my_photo.jpg");
  assert.throws(() => objectKey("uploads", "../evil", 0, "x.jpg"));
});

test("config: production refuses file lead storage unless explicitly allowed", () => {
  const cfg = loadConfig({ NODE_ENV: "production", MEDIA_STORAGE: "s3", S3_BUCKET: "b", S3_REGION: "r", S3_ACCESS_KEY_ID: "k", S3_SECRET_ACCESS_KEY: "s" });
  assert.throws(() => assertStorageConfigured(cfg), ConfigError);
  const allowed = loadConfig({ NODE_ENV: "production", ALLOW_LOCAL_STORAGE_IN_PRODUCTION: "1", MEDIA_STORAGE: "local" });
  assert.doesNotThrow(() => assertStorageConfigured(allowed));
});

test("config: production refuses local media unless explicitly allowed", () => {
  const cfg = loadConfig({ NODE_ENV: "production", ALLOW_LOCAL_STORAGE_IN_PRODUCTION: "0", LEAD_STORE: "file", MEDIA_STORAGE: "local" });
  assert.throws(() => assertStorageConfigured(cfg), /Production requires/);
  const s3Lead = loadConfig({ NODE_ENV: "production", ALLOW_LOCAL_STORAGE_IN_PRODUCTION: "0", MEDIA_STORAGE: "local" });
  assert.throws(() => assertStorageConfigured(s3Lead), /ALLOW_LOCAL_STORAGE_IN_PRODUCTION/);
});

test("config: S3 without credentials fails loudly, never silently", () => {
  assert.throws(() => assertStorageConfigured(loadConfig({ MEDIA_STORAGE: "s3" })), /S3_BUCKET/);
});

test("config: postgres is declared but not implemented, and says so", () => {
  assert.throws(() => assertStorageConfigured(loadConfig({ LEAD_STORE: "postgres" })), /not implemented/);
});

test("config: CRM timeouts and attempts are clamped to safe ranges", () => {
  const cfg = loadConfig({ CRM_WEBHOOK_TIMEOUT_MS: "999999", CRM_WEBHOOK_MAX_ATTEMPTS: "0" });
  assert.equal(cfg.crm.timeoutMs, 5000, "out of range falls back to default");
  assert.equal(cfg.crm.maxAttempts, 3);
});

test("webhook: no URL configured means silent skip, no network call", async () => {
  let called = false;
  const out = await deliverLeadWebhook({ id: "x", trackingCode: "TV-AAAAAA", payload: {} }, loadConfig({}), {
    fetchImpl: async () => {
      called = true;
      return new Response(null, { status: 200 });
    },
  });
  assert.deepEqual(out, { status: "skipped", attempts: 0 });
  assert.equal(called, false);
});

test("webhook: production refuses plain HTTP", async () => {
  const cfg = loadConfig({ NODE_ENV: "production", CRM_WEBHOOK_URL: "http://crm.example/hook", CRM_WEBHOOK_SECRET: "s", ALLOW_LOCAL_STORAGE_IN_PRODUCTION: "1" });
  const out = await deliverLeadWebhook({ id: "x", trackingCode: "TV-AAAAAA", payload: {} }, cfg, { fetchImpl: async () => new Response(null, { status: 200 }) });
  assert.equal(out.status, "failed");
  assert.equal(out.attempts, 0);
});

test("webhook: signed request, idempotency key, and verifiable HMAC", async () => {
  const cfg = loadConfig({ CRM_WEBHOOK_URL: "https://crm.example/hook", CRM_WEBHOOK_SECRET: "topsecret" });
  const captured: { headers: Record<string, string>; body: string }[] = [];
  const out = await deliverLeadWebhook({ id: "lead-1", trackingCode: "TV-AAAAAA", payload: { a: 1 } }, cfg, {
    now: () => 1_700_000_000,
    fetchImpl: async (_url, init) => {
      captured.push({ headers: init!.headers as Record<string, string>, body: String(init!.body) });
      return new Response(null, { status: 200 });
    },
  });
  assert.equal(out.status, "sent");
  assert.equal(captured.length, 1);
  const seen = captured[0];
  const h = seen.headers;
  assert.equal(h["idempotency-key"], "lead-1");
  assert.equal(h["x-tv-timestamp"], "1700000000");
  const expected = `sha256=${createHmac("sha256", "topsecret").update(`1700000000.${seen.body}`).digest("hex")}`;
  assert.equal(h["x-tv-signature"], expected);
  assert.equal(signPayload("topsecret", 1_700_000_000, seen.body), expected);
});

test("webhook: retries 5xx and network errors, then gives up without throwing", async () => {
  const cfg = loadConfig({ CRM_WEBHOOK_URL: "https://crm.example/hook", CRM_WEBHOOK_SECRET: "s", CRM_WEBHOOK_MAX_ATTEMPTS: "3" });
  let calls = 0;
  const sleeps: number[] = [];
  const out = await deliverLeadWebhook({ id: "x", trackingCode: "TV-AAAAAA", payload: {} }, cfg, {
    sleep: async (ms) => void sleeps.push(ms),
    fetchImpl: async () => {
      calls += 1;
      if (calls === 1) throw new TypeError("network down");
      return new Response(null, { status: 503 });
    },
  });
  assert.equal(out.status, "failed");
  assert.equal(out.attempts, 3);
  assert.equal(calls, 3);
  assert.deepEqual(sleeps, [250, 500]);
});

test("webhook: 4xx is not retried", async () => {
  const cfg = loadConfig({ CRM_WEBHOOK_URL: "https://crm.example/hook", CRM_WEBHOOK_SECRET: "s" });
  let calls = 0;
  const out = await deliverLeadWebhook({ id: "x", trackingCode: "TV-AAAAAA", payload: {} }, cfg, {
    sleep: async () => {},
    fetchImpl: async () => {
      calls += 1;
      return new Response(null, { status: 400 });
    },
  });
  assert.equal(out.status, "failed");
  assert.equal(calls, 1);
});

test("webhook: a 429 is retried, then success is reported", async () => {
  const cfg = loadConfig({ CRM_WEBHOOK_URL: "https://crm.example/hook", CRM_WEBHOOK_SECRET: "s" });
  let calls = 0;
  const out = await deliverLeadWebhook({ id: "x", trackingCode: "TV-AAAAAA", payload: {} }, cfg, {
    sleep: async () => {},
    fetchImpl: async () => {
      calls += 1;
      return new Response(null, { status: calls === 1 ? 429 : 200 });
    },
  });
  assert.equal(out.status, "sent");
  assert.equal(out.attempts, 2);
});

test("repository: save and find by code, latest record wins, corrupt lines are skipped", async () => {
  const dir = await mkdtemp(path.join(tmpdir(), "tvd-repo-"));
  try {
    const repo = new FileLeadRepository(dir);
    const base = { id: "a", trackingCode: "TV-ABC234", mobile: "09123456789", status: "received" } as unknown as Lead;
    await repo.save(base);
    await repo.save({ ...base, status: "reviewing" } as Lead);
    const { appendFile } = await import("node:fs/promises");
    await appendFile(path.join(dir, "leads.jsonl"), "{not json\n");
    const found = await repo.findByCode("tv-abc234");
    assert.equal(found?.status, "reviewing");
    assert.equal(await repo.findByCode("TV-ZZZZZZ"), undefined);
    const raw = await readFile(path.join(dir, "leads.jsonl"), "utf8");
    assert.ok(raw.includes("reviewing"));
  } finally {
    await rm(dir, { recursive: true, force: true });
  }
});

test("rate limit: blocks after the limit within the window and recovers after it", () => {
  const t0 = 1_000_000;
  for (let i = 0; i < 3; i++) assert.equal(isLimited("t", "ip1", 3, 1000, t0 + i), false);
  assert.equal(isLimited("t", "ip1", 3, 1000, t0 + 10), true);
  assert.equal(isLimited("t", "ip2", 3, 1000, t0 + 10), false, "other clients unaffected");
  assert.equal(isLimited("t", "ip1", 3, 1000, t0 + 5000), false, "window expired");
});

test("log redaction: personal and secret fields never appear in logs", () => {
  const out = redact({ leadId: "x", trackingCode: "TV-AAAAAA", name: "علی", mobile: "09123456789", address: "خیابان", secret: "s", httpStatus: 200 });
  assert.equal(out.name, "[redacted]");
  assert.equal(out.mobile, "[redacted]");
  assert.equal(out.address, "[redacted]");
  assert.equal(out.secret, "[redacted]");
  assert.equal(out.leadId, "x");
  assert.equal(out.httpStatus, 200);
});

test("SMS: default provider never claims to have sent anything", async () => {
  const r = await new NullSmsProvider().send({ useCase: "lead_received", to: "09123456789", text: SMS_TEMPLATES.lead_received("TV-AAAAAA") });
  assert.equal(r.status, "not_configured");
  assert.ok(SMS_TEMPLATES.lead_received("TV-AAAAAA").includes("TV-AAAAAA"));
});

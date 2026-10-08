import { after, NextResponse } from "next/server";
import { makeTrackingCode, validateLeadFields, validateMedia, type Lead, type LeadMedia, type MediaKind } from "@/lib/leads";
import { loadConfig, ConfigError } from "@/lib/server/config";
import { deliverLeadWebhook } from "@/lib/server/crm-webhook";
import { createLeadRepository } from "@/lib/server/lead-repository";
import { createMediaStorage, safeFileSuffix } from "@/lib/server/media-storage";
import { validateSignature, type MediaCategory } from "@/lib/server/file-signature";
import { log } from "@/lib/server/log";
import { isLimited, clientIp } from "@/lib/server/rate-limit";
import { createSmsProvider, SMS_TEMPLATES } from "@/lib/server/sms";
import { appendEvent } from "@/lib/server/store";

export const runtime = "nodejs";
export const dynamic = "force-dynamic";

const WINDOW_MS = 10 * 60 * 1000;
const LIMIT = 6;

const SERVICE_UNAVAILABLE =
  "ثبت آنلاین درخواست در این لحظه در دسترس نیست. لطفاً با شماره‌ی تماس یا از طریق صفحه‌ی تماس اقدام کنید.";
const INTERNAL_ERROR = "خطایی در ثبت درخواست رخ داد. دوباره تلاش کنید یا با ما تماس بگیرید.";

export async function POST(req: Request) {
  try {
    const cfg = loadConfig();
    if (isLimited("leads", clientIp(req, cfg.trustProxy), LIMIT, WINDOW_MS)) {
      return NextResponse.json({ ok: false, error: "تعداد درخواست‌ها زیاد است. کمی بعد دوباره تلاش کنید." }, { status: 429 });
    }

    let form: FormData;
    try {
      form = await req.formData();
    } catch {
      return NextResponse.json({ ok: false, error: "درخواست نامعتبر است." }, { status: 400 });
    }

    // Honeypot: real users never fill this hidden field. Pretend success, store nothing.
    if (String(form.get("website") ?? "") !== "") {
      return NextResponse.json({ ok: true, trackingCode: makeTrackingCode() }, { status: 201 });
    }

    const raw: Record<string, unknown> = {};
    for (const [key, value] of form.entries()) {
      if (typeof value === "string") raw[key] = value;
    }

    const fields = validateLeadFields(raw);
    if (!fields.ok) {
      return NextResponse.json({ ok: false, errors: fields.errors }, { status: 422 });
    }

    const files = form.getAll("media").filter((v): v is File => v instanceof File);
    const kinds = form.getAll("mediaKind").map(String);
    if (kinds.length !== files.length) {
      return NextResponse.json({ ok: false, error: "نوع هر فایل مشخص نشده است." }, { status: 422 });
    }
    const meta = files.map((f, i) => ({ kind: kinds[i], name: f.name, size: f.size, mime: f.type }));
    const metaCheck = validateMedia(meta);
    if (!metaCheck.ok) {
      return NextResponse.json({ ok: false, error: metaCheck.error }, { status: 422 });
    }

    // Read bytes once and verify each file's real signature before anything is stored.
    const bytesList: Uint8Array[] = [];
    for (let i = 0; i < files.length; i++) {
      const bytes = new Uint8Array(await files[i].arrayBuffer());
      if (bytes.byteLength === 0) {
        return NextResponse.json({ ok: false, error: "یکی از فایل‌ها خالی است." }, { status: 422 });
      }
      const category: MediaCategory = kinds[i] === "problem_video" ? "video" : "photo";
      const sig = validateSignature(bytes, category);
      if (!sig.ok) {
        return NextResponse.json({ ok: false, error: sig.error }, { status: 422 });
      }
      bytesList.push(bytes);
    }

    // Storage is checked before any upload so a misconfigured server never accepts files it cannot keep.
    let repo: ReturnType<typeof createLeadRepository>;
    let media: ReturnType<typeof createMediaStorage>;
    try {
      repo = createLeadRepository(cfg);
      media = createMediaStorage(cfg);
    } catch (err) {
      if (err instanceof ConfigError) {
        log("error", "lead_storage_not_ready", { reason: err.message });
        return NextResponse.json({ ok: false, error: SERVICE_UNAVAILABLE }, { status: 503 });
      }
      throw err;
    }

    const id = crypto.randomUUID();
    const trackingCode = makeTrackingCode();
    const storedMedia: LeadMedia[] = [];
    for (let i = 0; i < files.length; i++) {
      const stored = await media.put({
        leadId: id,
        index: i,
        originalName: files[i].name,
        contentType: files[i].type,
        bytes: bytesList[i],
      });
      storedMedia.push({
        kind: kinds[i] as MediaKind,
        name: safeFileSuffix(files[i].name),
        size: files[i].size,
        mime: files[i].type,
        storedAs: stored.key,
      });
    }

    const lead: Lead = {
      id,
      trackingCode,
      createdAt: new Date().toISOString(),
      status: "received",
      ...fields.value,
      media: storedMedia,
      technician: { assignedTo: null, visitAt: null },
      repair: { report: null, parts: [], invoiceId: null },
    };

    await repo.save(lead);

    // Notifications run after the response: the customer never waits on the CRM or SMS.
    after(async () => {
      try {
        await appendEvent({ name: "lead_created", trackingCode, problem: lead.problem, city: lead.city, brand: lead.brand, hasDiagnosis: Boolean(lead.diagnosis) });
        const [crm, sms] = await Promise.all([
          deliverLeadWebhook({ id, trackingCode, payload: lead }, cfg),
          createSmsProvider().send({ useCase: "lead_received", to: lead.mobile, text: SMS_TEMPLATES.lead_received(trackingCode) }),
        ]);
        await appendEvent({ name: "lead_notifications", trackingCode, crm: crm.status, sms: sms.status });
      } catch {
        log("error", "lead_notification_failed", { trackingCode });
      }
    });

    return NextResponse.json({ ok: true, trackingCode }, { status: 201 });
  } catch (err) {
    // Log the error class only: messages can contain personal data.
    log("error", "lead_request_failed", { errorName: err instanceof Error ? err.name : "unknown" });
    return NextResponse.json({ ok: false, error: INTERNAL_ERROR }, { status: 500 });
  }
}

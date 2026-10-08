import { NextResponse } from "next/server";
import {
  makeTrackingCode,
  validateLeadFields,
  validateMedia,
  type Lead,
  type LeadMedia,
  type MediaKind,
} from "@/lib/leads";
import { appendEvent, forwardLead, saveLead, saveUpload } from "@/lib/server/store";

export const runtime = "nodejs";
export const dynamic = "force-dynamic";

/** In-memory limiter: 6 submissions per IP per 10 minutes. Replace with a shared store for multi-instance deploys. */
const WINDOW_MS = 10 * 60 * 1000;
const LIMIT = 6;
const hits = new Map<string, number[]>();

function rateLimited(ip: string): boolean {
  const now = Date.now();
  const recent = (hits.get(ip) ?? []).filter((t) => now - t < WINDOW_MS);
  recent.push(now);
  hits.set(ip, recent);
  return recent.length > LIMIT;
}

function clientIp(req: Request): string {
  return req.headers.get("x-forwarded-for")?.split(",")[0].trim() || "unknown";
}

export async function POST(req: Request) {
  if (rateLimited(clientIp(req))) {
    return NextResponse.json({ ok: false, error: "تعداد درخواست‌ها زیاد است. کمی بعد دوباره تلاش کنید." }, { status: 429 });
  }

  let form: FormData;
  try {
    form = await req.formData();
  } catch {
    return NextResponse.json({ ok: false, error: "درخواست نامعتبر است." }, { status: 400 });
  }

  // Honeypot: real users never fill this hidden field.
  if (String(form.get("website") ?? "") !== "") {
    return NextResponse.json({ ok: true, trackingCode: makeTrackingCode() });
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
  const mediaCheck = validateMedia(meta);
  if (!mediaCheck.ok) {
    return NextResponse.json({ ok: false, error: mediaCheck.error }, { status: 422 });
  }

  const id = crypto.randomUUID();
  const trackingCode = makeTrackingCode();
  const media: LeadMedia[] = [];
  for (let i = 0; i < files.length; i++) {
    const kind = kinds[i] as MediaKind;
    const storedAs = await saveUpload(id, i, files[i].name, new Uint8Array(await files[i].arrayBuffer()));
    media.push({ kind, name: files[i].name, size: files[i].size, mime: files[i].type, storedAs });
  }

  const lead: Lead = {
    id,
    trackingCode,
    createdAt: new Date().toISOString(),
    status: "received",
    ...fields.value,
    media,
    technician: { assignedTo: null, visitAt: null },
    repair: { report: null, parts: [], invoiceId: null },
  };

  await saveLead(lead);
  const forwarded = await forwardLead(lead);
  await appendEvent({ name: "lead_created", trackingCode, problem: lead.problem, city: lead.city, brand: lead.brand, forwarded });

  return NextResponse.json({ ok: true, trackingCode }, { status: 201 });
}

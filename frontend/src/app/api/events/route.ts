import { NextResponse } from "next/server";
import { appendEvent } from "@/lib/server/store";

export const runtime = "nodejs";
export const dynamic = "force-dynamic";

/** Only these event names are accepted; anything else is rejected. */
const ALLOWED = new Set([
  "cta_click",
  "phone_click",
  "whatsapp_click",
  "telegram_click",
  "form_start",
  "form_step",
  "diagnosis_start",
  "diagnosis_symptom",
  "diagnosis_answer",
  "diagnosis_complete",
  "brand_selected",
  "problem_selected",
  "city_viewed",
  "exploded_part_selected",
  "tracking_lookup",
]);

const MAX_STR = 120;

export async function POST(req: Request) {
  let body: Record<string, unknown>;
  try {
    body = (await req.json()) as Record<string, unknown>;
  } catch {
    return NextResponse.json({ ok: false }, { status: 400 });
  }
  const name = typeof body.name === "string" ? body.name : "";
  if (!ALLOWED.has(name)) return NextResponse.json({ ok: false }, { status: 422 });

  // Keep only short scalar props; drop anything else (no free-form personal data).
  const props: Record<string, string | number | boolean> = {};
  if (body.props && typeof body.props === "object") {
    for (const [k, v] of Object.entries(body.props as Record<string, unknown>).slice(0, 12)) {
      if (typeof v === "string") props[k.slice(0, 40)] = v.slice(0, MAX_STR);
      else if (typeof v === "number" || typeof v === "boolean") props[k.slice(0, 40)] = v;
    }
  }
  await appendEvent({ name, props, path: typeof body.path === "string" ? body.path.slice(0, 200) : null });
  return NextResponse.json({ ok: true });
}

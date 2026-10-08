import { NextResponse } from "next/server";
import { isTrackingCode, normalizeMobile, publicTrackingView } from "@/lib/leads";
import { findLeadByCode } from "@/lib/server/store";

export const runtime = "nodejs";
export const dynamic = "force-dynamic";

/**
 * Customer tracking. Requires BOTH the tracking code and the mobile number used
 * for the request, so a code alone cannot be used to read someone else's status.
 * The response never includes address, phone, media or technician notes.
 */
export async function POST(req: Request) {
  let body: { code?: unknown; mobile?: unknown };
  try {
    body = (await req.json()) as typeof body;
  } catch {
    return NextResponse.json({ ok: false, error: "درخواست نامعتبر است." }, { status: 400 });
  }

  const code = typeof body.code === "string" ? body.code.trim().toUpperCase() : "";
  const mobile = typeof body.mobile === "string" ? normalizeMobile(body.mobile) : null;

  if (!isTrackingCode(code) || !mobile) {
    return NextResponse.json({ ok: false, error: "کد پیگیری یا شماره موبایل معتبر نیست." }, { status: 422 });
  }

  const lead = await findLeadByCode(code);
  // Same message for "not found" and "mobile mismatch" to avoid enumeration.
  if (!lead || lead.mobile !== mobile) {
    return NextResponse.json({ ok: false, error: "درخواستی با این کد و شماره پیدا نشد." }, { status: 404 });
  }

  return NextResponse.json({ ok: true, tracking: publicTrackingView(lead) });
}

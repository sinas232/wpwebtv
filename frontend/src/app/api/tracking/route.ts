import { NextResponse } from "next/server";
import { isTrackingCode, normalizeMobile, publicTrackingView } from "@/lib/leads";
import { ConfigError, loadConfig } from "@/lib/server/config";
import { createLeadRepository } from "@/lib/server/lead-repository";
import { log } from "@/lib/server/log";
import { clientIp, isLimited } from "@/lib/server/rate-limit";

export const runtime = "nodejs";
export const dynamic = "force-dynamic";

/**
 * Customer tracking. Requires BOTH the tracking code and the mobile number used
 * for the request, so a code alone cannot be used to read someone else's status.
 * The response never includes address, phone, media, technician notes or repair data.
 */
export async function POST(req: Request) {
  try {
    const cfg = loadConfig();
    if (isLimited("tracking", clientIp(req, cfg.trustProxy), 20, 10 * 60 * 1000)) {
      return NextResponse.json({ ok: false, error: "تعداد تلاش‌ها زیاد است. کمی بعد دوباره تلاش کنید." }, { status: 429 });
    }

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

    const lead = await createLeadRepository(cfg).findByCode(code);
    // Same message for "not found" and "mobile mismatch" to prevent enumeration.
    if (!lead || lead.mobile !== mobile) {
      return NextResponse.json({ ok: false, error: "درخواستی با این کد و شماره پیدا نشد." }, { status: 404 });
    }

    return NextResponse.json({ ok: true, tracking: publicTrackingView(lead) });
  } catch (err) {
    if (err instanceof ConfigError) {
      log("error", "tracking_storage_not_ready");
      return NextResponse.json({ ok: false, error: "پیگیری آنلاین در این لحظه در دسترس نیست. لطفاً بعداً تلاش کنید." }, { status: 503 });
    }
    log("error", "tracking_request_failed", { errorName: err instanceof Error ? err.name : "unknown" });
    return NextResponse.json({ ok: false, error: "خطایی رخ داد. دوباره تلاش کنید." }, { status: 500 });
  }
}

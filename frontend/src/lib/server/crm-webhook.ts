/**
 * Signed lead webhook to a CRM or automation tool.
 *
 * - Only sent when CRM_WEBHOOK_URL is set. Otherwise returns "skipped" silently.
 * - HTTPS is required in production.
 * - Each request is signed: X-TV-Timestamp (unix seconds) and
 *   X-TV-Signature: sha256=HMAC_SHA256(secret, `${timestamp}.${rawBody}`).
 *   The receiver should reject timestamps older than 5 minutes.
 * - Idempotency-Key = lead id, so the receiver can de-duplicate retries.
 * - Retries only on network errors, timeouts, 429 and 5xx, with exponential backoff.
 * - Never throws and never logs the body.
 */
import { createHmac } from "node:crypto";
import { log } from "./log.ts";
import type { AppConfig } from "./config.ts";

export type WebhookOutcome = { status: "sent" | "skipped" | "failed"; attempts: number; httpStatus?: number };

export type WebhookDeps = {
  fetchImpl?: typeof fetch;
  sleep?: (ms: number) => Promise<void>;
  now?: () => number;
};

const defaultSleep = (ms: number) => new Promise<void>((r) => setTimeout(r, ms));

export function signPayload(secret: string, timestamp: number, body: string): string {
  return `sha256=${createHmac("sha256", secret).update(`${timestamp}.${body}`).digest("hex")}`;
}

export async function deliverLeadWebhook(
  event: { id: string; trackingCode: string; payload: unknown },
  cfg: AppConfig,
  deps: WebhookDeps = {},
): Promise<WebhookOutcome> {
  const url = cfg.crm.url;
  if (!url) return { status: "skipped", attempts: 0 };

  if (cfg.isProduction && !url.startsWith("https://")) {
    log("error", "crm_webhook_rejected", { reason: "https_required", leadId: event.id });
    return { status: "failed", attempts: 0 };
  }
  if (!cfg.crm.secret) {
    log("error", "crm_webhook_rejected", { reason: "secret_missing", leadId: event.id });
    return { status: "failed", attempts: 0 };
  }

  const fetchImpl = deps.fetchImpl ?? fetch;
  const sleep = deps.sleep ?? defaultSleep;
  const now = deps.now ?? (() => Math.floor(Date.now() / 1000));
  const body = JSON.stringify({ event: "lead.created", sentAt: new Date(now() * 1000).toISOString(), data: event.payload });

  let attempts = 0;
  let lastStatus: number | undefined;
  while (attempts < cfg.crm.maxAttempts) {
    attempts += 1;
    const timestamp = now();
    try {
      const res = await fetchImpl(url, {
        method: "POST",
        headers: {
          "content-type": "application/json",
          "x-tv-timestamp": String(timestamp),
          "x-tv-signature": signPayload(cfg.crm.secret, timestamp, body),
          "idempotency-key": event.id,
        },
        body,
        redirect: "error",
        signal: AbortSignal.timeout(cfg.crm.timeoutMs),
      });
      lastStatus = res.status;
      if (res.ok) {
        log("info", "crm_webhook_sent", { leadId: event.id, trackingCode: event.trackingCode, attempts, httpStatus: res.status });
        return { status: "sent", attempts, httpStatus: res.status };
      }
      const retryable = res.status === 429 || res.status >= 500;
      if (!retryable) break;
    } catch {
      // Network error or timeout: retry below.
      lastStatus = undefined;
    }
    if (attempts < cfg.crm.maxAttempts) await sleep(250 * 2 ** (attempts - 1));
  }

  log("warn", "crm_webhook_failed", { leadId: event.id, trackingCode: event.trackingCode, attempts, httpStatus: lastStatus });
  return { status: "failed", attempts, httpStatus: lastStatus };
}

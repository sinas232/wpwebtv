/**
 * SMS provider contract. No provider is connected yet, so the default
 * implementation sends nothing and reports "not_configured". This is deliberate:
 * a fake "sent" status would tell customers a message arrived when it did not.
 *
 * Use cases: lead received (with tracking code), status changed, visit scheduled.
 * Connect an Iranian SMS gateway by implementing SmsProvider and selecting it in
 * createSmsProvider. Credentials come from environment variables only.
 */
import { log } from "./log.ts";

export type SmsUseCase = "lead_received" | "status_changed" | "visit_scheduled";

export type SmsMessage = {
  useCase: SmsUseCase;
  /** Normalised 09XXXXXXXXX number. Never logged. */
  to: string;
  text: string;
};

export type SmsResult = { status: "sent" | "not_configured" | "failed"; providerMessageId?: string };

export interface SmsProvider {
  readonly name: string;
  send(message: SmsMessage): Promise<SmsResult>;
}

export class NullSmsProvider implements SmsProvider {
  readonly name = "none";
  async send(message: SmsMessage): Promise<SmsResult> {
    log("info", "sms_not_sent", { useCase: message.useCase, reason: "provider_not_configured" });
    return { status: "not_configured" };
  }
}

export function createSmsProvider(): SmsProvider {
  // Add real providers here once credentials and a contract exist.
  return new NullSmsProvider();
}

/** Persian message templates. Keep them short and free of personal details. */
export const SMS_TEMPLATES = {
  lead_received: (code: string) => `تی‌وی دکتر: درخواست شما ثبت شد. کد پیگیری: ${code}`,
  status_changed: (code: string, statusLabel: string) => `تی‌وی دکتر: وضعیت درخواست ${code}: ${statusLabel}`,
  visit_scheduled: (code: string, when: string) => `تی‌وی دکتر: زمان مراجعه برای درخواست ${code}: ${when}`,
} as const;

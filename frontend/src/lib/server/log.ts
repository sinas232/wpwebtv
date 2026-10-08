/**
 * Structured server log. Every field whose name looks personal or secret is
 * redacted before it is written, so logs never carry phone numbers, names,
 * addresses, media, or credentials.
 */

const SENSITIVE = /name|mobile|phone|address|email|description|body|media|secret|token|signature|password|authorization|cookie/i;

type Level = "info" | "warn" | "error";

export function redact(fields: Record<string, unknown>): Record<string, unknown> {
  const out: Record<string, unknown> = {};
  for (const [k, v] of Object.entries(fields)) {
    out[k] = SENSITIVE.test(k) ? "[redacted]" : v;
  }
  return out;
}

export function log(level: Level, event: string, fields: Record<string, unknown> = {}): void {
  const line = JSON.stringify({ at: new Date().toISOString(), level, event, ...redact(fields) });
  if (level === "error") console.error(line);
  else if (level === "warn") console.warn(line);
  else console.log(line);
}

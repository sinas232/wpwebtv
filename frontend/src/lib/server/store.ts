/**
 * Server-side analytics sink. Development writes JSON lines to DATA_DIR.
 * Production should forward server events to GA4 (Measurement Protocol) or another
 * analytics backend. Never write personal data here.
 */
import { mkdir, appendFile } from "node:fs/promises";
import path from "node:path";
import { loadConfig } from "./config.ts";

export async function appendEvent(event: Record<string, unknown>): Promise<void> {
  try {
    const dir = loadConfig().dataDir;
    await mkdir(dir, { recursive: true });
    await appendFile(path.join(dir, "events.jsonl"), `${JSON.stringify({ at: new Date().toISOString(), ...event })}\n`, { encoding: "utf8", mode: 0o600 });
  } catch {
    // Analytics must never break a user request.
  }
}

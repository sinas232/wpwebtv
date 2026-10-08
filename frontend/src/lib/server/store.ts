/**
 * Server-side lead store. Node only (fs). Development and single-server
 * deployments use the local `.data/` directory. Production should replace this
 * module with a database + object storage adapter that keeps the same
 * functions (saveLead, findLeadByCode, saveUpload). Keep this file the only
 * place that touches the filesystem for leads so the swap is contained.
 *
 * Privacy: `.data/` is git-ignored. Uploads and phone numbers are personal data;
 * apply retention and access controls before going live.
 */
import { mkdir, readFile, writeFile, appendFile } from "node:fs/promises";
import path from "node:path";
import type { Lead } from "@/lib/leads";
import { site } from "@/lib/site";

const DATA_DIR = path.resolve(process.cwd(), ".data");
const LEADS_FILE = path.join(DATA_DIR, "leads.jsonl");
const UPLOAD_DIR = path.join(DATA_DIR, "uploads");

export async function saveLead(lead: Lead): Promise<void> {
  await mkdir(DATA_DIR, { recursive: true });
  await appendFile(LEADS_FILE, `${JSON.stringify(lead)}\n`, "utf8");
}

export async function readLeads(): Promise<Lead[]> {
  try {
    const raw = await readFile(LEADS_FILE, "utf8");
    return raw
      .split("\n")
      .filter(Boolean)
      .map((line) => JSON.parse(line) as Lead);
  } catch {
    return [];
  }
}

/** Latest record wins; the append-only log keeps history for audit. */
export async function findLeadByCode(code: string): Promise<Lead | undefined> {
  const normalized = code.trim().toUpperCase();
  const leads = await readLeads();
  for (let i = leads.length - 1; i >= 0; i--) {
    if (leads[i].trackingCode === normalized) return leads[i];
  }
  return undefined;
}

export async function saveUpload(leadId: string, index: number, name: string, bytes: Uint8Array): Promise<string> {
  const dir = path.join(UPLOAD_DIR, leadId);
  await mkdir(dir, { recursive: true });
  const safe = name.replace(/[^\w.\-]+/g, "_").slice(0, 80) || "file";
  const storedAs = `${index}-${safe}`;
  await writeFile(path.join(dir, storedAs), bytes);
  return storedAs;
}

/** Forwards the lead to a CRM / automation webhook when configured. Never throws. */
export async function forwardLead(lead: Lead): Promise<"sent" | "skipped" | "failed"> {
  if (!site.leadWebhookUrl) return "skipped";
  try {
    const res = await fetch(site.leadWebhookUrl, {
      method: "POST",
      headers: { "content-type": "application/json" },
      body: JSON.stringify({ event: "lead.created", lead }),
      signal: AbortSignal.timeout(5000),
    });
    return res.ok ? "sent" : "failed";
  } catch {
    return "failed";
  }
}

/** Analytics sink for server-side events (dev log; replace with GA4 Measurement Protocol in production). */
export async function appendEvent(event: Record<string, unknown>): Promise<void> {
  await mkdir(DATA_DIR, { recursive: true });
  await appendFile(path.join(DATA_DIR, "events.jsonl"), `${JSON.stringify({ at: new Date().toISOString(), ...event })}\n`, "utf8");
}

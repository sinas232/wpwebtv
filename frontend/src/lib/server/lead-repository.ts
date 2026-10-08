/**
 * Lead persistence contract. Routes depend only on this interface.
 *
 * Adapters:
 *  - FileLeadRepository: append-only JSON lines under DATA_DIR. Development and
 *    single-server installs only (requires ALLOW_LOCAL_STORAGE_IN_PRODUCTION=1 in production).
 *  - Production: a database adapter implementing the same interface (for example
 *    Postgres). Declared as LEAD_STORE=postgres; not implemented in this repository yet.
 */
import { appendFile, mkdir, readFile } from "node:fs/promises";
import path from "node:path";
import type { Lead } from "../leads.ts";
import { assertStorageConfigured, loadConfig, type AppConfig } from "./config.ts";

export interface LeadRepository {
  save(lead: Lead): Promise<void>;
  /** Latest record for the code wins; history is kept in the log. */
  findByCode(trackingCode: string): Promise<Lead | undefined>;
}

export class FileLeadRepository implements LeadRepository {
  private readonly file: string;

  constructor(dataDir: string) {
    this.file = path.join(dataDir, "leads.jsonl");
  }

  async save(lead: Lead): Promise<void> {
    await mkdir(path.dirname(this.file), { recursive: true });
    await appendFile(this.file, `${JSON.stringify(lead)}\n`, { encoding: "utf8", mode: 0o600 });
  }

  async findByCode(trackingCode: string): Promise<Lead | undefined> {
    const code = trackingCode.trim().toUpperCase();
    let raw: string;
    try {
      raw = await readFile(this.file, "utf8");
    } catch {
      return undefined;
    }
    const lines = raw.split("\n").filter(Boolean);
    for (let i = lines.length - 1; i >= 0; i--) {
      try {
        const lead = JSON.parse(lines[i]) as Lead;
        if (lead.trackingCode === code) return lead;
      } catch {
        // A corrupt line must not break lookups for everyone else.
      }
    }
    return undefined;
  }
}

/** Builds the configured repository. Throws ConfigError when the setup is unsafe. */
export function createLeadRepository(cfg: AppConfig = loadConfig()): LeadRepository {
  assertStorageConfigured(cfg);
  return new FileLeadRepository(cfg.dataDir);
}

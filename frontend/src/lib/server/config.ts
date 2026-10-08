/**
 * Server configuration. The ONLY place that reads secrets and storage settings
 * from process.env. Nothing here is ever sent to the browser.
 *
 * See docs/PRODUCTION.md for the full list and the deployment checklist.
 */

export type LeadStoreKind = "file" | "postgres";
export type MediaStorageKind = "local" | "s3";

export type AppConfig = {
  isProduction: boolean;
  leadStore: LeadStoreKind;
  mediaStorage: MediaStorageKind;
  /**
   * Local file storage (leads and uploads on disk) is refused in production
   * unless this is explicitly enabled. Enable it only for a single server
   * with a persistent, backed-up disk.
   */
  allowLocalInProduction: boolean;
  dataDir: string;
  s3: {
    bucket: string | null;
    region: string | null;
    endpoint: string | null;
    accessKeyId: string | null;
    secretAccessKey: string | null;
    prefix: string;
  };
  crm: {
    url: string | null;
    secret: string | null;
    timeoutMs: number;
    maxAttempts: number;
  };
  sms: { provider: "none" };
  /** Behind a reverse proxy / CDN that overwrites X-Forwarded-For. Set to "0" only when the app is directly exposed. */
  trustProxy: boolean;
  /** Public site URL, used only for links in notifications. */
  siteUrl: string | null;
};

type Env = Record<string, string | undefined>;

function opt(env: Env, key: string): string | null {
  const v = env[key]?.trim();
  return v ? v : null;
}

function intOr(env: Env, key: string, fallback: number, min: number, max: number): number {
  const n = Number(env[key]);
  return Number.isFinite(n) && n >= min && n <= max ? Math.floor(n) : fallback;
}

export class ConfigError extends Error {
  constructor(message: string) {
    super(message);
    this.name = "ConfigError";
  }
}

export function loadConfig(env: Env = process.env): AppConfig {
  const isProduction = env.NODE_ENV === "production";
  const leadStoreRaw = (opt(env, "LEAD_STORE") ?? "file").toLowerCase();
  const mediaRaw = (opt(env, "MEDIA_STORAGE") ?? "local").toLowerCase();

  return {
    isProduction,
    leadStore: leadStoreRaw === "postgres" ? "postgres" : "file",
    mediaStorage: mediaRaw === "s3" ? "s3" : "local",
    allowLocalInProduction: env.ALLOW_LOCAL_STORAGE_IN_PRODUCTION === "1",
    dataDir: opt(env, "DATA_DIR") ?? `${process.cwd()}/.data`,
    s3: {
      bucket: opt(env, "S3_BUCKET"),
      region: opt(env, "S3_REGION"),
      endpoint: opt(env, "S3_ENDPOINT"),
      accessKeyId: opt(env, "S3_ACCESS_KEY_ID"),
      secretAccessKey: opt(env, "S3_SECRET_ACCESS_KEY"),
      prefix: (opt(env, "S3_PREFIX") ?? "uploads").replace(/^\/+|\/+$/g, ""),
    },
    crm: {
      url: opt(env, "CRM_WEBHOOK_URL"),
      secret: opt(env, "CRM_WEBHOOK_SECRET"),
      timeoutMs: intOr(env, "CRM_WEBHOOK_TIMEOUT_MS", 5000, 500, 30000),
      maxAttempts: intOr(env, "CRM_WEBHOOK_MAX_ATTEMPTS", 3, 1, 6),
    },
    sms: { provider: "none" },
    trustProxy: env.TRUST_PROXY !== "0",
    siteUrl: opt(env, "NEXT_PUBLIC_SITE_URL"),
  };
}

/** Throws a ConfigError with a Persian-safe internal message when the setup is unsafe. */
export function assertStorageConfigured(cfg: AppConfig): void {
  if (cfg.leadStore === "postgres") {
    throw new ConfigError("LEAD_STORE=postgres is declared but the Postgres adapter is not implemented yet.");
  }
  if (cfg.mediaStorage === "s3") {
    const s = cfg.s3;
    if (!s.bucket || !s.region || !s.accessKeyId || !s.secretAccessKey) {
      throw new ConfigError("MEDIA_STORAGE=s3 requires S3_BUCKET, S3_REGION, S3_ACCESS_KEY_ID and S3_SECRET_ACCESS_KEY.");
    }
  }
  if (cfg.isProduction && !cfg.allowLocalInProduction) {
    if (cfg.leadStore === "file") {
      throw new ConfigError("Production requires a database lead store. For a single server with a persistent disk, set ALLOW_LOCAL_STORAGE_IN_PRODUCTION=1.");
    }
    if (cfg.mediaStorage === "local") {
      throw new ConfigError("Production requires MEDIA_STORAGE=s3. For a single server with a persistent disk, set ALLOW_LOCAL_STORAGE_IN_PRODUCTION=1.");
    }
  }
}

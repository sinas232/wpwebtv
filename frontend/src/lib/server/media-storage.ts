/**
 * Customer media storage. Routes depend only on MediaStorage.
 *
 *  - LocalMediaStorage: DATA_DIR/uploads. Development and single-server only.
 *  - S3MediaStorage: any S3-compatible object store (AWS S3, Cloudflare R2,
 *    MinIO, Backblaze B2 S3 API, ArvanCloud/other Iranian S3-compatible providers
 *    if they support the S3 API). Bucket must be private, encrypted at rest and
 *    have a lifecycle rule for retention.
 *
 * Keys never include the customer's file name verbatim; only a sanitised suffix.
 */
import { mkdir, writeFile } from "node:fs/promises";
import path from "node:path";
import { assertStorageConfigured, loadConfig, type AppConfig } from "./config.ts";

export type StoredObject = { key: string; backend: "local" | "s3" };

export interface MediaStorage {
  put(input: { leadId: string; index: number; originalName: string; contentType: string; bytes: Uint8Array }): Promise<StoredObject>;
}

export function safeFileSuffix(originalName: string): string {
  const base = originalName.replace(/[/\\]/g, "_").replace(/[^\w.\-]+/g, "_").replace(/^\.+/, "");
  return base.slice(-60) || "file";
}

export function objectKey(prefix: string, leadId: string, index: number, originalName: string): string {
  if (!/^[0-9a-f-]{36}$/.test(leadId)) throw new Error("invalid lead id");
  return [prefix, leadId, `${index}-${safeFileSuffix(originalName)}`].filter(Boolean).join("/");
}

export class LocalMediaStorage implements MediaStorage {
  private readonly root: string;

  constructor(root: string) {
    this.root = root;
  }

  async put({ leadId, index, originalName, bytes }: Parameters<MediaStorage["put"]>[0]): Promise<StoredObject> {
    const key = objectKey("", leadId, index, originalName);
    const full = path.join(this.root, key);
    await mkdir(path.dirname(full), { recursive: true });
    await writeFile(full, bytes, { mode: 0o600 });
    return { key, backend: "local" };
  }
}

export class S3MediaStorage implements MediaStorage {
  private clientPromise: Promise<import("@aws-sdk/client-s3").S3Client> | null = null;
  private readonly cfg: AppConfig;

  constructor(cfg: AppConfig) {
    this.cfg = cfg;
  }

  private client() {
    if (!this.clientPromise) {
      this.clientPromise = import("@aws-sdk/client-s3").then(
        ({ S3Client }) =>
          new S3Client({
            region: this.cfg.s3.region ?? undefined,
            endpoint: this.cfg.s3.endpoint ?? undefined,
            forcePathStyle: Boolean(this.cfg.s3.endpoint),
            credentials: {
              accessKeyId: this.cfg.s3.accessKeyId ?? "",
              secretAccessKey: this.cfg.s3.secretAccessKey ?? "",
            },
            maxAttempts: 3,
          }),
      );
    }
    return this.clientPromise;
  }

  async put({ leadId, index, originalName, contentType, bytes }: Parameters<MediaStorage["put"]>[0]): Promise<StoredObject> {
    const { PutObjectCommand } = await import("@aws-sdk/client-s3");
    const key = objectKey(this.cfg.s3.prefix, leadId, index, originalName);
    await (await this.client()).send(
      new PutObjectCommand({
        Bucket: this.cfg.s3.bucket ?? undefined,
        Key: key,
        Body: bytes,
        ContentType: contentType,
        ContentLength: bytes.byteLength,
        Metadata: { "lead-id": leadId },
      }),
    );
    return { key, backend: "s3" };
  }
}

export function createMediaStorage(cfg: AppConfig = loadConfig()): MediaStorage {
  assertStorageConfigured(cfg);
  return cfg.mediaStorage === "s3" ? new S3MediaStorage(cfg) : new LocalMediaStorage(path.join(cfg.dataDir, "uploads"));
}

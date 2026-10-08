/**
 * Server-side file validation by magic bytes. The browser-supplied MIME type
 * and file name are never trusted on their own.
 */

export type DetectedType = "jpeg" | "png" | "webp" | "heic" | "mp4" | "mov" | "webm";
export type MediaCategory = "photo" | "video";

const HEIC_BRANDS = new Set(["heic", "heix", "hevc", "heim", "heis", "mif1", "msf1"]);
const MOV_BRANDS = new Set(["qt  "]);
const MP4_BRANDS = new Set(["isom", "iso2", "iso5", "iso6", "mp41", "mp42", "avc1", "M4V ", "M4A "]);

const ascii = (bytes: Uint8Array, start: number, end: number) => String.fromCharCode(...bytes.subarray(start, end));

/** Detects the container from its first bytes. Returns null when unknown. */
export function detectType(bytes: Uint8Array): DetectedType | null {
  if (bytes.length < 12) return null;
  if (bytes[0] === 0xff && bytes[1] === 0xd8 && bytes[2] === 0xff) return "jpeg";
  if (bytes[0] === 0x89 && ascii(bytes, 1, 4) === "PNG" && bytes[4] === 0x0d && bytes[5] === 0x0a) return "png";
  if (ascii(bytes, 0, 4) === "RIFF" && ascii(bytes, 8, 12) === "WEBP") return "webp";
  if (bytes[0] === 0x1a && bytes[1] === 0x45 && bytes[2] === 0xdf && bytes[3] === 0xa3) return "webm";
  if (ascii(bytes, 4, 8) === "ftyp") {
    const brand = ascii(bytes, 8, 12);
    if (HEIC_BRANDS.has(brand)) return "heic";
    if (MOV_BRANDS.has(brand)) return "mov";
    if (MP4_BRANDS.has(brand)) return "mp4";
    // Unknown ftyp brands are accepted as MP4 only if the category check agrees later.
    return "mp4";
  }
  return null;
}

export function categoryOf(type: DetectedType): MediaCategory {
  return type === "mp4" || type === "mov" || type === "webm" ? "video" : "photo";
}

/** Returns ok when the bytes are a real image or video of the expected category. */
export function validateSignature(
  bytes: Uint8Array,
  expected: MediaCategory,
): { ok: true; type: DetectedType } | { ok: false; error: string } {
  const type = detectType(bytes);
  if (!type) return { ok: false, error: "فایل ارسالی یک عکس یا ویدئوی معتبر نیست." };
  if (categoryOf(type) !== expected) {
    return {
      ok: false,
      error: expected === "photo" ? "یکی از عکس‌ها در واقع عکس نیست. فقط JPG، PNG، WebP یا HEIC بفرستید." : "فایل ویدئویی معتبر نیست. فقط MP4، MOV یا WebM بفرستید.",
    };
  }
  return { ok: true, type };
}

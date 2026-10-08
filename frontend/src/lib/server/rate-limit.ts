/**
 * Sliding-window rate limiter.
 *
 * In-memory: correct for one process only. Multi-instance deployments must
 * replace the store with a shared one (Redis or the database) behind the same
 * `hit` function. Client IPs come from X-Forwarded-For when TRUST_PROXY is not "0";
 * the reverse proxy must overwrite that header, or clients can spoof it.
 */
const buckets = new Map<string, number[]>();
let lastSweep = 0;

export function clientIp(req: Request, trustProxy: boolean): string {
  if (trustProxy) {
    const xff = req.headers.get("x-forwarded-for")?.split(",")[0]?.trim();
    if (xff) return xff.slice(0, 64);
    const real = req.headers.get("x-real-ip")?.trim();
    if (real) return real.slice(0, 64);
  }
  return "unknown";
}

/** Returns true when the request is over the limit. */
export function isLimited(scope: string, key: string, max: number, windowMs: number, now = Date.now()): boolean {
  if (now - lastSweep > windowMs) {
    lastSweep = now;
    for (const [k, times] of buckets) if (times.every((t) => now - t >= windowMs)) buckets.delete(k);
  }
  const id = `${scope}:${key}`;
  const recent = (buckets.get(id) ?? []).filter((t) => now - t < windowMs);
  recent.push(now);
  buckets.set(id, recent);
  return recent.length > max;
}

/** Route loading state: a calm skeleton that keeps layout stable (no spinner jank). */
export default function Loading() {
  return (
    <section className="section" aria-busy="true" aria-live="polite">
      <div className="container" style={{ display: "grid", gap: "var(--s-4)" }}>
        <div style={{ height: 14, width: 120, borderRadius: 8, background: "var(--ink-800)" }} />
        <div style={{ height: 48, width: "min(560px, 90%)", borderRadius: 12, background: "var(--ink-800)" }} />
        <div style={{ height: 18, width: "min(640px, 100%)", borderRadius: 8, background: "var(--ink-800)" }} />
        <div className="grid grid--3" style={{ marginBlockStart: "var(--s-5)" }}>
          {[0, 1, 2].map((i) => (
            <div key={i} style={{ height: 160, borderRadius: 24, background: "var(--ink-800)", opacity: 0.6 }} />
          ))}
        </div>
        <span className="sr-only">در حال بارگذاری…</span>
      </div>
    </section>
  );
}

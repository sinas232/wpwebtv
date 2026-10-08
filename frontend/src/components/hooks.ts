"use client";

import { useEffect, useState, type RefObject } from "react";

function useMediaQuery(query: string, fallback = false): boolean {
  const [matches, setMatches] = useState(fallback);
  useEffect(() => {
    const mql = window.matchMedia(query);
    const update = () => setMatches(mql.matches);
    update();
    mql.addEventListener("change", update);
    return () => mql.removeEventListener("change", update);
  }, [query]);
  return matches;
}

export function useReducedMotion(): boolean {
  return useMediaQuery("(prefers-reduced-motion: reduce)");
}

export function useIsMobile(): boolean {
  return useMediaQuery("(max-width: 767px)");
}

/** Low-power heuristic: few cores, low memory, or save-data. Lowers 3D quality, never disables content. */
export function useLowPower(): boolean {
  const [low, setLow] = useState(false);
  useEffect(() => {
    const nav = navigator as Navigator & { deviceMemory?: number; connection?: { saveData?: boolean } };
    const cores = navigator.hardwareConcurrency ?? 8;
    const mem = nav.deviceMemory ?? 8;
    setLow(cores <= 4 || mem <= 4 || Boolean(nav.connection?.saveData));
  }, []);
  return low;
}

/** Synchronous WebGL2/WebGL check. Returns null while unknown (SSR and first paint). */
export function useWebGL(): boolean | null {
  const [supported, setSupported] = useState<boolean | null>(null);
  useEffect(() => {
    try {
      const canvas = document.createElement("canvas");
      const gl = canvas.getContext("webgl2") ?? canvas.getContext("webgl");
      setSupported(Boolean(gl));
    } catch {
      setSupported(false);
    }
  }, []);
  return supported;
}

/** True once the element has entered the viewport. Stays true afterwards unless `once` is false. */
export function useInView(ref: RefObject<Element | null>, options: { once?: boolean; rootMargin?: string } = {}): boolean {
  const { once = false, rootMargin = "0px 0px -10% 0px" } = options;
  const [inView, setInView] = useState(false);
  useEffect(() => {
    const el = ref.current;
    if (!el || typeof IntersectionObserver === "undefined") {
      setInView(true);
      return;
    }
    const io = new IntersectionObserver(
      ([entry]) => {
        if (entry.isIntersecting) {
          setInView(true);
          if (once) io.disconnect();
        } else if (!once) {
          setInView(false);
        }
      },
      { rootMargin, threshold: 0.15 },
    );
    io.observe(el);
    return () => io.disconnect();
  }, [ref, once, rootMargin]);
  return inView;
}

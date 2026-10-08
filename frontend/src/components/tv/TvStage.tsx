"use client";

import dynamic from "next/dynamic";
import { useEffect, useRef, useState, type MutableRefObject } from "react";
import type { PartId, ScreenMode } from "@/lib/diagnosis";
import { useInView, useLowPower, useReducedMotion, useWebGL } from "@/components/hooks";
import type { TvSceneProps } from "./TvScene";

/** The 3D scene is code-split and never server-rendered. */
const TvScene = dynamic(() => import("./TvScene"), { ssr: false, loading: () => null });

type Props = Omit<TvSceneProps, "lowPower" | "interactive"> & {
  label: string;
  className?: string;
  /** Hero and exploded views allow pointer/touch interaction; the detective view is also interactive. */
  interactive?: boolean;
};

/**
 * Chooses the rendering path:
 *  - WebGL available, motion allowed  -> live 3D (throttled on low-power, paused off-screen)
 *  - WebGL unavailable / context lost -> CSS illustration with the same screen states
 *  - prefers-reduced-motion           -> static CSS illustration (no continuous animation)
 * The fallback is rendered before WebGL detection completes, so layout never shifts.
 */
export function TvStage({ label, className, interactive = true, ...sceneProps }: Props) {
  const wrapRef = useRef<HTMLDivElement>(null);
  const webgl = useWebGL();
  const reduced = useReducedMotion();
  const lowPower = useLowPower();
  const inView = useInView(wrapRef, { rootMargin: "120px 0px" });
  const [lost, setLost] = useState(false);

  const use3d = webgl === true && !reduced && !lost;

  return (
    <div ref={wrapRef} className={`tv-stage ${className ?? ""}`} role="img" aria-label={label}>
      {use3d ? (
        <Canvas3D {...sceneProps} lowPower={lowPower} interactive={interactive && !reduced} inView={inView} onLost={() => setLost(true)} />
      ) : (
        <TvFallback screen={sceneProps.screen} />
      )}
    </div>
  );
}

function Canvas3D({
  inView,
  lowPower,
  onLost,
  ...scene
}: TvSceneProps & { inView: boolean; onLost: () => void }) {
  const [Canvas, setCanvas] = useState<null | typeof import("@react-three/fiber").Canvas>(null);
  // The three.js bundle is downloaded only once this stage is near the viewport.
  useEffect(() => {
    if (!inView) return;
    let cancelled = false;
    import("@react-three/fiber").then((m) => {
      if (!cancelled) setCanvas(() => m.Canvas);
    });
    return () => {
      cancelled = true;
    };
  }, [inView]);

  if (!Canvas) return <TvFallback screen={scene.screen} />;

  return (
    <Canvas
      frameloop={inView ? "always" : "never"}
      dpr={lowPower ? [1, 1.25] : [1, 1.75]}
      camera={{ position: [0, 0, 6.4], fov: 34, near: 0.1, far: 50 }}
      gl={{ antialias: !lowPower, alpha: true, powerPreference: "high-performance" }}
      onCreated={({ gl }) => {
        gl.domElement.addEventListener(
          "webglcontextlost",
          (e) => {
            e.preventDefault();
            onLost();
          },
          { once: true },
        );
      }}
    >
      <TvScene {...scene} lowPower={lowPower} />
    </Canvas>
  );
}

/** Lightweight CSS illustration. Same screen vocabulary as the 3D screen. */
export function TvFallback({ screen }: { screen: ScreenMode }) {
  const mode = screen === "black" || screen === "idle" ? "black" : screen === "dim" ? "dim" : "on";
  return (
    <div className="tv-fallback" aria-hidden="true">
      <div className="tv-fallback__set">
        <div className="tv-fallback__screen" data-mode={mode} />
        <div className="tv-fallback__stand" />
      </div>
    </div>
  );
}

export type { PartId, MutableRefObject };

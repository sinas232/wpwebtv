"use client";

/**
 * Procedural 3D television. No external model download: the body, panel,
 * LED backlight zones, boards and ports are built from primitives so the
 * scene loads instantly and works offline. Replace `buildParts` with a
 * Draco-compressed GLB later without changing the props contract.
 */
import { useEffect, useMemo, useRef, type MutableRefObject } from "react";
import { useFrame, useThree } from "@react-three/fiber";
import { RoundedBox } from "@react-three/drei";
import * as THREE from "three";
import type { PartId, ScreenMode } from "@/lib/diagnosis";

export type TvSceneProps = {
  screen: ScreenMode;
  highlight: PartId[];
  /** 0 = assembled, 1 = fully exploded. Read every frame via ref (no re-render). */
  explode?: MutableRefObject<number>;
  lowPower?: boolean;
  /** Pointer / touch parallax. Disabled for reduced motion by the parent. */
  interactive?: boolean;
  onPartSelect?: (id: PartId) => void;
  selectedPart?: PartId | null;
};

const ACCENT = new THREE.Color("#5b8cff");
const GRAPHITE = new THREE.Color("#22262e");
const BOARD = new THREE.Color("#1b2a24");
const PANEL_DARK = new THREE.Color("#0c0e12");

const SCREEN_VERT = /* glsl */ `
varying vec2 vUv;
void main() {
  vUv = uv;
  gl_Position = projectionMatrix * modelViewMatrix * vec4(position, 1.0);
}
`;

const SCREEN_FRAG = /* glsl */ `
precision mediump float;
varying vec2 vUv;
uniform float uTime;
uniform float uMode;    // 0 fluid, 1 no-signal, 2 wifi, 3 boot, 4 black, 5 standby
uniform float uBright;  // 0..1 smoothed
uniform vec3 uAccent;

float hash(vec2 p) { return fract(sin(dot(p, vec2(12.9898, 78.233))) * 43758.5453); }

void main() {
  vec2 uv = vUv;
  vec3 col = vec3(0.0);
  float t = uTime * 0.18;
  if (uMode < 0.5) {
    // Fluid: layered sine warps, one accent hue family.
    vec2 p = uv * 2.0 - 1.0;
    float w = sin(p.x * 2.4 + t * 4.0) + sin(p.y * 3.1 - t * 3.0) + sin((p.x + p.y) * 1.7 + t * 2.0);
    float g = smoothstep(-1.0, 2.5, w);
    vec3 deep = vec3(0.04, 0.05, 0.09);
    vec3 hi = uAccent * 1.05 + vec3(0.12, 0.10, 0.22);
    col = mix(deep, hi, g * 0.85);
    col += 0.04 * hash(uv * 900.0 + uTime);
  } else if (uMode < 1.5) {
    // No signal: scanning bars.
    float bar = step(0.5, fract(uv.y * 70.0 + uTime * 0.6));
    col = vec3(0.45) * bar * 0.4;
    col += 0.03 * hash(uv * 600.0 + uTime);
  } else if (uMode < 2.5) {
    // Wi-Fi arcs from bottom centre.
    float r = length((uv - vec2(0.5, -0.05)) * vec2(1.0, 1.4));
    float ring = step(0.5, fract(r * 5.0 - uTime * 0.9));
    col = uAccent * ring * smoothstep(1.0, 0.2, r) * 0.9;
  } else if (uMode < 3.5) {
    // Boot: flicker in and settle.
    float f = 0.5 + 0.5 * sin(uTime * 34.0) * step(0.8, fract(uTime * 0.7));
    col = uAccent * 0.55 * f + vec3(0.03);
  } else if (uMode < 4.5) {
    col = vec3(0.0);
  } else {
    // Standby: barely-there ambient.
    col = vec3(0.02, 0.025, 0.04);
  }
  gl_FragColor = vec4(col * uBright, 1.0);
}
`;

type PartSpec = {
  id: PartId;
  size: [number, number, number];
  home: [number, number, number];
  exp: [number, number, number];
  color: THREE.Color;
};

function buildParts(lowPower: boolean): PartSpec[] {
  const s: PartSpec[] = [
    { id: "display", size: [3.0, 1.7, 0.02], home: [0, 0, 0.075], exp: [0, 0.1, 1.65], color: PANEL_DARK },
    { id: "tcon", size: [0.5, 0.22, 0.03], home: [-1.15, 0.62, 0.02], exp: [-1.55, 0.95, -0.9], color: BOARD },
    { id: "backlight", size: [3.0, 1.7, 0.05], home: [0, 0, -0.04], exp: [0, 0.1, -0.7], color: GRAPHITE },
    { id: "mainboard", size: [1.05, 0.72, 0.035], home: [0.55, 0.26, -0.13], exp: [0.9, 0.5, -1.75], color: BOARD },
    { id: "powerboard", size: [0.62, 0.9, 0.035], home: [-0.95, -0.36, -0.13], exp: [-1.45, -0.55, -1.55], color: BOARD },
    { id: "wifi", size: [0.34, 0.2, 0.03], home: [1.22, 0.66, -0.1], exp: [1.65, 1.0, -2.45], color: BOARD },
    { id: "hdmi", size: [0.42, 0.14, 0.09], home: [1.35, -0.55, -0.02], exp: [2.05, -0.65, -0.55], color: GRAPHITE },
    { id: "speakers", size: [0.12, 1.2, 0.05], home: [1.52, 0.0, 0.0], exp: [2.05, 0.1, 0.5], color: GRAPHITE },
  ];
  return s;
}

const ZONES_X = 4;
const ZONES_Y = 3;

export default function TvScene({ screen, highlight, explode, lowPower = false, interactive = true, onPartSelect, selectedPart }: TvSceneProps) {
  const groupRef = useRef<THREE.Group>(null);
  const screenMat = useRef<THREE.ShaderMaterial>(null);
  const bodyMat = useRef<THREE.MeshStandardMaterial>(null);
  const ledRef = useRef<THREE.Mesh>(null);
  const cableDot = useRef<THREE.Mesh>(null);
  const zoneRefs = useRef<(THREE.Mesh | null)[]>([]);
  const highlightRef = useRef(highlight);
  const screenRef = useRef(screen);
  const selectedRef = useRef(selectedPart ?? null);
  const { camera, pointer } = useThree();

  useEffect(() => {
    highlightRef.current = highlight;
    screenRef.current = screen;
    selectedRef.current = selectedPart ?? null;
  }, [highlight, screen, selectedPart]);

  const parts = useMemo(() => buildParts(lowPower), [lowPower]);
  const uniforms = useMemo(
    () => ({
      uTime: { value: 0 },
      uMode: { value: 0 },
      uBright: { value: 1 },
      uAccent: { value: new THREE.Vector3(0.357, 0.549, 1.0) },
    }),
    [],
  );
  const partRefs = useRef<Record<string, THREE.Group | null>>({});
  const partMats = useRef<Record<string, THREE.MeshStandardMaterial | null>>({});
  const current = useRef({ rotY: 0, rotX: 0, bright: 1, p: 0 });

  useFrame((state, delta) => {
    const t = state.clock.elapsedTime;
    const hl = highlightRef.current;
    const sc = screenRef.current;
    const sel = selectedRef.current;
    const p = explode ? explode.current : 0;
    const eased = p * p * (3 - 2 * p);
    current.current.p += (eased - current.current.p) * Math.min(1, delta * 6);
    const pp = current.current.p;

    // Screen mode and brightness.
    const modeMap: Record<ScreenMode, number> = { fluid: 0, nosignal: 1, wifi: 2, boot: 3, black: 4, dim: 0, idle: 5 };
    const target = sc === "dim" ? 0.3 : sc === "black" || sc === "idle" ? 0.0 : sc === "boot" ? 0.8 : 1.0;
    current.current.bright += (target - current.current.bright) * Math.min(1, delta * 4);
    uniforms.uTime.value = t;
    uniforms.uMode.value = modeMap[sc];
    uniforms.uBright.value = current.current.bright;

    // Group motion: pointer parallax + slow cinematic turn when exploded.
    const g = groupRef.current;
    if (g) {
      const px = interactive ? pointer.x : 0;
      const py = interactive ? pointer.y : 0;
      const wantY = px * 0.32 + pp * 0.55;
      const wantX = -py * 0.18 + pp * 0.1;
      current.current.rotY += (wantY - current.current.rotY) * Math.min(1, delta * 4);
      current.current.rotX += (wantX - current.current.rotX) * Math.min(1, delta * 4);
      g.rotation.y = current.current.rotY;
      g.rotation.x = current.current.rotX;
      g.position.y = Math.sin(t * 0.9) * 0.035 * (1 - pp);
    }
    camera.position.z = 6.4 + pp * 2.4;
    camera.position.y = pp * 0.35;
    camera.lookAt(0, 0, -pp * 0.6);

    // Body shell fades as parts separate.
    if (bodyMat.current) bodyMat.current.opacity = 1 - pp * 0.8;

    // Part motion and highlight.
    const pulse = 0.5 + 0.5 * Math.sin(t * 3.2);
    for (const spec of parts) {
      const ref = partRefs.current[spec.id];
      if (ref) {
        ref.position.set(
          spec.home[0] + (spec.exp[0] - spec.home[0]) * pp,
          spec.home[1] + (spec.exp[1] - spec.home[1]) * pp,
          spec.home[2] + (spec.exp[2] - spec.home[2]) * pp,
        );
      }
      const mat = partMats.current[spec.id];
      if (mat) {
        const lit = hl.includes(spec.id) || sel === spec.id;
        const want = lit ? 0.55 + pulse * 0.45 : 0;
        mat.emissive.copy(ACCENT);
        mat.emissiveIntensity += (want - mat.emissiveIntensity) * Math.min(1, delta * 8);
      }
    }

    // Backlight zones: wave when dim/backlight is active, otherwise off.
    const backlightOn = hl.includes("backlight") || sc === "dim";
    zoneRefs.current.forEach((z, i) => {
      if (!z) return;
      const m = z.material as THREE.MeshStandardMaterial;
      const wave = 0.5 + 0.5 * Math.sin(t * 2.2 + i * 0.9);
      const want = backlightOn ? (sc === "dim" ? 0.25 + wave * 0.4 : 0.6 + wave * 0.4) : 0.06;
      m.emissiveIntensity += (want - m.emissiveIntensity) * Math.min(1, delta * 6);
    });

    // Power LED: blink when power board is the focus, otherwise steady dim.
    if (ledRef.current) {
      const m = ledRef.current.material as THREE.MeshStandardMaterial;
      const blink = hl.includes("powerboard") || sc === "boot" || sc === "idle";
      const want = blink ? (sc === "boot" ? (Math.sin(t * 12) > 0 ? 1.2 : 0.05) : 0.4 + pulse * 0.8) : 0.2;
      m.emissiveIntensity += (want - m.emissiveIntensity) * Math.min(1, delta * 10);
    }

    // HDMI cable animation when the port is highlighted.
    if (cableDot.current) {
      const on = hl.includes("hdmi");
      cableDot.current.visible = on;
      if (on) cableDot.current.position.x = 1.9 + ((t * 0.6) % 1) * 0.9;
    }
  });

  return (
    <group ref={groupRef}>
      {/* Body shell */}
      <RoundedBox args={[3.2, 1.85, 0.14]} radius={0.06} smoothness={lowPower ? 2 : 4} castShadow={false} receiveShadow={false}>
        <meshStandardMaterial ref={bodyMat} color="#16191f" metalness={0.65} roughness={0.32} transparent opacity={1} />
      </RoundedBox>

      {parts.map((spec) => {
        const isDisplay = spec.id === "display";
        const isBacklight = spec.id === "backlight";
        return (
          <group
            key={spec.id}
            ref={(el) => {
              partRefs.current[spec.id] = el;
            }}
            position={spec.home}
            onPointerDown={(e) => {
              e.stopPropagation();
              onPartSelect?.(spec.id);
            }}
            onPointerOver={() => {
              document.body.style.cursor = "pointer";
            }}
            onPointerOut={() => {
              document.body.style.cursor = "";
            }}
          >
            <mesh>
              <boxGeometry args={spec.size} />
              {isDisplay ? (
                <shaderMaterial
                  ref={screenMat}
                  uniforms={uniforms}
                  vertexShader={SCREEN_VERT}
                  fragmentShader={SCREEN_FRAG}
                />
              ) : (
                <meshStandardMaterial
                  ref={(m: THREE.MeshStandardMaterial | null) => {
                    partMats.current[spec.id] = m;
                  }}
                  color={spec.color}
                  metalness={0.4}
                  roughness={0.5}
                  emissive={ACCENT}
                  emissiveIntensity={0}
                />
              )}
            </mesh>

            {isBacklight && !lowPower
              ? Array.from({ length: ZONES_X * ZONES_Y }).map((_, i) => {
                  const col = i % ZONES_X;
                  const row = Math.floor(i / ZONES_X);
                  const x = (col - (ZONES_X - 1) / 2) * 0.72;
                  const y = (row - (ZONES_Y - 1) / 2) * 0.5;
                  return (
                    <mesh
                      key={i}
                      position={[x, y, 0.03]}
                      ref={(el: THREE.Mesh | null) => {
                        zoneRefs.current[i] = el;
                      }}
                    >
                      <boxGeometry args={[0.56, 0.36, 0.01]} />
                      <meshStandardMaterial color="#0f1116" emissive={ACCENT} emissiveIntensity={0.06} />
                    </mesh>
                  );
                })
              : null}

            {spec.id === "powerboard" ? (
              <mesh ref={ledRef} position={[0.2, 0.36, 0.03]}>
                <sphereGeometry args={[0.03, 12, 12]} />
                <meshStandardMaterial color="#9fffd0" emissive="#9fffd0" emissiveIntensity={0.2} />
              </mesh>
            ) : null}

            {spec.id === "speakers" ? (
              <>
                <mesh position={[0, 0.3, 0.03]} rotation={[Math.PI / 2, 0, 0]}>
                  <cylinderGeometry args={[0.07, 0.07, 0.02, 20]} />
                  <meshStandardMaterial color="#0c0e12" />
                </mesh>
              </>
            ) : null}
          </group>
        );
      })}

      {/* HDMI cable: a moving highlight dot travelling out of the port. */}
      <mesh ref={cableDot} visible={false} position={[1.9, -0.55, -0.02]}>
        <sphereGeometry args={[0.035, 10, 10]} />
        <meshStandardMaterial color="#ffffff" emissive={ACCENT} emissiveIntensity={1.4} />
      </mesh>

      {/* Lighting: key, rim and accent fill. No HDR download required. */}
      <ambientLight intensity={0.45} />
      <directionalLight position={[3, 4, 5]} intensity={1.1} />
      <pointLight position={[-3.5, -2, 2.5]} intensity={6} color="#5b8cff" distance={9} />
      <pointLight position={[3, 1, -2]} intensity={2} color="#f6f3ec" distance={8} />
    </group>
  );
}

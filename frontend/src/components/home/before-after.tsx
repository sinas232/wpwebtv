"use client";

import { useId, useState, type CSSProperties } from "react";

/**
 * Draggable comparison. Works with pointer, touch and keyboard (range input).
 * The illustrations are generated placeholders, labelled as such, until real
 * before/after photos are supplied by the business.
 */
export function BeforeAfter({ before, after, caption }: { before: string; after: string; caption: string }) {
  const [pos, setPos] = useState(50);
  const id = useId();
  return (
    <figure style={{ margin: 0 }}>
      <div className="ba" style={{ "--pos": `${pos}%` } as CSSProperties}>
        <div className="ba__layer ba__after" aria-hidden="true">
          <div className="ba__bars" />
        </div>
        <div className="ba__layer ba__before" aria-hidden="true">
          <div className="ba__bars" />
        </div>
        <span className="ba__label ba__label--before" aria-hidden="true">
          {before}
        </span>
        <span className="ba__label ba__label--after" aria-hidden="true">
          {after}
        </span>
        <div className="ba__handle" aria-hidden="true">
          <span className="ba__knob">⇔</span>
        </div>
        <label htmlFor={id} className="sr-only">
          مقایسه‌ی {before} و {after}
        </label>
        <input
          id={id}
          className="ba__input"
          type="range"
          min={0}
          max={100}
          value={pos}
          onChange={(e) => setPos(Number(e.target.value))}
          aria-valuetext={`${pos} درصد ${after}`}
        />
      </div>
      <figcaption className="ba-note">{caption}</figcaption>
    </figure>
  );
}

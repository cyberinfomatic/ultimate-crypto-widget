import React from "react";

/** Which way a figure moved. */
export type Direction = "up" | "down" | "flat";

/** The direction of a change. Zero, or anything that is not a number, is "flat". */
export const directionOf = (value: unknown): Direction => {
	if (typeof value !== "number" || !Number.isFinite(value) || value === 0) {
		return "flat";
	}
	return value > 0 ? "up" : "down";
};

const PATHS: Record<Direction, string> = {
	up: "M6 10V2M2.5 5.5 6 2l3.5 3.5",
	down: "M6 2v8M2.5 6.5 6 10l3.5-3.5",
	flat: "M2 6h8",
};

/**
 * An arrow for a rise or a fall, so the direction is never told by colour alone.
 * Hidden from screen readers: the number beside it carries its own sign.
 */
export const TrendIcon = ({ direction }: { direction: Direction }) => (
	<svg className="ucw-trend-icon" viewBox="0 0 12 12" fill="none" stroke="currentColor" strokeWidth="1.75" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true" focusable="false">
		<path d={PATHS[direction]} />
	</svg>
);

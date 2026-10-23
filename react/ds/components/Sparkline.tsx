import React from "react";
import { directionOf } from "./TrendIcon";

type SparklineProps = {
	points: number[];
	/** What the line shows, e.g. "Bitcoin, last 7 days". */
	label: string;
	width?: number;
	height?: number;
};

/** The points of a small line chart, fitted to a box with a 1px margin. */
export function sparklinePoints(points: number[], width: number, height: number): string {
	const values = points.filter((point) => Number.isFinite(point));
	if (values.length < 2) {
		return "";
	}
	const low = Math.min(...values);
	const range = Math.max(...values) - low || 1;
	const stepX = (width - 2) / (values.length - 1);

	return values.map((value, index) => `${(1 + index * stepX).toFixed(1)},${(1 + (height - 2) * (1 - (value - low) / range)).toFixed(1)}`).join(" ");
}

/** A small line showing where a price has been. Green if it ends higher than it began. */
export const Sparkline = ({ points, label, width = 96, height = 28 }: SparklineProps) => {
	const line = sparklinePoints(points, width, height);
	if (line === "") {
		return null;
	}
	const direction = directionOf(points[points.length - 1] - points[0]);

	return (
		<svg className="ucw-sparkline" data-direction={direction} role="img" aria-label={label} width={width} height={height} viewBox={`0 0 ${width} ${height}`} fill="none">
			<polyline points={line} stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round" />
		</svg>
	);
};

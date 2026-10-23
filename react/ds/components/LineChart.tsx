import React, { KeyboardEvent, PointerEvent, useState } from "react";
import { MISSING } from "../format";
import { sparklinePoints } from "./Sparkline";
import { directionOf } from "./TrendIcon";

export type ChartPoint = { time: number; value: number };

type LineChartProps = {
	points: ChartPoint[];
	/** What the chart shows, e.g. "Bitcoin price, last 7 days". */
	label: string;
	formatValue: (value: number) => string;
	formatTime?: (time: number) => string;
};

// The drawing's own units; the SVG stretches to whatever space it is given.
const WIDTH = 300;
const HEIGHT = 100;

/** Which point is under a position, given as a share (0 to 1) of the chart's width. */
export function nearestIndex(share: number, count: number): number {
	if (count < 2 || !Number.isFinite(share)) {
		return 0;
	}
	return Math.round(Math.min(1, Math.max(0, share)) * (count - 1));
}

/** The lowest and highest value, or null for an empty list. */
export function rangeOf(points: ChartPoint[]): { low: number; high: number } | null {
	if (points.length === 0) {
		return null;
	}
	const values = points.map((point) => point.value);
	return { low: Math.min(...values), high: Math.max(...values) };
}

const defaultTime = (time: number): string => new Date(time).toLocaleDateString(undefined, { month: "short", day: "numeric" });

/**
 * A price history as a filled line. Pointing at it, or using the arrow keys
 * when it has focus, reads out the price at that moment; otherwise it shows
 * the low and the high.
 */
export const LineChart = ({ points, label, formatValue, formatTime = defaultTime }: LineChartProps) => {
	const [active, setActive] = useState<number | null>(null);
	const range = rangeOf(points);
	const line = sparklinePoints(
		points.map((point) => point.value),
		WIDTH,
		HEIGHT
	);

	if (range === null || line === "") {
		return null;
	}

	const last = points.length - 1;
	const direction = directionOf(points[last].value - points[0].value);
	const shown = active === null ? null : points[Math.min(active, last)];

	const onPointerMove = (event: PointerEvent<HTMLDivElement>) => {
		const box = event.currentTarget.getBoundingClientRect();
		let share = box.width > 0 ? (event.clientX - box.left) / box.width : 0;
		if (getComputedStyle(event.currentTarget).direction === "rtl") {
			share = 1 - share;
		}
		setActive(nearestIndex(share, points.length));
	};

	const onKeyDown = (event: KeyboardEvent<HTMLDivElement>) => {
		const step = event.key === "ArrowRight" ? 1 : event.key === "ArrowLeft" ? -1 : 0;
		if (step === 0) {
			return;
		}
		event.preventDefault();
		setActive((current) => Math.min(last, Math.max(0, (current ?? last) + step)));
	};

	return (
		<div className="ucw-chart" data-direction={direction}>
			<div className="ucw-chart__readout" aria-live="polite">
				{shown ? (
					<>
						<span className="ucw-chart__value">{formatValue(shown.value)}</span>
						<span>{formatTime(shown.time)}</span>
					</>
				) : (
					<>
						<span>
							{formatValue(range.low)} {MISSING} {formatValue(range.high)}
						</span>
						<span>
							{formatTime(points[0].time)} {MISSING} {formatTime(points[last].time)}
						</span>
					</>
				)}
			</div>
			<div className="ucw-chart__plot" role="img" aria-label={label} tabIndex={0} onPointerMove={onPointerMove} onPointerLeave={() => setActive(null)} onBlur={() => setActive(null)} onKeyDown={onKeyDown}>
				<svg viewBox={`0 0 ${WIDTH} ${HEIGHT}`} preserveAspectRatio="none" aria-hidden="true" focusable="false">
					<polygon className="ucw-chart__area" points={`1,${HEIGHT} ${line} ${WIDTH - 1},${HEIGHT}`} />
					<polyline className="ucw-chart__line" points={line} />
				</svg>
				{active !== null && last > 0 && <span className="ucw-chart__marker" style={{ insetInlineStart: `${(Math.min(active, last) / last) * 100}%` }} />}
			</div>
		</div>
	);
};

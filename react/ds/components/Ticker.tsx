import React, { CSSProperties, ReactNode, useLayoutEffect, useRef, useState } from "react";
import { cssSize } from "../format";

type TickerProps = {
	/** What is scrolling past, for screen readers. */
	label: string;
	/** Pixels per second. */
	speed?: number;
	width?: unknown;
	children: ReactNode;
};

export const MIN_SPEED = 10;
export const MAX_SPEED = 400;

/** Seconds one full pass takes for content this wide. */
export function tickerDuration(contentWidth: number, speed: number): number {
	const safeSpeed = Math.min(MAX_SPEED, Math.max(MIN_SPEED, Number.isFinite(speed) ? speed : 50));
	return contentWidth > 0 ? contentWidth / safeSpeed : 0;
}

/**
 * Content that scrolls sideways in a loop. It pauses under the pointer or
 * keyboard focus, and for visitors who prefer reduced motion it stands still
 * and can be scrolled by hand.
 */
export const Ticker = ({ label, speed = 50, width, children }: TickerProps) => {
	const group = useRef<HTMLDivElement>(null);
	const [duration, setDuration] = useState(0);

	useLayoutEffect(() => {
		const element = group.current;
		if (!element) {
			return;
		}
		const measure = () => setDuration(tickerDuration(element.scrollWidth, speed));
		measure();
		if (typeof ResizeObserver === "undefined") {
			return;
		}
		const observer = new ResizeObserver(measure);
		observer.observe(element);
		return () => observer.disconnect();
	}, [speed]);

	const style = { inlineSize: cssSize(width), "--ucw-ticker-duration": `${duration}s` } as CSSProperties;

	return (
		<div className="ucw-ticker" role="group" aria-label={label} style={style} data-running={duration > 0 ? "" : undefined}>
			<div className="ucw-ticker__track">
				<div className="ucw-ticker__group" ref={group}>
					{children}
				</div>
				{/* A second copy follows the first so the loop has no gap. */}
				<div className="ucw-ticker__group" aria-hidden="true">
					{children}
				</div>
			</div>
		</div>
	);
};

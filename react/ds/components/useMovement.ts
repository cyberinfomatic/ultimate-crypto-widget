import { useEffect, useRef, useState } from "react";
import { Direction } from "./TrendIcon";

/** How long a changed price stays tinted. */
const HOLD_MS = 600;

/**
 * Says for a moment which way a number just moved, so a live price can show a
 * rise or a fall when it changes. "flat" the rest of the time.
 */
export function useMovement(value: unknown): Direction {
	const previous = useRef(value);
	const [moved, setMoved] = useState<Direction>("flat");

	useEffect(() => {
		const before = previous.current;
		previous.current = value;
		if (typeof before !== "number" || typeof value !== "number" || before === value) {
			return;
		}
		setMoved(value > before ? "up" : "down");
		const timer = setTimeout(() => setMoved("flat"), HOLD_MS);
		return () => clearTimeout(timer);
	}, [value]);

	return moved;
}

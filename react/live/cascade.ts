import { LiveFeed, Tick } from "./types";

export interface CascadeOptions {
	/** How long a feed gets to deliver a first price for a symbol before the next feed is asked. */
	patienceMs?: number;
}

/**
 * Gets every symbol a live price from the first feed that can provide one.
 *
 * The first feed is asked for all symbols. Whatever it has not priced after a
 * short wait (it does not list that coin, or it is blocked for this visitor) is
 * asked of the next feed, and so on. Feeds that are delivering keep running. A
 * feed that goes down hands its symbols to the next one straight away.
 *
 * @return Stops every feed.
 */
export const cascade = (
	feeds: LiveFeed[],
	symbols: string[],
	onTick: (tick: Tick, feed: string) => void,
	{ patienceMs = 8000 }: CascadeOptions = {}
): (() => void) => {
	const stops: Array<() => void> = [];
	const timers: Array<ReturnType<typeof setTimeout>> = [];
	let stopped = false;

	const start = (index: number, wanted: string[]) => {
		if (stopped || index >= feeds.length || wanted.length === 0) {
			return;
		}

		const feed = feeds[index];
		// Symbols this feed has not priced yet, and symbols already passed on.
		const waiting = new Set(wanted);
		const passedOn = new Set<string>();

		// Ask the next feed for these, unless it has been asked already.
		const passOn = (candidates: Iterable<string>) => {
			const fresh = Array.from(candidates).filter((symbol) => !passedOn.has(symbol));
			fresh.forEach((symbol) => passedOn.add(symbol));
			start(index + 1, fresh);
		};

		stops.push(
			feed.subscribe(
				wanted,
				(tick) => {
					if (stopped) {
						return;
					}
					waiting.delete(tick.symbol);
					onTick(tick, feed.name);
				},
				// The feed is gone: everything it was asked for needs another source.
				() => passOn(wanted)
			)
		);

		// Whatever it has not priced after a while is tried elsewhere.
		timers.push(setTimeout(() => passOn(waiting), patienceMs));
	};

	start(0, Array.from(new Set(symbols.map((symbol) => symbol.toUpperCase()))));

	return () => {
		stopped = true;
		timers.forEach(clearTimeout);
		stops.forEach((stop) => stop());
	};
};

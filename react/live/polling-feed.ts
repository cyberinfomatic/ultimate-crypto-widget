import { num } from "./socket-feed";
import { LiveFeed, Tick } from "./types";

/**
 * The site's REST root, read from the link WordPress puts in every page head.
 * Falls back to the default location.
 */
export const restRoot = (): string => {
	const link = document.querySelector<HTMLLinkElement>('link[rel="https://api.w.org/"]');
	return link?.href || `${window.location.origin}/wp-json/`;
};

/**
 * The last resort: ask the site's own cached price route every so often. Slower
 * than a socket, but it works wherever the page itself works.
 */
export const pollingFeed = (intervalMs = 60000, fetcher: typeof fetch = (...args) => fetch(...args)): LiveFeed => ({
	name: "polling",

	subscribe(symbols, onTick) {
		const wanted = new Set(symbols);
		let stopped = false;

		const poll = async () => {
			try {
				const response = await fetcher(`${restRoot()}ultimate-crypto-widget/v1/coins?default_currency=usd`);
				if (!response.ok) {
					return;
				}
				const coins: unknown = await response.json();
				if (stopped || !Array.isArray(coins)) {
					return;
				}
				coins.forEach((coin: Record<string, unknown>) => {
					const symbol = String(coin?.symbol ?? "").toUpperCase();
					const price = num(coin?.current_price);
					if (!wanted.has(symbol) || price === undefined) {
						return;
					}
					const tick: Tick = {
						symbol,
						price,
						high24h: num(coin.high_24h),
						low24h: num(coin.low_24h),
						change24h: num(coin.price_change_24h),
						changePercent24h: num(coin.price_change_percentage_24h),
						time: Date.now(),
					};
					onTick(tick);
				});
			} catch {
				// Try again at the next interval.
			}
		};

		poll();
		const timer = setInterval(poll, intervalMs);

		return () => {
			stopped = true;
			clearInterval(timer);
		};
	},
});

import { useEffect, useState } from "react";
import { TickerData } from "../types";
import { cascade } from "./cascade";
import { binance, coinbase, kraken } from "./feeds";
import { pollingFeed } from "./polling-feed";
import { LiveFeed, Tick } from "./types";

/**
 * In order of preference. Coinbase and Kraken serve visitors in the United
 * States; Binance covers coins they do not list; polling works everywhere.
 */
export const defaultFeeds = (): LiveFeed[] => [coinbase, kraken, binance, pollingFeed()];

/**
 * A tick as the widgets expect it: prices converted from US dollars into the
 * widget's currency.
 */
export const toTickerData = (tick: Tick, usdRate: number): TickerData => {
	const data: Partial<TickerData> = {
		symbol: tick.symbol.toLowerCase(),
		current_price: tick.price * usdRate,
		last_updated: new Date(tick.time).toISOString(),
	};
	if (tick.high24h !== undefined) data.high_24h = tick.high24h * usdRate;
	if (tick.low24h !== undefined) data.low_24h = tick.low24h * usdRate;
	if (tick.change24h !== undefined) data.price_change_24h = tick.change24h * usdRate;
	if (tick.changePercent24h !== undefined) data.price_change_percentage_24h = tick.changePercent24h;
	if (tick.volume24h !== undefined) data.total_volume = tick.volume24h;

	return data as TickerData;
};

/**
 * Live prices for a list of coin symbols.
 *
 * @param symbols Ticker symbols in any case, e.g. ["btc", "eth"].
 * @param usdRate How much one US dollar is in the widget's currency.
 * @return `data` is keyed by uppercase symbol and holds only what has changed since
 *         the page loaded; spread it over the coin the page started with.
 */
export const useLivePrices = (symbols: string[], usdRate = 1, feeds?: LiveFeed[]) => {
	const [data, setData] = useState<Record<string, TickerData>>({});
	const [connected, setConnected] = useState(false);

	// The list is rebuilt on every render; only a real change should reconnect.
	const key = Array.from(new Set(symbols.map((symbol) => symbol.toUpperCase()))).sort().join(",");

	useEffect(() => {
		if (key === "") {
			return undefined;
		}

		const stop = cascade(feeds ?? defaultFeeds(), key.split(","), (tick) => {
			setConnected(true);
			setData((previous) => ({
				...previous,
				[tick.symbol]: { ...previous[tick.symbol], ...toTickerData(tick, usdRate) },
			}));
		});

		return () => {
			stop();
			setConnected(false);
		};
	}, [key, usdRate]);

	return { connected, data, error: null as string | null };
};

export default useLivePrices;

import { useMemo } from "react";
import { toCount } from "../ds/format";
import { CoinData, TickerData, UCWPWidgetSetting } from "../types";
import useLivePrices from "./useLivePrices";

/** The coins a widget shows: as many as its "count" setting says, from the page's own data. */
export function visibleCoins(coins: unknown, count: unknown, fallback = 10): CoinData[] {
	if (!Array.isArray(coins)) {
		return [];
	}
	return coins.filter((coin): coin is CoinData => Boolean(coin) && typeof coin.symbol === "string").slice(0, toCount(count, fallback));
}

/** How far a live price may be from the one the page was built with before it is not believed. */
const LEAST_RATIO = 0.5;
const MOST_RATIO = 2;

/**
 * Whether a live price can be for the same coin as the page's own figure.
 *
 * Live feeds know coins by ticker, and a ticker is not unique: a small coin can share
 * one with a different asset on an exchange, whose price is then shown under the wrong
 * name. A real move of more than half or double since the page was built is rare; a
 * collision is usually off by far more. Without a figure to compare with, the live one is used.
 */
export function livePriceAgrees(pagePrice: unknown, livePrice: unknown): boolean {
	if (typeof livePrice !== "number" || !Number.isFinite(livePrice) || livePrice <= 0) {
		return false;
	}
	if (typeof pagePrice !== "number" || !Number.isFinite(pagePrice) || pagePrice <= 0) {
		return true;
	}
	const ratio = livePrice / pagePrice;
	return ratio >= LEAST_RATIO && ratio <= MOST_RATIO;
}

/** A coin with its live figures laid over it, unless they are not believable for it. */
export function withLive(coin: CoinData, live: Partial<TickerData> | undefined): CoinData {
	return live && livePriceAgrees(coin.current_price, live.current_price) ? { ...coin, ...live } : coin;
}

/**
 * A widget's coins, kept current: the prices the page was rendered with, with
 * live updates laid over them as they arrive.
 */
export function useLiveCoins(coins: unknown, settings: Pick<UCWPWidgetSetting, "count" | "usd_conversion_rate">): CoinData[] {
	const shown = useMemo(() => visibleCoins(coins, settings.count), [coins, settings.count]);
	const rate = Number(settings.usd_conversion_rate);
	const { data } = useLivePrices(
		shown.map((coin) => coin.symbol),
		Number.isFinite(rate) && rate > 0 ? rate : 1
	);

	return shown.map((coin) => withLive(coin, data[coin.symbol.toUpperCase()]));
}

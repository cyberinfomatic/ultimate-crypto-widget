import { useMemo } from "react";
import { toCount } from "../ds/format";
import { CoinData, UCWPWidgetSetting } from "../types";
import useLivePrices from "./useLivePrices";

/** The coins a widget shows: as many as its "count" setting says, from the page's own data. */
export function visibleCoins(coins: unknown, count: unknown, fallback = 10): CoinData[] {
	if (!Array.isArray(coins)) {
		return [];
	}
	return coins.filter((coin): coin is CoinData => Boolean(coin) && typeof coin.symbol === "string").slice(0, toCount(count, fallback));
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

	return shown.map((coin) => ({ ...coin, ...data[coin.symbol.toUpperCase()] }));
}

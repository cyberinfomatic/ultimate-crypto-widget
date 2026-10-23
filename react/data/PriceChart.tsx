import React from "react";
import { formatPrice, LineChart, Notice, Skeleton, Sparkline, toCount } from "../ds";
import { thin } from "./api";
import { useChart } from "./useRemote";

type PriceChartProps = {
	coinId: string;
	/** A saved "number of days" setting; 7 when it is missing. */
	days: unknown;
	currency?: string;
	symbol?: string;
	/** False to wait, e.g. until the panel holding the chart is opened. */
	enabled?: boolean;
	/** A small bare line instead of the full chart. */
	spark?: boolean;
	/** What the chart shows, for screen readers, given the number of days: "Bitcoin price, last 7 days". */
	describe: (days: number) => string;
	/** Shown when the history cannot be loaded. */
	unavailable: string;
};

/** Enough points for a smooth line without drawing every sample. */
const MAX_POINTS = 120;

/**
 * A coin's price history, loaded from the plugin's API, with its loading and failed
 * states. Its wording comes from the caller, so it is translated with the calling
 * plugin's own strings.
 */
export const PriceChart = ({ coinId, days, currency, symbol, enabled = true, spark = false, describe, unavailable }: PriceChartProps) => {
	const dayCount = Math.min(365, toCount(days, 7));
	const chart = useChart(coinId, dayCount, currency, enabled);
	const label = describe(dayCount);

	if (chart.status === "idle") {
		return null;
	}
	if (chart.status === "loading") {
		return <Skeleton height={spark ? "28" : "144"} width={spark ? "96" : undefined} />;
	}
	if (chart.status === "error" || chart.data.length < 2) {
		return spark ? null : <Notice>{unavailable}</Notice>;
	}

	const points = thin(chart.data, MAX_POINTS);

	return spark ? (
		<Sparkline points={points.map((point) => point.value)} label={label} />
	) : (
		<LineChart points={points} label={label} formatValue={(value) => formatPrice(value, { currency, symbol })} />
	);
};

import React from "react";
import { __, sprintf } from "@wordpress/i18n";
import { formatPrice, LineChart, Notice, Skeleton, Sparkline, toCount } from "../ds";
import { thin } from "./api";
import { useChart } from "./useRemote";

type PriceChartProps = {
	coinId: string;
	coinName: string;
	/** A saved "number of days" setting; 7 when it is missing. */
	days: unknown;
	currency?: string;
	symbol?: string;
	/** False to wait, e.g. until the panel holding the chart is opened. */
	enabled?: boolean;
	/** A small bare line instead of the full chart. */
	spark?: boolean;
};

/** Enough points for a smooth line without drawing every sample. */
const MAX_POINTS = 120;

/** A coin's price history, loaded from the plugin's API, with its loading and failed states. */
export const PriceChart = ({ coinId, coinName, days, currency, symbol, enabled = true, spark = false }: PriceChartProps) => {
	const dayCount = Math.min(365, toCount(days, 7));
	const chart = useChart(coinId, dayCount, currency, enabled);
	/* translators: 1: coin name, 2: number of days. */
	const label = sprintf(__("%1$s price, last %2$d days", "ultimate-crypto-widget"), coinName, dayCount);

	if (chart.status === "idle") {
		return null;
	}
	if (chart.status === "loading") {
		return <Skeleton height={spark ? "28" : "144"} width={spark ? "96" : undefined} />;
	}
	if (chart.status === "error" || chart.data.length < 2) {
		return spark ? null : <Notice>{__("The price history is not available right now.", "ultimate-crypto-widget")}</Notice>;
	}

	const points = thin(chart.data, MAX_POINTS);

	return spark ? (
		<Sparkline points={points.map((point) => point.value)} label={label} />
	) : (
		<LineChart points={points} label={label} formatValue={(value) => formatPrice(value, { currency, symbol })} />
	);
};

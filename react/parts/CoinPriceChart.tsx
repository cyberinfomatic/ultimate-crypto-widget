import React from "react";
import { __, sprintf } from "@wordpress/i18n";
import { PriceChart } from "../data/PriceChart";

type CoinPriceChartProps = {
	coinId: string;
	coinName: string;
	days: unknown;
	currency?: string;
	symbol?: string;
	enabled?: boolean;
	spark?: boolean;
};

/** The core's price chart with this add-on's wording. */
export const CoinPriceChart = ({ coinName, ...chart }: CoinPriceChartProps) => (
	<PriceChart
		{...chart}
		/* translators: 1: coin name, 2: number of days. */
		describe={(days) => sprintf(__("%1$s price, last %2$d days", "ultimate-crypto-widget"), coinName, days)}
		unavailable={__("The price history is not available right now.", "ultimate-crypto-widget")}
	/>
);

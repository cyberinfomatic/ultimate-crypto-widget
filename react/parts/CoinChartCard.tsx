import React, { ReactNode } from "react";
import { CoinIdentity, Delta, Price, Surface } from "../ds";
import { CoinData, UCWPWidgetSetting } from "../types";
import { CoinPriceChart } from "./CoinPriceChart";
import "./coin-chart-card.scss";

type CoinChartCardProps = {
	coin: CoinData;
	settings: UCWPWidgetSetting;
	/** Figures shown between the heading and the chart. */
	stats?: ReactNode;
};

/** One coin: who it is, its price and change, optional figures, and its price history. */
export const CoinChartCard = ({ coin, settings, stats }: CoinChartCardProps) => (
	<Surface className="ucw-chart-card">
		<div className="ucw-chart-card__head">
			<CoinIdentity name={coin.name} symbol={coin.symbol} image={coin.image} />
			<div className="ucw-chart-card__figures">
				<Price value={coin.current_price} currency={settings.default_currency} symbol={settings.currency_symbol} size="lg" />
				<Delta value={coin.price_change_percentage_24h} pill />
			</div>
		</div>
		{stats && <div className="ucw-chart-card__stats">{stats}</div>}
		<CoinPriceChart coinId={coin.id} coinName={coin.name} days={settings.no_of_days} currency={settings.default_currency} symbol={settings.currency_symbol} />
	</Surface>
);

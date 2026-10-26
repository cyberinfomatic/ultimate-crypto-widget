import React, { ReactNode } from "react";
import { __ } from "@wordpress/i18n";
import { Cell, Delta, formatNumber, formatPrice, Notice, Price, Row, Surface, Table, CoinIdentity } from "../../ds";
import ReactRender from "../../helper-components/react-wrapper";
import { useLiveCoins } from "../../live/useLiveCoins";
import { CoinData, UCWPWidgetSetting } from "../../types";
import { CoinPriceChart } from "../../parts/CoinPriceChart";
import "./style.scss";

type Props = { coins: CoinData[]; settings: UCWPWidgetSetting };

/** The most coins put side by side, and how many when the site owner chose none. */
const MOST = 4;
const WHEN_NONE_CHOSEN = 3;

const num = (value: unknown): number | null => (typeof value === "number" && Number.isFinite(value) ? value : null);

/** How many coins to compare: up to four chosen ones, or the three biggest when none were chosen. */
export function comparedCount(chosen: unknown): number {
	const text = Array.isArray(chosen) ? chosen.join(",") : String(chosen ?? "");
	return text.trim() === "" ? WHEN_NONE_CHOSEN : MOST;
}

/** Where the greatest figure is, or -1 when there is nothing to compare or two coins tie. */
export function bestIndex(values: Array<number | null>): number {
	const known = values.filter((value): value is number => value !== null);
	if (known.length < 2) {
		return -1;
	}
	const best = Math.max(...known);
	return known.filter((value) => value === best).length > 1 ? -1 : values.indexOf(best);
}

type Metric = {
	label: string;
	value: (coin: CoinData) => number | null;
	cell: (coin: CoinData) => ReactNode;
	/** Whether the greatest figure is marked as the best one. */
	ranked: boolean;
};

/** Two to four coins side by side: price, change over three periods, size, volume, supply and a trend line. */
export const CompareCoins = ({ coins, settings }: Props) => {
	const list = useLiveCoins(coins, { count: comparedCount(settings.coins), usd_conversion_rate: settings.usd_conversion_rate, live_prices: settings.live_prices });

	if (list.length === 0) {
		return (
			<Surface width={settings.parent_width}>
				<Notice>{__("Prices are not available right now.", "ultimate-crypto-widget")}</Notice>
			</Surface>
		);
	}

	const money = (value: unknown) => formatPrice(value, { currency: settings.default_currency, symbol: settings.currency_symbol, compact: true });

	const metrics: Metric[] = [
		{ label: __("Price", "ultimate-crypto-widget"), value: (coin) => num(coin.current_price), cell: (coin) => <Price value={coin.current_price} currency={settings.default_currency} symbol={settings.currency_symbol} />, ranked: false },
		{ label: __("24h", "ultimate-crypto-widget"), value: (coin) => num(coin.price_change_percentage_24h), cell: (coin) => <Delta value={coin.price_change_percentage_24h} />, ranked: true },
		{ label: __("7 days", "ultimate-crypto-widget"), value: (coin) => num(coin.price_change_percentage_7d_in_currency), cell: (coin) => <Delta value={coin.price_change_percentage_7d_in_currency} />, ranked: true },
		{ label: __("30 days", "ultimate-crypto-widget"), value: (coin) => num(coin.price_change_percentage_30d_in_currency), cell: (coin) => <Delta value={coin.price_change_percentage_30d_in_currency} />, ranked: true },
		{ label: __("Market cap", "ultimate-crypto-widget"), value: (coin) => num(coin.market_cap), cell: (coin) => money(coin.market_cap), ranked: true },
		{ label: __("Volume (24h)", "ultimate-crypto-widget"), value: (coin) => num(coin.total_volume), cell: (coin) => money(coin.total_volume), ranked: true },
		{ label: __("Circulating supply", "ultimate-crypto-widget"), value: (coin) => num(coin.circulating_supply), cell: (coin) => formatNumber(coin.circulating_supply, { compact: true }), ranked: false },
	];

	return (
		<Surface width={settings.parent_width} className="ucw-compare">
			<div className="ucw-compare__scroll">
				<Table label={__("Compare coins", "ultimate-crypto-widget")} columns={`minmax(5.5rem, 0.8fr) repeat(${list.length}, minmax(6.5rem, 1fr))`}>
					<Row header>
						<Cell header />
						{list.map((coin) => (
							<Cell header numeric key={coin.id}>
								<CoinIdentity name={coin.name} symbol={coin.symbol} image={coin.image} />
							</Cell>
						))}
					</Row>
					{metrics.map((metric) => {
						const best = metric.ranked ? bestIndex(list.map(metric.value)) : -1;
						return (
							<Row key={metric.label}>
								<Cell muted>{metric.label}</Cell>
								{list.map((coin, index) => (
									<Cell numeric key={coin.id} data-best={best === index ? "true" : undefined}>
										{metric.cell(coin)}
									</Cell>
								))}
							</Row>
						);
					})}
					<Row>
						<Cell muted>{__("Trend", "ultimate-crypto-widget")}</Cell>
						{list.map((coin) => (
							<Cell numeric key={coin.id}>
								<CoinPriceChart coinId={coin.id} coinName={coin.name} days={settings.no_of_days} currency={settings.default_currency} symbol={settings.currency_symbol} spark />
							</Cell>
						))}
					</Row>
				</Table>
			</div>
		</Surface>
	);
};

ReactRender<Props>(CompareCoins);

import React, { CSSProperties } from "react";
import { __ } from "@wordpress/i18n";
import { directionOf, formatPercent, Notice, Price, Surface } from "../../ds";
import ReactRender from "../../helper-components/react-wrapper";
import { useLiveCoins } from "../../live/useLiveCoins";
import { CoinData, UCWPWidgetSetting } from "../../types";
import "./style.scss";

type Props = { coins: CoinData[]; settings: UCWPWidgetSetting };

const num = (value: unknown): number | null => (typeof value === "number" && Number.isFinite(value) ? value : null);

/**
 * How strongly a tile is tinted, in percent. Never past 40, so the text on it
 * keeps its contrast in both themes; a tile with no change is not tinted.
 */
export function heatOf(change: number | null): number {
	if (change === null || change === 0) {
		return 0;
	}
	return Math.min(40, Math.round(8 + Math.abs(change) * 4));
}

/** How much room a coin takes: its share of the biggest coin's market value, softened so small coins stay readable. */
export function weightOf(cap: number | null, biggest: number): number {
	if (cap === null || cap <= 0 || biggest <= 0) {
		return 1;
	}
	return Math.max(1, Math.round(Math.sqrt(cap / biggest) * 10));
}

/** Coins as tiles: bigger for a bigger market value, greener for a rise and redder for a fall. */
export const CryptoHeatmap = ({ coins, settings }: Props) => {
	const list = useLiveCoins(coins, settings);

	if (list.length === 0) {
		return (
			<Surface width={settings.parent_width}>
				<Notice>{__("Prices are not available right now.", "ultimate-crypto-widget")}</Notice>
			</Surface>
		);
	}

	const biggest = Math.max(0, ...list.map((coin) => num(coin.market_cap) ?? 0));

	return (
		<Surface width={settings.parent_width} className="ucw-heat">
			<div className="ucw-heat__tiles" role="list" aria-label={__("Market heatmap", "ultimate-crypto-widget")}>
				{list.map((coin) => {
					const change = num(coin.price_change_percentage_24h);
					const style = { flexGrow: weightOf(num(coin.market_cap), biggest), "--ucw-heat": `${heatOf(change)}%` } as CSSProperties;
					return (
						<div key={coin.id} role="listitem" className="ucw-heat__tile" data-direction={directionOf(change === null ? null : Math.round(change * 100) / 100)} style={style} title={coin.name}>
							<span className="ucw-heat__symbol">{coin.symbol}</span>
							<span className="ucw-heat__change">{formatPercent(coin.price_change_percentage_24h)}</span>
							<Price value={coin.current_price} currency={settings.default_currency} symbol={settings.currency_symbol} />
						</div>
					);
				})}
			</div>
		</Surface>
	);
};

ReactRender<Props>(CryptoHeatmap);

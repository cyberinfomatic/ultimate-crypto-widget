import React, { useId, useMemo, useState } from "react";
import { __ } from "@wordpress/i18n";
import { Cell, CoinIdentity, Delta, Notice, Price, Row, Surface, Table, Tabs, toCount } from "../../ds";
import ReactRender from "../../helper-components/react-wrapper";
import { useLiveCoins, visibleCoins } from "../../live/useLiveCoins";
import { CoinData, UCWPWidgetSetting } from "../../types";
import "./style.scss";

type Props = { coins: CoinData[]; settings: UCWPWidgetSetting };

export type Side = "gainers" | "losers";
export type Period = "24h" | "7d" | "30d";

const num = (value: unknown): number | null => (typeof value === "number" && Number.isFinite(value) ? value : null);

/** A coin's change over a period, in percent; null when it is not known. */
export function changeOf(coin: CoinData, period: Period): number | null {
	switch (period) {
		case "7d":
			return num(coin.price_change_percentage_7d_in_currency);
		case "30d":
			return num(coin.price_change_percentage_30d_in_currency);
		default:
			return num(coin.price_change_percentage_24h);
	}
}

/**
 * The biggest risers or fallers. A "gainer" really rose and a "loser" really fell:
 * when the whole market moved one way, the other list is empty rather than showing
 * the least bad coins under the wrong name.
 */
export function movers(coins: unknown, period: Period, side: Side, limit: number): CoinData[] {
	return visibleCoins(coins, Number.MAX_SAFE_INTEGER)
		.map((coin) => ({ coin, change: changeOf(coin, period) }))
		.filter((row): row is { coin: CoinData; change: number } => row.change !== null && (side === "gainers" ? row.change > 0 : row.change < 0))
		.sort((a, b) => (side === "gainers" ? b.change - a.change : a.change - b.change))
		.slice(0, limit)
		.map((row) => row.coin);
}

/** The coins that moved most, as a list the visitor can switch between risers and fallers and between periods. */
export const TopMovers = ({ coins, settings }: Props) => {
	const [side, setSide] = useState<Side>("gainers");
	const [period, setPeriod] = useState<Period>("24h");
	const listId = useId();
	const limit = toCount(settings.count, 5);

	const picked = useMemo(() => movers(coins, period, side, limit), [coins, period, side, limit]);
	const list = useLiveCoins(picked, { count: limit, usd_conversion_rate: settings.usd_conversion_rate, live_prices: settings.live_prices });
	// The change shown is the one the list was ranked by, so a live tick cannot turn a gainer red.
	const ranked = new Map(picked.map((coin) => [coin.id, coin]));

	return (
		<Surface width={settings.parent_width}>
			<div className="ucw-movers__bar">
				<Tabs
					label={__("Show", "ultimate-crypto-widget")}
					tabs={[
						{ id: "gainers", label: __("Gainers", "ultimate-crypto-widget") },
						{ id: "losers", label: __("Losers", "ultimate-crypto-widget") },
					]}
					value={side}
					onChange={setSide}
					controls={listId}
				/>
				<Tabs
					label={__("Period", "ultimate-crypto-widget")}
					tabs={[
						{ id: "24h", label: __("24h", "ultimate-crypto-widget") },
						{ id: "7d", label: __("7 days", "ultimate-crypto-widget") },
						{ id: "30d", label: __("30 days", "ultimate-crypto-widget") },
					]}
					value={period}
					onChange={setPeriod}
					controls={listId}
				/>
			</div>
			{visibleCoins(coins, 1).length === 0 ? (
				<Notice>{__("Prices are not available right now.", "ultimate-crypto-widget")}</Notice>
			) : list.length === 0 ? (
				<Notice>{__("No coins in this list for this period.", "ultimate-crypto-widget")}</Notice>
			) : (
				<Table id={listId} label={side === "gainers" ? __("Top gainers", "ultimate-crypto-widget") : __("Top losers", "ultimate-crypto-widget")} columns="auto minmax(0, 1fr) auto auto">
					{list.map((coin, index) => (
						<Row key={coin.id}>
							<Cell muted>{index + 1}</Cell>
							<Cell>
								<CoinIdentity name={coin.name} symbol={coin.symbol} image={coin.image} />
							</Cell>
							<Cell numeric>
								<Price value={coin.current_price} currency={settings.default_currency} symbol={settings.currency_symbol} />
							</Cell>
							<Cell numeric>
								<Delta value={changeOf(ranked.get(coin.id) ?? coin, period)} />
							</Cell>
						</Row>
					))}
				</Table>
			)}
		</Surface>
	);
};

ReactRender<Props>(TopMovers);

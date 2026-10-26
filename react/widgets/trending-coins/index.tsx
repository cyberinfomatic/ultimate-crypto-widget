import React from "react";
import { __ } from "@wordpress/i18n";
import { Cell, CoinIdentity, Delta, Notice, Price, Row, Surface, Table, toCount } from "../../ds";
import ReactRender from "../../helper-components/react-wrapper";
import { liveOn, livePriceAgrees } from "../../live/useLiveCoins";
import { useLivePrices } from "../../live/useLivePrices";
import { TickerData, UCWPWidgetSetting } from "../../types";

/** A coin people are looking at right now, as the server sends it. Its price is in US dollars. */
export type TrendingCoin = {
	id: string;
	name: string;
	symbol: string;
	image: string;
	rank: number;
	price: number;
	change_24h: number;
};

type Props = { trending_coins?: unknown; settings: UCWPWidgetSetting };

/** The usable coins, in the order they came. */
export function readTrending(value: unknown): TrendingCoin[] {
	if (!Array.isArray(value)) {
		return [];
	}
	return value.filter((coin): coin is TrendingCoin => Boolean(coin) && typeof coin.id === "string" && typeof coin.name === "string" && typeof coin.symbol === "string");
}

const rate = (settings: UCWPWidgetSetting): number => (Number.isFinite(Number(settings.usd_conversion_rate)) && Number(settings.usd_conversion_rate) > 0 ? Number(settings.usd_conversion_rate) : 1);

/**
 * A trending coin's price in the widget's currency and its 24h change: the live figures when there are
 * believable ones, otherwise the ones the page was drawn with.
 */
export function trendingFigures(coin: TrendingCoin, live: Partial<TickerData> | undefined, usdRate: number): { price: number | null; change: number } {
	const pagePrice = typeof coin.price === "number" ? coin.price * usdRate : null;
	if (live && livePriceAgrees(pagePrice, live.current_price)) {
		return { price: live.current_price as number, change: live.price_change_percentage_24h ?? coin.change_24h };
	}
	return { price: pagePrice, change: coin.change_24h };
}

/** The coins people search for most right now, with their price and 24h change, kept live unless live prices are off. */
export const TrendingCoins = ({ trending_coins, settings }: Props) => {
	const list = readTrending(trending_coins).slice(0, toCount(settings.count, 7));
	const usdRate = rate(settings);
	const { data } = useLivePrices(liveOn(settings.live_prices) ? list.map((coin) => coin.symbol) : [], usdRate);

	if (list.length === 0) {
		return (
			<Surface width={settings.parent_width}>
				<Notice>{__("Trending coins are not available right now.", "ultimate-crypto-widget")}</Notice>
			</Surface>
		);
	}

	return (
		<Surface width={settings.parent_width}>
			<Table label={__("Trending coins", "ultimate-crypto-widget")} columns="auto minmax(0, 1fr) auto auto">
				{list.map((coin, index) => {
					const figures = trendingFigures(coin, data[coin.symbol.toUpperCase()], usdRate);
					return (
						<Row key={coin.id}>
							<Cell muted>{index + 1}</Cell>
							<Cell>
								<CoinIdentity name={coin.name} symbol={coin.symbol} image={coin.image} />
							</Cell>
							<Cell numeric>
								<Price value={figures.price} currency={settings.default_currency} symbol={settings.currency_symbol} />
							</Cell>
							<Cell numeric>
								<Delta value={figures.change} />
							</Cell>
						</Row>
					);
				})}
			</Table>
		</Surface>
	);
};

ReactRender<Props>(TrendingCoins);

import React from "react";
import { __ } from "@wordpress/i18n";
import { cssSize, formatPrice, Notice, Stat, Surface } from "../../ds";
import ReactRender from "../../helper-components/react-wrapper";
import { useLiveCoins } from "../../live/useLiveCoins";
import { CoinData, UCWPWidgetSetting } from "../../types";
import { CoinChartCard } from "../../parts/CoinChartCard";

type Props = { coins: CoinData[]; settings: UCWPWidgetSetting };

/** Each chosen coin with its market figures and its price over the chosen number of days. */
export const HistoricalPriceChart = ({ coins, settings }: Props) => {
	const list = useLiveCoins(coins, settings);
	const money = { currency: settings.default_currency, symbol: settings.currency_symbol, compact: true };

	if (list.length === 0) {
		return (
			<Surface width={settings.parent_width}>
				<Notice>{__("Prices are not available right now.", "ultimate-crypto-widget")}</Notice>
			</Surface>
		);
	}

	return (
		<div className="ucw-card-stack" style={{ inlineSize: cssSize(settings.parent_width) }}>
			{list.map((coin) => (
				<CoinChartCard
					key={coin.id}
					coin={coin}
					settings={settings}
					stats={
						<>
							<Stat label={__("Market cap", "ultimate-crypto-widget")}>{formatPrice(coin.market_cap, money)}</Stat>
							<Stat label={__("Volume (24h)", "ultimate-crypto-widget")}>{formatPrice(coin.total_volume, money)}</Stat>
							<Stat label={__("24h high", "ultimate-crypto-widget")}>{formatPrice(coin.high_24h, { ...money, compact: false })}</Stat>
							<Stat label={__("24h low", "ultimate-crypto-widget")}>{formatPrice(coin.low_24h, { ...money, compact: false })}</Stat>
						</>
					}
				/>
			))}
		</div>
	);
};

ReactRender<Props>(HistoricalPriceChart);

import React from "react";
import { __ } from "@wordpress/i18n";
import { cssSize, formatPrice, Notice, Stat, Surface } from "../../ds";
import ReactRender from "../../helper-components/react-wrapper";
import { useLiveCoins } from "../../live/useLiveCoins";
import { CoinData, UCWPWidgetSetting } from "../../types";
import { CoinChartCard } from "../../parts/CoinChartCard";

type Props = { coins: CoinData[]; settings: UCWPWidgetSetting };

/** A grid of coins, each with its day's volume and a price chart. */
export const PriceBlock2 = ({ coins, settings }: Props) => {
	const list = useLiveCoins(coins, settings);

	if (list.length === 0) {
		return (
			<Surface width={settings.parent_width}>
				<Notice>{__("Prices are not available right now.", "ultimate-crypto-widget")}</Notice>
			</Surface>
		);
	}

	return (
		<div className="ucw-card-grid" style={{ inlineSize: cssSize(settings.parent_width) }}>
			{list.map((coin) => (
				<CoinChartCard
					key={coin.id}
					coin={coin}
					settings={settings}
					stats={<Stat label={__("Volume (24h)", "ultimate-crypto-widget")}>{formatPrice(coin.total_volume, { currency: settings.default_currency, symbol: settings.currency_symbol, compact: true })}</Stat>}
				/>
			))}
		</div>
	);
};

ReactRender<Props>(PriceBlock2);

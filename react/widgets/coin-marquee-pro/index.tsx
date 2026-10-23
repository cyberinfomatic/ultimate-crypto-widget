import React from "react";
import { __ } from "@wordpress/i18n";
import { CoinIdentity, cssSize, Delta, formatPrice, Notice, Price, Stat, Surface, Ticker, toCount } from "../../ds";
import ReactRender from "../../helper-components/react-wrapper";
import { useLiveCoins } from "../../live/useLiveCoins";
import { CoinData, UCWPWidgetSetting } from "../../types";
import { CoinPriceChart } from "../../parts/CoinPriceChart";
import "./style.scss";

type Props = { coins: CoinData[]; settings: UCWPWidgetSetting };

/**
 * A scrolling strip with more per coin than the free one: "card-002" adds
 * volume and market cap, "card-003" adds a small price line as well.
 */
export const CoinMarqueePro = ({ coins, settings }: Props) => {
	const list = useLiveCoins(coins, settings);
	const withLine = settings.card === "card-003";
	const money = { currency: settings.default_currency, symbol: settings.currency_symbol };

	if (list.length === 0) {
		return (
			<Surface width={settings.parent_width}>
				<Notice>{__("Prices are not available right now.", "ultimate-crypto-widget")}</Notice>
			</Surface>
		);
	}

	return (
		<Surface width={settings.parent_width} className="ucw-marquee-pro">
			{/* The speed setting has meant "divided by the number of coins" since 0.x. */}
			<Ticker label={__("Cryptocurrency prices", "ultimate-crypto-widget")} speed={toCount(settings.speed, 3000) / list.length}>
				{list.map((coin) => (
					<div className="ucw-marquee-pro__item" key={coin.id} style={{ minInlineSize: cssSize(settings.card_width) }}>
						<div className="ucw-marquee-pro__main">
							<CoinIdentity name={coin.name} symbol={coin.symbol} image={coin.image} />
							<div className="ucw-marquee-pro__figures">
								<Price value={coin.current_price} {...money} />
								<Delta value={coin.price_change_percentage_24h} />
							</div>
						</div>
						{withLine && <CoinPriceChart spark coinId={coin.id} coinName={coin.name} days={settings.no_of_days} currency={settings.default_currency} />}
						<Stat label={__("Volume (24h)", "ultimate-crypto-widget")}>{formatPrice(coin.total_volume, { ...money, compact: true })}</Stat>
						<Stat label={__("Market cap", "ultimate-crypto-widget")}>{formatPrice(coin.market_cap, { ...money, compact: true })}</Stat>
					</div>
				))}
			</Ticker>
		</Surface>
	);
};

ReactRender<Props>(CoinMarqueePro);

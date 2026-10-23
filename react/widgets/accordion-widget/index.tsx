import React, { useState } from "react";
import { __ } from "@wordpress/i18n";
import { CoinIdentity, Delta, Disclosure, formatPrice, Notice, Price, Stat, Surface } from "../../ds";
import ReactRender from "../../helper-components/react-wrapper";
import { useLiveCoins } from "../../live/useLiveCoins";
import { CoinData, UCWPWidgetSetting } from "../../types";
import { CoinPriceChart } from "../../parts/CoinPriceChart";
import "./style.scss";

type Props = { coins: CoinData[]; settings: UCWPWidgetSetting };

const CoinRow = ({ coin, settings }: { coin: CoinData; settings: UCWPWidgetSetting }) => {
	// The chart is fetched the first time the row is opened, and kept after that.
	const [opened, setOpened] = useState(false);
	const money = { currency: settings.default_currency, symbol: settings.currency_symbol };

	return (
		<Disclosure
			onToggle={(open) => open && setOpened(true)}
			summary={
				<span className="ucw-accordion__summary">
					<CoinIdentity name={coin.name} symbol={coin.symbol} image={coin.image} />
					<Price value={coin.current_price} {...money} />
				</span>
			}
		>
			<div className="ucw-accordion__stats">
				<Stat label={__("24h change", "ultimate-crypto-widget")}>
					<Delta value={coin.price_change_percentage_24h} />
				</Stat>
				<Stat label={__("Market cap", "ultimate-crypto-widget")}>{formatPrice(coin.market_cap, { ...money, compact: true })}</Stat>
				<Stat label={__("Volume (24h)", "ultimate-crypto-widget")}>{formatPrice(coin.total_volume, { ...money, compact: true })}</Stat>
			</div>
			<CoinPriceChart enabled={opened} coinId={coin.id} coinName={coin.name} days={settings.no_of_days} {...money} />
		</Disclosure>
	);
};

/** A list of coins; opening one shows its figures and price history. */
export const AccordionWidget = ({ coins, settings }: Props) => {
	const list = useLiveCoins(coins, settings);

	return (
		<Surface width={settings.parent_width}>
			{list.length === 0 ? <Notice>{__("Prices are not available right now.", "ultimate-crypto-widget")}</Notice> : list.map((coin) => <CoinRow key={coin.id} coin={coin} settings={settings} />)}
		</Surface>
	);
};

ReactRender<Props>(AccordionWidget);

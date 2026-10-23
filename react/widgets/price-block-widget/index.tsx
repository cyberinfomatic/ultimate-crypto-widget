import React, { useState } from "react";
import { __ } from "@wordpress/i18n";
import { useCoinInfo } from "../../data/useRemote";
import { CoinIdentity, Delta, Notice, Price, Surface, Tabs } from "../../ds";
import ReactRender from "../../helper-components/react-wrapper";
import { useLiveCoins } from "../../live/useLiveCoins";
import { CoinData, UCWPWidgetSetting } from "../../types";
import "./style.scss";

type Props = { coins: CoinData[]; settings: UCWPWidgetSetting };

/** The currencies every block offers, as they always have. */
const OFFERED = ["usd", "gbp", "eur"];

/** The currencies to switch between: the widget's own first, then the usual three, once each. */
export const blockCurrencies = (own: string | undefined): string[] => Array.from(new Set([(own || "usd").toLowerCase(), ...OFFERED]));

const PriceBlock = ({ coin, settings }: { coin: CoinData; settings: UCWPWidgetSetting }) => {
	const currencies = blockCurrencies(settings.default_currency);
	const [currency, setCurrency] = useState(currencies[0]);
	const info = useCoinInfo(coin.id);

	// The widget's own currency is kept live; the others come from the coin's details.
	const price = currency === currencies[0] ? coin.current_price : info.status === "ready" ? info.data.prices[currency] : undefined;

	return (
		<Surface className="ucw-block">
			<div className="ucw-block__head">
				<CoinIdentity name={coin.name} symbol={coin.symbol} image={coin.image} />
				<Delta value={coin.price_change_percentage_24h} pill />
			</div>
			<Price value={price} currency={currency} size="lg" />
			<Tabs
				label={__("Currency", "ultimate-crypto-widget")}
				tabs={currencies.map((code) => ({ id: code, label: code.toUpperCase() }))}
				value={currency}
				onChange={setCurrency}
			/>
		</Surface>
	);
};

/** A block per coin with a large price the visitor can switch between currencies. */
export const PriceBlocks = ({ coins, settings }: Props) => {
	const list = useLiveCoins(coins, settings);

	if (list.length === 0) {
		return (
			<Surface>
				<Notice>{__("Prices are not available right now.", "ultimate-crypto-widget")}</Notice>
			</Surface>
		);
	}

	return (
		<div className="ucw-card-grid">
			{list.map((coin) => (
				<PriceBlock key={coin.id} coin={coin} settings={settings} />
			))}
		</div>
	);
};

ReactRender<Props>(PriceBlocks);

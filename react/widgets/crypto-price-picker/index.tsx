import React from "react";
import { __ } from "@wordpress/i18n";
import { CoinIdentity, Delta, Notice, Price, Surface } from "../../ds";
import ReactRender from "../../helper-components/react-wrapper";
import { useLiveCoins } from "../../live/useLiveCoins";
import { CoinData, UCWPWidgetSetting } from "../../types";
import "./style.scss";

type Props = { coins: CoinData[]; settings: UCWPWidgetSetting };

/** One card per coin, side by side or stacked. */
export const CryptoPricePicker = ({ coins, settings }: Props) => {
	const list = useLiveCoins(coins, settings);

	if (list.length === 0) {
		return (
			<Surface>
				<Notice>{__("Prices are not available right now.", "ultimate-crypto-widget")}</Notice>
			</Surface>
		);
	}

	return (
		<div className="ucw-picker" data-orientation={settings.orientation === "vertical" ? "vertical" : "horizontal"}>
			{list.map((coin) => (
				<Surface className="ucw-picker__card" key={coin.id}>
					<div className="ucw-picker__top">
						<CoinIdentity name={coin.name} symbol={coin.symbol} image={coin.image} />
						<Delta value={coin.price_change_percentage_24h} pill />
					</div>
					<Price value={coin.current_price} currency={settings.default_currency} symbol={settings.currency_symbol} size="lg" />
				</Surface>
			))}
		</div>
	);
};

ReactRender<Props>(CryptoPricePicker);

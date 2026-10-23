import React from "react";
import { __ } from "@wordpress/i18n";
import { CoinIdentity, Delta, formatPrice, Notice, Price, Stat, Surface } from "../../ds";
import ReactRender from "../../helper-components/react-wrapper";
import { useLiveCoins } from "../../live/useLiveCoins";
import { CoinData, UCWPWidgetSetting } from "../../types";
import "./style.scss";

type Props = { coins: CoinData[]; settings: UCWPWidgetSetting };

/**
 * Price cards, in two designs: "card-002" is a compact row per coin, "card-003"
 * adds market cap and volume.
 */
export const CryptoPricePickerPro = ({ coins, settings }: Props) => {
	const list = useLiveCoins(coins, settings);
	const detailed = settings.card === "card-003";
	const money = { currency: settings.default_currency, symbol: settings.currency_symbol };

	if (list.length === 0) {
		return (
			<Surface>
				<Notice>{__("Prices are not available right now.", "ultimate-crypto-widget")}</Notice>
			</Surface>
		);
	}

	return (
		<div className="ucw-picker-pro" data-orientation={settings.orientation === "vertical" ? "vertical" : "horizontal"}>
			{list.map((coin) => (
				<Surface className="ucw-picker-pro__card" key={coin.id}>
					<div className="ucw-picker-pro__main">
						<CoinIdentity name={coin.name} symbol={coin.symbol} image={coin.image} />
						<div className="ucw-picker-pro__figures">
							<Price value={coin.current_price} {...money} />
							<Delta value={coin.price_change_percentage_24h} />
						</div>
					</div>
					{detailed && (
						<div className="ucw-picker-pro__stats">
							<Stat label={__("Market cap", "ultimate-crypto-widget")}>{formatPrice(coin.market_cap, { ...money, compact: true })}</Stat>
							<Stat label={__("Volume (24h)", "ultimate-crypto-widget")}>{formatPrice(coin.total_volume, { ...money, compact: true })}</Stat>
						</div>
					)}
				</Surface>
			))}
		</div>
	);
};

ReactRender<Props>(CryptoPricePickerPro);

import React, { useState } from "react";
import { __ } from "@wordpress/i18n";
import { CoinIdentity, IconButton, Notice, Price, Surface, Ticker, toCount } from "../../ds";
import ReactRender from "../../helper-components/react-wrapper";
import { useLiveCoins } from "../../live/useLiveCoins";
import { CoinData, UCWPWidgetSetting } from "../../types";
import "./style.scss";

type Props = { coins: CoinData[]; settings: UCWPWidgetSetting };

/**
 * Coins and prices sliding past. "card-002" is the dark design, with buttons
 * that choose which way it slides.
 */
export const PriceSlider = ({ coins, settings }: Props) => {
	const list = useLiveCoins(coins, settings);
	const [reverse, setReverse] = useState(false);
	const controlled = settings.card === "card-002";

	if (list.length === 0) {
		return (
			<Surface width={settings.parent_width}>
				<Notice>{__("Prices are not available right now.", "ultimate-crypto-widget")}</Notice>
			</Surface>
		);
	}

	return (
		<Surface width={settings.parent_width} className="ucw-slider" data-ucw-theme={controlled ? "dark" : undefined}>
			{controlled && <IconButton label={__("Slide towards the start", "ultimate-crypto-widget")} icon="previous" pressed={!reverse} onClick={() => setReverse(false)} />}
			<Ticker label={__("Cryptocurrency prices", "ultimate-crypto-widget")} speed={toCount(settings.speed, 3000) / list.length} reverse={reverse}>
				{list.map((coin) => (
					<div className="ucw-slider__item" key={coin.id}>
						<CoinIdentity name={coin.name} symbol={coin.symbol} image={coin.image} />
						<Price value={coin.current_price} currency={settings.default_currency} symbol={settings.currency_symbol} />
					</div>
				))}
			</Ticker>
			{controlled && <IconButton label={__("Slide towards the end", "ultimate-crypto-widget")} icon="next" pressed={reverse} onClick={() => setReverse(true)} />}
		</Surface>
	);
};

ReactRender<Props>(PriceSlider);

import React from "react";
import { __ } from "@wordpress/i18n";
import { CoinIdentity, cssSize, Delta, Notice, Price, Surface, Ticker, toCount } from "../../ds";
import ReactRender from "../../helper-components/react-wrapper";
import { useLiveCoins } from "../../live/useLiveCoins";
import { CoinData, UCWPWidgetSetting } from "../../types";
import "./style.scss";

type Props = { coins: CoinData[]; settings: UCWPWidgetSetting };

/**
 * The saved "speed" setting as pixels per second. Since 0.x it has meant
 * "speed divided by the number of coins", so that is kept: 3000 with ten coins
 * scrolls at 300.
 */
export const marqueeSpeed = (speed: unknown, coins: number): number => toCount(speed, 3000) / Math.max(coins, 1);

/** Coins and their prices scrolling past in a strip. */
export const CoinMarquee = ({ coins, settings }: Props) => {
	const list = useLiveCoins(coins, settings);

	if (list.length === 0) {
		return (
			<Surface width={settings.parent_width}>
				<Notice>{__("Prices are not available right now.", "ultimate-crypto-widget")}</Notice>
			</Surface>
		);
	}

	return (
		<Surface width={settings.parent_width} className="ucw-marquee">
			<Ticker label={__("Cryptocurrency prices", "ultimate-crypto-widget")} speed={marqueeSpeed(settings.speed, list.length)}>
				{list.map((coin) => (
					<div className="ucw-marquee__item" key={coin.id} style={{ minInlineSize: cssSize(settings.card_width) }}>
						<CoinIdentity name={coin.name} symbol={coin.symbol} image={coin.image} compact />
						<Price value={coin.current_price} currency={settings.default_currency} symbol={settings.currency_symbol} />
						<Delta value={coin.price_change_percentage_24h} />
					</div>
				))}
			</Ticker>
		</Surface>
	);
};

ReactRender<Props>(CoinMarquee);

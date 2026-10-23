import React from "react";
import { __ } from "@wordpress/i18n";
import { useCoinInfo } from "../../data/useRemote";
import { CoinIdentity, formatNumber, MISSING, Notice, Price, Skeleton, Stat, Surface } from "../../ds";
import ReactRender from "../../helper-components/react-wrapper";
import { useLiveCoins } from "../../live/useLiveCoins";
import { CoinData, UCWPWidgetSetting } from "../../types";
import "./style.scss";

type Props = { coins: CoinData[]; settings: UCWPWidgetSetting };

/** How much of a coin's description is shown. */
const MAX_LENGTH = 500;

/** A text cut at a word, with an ellipsis, when it is longer than the limit. */
export function excerpt(text: string, limit = MAX_LENGTH): string {
	if (text.length <= limit) {
		return text;
	}
	const cut = text.slice(0, limit);
	const lastSpace = cut.lastIndexOf(" ");
	return (lastSpace > limit * 0.6 ? cut.slice(0, lastSpace) : cut).replace(/[\s.,;:]+$/, "") + "…";
}

const InfoCard = ({ coin, settings }: { coin: CoinData; settings: UCWPWidgetSetting }) => {
	const info = useCoinInfo(coin.id);

	return (
		<Surface className="ucw-info">
			<div className="ucw-info__head">
				<CoinIdentity name={coin.name} symbol={coin.symbol} image={coin.image} />
				<div className="ucw-info__stats">
					<Stat label={__("Rank", "ultimate-crypto-widget")}>{typeof coin.market_cap_rank === "number" ? `#${formatNumber(coin.market_cap_rank)}` : MISSING}</Stat>
					<Stat label={__("Price", "ultimate-crypto-widget")}>
						<Price value={coin.current_price} currency={settings.default_currency} symbol={settings.currency_symbol} />
					</Stat>
				</div>
			</div>
			{info.status === "loading" && (
				<div className="ucw-info__loading">
					<Skeleton />
					<Skeleton />
					<Skeleton width="60%" />
				</div>
			)}
			{info.status === "ready" && info.data.description !== "" && <p className="ucw-info__text">{excerpt(info.data.description)}</p>}
		</Surface>
	);
};

/** A coin's rank, price and what it is, in a few sentences. */
export const CryptoInfoCard = ({ coins, settings }: Props) => {
	const list = useLiveCoins(coins, settings);

	if (list.length === 0) {
		return (
			<Surface>
				<Notice>{__("Prices are not available right now.", "ultimate-crypto-widget")}</Notice>
			</Surface>
		);
	}

	return (
		<div className="ucw-card-stack">
			{list.map((coin) => (
				<InfoCard key={coin.id} coin={coin} settings={settings} />
			))}
		</div>
	);
};

ReactRender<Props>(CryptoInfoCard);

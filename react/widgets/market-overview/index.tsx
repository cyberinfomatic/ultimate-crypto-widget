import React, { useEffect, useState } from "react";
import { __, sprintf } from "@wordpress/i18n";
import { endpoint, getJson, Result } from "../../data/api";
import { useEnv } from "../../data/env";
import { liveOn } from "../../live/useLiveCoins";
import { Delta, formatNumber, formatPrice, MISSING, Notice, Stat, Surface } from "../../ds";
import ReactRender from "../../helper-components/react-wrapper";
import { UCWPWidgetSetting } from "../../types";
import "./style.scss";

/** The whole market in a few numbers, as the server sends it. */
export type GlobalMarket = {
	currency: string;
	market_cap: number;
	volume: number;
	market_cap_change_24h: number;
	btc_dominance: number;
	eth_dominance: number;
	active_coins: number;
};

type Props = { global_market?: unknown; settings: UCWPWidgetSetting };

const FIELDS: Array<keyof GlobalMarket> = ["market_cap", "volume", "market_cap_change_24h", "btc_dominance", "eth_dominance", "active_coins"];

/** The figures, or null when the server had none (it sends an empty list then). */
export function readMarket(value: unknown): GlobalMarket | null {
	if (!value || typeof value !== "object" || Array.isArray(value)) {
		return null;
	}
	const given = value as Record<string, unknown>;
	if (!FIELDS.every((field) => typeof given[field] === "number" && Number.isFinite(given[field] as number)) || typeof given.currency !== "string") {
		return null;
	}
	return given as unknown as GlobalMarket;
}

/**
 * A day's trading cannot be worth more than the whole market. The source has been seen to
 * say so (a volume 65 times the market's value), and a figure that is plainly wrong is
 * better left out than shown.
 */
export const plausibleVolume = (market: Pick<GlobalMarket, "market_cap" | "volume">): boolean => market.volume >= 0 && market.volume <= market.market_cap;

/** The shares of the market, clamped so the bar never overflows: bitcoin, ether, everything else. */
export function shares(btc: number, eth: number): [number, number, number] {
	const first = Math.min(100, Math.max(0, btc));
	const second = Math.min(100 - first, Math.max(0, eth));
	return [first, second, 100 - first - second];
}

const percent = (value: number): string => `${formatNumber(value)}%`;

/** How often the figures are refreshed while live prices are on. The site caches them, so this costs CoinGecko little. */
export const MARKET_REFRESH_MS = 60000;

/** The newer figures when an answer holds usable ones; otherwise the ones already shown. */
export function nextMarket(current: GlobalMarket | null, answer: Result<unknown>): GlobalMarket | null {
	return answer.ok ? (readMarket(answer.data) ?? current) : current;
}

/**
 * The page's figures, refreshed from this site every minute while the widget's live prices are on. The exchanges'
 * price streams carry no market totals, so this is how this widget stays current.
 */
export function useLiveMarket(initial: unknown, settings: UCWPWidgetSetting): GlobalMarket | null {
	const { api } = useEnv();
	const [market, setMarket] = useState<GlobalMarket | null>(() => readMarket(initial));
	const live = liveOn(settings.live_prices);
	const currency = String(settings.default_currency ?? "usd");

	useEffect(() => {
		if (!live) {
			return undefined;
		}
		let stopped = false;
		const timer = setInterval(async () => {
			const answer = await getJson(endpoint(api, "global-market", { currency }));
			if (!stopped) {
				setMarket((current) => nextMarket(current, answer));
			}
		}, MARKET_REFRESH_MS);
		return () => {
			stopped = true;
			clearInterval(timer);
		};
	}, [api, currency, live]);

	return market;
}

/** How big the market is, how much traded, and how much of it is bitcoin and ether. */
export const MarketOverview = ({ global_market, settings }: Props) => {
	const market = useLiveMarket(global_market, settings);

	if (market === null) {
		return (
			<Surface width={settings.parent_width}>
				<Notice>{__("Market figures are not available right now.", "ultimate-crypto-widget")}</Notice>
			</Surface>
		);
	}

	const money = (value: number) => formatPrice(value, { currency: market.currency, compact: true });
	const [btc, eth, others] = shares(market.btc_dominance, market.eth_dominance);
	/* translators: 1: Bitcoin's share of the whole market in percent, 2: Ether's share. */
	const shareLabel = sprintf(__("Share of the market: Bitcoin %1$s, Ether %2$s", "ultimate-crypto-widget"), percent(market.btc_dominance), percent(market.eth_dominance));

	return (
		<Surface width={settings.parent_width} className="ucw-overview">
			<div className="ucw-overview__figures">
				<Stat label={__("Total market cap", "ultimate-crypto-widget")}>
					<span className="ucw-overview__big">{money(market.market_cap)}</span> <Delta value={market.market_cap_change_24h} pill />
				</Stat>
				<Stat label={__("Volume (24h)", "ultimate-crypto-widget")}>{plausibleVolume(market) ? money(market.volume) : MISSING}</Stat>
				<Stat label={__("Cryptocurrencies", "ultimate-crypto-widget")}>{formatNumber(market.active_coins)}</Stat>
			</div>
			<div className="ucw-overview__share" role="img" aria-label={shareLabel}>
				<span className="ucw-overview__part ucw-overview__part--btc" style={{ flexGrow: btc }} />
				<span className="ucw-overview__part ucw-overview__part--eth" style={{ flexGrow: eth }} />
				<span className="ucw-overview__part ucw-overview__part--rest" style={{ flexGrow: others }} />
			</div>
			<div className="ucw-overview__key">
				<Stat label={__("Bitcoin share", "ultimate-crypto-widget")}>{percent(market.btc_dominance)}</Stat>
				<Stat label={__("Ether share", "ultimate-crypto-widget")}>{percent(market.eth_dominance)}</Stat>
			</div>
		</Surface>
	);
};

ReactRender<Props>(MarketOverview);

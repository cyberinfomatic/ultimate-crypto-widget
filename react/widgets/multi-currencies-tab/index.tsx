import React, { useId, useState } from "react";
import { __ } from "@wordpress/i18n";
import { Cell, CoinIdentity, Delta, Notice, Price, Row, Surface, Table, Tabs } from "../../ds";
import ReactRender from "../../helper-components/react-wrapper";
import { useLiveCoins } from "../../live/useLiveCoins";
import { CoinData, DefaultCurrencyRateByCurrency, UCWPWidgetSetting } from "../../types";
import "./style.scss";

type Props = {
	coins: CoinData[];
	settings: UCWPWidgetSetting;
	/** How much one unit of the widget's own currency is in each other currency. */
	default_currencies_rate: DefaultCurrencyRateByCurrency;
};

/** From this amount up, a price is shortened (1.25M) so the column stays narrow. */
const COMPACT_FROM = 1_000_000;

/**
 * The currencies to offer, as uppercase codes with their rates: the widget's own
 * first (rate 1), then the others once each.
 */
export function currencyRates(own: string | undefined, rates: unknown): Record<string, number> {
	const out: Record<string, number> = {};
	if (own) {
		out[own.toUpperCase()] = 1;
	}
	if (rates && typeof rates === "object" && !Array.isArray(rates)) {
		for (const [code, rate] of Object.entries(rates)) {
			const key = code.toUpperCase();
			if (key !== "" && !(key in out) && typeof rate === "number" && rate > 0) {
				out[key] = rate;
			}
		}
	}
	return out;
}

/** A price list the visitor can switch between currencies. */
export const MultiCurrenciesTab = ({ coins, settings, default_currencies_rate }: Props) => {
	const list = useLiveCoins(coins, settings);
	const rates = currencyRates(settings.default_currency, default_currencies_rate);
	const codes = Object.keys(rates);
	const [chosen, setChosen] = useState(codes[0] ?? "USD");
	const tableId = useId();

	// The chosen currency can disappear if the page's data changes under us.
	const currency = chosen in rates ? chosen : (codes[0] ?? "USD");
	const rate = rates[currency] ?? 1;

	return (
		<Surface width={settings.parent_width}>
			{codes.length > 1 && (
				<div className="ucw-mct__bar">
					<Tabs label={__("Currency", "ultimate-crypto-widget")} tabs={codes.map((code) => ({ id: code, label: code }))} value={currency} onChange={setChosen} controls={tableId} />
				</div>
			)}
			{list.length === 0 ? (
				<Notice>{__("Prices are not available right now.", "ultimate-crypto-widget")}</Notice>
			) : (
				<Table id={tableId} label={__("Cryptocurrency prices", "ultimate-crypto-widget")} columns="auto minmax(0, 1fr) auto auto">
					{list.map((coin, index) => {
						const price = typeof coin.current_price === "number" ? coin.current_price * rate : null;
						return (
							<Row key={coin.id}>
								<Cell muted>{index + 1}</Cell>
								<Cell>
									<CoinIdentity name={coin.name} symbol={coin.symbol} image={coin.image} />
								</Cell>
								<Cell numeric>
									<Price value={price} currency={currency} compact={price !== null && price >= COMPACT_FROM} />
								</Cell>
								<Cell numeric>
									<Delta value={coin.price_change_percentage_24h} />
								</Cell>
							</Row>
						);
					})}
				</Table>
			)}
		</Surface>
	);
};

ReactRender<Props>(MultiCurrenciesTab);

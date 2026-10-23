import React, { useState } from "react";
import { __ } from "@wordpress/i18n";
import { formatNumber, formatPrice, NumberField, Notice, pageLocale, SelectField, Surface, toAmount } from "../../ds";
import ReactRender from "../../helper-components/react-wrapper";
import { useLivePrices } from "../../live/useLivePrices";
import { livePriceAgrees, visibleCoins } from "../../live/useLiveCoins";
import { CoinData, UCWPWidgetSetting } from "../../types";
import "./style.scss";

type Props = {
	coins: CoinData[];
	settings: UCWPWidgetSetting;
	/** How much one unit of the widget's own currency is in each other chosen currency. */
	default_currencies_rate?: unknown;
};

/** Something to convert from or to: a coin or a currency, with what one unit is worth in the widget's own currency. */
export type Unit = { key: string; label: string; kind: "coin" | "currency"; worth: number; symbol?: string };

/** The most coins offered; the list a site owner did not choose is the top hundred. */
const MOST_COINS = 100;

/** The coins and currencies on offer: every coin with a price, the widget's own currency, then the other chosen ones. */
export function unitsOf(coins: unknown, own: string | undefined, rates: unknown): Unit[] {
	const units: Unit[] = visibleCoins(coins, MOST_COINS)
		.filter((coin) => typeof coin.current_price === "number" && coin.current_price > 0)
		.map((coin) => ({ key: `coin:${coin.id}`, label: `${coin.name} (${coin.symbol.toUpperCase()})`, kind: "coin" as const, worth: coin.current_price, symbol: coin.symbol }));

	const code = (own ?? "USD").toUpperCase();
	units.push({ key: `currency:${code}`, label: code, kind: "currency", worth: 1 });

	if (rates && typeof rates === "object" && !Array.isArray(rates)) {
		for (const [other, rate] of Object.entries(rates)) {
			const upper = other.toUpperCase();
			if (typeof rate === "number" && rate > 0 && !units.some((unit) => unit.key === `currency:${upper}`)) {
				// "rate" is how many of them make one of ours, so one of them is worth 1 / rate of ours.
				units.push({ key: `currency:${upper}`, label: upper, kind: "currency", worth: 1 / rate });
			}
		}
	}
	return units;
}

/** An amount of one thing, in another. Zero when the target is worth nothing, which cannot be divided by. */
export function convert(amount: number, from: Pick<Unit, "worth">, to: Pick<Unit, "worth">): number {
	return to.worth > 0 ? (amount * from.worth) / to.worth : 0;
}

/** A converted amount: a price for a currency, enough digits for a small coin amount. */
export function showAmount(value: number, unit: Unit): string {
	if (unit.kind === "currency") {
		return formatPrice(value, { currency: unit.label });
	}
	const text = value >= 1 ? formatNumber(value) : new Intl.NumberFormat(pageLocale(), { maximumSignificantDigits: 4 }).format(value);
	return `${text} ${unit.symbol?.toUpperCase() ?? ""}`.trim();
}

/** Turns an amount of a coin or a currency into another, at the current prices. */
export const CryptoConverter = ({ coins, settings, default_currencies_rate }: Props) => {
	const units = unitsOf(coins, settings.default_currency, default_currencies_rate);
	const [amount, setAmount] = useState("1");
	const [fromKey, setFromKey] = useState("");
	const [toKey, setToKey] = useState("");

	const first = units.find((unit) => unit.kind === "coin") ?? units[0];
	const own = units.find((unit) => unit.kind === "currency") ?? units[0];
	// A choice that is no longer on offer falls back to the default one.
	const from = units.find((unit) => unit.key === fromKey) ?? first;
	const to = units.find((unit) => unit.key === toKey) ?? own;

	// The two coins being converted are kept live; the rest keep the prices the page was built with.
	const rate = Number(settings.usd_conversion_rate) > 0 ? Number(settings.usd_conversion_rate) : 1;
	const { data } = useLivePrices(
		[from, to].filter((unit): unit is Unit => Boolean(unit) && unit.kind === "coin").map((unit) => unit.symbol as string),
		rate
	);
	const current = (unit: Unit): Unit => {
		const live = unit.kind === "coin" ? data[(unit.symbol as string).toUpperCase()]?.current_price : undefined;
		return livePriceAgrees(unit.worth, live) ? { ...unit, worth: live as number } : unit;
	};

	if (units.filter((unit) => unit.kind === "coin").length === 0) {
		return (
			<Surface width={settings.parent_width}>
				<Notice>{__("Prices are not available right now.", "ultimate-crypto-widget")}</Notice>
			</Surface>
		);
	}

	const options = (kind: Unit["kind"]) =>
		units
			.filter((unit) => unit.kind === kind)
			.map((unit) => (
				<option key={unit.key} value={unit.key}>
					{unit.label}
				</option>
			));

	const live = { from: current(from), to: current(to) };
	const result = convert(toAmount(amount), live.from, live.to);
	const one = convert(1, live.from, live.to);

	const choices = (
		<>
			<optgroup label={__("Coins", "ultimate-crypto-widget")}>{options("coin")}</optgroup>
			<optgroup label={__("Currencies", "ultimate-crypto-widget")}>{options("currency")}</optgroup>
		</>
	);

	return (
		<Surface width={settings.parent_width} className="ucw-converter">
			<div className="ucw-converter__fields">
				<NumberField label={__("Amount", "ultimate-crypto-widget")} value={amount} placeholder="1" onChange={setAmount} />
				<SelectField label={__("From", "ultimate-crypto-widget")} value={from.key} onChange={setFromKey}>
					{choices}
				</SelectField>
				<SelectField label={__("To", "ultimate-crypto-widget")} value={to.key} onChange={setToKey}>
					{choices}
				</SelectField>
			</div>
			<p className="ucw-converter__result" role="status">
				{showAmount(result, live.to)}
			</p>
			<p className="ucw-converter__rate">{`1 ${live.from.kind === "coin" ? (live.from.symbol ?? "").toUpperCase() : live.from.label} = ${showAmount(one, live.to)}`}</p>
		</Surface>
	);
};

ReactRender<Props>(CryptoConverter);

import React, { useEffect, useState } from "react";
import { __, sprintf } from "@wordpress/i18n";
import { Cell, CoinIdentity, Delta, NumberField, Notice, Price, Row, Stat, Surface, Table, toAmount } from "../../ds";
import ReactRender from "../../helper-components/react-wrapper";
import { useLiveCoins } from "../../live/useLiveCoins";
import { CoinData, UCWPWidgetSetting } from "../../types";
import "./style.scss";

type Props = { coins: CoinData[]; settings: UCWPWidgetSetting };

/** Where a visitor's amounts are kept: in their own browser, never sent anywhere. */
export const STORAGE_KEY = "ucw-holdings";

export type Amounts = Record<string, string>;

/** The saved amounts, or none when they are missing, unreadable or not what this widget wrote. */
export function readAmounts(raw: string | null): Amounts {
	if (!raw) {
		return {};
	}
	try {
		const parsed: unknown = JSON.parse(raw);
		if (!parsed || typeof parsed !== "object" || Array.isArray(parsed)) {
			return {};
		}
		return Object.fromEntries(Object.entries(parsed as Record<string, unknown>).filter((entry): entry is [string, string] => typeof entry[1] === "string"));
	} catch {
		return {};
	}
}

const load = (): Amounts => {
	try {
		return readAmounts(window.localStorage.getItem(STORAGE_KEY));
	} catch {
		// Storage can be blocked (private windows, strict settings); the widget still works for the visit.
		return {};
	}
};

const save = (amounts: Amounts): void => {
	try {
		window.localStorage.setItem(STORAGE_KEY, JSON.stringify(amounts));
	} catch {
		// Not saved; nothing else to do.
	}
};

/** The whole holding's value, and how much it moved over the last day given each coin's own move. */
export function portfolio(rows: Array<{ value: number; change: number | null }>): { total: number; change: number | null } {
	const total = rows.reduce((sum, row) => sum + row.value, 0);
	if (total <= 0) {
		return { total: 0, change: null };
	}
	// What each holding was worth a day ago; a coin that lost "everything" is capped so nothing divides by zero.
	const before = rows.reduce((sum, row) => sum + (row.change === null ? row.value : row.value / (1 + Math.max(row.change, -99.99) / 100)), 0);
	return { total, change: before > 0 ? (total / before - 1) * 100 : null };
}

/** A visitor types how much of each coin they hold and sees what it is worth. */
export const HoldingsCalculator = ({ coins, settings }: Props) => {
	const list = useLiveCoins(coins, settings);
	const [amounts, setAmounts] = useState<Amounts>({});

	// Read after the first render, so the page the server built and the browser's first draw agree.
	useEffect(() => setAmounts(load()), []);

	const change = (id: string, value: string) =>
		setAmounts((current) => {
			const next = { ...current, [id]: value };
			save(next);
			return next;
		});

	/* translators: %s: a coin's name. The visitor types how much of it they hold. */
	const amountLabel = (name: string) => sprintf(__("Amount of %s", "ultimate-crypto-widget"), name);

	const clear = () => {
		setAmounts({});
		save({});
	};

	if (list.length === 0) {
		return (
			<Surface width={settings.parent_width}>
				<Notice>{__("Prices are not available right now.", "ultimate-crypto-widget")}</Notice>
			</Surface>
		);
	}

	const rows = list.map((coin) => ({ coin, value: toAmount(amounts[coin.id] ?? "") * (typeof coin.current_price === "number" ? coin.current_price : 0), change: typeof coin.price_change_percentage_24h === "number" ? coin.price_change_percentage_24h : null }));
	const whole = portfolio(rows);

	return (
		<Surface width={settings.parent_width}>
			<Table label={__("Your holdings", "ultimate-crypto-widget")} columns="minmax(0, 1fr) minmax(5rem, 8rem) minmax(5rem, auto)">
				{rows.map(({ coin, value }) => (
					<Row key={coin.id}>
						<Cell>
							<CoinIdentity name={coin.name} symbol={coin.symbol} image={coin.image} />
						</Cell>
						<Cell>
							<NumberField label={amountLabel(coin.name)} value={amounts[coin.id] ?? ""} placeholder="0" onChange={(value) => change(coin.id, value)} />
						</Cell>
						<Cell numeric>
							<Price value={value} currency={settings.default_currency} symbol={settings.currency_symbol} />
						</Cell>
					</Row>
				))}
			</Table>
			<div className="ucw-holdings__total">
				<Stat label={__("Total value", "ultimate-crypto-widget")}>
					<Price value={whole.total} currency={settings.default_currency} symbol={settings.currency_symbol} size="lg" /> {whole.change !== null && <Delta value={whole.change} pill />}
				</Stat>
				<button type="button" className="ucw-holdings__clear" onClick={clear}>
					{__("Clear", "ultimate-crypto-widget")}
				</button>
			</div>
			<p className="ucw-holdings__note">{__("Saved only in this browser.", "ultimate-crypto-widget")}</p>
		</Surface>
	);
};

ReactRender<Props>(HoldingsCalculator);

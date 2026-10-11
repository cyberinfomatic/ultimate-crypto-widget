import React from "react";
import { renderToStaticMarkup } from "react-dom/server";
import { describe, expect, it } from "vitest";
import { visibleCoins } from "../live/useLiveCoins";
import { CoinData, UCWPWidgetSetting } from "../types";
import { CoinMarquee, marqueeSpeed } from "./coin-marquee";
import { CryptoPricePicker } from "./crypto-price-picker";
import { CryptoPriceTable } from "./crypto-price-table";
import { currencyRates, MultiCurrenciesTab } from "./multi-currencies-tab";

const coin = (id: string, symbol: string, price: number | null, change: number | null): CoinData =>
	({ id, symbol, name: id[0].toUpperCase() + id.slice(1), image: `https://example.org/${id}.png`, current_price: price, price_change_percentage_24h: change }) as unknown as CoinData;

const coins = [coin("bitcoin", "btc", 67123.456, 2.41), coin("ethereum", "eth", 3456.7, -0.8), coin("tether", "usdt", 1, null)];

// Settings as the server sends them: text, exactly as stored.
const settings = (extra: Record<string, unknown> = {}) => ({ type: "x", coins: "", count: "10", default_currency: "usd", currency_symbol: "$", usd_conversion_rate: 1, ...extra }) as unknown as UCWPWidgetSetting;

const html = (node: React.ReactElement) => renderToStaticMarkup(node);

describe("visibleCoins", () => {
	it("keeps as many coins as the count setting says", () => {
		expect(visibleCoins(coins, "2")).toHaveLength(2);
		expect(visibleCoins(coins, undefined)).toHaveLength(3);
	});

	it("survives data that is not a list of coins", () => {
		expect(visibleCoins(null, "5")).toEqual([]);
		expect(visibleCoins({ error: "x" }, "5")).toEqual([]);
		expect(visibleCoins([null, { id: "x" }, coins[0]], "5")).toEqual([coins[0]]);
	});
});

describe("crypto-price-table", () => {
	it("lists each coin with its rank, price and change", () => {
		const out = html(<CryptoPriceTable coins={coins} settings={settings({ parent_width: "400" })} />);

		expect(out).toContain("inline-size:400px");
		expect(out.match(/role="row"/g)).toHaveLength(4);
		expect(out).toContain("Bitcoin");
		expect(out).toContain("67,123.46");
		expect(out).toContain("+2.41%");
	});

	it("respects the count setting", () => {
		expect(html(<CryptoPriceTable coins={coins} settings={settings({ count: "1" })} />)).not.toContain("Ethereum");
	});

	it("says so when there are no prices, instead of an empty box", () => {
		const out = html(<CryptoPriceTable coins={[]} settings={settings()} />);

		expect(out).toContain('role="status"');
		expect(out).not.toContain('role="table"');
	});

	it("does not crash on a coin with missing figures", () => {
		const out = html(<CryptoPriceTable coins={[coin("ghost", "gst", null, null)]} settings={settings()} />);

		expect(out).toContain("Ghost");
		expect(out).not.toContain("NaN");
	});
});

describe("coin-marquee", () => {
	it("shows every coin, with the saved card width", () => {
		const out = html(<CoinMarquee coins={coins} settings={settings({ card_width: "157", parent_width: "100%" })} />);

		expect(out).toContain("min-inline-size:157px");
		expect(out).toContain("67,123.46");
		// Once to read, once more for the copy that closes the loop.
		expect(out.match(/btc/g)).toHaveLength(2);
	});

	it("keeps the meaning the speed setting has had since 0.x", () => {
		expect(marqueeSpeed("3000", 10)).toBe(300);
		expect(marqueeSpeed(undefined, 10)).toBe(300);
		expect(marqueeSpeed("600", 0)).toBe(600);
	});

	it("says so when there are no prices", () => {
		expect(html(<CoinMarquee coins={[]} settings={settings()} />)).toContain('role="status"');
	});
});

describe("crypto-price-picker", () => {
	it("draws one card per coin", () => {
		const out = html(<CryptoPricePicker coins={coins} settings={settings({ orientation: "horizontal" })} />);

		expect(out.match(/ucw-picker__card/g)).toHaveLength(3);
		expect(out).toContain('data-orientation="horizontal"');
	});

	it("stacks the cards when the widget is vertical", () => {
		expect(html(<CryptoPricePicker coins={coins} settings={settings({ orientation: "vertical" })} />)).toContain('data-orientation="vertical"');
	});
});

describe("multi-currencies-tab", () => {
	const rates = { USD: 1, EUR: 0.92, NGN: 1550.5 };

	it("offers each currency once, the widget's own first", () => {
		expect(Object.keys(currencyRates("usd", rates))).toEqual(["USD", "EUR", "NGN"]);
		expect(Object.keys(currencyRates("eur", { USD: 1.08, EUR: 1 }))).toEqual(["EUR", "USD"]);
	});

	it("ignores rates that are missing or unusable", () => {
		expect(currencyRates("usd", { "": "", EUR: 0, GBP: "0.78", JPY: 149 })).toEqual({ USD: 1, JPY: 149 });
		expect(currencyRates("usd", [])).toEqual({ USD: 1 });
		expect(currencyRates(undefined, null)).toEqual({});
	});

	it("starts in the widget's own currency with a tab for each other one", () => {
		const out = html(<MultiCurrenciesTab coins={coins} settings={settings()} default_currencies_rate={rates} />);

		expect(out.match(/role="tab"/g)).toHaveLength(3);
		expect(out).toMatch(/aria-selected="true"[^>]*>USD/);
		expect(out).toContain("67,123.46");
	});

	it("shows no tabs when there is only one currency", () => {
		expect(html(<MultiCurrenciesTab coins={coins} settings={settings()} default_currencies_rate={{}} />)).not.toContain('role="tablist"');
	});
});

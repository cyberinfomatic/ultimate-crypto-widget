import React from "react";
import { renderToStaticMarkup } from "react-dom/server";
import { describe, expect, it } from "vitest";
import { CoinData, UCWPWidgetSetting } from "../types";
import { AccordionWidget } from "./accordion-widget";
import { CoinMarqueePro } from "./coin-marquee-pro";
import { CompareCoins } from "./compare-coins";
import { CryptoDateChangeTable } from "./crypto-date-change-table";
import { CryptoHeatmap } from "./crypto-heatmap";
import { CryptoInfoCard, excerpt } from "./crypto-info-card";
import { CryptoPricePickerPro } from "./crypto-price-picker-pro";
import { CryptoPriceTable2 } from "./crypto-price-table-2";
import { HistoricalPriceChart } from "./historical-price-chart";
import { HoldingsCalculator } from "./holdings-calculator";
import { blockCurrencies, PriceBlocks } from "./price-block-widget";
import { PriceBlock2 } from "./price-block-widget-2";
import { PriceSlider } from "./price-slider-widget";

const coin = (id: string, symbol: string, rank: number): CoinData =>
	({
		id,
		symbol,
		name: id[0].toUpperCase() + id.slice(1),
		image: `https://example.org/${id}.png`,
		current_price: 1000 / rank,
		market_cap: 1_680_000_000_000 / rank,
		market_cap_rank: rank,
		total_volume: 43_000_000_000 / rank,
		circulating_supply: 20_100_000 * rank,
		high_24h: 1100 / rank,
		low_24h: 900 / rank,
		price_change_percentage_24h: 2.41,
		price_change_percentage_7d_in_currency: -3.5,
		price_change_percentage_30d_in_currency: 12,
	}) as unknown as CoinData;

const coins = [coin("bitcoin", "btc", 1), coin("ethereum", "eth", 2), coin("tether", "usdt", 3)];

// Settings as the server sends them: text, exactly as stored.
const settings = (extra: Record<string, unknown> = {}) => ({ type: "x", coins: "", count: "10", default_currency: "usd", currency_symbol: "$", usd_conversion_rate: 1, ...extra }) as unknown as UCWPWidgetSetting;

const html = (node: React.ReactElement) => renderToStaticMarkup(node);

/** Every Pro view, to check what they all must do. */
const views: Array<[string, React.ComponentType<{ coins: CoinData[]; settings: UCWPWidgetSetting }>]> = [
	["accordion-widget", AccordionWidget],
	["coin-marquee-pro", CoinMarqueePro],
	["compare-coins", CompareCoins],
	["crypto-date-change-table", CryptoDateChangeTable],
	["crypto-heatmap", CryptoHeatmap],
	["crypto-info-card", CryptoInfoCard],
	["crypto-price-picker-pro", CryptoPricePickerPro],
	["crypto-price-table-2", CryptoPriceTable2],
	["historical-price-chart", HistoricalPriceChart],
	["holdings-calculator", HoldingsCalculator],
	["price-block-widget", PriceBlocks],
	["price-block-widget-2", PriceBlock2],
	["price-slider-widget", PriceSlider],
];

describe.each(views)("%s", (_name, View) => {
	it("shows the coins it was given", () => {
		const out = html(<View coins={coins} settings={settings()} />);

		expect(out).toContain("Bitcoin");
		expect(out).toContain("Ethereum");
		expect(out).not.toContain("NaN");
		expect(out).not.toContain("undefined");
	});

	it("says so when there are no prices, instead of an empty box", () => {
		expect(html(<View coins={[]} settings={settings()} />)).toContain('role="status"');
	});

	it("does not crash on a coin with missing figures", () => {
		const bare = { id: "ghost", symbol: "gst", name: "Ghost" } as unknown as CoinData;
		const out = html(<View coins={[bare]} settings={settings()} />);

		expect(out).toContain("Ghost");
		expect(out).not.toContain("NaN");
	});
});

describe("the paged tables", () => {
	it("use the count setting as the page size and offer more pages", () => {
		const out = html(<CryptoPriceTable2 coins={coins} settings={settings({ count: "2" })} />);

		expect(out).toContain("Ethereum");
		expect(out).not.toContain("Tether");
		expect(out).toContain("Page 1 of 2");
	});

	it("show no pager when everything fits", () => {
		expect(html(<CryptoPriceTable2 coins={coins} settings={settings()} />)).not.toContain("ucw-pager");
	});

	it("use the saved search placeholder", () => {
		expect(html(<CryptoDateChangeTable coins={coins} settings={settings({ search_placeholder: "Search Coin" })} />)).toContain('placeholder="Search Coin"');
	});

	it("show market cap, volume and supply in the price table", () => {
		const out = html(<CryptoPriceTable2 coins={coins.slice(0, 1)} settings={settings()} />);

		expect(out).toContain("$1.68T");
		expect(out).toContain("$43B");
		expect(out).toContain("20.1M");
	});

	it("show the change over a day, a week and a month in the date table", () => {
		const out = html(<CryptoDateChangeTable coins={coins.slice(0, 1)} settings={settings()} />);

		expect(out).toContain("+2.41%");
		expect(out).toContain("-3.50%");
		expect(out).toContain("+12.00%");
	});
});

describe("card designs", () => {
	it("adds market figures to the price cards only in the third design", () => {
		expect(html(<CryptoPricePickerPro coins={coins} settings={settings({ card: "card-002" })} />)).not.toContain("Market cap");
		expect(html(<CryptoPricePickerPro coins={coins} settings={settings({ card: "card-003" })} />)).toContain("Market cap");
	});

	it("makes the second slider design dark, with direction buttons", () => {
		const plain = html(<PriceSlider coins={coins} settings={settings({ card: "card-001" })} />);
		const dark = html(<PriceSlider coins={coins} settings={settings({ card: "card-002" })} />);

		expect(plain).not.toContain("<button");
		expect(dark).toContain('data-ucw-theme="dark"');
		expect(dark.match(/<button/g)).toHaveLength(2);
	});
});

describe("price blocks", () => {
	it("offer the widget's own currency first, then the usual three once each", () => {
		expect(blockCurrencies("usd")).toEqual(["usd", "gbp", "eur"]);
		expect(blockCurrencies("NGN")).toEqual(["ngn", "usd", "gbp", "eur"]);
		expect(blockCurrencies(undefined)).toEqual(["usd", "gbp", "eur"]);
	});
});

describe("accordion", () => {
	it("starts closed, so no chart is fetched until a row is opened", () => {
		const out = html(<AccordionWidget coins={coins} settings={settings()} />);

		expect(out.match(/<details/g)).toHaveLength(3);
		expect(out).not.toContain("<details open");
		expect(out).not.toContain("ucw-chart");
	});
});

describe("excerpt", () => {
	it("leaves a short text alone", () => {
		expect(excerpt("Short.", 20)).toBe("Short.");
	});

	it("cuts a long text at a word and marks the cut", () => {
		expect(excerpt("Bitcoin is the first decentralized cryptocurrency.", 24)).toBe("Bitcoin is the first…");
	});

	it("cuts mid-word only when there is no space to cut at", () => {
		expect(excerpt("a".repeat(30), 10)).toBe("a".repeat(10) + "…");
	});
});

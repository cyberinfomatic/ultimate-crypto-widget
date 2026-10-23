import { describe, expect, it } from "vitest";
import { endpoint, parseChart, parseCoinInfo, thin } from "./api";

describe("endpoint", () => {
	it("builds a route under a pretty API root", () => {
		expect(endpoint("https://example.org/wp-json/ultimate-crypto-widget/v1/", "coin-info", { coin_id: "bitcoin" })).toBe("https://example.org/wp-json/ultimate-crypto-widget/v1/coin-info?coin_id=bitcoin");
	});

	it("works on a site without pretty permalinks", () => {
		expect(endpoint("https://example.org/index.php?rest_route=/ultimate-crypto-widget/v1/", "coin-chart-data", { coin_id: "bitcoin", days: 7 })).toBe(
			"https://example.org/index.php?rest_route=/ultimate-crypto-widget/v1/coin-chart-data&coin_id=bitcoin&days=7"
		);
	});

	it("leaves out parameters that have no value and escapes the rest", () => {
		expect(endpoint("/api", "coins", { coins: "a,b", default_currency: undefined, x: "" })).toBe("/api/coins?coins=a%2Cb");
	});
});

describe("parseChart", () => {
	it("reads time and price pairs", () => {
		expect(parseChart({ prices: [[1000, 5], [2000, 6.5]] })).toEqual({ ok: true, data: [{ time: 1000, value: 5 }, { time: 2000, value: 6.5 }] });
	});

	it("skips rows that are not a time and a price", () => {
		expect(parseChart({ prices: [[1000, 5], null, ["x", 1], [3000]] })).toEqual({ ok: true, data: [{ time: 1000, value: 5 }] });
	});

	it("refuses an answer of the wrong shape, such as an error", () => {
		for (const body of [null, [], "x", { code: "price_data_unavailable" }, { prices: "none" }]) {
			expect(parseChart(body)).toEqual({ ok: false, kind: "shape" });
		}
	});
});

describe("parseCoinInfo", () => {
	it("reads the description as plain text and the prices by currency", () => {
		const result = parseCoinInfo({
			description: { en: 'Bitcoin is a <a href="https://example.org">currency</a>.\r\n\r\nIt is digital.' },
			market_data: { current_price: { USD: 60000, eur: 55000, bad: "x" } },
		});

		expect(result).toEqual({ ok: true, data: { description: "Bitcoin is a currency. It is digital.", prices: { usd: 60000, eur: 55000 } } });
	});

	it("copes with a coin that has neither", () => {
		expect(parseCoinInfo({})).toEqual({ ok: true, data: { description: "", prices: {} } });
	});

	it("refuses an answer that is not an object", () => {
		expect(parseCoinInfo(null)).toEqual({ ok: false, kind: "shape" });
	});
});

describe("thin", () => {
	it("keeps the first and last point and spreads the rest evenly", () => {
		expect(thin([0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10], 3)).toEqual([0, 5, 10]);
	});

	it("leaves a short list alone", () => {
		expect(thin([1, 2, 3], 10)).toEqual([1, 2, 3]);
		expect(thin([1, 2, 3], 1)).toEqual([1, 2, 3]);
	});
});

import { describe, expect, it } from "vitest";
import { CoinData, TickerData } from "../types";
import { liveOn, livePriceAgrees, liveSymbols, visibleCoins, withLive } from "./useLiveCoins";

describe("liveOn", () => {
	it("is on unless a widget turns it off, so widgets saved before the setting stay live", () => {
		for (const value of [undefined, null, "", "1", "true", "yes", "on", true]) {
			expect(liveOn(value)).toBe(true);
		}
	});

	it("is off for the ways a checkbox, switch or shortcode says no", () => {
		for (const value of ["0", "false", "no", "off", " NO ", false]) {
			expect(liveOn(value)).toBe(false);
		}
	});
});

describe("liveSymbols", () => {
	const shown = [{ symbol: "btc" }, { symbol: "eth" }] as unknown as CoinData[];

	it("follows the shown coins while live prices are on", () => {
		expect(liveSymbols(shown, "1")).toEqual(["btc", "eth"]);
	});

	it("follows nothing when they are off, so no exchange is contacted", () => {
		expect(liveSymbols(shown, "0")).toEqual([]);
	});
});

const coin = (price: unknown): CoinData => ({ id: "pearl-2", symbol: "prl", name: "Pearl", current_price: price, price_change_percentage_24h: 8.3 }) as unknown as CoinData;
const tick = (price: number, change: number): Partial<TickerData> => ({ current_price: price, price_change_percentage_24h: change });

describe("livePriceAgrees", () => {
	it("believes a live price near the page's", () => {
		expect(livePriceAgrees(100, 100.4)).toBe(true);
		expect(livePriceAgrees(100, 60)).toBe(true);
		expect(livePriceAgrees(100, 190)).toBe(true);
	});

	it("does not believe a price that is off by more than half or double", () => {
		expect(livePriceAgrees(1.87, 0.09274)).toBe(false);
		expect(livePriceAgrees(100, 49)).toBe(false);
		expect(livePriceAgrees(100, 201)).toBe(false);
	});

	it("uses a live price when the page has none to compare with", () => {
		expect(livePriceAgrees(undefined, 5)).toBe(true);
		expect(livePriceAgrees(0, 5)).toBe(true);
		expect(livePriceAgrees("x", 5)).toBe(true);
	});

	it("never uses a live price that is not a usable number", () => {
		expect(livePriceAgrees(100, undefined)).toBe(false);
		expect(livePriceAgrees(100, 0)).toBe(false);
		expect(livePriceAgrees(100, -3)).toBe(false);
		expect(livePriceAgrees(100, NaN)).toBe(false);
		expect(livePriceAgrees(undefined, Infinity)).toBe(false);
	});
});

describe("withLive", () => {
	it("lays the live figures over the coin", () => {
		const merged = withLive(coin(1.87), tick(1.9, 8.9));

		expect(merged.current_price).toBe(1.9);
		expect(merged.price_change_percentage_24h).toBe(8.9);
		expect(merged.name).toBe("Pearl");
	});

	it("leaves the coin as the page built it when the live figures are for another asset", () => {
		// The ticker PRL also belongs to a coin worth five cents on an exchange.
		const page = coin(1.87);

		expect(withLive(page, tick(0.09274, -3.45))).toBe(page);
	});

	it("leaves the coin alone when nothing live has arrived", () => {
		const page = coin(1.87);

		expect(withLive(page, undefined)).toBe(page);
	});
});

describe("visibleCoins", () => {
	it("takes as many as asked, and skips anything that is not a coin", () => {
		expect(visibleCoins([coin(1), null, { id: "x" }, coin(2), coin(3)], 2)).toHaveLength(2);
		expect(visibleCoins("nonsense", 2)).toEqual([]);
	});
});

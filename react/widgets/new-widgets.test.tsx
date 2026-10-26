import React from "react";
import { renderToStaticMarkup } from "react-dom/server";
import { describe, expect, it } from "vitest";
import { CoinData, UCWPWidgetSetting } from "../types";
import { bestIndex, CompareCoins, comparedCount } from "./compare-coins";
import { convert, CryptoConverter, showAmount, unitsOf } from "./crypto-converter";
import { CryptoHeatmap, heatOf, weightOf } from "./crypto-heatmap";
import { HoldingsCalculator, portfolio, readAmounts } from "./holdings-calculator";
import { MarketOverview, nextMarket, plausibleVolume, readMarket, shares } from "./market-overview";
import { changeOf, movers, TopMovers } from "./top-movers";
import { readTrending, trendingFigures, TrendingCoins } from "./trending-coins";

const coin = (id: string, symbol: string, price: number, day: number, week: number, month: number, cap = 1_000_000_000): CoinData =>
	({
		id,
		symbol,
		name: id[0].toUpperCase() + id.slice(1),
		image: `https://example.org/${id}.png`,
		current_price: price,
		market_cap: cap,
		market_cap_rank: 1,
		total_volume: 5_000_000,
		circulating_supply: 20_000_000,
		price_change_percentage_24h: day,
		price_change_percentage_7d_in_currency: week,
		price_change_percentage_30d_in_currency: month,
	}) as unknown as CoinData;

const coins = [coin("bitcoin", "btc", 80000, 2, -3, 10, 1_600_000_000_000), coin("ethereum", "eth", 2400, -1.5, 4, -8, 290_000_000_000), coin("solana", "sol", 100, 6, 12, 30, 50_000_000_000), coin("dogecoin", "doge", 0.2, -4, -9, -20, 25_000_000_000)];

const settings = (extra: Record<string, unknown> = {}) => ({ type: "x", coins: "", count: "10", default_currency: "usd", currency_symbol: "$", usd_conversion_rate: 1, ...extra }) as unknown as UCWPWidgetSetting;
const html = (node: React.ReactElement) => renderToStaticMarkup(node);

describe("compare coins", () => {
	it("compares three coins when none were chosen and four when some were", () => {
		expect(comparedCount("")).toBe(3);
		expect(comparedCount("bitcoin,ethereum")).toBe(4);
		expect(comparedCount(["bitcoin"])).toBe(4);
		expect(comparedCount(undefined)).toBe(3);
	});

	it("puts one column per coin, up to the limit", () => {
		const none = html(<CompareCoins coins={coins} settings={settings()} />);
		const some = html(<CompareCoins coins={coins} settings={settings({ coins: "bitcoin,ethereum,solana,dogecoin" })} />);

		expect(none.match(/role="columnheader"/g)).toHaveLength(4); // the empty corner and three coins
		expect(some.match(/role="columnheader"/g)).toHaveLength(5);
		expect(none).not.toContain("Dogecoin");
		expect(some).toContain("Dogecoin");
	});

	it("marks the best figure of a row, and no one when the coins tie or only one has a figure", () => {
		expect(bestIndex([2, -1.5, 6])).toBe(2);
		expect(bestIndex([null, 3, 1])).toBe(1);
		expect(bestIndex([4, 4, 1])).toBe(-1);
		expect(bestIndex([null, null, 5])).toBe(-1);
		expect(bestIndex([])).toBe(-1);

		expect(html(<CompareCoins coins={coins} settings={settings()} />)).toContain('data-best="true"');
	});

	it("labels its rows and shows the figures", () => {
		const out = html(<CompareCoins coins={coins} settings={settings()} />);

		for (const label of ["Price", "24h", "7 days", "30 days", "Market cap", "Volume (24h)", "Circulating supply", "Trend"]) {
			expect(out).toContain(label);
		}
		expect(out).toContain("+6.00%");
	});
});

describe("top gainers and losers", () => {
	it("ranks by the chosen period", () => {
		expect(movers(coins, "24h", "gainers", 5).map((c) => c.id)).toEqual(["solana", "bitcoin"]);
		expect(movers(coins, "7d", "gainers", 5).map((c) => c.id)).toEqual(["solana", "ethereum"]);
		expect(movers(coins, "30d", "losers", 5).map((c) => c.id)).toEqual(["dogecoin", "ethereum"]);
	});

	it("never calls a coin that fell a gainer, nor one that rose a loser", () => {
		const falling = [coin("a", "a", 1, -1, -1, -1), coin("b", "b", 1, -2, -2, -2)];

		expect(movers(falling, "24h", "gainers", 5)).toEqual([]);
		expect(movers(falling, "24h", "losers", 5).map((c) => c.id)).toEqual(["b", "a"]);
	});

	it("stops at the limit and ignores coins with no figure", () => {
		const bare = { id: "ghost", symbol: "gst", name: "Ghost" } as unknown as CoinData;

		expect(movers([...coins, bare], "24h", "losers", 1)).toHaveLength(1);
		expect(movers([bare], "24h", "gainers", 5)).toEqual([]);
		expect(movers("nonsense", "24h", "gainers", 5)).toEqual([]);
		expect(changeOf(bare, "7d")).toBeNull();
	});

	it("shows the risers first, with a tab for the fallers and each period", () => {
		const out = html(<TopMovers coins={coins} settings={settings({ count: "5" })} />);

		expect(out.indexOf("Solana")).toBeLessThan(out.indexOf("Bitcoin"));
		expect(out).not.toContain("Dogecoin");
		for (const label of ["Gainers", "Losers", "24h", "7 days", "30 days"]) {
			expect(out).toContain(label);
		}
	});

	it("says so when there are no prices", () => {
		expect(html(<TopMovers coins={[]} settings={settings()} />)).toContain("Prices are not available right now.");
	});
});

describe("market overview", () => {
	const market = { currency: "usd", market_cap: 2_760_000_000_000, volume: 98_400_000_000, market_cap_change_24h: -0.74, btc_dominance: 58.8, eth_dominance: 10.6, active_coins: 17342 };

	it("takes newer figures from a refresh, and keeps the old ones when it fails or brings nothing usable", () => {
		const newer = { ...market, market_cap: 2_800_000_000_000 };
		expect(nextMarket(market, { ok: true, data: newer })).toEqual(newer);
		expect(nextMarket(market, { ok: false, kind: "network" })).toEqual(market);
		expect(nextMarket(market, { ok: true, data: [] })).toEqual(market);
		expect(nextMarket(null, { ok: true, data: newer })).toEqual(newer);
	});

	it("reads the figures the server sends, and nothing else", () => {
		expect(readMarket(market)).toEqual(market);
		expect(readMarket([])).toBeNull();
		expect(readMarket(null)).toBeNull();
		expect(readMarket({ ...market, market_cap: "a lot" })).toBeNull();
		expect(readMarket({ ...market, currency: undefined })).toBeNull();
	});

	it("splits the bar so it always adds up to a whole", () => {
		expect(shares(58.8, 10.6)).toEqual([58.8, 10.6, expect.closeTo(30.6, 5)]);
		expect(shares(80, 40)).toEqual([80, 20, 0]);
		expect(shares(-5, 200)).toEqual([0, 100, 0]);
	});

	it("shows size, volume, change, coins and the shares", () => {
		const out = html(<MarketOverview global_market={market} settings={settings()} />);

		expect(out).toContain("$2.76T");
		expect(out).toContain("$98.4B");
		expect(out).toContain("-0.74%");
		expect(out).toContain("58.8%");
		expect(out).toContain("10.6%");
		expect(out).toContain("17,342");
		expect(out).toContain('role="img"');
		expect(out).not.toContain("NaN");
	});

	it("leaves out a volume bigger than the whole market, which the source has been seen to send", () => {
		expect(plausibleVolume(market)).toBe(true);
		expect(plausibleVolume({ market_cap: 2.7e12, volume: 1.78e14 })).toBe(false);
		expect(plausibleVolume({ market_cap: 2.7e12, volume: -1 })).toBe(false);

		const out = html(<MarketOverview global_market={{ ...market, volume: 1.78e14 }} settings={settings()} />);
		expect(out).not.toContain("178");
		expect(out).toContain("$2.76T");
	});

	it("says so when the server had no figures", () => {
		expect(html(<MarketOverview global_market={[]} settings={settings()} />)).toContain("Market figures are not available right now.");
	});
});

describe("trending coins", () => {
	const trending = [
		{ id: "pepe", name: "Pepe", symbol: "PEPE", image: "https://example.org/pepe.png", rank: 38, price: 0.0000089, change_24h: 12.4 },
		{ id: "solana", name: "Solana", symbol: "SOL", image: "", rank: 6, price: 102.25, change_24h: -1.99 },
	];

	it("keeps only usable coins, in order", () => {
		expect(readTrending([...trending, null, { id: 3 }, "x"]).map((c) => c.id)).toEqual(["pepe", "solana"]);
		expect(readTrending("nonsense")).toEqual([]);
		expect(readTrending([])).toEqual([]);
	});

	it("shows the coins with their change and a price in the widget's currency", () => {
		const out = html(<TrendingCoins trending_coins={trending} settings={settings({ usd_conversion_rate: 2, default_currency: "usd" })} />);

		expect(out).toContain("Pepe");
		expect(out).toContain("+12.40%");
		expect(out).toContain("$204.50");
	});

	it("shows as many as the count says", () => {
		const out = html(<TrendingCoins trending_coins={trending} settings={settings({ count: "1" })} />);

		expect(out).toContain("Pepe");
		expect(out).not.toContain("Solana");
	});

	it("lays a believable live price and change over the page's figures", () => {
		const live = { current_price: 210, price_change_percentage_24h: 3.5 };
		expect(trendingFigures(trending[1], live, 2)).toEqual({ price: 210, change: 3.5 });
	});

	it("keeps the page's figures when there is no live price, or one that is not believable", () => {
		expect(trendingFigures(trending[1], undefined, 2)).toEqual({ price: 204.5, change: -1.99 });
		// A ticker shared with a different asset: far from the page's price.
		expect(trendingFigures(trending[1], { current_price: 0.4 }, 2)).toEqual({ price: 204.5, change: -1.99 });
	});

	it("says so when nothing is trending", () => {
		expect(html(<TrendingCoins trending_coins={[]} settings={settings()} />)).toContain("Trending coins are not available right now.");
	});
});

describe("heatmap", () => {
	it("tints more for a bigger move but never past the cap", () => {
		expect(heatOf(0)).toBe(0);
		expect(heatOf(null)).toBe(0);
		expect(heatOf(1)).toBe(12);
		expect(heatOf(-1)).toBe(12);
		expect(heatOf(50)).toBe(40);
	});

	it("gives a bigger coin more room, and every coin some", () => {
		expect(weightOf(1_000, 1_000)).toBe(10);
		expect(weightOf(250, 1_000)).toBe(5);
		expect(weightOf(1, 1_000_000)).toBe(1);
		expect(weightOf(null, 1_000)).toBe(1);
		expect(weightOf(0, 0)).toBe(1);
	});

	it("draws a tile per coin, each marked up or down", () => {
		const out = html(<CryptoHeatmap coins={coins} settings={settings({ count: "4" })} />);

		expect(out.match(/role="listitem"/g)).toHaveLength(4);
		expect(out).toContain('data-direction="up"');
		expect(out).toContain('data-direction="down"');
		expect(out).toContain("--ucw-heat");
	});
});

describe("holdings calculator", () => {
	it("reads saved amounts and shrugs off anything else", () => {
		expect(readAmounts('{"bitcoin":"0.5","ethereum":"2"}')).toEqual({ bitcoin: "0.5", ethereum: "2" });
		expect(readAmounts('{"bitcoin":0.5,"ethereum":"2"}')).toEqual({ ethereum: "2" });
		expect(readAmounts("[1,2]")).toEqual({});
		expect(readAmounts("not json")).toEqual({});
		expect(readAmounts(null)).toEqual({});
		expect(readAmounts("")).toEqual({});
	});

	it("adds up the holdings and how much they moved in a day", () => {
		// 100 that rose 10% (was 90.91) and 100 that fell 10% (was 111.11): worth 200 now, 202.02 before.
		const whole = portfolio([
			{ value: 100, change: 10 },
			{ value: 100, change: -10 },
		]);

		expect(whole.total).toBe(200);
		expect(whole.change).toBeCloseTo((200 / 202.0202 - 1) * 100, 3);
	});

	it("has no change to show for nothing held, and does not divide by zero for a collapse", () => {
		expect(portfolio([{ value: 0, change: 5 }])).toEqual({ total: 0, change: null });
		expect(portfolio([])).toEqual({ total: 0, change: null });
		expect(Number.isFinite(portfolio([{ value: 10, change: -100 }]).change)).toBe(true);
		expect(portfolio([{ value: 10, change: null }]).change).toBeCloseTo(0);
	});

	it("starts with empty boxes and a note that nothing leaves the browser", () => {
		const out = html(<HoldingsCalculator coins={coins} settings={settings()} />);

		expect(out).toContain('aria-label="Amount of Bitcoin"');
		expect(out).toContain("Total value");
		expect(out).toContain("Saved only in this browser.");
		expect(out).toContain("Clear");
	});
});

describe("converter", () => {
	const rates = { EUR: 0.92, NGN: 1550 };

	it("offers every coin with a price, the widget's currency and the others once", () => {
		const bare = { id: "ghost", symbol: "gst", name: "Ghost" } as unknown as CoinData;
		const units = unitsOf([...coins, bare], "usd", { ...rates, usd: 1, EUR: 0.92 });

		expect(units.map((u) => u.key)).toEqual(["coin:bitcoin", "coin:ethereum", "coin:solana", "coin:dogecoin", "currency:USD", "currency:EUR", "currency:NGN"]);
		// One euro is worth 1 / 0.92 of a dollar.
		expect(units.find((u) => u.key === "currency:EUR")?.worth).toBeCloseTo(1 / 0.92);
	});

	it("ignores rates that are not usable", () => {
		const units = unitsOf(coins, "usd", { EUR: 0, GBP: "x", NGN: 1550 });

		expect(units.filter((u) => u.kind === "currency").map((u) => u.label)).toEqual(["USD", "NGN"]);
		expect(unitsOf(coins, "usd", [])).toHaveLength(coins.length + 1);
		expect(unitsOf("nonsense", "usd", null).map((u) => u.key)).toEqual(["currency:USD"]);
	});

	it("converts through the widget's own currency", () => {
		const [bitcoin, ethereum, , , dollar, euro] = unitsOf(coins, "usd", rates);

		expect(convert(2, bitcoin, dollar)).toBe(160000);
		expect(convert(1, bitcoin, ethereum)).toBeCloseTo(80000 / 2400);
		expect(convert(100, dollar, euro)).toBeCloseTo(92);
		expect(convert(5, bitcoin, { worth: 0 })).toBe(0);
	});

	it("shows a price for a currency and enough digits for a small coin amount", () => {
		const [bitcoin, , , , dollar] = unitsOf(coins, "usd", rates);

		expect(showAmount(1234.5, dollar)).toBe("$1,234.50");
		expect(showAmount(0.00012345, bitcoin)).toBe("0.0001235 BTC");
		expect(showAmount(33.333333, bitcoin)).toBe("33.33 BTC");
	});

	it("starts at one coin in the widget's currency", () => {
		const out = html(<CryptoConverter coins={coins} settings={settings()} default_currencies_rate={rates} />);

		expect(out).toContain('aria-label="Amount"');
		expect(out).toContain('label="Coins"');
		expect(out).toContain('label="Currencies"');
		expect(out).toContain("$80,000.00");
		expect(out).toContain("1 BTC = $80,000.00");
	});

	it("says so when there are no prices", () => {
		expect(html(<CryptoConverter coins={[]} settings={settings()} />)).toContain("Prices are not available right now.");
	});
});

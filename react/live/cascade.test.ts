import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";
import { cascade } from "./cascade";
import { binanceSpec, coinbaseSpec, krakenSpec } from "./feeds";
import { LiveFeed, Tick } from "./types";
import { toTickerData } from "./useLivePrices";

/**
 * A feed the test drives by hand.
 */
const fakeFeed = (name: string) => {
	const state = {
		symbols: [] as string[][],
		tick: (_symbol: string, _price: number) => {},
		down: () => {},
		stopped: 0,
	};
	const feed: LiveFeed = {
		name,
		subscribe(symbols, onTick, onDown) {
			state.symbols.push(symbols);
			state.tick = (symbol, price) => onTick({ symbol, price, time: 0 });
			state.down = onDown;
			return () => {
				state.stopped += 1;
			};
		},
	};
	return { feed, state };
};

beforeEach(() => vi.useFakeTimers());
afterEach(() => vi.useRealTimers());

describe("cascade", () => {
	it("asks the first feed for every symbol, uppercased and without repeats", () => {
		const a = fakeFeed("a");
		cascade([a.feed], ["btc", "eth", "BTC"], () => {});

		expect(a.state.symbols).toEqual([["BTC", "ETH"]]);
	});

	it("does not bother the second feed while the first one covers everything", () => {
		const a = fakeFeed("a");
		const b = fakeFeed("b");
		cascade([a.feed, b.feed], ["BTC", "ETH"], () => {});

		a.state.tick("BTC", 1);
		a.state.tick("ETH", 2);
		vi.advanceTimersByTime(60000);

		expect(b.state.symbols).toEqual([]);
	});

	it("asks the next feed only for what the first one has not priced in time", () => {
		const a = fakeFeed("a");
		const b = fakeFeed("b");
		const seen: string[] = [];
		cascade([a.feed, b.feed], ["BTC", "BNB"], (tick, feed) => seen.push(`${feed}:${tick.symbol}`), { patienceMs: 1000 });

		a.state.tick("BTC", 1);
		vi.advanceTimersByTime(1000);
		b.state.tick("BNB", 2);

		expect(b.state.symbols).toEqual([["BNB"]]);
		expect(seen).toEqual(["a:BTC", "b:BNB"]);
	});

	it("hands everything to the next feed at once when a feed goes down", () => {
		const a = fakeFeed("a");
		const b = fakeFeed("b");
		cascade([a.feed, b.feed], ["BTC", "ETH"], () => {}, { patienceMs: 1000 });

		a.state.tick("BTC", 1);
		a.state.down();

		expect(b.state.symbols).toEqual([["BTC", "ETH"]]);
	});

	it("never asks a feed twice for the same symbol", () => {
		const a = fakeFeed("a");
		const b = fakeFeed("b");
		cascade([a.feed, b.feed], ["BTC", "BNB"], () => {}, { patienceMs: 1000 });

		a.state.tick("BTC", 1);
		vi.advanceTimersByTime(1000); // BNB goes to b.
		a.state.down(); // Now BTC goes too, but BNB is not asked for again.

		expect(b.state.symbols).toEqual([["BNB"], ["BTC"]]);
	});

	it("walks down the whole list for a coin nobody lists", () => {
		const feeds = [fakeFeed("a"), fakeFeed("b"), fakeFeed("c")];
		cascade(feeds.map((f) => f.feed), ["OBSCURE"], () => {}, { patienceMs: 1000 });

		vi.advanceTimersByTime(1000);
		vi.advanceTimersByTime(1000);
		vi.advanceTimersByTime(1000);

		expect(feeds.map((f) => f.state.symbols)).toEqual([[["OBSCURE"]], [["OBSCURE"]], [["OBSCURE"]]]);
	});

	it("stops every feed and ignores late ticks once stopped", () => {
		const a = fakeFeed("a");
		const b = fakeFeed("b");
		const seen: Tick[] = [];
		const stop = cascade([a.feed, b.feed], ["BTC", "BNB"], (tick) => seen.push(tick), { patienceMs: 1000 });
		vi.advanceTimersByTime(1000);

		stop();
		a.state.tick("BTC", 1);
		vi.advanceTimersByTime(5000);

		expect(a.state.stopped).toBe(1);
		expect(b.state.stopped).toBe(1);
		expect(seen).toEqual([]);
	});
});

describe("exchange messages", () => {
	it("reads a Coinbase ticker and works out the 24-hour change", () => {
		const [tick] = coinbaseSpec.parse({
			type: "ticker",
			product_id: "BTC-USD",
			price: "110",
			open_24h: "100",
			high_24h: "120",
			low_24h: "90",
			volume_24h: "5",
			time: "2026-10-01T12:00:00.000Z",
		});

		expect(tick).toMatchObject({ symbol: "BTC", price: 110, change24h: 10, changePercent24h: 10, high24h: 120 });
		expect(coinbaseSpec.subscribeMessage(["BTC"])).toEqual({ type: "subscribe", product_ids: ["BTC-USD"], channels: ["ticker"] });
	});

	it("reads a Kraken ticker", () => {
		const ticks = krakenSpec.parse({ channel: "ticker", type: "update", data: [{ symbol: "ETH/USD", last: 3000, change_pct: -1.5 }] });

		expect(ticks).toMatchObject([{ symbol: "ETH", price: 3000, changePercent24h: -1.5 }]);
	});

	it("reads a Binance ticker and never subscribes to a USDT/USDT pair", () => {
		const [tick] = binanceSpec.parse({ stream: "bnbusdt@ticker", data: { s: "BNBUSDT", c: "580.5", P: "2.1", E: 1700000000000 } });

		expect(tick).toMatchObject({ symbol: "BNB", price: 580.5, changePercent24h: 2.1, time: 1700000000000 });
		expect((binanceSpec.subscribeMessage(["BTC", "USDT"]) as { params: string[] }).params).toEqual(["btcusdt@ticker"]);
	});

	it("ignores messages that are not ticks", () => {
		expect(coinbaseSpec.parse({ type: "subscriptions" })).toEqual([]);
		expect(krakenSpec.parse({ channel: "heartbeat" })).toEqual([]);
		expect(binanceSpec.parse({ result: null, id: "1" })).toEqual([]);
		expect(coinbaseSpec.parse(null)).toEqual([]);
	});
});

describe("toTickerData", () => {
	it("converts dollar prices into the widget's currency and leaves percentages alone", () => {
		const data = toTickerData({ symbol: "BTC", price: 100, high24h: 110, changePercent24h: 5, time: 0 }, 0.92);

		expect(data.symbol).toBe("btc");
		expect(data.current_price).toBeCloseTo(92);
		expect(data.high_24h).toBeCloseTo(101.2);
		expect(data.price_change_percentage_24h).toBe(5);
	});

	it("leaves out what the feed did not send, so the page's own value is kept", () => {
		const data = toTickerData({ symbol: "BTC", price: 100, time: 0 }, 1);

		expect(data).not.toHaveProperty("total_volume");
		expect(data).not.toHaveProperty("price_change_percentage_24h");
	});
});

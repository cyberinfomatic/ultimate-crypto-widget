import { num, socketFeed, SocketFeedSpec } from "./socket-feed";
import { Tick } from "./types";

type Json = Record<string, any>;

/**
 * Coinbase Exchange's public ticker. Available to visitors in the United States.
 */
export const coinbaseSpec: SocketFeedSpec = {
	name: "coinbase",
	url: "wss://ws-feed.exchange.coinbase.com",
	subscribeMessage: (symbols) => ({
		type: "subscribe",
		product_ids: symbols.map((symbol) => `${symbol}-USD`),
		channels: ["ticker"],
	}),
	parse(message) {
		const m = message as Json;
		const price = num(m?.price);
		if (m?.type !== "ticker" || typeof m.product_id !== "string" || price === undefined) {
			return [];
		}
		const open = num(m.open_24h);
		const tick: Tick = {
			symbol: m.product_id.replace(/-USD$/, ""),
			price,
			high24h: num(m.high_24h),
			low24h: num(m.low_24h),
			volume24h: num(m.volume_24h),
			time: Date.parse(m.time) || Date.now(),
		};
		if (open !== undefined && open > 0) {
			tick.change24h = price - open;
			tick.changePercent24h = ((price - open) / open) * 100;
		}
		return [tick];
	},
};

/**
 * Kraken's public ticker (WebSocket API v2).
 */
export const krakenSpec: SocketFeedSpec = {
	name: "kraken",
	url: "wss://ws.kraken.com/v2",
	subscribeMessage: (symbols) => ({
		method: "subscribe",
		params: { channel: "ticker", symbol: symbols.map((symbol) => `${symbol}/USD`) },
	}),
	parse(message) {
		const m = message as Json;
		if (m?.channel !== "ticker" || !Array.isArray(m.data)) {
			return [];
		}
		const ticks: Tick[] = [];
		m.data.forEach((row: Json) => {
			const price = num(row?.last);
			if (typeof row?.symbol !== "string" || price === undefined) {
				return;
			}
			ticks.push({
				symbol: row.symbol.replace(/\/USD$/, ""),
				price,
				high24h: num(row.high),
				low24h: num(row.low),
				change24h: num(row.change),
				changePercent24h: num(row.change_pct),
				volume24h: num(row.volume),
				time: Date.now(),
			});
		});
		return ticks;
	},
};

/**
 * Binance's public ticker stream, quoted in USDT. Binance refuses visitors from
 * the United States, so it is never the only feed.
 */
export const binanceSpec: SocketFeedSpec = {
	name: "binance",
	url: "wss://stream.binance.com:9443/stream?streams=",
	subscribeMessage: (symbols) => ({
		id: Date.now().toString(),
		method: "SUBSCRIBE",
		// A dollar stablecoin has no pair against itself.
		params: symbols.filter((symbol) => symbol !== "USDT").map((symbol) => `${symbol.toLowerCase()}usdt@ticker`),
	}),
	parse(message) {
		const data = (message as Json)?.data as Json | undefined;
		const price = num(data?.c);
		if (!data || typeof data.s !== "string" || price === undefined) {
			return [];
		}
		return [
			{
				symbol: data.s.replace(/USDT$/, ""),
				price,
				high24h: num(data.h),
				low24h: num(data.l),
				change24h: num(data.p),
				changePercent24h: num(data.P),
				volume24h: num(data.v),
				time: num(data.E) ?? Date.now(),
			},
		];
	},
};

export const coinbase = socketFeed(coinbaseSpec);
export const kraken = socketFeed(krakenSpec);
export const binance = socketFeed(binanceSpec);

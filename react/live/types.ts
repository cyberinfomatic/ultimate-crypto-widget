/**
 * A live price update for one coin, in US dollars.
 */
export interface Tick {
	/** Uppercase ticker symbol, e.g. "BTC". */
	symbol: string;
	price: number;
	high24h?: number;
	low24h?: number;
	change24h?: number;
	changePercent24h?: number;
	// No volume: an exchange reports its own, counted in coins, which is not the
	// market-wide figure in money that the page already has.
	/** Milliseconds since the epoch. */
	time: number;
}

/**
 * Somewhere live prices come from. A feed is asked for a set of symbols and
 * reports each update it gets; it may not know every symbol, and it may go down.
 */
export interface LiveFeed {
	readonly name: string;

	/**
	 * Start sending ticks for these symbols.
	 *
	 * @param symbols Uppercase ticker symbols.
	 * @param onTick  Called for every update.
	 * @param onDown  Called once if the feed fails or closes on its own.
	 * @return Stops the feed. Safe to call more than once.
	 */
	subscribe(symbols: string[], onTick: (tick: Tick) => void, onDown: () => void): () => void;
}

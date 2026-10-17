<?php

namespace Cyberinfomatic\UltimateCryptoWidget\Data;

/**
 * Where prices come from. The widgets, the REST routes and the admin screens only
 * know this interface, so the source can be swapped (see the ucwp_price_provider filter).
 *
 * Every method throws PriceDataException when the data cannot be had.
 */
interface PriceProvider
{
	/**
	 * Coins with their market data, most valuable first.
	 *
	 * @param string        $currency Lowercase currency code, e.g. "usd".
	 * @param string[]|null $coinIds  Limit to these coin ids; null for the top coins.
	 * @return array<int, array<string, mixed>>
	 */
	public function markets(string $currency = 'usd', ?array $coinIds = null): array;

	/**
	 * Everything known about one coin.
	 *
	 * @return array<string, mixed>
	 */
	public function coin(string $coinId): array;

	/**
	 * Price history for one coin.
	 *
	 * @return array<string, mixed>
	 */
	public function chart(string $coinId, string $currency = 'usd', int $days = 7, string $interval = 'daily'): array;

	/**
	 * How much one unit of $base is worth in each of $currencies.
	 *
	 * @param string[] $currencies Currency codes, any case. Empty means every ordinary currency known.
	 * @return array<string, float> Keyed by uppercase currency code.
	 */
	public function fiatRates(string $base, array $currencies): array;

	/**
	 * The whole market in a few numbers.
	 *
	 * @param string $currency Lowercase currency code. When the source does not quote it, "usd" is used.
	 * @return array{currency: string, market_cap: float, volume: float, market_cap_change_24h: float, btc_dominance: float, eth_dominance: float, active_coins: int} Empty when the source has no figures.
	 */
	public function globalMarket(string $currency = 'usd'): array;

	/**
	 * Coins people are looking at most right now, most searched first.
	 *
	 * @return array<int, array{id: string, name: string, symbol: string, image: string, rank: int, price: float, change_24h: float}> Prices are in US dollars.
	 */
	public function trending(): array;

	/**
	 * Forget everything cached.
	 */
	public function clearCache(): void;

	/**
	 * How many requests went to the source in the current 30-day window.
	 */
	public function callCount(): int;
}

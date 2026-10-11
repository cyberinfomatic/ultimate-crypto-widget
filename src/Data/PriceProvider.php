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
	 * Forget everything cached.
	 */
	public function clearCache(): void;

	/**
	 * How many requests went to the source in the current 30-day window.
	 */
	public function callCount(): int;
}

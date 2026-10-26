<?php

namespace Cyberinfomatic\UltimateCryptoWidget\Data;

use Cyberinfomatic\UltimateCryptoWidget\Lib\WPToolkit\Contracts\Cache\CacheStore;
use Cyberinfomatic\UltimateCryptoWidget\Lib\WPToolkit\Contracts\Log\Logger;
use Cyberinfomatic\UltimateCryptoWidget\Lib\WPToolkit\Http\Client\ApiClient;
use Throwable;

/**
 * Prices from CoinGecko's public API. Works without an API key; a key raises the
 * rate limit and is sent by the ApiClient this class is given.
 *
 * Every answer is cached. A request counts against the site's quota only when it
 * really goes to CoinGecko.
 */
final class CoinGeckoProvider implements PriceProvider
{
	private const DAY          = 86400;
	private const COUNT_OPTION = 'ucwp_coin_gecko_api_calls';
	private const COUNT_SINCE  = 'ucwp_coin_gecko_api_calls-start_date';

	/**
	 * @param int $marketTtl Seconds to keep market data (the "cache interval" setting).
	 */
	public function __construct(
		private readonly ApiClient $api,
		private readonly CacheStore $cache,
		private readonly Logger $logger,
		private readonly int $marketTtl = 600
	) {
	}

	/** Cached for the "cache interval" setting, with the 24-hour, 7-day and 30-day changes the widgets show. */
	public function markets(string $currency = 'usd', ?array $coinIds = null): array
	{
		$query = [
			'vs_currency'             => strtolower($currency),
			'price_change_percentage' => '24h,7d,30d',
		];
		if ($coinIds !== null && $coinIds !== []) {
			$query['ids'] = implode(',', $coinIds);
		}

		return $this->fetch('/coins/markets', $query, $this->marketTtl);
	}

	/** Cached for a day. Leaves out the tickers, translations and developer figures, which no widget shows. */
	public function coin(string $coinId): array
	{
		return $this->fetch(
			'/coins/' . rawurlencode($coinId),
			[
				'localization'   => 'false',
				'tickers'        => 'false',
				'market_data'    => 'true',
				'community_data' => 'true',
				'developer_data' => 'false',
				'sparkline'      => 'false',
			],
			self::DAY
		);
	}

	/** Cached for ten minutes. */
	public function chart(string $coinId, string $currency = 'usd', int $days = 7, string $interval = 'daily'): array
	{
		return $this->fetch(
			'/coins/' . rawurlencode($coinId) . '/market_chart',
			[
				'vs_currency' => strtolower($currency),
				'days'        => $days,
				'interval'    => $interval,
			],
			600
		);
	}

	/** Worked out from CoinGecko's rates against bitcoin, cached for an hour. A currency it does not quote is left out. */
	public function fiatRates(string $base, array $currencies): array
	{
		// CoinGecko quotes every currency against one bitcoin; two of those give a cross rate.
		$perBitcoin = $this->fetch('/exchange_rates', [], 3600)['rates'] ?? [];
		$baseValue  = (float) ($perBitcoin[strtolower($base)]['value'] ?? 0);

		if ($currencies === []) {
			// None asked for: every ordinary (fiat) currency CoinGecko lists.
			$currencies = array_keys(
				array_filter($perBitcoin, static fn ($rate): bool => ($rate['type'] ?? '') === 'fiat')
			);
		}

		$rates = [];
		foreach ($currencies as $currency) {
			$value = (float) ($perBitcoin[strtolower((string) $currency)]['value'] ?? 0);
			if ($baseValue > 0 && $value > 0) {
				$rates[strtoupper((string) $currency)] = $value / $baseValue;
			}
		}

		return $rates;
	}

	/**
	 * CoinGecko's /global figures, cached for the "cache interval" setting. The 24-hour
	 * change is the one CoinGecko works out in US dollars, whatever the currency.
	 */
	public function globalMarket(string $currency = 'usd'): array
	{
		$data = $this->fetch('/global', [], $this->marketTtl)['data'] ?? [];
		if (!is_array($data) || [] === $data) {
			return [];
		}

		$code   = strtolower($currency);
		$caps   = (array) ($data['total_market_cap'] ?? []);
		$code   = isset($caps[$code]) ? $code : 'usd';
		$shares = (array) ($data['market_cap_percentage'] ?? []);

		return [
			'currency'              => $code,
			'market_cap'            => (float) ($caps[$code] ?? 0),
			'volume'                => (float) (((array) ($data['total_volume'] ?? []))[$code] ?? 0),
			'market_cap_change_24h' => (float) ($data['market_cap_change_percentage_24h_usd'] ?? 0),
			'btc_dominance'         => (float) ($shares['btc'] ?? 0),
			'eth_dominance'         => (float) ($shares['eth'] ?? 0),
			'active_coins'          => (int) ($data['active_cryptocurrencies'] ?? 0),
		];
	}

	/** CoinGecko's trending searches, cached for 15 minutes. An entry without an id, name or symbol is skipped. */
	public function trending(): array
	{
		$rows = $this->fetch('/search/trending', [], 900)['coins'] ?? [];

		$coins = [];
		foreach ((array) $rows as $row) {
			$item = is_array($row) ? ($row['item'] ?? null) : null;
			if (!is_array($item) || !isset($item['id'], $item['name'], $item['symbol'])) {
				continue;
			}
			$data    = (array) ($item['data'] ?? []);
			$changes = (array) ($data['price_change_percentage_24h'] ?? []);
			$coins[] = [
				'id'         => (string) $item['id'],
				'name'       => (string) $item['name'],
				'symbol'     => (string) $item['symbol'],
				'image'      => (string) ($item['small'] ?? $item['thumb'] ?? ''),
				'rank'       => (int) ($item['market_cap_rank'] ?? 0),
				'price'      => (float) ($data['price'] ?? 0),
				'change_24h' => (float) ($changes['usd'] ?? 0),
			];
		}

		return $coins;
	}

	/** Clears only the "prices" cache group (see DataProvider). */
	public function clearCache(): void
	{
		$this->cache->clear();
	}

	/** Kept in an option; the count starts again 30 days after its window opened (countCall()). */
	public function callCount(): int
	{
		return (int) get_option(self::COUNT_OPTION, 0);
	}

	/**
	 * @param array<string, scalar> $query
	 * @return array<int|string, mixed>
	 */
	private function fetch(string $path, array $query, int $ttl): array
	{
		$key = 'coingecko:' . md5($path . '?' . http_build_query($query));

		$cached = $this->cache->get($key);
		if (is_array($cached)) {
			return $cached;
		}

		try {
			$data = $this->api->getJson($path, $query);
		} catch (Throwable $e) {
			$this->logger->warning('CoinGecko request failed.', ['path' => $path, 'reason' => $e->getMessage()]);
			throw new PriceDataException('CoinGecko request failed: ' . $path, 0, $e);
		} finally {
			$this->countCall();
		}

		if (!is_array($data)) {
			throw new PriceDataException('CoinGecko answered with something that is not a list or an object: ' . $path);
		}

		$this->cache->set($key, $data, $ttl);

		return $data;
	}

	/** Counts one request to CoinGecko, opening a new 30-day window when the last one has run out. */
	private function countCall(): void
	{
		$since = (string) get_option(self::COUNT_SINCE, '');
		if ($since === '' || strtotime($since) < strtotime('-30 days')) {
			update_option(self::COUNT_SINCE, gmdate('Y-m-d'), false);
			update_option(self::COUNT_OPTION, 1, false);

			return;
		}

		update_option(self::COUNT_OPTION, $this->callCount() + 1, false);
	}
}

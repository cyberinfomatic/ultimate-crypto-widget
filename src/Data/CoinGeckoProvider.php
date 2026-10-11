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

	public function clearCache(): void
	{
		$this->cache->clear();
	}

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

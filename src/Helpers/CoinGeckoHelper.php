<?php
namespace Cyberinfomatic\UltimateCryptoWidget\Helpers;

use Cyberinfomatic\UltimateCryptoWidget\Controllers\Settings;

class CoinGeckoHelper extends APIHelper {

	const HOST = 'https://api.coingecko.com/api/v3';

	static function get_api_key() {
		return Settings::get('coingecko_api_key');
	}

	const ENDPOINTS = [
		'coin-list' => [
			'route' => '/coins/list',
			'method' => 'GET',
			'cache' => 1440, // in minutes for 24 hours
			'params' => [
				'include_platform' => 'false'
			]
		],
		'coin-info' => [
			'route' => '/coins/{{coin_id}}',
			'method' => 'GET',
			'cache' => 1440, // in minutes for 24 hours
			'params' => [
				'localization' => 'false',
				'tickers' => 'false',
				'market_data' => 'true',
				'community_data' => 'true',
				'developer_data' => 'false',
				'sparkline' => 'false'
			],
			'substitutions' => [
				'coin_id'
			]
		],
		'coins-list-market-data' => [
			'route' => '/coins/markets',
			'method' => 'GET',
			'params' => [
				'vs_currency' => 'usd',
				'price_change_percentage' => '24h,7d,30d',
				'ids' => null
			]
		],
		'coin-historical-chart-data' => [
			'route' => '/coins/{{coin_id}}/market_chart',
			'method' => 'GET',
			'cache' => 10, // in minutes
			'params' => [
				'vs_currency' => 'usd',
				'days' => 7,
				'interval' => 'daily'
			],
			'substitutions' => [
				'coin_id'
			]
		],

	];

	static function get_headers() {
		return [
			'accept' => 'application/json',
			'x-cg-demo-api-key' => self::get_api_key()
		];
	}

	static function get_coins_list(array $setting = []): array {
		try {
			return self::make_request('coin-list', [
				'include_platform' => $setting['include_platform'] ?? 'false'
			]);
		} catch (\Exception $e) {
			return [
				'error' => $e->getMessage(),
				'code' => $e->getCode(),
				'data' => [
					'setting' => $setting
				]
			];
		}
	}

	static function get_coin_info($coin_id, $setting = []): array {
		try {
			return self::make_request('coin-info', [], [
				'coin_id' => $coin_id
			]);
		} catch (\Exception $e) {
			return [
				'error' => $e->getMessage(),
				'code' => $e->getCode(),
				'data' => [
					'coin_id' => $coin_id,
					'setting' => $setting
				]
			];
		}
	}

	static function get_coins_with_market_data($setting = []): array {
		try {
			return self::make_request('coins-list-market-data', [
				'vs_currency' => $setting['default_currency'] ?? 'usd',
				'ids' => isset($setting['coins']) && is_array($setting['coins']) ? implode(',', $setting['coins']) : ($setting['coins'] ?? null)
			]);
		} catch (\Exception $e) {
			return [
				'error' => $e->getMessage(),
				'code' => $e->getCode(),
				'data' => [
					'setting' => $setting
				]
			];
		}
	}

	static function get_coin_chart_data($coin_id, $setting) {
		try {
			return self::make_request('coin-historical-chart-data', [
				'vs_currency' => $setting['default_currency'] ?? 'usd',
				'days' => $setting['no_of_days'] ?? 7,
				'interval' => $setting['interval'] ?? 'daily'
			], [
				'coin_id' => $coin_id
			]);
		} catch (\Exception $e) {
			return [
				'error' => $e->getMessage(),
				'code' => $e->getCode(),
				'data' => [
					'coin_id' => $coin_id,
					'setting' => $setting
				]
			];
		}
	}

	static function getName(): string {
		return 'coin_gecko';
	}

}

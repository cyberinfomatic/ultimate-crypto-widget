<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

use Cyberinfomatic\UltimateCryptoWidget\Helpers\CoinGeckoHelper;

if (!function_exists( 'ucwp_get_coins' )) {
	function ucwp_get_coins($setting): array {
		return CoinGeckoHelper::get_coins_with_market_data($setting);
	}
}

if (!function_exists( 'ucwp_get_coin_historical_chart_data' )) {
	/**
	 * @throws Exception
	 */
	function ucwp_get_coin_historical_chart_data($coin_id, $days = 7, $currency = 'usd', $interval = 'daily') {
		return CoinGeckoHelper::get_coin_chart_data($coin_id, [
			'default_currency' => $currency,
			'days' => $days
		]);
	}
}
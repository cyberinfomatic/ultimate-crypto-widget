<?php

namespace Cyberinfomatic\UltimateCryptoWidget\Http;

use Cyberinfomatic\UltimateCryptoWidget\Helpers\CoinGeckoHelper;
use Cyberinfomatic\UltimateCryptoWidget\Lib\WPToolkit\Http\HttpError;
use Cyberinfomatic\UltimateCryptoWidget\Lib\WPToolkit\Http\Request;

/**
 * Read-only price data for the widgets. Called from visitors' browsers, so these
 * routes are public; their input is declared and validated in HttpProvider.
 */
final class MarketsController
{
	/**
	 * @return array<int|string, mixed>
	 */
	public function coins(Request $request): array
	{
		$setting = array_filter(
			[
				'default_currency' => $request->input('default_currency'),
				'coins'            => $request->input('coins'),
			],
			static fn ($value): bool => null !== $value && '' !== $value
		);

		return $this->answer(CoinGeckoHelper::get_coins_with_market_data($setting));
	}

	/**
	 * @return array<int|string, mixed>
	 */
	public function coin(Request $request): array
	{
		return $this->answer(CoinGeckoHelper::get_coin_info((string) $request->input('coin_id')));
	}

	/**
	 * @return array<int|string, mixed>
	 */
	public function chart(Request $request): array
	{
		return $this->answer(
			CoinGeckoHelper::get_coin_chart_data(
				(string) $request->input('coin_id'),
				[
					'no_of_days'       => (int) $request->input('days', 7),
					'default_currency' => (string) $request->input('currency', 'usd'),
				]
			)
		);
	}

	/**
	 * The helper reports a failed upstream call as an array with an "error" message.
	 * That message is for the log, not for the visitor.
	 *
	 * @param mixed $data
	 * @return array<int|string, mixed>
	 */
	private function answer($data): array
	{
		if (!is_array($data) || isset($data['error'])) {
			throw new HttpError(
				502,
				'price_data_unavailable',
				__('Price data is temporarily unavailable.', 'ultimate-crypto-widget')
			);
		}

		return $data;
	}
}

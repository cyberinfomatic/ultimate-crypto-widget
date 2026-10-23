<?php

namespace Cyberinfomatic\UltimateCryptoWidget\Http;

use Cyberinfomatic\UltimateCryptoWidget\Data\PriceDataException;
use Cyberinfomatic\UltimateCryptoWidget\Data\PriceProvider;
use Cyberinfomatic\UltimateCryptoWidget\Lib\WPToolkit\Http\HttpError;
use Cyberinfomatic\UltimateCryptoWidget\Lib\WPToolkit\Http\Request;

/**
 * Read-only price data for the widgets. Called from visitors' browsers, so these
 * routes are public; their input is declared and validated in HttpProvider.
 */
final class MarketsController
{
	public function __construct(private readonly PriceProvider $prices)
	{
	}

	/**
	 * @return array<int|string, mixed>
	 */
	public function coins(Request $request): array
	{
		$ids = array_values(array_filter(explode(',', (string) $request->input('coins', ''))));

		return $this->answer(
			fn (): array => $this->prices->markets(
				(string) ($request->input('default_currency') ?: 'usd'),
				[] !== $ids ? $ids : null
			)
		);
	}

	/**
	 * @return array<int|string, mixed>
	 */
	public function coin(Request $request): array
	{
		return $this->answer(fn (): array => $this->prices->coin((string) $request->input('coin_id')));
	}

	/**
	 * @return array<int|string, mixed>
	 */
	public function chart(Request $request): array
	{
		return $this->answer(
			fn (): array => $this->prices->chart(
				(string) $request->input('coin_id'),
				(string) $request->input('currency', 'usd'),
				(int) $request->input('days', 7)
			)
		);
	}

	/**
	 * Why the data is missing goes to the log (the provider writes it), not to the visitor.
	 *
	 * @param callable(): array<int|string, mixed> $fetch
	 * @return array<int|string, mixed>
	 */
	private function answer(callable $fetch): array
	{
		try {
			return $fetch();
		} catch (PriceDataException $e) {
			throw new HttpError(
				502,
				'price_data_unavailable',
				__('Price data is temporarily unavailable.', 'ultimate-crypto-widget'),
				[],
				[],
				$e
			);
		}
	}
}

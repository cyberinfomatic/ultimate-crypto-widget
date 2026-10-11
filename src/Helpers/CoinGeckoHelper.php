<?php
namespace Cyberinfomatic\UltimateCryptoWidget\Helpers;

use Cyberinfomatic\UltimateCryptoWidget\Data\PriceDataException;
use Cyberinfomatic\UltimateCryptoWidget\Legacy\Services;

/**
 * The old static way to get prices, kept for the code that still calls it.
 * It only forwards to the PriceProvider; new code should take a PriceProvider instead.
 *
 * On failure each method returns ['error' => true], as callers expect, never the reason.
 */
class CoinGeckoHelper {

	/**
	 * @param array|\ArrayAccess $setting May hold "default_currency" and "coins" (a list or a comma-separated string).
	 */
	static function get_coins_with_market_data( $setting = [] ): array {
		$coins = $setting['coins'] ?? null;
		if ( is_string( $coins ) ) {
			$coins = array_filter( array_map( 'trim', explode( ',', $coins ) ) );
		}

		try {
			return Services::prices()->markets(
				(string) ( $setting['default_currency'] ?? 'usd' ) ?: 'usd',
				is_array( $coins ) && [] !== $coins ? array_values( $coins ) : null
			);
		} catch ( PriceDataException $e ) {
			return [ 'error' => true ];
		}
	}

	static function get_coin_info( $coin_id, $setting = [] ): array {
		try {
			return Services::prices()->coin( (string) $coin_id );
		} catch ( PriceDataException $e ) {
			return [ 'error' => true ];
		}
	}

	static function get_coin_chart_data( $coin_id, $setting ) {
		try {
			return Services::prices()->chart(
				(string) $coin_id,
				(string) ( $setting['default_currency'] ?? 'usd' ),
				(int) ( $setting['no_of_days'] ?? 7 ),
				(string) ( $setting['interval'] ?? 'daily' )
			);
		} catch ( PriceDataException $e ) {
			return [ 'error' => true ];
		}
	}

	static function get_api_call_count(): int {
		return Services::prices()->callCount();
	}
}

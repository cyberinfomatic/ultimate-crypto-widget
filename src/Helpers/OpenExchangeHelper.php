<?php
namespace Cyberinfomatic\UltimateCryptoWidget\Helpers;

use Cyberinfomatic\UltimateCryptoWidget\Data\PriceDataException;
use Cyberinfomatic\UltimateCryptoWidget\Legacy\Services;

/**
 * The old static way to get currency rates, kept for the code that still calls it.
 *
 * Rates now come from the PriceProvider, so an Open Exchange Rates account is no
 * longer needed. The class keeps its name only until its callers are replaced.
 */
class OpenExchangeHelper {

	/**
	 * How much $value of $from is in $to. Returns 0 when the rate is not known.
	 */
	static function convert( $from, $to, $value = 1 ): float {
		if ( strtoupper( (string) $from ) === strtoupper( (string) $to ) ) {
			return (float) $value;
		}

		$rates = self::rates( (string) $from, [ (string) $to ] );

		return (float) $value * ( $rates[ strtoupper( (string) $to ) ] ?? 0 );
	}

	/**
	 * @return array{base: string, rates: array<string, float>}|array{}
	 */
	public static function get_latest( $pairs = [ 'USD' ], $base = 'USD' ): array {
		$rates = self::rates( (string) $base, (array) $pairs );

		return [] === $rates ? [] : [
			'base'  => strtoupper( (string) $base ),
			'rates' => $rates,
		];
	}

	/**
	 * Rates for a widget's chosen currencies; every known currency when none is chosen.
	 *
	 * @return array<string, float>
	 */
	static function get_default_currencies_rate( $setting = [] ) {
		$currencies = $setting['currencies'] ?? [];
		if ( is_string( $currencies ) ) {
			$currencies = explode( ',', $currencies );
		}

		return self::rates(
			(string) ( $setting['default_currency'] ?? 'USD' ),
			array_values( array_filter( array_map( 'trim', (array) $currencies ) ) )
		);
	}

	/**
	 * @param string[] $currencies
	 * @return array<string, float>
	 */
	private static function rates( string $base, array $currencies ): array {
		try {
			return Services::prices()->fiatRates( $base, $currencies );
		} catch ( PriceDataException $e ) {
			return [];
		}
	}
}

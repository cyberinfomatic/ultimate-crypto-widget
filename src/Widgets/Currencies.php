<?php

namespace Cyberinfomatic\UltimateCryptoWidget\Widgets;

/**
 * The currencies a widget can show prices in.
 *
 * The codes are stored in widgets' "default_currency" and "currencies" settings
 * and sent to the price source, so one may be added but never renamed.
 */
final class Currencies
{
	/** ISO 4217 codes, lowercase, as they are stored. */
	public const CODES = [
		'usd', 'eur', 'gbp', 'jpy', 'cad', 'aud', 'cny', 'krw', 'inr', 'rub',
		'brl', 'zar', 'idr', 'ngn', 'php', 'try', 'pln', 'sgd', 'thb', 'vnd',
		'chf', 'clp', 'czk', 'dkk', 'huf', 'mxn', 'nok', 'nzd', 'sek', 'twd',
	];

	/**
	 * The sign printed for a currency when the visitor's browser cannot format it.
	 * Widgets normally format prices themselves (Intl), which also places the sign.
	 */
	private const SYMBOLS = [
		'usd' => '$',
		'eur' => '€',
		'gbp' => '£',
		'jpy' => '¥',
		'cad' => 'C$',
		'aud' => 'A$',
		'cny' => '¥',
		'krw' => '₩',
		'inr' => '₹',
		'rub' => '₽',
		'brl' => 'R$',
		'zar' => 'R',
		'idr' => 'Rp',
		'ngn' => '₦',
		'php' => '₱',
		'try' => '₺',
		'pln' => 'zł',
		'sgd' => 'S$',
		'thb' => '฿',
		'vnd' => '₫',
		'chf' => 'CHF',
		'clp' => 'CLP',
		'czk' => 'Kč',
		'dkk' => 'kr',
		'huf' => 'Ft',
		'mxn' => 'Mex$',
		'nok' => 'kr',
		'nzd' => 'NZ$',
		'sek' => 'kr',
		'twd' => 'NT$',
	];

	/** Whether a code, in any case, is one of CODES. */
	public static function has(string $code): bool
	{
		return in_array(strtolower($code), self::CODES, true);
	}

	/**
	 * The sign for a currency code, or '' when there is none on record.
	 */
	public static function symbolFor(string $code): string
	{
		return self::SYMBOLS[strtolower($code)] ?? '';
	}
}

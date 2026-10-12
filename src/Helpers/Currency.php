<?php
namespace Cyberinfomatic\UltimateCryptoWidget\Helpers;

class Currency {

	const CURRENCY_PAIR = [
		'usd' => 'USD',
		'eur' => 'EUR',
		'gbp' => 'GBP',
		'jpy' => 'JPY',
		'cad' => 'CAD',
		'aud' => 'AUD',
		'cny' => 'CNY',
		'krw' => 'KRW',
		'inr' => 'INR',
		'rub' => 'RUB',
		'brl' => 'BRL',
		'zar' => 'ZAR',
		'idr' => 'IDR',
		'ngn' => 'NGN',
		'php' => 'PHP',
		'try' => 'TRY',
		'pln' => 'PLN',
		'sgd' => 'SGD',
		'thb' => 'THB',
		'vnd' => 'VND',
		'chf' => 'CHF',
		'clp' => 'CLP',
		'czk' => 'CZK',
		'dkk' => 'DKK',
		'huf' => 'HUF',
		'mxn' => 'MXN',
		'nok' => 'NOK',
		'nzd' => 'NZD',
		'sek' => 'SEK',
		'twd' => 'TWD',
	];

	const SYMBOLS = [
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

	/**
	 * Get the currency symbol for a given currency code.
	 *
	 * @param string $currency Currency code (e.g., 'usd', 'eur').
	 * @return string Currency symbol or empty string if not found.
	 */
	public static function get_symbol(string $currency): string {
		return self::SYMBOLS[strtolower($currency)] ?? '';
	}
}

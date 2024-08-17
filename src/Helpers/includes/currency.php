<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

use Cyberinfomatic\UltimateCryptoWidget\Helpers\Currency;

if(!function_exists( 'ucwp_get_symbol' )) {
	function ucwp_get_symbol($currency): string {
		return Currency::get_symbol($currency);
	}
}

if(!function_exists( 'ucwp_convert_currency' )) {
	function ucwp_convert_currency(int $amount, string $from, string $to): int {
		return Currency::convert($amount, $from, $to);
	}
}
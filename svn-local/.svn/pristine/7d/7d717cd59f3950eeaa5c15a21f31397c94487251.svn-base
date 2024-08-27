<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

if (!function_exists( 'ucwp_shorten_number' )) {
	function ucwp_shorten_number(int|float $number, $precision = 2): string {
		$suffix = '';
		if ($number >= 1000000000) {
			$number = $number / 1000000000;
			$suffix = 'B';
		} elseif ($number >= 1000000) {
			$number = $number / 1000000;
			$suffix = 'M';
		} elseif ($number >= 1000) {
			$number = $number / 1000;
			$suffix = 'K';
		}
		return round($number, $precision) . $suffix;
	}
}
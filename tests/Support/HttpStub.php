<?php
/**
 * Canned answers for the external APIs.
 *
 * @package Ultimate_Crypto_Widget
 */

namespace Cyberinfomatic\UltimateCryptoWidget\Tests\Support;

use WP_Error;

/**
 * Intercepts every outgoing HTTP request. Known API URLs get a fixture; anything
 * else gets an error, so a test can never reach the network.
 */
final class HttpStub {

	/**
	 * URLs requested since the last reset.
	 *
	 * @var string[]
	 */
	public static array $requests = array();

	/**
	 * URL fragment => fixture file in tests/fixtures/http. First match wins.
	 */
	private const ROUTES = array(
		'api.coingecko.com/api/v3/coins/markets'      => 'coingecko-markets.json',
		'api.coingecko.com/api/v3/coins/list'         => 'coingecko-list.json',
		'/market_chart'                               => 'coingecko-market-chart.json',
		'api.coingecko.com/api/v3/coins/'             => 'coingecko-coin.json',
		'openexchangerates.org/api/latest.json'       => 'openexchange-latest.json',
		'openexchangerates.org/api/convert/'          => 'openexchange-convert.json',
	);

	public static function install(): void {
		add_filter( 'pre_http_request', array( self::class, 'answer' ), 10, 3 );
	}

	public static function reset(): void {
		self::$requests = array();
	}

	/**
	 * @param false|array|WP_Error $preempt Earlier answer, if any.
	 * @param array                $args    Request arguments.
	 * @param string               $url     Request URL.
	 * @return array|WP_Error
	 */
	public static function answer( $preempt, $args, $url ) {
		self::$requests[] = $url;

		foreach ( self::ROUTES as $fragment => $file ) {
			if ( false !== strpos( $url, $fragment ) ) {
				return array(
					'headers'  => array( 'content-type' => 'application/json' ),
					// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
					'body'     => file_get_contents( dirname( __DIR__ ) . '/fixtures/http/' . $file ),
					'response' => array(
						'code'    => 200,
						'message' => 'OK',
					),
					'cookies'  => array(),
					'filename' => null,
				);
			}
		}

		return new WP_Error( 'ucwp_tests_no_network', 'Unexpected HTTP request in tests: ' . $url );
	}
}

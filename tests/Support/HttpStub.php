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
	 * Headers of the most recent request.
	 *
	 * @var array<string, string>
	 */
	public static array $last_headers = array();

	/**
	 * URL fragment => fixture file in tests/fixtures/http. First match wins.
	 */
	private const ROUTES = array(
		'api.coingecko.com/api/v3/exchange_rates'     => 'coingecko-exchange-rates.json',
		'api.coingecko.com/api/v3/global'             => 'coingecko-global.json',
		'api.coingecko.com/api/v3/search/trending'    => 'coingecko-trending.json',
		'api.coingecko.com/api/v3/coins/markets'      => 'coingecko-markets.json',
		'api.coingecko.com/api/v3/coins/list'         => 'coingecko-list.json',
		'/market_chart'                               => 'coingecko-market-chart.json',
		'api.coingecko.com/api/v3/coins/'             => 'coingecko-coin.json',
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
		self::$requests[]   = $url;
		self::$last_headers = array_change_key_case( (array) ( $args['headers'] ?? array() ) );

		foreach ( self::ROUTES as $fragment => $file ) {
			if ( false !== strpos( $url, $fragment ) ) {
				return array(
					// The same header object a real WordPress response carries, with a
					// repeated header, so the code is tested against what it really gets.
					'headers'  => new \WpOrg\Requests\Utility\CaseInsensitiveDictionary(
						array(
							'content-type' => 'application/json',
							'set-cookie'   => array( 'a=1', 'b=2' ),
						)
					),
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

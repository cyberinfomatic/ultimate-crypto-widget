<?php
/**
 * What the public REST routes answer.
 *
 * @package Ultimate_Crypto_Widget
 */

namespace Cyberinfomatic\UltimateCryptoWidget\Tests\Integration;

use Cyberinfomatic\UltimateCryptoWidget\Tests\Support\HttpStub;
use Cyberinfomatic\UltimateCryptoWidget\Tests\Support\Snapshots;
use Cyberinfomatic\UltimateCryptoWidget\Tests\Support\TestCase;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Pins the URLs and response shapes the built React bundle depends on.
 */
final class RestSnapshotTest extends TestCase {

	private const NS = '/ultimate-crypto-widget/v1';

	public function set_up() {
		parent::set_up();


		global $wp_rest_server;
		$wp_rest_server = null;
		rest_get_server();

		wp_set_current_user( 0 ); // The widgets call these routes as anonymous visitors.
	}

	public function tear_down() {
		global $wp_rest_server;
		$wp_rest_server = null;
		parent::tear_down();
	}

	public function test_the_namespace_and_routes_exist(): void {
		$routes = array_keys( rest_get_server()->get_routes( 'ultimate-crypto-widget/v1' ) );

		foreach ( array( '/coins', '/coin-info', '/coin-chart-data', '/load-shortcode' ) as $route ) {
			$this->assertContains( self::NS . $route, $routes );
		}
	}

	public function test_coins(): void {
		$response = $this->get( '/coins' );

		$this->assertSame( 200, $response->get_status() );
		Snapshots::assert_matches( 'rest/coins.json', Snapshots::json( $response->get_data() ) );
	}

	public function test_coin_info(): void {
		$response = $this->get( '/coin-info', array( 'coin_id' => 'bitcoin' ) );

		$this->assertSame( 200, $response->get_status() );
		Snapshots::assert_matches( 'rest/coin-info.json', Snapshots::json( $response->get_data() ) );
	}

	public function test_coin_info_without_a_coin_id(): void {
		$response = $this->get( '/coin-info' );

		$this->assertSame( 400, $response->get_status() );
		Snapshots::assert_matches( 'rest/coin-info.missing-id.json', Snapshots::json( $response->get_data() ) );
	}

	public function test_coin_chart_data(): void {
		$response = $this->get(
			'/coin-chart-data',
			array(
				'coin_id'  => 'bitcoin',
				'days'     => 7,
				'currency' => 'usd',
			)
		);

		$this->assertSame( 200, $response->get_status() );
		Snapshots::assert_matches( 'rest/coin-chart-data.json', Snapshots::json( $response->get_data() ) );
	}

	public function test_coin_chart_data_without_a_coin_id(): void {
		$response = $this->get( '/coin-chart-data' );

		// Today this error comes back with status 200. Pinned so a change is deliberate.
		$this->assertSame( 200, $response->get_status() );
		Snapshots::assert_matches( 'rest/coin-chart-data.missing-id.json', Snapshots::json( $response->get_data() ) );
	}

	public function test_a_repeat_request_is_served_from_the_cache(): void {
		$this->get( '/coins' );
		$calls = count( HttpStub::$requests );
		$this->get( '/coins' );

		$this->assertSame( $calls, count( HttpStub::$requests ), 'The second request must not call CoinGecko again.' );
	}

	private function get( string $route, array $params = array() ): WP_REST_Response {
		$request = new WP_REST_Request( 'GET', self::NS . $route );
		$request->set_query_params( $params );

		return rest_get_server()->dispatch( $request );
	}
}

<?php
/**
 * Who may call the REST routes, and with what.
 *
 * @package Ultimate_Crypto_Widget
 */

namespace Cyberinfomatic\UltimateCryptoWidget\Tests\Integration;

use Cyberinfomatic\UltimateCryptoWidget\Tests\Support\HttpStub;
use Cyberinfomatic\UltimateCryptoWidget\Tests\Support\TestCase;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Written from the attacker's side: each test tries something a route must refuse.
 */
final class HttpSecurityTest extends TestCase {

	private const NS = '/ultimate-crypto-widget/v1';

	/**
	 * Set by the probe shortcode.
	 *
	 * @var bool
	 */
	public static bool $probe_ran = false;

	public function set_up() {
		parent::set_up();

		self::$probe_ran = false;
		add_shortcode(
			'ucwp_probe',
			static function () {
				self::$probe_ran = true;
				return 'PROBE';
			}
		);

		global $wp_rest_server;
		$wp_rest_server = null;
		rest_get_server();
	}

	public function tear_down() {
		global $wp_rest_server;
		$wp_rest_server = null;
		remove_shortcode( 'ucwp_probe' );
		parent::tear_down();
	}

	public function test_every_route_has_an_access_rule(): void {
		$checked = 0;
		foreach ( rest_get_server()->get_routes( 'ultimate-crypto-widget/v1' ) as $route => $handlers ) {
			if ( self::NS === $route ) {
				continue; // The namespace index is WordPress's own.
			}
			foreach ( $handlers as $handler ) {
				$this->assertIsCallable( $handler['permission_callback'] ?? null, "{$route} has no permission_callback." );
				++$checked;
			}
		}

		$this->assertSame( 4, $checked, 'Expected exactly the four known routes.' );
	}

	public function test_an_anonymous_visitor_cannot_render_a_preview(): void {
		wp_set_current_user( 0 );

		$response = $this->preview( array( 'type' => 'crypto-price-table-1' ) );

		$this->assertContains( $response->get_status(), array( 401, 403 ) );
	}

	public function test_a_subscriber_cannot_render_a_preview(): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'subscriber' ) ) );

		$this->assertSame( 403, $this->preview( array( 'type' => 'crypto-price-table-1' ) )->get_status() );
	}

	public function test_a_preview_request_cannot_run_another_shortcode(): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'editor' ) ) );

		$response = $this->preview(
			array(
				'type'               => 'x"] [ucwp_probe] [ucwp_widget type="x',
				'search_placeholder' => '"] [ucwp_probe] [',
				'ucwp_probe'         => '1',
			)
		);

		$this->assertFalse( self::$probe_ran, 'A second shortcode was executed from request input.' );
		$this->assertStringNotContainsString( 'PROBE', wp_json_encode( $response->get_data() ) );
	}

	public function test_an_editor_gets_a_preview(): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'editor' ) ) );

		$response = $this->preview(
			array(
				'type'             => 'crypto-price-table-1',
				'count'            => '5',
				'parent_width'     => '100%',
				'default_currency' => 'usd',
			)
		);

		$this->assertSame( 200, $response->get_status() );
		$this->assertArrayHasKey( 'content', $response->get_data() );
		$this->assertSame(
			'[ucwp_widget type="crypto-price-table-1" count="5" parent_width="100%" default_currency="usd"]',
			$response->get_data()['shortcode']
		);
	}

	public function test_only_the_plugins_own_shortcode_is_accepted(): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'editor' ) ) );

		$response = $this->preview( array( 'type' => 'crypto-price-table-1' ), 'ucwp_probe' );

		$this->assertSame( 422, $response->get_status() );
		$this->assertFalse( self::$probe_ran );
	}

	/**
	 * @dataProvider bad_coin_id_provider
	 */
	public function test_a_malformed_coin_id_never_reaches_coingecko( string $coin_id ): void {
		wp_set_current_user( 0 );
		HttpStub::reset();

		$response = $this->get( '/coin-info', array( 'coin_id' => $coin_id ) );

		$this->assertSame( 422, $response->get_status() );
		$this->assertSame( array(), HttpStub::$requests, 'An invalid id was sent upstream.' );
	}

	public function bad_coin_id_provider(): array {
		return array(
			'path traversal' => array( '../../ping' ),
			'query string'   => array( 'bitcoin?x_cg_demo_api_key=steal' ),
			'spaces'         => array( 'bit coin' ),
			'too long'       => array( str_repeat( 'a', 101 ) ),
		);
	}

	public function test_chart_days_and_currency_are_validated(): void {
		wp_set_current_user( 0 );

		$this->assertSame( 422, $this->get( '/coin-chart-data', array( 'coin_id' => 'bitcoin', 'days' => 100000 ) )->get_status() );
		$this->assertSame( 422, $this->get( '/coin-chart-data', array( 'coin_id' => 'bitcoin', 'currency' => 'not-a-currency' ) )->get_status() );
	}

	public function test_public_routes_are_rate_limited(): void {
		wp_set_current_user( 0 );

		// The limit counts per clock minute. Started in a minute's last seconds, the
		// requests would be split over two minutes and none refused, so wait it out.
		$into_minute = time() % 60;
		if ( $into_minute > 50 ) {
			sleep( 61 - $into_minute );
		}

		$last = null;
		for ( $i = 0; $i < 31; $i++ ) {
			$last = $this->get( '/coin-info', array( 'coin_id' => 'bitcoin' ) );
		}

		$this->assertSame( 429, $last->get_status(), 'The 31st request in a minute must be refused.' );
		$this->assertArrayHasKey( 'Retry-After', $last->get_headers() );
	}

	public function test_an_upstream_failure_does_not_leak_details(): void {
		wp_set_current_user( 0 );
		remove_filter( 'pre_http_request', array( HttpStub::class, 'answer' ), 10 );
		add_filter(
			'pre_http_request',
			static function () {
				return new \WP_Error( 'http_request_failed', 'cURL error 28: secret-internal-host timed out' );
			}
		);

		$response = $this->get( '/coin-info', array( 'coin_id' => 'ethereum' ) );

		remove_all_filters( 'pre_http_request' );
		HttpStub::install();

		$this->assertSame( 502, $response->get_status() );
		$this->assertStringNotContainsString( 'secret-internal-host', wp_json_encode( $response->get_data() ) );
		$this->assertStringNotContainsString( 'cURL', wp_json_encode( $response->get_data() ) );
	}

	private function get( string $route, array $params = array() ): WP_REST_Response {
		$request = new WP_REST_Request( 'GET', self::NS . $route );
		$request->set_query_params( $params );

		return rest_get_server()->dispatch( $request );
	}

	private function preview( array $atts, string $shortcode = 'ucwp_widget' ): WP_REST_Response {
		$request = new WP_REST_Request( 'POST', self::NS . '/load-shortcode' );
		$request->set_body_params(
			array(
				'shortcode' => $shortcode,
				'atts'      => $atts,
			)
		);

		return rest_get_server()->dispatch( $request );
	}
}

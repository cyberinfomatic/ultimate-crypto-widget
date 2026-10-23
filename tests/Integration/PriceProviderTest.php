<?php
/**
 * The price source.
 *
 * @package Ultimate_Crypto_Widget
 */

namespace Cyberinfomatic\UltimateCryptoWidget\Tests\Integration;

use Cyberinfomatic\UltimateCryptoWidget\Data\PriceDataException;
use Cyberinfomatic\UltimateCryptoWidget\Data\PriceProvider;
use Cyberinfomatic\UltimateCryptoWidget\Tests\Support\Services;
use Cyberinfomatic\UltimateCryptoWidget\Tests\Support\HttpStub;
use Cyberinfomatic\UltimateCryptoWidget\Tests\Support\TestCase;

/**
 * Prices and currency rates come from one provider, which needs no API key.
 */
final class PriceProviderTest extends TestCase {

	public function test_markets_asks_coingecko_for_the_chosen_currency_and_coins(): void {
		$coins = Services::prices()->markets( 'EUR', array( 'bitcoin', 'ethereum' ) );

		$this->assertSame( 'bitcoin', $coins[0]['id'] );
		$this->assertStringContainsString( 'vs_currency=eur', HttpStub::$requests[0] );
		$this->assertStringContainsString( 'ids=bitcoin%2Cethereum', HttpStub::$requests[0] );
	}

	public function test_a_second_identical_request_is_served_from_the_cache(): void {
		Services::prices()->markets();
		Services::prices()->markets();

		$this->assertCount( 1, HttpStub::$requests );
	}

	public function test_currency_rates_are_cross_rates_keyed_by_uppercase_code(): void {
		$rates = Services::prices()->fiatRates( 'usd', array( 'eur', 'GBP', 'ngn' ) );

		$this->assertSame( array( 'EUR', 'GBP', 'NGN' ), array_keys( $rates ) );
		$this->assertEqualsWithDelta( 0.92, $rates['EUR'], 0.000001 );
		$this->assertEqualsWithDelta( 1550.5, $rates['NGN'], 0.000001 );

		$from_euro = Services::prices()->fiatRates( 'eur', array( 'usd' ) );
		$this->assertEqualsWithDelta( 1 / 0.92, $from_euro['USD'], 0.000001 );
	}

	public function test_no_currencies_means_every_ordinary_currency_but_no_coins_or_metals(): void {
		$rates = Services::prices()->fiatRates( 'usd', array() );

		$this->assertSame( array( 'USD', 'EUR', 'GBP', 'NGN', 'JPY' ), array_keys( $rates ) );
	}

	public function test_the_whole_market_is_summed_up_in_the_chosen_currency(): void {
		$market = Services::prices()->globalMarket( 'EUR' );

		$this->assertSame( 'eur', $market['currency'] );
		$this->assertEqualsWithDelta( 2539200000000, $market['market_cap'], 1 );
		$this->assertEqualsWithDelta( 90528000000, $market['volume'], 1 );
		$this->assertEqualsWithDelta( 58.8, $market['btc_dominance'], 0.0001 );
		$this->assertEqualsWithDelta( 10.6, $market['eth_dominance'], 0.0001 );
		$this->assertEqualsWithDelta( -0.74, $market['market_cap_change_24h'], 0.0001 );
		$this->assertSame( 17342, $market['active_coins'] );
	}

	public function test_a_currency_the_market_figures_do_not_quote_falls_back_to_dollars(): void {
		$this->assertSame( 'usd', Services::prices()->globalMarket( 'ngn' )['currency'] );
	}

	public function test_trending_coins_are_read_in_order_and_unusable_rows_are_left_out(): void {
		$coins = Services::prices()->trending();

		$this->assertSame( array( 'pepe', 'solana' ), array_column( $coins, 'id' ) );
		$this->assertSame( 'PEPE', $coins[0]['symbol'] );
		$this->assertSame( 38, $coins[0]['rank'] );
		$this->assertEqualsWithDelta( 12.4, $coins[0]['change_24h'], 0.0001 );
		$this->assertStringEndsWith( 'small/pepe-token.jpeg', $coins[0]['image'] );
		// No "small" picture: the thumbnail is used.
		$this->assertStringContainsString( 'standard/solana.png', $coins[1]['image'] );
	}

	public function test_the_new_figures_are_cached_like_everything_else(): void {
		Services::prices()->globalMarket();
		Services::prices()->globalMarket( 'eur' );
		Services::prices()->trending();
		Services::prices()->trending();

		$this->assertCount( 2, HttpStub::$requests );
	}

	public function test_an_unknown_currency_is_left_out(): void {
		$this->assertSame( array(), Services::prices()->fiatRates( 'usd', array( 'zzz' ) ) );
	}

	public function test_no_open_exchange_rates_request_is_ever_made(): void {
		do_shortcode( '[ucwp_widget type="crypto-price-table-1" count="5" parent_width="100%" default_currency="eur"]' );

		foreach ( HttpStub::$requests as $url ) {
			$this->assertStringNotContainsString( 'openexchangerates.org', $url );
		}
	}

	public function test_a_widget_in_another_currency_gets_the_real_dollar_rate(): void {
		if ( ! self::has_react_build() ) {
			$this->markTestSkipped( 'React bundles are not built.' );
		}

		$html = (string) do_shortcode( '[ucwp_widget type="crypto-price-table-1" count="5" parent_width="100%" default_currency="eur"]' );

		$rate = self::widget_props( $html )[0]['settings']['usd_conversion_rate'];

		// The old code cast this to an integer, turning 0.92 into 0.
		$this->assertEqualsWithDelta( 0.92, $rate, 0.000001 );
	}

	public function test_it_works_without_an_api_key(): void {
		delete_option( 'ultimate_crypto_widget_coingecko_api_key' );
		$prices = $this->fresh_provider();

		$this->assertNotEmpty( $prices->markets() );
		$this->assertArrayNotHasKey( 'x-cg-demo-api-key', HttpStub::$last_headers );
	}

	public function test_a_saved_api_key_is_sent_as_a_header_and_never_in_the_url(): void {
		update_option( 'ultimate_crypto_widget_coingecko_api_key', 'secret-key-123' );
		$prices = $this->fresh_provider();

		$prices->markets();

		$this->assertSame( 'secret-key-123', HttpStub::$last_headers['x-cg-demo-api-key'] ?? null );
		$this->assertStringNotContainsString( 'secret-key-123', HttpStub::$requests[0] );
	}

	public function test_only_real_requests_count_against_the_quota(): void {
		$prices = Services::prices();
		$before = $prices->callCount();

		$prices->markets();
		$prices->markets();
		$prices->coin( 'bitcoin' );

		$this->assertSame( $before + 2, $prices->callCount() );
	}

	public function test_a_failed_request_raises_a_price_data_exception(): void {
		remove_all_filters( 'pre_http_request' );
		add_filter(
			'pre_http_request',
			static function () {
				return new \WP_Error( 'http_request_failed', 'timed out' );
			}
		);

		try {
			$this->expectException( PriceDataException::class );
			Services::prices()->coin( 'dogecoin' );
		} finally {
			remove_all_filters( 'pre_http_request' );
			HttpStub::install();
		}
	}

	public function test_a_real_wordpress_response_is_read_without_warnings_and_keeps_its_headers(): void {
		$client   = Services::container()->get( \Cyberinfomatic\UltimateCryptoWidget\Lib\WPToolkit\Contracts\Http\HttpClient::class );
		$warnings = array();

		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_set_error_handler
		set_error_handler(
			static function ( int $level, string $message ) use ( &$warnings ) {
				$warnings[] = $message;
				return true;
			}
		);
		try {
			$response = $client->send( \Cyberinfomatic\UltimateCryptoWidget\Lib\WPToolkit\Contracts\Http\HttpRequest::get( 'https://api.coingecko.com/api/v3/coins/markets' ) );
		} finally {
			restore_error_handler();
		}

		$this->assertSame( array(), $warnings );
		$this->assertSame( 'application/json', $response->header( 'Content-Type' ) );
		$this->assertSame( 'a=1, b=2', $response->header( 'set-cookie' ) );
	}

	public function test_the_provider_can_be_replaced_by_a_filter(): void {
		$replacement = $this->createMock( PriceProvider::class );
		$replacement->method( 'markets' )->willReturn( array( array( 'id' => 'from-the-replacement' ) ) );
		add_filter( 'ucwp_price_provider', static fn () => $replacement );

		$this->assertSame( 'from-the-replacement', $this->fresh_provider()->markets()[0]['id'] );
	}

	public function test_the_widget_catalogue_needs_no_open_exchange_account(): void {
		delete_option( 'ultimate_crypto_widget_openexchangerates_app_id' );

		$this->assertNotEmpty( self::widget_types() );
		$this->assertNotSame( '', (string) do_shortcode( '[ucwp_widget type="multi-currencies-tab" default_currency="usd" currencies="usd,eur"]' ) );
	}

	/**
	 * A provider built now, from the options and filters as they are in this test.
	 */
	private function fresh_provider(): PriceProvider {
		$provider = \Cyberinfomatic\UltimateCryptoWidget\Providers\DataProvider::make( Services::container() );
		$provider->clearCache();

		return $provider;
	}
}

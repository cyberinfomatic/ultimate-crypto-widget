<?php
/**
 * Defects that are fixed and must stay fixed.
 *
 * @package Ultimate_Crypto_Widget
 */

namespace Cyberinfomatic\UltimateCryptoWidget\Tests\Integration;

use Cyberinfomatic\UltimateCryptoWidget\Controllers\WidgetPostType;
use Cyberinfomatic\UltimateCryptoWidget\Legacy\Services;
use Cyberinfomatic\UltimateCryptoWidget\Tests\Support\HttpStub;
use Cyberinfomatic\UltimateCryptoWidget\Tests\Support\TestCase;

/**
 * These were open in the Pro 0.0.5 code and are already fixed in this repository.
 * The rewrite must not bring them back.
 */
final class RegressionTest extends TestCase {

	/**
	 * Set by the probe class below.
	 *
	 * @var bool
	 */
	public static bool $probe_ran = false;

	public function set_up() {
		parent::set_up();
		self::$probe_ran = false;
	}

	public function tear_down() {
		global $wp_rest_server;
		$wp_rest_server = null;
		parent::tear_down();
	}


	public function test_there_is_no_pro_reinstall_route(): void {
		$this->assertArrayNotHasKey( '/ultimate-crypto-widget/v1/pro-reinstall', $this->routes() );
	}

	public function test_shortcode_attributes_are_never_unserialized_into_objects(): void {
		$payload = serialize( new UnserializeProbe() ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize

		WidgetPostType::get_instance()->shortcode(
			array(
				'type'             => 'crypto-price-picker-1',
				'default_currency' => 'usd',
				'count'            => $payload,
			)
		);

		$this->assertFalse( self::$probe_ran, 'An object from a shortcode attribute was instantiated.' );
	}

	public function test_saving_without_the_widget_form_raises_no_warning(): void {
		$_POST    = array();
		$warnings = array();

		// The handler catches exceptions, so collect PHP warnings directly.
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_set_error_handler
		set_error_handler(
			static function ( int $level, string $message ) use ( &$warnings ) {
				$warnings[] = $message;
				return true;
			},
			E_WARNING | E_NOTICE
		);
		try {
			self::factory()->post->create( array( 'post_type' => 'ucwp_widget' ) );
		} finally {
			restore_error_handler();
		}

		$this->assertSame( array(), $warnings );
	}

	public function test_only_administrators_can_clear_the_cache(): void {
		Services::prices()->markets(); // Warm the cache.
		$calls = count( HttpStub::$requests );

		wp_set_current_user( self::factory()->user->create( array( 'role' => 'subscriber' ) ) );
		$_POST['security'] = wp_create_nonce( 'ucwp_clear_api_cache' );

		try {
			do_action( 'admin_post_ucwp_clear_api_cache' );
		} catch ( \WPDieException $e ) {
			unset( $e ); // Being refused is the correct outcome.
		}

		Services::prices()->markets();
		$this->assertSame( $calls, count( HttpStub::$requests ), 'A subscriber cleared the API cache.' );
	}

	public function test_clearing_the_cache_forgets_every_cached_price(): void {
		$prices = Services::prices();
		$prices->markets();
		$prices->coin( 'bitcoin' );
		$prices->markets(); // From the cache.
		$this->assertSame( 2, count( HttpStub::$requests ) );

		$prices->clearCache();

		$prices->markets();
		$prices->coin( 'bitcoin' );
		$this->assertSame( 4, count( HttpStub::$requests ), 'Cached prices survived "clear cache".' );
	}

	/**
	 * @return array<string, array> The plugin's routes with their handlers.
	 */
	private function routes(): array {
		global $wp_rest_server;
		$wp_rest_server = null;

		return rest_get_server()->get_routes( 'ultimate-crypto-widget/v1' );
	}
}

/**
 * Records that it was rebuilt from a serialized string.
 */
final class UnserializeProbe { // phpcs:ignore Generic.Files.OneObjectStructurePerFile.MultipleFound

	public function __wakeup() {
		RegressionTest::$probe_ran = true;
	}
}

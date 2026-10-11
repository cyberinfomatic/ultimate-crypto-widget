<?php
/**
 * Defects that are fixed and must stay fixed.
 *
 * @package Ultimate_Crypto_Widget
 */

namespace Cyberinfomatic\UltimateCryptoWidget\Tests\Integration;

use Cyberinfomatic\UltimateCryptoWidget\Controllers\WidgetPostType;
use Cyberinfomatic\UltimateCryptoWidget\Helpers\APIHelper;
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

	public function test_every_rest_route_has_a_permission_callback(): void {
		foreach ( $this->routes() as $route => $handlers ) {
			if ( '/ultimate-crypto-widget/v1' === $route ) {
				continue; // The namespace index is WordPress's own.
			}
			foreach ( $handlers as $handler ) {
				$this->assertNotEmpty( $handler['permission_callback'] ?? null, "{$route} has no permission_callback." );
			}
		}
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
		APIHelper::cache( 'ucwp_test_entry', array( 'price' => 1 ), 60 );

		wp_set_current_user( self::factory()->user->create( array( 'role' => 'subscriber' ) ) );
		$_POST['security'] = wp_create_nonce( 'ucwp_clear_api_cache' );
		add_filter( 'wp_redirect', '__return_false' );

		try {
			do_action( 'admin_post_ucwp_clear_api_cache' );
		} catch ( \WPDieException $e ) {
			unset( $e ); // Being refused is the correct outcome.
		}

		$this->assertNotFalse( get_transient( 'ucwp_test_entry' ), 'A subscriber cleared the API cache.' );
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

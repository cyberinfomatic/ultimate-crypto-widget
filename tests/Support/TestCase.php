<?php
/**
 * Base test case.
 *
 * @package Ultimate_Crypto_Widget
 */

namespace Cyberinfomatic\UltimateCryptoWidget\Tests\Support;

use Cyberinfomatic\UltimateCryptoWidget\Controllers\WidgetPostType;
use Cyberinfomatic\UltimateCryptoWidget\Helpers\APIHelper;
use Throwable;
use WP_UnitTestCase;

/**
 * Shared set-up: an administrator, API keys saved, no network.
 */
abstract class TestCase extends WP_UnitTestCase {

	public function set_up() {
		parent::set_up();

		HttpStub::reset();
		APIHelper::clear_cache();

		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );

		update_option( 'ultimate_crypto_widget_coingecko_api_key', 'test-coingecko-key' );
		update_option( 'ultimate_crypto_widget_openexchangerates_app_id', 'test-openexchange-id' );
		update_option( 'ultimate_crypto_widget_cache_interval', 10 );

		$_POST = array();
		$_GET  = array();

		// Start each test with nothing enqueued.
		unset( $GLOBALS['wp_scripts'], $GLOBALS['wp_styles'] );
	}

	/**
	 * Every widget type the plugin registers.
	 *
	 * @return string[]
	 */
	protected static function widget_types(): array {
		return array_keys( WidgetPostType::WidgetTypes() );
	}

	/**
	 * The widget types this plugin can render itself (the rest need the Pro add-on).
	 *
	 * @return string[]
	 */
	protected static function free_widget_types(): array {
		return array_keys(
			array_filter(
				WidgetPostType::WidgetTypes(),
				static function ( array $type ): bool {
					return empty( $type['pro'] );
				}
			)
		);
	}

	/**
	 * Whether the React bundles have been built (npm run build).
	 */
	protected static function has_react_build(): bool {
		return is_dir( UCWP_PLUGIN_DIR . 'assets/react-build/widgets' );
	}

	/**
	 * Asserts behaviour the plugin does not have yet.
	 *
	 * The callback describes how the plugin should behave. While the bug exists the
	 * test is reported as incomplete; once the callback passes, the test fails so
	 * that this wrapper gets removed and the assertion becomes a normal one.
	 *
	 * @param string   $bug     What is wrong today and which phase fixes it.
	 * @param callable $desired Assertions for the correct behaviour.
	 */
	protected function known_bug( string $bug, callable $desired ): void {
		try {
			$desired();
		} catch ( Throwable $e ) {
			$this->markTestIncomplete( 'Known bug, still present: ' . $bug . ' [' . $e->getMessage() . ']' );
		}

		$this->fail( 'This known bug now behaves correctly, so remove the known_bug() wrapper: ' . $bug );
	}
}

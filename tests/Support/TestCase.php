<?php
/**
 * Base test case.
 *
 * @package Ultimate_Crypto_Widget
 */

namespace Cyberinfomatic\UltimateCryptoWidget\Tests\Support;

use Cyberinfomatic\UltimateCryptoWidget\Widgets\WidgetCatalog;
use Throwable;
use WP_UnitTestCase;

/**
 * Shared set-up: an administrator, API keys saved, no network.
 */
abstract class TestCase extends WP_UnitTestCase {

	public function set_up() {
		parent::set_up();

		HttpStub::reset();
		Services::prices()->clearCache();

		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );

		update_option( 'ultimate_crypto_widget_coingecko_api_key', 'test-coingecko-key' );
		update_option( 'ultimate_crypto_widget_openexchangerates_app_id', 'test-openexchange-id' );
		update_option( 'ultimate_crypto_widget_cache_interval', 10 );

		$_POST = array();
		$_GET  = array();

		// Start each test with nothing enqueued.
		unset( $GLOBALS['wp_scripts'], $GLOBALS['wp_styles'] );
	}

	/** A Pro type this site cannot use, registered by tests/bootstrap.php in every edition. */
	protected const LOCKED_TYPE = 'test-locked-widget';

	/**
	 * Every widget type the plugin registers (not the test's locked type).
	 *
	 * @return string[]
	 */
	protected static function widget_types(): array {
		return array_values( array_diff( array_keys( Services::container()->get( WidgetCatalog::class )->all() ), array( self::LOCKED_TYPE ) ) );
	}

	/**
	 * The widget types this plugin can render itself (the rest need the Pro add-on).
	 *
	 * @return string[]
	 */
	protected static function free_widget_types(): array {
		return array_keys(
			array_filter(
				Services::container()->get( WidgetCatalog::class )->all(),
				static fn ( $definition ): bool => $definition->available
			)
		);
	}

	/**
	 * The data each widget element in this HTML carries for its React component.
	 *
	 * @return array<int, array<string, mixed>> One entry per widget, in page order.
	 */
	protected static function widget_props( string $html ): array {
		$all = array();
		if ( preg_match_all( '/data-ucw-props="([^"]*)"/', $html, $matches ) ) {
			foreach ( $matches[1] as $encoded ) {
				$all[] = json_decode( html_entity_decode( $encoded, ENT_QUOTES ), true );
			}
		}
		return $all;
	}

	/**
	 * A widget's element without its data and with a fixed id, for comparing markup.
	 */
	protected static function widget_shell( string $html ): string {
		$html = preg_replace( '/ data-ucw-props="[^"]*"/', '', $html );
		return preg_replace( '/ucwp-react-\d+/', 'ucwp-react-ID', (string) $html );
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

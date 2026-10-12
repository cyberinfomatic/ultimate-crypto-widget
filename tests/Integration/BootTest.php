<?php
/**
 * How the plugin starts.
 *
 * @package Ultimate_Crypto_Widget
 */

namespace Cyberinfomatic\UltimateCryptoWidget\Tests\Integration;

use Cyberinfomatic\UltimateCryptoWidget\Plugin;
use Cyberinfomatic\UltimateCryptoWidget\Lib\WPToolkit\Foundation\Application;
use Cyberinfomatic\UltimateCryptoWidget\Tests\Support\TestCase;
use ReflectionClass;

/**
 * The plugin boots through WPToolkit's guard, using its own bundled, renamed copy.
 */
final class BootTest extends TestCase {

	public function test_the_plugin_started(): void {
		$this->assertInstanceOf( Application::class, Plugin::app() );
		$this->assertSame( 1, did_action( 'ucwp_loaded' ) );
		$this->assertSame( 1, did_action( 'init' ) );
	}

	public function test_it_runs_its_own_bundled_copy_of_the_toolkit(): void {
		$file = ( new ReflectionClass( Application::class ) )->getFileName();

		$this->assertStringContainsString(
			'lib/wptoolkit/src/Foundation/Application.php',
			str_replace( '\\', '/', (string) $file )
		);
		$this->assertFalse(
			class_exists( 'Codad5\\WPToolkit\\Foundation\\Application', false ),
			'The toolkit must be loaded under the plugin\'s own namespace only.'
		);
	}

	public function test_the_bundled_toolkit_satisfies_the_version_the_plugin_asks_for(): void {
		$this->assertMatchesRegularExpression( '/^1\./', Application::VERSION );
	}

	public function test_the_constants_the_old_classes_read_are_defined(): void {
		foreach ( array( 'UCWP_PLUGIN_FILE', 'UCWP_PLUGIN_DIR', 'UCWP_PLUGIN_URL', 'UCWP_PLUGIN_BASENAME', 'UCW_ENV' ) as $constant ) {
			$this->assertTrue( defined( $constant ), "{$constant} is not defined." );
		}
		$this->assertStringEndsWith( 'ultimate-crypto-widget.php', UCWP_PLUGIN_FILE );
		$this->assertSame( 'prod', UCW_ENV, 'A test site is not a development environment.' );
	}

	public function test_an_unmet_requirement_pauses_the_plugin_instead_of_crashing(): void {
		$booted = false;
		$guard  = require dirname( __DIR__, 2 ) . '/lib/wptoolkit/bootstrap/guard.php';

		$started = $guard(
			UCWP_PLUGIN_FILE,
			array(
				'php'      => '99.0',
				'name'     => 'Ultimate Crypto Widget',
				'autoload' => 'standalone',
			),
			static function () use ( &$booted ) {
				$booted = true;
			}
		);

		$this->assertFalse( $started );
		$this->assertFalse( $booted, 'The plugin code must not run when a requirement is not met.' );

		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		grant_super_admin( get_current_user_id() );
		ob_start();
		do_action( 'admin_notices' );
		$notices = ob_get_clean();

		$this->assertStringContainsString( 'Ultimate Crypto Widget has been paused', $notices );
		$this->assertStringContainsString( 'PHP 99.0 or newer is required', $notices );
	}

	public function test_the_main_file_holds_no_code_of_its_own(): void {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$source = file_get_contents( UCWP_PLUGIN_FILE );

		// It must parse on PHP 5.6, so none of the syntax the rest of the plugin uses.
		$this->assertDoesNotMatchRegularExpression( '/\bnamespace\b|\buse\s+[A-Z]|\?\?|\bfn\s*\(|\):\s*\w+\s*\{|\[\s*\]|=>\s*\[/', $source );
		$this->assertStringContainsString( "require __DIR__ . '/src/boot.php';", $source );
	}
}

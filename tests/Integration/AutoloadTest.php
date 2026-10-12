<?php
/**
 * Class loading.
 *
 * @package Ultimate_Crypto_Widget
 */

namespace Cyberinfomatic\UltimateCryptoWidget\Tests\Integration;

use Cyberinfomatic\UltimateCryptoWidget\Tests\Support\TestCase;

/**
 * The plugin's classes are found by name alone: …\Widgets\WidgetCatalog must be in
 * src/Widgets/WidgetCatalog.php. A file named in the wrong case still loads on
 * Windows and macOS and then fails on the live server, so every file is checked here.
 */
final class AutoloadTest extends TestCase {

	private const PREFIX = 'Cyberinfomatic\\UltimateCryptoWidget\\';

	public static function set_up_before_class() {
		parent::set_up_before_class();
		// The Elementor element extends one of Elementor's classes.
		require_once __DIR__ . '/../Support/ElementorStubs.php';
	}

	public function test_no_hand_written_autoloader_is_left(): void {
		$this->assertFileDoesNotExist( UCWP_PLUGIN_DIR . 'src/Helpers/autoloader.php' );
		$this->assertFileExists( UCWP_PLUGIN_DIR . 'lib/wptoolkit/bootstrap/namespaces.php' );
	}

	/**
	 * @dataProvider class_file_provider
	 */
	public function test_each_file_holds_the_class_its_path_names( string $class, string $file ): void {
		$this->assertTrue(
			class_exists( $class ) || interface_exists( $class ) || enum_exists( $class ),
			"{$file} should declare {$class}."
		);

		$reflection = new \ReflectionClass( $class );

		// PHP ignores case in class names; the live server's disk does not.
		$this->assertSame( $class, $reflection->getName(), "The class in {$file} is spelled differently from its path." );
		$this->assertSame( wp_normalize_path( $file ), wp_normalize_path( (string) $reflection->getFileName() ) );
	}

	public function test_a_class_from_every_folder_is_reachable(): void {
		$folders = array();
		foreach ( array_keys( $this->class_file_provider() ) as $relative ) {
			$folders[ strtok( $relative, '/' ) ] = true;
		}

		$this->assertGreaterThanOrEqual( 7, count( $folders ), 'Expected Admin, Blocks, Data, Http, Integrations, Providers and Widgets at least.' );
	}

	/**
	 * Every PHP file under src/ except the start-up script, with the class PSR-4 says it holds.
	 * Names come from the disk as they are spelled there.
	 *
	 * @return array<string, array{0: string, 1: string}>
	 */
	public function class_file_provider(): array {
		$root  = dirname( __DIR__, 2 ) . '/src';
		$cases = array();

		$files = new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( $root, \FilesystemIterator::SKIP_DOTS ) );
		foreach ( $files as $file ) {
			$relative = str_replace( '\\', '/', substr( $file->getPathname(), strlen( $root ) + 1 ) );
			if ( 'php' !== $file->getExtension() || 'boot.php' === $relative ) {
				continue;
			}
			$cases[ $relative ] = array( self::PREFIX . str_replace( '/', '\\', substr( $relative, 0, -4 ) ), $file->getPathname() );
		}

		return $cases;
	}
}

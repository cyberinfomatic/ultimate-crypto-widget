<?php
/**
 * PHPUnit bootstrap: loads WordPress's test suite, then the plugin.
 *
 * WordPress core and its test library come from Composer (roots/wordpress-no-content,
 * wp-phpunit/wp-phpunit), so no SVN checkout or Docker is needed.
 *
 * @package Ultimate_Crypto_Widget
 */

$ucwp_root = dirname( __DIR__ );

if ( ! file_exists( $ucwp_root . '/vendor/autoload.php' ) ) {
	echo 'Run "composer install" first.' . PHP_EOL; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	exit( 1 );
}

require_once $ucwp_root . '/vendor/autoload.php';

putenv( 'WP_PHPUNIT__TESTS_CONFIG=' . $ucwp_root . '/tests/wp-tests-config.php' ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_putenv

// roots/wordpress-no-content ships no wp-content; give the suite an empty one.
foreach ( array( '', '/plugins', '/themes', '/uploads' ) as $ucwp_dir ) {
	if ( ! is_dir( $ucwp_root . '/tests/.wp-content' . $ucwp_dir ) ) {
		mkdir( $ucwp_root . '/tests/.wp-content' . $ucwp_dir, 0777, true ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_mkdir
	}
}

$ucwp_tests_dir = getenv( 'WP_PHPUNIT__DIR' );

require_once $ucwp_tests_dir . '/includes/functions.php';

require_once __DIR__ . '/Support/HttpStub.php';

tests_add_filter(
	'muplugins_loaded',
	static function () use ( $ucwp_root ) {
		// No request in these tests may reach a real API.
		\Cyberinfomatic\UltimateCryptoWidget\Tests\Support\HttpStub::install();

		// UCWP_TEST_PLUGIN_DIR points the suite at an unpacked release zip, to test what ships.
		$plugin_dir = getenv( 'UCWP_TEST_PLUGIN_DIR' ) ?: $ucwp_root;

		require rtrim( $plugin_dir, '/\\' ) . '/ultimate-crypto-widget.php';
	}
);

require $ucwp_tests_dir . '/includes/bootstrap.php';

// The settings form is built on the plugin's own admin screens. Tests never visit one, so build
// it once here, before WordPress backs up the hooks it registers (it restores them after each test).
\Cyberinfomatic\UltimateCryptoWidget\Plugin::app()->container()->get( \Cyberinfomatic\UltimateCryptoWidget\Admin\WidgetEditor::class )->box();

// These build on WP_UnitTestCase, which exists only now.
require_once __DIR__ . '/Support/Services.php';
require_once __DIR__ . '/Support/Snapshots.php';
require_once __DIR__ . '/Support/WidgetFactory.php';
require_once __DIR__ . '/Support/TestCase.php';

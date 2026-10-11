<?php
/**
 * Starts the plugin. Loaded by the guard in the main plugin file once the PHP,
 * WordPress and WPToolkit versions are known to be good.
 *
 * @package UltimateCryptoWidget
 */

use Cyberinfomatic\UltimateCryptoWidget\Helpers\Debugger;
use Cyberinfomatic\UltimateCryptoWidget\Lib\WPToolkit\Foundation\Application;
use Cyberinfomatic\UltimateCryptoWidget\Providers\LegacyProvider;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$ucwp_plugin_file = dirname( __DIR__ ) . '/ultimate-crypto-widget.php';

// Constants the existing classes read. They go away as those classes are replaced.
if ( ! defined( 'UCWP_PLUGIN_FILE' ) ) {
	define( 'UCWP_PLUGIN_FILE', $ucwp_plugin_file );
}
if ( ! defined( 'UCWP_PLUGIN_DIR' ) ) {
	define( 'UCWP_PLUGIN_DIR', plugin_dir_path( $ucwp_plugin_file ) );
}
if ( ! defined( 'UCWP_PLUGIN_URL' ) ) {
	define( 'UCWP_PLUGIN_URL', plugin_dir_url( $ucwp_plugin_file ) );
}
if ( ! defined( 'UCWP_PLUGIN_BASENAME' ) ) {
	define( 'UCWP_PLUGIN_BASENAME', plugin_basename( $ucwp_plugin_file ) );
}

require_once __DIR__ . '/Helpers/autoloader.php';

$ucwp_app = Application::create(
	$ucwp_plugin_file,
	array(
		'slug'        => 'ultimate-crypto-widget',
		'text_domain' => 'ultimate-crypto-widget',
	)
);

// WordPress's own environment setting decides "dev", not the site's hostname.
if ( ! defined( 'UCW_ENV' ) ) {
	define( 'UCW_ENV', $ucwp_app->isDevelopment() ? 'dev' : 'prod' );
}
if ( ! defined( 'UCWP_MAIN_SERVER' ) ) {
	define( 'UCWP_MAIN_SERVER', 'dev' === UCW_ENV ? 'http://localhost:8000' : 'https://ucp.cyberinfomatic.com' );
}

Debugger::set_dev( 'dev' === UCW_ENV );

$ucwp_app->providers( array( LegacyProvider::class ) )->boot();

unset( $ucwp_plugin_file, $ucwp_app );

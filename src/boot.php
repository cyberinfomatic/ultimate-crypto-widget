<?php
/**
 * Starts the plugin. Loaded by the guard in the main plugin file once the PHP,
 * WordPress and WPToolkit versions are known to be good.
 *
 * @package UltimateCryptoWidget
 */

use Cyberinfomatic\UltimateCryptoWidget\Lib\WPToolkit\Foundation\Application;
use Cyberinfomatic\UltimateCryptoWidget\Plugin;
use Cyberinfomatic\UltimateCryptoWidget\Providers\AdminProvider;
use Cyberinfomatic\UltimateCryptoWidget\Providers\AssetsProvider;
use Cyberinfomatic\UltimateCryptoWidget\Providers\BlocksProvider;
use Cyberinfomatic\UltimateCryptoWidget\Providers\DataProvider;
use Cyberinfomatic\UltimateCryptoWidget\Providers\HttpProvider;
use Cyberinfomatic\UltimateCryptoWidget\Providers\IntegrationsProvider;
use Cyberinfomatic\UltimateCryptoWidget\Providers\SettingsProvider;
use Cyberinfomatic\UltimateCryptoWidget\Providers\WidgetsProvider;

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

$ucwp_app->providers( array( AssetsProvider::class, SettingsProvider::class, DataProvider::class, WidgetsProvider::class, AdminProvider::class, BlocksProvider::class, IntegrationsProvider::class, HttpProvider::class ) )->boot();

// Lets an add-on find the running plugin; fires the ucwp_loaded action.
Plugin::started( $ucwp_app, $ucwp_plugin_file );

unset( $ucwp_plugin_file, $ucwp_app );

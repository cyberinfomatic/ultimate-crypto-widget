<?php
/**
 * Ultimate Crypto Widget
 *
 * @package           UltimateCryptoWidget
 * @author            Cyberinfomatic
 * @copyright         2024 Cyberinfomatic
 * @license           GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name:       Ultimate Crypto Widget
 * Plugin URI:        https://ultimatecryptowidget.com
 * Description:       Crypto Currency Widget on the go!
 * Version:           1.0.0-beta.1
 * Requires at least: 6.5.5
 * Requires PHP:      8.2
 * Author:            Cyberinfomatic
 * Author URI:        https://cyberinfomatic.com
 * Text Domain:       ultimate-crypto-widget
 * License:           GPL v2 or later
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 */

// Prevent direct script access
if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

// This file stays valid PHP 5.6 on purpose: on a server that is too old it must still
// parse, so the guard can show administrators a notice instead of a fatal error. All
// real code starts in src/boot.php.
call_user_func(
	require __DIR__ . '/lib/wptoolkit/bootstrap/guard.php',
	__FILE__,
	array(
		'php'        => '8.2',
		'wp'         => '6.5.5',
		'toolkit'    => '^1.0',
		'name'       => 'Ultimate Crypto Widget',
		// The bundled copy in lib/wptoolkit, never a Composer vendor directory.
		'autoload'   => 'standalone',
		// The plugin's own classes, by PSR-4: …\Widgets\WidgetCatalog is src/Widgets/WidgetCatalog.php.
		// File and folder names must match class names exactly, including case.
		'namespaces' => array( 'Cyberinfomatic\\UltimateCryptoWidget\\' => __DIR__ . '/src' ),
	),
	function () {
		require __DIR__ . '/src/boot.php';
	}
);

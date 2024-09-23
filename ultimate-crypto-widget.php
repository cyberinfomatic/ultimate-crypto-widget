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
 * Version:           0.1.4
 * Requires at least: 6.5.5
 * Requires PHP:      8.0
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

// Check PHP version
if (version_compare(PHP_VERSION, '8.0', '<')) {
	add_action('admin_notices', function () {
		?>
		<div class="notice notice-error is-dismissible">
			<p><?php esc_html_e('Ultimate Crypto Widget requires PHP 8.0 or higher to run.', 'ultimate-crypto-widget'); ?></p>
		</div>
		<?php
	});
	return;
}

// Check WordPress version
if (version_compare(get_bloginfo('version'), '6.5.5', '<')) {
	add_action('admin_notices', function () {
		?>
		<div class="notice notice-error is-dismissible">
			<p><?php esc_html_e('Ultimate Crypto Widget requires WordPress 6.5.5 or higher to run.', 'ultimate-crypto-widget'); ?></p>
		</div>
		<?php
	});
	return;
}


// Define plugin constants
if (!defined('UCWP_PLUGIN_FILE')) {
	define('UCWP_PLUGIN_FILE', __FILE__);
}
if (!defined('UCWP_PLUGIN_DIR')) {
	define('UCWP_PLUGIN_DIR', plugin_dir_path(__FILE__));
}
if (!defined('UCWP_PLUGIN_URL')) {
	define('UCWP_PLUGIN_URL', plugin_dir_url(__FILE__));
}
if (!defined('UCWP_PLUGIN_BASENAME')) {
	define('UCWP_PLUGIN_BASENAME', plugin_basename(__FILE__));
}

// Helper function to determine the environment
if (!function_exists( 'ucwp_env' )) {
	function ucwp_env(): string {
		$localDomains = ['localhost', 'wp.local', 'ridox-wp.local'];
		$host = sanitize_text_field($_SERVER['SERVER_NAME'] ?? '');
		foreach ($localDomains as $domain) {
			if (str_contains($host, $domain)) {
				return 'dev';
			}
		}
		return 'prod';
	}
}

if (!defined('UCW_ENV')) {
	define('UCW_ENV', ucwp_env());
}

// Define main server URL based on environment
if (!defined('UCWP_MAIN_SERVER')) {
	$protocol = (UCW_ENV === 'dev') ? 'http://' : 'https://';
	$domain = (UCW_ENV === 'dev') ? 'localhost:8000' : 'ucp.cyberinfomatic.com';
	define('UCWP_MAIN_SERVER', $protocol . $domain);
}

// Load autoloader
require_once UCWP_PLUGIN_DIR . '/src/Helpers/autoloader.php';

use Cyberinfomatic\UltimateCryptoWidget\App;
use Cyberinfomatic\UltimateCryptoWidget\Helpers\Debugger;

// Set debugger environment
Debugger::set_dev(UCW_ENV === 'dev');

// Initialize and start the application
$app = new App();
$app->start();

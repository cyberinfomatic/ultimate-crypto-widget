<?php
namespace Cyberinfomatic\UltimateCryptoWidget\Controllers;

use Cyberinfomatic\UltimateCryptoWidget\Helpers\{APIHelper,
	CoinGeckoHelper,
	Debugger,
	Notification
};

/**
 * Class Settings
 *
 * Handles the settings and configurations for the Ultimate Crypto Widget plugin.
 *
 * @package Cyberinfomatic\UltimateCryptoWidget\Controllers
 */
class Settings {
	const SERVER_URL = UCWP_MAIN_SERVER;
	const ADD_LICENSE_PATH = '/license/verify';
	const OPTION_PREFIX = 'ultimate_crypto_widget_';

	private static array $plugin_details;
	const SETTINGS = [
		[
			'option_name' => 'openexchangerates_app_id',
			'label' => 'Open Exchange Rates App ID',
			'type' => 'text',
			'default' => '',
			'placeholder' => 'Your Open Exchange Rates App ID',
			'description' => 'Get your App ID from <a href="https://openexchangerates.org/signup" target="_blank">Open Exchange Rates</a>',
			'tab' => 'api',
		],
		[
			'option_name' => 'coingecko_api_key',
			'label' => 'CoinGecko API Key',
			'type' => 'text',
			'default' => '',
			'placeholder' => 'Your CoinGecko API Key',
			'description' => 'Get your API Key from <a href="https://coingecko.com" target="_blank">CoinGecko</a>',
			'tab' => 'api'
		],
		[
			'option_name' => 'cache_interval',
			'label' => 'Cache Interval',
			'type' => 'number',
			'default' => 10,
			'placeholder' => 'Cache Interval',
			'description' => 'Cache interval in minutes',
			'tab' => 'api'
		]
	];

	/**
	 * Load the settings and add hooks.
	 */
	static function load(): void {
		add_action('admin_init', [self::class, 'register_settings']);
		add_action('admin_post_ucwp_clear_api_cache', function() {
			// sanitize and verify nonce
			$security = sanitize_text_field(wp_unslash($_POST['security'] ?? ''));
			if (!wp_verify_nonce($security, 'ucwp_clear_api_cache')) {
				wp_die(esc_html__('Invalid security token', 'ultimate-crypto-widget')); // Display error or redirect
				return;
			}
			APIHelper::clear_cache();
			Notification::add_notification(esc_html__('API Cache Cleared', 'ultimate-crypto-widget'));
			return wp_redirect(esc_url(Page::get_page_url('ultimate-crypto-widget-settings')));
		});
		add_action('wp_head', [self::class, 'add_ucwp_head_information']);
	}

	static function get_plugin_detail($key = null) {
		if(!function_exists('get_plugin_data')) {
			require_once(ABSPATH . 'wp-admin/includes/plugin.php');
		}
		if(!isset(self::$plugin_details)) {
			self::$plugin_details = get_plugin_data(UCWP_PLUGIN_FILE);
		}
		return $key ? self::$plugin_details[$key] : self::$plugin_details;
	}



	/**
	 * Add ucwp head information for pro users.
	 */
	static function add_ucwp_head_information(): void {
//		$hash is plugin version
		$hash = hash('sha256', self::get_plugin_detail('Version'));
		echo "<meta name='ucwp." . esc_attr(str_replace(['http://', 'https://'], '', home_url())) . "' content='" . esc_attr($hash) . "'>";
	}

	/**
	 * Register the settings.
	 */
	static function register_settings(): void {
		foreach (self::SETTINGS as $setting) {
			register_setting(self::OPTION_PREFIX . $setting['tab'], self::OPTION_PREFIX . $setting['option_name'], [
				'type' => $setting['type'] ?? 'text',
				'description' => $setting['description'] ?? '',
				'default' => $setting['default'] ?? ''
			]);
		}

		$proUrl = self::getGetProUrl();
		$notificationMessage = sprintf(
		/* translators: %1$s: Opening anchor tag with the Pro URL, %2$s: Closing anchor tag */
			__('You are using the free version of this plugin. To get more features , consider %1$sUpgrading to Pro%2$s', 'ultimate-crypto-widget'),
			'<a href="' . esc_url($proUrl) . '">',
			'</a>'
		);

		Notification::add_notification($notificationMessage, 'info', 'plugin');

		if (!self::get('coingecko_api_key') ) {
			$notificationMessage = sprintf(
				__('You need to set your CoinGecko API Key in the %1$ssettings%2$s to use the plugin.', 'ultimate-crypto-widget'),
				'<a href="' . esc_url(Page::get_page_url('ultimate-crypto-widget-settings')) . '">',
				'</a>'
			);
			Notification::add_notification($notificationMessage, 'error');
		}
		if (!self::get('openexchangerates_app_id') ) {
			$notificationMessage = sprintf(
				__('You need to set your OpenExchangeRate API Key in the %1$ssettings%2$s to use the plugin.', 'ultimate-crypto-widget'),
				'<a href="' . esc_url(Page::get_page_url('ultimate-crypto-widget-settings')) . '">',
				'</a>'
			);
			Notification::add_notification($notificationMessage, 'error');
		}
	}


	/**
	 * Display the settings page.
	 */
	static function settings_page(): void {
		$upgrade_url = self::getGetProUrl();
		View::render('admin/settings', [
			'settings' => self::SETTINGS,
			'prefix' => self::OPTION_PREFIX,
			'clear_cache_post_url' => admin_url('admin-post.php?action=ucwp_clear_api_cache'),
			'coin_gecko_call_count' => CoinGeckoHelper::get_api_call_count(),
			'upgrade_url' => $upgrade_url,
		]);
	}

	/**
	 * Store a setting value.
	 *
	 * @param string $option_name The name of the option.
	 * @param mixed $value The value to store.
	 * @return bool True if the option was updated successfully, false otherwise.
	 */
	static function store(string $option_name, string|int|null $value): bool {
		$value = sanitize_text_field($value);
		return update_option(self::OPTION_PREFIX . $option_name, $value);
	}

	/**
	 * Get a setting value.
	 *
	 * @param string $option_name The name of the option.
	 * @param mixed $default The default value if the option does not exist.
	 * @return mixed The option value or the default value.
	 */
	static function get(string $option_name, mixed $default = '') {
		return get_option(self::OPTION_PREFIX . $option_name, $default);
	}

	/**
	 * Get the URL for upgrading to Pro.
	 *
	 * @return string The URL for upgrading to Pro.
	 */
	static function getGetProUrl(): string {
		$href = urlencode(home_url());
		return 'https://ultimatecryptowidget.com/go-pro?utm_source=plugin&utm_medium=upgrade&utm_campaign=pro&utm_content=' . $href;
	}
}

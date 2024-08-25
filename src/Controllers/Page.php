<?php

namespace Cyberinfomatic\UltimateCryptoWidget\Controllers;

use Cyberinfomatic\UltimateCryptoWidget\Helpers\Debugger;
use Cyberinfomatic\UltimateCryptoWidget\Helpers\UCWPFileSystem;

/**
 * Class Page
 *
 * This class handles the creation and management of admin menus and submenus
 * for the Ultimate Crypto Widget plugin.
 *
 * @package Cyberinfomatic\UltimateCryptoWidget\Controllers
 */
class Page {

	/**
	 * Returns an array of menus to be added to the admin menu.
	 *
	 * @return array The array of menus.
	 */
	static function menus(): array {
		return [
			'ultimate-crypto-widget' => [
				'title' => esc_html__( 'Ultimate Crypto Widget', 'ultimate-crypto-widget' ),
				'capability' => 'manage_options',
//				'icon' => 'dashicons-chart-line',
				'icon' => 'data:image/svg+xml;base64,' . base64_encode(UCWPFileSystem::get_file_content( UCWP_PLUGIN_DIR . '/assets/images/ucwp-logo.svg')),
				'position' => '2.2',
				'callback' => 'page'
			],
		];
	}

	/**
	 * Retrieves a specific menu by its ID.
	 *
	 * @param string $id The menu ID.
	 * @return array|null The menu array or null if not found.
	 */
	static function getMenu(string $id): ?array {
		$menus = self::menus();
		return $menus[$id] ?? null;
	}

	/**
	 * Returns an array of submenus to be added to the admin menu.
	 *
	 * @return array The array of submenus.
	 */
	static function submenus() {
		return [
			'edit.php?post_type=ucwp_widget' => [
				'parent' => 'ultimate-crypto-widget',
				'title' => esc_html__( 'UCW Widgets', 'ultimate-crypto-widget' ),
				'capability' => 'manage_options',
				'callback' => false
			],

			'ultimate-crypto-widget-quick-widget' => [
				'parent' => 'ultimate-crypto-widget',
				'title' => esc_html__( 'UCW Quick Widget', 'ultimate-crypto-widget' ),
				'capability' => 'manage_options',
				'callback' => [self::class, 'quick_widget']
			],

			'ultimate-crypto-widget-settings' => [
				'parent' => 'ultimate-crypto-widget',
				'title' => esc_html__( 'Settings', 'ultimate-crypto-widget' ),
				'capability' => 'manage_options',
				'callback' => 'settings_page'
			],
		];
	}

	/**
	 * Retrieves a specific submenu by its ID.
	 *
	 * @param string $id The submenu ID.
	 * @return array|null The submenu array or null if not found.
	 */
	static function getSubmenu(string $id): ?array {
		$submenus = self::submenus();
		return $submenus[$id] ?? null;
	}

	/**
	 * Constructor for the Page class.
	 */
	public function __construct() {
	}

	/**
	 * Registers the add_menu action to load menus.
	 */
	static function load(): void {
		add_action( 'admin_menu', array( self::class, 'add_menu' ) );
	}

	/**
	 * Adds menus and submenus to the admin menu.
	 */
	static function add_menu() {
		foreach ( self::menus() as $menu_slug => $menu ) {
			add_menu_page(
				$menu['title'],
				$menu['title'],
				$menu['capability'],
				$menu_slug,
				self::get_page_callback( $menu['callback'] ),
				$menu['icon'],
				floatval($menu['position'])
			);
		}

		foreach ( self::submenus() as $submenu_slug => $submenu ) {
			add_submenu_page(
				$submenu['parent'],
				$submenu['title'],
				$submenu['title'],
				$submenu['capability'],
				$submenu_slug,
				self::get_page_callback( $submenu['callback'] )
			);
		}
	}

	/**
	 * Renders the main page for the plugin.
	 */
	static function page(): void {
		View::renderReact('admin', [
			'svg_logo' => 'data:image/svg+xml;base64,' . base64_encode(UCWPFileSystem::get_file_content( UCWP_PLUGIN_DIR . '/assets/images/ucwp-logo.svg')),
			'png_logo' => 'data:image/png;base64,' . base64_encode(UCWPFileSystem::get_file_content( UCWP_PLUGIN_DIR . '/assets/images/ucwp-logo.png')),
			'pro_url' => Settings::getGetProUrl(),
			...Settings::get_plugin_detail()
		]);
	}

	/**
	 * Renders the settings page for the plugin.
	 */
	static function settings_page(): void {
		Settings::settings_page();
	}

	/**
	 * Returns the appropriate callback function for a page.
	 *
	 * @param mixed $callback The callback function.
	 *
	 * @return string|array|bool The sanitized callback function or false.
	 */
	static function get_page_callback( bool|array|string $callback ): string|array|bool {
		if ( $callback === false ) {
			return false;
		}
		return is_array( $callback ) ? $callback : array( self::class, sanitize_text_field($callback) );
	}

	/**
	 * Returns the URL for a specific page by its key.
	 *
	 * @param string $key The page key.
	 * @return string The URL of the page.
	 */
	static function get_page_url($key): string {
		$key = sanitize_key($key);
		if (!self::getMenu($key) && !self::getSubmenu($key)) {
			wp_die(esc_html__( 'Page not found', 'ultimate-crypto-widget' ));
		}
		return esc_url(admin_url('admin.php?page=' . $key));
	}

	/**
	 * Returns the current page key.
	 *
	 * @return string The key of the current page.
	 */
	static function get_current_page_key(): string {
		$query = sanitize_text_field($_SERVER['QUERY_STRING'] ?? '');
		$parts = explode('&', $query);
		foreach ($parts as $part) {
			$pair = explode('=', $part);
			if ($pair[0] === 'page') {
				return $pair[1];
			}
		}
		return '';
	}


	/**
	 * Returns all page keys (both menus and submenus).
	 *
	 * @return array The array of all page keys.
	 */
	static function get_all_page_keys(): array {
		$menu_keys = array_keys(self::menus());
		$submenu_keys = array_keys(self::submenus());
		return array_merge($menu_keys, $submenu_keys);
	}

	/**
	 * Renders the quick widget admin page.
	 */
	static function quick_widget(): void {
		wp_enqueue_script('ucwp-quick-widget', plugins_url('/assets/scripts/quick-widget.js', UCWP_PLUGIN_FILE), ['jquery'], '0.0.1', true);
		$widget_post_type_instance = WidgetPostType::get_instance();
		$meta_box = $widget_post_type_instance->get_metabox();
		View::render('admin/quick-widget', [
			'meta_box' => $meta_box->get_fields_html()
		]);
	}
}

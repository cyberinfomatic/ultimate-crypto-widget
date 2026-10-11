<?php

namespace Cyberinfomatic\UltimateCryptoWidget;

use Cyberinfomatic\UltimateCryptoWidget\Helpers\Notification;
use Cyberinfomatic\UltimateCryptoWidget\Controllers\{Page, Settings, WidgetPostType};
class App
{

	// a property that holds callback to call before the plugin starts
	public static array $before_start = [];
	// a  property that holds callback to call after the plugin starts
	public static array $after_start = [];
	// a bool to show if plugin has started
	public static bool $started = false;
	public function __construct()
	{
	}

	public function start(): static {
		// call the before start callbacks
		foreach (self::$before_start as $callback) {
			call_user_func($callback);
		}
//		deactivate free version of the plugin with name 'ultimate-crypto-widget/ultimate-crypto-widget.php'
		self::enqueues();
		WidgetPostType::load();
		Page::load();
		Settings::load();
		Notification::show_notices();
		self::$started = true;
		// call the after start callbacks
		foreach (self::$after_start as $callback) {
			call_user_func($callback);
		}
		return $this;
	}

	public function enqueues(): static {
		// Nothing is loaded on the front end here: a widget brings its own script
		// and style when it renders, so pages without a widget load nothing of ours.
		add_action('admin_enqueue_scripts', [$this, 'admin_scripts']);
		add_action('admin_enqueue_scripts', [$this, 'admin_styles']);
		return $this;
	}

	/**
	 * Whether the current admin screen belongs to this plugin.
	 */
	private function on_plugin_screen(): bool {
		$screen = function_exists('get_current_screen') ? get_current_screen() : null;
		if (!$screen) {
			return false;
		}

		return 'ucwp_widget' === $screen->post_type || str_contains((string) $screen->id, 'ultimate-crypto-widget');
	}

//	admin scripts
	public function admin_scripts(): static {
		if ($this->on_plugin_screen()) {
			wp_enqueue_script( 'ucwp-chosen-js',  plugins_url( '/assets/libs/chosen/1.8.7/chosen.jquery.min.js', UCWP_PLUGIN_FILE ), array( 'jquery' ), '1.0', true );
		}
		return $this;
	}

//	admin styles
	public function admin_styles(): static {
		if ($this->on_plugin_screen()) {
			wp_enqueue_style( 'ucwp-admin-metabox', plugins_url( '/assets/styles/metabox.css', UCWP_PLUGIN_FILE ) );
			wp_enqueue_style( 'ucwp-chosen-css',  plugins_url( '/assets/libs/chosen/1.8.7/chosen.min.css', UCWP_PLUGIN_FILE ) );
		}
		return $this;
	}

	// a method to add a callback to the before start property
	public static function before_start(callable $callback): void {
		// if app has started, throw an exception
		if (self::$started) {
			throw new \Exception('App has already started');
		}
		self::$before_start[] = $callback;
	}

	// a method to add a callback to the after start property
	public static function after_start(callable $callback): void {
		self::$after_start[] = $callback;
	}


}
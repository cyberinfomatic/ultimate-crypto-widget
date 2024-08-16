<?php

namespace Cyberinfomatic\UltimateCryptoWidget;

use Cyberinfomatic\UltimateCryptoWidget\Helpers\Notification;
use Cyberinfomatic\UltimateCryptoWidget\Controllers\{Page, RouteHandler, Settings, WidgetPostType};
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
		self::enqueues();
		RouteHandler::load();
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
		add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
		add_action('wp_enqueue_scripts', [$this, 'enqueue_styles']);
		add_action('admin_enqueue_scripts', [$this, 'admin_scripts']);
		add_action('admin_enqueue_scripts', [$this, 'admin_styles']);
		return $this;
	}

	public function enqueue_scripts(): static {
		wp_enqueue_script( 'ucwp-marquee-graph', plugins_url( 'assets/scripts/coin-marquee-graph.js', UCWP_PLUGIN_BASENAME ), array( 'jquery' , 'ucwp-chart-js' ), '1.0', false );
		wp_enqueue_script( 'ucwp-crypto-price-table', plugins_url( 'assets/scripts/crypto-price-table.js', UCWP_PLUGIN_BASENAME ), array( 'jquery'  ), '1.0', true );
		wp_enqueue_script( 'ucwp-chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', array( 'jquery' ), '1.0', true );
		return $this;
	}

	public function enqueue_styles(): static {
//		font awesome
		wp_enqueue_style( 'ucwp-font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css' );
		return $this;
	}

//	admin scripts
	public function admin_scripts(): static {
//		wp_enqueue_script( 'ucwp-admin-js', plugins_url( '/src/javascripts/admin.js', UCW_PLUGIN_BASENAME ), array( 'jquery' ), '1.0', true );
		wp_enqueue_script( 'ucwp-chosen-js', 'https://cdnjs.cloudflare.com/ajax/libs/chosen/1.8.7/chosen.jquery.min.js', array( 'jquery' ), '1.0', true );
		return $this;
	}

//	admin styles
	public function admin_styles(): static {
		wp_enqueue_style( 'ucwp-admin-metabox', plugins_url( '/assets/styles/metabox.css', UCWP_PLUGIN_BASENAME ) );
		wp_enqueue_style( 'ucwp-chosen-css', 'https://cdnjs.cloudflare.com/ajax/libs/chosen/1.8.7/chosen.min.css' );
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
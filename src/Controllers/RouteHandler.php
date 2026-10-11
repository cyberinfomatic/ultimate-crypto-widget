<?php

namespace Cyberinfomatic\UltimateCryptoWidget\Controllers;

use Cyberinfomatic\UltimateCryptoWidget\Helpers\CoinGeckoHelper;
use WP_REST_Request;
use WP_REST_Response;

class RouteHandler {
	const namespace = 'ultimate-crypto-widget/v1';  // namespace for the rest api
	private static $instance = null;

	const ENDPOINTS = [
		'coins' => [
			'route' => '/coins',
			'callback' => [CoinGeckoHelper::class, 'get_coins_with_market_data' ],
			'method' => 'GET',
			'permission_callback' => '__return_true',
		],
		'coin-info' => [
			'route' => '/coin-info',
			'callback' => [self::class, 'get_coin_info'],
			'method' => 'GET',
			'permission_callback' => '__return_true',
		],
		'coin-chart-data' => [
			'route' => '/coin-chart-data',
			'callback' => [self::class, 'get_coin_chart_data'],
			'method' => 'GET',
			'permission_callback' => '__return_true',
		],
		'load-shortcode' => [
			'route' => '/load-shortcode',
			'callback' => [self::class, 'load_shortcode'],
			'method' => 'POST',
			// Admin preview only.
			'permission_callback' => [self::class, 'can_preview'],
		],
	];

	public static function instance(): static {
		if (self::$instance === null) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	static function load(): static {
		return self::instance();
	}

	static function get_endpoint(string $endpoint):? array {
		return self::ENDPOINTS[$endpoint] ?? null;
	}

	static function get_endpoint_path(string $endpoint, string $wp_parent_path = 'wp-json'):? string {
		$endpoint = self::get_endpoint($endpoint);
		return $endpoint ? $wp_parent_path . '/' . self::namespace . $endpoint['route'] : null;
	}

	public function __construct() {
		add_action('rest_api_init', [$this, 'register_routes']);
	}

	public function register_routes(): void {
		foreach (self::ENDPOINTS as $endpoint) {
			register_rest_route(self::namespace, $endpoint['route'], [
				'methods'             => $endpoint['method'],
				'callback'            => $endpoint['callback'],
				'permission_callback' => $endpoint['permission_callback'] ?? '__return_true',
			]);
		}
	}

	static function get_coin_info($request): WP_REST_Response {
		try {
			if(!$request->get_param('coin_id')){
				throw new \Exception('Coin ID is required: ('.$request->get_param('coin_id').')', 400);
			}
			$data = CoinGeckoHelper::get_coin_info($request->get_param('coin_id'));
			return new WP_REST_Response($data, 200);
		} catch (\Exception $e) {
			return new WP_REST_Response([
				'error' => $e->getMessage()
			], 400);
		}
	}

	static function get_coin_chart_data($request){
		try{
			if(!$request->get_param('coin_id')){
				throw new \Exception('Coin ID is required: ('.$request->get_param('coin_id').')', 400);
			}
			$setting = [
				'no_of_days' => $request->get_param('days') ?? 7,
				'default_currency' => $request->get_param('currency') ?? 'usd'
			];
			return CoinGeckoHelper::get_coin_chart_data($request->get_param('coin_id'), $setting);
		}catch (\Exception $e) {
			return [
				'error' => $e->getMessage()
			];
		}
	}


	static function can_preview(): bool {
		return current_user_can('edit_posts');
	}

	static function load_shortcode(WP_REST_Request $request): WP_REST_Response {
		$accepted_shortcodes = ['ucwp_widget'];
		try {
			$shortcode = $request->get_param('shortcode');
			$atts = $request->get_param('atts') ?? [];
			if(!$shortcode || !in_array($shortcode, $accepted_shortcodes)){
				throw new \Exception('Invalid shortcode', 400);
			}
			if(!is_array($atts)){
				$atts = json_decode($atts, true);
				if(!$atts){
					throw new \Exception('Invalid atts', 400);
				}
			}
			// Keep only the attributes the widget knows, as plain text. The widget is
			// rendered by calling its handler; a shortcode string built from the request
			// is never passed to do_shortcode().
			$allowed = ['id'];
			foreach (WidgetPostType::fields() as $field) {
				$allowed[] = str_replace('ucwp_widget_', '', (string) $field['id']);
			}
			$clean = [];
			foreach ($atts as $key => $value) {
				if (!is_string($key) || !in_array($key, $allowed, true)) {
					continue;
				}
				if (is_array($value)) {
					$value = implode(',', array_filter($value, 'is_scalar'));
				}
				if (!is_scalar($value)) {
					continue;
				}
				$clean[$key] = str_replace(['[', ']', '"', "'"], '', sanitize_text_field((string) $value));
			}

			$text = '[ucwp_widget';
			foreach ($clean as $key => $value) {
				$text .= ' ' . $key . '="' . $value . '"';
			}
			$text .= ']';

			return new WP_REST_Response([
				'content' => (string) WidgetPostType::get_instance()->shortcode($clean),
				'shortcode' => $text
			], 200);
		} catch (\Exception $e) {
			return new WP_REST_Response([
				'error' => $e->getMessage()
			], 400);
		}
	}





}
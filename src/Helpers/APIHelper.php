<?php
namespace Cyberinfomatic\UltimateCryptoWidget\Helpers;

use Cyberinfomatic\UltimateCryptoWidget\Controllers\Settings;

abstract class APIHelper {

	private static string $name = 'APIHelper';

	public static function prepare_request_url($url, $params = []): string {
		$url =  $url . '?' . http_build_query($params);
		return rtrim($url, '?#');
	}

	public static function cache($key, $value, int $expiration = null) {
		if ($expiration === null) {
			$expiration = Settings::get('cache_interval', 90); // caching for 90 minutes
		}
		if(set_transient($key, $value, intval($expiration) * MINUTE_IN_SECONDS)) {
			$previous_cache_key = get_transient( 'ucwp_cache_keys' ) ?: [];
			$previous_cache_key = array_unique( array_merge( $previous_cache_key, [ $key ] ) );
			set_transient( 'ucwp_cache_keys', $previous_cache_key, intval( $expiration ) * MINUTE_IN_SECONDS );
			return true;
		}
		return false;

	}

	public static function clear_cache(): void {
		$cache_keys = get_transient('ucwp_cache_keys');
		if ($cache_keys) {
			foreach ($cache_keys as $key) {
				delete_transient($key);
			}
		}
		delete_transient('ucwp_cache_keys');
	}

	// list caches
	public static function list_cache(): array {
		$cache_keys = get_transient('ucwp_cache_keys');
		$caches = [];
		if ($cache_keys) {
			foreach ($cache_keys as $key) {
				$caches[$key] = get_transient($key);
			}
		}
		return $caches;
	}

	public static function get_cache($key) {
		return get_transient($key);
	}

	/**
	 * @throws \Exception
	 */
	public static function request($method, $url, $params = [], $args = []) {
		$response = wp_remote_request($url, array_merge($args['headers'] ?? [], [
			'method' => strtoupper($method),
			'body' => (strtoupper($method) === 'GET') ? null : $params,
		]));

		if (is_wp_error($response)) {
			throw new \Exception(sprintf(__('Error %s: %s', 'ultimate-crypto-widget'), esc_html($response->get_error_code()), esc_html($response->get_error_message())));
		}

		$body = wp_remote_retrieve_body($response);
		$data = json_decode($body, true);

		if (json_last_error() !== JSON_ERROR_NONE) {
			throw new \Exception(sprintf(__('Error %s: %s', 'ultimate-crypto-widget'), json_last_error(), json_last_error_msg()));
		}
		$cache = $args['cache'] ?? true;
		try {
			if (is_callable($cache)) {
				$cache = $cache($data);
			}
		} catch (\Exception $e) {
			$cache = false;
			Debugger::console(sprintf(__('Error %s: %s', 'ultimate-crypto-widget'), esc_html($e->getCode()), esc_html($e->getMessage())), 'error');
		}

		if ($cache && strtoupper($method) === 'GET') {
			static::cache($url, $data, is_numeric($cache) ? $cache : Settings::get('cache_interval', 90));
		}


		static::update_api_call_count();
		return $data;
	}

	private static function update_api_call_count(): void {
		$name = static::getName();
		$cache_key = "ucwp_".$name."_api_calls";
		$api_calls = get_option($cache_key, 0);

		if ($api_calls === 0) {
			update_option("$cache_key-start_date", gmdate('Y-m-d'));
		}

		if (strtotime(get_option("$cache_key-start_date")) < strtotime('-30 days')) {
			update_option($cache_key, 0);
			update_option("$cache_key-start_date", gmdate('Y-m-d'));
			$api_calls = 0;
		}

		update_option($cache_key, $api_calls + 1);
	}

	/**
	 * Get CoinGecko API call count.
	 */
	static function get_api_call_count() {
		$name = static::getName();
		$cache_key = "ucwp_".$name."_api_calls";
		return get_option("$cache_key", 0);
	}

	static function getName(): string {
		return static::$name;
	}

	static function setName($name): void {
		self::$name = $name;
	}

	/**
	 * @throws \Exception
	 */
	static function make_request($name, $params = [], $substitutions = []) {
		$endpoint = static::ENDPOINTS[$name] ?? null;
		if (!$endpoint) {
			throw new \Exception(__('Invalid endpoint name', 'ultimate-crypto-widget'));
		}

		$route = $endpoint['route'] ?? null;
		if (!$route) {
			throw new \Exception(__('Invalid endpoint route', 'ultimate-crypto-widget'));
		}

		$route = static::substitute($route, $substitutions);
		$endpoint_params = $endpoint['params'] ?? [];
		$params = [...$endpoint_params, ...$params];

		// Filter params to match only keys defined in endpoint_params
		$params = array_filter($params, function ($key) use ($endpoint_params) {
			return array_key_exists($key, $endpoint_params);
		}, ARRAY_FILTER_USE_KEY);

		// Check if any params are callable and execute them
		foreach ($params as $key => $value) {
			if (is_callable($value)) {
				$params[$key] = $value();
			}
		}

		// Make the request with the right method, defaulting to GET
		$method = $endpoint['method'] ?? 'GET';
		$url = static::HOST . $route;
		if ($method === 'GET') {
			$url = static::prepare_request_url($url, $params);
			$cached_data = static::get_cache($url);
			if ($cached_data) {
				Debugger::console(sprintf(__('Using cached data for %s', 'ultimate-crypto-widget'), esc_html($url)), 'warn');
				return $cached_data;
			}
		}
		return static::request($method, $url, $params, $endpoint);
	}

	/**
	 * @throws \Exception
	 */
	static function substitute($route, $substitutions) {
		preg_match_all('/{{(.*?)}}/', $route, $matches);
		$placeholders = $matches[1] ?? [];
		foreach ($placeholders as $placeholder) {
			$value = $substitutions[$placeholder] ?? null;
			if (!$value) {
				throw new \Exception(sprintf(__('Missing substitution value for %s', 'ultimate-crypto-widget'), esc_html($placeholder)));
			}
			$route = str_replace('{{' . $placeholder . '}}', $value, $route);
		}
		return $route;
	}



}


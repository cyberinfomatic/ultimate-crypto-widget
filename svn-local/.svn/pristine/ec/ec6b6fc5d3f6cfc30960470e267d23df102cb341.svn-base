<?php

namespace Cyberinfomatic\UltimateCryptoWidget\Controllers;

use Cyberinfomatic\UltimateCryptoWidget\Helpers\Debugger;

final class View {

	const helpers = [
		'numbers' => 'numbers.php',
		'coin-gecko' => 'coin-gecko.php',
		'currency' => 'currency.php',
	];

	static function load($view, $data = [], $helper = []): ?string {
		$dir = UCWP_PLUGIN_DIR . 'src/Views/';
		$file = $dir . $view;
		if (file_exists($file . '.php') || file_exists($file . '/main.php')) {
			$file = file_exists($file . '.php') ? $file . '.php' : $file . '/main.php';
			ob_start();
			extract($data);
			self::require_helper($helper);
			include $file;
			Debugger::console(sprintf(__('Success loading View => %s', 'ultimate-crypto-widget'), esc_html($view)));
			return ob_get_clean();
		}
		Debugger::console(sprintf(__('No View Found for %s in %s', 'ultimate-crypto-widget'), esc_html($view), esc_html($file)));
		return null;
	}

	static function loadReact($component, $data = [], $main_view = 'react-loader'): ?string {
		$dir = UCWP_PLUGIN_DIR . 'assets/react-build/';
		$file = $component;
		$file_js = str_ends_with($file, '.js') ? $file : $file . '.js';
		$file_jsx = str_ends_with($file, '.jsx') ? $file : $file . '.jsx';
		$file = file_exists($dir . $file_js) ? $file_js : $file_jsx;

		if (!file_exists($dir . $file) && is_dir($dir . $component)) {
			Debugger::console(sprintf(__('Trying to load React Component => %s/index', 'ultimate-crypto-widget'), esc_html($component)));
			return self::loadReact($component . '/index', $data, $main_view);
		}

		if (file_exists($dir . $file)) {
			$react_id = 'ucwp-react-' . uniqid();
			$data['ucwp_render_component'] = $component;
			$data['react_id'] = $react_id;
			$ver = UCW_ENV === 'dev' ? wp_rand(1, 1000) : null;
			wp_enqueue_script('wp-element');
			wp_enqueue_script('ucwp-react-vendor', plugin_dir_url(UCWP_PLUGIN_FILE) . 'assets/react-build/vendors/index.js', ['wp-element', 'wp-i18n', 'jquery'], $ver, true);
			wp_enqueue_script($react_id, plugin_dir_url(UCWP_PLUGIN_FILE) . 'assets/react-build/' . esc_attr($file), ['ucwp-react-vendor'], $ver, true);
			wp_localize_script($react_id, 'ucwpReactData', $data);
			$component = str_ends_with($component, '/index') ? substr($component, 0, -6) : $component;
			wp_enqueue_style($component . '-style', plugin_dir_url(UCWP_PLUGIN_FILE) . 'assets/react-build/' . esc_attr($component) . '.css', [], $ver);
			return self::load($main_view, ['react_id' => $react_id, 'component' => esc_html($component)]);
		}

		Debugger::console(sprintf(__('No React Component Found for %s in %s', 'ultimate-crypto-widget'), esc_html($component), esc_url($dir . $file)));
		return null;
	}

	static function renderReact($component, $data = [], $main_view = 'react-loader'): void {
		echo self::loadReact($component, $data, $main_view);
	}

	static function renderReactComponent($component, $data = [], $main_view = 'react-loader'): void {
		echo self::importReactComponent($component, $data, $main_view);
	}

	static function importReactComponent($component, $data = [], $main_view = 'react-loader'): ?string {
		return self::loadReact("widgets/" . esc_attr($component), $data, $main_view);
	}

	static function render($view, $data = []): void {
		echo self::load($view, $data);
	}

	static function import_component($component, $data = []): ?string {
		return self::load('widgets/' . esc_attr($component), $data);
	}

	static function render_component($component, $data = []): void {
		echo self::import_component($component, $data);
	}

	static function require_helper($helpers = []): void {
		if (empty($helpers)) {
			foreach (self::helpers as $helper) {
				require_once UCWP_PLUGIN_DIR . 'src/Helpers/includes/' . esc_attr($helper);
			}
		} else {
			foreach ($helpers as $helper) {
				require_once UCWP_PLUGIN_DIR . 'src/Helpers/includes/' . esc_attr($helper);
			}
		}
	}
}

<?php

namespace Cyberinfomatic\UltimateCryptoWidget\Controllers;

use Cyberinfomatic\UltimateCryptoWidget\Helpers\{Currency, Debugger, OpenExchangeHelper};

final class WidgetType {
	/**
	 * @var Array<Array<string $type, string $display_name, string $view, $pro ,array $params>> $widgets_type
	 */
	private array $widgets_type = [];


	function add_widget_type(string $type, string $display_name, string $view, string $card, array $params, array $settings_param = [], bool $pro = false) {
		$this->widgets_type[] = [
			'type' => $type,
			'display_name' => $display_name, // TODO: add display name 'Bitcoin Price Ticker Widget
			'view' => $view,
			'card' => $card,
			// this can be either normal array of string or key pair array with the value being either the default value or a callable function
			'params' => $params,
			// this can be either normal array of string or key pair array with the value being either the default value or a callable function
			'settings_params' => $settings_param,
			'pro' => $pro,
		];
		return $this;
	}

	function get_widget(string $type) {
		foreach($this->widgets_type as $widget) {
			if($widget['type'] === $type) {
				return $widget;
			}
		}
		return null;
	}

	function load_widget(string $type, array $settings, array $data = []) {
		$widget = $this->get_widget($type);
		if(!$widget) {
			Debugger::console(sprintf(__('Widget not found: %s', 'ultimate-crypto-widget'), esc_html($type)));
			return;
		}
		if ($widget['pro']){
			Debugger::console(sprintf(__('You need to be a pro user to use this widget: %s', 'ultimate-crypto-widget'), esc_html($widget['display_name'])));
			return;
		}

		// add the card and pro to the params
		$passable_setting_param = [
			'card' => $widget['card'],
			'pro' => $widget['pro'],
//			'react' => ( $settings['react'] ?? true) != 'false'
		];

		// map through settings if any data is selerized then unserialize it use the wp is_serialized function
		$settings = array_map(function($setting) {
			return is_serialized($setting) ? unserialize($setting) : $setting;
		}, $settings);

//		return json_encode($passable_setting_param)." ".json_encode($settings);
		$data_param = [];
		// check and assign if all params are given
		foreach($widget['params'] as $param => $default) {

			$is_callable = is_callable($default);

			if(!isset($data[$param]) && !$is_callable && is_int($param)) {
				throw new \Exception(sprintf(__('Missing View Data param: %s', 'ultimate-crypto-widget'), esc_html($param)));
			}

			if (isset($data[$param])) {
				$data_param[$param] = $data[$param];
			} else if ($is_callable) {
				$data_param[$param] = $default($settings);
			} else {
				$data_param[$param] = $default;
			}

			// data param to be set below

		}

		// check and assign if all settings params are given
		foreach($widget['settings_params'] as $param) {
			$is_callable = is_callable($param);

			if(!isset($settings[$param]) && !$is_callable && is_int($param)) {
				throw new \Exception(sprintf(__('Missing View Data param: %s', 'ultimate-crypto-widget'), esc_html($param)));
			}

			if (isset($settings[$param])) {
				$passable_setting_param[$param] = $settings[$param];
			} else if ($is_callable) {
				$passable_setting_param[$param] = $param($settings);
			} else {
				$passable_setting_param[$param] = $param;
			}

			// possible setting param to be set below
		}
		$passable_setting_param['currency_symbol'] = $settings['currency_symbol'] ?? Currency::get_symbol($passable_setting_param['default_currency']);
		$passable_setting_param['usd_conversion_rate'] = (int) OpenExchangeHelper::convert( 'USD', $passable_setting_param['default_currency'],1);
//		// if usd conversion rate is 1 and default currency is not USD then set the conversion rate to 1
		if($passable_setting_param['usd_conversion_rate'] === 1 && strtoupper($passable_setting_param['default_currency']) !== 'USD') {
			$latest = OpenExchangeHelper::get_latest([strtoupper($passable_setting_param['default_currency'])]);
			$passable_setting_param['usd_conversion_rate'] = $latest['rates'][strtoupper($passable_setting_param['default_currency'])] ?? 1;
			$passable_setting_param['usd_conversion_rate'] = round($passable_setting_param['usd_conversion_rate'], 2);
		}
		$pass = [
			'settings' => $passable_setting_param,
			...$data_param
		];
//		return View::import_component($widget['view'], $pass);
		return ($passable_setting_param['react']  ?? true) ? View::importReactComponent($widget['view'], $pass) : View::import_component($widget['view'], $pass);
	}


	function get_widget_types(): array {
		return $this->widgets_type;
	}
}
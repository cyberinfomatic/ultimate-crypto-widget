<?php

namespace Cyberinfomatic\UltimateCryptoWidget\Widgets;

use Cyberinfomatic\UltimateCryptoWidget\Data\PriceDataException;
use Cyberinfomatic\UltimateCryptoWidget\Data\PriceProvider;

/**
 * Turns a widget type and its settings into HTML: a mount point plus the data the
 * widget's React component starts with.
 */
final class WidgetRenderer
{
	public function __construct(
		private readonly WidgetCatalog $catalog,
		private readonly PriceProvider $prices,
		private readonly ReactMount $mount
	) {
	}

	/**
	 * @param array<string, mixed> $settings Keyed by setting name; anything a widget does not use is ignored.
	 * @return string Empty when the type is unknown or not available on this site.
	 */
	public function render(string $type, array $settings): string
	{
		$definition = $this->catalog->get($type);
		if (null === $definition || !$definition->available) {
			return '';
		}

		$props = ['settings' => $this->componentSettings($definition, $settings)];
		foreach ($definition->data as $need) {
			$props[$need] = $this->data($need, $settings, (string) $props['settings']['default_currency']);
		}

		return $this->mount->mount($definition, $props);
	}

	/**
	 * What the component receives as `settings`.
	 *
	 * @param array<string, mixed> $settings
	 * @return array<string, mixed>
	 */
	private function componentSettings(WidgetDefinition $definition, array $settings): array
	{
		$out = [
			'card' => $definition->card,
			'pro'  => $definition->pro,
		];

		foreach ($definition->settings as $name) {
			$value = $settings[$name] ?? null;
			if (WidgetFields::isList($name)) {
				$value = WidgetFields::listToString($value);
			}
			$out[$name] = null !== $value ? $value : (WidgetFields::DEFAULTS[$name] ?? '');
		}

		$currency = (string) ($out['default_currency'] ?? '');
		if ('' === $currency) {
			$currency = WidgetFields::DEFAULTS['default_currency'];
		}
		$out['default_currency'] = $currency;
		$out['currency_symbol']  = $settings['currency_symbol'] ?? Currencies::symbolFor($currency);

		// Live prices arrive in US dollars; the component multiplies them by this.
		// Stays 1 when the rate is unknown, so a price is still shown.
		$out['usd_conversion_rate'] = 1;
		if ('USD' !== strtoupper($currency)) {
			$rate = $this->rates('usd', [$currency])[strtoupper($currency)] ?? 0;
			if ($rate > 0) {
				$out['usd_conversion_rate'] = round($rate, 6);
			}
		}

		return $out;
	}

	/**
	 * @param array<string, mixed> $settings
	 * @return array<int|string, mixed>
	 */
	private function data(string $need, array $settings, string $currency): array
	{
		$currencies = WidgetFields::listToArray($settings['currencies'] ?? '');

		switch ($need) {
			case WidgetDefinition::DATA_COINS:
				$coins = WidgetFields::listToArray($settings['coins'] ?? '');
				try {
					return $this->prices->markets($currency, [] !== $coins ? $coins : null);
				} catch (PriceDataException $e) {
					return []; // The widget shows its empty state.
				}

			case WidgetDefinition::DATA_CURRENCY_RATES:
				return $this->rates($currency, $currencies);

			case WidgetDefinition::DATA_CURRENCY_SYMBOLS:
				$symbols = [];
				foreach ($currencies as $code) {
					$symbols[$code] = Currencies::symbolFor($code);
				}

				return $symbols;
		}

		return [];
	}

	/**
	 * @param string[] $currencies
	 * @return array<string, float>
	 */
	private function rates(string $base, array $currencies): array
	{
		try {
			return $this->prices->fiatRates($base, $currencies);
		} catch (PriceDataException $e) {
			return [];
		}
	}
}

<?php

namespace Cyberinfomatic\UltimateCryptoWidget\Widgets;

/**
 * Every widget type the site knows about.
 *
 * The plugin registers its own types, then fires `ucwp_register_widgets` so an
 * add-on can register more, or replace a teaser with the real thing.
 */
final class WidgetCatalog
{
	/** @var array<string, WidgetDefinition> */
	private array $definitions = [];

	private bool $loaded = false;

	public function register(WidgetDefinition $definition): void
	{
		$this->definitions[$definition->type] = $definition;
	}

	public function get(string $type): ?WidgetDefinition
	{
		return $this->all()[$type] ?? null;
	}

	/**
	 * @return array<string, WidgetDefinition> Keyed by type, in registration order.
	 */
	public function all(): array
	{
		$this->load();

		return $this->definitions;
	}

	/**
	 * Filled on first use, so labels are translated after the text domain is ready.
	 */
	private function load(): void
	{
		if ($this->loaded) {
			return;
		}
		$this->loaded = true;

		foreach ($this->builtIn() as $definition) {
			$this->register($definition);
		}

		/**
		 * Register more widget types.
		 *
		 * @param WidgetCatalog $catalog
		 */
		do_action('ucwp_register_widgets', $this);
	}

	/**
	 * The order is the order of the "Widget Type" list in the admin.
	 *
	 * @return WidgetDefinition[]
	 */
	private function builtIn(): array
	{
		$rates   = WidgetDefinition::DATA_CURRENCY_RATES;
		$symbols = WidgetDefinition::DATA_CURRENCY_SYMBOLS;
		$coins   = WidgetDefinition::DATA_COINS;

		return [
			new WidgetDefinition('crypto-price-picker-1', __('Crypto Price Label', 'ultimate-crypto-widget'), 'crypto-price-picker', 'card-001', ['count', 'orientation', 'default_currency']),
			WidgetDefinition::teaser('crypto-price-picker-2', __('Crypto Price Label (2)', 'ultimate-crypto-widget'), 'crypto-price-picker-pro', 'card-002', ['count', 'orientation', 'default_currency']),
			WidgetDefinition::teaser('crypto-price-picker-3', __('Crypto Price Label (3)', 'ultimate-crypto-widget'), 'crypto-price-picker-pro', 'card-003', ['count', 'orientation', 'default_currency']),
			new WidgetDefinition('coin-marquee-1', __('Coin Marquee', 'ultimate-crypto-widget'), 'coin-marquee', 'card-001', ['count', 'card_width', 'parent_width', 'speed', 'default_currency']),
			WidgetDefinition::teaser('coin-marquee-2', __('Coin Marquee (2)', 'ultimate-crypto-widget'), 'coin-marquee-pro', 'card-002', ['count', 'card_width', 'parent_width', 'speed', 'default_currency']),
			WidgetDefinition::teaser('coin-marquee-3', __('Coin Marquee (3)', 'ultimate-crypto-widget'), 'coin-marquee-pro', 'card-003', ['count', 'card_width', 'parent_width', 'speed', 'no_of_days', 'default_currency', 'data_interval']),
			new WidgetDefinition('crypto-price-table-1', __('Crypto Price Table', 'ultimate-crypto-widget'), 'crypto-price-table', 'card-001', ['count', 'parent_width', 'default_currency', 'search_placeholder']),
			WidgetDefinition::teaser('crypto-price-table-2', __('Crypto Price Table (2)', 'ultimate-crypto-widget'), 'crypto-price-table-2', 'card-002', ['count', 'parent_width', 'default_currency', 'search_placeholder']),
			WidgetDefinition::teaser('crypto-date-change-table', __('Crypto Date Change Table', 'ultimate-crypto-widget'), 'crypto-date-change-table', '', ['count', 'parent_width', 'default_currency', 'search_placeholder']),
			WidgetDefinition::teaser('price-slider-widget-1', __('Price Slider Widget', 'ultimate-crypto-widget'), 'price-slider-widget', 'card-001', ['count', 'parent_width', 'default_currency', 'speed']),
			WidgetDefinition::teaser('price-slider-widget-2', __('Price Slider Widget (2)', 'ultimate-crypto-widget'), 'price-slider-widget', 'card-002', ['count', 'parent_width', 'default_currency', 'speed']),
			WidgetDefinition::teaser('historical-price-chart', __('Historical Price Chart', 'ultimate-crypto-widget'), 'historical-price-chart', 'card-001', ['coins', 'parent_width', 'default_currency', 'no_of_days', 'data_interval', 'dark_mode']),
			new WidgetDefinition('multi-currencies-tab', __('Multi Currencies Tab', 'ultimate-crypto-widget'), 'multi-currencies-tab', 'card-001', ['coins', 'currencies', 'default_currency', 'no_of_days', 'data_interval', 'dark_mode'], [$coins, $rates, $symbols]),
		];
	}
}

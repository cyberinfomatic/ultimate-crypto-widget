<?php

namespace Cyberinfomatic\UltimateCryptoWidget\Widgets;

/**
 * Every widget type the site knows about.
 *
 * The plugin registers its own types, then fires `ucwp_register_widgets` so an
 * add-on can register more, or replace a listed Pro type with the real thing.
 *
 * Which types are unlocked depends on the edition (Edition, edition.json): in
 * "all-free" every type is free and drawn by this plugin; in "tiered" the Pro
 * tier is listed but locked until the Pro add-on registers it.
 */
final class WidgetCatalog
{
	/** @var array<string, WidgetDefinition> */
	private array $definitions = [];

	private bool $loaded = false;

	private ?string $edition = null;

	/** A catalogue for a given edition, rather than the one this plugin was released as. For tests and add-ons. */
	public static function withEdition(string $edition): self
	{
		$catalog          = new self();
		$catalog->edition = $edition;

		return $catalog;
	}

	public function edition(): string
	{
		return $this->edition ?? Edition::current();
	}

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
	 * Every type, free and Pro tier. The order is the order of the "Widget Type" list in the admin.
	 * Type slugs are stored on customers' sites: never rename one.
	 *
	 * @return WidgetDefinition[]
	 */
	private function builtIn(): array
	{
		$coins    = [WidgetDefinition::DATA_COINS];
		$rates    = [WidgetDefinition::DATA_COINS, WidgetDefinition::DATA_CURRENCY_RATES, WidgetDefinition::DATA_CURRENCY_SYMBOLS];
		$global   = [WidgetDefinition::DATA_GLOBAL];
		$trending = [WidgetDefinition::DATA_TRENDING];

		$free = fn (string $type, string $label, string $view, string $card, array $settings, array $data = [WidgetDefinition::DATA_COINS]): WidgetDefinition
			=> new WidgetDefinition($type, $label, $view, $card, $settings, $data);
		$pro  = fn (string $type, string $label, string $view, string $card, array $settings, array $data = [WidgetDefinition::DATA_COINS]): WidgetDefinition
			=> $this->proTier($type, $label, $view, $card, $settings, $data);

		return [
			$free('crypto-price-picker-1', __('Crypto Price Label', 'ultimate-crypto-widget'), 'crypto-price-picker', 'card-001', ['count', 'orientation', 'default_currency']),
			$pro('crypto-price-picker-2', __('Crypto Price Label (2)', 'ultimate-crypto-widget'), 'crypto-price-picker-pro', 'card-002', ['count', 'orientation', 'default_currency', 'coins']),
			$pro('crypto-price-picker-3', __('Crypto Price Label (3)', 'ultimate-crypto-widget'), 'crypto-price-picker-pro', 'card-003', ['count', 'orientation', 'default_currency', 'coins']),
			$free('coin-marquee-1', __('Coin Marquee', 'ultimate-crypto-widget'), 'coin-marquee', 'card-001', ['count', 'card_width', 'parent_width', 'speed', 'default_currency']),
			$pro('coin-marquee-2', __('Coin Marquee (2)', 'ultimate-crypto-widget'), 'coin-marquee-pro', 'card-002', ['count', 'card_width', 'parent_width', 'speed', 'default_currency', 'coins']),
			$pro('coin-marquee-3', __('Coin Marquee (3)', 'ultimate-crypto-widget'), 'coin-marquee-pro', 'card-003', ['count', 'card_width', 'parent_width', 'speed', 'no_of_days', 'default_currency', 'data_interval', 'coins']),
			$free('crypto-price-table-1', __('Crypto Price Table', 'ultimate-crypto-widget'), 'crypto-price-table', 'card-001', ['count', 'parent_width', 'default_currency', 'search_placeholder']),
			$pro('crypto-price-table-2', __('Crypto Price Table (2)', 'ultimate-crypto-widget'), 'crypto-price-table-2', 'card-002', ['count', 'parent_width', 'default_currency', 'search_placeholder']),
			$pro('crypto-date-change-table', __('Crypto Date Change Table', 'ultimate-crypto-widget'), 'crypto-date-change-table', '', ['count', 'parent_width', 'default_currency', 'search_placeholder']),
			$pro('price-slider-widget-1', __('Price Slider Widget', 'ultimate-crypto-widget'), 'price-slider-widget', 'card-001', ['count', 'parent_width', 'default_currency', 'speed', 'coins']),
			$pro('price-slider-widget-2', __('Price Slider Widget (2)', 'ultimate-crypto-widget'), 'price-slider-widget', 'card-002', ['count', 'parent_width', 'default_currency', 'speed', 'coins']),
			$pro('historical-price-chart', __('Historical Price Chart', 'ultimate-crypto-widget'), 'historical-price-chart', 'card-001', ['coins', 'parent_width', 'default_currency', 'no_of_days', 'data_interval', 'dark_mode']),
			$free('multi-currencies-tab', __('Multi Currencies Tab', 'ultimate-crypto-widget'), 'multi-currencies-tab', 'card-001', ['coins', 'currencies', 'default_currency', 'no_of_days', 'data_interval', 'dark_mode'], $rates),
			$pro('price-block-widget', __('Price Block Widget', 'ultimate-crypto-widget'), 'price-block-widget', '', ['coins', 'count', 'default_currency']),
			$pro('price-block-widget-2', __('Price Block Widget (2)', 'ultimate-crypto-widget'), 'price-block-widget-2', '', ['coins', 'count', 'default_currency', 'parent_width', 'no_of_days', 'dark_mode']),
			$pro('crypto-info-card', __('Coin Info Card', 'ultimate-crypto-widget'), 'crypto-info-card', 'card-001', ['coins', 'default_currency', 'dark_mode']),
			$pro('accordion-widget', __('Accordion Widget', 'ultimate-crypto-widget'), 'accordion-widget', 'card-001', ['coins', 'default_currency', 'dark_mode']),
			$pro('compare-coins', __('Compare Coins', 'ultimate-crypto-widget'), 'compare-coins', '', ['coins', 'default_currency', 'no_of_days', 'parent_width', 'dark_mode'], $coins),
			$pro('crypto-converter', __('Crypto Converter', 'ultimate-crypto-widget'), 'crypto-converter', '', ['coins', 'currencies', 'default_currency', 'parent_width', 'dark_mode'], $rates),
			$pro('top-movers', __('Top Gainers and Losers', 'ultimate-crypto-widget'), 'top-movers', '', ['coins', 'count', 'default_currency', 'parent_width', 'dark_mode'], $coins),
			$pro('market-overview', __('Market Overview', 'ultimate-crypto-widget'), 'market-overview', '', ['default_currency', 'parent_width', 'dark_mode'], $global),
			$pro('crypto-heatmap', __('Crypto Heatmap', 'ultimate-crypto-widget'), 'crypto-heatmap', '', ['coins', 'count', 'default_currency', 'parent_width', 'dark_mode'], $coins),
			$pro('trending-coins', __('Trending Coins', 'ultimate-crypto-widget'), 'trending-coins', '', ['count', 'default_currency', 'parent_width', 'dark_mode'], $trending),
			$pro('holdings-calculator', __('Holdings Calculator', 'ultimate-crypto-widget'), 'holdings-calculator', '', ['coins', 'count', 'default_currency', 'parent_width', 'dark_mode'], $coins),
		];
	}

	/**
	 * A Pro-tier type: free and drawn by this plugin in the all-free edition; listed but locked in the
	 * tiered edition, until the Pro add-on registers its own definition of the same type.
	 *
	 * @param string[] $settings
	 * @param string[] $data
	 */
	private function proTier(string $type, string $label, string $view, string $card, array $settings, array $data): WidgetDefinition
	{
		return Edition::TIERED === $this->edition()
			? new WidgetDefinition($type, $label, $view, $card, $settings, $data, true, false)
			: new WidgetDefinition($type, $label, $view, $card, $settings, $data);
	}
}

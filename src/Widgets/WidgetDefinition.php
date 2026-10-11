<?php

namespace Cyberinfomatic\UltimateCryptoWidget\Widgets;

/**
 * One kind of widget: what it is called, which React component draws it, which
 * settings it reads and which data it needs.
 */
final class WidgetDefinition
{
	/** Data a widget can ask for; the renderer knows how to fetch each. */
	public const DATA_COINS            = 'coins';
	public const DATA_CURRENCY_RATES   = 'default_currencies_rate';
	public const DATA_CURRENCY_SYMBOLS = 'default_currencies_symbol';

	/**
	 * @param string   $type      Stored in the widget's "type" setting, e.g. "coin-marquee-1". Never change one.
	 * @param string   $label     Shown in the admin; already translated.
	 * @param string   $view      Folder under react/widgets that draws it.
	 * @param string   $card      Which card design of that view, e.g. "card-001".
	 * @param string[] $settings  Names of the settings passed to the component, in order.
	 * @param string[] $data      DATA_* constants.
	 * @param bool     $pro       Belongs to the Pro add-on.
	 * @param bool     $available Can be rendered now. A Pro widget is listed but unavailable until the add-on provides it.
	 */
	public function __construct(
		public readonly string $type,
		public readonly string $label,
		public readonly string $view,
		public readonly string $card,
		public readonly array $settings,
		public readonly array $data = [self::DATA_COINS],
		public readonly bool $pro = false,
		public readonly bool $available = true
	) {
	}

	/**
	 * A Pro widget the free plugin only names, so the admin can show what Pro adds.
	 *
	 * @param string[] $settings
	 */
	public static function teaser(string $type, string $label, string $view, string $card, array $settings): self
	{
		return new self($type, $label, $view, $card, $settings, [self::DATA_COINS], true, false);
	}
}

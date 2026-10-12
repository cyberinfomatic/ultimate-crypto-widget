<?php

namespace Cyberinfomatic\UltimateCryptoWidget\Widgets;

use Cyberinfomatic\UltimateCryptoWidget\Data\PriceDataException;
use Cyberinfomatic\UltimateCryptoWidget\Data\PriceProvider;
use Cyberinfomatic\UltimateCryptoWidget\Helpers\Currency;
use Cyberinfomatic\UltimateCryptoWidget\Lib\WPToolkit\Data\Field\Field;
use Cyberinfomatic\UltimateCryptoWidget\Lib\WPToolkit\Data\Field\FieldFactory;
use Cyberinfomatic\UltimateCryptoWidget\Lib\WPToolkit\Support\Validation\Rules;
use Cyberinfomatic\UltimateCryptoWidget\Widgets\Fields\ListFieldType;
use Cyberinfomatic\UltimateCryptoWidget\Widgets\Fields\WidgetTypeFieldType;

/**
 * A widget's settings as form fields: the one description of what each setting
 * is, what it may hold and which widget types use it. The widget editor is drawn
 * from it and saves through it.
 *
 * The names and the stored shapes are WidgetFields'; nothing here changes them.
 */
final class WidgetSchema
{
	/** How many coins a widget may show unless an add-on offers more. */
	private const COUNTS = [5, 10];

	public function __construct(
		private readonly WidgetCatalog $catalog,
		private readonly PriceProvider $prices
	) {
	}

	/**
	 * Call on "init" or later: labels are translated and add-ons have registered their types.
	 *
	 * @return list<Field> In the order of WidgetFields::NAMES.
	 */
	public function fields(): array
	{
		$f      = new FieldFactory();
		$fields = [
			'type'               => $this->typeField($f),
			'count'              => $f->select('count', $this->countChoices())
				->label(__('Number of coins', 'ultimate-crypto-widget')),
			'orientation'        => $f->select('orientation', [
				'horizontal' => __('Side by side', 'ultimate-crypto-widget'),
				'vertical'   => __('Stacked', 'ultimate-crypto-widget'),
			])->label(__('Layout', 'ultimate-crypto-widget')),
			'speed'              => $f->number('speed')
				->label(__('Speed', 'ultimate-crypto-widget'))
				->description(__('Higher is faster. 100 to 5000.', 'ultimate-crypto-widget'))
				->rules('min:100|max:5000')
				->attributes(['min' => 100, 'max' => 5000, 'step' => 100]),
			'card_width'         => $f->number('card_width')
				->label(__('Card width (px)', 'ultimate-crypto-widget'))
				->rules('min:100|max:500')
				->attributes(['min' => 100, 'max' => 500]),
			'parent_width'       => $f->text('parent_width')
				->label(__('Widget width', 'ultimate-crypto-widget'))
				->description(__('In pixels (400) or as a share of the space it sits in (100%).', 'ultimate-crypto-widget'))
				->rules([Rules::regex('/^\d{1,4}(?:px|%)?$/')]),
			'default_currency'   => $f->select('default_currency', $this->currencyChoices())
				->label(__('Currency', 'ultimate-crypto-widget')),
			'no_of_days'         => $f->number('no_of_days')
				->label(__('Days of price history', 'ultimate-crypto-widget'))
				->rules('integer|min:1|max:365')
				->attributes(['min' => 1, 'max' => 365, 'step' => 1]),
			'data_interval'      => $f->select('data_interval', ['daily' => __('Daily', 'ultimate-crypto-widget')])
				->label(__('Price history interval', 'ultimate-crypto-widget')),
			'search_placeholder' => $f->text('search_placeholder')
				->label(__('Search box hint', 'ultimate-crypto-widget'))
				->rules('max:60'),
			'coins'              => $f->of('coins', ListFieldType::NAME)
				->label(__('Coins', 'ultimate-crypto-widget'))
				->description(__('Leave empty to show the top coins by market value.', 'ultimate-crypto-widget'))
				->with('choices', fn (): array => $this->coinChoices()),
			'currencies'         => $f->of('currencies', ListFieldType::NAME)
				->label(__('Other currencies', 'ultimate-crypto-widget'))
				->description(__('Up to three, offered as tabs beside the widget\'s own currency.', 'ultimate-crypto-widget'))
				->with('choices', $this->currencyChoices())
				->with('max', 3),
			'dark_mode'          => $f->select('dark_mode', [
				'false' => __('Light', 'ultimate-crypto-widget'),
				'true'  => __('Dark', 'ultimate-crypto-widget'),
			])->label(__('Colours', 'ultimate-crypto-widget')),
		];

		$usedBy = $this->usedBy();
		$out    = [];
		foreach (WidgetFields::NAMES as $name) {
			$field = $fields[$name];
			if (array_key_exists($name, WidgetFields::DEFAULTS) && '' !== WidgetFields::DEFAULTS[$name]) {
				$field = $field->default(WidgetFields::DEFAULTS[$name]);
			}
			if ('type' !== $name) {
				// Which widget types have this setting; the editor shows a field only for those.
				$field = $field->attributes(['data-ucw-for' => implode(' ', $usedBy[$name] ?? [])] + $field->htmlAttributes());
			}
			$out[] = $field;
		}

		return $out;
	}

	private function typeField(FieldFactory $f): Field
	{
		$labels = [];
		$locked = [];
		foreach ($this->catalog->all() as $type => $definition) {
			$labels[$type] = $definition->label;
			if (!$definition->available) {
				$locked[] = $type;
			}
		}

		return $f->of('type', WidgetTypeFieldType::NAME)
			->label(__('Widget type', 'ultimate-crypto-widget'))
			->options($labels)
			->with('locked', $locked)
			->required();
	}

	/**
	 * @return array<string, string[]> Setting name => the widget types that use it.
	 */
	private function usedBy(): array
	{
		$usedBy = [];
		foreach ($this->catalog->all() as $type => $definition) {
			foreach ($definition->settings as $name) {
				$usedBy[$name][] = $type;
			}
		}

		return $usedBy;
	}

	/**
	 * @return array<string, string>
	 */
	private function countChoices(): array
	{
		/**
		 * How many coins a widget may be set to show.
		 *
		 * @param int[] $counts
		 */
		$counts = (array) apply_filters('ucwp_widget_count_choices', self::COUNTS);

		$choices = [];
		foreach ($counts as $count) {
			$count = (int) $count;
			if ($count > 0 && $count <= 250) {
				/* translators: %d: number of coins. */
				$choices[(string) $count] = sprintf(__('Top %d', 'ultimate-crypto-widget'), $count);
			}
		}
		ksort($choices, SORT_NUMERIC);

		return $choices;
	}

	/**
	 * @return array<string, string> Code => "USD".
	 */
	private function currencyChoices(): array
	{
		$choices = [];
		foreach (Currency::CURRENCY_PAIR as $code => $label) {
			$choices[(string) $code] = strtoupper((string) $code);
		}

		return $choices;
	}

	/**
	 * The coins to choose from: the top ones by market value. Asked for only when
	 * the form is drawn, and empty if prices cannot be fetched right now.
	 *
	 * @return array<string, string> Coin id => "Bitcoin (BTC)".
	 */
	private function coinChoices(): array
	{
		try {
			$coins = $this->prices->markets();
		} catch (PriceDataException) {
			return [];
		}

		$choices = [];
		foreach ($coins as $coin) {
			if (is_array($coin) && isset($coin['id'], $coin['name'], $coin['symbol'])) {
				$choices[(string) $coin['id']] = sprintf('%s (%s)', $coin['name'], strtoupper((string) $coin['symbol']));
			}
		}

		return $choices;
	}
}

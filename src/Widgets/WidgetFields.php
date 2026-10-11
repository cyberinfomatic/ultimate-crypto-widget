<?php

namespace Cyberinfomatic\UltimateCryptoWidget\Widgets;

/**
 * The settings a widget can have and how each is stored.
 *
 * A setting named "count" lives in post meta under "ucwp_widget_count" and is the
 * shortcode attribute count="…". These names are in users' databases and content:
 * never rename one.
 */
final class WidgetFields
{
	public const META_PREFIX = 'ucwp_widget_';

	/** Every setting, in the order of the admin form. */
	public const NAMES = [
		'type',
		'count',
		'orientation',
		'speed',
		'card_width',
		'parent_width',
		'default_currency',
		'no_of_days',
		'data_interval',
		'search_placeholder',
		'coins',
		'currencies',
		'dark_mode',
	];

	/** Settings that hold several values, stored and written as "a,b,c". */
	public const LISTS = ['coins', 'currencies'];

	/** Used when a widget has no value for a setting. */
	public const DEFAULTS = [
		'count'              => '10',
		'orientation'        => 'horizontal',
		'speed'              => '3000',
		'card_width'         => '157',
		'parent_width'       => '100%',
		'default_currency'   => 'usd',
		'no_of_days'         => '7',
		'data_interval'      => 'daily',
		'search_placeholder' => '',
		'coins'              => '',
		'currencies'         => '',
		'dark_mode'          => 'false',
	];

	public static function metaKey(string $name): string
	{
		return self::META_PREFIX . $name;
	}

	public static function isList(string $name): bool
	{
		return in_array($name, self::LISTS, true);
	}

	/**
	 * A list setting as it is stored: "bitcoin,ethereum".
	 *
	 * Accepts what a form posts (an array), what a shortcode gives (a string) and
	 * what an older version may have stored (a serialized array).
	 *
	 * @param mixed $value
	 */
	public static function listToString($value): string
	{
		return implode(',', self::listToArray($value));
	}

	/**
	 * @param mixed $value
	 * @return string[]
	 */
	public static function listToArray($value): array
	{
		if (is_string($value) && is_serialized($value)) {
			// Never build objects from stored or posted text.
			$value = unserialize(trim($value), ['allowed_classes' => false]); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_unserialize
		}
		if (is_string($value)) {
			$value = explode(',', $value);
		}
		if (!is_array($value)) {
			return [];
		}

		$items = [];
		foreach ($value as $item) {
			if (!is_scalar($item)) {
				continue;
			}
			$item = sanitize_text_field((string) $item);
			if ('' !== $item) {
				$items[] = $item;
			}
		}

		return array_values(array_unique($items));
	}
}

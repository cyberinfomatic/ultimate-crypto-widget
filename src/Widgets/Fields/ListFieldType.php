<?php

namespace Cyberinfomatic\UltimateCryptoWidget\Widgets\Fields;

use Closure;
use Cyberinfomatic\UltimateCryptoWidget\Lib\WPToolkit\Data\Field\Field;
use Cyberinfomatic\UltimateCryptoWidget\Lib\WPToolkit\Data\Field\FieldType;
use Cyberinfomatic\UltimateCryptoWidget\Widgets\WidgetFields;

/**
 * Several choices kept as one piece of text: "bitcoin,ethereum".
 *
 * That is how the plugin has stored a widget's coins and currencies since 0.x, so
 * it is one stored value, not the toolkit's "multiple" field (which would rewrite
 * every saved widget as a serialized array).
 *
 * Field settings: "choices" (value => label, or a closure returning that, called
 * only when the form is drawn) and "max" (how many may be chosen; 0 for no limit).
 */
final class ListFieldType implements FieldType
{
	public const NAME = 'ucw_list';

	public function sanitize(mixed $value, Field $field): mixed
	{
		$items = array_values(array_filter(
			WidgetFields::listToArray($value),
			// Coin ids and currency codes: nothing else may be stored or reach a URL.
			static fn (string $item): bool => 1 === preg_match('/^[a-z0-9_-]{1,100}$/', $item)
		));

		$max = (int) $field->setting('max', 0);
		if ($max > 0) {
			$items = array_slice($items, 0, $max);
		}

		return implode(',', $items);
	}

	public function read(mixed $stored, Field $field): mixed
	{
		return WidgetFields::listToString($stored);
	}

	public function rules(Field $field): array
	{
		return [];
	}

	public function render(Field $field, string $name, string $id, mixed $value): string
	{
		$chosen  = WidgetFields::listToArray($value);
		$choices = $field->setting('choices', []);
		if ($choices instanceof Closure) {
			$choices = $choices();
		}
		$choices = is_array($choices) ? $choices : [];

		// A saved choice that is no longer offered (a coin that left the top list) stays selectable.
		foreach ($chosen as $item) {
			$choices += [$item => $item];
		}

		$options = '';
		foreach ($choices as $option => $label) {
			$options .= sprintf(
				'<option value="%s"%s>%s</option>',
				esc_attr((string) $option),
				selected(in_array((string) $option, $chosen, true), true, false),
				esc_html((string) $label)
			);
		}

		$attributes = '';
		foreach ($field->htmlAttributes() + ['data-ucw-max' => (string) (int) $field->setting('max', 0)] as $attribute => $attributeValue) {
			$attributes .= sprintf(' %s="%s"', esc_attr((string) $attribute), esc_attr((string) $attributeValue));
		}

		// The empty input comes first, so choosing nothing still posts the field and clears it.
		return sprintf(
			'<input type="hidden" name="%1$s" value="" /><select id="%2$s" name="%1$s[]" multiple size="6" class="ucw-list-field"%3$s>%4$s</select>',
			esc_attr($name),
			esc_attr($id),
			$attributes,
			$options
		);
	}
}

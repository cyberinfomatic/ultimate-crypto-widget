<?php

namespace Cyberinfomatic\UltimateCryptoWidget\Widgets\Fields;

use Cyberinfomatic\UltimateCryptoWidget\Lib\WPToolkit\Data\Field\Field;
use Cyberinfomatic\UltimateCryptoWidget\Lib\WPToolkit\Data\Field\FieldType;
use Cyberinfomatic\UltimateCryptoWidget\Lib\WPToolkit\Support\Validation\Rules;

/**
 * The "which widget is this" choice.
 *
 * Every type in the catalogue is listed, so a site owner sees what an add-on
 * would give them, but only the ones this site can use can be chosen or saved.
 * The field's "locked" setting names the others.
 */
final class WidgetTypeFieldType implements FieldType
{
	public const NAME = 'ucw_widget_type';

	/** Plain text. Whether the type may be used is for rules() to say. */
	public function sanitize(mixed $value, Field $field): mixed
	{
		return sanitize_text_field(is_scalar($value) ? (string) $value : '');
	}

	/** The type's name is stored as it is, so it is read back unchanged. */
	public function read(mixed $stored, Field $field): mixed
	{
		return $stored;
	}

	/** Only a type this site can use; a locked type is refused when saving. */
	public function rules(Field $field): array
	{
		return [Rules::in($this->usable($field))];
	}

	/** A select listing every type, with the locked ones disabled and marked "(Pro)". */
	public function render(Field $field, string $name, string $id, mixed $value): string
	{
		$locked  = $this->locked($field);
		$options = sprintf('<option value="">%s</option>', esc_html__('Choose a widget type…', 'ultimate-crypto-widget'));

		foreach ($field->choices() as $type => $label) {
			$isLocked = in_array((string) $type, $locked, true);
			$options .= sprintf(
				'<option value="%s"%s%s>%s</option>',
				esc_attr((string) $type),
				selected((string) $value, (string) $type, false),
				disabled($isLocked, true, false),
				/* translators: %s: widget type name. Shown for a type that needs the Pro add-on. */
				esc_html($isLocked ? sprintf(__('%s (Pro)', 'ultimate-crypto-widget'), $label) : (string) $label)
			);
		}

		return sprintf('<select id="%s" name="%s" class="ucw-type-field" required>%s</select>', esc_attr($id), esc_attr($name), $options);
	}

	/**
	 * @return string[]
	 */
	private function locked(Field $field): array
	{
		$locked = $field->setting('locked', []);

		return is_array($locked) ? array_map('strval', $locked) : [];
	}

	/**
	 * @return string[]
	 */
	private function usable(Field $field): array
	{
		return array_values(array_diff(array_map('strval', array_keys($field->choices())), $this->locked($field)));
	}
}

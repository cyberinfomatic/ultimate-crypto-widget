<?php

namespace Cyberinfomatic\UltimateCryptoWidget\Http;

use Cyberinfomatic\UltimateCryptoWidget\Controllers\WidgetPostType;
use Cyberinfomatic\UltimateCryptoWidget\Lib\WPToolkit\Http\HttpError;
use Cyberinfomatic\UltimateCryptoWidget\Lib\WPToolkit\Http\Request;

/**
 * Renders a widget preview for the admin "Quick widget" page.
 *
 * The widget is rendered by calling the shortcode handler with a checked list of
 * attributes. A shortcode string is never assembled from the request and never
 * passed to do_shortcode(), so the request cannot run any other shortcode.
 */
final class QuickWidgetController
{
	/**
	 * @return array{content: string, shortcode: string}
	 */
	public function render(Request $request): array
	{
		$attributes = $this->attributes($request->input('atts'));

		$content = WidgetPostType::get_instance()->shortcode($attributes);

		return [
			'content'   => (string) $content,
			'shortcode' => $this->describe($attributes),
		];
	}

	/**
	 * Keeps only the attributes [ucwp_widget] knows, with plain text values.
	 *
	 * @param mixed $input An array, or the same array as a JSON string.
	 * @return array<string, string>
	 */
	private function attributes($input): array
	{
		if (is_string($input)) {
			$input = json_decode($input, true);
		}
		if (!is_array($input)) {
			throw HttpError::badRequest(__('The widget settings are not valid.', 'ultimate-crypto-widget'));
		}

		$allowed    = $this->allowed();
		$attributes = [];

		foreach ($input as $name => $value) {
			if (!is_string($name) || !in_array($name, $allowed, true)) {
				continue;
			}
			if (is_array($value)) {
				$value = implode(',', array_filter($value, 'is_scalar'));
			}
			if (!is_scalar($value)) {
				continue;
			}
			// Brackets and quotes have no place in a widget setting.
			$attributes[$name] = str_replace(['[', ']', '"', "'"], '', sanitize_text_field((string) $value));
		}

		return $attributes;
	}

	/**
	 * @return string[] "id" and one name per widget field ("type", "count", ...).
	 */
	private function allowed(): array
	{
		$names = ['id'];
		foreach (WidgetPostType::fields() as $field) {
			$names[] = str_replace('ucwp_widget_', '', (string) $field['id']);
		}

		return $names;
	}

	/**
	 * The shortcode as text, for display only.
	 *
	 * @param array<string, string> $attributes
	 */
	private function describe(array $attributes): string
	{
		$text = '[ucwp_widget';
		foreach ($attributes as $name => $value) {
			$text .= ' ' . $name . '="' . $value . '"';
		}

		return $text . ']';
	}
}

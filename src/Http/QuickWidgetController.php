<?php

namespace Cyberinfomatic\UltimateCryptoWidget\Http;

use Cyberinfomatic\UltimateCryptoWidget\Lib\WPToolkit\Frontend\Shortcodes;
use Cyberinfomatic\UltimateCryptoWidget\Lib\WPToolkit\Http\HttpError;
use Cyberinfomatic\UltimateCryptoWidget\Lib\WPToolkit\Http\Request;
use Cyberinfomatic\UltimateCryptoWidget\Widgets\WidgetFields;
use Cyberinfomatic\UltimateCryptoWidget\Widgets\WidgetShortcode;

/**
 * Renders a widget preview for the admin "Quick widget" page.
 *
 * The widget is rendered through Shortcodes::render() with a checked list of
 * attributes. A shortcode string is never assembled from the request and never
 * passed to do_shortcode(), so the request cannot run any other shortcode.
 */
final class QuickWidgetController
{
	public function __construct(private readonly Shortcodes $shortcodes)
	{
	}

	/**
	 * @return array{content: string, shortcode: string}
	 */
	public function render(Request $request): array
	{
		$attributes = $this->attributes($request->input('atts'));

		return [
			'content'   => $this->shortcodes->render('widget', $attributes),
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

		$allowed    = array_merge(['id'], WidgetFields::NAMES);
		$attributes = [];

		foreach ($input as $name => $value) {
			if (!is_string($name) || !in_array($name, $allowed, true)) {
				continue;
			}
			if (is_array($value)) {
				$value = WidgetFields::listToString($value);
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
	 * The shortcode as text, for display only.
	 *
	 * @param array<string, string> $attributes
	 */
	private function describe(array $attributes): string
	{
		$text = '[' . WidgetShortcode::TAG;
		foreach ($attributes as $name => $value) {
			$text .= ' ' . $name . '="' . $value . '"';
		}

		return $text . ']';
	}
}

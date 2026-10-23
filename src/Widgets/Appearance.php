<?php

namespace Cyberinfomatic\UltimateCryptoWidget\Widgets;

use Cyberinfomatic\UltimateCryptoWidget\Lib\WPToolkit\Admin\Settings\Settings;

/**
 * How the site owner wants every widget to look: light or dark, and the accent
 * colour. It only ever sets values of the theme's CSS variables on a widget's own
 * element; the stylesheet and the components do not change.
 */
final class Appearance
{
	public const THEMES = ['light', 'dark', 'auto'];

	public function __construct(private readonly Settings $settings)
	{
	}

	/**
	 * Light, dark, or "auto" (follow the visitor's device). A widget whose own
	 * "dark mode" setting is on is dark whatever the site's choice; that setting
	 * has been stored as the text "true" or "false" since 0.x.
	 *
	 * @param mixed $widgetSettings The widget's settings, if it has any.
	 */
	public function theme($widgetSettings = []): string
	{
		$dark = is_array($widgetSettings) ? ($widgetSettings['dark_mode'] ?? false) : false;
		if (true === $dark || in_array($dark, ['true', '1', 1, 'on'], true)) {
			return 'dark';
		}

		$chosen = (string) $this->settings->get('theme');

		return in_array($chosen, self::THEMES, true) ? $chosen : 'light';
	}

	/**
	 * The theme variables to set on a widget, by name.
	 *
	 * @return array<string, string>
	 */
	public function variables(): array
	{
		$variables = [];

		$accent = Color::hexToOklch((string) $this->settings->get('accent_color'));
		if (null !== $accent) {
			$variables['--ucw-brand-hue'] = (string) $accent['hue'];
			// Kept within what stays readable on both light and dark surfaces.
			$variables['--ucw-brand-chroma'] = (string) min(0.26, max(0.02, $accent['chroma']));
		}

		/**
		 * Theme variables set on every widget, e.g. ['--ucw-radius' => '4px'].
		 *
		 * Names must start with "--ucw-". See react/ds/tokens for what exists.
		 *
		 * @param array<string, string> $variables
		 */
		$filtered = apply_filters('ucwp_widget_style', $variables);

		$safe = [];
		foreach (is_array($filtered) ? $filtered : $variables as $name => $value) {
			if (self::isSafe($name, $value)) {
				$safe[(string) $name] = trim((string) $value);
			}
		}

		return $safe;
	}

	/**
	 * The same variables as the text of a style attribute; '' when there are none.
	 */
	public function style(): string
	{
		$declarations = [];
		foreach ($this->variables() as $name => $value) {
			$declarations[] = $name . ':' . $value;
		}

		return implode(';', $declarations);
	}

	/**
	 * A variable of ours with a plain value: nothing that could end the declaration,
	 * open another rule or fetch a file.
	 *
	 * @param mixed $name
	 * @param mixed $value
	 */
	private static function isSafe($name, $value): bool
	{
		if (!is_string($name) || !is_scalar($value) || 1 !== preg_match('/^--ucw-[a-z0-9-]+$/', $name)) {
			return false;
		}
		$value = trim((string) $value);

		return '' !== $value
			&& strlen($value) <= 200
			&& 1 === preg_match('/^[a-zA-Z0-9#%.,()\s\/"\'_+*-]+$/', $value)
			&& false === stripos($value, 'url(')
			&& false === stripos($value, 'expression(');
	}
}

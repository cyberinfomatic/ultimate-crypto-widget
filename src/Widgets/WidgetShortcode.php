<?php

namespace Cyberinfomatic\UltimateCryptoWidget\Widgets;

/**
 * [ucwp_widget id="12"] shows a saved widget; [ucwp_widget type="coin-marquee-1" …]
 * shows one described by its attributes. Attributes on a saved widget override
 * what was saved.
 */
final class WidgetShortcode
{
	public const TAG = 'ucwp_widget';

	/** Saved widgets are read from the repository; both kinds are drawn by the renderer. */
	public function __construct(
		private readonly WidgetRepository $widgets,
		private readonly WidgetRenderer $renderer
	) {
	}

	/**
	 * The attributes the shortcode accepts, in the shape Shortcodes::attributes() expects.
	 *
	 * @return array<string, array{type: string, default: string}>
	 */
	public static function attributes(): array
	{
		$attributes = [
			'id'    => ['type' => 'text', 'default' => ''],
			'react' => ['type' => 'text', 'default' => ''], // Accepted for old content; no longer used.
		];
		foreach (WidgetFields::NAMES as $name) {
			$attributes[$name] = ['type' => 'text', 'default' => ''];
		}

		return $attributes;
	}

	/**
	 * @param array<string, mixed> $attributes
	 */
	public function __invoke(array $attributes, string $content = ''): string
	{
		$given = [];
		foreach ($attributes as $name => $value) {
			if (is_scalar($value) && '' !== (string) $value && in_array($name, WidgetFields::NAMES, true)) {
				$given[$name] = (string) $value;
			}
		}
		$id = trim((string) ($attributes['id'] ?? ''));

		if ('' === $id || '0' === $id) {
			return isset($given['type']) ? $this->renderer->render($given['type'], $given) : $this->missing($id);
		}

		$saved = $this->widgets->settings((int) $id);
		if (null === $saved) {
			return $this->missing($id);
		}

		$type = $saved['type'] ?? '';
		if ('' === $type) {
			// Saved without a type, e.g. a Pro type was picked on a site without Pro.
			return current_user_can('edit_posts')
				? esc_html__('Choose a widget type and save to see the widget.', 'ultimate-crypto-widget')
				: '';
		}

		// The saved type always wins: an attribute cannot turn one widget into another.
		return $this->renderer->render($type, array_merge($saved, $given, ['type' => $type]));
	}

	/** Shown when there is no widget to draw: a hint in wp-admin, a short notice naming the id elsewhere. */
	private function missing(string $id): string
	{
		if (is_admin() && !is_preview()) {
			return esc_html__('Save the post to preview the widget', 'ultimate-crypto-widget');
		}

		return esc_html(sprintf('Widget with id (%s) does not exist', $id));
	}
}

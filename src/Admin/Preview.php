<?php

namespace Cyberinfomatic\UltimateCryptoWidget\Admin;

use Cyberinfomatic\UltimateCryptoWidget\Lib\WPToolkit\Frontend\Shortcodes;
use Cyberinfomatic\UltimateCryptoWidget\Widgets\WidgetFields;

/**
 * Shows a widget on its own, in a frame, while it is being set up.
 *
 * The frame is a separate small page, so the widget looks as it will on the site
 * (the admin's styles cannot reach it) and its scripts load the normal way.
 * Only people who can edit content may open it, and only with a fresh nonce.
 */
final class Preview
{
	public const ACTION = 'ucwp_preview';

	/** Tells the page holding the frame how tall the widget is, so the frame can fit it. */
	private const HEIGHT_SCRIPT = '<script>(function(){var tell=function(){parent.postMessage({ucwPreviewHeight:document.documentElement.scrollHeight},location.origin)};if(window.ResizeObserver){new ResizeObserver(tell).observe(document.body)}tell()})();</script>';

	public function __construct(private readonly Shortcodes $shortcodes)
	{
	}

	/**
	 * The address of a preview. With no settings it shows the saved widget.
	 *
	 * @param array<string, string> $settings
	 */
	public function url(int $widgetId = 0, array $settings = []): string
	{
		return add_query_arg(
			array_filter([
				'action'   => self::ACTION,
				'id'       => $widgetId > 0 ? $widgetId : null,
				// add_query_arg() does not encode values: "100%" or "a,b" must be encoded here.
				's'        => [] === $settings ? null : array_map('rawurlencode', array_map('strval', $settings)),
				'_wpnonce' => wp_create_nonce(self::ACTION),
			], static fn ($value): bool => null !== $value),
			admin_url('admin-post.php')
		);
	}

	/**
	 * The frame for the edit screen. `data-ucw-preview` holds the address without
	 * settings; the editor's script adds the form's current values to it.
	 */
	public function frame(int $widgetId = 0): string
	{
		return sprintf(
			'<iframe class="ucw-preview" title="%s" src="%s" data-ucw-preview="%s" loading="lazy"></iframe>',
			esc_attr__('Widget preview', 'ultimate-crypto-widget'),
			esc_url($this->url($widgetId)),
			esc_url($this->url())
		);
	}

	/**
	 * Hooked to admin_post_ucwp_preview. Prints the page and stops.
	 */
	public function handle(): void
	{
		if (!current_user_can('edit_posts')) {
			wp_die(esc_html__('You are not allowed to preview widgets.', 'ultimate-crypto-widget'), '', ['response' => 403]);
		}
		check_admin_referer(self::ACTION);

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- document() escapes.
		echo $this->document($this->requested());
		exit;
	}

	/**
	 * The shortcode attributes the request asks for: an id, or settings by name.
	 * Anything that is not one of the widget's own settings is dropped.
	 *
	 * @return array<string, string>
	 */
	public function requested(): array
	{
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- verified in handle().
		$attributes = [];
		$given      = isset($_GET['s']) && is_array($_GET['s']) ? map_deep(wp_unslash($_GET['s']), 'sanitize_text_field') : [];
		foreach (WidgetFields::NAMES as $name) {
			if (!isset($given[$name])) {
				continue;
			}
			$value = WidgetFields::isList($name) ? WidgetFields::listToString($given[$name]) : (is_scalar($given[$name]) ? sanitize_text_field((string) $given[$name]) : '');
			if ('' !== $value) {
				$attributes[$name] = $value;
			}
		}
		if ([] === $attributes && isset($_GET['id'])) {
			$attributes['id'] = (string) absint(wp_unslash($_GET['id']));
		}
		// phpcs:enable

		return $attributes;
	}

	/**
	 * @param array<string, string> $attributes
	 */
	public function document(array $attributes): string
	{
		$widget = [] === $attributes ? '' : $this->shortcodes->render('widget', $attributes);
		if ('' === trim($widget)) {
			$widget = '<p class="ucw-preview-empty">' . esc_html__('Choose a widget type to see it here.', 'ultimate-crypto-widget') . '</p>';
		}

		// The widget is rendered first: that is what queues its styles and scripts.
		// Only what the widget queued is printed; the page's usual head and footer hooks
		// are not run, so nothing from the theme or other plugins comes along.
		ob_start();
		wp_styles()->do_items();
		$styles = (string) ob_get_clean();

		ob_start();
		wp_scripts()->do_items();
		$scripts = (string) ob_get_clean();

		return sprintf(
			'<!doctype html><html %1$s><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex"><title>%2$s</title>%3$s<style>body{margin:0;padding:16px;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;background:#fff}.ucw-preview-empty{color:#646970;text-align:center;margin:32px 0}</style></head><body>%4$s%5$s</body></html>',
			get_language_attributes(),
			esc_html__('Widget preview', 'ultimate-crypto-widget'),
			$styles,
			$widget,
			$scripts . self::HEIGHT_SCRIPT
		);
	}
}

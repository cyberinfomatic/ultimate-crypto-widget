<?php

namespace Cyberinfomatic\UltimateCryptoWidget\Widgets;

/**
 * Puts a widget's React component on the page: an element for it to draw in,
 * carrying the data it starts with, plus its script and style.
 *
 * Assets are loaded here, when a widget actually renders, so a page without a
 * widget loads nothing. A widget type's script is loaded once however many
 * widgets of that type are on the page.
 */
final class ReactMount
{
	/**
	 * @param WidgetBundle $core This plugin's own build: its widgets, and the stylesheet every widget shares.
	 */
	public function __construct(
		private readonly WidgetBundle $core,
		private readonly Appearance $appearance
	) {
	}

	/**
	 * @param array<string, mixed> $props What the component receives.
	 * @return string The widget's element, or '' when its script has not been built.
	 */
	public function mount(WidgetDefinition $definition, array $props): string
	{
		$bundle = $definition->bundle ?? $this->core;
		$view   = $definition->view;

		if (!$bundle->has($view)) {
			return '';
		}

		// The look shared by every widget comes from this plugin, whoever provides the widget.
		$theme = $this->core->style('theme', 'theme.css');
		$bundle->load($view, null === $theme ? [] : [$theme]);
		$style = $this->appearance->style();

		return sprintf(
			'<div id="%1$s" class="ucw-widget ucw-widget--%2$s" data-ucw-view="%2$s" data-ucw-theme="%5$s" data-ucw-api="%6$s"%7$s data-ucw-props="%3$s">%4$s</div>',
			esc_attr('ucwp-react-' . wp_unique_id()),
			esc_attr($view),
			esc_attr((string) wp_json_encode($props)),
			esc_html__('Loading...', 'ultimate-crypto-widget'),
			esc_attr($this->appearance->theme($props['settings'] ?? [])),
			// Widgets that load more data (charts, coin details) call this; it is right for any permalink setting.
			esc_url(rest_url('ultimate-crypto-widget/v1/')),
			// The site's own colours, as values of the theme's variables on this element only.
			'' === $style ? '' : ' style="' . esc_attr($style) . '"'
		);
	}
}

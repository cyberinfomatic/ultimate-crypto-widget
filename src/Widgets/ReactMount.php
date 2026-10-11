<?php

namespace Cyberinfomatic\UltimateCryptoWidget\Widgets;

/**
 * Puts a widget's React component on the page: an element for it to draw in,
 * carrying the data it starts with, plus its script and style.
 *
 * Scripts are enqueued here, when a widget actually renders, so a page without a
 * widget loads nothing. A widget type's script is loaded once however many
 * widgets of that type are on the page.
 */
final class ReactMount
{
	/** Where this plugin's own built scripts are, relative to the plugin folder. */
	private const BUILD = 'assets/react-build/';

	/**
	 * @param array<string, mixed> $props What the component receives.
	 * @return string The widget's element, or '' when its script has not been built.
	 */
	public function mount(WidgetDefinition $definition, array $props): string
	{
		$coreDir = UCWP_PLUGIN_DIR . self::BUILD;
		$coreUrl = plugin_dir_url(UCWP_PLUGIN_FILE) . self::BUILD;
		$dir     = $definition->buildDir ?? $coreDir;
		$url     = $definition->buildUrl ?? $coreUrl;
		$view    = $definition->view;
		$script  = 'widgets/' . $view . '/index.js';

		if (!is_file($dir . $script) || !is_file($dir . 'vendors/index.js')) {
			return '';
		}

		// Each build (this plugin's, an add-on's) has its own shared bundle and handles.
		$suffix = null === $definition->buildDir ? '' : '-' . substr(md5($dir), 0, 8);
		$shared = 'ucwp-react-vendor' . $suffix;

		// A file's change time is its version: browsers refetch after an update only.
		wp_enqueue_script($shared, $url . 'vendors/index.js', ['wp-element', 'wp-i18n'], (string) filemtime($dir . 'vendors/index.js'), true);
		wp_enqueue_script('ucw-widget-' . $view . $suffix, $url . $script, [$shared], (string) filemtime($dir . $script), true);

		// The look shared by every widget comes from this plugin, whoever provides the widget.
		if (is_file($coreDir . 'theme.css')) {
			wp_enqueue_style('ucw-theme', $coreUrl . 'theme.css', [], (string) filemtime($coreDir . 'theme.css'));
		}
		$style = 'widgets/' . $view . '.css';
		if (is_file($dir . $style)) {
			wp_enqueue_style('ucw-widget-' . $view . $suffix, $url . $style, is_file($coreDir . 'theme.css') ? ['ucw-theme'] : [], (string) filemtime($dir . $style));
		}

		return sprintf(
			'<div id="%1$s" class="ucw-widget ucw-widget--%2$s" data-ucw-view="%2$s" data-ucw-props="%3$s">%4$s</div>',
			esc_attr('ucwp-react-' . wp_unique_id()),
			esc_attr($view),
			esc_attr((string) wp_json_encode($props)),
			esc_html__('Loading...', 'ultimate-crypto-widget')
		);
	}
}

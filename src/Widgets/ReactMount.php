<?php

namespace Cyberinfomatic\UltimateCryptoWidget\Widgets;

/**
 * Puts a widget's React component on the page: an element for it to draw in, its
 * script and style, and the data it starts with.
 *
 * Scripts are enqueued here, when a widget actually renders, so a page without a
 * widget loads nothing.
 */
final class ReactMount
{
	/** Where this plugin's own built scripts are, relative to the plugin folder. */
	private const BUILD = 'assets/react-build/';

	/**
	 * @param array<string, mixed> $props What the component receives.
	 * @return string The mount point, or '' when the widget's script has not been built.
	 */
	public function mount(WidgetDefinition $definition, array $props): string
	{
		$dir    = $definition->buildDir ?? UCWP_PLUGIN_DIR . self::BUILD;
		$url    = $definition->buildUrl ?? plugin_dir_url(UCWP_PLUGIN_FILE) . self::BUILD;
		$script = 'widgets/' . $definition->view . '/index.js';

		if (!is_file($dir . $script) || !is_file($dir . 'vendors/index.js')) {
			return '';
		}

		$id = 'ucwp-react-' . uniqid();

		// Each build has its own shared bundle; the handle is tied to where it lives.
		$shared = 'ucwp-react-vendor' . (null === $definition->buildDir ? '' : '-' . substr(md5($dir), 0, 8));

		// A file's change time is its version: browsers refetch after an update only.
		wp_enqueue_script($shared, $url . 'vendors/index.js', ['wp-element', 'wp-i18n'], (string) filemtime($dir . 'vendors/index.js'), true);
		wp_enqueue_script($id, $url . $script, [$shared], (string) filemtime($dir . $script), true);
		wp_localize_script(
			$id,
			'ucwpReactData',
			$props + [
				'ucwp_render_component' => 'widgets/' . $definition->view . '/index',
				'react_id'              => $id,
			]
		);

		$style = 'widgets/' . $definition->view . '.css';
		if (is_file($dir . $style)) {
			wp_enqueue_style('widgets/' . $definition->view . '-style', $url . $style, [], (string) filemtime($dir . $style));
		}

		return '<div id="' . esc_attr($id) . '">' . esc_html__('Loading...', 'ultimate-crypto-widget') . '</div>';
	}
}

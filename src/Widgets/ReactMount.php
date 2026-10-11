<?php

namespace Cyberinfomatic\UltimateCryptoWidget\Widgets;

use Cyberinfomatic\UltimateCryptoWidget\Lib\WPToolkit\Assets\AssetManager;

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
	/** Where this plugin's own built scripts are, relative to the plugin folder. */
	private const BUILD = 'assets/react-build/';

	/**
	 * Asset names already declared to the asset manager, which lives as long as the request.
	 *
	 * @var array<string, true>
	 */
	private static array $declared = [];

	public function __construct(private readonly AssetManager $assets)
	{
	}

	/**
	 * @param array<string, mixed> $props What the component receives.
	 * @return string The widget's element, or '' when its script has not been built.
	 */
	public function mount(WidgetDefinition $definition, array $props): string
	{
		$dir    = $definition->buildDir ?? UCWP_PLUGIN_DIR . self::BUILD;
		$view   = $definition->view;
		$script = 'widgets/' . $view . '/index.js';

		if (!is_file($dir . $script) || !is_file($dir . 'vendors/index.js')) {
			return '';
		}

		// The look shared by every widget comes from this plugin, whoever provides the widget.
		$theme = $this->style('theme', 'theme.css');

		if (null === $definition->buildDir) {
			$this->script('vendors', 'vendors/index.js', ['wp-element', 'wp-i18n']);
			$this->script('widget-' . $view, $script, [$this->assets->handle('vendors')]);
			$this->style('widget-' . $view, 'widgets/' . $view . '.css', $theme ? [$this->assets->handle('theme')] : []);
		} else {
			$this->loadFromAddOn($dir, (string) $definition->buildUrl, $view, $theme ? [$this->assets->handle('theme')] : []);
		}

		return sprintf(
			'<div id="%1$s" class="ucw-widget ucw-widget--%2$s" data-ucw-view="%2$s" data-ucw-props="%3$s">%4$s</div>',
			esc_attr('ucwp-react-' . wp_unique_id()),
			esc_attr($view),
			esc_attr((string) wp_json_encode($props)),
			esc_html__('Loading...', 'ultimate-crypto-widget')
		);
	}

	/**
	 * Loads one of this plugin's built scripts through the toolkit, which reads its
	 * dependencies and version from the build and attaches its translations.
	 *
	 * @param string[] $deps
	 */
	private function script(string $name, string $file, array $deps): void
	{
		if (!isset(self::$declared['script:' . $name])) {
			self::$declared['script:' . $name] = true;
			$this->assets->script($name, self::BUILD . $file)->dependsOn(...$deps)->onDemand();
		}
		$this->assets->load($name);
	}

	/**
	 * @param string[] $deps
	 * @return bool False when the build has no such stylesheet.
	 */
	private function style(string $name, string $file, array $deps = []): bool
	{
		if (!is_file(UCWP_PLUGIN_DIR . self::BUILD . $file)) {
			return false;
		}
		// Styles and scripts are named separately, so "widget-x" can be both.
		$name .= '-style';
		if (!isset(self::$declared[$name])) {
			self::$declared[$name] = true;
			$this->assets->style($name, self::BUILD . $file)->dependsOn(...$deps)->onDemand();
		}
		$this->assets->load($name);

		return true;
	}

	/**
	 * An add-on's build lives in another plugin's folder, which the toolkit's asset
	 * manager cannot address yet, so these few are handed to WordPress directly.
	 * Each build has its own shared bundle, hence the handle suffix.
	 *
	 * @param string[] $styleDeps
	 */
	private function loadFromAddOn(string $dir, string $url, string $view, array $styleDeps): void
	{
		$suffix = '-' . substr(md5($dir), 0, 8);
		$shared = $this->assets->handle('vendors' . $suffix);
		$script = 'widgets/' . $view . '/index.js';
		$style  = 'widgets/' . $view . '.css';

		// A file's change time is its version: browsers refetch after an update only.
		wp_enqueue_script($shared, $url . 'vendors/index.js', ['wp-element', 'wp-i18n'], (string) filemtime($dir . 'vendors/index.js'), true);
		wp_enqueue_script($this->assets->handle('widget-' . $view . $suffix), $url . $script, [$shared], (string) filemtime($dir . $script), true);

		if (is_file($dir . $style)) {
			wp_enqueue_style($this->assets->handle('widget-' . $view . $suffix . '-style'), $url . $style, $styleDeps, (string) filemtime($dir . $style));
			if (is_file($dir . 'widgets/' . $view . '-rtl.css')) {
				wp_style_add_data($this->assets->handle('widget-' . $view . $suffix . '-style'), 'rtl', 'replace');
			}
		}
	}
}

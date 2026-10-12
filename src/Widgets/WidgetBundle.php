<?php

namespace Cyberinfomatic\UltimateCryptoWidget\Widgets;

use Cyberinfomatic\UltimateCryptoWidget\Lib\WPToolkit\Assets\AssetManager;

/**
 * One plugin's built widget scripts: a folder with `vendors/index.js` (the code
 * its widgets share) and `widgets/{view}/index.js` for each widget, plus an
 * optional `widgets/{view}.css`.
 *
 * It loads them through that plugin's own toolkit asset manager, which reads each
 * file's dependencies and version from the build, attaches translations and picks
 * the right-to-left stylesheet. This plugin has one bundle; an add-on brings its
 * own and names it in its WidgetDefinitions.
 */
final class WidgetBundle
{
	/** @var array<string, true> Asset names already declared to the manager. */
	private array $declared = [];

	/**
	 * @param AssetManager $assets The asset manager of the plugin that owns the build.
	 * @param string       $dir    The build folder on disk, with a trailing slash.
	 * @param string       $path   The same folder relative to that plugin's root, with a trailing slash.
	 */
	public function __construct(
		private readonly AssetManager $assets,
		private readonly string $dir,
		private readonly string $path
	) {
	}

	/**
	 * Whether the widget's script, and the shared one it needs, have been built.
	 */
	public function has(string $view): bool
	{
		return is_file($this->dir . 'widgets/' . $view . '/index.js') && is_file($this->dir . 'vendors/index.js');
	}

	/**
	 * Loads a widget's script and, if it has one, its stylesheet.
	 *
	 * @param string[] $styleDeps Handles its stylesheet must come after.
	 */
	public function load(string $view, array $styleDeps = []): void
	{
		$this->script('vendors', 'vendors/index.js', ['wp-element', 'wp-i18n']);
		$this->script('widget-' . $view, 'widgets/' . $view . '/index.js', [$this->assets->handle('vendors')]);
		$this->style('widget-' . $view, 'widgets/' . $view . '.css', $styleDeps);
	}

	/**
	 * Loads a stylesheet from the build's root.
	 *
	 * @param string[] $deps
	 * @return string|null Its handle, or null when the build has no such file.
	 */
	public function style(string $name, string $file, array $deps = []): ?string
	{
		if (!is_file($this->dir . $file)) {
			return null;
		}
		// Styles and scripts are named separately, so "widget-x" can be both.
		$name .= '-style';
		if (!isset($this->declared[$name])) {
			$this->declared[$name] = true;
			$this->assets->style($name, $this->path . $file)->dependsOn(...$deps)->onDemand();
		}
		$this->assets->load($name);

		return $this->assets->handle($name);
	}

	/**
	 * @param string[] $deps
	 */
	private function script(string $name, string $file, array $deps): void
	{
		if (!isset($this->declared['script:' . $name])) {
			$this->declared['script:' . $name] = true;
			$this->assets->script($name, $this->path . $file)->dependsOn(...$deps)->onDemand();
		}
		$this->assets->load($name);
	}
}

<?php

namespace Cyberinfomatic\UltimateCryptoWidget;

use Cyberinfomatic\UltimateCryptoWidget\Lib\WPToolkit\Foundation\Application;

/**
 * What an add-on needs to know about the running plugin.
 *
 *     add_action('plugins_loaded', function () {
 *         if (!class_exists(\Cyberinfomatic\UltimateCryptoWidget\Plugin::class)) {
 *             return; // The core plugin is not active.
 *         }
 *         $core = \Cyberinfomatic\UltimateCryptoWidget\Plugin::app();
 *         …
 *     });
 *
 * The plugin's own code does not use this: it takes its services by injection.
 */
final class Plugin
{
	private static ?Application $app = null;

	private static string $file = '';

	/**
	 * @internal Called once from src/boot.php.
	 */
	public static function started(Application $app, string $file): void
	{
		self::$app  = $app;
		self::$file = $file;

		/**
		 * The core plugin has started. Its services are in $app->container().
		 *
		 * @param Application $app
		 */
		do_action('ucwp_loaded', $app);
	}

	/**
	 * The running application, or null if the plugin was kept from starting.
	 */
	public static function app(): ?Application
	{
		return self::$app;
	}

	/**
	 * The plugin's version, from its header.
	 */
	public static function version(): string
	{
		static $version = null;
		if (null === $version) {
			$version = '' === self::$file ? '' : (string) get_file_data(self::$file, ['Version' => 'Version'])['Version'];
		}

		return $version;
	}

	/**
	 * The main plugin file.
	 */
	public static function file(): string
	{
		return self::$file;
	}

	/**
	 * Loads an add-on's classes by PSR-4, with the same loader WPToolkit gives this plugin:
	 *
	 *     Plugin::autoload('Acme\\CryptoAddon\\', __DIR__ . '/src');
	 *
	 * `Acme\CryptoAddon\Admin\Page` is then read from `src/Admin/Page.php`. File and folder
	 * names must match class names exactly, including case. An add-on borrows this plugin's
	 * copy of the toolkit and cannot call the toolkit's guard itself, so it asks here.
	 *
	 * @param string $prefix    The namespace every class of the add-on starts with.
	 * @param string $directory The folder that namespace maps to.
	 * @throws \InvalidArgumentException When the prefix is empty or the folder does not exist.
	 */
	public static function autoload(string $prefix, string $directory): void
	{
		$prefix    = trim($prefix, '\\');
		$directory = rtrim($directory, '/\\');
		if ('' === $prefix || !is_dir($directory)) {
			throw new \InvalidArgumentException(sprintf('Cannot autoload "%s" from "%s": give a namespace and a folder that exists.', $prefix, $directory));
		}

		// bootstrap/namespaces.php, in the toolkit copy this plugin runs on.
		$toolkit  = dirname((string) (new \ReflectionClass(Application::class))->getFileName(), 3);
		$register = require $toolkit . '/bootstrap/namespaces.php';
		$register([$prefix . '\\' => $directory]);
	}
}

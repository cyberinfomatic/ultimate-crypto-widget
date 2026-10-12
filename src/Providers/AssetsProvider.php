<?php

namespace Cyberinfomatic\UltimateCryptoWidget\Providers;

use Cyberinfomatic\UltimateCryptoWidget\Lib\WPToolkit\Assets\AssetManager;
use Cyberinfomatic\UltimateCryptoWidget\Lib\WPToolkit\Assets\JsNamespace;
use Cyberinfomatic\UltimateCryptoWidget\Lib\WPToolkit\Contracts\Container\Container;
use Cyberinfomatic\UltimateCryptoWidget\Lib\WPToolkit\Foundation\Application;
use Cyberinfomatic\UltimateCryptoWidget\Lib\WPToolkit\Foundation\ServiceProvider;
use Cyberinfomatic\UltimateCryptoWidget\Widgets\WidgetBundle;

/**
 * The plugin's asset manager and its bundle of built widget scripts.
 */
final class AssetsProvider extends ServiceProvider
{
	/** Where the built scripts are, relative to a plugin's folder. */
	public const BUILD = 'assets/react-build/';

	public function register(): void
	{
		$app       = $this->app;
		$container = $app->container();

		$container->singleton(AssetManager::class, static fn (): AssetManager => self::managerFor($app, UCWP_PLUGIN_FILE, 'ultimate-crypto-widget'));

		$container->singleton(WidgetBundle::class, static fn (Container $c): WidgetBundle => new WidgetBundle(
			$c->get(AssetManager::class),
			UCWP_PLUGIN_DIR . self::BUILD,
			self::BUILD
		));
	}

	/**
	 * An asset manager that knows a plugin's real address.
	 *
	 * The toolkit (1.0.0-rc.4) works a plugin's URL out from its folder's place under
	 * wp-content. A plugin installed through a symbolic link is somewhere else on
	 * disk, so every script got an address that does not exist. WordPress's own
	 * plugin_dir_url() handles links. An add-on with its own toolkit application
	 * uses this too; drop it once the toolkit does the same.
	 *
	 * @param string $file The plugin's main file.
	 */
	public static function managerFor(Application $app, string $file, string $textDomain): AssetManager
	{
		$dir     = plugin_dir_path($file);
		$library = UCWP_PLUGIN_DIR . 'lib/wptoolkit';

		$assets = new AssetManager(
			$app->identity(),
			new JsNamespace($app->identity(), Application::VERSION),
			untrailingslashit($dir),
			plugin_dir_url($file),
			$library,
			plugin_dir_url(UCWP_PLUGIN_FILE) . 'lib/wptoolkit',
			$textDomain,
			$dir . 'languages'
		);
		$assets->register($app->hooks());

		return $assets;
	}
}

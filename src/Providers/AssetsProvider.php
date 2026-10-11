<?php

namespace Cyberinfomatic\UltimateCryptoWidget\Providers;

use Cyberinfomatic\UltimateCryptoWidget\Lib\WPToolkit\Assets\AssetManager;
use Cyberinfomatic\UltimateCryptoWidget\Lib\WPToolkit\Assets\JsNamespace;
use Cyberinfomatic\UltimateCryptoWidget\Lib\WPToolkit\Foundation\Application;
use Cyberinfomatic\UltimateCryptoWidget\Lib\WPToolkit\Foundation\ServiceProvider;

/**
 * Gives the toolkit's asset manager this plugin's real address.
 *
 * The toolkit (1.0.0-rc.4) works a plugin's URL out from its folder's place under
 * wp-content. A plugin installed through a symbolic link is somewhere else on
 * disk, so every script got an address that does not exist. WordPress's own
 * plugin_dir_url() handles links, so the asset manager is built here with that.
 * Remove this provider once the toolkit does the same.
 */
final class AssetsProvider extends ServiceProvider
{
	public function register(): void
	{
		$app = $this->app;

		$app->container()->singleton(AssetManager::class, static function () use ($app): AssetManager {
			$library = UCWP_PLUGIN_DIR . 'lib/wptoolkit';
			$url     = plugin_dir_url(UCWP_PLUGIN_FILE);

			$assets = new AssetManager(
				$app->identity(),
				new JsNamespace($app->identity(), Application::VERSION),
				untrailingslashit(UCWP_PLUGIN_DIR),
				$url,
				$library,
				$url . 'lib/wptoolkit',
				'ultimate-crypto-widget',
				UCWP_PLUGIN_DIR . 'languages'
			);
			$assets->register($app->hooks());

			return $assets;
		});
	}
}

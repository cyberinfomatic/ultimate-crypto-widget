<?php

namespace Cyberinfomatic\UltimateCryptoWidget\Providers;

use Cyberinfomatic\UltimateCryptoWidget\App;
use Cyberinfomatic\UltimateCryptoWidget\Lib\WPToolkit\Foundation\ServiceProvider;

/**
 * Runs the plugin's original start-up code inside the WPToolkit application.
 *
 * Nothing about the plugin's behaviour changes here. Each responsibility of App::start()
 * moves to its own provider in later phases, and this class is deleted when none is left.
 */
final class LegacyProvider extends ServiceProvider
{
	/**
	 * Called on `init`, after the text domain is available.
	 */
	public function boot(): void
	{
		( new App() )->start();
	}
}

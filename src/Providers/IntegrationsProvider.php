<?php

namespace Cyberinfomatic\UltimateCryptoWidget\Providers;

use Cyberinfomatic\UltimateCryptoWidget\Admin\Preview;
use Cyberinfomatic\UltimateCryptoWidget\Integrations\Elementor\CryptoWidget;
use Cyberinfomatic\UltimateCryptoWidget\Lib\WPToolkit\Foundation\HookRegistrar;
use Cyberinfomatic\UltimateCryptoWidget\Lib\WPToolkit\Foundation\ServiceProvider;
use Cyberinfomatic\UltimateCryptoWidget\Lib\WPToolkit\Frontend\Shortcodes;
use Cyberinfomatic\UltimateCryptoWidget\Widgets\WidgetSchema;

/**
 * Other plugins this one fits into. Each costs nothing unless that plugin is running.
 */
final class IntegrationsProvider extends ServiceProvider
{
	public function boot(HookRegistrar $hooks): void
	{
		$container = $this->app->container();

		// Elementor fires this only when it is active and needs its elements, so our
		// element class (which extends one of Elementor's) is never loaded otherwise.
		$hooks->addAction('elementor/widgets/register', static function ($widgets) use ($container): void {
			if (!is_object($widgets) || !method_exists($widgets, 'register')) {
				return;
			}
			CryptoWidget::use($container->get(Shortcodes::class), $container->get(WidgetSchema::class), $container->get(Preview::class));
			$widgets->register(new CryptoWidget());
		});
	}
}

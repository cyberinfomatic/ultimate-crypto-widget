<?php

namespace Cyberinfomatic\UltimateCryptoWidget\Providers;

use Cyberinfomatic\UltimateCryptoWidget\Admin\Preview;
use Cyberinfomatic\UltimateCryptoWidget\Integrations\Elementor\CryptoWidget;
use Cyberinfomatic\UltimateCryptoWidget\Lib\WPToolkit\Foundation\HookRegistrar;
use Cyberinfomatic\UltimateCryptoWidget\Lib\WPToolkit\Foundation\ServiceProvider;
use Cyberinfomatic\UltimateCryptoWidget\Lib\WPToolkit\Frontend\Shortcodes;
use Cyberinfomatic\UltimateCryptoWidget\Widgets\WidgetCatalog;
use Cyberinfomatic\UltimateCryptoWidget\Widgets\WidgetSchema;

/**
 * Other plugins this one fits into. Each costs nothing unless that plugin is running.
 */
final class IntegrationsProvider extends ServiceProvider
{
	public function boot(HookRegistrar $hooks): void
	{
		$container = $this->app->container();

		// A group of our own in Elementor's panel, so the element is easy to find.
		$hooks->addAction('elementor/elements/categories_registered', static function ($elements): void {
			if (is_object($elements) && method_exists($elements, 'add_category')) {
				$elements->add_category(CryptoWidget::CATEGORY, [
					'title' => __('Crypto Widgets', 'ultimate-crypto-widget'),
					'icon'  => 'eicon-price-table',
				]);
			}
		});

		// Elementor fires this only when it is active and needs its elements, so our
		// element class (which extends one of Elementor's) is never loaded otherwise.
		$hooks->addAction('elementor/widgets/register', static function ($widgets) use ($container): void {
			if (!is_object($widgets) || !method_exists($widgets, 'register')) {
				return;
			}
			// One element per widget type this site can use: this plugin's own, and an add-on's
			// once it has unlocked them.
			$types = [];
			foreach ($container->get(WidgetCatalog::class)->all() as $type => $definition) {
				if ($definition->available) {
					$types[$type] = ['label' => $definition->label, 'view' => $definition->view];
				}
			}
			CryptoWidget::use($container->get(Shortcodes::class), $container->get(WidgetSchema::class), $container->get(Preview::class), $types);

			foreach (array_keys($types) as $type) {
				$widgets->register(CryptoWidget::forType((string) $type));
			}
			// The element with a type picker stays registered, out of the panel, for any page that has it.
			$widgets->register(new CryptoWidget());
		});
	}
}

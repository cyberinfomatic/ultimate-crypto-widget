<?php

namespace Cyberinfomatic\UltimateCryptoWidget\Providers;

use Cyberinfomatic\UltimateCryptoWidget\Lib\WPToolkit\Foundation\ServiceProvider;
use Cyberinfomatic\UltimateCryptoWidget\Lib\WPToolkit\Frontend\Shortcodes;
use Cyberinfomatic\UltimateCryptoWidget\Widgets\WidgetCatalog;
use Cyberinfomatic\UltimateCryptoWidget\Widgets\WidgetShortcode;

/**
 * The widget catalogue and the [ucwp_widget] shortcode.
 */
final class WidgetsProvider extends ServiceProvider
{
	public function register(): void
	{
		// One catalogue for the whole request, so add-ons register into the one everything reads.
		$this->app->container()->singleton(WidgetCatalog::class);
	}

	public function boot(Shortcodes $shortcodes, WidgetShortcode $shortcode): void
	{
		// The tag is in users' content; it must stay "ucwp_widget".
		$shortcodes->add('widget', $shortcode(...), tag: WidgetShortcode::TAG)
			->public()
			->attributes(WidgetShortcode::attributes());
	}
}

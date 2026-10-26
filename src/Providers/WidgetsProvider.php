<?php

namespace Cyberinfomatic\UltimateCryptoWidget\Providers;

use Cyberinfomatic\UltimateCryptoWidget\Lib\WPToolkit\Foundation\ServiceProvider;
use Cyberinfomatic\UltimateCryptoWidget\Lib\WPToolkit\Frontend\Shortcodes;
use Cyberinfomatic\UltimateCryptoWidget\Widgets\WidgetCatalog;
use Cyberinfomatic\UltimateCryptoWidget\Widgets\WidgetSchema;
use Cyberinfomatic\UltimateCryptoWidget\Widgets\WidgetShortcode;

/**
 * The widget catalogue and the [ucwp_widget] shortcode.
 */
final class WidgetsProvider extends ServiceProvider
{
	/** Binds the catalogue and the settings schema, one instance of each. */
	public function register(): void
	{
		// One catalogue for the whole request, so add-ons register into the one everything reads.
		$this->app->container()->singleton(WidgetCatalog::class);
		// One description of the settings for the editor, the block and page builders.
		$this->app->container()->singleton(WidgetSchema::class);
	}

	/** Registers the [ucwp_widget] shortcode. */
	public function boot(Shortcodes $shortcodes, WidgetShortcode $shortcode): void
	{
		// The tag is in users' content; it must stay "ucwp_widget".
		$shortcodes->add('widget', $shortcode(...), tag: WidgetShortcode::TAG)
			->public()
			->attributes(WidgetShortcode::attributes());
	}
}

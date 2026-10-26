<?php

namespace Cyberinfomatic\UltimateCryptoWidget\Providers;

use Cyberinfomatic\UltimateCryptoWidget\Admin\CacheTools;
use Cyberinfomatic\UltimateCryptoWidget\Admin\Preview;
use Cyberinfomatic\UltimateCryptoWidget\Admin\ReviewNotice;
use Cyberinfomatic\UltimateCryptoWidget\Admin\Screens;
use Cyberinfomatic\UltimateCryptoWidget\Admin\WidgetEditor;
use Cyberinfomatic\UltimateCryptoWidget\Data\PriceProvider;
use Cyberinfomatic\UltimateCryptoWidget\Lib\WPToolkit\Admin\Notices;
use Cyberinfomatic\UltimateCryptoWidget\Lib\WPToolkit\Admin\Pages;
use Cyberinfomatic\UltimateCryptoWidget\Lib\WPToolkit\Admin\Settings\Settings;
use Cyberinfomatic\UltimateCryptoWidget\Lib\WPToolkit\Admin\Settings\SettingsForm;
use Cyberinfomatic\UltimateCryptoWidget\Lib\WPToolkit\Assets\Asset;
use Cyberinfomatic\UltimateCryptoWidget\Lib\WPToolkit\Assets\AssetManager;
use Cyberinfomatic\UltimateCryptoWidget\Lib\WPToolkit\Contracts\Container\Container;
use Cyberinfomatic\UltimateCryptoWidget\Lib\WPToolkit\Data\EntityRegistrar;
use Cyberinfomatic\UltimateCryptoWidget\Lib\WPToolkit\Data\Field\FieldTypes;
use Cyberinfomatic\UltimateCryptoWidget\Lib\WPToolkit\Foundation\ServiceProvider;
use Cyberinfomatic\UltimateCryptoWidget\Lib\WPToolkit\Frontend\Shortcodes;
use Cyberinfomatic\UltimateCryptoWidget\Widgets\WidgetCatalog;
use Cyberinfomatic\UltimateCryptoWidget\Widgets\WidgetSchema;

/**
 * The widget post type and everything in wp-admin: the editor, the menu and its
 * screens, the settings form.
 */
final class AdminProvider extends ServiceProvider
{
	/** Binds the admin services other providers use too: the preview (the block and Elementor show it), the widget editor and the settings form. */
	public function register(): void
	{
		$app       = $this->app;
		$container = $app->container();

		$container->singleton(Preview::class, static fn (Container $c): Preview => new Preview($c->get(Shortcodes::class)));

		$container->singleton(WidgetEditor::class, static fn (Container $c): WidgetEditor => new WidgetEditor(
			$c->get(EntityRegistrar::class),
			$c->get(FieldTypes::class),
			$c->get(WidgetSchema::class),
			$c->get(WidgetCatalog::class),
			$c->get(Preview::class),
			$c->get(Notices::class),
			$app->hooks()
		));

		$container->singleton(SettingsForm::class, static fn (Container $c): SettingsForm => new SettingsForm(
			$c->get(Settings::class),
			$c->get(FieldTypes::class),
			$app->identity()
		));
	}

	/**
	 * Runs on "init": add-ons have registered their widget types and text can be translated.
	 */
	public function boot(WidgetEditor $editor, WidgetSchema $schema, Preview $preview, SettingsForm $form, Pages $pages, FieldTypes $types, PriceProvider $prices, Notices $notices, AssetManager $assets): void
	{
		$hooks = $this->app->hooks();

		// The post type and its save handler are needed on every request that can save a widget.
		$editor->register();

		$form->section('general', __('Price data', 'ultimate-crypto-widget'));
		$form->section('appearance', __('Appearance', 'ultimate-crypto-widget'));
		$form->register($hooks, Screens::SETTINGS);

		(new Screens($pages, $form, $schema, $preview, $types, $prices))->register();

		$hooks->addAction('admin_post_' . Preview::ACTION, [$preview, 'handle']);
		$hooks->addAction('admin_post_' . CacheTools::ACTION, [new CacheTools($prices, $notices), 'handle']);
		(new ReviewNotice($hooks))->register();

		$onOurScreens = static fn (): bool => Screens::owns(function_exists('get_current_screen') ? get_current_screen() : null);
		// Also on the Dashboard, where ReviewNotice can show; every rule in it is under a .ucw- class.
		$onOurScreensOrDashboard = static fn (): bool => $onOurScreens() || 'dashboard' === (function_exists('get_current_screen') ? get_current_screen()?->id : null);
		$assets->style('admin-style', 'assets/react-build/admin.css')->in(Asset::ADMIN)->when($onOurScreensOrDashboard);
		// The address and nonce the script uses to say a shortcode was copied (ReviewNotice).
		$assets->script('admin', 'assets/react-build/admin/index.js')->in(Asset::ADMIN)->when($onOurScreens)->with(is_admin() ? ['usage' => ReviewNotice::scriptData()] : []);
	}
}

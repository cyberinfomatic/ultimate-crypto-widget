<?php

namespace Cyberinfomatic\UltimateCryptoWidget\Providers;

use Cyberinfomatic\UltimateCryptoWidget\Lib\WPToolkit\Admin\Settings\Settings;
use Cyberinfomatic\UltimateCryptoWidget\Lib\WPToolkit\Contracts\Container\Container;
use Cyberinfomatic\UltimateCryptoWidget\Lib\WPToolkit\Contracts\Log\Logger;
use Cyberinfomatic\UltimateCryptoWidget\Lib\WPToolkit\Data\Field\FieldFactory;
use Cyberinfomatic\UltimateCryptoWidget\Lib\WPToolkit\Data\Field\FieldTypes;
use Cyberinfomatic\UltimateCryptoWidget\Lib\WPToolkit\Foundation\ServiceProvider;
use Cyberinfomatic\UltimateCryptoWidget\Lib\WPToolkit\Support\Validation\Rules;

/**
 * The plugin's settings.
 *
 * They are stored where they have always been: one option each, named
 * ultimate_crypto_widget_{name}. That prefix (underscores) differs from the
 * plugin's slug (hyphens), so it is given explicitly. Do not change it: users'
 * saved API keys live under those names.
 */
final class SettingsProvider extends ServiceProvider
{
	public const OPTION_PREFIX = 'ultimate_crypto_widget_';

	public function register(): void
	{
		$app = $this->app;

		$app->container()->singleton(
			Settings::class,
			static function (Container $c) use ($app): Settings {
				$field = new FieldFactory();

				return new Settings(
					[
						// API keys are secrets: readable by name only, never exported or logged.
						$field->password('coingecko_api_key')
							->label(__('CoinGecko API Key (optional)', 'ultimate-crypto-widget'))
							->description(__('The widgets work without a key. A free CoinGecko key raises the request limit for busy sites.', 'ultimate-crypto-widget'))
							->sensitive(),
						// No longer used; declared so a key saved by an older version stays protected.
						$field->password('openexchangerates_app_id')
							->label(__('Open Exchange Rates App ID (no longer needed)', 'ultimate-crypto-widget'))
							->sensitive(),
						$field->number('cache_interval')
							->label(__('Cache Interval', 'ultimate-crypto-widget'))
							->description(__('How long prices are kept before they are fetched again, in minutes.', 'ultimate-crypto-widget'))
							->default(10)
							->rules('integer|min:1|max:1440'),
						$field->select('theme', [
							'light' => __('Light', 'ultimate-crypto-widget'),
							'dark'  => __('Dark', 'ultimate-crypto-widget'),
							'auto'  => __('Match the visitor\'s device', 'ultimate-crypto-widget'),
						])
							->label(__('Colours', 'ultimate-crypto-widget'))
							->description(__('A widget with its own "Dark" setting stays dark.', 'ultimate-crypto-widget'))
							->default('light')
							->with('group', 'appearance'),
						$field->text('accent_color')
							->label(__('Accent colour', 'ultimate-crypto-widget'))
							->description(__('Used for highlights such as the selected tab. A colour code like #4f46e5; leave empty for the default.', 'ultimate-crypto-widget'))
							->rules([Rules::regex('/^#[0-9a-fA-F]{6}$/')])
							->attributes(['placeholder' => '#4f46e5', 'maxlength' => 7, 'class' => 'ucw-colour-field'])
							->with('group', 'appearance'),
					],
					$c->get(FieldTypes::class),
					$app->identity(),
					null,
					$c->get(Logger::class),
					optionPrefix: self::OPTION_PREFIX
				);
			}
		);
	}
}

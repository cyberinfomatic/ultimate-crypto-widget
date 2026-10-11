<?php

namespace Cyberinfomatic\UltimateCryptoWidget\Providers;

use Cyberinfomatic\UltimateCryptoWidget\Data\CoinGeckoProvider;
use Cyberinfomatic\UltimateCryptoWidget\Data\PriceProvider;
use Cyberinfomatic\UltimateCryptoWidget\Legacy\Services;
use Cyberinfomatic\UltimateCryptoWidget\Lib\WPToolkit\Adapters\Cache\CacheFactory;
use Cyberinfomatic\UltimateCryptoWidget\Lib\WPToolkit\Contracts\Container\Container;
use Cyberinfomatic\UltimateCryptoWidget\Lib\WPToolkit\Contracts\Http\HttpClient;
use Cyberinfomatic\UltimateCryptoWidget\Lib\WPToolkit\Contracts\Log\Logger;
use Cyberinfomatic\UltimateCryptoWidget\Lib\WPToolkit\Foundation\ServiceProvider;
use Cyberinfomatic\UltimateCryptoWidget\Lib\WPToolkit\Http\Client\ApiClient;
use Cyberinfomatic\UltimateCryptoWidget\Lib\WPToolkit\Http\Client\Auth;

/**
 * Binds the price source.
 */
final class DataProvider extends ServiceProvider
{
	private const COINGECKO_URL = 'https://api.coingecko.com/api/v3';

	public function register(): void
	{
		$container = $this->app->container();

		$container->singleton(PriceProvider::class, static fn (Container $c): PriceProvider => self::make($c));

		// Until the old static helpers are gone they reach services through this.
		Services::use($container);
	}

	/**
	 * Builds the price source from the saved settings.
	 */
	public static function make(Container $c): PriceProvider
	{
		// The key is optional: without one CoinGecko's public limits apply.
		$key = trim((string) get_option('ultimate_crypto_widget_coingecko_api_key', ''));

		$provider = new CoinGeckoProvider(
			new ApiClient(
				$c->get(HttpClient::class),
				self::COINGECKO_URL,
				'' !== $key ? Auth::header('x-cg-demo-api-key', $key) : null,
				['accept' => 'application/json'],
				logger: $c->get(Logger::class)
			),
			// Its own group, so "Clear API cache" clears prices and nothing else.
			$c->get(CacheFactory::class)->make('prices'),
			$c->get(Logger::class),
			max(1, (int) get_option('ultimate_crypto_widget_cache_interval', 10)) * MINUTE_IN_SECONDS
		);

		/**
		 * Replace the price source, e.g. with a hosted feed.
		 *
		 * @param PriceProvider $provider The default CoinGecko provider.
		 */
		$filtered = apply_filters('ucwp_price_provider', $provider);

		return $filtered instanceof PriceProvider ? $filtered : $provider;
	}
}

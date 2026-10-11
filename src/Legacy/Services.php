<?php

namespace Cyberinfomatic\UltimateCryptoWidget\Legacy;

use Cyberinfomatic\UltimateCryptoWidget\Data\PriceProvider;
use Cyberinfomatic\UltimateCryptoWidget\Lib\WPToolkit\Contracts\Container\Container;

/**
 * Lets the old static helper classes reach the application's services.
 *
 * New code takes its dependencies through its constructor and must not use this.
 * It is deleted together with the last static helper.
 */
final class Services
{
	private static ?Container $container = null;

	public static function use(Container $container): void
	{
		self::$container = $container;
	}

	public static function container(): Container
	{
		if (null === self::$container) {
			throw new \LogicException('The plugin has not started yet.');
		}

		return self::$container;
	}

	public static function prices(): PriceProvider
	{
		return self::container()->get(PriceProvider::class);
	}
}

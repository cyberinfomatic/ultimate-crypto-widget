<?php
/**
 * Reaches the running plugin's services from a test.
 *
 * @package Ultimate_Crypto_Widget
 */

namespace Cyberinfomatic\UltimateCryptoWidget\Tests\Support;

use Cyberinfomatic\UltimateCryptoWidget\Data\PriceProvider;
use Cyberinfomatic\UltimateCryptoWidget\Lib\WPToolkit\Contracts\Container\Container;
use Cyberinfomatic\UltimateCryptoWidget\Plugin;

/**
 * The plugin's own code takes what it needs through constructors; only tests look
 * services up by name.
 */
final class Services {

	public static function container(): Container {
		$app = Plugin::app();
		if ( null === $app ) {
			throw new \LogicException( 'The plugin has not started yet.' );
		}

		return $app->container();
	}

	public static function prices(): PriceProvider {
		return self::container()->get( PriceProvider::class );
	}
}

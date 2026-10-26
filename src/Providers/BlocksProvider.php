<?php

namespace Cyberinfomatic\UltimateCryptoWidget\Providers;

use Cyberinfomatic\UltimateCryptoWidget\Admin\Preview;
use Cyberinfomatic\UltimateCryptoWidget\Blocks\WidgetBlock;
use Cyberinfomatic\UltimateCryptoWidget\Lib\WPToolkit\Assets\AssetManager;
use Cyberinfomatic\UltimateCryptoWidget\Lib\WPToolkit\Contracts\Container\Container;
use Cyberinfomatic\UltimateCryptoWidget\Lib\WPToolkit\Foundation\ServiceProvider;
use Cyberinfomatic\UltimateCryptoWidget\Lib\WPToolkit\Frontend\Shortcodes;
use Cyberinfomatic\UltimateCryptoWidget\Widgets\WidgetSchema;

/**
 * The block editor's "Crypto Widget" block.
 */
final class BlocksProvider extends ServiceProvider
{
	/** Binds the block; boot() registers it with WordPress. */
	public function register(): void
	{
		$app = $this->app;

		$app->container()->singleton(WidgetBlock::class, static fn (Container $c): WidgetBlock => new WidgetBlock(
			$c->get(Shortcodes::class),
			$c->get(WidgetSchema::class),
			$c->get(Preview::class),
			$c->get(AssetManager::class),
			$app->hooks()
		));
	}

	/** Runs on "init", once add-ons have registered their widget types. */
	public function boot(WidgetBlock $block): void
	{
		$block->register();
	}
}

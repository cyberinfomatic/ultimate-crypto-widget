<?php

namespace Cyberinfomatic\UltimateCryptoWidget\Providers;

use Cyberinfomatic\UltimateCryptoWidget\Http\MarketsController;
use Cyberinfomatic\UltimateCryptoWidget\Http\QuickWidgetController;
use Cyberinfomatic\UltimateCryptoWidget\Lib\WPToolkit\Foundation\ServiceProvider;
use Cyberinfomatic\UltimateCryptoWidget\Widgets\Currencies;
use Cyberinfomatic\UltimateCryptoWidget\Lib\WPToolkit\Http\Router;

/**
 * The plugin's REST routes, under ultimate-crypto-widget/v1.
 *
 * Every route states who may call it, declares its input and is rate limited.
 * The URLs are the ones the widgets have always called.
 */
final class HttpProvider extends ServiceProvider
{
	/** A CoinGecko coin id, and a comma-separated list of them. */
	private const COIN_ID  = 'regex:/^[a-z0-9_-]{1,100}$/';
	private const COIN_IDS = 'regex:/^[a-z0-9_,-]{1,2000}$/';

	/** Declares the routes. The widgets' scripts call these paths: do not change them. */
	public function boot(Router $router): void
	{
		$currencies = 'in:' . implode(',', Currencies::CODES);

		// Public on purpose: the widgets call these from visitors' browsers, often on
		// cached pages. The limits protect the site owner's CoinGecko quota.
		$router->get('coins', [MarketsController::class, 'coins'])
			->public()
			->rateLimit(60, perSeconds: 60)
			->args([
				'default_currency' => ['rules' => $currencies, 'type' => 'key'],
				'coins'            => ['rules' => self::COIN_IDS, 'type' => 'text'],
			]);

		$router->get('coin-info', [MarketsController::class, 'coin'])
			->public()
			->rateLimit(30, perSeconds: 60)
			->args([
				'coin_id' => ['rules' => 'required|' . self::COIN_ID, 'type' => 'key'],
			]);

		$router->get('coin-chart-data', [MarketsController::class, 'chart'])
			->public()
			->rateLimit(30, perSeconds: 60)
			->args([
				'coin_id'  => ['rules' => 'required|' . self::COIN_ID, 'type' => 'key'],
				'days'     => ['rules' => 'integer|min:1|max:365', 'type' => 'int', 'default' => 7],
				'currency' => ['rules' => $currencies, 'type' => 'key', 'default' => 'usd'],
			]);

		// The whole market's figures, for Market Overview to refresh while live prices are on.
		$router->get('global-market', [MarketsController::class, 'globalMarket'])
			->public()
			->rateLimit(30, perSeconds: 60)
			->args([
				'currency' => ['rules' => $currencies, 'type' => 'key', 'default' => 'usd'],
			]);

		// Admin preview only.
		$router->post('load-shortcode', [QuickWidgetController::class, 'render'])
			->can('edit_posts')
			->rateLimit(30, perSeconds: 60, by: 'user')
			->args([
				'shortcode' => ['rules' => 'required|in:ucwp_widget', 'type' => 'key'],
				'atts'      => ['rules' => 'required', 'type' => 'raw'],
			]);
	}
}

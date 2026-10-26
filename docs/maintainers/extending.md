# Extending

How another plugin (or a theme) can change Ultimate Crypto Widget without editing it. The Pro add-on uses exactly these.

## Hooks and filters

| Hook | Kind | Use it to |
| --- | --- | --- |
| `ucwp_loaded` | action | Run code once the plugin has started. Receives the toolkit `Application`. |
| `ucwp_register_widgets` | action | Add widget types, or replace a listed one. Receives the `WidgetCatalog`. |
| `ucwp_price_provider` | filter | Replace the price source with another `Data\PriceProvider`. |
| `ucwp_widget_style` | filter | Set theme variables on every widget: `['--ucw-radius' => '4px']`. Only `--ucw-*` names with plain values are printed. |
| `ucwp_widget_count_choices` | filter | Change how many coins a widget may be set to show (default `[5, 10, 25, 50, 100]`; at most 250). |
| `ucwp_upgrade_url` | filter | Change where "See Pro" links lead (tiered edition only). |
| `ucwp_show_upgrade_notice` | filter | Return `false` to hide the suggestions to get Pro. They are off in the all-free edition. |

Add your callbacks no later than `plugins_loaded`: the catalogue is filled the first time it is asked for, on `init`.

Counts above 100 need a price provider that returns more than CoinGecko's default 100 coins.

## Adding widget types from another plugin

An add-on brings its own built scripts and says where they are with a `WidgetBundle`. The bundle loads them through the
add-on's own toolkit asset manager, so they get the add-on's handles, version, translations and right-to-left stylesheet.

```php
use Cyberinfomatic\UltimateCryptoWidget\Lib\WPToolkit\Foundation\Application;
use Cyberinfomatic\UltimateCryptoWidget\Providers\AssetsProvider;
use Cyberinfomatic\UltimateCryptoWidget\Widgets\WidgetBundle;
use Cyberinfomatic\UltimateCryptoWidget\Widgets\WidgetCatalog;
use Cyberinfomatic\UltimateCryptoWidget\Widgets\WidgetDefinition;

add_action('plugins_loaded', function () {
    if (!class_exists(\Cyberinfomatic\UltimateCryptoWidget\Plugin::class)) {
        return; // The core plugin is not active.
    }

    // An application of your own, built from the toolkit the core bundles.
    $app    = Application::create(__FILE__, ['slug' => 'my-crypto-addon']);
    $assets = AssetsProvider::managerFor($app, __FILE__, 'my-crypto-addon');
    $bundle = new WidgetBundle($assets, plugin_dir_path(__FILE__) . 'build/', 'build/');

    add_action('ucwp_register_widgets', function (WidgetCatalog $catalog) use ($bundle) {
        $catalog->register(new WidgetDefinition(
            'my-heatmap',                       // type: stored in widgets, never rename it
            __('Heatmap', 'my-crypto-addon'),   // label
            'heatmap',                          // view: build/widgets/heatmap/index.js
            '',                                 // card design, if the view has several
            ['count', 'default_currency', 'live_prices'], // the settings it uses (WidgetFields::NAMES)
            [WidgetDefinition::DATA_COINS],     // the data it needs
            false,                              // a Pro type?
            true,                               // usable on this site?
            $bundle
        ));
    });
});
```

The type then appears everywhere: the widget form, the block inserter (as its own block), Elementor, and the shortcode.

The data a widget asks for arrives in its `data-ucw-props`, under the name of the constant:

| Constant | Prop | What it is |
| --- | --- | --- |
| `DATA_COINS` | `coins` | Market data for the chosen coins, or the top 100. |
| `DATA_CURRENCY_RATES` | `default_currencies_rate` | One unit of the widget's currency in each chosen currency. |
| `DATA_CURRENCY_SYMBOLS` | `default_currencies_symbol` | The symbol of each chosen currency. |
| `DATA_GLOBAL` | `global_market` | The whole market: value, 24h volume, change, Bitcoin and Ether share, active coins. |
| `DATA_TRENDING` | `trending_coins` | The coins people look at most now: id, name, symbol, image, rank, price in US dollars, 24h change. |

A price provider (`ucwp_price_provider`) must answer all of these; `globalMarket()` and `trending()` return an empty array
when it has no figures, and the widget shows its empty state.

The build folder must hold `vendors/index.js` (shared code), `widgets/{view}/index.js` and, if the widget has its own
layout, `widgets/{view}.css`. The Pro add-on's `webpack.config.js` is a working example; it imports this plugin's shared
code as `@core/…`.

## Writing a widget

A widget is one React component in `react/widgets/{view}/index.tsx` (here, or in an add-on importing `@core/…`):

```tsx
import React from "react";
import { __ } from "@wordpress/i18n";
import { CoinIdentity, Notice, Price, Surface } from "@core/ds";
import ReactRender from "@core/helper-components/react-wrapper";
import { useLiveCoins } from "@core/live/useLiveCoins";

/** Coins as a grid of prices, kept live. */
export const Heatmap = ({ coins, settings }) => {
    // The page's coins, as many as the count setting, kept live unless live_prices is off.
    const list = useLiveCoins(coins, settings);

    if (list.length === 0) {
        return <Surface><Notice>{__("Prices are not available right now.", "my-crypto-addon")}</Notice></Surface>;
    }
    return (
        <Surface>
            {list.map((coin) => (
                <div key={coin.id}>
                    <CoinIdentity name={coin.name} symbol={coin.symbol} image={coin.image} />
                    <Price value={coin.current_price} currency={settings.default_currency} />
                </div>
            ))}
        </Surface>
    );
};

ReactRender(Heatmap);   // draws it into every element that asks for this view
```

Rules, in full in the [frontend standards](frontend-standards.md):

- build the UI from `react/ds` and style it with the theme's tokens, never a raw colour;
- a widget's own stylesheet holds layout only, spaced with `gap`;
- show a deliberate state for "no data" and "failed", never an empty box;
- follow live prices through `useLiveCoins()` (or `useLivePrices()` with `liveOn(settings.live_prices)`), so the widget's
  **Live prices** setting is respected;
- export the component so a test can render it, and add it to the tests that render every widget.

In this repository, a new built-in widget also needs its type in `WidgetCatalog::builtIn()`, and, if it should be Pro in
the tiered edition, its view in `edition.json`'s `proTierViews` (see [Editions](editions.md)).

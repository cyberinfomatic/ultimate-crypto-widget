# Developer guide

How Ultimate Crypto Widget is put together, and how to extend it without editing it.

## How a widget gets on the page

1. Something asks for a widget: the `[ucwp_widget]` shortcode, the `ucwp/widget` block or the
   Elementor element. All three end in the same call, `Shortcodes::render('widget', $attributes)`.
2. `WidgetShortcode` works out the settings: from a saved widget (`id="12"`) or from the attributes.
3. `WidgetRenderer` looks the type up in the `WidgetCatalog`, fetches its data through the
   `PriceProvider`, and hands both to `ReactMount`.
4. `ReactMount` prints one element and loads that widget's script and style:

   ```html
   <div class="ucw-widget ucw-widget--coin-marquee" data-ucw-view="coin-marquee"
        data-ucw-theme="light" data-ucw-api="https://example.org/wp-json/ultimate-crypto-widget/v1/"
        data-ucw-props="{…}">Loading...</div>
   ```

5. In the browser the widget's script draws its React component into every element with its
   `data-ucw-view`, then keeps prices current from a live feed.

Nothing is loaded on a page that shows no widget.

## Layout

| Path | What is there |
| --- | --- |
| `src/Providers/` | One WPToolkit service provider per concern. `src/boot.php` lists them. |
| `src/Widgets/` | Widget types (`WidgetCatalog`, `WidgetDefinition`, `WidgetBundle`), settings (`WidgetFields`, `WidgetSchema`), rendering, appearance. |
| `src/Admin/` | The widget editor, preview, menu and screens. |
| `src/Blocks/`, `src/Integrations/` | The block; other plugins we fit into (Elementor). |
| `src/Data/`, `src/Http/` | The price source; REST controllers. |
| `views/` | Admin templates. |
| `react/ds/` | The design system: tokens, components, formatters. |
| `react/widgets/` | One folder per widget. |
| `react/data/`, `react/live/` | REST client; live prices. |
| `react/admin/`, `react/block/` | The editor's script; the block. |
| `lib/wptoolkit/` | The bundled, renamed copy of WPToolkit. Do not edit; see `lib/README.md`. |

Hooks go through the toolkit's `HookRegistrar`, assets through `AssetManager`, settings through
`Settings`, admin pages through `Pages`. Do not call `add_action`, `wp_enqueue_*`,
`register_setting` or `add_menu_page` directly.

## What must never change

These are in users' databases and content:

- the post type `ucwp_widget` and its meta keys `ucwp_widget_{setting}` (see `WidgetFields::NAMES`);
- the shortcode tag `ucwp_widget` and its attribute names;
- widget type slugs (`coin-marquee-1`, `crypto-price-table-1`, …);
- option names `ultimate_crypto_widget_{setting}`;
- REST routes under `ultimate-crypto-widget/v1`;
- the block name `ucwp/widget` and its attributes;
- admin page slugs (`ultimate-crypto-widget`, `…-quick-widget`, `…-settings`).

`tests/Integration/DataCompatibilityTest.php` and the snapshots in `tests/snapshots/` hold you to it.

## Hooks

| Hook | Kind | Use it to |
| --- | --- | --- |
| `ucwp_loaded` | action | Run code once the plugin has started. Receives the toolkit `Application`. |
| `ucwp_register_widgets` | action | Add widget types, or replace a listed one. Receives the `WidgetCatalog`. |
| `ucwp_price_provider` | filter | Replace the price source with another `Data\PriceProvider`. |
| `ucwp_widget_style` | filter | Set theme variables on every widget: `['--ucw-radius' => '4px']`. Only `--ucw-*` names with plain values are printed. |
| `ucwp_widget_count_choices` | filter | Change how many coins a widget may be set to show (default `[5, 10]`). |
| `ucwp_upgrade_url` | filter | Change where "See Pro" links lead. |
| `ucwp_show_upgrade_notice` | filter | Return `false` to hide the suggestions to get Pro. They are off in the all-free edition. |

Add your callbacks no later than `plugins_loaded`: the catalogue is filled the first time it is asked
for, which is on `init`.

## Editions

Which widgets are free is one value in `edition.json`: `all-free` (every widget, the current release) or
`tiered` (the Pro-tier types locked until the Pro add-on unlocks them). `Edition` reads it; the release build
and the Pro add-on's build read it too. `WidgetCatalog::withEdition()` builds a catalogue for either, for tests and
add-ons. See [editions.md](editions.md).

## Adding widget types from another plugin

An add-on brings its own built scripts and says where they are with a `WidgetBundle`. The bundle
loads them through the add-on's own toolkit asset manager, so they get the add-on's handles, version,
translations and right-to-left stylesheet.

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
            ['count', 'default_currency'],      // the settings it uses (WidgetFields::NAMES)
            [WidgetDefinition::DATA_COINS],     // the data it needs
            false,                              // a Pro type?
            true,                               // usable on this site?
            $bundle
        ));
    });
});
```

The data a widget asks for is delivered in its `data-ucw-props`, under the name of the constant:

| Constant | Prop | What it is |
| --- | --- | --- |
| `DATA_COINS` | `coins` | Market data for the chosen coins, or the top 100. |
| `DATA_CURRENCY_RATES` | `default_currencies_rate` | One unit of the widget's currency in each chosen currency. |
| `DATA_CURRENCY_SYMBOLS` | `default_currencies_symbol` | The symbol of each chosen currency. |
| `DATA_GLOBAL` | `global_market` | The whole market: value, 24h volume, change, Bitcoin and Ether share, active coins. |
| `DATA_TRENDING` | `trending_coins` | The coins people look at most now: id, name, symbol, image, rank, price in US dollars, 24h change. |

A price provider (`ucwp_price_provider`) must answer all of these; `globalMarket()` and `trending()` return an empty
array when it has no figures, and the widget shows its empty state.

The build folder must hold `vendors/index.js` (shared code), `widgets/{view}/index.js` and, if the
widget has its own layout, `widgets/{view}.css`. The Pro add-on's `webpack.config.js` is a working
example; it imports this plugin's shared code as `@core/…`.

## Writing a widget

```tsx
import React from "react";
import { __ } from "@wordpress/i18n";
import { CoinIdentity, Notice, Price, Surface } from "@core/ds";
import ReactRender from "@core/helper-components/react-wrapper";
import { useLiveCoins } from "@core/live/useLiveCoins";

export const Heatmap = ({ coins, settings }) => {
    const list = useLiveCoins(coins, settings);          // the page's coins, kept live

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

Rules, in full in [standards/frontend.md](standards/frontend.md):

- build the UI from `react/ds` and style it with the theme's tokens, never a raw colour;
- a widget's own stylesheet holds layout only, spaced with `gap`;
- show a deliberate state for "no data" and "failed", never an empty box;
- export the component so a test can render it, and add it to the tests that render every widget.

## The settings schema

`WidgetSchema` is the one description of a widget's settings: what each is, what it may hold, and
which types use it. The widget editor (a toolkit `MetaBox`), the block's sidebar and the Elementor
element are all drawn from it, so a new setting appears in all three. Its names and stored shapes
come from `WidgetFields`. Coins and currencies are the toolkit's own multiple-choice field: one meta row
holding an array, shown as a chip picker with search when the list is long. Everywhere outside the database
(the shortcode's `coins="a,b"`, the widget's data, REST) they are text, `"a,b,c"`; `WidgetFields::listToString()`
converts, and reads either form. How many may be chosen is a field setting (`WidgetSchema::MAX_CHOICES`), enforced
when saving by `WidgetEditor::enforceLimits()`, because the toolkit checks each value, not how many.

The settings form lists the top coins, so it is built only on the widget screens
(`WidgetEditor::needsForm()`), never on a visitor's page view.

Stored data changes go through a migration (`src/Migrations/`, registered in `src/boot.php`), never a branch on a
"data version".

## REST API

All under `ultimate-crypto-widget/v1`. Every route states who may call it, validates its input and
is rate limited.

| Route | Who | Returns |
| --- | --- | --- |
| `GET coins` | anyone, 60/min | Market data for the top coins, or for `coins=bitcoin,ethereum`. |
| `GET coin-info?coin_id=` | anyone, 30/min | One coin's details. |
| `GET coin-chart-data?coin_id=&days=&currency=` | anyone, 30/min | A coin's price history. |
| `POST load-shortcode` | `edit_posts` | A widget's HTML, for previews. |

They are public on purpose: widgets call them from visitors' browsers, often on cached pages. The
limits protect the site's CoinGecko quota. API keys never appear in a response, a script or a log.

## Working on the code

```bash
composer install && npm ci
```

```bash
npm run build
```

```bash
composer phpunit
```

```bash
npm run typecheck && npm run test:js
```

```bash
composer phpcs
```

The PHP tests need a MySQL database (`ucw_tests` by default; see `tests/wp-tests-config.php`). No
test reaches a real API: `tests/Support/HttpStub.php` answers every outgoing request from fixtures.
Refresh the snapshots with `UPDATE_SNAPSHOTS=1 composer phpunit`, and only for a change you intend.

Commits follow [Conventional Commits](https://www.conventionalcommits.org/).

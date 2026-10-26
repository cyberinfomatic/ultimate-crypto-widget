# Architecture

How Ultimate Crypto Widget is put together. To extend it from another plugin, see [Extending](extending.md).

## How a widget gets on the page

1. Something asks for a widget: the `[ucwp_widget]` shortcode, the `ucwp/widget` block or an Elementor element. All three
   end in the same call, `Shortcodes::render('widget', $attributes)`.
2. `WidgetShortcode` works out the settings: from a saved widget (`id="12"`, with any attributes laid over it, except the
   type) or from the attributes alone.
3. `WidgetRenderer` looks the type up in the `WidgetCatalog`, fills in defaults (`WidgetFields::DEFAULTS`), fetches the
   data the type asks for through the `PriceProvider`, and hands both to `ReactMount`.
4. `ReactMount` prints one element and loads that widget's script and style:

   ```html
   <div class="ucw-widget ucw-widget--coin-marquee" data-ucw-view="coin-marquee"
        data-ucw-theme="light" data-ucw-api="https://example.org/wp-json/ultimate-crypto-widget/v1/"
        data-ucw-props="{…}">Loading...</div>
   ```

5. In the browser the widget's script draws its React component into every element with its `data-ucw-view`. While the
   widget's **Live prices** setting is on, it keeps prices current from a live feed (below).

Nothing is loaded on a page that shows no widget.

## Code layout

| Path | What is there |
| --- | --- |
| `src/boot.php` | Starts the WPToolkit application and lists the service providers, in order. |
| `src/Providers/` | One service provider per concern: settings, assets, widgets, admin, REST, integrations. |
| `src/Widgets/` | Widget types (`WidgetCatalog`, `WidgetDefinition`, `WidgetBundle`), the edition (`Edition`), settings (`WidgetFields`, `WidgetSchema`), saving and reading (`WidgetRepository`), drawing (`WidgetShortcode`, `WidgetRenderer`, `ReactMount`), appearance. |
| `src/Admin/` | The widget editor, the preview, the admin screens, cache tools. |
| `src/Blocks/` | The `ucwp/widget` block. |
| `src/Integrations/` | Elementor. |
| `src/Data/` | The price source (`PriceProvider`, `CoinGeckoProvider`). |
| `src/Http/` | REST controllers. |
| `src/Migrations/` | Changes to stored data, run once each. |
| `views/` | Admin templates. |
| `react/widgets/` | One folder per widget view, every widget's code (the Pro tier's included). |
| `react/parts/` | Pieces several widgets share, such as the paged coin table and the chart card. |
| `react/ds/` | The design system: tokens, components, number and price formatting. |
| `react/live/` | Live prices: the exchange feeds, the fallback to polling, and the hooks widgets use. |
| `react/data/` | The REST client. |
| `react/admin/`, `react/block/` | The widget editor's script; the block. |
| `lib/wptoolkit/` | The bundled, renamed copy of WPToolkit. Do not edit it; see `lib/README.md`. |

## Widget types and views

A **type** is what a site owner picks and what is stored (`coin-marquee-2`). A **view** is the code that draws it
(`react/widgets/coin-marquee-pro`). Several types can share a view with a different card design: `WidgetDefinition`
holds the type, its label, its view, its card, the settings it uses and the data it needs. `WidgetCatalog::builtIn()`
lists the 24 built-in types; add-ons add more through `ucwp_register_widgets`.

Whether a type is free or Pro depends on the edition: see [Editions](editions.md).

## The settings schema

`WidgetSchema` is the one description of a widget's settings: what each is, what it may hold, and which types use it.
The widget editor (a toolkit `MetaBox`), the block's sidebar and the Elementor element are all drawn from it, so a new
setting appears in all three. Names, defaults and stored shapes come from `WidgetFields`.

How each setting is shown, by its `control` in `WidgetSchema::toArray()`:

| Control | Widget form | Block sidebar | Elementor |
| --- | --- | --- | --- |
| `select` | select | `SelectControl` | select |
| `number`, `text` | input | `TextControl` | number, text |
| `list` (coins, currencies) | the toolkit's chip picker | `FormTokenField` (search, chips) | select2 |
| `toggle` (live prices) | checkbox | `ToggleControl` | switcher |

Things to know when adding or changing a setting:

- **Add it** to `WidgetFields::NAMES` (and `DEFAULTS`), build its field in `WidgetSchema::fields()`, and add it to the
  types that use it in `WidgetCatalog::builtIn()`. Its meta key is `ucwp_widget_{name}`, its shortcode attribute `{name}`.
- **Lists** (coins, currencies) are one meta row holding an array. Everywhere else (shortcode, widget data, REST) they are
  text, `"a,b,c"`; `WidgetFields::listToString()` converts and reads either form. How many may be chosen is a field
  setting (`WidgetSchema::MAX_CHOICES`), enforced on save by `WidgetEditor::enforceLimits()`.
- **Checkboxes** store `"1"` or `"0"`. An unticked box posts nothing, so the toolkit adds a hidden `"0"`; Elementor stores
  an empty value for a switch that is off, which `CryptoWidget::shortcodeAttributes()` turns into `"0"`.
- **Defaults** apply when a widget has no stored value, so a new setting's default is what every existing widget gets.
- The form lists the top coins, so it is built only on the widget screens (`WidgetEditor::needsForm()`), never on a
  visitor's page view.
- Changing what is already stored goes through a migration (`src/Migrations/`, registered in `src/boot.php`).

## The block

`ucwp/widget` is one dynamic block: the post stores only `widgetId` or the block's own `settings`, never markup, so a
redesign needs no block migration. In the editor it is offered as one **variation per widget type**, in its own
**Crypto Widgets** inserter section (`react/block/values.ts`, `variationsFor()`). The preview in the editor is the real
widget, drawn in a frame by `Admin\Preview`.

## Live prices

`react/live/` keeps a widget's prices current while it is on screen:

1. `useLiveCoins()` takes the coins the page was drawn with and the widget's settings. If `live_prices` is off it follows
   nothing, and no connection is opened.
2. `useLivePrices()` connects to a public exchange stream through `cascade()`: Coinbase first, then Kraken, then Binance,
   then polling this site's REST API about once a minute.
3. A live price is laid over the page's figure only if it is believable for that coin (`livePriceAgrees()`), because
   tickers are not unique across exchanges.

Every widget has the `live_prices` setting. Market Overview shows market totals, which no price stream carries, so
`useLiveMarket()` refreshes them from the `global-market` route once a minute instead. A new widget must respect the
setting too.

## Getting started and the review request

`Admin\ReviewNotice` shows administrators (`manage_options`) one notice, on the Dashboard and the plugin's own screens
only, never on the front end. It says one of two things, never both:

| The site | The notice | Buttons |
| --- | --- | --- |
| Has not used the plugin yet | **Get started**: how to add a first widget. | **Add a widget** (the Add widget screen), **Dismiss** (for good). |
| Has used it | **A review on WordPress.org**, straight away. | **Leave a review** and **I already did** end it for good; **Still new to it** asks again in 3 days (`SNOOZE_DAYS`). |

**"Used"** is recorded once, in the `ucwp_used_at` option, by an action in the admin from someone who can create
widgets (`edit_posts`), never by a visitor:

- saving a widget (not the auto-draft that opening "Add widget" creates);
- opening a widget preview (`admin_post_ucwp_preview`, before the preview prints);
- copying a shortcode (the editor script calls `wp_ajax_ucwp_mark_used` with a nonce; it sends nothing else);
- or a widget already on a published page: a saved widget, the block, the shortcode or an Elementor element (checked at
  most once a day, for sites that used the plugin before this notice existed).

Choices are stored per user (`ucwp_review_notice`, `ucwp_start_notice` user meta) through a nonce-checked `admin-post`
action.

## Price data on the server

`Data\PriceProvider` is the interface; `CoinGeckoProvider` is the implementation. Every answer is cached for the cache
interval (a site-wide setting), and the CoinGecko key, if any, never leaves the server. Another plugin can replace the
provider through the `ucwp_price_provider` filter.

## REST API

All under `ultimate-crypto-widget/v1`. Every route states who may call it, validates its input and is rate limited.

| Route | Who | Returns |
| --- | --- | --- |
| `GET coins` | anyone, 60/min | Market data for the top coins, or for `coins=bitcoin,ethereum`. |
| `GET coin-info?coin_id=` | anyone, 30/min | One coin's details. |
| `GET coin-chart-data?coin_id=&days=&currency=` | anyone, 30/min | A coin's price history. |
| `GET global-market?currency=` | anyone, 30/min | The whole market's figures, for Market Overview's refresh. |
| `POST load-shortcode` | `edit_posts` | A widget's HTML, for previews. |

They are public on purpose: widgets call them from visitors' browsers, often on cached pages. The limits protect the
site's CoinGecko quota. API keys never appear in a response, a script or a log.

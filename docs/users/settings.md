# Settings

## A widget's settings

Each widget type shows only the settings it uses ([The widgets](widgets.md) lists which). They are the same in the
widget form, the block sidebar and Elementor.

| Setting | What it does | Choices |
| --- | --- | --- |
| Widget type | Which widget this is. | One of the 24 types. |
| Number of coins | How many coins to show. | Top 5, 10, 25, 50 or 100. Default: 10. |
| Coins | Which coins to show. Leave empty for the top coins by market value. | Any of the top 100 coins. |
| Currency | The currency prices are shown in. | 30 currencies, US dollars by default. |
| Other currencies | Up to three more, offered as tabs or choices beside the widget's own currency. | Multi Currencies Tab, Crypto Converter. |
| Widget width | How wide the widget is. | Pixels (`400`) or a share of the space it sits in (`100%`). Default: `100%`. |
| Card width (px) | The least width of one coin in a marquee. | 100 to 500. Default: 157. |
| Speed | How fast a marquee or slider moves. Higher is faster. | 100 to 5000. Default: 3000. |
| Layout | Price labels side by side or stacked. | Side by side, stacked. |
| Days of price history | How far back a chart goes. | 1 to 365. Default: 7. |
| Price history interval | How often a chart has a point. | Daily. |
| Search box hint | The grey text in a table's search box. | Up to 60 characters. Empty for the default. |
| Colours | Light, or dark for this widget whatever the site's choice. | Light, dark. |
| Live prices | Keep prices moving while the page is open. Off, the widget shows the prices from when the page loaded and visitors' browsers contact no exchange. | On (default), off. |

## Shortcode attributes

A saved widget needs only its id: `[ucwp_widget id="12"]`. Any attribute you add overrides that widget's own setting, except its type: `[ucwp_widget id="12" live_prices="0"]`.

Without an id, give the type and any settings: `[ucwp_widget type="crypto-heatmap" count="50" live_prices="0"]`.
Settings you leave out use their defaults.

| Attribute | Setting | Example |
| --- | --- | --- |
| `id` | A widget saved under Crypto Widgets | `id="12"` |
| `type` | Widget type | `type="coin-marquee-1"` |
| `count` | Number of coins | `count="25"` |
| `coins` | Coins, by CoinGecko id, comma-separated | `coins="bitcoin,ethereum,solana"` |
| `default_currency` | Currency | `default_currency="eur"` |
| `currencies` | Other currencies, comma-separated | `currencies="gbp,ngn"` |
| `parent_width` | Widget width | `parent_width="600px"` |
| `card_width` | Card width | `card_width="180"` |
| `speed` | Speed | `speed="4000"` |
| `orientation` | Layout | `orientation="vertical"` |
| `no_of_days` | Days of price history | `no_of_days="30"` |
| `data_interval` | Price history interval | `data_interval="daily"` |
| `search_placeholder` | Search box hint | `search_placeholder="Find a coin"` |
| `dark_mode` | Colours | `dark_mode="true"` |
| `live_prices` | Live prices | `live_prices="0"` (also `no`, `false`, `off`) |

A coin's CoinGecko id is the last part of its address on coingecko.com: `coingecko.com/en/coins/bitcoin` is `bitcoin`.

## Site-wide settings

Under **Crypto Widgets → Settings**.

### Price data

- **CoinGecko API key (optional).** The widgets work without one. A free key raises how many requests your site may
  make; add one if prices stop updating on a busy site.
- **Cache interval.** How many minutes prices are kept before they are fetched again. Default: 10. Live prices in
  visitors' browsers do not wait for this.
- **Clear cached prices** fetches fresh prices now.

### Appearance

- **Colours.** Light, dark, or match each visitor's device. A widget whose own **Colours** setting is dark stays dark.
- **Accent colour.** Used for highlights such as the selected tab. A colour code like `#4f46e5`; leave empty for the
  default.

## Where prices come from

- **CoinGecko** supplies prices and market data. Your server fetches them and keeps them for the cache interval. No
  visitor information is sent.
- **Coinbase, then Kraken, then Binance** supply live updates while a widget is on screen, if its **Live prices** is on.
  The visitor's browser connects to one of their public price streams. If none can be reached, the widget asks your own
  site for fresh prices about once a minute instead.

The readme's "External services" section lists exactly what each service receives.

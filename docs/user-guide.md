# User guide

## Putting a widget on a page

There are three ways. They all draw the same widgets.

### The block

1. In the block editor, add the **Crypto Widget** block.
2. Choose a **widget type**, or pick a widget you have saved.
3. Change its settings in the block sidebar. The block shows the real widget as you go.

### A shortcode

1. Go to **Crypto Widgets → Widgets → Add widget**.
2. Give it a title, choose a type and its settings. The preview follows your changes.
3. Publish it, then copy the shortcode from the **Shortcode** box, for example `[ucwp_widget id="12"]`.
4. Paste it into any page, post or text widget.

To try a widget without saving one, use **Crypto Widgets → Quick widget**. It gives you a shortcode
that carries its own settings, such as `[ucwp_widget type="coin-marquee-1" count="5"]`.

### Elementor

Drag the **Crypto Widget** element onto the page and choose its type and settings in the panel.

## The widgets

| Widget | What it shows |
| --- | --- |
| Crypto Price Table | Ranked coins with price and 24-hour change. |
| Coin Marquee | A scrolling strip of coins and prices. |
| Crypto Price Label | One card per coin, side by side or stacked. |
| Multi Currencies Tab | A price list visitors can switch between currencies. |

Types marked **(Pro)** need the Pro add-on.

## Settings a widget can have

Each type shows only the settings it uses.

| Setting | Meaning |
| --- | --- |
| Number of coins | How many coins to show. |
| Coins | Which coins. Leave empty for the top coins by market value. |
| Currency | The currency prices are shown in. |
| Other currencies | Up to three more, offered as tabs (Multi Currencies Tab). |
| Widget width | In pixels (`400`) or as a share of the space (`100%`). |
| Card width | The least width of one item in the marquee. |
| Speed | How fast the marquee scrolls. Higher is faster. |
| Layout | Cards side by side or stacked. |
| Colours | Light, or dark for this widget whatever the site's choice. |

## Site-wide settings

Under **Crypto Widgets → Settings**.

**Price data**

- **CoinGecko API key (optional).** The widgets work without one. A free key raises the request
  limit; add one if prices stop updating on a busy site.
- **Cache interval.** How many minutes prices are kept before they are fetched again. Live prices in
  the visitor's browser are not affected by this.
- **Clear cached prices** fetches fresh prices now.

**Appearance**

- **Colours.** Light, dark, or match the visitor's device.
- **Accent colour.** Used for highlights such as the selected tab. Leave empty for the default.

With the Pro add-on, **Crypto Widgets → Pro Styles** adds typeface, the colours for a rise and a
fall, corner radius and spacing.

## Where prices come from

Prices and market data come from CoinGecko, fetched by your server and kept for the cache interval.
While a widget is on the screen, the visitor's browser also follows a public price stream (Coinbase,
then Kraken, then Binance) so prices move without a reload. If none can be reached, the widget asks
your own site for fresh prices instead. See the readme's "External services" section for what each
service receives.

## If something looks wrong

| What you see | What to do |
| --- | --- |
| "Prices are not available right now." | CoinGecko could not be reached or its limit was hit. Wait a minute, or add an API key. |
| A widget shows nothing for visitors | The widget is a draft, or its type needs the Pro add-on. Editors see a note explaining which. |
| My own CSS no longer applies | Version 1.0 has new class names (`ucw-…`). Update your CSS, or use the appearance settings. |
| Prices do not move | The visitor's network blocks the price streams. The widget then refreshes from your site about once a minute. |

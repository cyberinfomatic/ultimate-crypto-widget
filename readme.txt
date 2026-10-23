=== Ultimate Crypto Widget ===
Contributors: heisenejii
Tags: crypto, cryptocurrency, finance, widget, real-time price ticker
Requires at least: 6.5.5
Tested up to: 7.1
Requires PHP: 8.2
Stable tag: 1.0.0
License: GPL-2.0-or-later
Donate link: http://ultimatecryptowidget.com
License URI: http://www.gnu.org/licenses/gpl-2.0.txt

Live cryptocurrency prices: tables, a ticker, price cards and a multi-currency list. Use it as a block, a shortcode or in Elementor.

== Description ==

Ultimate Crypto Widget shows live cryptocurrency prices on your WordPress site. Pick a widget, choose its coins and currency, and place it with the block editor, a shortcode or Elementor. Prices update on the page as the market moves.

**24 widgets, all free**

* **Tickers** – coin marquees in three designs, price labels in three designs, and price sliders.
* **Tables** – a ranked price table, a searchable and paged table with market cap, volume and supply, a table of change over a day, a week and a month, and a multi-currency list.
* **Charts** – a coin's price history over the days you choose, a market heatmap, and an overview of the whole market.
* **Cards** – price cards, price cards with charts, coin information cards, and an accordion that opens to show a chart.
* **Tools** – compare two to four coins side by side, a crypto converter, the top gainers and losers, trending coins, and a holdings calculator.

**What you get**

* **Three ways to place a widget**: the Crypto Widget block, the `[ucwp_widget]` shortcode, or the Crypto Widget element in Elementor.
* **Live prices** without a page reload.
* **No account needed**: prices come from CoinGecko's public API. A free CoinGecko key is optional and only raises the request limit for busy sites.
* **Light, dark or automatic** colours, and your own accent colour, for every widget at once.
* **30 display currencies** and the top 100 coins to choose from.
* **A live preview** while you set a widget up.
* **Nothing loaded where it is not used**: a page without a widget loads no script or style from this plugin.
* **Accessible and translatable**: keyboard and screen-reader friendly, right-to-left ready, prices and numbers formatted for the page's language.

**Source code**

The unminified source of every script and style is in the plugin's [GitHub repository](https://github.com/cyberinfomatic/ultimate-crypto-widget/), under `react/`. Build it with `npm ci && npm run build`.

== External services ==

This plugin shows market data, so it has to fetch it. These are all the services it contacts.

**CoinGecko** – coin prices, market data and currency rates.
Your *server* asks CoinGecko when a widget is drawn and keeps the answer for the number of minutes you set (10 by default). The requests contain the coins and currency a widget shows and, if you saved one, your CoinGecko API key. No visitor information is sent.
[Terms](https://www.coingecko.com/en/terms) · [Privacy policy](https://www.coingecko.com/en/privacy)

**Coinbase, Kraken and Binance** – live price updates.
A *visitor's browser* opens a connection to one of these exchanges' public price streams while a widget is on the page, so prices can move without a reload. Coinbase is tried first, then Kraken, then Binance. The connection says which coins the widget shows; as with any connection, the exchange sees the visitor's IP address. No account, cookie or personal data from your site is sent. If none can be reached, the widget asks your own site for fresh prices instead.
Coinbase: [terms](https://www.coinbase.com/legal/user_agreement) · [privacy](https://www.coinbase.com/legal/privacy)
Kraken: [terms](https://www.kraken.com/legal) · [privacy](https://www.kraken.com/legal/privacy)
Binance: [terms](https://www.binance.com/en/terms) · [privacy](https://www.binance.com/en/privacy)

Coin logos are loaded by the visitor's browser from the addresses CoinGecko provides.

== Installation ==

1. In your dashboard go to **Plugins → Add New**, search for **Ultimate Crypto Widget**, then **Install** and **Activate**.
2. Open **Crypto Widgets** in the dashboard menu.
3. Create a widget, or add the **Crypto Widget** block straight to a page.

== Frequently Asked Questions ==

= Do I need an API key? =

No. The widgets work without one. If your site is busy and prices stop updating, add a free CoinGecko key under **Crypto Widgets → Settings** to raise the limit.

= How do I put a widget on a page? =

Any of three ways. In the block editor, add the **Crypto Widget** block and choose a saved widget or set one up in the block. With a shortcode, create a widget under **Crypto Widgets → Widgets** and paste the shortcode it gives you, for example `[ucwp_widget id="12"]`. In Elementor, drag in the **Crypto Widget** element.

= What is the Quick widget screen? =

It builds a shortcode that carries its own settings, such as `[ucwp_widget type="coin-marquee-1" count="5"]`, so you can try a widget without saving one.

= Can I change the colours? =

Yes. Under **Crypto Widgets → Settings → Appearance** choose light, dark or "match the visitor's device", and an accent colour. A single widget can also be set to dark on its own.

= I am updating from 0.x. Will my widgets still work? =

Yes. Saved widgets, their settings and every `[ucwp_widget]` shortcode keep working as they are. The widgets have a new design; if you wrote your own CSS against the old `ucwp-…` class names, it will need updating to the new `ucw-…` ones.

= Does a page without a widget load anything from this plugin? =

No. Scripts and styles are loaded only on pages that show a widget.

= Is it translatable? =

Yes, including the text inside the widgets. It also supports right-to-left languages. See `docs/translating.md` in the source repository.

= Where do I get help? =

In the [support forum](https://wordpress.org/support/plugin/ultimate-crypto-widget/).

== Screenshots ==

1. Some of the widgets: price table, coin marquee, price cards and multi-currency list.
2. The same widgets in the dark theme.
3. Setting a widget up, with the live preview.
4. The Crypto Widget block in the editor.

== Changelog ==

= 1.0.0 =

A rebuild of the plugin.

* New: a **Crypto Widget block** for the block editor, with a live preview.
* New: a **Crypto Widget element for Elementor**.
* New: a modern design for every widget, with light, dark and automatic colours and an accent colour you choose.
* New: a live preview while you set a widget up, and a redesigned admin.
* New: the widgets work **without any API key**. Open Exchange Rates is no longer used.
* New: live prices now come from Coinbase, then Kraken, then Binance, so they also work for visitors in the United States.
* New: every text can be translated, including inside the widgets; right-to-left languages are supported; prices follow the page's language.
* Security: closed a hole that let a visitor run shortcodes through the plugin's REST API. Every REST route now checks who is asking, validates its input and is rate limited.
* Security: draft widgets are no longer visible to visitors.
* Performance: scripts and styles load only on pages that show a widget; the scripts are much smaller.
* Fix: several widgets on one page no longer overwrite each other.
* Fix: prices in a currency other than US dollars were converted with a rounded rate.
* Fix: a coin's 24-hour volume could be replaced by a wrong figure once live prices arrived.
* Change: the widgets' HTML and class names are new (`ucw-…`). Custom CSS written for the old ones needs updating.
* New: **every widget is free**, including the ones that were Pro: price history charts, searchable tables, sliders, coin cards, the accordion and more card designs. Widgets saved with the old Pro plugin keep working with this plugin alone.
* New: seven widgets: compare coins, a crypto converter, top gainers and losers, a market overview, a heatmap, trending coins and a holdings calculator.
* Requires WordPress 6.5.5 and PHP 8.2.

= 0.1.5 =
* Tested up to WordPress 6.8
* Requires PHP 8.2 or higher
* Security: REST API permission callbacks, admin capability checks, and safer unserialize handling
* Fix: Correct plugin asset URLs and admin notice HTML rendering

= 0.1.4 =
* Supported dark mode in accordion
* Added icon to accordion
* Added decimal place rounder to PricePercentage component to round off the decimal places to 2 and for neatness.
* New Widget:
	- Multi Currencies Tab Widget
* Dev Changes:
 	- Separate API module
 	- added new props tp roundup decimal places in the PricePercentage component
 	- Added Multi Currencies selector in the widget settings


= 0.1.3 =
* Support Wordpress version 6.6.2
* Added support for Binance Stream API to fetch real-time cryptocurrency data using Binance API.

= 0.0.2 =
* Support Wordpress version 6.6.1

= 0.0.1 =
* Initial release.

== Upgrade Notice ==

= 1.0.0 =
A rebuild with a new design, a block and Elementor element, and an important security fix. Your saved widgets and shortcodes keep working. Custom CSS for the old widgets will need updating.

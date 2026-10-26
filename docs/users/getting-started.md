# Getting started

## Install

1. In your WordPress dashboard go to **Plugins → Add New**, search for **Ultimate Crypto Widget**, then **Install**
   and **Activate**.
2. A **Crypto Widgets** menu appears in the dashboard.

You need WordPress 6.5.5 or newer and PHP 8.2 or newer. No account or API key is needed: prices come from
CoinGecko's public service.

## Put a widget on a page

There are three ways. They all draw the same widgets, with the same settings.

### In the block editor

1. Open the block inserter (the **+** button) and find the **Crypto Widgets** section. Every widget has its own
   block there: Crypto Heatmap, Coin Marquee, Crypto Price Table and the rest. You can also search for one by name.
2. Click a widget. It is placed with its usual settings and shows live on the page.
3. Change its settings in the block sidebar: the number of coins, which coins, the currency, live prices and so on.
   The block shows the real widget as you go.

To reuse a widget you have saved (see below), add the general **Crypto Widget** block and pick it under **Widget**.

### With a shortcode

1. Go to **Crypto Widgets → Widgets → Add widget**.
2. Give it a title, choose a **Widget type** and its settings. The preview follows your changes.
3. **Publish** it, then copy its shortcode from the **Shortcode** box, for example `[ucwp_widget id="12"]`.
4. Paste the shortcode into any page, post, or text widget.

Change the saved widget later and every page that uses its shortcode changes with it.

To try a widget without saving it, use **Crypto Widgets → Quick widget**. It gives you a shortcode that carries its own
settings, such as `[ucwp_widget type="coin-marquee-1" count="5"]`. Every attribute is listed in
[Settings](settings.md#shortcode-attributes).

### In Elementor

Open the Elementor panel and find the **Crypto Widgets** group. Each widget is its own element; drag one onto the page
and set it up in the panel.

## Next

- [The widgets](widgets.md): what each of the 24 shows.
- [Settings](settings.md): every setting, and the site-wide ones under **Crypto Widgets → Settings**.
- [Troubleshooting](troubleshooting.md), if something does not look right.

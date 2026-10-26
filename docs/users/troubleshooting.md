# Troubleshooting

| What you see | Why, and what to do |
| --- | --- |
| "Prices are not available right now." | CoinGecko could not be reached, or your site used up its free requests. Wait a minute. On a busy site, add a free CoinGecko API key under **Crypto Widgets → Settings**. |
| A widget shows nothing to visitors | The saved widget is a draft, or its type cannot be used on this site. Editors see a note on the page explaining which. Publish the widget. |
| Prices do not move | Either the widget's **Live prices** is off, or the visitor's network blocks the exchanges' price streams. In the second case the widget refreshes from your site about once a minute. |
| Prices are old after I changed something | Prices are kept for the cache interval. Use **Clear cached prices** under **Crypto Widgets → Settings**. |
| My own CSS no longer applies | Version 1.0 gave the widgets new HTML and class names (`ucw-…`). Update your CSS, or use the colour and accent settings. |
| The block says it could not load its settings | Reload the editor. If it stays, another plugin's script error stopped it: check the browser console. |
| A coin I want is not in the list | The list holds the top 100 coins by market value. In a shortcode you can give any CoinGecko id with `coins="…"`. |

Still stuck? Ask in the [support forum](https://wordpress.org/support/plugin/ultimate-crypto-widget/). Say which widget
type, your WordPress version, and what you see.

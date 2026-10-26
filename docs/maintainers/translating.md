# Translating

Everything a visitor or a site owner reads can be translated: the admin screens, the block, and the
text inside the widgets themselves. Numbers, prices and dates are not translated by hand; the browser
formats them for the page's language.

## For translators

- **The free plugin** is translated on [translate.wordpress.org](https://translate.wordpress.org/projects/wp-plugins/ultimate-crypto-widget/).
  WordPress then delivers the language pack to every site automatically.
- **The Pro add-on** ships its translations in its own `languages/` folder. Start from
  `languages/ultimate-crypto-widget-pro.pot` and send back a `.po` file named
  `ultimate-crypto-widget-pro-{locale}.po`, for example `ultimate-crypto-widget-pro-fr_FR.po`.

Placeholders such as `%d` and `%1$s` stand for a number or a name and must stay in the translation.
Each one has a note for translators saying what it is.

## What is translated where

| What | Text domain | How it reaches the page |
| --- | --- | --- |
| Free plugin, PHP (admin screens, settings, block registration) | `ultimate-crypto-widget` | `.mo` file |
| Free plugin, scripts (widgets, widget editor, block) | `ultimate-crypto-widget` | one `.json` file per script |
| Pro add-on, PHP (license and style screens) | `ultimate-crypto-widget-pro` | `.mo` file |

The toolkit's asset manager attaches the right `.json` file to each script by itself.

**Every widget's text, the Pro tier's included, is in this plugin's domain**, because every widget's code lives here. In
the all-free edition this plugin ships and translates all of them. In the tiered edition the Pro add-on builds the Pro-tier
widgets into its own scripts, whose text is still in the `ultimate-crypto-widget` domain: before selling Pro, make sure
those scripts load this plugin's translations (see [Editions](editions.md#switching-to-free-and-pro), step 6).

## For developers

**Writing text**

- PHP: `__()`, `esc_html__()`, `_n()` with the plugin's own text domain, from `init` onwards.
- TypeScript: `__()`, `_n()`, `sprintf()` from `@wordpress/i18n`, with the text domain written out
  (the tools find strings by reading the code, so it cannot be a variable).
- Add a `translators:` comment above any string with a placeholder.
- **Shared components carry no text.** Nothing under `react/ds`, `react/data` or `react/live` calls
  `__()`. The Pro add-on compiles those files into its own scripts, where the free plugin's
  translations do not reach, so a component that needs wording takes it from its caller
  (see `Pager`'s `labels` and `PriceChart`'s `describe`).
- Numbers and money go through `react/ds/format.ts` (`Intl`), never through string building.

**Right to left**

The build writes a mirrored `-rtl.css` beside every stylesheet and WordPress serves it on
right-to-left sites. Keep it working by using logical properties (`margin-inline-start`,
`inset-inline-end`, `text-align: start`) and never `left` or `right`.

**Updating the templates**

Strings in scripts are read from the built files, so build first:

```bash
npm run build && composer make-pot
```

**Making the script translation files** (the Pro add-on, at release time; WordPress.org does this
for the free plugin):

```bash
vendor/bin/wp i18n make-json languages --no-purge
```

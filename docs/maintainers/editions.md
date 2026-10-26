# Editions: all free, or free and Pro

> **Now: all free.** Since 1.0 the plugin is released in its `all-free` edition. Every widget is free, the Pro add-on is
> not needed, and nothing suggests buying it.

The plugin can be released two ways. One line in [`edition.json`](../../edition.json) decides which.

| `edition` | What ships in this plugin | The Pro add-on |
| --- | --- | --- |
| **`all-free`** (current) | All 24 widget types. Nothing is locked; no "get Pro" suggestions. | Not needed. If installed, it adds only its style controls; it registers no widgets. |
| `tiered` | The 4 free types (Coin Marquee, Crypto Price Table, Crypto Price Label, Multi Currencies Tab). The others are listed as **(Pro)** and locked, and their scripts are left out of this plugin's zip. | Unlocks the Pro types with a license and ships their scripts. |

## How it works

- **One copy of the code.** Every widget's source is in `react/widgets/` in this repository, the Pro tier's included.
  `edition.json` lists the Pro tier's views under `proTierViews`. The Pro add-on's build compiles those views from this
  repository; it holds no copy.
- **One switch, read in three places:**
  - `src/Widgets/Edition.php`, at runtime: whether the Pro-tier types are free (`WidgetCatalog::proTier()`) and
    whether to suggest Pro (`Screens::suggestsUpgrade()`).
  - `build.php`: in the tiered edition the release zip leaves out the Pro-tier views' built scripts, so the WordPress.org
    plugin never contains locked code.
  - The Pro add-on: its `webpack.config.js` builds the views in `proTierViews`, and its `ProWidgets` registers the Pro
    types only when this plugin does not already provide them for free.
- **Both editions are tested.** `WidgetCatalog::withEdition()` builds a catalogue for either edition, and the test suite
  registers a locked sample type (`test-locked-widget`) so locked types stay tested whatever the edition.

## Switching to free and Pro

Do these in order. The first step matters most.

1. **Decide and announce it.** Widgets that were free become paid. After the update, sites using them will see those
   widgets stop showing to visitors (editors see a note) until they buy Pro. Agree which widgets become Pro, then say it
   in the changelog and the upgrade notice of the release, and on the website, before it ships. Consider grandfathering:
   leaving a widget free if many sites already use it.
2. **Choose the Pro tier.** In `edition.json`, make `proTierViews` list exactly the views (folders in `react/widgets/`)
   that should be Pro. A view is shared by several types (`coin-marquee-pro` draws Coin Marquee (2) and (3)), so a view is
   either all free or all Pro. Then mark the same types as Pro in `WidgetCatalog::builtIn()` (`$pro(…)` rather than
   `$free(…)`), and check the Pro add-on's `src/Widgets/ProWidgets.php` defines every one of them.
3. **Flip the switch:** set `"edition": "tiered"`.
4. **Check it.**
   - `composer phpunit` and `npm run test:js` pass.
   - `composer build`, then confirm the zip in `dist/` has no `assets/react-build/widgets/<view>/` for any Pro-tier view.
   - On a test site with only the free plugin, a Pro widget shows "(Pro)" in the editors and a note instead of the
     widget. With Pro installed and licensed, it shows.
5. **Set up selling and licensing.** Lemon Squeezy, the license service, the signing keys and the website's plans: the Pro
   repository's `docs/` has every step.
6. **Fix the Pro widgets' translations.** In the all-free edition every widget is translated under this plugin's text
   domain (`ultimate-crypto-widget`). When Pro builds those widgets, its scripts are registered under Pro's handles, so
   their translations must be loaded for this plugin's domain too. Check this on a non-English site before selling.
7. **Release this plugin first**, then Pro at the matching version. Pro builds against this plugin's source.

## Keeping Pro widgets' code private

This repository is **public**, and WordPress.org requires the readable source of any minified script it hosts, so code
here is open to anyone. The Pro repository is **private**. Where a widget's source lives decides who can read it.

**What is already public.** All 21 widget views are in this repository, and their built scripts have shipped on
WordPress.org since 1.0. Under the GPL that code stays available: anyone may keep and reuse the versions already
published, and moving the source later does not remove it from the git history. Moving a widget later only keeps its
*future changes* private.

**A new Pro widget, private from the start.** Write it in the Pro repository, not here:

1. Create its folder in the Pro repository: `react/widgets/<view>/index.tsx` (and `style.scss` if it has its own layout).
   Import shared code from this plugin as `@core/…`, as in [Extending → Writing a widget](extending.md#writing-a-widget).
2. In the Pro repository's `webpack.config.js`, add an entry for every folder in its own `react/widgets/`, next to the
   ones built from `proTierViews`:

   ```js
   // Pro's own widgets, kept in this private repository.
   const ownDir = path.resolve(__dirname, 'react/widgets');
   if (fs.existsSync(ownDir)) {
   	for (const view of fs.readdirSync(ownDir)) {
   		const index = ['tsx', 'ts'].map((ext) => path.join(ownDir, view, `index.${ext}`)).find(fs.existsSync);
   		if (index) entries[`widgets/${view}`] = index;
   	}
   }
   ```

3. Register its type in Pro's `src/Widgets/ProWidgets.php`, like the others there. This plugin's catalogue accepts types
   from an add-on through the `ucwp_register_widgets` action, so nothing changes here. **Do not** list the view in this
   plugin's `edition.json`: `proTierViews` is only for views whose source lives here.
4. Give the type a new slug and never rename it afterwards: slugs are stored in customers' widgets.

**Moving an existing widget's future work out of this repository.** Copy its folder to the Pro repository's
`react/widgets/`, delete it here, remove the view from `proTierViews`, and keep its type slugs exactly as they are. Its
types must then be `$pro(…)` here and defined in `ProWidgets`. Remember the note above about what is already public.

**About "private".** Pro is licensed under the GPL like this plugin, as WordPress plugins are. Customers who receive it
may share its code. Private means it is not published for anyone to read; that is how paid WordPress plugins work.

## Switching to all free (what was done for 1.0)

Set `"edition": "all-free"` and release. Pro-tier widgets that customers saved with the old Pro plugin then show with
this plugin alone; `DataCompatibilityTest::test_a_pro_widget_saved_by_0x_shows_in_the_all_free_edition` checks it.

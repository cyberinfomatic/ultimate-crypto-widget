# Editions: all free, or free and Pro

The plugin can be released two ways. One line in [`edition.json`](../edition.json) decides which.

| `edition` | What ships in this plugin | Pro add-on |
| --- | --- | --- |
| **`all-free`** (current) | Every widget, 24 types. Nothing is locked; no "upgrade" suggestions. | Not needed. If installed, it adds only its style controls and does not register widgets. |
| `tiered` | The 4 free types. The Pro-tier types are listed in the admin as **(Pro)** and locked; their scripts are left out of this plugin's zip. | Unlocks the Pro-tier types with a license, and ships their scripts, built from this plugin's source. |

## How it works

- **One copy of the code.** Every widget's source is in `react/widgets/` here, including the Pro tier, whose
  views are listed in `edition.json` under `proTierViews`. The Pro add-on's build compiles those views from
  this repository; it holds no copy.
- **One switch, read in three places:**
  - `src/Widgets/Edition.php`: at runtime, whether the Pro-tier types are free (`WidgetCatalog::proTier()`)
    and whether to suggest Pro (`Screens::suggestsUpgrade()`).
  - `build.php`: in the tiered edition, the release zip leaves out the Pro-tier views' built files, so the
    WordPress.org plugin never contains locked code.
  - The Pro add-on's build and `ProWidgets`: they build and register the Pro tier only when this plugin
    does not already provide it.
- **Tests cover both.** `WidgetCatalog::withEdition()` builds a catalogue for either edition, and the test
  suite registers a locked sample type (`test-locked-widget`) so the handling of a locked type stays tested
  whatever the edition.

## Switching back to free and Pro

1. **Agree it first.** Widgets that were free become paid: sites using them will see them stop rendering for
   visitors after the update unless they buy Pro. Say so in the changelog and on the website before it ships.
2. In `edition.json`, set `"edition": "tiered"`. Check `proTierViews` lists the views that should be Pro.
3. Run the checks (`composer phpunit`, `npm run test:js`) and `composer build`, and confirm the zip no longer
   contains `assets/react-build/widgets/<view>` for the Pro-tier views.
4. Set up licensing for Pro (Lemon Squeezy, the license service, the public key): see the Pro repository's
   `docs/setup-and-release.md`.
5. Release the free plugin, then Pro (Pro builds against this plugin's source at the same version).

## Switching to all free (what was done for 1.0)

Set `"edition": "all-free"` and release. Pro-tier widgets that customers saved with the old Pro plugin start
rendering with the free plugin alone (`DataCompatibilityTest::test_a_pro_widget_saved_by_0x_shows_in_the_all_free_edition`).

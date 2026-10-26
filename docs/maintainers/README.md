# Maintainer overview

Read this first. It is the map; the other guides go into detail.

## The state of the project

- **Every widget is free.** The plugin is released in its **all-free edition**: all 24 widget types are in this plugin,
  nothing is locked, and nothing suggests buying Pro. This is set by one line in [`edition.json`](../../edition.json).
- **Pro is ready but dormant.** The Pro add-on, its license service and the product website exist in a separate,
  private repository. While this plugin is all free, Pro adds no widgets and nothing is sold.
- **To sell Pro later**, switch the edition to `tiered` and release. [Editions](editions.md) has the steps, including how
  to keep future Pro widgets' code private.

## Two repositories

| Repository | Visibility | What it holds |
| --- | --- | --- |
| `cyberinfomatic/ultimate-crypto-widget` (this one) | Public | The free plugin, published on WordPress.org. **All widget code**, the Pro tier's included. |
| `Stephen-Eneji/Ultimate-Crypto-Widget` | Private | The Pro add-on (licensing, updates, style controls), the license service, and the website at ultimatecryptowidget.com. |

Pro holds no copy of the widgets. When Pro is built, it compiles the Pro-tier widgets straight from this repository's
source. Fix a widget here and both plugins get the fix.

## Where things are

| Path | What is there |
| --- | --- |
| `ultimate-crypto-widget.php` | The plugin header (version, requirements) and the checks before it starts. |
| `src/` | PHP. `src/boot.php` lists the service providers; one provider per concern in `src/Providers/`. |
| `react/` | TypeScript. One folder per widget in `react/widgets/`; the design system in `react/ds/`; live prices in `react/live/`; the block in `react/block/`. |
| `views/` | Admin page templates. |
| `lib/wptoolkit/` | A bundled, renamed copy of WPToolkit, the framework the plugin is built on. Do not edit it; see `lib/README.md`. |
| `tests/` | PHP integration tests, fixtures from old versions, and snapshots. JavaScript tests sit beside their code. |
| `edition.json` | All free, or free and Pro. |
| `build.php` | Builds the release zip. |
| `.github/workflows/` | CI on pull requests, a zip on every push to `main`, and the release to WordPress.org. |
| `docs/` | These guides, and the user guides in `docs/users/`. |

[Architecture](architecture.md) explains how these fit together.

## What must never change

These are stored in users' databases and pages. Renaming one breaks sites.

- the post type `ucwp_widget` and its meta keys `ucwp_widget_{setting}` (see `WidgetFields::NAMES`);
- the shortcode tag `ucwp_widget` and its attribute names;
- widget type slugs (`coin-marquee-1`, `crypto-price-table-1`, …);
- option names `ultimate_crypto_widget_{setting}`;
- REST routes under `ultimate-crypto-widget/v1`;
- the block name `ucwp/widget` and its attributes;
- admin page slugs (`ultimate-crypto-widget`, `…-quick-widget`, `…-settings`).

`tests/Integration/DataCompatibilityTest.php` (with the exact rows the 0.x versions wrote, in
`tests/fixtures/legacy-0.x/`) and the snapshots in `tests/snapshots/` hold you to this. Never edit those fixtures.

## Working rules

- Work on a branch and open a pull request into `main`; never commit to `main`. CI must pass before merging.
- Commits follow [Conventional Commits](https://www.conventionalcommits.org/): `feat:`, `fix:`, `docs:`, `test:`,
  `ci:`, `refactor:`, `chore:`.
- Hooks, assets, settings, meta boxes and admin pages go through WPToolkit (`HookRegistrar`, `AssetManager`, `Settings`,
  `MetaBox`, `Pages`). Do not call `add_action`, `wp_enqueue_*`, `register_setting` or `add_menu_page` directly.
- Every REST route states who may call it, validates its input and is rate limited.
- API keys are secrets: never in a script, a response or a log.
- Every user-facing text is translatable (see [Translating](translating.md)).
- Widget code follows the [frontend standards](frontend-standards.md).
- Every class, function and export has a short doc comment saying what it is for.

## Common jobs

| I want to… | Read |
| --- | --- |
| Fix or change a widget | [Architecture](architecture.md), [Frontend standards](frontend-standards.md), [Development](development.md) |
| Add a setting to widgets | [Architecture → The settings schema](architecture.md#the-settings-schema) |
| Add a widget type | [Extending → Writing a widget](extending.md#writing-a-widget) |
| Release a new version | [Releasing](releasing.md) |
| Start selling Pro | [Editions](editions.md) |
| Let another plugin add widgets or change prices | [Extending](extending.md) |

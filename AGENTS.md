# AGENTS.md — Ultimate Crypto Widget

A WordPress plugin that renders cryptocurrency widgets from a custom post type, a shortcode and a
React bundle. This repository is the free plugin on WordPress.org and the shared core that the Pro
add-on builds on.

Read [docs/maintainers/README.md](docs/maintainers/README.md) first: the state of the project, the two
repositories, what must never change, and which guide covers what. The guides for site owners are in
[docs/users/](docs/users/).

## Rules

- Do **not** rename or reformat stored data: the `ucwp_widget` post type, the `ucwp_widget_*` meta
  keys, the `ultimate_crypto_widget_*` options, the `[ucwp_widget]` shortcode and its attributes, REST
  URLs under `ultimate-crypto-widget/v1`, or admin menu slugs.
- Every REST route needs an explicit access rule. Public routes are read-only and rate limited.
- API keys are secrets: not in JavaScript data, responses or logs.
- No locked code in the WordPress.org zip. Every widget's source lives here, including the Pro tier; which
  ones are free is decided by `edition.json` (currently `all-free`: everything is free). In the `tiered`
  edition the Pro-tier views are left out of the zip and the Pro add-on ships them. See
  [docs/maintainers/editions.md](docs/maintainers/editions.md).
- Frontend code follows [docs/maintainers/frontend-standards.md](docs/maintainers/frontend-standards.md).
- Every class, function and export has a short doc comment saying what it is for.
- Commits follow Conventional Commits (`feat:`, `fix:`, `test:`, `docs:`, `ci:`, `refactor:`, `chore:`).
- Work on a branch and open a pull request; never commit to `main`.

## Tests

`composer phpunit` runs the integration suite. It needs `composer install`, `npm ci && npm run build`
and a MySQL database (`ucw_tests` by default; see `tests/wp-tests-config.php`).

- `tests/fixtures/legacy-0.x/` holds the rows the 0.x plugins wrote; `DataCompatibilityTest` proves they
  still read and render. Never edit those fixtures.
- `tests/snapshots/` pins stored rows, shortcode output and REST responses. Refresh with
  `UPDATE_SNAPSHOTS=1 composer phpunit` only for an intended change.
- `tests/Integration/RegressionTest.php` holds a test for every bug fixed so far; add one with each fix.

## Layout

- `ultimate-crypto-widget.php` — bootstrap
- `src/Providers/` — one WPToolkit service provider per concern; `src/boot.php` lists them
- `src/Widgets/` — the catalogue of widget types, their settings (`WidgetFields`, `WidgetSchema`),
  the shortcode and the renderer
- `src/Admin/` — the widget editor, preview, menu and screens (toolkit `MetaBox`, `Pages`, `SettingsForm`)
- `src/Data/` — the price source (`PriceProvider`, CoinGecko); `src/Http/` — REST controllers
- `views/` — admin templates, printed through the toolkit's escaper (`$e`)
- `react/ds/` — the design system; `react/widgets/` — one folder per widget; `react/data/`, `react/live/` —
  REST client and live prices; `react/admin/` — the editor's script. Built with `npm run build`.
- `assets/react-build/` — build output (not committed)

Hooks go through the toolkit's `HookRegistrar` (`$hooks->addAction()` / `addFilter()`), assets through
`AssetManager`, settings through `Settings`. Do not call `add_action`, `wp_enqueue_*` or
`register_setting` directly.

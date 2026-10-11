# AGENTS.md — Ultimate Crypto Widget

A WordPress plugin that renders cryptocurrency widgets from a custom post type, a shortcode and a
React bundle. This repository is the free plugin on WordPress.org and the shared core that the Pro
add-on builds on.

## Rules

- Do **not** rename or reformat stored data: the `ucwp_widget` post type, the `ucwp_widget_*` meta
  keys, the `ultimate_crypto_widget_*` options, the `[ucwp_widget]` shortcode and its attributes, REST
  URLs under `ultimate-crypto-widget/v1`, or admin menu slugs.
- Every REST route needs an explicit access rule. Public routes are read-only and rate limited.
- API keys are secrets: not in JavaScript data, responses or logs.
- No locked or Pro-only code in this repository. Pro features are added by the add-on through hooks.
- Frontend code follows [docs/standards/frontend.md](docs/standards/frontend.md).
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
- `src/` — PHP (`Controllers/`, `Helpers/`, `Views/`)
- `react/` — widgets (TypeScript), built with `npm run build` (`@wordpress/scripts`)
- `assets/` — scripts, styles, libraries

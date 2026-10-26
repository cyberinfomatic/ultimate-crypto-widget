# Development

## What you need

- PHP 8.2 or newer, with Composer.
- Node.js 20 and npm.
- MySQL or MariaDB, for the PHP tests.
- [WP-CLI](https://wp-cli.org/), to make the translation template.

## Set up

```bash
composer install && npm ci
```

```bash
npm run build
```

The scripts in `react/` are TypeScript, built by webpack (`@wordpress/scripts`) into `assets/react-build/`, which is not
committed. For a working copy you can use in WordPress, put this folder in a site's `wp-content/plugins/` (or link it
there) and run `npm run build` after each change to `react/`.

## Tests and checks

Run all of these before opening a pull request. CI runs the PHP tests on PHP 8.2 with WordPress 6.5.5 (the oldest we
support) and on PHP 8.2 and 8.3 with the WordPress version locked in `composer.lock`.

| Command | What it checks |
| --- | --- |
| `composer phpunit` | The PHP integration tests, inside a real WordPress. |
| `npm run typecheck` | TypeScript types. |
| `npm run test:js` | The JavaScript tests (Vitest), beside the code they test. |
| `composer phpcs` | WordPress coding standards for PHP. Warnings about long lines are tolerated; errors are not. |

The PHP tests need a MySQL database, `ucw_tests` by default; `tests/wp-tests-config.php` has the connection details. No
test reaches a real API: `tests/Support/HttpStub.php` answers every outgoing request from fixtures in `tests/fixtures/`.

## Snapshots

`tests/snapshots/` pins what is stored in the database, the shortcode's HTML and data, and the REST responses. A change
that alters any of them fails the tests until the snapshots are refreshed:

```bash
UPDATE_SNAPSHOTS=1 composer phpunit
```

Refresh them only for a change you intend, then read the diff: it should hold exactly the change you meant. On Windows,
Git may also show every snapshot as changed because of line endings; those files have no real difference
(`git diff --ignore-cr-at-eol`) and are normalised when committed.

## Compatibility with old versions

`tests/fixtures/legacy-0.x/` holds the exact rows the 0.x plugins wrote. `DataCompatibilityTest` proves they still read
and render. Never edit those fixtures; if a change is deliberate, record it in the test with a comment saying why.

## Translation template

```bash
composer make-pot
```

Run it after adding or changing text, and commit `languages/ultimate-crypto-widget.pot`. See [Translating](translating.md).

## The release zip

```bash
composer build
```

This runs `build.php`, which copies only what a site needs into `dist/ultimate-crypto-widget-{version}.zip` and checks it.
Every push to `main` also builds the zip in GitHub Actions ("Build the zip on main") and keeps it as an artifact.

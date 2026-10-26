# Ultimate Crypto Widget

Live cryptocurrency prices for WordPress: 24 widgets (tickers, price tables, charts, a market heatmap and
overview, compare, converter and holdings tools, and more). Place a widget with the block editor, a shortcode
or Elementor.

This is the free plugin, published on WordPress.org. **Every widget is free**: it is released in its
**all-free edition** ([edition.json](edition.json)). A Pro add-on, in a separate private repository, is ready for the
day some widgets should be paid; see [Editions](docs/maintainers/editions.md).

**Documentation:** [docs/README.md](docs/README.md)

- **Site owners:** [getting started](docs/users/getting-started.md), [the widgets](docs/users/widgets.md),
  [settings and shortcode attributes](docs/users/settings.md), [troubleshooting](docs/users/troubleshooting.md).
- **Maintainers:** start with the [overview](docs/maintainers/README.md), then [architecture](docs/maintainers/architecture.md),
  [development](docs/maintainers/development.md) and [releasing](docs/maintainers/releasing.md).
- **For AI coding agents:** [AGENTS.md](AGENTS.md).

The text shown on WordPress.org is [readme.txt](readme.txt).

## Requirements

WordPress 6.5.5 or newer, PHP 8.2 or newer. No API key is needed.

## Building from source

```bash
composer install && npm ci
```

```bash
npm run build
```

The scripts in `react/` are written in TypeScript and built into `assets/react-build/`, which is not
committed. To make the release zip:

```bash
composer build
```

## Tests and checks

```bash
composer phpunit
```

```bash
npm run typecheck && npm run test:js
```

```bash
composer phpcs
```

## Built on WPToolkit

The plugin is built on [WPToolkit](https://github.com/codad5/wptoolkit). A renamed copy is bundled
in `lib/wptoolkit`, so it cannot clash with another plugin's copy; see [lib/README.md](lib/README.md)
for how to update it.

## License

GPL-2.0-or-later.

# Ultimate Crypto Widget

Live cryptocurrency prices for WordPress: 24 widgets (tickers, price tables, charts, a market heatmap and
overview, compare, converter and holdings tools, and more). Place a widget with the block editor, a shortcode
or Elementor.

This is the free plugin, published on WordPress.org. Every widget is free: it is released in its
**all-free edition** ([edition.json](edition.json)). A Pro add-on exists for a later, tiered edition; it is not
needed now. How to switch: [docs/editions.md](docs/editions.md).

- **Using the plugin:** [docs/user-guide.md](docs/user-guide.md)
- **Free and Pro editions:** [docs/editions.md](docs/editions.md)
- **Extending it or working on it:** [docs/developers.md](docs/developers.md)
- **Translating it:** [docs/translating.md](docs/translating.md)
- **Frontend rules:** [docs/standards/frontend.md](docs/standards/frontend.md)
- **For AI coding agents:** [AGENTS.md](AGENTS.md)

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

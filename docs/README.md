# Ultimate Crypto Widget documentation

Ultimate Crypto Widget shows live cryptocurrency prices on WordPress sites: 24 widgets (tickers, tables, charts,
a heatmap, a converter and more), placed with the block editor, a shortcode or Elementor.

> **Current state: every widget is free.** The plugin is released in its *all-free edition*. A Pro add-on exists
> and is ready for the day some widgets should be paid; switching is one setting plus a release. See
> [maintainers/editions.md](maintainers/editions.md).

## I use the plugin on my site

| Guide | What it covers |
| --- | --- |
| [Getting started](users/getting-started.md) | Installing, your first widget, placing it with the block editor, a shortcode or Elementor. |
| [The widgets](users/widgets.md) | All 24 widgets: what each shows and which settings it has. |
| [Settings](users/settings.md) | Every widget setting, the shortcode's attributes, and the site-wide settings. |
| [Troubleshooting](users/troubleshooting.md) | What to do when a widget shows nothing, prices stop moving, or your CSS no longer applies. |

## I maintain or extend the plugin

Start with the overview. It says what is where, what must never change, and the state of the project.

| Guide | What it covers |
| --- | --- |
| [Overview](maintainers/README.md) | The project at a glance, the two repositories, the all-free state, the rules. |
| [Architecture](maintainers/architecture.md) | How a widget reaches the page, the code layout, the settings schema, the REST API. |
| [Extending](maintainers/extending.md) | Hooks and filters, adding widget types from another plugin, writing a widget. |
| [Editions: all free, or free and Pro](maintainers/editions.md) | The one switch, how to go back to free and Pro, keeping Pro widgets' code private. |
| [Development](maintainers/development.md) | Setting up, building, the tests and checks, snapshots. |
| [Releasing](maintainers/releasing.md) | Versioning, the release workflow, WordPress.org, and fixing a failed release. |
| [Translating](maintainers/translating.md) | Text domains, the translation template, right-to-left. |
| [Frontend standards](maintainers/frontend-standards.md) | The rules for widget code and styles. |
| [WPToolkit notes](maintainers/wptoolkit-notes.md) | The toolkit gaps this plugin works around. |

The Pro add-on, the license service and the website live in a separate, private repository with their own
docs (`docs/README.md` there).

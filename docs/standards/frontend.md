# Frontend standards

Binding for new code in `react/` and for the widget and admin styles. Existing widgets move to these
rules one at a time; a widget's old code is deleted only when its replacement passes its tests.

The widgets render inside other people's themes, so everything here is scoped and nothing leaks.

## Layout

- **Gap-only spacing.** A parent arranges its children with flex or grid and spaces them with `gap`.
  No margins on components.
- **Padding is internal and symmetric** (`padding: var(--ucw-space-4)`). One-sided padding only when
  the design needs it, with a comment saying why.
- **`position: absolute` only inside a `position: relative` parent.**
- Logical properties (`margin-inline-start`, `inset-inline-end`), so right-to-left works.

## Tokens

All CSS variables, in three layers:

```
react/ds/tokens/
  primitives.scss   OKLCH ramps. Never used by components.
  themes/light.scss role tokens -> primitives, on .ucw-widget and [data-ucw-theme="light"]
  themes/dark.scss  role tokens -> primitives, on [data-ucw-theme="dark"]
  brand.scss        accent hue and its steps
```

- Components use **role** tokens only: `--ucw-surface`, `--ucw-fg`, `--ucw-fg-muted`, `--ucw-border`,
  `--ucw-accent`, `--ucw-up`, `--ucw-down`. Never a primitive, never a raw hex/rgb/oklch value.
- Radius, spacing, type, shadow, motion and density are tokens too.
- A new need means a new token, then use it.
- Theme and brand switch at runtime through attributes and inline variables on the widget's root
  element. Style controls change token values, never component code.

## Components

- Widgets import UI only from `react/ds/` (`Card`, `Price`, `Delta`, `Sparkline`, `Table`, `Tabs`,
  `Skeleton`). Third-party UI libraries are wrapped there and nowhere else.
- Styles are plain SCSS scoped under `.ucw-widget` with low specificity, so a site's theme can still
  override them and nothing leaks out. No utility-class framework.
- Up and down are never shown by colour alone: pair the colour with an icon or a sign.
- Prices use tabular numerals.

## TypeScript

- `strict` mode. No `any`, no `eslint-disable` without a reason on the same line.
- API responses are parsed into a typed result (`{ ok: true, data }` or `{ ok: false, kind }`).
  Every widget renders its loading, empty and error states on purpose.
- Each widget reads its data from its own mount element. Do not add to the shared
  `window.ucwpReactData` object; it is kept only until the last old widget is replaced.
- No `console.log` in shipped code.
- User-facing strings go through `@wordpress/i18n`; numbers, prices and dates through `Intl`.

## Motion

Durations and easing from tokens, under 200ms. Motion explains a change; it never decorates. None
when the visitor prefers reduced motion.

## Tests

Every component ships a test, and every component that loads data ships its skeleton.

## Decisions

A decision someone could reasonably question gets a short record in `docs/adr/`.

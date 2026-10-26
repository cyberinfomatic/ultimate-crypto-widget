# Notes for WPToolkit

Found while building Ultimate Crypto Widget on WPToolkit 1.0.0-rc.4. Checked again on 1.0.0-rc.5:
items 1 and 2 are unchanged there, item 6 is mostly fixed. Each has a workaround in this
plugin that should be deleted once the toolkit is fixed.

## 1. `WpHttpClient` loses response headers and raises a warning (bug)

**Where:** `src/Adapters/Http/WpHttpClient.php`

`wp_remote_retrieve_headers()` returns a `WpOrg\Requests\Utility\CaseInsensitiveDictionary`, not an
array. Casting it with `(array)` gives the object's private properties, so every real response raises
"Array to string conversion" and `HttpResponse::header()` finds nothing. Tests that stub
`pre_http_request` with a plain array of headers do not see it.

**Fix:** if the value is the dictionary, call `->getAll()`; join a header that has several values
with `", "`.

**Our workaround:** `src/Data/Http/WordPressHttpClient.php`, bound in `DataProvider`.
**Our test:** `PriceProviderTest::test_a_real_wordpress_response_is_read_without_warnings_and_keeps_its_headers`.

## 2. Asset URLs are wrong for a plugin installed through a symbolic link (bug)

**Where:** `CoreServices::contentUrl()`

The URL is worked out from the plugin folder's position under `WP_CONTENT_DIR`. A symlinked plugin's
real path is somewhere else, so the method falls back to `content_url()` and every script is
addressed as `/wp-content/assets/…` (404). Symlinks are common in development and on some hosts.

**Fix:** for a plugin use `plugin_dir_url($config->file)`, which WordPress resolves through
`wp_register_plugin_realpath()`; keep the current logic for themes and mu-plugins.

**Our workaround:** `AssetsProvider::managerFor()` builds the `AssetManager` with that URL.
**Our test:** `AssetsTest::test_scripts_are_addressed_through_wordpress_so_a_linked_plugin_folder_works`.

## 3. Two applications built from one copy of the library (works; worth documenting)

The Pro add-on does not bundle the toolkit. It creates its own `Application` from the copy the core
plugin bundles (`Application::create($proFile, ['slug' => 'ultimate-crypto-widget-pro'])`), and so
gets its own identity, hooks, pages and asset manager. That works today. Two things would make it a
supported pattern rather than a discovery:

- say in the docs that an add-on may do this, and what it must check first (the host plugin is
  active and its toolkit version is new enough);
- item 2 applies to the add-on's asset manager as well, so both plugins currently build it by hand
  (`AssetsProvider::managerFor()`).

## 4. `wptoolkit-build` staging is slow on Windows (performance)

Building this plugin's zip took about 11 minutes here. Not profiled, but the tree is copied to a
temporary folder and pruned there, so `node_modules` (tens of thousands of small files) is most
likely copied and then deleted.

**Suggestion:** apply the exclusions while walking the tree, so an excluded folder is never entered,
and check with a project that has a large `node_modules`.

## 5. A post type's labels cannot be translated (missing feature)

`#[PostType('book', singular: 'Book', plural: 'Books')]` takes its labels as attribute arguments, which
PHP evaluates as constants, so they cannot go through `__()`. `EntityRegistrar::registerPostType()`
then builds only `name` and `singular_name`. A plugin that ships translations has to set
`register: false` and call `register_post_type()` itself.

**Suggestion:** let the registrar take the labels at registration time, e.g.
`$registrar->entity(Book::class, labels: [...])`, or accept a `labels` callback.

**Our workaround:** `Admin\WidgetEditor::registerPostType()` calls `register_post_type()` directly and
builds the settings form with `$registrar->metaBox()` rather than an entity.

## 6. Autoloading the plugin's own classes without Composer (fixed in rc.5; one case left)

**Fixed in 1.0.0-rc.5** for a plugin that bundles the toolkit: the guard's `namespaces` option loads
the plugin's classes by PSR-4. This plugin uses it and its hand-written loader is gone
(`tests/Integration/AutoloadTest.php` checks every file's name against its class).

**Add-ons:** an add-on that borrows a host plugin's copy of the toolkit cannot call the guard (the
guard file is in the host's folder, and the add-on must first find out whether the host is there).
We bridge that in the host: `Plugin::autoload($prefix, $directory)` requires
`bootstrap/namespaces.php` from the toolkit copy in use and registers the add-on's namespace, and
the Pro add-on calls it. It works, but it leans on a file the toolkit describes as "required by the
guard".

**Suggestion:** make that supported, for example `Application::namespaces(array( … ))`, or say in
the docs that `bootstrap/namespaces.php` may be required directly.

## 7. A multiple-choice field cannot be limited to a number of choices (missing feature)

Rules run on each chosen value, so `Rules::max()` cannot say "at most 3 choices". This plugin limits the
currencies a widget offers to three and enforces it in `WidgetEditor::enforceLimits()`, which runs just before
the meta box saves, removes a too-long list from the request and flashes an error through `Notices`.

**Suggestion:** a field setting such as `->maxChoices(3)`, checked by `FieldValidator`, with the picker stopping
a tick beyond it.

## 8. A field's options must be an array when the field is built (limitation)

The coins to choose from come from a price API. Because `->options()` takes an array, building the form costs
that request. This plugin builds the form only on the widget screens, and keeps the result for the request.

**Suggestion:** accept a closure that is called when the field is drawn or validated.

## 9. `MetaBox::render()` puts a fieldset inside a paragraph (bug)

Each field is printed as `<p class="wptoolkit-field"><label>…</label><br />{input}<br /><span class="description">…</span></p>`.
A browser closes a paragraph when a `<fieldset>` starts (the rc.8 checkbox and chip pickers are fieldsets), so the label,
the fieldset and the description end up as loose siblings and an empty paragraph follows. A script that hides or lays out
"a field" as one element cannot. `react/admin/index.ts` puts such a row back together.

**Fix:** use a `<div class="wptoolkit-field">` instead of a `<p>`.

## 10. One invalid value rejects a whole multiple-choice field (behaviour, probably intended)

A list that contains a value that is not one of the options is refused as a whole, and the old value is kept. The old code
of this plugin dropped the bad value and saved the rest. Noted because it is a change; this plugin keeps the toolkit's way.

# Notes for WPToolkit

Found while building Ultimate Crypto Widget on WPToolkit 1.0.0-rc.4. Each has a workaround in this
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

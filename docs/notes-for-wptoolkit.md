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

**Our workaround:** `src/Providers/AssetsProvider.php` rebuilds the `AssetManager` with that URL.
**Our test:** `AssetsTest::test_scripts_are_addressed_through_wordpress_so_a_linked_plugin_folder_works`.

## 3. `AssetManager` cannot load a file from another plugin's folder (missing feature)

An add-on registers widgets into this plugin, and their bundles live in the add-on's folder. Sources
are resolved against one base folder only, so those bundles get no manifest, version, translations
or RTL handling from the toolkit.

**Suggestion:** let an asset carry its own base, e.g.
`$assets->script('name', 'build/x.js')->from($dir, $url)` (and a text domain for its translations).

**Our workaround:** `ReactMount::loadFromAddOn()` calls `wp_enqueue_script()` directly.

## 4. `wptoolkit-build` staging is slow on Windows (performance)

Building this plugin's zip took about 11 minutes here. Not profiled, but the tree is copied to a
temporary folder and pruned there, so `node_modules` (tens of thousands of small files) is most
likely copied and then deleted.

**Suggestion:** apply the exclusions while walking the tree, so an excluded folder is never entered,
and check with a project that has a large `node_modules`.

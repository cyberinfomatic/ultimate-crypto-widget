# Bundled libraries

## wptoolkit

[WPToolkit](https://github.com/codad5/wptoolkit) **1.0.0-rc.5**, the standalone zip from its GitHub
release, with its namespace renamed to `Cyberinfomatic\UltimateCryptoWidget\Lib\WPToolkit` so it
cannot clash with another plugin that bundles a different version.

Do not edit files in `lib/wptoolkit/`. Fix things upstream and update the copy.

### Updating

1. Download `wptoolkit-<version>.zip` from the release and check it against its `.sha256` file.
2. Replace the `lib/wptoolkit/` folder with the zip's `wptoolkit/` folder.
3. Rename the namespace:

   ```bash
   php lib/wptoolkit/bin/scope.php "Cyberinfomatic\UltimateCryptoWidget\Lib\WPToolkit" lib/wptoolkit
   ```

4. Update the version in this file, run `composer phpunit`, and commit the folder in one commit.

The plugin's own code always refers to the renamed namespace, in development and in the release
zip, so what the tests run is what ships.

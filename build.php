<?php
/**
 * Builds the release zip: `composer build` (or `php build.php`).
 *
 * The working tree is copied to a temporary directory, pruned and verified there, so
 * nothing here is modified. Run `npm ci` first; this script runs the asset build.
 *
 * @package UltimateCryptoWidget
 */

use Codad5\WPToolkit\Build\Build;
use Codad5\WPToolkit\Build\Patterns;
use Codad5\WPToolkit\Build\Version;

require __DIR__ . '/vendor/autoload.php';

Build::plugin( __DIR__ )
	->version( Version::fromHeader() )
	// Its output is shown as it runs, so a long build does not look stuck.
	->runVerbose( 'npm run build' )
	->exclude(
		Patterns::fromDistignore(),
		// Sources and tooling: the zip carries only what runs.
		'/react',
		'/build.php',
		// Images for the plugin's WordPress.org page, not for the plugin itself. The
		// release workflow sends the banners and icons to WordPress.org separately.
		'/assets/images/banner-*',
		'/assets/images/icon-*',
		'/assets/images/old-icon*',
		'/assets/images/ucwp-logo.png',
		'/assets/coin-logos',
		'/bin',
		'/vendor',
		'/dist',
		'/svn-local',
		'/Gruntfile.js',
		'/tsconfig.json',
		'.phpunit.result.cache',
		// Parts of the bundled toolkit that only developers use.
		'/lib/README.md',
		'/lib/wptoolkit/bin',
		'/lib/wptoolkit/README.md',
		'/lib/wptoolkit/llms.txt',
		'/lib/wptoolkit/resources/stubs'
	)
	->verify( array( '*.map', '*.ts', '*.tsx', '*.scss', 'composer.json', 'package.json' ), 5 )
	// zip() prints the summary when it finishes.
	->zip( 'dist/{slug}-{version}.zip' );

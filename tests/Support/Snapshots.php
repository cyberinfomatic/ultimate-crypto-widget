<?php
/**
 * Snapshot comparison.
 *
 * @package Ultimate_Crypto_Widget
 */

namespace Cyberinfomatic\UltimateCryptoWidget\Tests\Support;

use PHPUnit\Framework\Assert;

/**
 * Compares output with a file in tests/snapshots.
 *
 * Record or refresh snapshots with: UPDATE_SNAPSHOTS=1 composer phpunit
 * A changed snapshot is either an intended, documented change or a bug.
 */
final class Snapshots {

	public static function assert_matches( string $name, string $actual ): void {
		$file   = dirname( __DIR__ ) . '/snapshots/' . $name;
		$actual = self::normalise( $actual );

		if ( getenv( 'UPDATE_SNAPSHOTS' ) ) {
			if ( ! is_dir( dirname( $file ) ) ) {
				mkdir( dirname( $file ), 0777, true ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_mkdir
			}
			file_put_contents( $file, $actual ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		}

		Assert::assertFileExists( $file, "No snapshot for {$name}. Record it with UPDATE_SNAPSHOTS=1." );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		Assert::assertSame( self::normalise( file_get_contents( $file ) ), $actual, "Output differs from snapshot {$name}." );
	}

	public static function json( $value ): string {
		return wp_json_encode( $value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . "\n";
	}

	private static function normalise( string $text ): string {
		return str_replace( "\r\n", "\n", $text );
	}
}

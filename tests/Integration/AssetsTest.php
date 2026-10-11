<?php
/**
 * What the plugin loads, and where.
 *
 * @package Ultimate_Crypto_Widget
 */

namespace Cyberinfomatic\UltimateCryptoWidget\Tests\Integration;

use Cyberinfomatic\UltimateCryptoWidget\Tests\Support\TestCase;

/**
 * A page without a widget must not pay for the plugin.
 */
final class AssetsTest extends TestCase {

	public function test_a_page_without_a_widget_loads_nothing_from_the_plugin(): void {
		do_action( 'wp_enqueue_scripts' );

		$this->assertSame( array(), $this->ours( wp_scripts()->queue ) );
		$this->assertSame( array(), $this->ours( wp_styles()->queue ) );
	}

	public function test_a_widget_brings_its_own_script_and_style(): void {
		if ( ! self::has_react_build() ) {
			$this->markTestSkipped( 'React bundles are not built.' );
		}

		do_shortcode( '[ucwp_widget type="crypto-price-table-1" count="5" parent_width="100%" default_currency="usd"]' );

		$scripts = $this->ours( wp_scripts()->queue );
		$this->assertContains( 'ultimate-crypto-widget-vendors', $scripts );
		$this->assertCount( 2, $scripts, 'Expected the shared bundle and one widget bundle.' );
		$this->assertContains( 'ultimate-crypto-widget-widget-crypto-price-table-style', $this->ours( wp_styles()->queue ) );
	}

	public function test_widget_scripts_do_not_pull_in_jquery(): void {
		if ( ! self::has_react_build() ) {
			$this->markTestSkipped( 'React bundles are not built.' );
		}

		do_shortcode( '[ucwp_widget type="coin-marquee-1" count="5" default_currency="usd"]' );

		foreach ( $this->ours( wp_scripts()->queue ) as $handle ) {
			$this->assertNotContains( 'jquery', wp_scripts()->registered[ $handle ]->deps, "{$handle} depends on jQuery." );
		}
	}

	public function test_widget_scripts_carry_a_version_so_updates_reach_browsers(): void {
		if ( ! self::has_react_build() ) {
			$this->markTestSkipped( 'React bundles are not built.' );
		}

		do_shortcode( '[ucwp_widget type="coin-marquee-1" count="5" default_currency="usd"]' );

		foreach ( $this->ours( wp_scripts()->queue ) as $handle ) {
			$this->assertNotEmpty( wp_scripts()->registered[ $handle ]->ver, "{$handle} has no version." );
		}
	}

	public function test_admin_styles_stay_on_the_plugins_own_screens(): void {
		set_current_screen( 'dashboard' );
		do_action( 'admin_enqueue_scripts', 'index.php' );
		$this->assertSame( array(), $this->ours( wp_styles()->queue ) );

		set_current_screen( 'edit-ucwp_widget' );
		do_action( 'admin_enqueue_scripts', 'edit.php' );
		$this->assertContains( 'ucwp-admin-metabox', wp_styles()->queue );

		set_current_screen( 'front' );
	}

	/**
	 * @param string[] $handles
	 * @return string[] Those that belong to this plugin.
	 */
	private function ours( array $handles ): array {
		return array_values(
			array_filter(
				$handles,
				static fn ( string $handle ): bool => 1 === preg_match( '/^(ucw|ultimate-crypto-widget-)/', $handle )
			)
		);
	}

	public function test_several_widgets_of_one_type_load_its_script_once(): void {
		if ( ! self::has_react_build() ) {
			$this->markTestSkipped( 'React bundles are not built.' );
		}

		do_shortcode( str_repeat( '[ucwp_widget type="coin-marquee-1" count="5" default_currency="usd"]', 3 ) );

		$this->assertCount( 2, $this->ours( wp_scripts()->queue ), 'Expected the shared bundle and one widget bundle, not one per widget.' );
	}
}

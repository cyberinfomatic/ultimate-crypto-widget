<?php
/**
 * What [ucwp_widget] prints and hands to React.
 *
 * @package Ultimate_Crypto_Widget
 */

namespace Cyberinfomatic\UltimateCryptoWidget\Tests\Integration;

use Cyberinfomatic\UltimateCryptoWidget\Tests\Support\Snapshots;
use Cyberinfomatic\UltimateCryptoWidget\Tests\Support\TestCase;
use Cyberinfomatic\UltimateCryptoWidget\Tests\Support\WidgetFactory;

/**
 * Pins, for every widget type this plugin provides, the element the shortcode
 * returns and the data it gives the React component.
 */
final class ShortcodeSnapshotTest extends TestCase {

	public function set_up() {
		parent::set_up();

		if ( ! self::has_react_build() ) {
			$this->markTestSkipped( 'React bundles are not built. Run "npm ci && npm run build".' );
		}
	}

	/**
	 * @dataProvider widget_type_provider
	 */
	public function test_saved_widget_renders_as_before( string $type ): void {
		$id = WidgetFactory::create( $type );

		$html = (string) do_shortcode( '[ucwp_widget id="' . $id . '"]' );

		Snapshots::assert_matches( "shortcode/{$type}.html", self::widget_shell( $html ) . "\n" );
		Snapshots::assert_matches( "shortcode/{$type}.data.json", Snapshots::json( self::widget_props( $html ) ) );
	}

	public function test_ad_hoc_widget_from_a_type_attribute(): void {
		$html = (string) do_shortcode( '[ucwp_widget type="crypto-price-table-1" count="5" parent_width="100%" default_currency="usd" search_placeholder="Find"]' );

		Snapshots::assert_matches( 'shortcode/ad-hoc-crypto-price-table-1.html', self::widget_shell( $html ) . "\n" );
		Snapshots::assert_matches( 'shortcode/ad-hoc-crypto-price-table-1.data.json', Snapshots::json( self::widget_props( $html ) ) );
	}

	public function test_two_widgets_of_one_type_each_carry_their_own_data(): void {
		$html = do_shortcode(
			'[ucwp_widget type="crypto-price-table-1" count="5" default_currency="usd"]'
			. '[ucwp_widget type="crypto-price-table-1" count="20" default_currency="eur"]'
		);

		$props = self::widget_props( (string) $html );

		$this->assertCount( 2, $props );
		$this->assertSame( '5', $props[0]['settings']['count'] );
		$this->assertSame( '20', $props[1]['settings']['count'] );
		$this->assertSame( 'eur', $props[1]['settings']['default_currency'] );
	}

	public function test_widget_data_cannot_break_out_of_its_attribute(): void {
		$html = (string) do_shortcode( '[ucwp_widget type="crypto-price-table-1" default_currency="usd" search_placeholder=\'"><script>alert(1)</script>\']' );

		$this->assertStringNotContainsString( '<script>', $html );
		$this->assertCount( 1, self::widget_props( $html ) );
	}

	public function test_a_missing_widget_says_so(): void {
		$this->assertSame( 'Widget with id (999999) does not exist', do_shortcode( '[ucwp_widget id="999999"]' ) );
	}

	public function test_a_pro_widget_type_renders_nothing(): void {
		$this->assertSame( '', (string) do_shortcode( '[ucwp_widget type="historical-price-chart" default_currency="usd"]' ) );
	}

	public function widget_type_provider(): array {
		$types = array();
		foreach ( self::free_widget_types() as $type ) {
			$types[ $type ] = array( $type );
		}
		return $types;
	}
}

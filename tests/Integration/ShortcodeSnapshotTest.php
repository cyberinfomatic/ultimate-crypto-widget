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
 * Pins, for every widget type, the HTML the shortcode returns and the data object
 * (window.ucwpReactData) it gives the React bundle. Valid until the UI redesign
 * (Phase 5), which changes the output on purpose.
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

		$html = do_shortcode( '[ucwp_widget id="' . $id . '"]' );

		Snapshots::assert_matches( "shortcode/{$type}.html", $this->stable( (string) $html ) . "\n" );
		Snapshots::assert_matches( "shortcode/{$type}.data.json", Snapshots::json( $this->react_data() ) );
	}

	public function test_ad_hoc_widget_from_a_type_attribute(): void {
		$html = do_shortcode( '[ucwp_widget type="crypto-price-table-1" count="5" parent_width="100%" default_currency="usd" search_placeholder="Find"]' );

		Snapshots::assert_matches( 'shortcode/ad-hoc-crypto-price-table-1.html', $this->stable( (string) $html ) . "\n" );
		Snapshots::assert_matches( 'shortcode/ad-hoc-crypto-price-table-1.data.json', Snapshots::json( $this->react_data() ) );
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

	/**
	 * The mount point's id is random (uniqid); replace it.
	 */
	private function stable( string $text ): string {
		return preg_replace( '/ucwp-react-[0-9a-f]+/', 'ucwp-react-ID', $text );
	}

	/**
	 * The data passed to the bundle through wp_localize_script, decoded.
	 *
	 * @return array<int, mixed> One entry per enqueued widget script.
	 */
	private function react_data(): array {
		$data = array();
		foreach ( wp_scripts()->registered as $handle => $script ) {
			if ( 0 !== strpos( $handle, 'ucwp-react-' ) || 'ucwp-react-vendor' === $handle ) {
				continue;
			}
			$inline = (string) wp_scripts()->get_data( $handle, 'data' );
			if ( preg_match( '/var ucwpReactData = (.*);\s*$/s', $inline, $match ) ) {
				$decoded = json_decode( $match[1], true );
				if ( isset( $decoded['react_id'] ) ) {
					$decoded['react_id'] = 'ucwp-react-ID';
				}
				$data[] = $decoded;
			}
		}
		return $data;
	}
}

<?php
/**
 * One test per known defect, written as the behaviour we want.
 *
 * @package Ultimate_Crypto_Widget
 */

namespace Cyberinfomatic\UltimateCryptoWidget\Tests\Integration;

use Cyberinfomatic\UltimateCryptoWidget\Tests\Support\TestCase;
use Cyberinfomatic\UltimateCryptoWidget\Tests\Support\WidgetFactory;

/**
 * Each test states the correct behaviour inside known_bug(). It reports as
 * "incomplete" while the defect exists and fails once the defect is fixed, which is
 * the cue to remove the wrapper and move the test to RegressionTest.
 */
final class KnownBugsTest extends TestCase {

	public function test_multi_select_fields_are_saved(): void {
		$this->known_bug(
			'Multi-select fields post an array, which sanitize_text_field() turns into an empty string (fixed in Phase 3).',
			function () {
				$id = WidgetFactory::create( 'multi-currencies-tab', array( 'ucwp_widget_coins' => array( 'bitcoin', 'ethereum' ) ) );

				$stored = get_post_meta( $id, 'ucwp_widget_coins', true );
				$this->assertNotSame( '', $stored, 'The selected coins were not stored.' );
				$this->assertStringContainsString( 'ethereum', is_array( $stored ) ? implode( ',', $stored ) : (string) $stored );
			}
		);
	}

	public function test_a_widget_saved_without_a_type_does_not_crash_the_page(): void {
		$this->known_bug(
			'A widget post with no saved type (e.g. a Pro type was chosen) makes its shortcode throw a TypeError, a fatal error on the page (fixed in Phase 3).',
			function () {
				$id = WidgetFactory::create( 'historical-price-chart' ); // Pro: refused, so nothing is stored.

				$html = do_shortcode( '[ucwp_widget id="' . $id . '"]' );

				$this->assertIsString( $html );
			}
		);
	}
}

<?php
/**
 * One test per known defect, written as the behaviour we want.
 *
 * @package Ultimate_Crypto_Widget
 */

namespace Cyberinfomatic\UltimateCryptoWidget\Tests\Integration;

use Cyberinfomatic\UltimateCryptoWidget\Helpers\APIHelper;
use Cyberinfomatic\UltimateCryptoWidget\Tests\Support\TestCase;
use Cyberinfomatic\UltimateCryptoWidget\Tests\Support\WidgetFactory;
use WP_REST_Request;

/**
 * Each test states the correct behaviour inside known_bug(). It reports as
 * "incomplete" while the defect exists and fails once the defect is fixed, which is
 * the cue to remove the wrapper and move the test to RegressionTest.
 */
final class KnownBugsTest extends TestCase {

	/**
	 * Set by the probe shortcode below.
	 *
	 * @var bool
	 */
	public static bool $probe_ran = false;

	public function set_up() {
		parent::set_up();
		self::$probe_ran = false;
	}

	public function tear_down() {
		global $wp_rest_server;
		$wp_rest_server = null;
		remove_shortcode( 'ucwp_probe' );
		parent::tear_down();
	}

	public function test_load_shortcode_cannot_run_another_shortcode(): void {
		add_shortcode(
			'ucwp_probe',
			static function () {
				self::$probe_ran = true;
				return '';
			}
		);

		$this->known_bug(
			'An anonymous POST to /load-shortcode can break out of an attribute and run any shortcode (fixed in Phase 2).',
			function () {
				global $wp_rest_server;
				$wp_rest_server = null;
				rest_get_server();
				wp_set_current_user( 0 );

				$request = new WP_REST_Request( 'POST', '/ultimate-crypto-widget/v1/load-shortcode' );
				$request->set_body_params(
					array(
						'shortcode' => 'ucwp_widget',
						'atts'      => array( 'type' => 'x"] [ucwp_probe] [ucwp_widget type="x' ),
					)
				);
				$response = rest_get_server()->dispatch( $request );

				$this->assertFalse( self::$probe_ran, 'A second shortcode was executed from request input.' );
				$this->assertContains( $response->get_status(), array( 401, 403 ), 'An anonymous visitor must be refused.' );
			}
		);
	}

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

	public function test_clear_cache_works_after_the_key_list_expired(): void {
		$this->known_bug(
			'The list of cache keys lives in a transient; once it expires, "Clear API cache" finds nothing to clear (fixed in Phase 2).',
			function () {
				APIHelper::cache( 'ucwp_test_entry', array( 'price' => 1 ), 60 );
				delete_transient( 'ucwp_cache_keys' ); // What expiry does.

				APIHelper::clear_cache();

				$this->assertFalse( get_transient( 'ucwp_test_entry' ), 'A cached API response survived "clear cache".' );
			}
		);
	}
}

<?php
/**
 * Defects that are fixed and must stay fixed.
 *
 * @package Ultimate_Crypto_Widget
 */

namespace Cyberinfomatic\UltimateCryptoWidget\Tests\Integration;

use Cyberinfomatic\UltimateCryptoWidget\Widgets\WidgetShortcode;
use Cyberinfomatic\UltimateCryptoWidget\Legacy\Services;
use Cyberinfomatic\UltimateCryptoWidget\Tests\Support\HttpStub;
use Cyberinfomatic\UltimateCryptoWidget\Tests\Support\TestCase;
use Cyberinfomatic\UltimateCryptoWidget\Tests\Support\WidgetFactory;

/**
 * These were open in the Pro 0.0.5 code and are already fixed in this repository.
 * The rewrite must not bring them back.
 */
final class RegressionTest extends TestCase {

	/**
	 * Set by the probe class below.
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
		parent::tear_down();
	}


	public function test_there_is_no_pro_reinstall_route(): void {
		$this->assertArrayNotHasKey( '/ultimate-crypto-widget/v1/pro-reinstall', $this->routes() );
	}

	public function test_shortcode_attributes_are_never_unserialized_into_objects(): void {
		$payload = serialize( new UnserializeProbe() ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize

		$shortcode = Services::container()->get( WidgetShortcode::class );
		$shortcode(
			array(
				'type'             => 'multi-currencies-tab',
				'default_currency' => 'usd',
				'count'            => $payload,
				'coins'            => $payload,
				'currencies'       => $payload,
			)
		);

		$this->assertFalse( self::$probe_ran, 'An object from a shortcode attribute was instantiated.' );
	}

	public function test_saving_without_the_widget_form_raises_no_warning(): void {
		$_POST    = array();
		$warnings = array();

		// The handler catches exceptions, so collect PHP warnings directly.
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_set_error_handler
		set_error_handler(
			static function ( int $level, string $message ) use ( &$warnings ) {
				$warnings[] = $message;
				return true;
			},
			E_WARNING | E_NOTICE
		);
		try {
			self::factory()->post->create( array( 'post_type' => 'ucwp_widget' ) );
		} finally {
			restore_error_handler();
		}

		$this->assertSame( array(), $warnings );
	}

	public function test_only_administrators_can_clear_the_cache(): void {
		Services::prices()->markets(); // Warm the cache.
		$calls = count( HttpStub::$requests );

		wp_set_current_user( self::factory()->user->create( array( 'role' => 'subscriber' ) ) );
		$_POST['security'] = wp_create_nonce( 'ucwp_clear_api_cache' );

		try {
			do_action( 'admin_post_ucwp_clear_api_cache' );
		} catch ( \WPDieException $e ) {
			unset( $e ); // Being refused is the correct outcome.
		}

		Services::prices()->markets();
		$this->assertSame( $calls, count( HttpStub::$requests ), 'A subscriber cleared the API cache.' );
	}

	public function test_clearing_the_cache_forgets_every_cached_price(): void {
		$prices = Services::prices();
		$prices->markets();
		$prices->coin( 'bitcoin' );
		$prices->markets(); // From the cache.
		$this->assertSame( 2, count( HttpStub::$requests ) );

		$prices->clearCache();

		$prices->markets();
		$prices->coin( 'bitcoin' );
		$this->assertSame( 4, count( HttpStub::$requests ), 'Cached prices survived "clear cache".' );
	}

	public function test_multi_select_fields_are_saved_and_shown_as_selected(): void {
		$id = WidgetFactory::create(
			'multi-currencies-tab',
			array(
				'ucwp_widget_coins'      => array( 'bitcoin', 'ethereum' ),
				'ucwp_widget_currencies' => array( 'usd', 'eur', 'ngn' ),
			)
		);

		$this->assertSame( 'bitcoin,ethereum', get_post_meta( $id, 'ucwp_widget_coins', true ) );
		$this->assertSame( 'usd,eur,ngn', get_post_meta( $id, 'ucwp_widget_currencies', true ) );

		ob_start();
		\Cyberinfomatic\UltimateCryptoWidget\Controllers\WidgetPostType::get_instance()->get_metabox()->callback( get_post( $id ) );
		$form = ob_get_clean();

		$this->assertMatchesRegularExpression( '/<option value="ngn"\s+selected/', $form );
		$this->assertDoesNotMatchRegularExpression( '/<option value="gbp"\s+selected/', $form );
	}

	public function test_a_widget_saved_without_a_type_does_not_crash_the_page(): void {
		$id = WidgetFactory::create( 'historical-price-chart' ); // Pro: refused, so no type is stored.

		wp_set_current_user( 0 );
		$this->assertSame( '', do_shortcode( '[ucwp_widget id="' . $id . '"]' ) );

		wp_set_current_user( self::factory()->user->create( array( 'role' => 'editor' ) ) );
		$this->assertStringContainsString( 'Choose a widget type', do_shortcode( '[ucwp_widget id="' . $id . '"]' ) );
	}

	public function test_a_shortcode_attribute_cannot_change_a_saved_widgets_type(): void {
		$id = WidgetFactory::create( 'crypto-price-table-1' );

		do_shortcode( '[ucwp_widget id="' . $id . '" type="coin-marquee-1"]' );

		$components = array();
		foreach ( wp_scripts()->registered as $handle => $script ) {
			if ( preg_match( '/"ucwp_render_component":"([^"]+)"/', (string) wp_scripts()->get_data( $handle, 'data' ), $match ) ) {
				$components[] = stripslashes( $match[1] );
			}
		}
		$this->assertSame( array( 'widgets/crypto-price-table/index' ), $components );
	}

	public function test_a_draft_widget_is_hidden_from_visitors(): void {
		$id = WidgetFactory::create( 'crypto-price-table-1' );
		wp_update_post( array( 'ID' => $id, 'post_status' => 'draft' ) );

		wp_set_current_user( 0 );

		$this->assertStringContainsString( 'does not exist', do_shortcode( '[ucwp_widget id="' . $id . '"]' ) );
	}

	/**
	 * @return array<string, array> The plugin's routes with their handlers.
	 */
	private function routes(): array {
		global $wp_rest_server;
		$wp_rest_server = null;

		return rest_get_server()->get_routes( 'ultimate-crypto-widget/v1' );
	}
}

/**
 * Records that it was rebuilt from a serialized string.
 */
final class UnserializeProbe { // phpcs:ignore Generic.Files.OneObjectStructurePerFile.MultipleFound

	public function __wakeup() {
		RegressionTest::$probe_ran = true;
	}
}

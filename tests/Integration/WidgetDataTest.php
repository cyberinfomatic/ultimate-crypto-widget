<?php
/**
 * What the plugin stores for a widget.
 *
 * @package Ultimate_Crypto_Widget
 */

namespace Cyberinfomatic\UltimateCryptoWidget\Tests\Integration;

use Cyberinfomatic\UltimateCryptoWidget\Admin\WidgetEditor;
use Cyberinfomatic\UltimateCryptoWidget\Tests\Support\Services;
use Cyberinfomatic\UltimateCryptoWidget\Tests\Support\Snapshots;
use Cyberinfomatic\UltimateCryptoWidget\Tests\Support\TestCase;
use Cyberinfomatic\UltimateCryptoWidget\Tests\Support\WidgetFactory;

/**
 * Pins the database rows written by the current save handler. The rewrite must
 * read these rows unchanged and write the same shape back.
 */
final class WidgetDataTest extends TestCase {

	public function test_the_post_type_is_registered_as_today(): void {
		$type = get_post_type_object( 'ucwp_widget' );

		$this->assertNotNull( $type );
		$this->assertFalse( $type->public );
		$this->assertTrue( $type->show_ui );
		$this->assertFalse( $type->show_in_menu );
		$this->assertSame( 'page', $type->capability_type );
		$this->assertTrue( post_type_supports( 'ucwp_widget', 'title' ) );
		$this->assertFalse( post_type_supports( 'ucwp_widget', 'editor' ) );
	}

	public function test_the_shortcode_is_registered(): void {
		$this->assertTrue( shortcode_exists( 'ucwp_widget' ) );
	}

	/**
	 * @dataProvider widget_type_provider
	 */
	public function test_saved_rows_match_the_fixture( string $type ): void {
		$id = WidgetFactory::create( $type );

		$this->assertSame( 'ucwp_widget', get_post_type( $id ) );
		Snapshots::assert_matches( "rows/{$type}.json", Snapshots::json( WidgetFactory::rows( $id ) ) );
	}

	public function test_exactly_the_thirteen_known_meta_keys_are_written(): void {
		$id = WidgetFactory::create( 'coin-marquee-1' );

		$this->assertSame(
			array(
				'ucwp_widget_card_width',
				'ucwp_widget_coins',
				'ucwp_widget_count',
				'ucwp_widget_currencies',
				'ucwp_widget_dark_mode',
				'ucwp_widget_data_interval',
				'ucwp_widget_default_currency',
				'ucwp_widget_no_of_days',
				'ucwp_widget_orientation',
				'ucwp_widget_parent_width',
				'ucwp_widget_search_placeholder',
				'ucwp_widget_speed',
				'ucwp_widget_type',
			),
			array_keys( WidgetFactory::rows( $id ) )
		);
	}

	public function test_a_pro_widget_type_is_not_saved(): void {
		$id = WidgetFactory::create( 'historical-price-chart' );

		$this->assertArrayNotHasKey( 'ucwp_widget_type', WidgetFactory::rows( $id ), 'A Pro type must not be stored by the free plugin.' );
		// The rest of the form is kept, so nothing has to be typed again once a usable type is chosen.
		$this->assertSame( array( '10' ), WidgetFactory::rows( $id )['ucwp_widget_count'] ?? null );
	}

	public function test_a_refused_field_is_reported_to_whoever_saved(): void {
		wp_set_current_user( WidgetFactory::editor() );
		WidgetFactory::create( 'historical-price-chart' );

		ob_start();
		Services::container()->get( WidgetEditor::class )->box()->printErrors();
		$notice = (string) ob_get_clean();

		$this->assertStringContainsString( 'role="alert"', $notice );
		$this->assertStringContainsString( 'Widget type', $notice );
	}

	public function test_someone_who_cannot_edit_widgets_cannot_save_one(): void {
		$id = WidgetFactory::create( 'coin-marquee-1' );

		wp_set_current_user( self::factory()->user->create( array( 'role' => 'subscriber' ) ) );
		$_POST = array( 'ucwp_widget_count' => '5' ) + WidgetFactory::nonce();
		do_action( 'save_post_ucwp_widget', $id, get_post( $id ), true );
		$_POST = array();

		$this->assertSame( '10', get_post_meta( $id, 'ucwp_widget_count', true ) );
	}

	public function test_a_setting_outside_its_limits_is_refused_and_the_old_value_kept(): void {
		$id = WidgetFactory::create( 'coin-marquee-1' );

		wp_set_current_user( WidgetFactory::editor() );
		$_POST = array(
			'ucwp_widget_speed'        => '999999',
			'ucwp_widget_parent_width' => '100%;background:red',
			'ucwp_widget_card_width'   => '200',
		) + WidgetFactory::nonce();
		do_action( 'save_post_ucwp_widget', $id, get_post( $id ), true );
		$_POST = array();

		$this->assertSame( '3000', get_post_meta( $id, 'ucwp_widget_speed', true ) );
		$this->assertSame( '400', get_post_meta( $id, 'ucwp_widget_parent_width', true ) );
		$this->assertSame( '200', get_post_meta( $id, 'ucwp_widget_card_width', true ), 'A valid field beside invalid ones is still saved.' );
	}

	public function test_a_list_keeps_only_real_ids_and_stays_within_its_limit(): void {
		$id = WidgetFactory::create(
			'multi-currencies-tab',
			array(
				'ucwp_widget_coins'      => array( 'bitcoin', '../etc', 'eth ereum', 'solana' ),
				'ucwp_widget_currencies' => array( 'usd', 'eur', 'gbp', 'ngn', 'jpy' ),
			)
		);

		$this->assertSame( 'bitcoin,solana', get_post_meta( $id, 'ucwp_widget_coins', true ) );
		$this->assertSame( 'usd,eur,gbp', get_post_meta( $id, 'ucwp_widget_currencies', true ) );
	}

	public function test_clearing_a_list_in_the_form_clears_it(): void {
		$id = WidgetFactory::create( 'multi-currencies-tab' );
		$this->assertSame( 'bitcoin,ethereum', get_post_meta( $id, 'ucwp_widget_coins', true ) );

		wp_set_current_user( WidgetFactory::editor() );
		// What the form posts when nothing is selected: only its empty hidden input.
		$_POST = array( 'ucwp_widget_coins' => '' ) + WidgetFactory::nonce();
		do_action( 'save_post_ucwp_widget', $id, get_post( $id ), true );
		$_POST = array();

		$this->assertSame( '', get_post_meta( $id, 'ucwp_widget_coins', true ) );
	}

	public function widget_type_provider(): array {
		$types = array();
		foreach ( self::free_widget_types() as $type ) {
			$types[ $type ] = array( $type );
		}
		return $types;
	}
}

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
		$id = WidgetFactory::create( self::LOCKED_TYPE );

		$this->assertArrayNotHasKey( 'ucwp_widget_type', WidgetFactory::rows( $id ), 'A Pro type must not be stored by the free plugin.' );
		// The rest of the form is kept, so nothing has to be typed again once a usable type is chosen.
		$this->assertSame( array( '10' ), WidgetFactory::rows( $id )['ucwp_widget_count'] ?? null );
	}

	public function test_a_refused_field_is_reported_to_whoever_saved(): void {
		wp_set_current_user( WidgetFactory::editor() );
		WidgetFactory::create( self::LOCKED_TYPE );

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

	public function test_a_list_is_stored_the_way_the_toolkit_stores_one(): void {
		global $wpdb;
		$id = WidgetFactory::create( 'multi-currencies-tab' );

		$this->assertSame( array( 'bitcoin', 'ethereum' ), get_post_meta( $id, 'ucwp_widget_coins', true ) );
		$this->assertCount( 1, get_post_meta( $id, 'ucwp_widget_coins', false ), 'One row, not one per value.' );
		$this->assertSame(
			'a:2:{i:0;s:7:"bitcoin";i:1;s:8:"ethereum";}',
			$wpdb->get_var( $wpdb->prepare( "SELECT meta_value FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key = 'ucwp_widget_coins'", $id ) )
		);
	}

	public function test_a_value_that_is_not_on_offer_refuses_the_whole_field_and_keeps_the_old_one(): void {
		$id = WidgetFactory::create( 'multi-currencies-tab' );

		wp_set_current_user( WidgetFactory::editor() );
		$_POST = array( 'ucwp_widget_coins' => array( '', 'bitcoin', '../etc', 'eth ereum' ), 'ucwp_widget_count' => '5' ) + WidgetFactory::nonce();
		do_action( 'save_post_ucwp_widget', $id, get_post( $id ), true );
		$_POST = array();

		$this->assertSame( array( 'bitcoin', 'ethereum' ), get_post_meta( $id, 'ucwp_widget_coins', true ), 'Nothing from the bad list is stored.' );
		$this->assertSame( '5', get_post_meta( $id, 'ucwp_widget_count', true ), 'The other fields are still saved.' );
	}

	/**
	 * @dataProvider larger_count_provider
	 */
	public function test_a_widget_can_show_up_to_the_top_100_coins( string $count ): void {
		$id = WidgetFactory::create( 'crypto-heatmap' );

		wp_set_current_user( WidgetFactory::editor() );
		$_POST = array( 'ucwp_widget_count' => $count ) + WidgetFactory::nonce();
		do_action( 'save_post_ucwp_widget', $id, get_post( $id ), true );
		$_POST = array();

		$this->assertSame( $count, get_post_meta( $id, 'ucwp_widget_count', true ) );
	}

	public function larger_count_provider(): array {
		return array( '25' => array( '25' ), '50' => array( '50' ), '100' => array( '100' ) );
	}

	public function test_a_count_that_is_not_offered_is_refused_and_the_old_one_kept(): void {
		$id = WidgetFactory::create( 'crypto-heatmap' );

		wp_set_current_user( WidgetFactory::editor() );
		$_POST = array( 'ucwp_widget_count' => '250' ) + WidgetFactory::nonce();
		do_action( 'save_post_ucwp_widget', $id, get_post( $id ), true );
		$_POST = array();

		$this->assertSame( '10', get_post_meta( $id, 'ucwp_widget_count', true ) );
	}

	public function test_exactly_the_currency_limit_is_accepted(): void {
		$id = WidgetFactory::create( 'multi-currencies-tab', array( 'ucwp_widget_currencies' => array( 'usd', 'eur', 'gbp' ) ) );

		$this->assertSame( array( 'usd', 'eur', 'gbp' ), get_post_meta( $id, 'ucwp_widget_currencies', true ) );
	}

	public function test_one_over_the_currency_limit_is_refused_with_a_message_and_the_old_choice_kept(): void {
		$id = WidgetFactory::create( 'multi-currencies-tab' );
		$this->assertSame( array( 'usd', 'eur' ), get_post_meta( $id, 'ucwp_widget_currencies', true ) );

		wp_set_current_user( WidgetFactory::editor() );
		$_POST = array( 'ucwp_widget_currencies' => array( '', 'usd', 'eur', 'gbp', 'ngn' ), 'ucwp_widget_count' => '5' ) + WidgetFactory::nonce();
		do_action( 'save_post_ucwp_widget', $id, get_post( $id ), true );
		$_POST = array();

		$this->assertSame( array( 'usd', 'eur' ), get_post_meta( $id, 'ucwp_widget_currencies', true ), 'The old choice is kept.' );
		$this->assertSame( '5', get_post_meta( $id, 'ucwp_widget_count', true ), 'The other fields are saved.' );

		// The person is told, on the next admin page.
		ob_start();
		do_action( 'admin_notices' );
		$notice = (string) ob_get_clean();
		$this->assertStringContainsString( 'role="alert"', $notice );
		$this->assertStringContainsString( 'Other currencies: choose up to 3 (you chose 4)', $notice );
	}

	public function test_empty_entries_do_not_count_towards_the_limit(): void {
		$id = WidgetFactory::create( 'multi-currencies-tab' );

		wp_set_current_user( WidgetFactory::editor() );
		// The form always sends an empty entry first.
		$_POST = array( 'ucwp_widget_currencies' => array( '', 'jpy', 'eur', 'gbp' ) ) + WidgetFactory::nonce();
		do_action( 'save_post_ucwp_widget', $id, get_post( $id ), true );
		$_POST = array();

		$this->assertSame( array( 'jpy', 'eur', 'gbp' ), get_post_meta( $id, 'ucwp_widget_currencies', true ) );
	}

	public function test_clearing_a_list_in_the_form_clears_it_and_removes_the_row(): void {
		$id = WidgetFactory::create( 'multi-currencies-tab' );
		$this->assertTrue( metadata_exists( 'post', $id, 'ucwp_widget_coins' ) );

		wp_set_current_user( WidgetFactory::editor() );
		// What the form posts when nothing is ticked: only its empty marker.
		$_POST = array( 'ucwp_widget_coins' => array( '' ), 'ucwp_widget_currencies' => array( '' ) ) + WidgetFactory::nonce();
		do_action( 'save_post_ucwp_widget', $id, get_post( $id ), true );
		$_POST = array();

		$this->assertFalse( metadata_exists( 'post', $id, 'ucwp_widget_coins' ), 'An empty selection is no row at all.' );
		$this->assertFalse( metadata_exists( 'post', $id, 'ucwp_widget_currencies' ) );
	}

	public function widget_type_provider(): array {
		$types = array();
		foreach ( self::free_widget_types() as $type ) {
			$types[ $type ] = array( $type );
		}
		return $types;
	}
}

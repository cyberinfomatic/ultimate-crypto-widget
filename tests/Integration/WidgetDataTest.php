<?php
/**
 * What the plugin stores for a widget.
 *
 * @package Ultimate_Crypto_Widget
 */

namespace Cyberinfomatic\UltimateCryptoWidget\Tests\Integration;

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

		$this->assertSame( array(), WidgetFactory::rows( $id ), 'A Pro type must not be stored by the free plugin.' );
	}

	public function widget_type_provider(): array {
		$types = array();
		foreach ( self::free_widget_types() as $type ) {
			$types[ $type ] = array( $type );
		}
		return $types;
	}
}

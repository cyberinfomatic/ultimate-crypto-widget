<?php
/**
 * Moving coins and currencies from the comma form to the toolkit's array.
 *
 * @package Ultimate_Crypto_Widget
 */

namespace Cyberinfomatic\UltimateCryptoWidget\Tests\Integration;

use Cyberinfomatic\UltimateCryptoWidget\Migrations\ConvertCommaLists;
use Cyberinfomatic\UltimateCryptoWidget\Tests\Support\TestCase;
use Cyberinfomatic\UltimateCryptoWidget\Tests\Support\WidgetFactory;

/**
 * Rows in the comma form were written only by pre-release 1.0 builds; released 0.x versions stored
 * an empty string. These tests use made-up rows, not a real site's database.
 */
final class ConvertCommaListsTest extends TestCase {

	/** A widget with no settings rows of its own for the two list fields. */
	private function bare_widget(): int {
		$id = self::factory()->post->create( array( 'post_type' => 'ucwp_widget', 'post_status' => 'publish' ) );
		foreach ( array( 'ucwp_widget_type' => 'multi-currencies-tab', 'ucwp_widget_default_currency' => 'usd' ) as $key => $value ) {
			add_post_meta( $id, $key, $value );
		}

		return $id;
	}

	/**
	 * @param mixed $value Stored exactly as given: text as text, an array serialized by WordPress.
	 */
	private function widget_with( string $key, $value ): int {
		$id = $this->bare_widget();
		add_post_meta( $id, $key, $value );

		return $id;
	}

	private function run_migration(): void {
		( new ConvertCommaLists() )->up();
		wp_cache_flush();
	}

	public function test_it_has_a_valid_id_and_is_registered_to_run(): void {
		$migration = new ConvertCommaLists();
		$migration->assertValidId();

		$ids = array_map( static fn ( $m ) => $m->id(), \Cyberinfomatic\UltimateCryptoWidget\Plugin::app()->resolvedMigrations() );
		$this->assertContains( $migration->id(), $ids );
	}

	public function test_a_comma_row_becomes_an_array(): void {
		$id = $this->widget_with( 'ucwp_widget_coins', 'bitcoin,ethereum,solana' );

		$this->run_migration();

		$this->assertSame( array( 'bitcoin', 'ethereum', 'solana' ), get_post_meta( $id, 'ucwp_widget_coins', true ) );
		$this->assertCount( 1, get_post_meta( $id, 'ucwp_widget_coins', false ) );
	}

	public function test_both_fields_are_converted(): void {
		$id = $this->widget_with( 'ucwp_widget_coins', 'bitcoin,ethereum' );
		add_post_meta( $id, 'ucwp_widget_currencies', 'usd,eur' );

		$this->run_migration();

		$this->assertSame( array( 'bitcoin', 'ethereum' ), get_post_meta( $id, 'ucwp_widget_coins', true ) );
		$this->assertSame( array( 'usd', 'eur' ), get_post_meta( $id, 'ucwp_widget_currencies', true ) );
	}

	public function test_spaces_and_empty_items_are_dropped_and_repeats_removed(): void {
		$id = $this->widget_with( 'ucwp_widget_coins', ' bitcoin , ethereum,, solana ,bitcoin,' );

		$this->run_migration();

		$this->assertSame( array( 'bitcoin', 'ethereum', 'solana' ), get_post_meta( $id, 'ucwp_widget_coins', true ) );
	}

	public function test_an_already_converted_row_is_left_alone(): void {
		global $wpdb;
		$id  = $this->widget_with( 'ucwp_widget_coins', array( 'bitcoin', 'ethereum' ) );
		$sql = $wpdb->prepare( "SELECT meta_id FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key = 'ucwp_widget_coins'", $id );
		$was = $wpdb->get_var( $sql );

		$this->run_migration();

		$this->assertSame( array( 'bitcoin', 'ethereum' ), get_post_meta( $id, 'ucwp_widget_coins', true ) );
		$this->assertSame( $was, $wpdb->get_var( $sql ), 'The row itself was not rewritten.' );
	}

	public function test_an_empty_row_is_left_alone(): void {
		// What the released 0.x plugins stored.
		$id = $this->widget_with( 'ucwp_widget_coins', '' );

		$this->run_migration();

		$this->assertSame( array( '' ), get_post_meta( $id, 'ucwp_widget_coins', false ) );
	}

	public function test_a_widget_with_no_row_is_left_alone(): void {
		$id = $this->bare_widget();

		$this->run_migration();

		$this->assertFalse( metadata_exists( 'post', $id, 'ucwp_widget_coins' ) );
		$this->assertSame( 'usd', get_post_meta( $id, 'ucwp_widget_default_currency', true ) );
	}

	public function test_a_single_value_without_a_comma_is_left_alone_and_still_reads_as_one_choice(): void {
		$id = $this->widget_with( 'ucwp_widget_coins', 'bitcoin' );

		$this->run_migration();

		$this->assertSame( 'bitcoin', get_post_meta( $id, 'ucwp_widget_coins', true ) );
		$this->assertSame( 'bitcoin', $this->widget_settings( $id )['coins'], 'The widget still shows its coin.' );
	}

	public function test_a_row_with_nothing_in_it_but_commas_is_removed(): void {
		$id = $this->widget_with( 'ucwp_widget_coins', ' , ,, ' );

		$this->run_migration();

		$this->assertFalse( metadata_exists( 'post', $id, 'ucwp_widget_coins' ) );
	}

	public function test_only_the_two_list_fields_of_widgets_are_touched(): void {
		$widget = $this->widget_with( 'ucwp_widget_search_placeholder', 'Find a coin, any coin' );
		add_post_meta( $widget, 'ucwp_widget_parent_width', '1,2' );
		$page = self::factory()->post->create( array( 'post_type' => 'page' ) );
		add_post_meta( $page, 'ucwp_widget_coins', 'bitcoin,ethereum' );

		$this->run_migration();

		$this->assertSame( 'Find a coin, any coin', get_post_meta( $widget, 'ucwp_widget_search_placeholder', true ) );
		$this->assertSame( '1,2', get_post_meta( $widget, 'ucwp_widget_parent_width', true ) );
		$this->assertSame( 'bitcoin,ethereum', get_post_meta( $page, 'ucwp_widget_coins', true ), 'Not a widget.' );
	}

	public function test_running_it_again_changes_nothing(): void {
		global $wpdb;
		$id = $this->widget_with( 'ucwp_widget_coins', 'bitcoin, ethereum' );

		$this->run_migration();
		$first = $wpdb->get_results( "SELECT meta_id, meta_value FROM {$wpdb->postmeta} WHERE meta_key LIKE 'ucwp_widget_%' ORDER BY meta_id", ARRAY_A );
		$this->run_migration();
		$second = $wpdb->get_results( "SELECT meta_id, meta_value FROM {$wpdb->postmeta} WHERE meta_key LIKE 'ucwp_widget_%' ORDER BY meta_id", ARRAY_A );

		$this->assertSame( $first, $second );
		$this->assertSame( array( 'bitcoin', 'ethereum' ), get_post_meta( $id, 'ucwp_widget_coins', true ) );
	}

	public function test_it_works_through_more_rows_than_one_batch(): void {
		$ids = array();
		for ( $i = 0; $i < 5; $i++ ) {
			$ids[] = $this->widget_with( 'ucwp_widget_coins', "bitcoin,coin-{$i}" );
		}
		$migration = new ConvertCommaLists();

		$this->assertTrue( $migration->batch( 2 ), 'Rows remain after the first batch.' );
		$this->assertTrue( $migration->batch( 2 ), 'And after the second.' );
		$this->assertFalse( $migration->batch( 2 ), 'The last batch finishes it.' );
		$this->assertFalse( $migration->batch( 2 ), 'A finished migration has nothing left.' );

		wp_cache_flush();
		foreach ( $ids as $i => $id ) {
			$this->assertSame( array( 'bitcoin', "coin-{$i}" ), get_post_meta( $id, 'ucwp_widget_coins', true ) );
		}
	}

	/**
	 * The point of it all: a widget shows the same before and after, and the same as the form saves.
	 */
	public function test_a_widget_renders_the_same_in_every_form_it_has_been_stored_in(): void {
		if ( ! self::has_react_build() ) {
			$this->markTestSkipped( 'React bundles are not built.' );
		}
		$settings = array(
			'type'               => 'multi-currencies-tab',
			'count'              => '10',
			'orientation'        => 'horizontal',
			'speed'              => '3000',
			'card_width'         => '157',
			'parent_width'       => '400',
			'default_currency'   => 'usd',
			'no_of_days'         => '7',
			'data_interval'      => 'daily',
			'search_placeholder' => 'Search Coin',
			'dark_mode'          => 'false',
		);

		// 1. Saved by the form now: arrays.
		$saved_now = WidgetFactory::create( 'multi-currencies-tab' );

		// 2. Saved by a pre-release 1.0 build: comma text, before the migration.
		$comma_before = $this->bare_widget();
		foreach ( $settings as $name => $value ) {
			delete_post_meta( $comma_before, 'ucwp_widget_' . $name );
			add_post_meta( $comma_before, 'ucwp_widget_' . $name, $value );
		}
		add_post_meta( $comma_before, 'ucwp_widget_coins', 'bitcoin,ethereum' );
		add_post_meta( $comma_before, 'ucwp_widget_currencies', 'usd,eur' );

		// 3. The same, after the migration.
		$comma_after = $this->bare_widget();
		foreach ( $settings as $name => $value ) {
			delete_post_meta( $comma_after, 'ucwp_widget_' . $name );
			add_post_meta( $comma_after, 'ucwp_widget_' . $name, $value );
		}
		add_post_meta( $comma_after, 'ucwp_widget_coins', 'bitcoin,ethereum' );
		add_post_meta( $comma_after, 'ucwp_widget_currencies', 'usd,eur' );
		$this->run_migration();

		// 4. No saved widget at all: the shortcode's own attributes, comma separated as ever.
		$attributes = '';
		foreach ( $settings + array( 'coins' => 'bitcoin,ethereum', 'currencies' => 'usd,eur' ) as $name => $value ) {
			$attributes .= ' ' . $name . '="' . $value . '"';
		}

		$expected = self::widget_props( (string) do_shortcode( '[ucwp_widget' . $attributes . ']' ) )[0];
		$this->assertSame( 'bitcoin,ethereum', $expected['settings']['coins'], 'The widget is told its coins as text, as ever.' );
		$this->assertSame( 'usd,eur', $expected['settings']['currencies'] );

		foreach ( array( 'form' => $saved_now, 'comma, not migrated' => $comma_before, 'comma, migrated' => $comma_after ) as $label => $id ) {
			$this->assertEquals( $expected, self::widget_props( (string) do_shortcode( '[ucwp_widget id="' . $id . '"]' ) )[0], "A widget saved as: {$label}." );
		}
	}

	/**
	 * @return array<string, string>
	 */
	private function widget_settings( int $id ): array {
		return ( new \Cyberinfomatic\UltimateCryptoWidget\Widgets\WidgetRepository() )->settings( $id ) ?? array();
	}
}

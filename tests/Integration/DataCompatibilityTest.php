<?php
/**
 * Widgets saved by the 0.x plugins keep working.
 *
 * @package Ultimate_Crypto_Widget
 */

namespace Cyberinfomatic\UltimateCryptoWidget\Tests\Integration;

use Cyberinfomatic\UltimateCryptoWidget\Legacy\Services;
use Cyberinfomatic\UltimateCryptoWidget\Tests\Support\TestCase;
use Cyberinfomatic\UltimateCryptoWidget\Tests\Support\WidgetFactory;
use Cyberinfomatic\UltimateCryptoWidget\Widgets\WidgetFields;
use Cyberinfomatic\UltimateCryptoWidget\Widgets\WidgetRepository;

/**
 * tests/fixtures/legacy-0.x holds the exact meta rows the free 0.1.5 and Pro 0.0.5
 * plugins wrote for every widget type, and what the free ones rendered. These tests
 * put those rows in the database untouched and check the current code against them.
 */
final class DataCompatibilityTest extends TestCase {

	private const FIXTURES = __DIR__ . '/../fixtures/legacy-0.x';

	/**
	 * @dataProvider legacy_type_provider
	 */
	public function test_rows_written_by_0x_are_read_without_being_changed( string $type ): void {
		$rows = $this->legacy_rows( $type );
		$id   = $this->insert_legacy_widget( $rows );

		$settings = ( new WidgetRepository() )->settings( $id );

		$this->assertSame( $type, $settings['type'] );
		foreach ( $rows as $key => $values ) {
			$name = substr( $key, strlen( WidgetFields::META_PREFIX ) );
			$this->assertSame( $values[0], $settings[ $name ], "Setting {$name} was not read as stored." );
		}

		// Reading must never write.
		$this->assertSame( $rows, WidgetFactory::rows( $id ) );
	}

	/**
	 * @dataProvider free_type_provider
	 */
	public function test_a_0x_widget_renders_what_it_rendered_before( string $type ): void {
		if ( ! self::has_react_build() ) {
			$this->markTestSkipped( 'React bundles are not built.' );
		}

		$id = $this->insert_legacy_widget( $this->legacy_rows( $type ) );

		$html = do_shortcode( '[ucwp_widget id="' . $id . '"]' );
		$data = $this->react_data();

		$this->assertSame(
			trim( (string) file_get_contents( self::FIXTURES . "/shortcode/{$type}.html" ) ), // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
			trim( preg_replace( '/ucwp-react-[0-9a-f]+/', 'ucwp-react-ID', (string) $html ) )
		);

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$expected = json_decode( (string) file_get_contents( self::FIXTURES . "/shortcode/{$type}.data.json" ), true )[0];

		// One deliberate difference: with no currency chosen, 0.x produced the
		// meaningless map {"": ""}; it is now an empty list.
		if ( array( '' => '' ) === ( $expected['default_currencies_symbol'] ?? null ) ) {
			$expected['default_currencies_symbol'] = array();
		}

		$this->assertEquals( $expected, $data );
	}

	public function test_saving_a_0x_widget_again_keeps_every_value(): void {
		$rows = $this->legacy_rows( 'coin-marquee-1' );
		$id   = $this->insert_legacy_widget( $rows );

		// Re-save through the admin form with the values it would show.
		$form = array();
		foreach ( $rows as $key => $values ) {
			$form[ $key ] = $values[0];
		}
		$_POST = $form + array( 'ucwp_widget_nonce' => wp_create_nonce( basename( UCWP_PLUGIN_FILE ) ) );
		wp_update_post( array( 'ID' => $id, 'post_title' => 'Saved again' ) );
		$_POST = array();

		$this->assertSame( $rows, WidgetFactory::rows( $id ) );
	}

	public function test_a_list_stored_in_any_older_shape_is_understood(): void {
		$this->assertSame( array( 'bitcoin', 'ethereum' ), WidgetFields::listToArray( 'bitcoin,ethereum' ) );
		$this->assertSame( array( 'bitcoin', 'ethereum' ), WidgetFields::listToArray( array( 'bitcoin', 'ethereum' ) ) );
		$this->assertSame( array( 'bitcoin', 'ethereum' ), WidgetFields::listToArray( 'a:2:{i:0;s:7:"bitcoin";i:1;s:8:"ethereum";}' ) );
		$this->assertSame( array(), WidgetFields::listToArray( '' ) );
		$this->assertSame( array(), WidgetFields::listToArray( null ) );
		$this->assertSame( 'bitcoin,ethereum', WidgetFields::listToString( array( ' bitcoin ', '', 'ethereum', 'bitcoin' ) ) );
	}

	public function test_a_widget_of_a_type_this_site_cannot_show_renders_nothing_and_loses_nothing(): void {
		// A Pro widget on a site where the add-on is not active.
		$rows = $this->legacy_rows( 'historical-price-chart' );
		$id   = $this->insert_legacy_widget( $rows );

		$this->assertSame( '', (string) do_shortcode( '[ucwp_widget id="' . $id . '"]' ) );
		$this->assertSame( $rows, WidgetFactory::rows( $id ) );
	}

	public function legacy_type_provider(): array {
		$types = array();
		foreach ( glob( self::FIXTURES . '/rows/*.json' ) as $file ) {
			$types[ basename( $file, '.json' ) ] = array( basename( $file, '.json' ) );
		}
		return $types;
	}

	public function free_type_provider(): array {
		$types = array();
		foreach ( glob( self::FIXTURES . '/shortcode/*.data.json' ) as $file ) {
			$types[ basename( $file, '.data.json' ) ] = array( basename( $file, '.data.json' ) );
		}
		return $types;
	}

	/**
	 * @return array<string, string[]> Meta key => values, as stored by 0.x.
	 */
	private function legacy_rows( string $type ): array {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		return json_decode( (string) file_get_contents( self::FIXTURES . "/rows/{$type}.json" ), true );
	}

	/**
	 * Writes the rows straight to the database, bypassing the current save code.
	 *
	 * @param array<string, string[]> $rows
	 */
	private function insert_legacy_widget( array $rows ): int {
		$id = self::factory()->post->create(
			array(
				'post_type'   => 'ucwp_widget',
				'post_status' => 'publish',
			)
		);
		foreach ( array_keys( WidgetFactory::rows( $id ) ) as $key ) {
			delete_post_meta( $id, $key );
		}
		foreach ( $rows as $key => $values ) {
			add_post_meta( $id, $key, $values[0] );
		}
		Services::prices()->clearCache();

		return $id;
	}

	/**
	 * @return array<string, mixed> What the widget's script was given.
	 */
	private function react_data(): array {
		foreach ( wp_scripts()->registered as $handle => $script ) {
			$inline = (string) wp_scripts()->get_data( $handle, 'data' );
			if ( 0 === strpos( $handle, 'ucwp-react-' ) && preg_match( '/var ucwpReactData = (.*);\s*$/s', $inline, $match ) ) {
				$data             = json_decode( $match[1], true );
				$data['react_id'] = 'ucwp-react-ID';
				return $data;
			}
		}
		return array();
	}
}

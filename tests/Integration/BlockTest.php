<?php
/**
 * The "Crypto Widget" block.
 *
 * @package Ultimate_Crypto_Widget
 */

namespace Cyberinfomatic\UltimateCryptoWidget\Tests\Integration;

use Cyberinfomatic\UltimateCryptoWidget\Blocks\WidgetBlock;
use Cyberinfomatic\UltimateCryptoWidget\Tests\Support\Services;
use Cyberinfomatic\UltimateCryptoWidget\Tests\Support\TestCase;
use Cyberinfomatic\UltimateCryptoWidget\Tests\Support\WidgetFactory;
use Cyberinfomatic\UltimateCryptoWidget\Widgets\WidgetSchema;

/**
 * A block is the shortcode with a nicer way to fill it in: both must draw the
 * same widget, and a block stores only a choice, never markup.
 */
final class BlockTest extends TestCase {

	public function test_the_block_is_registered_with_the_name_stored_in_posts(): void {
		$block = \WP_Block_Type_Registry::get_instance()->get_registered( 'ucwp/widget' );

		$this->assertNotNull( $block );
		$this->assertSame( array( 'widgetId', 'settings' ), array_slice( array_keys( $block->attributes ), 0, 2 ) );
		$this->assertTrue( $block->is_dynamic() );
	}

	public function test_a_block_pointing_at_a_saved_widget_draws_that_widget(): void {
		if ( ! self::has_react_build() ) {
			$this->markTestSkipped( 'React bundles are not built.' );
		}
		$id = WidgetFactory::create( 'coin-marquee-1' );

		$from_block     = do_blocks( '<!-- wp:ucwp/widget {"widgetId":' . $id . '} /-->' );
		$from_shortcode = do_shortcode( '[ucwp_widget id="' . $id . '"]' );

		$this->assertStringContainsString( 'wp-block-ucwp-widget', $from_block );
		$this->assertSame( self::widget_props( $from_shortcode ), self::widget_props( $from_block ) );
		$this->assertStringContainsString( 'data-ucw-view="coin-marquee"', $from_block );
	}

	public function test_a_block_set_up_on_its_own_draws_the_same_as_the_shortcode_with_those_settings(): void {
		if ( ! self::has_react_build() ) {
			$this->markTestSkipped( 'React bundles are not built.' );
		}

		$from_block     = do_blocks( '<!-- wp:ucwp/widget {"settings":{"type":"crypto-price-table-1","count":"5","parent_width":"100%","default_currency":"eur"}} /-->' );
		$from_shortcode = do_shortcode( '[ucwp_widget type="crypto-price-table-1" count="5" parent_width="100%" default_currency="eur"]' );

		$this->assertSame( self::widget_props( $from_shortcode ), self::widget_props( $from_block ) );
	}

	public function test_a_block_loads_the_widgets_script_only_where_it_is_used(): void {
		if ( ! self::has_react_build() ) {
			$this->markTestSkipped( 'React bundles are not built.' );
		}

		do_blocks( '<!-- wp:paragraph --><p>No widget here.</p><!-- /wp:paragraph -->' );
		$this->assertNotContains( 'ultimate-crypto-widget-vendors', wp_scripts()->queue );

		do_blocks( '<!-- wp:ucwp/widget {"settings":{"type":"coin-marquee-1"}} /-->' );
		$this->assertContains( 'ultimate-crypto-widget-widget-coin-marquee', wp_scripts()->queue );
		$this->assertNotContains( 'ultimate-crypto-widget-block', wp_scripts()->queue, 'The editor script must stay in the editor.' );
	}

	public function test_a_block_takes_only_a_widgets_own_settings(): void {
		$block = Services::container()->get( WidgetBlock::class );

		$this->assertSame(
			array( 'type' => 'coin-marquee-1', 'coins' => 'bitcoin,ethereum' ),
			$block->shortcodeAttributes(
				array(
					'settings' => array(
						'type'    => 'coin-marquee-1',
						'coins'   => array( 'bitcoin', 'ethereum' ),
						'id'      => '999',
						'onclick' => 'alert(1)',
						'count'   => array( 'x' ),
						'speed'   => '',
					),
				)
			)
		);
		$this->assertSame( array( 'id' => '12' ), $block->shortcodeAttributes( array( 'widgetId' => 12, 'settings' => array( 'type' => 'ignored' ) ) ) );
	}

	public function test_a_block_with_nothing_chosen_or_nothing_usable_prints_nothing(): void {
		wp_set_current_user( 0 );

		$this->assertSame( '', trim( do_blocks( '<!-- wp:ucwp/widget /-->' ) ) );
		$this->assertSame( '', trim( do_blocks( '<!-- wp:ucwp/widget {"settings":{"type":"' . self::LOCKED_TYPE . '"}} /-->' ) ), 'A locked type without the add-on.' );
	}

	public function test_a_block_cannot_show_visitors_a_draft_widget(): void {
		$id = WidgetFactory::create( 'coin-marquee-1' );
		wp_update_post( array( 'ID' => $id, 'post_status' => 'draft' ) );
		wp_set_current_user( 0 );

		$this->assertSame( '', trim( do_blocks( '<!-- wp:ucwp/widget {"widgetId":' . $id . '} /-->' ) ) );
	}

	public function test_the_settings_are_described_once_for_every_editor(): void {
		$fields = array_column( Services::container()->get( WidgetSchema::class )->toArray(), null, 'name' );

		$this->assertSame( array( 'type', 'count', 'orientation', 'speed', 'card_width', 'parent_width', 'default_currency', 'no_of_days', 'data_interval', 'search_placeholder', 'coins', 'currencies', 'dark_mode' ), array_keys( $fields ) );

		$this->assertSame( 'type', $fields['type']['control'] );
		$locked = array_column( $fields['type']['options'], 'locked', 'value' );
		$this->assertFalse( $locked['coin-marquee-1'] );
		$this->assertTrue( $locked[ self::LOCKED_TYPE ] );

		$this->assertSame( 'number', $fields['speed']['control'] );
		$this->assertSame( array( 100.0, 5000.0, '3000' ), array( $fields['speed']['min'], $fields['speed']['max'], $fields['speed']['default'] ) );
		$this->assertContains( 'coin-marquee-1', $fields['speed']['usedBy'] );
		$this->assertNotContains( 'crypto-price-table-1', $fields['speed']['usedBy'] );

		$this->assertSame( 'list', $fields['currencies']['control'] );
		$this->assertSame( 3, $fields['currencies']['limit'] );
		$this->assertContains( 'bitcoin', array_column( $fields['coins']['options'], 'value' ) );
	}

	public function test_the_editor_gets_its_settings_saved_widgets_and_a_preview_address(): void {
		$id = WidgetFactory::create( 'coin-marquee-1' );
		wp_set_current_user( WidgetFactory::editor() );

		$data = Services::container()->get( WidgetBlock::class )->editorData();

		$this->assertSame( 'ucwp/widget', $data['name'] );
		$this->assertContains( $id, array_column( $data['saved'], 'id' ) );
		$this->assertStringContainsString( 'action=ucwp_preview', $data['previewUrl'] );
		$this->assertStringContainsString( '_wpnonce=', $data['previewUrl'] );
		$this->assertStringNotContainsString( 'api_key', (string) wp_json_encode( $data ) );
	}
}

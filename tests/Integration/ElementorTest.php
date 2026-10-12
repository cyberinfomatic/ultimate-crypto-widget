<?php
/**
 * The Elementor element.
 *
 * @package Ultimate_Crypto_Widget
 */

namespace Cyberinfomatic\UltimateCryptoWidget\Tests\Integration;

use Cyberinfomatic\UltimateCryptoWidget\Admin\Preview;
use Cyberinfomatic\UltimateCryptoWidget\Integrations\Elementor\CryptoWidget;
use Cyberinfomatic\UltimateCryptoWidget\Lib\WPToolkit\Frontend\Shortcodes;
use Cyberinfomatic\UltimateCryptoWidget\Tests\Support\Services;
use Cyberinfomatic\UltimateCryptoWidget\Tests\Support\TestCase;
use Cyberinfomatic\UltimateCryptoWidget\Widgets\WidgetSchema;

/**
 * Run against stand-ins for Elementor's classes (tests/Support/ElementorStubs.php):
 * they check what we hand Elementor and what we draw, not Elementor itself.
 */
final class ElementorTest extends TestCase {

	public static function set_up_before_class() {
		parent::set_up_before_class();
		require_once __DIR__ . '/../Support/ElementorStubs.php';
	}

	private function element(): CryptoWidget {
		$container = Services::container();
		CryptoWidget::use( $container->get( Shortcodes::class ), $container->get( WidgetSchema::class ), $container->get( Preview::class ) );

		return new CryptoWidget();
	}

	public function test_nothing_of_elementor_is_touched_on_a_site_without_it(): void {
		// The plugin booted long before the stand-ins were loaded, without an error.
		$this->assertNotFalse( has_action( 'elementor/widgets/register' ) );
		$this->assertFalse( class_exists( \Elementor\Plugin::class, false ) );
	}

	public function test_the_element_registers_itself_when_elementor_asks(): void {
		$manager = new class() {
			/** @var object[] */
			public array $registered = array();

			public function register( object $widget ): void {
				$this->registered[] = $widget;
			}
		};

		do_action( 'elementor/widgets/register', $manager );

		$this->assertCount( 1, $manager->registered );
		$this->assertSame( 'ucwp-widget', $manager->registered[0]->get_name() );
	}

	public function test_its_controls_come_from_the_widget_settings(): void {
		$element = $this->element();
		$element->build_controls();

		$this->assertSame(
			array( 'ucw_type', 'ucw_count', 'ucw_orientation', 'ucw_speed', 'ucw_card_width', 'ucw_parent_width', 'ucw_default_currency', 'ucw_no_of_days', 'ucw_data_interval', 'ucw_search_placeholder', 'ucw_coins', 'ucw_currencies', 'ucw_dark_mode' ),
			array_keys( $element->controls )
		);

		$type = $element->controls['ucw_type'];
		$this->assertArrayHasKey( 'coin-marquee-1', $type['options'] );
		$this->assertArrayNotHasKey( 'historical-price-chart', $type['options'], 'A type the site cannot use must not be offered.' );

		$speed = $element->controls['ucw_speed'];
		$this->assertSame( 'number', $speed['type'] );
		$this->assertSame( array( 100.0, 5000.0 ), array( $speed['min'], $speed['max'] ) );
		$this->assertContains( 'coin-marquee-1', $speed['condition']['ucw_type'] );
		$this->assertNotContains( 'crypto-price-table-1', $speed['condition']['ucw_type'] );

		$coins = $element->controls['ucw_coins'];
		$this->assertSame( 'select2', $coins['type'] );
		$this->assertTrue( $coins['multiple'] );
		$this->assertSame( array(), $coins['default'] );
	}

	public function test_it_draws_the_same_widget_as_the_shortcode(): void {
		if ( ! self::has_react_build() ) {
			$this->markTestSkipped( 'React bundles are not built.' );
		}
		$element           = $this->element();
		$element->settings = array(
			'ucw_type'             => 'crypto-price-table-1',
			'ucw_count'            => '5',
			'ucw_parent_width'     => '100%',
			'ucw_default_currency' => 'eur',
			// Left over from another type; this type has no speed.
			'ucw_speed'            => '900',
			'ucw_coins'            => array( 'bitcoin', 'ethereum' ),
			'_element_id'          => 'something-of-elementors',
		);

		$from_element   = $element->output();
		$from_shortcode = do_shortcode( '[ucwp_widget type="crypto-price-table-1" count="5" parent_width="100%" default_currency="eur"]' );

		$this->assertSame( self::widget_props( $from_shortcode ), self::widget_props( $from_element ) );
		$this->assertContains( 'ultimate-crypto-widget-widget-crypto-price-table', wp_scripts()->queue );
	}

	public function test_a_list_chosen_in_elementor_is_passed_in_its_stored_shape(): void {
		$fields = Services::container()->get( WidgetSchema::class )->toArray();

		$this->assertSame(
			array( 'type' => 'multi-currencies-tab', 'default_currency' => 'usd', 'currencies' => 'eur,gbp' ),
			CryptoWidget::shortcodeAttributes(
				array(
					'ucw_type'             => 'multi-currencies-tab',
					'ucw_default_currency' => 'usd',
					'ucw_currencies'       => array( 'eur', 'gbp' ),
					'ucw_coins'            => array(),
				),
				$fields
			)
		);
	}

	public function test_it_draws_nothing_until_a_type_is_chosen(): void {
		$element           = $this->element();
		$element->settings = array( 'ucw_count' => '5' );

		$this->assertSame( '', $element->output() );
	}
}

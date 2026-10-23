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

	/**
	 * An element as Elementor builds one that is placed on a page: from its saved data.
	 *
	 * @param array<string, mixed> $settings
	 */
	private function element( string $type = '', array $settings = array() ): CryptoWidget {
		do_action( 'elementor/widgets/register', $this->manager() );

		$name = '' === $type ? CryptoWidget::GENERIC : CryptoWidget::NAME_PREFIX . $type;

		return new CryptoWidget( array( 'widgetType' => $name, 'settings' => $settings ), array() );
	}

	/**
	 * Stands in for Elementor's widget manager: it keeps what is registered, by name.
	 */
	private function manager(): object {
		return new class() {
			/** @var array<string, object> */
			public array $registered = array();

			public function register( object $widget ): void {
				$this->registered[ $widget->get_name() ] = $widget;
			}
		};
	}

	public function test_nothing_of_elementor_is_touched_on_a_site_without_it(): void {
		// The plugin booted long before the stand-ins were loaded, without an error.
		$this->assertNotFalse( has_action( 'elementor/widgets/register' ) );
		$this->assertFalse( class_exists( \Elementor\Plugin::class, false ) );
	}

	public function test_every_widget_type_the_site_can_use_gets_its_own_element(): void {
		$manager = $this->manager();

		do_action( 'elementor/widgets/register', $manager );

		$names = array_keys( $manager->registered );
		foreach ( self::free_widget_types() as $type ) {
			$this->assertContains( 'ucwp-' . $type, $names );
		}
		$this->assertNotContains( 'ucwp-' . self::LOCKED_TYPE, $names, 'A locked type must wait for the add-on.' );

		$table = $manager->registered['ucwp-crypto-price-table-1'];
		$this->assertSame( 'Crypto Price Table', $table->get_title() );
		$this->assertSame( 'eicon-table', $table->get_icon() );
		$this->assertSame( array( 'ucwp' ), $table->get_categories() );
		$this->assertTrue( $table->show_in_panel() );
	}

	public function test_an_add_ons_types_get_elements_once_they_are_usable(): void {
		$unlock = static function ( \Cyberinfomatic\UltimateCryptoWidget\Widgets\WidgetCatalog $catalog ): void {
			$catalog->register( new \Cyberinfomatic\UltimateCryptoWidget\Widgets\WidgetDefinition( 'historical-price-chart', 'Historical Price Chart', 'historical-price-chart', 'card-001', array( 'coins', 'no_of_days' ), array(), true, true ) );
		};
		add_action( 'ucwp_register_widgets', $unlock );
		$container = Services::container();

		// What the provider does, with a catalogue that has the add-on's type unlocked.
		$types = array();
		foreach ( ( new \Cyberinfomatic\UltimateCryptoWidget\Widgets\WidgetCatalog() )->all() as $type => $definition ) {
			if ( $definition->available ) {
				$types[ $type ] = array( 'label' => $definition->label, 'view' => $definition->view );
			}
		}
		remove_action( 'ucwp_register_widgets', $unlock );
		CryptoWidget::use( $container->get( Shortcodes::class ), $container->get( WidgetSchema::class ), $container->get( Preview::class ), $types );

		$chart = CryptoWidget::forType( 'historical-price-chart' );

		$this->assertSame( 'ucwp-historical-price-chart', $chart->get_name() );
		$this->assertSame( 'Historical Price Chart', $chart->get_title() );
		$this->assertSame( 'eicon-skill-bar', $chart->get_icon() );
	}

	public function test_the_element_with_a_type_picker_is_kept_but_not_offered(): void {
		$manager = $this->manager();

		do_action( 'elementor/widgets/register', $manager );

		$this->assertArrayHasKey( 'ucwp-widget', $manager->registered );
		$this->assertFalse( $manager->registered['ucwp-widget']->show_in_panel() );
	}

	public function test_it_has_its_own_group_in_elementors_panel(): void {
		$elements = new class() {
			/** @var array<string, array<string, string>> */
			public array $categories = array();

			public function add_category( string $name, array $properties ): void {
				$this->categories[ $name ] = $properties;
			}
		};

		do_action( 'elementor/elements/categories_registered', $elements );

		$this->assertSame( 'Crypto Widgets', $elements->categories['ucwp']['title'] ?? null );
		$this->assertSame( array( 'ucwp' ), $this->element( 'coin-marquee-1' )->get_categories() );
	}

	public function test_an_element_offers_only_its_own_types_settings(): void {
		$marquee = $this->element( 'coin-marquee-1' );
		$marquee->build_controls();

		// No type picker, no conditions: the element already is the type.
		$this->assertSame( array( 'ucw_count', 'ucw_speed', 'ucw_card_width', 'ucw_parent_width', 'ucw_default_currency' ), array_keys( $marquee->controls ) );
		$this->assertArrayNotHasKey( 'condition', $marquee->controls['ucw_speed'] );

		$speed = $marquee->controls['ucw_speed'];
		$this->assertSame( 'number', $speed['type'] );
		$this->assertSame( array( 100.0, 5000.0, '3000' ), array( $speed['min'], $speed['max'], $speed['default'] ) );

		$tabs = $this->element( 'multi-currencies-tab' );
		$tabs->build_controls();
		$this->assertArrayNotHasKey( 'ucw_speed', $tabs->controls );
		$this->assertSame( 'select2', $tabs->controls['ucw_currencies']['type'] );
		$this->assertTrue( $tabs->controls['ucw_currencies']['multiple'] );
		$this->assertSame( array(), $tabs->controls['ucw_coins']['default'] );
	}

	public function test_the_generic_element_still_has_every_setting_behind_a_type_picker(): void {
		$element = $this->element();
		$element->build_controls();

		$this->assertSame( 'ucw_type', array_key_first( $element->controls ) );
		$this->assertCount( 13, $element->controls );
		$this->assertArrayNotHasKey( self::LOCKED_TYPE, $element->controls['ucw_type']['options'], 'A type the site cannot use must not be offered.' );
		$this->assertContains( 'coin-marquee-1', $element->controls['ucw_speed']['condition']['ucw_type'] );
		$this->assertNotContains( 'crypto-price-table-1', $element->controls['ucw_speed']['condition']['ucw_type'] );
	}

	public function test_it_draws_the_same_widget_as_the_shortcode(): void {
		if ( ! self::has_react_build() ) {
			$this->markTestSkipped( 'React bundles are not built.' );
		}
		$element = $this->element(
			'crypto-price-table-1',
			array(
				'ucw_count'            => '5',
				'ucw_parent_width'     => '100%',
				'ucw_default_currency' => 'eur',
				// Not settings of this type; they must not reach the widget.
				'ucw_speed'            => '900',
				'ucw_type'             => 'coin-marquee-1',
				'_element_id'          => 'something-of-elementors',
			)
		);

		$from_element   = $element->output();
		$from_shortcode = do_shortcode( '[ucwp_widget type="crypto-price-table-1" count="5" parent_width="100%" default_currency="eur"]' );

		$this->assertSame( self::widget_props( $from_shortcode ), self::widget_props( $from_element ) );
		$this->assertContains( 'ultimate-crypto-widget-widget-crypto-price-table', wp_scripts()->queue );
	}

	public function test_a_page_that_has_the_generic_element_still_draws(): void {
		if ( ! self::has_react_build() ) {
			$this->markTestSkipped( 'React bundles are not built.' );
		}
		$element = $this->element( '', array( 'ucw_type' => 'coin-marquee-1', 'ucw_count' => '5' ) );

		$this->assertStringContainsString( 'data-ucw-view="coin-marquee"', $element->output() );
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

	public function test_the_generic_element_draws_nothing_until_a_type_is_chosen(): void {
		$this->assertSame( '', $this->element( '', array( 'ucw_count' => '5' ) )->output() );
	}
}

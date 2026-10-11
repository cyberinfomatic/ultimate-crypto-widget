<?php
/**
 * What an add-on (such as Pro) can rely on.
 *
 * @package Ultimate_Crypto_Widget
 */

namespace Cyberinfomatic\UltimateCryptoWidget\Tests\Integration;

use Cyberinfomatic\UltimateCryptoWidget\Legacy\Services;
use Cyberinfomatic\UltimateCryptoWidget\Lib\WPToolkit\Foundation\Application;
use Cyberinfomatic\UltimateCryptoWidget\Plugin;
use Cyberinfomatic\UltimateCryptoWidget\Tests\Support\TestCase;
use Cyberinfomatic\UltimateCryptoWidget\Widgets\ReactMount;
use Cyberinfomatic\UltimateCryptoWidget\Widgets\WidgetCatalog;
use Cyberinfomatic\UltimateCryptoWidget\Widgets\WidgetDefinition;
use Cyberinfomatic\UltimateCryptoWidget\Widgets\WidgetRenderer;

/**
 * These are public extension points. Changing one breaks every add-on.
 */
final class AddOnApiTest extends TestCase {

	private const BUILD = __DIR__ . '/../fixtures/addon-build/';

	public function test_an_add_on_can_find_the_running_plugin(): void {
		$this->assertInstanceOf( Application::class, Plugin::app() );
		$this->assertMatchesRegularExpression( '/^\d+\.\d+\.\d+/', Plugin::version() );
		$this->assertStringEndsWith( 'ultimate-crypto-widget.php', Plugin::file() );
		$this->assertSame( 1, did_action( 'ucwp_loaded' ) );
	}

	public function test_pro_widget_types_are_listed_but_cannot_be_used_without_the_add_on(): void {
		$chart = ( new WidgetCatalog() )->get( 'historical-price-chart' );

		$this->assertTrue( $chart->pro );
		$this->assertFalse( $chart->available );
		$this->assertSame( '', $this->renderer( new WidgetCatalog() )->render( 'historical-price-chart', array() ) );
	}

	public function test_an_add_on_can_unlock_a_listed_type_and_add_new_ones(): void {
		$register = static function ( WidgetCatalog $catalog ): void {
			// Replaces the teaser of the same type.
			$catalog->register( new WidgetDefinition( 'historical-price-chart', 'Historical Price Chart', 'sample-pro-widget', 'card-001', array( 'coins', 'default_currency' ), array( WidgetDefinition::DATA_COINS ), true, true, self::BUILD, 'https://example.org/addon/build/' ) );
			$catalog->register( new WidgetDefinition( 'brand-new-widget', 'Brand New', 'sample-pro-widget', '', array( 'default_currency' ), array(), true, true, self::BUILD, 'https://example.org/addon/build/' ) );
		};
		add_action( 'ucwp_register_widgets', $register );
		$catalog = new WidgetCatalog();

		$this->assertTrue( $catalog->get( 'historical-price-chart' )->available );
		$this->assertNotNull( $catalog->get( 'brand-new-widget' ) );
		// The built-in order is kept; new types follow.
		$this->assertSame( 'brand-new-widget', array_key_last( $catalog->all() ) );

		remove_action( 'ucwp_register_widgets', $register );
	}

	public function test_an_add_ons_widget_loads_its_scripts_from_the_add_ons_own_folder(): void {
		$catalog = new WidgetCatalog();
		$catalog->register( new WidgetDefinition( 'brand-new-widget', 'Brand New', 'sample-pro-widget', 'card-009', array( 'count', 'default_currency' ), array( WidgetDefinition::DATA_COINS ), true, true, self::BUILD, 'https://example.org/addon/build/' ) );

		$html = $this->renderer( $catalog )->render( 'brand-new-widget', array( 'count' => '3' ) );

		$this->assertMatchesRegularExpression( '/^<div id="ucwp-react-[0-9a-f]+">/', $html );

		$sources = array();
		foreach ( wp_scripts()->queue as $handle ) {
			$sources[] = wp_scripts()->registered[ $handle ]->src;
		}
		$this->assertContains( 'https://example.org/addon/build/widgets/sample-pro-widget/index.js', $sources );
		$this->assertContains( 'https://example.org/addon/build/vendors/index.js', $sources );
		$this->assertContains( 'https://example.org/addon/build/widgets/sample-pro-widget.css', wp_list_pluck( wp_styles()->registered, 'src' ) );
	}

	public function test_a_widget_whose_script_is_missing_renders_nothing_rather_than_a_broken_box(): void {
		$catalog = new WidgetCatalog();
		$catalog->register( new WidgetDefinition( 'not-built', 'Not built', 'no-such-view', '', array( 'default_currency' ), array(), true, true, self::BUILD, 'https://example.org/addon/build/' ) );

		$this->assertSame( '', $this->renderer( $catalog )->render( 'not-built', array() ) );
		$this->assertSame( array(), wp_scripts()->queue );
	}

	private function renderer( WidgetCatalog $catalog ): WidgetRenderer {
		return new WidgetRenderer( $catalog, Services::prices(), new ReactMount() );
	}
}

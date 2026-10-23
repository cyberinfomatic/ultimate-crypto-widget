<?php
/**
 * What an add-on (such as Pro) can rely on.
 *
 * @package Ultimate_Crypto_Widget
 */

namespace Cyberinfomatic\UltimateCryptoWidget\Tests\Integration;

use Cyberinfomatic\UltimateCryptoWidget\Tests\Support\Services;
use Cyberinfomatic\UltimateCryptoWidget\Lib\WPToolkit\Foundation\Application;
use Cyberinfomatic\UltimateCryptoWidget\Plugin;
use Cyberinfomatic\UltimateCryptoWidget\Tests\Support\TestCase;
use Cyberinfomatic\UltimateCryptoWidget\Lib\WPToolkit\Assets\AssetManager;
use Cyberinfomatic\UltimateCryptoWidget\Lib\WPToolkit\Assets\JsNamespace;
use Cyberinfomatic\UltimateCryptoWidget\Widgets\ReactMount;
use Cyberinfomatic\UltimateCryptoWidget\Widgets\Edition;
use Cyberinfomatic\UltimateCryptoWidget\Widgets\WidgetBundle;
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

	public function test_an_add_on_gets_its_classes_loaded_by_the_toolkits_loader(): void {
		$this->assertFalse( class_exists( 'UcwSampleAddon\\Greeting\\Hello', false ) );

		Plugin::autoload( 'UcwSampleAddon\\', __DIR__ . '/../fixtures/addon-src' );

		$this->assertTrue( class_exists( 'UcwSampleAddon\\Greeting\\Hello' ) );
		$this->assertSame( 'hello', \UcwSampleAddon\Greeting\Hello::say() );
		// A class that is not there is simply not found; nothing is thrown.
		$this->assertFalse( class_exists( 'UcwSampleAddon\\Greeting\\Missing' ) );
	}

	public function test_an_add_on_is_told_when_its_class_folder_is_wrong(): void {
		$this->expectException( \InvalidArgumentException::class );

		Plugin::autoload( 'UcwSampleAddon\\', __DIR__ . '/no-such-folder' );
	}

	public function test_in_the_all_free_edition_every_type_is_free_and_drawn_here(): void {
		$catalog = WidgetCatalog::withEdition( Edition::ALL_FREE );

		foreach ( Edition::proTierViews() as $view ) {
			$this->assertNotEmpty( array_filter( $catalog->all(), static fn ( $definition ): bool => $definition->view === $view ), "No type is drawn by {$view}." );
		}
		foreach ( $catalog->all() as $type => $definition ) {
			if ( self::LOCKED_TYPE === $type ) {
				continue;
			}
			$this->assertTrue( $definition->available, "{$type} must be usable." );
			$this->assertFalse( $definition->pro, "{$type} must not be marked Pro." );
			$this->assertNull( $definition->bundle, "{$type} must be drawn by this plugin." );
		}
		$this->assertNotSame( '', $this->renderer( $catalog )->render( 'compare-coins', array( 'coins' => 'bitcoin,ethereum' ) ) );
	}

	public function test_the_edition_file_lists_every_view_the_pro_tier_uses(): void {
		$tiered = array_filter( WidgetCatalog::withEdition( Edition::TIERED )->all(), static fn ( $definition ): bool => $definition->pro );
		$views  = array_unique( array_map( static fn ( $definition ): string => $definition->view, $tiered ) );

		// The release build leaves exactly these views out of a tiered zip, and Pro builds exactly these.
		$this->assertEqualsCanonicalizing( Edition::proTierViews(), array_values( $views ) );
	}

	public function test_pro_widget_types_are_listed_but_cannot_be_used_without_the_add_on(): void {
		$chart = WidgetCatalog::withEdition( Edition::TIERED )->get( 'historical-price-chart' );

		$this->assertTrue( $chart->pro );
		$this->assertFalse( $chart->available );
		$this->assertSame( '', $this->renderer( WidgetCatalog::withEdition( Edition::TIERED ) )->render( 'historical-price-chart', array() ) );
	}

	public function test_an_add_on_can_unlock_a_listed_type_and_add_new_ones(): void {
		$register = function ( WidgetCatalog $catalog ): void {
			// Replaces the teaser of the same type.
			$catalog->register( new WidgetDefinition( 'historical-price-chart', 'Historical Price Chart', 'sample-pro-widget', 'card-001', array( 'coins', 'default_currency' ), array( WidgetDefinition::DATA_COINS ), true, true, $this->bundle() ) );
			$catalog->register( new WidgetDefinition( 'brand-new-widget', 'Brand New', 'sample-pro-widget', '', array( 'default_currency' ), array(), true, true, $this->bundle() ) );
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
		$catalog->register( new WidgetDefinition( 'brand-new-widget', 'Brand New', 'sample-pro-widget', 'card-009', array( 'count', 'default_currency' ), array( WidgetDefinition::DATA_COINS ), true, true, $this->bundle() ) );

		$html = $this->renderer( $catalog )->render( 'brand-new-widget', array( 'count' => '3' ) );

		$this->assertStringContainsString( 'data-ucw-view="sample-pro-widget"', $html );
		$this->assertSame( '3', self::widget_props( $html )[0]['settings']['count'] );

		$sources = array();
		foreach ( wp_scripts()->queue as $handle ) {
			$sources[] = wp_scripts()->registered[ $handle ]->src;
		}
		$this->assertContains( 'https://example.org/addon/addon-build/widgets/sample-pro-widget/index.js', $sources );
		$this->assertContains( 'https://example.org/addon/addon-build/vendors/index.js', $sources );

		// Its handles are the add-on's own, so they cannot collide with this plugin's.
		$this->assertContains( 'sample-addon-vendors', wp_scripts()->queue );
		$this->assertNotContains( 'ultimate-crypto-widget-vendors', wp_scripts()->queue );

		// Its stylesheet comes after the look every widget shares.
		$style = wp_styles()->registered['sample-addon-widget-sample-pro-widget-style'] ?? null;
		$this->assertNotNull( $style );
		$this->assertSame( 'https://example.org/addon/addon-build/widgets/sample-pro-widget.css', $style->src );
		if ( self::has_react_build() ) {
			$this->assertContains( 'ultimate-crypto-widget-theme-style', $style->deps );
		}
	}

	public function test_an_add_on_can_ask_for_the_whole_market_and_for_trending_coins(): void {
		$catalog = new WidgetCatalog();
		$catalog->register( new WidgetDefinition( 'market-sample', 'Market', 'sample-pro-widget', '', array( 'default_currency' ), array( WidgetDefinition::DATA_GLOBAL, WidgetDefinition::DATA_TRENDING ), true, true, $this->bundle() ) );

		$props = self::widget_props( $this->renderer( $catalog )->render( 'market-sample', array( 'default_currency' => 'EUR' ) ) )[0];

		$this->assertSame( 'eur', $props['global_market']['currency'] );
		$this->assertEqualsWithDelta( 2539200000000, $props['global_market']['market_cap'], 1 );
		$this->assertSame( array( 'pepe', 'solana' ), array_column( $props['trending_coins'], 'id' ) );
	}

	public function test_a_widget_whose_script_is_missing_renders_nothing_rather_than_a_broken_box(): void {
		$catalog = new WidgetCatalog();
		$catalog->register( new WidgetDefinition( 'not-built', 'Not built', 'no-such-view', '', array( 'default_currency' ), array(), true, true, $this->bundle() ) );

		$this->assertSame( '', $this->renderer( $catalog )->render( 'not-built', array() ) );
		$this->assertSame( array(), wp_scripts()->queue );
	}

	/**
	 * A build belonging to another plugin, loaded through that plugin's own asset manager.
	 */
	private function bundle(): WidgetBundle {
		static $bundle = null;
		if ( null === $bundle ) {
			$addon  = Application::create( dirname( self::BUILD ) . '/sample-addon.php', array( 'slug' => 'sample-addon' ) );
			$assets = new AssetManager(
				$addon->identity(),
				new JsNamespace( $addon->identity(), Application::VERSION ),
				dirname( self::BUILD ),
				'https://example.org/addon',
				UCWP_PLUGIN_DIR . 'lib/wptoolkit',
				'https://example.org/lib',
				'sample-addon',
				dirname( self::BUILD ) . '/languages'
			);
			$bundle = new WidgetBundle( $assets, self::BUILD, 'addon-build/' );
		}

		return $bundle;
	}

	private function renderer( WidgetCatalog $catalog ): WidgetRenderer {
		return new WidgetRenderer( $catalog, Services::prices(), Services::container()->get( ReactMount::class ) );
	}
}

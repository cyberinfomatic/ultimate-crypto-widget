<?php
/**
 * The plugin's screens in wp-admin.
 *
 * @package Ultimate_Crypto_Widget
 */

namespace Cyberinfomatic\UltimateCryptoWidget\Tests\Integration;

use Cyberinfomatic\UltimateCryptoWidget\Admin\Preview;
use Cyberinfomatic\UltimateCryptoWidget\Admin\Screens;
use Cyberinfomatic\UltimateCryptoWidget\Admin\WidgetEditor;
use Cyberinfomatic\UltimateCryptoWidget\Tests\Support\Services;
use Cyberinfomatic\UltimateCryptoWidget\Tests\Support\TestCase;
use Cyberinfomatic\UltimateCryptoWidget\Tests\Support\WidgetFactory;

/**
 * The menu, the widget editor and the preview. Page slugs are in bookmarks and
 * in other plugins' links, so they are pinned here.
 */
final class AdminTest extends TestCase {

	public function tear_down() {
		$_GET = array();
		parent::tear_down();
	}

	public function test_the_menu_keeps_its_page_addresses(): void {
		global $submenu, $menu;
		$submenu = array(); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$menu    = array(); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		wp_set_current_user( WidgetFactory::editor() );

		do_action( 'admin_menu' );

		$this->assertContains( 'ultimate-crypto-widget', wp_list_pluck( $menu, 2 ) );
		$this->assertSame(
			array( 'ultimate-crypto-widget', 'edit.php?post_type=ucwp_widget', 'ultimate-crypto-widget-quick-widget', 'ultimate-crypto-widget-settings' ),
			array_values( wp_list_pluck( $submenu['ultimate-crypto-widget'], 2 ) )
		);
	}

	public function test_every_screen_is_for_administrators_only(): void {
		global $submenu, $menu;
		$submenu = array(); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$menu    = array(); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		wp_set_current_user( WidgetFactory::editor() );

		do_action( 'admin_menu' );

		// WordPress hides and refuses a page to anyone without its capability.
		foreach ( array_merge( $menu, $submenu['ultimate-crypto-widget'] ) as $item ) {
			$this->assertSame( 'manage_options', $item[1], "{$item[2]} is open to more than administrators." );
		}
	}

	public function test_the_widget_form_offers_every_setting_and_says_which_types_use_it(): void {
		$id = WidgetFactory::create( 'coin-marquee-1' );

		ob_start();
		Services::container()->get( WidgetEditor::class )->box()->render( get_post( $id ) );
		$form = (string) ob_get_clean();

		foreach ( array( 'type', 'count', 'orientation', 'speed', 'card_width', 'parent_width', 'default_currency', 'no_of_days', 'data_interval', 'search_placeholder', 'coins', 'currencies', 'dark_mode' ) as $name ) {
			$this->assertStringContainsString( 'name="ucwp_widget_' . $name, $form, "The form has no field for {$name}." );
		}
		$this->assertMatchesRegularExpression( '/name="ucwp_widget_speed"[^>]*data-ucw-for="[^"]*coin-marquee-1/', $form );
		$this->assertDoesNotMatchRegularExpression( '/name="ucwp_widget_speed"[^>]*data-ucw-for="[^"]*crypto-price-table-1/', $form );
		$this->assertMatchesRegularExpression( '/<option value="coin-marquee-1" selected/', $form );
	}

	public function test_a_type_the_site_cannot_use_is_listed_but_cannot_be_chosen(): void {
		ob_start();
		Services::container()->get( WidgetEditor::class )->box()->render( get_post( WidgetFactory::create( 'coin-marquee-1' ) ) );
		$form = (string) ob_get_clean();

		$this->assertMatchesRegularExpression( '/<option value="' . self::LOCKED_TYPE . '"[^>]*disabled[^>]*>[^<]*\(Pro\)/', $form );
		$this->assertDoesNotMatchRegularExpression( '/<option value="coin-marquee-1"[^>]*disabled/', $form );
	}

	public function test_the_settings_form_is_built_only_for_requests_about_widgets(): void {
		$widget = WidgetFactory::create( 'coin-marquee-1' );
		$page   = self::factory()->post->create( array( 'post_type' => 'page' ) );

		// Edit, add and list screens of the widget type, and quick edit in the list.
		$this->assertTrue( WidgetEditor::needsForm( 'post.php', array( 'post' => (string) $widget ) ) );
		$this->assertTrue( WidgetEditor::needsForm( 'post.php', array( 'post_ID' => (string) $widget, 'post_type' => '' ) ) );
		$this->assertTrue( WidgetEditor::needsForm( 'post-new.php', array( 'post_type' => 'ucwp_widget' ) ) );
		$this->assertTrue( WidgetEditor::needsForm( 'edit.php', array( 'post_type' => 'ucwp_widget' ) ) );
		$this->assertTrue( WidgetEditor::needsForm( 'admin-ajax.php', array( 'action' => 'inline-save', 'post_type' => 'ucwp_widget' ) ) );

		// Anything else: other post types, other screens, other ajax calls, odd input. These
		// would otherwise cost a coin-list request on a cold cache.
		$this->assertFalse( WidgetEditor::needsForm( 'post.php', array( 'post' => (string) $page ) ) );
		$this->assertFalse( WidgetEditor::needsForm( 'post-new.php', array() ) );
		$this->assertFalse( WidgetEditor::needsForm( 'index.php', array( 'post_type' => 'ucwp_widget' ) ) );
		$this->assertFalse( WidgetEditor::needsForm( 'admin-ajax.php', array( 'action' => 'heartbeat', 'post_type' => 'ucwp_widget' ) ) );
		$this->assertFalse( WidgetEditor::needsForm( 'post.php', array( 'post' => array( 'x' ) ) ) );
		$this->assertFalse( WidgetEditor::needsForm( '', array() ) );
	}

	public function test_the_widget_list_shows_each_widgets_type_and_shortcode(): void {
		$id      = WidgetFactory::create( 'crypto-price-table-1' );
		$columns = apply_filters( 'manage_ucwp_widget_posts_columns', array( 'cb' => '', 'title' => 'Title', 'date' => 'Date' ) );

		$this->assertSame( array( 'cb', 'title', 'ucwp_widget_type', 'ucw_shortcode', 'date' ), array_keys( $columns ) );

		ob_start();
		do_action( 'manage_ucwp_widget_posts_custom_column', 'ucwp_widget_type', $id );
		do_action( 'manage_ucwp_widget_posts_custom_column', 'ucw_shortcode', $id );
		$cells = (string) ob_get_clean();

		$this->assertStringContainsString( 'Crypto Price Table', $cells );
		$this->assertStringContainsString( '[ucwp_widget id=&quot;' . $id . '&quot;]', $cells );
	}

	public function test_the_preview_draws_the_widget_with_its_scripts_on_a_page_of_its_own(): void {
		if ( ! self::has_react_build() ) {
			$this->markTestSkipped( 'React bundles are not built.' );
		}

		$page = Services::container()->get( Preview::class )->document( array( 'type' => 'crypto-price-table-1', 'count' => '5' ) );

		$this->assertStringStartsWith( '<!doctype html>', $page );
		$this->assertStringContainsString( 'data-ucw-view="crypto-price-table"', $page );
		$this->assertStringContainsString( 'widgets/crypto-price-table/index.js', $page );
		$this->assertStringContainsString( 'theme.css', $page );
		$this->assertStringContainsString( 'noindex', $page );
	}

	public function test_the_preview_takes_only_a_widgets_own_settings_from_the_address(): void {
		$_GET = array(
			's' => array(
				'type'    => 'coin-marquee-1',
				'count'   => '5',
				'coins'   => array( 'bitcoin', 'ethereum' ),
				'id'      => '999',
				'onclick' => 'alert(1)',
				'speed'   => array( 'not', 'a', 'number' ),
			),
		);

		$this->assertSame(
			array( 'type' => 'coin-marquee-1', 'count' => '5', 'coins' => 'bitcoin,ethereum' ),
			Services::container()->get( Preview::class )->requested()
		);
	}

	public function test_a_preview_address_survives_values_with_special_characters(): void {
		wp_set_current_user( WidgetFactory::editor() );
		$settings = array(
			'type'               => 'crypto-price-table-1',
			'parent_width'       => '100%',
			'coins'              => 'bitcoin,ethereum',
			'search_placeholder' => 'Find a coin & more #1',
		);

		$url = Services::container()->get( Preview::class )->url( 0, $settings );

		// Read it back the way PHP will when the frame asks for it.
		parse_str( (string) wp_parse_url( $url, PHP_URL_QUERY ), $query );
		$this->assertSame( $settings, $query['s'] );
		$this->assertSame( 'ucwp_preview', $query['action'] );
		$this->assertStringNotContainsString( '100%&', $url, 'A bare % makes the address malformed.' );
	}

	public function test_the_preview_is_refused_to_visitors_and_without_a_fresh_link(): void {
		$preview = Services::container()->get( Preview::class );

		wp_set_current_user( 0 );
		$this->assertDied( static fn () => $preview->handle() );

		wp_set_current_user( WidgetFactory::editor() );
		$_GET     = array( '_wpnonce' => 'stale' );
		$_REQUEST = $_GET;
		$this->assertDied( static fn () => $preview->handle() );
		$_REQUEST = array();
	}

	public function test_the_upgrade_link_and_prompt_can_be_changed_by_an_add_on(): void {
		// Suggested only when the edition sells Pro: never in the all-free edition.
		$this->assertSame( \Cyberinfomatic\UltimateCryptoWidget\Widgets\Edition::sellsPro(), Screens::suggestsUpgrade() );
		$this->assertStringStartsWith( 'https://ultimatecryptowidget.com/', Screens::upgradeUrl() );

		add_filter( 'ucwp_upgrade_url', static fn (): string => 'https://example.org/buy' );
		add_filter( 'ucwp_show_upgrade_notice', '__return_false' );

		$this->assertSame( 'https://example.org/buy', Screens::upgradeUrl() );
		$this->assertFalse( Screens::suggestsUpgrade() );
	}

	/**
	 * Passes when the callback ends in wp_die().
	 */
	private function assertDied( callable $callback ): void {
		try {
			$callback();
		} catch ( \WPDieException $e ) {
			$this->addToAssertionCount( 1 );
			return;
		}
		$this->fail( 'Expected the request to be refused.' );
	}
}

<?php
/**
 * Where the plugin's settings live.
 *
 * @package Ultimate_Crypto_Widget
 */

namespace Cyberinfomatic\UltimateCryptoWidget\Tests\Integration;

use Cyberinfomatic\UltimateCryptoWidget\Tests\Support\Services;
use Cyberinfomatic\UltimateCryptoWidget\Lib\WPToolkit\Admin\Settings\Settings;
use Cyberinfomatic\UltimateCryptoWidget\Tests\Support\TestCase;

/**
 * Users' saved API keys are stored as ultimate_crypto_widget_{name}. The settings
 * must be read from, and written to, exactly those options.
 */
final class SettingsTest extends TestCase {

	private Settings $settings;

	public function set_up() {
		parent::set_up();

		// As saved by the 0.x plugins.
		update_option( 'ultimate_crypto_widget_coingecko_api_key', 'cg-key-from-0x' );
		update_option( 'ultimate_crypto_widget_openexchangerates_app_id', 'oxr-id-from-0x' );
		update_option( 'ultimate_crypto_widget_cache_interval', '25' );

		$this->settings = Services::container()->get( Settings::class );
	}

	public function test_options_saved_by_0x_are_read_where_they_are(): void {
		$this->assertSame( 'cg-key-from-0x', $this->settings->get( 'coingecko_api_key' ) );
		$this->assertSame( 'oxr-id-from-0x', $this->settings->get( 'openexchangerates_app_id' ) );
		$this->assertEquals( 25, $this->settings->get( 'cache_interval' ) );
	}

	public function test_each_setting_maps_to_its_existing_option_name(): void {
		$this->assertSame( 'ultimate_crypto_widget_coingecko_api_key', $this->settings->optionKey( 'coingecko_api_key' ) );
		$this->assertSame( 'ultimate_crypto_widget_openexchangerates_app_id', $this->settings->optionKey( 'openexchangerates_app_id' ) );
		$this->assertSame( 'ultimate_crypto_widget_cache_interval', $this->settings->optionKey( 'cache_interval' ) );
	}

	public function test_saving_writes_back_to_the_same_options_and_creates_no_others(): void {
		global $wpdb;

		$this->assertSame( array(), $this->settings->set( 'cache_interval', 40 ) );
		$this->assertSame( array(), $this->settings->set( 'coingecko_api_key', 'a-new-key' ) );

		$this->assertEquals( 40, get_option( 'ultimate_crypto_widget_cache_interval' ) );
		$this->assertSame( 'a-new-key', get_option( 'ultimate_crypto_widget_coingecko_api_key' ) );

		// Nothing may appear under the slug-based prefix the toolkit would use by default.
		$stray = $wpdb->get_col( "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE 'ultimate-crypto-widget\\_%'" );
		$this->assertSame( array(), $stray );
	}

	public function test_api_keys_are_left_out_of_everything_that_lists_settings(): void {
		$all = $this->settings->all();

		$this->assertArrayHasKey( 'cache_interval', $all );
		$this->assertArrayNotHasKey( 'coingecko_api_key', $all );
		$this->assertArrayNotHasKey( 'openexchangerates_app_id', $all );
		$this->assertStringNotContainsString( 'cg-key-from-0x', wp_json_encode( $all ) );
	}

	public function test_an_api_key_cannot_be_handed_to_a_script(): void {
		$this->expectException( \Throwable::class );

		$this->settings->forScript( 'coingecko_api_key' );
	}

	public function test_a_nonsense_cache_interval_is_refused(): void {
		$this->assertNotSame( array(), $this->settings->set( 'cache_interval', -5 ) );
		$this->assertEquals( 25, get_option( 'ultimate_crypto_widget_cache_interval' ) );
	}

	public function test_the_cache_interval_defaults_to_ten_minutes(): void {
		delete_option( 'ultimate_crypto_widget_cache_interval' );

		$this->assertEquals( 10, $this->settings->get( 'cache_interval' ) );
	}
}

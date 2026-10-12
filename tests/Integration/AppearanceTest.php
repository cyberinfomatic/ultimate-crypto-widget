<?php
/**
 * The site-wide look of the widgets.
 *
 * @package Ultimate_Crypto_Widget
 */

namespace Cyberinfomatic\UltimateCryptoWidget\Tests\Integration;

use Cyberinfomatic\UltimateCryptoWidget\Lib\WPToolkit\Admin\Settings\Settings;
use Cyberinfomatic\UltimateCryptoWidget\Tests\Support\Services;
use Cyberinfomatic\UltimateCryptoWidget\Tests\Support\TestCase;
use Cyberinfomatic\UltimateCryptoWidget\Widgets\Appearance;
use Cyberinfomatic\UltimateCryptoWidget\Widgets\Color;

/**
 * Style controls change the values of the theme's variables on a widget's own
 * element and nothing else.
 */
final class AppearanceTest extends TestCase {

	private const WIDGET = '[ucwp_widget type="crypto-price-table-1" count="5" parent_width="100%" default_currency="usd"]';

	public function tear_down() {
		delete_option( 'ultimate_crypto_widget_theme' );
		delete_option( 'ultimate_crypto_widget_accent_color' );
		remove_all_filters( 'ucwp_widget_style' );
		parent::tear_down();
	}

	private function appearance(): Appearance {
		return new Appearance( Services::container()->get( Settings::class ) );
	}

	public function test_a_colour_code_becomes_a_hue_and_a_strength(): void {
		$red = Color::hexToOklch( '#ff0000' );
		$this->assertEqualsWithDelta( 0.628, $red['lightness'], 0.001 );
		$this->assertEqualsWithDelta( 0.2577, $red['chroma'], 0.001 );
		$this->assertEqualsWithDelta( 29.23, $red['hue'], 0.05 );

		$blue = Color::hexToOklch( '#0000FF' );
		$this->assertEqualsWithDelta( 264.05, $blue['hue'], 0.05 );

		$this->assertEqualsWithDelta( 1.0, Color::hexToOklch( '#ffffff' )['lightness'], 0.001 );
		$this->assertSame( 0.0, Color::hexToOklch( '#808080' )['hue'], 'A grey has no hue.' );
	}

	public function test_text_that_is_not_a_colour_code_is_refused(): void {
		foreach ( array( '', 'red', '#fff', '#gggggg', '#ff0000;color:red', 'rgb(1,2,3)' ) as $text ) {
			$this->assertNull( Color::hexToOklch( $text ), "Accepted: {$text}" );
		}
	}

	public function test_widgets_are_light_with_no_extra_styling_until_something_is_chosen(): void {
		$this->assertSame( 'light', $this->appearance()->theme() );
		$this->assertSame( '', $this->appearance()->style() );

		if ( self::has_react_build() ) {
			$this->assertStringNotContainsString( ' style=', (string) do_shortcode( self::WIDGET ) );
		}
	}

	public function test_the_sites_choice_of_light_dark_or_auto_reaches_every_widget(): void {
		update_option( 'ultimate_crypto_widget_theme', 'auto' );
		$this->assertSame( 'auto', $this->appearance()->theme() );

		update_option( 'ultimate_crypto_widget_theme', 'dark' );
		$this->assertSame( 'dark', $this->appearance()->theme( array( 'dark_mode' => 'false' ) ) );

		if ( self::has_react_build() ) {
			$this->assertStringContainsString( 'data-ucw-theme="dark"', (string) do_shortcode( self::WIDGET ) );
		}
	}

	public function test_a_widget_set_to_dark_stays_dark_on_a_light_site(): void {
		update_option( 'ultimate_crypto_widget_theme', 'light' );

		$this->assertSame( 'dark', $this->appearance()->theme( array( 'dark_mode' => 'true' ) ) );
		$this->assertSame( 'light', $this->appearance()->theme( array( 'dark_mode' => 'false' ) ) );
	}

	public function test_an_unknown_theme_in_the_database_falls_back_to_light(): void {
		update_option( 'ultimate_crypto_widget_theme', 'neon"><script>' );

		$this->assertSame( 'light', $this->appearance()->theme() );
	}

	public function test_the_accent_colour_sets_the_themes_hue_on_the_widget(): void {
		update_option( 'ultimate_crypto_widget_accent_color', '#ff0000' );

		$variables = $this->appearance()->variables();
		$this->assertEqualsWithDelta( 29.23, (float) $variables['--ucw-brand-hue'], 0.05 );
		$this->assertEqualsWithDelta( 0.2577, (float) $variables['--ucw-brand-chroma'], 0.001 );

		if ( self::has_react_build() ) {
			$this->assertMatchesRegularExpression( '/ style="--ucw-brand-hue:29\.\d+;--ucw-brand-chroma:0\.25\d*"/', (string) do_shortcode( self::WIDGET ) );
		}
	}

	public function test_a_grey_or_garish_accent_is_kept_within_readable_strength(): void {
		update_option( 'ultimate_crypto_widget_accent_color', '#808080' );
		$this->assertSame( '0.02', $this->appearance()->variables()['--ucw-brand-chroma'] );

		update_option( 'ultimate_crypto_widget_accent_color', '#ff00ff' );
		$this->assertSame( '0.26', $this->appearance()->variables()['--ucw-brand-chroma'] );
	}

	public function test_the_settings_form_refuses_an_accent_that_is_not_a_colour_code(): void {
		$settings = Services::container()->get( Settings::class );

		$this->assertNotSame( array(), $settings->set( 'accent_color', 'red;background:url(x)' ) );
		$this->assertSame( array(), $settings->set( 'accent_color', '#4f46e5' ) );
		$this->assertNotSame( array(), $settings->set( 'theme', 'neon' ) );
	}

	public function test_an_add_on_can_set_more_theme_variables(): void {
		add_filter(
			'ucwp_widget_style',
			static fn ( array $variables ): array => $variables + array(
				'--ucw-radius' => '4px',
				'--ucw-font'   => '"Inter", system-ui, sans-serif',
				'--ucw-up'     => '#16a34a',
			)
		);

		$this->assertSame( '--ucw-radius:4px;--ucw-font:"Inter", system-ui, sans-serif;--ucw-up:#16a34a', $this->appearance()->style() );
	}

	public function test_only_plain_values_of_our_own_variables_are_printed(): void {
		add_filter(
			'ucwp_widget_style',
			static fn (): array => array(
				'--ucw-radius'     => '4px',
				'color'            => 'red',
				'--other-variable' => '1',
				'--ucw-a'          => '1px;background:red',
				'--ucw-b'          => 'url(https://example.org/x.png)',
				'--ucw-c'          => '</style><script>',
				'--ucw-d'          => array( 'x' ),
				'--ucw-e'          => '',
				'--UCW-F'          => '1',
			)
		);

		$this->assertSame( array( '--ucw-radius' => '4px' ), $this->appearance()->variables() );
	}
}

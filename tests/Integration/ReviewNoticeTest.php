<?php
/**
 * The request for a review.
 *
 * @package Ultimate_Crypto_Widget
 */

namespace Cyberinfomatic\UltimateCryptoWidget\Tests\Integration;

use Cyberinfomatic\UltimateCryptoWidget\Admin\ReviewNotice;
use Cyberinfomatic\UltimateCryptoWidget\Plugin;
use Cyberinfomatic\UltimateCryptoWidget\Tests\Support\TestCase;
use Cyberinfomatic\UltimateCryptoWidget\Tests\Support\WidgetFactory;

/**
 * WordPress.org allows asking for a review only politely: dismissible, not everywhere, not
 * nagging. These tests hold the notice to that.
 */
final class ReviewNoticeTest extends TestCase {

	private ReviewNotice $notice;
	private int $admin;
	private int $now;

	public function set_up() {
		parent::set_up();
		$this->notice = new ReviewNotice( Plugin::app()->hooks() );
		$this->admin  = self::factory()->user->create( array( 'role' => 'administrator' ) );
		$this->now    = time();
		delete_transient( 'ucwp_review_in_use' );
		update_option( ReviewNotice::INSTALLED_OPTION, $this->now - 8 * DAY_IN_SECONDS );
	}

	public function tear_down() {
		delete_option( ReviewNotice::INSTALLED_OPTION );
		delete_transient( 'ucwp_review_in_use' );
		parent::tear_down();
	}

	private function dashboard(): \WP_Screen {
		return \WP_Screen::get( 'dashboard' );
	}

	public function test_an_administrator_sees_it_after_a_week_once_a_widget_is_in_use(): void {
		WidgetFactory::create( 'crypto-heatmap' );

		$this->assertTrue( $this->notice->shouldShow( $this->admin, $this->dashboard(), $this->now ) );
		$this->assertTrue( $this->notice->shouldShow( $this->admin, \WP_Screen::get( 'edit-ucwp_widget' ), $this->now ), 'On the plugin\'s own screens too.' );
	}

	public function test_not_in_the_first_week(): void {
		WidgetFactory::create( 'crypto-heatmap' );
		update_option( ReviewNotice::INSTALLED_OPTION, $this->now - 6 * DAY_IN_SECONDS );

		$this->assertFalse( $this->notice->shouldShow( $this->admin, $this->dashboard(), $this->now ) );
	}

	public function test_not_until_a_widget_is_in_use(): void {
		$this->assertFalse( $this->notice->shouldShow( $this->admin, $this->dashboard(), $this->now ) );
	}

	public function test_a_block_shortcode_or_elementor_element_in_published_content_counts_as_in_use(): void {
		foreach ( array( '<!-- wp:ucwp/widget {"settings":{"type":"crypto-heatmap"}} /-->', 'Prices: [ucwp_widget id="3"]' ) as $content ) {
			delete_transient( 'ucwp_review_in_use' );
			$post = self::factory()->post->create( array( 'post_content' => $content ) );
			$this->assertTrue( $this->notice->inUse(), $content );
			wp_delete_post( $post, true );
		}

		delete_transient( 'ucwp_review_in_use' );
		$page = self::factory()->post->create( array( 'post_type' => 'page' ) );
		update_post_meta( $page, '_elementor_data', wp_slash( '[{"elType":"widget","widgetType":"ucwp-crypto-heatmap"}]' ) );
		$this->assertTrue( $this->notice->inUse(), 'An Elementor element.' );
	}

	public function test_a_draft_does_not_count_as_in_use(): void {
		self::factory()->post->create( array( 'post_status' => 'draft', 'post_content' => '[ucwp_widget id="3"]' ) );

		$this->assertFalse( $this->notice->inUse() );
	}

	public function test_only_for_administrators(): void {
		WidgetFactory::create( 'crypto-heatmap' );

		$editor = self::factory()->user->create( array( 'role' => 'editor' ) );

		$this->assertFalse( $this->notice->shouldShow( $editor, $this->dashboard(), $this->now ) );
	}

	public function test_only_on_the_dashboard_and_the_plugins_screens(): void {
		WidgetFactory::create( 'crypto-heatmap' );

		$this->assertFalse( $this->notice->shouldShow( $this->admin, \WP_Screen::get( 'edit-post' ), $this->now ) );
		$this->assertFalse( $this->notice->shouldShow( $this->admin, \WP_Screen::get( 'plugins' ), $this->now ) );
		$this->assertFalse( $this->notice->shouldShow( $this->admin, null, $this->now ), 'Never outside the admin.' );
	}

	public function test_maybe_later_hides_it_for_30_days(): void {
		WidgetFactory::create( 'crypto-heatmap' );
		$this->notice->choose( $this->admin, 'later', $this->now );

		$this->assertFalse( $this->notice->shouldShow( $this->admin, $this->dashboard(), $this->now + 29 * DAY_IN_SECONDS ) );
		$this->assertTrue( $this->notice->shouldShow( $this->admin, $this->dashboard(), $this->now + 31 * DAY_IN_SECONDS ) );
	}

	public function test_reviewing_or_saying_no_hides_it_for_good(): void {
		WidgetFactory::create( 'crypto-heatmap' );

		foreach ( array( 'review', 'never' ) as $choice ) {
			$this->notice->choose( $this->admin, $choice, $this->now );
			$this->assertFalse( $this->notice->shouldShow( $this->admin, $this->dashboard(), $this->now + 400 * DAY_IN_SECONDS ), $choice );
			delete_user_meta( $this->admin, ReviewNotice::USER_META );
		}
	}

	public function test_each_administrator_chooses_for_themselves(): void {
		WidgetFactory::create( 'crypto-heatmap' );
		$other = self::factory()->user->create( array( 'role' => 'administrator' ) );

		$this->notice->choose( $this->admin, 'never', $this->now );

		$this->assertTrue( $this->notice->shouldShow( $other, $this->dashboard(), $this->now ) );
	}

	public function test_an_unknown_choice_changes_nothing(): void {
		$this->notice->choose( $this->admin, 'forever-and-ever', $this->now );

		$this->assertSame( '', get_user_meta( $this->admin, ReviewNotice::USER_META, true ) );
	}

	public function test_the_install_time_is_set_once(): void {
		delete_option( ReviewNotice::INSTALLED_OPTION );
		$this->notice->rememberInstall();
		$first = get_option( ReviewNotice::INSTALLED_OPTION );

		update_option( ReviewNotice::INSTALLED_OPTION, 1000 );
		$this->notice->rememberInstall();

		$this->assertGreaterThan( 0, (int) $first );
		$this->assertSame( 1000, (int) get_option( ReviewNotice::INSTALLED_OPTION ), 'An existing time is never moved.' );
	}

	public function test_the_notice_offers_the_three_choices_through_a_checked_admin_action(): void {
		WidgetFactory::create( 'crypto-heatmap' );
		wp_set_current_user( $this->admin );
		set_current_screen( 'dashboard' );

		ob_start();
		$this->notice->print();
		$html = (string) ob_get_clean();

		$this->assertStringContainsString( 'Leave a review', $html );
		$this->assertStringContainsString( 'Maybe later', $html );
		$this->assertStringContainsString( 'don&#039;t ask again', $html );
		$this->assertSame( 3, substr_count( $html, 'action=' . ReviewNotice::ACTION ) );
		$this->assertSame( 3, substr_count( $html, '_wpnonce=' ) );
		$this->assertStringContainsString( 'target="_blank"', $html );
	}
}

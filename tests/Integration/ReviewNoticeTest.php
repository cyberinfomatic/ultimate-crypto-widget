<?php
/**
 * The get-started notice and the request for a review.
 *
 * @package Ultimate_Crypto_Widget
 */

namespace Cyberinfomatic\UltimateCryptoWidget\Tests\Integration;

use Cyberinfomatic\UltimateCryptoWidget\Admin\Preview;
use Cyberinfomatic\UltimateCryptoWidget\Admin\ReviewNotice;
use Cyberinfomatic\UltimateCryptoWidget\Plugin;
use Cyberinfomatic\UltimateCryptoWidget\Tests\Support\TestCase;
use Cyberinfomatic\UltimateCryptoWidget\Tests\Support\WidgetFactory;

/**
 * WordPress.org allows asking for a review only politely: dismissible, not everywhere, not
 * nagging. These tests hold the notice to that, and to recording use only from the admin.
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
		$this->forget_use();
	}

	public function tear_down() {
		$this->forget_use();
		parent::tear_down();
	}

	private function forget_use(): void {
		delete_option( ReviewNotice::USED_OPTION );
		delete_transient( 'ucwp_review_in_content' );
	}

	private function dashboard(): \WP_Screen {
		return \WP_Screen::get( 'dashboard' );
	}

	private function role( string $role ): int {
		return self::factory()->user->create( array( 'role' => $role ) );
	}

	public function test_before_any_use_an_administrator_is_shown_how_to_get_started(): void {
		$this->assertSame( 'start', $this->notice->which( $this->admin, $this->dashboard(), $this->now ) );
	}

	public function test_once_the_plugin_is_used_the_review_request_shows_straight_away(): void {
		$this->notice->recordUse( $this->admin );

		$this->assertSame( 'review', $this->notice->which( $this->admin, $this->dashboard(), $this->now ) );
		$this->assertSame( 'review', $this->notice->which( $this->admin, \WP_Screen::get( 'edit-ucwp_widget' ), $this->now ), 'On the plugin\'s own screens too.' );
	}

	public function test_saving_a_widget_counts_as_use_but_opening_the_add_screen_does_not(): void {
		wp_set_current_user( $this->admin );

		// "Add widget" creates an auto-draft.
		self::factory()->post->create( array( 'post_type' => 'ucwp_widget', 'post_status' => 'auto-draft' ) );
		$this->assertFalse( get_option( ReviewNotice::USED_OPTION ) );

		self::factory()->post->create( array( 'post_type' => 'ucwp_widget', 'post_status' => 'draft' ) );
		$this->assertNotFalse( get_option( ReviewNotice::USED_OPTION ) );
	}

	public function test_opening_a_preview_counts_as_use(): void {
		wp_set_current_user( $this->admin );
		// The notice's own callback runs before the preview prints and stops; call that priority only.
		global $wp_filter;
		foreach ( $wp_filter[ 'admin_post_' . Preview::ACTION ]->callbacks[5] ?? array() as $callback ) {
			call_user_func( $callback['function'] );
		}

		$this->assertNotFalse( get_option( ReviewNotice::USED_OPTION ) );
	}

	public function test_only_people_who_can_create_widgets_record_use(): void {
		$this->assertFalse( $this->notice->recordUse( $this->role( 'subscriber' ) ) );
		$this->assertFalse( $this->notice->recordUse( 0 ), 'Never a visitor.' );
		$this->assertFalse( get_option( ReviewNotice::USED_OPTION ) );

		$this->assertTrue( $this->notice->recordUse( $this->role( 'editor' ) ) );
	}

	public function test_a_widget_already_on_a_published_page_counts_as_use(): void {
		foreach ( array( '<!-- wp:ucwp/widget {"settings":{"type":"crypto-heatmap"}} /-->', 'Prices: [ucwp_widget id="3"]' ) as $content ) {
			$this->forget_use();
			$post = self::factory()->post->create( array( 'post_content' => $content ) );
			$this->assertTrue( $this->notice->inUse(), $content );
			wp_delete_post( $post, true );
		}

		$this->forget_use();
		$page = self::factory()->post->create( array( 'post_type' => 'page' ) );
		update_post_meta( $page, '_elementor_data', wp_slash( '[{"elType":"widget","widgetType":"ucwp-crypto-heatmap"}]' ) );
		$this->assertTrue( $this->notice->inUse(), 'An Elementor element.' );
	}

	public function test_a_draft_page_does_not_count_as_use(): void {
		self::factory()->post->create( array( 'post_status' => 'draft', 'post_content' => '[ucwp_widget id="3"]' ) );

		$this->assertFalse( $this->notice->inUse() );
	}

	public function test_still_new_to_it_asks_again_in_3_days(): void {
		$this->notice->recordUse( $this->admin );
		$this->notice->choose( $this->admin, 'later', $this->now );

		$this->assertSame( '', $this->notice->which( $this->admin, $this->dashboard(), $this->now + 2 * DAY_IN_SECONDS ) );
		$this->assertSame( 'review', $this->notice->which( $this->admin, $this->dashboard(), $this->now + 3 * DAY_IN_SECONDS + 1 ) );
	}

	public function test_reviewing_or_saying_no_ends_the_request_for_good(): void {
		$this->notice->recordUse( $this->admin );

		foreach ( array( 'review', 'never' ) as $choice ) {
			$this->notice->choose( $this->admin, $choice, $this->now );
			$this->assertSame( '', $this->notice->which( $this->admin, $this->dashboard(), $this->now + 400 * DAY_IN_SECONDS ), $choice );
			delete_user_meta( $this->admin, ReviewNotice::USER_META );
		}
	}

	public function test_dismissing_get_started_does_not_stop_the_review_request_later(): void {
		$this->notice->choose( $this->admin, 'start-done', $this->now );
		$this->assertSame( '', $this->notice->which( $this->admin, $this->dashboard(), $this->now ) );

		$this->notice->recordUse( $this->admin );
		$this->assertSame( 'review', $this->notice->which( $this->admin, $this->dashboard(), $this->now ) );
	}

	public function test_only_administrators_see_either(): void {
		$editor = $this->role( 'editor' );

		$this->assertSame( '', $this->notice->which( $editor, $this->dashboard(), $this->now ) );
		$this->notice->recordUse( $editor );
		$this->assertSame( '', $this->notice->which( $editor, $this->dashboard(), $this->now ) );
	}

	public function test_only_on_the_dashboard_and_the_plugins_screens(): void {
		$this->assertSame( '', $this->notice->which( $this->admin, \WP_Screen::get( 'edit-post' ), $this->now ) );
		$this->assertSame( '', $this->notice->which( $this->admin, \WP_Screen::get( 'plugins' ), $this->now ) );
		$this->assertSame( '', $this->notice->which( $this->admin, null, $this->now ), 'Never outside the admin.' );
	}

	public function test_each_administrator_chooses_for_themselves(): void {
		$this->notice->recordUse( $this->admin );
		$other = $this->role( 'administrator' );

		$this->notice->choose( $this->admin, 'never', $this->now );

		$this->assertSame( 'review', $this->notice->which( $other, $this->dashboard(), $this->now ) );
	}

	public function test_an_unknown_choice_changes_nothing(): void {
		$this->notice->choose( $this->admin, 'forever-and-ever', $this->now );

		$this->assertSame( '', get_user_meta( $this->admin, ReviewNotice::USER_META, true ) );
		$this->assertSame( '', get_user_meta( $this->admin, ReviewNotice::START_META, true ) );
	}

	public function test_the_get_started_notice_links_to_adding_a_widget(): void {
		$html = $this->printed();

		$this->assertStringContainsString( 'Add a widget', $html );
		$this->assertStringContainsString( 'post-new.php?post_type=ucwp_widget', $html );
		$this->assertStringContainsString( 'choice=start-done', $html );
		$this->assertStringNotContainsString( 'Leave a review', $html, 'Never two asks at once.' );
		$this->assertStringContainsString( ReviewNotice::REQUEST_URL, $html, 'Missing a widget? Request it.' );
		$this->assertStringContainsString( 'assets/images/ucwp-logo.svg', $html );
		$this->assertStringNotContainsString( ReviewNotice::GITHUB_URL, $html, 'The star is asked for only with the review.' );
	}

	public function test_the_review_request_offers_its_three_choices_through_a_checked_admin_action(): void {
		$this->notice->recordUse( $this->admin );
		$html = $this->printed();

		$this->assertStringContainsString( 'Leave a review', $html );
		$this->assertStringContainsString( 'Still new to it, ask me in 3 days', $html );
		$this->assertStringContainsString( 'don&#039;t ask again', $html );
		$this->assertSame( 3, substr_count( $html, 'action=' . ReviewNotice::ACTION ) );
		$this->assertSame( 3, substr_count( $html, '_wpnonce=' ) );
		$this->assertStringContainsString( 'target="_blank"', $html );
		$this->assertStringNotContainsString( 'Add a widget', $html, 'Never two asks at once.' );
		$this->assertStringContainsString( ReviewNotice::GITHUB_URL, $html );
		$this->assertStringContainsString( ReviewNotice::REQUEST_URL, $html );
		$this->assertStringContainsString( 'ucw-notice__cta', $html, 'The review is the one gold button.' );
		$this->assertSame( 1, substr_count( $html, 'ucw-notice__cta' ) );
	}

	public function test_the_script_is_told_where_to_report_a_copied_shortcode(): void {
		wp_set_current_user( $this->admin );
		$data = ReviewNotice::scriptData();

		$this->assertStringEndsWith( 'admin-ajax.php', $data['url'] );
		$this->assertSame( ReviewNotice::USE_ACTION, $data['action'] );
		$this->assertSame( 1, wp_verify_nonce( $data['nonce'], ReviewNotice::USE_ACTION ) );
	}

	private function printed(): string {
		wp_set_current_user( $this->admin );
		set_current_screen( 'dashboard' );

		ob_start();
		$this->notice->print();

		return (string) ob_get_clean();
	}
}

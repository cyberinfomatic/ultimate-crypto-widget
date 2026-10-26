<?php

namespace Cyberinfomatic\UltimateCryptoWidget\Admin;

use Cyberinfomatic\UltimateCryptoWidget\Lib\WPToolkit\Foundation\HookRegistrar;
use Cyberinfomatic\UltimateCryptoWidget\Widgets\WidgetRepository;

/**
 * A one-time request for a review on WordPress.org, within WordPress.org's rules: administrators only, on the
 * Dashboard and this plugin's own screens, never before the plugin has been in use for a week, and gone for good
 * once someone reviews or says no. "Maybe later" hides it for 30 days. Each administrator's choice is their own.
 */
final class ReviewNotice
{
	public const ACTION = 'ucwp_review_notice';

	/** WordPress.org's form for a new review of this plugin. */
	public const REVIEW_URL = 'https://wordpress.org/support/plugin/ultimate-crypto-widget/reviews/#new-post';

	/** When this site first ran a version with the notice, as a Unix time. */
	public const INSTALLED_OPTION = 'ucwp_installed_at';

	/** Per user: "done" for good, or the Unix time until which it is snoozed. */
	public const USER_META = 'ucwp_review_notice';

	public const WAIT_DAYS   = 7;
	public const SNOOZE_DAYS = 30;

	/** Whether a widget is in use, worked out at most once a day. */
	private const IN_USE_TRANSIENT = 'ucwp_review_in_use';

	public const CHOICES = ['review', 'later', 'never'];

	/** The registrar every hook goes through. */
	public function __construct(private readonly HookRegistrar $hooks)
	{
	}

	/**
	 * Call once, in the admin.
	 */
	public function register(): void
	{
		$this->hooks->addAction('admin_init', fn () => $this->rememberInstall());
		$this->hooks->addAction('admin_notices', fn () => $this->print());
		$this->hooks->addAction('admin_post_' . self::ACTION, fn () => $this->handle());
	}

	/** Starts the week's wait the first time this version runs. Sites that update wait a week from then. */
	public function rememberInstall(): void
	{
		if (false === get_option(self::INSTALLED_OPTION)) {
			add_option(self::INSTALLED_OPTION, time(), '', false);
		}
	}

	/**
	 * Whether this user should see the notice on this screen now.
	 */
	public function shouldShow(int $userId, ?\WP_Screen $screen, int $now): bool
	{
		if (!user_can($userId, 'manage_options') || !self::isOurPlace($screen)) {
			return false;
		}

		$installed = (int) get_option(self::INSTALLED_OPTION, 0);
		if ($installed <= 0 || $now - $installed < self::WAIT_DAYS * DAY_IN_SECONDS) {
			return false;
		}

		$state = (string) get_user_meta($userId, self::USER_META, true);
		if ('done' === $state || (ctype_digit($state) && $now < (int) $state)) {
			return false;
		}

		return $this->inUse();
	}

	/**
	 * Records a choice for a user: a review or "never" hide it for good, "later" for SNOOZE_DAYS.
	 */
	public function choose(int $userId, string $choice, int $now): void
	{
		if (!in_array($choice, self::CHOICES, true)) {
			return;
		}
		$value = 'later' === $choice ? (string) ($now + self::SNOOZE_DAYS * DAY_IN_SECONDS) : 'done';
		update_user_meta($userId, self::USER_META, $value);
	}

	/**
	 * Whether a widget is on the site: a published saved widget, or the block, the shortcode or an Elementor
	 * element in published content.
	 */
	public function inUse(): bool
	{
		$cached = get_transient(self::IN_USE_TRANSIENT);
		if (false !== $cached) {
			return '1' === $cached;
		}

		$counts = wp_count_posts(WidgetRepository::POST_TYPE);
		$found  = (int) ($counts->publish ?? 0) > 0 || self::inContent();

		// A yes stays yes; a no is asked again tomorrow.
		set_transient(self::IN_USE_TRANSIENT, $found ? '1' : '0', $found ? WEEK_IN_SECONDS : DAY_IN_SECONDS);

		return $found;
	}

	/**
	 * Hooked to admin_notices.
	 */
	public function print(): void
	{
		$screen = function_exists('get_current_screen') ? get_current_screen() : null;
		if (!$this->shouldShow(get_current_user_id(), $screen, time())) {
			return;
		}

		$link = static fn (string $choice): string => wp_nonce_url(
			add_query_arg(['action' => self::ACTION, 'choice' => $choice], admin_url('admin-post.php')),
			self::ACTION
		);

		printf(
			'<div class="notice notice-info ucw-review-notice"><p>%1$s</p><p>'
				. '<a class="button button-primary" href="%2$s" target="_blank" rel="noopener">%3$s</a> '
				. '<a class="button" href="%4$s">%5$s</a> '
				. '<a href="%6$s">%7$s</a></p></div>',
			esc_html__('Is Ultimate Crypto Widget working well for you? A short review on WordPress.org helps other site owners find it, and helps us keep it free.', 'ultimate-crypto-widget'),
			esc_url($link('review')),
			esc_html__('Leave a review', 'ultimate-crypto-widget'),
			esc_url($link('later')),
			esc_html__('Maybe later', 'ultimate-crypto-widget'),
			esc_url($link('never')),
			esc_html__('I already did, don\'t ask again', 'ultimate-crypto-widget')
		);
	}

	/**
	 * Hooked to admin_post_ucwp_review_notice: records the choice, then goes to the review form or back.
	 */
	public function handle(): void
	{
		if (!current_user_can('manage_options')) {
			wp_die(esc_html__('You do not have permission to do this.', 'ultimate-crypto-widget'), '', ['response' => 403]);
		}
		check_admin_referer(self::ACTION);

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- checked by check_admin_referer() above.
		$choice = isset($_GET['choice']) ? sanitize_key(wp_unslash($_GET['choice'])) : '';
		$this->choose(get_current_user_id(), $choice, time());

		if ('review' === $choice) {
			// A fixed address on wordpress.org, not one taken from the request.
			wp_redirect(self::REVIEW_URL); // phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect -- fixed external address.
			exit;
		}
		wp_safe_redirect(wp_get_referer() ?: admin_url());
		exit;
	}

	/** The Dashboard, or one of this plugin's screens. */
	private static function isOurPlace(?\WP_Screen $screen): bool
	{
		return null !== $screen && ('dashboard' === $screen->id || Screens::owns($screen));
	}

	/** Whether published content holds the block, the shortcode or an Elementor element of this plugin. */
	private static function inContent(): bool
	{
		global $wpdb;

		$block     = '%' . $wpdb->esc_like('<!-- wp:ucwp/widget') . '%';
		$shortcode = '%' . $wpdb->esc_like('[ucwp_widget') . '%';
		$element   = '%' . $wpdb->esc_like('"widgetType":"ucwp-') . '%';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- one LIMIT 1 query, cached by inUse().
		$post = $wpdb->get_var($wpdb->prepare("SELECT ID FROM {$wpdb->posts} WHERE post_status = 'publish' AND (post_content LIKE %s OR post_content LIKE %s) LIMIT 1", $block, $shortcode));
		if (null !== $post) {
			return true;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- as above.
		return null !== $wpdb->get_var($wpdb->prepare("SELECT meta_id FROM {$wpdb->postmeta} WHERE meta_key = '_elementor_data' AND meta_value LIKE %s LIMIT 1", $element));
	}
}

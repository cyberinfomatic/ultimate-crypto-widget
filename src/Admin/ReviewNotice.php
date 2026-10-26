<?php

namespace Cyberinfomatic\UltimateCryptoWidget\Admin;

use Cyberinfomatic\UltimateCryptoWidget\Lib\WPToolkit\Foundation\HookRegistrar;
use Cyberinfomatic\UltimateCryptoWidget\Widgets\WidgetRepository;

/**
 * One notice for administrators, on the Dashboard and this plugin's own screens, that says one of two things:
 *
 * - **Get started**, while nobody on the site has used the plugin yet: a button to add the first widget.
 * - **A review on WordPress.org**, as soon as someone has: a widget saved, a shortcode copied, a preview opened,
 *   or a widget on a published page. "Still new to it" asks again in 3 days.
 *
 * Within WordPress.org's rules: dismissible, never on the front end, never two asks at once. Use is recorded from
 * actions in the admin by people who can create widgets, never from visitors. Each administrator's choices are
 * their own.
 */
final class ReviewNotice
{
	public const ACTION = 'ucwp_review_notice';

	/** The admin-ajax action the editor script calls when a shortcode is copied. */
	public const USE_ACTION = 'ucwp_mark_used';

	/** WordPress.org's form for a new review of this plugin. */
	public const REVIEW_URL = 'https://wordpress.org/support/plugin/ultimate-crypto-widget/reviews/#new-post';

	/** Where to ask for a widget the plugin does not have: a new topic in its WordPress.org support forum. */
	public const REQUEST_URL = 'https://wordpress.org/support/plugin/ultimate-crypto-widget/#new-post';

	/** The plugin's public source, for a star. */
	public const GITHUB_URL = 'https://github.com/cyberinfomatic/ultimate-crypto-widget';

	/** When the plugin was first used on this site, as a Unix time; missing until then. */
	public const USED_OPTION = 'ucwp_used_at';

	/** Per user, the review request: "done" for good, or the Unix time until which it is snoozed. */
	public const USER_META = 'ucwp_review_notice';

	/** Per user, the get-started notice: "done" once dismissed. */
	public const START_META = 'ucwp_start_notice';

	/** How long "Still new to it" puts the review request off. */
	public const SNOOZE_DAYS = 3;

	/** Whether published content holds a widget, worked out at most once a day while nothing else says so. */
	private const CONTENT_TRANSIENT = 'ucwp_review_in_content';

	/** What the notice's buttons may ask for. */
	public const CHOICES = ['review', 'later', 'never', 'start-done'];

	/** The registrar every hook goes through. */
	public function __construct(private readonly HookRegistrar $hooks)
	{
	}

	/**
	 * Call once. Hooks only admin requests: the notice, its buttons, and the actions that count as use.
	 */
	public function register(): void
	{
		$this->hooks->addAction('admin_notices', fn () => $this->print());
		$this->hooks->addAction('admin_post_' . self::ACTION, fn () => $this->handle());

		// Use, from people who can create widgets: saving one, opening a preview (before the preview prints and
		// stops), copying a shortcode (the editor script reports it).
		$this->hooks->addAction('save_post_' . WidgetRepository::POST_TYPE, function (int $postId, \WP_Post $post): void {
			// Opening "Add widget" creates an auto-draft; only a real save counts.
			if ('auto-draft' !== $post->post_status && !wp_is_post_autosave($postId) && !wp_is_post_revision($postId)) {
				$this->recordUse(get_current_user_id());
			}
		}, 10, 2);
		$this->hooks->addAction('admin_post_' . Preview::ACTION, fn () => $this->recordUse(get_current_user_id()), 5);
		$this->hooks->addAction('wp_ajax_' . self::USE_ACTION, fn () => $this->handleUseReport());
	}

	/**
	 * What the editor script needs to report a copied shortcode.
	 *
	 * @return array{url: string, action: string, nonce: string}
	 */
	public static function scriptData(): array
	{
		return ['url' => admin_url('admin-ajax.php'), 'action' => self::USE_ACTION, 'nonce' => wp_create_nonce(self::USE_ACTION)];
	}

	/**
	 * Notes that the plugin is in use, if this user may create widgets. The first time only; later calls change
	 * nothing. Returns whether it is now recorded.
	 */
	public function recordUse(int $userId): bool
	{
		if ($userId <= 0 || !user_can($userId, 'edit_posts')) {
			return false;
		}
		if (false === get_option(self::USED_OPTION)) {
			add_option(self::USED_OPTION, time(), '', false);
		}

		return true;
	}

	/**
	 * Whether the plugin is in use: something recorded by recordUse(), or a widget already on a published page
	 * (sites that used it before this notice existed).
	 */
	public function inUse(): bool
	{
		if (false !== get_option(self::USED_OPTION)) {
			return true;
		}

		$cached = get_transient(self::CONTENT_TRANSIENT);
		$found  = false !== $cached ? '1' === $cached : self::inContent();
		if (false === $cached) {
			set_transient(self::CONTENT_TRANSIENT, $found ? '1' : '0', DAY_IN_SECONDS);
		}
		if ($found) {
			add_option(self::USED_OPTION, time(), '', false);
		}

		return $found;
	}

	/**
	 * Which notice this user should see on this screen now: "start", "review", or "" for none.
	 */
	public function which(int $userId, ?\WP_Screen $screen, int $now): string
	{
		if (!user_can($userId, 'manage_options') || !self::isOurPlace($screen)) {
			return '';
		}

		if (!$this->inUse()) {
			return 'done' === get_user_meta($userId, self::START_META, true) ? '' : 'start';
		}

		$state = (string) get_user_meta($userId, self::USER_META, true);
		if ('done' === $state || (ctype_digit($state) && $now < (int) $state)) {
			return '';
		}

		return 'review';
	}

	/**
	 * Records a choice for a user: a review or "never" end the review request, "later" puts it off SNOOZE_DAYS,
	 * "start-done" closes the get-started notice.
	 */
	public function choose(int $userId, string $choice, int $now): void
	{
		if ('start-done' === $choice) {
			update_user_meta($userId, self::START_META, 'done');
			return;
		}
		if (!in_array($choice, self::CHOICES, true)) {
			return;
		}
		$value = 'later' === $choice ? (string) ($now + self::SNOOZE_DAYS * DAY_IN_SECONDS) : 'done';
		update_user_meta($userId, self::USER_META, $value);
	}

	/**
	 * Hooked to admin_notices.
	 */
	public function print(): void
	{
		$screen = function_exists('get_current_screen') ? get_current_screen() : null;
		$which  = $this->which(get_current_user_id(), $screen, time());
		if ('' === $which) {
			return;
		}

		$link = static fn (string $choice): string => wp_nonce_url(
			add_query_arg(['action' => self::ACTION, 'choice' => $choice], admin_url('admin-post.php')),
			self::ACTION
		);

		$external = static fn (string $url, string $class, string $text): string => sprintf(
			'<a class="%1$s" href="%2$s" target="_blank" rel="noopener">%3$s<span class="screen-reader-text"> %4$s</span></a>',
			esc_attr($class),
			esc_url($url),
			esc_html($text),
			esc_html__('(opens in a new tab)', 'ultimate-crypto-widget')
		);
		$plain = static fn (string $url, string $class, string $text): string => sprintf('<a class="%1$s" href="%2$s">%3$s</a>', esc_attr($class), esc_url($url), esc_html($text));
		$request = sprintf(
			'%1$s %2$s',
			esc_html__('Missing a widget you need?', 'ultimate-crypto-widget'),
			$external(self::REQUEST_URL, 'ucw-notice__request', __('Request it', 'ultimate-crypto-widget'))
		);

		if ('start' === $which) {
			self::card(
				'start',
				__('Ultimate Crypto Widget is ready', 'ultimate-crypto-widget'),
				__('Add your first live price widget in a minute: pick one from the Crypto Widgets section of the block editor, or set one up here and paste its shortcode anywhere.', 'ultimate-crypto-widget'),
				$plain(admin_url('post-new.php?post_type=' . WidgetRepository::POST_TYPE), 'ucw-notice__cta', __('+ Add a widget', 'ultimate-crypto-widget'))
					. $plain($link('start-done'), 'ucw-notice__link', __('Dismiss', 'ultimate-crypto-widget')),
				$request
			);
			return;
		}

		self::card(
			'review',
			__('Enjoying Ultimate Crypto Widget?', 'ultimate-crypto-widget'),
			__('A short review on WordPress.org helps other site owners find it, and helps us keep it free.', 'ultimate-crypto-widget'),
			$external($link('review'), 'ucw-notice__cta', __('★ Leave a review', 'ultimate-crypto-widget'))
				. $external(self::GITHUB_URL, 'ucw-notice__secondary', __('☆ Star on GitHub', 'ultimate-crypto-widget'))
				. $plain($link('later'), 'ucw-notice__link', __('Still new to it, ask me in 3 days', 'ultimate-crypto-widget'))
				. $plain($link('never'), 'ucw-notice__link', __('I already did, don\'t ask again', 'ultimate-crypto-widget')),
			$request
		);
	}

	/**
	 * Prints a notice in the plugin's colours: the logo, a bold title, a line of text, the actions, and a footer.
	 * `$actions` and `$footer` are HTML already escaped by the caller.
	 */
	private static function card(string $kind, string $title, string $text, string $actions, string $footer): void
	{
		printf(
			'<div class="notice ucw-notice ucw-notice--%1$s">'
				. '<img class="ucw-notice__logo" src="%2$s" alt="" width="48" height="48" />'
				. '<div class="ucw-notice__body">'
				. '<p class="ucw-notice__title">%3$s</p>'
				. '<p class="ucw-notice__text">%4$s</p>'
				. '<p class="ucw-notice__actions">%5$s</p>'
				. '<p class="ucw-notice__footer">%6$s</p>'
				. '</div></div>',
			esc_attr($kind),
			esc_url(UCWP_PLUGIN_URL . 'assets/images/ucwp-logo.svg'),
			esc_html($title),
			esc_html($text),
			$actions, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by print().
			$footer // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by print().
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

	/**
	 * Hooked to wp_ajax_ucwp_mark_used: the editor script says a shortcode was copied.
	 */
	public function handleUseReport(): void
	{
		check_ajax_referer(self::USE_ACTION);
		wp_send_json_success(['recorded' => $this->recordUse(get_current_user_id())]);
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

		$counts = wp_count_posts(WidgetRepository::POST_TYPE);
		if ((int) ($counts->publish ?? 0) > 0) {
			return true;
		}

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

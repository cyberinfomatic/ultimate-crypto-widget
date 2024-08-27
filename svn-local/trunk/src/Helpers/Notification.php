<?php

namespace Cyberinfomatic\UltimateCryptoWidget\Helpers;

use Cyberinfomatic\UltimateCryptoWidget\Controllers\Page;

/**
 * Class Notification
 *
 * Handles the display and management of notifications within the Ultimate Crypto Widget plugin.
 *
 * @package Cyberinfomatic\UltimateCryptoWidget\Helpers
 */
class Notification {

	private static string $cache_name;

	/**
	 * Returns a unique cache key for the current user.
	 *
	 * @param bool $all Whether to get the cache key for all users.
	 *
	 * @return string The unique cache key for the user.
	 */
	static function getUserNotificationCacheKey(): string {
		if (isset(static::$cache_name)) {
			return static::$cache_name;
		}
		static::$cache_name = 'ucwp_notification_user_';
		return static::$cache_name;
	}

	/**
	 * Registers the display_notifications method to the admin_notices action hook.
	 *
	 * @return bool|null True on success, false on failure, or null if not in admin.
	 */
	static function show_notices(): ?bool {
		if (is_admin()) {
			return add_action('admin_notices', [self::class, 'display_notifications']);
		}
		return null;
	}

	/**
	 * Adds a notification message to be displayed in the admin area.
	 *
	 * @param string $message The notification message.
	 * @param string $type The type of notification (default: 'success').
	 * @param string|array $allowed_pages The pages where the notification is allowed to be displayed.
	 *                                    Accepts:
	 *                                    - 'all': Display on all admin pages.
	 *                                    - 'plugin': Display on all plugin-specific pages.
	 *                                    - 'parent': Display only on the current page.
	 *                                    - array of page keys: Display on specific pages.
	 * @return bool True on success, false on failure.
	 */
	static function add_notification(string $message, string $type = 'success', string|array $allowed_pages = 'parent'): bool {
		$type = sanitize_key($type);
		$message = wp_kses_post($message); // Allowing specific HTML in the message
		$notifications = get_transient(self::getUserNotificationCacheKey());
		$notifications = $notifications ?: [];
		$notifications[] = ['message' => $message, 'type' => $type, 'allowed_pages' => $allowed_pages];
		return set_transient(self::getUserNotificationCacheKey(), $notifications, 20);
	}

	/**
	 * Retrieves all notifications for the current user.
	 *
	 * @return array The array of notifications.
	 */
	static function get_notifications(): array {
		$d = get_transient(self::getUserNotificationCacheKey());
		return $d ? $d : [];
	}

	/**
	 * Clears all notifications for the current user.
	 */
	static function clear_notifications(): void {
		delete_transient(self::getUserNotificationCacheKey());
	}

	/**
	 * Displays the notifications in the WordPress admin area.
	 */
	static function display_notifications(): void {
		$notifications = self::get_notifications();
		$notifications = array_unique($notifications, SORT_REGULAR); 	// remove notification with the same message and type
		self::clear_notifications();
		if (empty($notifications)) {
			return;
		}

		$current_page = Page::get_current_page_key();
		foreach ($notifications as $notification) {
			$message = wp_kses_post($notification['message']); // Escaping the message for HTML output
			$type = esc_attr($notification['type']); // Escaping the type for use in HTML attributes
			$allowed_pages = $notification['allowed_pages'];

			$show_notification = false;
			if ($allowed_pages === 'all') {
				$show_notification = true;
			} elseif ($allowed_pages === 'plugin' && self::is_plugin_page($current_page)) {
				$show_notification = true;
			} elseif ($allowed_pages === 'parent' && $current_page === Page::get_current_page_key()) {
				$show_notification = true;
			} elseif (is_array($allowed_pages) && in_array($current_page, $allowed_pages, true)) {
				$show_notification = true;
			}

			if ($show_notification) {
				?>
				<div class="notice notice-<?php echo $type; ?> is-dismissible">
					<p><?php echo sprintf(esc_html('Ultimate Crypto Widget: %s', 'ultimate-crypto-widget'), $message); ?></p>
				</div>
				<?php
			} else {
				// Re-add the notification if it's not shown on this page
				self::add_notification($message, $type, $allowed_pages);
			}
		}
	}

	/**
	 * Helper function to check if the current page is a plugin-specific page.
	 *
	 * @param string $current_page The key of the current page.
	 *
	 * @return bool True if it's a plugin-specific page, false otherwise.
	 */
	private static function is_plugin_page(string $current_page): bool {
		$plugin_pages = Page::get_all_page_keys();
		return in_array($current_page, $plugin_pages, true);
	}
}

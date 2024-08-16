<?php

namespace Cyberinfomatic\UltimateCryptoWidget\Helpers;

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
	 */
	static function add_notification(string $message, string $type = 'success'): bool {
		$type = sanitize_key($type);
		$message = wp_kses_post($message); // Allowing specific HTML in the message
		$notifications = get_transient(self::getUserNotificationCacheKey());
		$notifications = $notifications ?: [];
		$notifications[] = ['message' => $message, 'type' => $type];
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
		if (empty($notifications)) {
			return;
		}
		// remove notification with the same message and type
		$notifications = array_unique($notifications, SORT_REGULAR);
		foreach ($notifications as $notification) {
			$message = wp_kses_post($notification['message']); // Escaping the message for HTML output
			$type = esc_attr($notification['type']); // Escaping the type for use in HTML attributes
			?>
			<div class="notice notice-<?php echo $type; ?> is-dismissible">
				<p><?php echo sprintf(__('Ultimate Crypto Widget : %s', 'ultimate-crypto-widget'), $message); ?></p>
			</div>
			<?php
		}
		self::clear_notifications();
	}
}

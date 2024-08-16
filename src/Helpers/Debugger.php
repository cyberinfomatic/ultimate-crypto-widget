<?php

namespace Cyberinfomatic\UltimateCryptoWidget\Helpers;

/**
 * Class Debugger
 *
 * A helper class for debugging and logging messages in the development environment.
 *
 * @package Cyberinfomatic\UltimateCryptoWidget\Helpers
 */
class Debugger {
	/**
	 * @var bool $is_dev Flag indicating if the environment is development.
	 */
	private static bool $is_dev = false;

	/**
	 * Initialize the debugger environment.
	 *
	 * @param bool $is_dev Indicates if the environment is development.
	 */
	public static function set_dev(bool $is_dev): void {
		self::$is_dev = $is_dev;
		self::log('Debugger initialized with is_dev = ' . ($is_dev ? 'true' : 'false'));
	}

	/**
	 * Log a message to the browser console.
	 *
	 * @param string $message The message to log.
	 * @param string $type The type of console method to use (e.g., 'log', 'error').
	 * @param bool $safe Optional. If true, adds the script to the footer using wp_footer action.
	 * @return bool|null True if the script is echoed, false otherwise. Null if not in development mode.
	 */
	public static function console(mixed $message, string $type = 'log', bool $safe = true): ?bool {
		self::log($message);
		if (!self::$is_dev) {
			return false;
		}

		// If the message is not a string, convert it to a string
		if (!is_string($message)) {
			$message = print_r($message, true);
		}

		// Make sure $type is in the list of allowed console methods
		$allowed_types = ['log', 'info', 'warn', 'error'];
		if (!in_array($type, $allowed_types)) {
			$type = 'log';
		}

		// Prepare the script
		$safe_message = esc_js($message);
		$backtrace = debug_backtrace();
		$caller = $backtrace[0];
		$caller['file'] = esc_js(str_replace('\\', '/', $caller['file']));
		$caller['line'] = esc_js($caller['line']);

		$script = "console.$type('ucwp Debugger : $safe_message '.concat('Called by {$caller['file']} on line {$caller['line']}'));";

		// Use wp_add_inline_script or wp_footer to add the script
		if ($safe) {
			add_action('wp_footer', function() use ($script) {
				echo '<script>' . $script . '</script>';
			});
			return true;
		} else {
			echo '<script>' . $script . '</script>';
			return true;
		}
	}

	/**
	 * Print a human-readable representation of a variable.
	 *
	 * @param mixed $data The data to print.
	 * @param bool|string $die Optional. If true, stops the execution after printing.
	 */
	public static function print_r(mixed $data, bool|string $die = false): void {
		self::log($data);
		if (self::$is_dev) {
			echo esc_html("ucwp Debugger :");
			$backtrace = debug_backtrace();
			$caller = $backtrace[0];
			echo wp_kses_post("<pre>Called by " . esc_html($caller['file']) . " on line " . esc_html($caller['line']) . "</pre>");
			echo "<pre>";
			echo esc_html(print_r($data, true)); // Escaping the printed data
			echo "</pre>";
			if ($die) {
				wp_die(esc_html($die));
			}
		}
	}

	/**
	 * Dump information about a variable.
	 *
	 * @param mixed $data The data to dump.
	 * @param bool|string $die Optional. If true, stops the execution after dumping.
	 */
	public static function var_dump(mixed $data, bool|string $die = false): void {
		self::log($data);
		if (self::$is_dev) {
			echo esc_html("ucwp Debugger :");
			$backtrace = debug_backtrace();
			$caller = $backtrace[0];
			echo wp_kses_post("<pre>Called by " . esc_html($caller['file']) . " on line " . esc_html($caller['line']) . "</pre>");
			echo "<pre>";
			ob_start();
			var_dump($data);
			echo esc_html(ob_get_clean()); // Escaping the dumped data
			echo "</pre>";
			if ($die) {
				wp_die(esc_html($die));
			}
		}
	}

	/**
	 * Acts as a breakpoint to stop the script execution and inspect the current state.
	 *
	 * @param string $message Optional. A message to display before stopping execution.
	 */
	public static function breaker(string $message = ''): void {
		self::log($message);
		if (self::$is_dev) {
			if (!empty($message)) {
				echo "<pre>" . esc_html("ucwp Debugger BREAKER : ") . "</pre>";
				$backtrace = debug_backtrace();
				$caller = $backtrace[0];
				echo wp_kses_post("<pre>Called by " . esc_html($caller['file']) . " on line " . esc_html($caller['line']) . "</pre>");
				echo "<pre>Message " . esc_html($message) . "</pre>";
			}
			wp_die(esc_html('Script execution stopped by Debugger::breaker.'));
		}
	}

	/**
	 * Add a notification message to be displayed in the admin area.
	 *
	 * @param string $message The notification message.
	 * @param string $type The type of notification (default: 'success').
	 * @return bool True on success, false on failure.
	 */
	public static function notification(string $message, string $type = 'success'): bool {
		$message = esc_html($message);
		$type = sanitize_key($type);
		return Notification::add_notification("ucwp Debugger : $message", $type);
	}

	/**
	 * Sleep for a given number of seconds.
	 *
	 * @param int $seconds The number of seconds to sleep.
	 */
	public static function sleep(int $seconds): void {
		if (self::$is_dev) {
			sleep($seconds);
		}
	}

	/**
	 * Log a message to the error log.
	 * @param mixed $message The message to log.
	 *
	 * @return void
	 */
	static function log(mixed $message): void {
		if (!is_string($message)) {
			$message = print_r($message, true);
		}
		$message = "ucwp Debugger : " . $message;
		error_log($message);
	}
}

<?php
/**
 * Checks that an upgrade kept every saved widget: take a snapshot of a WordPress site before the
 * upgrade, another after it, and compare them.
 *
 *   php bin/compare-widgets.php dump <path-to-wordpress> <snapshot.json> [--no-render] [--host=example.test]
 *   php bin/compare-widgets.php compare <before.json> <after.json>
 *
 * What a snapshot holds, per saved widget: its stored settings (as rows, and what they mean once a list
 * is read as a list), and, unless --no-render, what its shortcode renders (its settings as the widget
 * receives them, and the text a visitor sees). It also holds the plugin's options, with anything that
 * looks like a key or a secret replaced by "[redacted]". It reads the database; it changes nothing.
 *
 * Differences that fail the comparison: a widget gone or added, a setting whose meaning changed, a
 * widget that now receives different settings. A coin or currency list stored in another shape (text
 * "a,b" before, an array after) is only reported: that is the intended change.
 *
 * Not part of the plugin: /bin is left out of the release zip.
 */

if ('cli' !== PHP_SAPI) {
	exit(1);
}

const LIST_SETTINGS = array('coins', 'currencies');
const KEY_PREFIX    = 'ucwp_widget_';
const SECRET_NAME   = '/key|licen[sc]e|secret|token|password|salt|signature/i';

exit(main($argv));

function main(array $argv): int
{
	$args  = array_values(array_filter(array_slice($argv, 1), static fn ($a) => 0 !== strpos($a, '--')));
	$flags = array_values(array_filter(array_slice($argv, 1), static fn ($a) => 0 === strpos($a, '--')));

	$command = $args[0] ?? '';
	if ('dump' === $command && 3 === count($args)) {
		return dump($args[1], $args[2], $flags);
	}
	if ('compare' === $command && 3 === count($args)) {
		return compare($args[1], $args[2]);
	}

	fwrite(STDERR, "Usage:\n  php bin/compare-widgets.php dump <path-to-wordpress> <snapshot.json> [--no-render] [--host=example.test]\n  php bin/compare-widgets.php compare <before.json> <after.json>\n");

	return 2;
}

/** The meaning of a stored value: lists become "a,b,c" however they were stored. */
function meaning(string $setting, $value): string
{
	if (in_array($setting, LIST_SETTINGS, true)) {
		$items = is_array($value) ? $value : explode(',', (string) $value);
		$items = array_map(static fn ($item) => trim((string) $item), $items);

		return implode(',', array_values(array_filter($items, static fn ($item) => '' !== $item)));
	}

	return is_scalar($value) || null === $value ? (string) $value : (string) json_encode($value);
}

/** How a row is stored. */
function shape(string $raw): string
{
	if ('' === $raw) {
		return 'empty';
	}
	if (is_serialized($raw)) {
		return 'serialized';
	}

	return false !== strpos($raw, ',') ? 'comma' : 'text';
}

function dump(string $path, string $out, array $flags): int
{
	$root = rtrim($path, '/\\');
	if (!is_file($root . '/wp-load.php')) {
		fwrite(STDERR, "No wp-load.php in {$root}\n");

		return 2;
	}

	$host = 'localhost';
	foreach ($flags as $flag) {
		if (0 === strpos($flag, '--host=')) {
			$host = substr($flag, 7);
		}
	}
	$_SERVER['HTTP_HOST']      = $host;
	$_SERVER['REQUEST_URI']    = '/';
	$_SERVER['SERVER_NAME']    = $host;
	$_SERVER['REQUEST_METHOD'] = 'GET';

	define('WP_USE_THEMES', false);
	ob_start();
	require $root . '/wp-load.php';
	require_once ABSPATH . 'wp-admin/includes/plugin.php';
	ob_end_clean();

	global $wpdb;
	$render = !in_array('--no-render', $flags, true);

	$plugins = array();
	foreach (get_plugins() as $file => $data) {
		if (false !== stripos($file, 'crypto')) {
			$plugins[$file] = array('version' => $data['Version'], 'active' => is_plugin_active($file));
		}
	}

	$widgets = array();
	$posts   = get_posts(array('post_type' => 'ucwp_widget', 'post_status' => 'any', 'numberposts' => -1, 'orderby' => 'ID', 'order' => 'ASC'));
	foreach ($posts as $post) {
		$rows = $wpdb->get_results(
			$wpdb->prepare("SELECT meta_key, meta_value FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key LIKE %s ORDER BY meta_key", $post->ID, $wpdb->esc_like(KEY_PREFIX) . '%'),
			ARRAY_A
		);

		$settings = array();
		$shapes   = array();
		foreach ($rows as $row) {
			$name            = substr($row['meta_key'], strlen(KEY_PREFIX));
			$settings[$name] = meaning($name, maybe_unserialize($row['meta_value']));
			$shapes[$name]   = shape((string) $row['meta_value']);
		}

		$widgets[(string) $post->ID] = array(
			'title'    => $post->post_title,
			'status'   => $post->post_status,
			'settings' => $settings,
			'shapes'   => $shapes,
			'render'   => $render ? render($post->ID) : null,
		);
	}

	$options = array();
	$names   = $wpdb->get_col($wpdb->prepare("SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s ORDER BY option_name", $wpdb->esc_like('ultimate_crypto_widget_') . '%'));
	foreach ($names as $name) {
		$options[$name] = preg_match(SECRET_NAME, $name) ? '[redacted]' : get_option($name);
	}

	$snapshot = array(
		'taken'   => gmdate('c'),
		'site'    => get_option('siteurl'),
		'plugins' => $plugins,
		'widgets' => $widgets,
		'options' => $options,
	);

	file_put_contents($out, json_encode($snapshot, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n");
	printf("%d widgets, %d options written to %s\n", count($widgets), count($options), $out);

	return 0;
}

/** What a widget's shortcode renders now. Prices are not kept: they change from one run to the next. */
function render(int $id): array
{
	try {
		ob_start();
		$html = do_shortcode('[ucwp_widget id="' . $id . '"]');
		ob_end_clean();
	} catch (Throwable $error) {
		if (ob_get_level() > 0) {
			ob_end_clean();
		}

		return array('error' => $error->getMessage());
	}

	$props = null;
	if (preg_match('/data-ucw-props="([^"]*)"/', $html, $found)) {
		$decoded = json_decode(html_entity_decode($found[1], ENT_QUOTES), true);
		$props   = is_array($decoded) && isset($decoded['settings']) && is_array($decoded['settings']) ? array_map('meaning_of', $decoded['settings']) : null;
	}

	return array(
		'view'     => preg_match('/data-ucw-view="([^"]*)"/', $html, $view) ? $view[1] : null,
		'settings' => $props,
		'text'     => substr(trim((string) preg_replace('/\s+/', ' ', wp_strip_all_tags($html))), 0, 300),
		'empty'    => '' === trim($html),
	);
}

function meaning_of($value): string
{
	return is_scalar($value) || null === $value ? (string) $value : (string) json_encode($value);
}

function load(string $file): array
{
	$data = is_file($file) ? json_decode((string) file_get_contents($file), true) : null;
	if (!is_array($data) || !isset($data['widgets'])) {
		fwrite(STDERR, "{$file} is not a snapshot.\n");
		exit(2);
	}

	return $data;
}

function compare(string $beforeFile, string $afterFile): int
{
	$before = load($beforeFile);
	$after  = load($afterFile);

	$failures = array();
	$notes    = array();

	foreach ($before['widgets'] as $id => $old) {
		$label = sprintf('widget %s "%s"', $id, $old['title']);
		if (!isset($after['widgets'][$id])) {
			$failures[] = "{$label}: gone after the upgrade";
			continue;
		}
		$new = $after['widgets'][$id];

		foreach (array_unique(array_merge(array_keys($old['settings']), array_keys($new['settings']))) as $name) {
			$was = $old['settings'][$name] ?? null;
			$is  = $new['settings'][$name] ?? null;
			if ($was !== $is) {
				// A row with no value and no row at all mean the same thing.
				if (('' === (string) $was) && ('' === (string) $is)) {
					$notes[] = sprintf('%s: %s %s', $label, $name, null === $was ? 'row added (empty)' : 'row removed (was empty)');
					continue;
				}
				$failures[] = sprintf('%s: %s was %s, is %s', $label, $name, var_export($was, true), var_export($is, true));
			} elseif (($old['shapes'][$name] ?? '') !== ($new['shapes'][$name] ?? '')) {
				$notes[] = sprintf('%s: %s stored as %s, now %s', $label, $name, $old['shapes'][$name] ?? 'nothing', $new['shapes'][$name] ?? 'nothing');
			}
		}

		if ($old['status'] !== $new['status']) {
			$failures[] = sprintf('%s: status was %s, is %s', $label, $old['status'], $new['status']);
		}

		$was = $old['render'] ?? null;
		$is  = $new['render'] ?? null;
		if (is_array($is) && isset($is['error'])) {
			$failures[] = "{$label}: rendering failed: {$is['error']}";
		} elseif (is_array($is) && !empty($is['empty']) && 'publish' === $new['status']) {
			$failures[] = "{$label}: renders nothing";
		} elseif (is_array($was) && is_array($is) && is_array($was['settings'] ?? null) && is_array($is['settings'] ?? null)) {
			if ($was['settings'] !== $is['settings']) {
				$changed    = array_keys(array_diff_assoc($is['settings'], $was['settings']) + array_diff_assoc($was['settings'], $is['settings']));
				$failures[] = sprintf('%s: the widget now receives different settings (%s)', $label, implode(', ', $changed));
			}
		} elseif (is_array($was) && is_array($is)) {
			$notes[] = "{$label}: the old output does not carry its settings, so only the stored settings were compared";
		}
	}

	foreach ($after['widgets'] as $id => $new) {
		if (!isset($before['widgets'][$id])) {
			$notes[] = sprintf('widget %s "%s": new since the snapshot', $id, $new['title']);
		}
	}

	foreach (array_unique(array_merge(array_keys($before['options']), array_keys($after['options']))) as $name) {
		$was = $before['options'][$name] ?? null;
		$is  = $after['options'][$name] ?? null;
		if ($was !== $is) {
			if (null === $was || null === $is) {
				$notes[] = sprintf('option %s: %s', $name, null === $was ? 'added' : 'removed');
			} else {
				$failures[] = sprintf('option %s: was %s, is %s', $name, json_encode($was), json_encode($is));
			}
		}
	}

	printf("Before: %s (%d widgets)\nAfter:  %s (%d widgets)\n\n", $before['taken'], count($before['widgets']), $after['taken'], count($after['widgets']));

	foreach ($notes as $note) {
		echo "  note  {$note}\n";
	}
	foreach ($failures as $failure) {
		echo "  FAIL  {$failure}\n";
	}

	printf("\n%d widgets checked, %d failures, %d notes.\n", count($before['widgets']), count($failures), count($notes));

	return array() === $failures ? 0 : 1;
}

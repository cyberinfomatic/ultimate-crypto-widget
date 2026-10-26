<?php

namespace Cyberinfomatic\UltimateCryptoWidget\Migrations;

use Cyberinfomatic\UltimateCryptoWidget\Lib\WPToolkit\Data\Migrations\BatchedMigration;
use Cyberinfomatic\UltimateCryptoWidget\Widgets\WidgetRepository;

/**
 * Turns a widget's coins and currencies from the text "bitcoin,ethereum" into an array.
 *
 * The toolkit's multiple-choice field stores an array (one row, serialized) and reads a text row
 * as a single choice named with the whole string, so a widget saved in the comma form would show
 * nothing ticked. That form was only ever written by pre-release 1.0 builds: the released 0.x
 * plugins stored an empty string for these fields (sanitize_text_field() turns an array into
 * ""), and an empty row is left alone.
 *
 * NOT checked against a real site's database. It has only been run against test data.
 *
 * A row is changed only if it is plain text containing a comma. After it is changed it no longer
 * matches, so an interrupted batch simply repeats, and running it twice changes nothing more.
 * A row that holds no coin once trimmed (",") is removed, as an empty selection is stored as no row.
 */
final class ConvertCommaLists extends BatchedMigration
{
	/** The meta keys of the two multiple-choice fields. */
	private const KEYS = ['ucwp_widget_coins', 'ucwp_widget_currencies'];

	/** Recorded once the migration has run, so it never runs twice: do not change it. */
	public function id(): string
	{
		return '2026_10_12_150000_convert_comma_lists_to_arrays';
	}

	/** Rows changed per batch; the Migrator runs batches within a time budget. */
	public function batchSize(): int
	{
		return 100;
	}

	/** Converts the next $size rows. One row more is fetched than converted, to tell whether any remain. */
	public function batch(int $size): bool
	{
		global $wpdb;

		$keys = implode(',', array_fill(0, count(self::KEYS), '%s'));
		// A serialized array starts with "a:"; those are already in the new form.
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- the placeholders are built above.
		$sql = "SELECT m.meta_id, m.post_id, m.meta_value FROM {$wpdb->postmeta} m
			INNER JOIN {$wpdb->posts} p ON p.ID = m.post_id
			WHERE p.post_type = %s AND m.meta_key IN ($keys) AND m.meta_value LIKE %s AND m.meta_value NOT LIKE %s
			ORDER BY m.meta_id LIMIT %d";

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- a one-off data change.
		$rows = $wpdb->get_results($wpdb->prepare($sql, ...array_merge([WidgetRepository::POST_TYPE], self::KEYS, ['%,%', 'a:%'], [$size + 1])));
		if (!is_array($rows) || [] === $rows) {
			return false;
		}

		$more = count($rows) > $size;
		foreach (array_slice($rows, 0, $size) as $row) {
			$this->convert((int) $row->meta_id, (int) $row->post_id, (string) $row->meta_value);
		}

		return $more;
	}

	/**
	 * "bitcoin, ethereum,,solana" becomes [bitcoin, ethereum, solana]; nothing left removes the row.
	 */
	public static function split(string $text): array
	{
		$items = array_map('trim', explode(',', $text));

		return array_values(array_unique(array_filter($items, static fn (string $item): bool => '' !== $item)));
	}

	/** Rewrites one meta row by its id, or removes it when no coin is left, then drops the post's cached meta. */
	private function convert(int $metaId, int $postId, string $value): void
	{
		global $wpdb;

		$items = self::split($value);

		if ([] === $items) {
			delete_metadata_by_mid('post', $metaId);
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- the row is addressed by its id.
			$wpdb->update($wpdb->postmeta, ['meta_value' => maybe_serialize($items)], ['meta_id' => $metaId]);
		}

		wp_cache_delete($postId, 'post_meta');
	}
}

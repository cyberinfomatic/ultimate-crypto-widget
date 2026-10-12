<?php

namespace Cyberinfomatic\UltimateCryptoWidget\Widgets;

/**
 * Reads saved widgets (posts of type ucwp_widget and their meta rows).
 */
final class WidgetRepository
{
	public const POST_TYPE = 'ucwp_widget';

	/**
	 * The settings saved for a widget, keyed by setting name ("type", "count", …).
	 * Settings never saved are left out. Null when there is no such widget, or the
	 * current visitor may not see it.
	 *
	 * @return array<string, string>|null
	 */
	public function settings(int $id): ?array
	{
		$post = $id > 0 ? get_post($id) : null;
		if (null === $post || self::POST_TYPE !== $post->post_type) {
			return null;
		}

		// Visitors see published widgets; whoever can edit one may preview a draft.
		$hidden = in_array($post->post_status, ['trash', 'auto-draft'], true) || !current_user_can('edit_post', $post->ID);
		if ('publish' !== $post->post_status && $hidden) {
			return null;
		}

		$settings = [];
		foreach (WidgetFields::NAMES as $name) {
			if (!metadata_exists('post', $post->ID, WidgetFields::metaKey($name))) {
				continue;
			}
			$value = get_post_meta($post->ID, WidgetFields::metaKey($name), true);

			$settings[$name] = WidgetFields::isList($name)
				? WidgetFields::listToString($value)
				: (is_scalar($value) ? (string) $value : '');
		}

		return $settings;
	}
}

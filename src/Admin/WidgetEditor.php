<?php

namespace Cyberinfomatic\UltimateCryptoWidget\Admin;

use Cyberinfomatic\UltimateCryptoWidget\Lib\WPToolkit\Admin\Column;
use Cyberinfomatic\UltimateCryptoWidget\Lib\WPToolkit\Admin\Columns;
use Cyberinfomatic\UltimateCryptoWidget\Lib\WPToolkit\Admin\NoticeType;
use Cyberinfomatic\UltimateCryptoWidget\Lib\WPToolkit\Admin\Notices;
use Cyberinfomatic\UltimateCryptoWidget\Lib\WPToolkit\Foundation\HookRegistrar;
use Cyberinfomatic\UltimateCryptoWidget\Lib\WPToolkit\Data\EntityRegistrar;
use Cyberinfomatic\UltimateCryptoWidget\Lib\WPToolkit\Data\Field\FieldTypes;
use Cyberinfomatic\UltimateCryptoWidget\Lib\WPToolkit\Data\MetaBox;
use Cyberinfomatic\UltimateCryptoWidget\Widgets\Fields\WidgetTypeFieldType;
use Cyberinfomatic\UltimateCryptoWidget\Widgets\WidgetCatalog;
use Cyberinfomatic\UltimateCryptoWidget\Widgets\WidgetFields;
use Cyberinfomatic\UltimateCryptoWidget\Widgets\WidgetRepository;
use Cyberinfomatic\UltimateCryptoWidget\Widgets\WidgetSchema;
use WP_Post;

/**
 * The "widget" post type and its edit screen: the settings form (a toolkit meta
 * box drawn from WidgetSchema), a live preview, the shortcode to copy, and the
 * columns of the list.
 *
 * The post type's name and the meta keys are in users' databases; they stay
 * "ucwp_widget" and "ucwp_widget_{setting}".
 */
final class WidgetEditor
{
	private ?MetaBox $box = null;

	public function __construct(
		private readonly EntityRegistrar $registrar,
		private readonly FieldTypes $types,
		private readonly WidgetSchema $schema,
		private readonly WidgetCatalog $catalog,
		private readonly Preview $preview,
		private readonly Notices $notices,
		private readonly HookRegistrar $hooks
	) {
	}

	/**
	 * Call once, on "init".
	 */
	public function register(): void
	{
		$this->registerPostType();

		$this->types->register(WidgetTypeFieldType::NAME, new WidgetTypeFieldType());

		// The settings form lists the top coins, which costs a price request when the cache is
		// cold. It is built where a widget is edited or listed, never on a visitor's page view.
		$this->hooks->addAction('admin_init', function (): void {
			global $pagenow;
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- only decides whether to build the form.
			if (self::needsForm((string) $pagenow, $_REQUEST)) {
				$this->box();
			}
		});

		$this->hooks->addAction('add_meta_boxes_' . WidgetRepository::POST_TYPE, fn () => $this->addBoxes());

		$this->hooks->addFilter('manage_' . WidgetRepository::POST_TYPE . '_posts_columns', fn (array $columns): array => $this->withShortcodeColumn($columns), 20);
		$this->hooks->addAction('manage_' . WidgetRepository::POST_TYPE . '_posts_custom_column', function (string $column, int $postId): void {
			if ('ucw_shortcode' === $column) {
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- shortcodeTag() escapes.
				echo self::shortcodeTag($postId);
			}
		}, 10, 2);
	}

	/**
	 * The settings form, for code that needs a widget's fields or values.
	 */
	public function box(): MetaBox
	{
		return $this->box ??= $this->buildBox();
	}

	/**
	 * Whether an admin request is about the widget post type: its edit and list screens, and
	 * quick edit in the list.
	 *
	 * @param array<string, mixed> $request The request's parameters.
	 */
	public static function needsForm(string $page, array $request): bool
	{
		$quickEdit = 'admin-ajax.php' === $page && 'inline-save' === ($request['action'] ?? '');
		if (!$quickEdit && !in_array($page, ['post.php', 'post-new.php', 'edit.php'], true)) {
			return false;
		}

		$type = is_string($request['post_type'] ?? null) ? $request['post_type'] : '';
		if ('' === $type) {
			// An existing post is named by its id; its type is looked up.
			$id   = $request['post'] ?? $request['post_ID'] ?? 0;
			$type = is_scalar($id) && absint($id) > 0 ? (string) get_post_type(absint($id)) : '';
		}

		return WidgetRepository::POST_TYPE === $type;
	}

	/**
	 * Refuses a choice list longer than its field allows, keeping what was saved before.
	 *
	 * The toolkit validates each chosen value, not how many were chosen. Hooked before the
	 * form's own save (priority 10): a refused field is taken out of the request, so the form
	 * leaves it as it was, and the person is told why. The other fields are saved.
	 */
	public function enforceLimits(int $postId, WP_Post $post): void
	{
		$box = $this->box();
		if (!$box->maySave($postId, $post)) {
			return;
		}

		foreach ($box->fields() as $name => $field) {
			$max = (int) $field->setting(WidgetSchema::MAX_CHOICES, 0);
			$key = $box->metaKey($name);
			// phpcs:ignore WordPress.Security.NonceVerification.Missing -- the form's nonce was checked by maySave().
			if ($max < 1 || !$field->isMultiple() || !isset($_POST[$key])) {
				continue;
			}

			// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- counted only.
			$chosen = array_filter((array) wp_unslash($_POST[$key]), static fn ($value): bool => is_scalar($value) && '' !== (string) $value);
			if (count($chosen) <= $max) {
				continue;
			}

			unset($_POST[$key]);
			$this->notices->flash(
				sprintf(
					/* translators: 1: setting name, 2: most that may be chosen, 3: number chosen. */
					__('%1$s: choose up to %2$d (you chose %3$d). It was left as it was; your other changes were saved.', 'ultimate-crypto-widget'),
					$field->labelText(),
					$max,
					count($chosen)
				),
				NoticeType::Error
			);
		}
	}

	/**
	 * The settings form and the list columns that read from it.
	 */
	private function buildBox(): MetaBox
	{
		$box = $this->registrar
			->metaBox('settings', __('Widget settings', 'ultimate-crypto-widget'), WidgetRepository::POST_TYPE, $this->schema->fields())
			->prefix(WidgetFields::META_PREFIX)
			->placement('normal', 'high');

		$this->hooks->addAction('save_post_' . WidgetRepository::POST_TYPE, [$this, 'enforceLimits'], 5, 2);

		(new Columns($box, [
			Column::field('type')
				->label(__('Type', 'ultimate-crypto-widget'))
				->position(Column::AFTER_TITLE)
				->format(fn (mixed $type): string => esc_html($this->catalog->get((string) $type)?->label ?? '—')),
		]))->register($this->hooks);

		return $box;
	}

	/**
	 * `[ucwp_widget id="12"]` in a <code> element a script can copy from.
	 */
	public static function shortcodeTag(int $postId): string
	{
		return sprintf('<code class="ucw-shortcode" data-ucw-copy>[ucwp_widget id=&quot;%d&quot;]</code>', $postId);
	}

	private function registerPostType(): void
	{
		register_post_type(
			WidgetRepository::POST_TYPE,
			[
				'labels'              => [
					'name'               => __('Crypto widgets', 'ultimate-crypto-widget'),
					'singular_name'      => __('Crypto widget', 'ultimate-crypto-widget'),
					'add_new'            => __('Add widget', 'ultimate-crypto-widget'),
					'add_new_item'       => __('Add a widget', 'ultimate-crypto-widget'),
					'edit_item'          => __('Edit widget', 'ultimate-crypto-widget'),
					'new_item'           => __('New widget', 'ultimate-crypto-widget'),
					'search_items'       => __('Search widgets', 'ultimate-crypto-widget'),
					'not_found'          => __('No widgets yet.', 'ultimate-crypto-widget'),
					'not_found_in_trash' => __('No widgets in the bin.', 'ultimate-crypto-widget'),
					'all_items'          => __('Widgets', 'ultimate-crypto-widget'),
				],
				'description'         => __('Cryptocurrency widgets you can place with a shortcode or a block.', 'ultimate-crypto-widget'),
				'supports'            => ['title'],
				'hierarchical'        => false,
				// Not a page of its own: a widget is only ever shown inside other content.
				'public'              => false,
				'publicly_queryable'  => false,
				'exclude_from_search' => true,
				'show_ui'             => true,
				'show_in_menu'        => false, // Listed under the plugin's own menu instead.
				'show_in_admin_bar'   => false,
				'show_in_nav_menus'   => false,
				'show_in_rest'        => false,
				'can_export'          => true,
				'has_archive'         => false,
				'rewrite'             => false,
				'capability_type'     => 'page',
			]
		);
	}

	private function addBoxes(): void
	{
		add_meta_box(
			'ucw-preview',
			__('Preview', 'ultimate-crypto-widget'),
			function (WP_Post $post): void {
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- frame() escapes.
				echo $this->preview->frame($post->ID);
			},
			WidgetRepository::POST_TYPE,
			'normal',
			'default'
		);

		add_meta_box(
			'ucw-shortcode',
			__('Shortcode', 'ultimate-crypto-widget'),
			static function (WP_Post $post): void {
				if ('auto-draft' === $post->post_status) {
					echo '<p>' . esc_html__('Save the widget to get its shortcode.', 'ultimate-crypto-widget') . '</p>';
					return;
				}
				echo '<p>' . esc_html__('Paste this where the widget should appear:', 'ultimate-crypto-widget') . '</p>';
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- shortcodeTag() escapes.
				echo '<p>' . self::shortcodeTag($post->ID) . '</p>';
				printf('<button type="button" class="button" data-ucw-copy-from=".ucw-shortcode">%s</button>', esc_html__('Copy', 'ultimate-crypto-widget'));
			},
			WidgetRepository::POST_TYPE,
			'side',
			'high'
		);
	}

	/**
	 * @param array<string, string> $columns
	 * @return array<string, string> With "Shortcode" before the date.
	 */
	private function withShortcodeColumn(array $columns): array
	{
		$out = [];
		foreach ($columns as $id => $label) {
			if ('date' === $id) {
				$out['ucw_shortcode'] = __('Shortcode', 'ultimate-crypto-widget');
			}
			$out[$id] = $label;
		}

		return $out + ['ucw_shortcode' => __('Shortcode', 'ultimate-crypto-widget')];
	}
}

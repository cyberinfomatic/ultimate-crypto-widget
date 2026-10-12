<?php

namespace Cyberinfomatic\UltimateCryptoWidget\Admin;

use Cyberinfomatic\UltimateCryptoWidget\Lib\WPToolkit\Admin\Column;
use Cyberinfomatic\UltimateCryptoWidget\Lib\WPToolkit\Admin\Columns;
use Cyberinfomatic\UltimateCryptoWidget\Lib\WPToolkit\Foundation\HookRegistrar;
use Cyberinfomatic\UltimateCryptoWidget\Lib\WPToolkit\Data\EntityRegistrar;
use Cyberinfomatic\UltimateCryptoWidget\Lib\WPToolkit\Data\Field\FieldTypes;
use Cyberinfomatic\UltimateCryptoWidget\Lib\WPToolkit\Data\MetaBox;
use Cyberinfomatic\UltimateCryptoWidget\Widgets\Fields\ListFieldType;
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
		private readonly HookRegistrar $hooks
	) {
	}

	/**
	 * Call once, on "init".
	 */
	public function register(): void
	{
		$this->registerPostType();

		$this->types->register(ListFieldType::NAME, new ListFieldType());
		$this->types->register(WidgetTypeFieldType::NAME, new WidgetTypeFieldType());

		$this->box = $this->registrar
			->metaBox('settings', __('Widget settings', 'ultimate-crypto-widget'), WidgetRepository::POST_TYPE, $this->schema->fields())
			->prefix(WidgetFields::META_PREFIX)
			->placement('normal', 'high');

		$this->hooks->addAction('add_meta_boxes_' . WidgetRepository::POST_TYPE, fn () => $this->addBoxes());

		(new Columns($this->box, [
			Column::field('type')
				->label(__('Type', 'ultimate-crypto-widget'))
				->position(Column::AFTER_TITLE)
				->format(fn (mixed $type): string => esc_html($this->catalog->get((string) $type)?->label ?? '—')),
		]))->register($this->hooks);

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
		return $this->box ?? throw new \LogicException('The widget editor is registered on "init".');
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

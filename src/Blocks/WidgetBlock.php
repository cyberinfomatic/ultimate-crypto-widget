<?php

namespace Cyberinfomatic\UltimateCryptoWidget\Blocks;

use Cyberinfomatic\UltimateCryptoWidget\Admin\Preview;
use Cyberinfomatic\UltimateCryptoWidget\Lib\WPToolkit\Assets\AssetManager;
use Cyberinfomatic\UltimateCryptoWidget\Lib\WPToolkit\Foundation\HookRegistrar;
use Cyberinfomatic\UltimateCryptoWidget\Lib\WPToolkit\Frontend\Shortcodes;
use Cyberinfomatic\UltimateCryptoWidget\Providers\AssetsProvider;
use Cyberinfomatic\UltimateCryptoWidget\Widgets\WidgetFields;
use Cyberinfomatic\UltimateCryptoWidget\Widgets\WidgetRepository;
use Cyberinfomatic\UltimateCryptoWidget\Widgets\WidgetSchema;

/**
 * The "Crypto Widget" block.
 *
 * It shows either a saved widget (by id) or one set up in the block itself. On
 * the site it is drawn by the same code as the shortcode, so a block and a
 * shortcode with the same settings are the same widget. Only the choice is
 * stored in the post, never markup, so a redesign needs no block migration.
 */
final class WidgetBlock
{
	public const NAME = 'ucwp/widget';

	private bool $declared = false;

	public function __construct(
		private readonly Shortcodes $shortcodes,
		private readonly WidgetSchema $schema,
		private readonly Preview $preview,
		private readonly AssetManager $assets,
		private readonly HookRegistrar $hooks
	) {
	}

	/**
	 * Call once, on "init".
	 */
	public function register(): void
	{
		register_block_type(
			self::NAME,
			[
				'api_version'     => 3,
				'title'           => __('Crypto Widget', 'ultimate-crypto-widget'),
				'description'     => __('Live cryptocurrency prices: a table, a ticker, price cards and more.', 'ultimate-crypto-widget'),
				'category'        => 'widgets',
				'icon'            => 'chart-line',
				'keywords'        => ['crypto', 'bitcoin', 'price', 'ticker', 'coin'],
				'textdomain'      => 'ultimate-crypto-widget',
				'attributes'      => [
					'widgetId' => ['type' => 'integer', 'default' => 0],
					'settings' => ['type' => 'object', 'default' => []],
				],
				'supports'        => ['html' => false, 'align' => ['wide', 'full'], 'reusable' => true],
				'render_callback' => fn (array $attributes): string => $this->render($attributes),
			]
		);

		// Declared only when the block editor opens: its data includes the coin list.
		$this->hooks->addAction('enqueue_block_editor_assets', function (): void {
			if ($this->declared) {
				return;
			}
			$this->declared = true;
			$this->assets->script('block', AssetsProvider::BUILD . 'block/index.js')->onDemand()->with(['block' => $this->editorData()]);
			$this->assets->style('block-style', AssetsProvider::BUILD . 'block.css')->onDemand();
			$this->assets->load('block', 'block-style');
		});
	}

	/**
	 * @param array<string, mixed> $attributes
	 */
	public function render(array $attributes): string
	{
		$chosen = $this->shortcodeAttributes($attributes);
		if ([] === $chosen) {
			return ''; // Nothing picked yet.
		}

		$widget = $this->shortcodes->render('widget', $chosen);
		if ('' === trim($widget)) {
			return '';
		}
		// When there is no widget to draw, the shortcode explains why. In a block that
		// note is for whoever edits the page, not for its visitors.
		if (!str_contains($widget, 'data-ucw-view') && !current_user_can('edit_posts')) {
			return '';
		}

		return sprintf('<div %s>%s</div>', get_block_wrapper_attributes(), $widget);
	}

	/**
	 * What the shortcode is given for a block: a saved widget's id, or the block's
	 * own settings with anything that is not a widget setting dropped.
	 *
	 * @param array<string, mixed> $attributes
	 * @return array<string, string>
	 */
	public function shortcodeAttributes(array $attributes): array
	{
		$id = absint($attributes['widgetId'] ?? 0);
		if ($id > 0) {
			return ['id' => (string) $id];
		}

		$given = is_array($attributes['settings'] ?? null) ? $attributes['settings'] : [];
		$out   = [];
		foreach (WidgetFields::NAMES as $name) {
			if (!isset($given[$name])) {
				continue;
			}
			$value = WidgetFields::isList($name) ? WidgetFields::listToString($given[$name]) : (is_scalar($given[$name]) ? sanitize_text_field((string) $given[$name]) : '');
			if ('' !== $value) {
				$out[$name] = $value;
			}
		}

		return $out;
	}

	/**
	 * What the editor script needs: the settings to offer, the saved widgets to
	 * pick from, and where previews come from.
	 *
	 * @return array<string, mixed>
	 */
	public function editorData(): array
	{
		$saved = [];
		foreach (get_posts(['post_type' => WidgetRepository::POST_TYPE, 'post_status' => 'publish', 'numberposts' => 100, 'orderby' => 'title', 'order' => 'ASC']) as $post) {
			$saved[] = ['id' => $post->ID, 'title' => '' !== $post->post_title ? $post->post_title : sprintf('#%d', $post->ID)];
		}

		return [
			'name'       => self::NAME,
			'fields'     => $this->schema->toArray(),
			'saved'      => $saved,
			'previewUrl' => $this->preview->url(),
			'newUrl'     => admin_url('post-new.php?post_type=' . WidgetRepository::POST_TYPE),
		];
	}
}

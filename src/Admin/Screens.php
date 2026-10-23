<?php

namespace Cyberinfomatic\UltimateCryptoWidget\Admin;

use Cyberinfomatic\UltimateCryptoWidget\Data\PriceProvider;
use Cyberinfomatic\UltimateCryptoWidget\Lib\WPToolkit\Admin\Page;
use Cyberinfomatic\UltimateCryptoWidget\Lib\WPToolkit\Admin\Pages;
use Cyberinfomatic\UltimateCryptoWidget\Lib\WPToolkit\Admin\Settings\SettingsForm;
use Cyberinfomatic\UltimateCryptoWidget\Lib\WPToolkit\Data\Field\FieldTypes;
use Cyberinfomatic\UltimateCryptoWidget\Plugin;
use Cyberinfomatic\UltimateCryptoWidget\Widgets\Edition;
use Cyberinfomatic\UltimateCryptoWidget\Widgets\WidgetFields;
use Cyberinfomatic\UltimateCryptoWidget\Widgets\WidgetRepository;
use Cyberinfomatic\UltimateCryptoWidget\Widgets\WidgetSchema;

/**
 * The plugin's menu and its screens: overview, widgets, quick widget, settings.
 *
 * The page slugs are in bookmarks and in other plugins' links; they do not change.
 */
final class Screens
{
	public const MENU     = 'ultimate-crypto-widget';
	public const QUICK    = 'ultimate-crypto-widget-quick-widget';
	public const SETTINGS = 'ultimate-crypto-widget-settings';

	private const CAPABILITY = 'manage_options';

	public function __construct(
		private readonly Pages $pages,
		private readonly SettingsForm $form,
		private readonly WidgetSchema $schema,
		private readonly Preview $preview,
		private readonly FieldTypes $types,
		private readonly PriceProvider $prices
	) {
	}

	/**
	 * Call once, on "init".
	 */
	public function register(): void
	{
		$this->pages->add(
			Page::top(self::MENU, __('Ultimate Crypto Widget', 'ultimate-crypto-widget'), self::CAPABILITY)
				->menuTitle(__('Crypto Widgets', 'ultimate-crypto-widget'))
				->icon(self::icon())
				->position(26)
				->view('admin/overview', fn (): array => $this->overview())
		);
		$this->pages->add(Page::postTypeList(self::MENU, WidgetRepository::POST_TYPE, __('Widgets', 'ultimate-crypto-widget'), self::CAPABILITY));
		$this->pages->add(
			Page::under(self::MENU, self::QUICK, __('Quick widget', 'ultimate-crypto-widget'), self::CAPABILITY)
				->view('admin/quick-widget', fn (): array => $this->quickWidget())
		);
		$this->pages->add(
			Page::under(self::MENU, self::SETTINGS, __('Settings', 'ultimate-crypto-widget'), self::CAPABILITY)
				->view('admin/settings', fn (): array => [
					'form'      => fn () => $this->form->render(self::SETTINGS),
					'callCount' => $this->prices->callCount(),
					'clearUrl'  => admin_url('admin-post.php?action=' . CacheTools::ACTION),
					'clearNonce' => wp_create_nonce(CacheTools::ACTION),
				])
		);
	}

	/**
	 * Whether an admin screen belongs to this plugin, so its styles load only there.
	 */
	public static function owns(?\WP_Screen $screen): bool
	{
		if (null === $screen) {
			return false;
		}

		return WidgetRepository::POST_TYPE === $screen->post_type || str_contains((string) $screen->id, self::MENU);
	}

	/**
	 * Where "upgrade to Pro" links lead.
	 */
	public static function upgradeUrl(): string
	{
		$url = 'https://ultimatecryptowidget.com/get-pro?utm_source=plugin&utm_medium=upgrade&utm_campaign=pro';

		/**
		 * Where "upgrade to Pro" links lead.
		 *
		 * @param string $url
		 */
		return (string) apply_filters('ucwp_upgrade_url', $url);
	}

	/**
	 * Whether to suggest the Pro add-on: only in the tiered edition, where there is a Pro to
	 * get (Edition). The add-on turns this off once licensed.
	 */
	public static function suggestsUpgrade(): bool
	{
		/**
		 * Whether to show the "upgrade to Pro" suggestions.
		 *
		 * @param bool $show
		 */
		return (bool) apply_filters('ucwp_show_upgrade_notice', Edition::sellsPro());
	}

	/**
	 * @return array<string, mixed>
	 */
	private function overview(): array
	{
		$counts = wp_count_posts(WidgetRepository::POST_TYPE);

		return [
			'version'     => Plugin::version(),
			'widgetCount' => (int) ($counts->publish ?? 0),
			'newUrl'      => admin_url('post-new.php?post_type=' . WidgetRepository::POST_TYPE),
			'listUrl'     => admin_url('edit.php?post_type=' . WidgetRepository::POST_TYPE),
			'quickUrl'    => $this->pages->url(self::QUICK),
			'settingsUrl' => $this->pages->url(self::SETTINGS),
			'upgradeUrl'  => self::suggestsUpgrade() ? self::upgradeUrl() : '',
			'supportUrl'  => 'https://wordpress.org/support/plugin/ultimate-crypto-widget/',
			'reviewUrl'   => 'https://wordpress.org/support/plugin/ultimate-crypto-widget/reviews/#new-post',
		];
	}

	/**
	 * The quick widget screen: the same fields as the editor, tied to no saved
	 * widget, with a preview and the shortcode that would produce it.
	 *
	 * @return array<string, mixed>
	 */
	private function quickWidget(): array
	{
		$fields = [];
		foreach ($this->schema->fields() as $field) {
			$id       = 'ucw-quick-' . $field->name;
			$fields[] = [
				'id'          => $id,
				'label'       => $field->labelText(),
				'description' => (string) $field->setting('description', ''),
				'input'       => $this->types->get($field->type)->render($field, WidgetFields::metaKey($field->name), $id, $field->defaultValue()),
			];
		}

		return [
			'fields'  => $fields,
			'preview' => $this->preview->frame(),
		];
	}

	/**
	 * The menu icon: the plugin's logo as a data URI, or a dashicon if the file is missing.
	 */
	private static function icon(): string
	{
		$file = UCWP_PLUGIN_DIR . 'assets/images/ucwp-logo.svg';
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- a local file.
		$svg = is_readable($file) ? file_get_contents($file) : false;

		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- the format WordPress asks for.
		return is_string($svg) && '' !== $svg ? 'data:image/svg+xml;base64,' . base64_encode($svg) : 'dashicons-chart-line';
	}
}

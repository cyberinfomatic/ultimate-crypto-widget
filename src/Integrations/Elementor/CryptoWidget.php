<?php

namespace Cyberinfomatic\UltimateCryptoWidget\Integrations\Elementor;

use Cyberinfomatic\UltimateCryptoWidget\Admin\Preview;
use Cyberinfomatic\UltimateCryptoWidget\Lib\WPToolkit\Frontend\Shortcodes;
use Cyberinfomatic\UltimateCryptoWidget\Widgets\WidgetFields;
use Cyberinfomatic\UltimateCryptoWidget\Widgets\WidgetSchema;
use Elementor\Controls_Manager;
use Elementor\Widget_Base;

/**
 * The plugin's elements for Elementor: one per widget type the site can use, so
 * "Coin Marquee" or "Historical Price Chart" is dragged in ready-made and only
 * its own settings are left to fill in.
 *
 * Every element is this one class. Which widget type an instance stands for
 * comes from its Elementor name ("ucwp-coin-marquee-1"), so a type added to the
 * catalogue, by this plugin or an add-on, gets its element with no code here.
 * Controls are generated from WidgetSchema, the same description the widget
 * editor and the block use, and on the site an element is drawn by the same code
 * as the shortcode.
 *
 * This file is loaded only when Elementor asks for widgets, so the plugin never
 * refers to Elementor's classes on a site without it.
 */
final class CryptoWidget extends Widget_Base
{
	/** Elementor control ids are shared by a whole element; ours are prefixed. */
	public const PREFIX = 'ucw_';

	/** The group the elements are listed under in Elementor's panel. */
	public const CATEGORY = 'ucwp';

	/** An element's Elementor name is this plus its widget type. Saved in pages: never change it. */
	public const NAME_PREFIX = 'ucwp-';

	/** The element with a type picker. Kept for pages that use it; not offered in the panel. */
	public const GENERIC = 'ucwp-widget';

	/**
	 * Run when the editor's preview frame has loaded: the preview page reports its height as
	 * the widget draws and resizes, and the frame follows. An attribute, because Elementor puts
	 * an element's HTML into its canvas without running script tags.
	 */
	private const FIT_FRAME = "var f=this;window.addEventListener('message',function(e){var h=e.data&&Number(e.data.ucwPreviewHeight);if(e.source===f.contentWindow&&h>0){f.style.height=Math.min(h,1600)+'px'}});f.style.height=Math.min(f.contentDocument.body.offsetHeight,1600)+'px'";

	/** Elementor's icon for a view, by a word in the view's name. First match wins. */
	private const ICONS = [
		'table'     => 'eicon-table',
		'marquee'   => 'eicon-slides',
		'slider'    => 'eicon-slides',
		'tab'       => 'eicon-tabs',
		'accordion' => 'eicon-accordion',
		'info'      => 'eicon-info-box',
		'chart'     => 'eicon-skill-bar',
		'picker'    => 'eicon-price-list',
		'block'     => 'eicon-price-list',
	];

	private static ?Shortcodes $shortcodes = null;
	private static ?WidgetSchema $schema   = null;
	private static ?Preview $preview       = null;

	/** @var array<string, array{label: string, view: string}> The widget types that have an element. */
	private static array $types = [];

	/** The widget type this instance stands for; '' for the generic element. */
	private string $type = '';

	/**
	 * Elementor builds elements itself, so they cannot be given their services
	 * through the constructor. Called once, before the elements are registered.
	 *
	 * @param array<string, array{label: string, view: string}> $types Widget type => its label and view.
	 */
	public static function use(Shortcodes $shortcodes, WidgetSchema $schema, Preview $preview, array $types = []): void
	{
		self::$shortcodes = $shortcodes;
		self::$schema     = $schema;
		self::$preview    = $preview;
		self::$types      = $types;
	}

	/**
	 * The element to register for one widget type.
	 */
	public static function forType(string $type): self
	{
		return new self([], ['ucw_type' => $type]);
	}

	/**
	 * Elementor creates an instance in two ways: once per element type to learn
	 * about it (no data), and once per element on a page, with that element's
	 * saved data, which carries its name. Both must end up knowing the type.
	 *
	 * @param array<string, mixed>      $data
	 * @param array<string, mixed>|null $args
	 */
	public function __construct($data = [], $args = null)
	{
		$name = is_array($data) && isset($data['widgetType']) && is_string($data['widgetType']) ? $data['widgetType'] : '';

		if ('' !== $name && self::GENERIC !== $name && str_starts_with($name, self::NAME_PREFIX)) {
			$this->type = substr($name, strlen(self::NAME_PREFIX));
		} elseif ('' === $name && is_array($args) && isset($args['ucw_type']) && is_string($args['ucw_type'])) {
			$this->type = $args['ucw_type'];
		}

		parent::__construct($data, $args);
	}

	/** NAME_PREFIX plus the widget type, or GENERIC for the element with a type picker. Saved in pages. */
	public function get_name(): string
	{
		return '' === $this->type ? self::GENERIC : self::NAME_PREFIX . $this->type;
	}

	/** The widget type's label from the catalogue, as Elementor's panel shows it. */
	public function get_title(): string
	{
		return self::$types[$this->type]['label'] ?? __('Crypto Widget', 'ultimate-crypto-widget');
	}

	/** One of Elementor's icons, picked by a word in the type's view name (ICONS). */
	public function get_icon(): string
	{
		$view = self::$types[$this->type]['view'] ?? '';
		foreach (self::ICONS as $word => $icon) {
			if (str_contains($view, $word)) {
				return $icon;
			}
		}

		return 'eicon-price-table';
	}

	/**
	 * @return string[]
	 */
	public function get_categories(): array
	{
		return [self::CATEGORY];
	}

	/**
	 * @return string[]
	 */
	public function get_keywords(): array
	{
		return ['crypto', 'bitcoin', 'price', 'ticker', 'coin'];
	}

	/**
	 * The generic element is not offered: every type has an element of its own.
	 */
	public function show_in_panel(): bool
	{
		return '' !== $this->type;
	}

	/**
	 * The widget's scripts load when it is drawn, so its HTML must not be served
	 * from Elementor's element cache.
	 */
	protected function is_dynamic_content(): bool
	{
		return true;
	}

	/** The settings panel, generated from WidgetSchema. Empty until use() has been called. */
	protected function register_controls(): void
	{
		if (null === self::$schema) {
			return;
		}

		$this->start_controls_section(self::PREFIX . 'section', ['label' => __('Widget', 'ultimate-crypto-widget'), 'tab' => Controls_Manager::TAB_CONTENT]);

		foreach (self::controls(self::$schema->toArray(), $this->type) as $id => $control) {
			$this->add_control($id, $control);
		}

		$this->end_controls_section();
	}

	/** On the site, the widget as the shortcode draws it; in Elementor's editor, a preview frame. */
	protected function render(): void
	{
		if (null === self::$shortcodes || null === self::$schema || null === self::$preview) {
			return;
		}
		$attributes = self::shortcodeAttributes((array) $this->get_settings_for_display(), self::$schema->toArray(), $this->type);
		if (!isset($attributes['type'])) {
			if (self::editing()) {
				echo '<p>' . esc_html__('Choose a widget type in the panel.', 'ultimate-crypto-widget') . '</p>';
			}
			return;
		}

		if (self::editing()) {
			// The editor redraws an element without loading its scripts, so the
			// widget is shown in a frame there, as in the block editor.
			printf(
				'<iframe src="%s" title="%s" style="display:block;width:100%%;height:240px;border:0;pointer-events:none" onload="%s"></iframe>',
				esc_url(self::$preview->url(0, $attributes)),
				esc_attr__('Widget preview', 'ultimate-crypto-widget'),
				esc_attr(self::FIT_FRAME)
			);
			return;
		}

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- the shortcode's output is already escaped.
		echo self::$shortcodes->render('widget', $attributes);
	}

	/**
	 * Elementor controls for the schema's fields, keyed by control id.
	 *
	 * For one widget type: that type's settings and nothing else. For the generic
	 * element ($type ''): a type picker, and every setting shown only for the types
	 * that have it.
	 *
	 * @param array<int, array<string, mixed>> $fields WidgetSchema::toArray().
	 * @return array<string, array<string, mixed>>
	 */
	public static function controls(array $fields, string $type = ''): array
	{
		$controls = [];
		foreach ($fields as $field) {
			$isType = 'type' === $field['name'];
			if ('' !== $type && ($isType || !in_array($type, $field['usedBy'], true))) {
				continue;
			}

			$options = [];
			foreach ($field['options'] as $option) {
				// Elementor cannot grey out one option; a type this site cannot use is left out.
				if (!$option['locked']) {
					$options[$option['value']] = $option['label'];
				}
			}

			$control = ['label' => $field['label'], 'default' => $field['default']];
			if ('' !== $field['help']) {
				$control['description'] = $field['help'];
			}

			switch ($field['control']) {
				case 'type':
					$control += ['type' => Controls_Manager::SELECT, 'options' => ['' => __('Choose a widget type…', 'ultimate-crypto-widget')] + $options];
					break;
				case 'select':
					$control += ['type' => Controls_Manager::SELECT, 'options' => $options];
					break;
				case 'list':
					$control = ['type' => Controls_Manager::SELECT2, 'multiple' => true, 'label_block' => true, 'options' => $options, 'default' => WidgetFields::listToArray($field['default'])] + $control;
					break;
				case 'toggle':
					// Elementor stores an empty value when a switch is off; shortcodeAttributes() reads that as "0".
					$control = ['type' => Controls_Manager::SWITCHER, 'return_value' => '1', 'default' => '1' === $field['default'] ? '1' : ''] + $control;
					break;
				case 'number':
					$control += array_filter(['type' => Controls_Manager::NUMBER, 'min' => $field['min'], 'max' => $field['max'], 'step' => $field['step']], static fn ($value): bool => null !== $value);
					break;
				default:
					$control += ['type' => Controls_Manager::TEXT];
			}

			if ('' === $type && !$isType) {
				$control['condition'] = [self::PREFIX . 'type' => $field['usedBy']];
			}

			$controls[self::PREFIX . $field['name']] = $control;
		}

		return $controls;
	}

	/**
	 * What the shortcode is given for an element's settings: its type and the
	 * settings that type has, in their stored shapes.
	 *
	 * @param array<string, mixed>             $settings Elementor's settings for the element.
	 * @param array<int, array<string, mixed>> $fields   WidgetSchema::toArray().
	 * @param string                           $type     The element's own type; '' to read it from the settings.
	 * @return array<string, string>
	 */
	public static function shortcodeAttributes(array $settings, array $fields, string $type = ''): array
	{
		if ('' === $type) {
			$chosen = $settings[self::PREFIX . 'type'] ?? '';
			$type   = is_string($chosen) ? sanitize_text_field($chosen) : '';
		}
		if ('' === $type) {
			return [];
		}

		$attributes = ['type' => $type];
		foreach ($fields as $field) {
			$name = $field['name'];
			if ('type' === $name || !in_array($type, $field['usedBy'], true) || !isset($settings[self::PREFIX . $name])) {
				continue;
			}
			$given = $settings[self::PREFIX . $name];
			if ('toggle' === $field['control']) {
				$attributes[$name] = '1' === (string) $given ? '1' : '0';
				continue;
			}
			$value = WidgetFields::isList($name) ? WidgetFields::listToString($given) : (is_scalar($given) ? sanitize_text_field((string) $given) : '');
			if ('' !== $value) {
				$attributes[$name] = $value;
			}
		}

		return $attributes;
	}

	/**
	 * Whether the element is being drawn inside Elementor's editor.
	 */
	private static function editing(): bool
	{
		$elementor = class_exists(\Elementor\Plugin::class) ? \Elementor\Plugin::$instance : null;

		return null !== $elementor && isset($elementor->editor) && $elementor->editor->is_edit_mode();
	}
}

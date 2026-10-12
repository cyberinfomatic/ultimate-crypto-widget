<?php

namespace Cyberinfomatic\UltimateCryptoWidget\Integrations\Elementor;

use Cyberinfomatic\UltimateCryptoWidget\Admin\Preview;
use Cyberinfomatic\UltimateCryptoWidget\Lib\WPToolkit\Frontend\Shortcodes;
use Cyberinfomatic\UltimateCryptoWidget\Widgets\WidgetFields;
use Cyberinfomatic\UltimateCryptoWidget\Widgets\WidgetSchema;
use Elementor\Controls_Manager;
use Elementor\Widget_Base;

/**
 * The "Crypto Widget" element for Elementor.
 *
 * Its controls are generated from WidgetSchema, the same description the widget
 * editor and the block use, and on the site it is drawn by the same code as the
 * shortcode. This file is loaded only when Elementor asks for widgets, so the
 * plugin never refers to Elementor's classes on a site without it.
 */
final class CryptoWidget extends Widget_Base
{
	/** Elementor control ids are shared by a whole element; ours are prefixed. */
	public const PREFIX = 'ucw_';

	private static ?Shortcodes $shortcodes = null;
	private static ?WidgetSchema $schema   = null;
	private static ?Preview $preview       = null;

	/**
	 * Elementor builds elements itself, so they cannot be given their services
	 * through the constructor. Called once, before the element is registered.
	 */
	public static function use(Shortcodes $shortcodes, WidgetSchema $schema, Preview $preview): void
	{
		self::$shortcodes = $shortcodes;
		self::$schema     = $schema;
		self::$preview    = $preview;
	}

	public function get_name(): string
	{
		return 'ucwp-widget';
	}

	public function get_title(): string
	{
		return __('Crypto Widget', 'ultimate-crypto-widget');
	}

	public function get_icon(): string
	{
		return 'eicon-price-table';
	}

	/**
	 * @return string[]
	 */
	public function get_categories(): array
	{
		return ['general'];
	}

	/**
	 * @return string[]
	 */
	public function get_keywords(): array
	{
		return ['crypto', 'bitcoin', 'price', 'ticker', 'coin'];
	}

	/**
	 * The widget's scripts load when it is drawn, so its HTML must not be served
	 * from Elementor's element cache.
	 */
	protected function is_dynamic_content(): bool
	{
		return true;
	}

	protected function register_controls(): void
	{
		if (null === self::$schema) {
			return;
		}

		$this->start_controls_section(self::PREFIX . 'section', ['label' => __('Widget', 'ultimate-crypto-widget'), 'tab' => Controls_Manager::TAB_CONTENT]);

		foreach (self::controls(self::$schema->toArray()) as $id => $control) {
			$this->add_control($id, $control);
		}

		$this->end_controls_section();
	}

	protected function render(): void
	{
		if (null === self::$shortcodes || null === self::$schema || null === self::$preview) {
			return;
		}
		$attributes = self::shortcodeAttributes((array) $this->get_settings_for_display(), self::$schema->toArray());
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
				'<iframe src="%s" title="%s" style="display:block;width:100%%;height:420px;border:0;pointer-events:none"></iframe>',
				esc_url(self::$preview->url(0, $attributes)),
				esc_attr__('Widget preview', 'ultimate-crypto-widget')
			);
			return;
		}

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- the shortcode's output is already escaped.
		echo self::$shortcodes->render('widget', $attributes);
	}

	/**
	 * Elementor controls for the schema's fields, keyed by control id. A setting
	 * is shown only for the widget types that have it.
	 *
	 * @param array<int, array<string, mixed>> $fields WidgetSchema::toArray().
	 * @return array<string, array<string, mixed>>
	 */
	public static function controls(array $fields): array
	{
		$controls = [];
		foreach ($fields as $field) {
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
				case 'number':
					$control += array_filter(['type' => Controls_Manager::NUMBER, 'min' => $field['min'], 'max' => $field['max'], 'step' => $field['step']], static fn ($value): bool => null !== $value);
					break;
				default:
					$control += ['type' => Controls_Manager::TEXT];
			}

			if ('type' !== $field['name']) {
				$control['condition'] = [self::PREFIX . 'type' => $field['usedBy']];
			}

			$controls[self::PREFIX . $field['name']] = $control;
		}

		return $controls;
	}

	/**
	 * What the shortcode is given for an element's settings: the chosen type and
	 * the settings that type has, in their stored shapes.
	 *
	 * @param array<string, mixed>             $settings Elementor's settings for the element.
	 * @param array<int, array<string, mixed>> $fields   WidgetSchema::toArray().
	 * @return array<string, string>
	 */
	public static function shortcodeAttributes(array $settings, array $fields): array
	{
		$type = $settings[self::PREFIX . 'type'] ?? '';
		$type = is_string($type) ? sanitize_text_field($type) : '';
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

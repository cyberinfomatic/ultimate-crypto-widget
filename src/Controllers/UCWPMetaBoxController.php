<?php
namespace Cyberinfomatic\UltimateCryptoWidget\Controllers;

use Cyberinfomatic\UltimateCryptoWidget\Helpers\Debugger;
use WP_Post;
use Closure;

class UCWPMetaBoxController {

	private string $id;
	private string $title;
	private string $screen;
	private string $context = 'normal'; // 'normal', 'advanced', or 'side'
	private string $priority = 'default'; // 'default', 'high', 'low', or 'core'

	public ?WP_Post $post = null;

	private Closure $customise_callback;

	private array $fields = [];

	function __construct($id, $title, $screen) {
		$this->id         = $id;
		$this->title      = $title;
		$this->screen     = $screen;
		$this->customise_callback = fn($post) => $this->callback($post);
	}

	function set($property, $value) {
		$this->$property = $value;
	}

	function show(WP_Post $post = null) {
		$this->post = $post;
		add_meta_box(
			$this->id,
			$this->title,
			fn($post) => $this->customise_callback->__invoke($post),
			$this->screen,
			$this->context,
			$this->priority
		);
	}

	function enqueue_scripts($path, $handle = 'ucwp_widget_admin_script', $deps = [], $ver = false, $in_footer = true): void {
		add_action('admin_enqueue_scripts', function() use ($path, $handle, $deps, $ver, $in_footer) {
			wp_enqueue_script($handle, $path, $deps, $ver, $in_footer);
		});
	}

	function enqueue_styles($path, $handle = 'ucwp_widget_admin_style', $deps = [], $ver = false, $media = 'all'): void {
		add_action('admin_enqueue_scripts', function() use ($path, $handle, $deps, $ver, $media) {
			wp_enqueue_style($handle, $path, $deps, $ver, $media);
		});
	}

	function add_field($id, $label, $type, $options = [], $attributes = [], $options_attributes = []): static {
		$this->fields[] = [
			...$options_attributes,
			'id' => $id,
			'label' => $label,
			'type' => $type,
			'options' => $options,
			'attributes' => $attributes,
			'default' => $attributes['value'] ?? $options_attributes['default'] ?? ''
		];
		return $this;
	}

	function set_callback(Closure $callback): UCWPMetaBoxController {
		$this->customise_callback = $callback;
		return $this;
	}

	function callback($post = null) {
		wp_nonce_field(basename(UCWP_PLUGIN_FILE), 'ucwp_widget_nonce');
		foreach ($this->fields as $field) {
			$value = get_post_meta($post->ID, $field['id'], true);
			$field['default'] = $value;
			echo $this->generate_input_type_html($field['type'], $field['id'], $field);
		}
	}

	function all_meta($post_id, string $strip = null) {
		$meta = [];
		$save_data = get_post_meta($post_id);
		foreach ($this->fields as $field) {
			$key = $strip ? str_replace($strip . '_', '', $field['id']) : $field['id'];
			$meta[$key] = $save_data[$field['id']][0] ?? null;
		}
		return $meta;
	}

	function generate_input_type_html(string $type, string $id, array $data): string {
		$default_value = esc_attr($data['default'] ?? '');
		$attributes = isset($data['attributes']) && is_array($data['attributes']) ? $data['attributes'] : [];
		$attributes['class'] = esc_attr(($attributes['class'] ?? '') . ' ucwpmetabox-input');
		$required = !empty($attributes['required']);

		// Convert style array to string if necessary and escape
		if (isset($attributes['style'])) {
			$attributes['style'] = is_array($attributes['style']) ? esc_attr($this->style_to_string($attributes['style'])) : esc_attr($attributes['style']);
		}

		// Escape individual attributes
		$attributes_as_string = $this->attributes_to_string(array_map('esc_attr', $attributes));

		$html = "<div class=\"ucwpmetabox-field\">";
		$html .= "<label for=\"" . esc_attr($id) . "\" class=\"ucwpmetabox-label\">";
		$html .= esc_html($data['label']);
		$html .= $required ? '<span class="ucwpmetabox-required">*</span>' : '';
		$html .= "</label>";

		switch ($type) {
			case 'textarea':
				$html .= "<textarea id=\"" . esc_attr($id) . "\" name=\"" . esc_attr($id) . "\" $attributes_as_string>";
				$html .= esc_textarea($default_value);
				$html .= "</textarea>";
				break;

			case 'select':
				$html .= "<select id=\"" . esc_attr($id) . "\" name=\"" . esc_attr($id . (($data['attributes']['multiple'] ?? false) ? '[]' : '')) . "\" $attributes_as_string>";
				foreach ($data['options'] as $option => $option_data) {
					$option_label = is_array($option_data) ? esc_html($option_data['label'] ?? $option) : esc_html($option_data);
					$selected = $default_value == $option || (is_array($default_value) && in_array($option, $default_value)) ? 'selected' : '';
					$other_attributes = is_array($option_data) ? $this->attributes_to_string(array_map('esc_attr', $option_data['attributes'] ?? [])) : '';
					$html .= "<option value=\"" . esc_attr($option) . "\" $selected $other_attributes>";
					$html .= esc_html($option_label);
					$html .= "</option>";
				}
				$html .= "</select>";
				break;

			case 'checkbox':
				$checked = $default_value ? 'checked' : '';
				$html .= "<input type=\"checkbox\" id=\"" . esc_attr($id) . "\" name=\"" . esc_attr($id) . "\" value=\"1\" $checked $attributes_as_string />";
				break;

			case 'radio':
				foreach ($data['options'] as $option => $option_data) {
					$option_label = is_array($option_data) ? esc_html($option_data['label'] ?? $option) : esc_html($option_data);
					$checked = $default_value == $option ? 'checked' : '';
					$other_attributes = is_array($option_data) ? $this->attributes_to_string(array_map('esc_attr', $option_data['attributes'] ?? [])) : '';
					$html .= "<label>";
					$html .= "<input type=\"radio\" id=\"" . esc_attr($id) . "\" name=\"" . esc_attr($id) . "\" value=\"" . esc_attr($option) . "\" $checked $other_attributes $attributes_as_string />";
					$html .= esc_html($option_label);
					$html .= "</label>";
				}
				break;

			case "number":
				$html .= "<input type=\"number\" id=\"" . esc_attr($id) . "\" name=\"" . esc_attr($id) . "\" value=\"" . esc_attr($default_value) . "\" $attributes_as_string />";
				break;

			default:
				$html .= "<input type=\"text\" id=\"" . esc_attr($id) . "\" name=\"" . esc_attr($id) . "\" value=\"" . esc_attr($default_value) . "\" $attributes_as_string />";
				break;
		}

		$html .= "</div>";

		return $html;
	}

	function print_input_type(string $type, string $id, array $data): void {
		echo $this->generate_input_type_html($type, $id, $data);
	}

	function save($post_id): bool {
		if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
			return false;
		}

		if (defined('DOING_AJAX') && DOING_AJAX) {
			return false;
		}

		if (!current_user_can('edit_post', $post_id)) {
			return false;
		}

		if (!isset($_POST['ucwp_widget_nonce'])) {
			return false;
		}

		$nonce = sanitize_text_field(wp_unslash($_POST['ucwp_widget_nonce']));
		if (!wp_verify_nonce($nonce, basename(UCWP_PLUGIN_FILE))) {
			return false;
		}

		$names = array_map(fn($field) => $field['id'], $this->fields);

		$final_bool = true;
		foreach ($names as $name) {
			$data = sanitize_text_field($_POST[$name] ?? '');
			$old_data = get_post_meta($post_id, $name, true);
			$final_bool = $final_bool && (update_post_meta($post_id, $name, $data) || $old_data == $data);
		}
		return $final_bool;
	}

	function style_to_string(array $styles): string {
		$string_styles = '';
		foreach ($styles as $property => $value) {
			$string_styles .= esc_attr($property) . ': ' . esc_attr($value) . '; ';
		}
		return rtrim($string_styles);
	}

	function attributes_to_string(array $attributes): string {
		$string_attributes = '';
		foreach ($attributes as $key => $value) {
			$string_attributes .= esc_attr($key) . '="' . esc_attr($value) . '" ';
		}
		return rtrim($string_attributes);
	}

	function get_fields(): array {
		return $this->fields;
	}

	function get_fields_html(): array {
		$html = [];
		foreach ($this->fields as $field) {
			$html[] = $this->generate_input_type_html($field['type'], $field['id'], $field);
		}
		return $html;
	}
}

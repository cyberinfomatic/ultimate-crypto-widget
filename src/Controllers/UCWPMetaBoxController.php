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

	function show( WP_Post  $post  = null) {
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

	// enqueue scripts to the admin for post type ucwp_widget
	function enqueue_scripts($path , $handle = 'ucwp_widget_admin_script', $deps = [], $ver = false, $in_footer = true): void {
		add_action('admin_enqueue_scripts', function() use ($path, $handle, $deps, $ver, $in_footer){
			wp_enqueue_script($handle, $path, $deps, $ver, $in_footer);
		});
	}

	function enqueue_styles($path , $handle = 'ucwp_widget_admin_style', $deps = [], $ver = false, $media = 'all'): void {
		add_action('admin_enqueue_scripts', function() use ($path, $handle, $deps, $ver, $media){
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
			$this->print_input_type($field['type'], $field['id'], $field);
		}
	}


	function all_meta($post_id, string $strip = null) {
		$meta = [];
		$save_data = get_post_meta($post_id);
		foreach ($this->fields as $field) {
			// if post is given and strip out is true remove the post from the meta key '{post}_ should be deleted from the key
			$key =  $strip ? str_replace($strip . '_', '', $field['id']) : $field['id'];
			$meta[$key] = $save_data[$field['id']][0] ?? null;
		}
		return $meta;
	}


	function print_input_type(string $type, string $id, array $data): void
	{
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

		?>
		<div class="ucwpmetabox-field">
			<label for="<?php echo esc_attr($id); ?>" class="ucwpmetabox-label">
				<?php echo esc_html($data['label']); ?>
				<?php echo $required ? '<span class="ucwpmetabox-required">*</span>' : ''; ?>
			</label>
			<?php
			switch ($type) {
				case 'textarea':
					?>
					<textarea id="<?php echo esc_attr($id); ?>" name="<?php echo esc_attr($id); ?>" <?php echo $attributes_as_string; ?>>
                    <?php echo esc_textarea($default_value); ?>
                </textarea>
					<?php
					break;

				case 'select':
					?>
					<select id="<?php echo esc_attr($id); ?>" name="<?php echo esc_attr($id . (($data['attributes']['multiple'] ?? false) ? '[]' : '')); ?>" <?php echo $attributes_as_string; ?>>
						<?php
						foreach ($data['options'] as $option => $option_data) {
							$option_label = is_array($option_data) ? esc_html($option_data['label'] ?? $option) : esc_html($option_data);
							$selected = $default_value == $option || (is_array($default_value) && in_array($option, $default_value)) ? 'selected' : '';
							$other_attributes = is_array($option_data) ? $this->attributes_to_string(array_map('esc_attr', $option_data['attributes'] ?? [])) : '';
							?>
							<option value="<?php echo esc_attr($option); ?>" <?php echo esc_attr($selected); ?> <?php echo $other_attributes; ?>>
								<?php echo esc_html($option_label); ?>
							</option>
							<?php
						}
						?>
					</select>
					<?php
					break;

				case 'checkbox':
					$checked = $default_value ? 'checked' : '';
					?>
					<input type="checkbox" id="<?php echo esc_attr($id); ?>" name="<?php echo esc_attr($id); ?>" value="1" <?php echo esc_attr($checked); ?> <?php echo $attributes_as_string; ?> />
					<?php
					break;

				case 'radio':
					foreach ($data['options'] as $option => $option_data) {
						$option_label = is_array($option_data) ? esc_html($option_data['label'] ?? $option) : esc_html($option_data);
						$checked = $default_value == $option ? 'checked' : '';
						$other_attributes = is_array($option_data) ? $this->attributes_to_string(array_map('esc_attr', $option_data['attributes'] ?? [])) : '';
						?>
						<label>
							<input type="radio" id="<?php echo esc_attr($id); ?>" name="<?php echo esc_attr($id); ?>" value="<?php echo esc_attr($option); ?>" <?php echo esc_attr($checked); ?> <?php echo $other_attributes . ' ' . $attributes_as_string; ?> />
							<?php echo esc_html($option_label); ?>
						</label>
						<?php
					}
					break;

				case "number":
					?>
					<input type="number" id="<?php echo esc_attr($id); ?>" name="<?php echo esc_attr($id); ?>" value="<?php echo esc_attr($default_value); ?>" <?php echo $attributes_as_string; ?> />
					<?php
					break;

				default:
					?>
					<input type="text" id="<?php echo esc_attr($id); ?>" name="<?php echo esc_attr($id); ?>" value="<?php echo esc_attr($default_value); ?>" <?php echo $attributes_as_string; ?> />
					<?php
					break;
			}
			?>
		</div>
		<?php
	}

	/**
	 * @throws \Exception
	 */
	function save($post_id): bool {

		if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) {
			return false;
		}

		if (defined('DOING_AJAX') && DOING_AJAX) {
			return false;
		}
		// Check if the current user is authorized to do this action
		if ( !current_user_can( 'edit_post', $post_id ) ) {
			return false;
		}

		// Check if the nonce is set.
		if ( !isset( $_POST['ucwp_widget_nonce'] ) ) {
			return false;
		}

		// Verify that the nonce is valid.
		$nonce = sanitize_text_field(wp_unslash($_POST['ucwp_widget_nonce']));
		if ( !wp_verify_nonce( $nonce, basename(UCWP_PLUGIN_FILE) ) ) {
			Debugger::var_dump(__("The nonce is not valid", 'ultimate-crypto-widget'), true);
			return false;
		}


		$names = array_map(function($field){
			return $field['id'];
		}, $this->fields);

		$final_bool = true;
		foreach ($names as $name) {
			$data = $_POST[$name] ?? '';
			// sanitize the data
			$data = sanitize_text_field($data);
			// check if data is same as the one in the database
			$old_data = get_post_meta($post_id, $name, true);
			$final_bool = $final_bool && (update_post_meta($post_id, $name, $data) || $old_data == $data);
		}
		return $final_bool;
	}

	/**
	 * A helper function to convert an array of CSS styles to a string format.
	 * @param array $styles
	 * @return string
	 */
	function style_to_string(array $styles): string
	{
		$string_styles = '';
		foreach ($styles as $property => $value) {
			$string_styles .= esc_attr($property) . ': ' . esc_attr($value) . '; ';
		}
		return rtrim($string_styles);
	}

	/**
	 * A helper function to convert an array of attributes to a string format.
	 * @param array $attributes
	 * @return string
	 */
	function attributes_to_string(array $attributes): string
	{
		$string_attributes = '';
		foreach ($attributes as $key => $value) {
			$string_attributes .= esc_attr($key) . '="' . esc_attr($value) . '" ';
		}
		return rtrim($string_attributes);
	}

	 /**
	 * @return array
	 */
	 function get_fields(): array {
		return $this->fields;
	}
}

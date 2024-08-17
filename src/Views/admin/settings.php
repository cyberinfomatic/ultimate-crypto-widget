<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

if (!isset($settings, $prefix, $clear_cache_post_url, $coin_gecko_call_count)) {
	?>
	<!-- Error message if settings are not available -->
	<div class="wrap">
		<h1><?php esc_html_e('something went wrong', 'ultimate-crypto-widget'); ?></h1>
		<p><?php esc_html_e('Something went wrong while loading the settings page. Please try again or update the plugin', 'ultimate-crypto-widget'); ?></p>
	</div>
	<?php
	return;
}
?>

<div class="wrap">
	<h1><?php esc_html_e('ultimate crypto widget', 'ultimate-crypto-widget'); ?></h1>

	<form method="post" action="options.php">
		<?php
		settings_fields($prefix . 'api');
		do_settings_sections($prefix . 'api');
		foreach ($settings as $setting) {
			if ($setting['tab'] !== 'api') {
				continue;
			}
			?>
			<div class="form-group">
				<label for="<?php echo esc_attr($prefix . $setting['option_name']); ?>">
					<?php echo esc_html($setting['label']); ?>
				</label>
				<input type="<?php echo esc_attr($setting['type']); ?>"
					   name="<?php echo esc_attr($prefix . $setting['option_name']); ?>"
					   id="<?php echo esc_attr($prefix . $setting['option_name']); ?>"
					   class="regular-text"
					   value="<?php echo esc_attr(get_option($prefix . $setting['option_name'], $setting['default'])); ?>"
					   placeholder="<?php echo esc_attr($setting['placeholder']); ?>">
				<p class="description">
					<?php echo wp_kses_post($setting['description']); ?>
				</p>
			</div>
			<?php
		}
		?>
		<?php
		submit_button(__('save changes', 'ultimate-crypto-widget'));
		?>
	</form>
</div>

<!-- Form/button to clear cache -->
<div class="wrap">
	<h1><?php esc_html_e('coin gecko', 'ultimate-crypto-widget'); ?></h1>
	<!-- API call count in the last 30 days -->
	<p><?php echo esc_html__('API call count in the last 30 days: ', 'ultimate-crypto-widget') . esc_html($coin_gecko_call_count); ?></p>
	<form method="post" action="<?php echo esc_url($clear_cache_post_url ?? admin_url('admin-post.php?action=ucwp_clear_api_cache')); ?>">
		<?php wp_nonce_field('ucwp_clear_api_cache', 'security'); ?>
		<input type="submit" id="clear_ucwp_cache_button" value="<?php esc_attr_e('clear api caches', 'ultimate-crypto-widget'); ?>" />
	</form>
</div>

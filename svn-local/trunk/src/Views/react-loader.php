<?php if(!defined('ABSPATH')) exit(); ?>

<?php if(isset($react_id)) : ?>
	<div id="<?php echo  esc_attr($react_id) ?>">Loading...</div>
<?php else : ?>
	<div class="ucwp-react-load-failed">Failed to load the react component (<?php echo esc_html($component ?? '') ?>)</div>
<?php endif; ?>
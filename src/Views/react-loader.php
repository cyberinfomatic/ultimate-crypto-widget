<?php if(isset($react_id)) : ?>
	<div id="<?php echo  $react_id ?>">Loading...</div>
<?php else : ?>
	<div class="ucwp-react-load-failed">Failed to load the react component (<?php echo $component ?? '' ?>)</div>
<?php endif; ?>
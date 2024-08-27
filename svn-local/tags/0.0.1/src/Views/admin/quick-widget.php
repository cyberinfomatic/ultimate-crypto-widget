<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}


if(!isset($meta_box) || !is_array($meta_box)){
	?>
	<!--			 a something went wrong element-->
	<div class="wrap">
		<h1>Something went wrong</h1>
		<p>Something went wrong while loading the settings page. Please try again or Update the plugin</p>
	</div>
	<?php
	return;
}
?>

<style>
	div.ucwp-quick-setting-two-view-cnt {
		display: inline-block;
		width: 100%;
	}


	div.ucwp-quick-setting-two-view-cnt > div{
		display: inline-block;
		width: 45%;
		padding : 3px;
	}
</style>

<div class="wrap">
	<h1>Ultimate Crypto Widget</h1>
	<h2>Quick Widget</h2>
	<div class="devices-wrapper">
		<pre class="ucwp-short-code-preview">
			[ucwp_widget type=""]
		</pre>
<!--		copy button-->
		<button class="button button-primary ucwp-copy-shortcode">Copy Shortcode</button>
		<span class="ucwp-copy-shortcode-message"></span>
	</div>
	<div class="ucwp-quick-setting-two-view-cnt">
		<div class="wrap" id="ucwp_widget">
			<?php
				foreach ($meta_box as $field){
					echo $field;
				}
			?>
		</div>
	</div>
</div>

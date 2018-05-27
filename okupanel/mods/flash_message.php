<?php

// flash a message every while

if (!defined('ABSPATH'))
	exit();

add_action('okupanel_print_extra_textarea_fields_2', 'okupanel_flash_fields');
function okupanel_flash_fields(){
	?>
	<div class="okupanel-field okupanel-settings-field">
		<label><?= __('Flash message', 'okupanel') ?></label>
		<div class="okupanel-field-inner">
			<div><textarea name="okupanel_flash_message"><?= esc_textarea(get_option('okupanel_flash_message', '')) ?></textarea></div>
		</div>
	</div>
	<?php
}

add_filter('okupanel_textline_fields', 'okupanel_flash_fields_save');
function okupanel_flash_fields_save($fields){
	$fields[] = 'flash_message';
	return $fields;
}

add_action('wp_footer', 'okupanel_flash_footer');
function okupanel_flash_footer(){
	if (empty($_GET['fullscreen']))
		return;
		
	$msg = trim(get_option('okupanel_flash_message'));
	if ($msg == '')
		return;
		
	?>
	<style>
		.okupanel-flash-msg {
			display: none;
			position: fixed;
			top: 0;
			bottom: 0;
			left: 0;
			right: 0;
			z-index: 997;
		}
		.okupanel-flash-bg {
			position: absolute;
			top: 0;
			bottom: 0;
			left: 0;
			right: 0;
			background: rgba(0,0,0,0.5);
			z-index: 998;
		}
		.okupanel-flash-msg > div.okupanel-flash-content {
			position: absolute;
			top: 0;
			bottom: 0;
			left: 0;
			right: 0;
			z-index: 1000;
			display: table;
			vertical-align: middle;
			width: 100%;
			height: 100%;
			border: none;
			text-align: center;
		}
		.okupanel-flash-msg > div > div {
			display: table-cell;
			vertical-align: middle;
			width: 100%;
			height: 100%;
			border: none;
			text-align: center;
		}
		.okupanel-flash-msg > div > div > div {
			display: inline-block;
			vertical-align: middle;
			max-width: 800px;
			padding: 20px;
			font-size: 40px;
			border-radius: 10px;
			border: 1px solid #333;
			background: white;
			text-align: center;
			margin: 20px;
			font-family: nasalization;
		}
		.okupanel-flash-msg > div > div > div i {
			font-size: 210px;
			color: red;
		}
		.okupanel-flash-msg > div > div > div > div {
		}
	</style>
	<div class="okupanel-flash-msg">
		<div class="okupanel-flash-bg"></div>
		<div class="okupanel-flash-content"><div><div><?= nl2br($msg) ?></div></div></div>
	</div>
	<script>
		jQuery(document).ready(function(){
			
			var date_diff = ((new Date()).getTime() / 1000) - OKUPANEL.now;
			
			function okulec_check_flash(){
						
				var d = new Date();
				d = new Date(d.valueOf() + d.getTimezoneOffset() * 60000); // set to GMT-0

				d.setTime((d.getTime() / 1000 - date_diff) * 1000); // adjust to server time
				
				var h = d.getHours();
				var m = d.getMinutes();
				//alert(h+':'+m);

				if ((h >= 22 && m >= 20) || h > 22 || h < 5){
					
					jQuery('.okupanel-flash-msg').show();
					
					setTimeout(function(){
						jQuery('.okupanel-flash-msg').hide();
					}, 30 * 1000); // during 30s
				}
			}
			
			setInterval(function(){
				okulec_check_flash();
			}, 2 * 60 * 1000); // every 2min
			
			setTimeout(function(){
				okulec_check_flash();
			}, 5000); // 5s
	
		});
	</script>
	<?php
}

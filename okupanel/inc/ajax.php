<?php

if (!defined('ABSPATH'))
	exit();


add_action('wp_ajax_okupanel', 'okupanel_ajax');
add_action('wp_ajax_nopriv_okupanel', 'okupanel_ajax');

function okupanel_ajax(){
	if (!empty($_REQUEST['target']) && preg_match('#^([0-9A-Z_]+)$#i', $_REQUEST['target'])){
		$fn = 'okupanel_ajax_'.$_REQUEST['target'];
		if (function_exists($fn)){
			$ret = call_user_func($fn);
			echo json_encode(is_string($ret) ? array('success' => false, 'error' => $ret) : ($ret === true ? array('success' => true) : $ret));
			exit();
		} else
			die('0');
	} else
		die('0');
}

function okupanel_ajax_table_refresh(){
	ob_start();
	okupanel_print_panel();
	return array(
		'success' => true, 
		'html' => ob_get_clean(), 
		'right' => '<div class="okupanel-panel-right-inner">'.do_shortcode(get_option('okupanel_right_panel', '')).'</div>', 
		'bottombar' => okupanel_bottombar(),
		'reload' => empty($_POST['okupanel_version']) || OKUPANEL_VERSION !== $_POST['okupanel_version'],
		'extra' => apply_filters('okupanel_js_return', array()),
	);
}

function okupanel_bottombar(){
	$html = '';
	if ($text = get_option('okupanel_bottombar', false))
		foreach (explode("\n", trim($text)) as $line){
			$line = trim($line);
			if ($line != ''){
				$html .= '<div class="okupanel-bottom-bar-line">'.$line.'</div>';
			}
		}
	else
		return false;
	return $html;
}

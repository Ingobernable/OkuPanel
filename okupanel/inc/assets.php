<?php

if (!defined('ABSPATH'))
	exit();
	
// add scripts
add_action('wp_enqueue_scripts', 'okupanel_wp_enqueue_styles', 9999);
add_action('admin_enqueue_scripts', 'okupanel_wp_enqueue_styles', 9999);
function okupanel_wp_enqueue_styles(){
	static $done = false;
	if ($done)
		return;
	$done = true;
	
	$dept = array();
	if (okupanel_is_page()){
		wp_enqueue_style('okupanel-fa', OKUPANEL_URL.'/assets/lib/font-awesome-4.7.0/css/font-awesome.min.css', array(), OKUPANEL_VERSION);
		$dept = array('okupanel-fa');
	}
	
	okupanel_enqueue_style_if('settings', okupanel_is_page('settings'), $dept);
		
	$is_panel = okupanel_is_page('panel');
	if ($is_panel)
		wp_dequeue_style('style.css');
	okupanel_enqueue_style_if('panel', $is_panel, $dept);
	
	okupanel_enqueue_script_if('panel', $is_panel, array('jquery'));
}

function okupanel_enqueue_style_if($id, $if, $dept = array()){
	if (apply_filters('okupanel_enqueue_style', $if, $id, $dept))
		wp_enqueue_style('okupanel-'.$id, OKUPANEL_URL.'/assets/css/'.$id.'.css', $dept, OKUPANEL_VERSION);
}

function okupanel_enqueue_script_if($id, $if, $dept = array()){
	if (apply_filters('okupanel_enqueue_script', $if, $id, $dept))
		wp_enqueue_script('okupanel-'.$id, OKUPANEL_URL.'/assets/js/'.$id.'.js', $dept, OKUPANEL_VERSION);
}

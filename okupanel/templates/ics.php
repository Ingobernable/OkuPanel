<?php 

if (!defined('ABSPATH'))
	exit();


if (!empty($_GET['okupanel_debug']) && current_user_can('manage_options')){
	ini_set('display_errors', 'On');
	error_reporting(E_ALL);
}

show_admin_bar(false);

header('Content-type: text/calendar; charset=utf-8');
header('Content-Disposition: attachment; filename=' . $filename);

?><?php
	# if (!($events = okupanel_get_events()))
	# 	return '';
	# print_r($events);
	$base = WP_CONTENT_DIR.'/cache';
	$cached_ics = $base.'/okupanel-cache.ics';
	if (!($file = file_get_contents($cached_ics, FILE_USE_INCLUDE_PATH)))
		return '';
	print($file);

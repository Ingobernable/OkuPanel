<?php 

if (!defined('ABSPATH'))
	exit();


if (!empty($_GET['okupanel_debug']) && current_user_can('manage_options')){
	ini_set('display_errors', 'On');
	error_reporting(E_ALL);
}

require OKUPANEL_PATH.'/inc/lib/SimpleICS.php';


$cal = new SimpleICS();

global $okupanel_ics_ev;
foreach (okupanel_get_events() as $ev){
	$okupanel_ics_ev = $ev;
	$cal->addEvent(function($e){
		global $okupanel_ics_ev;
		$e->startDate = new DateTime(date('Y-m-d H:i:s', $okupanel_ics_ev['start_gmt']));
		$e->endDate = new DateTime(date('Y-m-d H:i:s', $okupanel_ics_ev['end_gmt']));
		$e->uri = $okupanel_ics_ev['htmlLink'];
		$e->location = $okupanel_ics_ev['location'];
		$e->description = $okupanel_ics_ev['description'];
		$e->summary = $okupanel_ics_ev['summary'];
	});
}


header('Content-Type: '.SimpleICS::MIME_TYPE);
header('Content-Disposition: attachment; filename=calendar.ics');
echo $cal->serialize();
exit();

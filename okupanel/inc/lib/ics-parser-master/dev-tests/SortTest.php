<?php
// Provide default, to prevent warnings about it not being set.
date_default_timezone_set("UTC");

require 'class.iCalReader.php';
$ical = new ICal('MyCal.ics');
/*
require 'class.iCalParser.php';
$ical = new ParsedICal('MyCal.ics');
*/

?>
<DOCTYPE html>
<head>
	<meta http-equiv="Content-Type" content="text/html;charset=utf-8" >
</head>

<body>
<?php

$events = $ical->getEvents();

function echoTimes ($events) {
	echo "<pre>\n";
	echo "<b>UID\t\t\t\tDTSTAMP\t\t\tDTSTART\t\t\tDTEND</b>\n";
	foreach ($events as $event) {
		if (isset($event['dtstart'])) {
			$uid = $event['uid'];
		//	$dtstamp = date("Ymd His", $event['dtstamp']);
			$dtstamp = "00000000T000000";
			$dtstart = date("Ymd His", $event['dtstart']);
			$dtend = date("Ymd His", $event['dtend']);
		} else {
			$uid = $event['UID']['value'];
			$dtstamp = $event['DTSTAMP']['value'];
			$dtstart = $event['DTSTART']['value'];
			$dtend = $event['DTEND']['value'];
		}
		
		echo "<i>" . substr($uid, 0, strpos($uid,'@')) . "</i>\t";
		
		echo substr($dtstamp,0,4)  . "-" .
			 substr($dtstamp,4,2)  . "-" .
			 substr($dtstamp,6,2)  . " " .
			 substr($dtstamp,9,2)  . ":" .
			 substr($dtstamp,11,2) . ":" .
			 substr($dtstamp,13,2) . "\t";
		
		echo substr($dtstart,0,4)  . "-" .
			 substr($dtstart,4,2)  . "-" .
			 substr($dtstart,6,2)  . " " ;
		if (strlen($dtstart) > 10) {
			echo substr($dtstart,9,2)  . ":".
				 substr($dtstart,11,2) . ":".
				 substr($dtstart,13,2) . "\t";
		} else {
			echo "\t\t";
		}
		
		echo substr($dtend,0,4)  . "-" .
			 substr($dtend,4,2)  . "-" .
			 substr($dtend,6,2)  . " " ;
		if (strlen($dtend) > 10) {
			echo substr($dtend,9,2)  . ":".
				 substr($dtend,11,2) . ":".
				 substr($dtend,13,2);
		}
		echo "\n";
	}
	echo "</pre>\n";
}

echo "<p><b>Current Test:</b> sort (dtstart)</p>\n\n";
echo "<hr>\n\n";

echo "<p>Before:</p>\n";
echoTimes($events);

$ical->sortEvents($events);

echo "<p>After:</p>\n";
echoTimes($events);

?>
</pre>
</body>
</html>

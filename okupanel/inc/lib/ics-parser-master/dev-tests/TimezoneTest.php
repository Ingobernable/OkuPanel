<?php
// Provide default, to prevent warnings about it not being set.
date_default_timezone_set("UTC");

/**
 * De-comment a TimeZone to test it, or try your own.
 * 
 * See: http://uk1.php.net/manual/en/timezones.php
 */
//date_default_timezone_set("Europe/Berlin");
//date_default_timezone_set("America/Vancouver");
//date_default_timezone_set("Australia/Sydney");
//date_default_timezone_set("America/New_York");
//date_default_timezone_set("America/Montreal");
//date_default_timezone_set("Africa/Cairo");

require '../class.iCalReader.php';

$ical   = new ICal('TimezoneTest.ics');
$events = $ical->events();

echo "<p><b>Currently Set Timezone:</b> " . date_default_timezone_get() . "</p>\n\n";
echo "<p><i>Please note that the times derived may be different from that stated in the descriptions by an hour due to Daylight Savings Time in certain locales.</i></p>\n\n";
echo "<hr>\n\n";

foreach ($events as $event) {
	echo "<p><b>" . $event['SUMMARY']['value'] . "</b></p>\n";
	echo "<p>" . $event['DESCRIPTION']['value'] . "</p>\n<pre>";
	
	$datetime = $event['DTSTART']['value'];
	echo "DTSTART: ".$datetime."\n";
	if (is_array($event['DTSTART']['params'])) {
		echo "   TZID: " . $event['DTSTART']['params']['TZID'] . "\n";
	}
	$datetime = $ical->iCalDateToUnixTimestamp($event['DTSTART']);
	echo "   Unix: ".$datetime."\n";
	echo "  Human: ".date("Y-n-j H:i", $datetime)."\n\n";
	
	$datetime = $event['DTEND']['value'];
	echo "  DTEND: ".$datetime."\n";
	if (is_array($event['DTSTART']['params'])) {
		echo "   TZID: " . $event['DTSTART']['params']['TZID'] . "\n";
	}
	$datetime = $ical->iCalDateToUnixTimestamp($event['DTEND']);
	echo "   Unix: ".$datetime."\n";
	echo "  Human: ".date("Y-n-j H:i", $datetime)."\n</pre>\n<br>\n";
}

?>
</pre>

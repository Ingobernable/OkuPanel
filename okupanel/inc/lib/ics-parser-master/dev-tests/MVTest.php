<?php
// Provide default, to prevent warnings about it not being set.
date_default_timezone_set("UTC");

require '../class.iCalReader.php';

?>
<DOCTYPE html>
<head>
	<meta http-equiv="Content-Type" content="text/html;charset=utf-8" >
</head>

<body>
<?php

$ical   = new ICal('MVTest.ics');
$events = $ical->events();

echo "<p><b>Current Test:</b> MLMV</p>\n\n";
echo "<hr>\n\n";

foreach ($events as $event) {
	echo "<p><b>" . $event['SUMMARY']['value'] . "</b></p>\n";
	echo "<p>" . $event['DESCRIPTION']['value'] . "</p>\n<pre>";
	echo "[RESOURCES] => " . print_r($event['RESOURCES'], true);
	echo "</pre>";
}

?>
</pre>
</body>
</html>

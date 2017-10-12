<?php
/**
 * This example demonstrates how the Ics-Parser should be used.
 *
 * PHP Version 5
 *
 * @category Example
 * @package  Ics-parser
 * @author   Martin Thoma <info@martin-thoma.de>
 * @license  http://www.opensource.org/licenses/mit-license.php  MIT License
 * @version  SVN: <svn_id>
 * @link     http://code.google.com/p/ics-parser/
 * @example  $ical = new ical('MyCal.ics');
 *           print_r( $ical->get_event_array() );
 */
require 'class.iCalReader.php';

$ical   = new ICal('MyCal.ics');
$events = $ical->events();

$date = $events[0]['DTSTART']['value'];
echo "The ical date: ";
echo $date;
echo "<br/>";

echo "The Unix timestamp: ";
echo $ical->iCalDateToUnixTimestamp($date);
echo "<br/>";

echo "The number of events: ";
echo $ical->event_count;
echo "<br/>";

echo "The number of todos: ";
echo $ical->todo_count;
echo "<br/>";
echo "<hr/><hr/>";

foreach ($events as $event) {
    echo "SUMMARY: ".$event['SUMMARY']['value']."<br/>";
    echo "DTSTART: ".$event['DTSTART']['value']." - UNIX-Time: ".$ical->iCalDateToUnixTimestamp($event['DTSTART']['value'])."<br/>";
    echo "DTEND: ".$event['DTEND']['value']."<br/>";
    echo "DTSTAMP: ".$event['DTSTAMP']['value']."<br/>";
    echo "UID: ".$event['UID']['value']."<br/>";
    echo "CREATED: ".$event['CREATED']['value']."<br/>";
    echo "DESCRIPTION: ".$event['DESCRIPTION']['value']."<br/>";
    echo "LAST-MODIFIED: ".$event['LAST-MODIFIED']['value']."<br/>";
    echo "LOCATION: ".$event['LOCATION']['value']."<br/>";
    echo "SEQUENCE: ".$event['SEQUENCE']['value']."<br/>";
    echo "STATUS: ".$event['STATUS']['value']."<br/>";
    echo "TRANSP: ".$event['TRANSP']['value']."<br/>";
    echo "<hr/>";
}
?>

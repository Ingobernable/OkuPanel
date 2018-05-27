<?php
// still in development, please do not activate still!!!

// generate an ICS output stream

if (!defined('ABSPATH'))
	die();
	
	
	
/**
 * Script from https://gist.github.com/jakebellacera/635416
 * 
 * ICS.php
 * =======
 * Use this class to create an .ics file.
 *
 * Usage
 * -----
 * Basic usage - generate ics file contents (see below for available properties):
 *   $ics = new ICS($props);
 *   $ics_file_contents = $ics->to_string();
 *
 * Setting properties after instantiation
 *   $ics = new ICS();
 *   $ics->set('summary', 'My awesome event');
 *
 * You can also set multiple properties at the same time by using an array:
 *   $ics->set(array(
 *     'dtstart' => 'now + 30 minutes',
 *     'dtend' => 'now + 1 hour'
 *   ));
 *
 * Available properties
 * --------------------
 * description
 *   String description of the event.
 * dtend
 *   A date/time stamp designating the end of the event. You can use either a
 *   DateTime object or a PHP datetime format string (e.g. "now + 1 hour").
 * dtstart
 *   A date/time stamp designating the start of the event. You can use either a
 *   DateTime object or a PHP datetime format string (e.g. "now + 1 hour").
 * location
 *   String address or description of the location of the event.
 * summary
 *   String short summary of the event - usually used as the title.
 * url
 *   A url to attach to the the event. Make sure to add the protocol (http://
 *   or https://).
 */
class ICS {
  const DT_FORMAT = 'Ymd\THis\Z';
  protected $properties = array();
  private $available_properties = array(
    'description',
    'dtend',
    'dtstart',
    'location',
    'summary',
    'url'
  );
  public function __construct($props) {
    $this->set($props);
  }
  public function set($key, $val = false) {
    if (is_array($key)) {
      foreach ($key as $k => $v) {
        $this->set($k, $v);
      }
    } else {
      if (in_array($key, $this->available_properties)) {
        $this->properties[$key] = $this->sanitize_val($val, $key);
      }
    }
  }
  public function to_string() {
    $rows = $this->build_props();
    return implode("\r\n", $rows);
  }
  private function build_props() {
    // Build ICS properties - add header
    $ics_props = array(
      'BEGIN:VCALENDAR',
      'VERSION:2.0',
      'PRODID:-//hacksw/handcal//NONSGML v1.0//EN',
      'CALSCALE:GREGORIAN',
      'BEGIN:VEVENT'
    );
    // Build ICS properties - add header
    $props = array();
    foreach($this->properties as $k => $v) {
      $props[strtoupper($k . ($k === 'url' ? ';VALUE=URI' : ''))] = $v;
    }
    // Set some default values
    $props['DTSTAMP'] = $this->format_timestamp('now');
    $props['UID'] = uniqid();
    // Append properties
    foreach ($props as $k => $v) {
      $ics_props[] = "$k:$v";
    }
    // Build ICS properties - add footer
    $ics_props[] = 'END:VEVENT';
    $ics_props[] = 'END:VCALENDAR';
    return $ics_props;
  }
  private function sanitize_val($val, $key = false) {
    switch($key) {
      case 'dtend':
      case 'dtstamp':
      case 'dtstart':
        $val = $this->format_timestamp($val);
        break;
      default:
        $val = $this->escape_string($val);
    }
    return $val;
  }
  private function format_timestamp($timestamp) {
    $dt = new DateTime($timestamp);
    return $dt->format(self::DT_FORMAT);
  }
  private function escape_string($str) {
    return preg_replace('/([\,;])/','\\\$1', $str);
  }
}



add_action('okupanel_action_ics', function(){
	//header('Content-type: text/calendar; charset=utf-8');
//	header('Content-Disposition: attachment; filename=invite.ics');
	
	$events = array();
	foreach (okupanel_get_events() as $e)
		$events = array(
		  'location' => $e['location'],
		  'description' => $e['description'],
		  'dtstart' => date_i18n('Y-m-d H:i:s', $e['start']),
		  'dtend' => date_i18n('Y-m-d H:i:s', $e['end']),
		  'summary' => $e['summary'],
		  'url' => ''//$e['url']
		);
	require_once('../inc/lib/SimpleICS.php');

	$cal = new SimpleICS();
	foreach ($events as $ce){
		$e = new SimpleICS_Event();
		$e->startDate = new DateTime(date_i18n('Y-m-d H:i:s', $ce['start']));
		$e->endDate = new DateTime(date_i18n('Y-m-d H:i:s', $ce['end']));
		$e->location = $ce['location'];
		$e->description = $ce['description'];
		$e->summary = $ce['summary'];
		$cal->addEvent($e);
	}
	header('Content-Type: '.SimpleICS::MIME_TYPE);
	if (isset($_GET['download'])) {
		header('Content-Disposition: attachment; filename=event.ics');
	}
	echo $cal->serialize();
	exit();
});

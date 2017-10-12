<?php
/**
 * MODIFIED BETWEEN L206 AND L210!!
 * 
 * This PHP-Class should only read a iCal-File (*.ics), parse it and give an 
 * array with its content.
 *
 * PHP Version 5
 *
 * @category Parser
 * @package  Ics-parser
 * @author   Martin Thoma <info@martin-thoma.de>
 * @license  http://www.opensource.org/licenses/mit-license.php  MIT License
 * @version  SVN: r13
 * @link     http://code.google.com/p/ics-parser/
 * @example  $ical = new ICal('MyCal.ics');
 *           print_r( $ical->events() );
 */

//error_reporting(E_ALL);

/**
 * This is the iCal-class
 *
 * @category Parser
 * @package  Ics-parser
 * @author   Martin Thoma <info@martin-thoma.de>
 * @license  http://www.opensource.org/licenses/mit-license.php  MIT License
 * @link     http://code.google.com/p/ics-parser/
 *
 * @param {string} $filename The name of the file which should be parsed
 * @constructor
 */
class ICal
{
    /* How many ToDos are in this ical? */
    public  /** @type {int} */ $todo_count = 0;

    /* How many events are in this ical? */
    public  /** @type {int} */ $event_count = 0; 

    /* The parsed calendar */
    public /** @type {Array} */ $cal;

    /* Which keyword has been added to cal at last? */
    private /** @type {string} */ $_lastKeyWord;
    
    /* Reference of keywords that permit multiple values over multiple lines */
    protected static /** @type {array} */ $mlmvKeys = array(
            'glob' => array( // Always permits MLMV
                'ATTENDEE', 'COMMENT', 'RSTATUS'
            ),
            'some' => array( // Permits MLMV under some Sections
                'ATTACH' => array('VEVENT', 'VTODO', 'VJOURNAL', 'VALARM'),
                'CATEGORIES' => array('VEVENT', 'VTODO', 'VJOURNAL'),
                'CONTACT' => array('VEVENT', 'VTODO', 'VJOURNAL'),
                'EXDATE' => array('VEVENT', 'VTODO', 'VJOURNAL'),
                'RELATED' => array('VEVENT', 'VTODO', 'VJOURNAL'),
                'RESOURCES' => array('VEVENT', 'VTODO'),
                'RDATE' => array('VEVENT', 'VTODO', 'VJOURNAL'),
            ),
            'spec' => array( // Permits MLMV under a specific Section
                'VFREEBUSY' => array('FREEBUSY'),
                'VJOURNAL' => array('DESCRIPTION'),
            )
        );
    
    /* Reference of keywords that permit multiple values over a single line,
                                                    along with their data type(s) */
    protected static /** @type {array} */ $slmvKeys = array(
            "EXDATE",                    /* DATE / DATE-TIME          */
            "RDATE",                     /* DATE / DATE-TIME / PERIOD */
            "FREEBUSY",                  /* DURATION / PERIOD         */
            "CATEGORIES", "RESOURCES",   /* TEXT                      */
            /* -none- */                 /* FLOAT / INTEGER / TIME    */ 
        );
    
    /* ISO.8601.2004 pattern used to express dates */
    protected static /** @type {regex string} */ $iso8601pattern =
       /*  --YYYY--  ---MM---  ---DD---            ----HH----  ----MM----  ----SS----  */
        '/([0-9]{4})([0-9]{2})([0-9]{2})([T]{0,1})([0-9]{0,2})([0-9]{0,2})([0-9]{0,2})/';

    /** 
     * Creates the iCal-Object
     * 
     * @param {string} $filename The path to the iCal-file
     *
     * @return Object The iCal-Object
     */ 
    public function __construct($filename) 
    {
        if (!$filename) {
            return false;
        }
        
        $lines = file($filename, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (stristr($lines[0], 'BEGIN:VCALENDAR') === false) {
            return false;
        } else {
            // TODO: Fix multiline-description problem (see http://tools.ietf.org/html/rfc5545#section-3.8.1.5)
            foreach ($lines as $line) {
                $line = trim($line);
                $add  = $this->keyValueFromString($line);
                if ($add === false) {
                    $this->addCalendarComponentWithKeyAndValue($type, false, $line);
                    continue;
                } 

                list($keyword, $value) = $add;

                switch ($line) {
                // http://www.kanzaki.com/docs/ical/vtodo.html
                case "BEGIN:VTODO": 
                    $this->todo_count++;
                    $type = "VTODO"; 
                    break; 

                // http://www.kanzaki.com/docs/ical/vevent.html
                case "BEGIN:VEVENT": 
                    //echo "vevent gematcht";
                    $this->event_count++;
                    $type = "VEVENT"; 
                    break; 

                //all other special strings
                case "BEGIN:VCALENDAR": 
                case "BEGIN:DAYLIGHT": 
                    // http://www.kanzaki.com/docs/ical/vtimezone.html
                case "BEGIN:VTIMEZONE": 
                case "BEGIN:STANDARD": 
                    $type = $value;
                    break; 
                case "END:VTODO": // end special text - goto VCALENDAR key 
                case "END:VEVENT": 
                case "END:VCALENDAR": 
                case "END:DAYLIGHT": 
                case "END:VTIMEZONE": 
                case "END:STANDARD": 
                    $type = "VCALENDAR"; 
                    break; 
                default:
                    $this->addCalendarComponentWithKeyAndValue($type, 
                                                               $keyword, 
                                                               $value);
                    break; 
                } 
            }
            return $this->cal; 
        }
    }

    /** 
     * Add to $this->ical array one value and key.
     * 
     * @param {string} $component This could be VTODO, VEVENT, VCALENDAR, ... 
     * @param {string} $keyword   The keyword, for example DTSTART
     * @param {string} $value     The value, for example 20110105T090000Z
     *
     * @return {None}
     */ 
    public function addCalendarComponentWithKeyAndValue($component, 
                                                        $keyword, 
                                                        $value) 
    {
        switch ($component) {
        case "VEVENT":
            $count = $this->event_count - 1;
            break;
        case "VTODO":
            $count = $this->todo_count - 1;
            break;
        default:
            $count = -1;
        }
        
        if ($keyword == false) { 
            $keyword = $this->last_keyword;
            $extract = $this->cal[$component][$count][$keyword];
            
            if ($this->_mlmvCheck($component, $keyword)) {
                $valCount = count($extract) - 1;
                
                if ($this->_slmvCheck($keyword)) {
                    $value = $this->_slmvExplode($value);
                    $value[0] = array_pop($extract[$valCount]['value']) . $value[0];
                    $value = array_merge($extract[$valCount]['value'], $value);
                } else {
                    $value = $extract[$valCount]['value'] . $value;
                }
                
                if (isset($extract[$valCount]['params'])) {
                    $params = $extract[$valCount]['params'];
                }
            } else {
                $value = $extract['value'] . $value;
                /* There isn't a check for SLMV here because all SLMV
                    keywords are also MLMV keywords under all circumstances
                    they can validly be used */
                if (isset($extract['params'])) {
                    $params = $extract['params'];
                }
            }
        } else {
            $keyword = explode(";", $keyword);
            if (count($keyword) > 1) {
                $params = array();
                for ($k=1; $k<count($keyword); $k++) {
					// MODIFIED!
                    $ckeys = explode("=", @$keyword[$k], 2);
                    if (count($ckeys) > 1)
						$params[$ckeys[0]] = $ckeys[1];
					// END MODIFIED
                }
            } else {
                $params = isset($params) ? $params : "";
            }
            $keyword = $keyword[0];
            $this->last_keyword = $keyword;
            
            if ($this->_slmvCheck($keyword)) {
                $value = $this->_slmvExplode($value);
            }
        }
        
        $value = array( "value" => $value );
        if (isset($params) && $params != "") { $value["params"] = $params; }
        
        if ($count == -1) {
            $this->cal[$component][$keyword] = $value; 
        } else {
            if ($this->_mlmvCheck($component, $keyword)) {
                if (isset($valCount)) {
                    $this->cal[$component][$count][$keyword][$valCount] = $value;
                } else {
                    $this->cal[$component][$count][$keyword][] = $value;
                }
            } else {
                $this->cal[$component][$count][$keyword] = $value;
            }
        }
    }

    /**
     * Get a key-value pair from a string.
     *
     * @param {string} $text which is like "VCALENDAR:Begin" or "LOCATION:"
     *
     * @return {array} array("VCALENDAR", "Begin")
     */
    public function keyValueFromString($text) 
    {
        preg_match("/([^:]+)[:]([\w\W]*)/", $text, $matches);
        if (count($matches) == 0) {
            return false;
        }
        $matches = array_splice($matches, 1, 2);
        return $matches;
    }
    
    /**
     * Returns a multidimensioned array of arrays with either all events, or events
     *   from within a given range. Every event is an associative array and each
     *   property is an element within it.
     * 
     * If both $start and $end are false, all events are returned.
     * 
     * If $start is a valid date/date-time but $end is equivalent to false, then the
     *   function will return all events that start after the time passed via $start
     * 
     * If $start is equivalent to false but $end is a valid date/date-time, then the
     *   function will return all events that end before the time passed via $end
     *
     * If both $start and $end are valid dates/date-times, then the function will return
     *   all events that start after the time passed via $start and end before the
     *   time passed via $end
     * 
     * Valid dates/date-times follow the ISO.8601.2004 format as described in
     *   http://tools.ietf.org/html/rfc5545#section-3.3.5
     * 
     * @param {boolean|string} $start false if no range-start set, or a valid start time/date
     * @param {boolean|string} $end   false if no range-end set, or a valid end time/date
     * 
     * @return {array}
     */
    public function getEvents($start = false, $end = false) 
    {
        if ($start === false && $end === false) {
            return $this->cal['VEVENT'];
        } else {
            
            if ($start != false) {
                preg_match($this::$iso8601pattern, $start, $start);
                $start = (count($start) > 0) ? $start[0] : false;
            }
            if ($end != false) {
                preg_match($this::$iso8601pattern, $end, $end);
                echo print_r($end, true) . "<br>";
                $end = (count($end) > 0) ? $end[0] : false;
            }
            
            $return = array();
            
            foreach ($this->cal['VEVENT'] as $event) {
                if (($start == false || $event["DTSTART"]['value'] >= $start)
                        && ($end == false || $event["DTEND"]['value'] <= $end)) {
                    $return[] = $event;
                }
            }
            return $return;
        }
    }
    
    /**
     * Returns true if the current calendar has events or false if it does not
     *
     * @return {boolean}
     */
    public function hasEvents() 
    {
        return ( count($this->events()) > 0 ? true : false );
    }
    
    /**
     * Sorts an array of events
     * 
     * This is a rough sort that compares date-time strings. For a more
     * accurate sort that uses Unix times, see class.iCalParser.php
     *
     * @param {array} &$events   An array with events.
     * @param {array} $sortKey   Which date-time to sort by (DTSTART, DTEND, DTSTAMP)
     * @param {array} $sortOrder Either SORT_ASC or SORT_DESC
     */
    public function sortEvents (&$events, $sortKey = "DTSTART", $sortOrder = SORT_ASC)
    {
        if ($sortOrder !== SORT_ASC && $sortOrder !== SORT_DESC) {
            // todo: set error
            return;
        }
        
        $evDTstamp = array();
        foreach ($events as $event) {
            switch ($sortKey) {
            case "DTSTAMP":
                $dt = $event["DTSTAMP"]["value"];
                break;
            case "DTEND":
                if (isset($event["DTEND"])) {
                    $dt = $event["DTEND"]["value"];
                    break;
                }
            case "DTSTART":
            default:
                $dt = $event["DTSTART"]["value"];
                break;
            }
            $evDTstamp[$event["UID"]["value"]] = $dt;
        }
        
        array_multisort($evDTstamp, $sortOrder, $events);
    }
    
    /**
     * Checks whether or not a keyword permit multiple values over multiple lines
     *
     * @param {string} $section The Section the Keyword is contained within
     * @param {string} $keyword The Keyword being looked into
     *
     * @return {boolean}
     */
    private function _mlmvCheck ($section, $keyword)
    {
        // convert to uppercase
        $section = strtoupper($section);
        $keyword = strtoupper($keyword);
        
        // Special cases
        if ($section == "VALARM") {
            return ($keyword == "ATTACH");
        } else if ($section == "VTIMEZONE") {
            return false;
        }
        
        // check through array
        $mlmv_glob = in_array($keyword, $this::$mlmvKeys['glob']);
        $mlmv_some = isset($this::$mlmvKeys['some'][$keyword]) && in_array($section, $this::$mlmvKeys['some'][$keyword]);
        $mlmv_spec = isset($this::$mlmvKeys['spec'][$section]) && in_array($keyword, $this::$mlmvKeys['spec'][$section]);
        if ($mlmv_glob || $mlmv_some || $mlmv_spec || substr($keyword, 0, 2) == "X-")
        {
            return true;
        }
        return false;
    }
    
    /**
     * Checks whether or not a keyword permits multiple values over multiple lines
     * 
     * @param {string} $keyword The keyword being checked
     * 
     * @return {boolean}
     */
    private function _slmvCheck ($keyword)
    {
        return in_array($keyword, $this::$slmvKeys);
    }
    
    /**
     * Explodes apart values from a single string, taking into account the
     *   possibility of an escaped comma.
     * 
     * @params {string} $values The string containing the values separated by commas
     * 
     * @return {array}
     */
    private function _slmvExplode ($values)
    {
        $exploded = explode(",", $values);
        
        $values = array();
        
        for ($v=0; $v<count($exploded); $v++) {
            $newValue = $exploded[$v];
            while (substr($newValue, -1) == "\\") {
                $v++;
                $newValue .= "," . $exploded[$v];
            }
            $values[] = trim($newValue);
        }
        return $values; 
    }
    
} 
?>

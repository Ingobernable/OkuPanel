<?php

// see https://github.com/s0600204/ics-parser/blob/master/example.php


class Okupanel_interface_ics extends Okupanel_interface {

	function get_label(){
		return __('iCalendar (Recommended, via .ics)', 'okupanel');
	}
	
	function print_config($i){
		?>
		<h3><?= __('iCalendar setup instructions', 'okupanel') ?>:</h3>
		<div class="okupanel-field">
			<label><?= __('iCalendar URLs', 'okupanel') ?>:</label>
			<div class="okupanel-field-inner">
				<div><textarea style="height: 120px" name="okupanel_ical_url"><?= esc_textarea(get_option('okupanel_ical_url', '')) ?></textarea></div>
				<div><?= __('iCalendar URLs end up in ".ics". All URLs in this field will be fetched and merged. Other content will be ignored (use it as comments ;)).', 'okupanel') ?></div>
			</div>
		</div>
		<?php
	}
	
	function save_config(){
		update_option('okupanel_ical_url', trim(sanitize_textarea_field(@$_POST['okupanel_ical_url'])));
	}
	
	function fetch_events(){
		if (($urls = trim(get_option('okupanel_ical_url', ''))) == '')
			return false;
		
		require_once OKUPANEL_PATH.'/inc/lib/ics-parser-master/class.iCalReader.php';
		$base = WP_CONTENT_DIR.'/cache';

		$ret = array();
		if (preg_match_all('#https?://[^\s]+#ius', $urls, $matches)){
			foreach ($matches[0] as $url){
				$url = esc_url($url);
				if (empty($url))
					continue;
				
				if (!($content = okupanel_fetch($url, false, 'get', array(), array(), 10, !OKUPANEL_CALENDAR_CHECK_SSL)))
					return false;
				
				if (!wp_mkdir_p($base))
					return false;

				@unlink($base.'/ics-cache.ics');
				if (!file_put_contents($base.'/okupanel-cache.ics', $content))
					return false;
				
				$ical = new ICal($base.'/okupanel-cache.ics');

				@unlink($base.'/ics-cache.ics');
				
				if (!$ical)
					return false;
				if (!($events = $ical->getEvents()))
					return false;

				$offset = intval(get_option('gmt_offset', 0) * HOUR_IN_SECONDS);

				foreach ($events as $e){
		//			echo '<br><br><br>';
		//			print_r($e);

					if (!empty($e['CLASS']['value']) && @$e['CLASS']['value'] !== 'PUBLIC')
						continue;
						
					$event = array(
						'origin' => $url,
						'id' => trim(sanitize_text_field(@$e['UID']['value'])),
						'summary' => trim(sanitize_text_field(@$e['SUMMARY']['value'])),
						'description' => trim(sanitize_text_field(@$e['DESCRIPTION']['value'])),
						'location' => trim(sanitize_text_field(@$e['LOCATION']['value'])),
						'status' => strtolower(sanitize_text_field(@$e['STATUS']['value'])),
						'created' => strtotime(sanitize_text_field(@$e['DTSTAMP']['value'])),
						'start' => strtotime(sanitize_text_field(@$e['DTSTART']['value'])),
						'end' => strtotime(sanitize_text_field(@$e['DTEND']['value'])),
						'updated' => strtotime(sanitize_text_field(@$e['LAST-MODIFIED']['value'])),
						'htmlLink' => null,//preg_replace('#^(.*?)(\.ics)$#i', '$1.html', $url).'?view=month&action=view&invId='.trim(sanitize_text_field(@$e['UID']['value'])).'&pstat=AC&useInstance=1',//&instStartTime=1504706400000&instDuration=7200000
						'recurrence' => sanitize_text_field(@$e['RRULE']['value']),
					);
					
					// set gmts
					foreach (array('start', 'end', 'updated', 'created') as $k)
						if (empty($event[$k]))
							$event[$k.'_gmt'] = $event[$k] = null;
						else 
							$event[$k.'_gmt'] = $event[$k] - $offset;
							
					foreach (array('recurrence') as $k)
						if (empty($event[$k]))
							$event[$k] = null;
						
					if (!empty($event['description']))
						$event['description'] = trim(str_replace('\n', " ", $event['description']));
					if (empty($event['description']) || preg_match('#^reminders?$#iu', $event['description']))
						$event['description'] = null;
						
					// TODO: recurrence missing
		//			echo date_i18n('Y-m-d H:i:s', $event['created']);
		//			echo '<br><br><br>';

					$ret[] = $event;
				}
			}
		}
		
		
		// clean doublons (by start+title)
		$clean = array();
		foreach ($ret as $e){
			$uid = $e['start'].($e['end'] ? '-'.$e['end'] : '').'-'.@sanitize_title($e['summary']).'-'.@sanitize_title($e['description']);
			if (!isset($clean[$uid]))
				$clean[$uid] = $e;
		}
		
		return array_values($clean);
	}
	
}

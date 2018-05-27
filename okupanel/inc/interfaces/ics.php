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
				<div><?= __('iCalendar URLs end up in ".ics". All URLs in this field will be fetched and merged. Other content will be ignored (use it as comments!), except strings like " ROOM=Any Room" following URLs (which will force a location for all the feed\'s events) and strings like " OFFSET=1.5" following URLs (which will add an offset to all the feed\'s events, in hours).', 'okupanel') ?></div>
			</div>
		</div>
		<?php
	}

	function save_config(){
		update_option('okupanel_ical_url', trim(sanitize_textarea_field(@$_POST['okupanel_ical_url'])));
	}
	
	function fetch_events($month_time = null){
		if (($urls = trim(get_option('okupanel_ical_url', ''))) == '')
			return false;

		require_once OKUPANEL_PATH.'/inc/lib/ics-parser-master/class.iCalReader.php';
		$base = WP_CONTENT_DIR.'/cache';

		$ret = array();
		if (preg_match_all('#(https?://[^\s]+)(\s+ROOM=([A-Z0-9\.,]+)[\\n|$]?)?(\s+OFFSET=([-+]?[0-9\.,]+))?#ius', $urls, $matches, PREG_SET_ORDER)){
			foreach ($matches as $line){
				$data = array();
				$url = $line[1];
				$room = !empty($line[3]) ? $line[3] : null;
				$offset_all_items = !empty($line[5]) ? floatval(str_replace(',', '.', $line[5])) * HOUR_IN_SECONDS : 0;

				$url = esc_url($url);
				if (empty($url))
					continue;
					
				$format = 'ics';
				$ext = 'ics';
					
				// fetch back in time
				if ($month_time){
					
					if (preg_match('#^([hH][tT][tT][pP][sS]?://.*/home/([^/]+)/Calendar)\.ics(?:[\?\#].*)?$#', $url, $m)){
						// only for zimbra
						
						$url = $m[1].'.json';
						$data['end'] = date('Y/m/d', strtotime(date('Y-m', strtotime('+35 days', $month_time)).'-01'));
						$format = 'zimbra_json';
						$ext = 'json';
						//#debug echo 'fetching zimbra API '.$url.' ('.$data['end'].')<br>';
					
					} else {
						//#debug echo 'skipping '.$url.', not zimbra<br>';
						continue;
					}
				}

				if (!($content = okupanel_fetch($url, $ext == 'json', 'get', $data, array(), 10, !OKUPANEL_CALENDAR_CHECK_SSL))){
					//echo 'no content for '.$url.'<br>';
					return false;
				}
				
				$nevents = array();
				
				if ($format == 'zimbra_json'){
					if (!property_exists($content, 'appt')){
						//echo 'bad format: <br>';
						// print_r($content);

					} else
						foreach ($content->appt as $e){
							if (empty($e->inv[0]->comp[0]->name)){
								//#debug echo 'no name<br>';
								continue;
							}
							$nevent = array(
								'origin' => $url,
								'summary' => !empty($e->inv[0]->comp[0]->fr) ? $e->inv[0]->comp[0]->fr : $e->inv[0]->comp[0]->name,
								'description' => @$e->inv[0]->comp[0]->desc[0]->_content,
								'htmlLink' => @$e->inv[0]->comp[0]->url,
								'location' => @$e->inv[0]->comp[0]->loc,
								'status' => $e->inv[0]->comp[0]->status,
								
								'start' => strtotime($e->inv[0]->comp[0]->s[0]->d),
								'end' => strtotime($e->inv[0]->comp[0]->e[0]->d),
								
								/*
								'id' => trim(sanitize_text_field(@$e['UID']['value'])),
								'summary' => trim(sanitize_text_field(okupanel_stripslashes(@$e['SUMMARY']['value']))),
								'description' => trim(sanitize_text_field(okupanel_stripslashes(@$e['DESCRIPTION']['value']))),
								'location' => ($room || !okupanel_clean_room(@$e['LOCATION']['value'], $found) || !$found ? $room : (isset($e['LOCATION']) && !empty($e['LOCATION']['value']) ? trim(sanitize_text_field(stripslashes($e['LOCATION']['value']))) : null)),
								'status' => isset($e['STATUS']) && !empty($e['STATUS']['value']) ? strtolower(sanitize_text_field($e['STATUS']['value'])) : 'confirmed',
								'created' => okupanel_parse_time(sanitize_text_field(@$e['DTSTAMP']['value'])),
								'created_gmt' => okupanel_parse_time(sanitize_text_field(@$e['DTSTAMP']['value']), true),
								'start' => okupanel_parse_time(sanitize_text_field(@$e['DTSTART']['value'])),
								'start_gmt' => okupanel_parse_time(sanitize_text_field(@$e['DTSTART']['value']), true),
								'end' => okupanel_parse_time(sanitize_text_field(@$e['DTEND']['value'])),
								'end_gmt' => okupanel_parse_time(sanitize_text_field(@$e['DTEND']['value']), true),
								'updated' => okupanel_parse_time(sanitize_text_field(@$e['LAST-MODIFIED']['value'])),
								'updated_gmt' => okupanel_parse_time(sanitize_text_field(@$e['LAST-MODIFIED']['value']), true),
								'htmlLink' => isset($e['URL']) && !empty($e['URL']['value']) ? trim(sanitize_text_field($e['URL']['value'])) : null,//preg_replace('#^(.*?)(\.ics)$#i', '$1.html', $url).'?view=month&action=view&invId='.trim(sanitize_text_field(@$e['UID']['value'])).'&pstat=AC&useInstance=1',//&instStartTime=1504706400000&instDuration=7200000
								* */
								'recurrence' => null,//sanitize_text_field(@$e['RRULE']['value']),
								'exceptions' => array(),
							);
							$nevent['start_gmt'] = $nevent['start'];
							$nevent['end_gmt'] = $nevent['end'];
							$nevents[] = $nevent;
						}
	
				} else if ($format == 'ics'){

					// force UTF8
					$content = iconv(mb_detect_encoding($content, mb_detect_order(), true), "UTF-8", $content);

					if (!wp_mkdir_p($base))
						return false;

					if (!preg_match('#.*BEGIN:VEVENT.*#', $content))
						continue;
						
					$content = str_replace('&amp\\;', '&', $content);
						
					@unlink($base.'/ics-cache.'.$ext);
					if (!file_put_contents($base.'/okupanel-cache.'.$ext, $content))
						return false;

					$ical = new ICal($base.'/okupanel-cache.'.$ext);

					@unlink($base.'/ics-cache.'.$ext);

					if (!$ical)
						continue;
						
					if (!($events = $ical->getEvents()))
						continue;
						
					foreach ($events as $e){

						if (!empty($e['CLASS']['value']) && @$e['CLASS']['value'] !== 'PUBLIC')
							continue;

						if (!empty($e['STATUS']['value']) && @$e['STATUS']['value'] !== 'CONFIRMED')
							continue;
							
						$nevent = array(
							'origin' => $url,
							'id' => trim(sanitize_text_field(@$e['UID']['value'])),
							'summary' => trim(sanitize_text_field(okupanel_stripslashes(@$e['SUMMARY']['value']))),
							'description' => trim(sanitize_text_field(okupanel_stripslashes(@$e['DESCRIPTION']['value']))),
							'location' => ($room || !okupanel_clean_room(@$e['LOCATION']['value'], $found) || !$found ? $room : (isset($e['LOCATION']) && !empty($e['LOCATION']['value']) ? trim(sanitize_text_field(stripslashes($e['LOCATION']['value']))) : null)),
							'status' => isset($e['STATUS']) && !empty($e['STATUS']['value']) ? strtolower(sanitize_text_field($e['STATUS']['value'])) : 'confirmed',
							'created' => okupanel_parse_time(sanitize_text_field(@$e['DTSTAMP']['value'])),
							'created_gmt' => okupanel_parse_time(sanitize_text_field(@$e['DTSTAMP']['value']), true),
							'start' => okupanel_parse_time(sanitize_text_field(@$e['DTSTART']['value'])),
							'start_gmt' => okupanel_parse_time(sanitize_text_field(@$e['DTSTART']['value']), true),
							'end' => okupanel_parse_time(sanitize_text_field(@$e['DTEND']['value'])),
							'end_gmt' => okupanel_parse_time(sanitize_text_field(@$e['DTEND']['value']), true),
							'updated' => okupanel_parse_time(sanitize_text_field(@$e['LAST-MODIFIED']['value'])),
							'updated_gmt' => okupanel_parse_time(sanitize_text_field(@$e['LAST-MODIFIED']['value']), true),
							'htmlLink' => isset($e['URL']) && !empty($e['URL']['value']) ? trim(sanitize_text_field($e['URL']['value'])) : null,//preg_replace('#^(.*?)(\.ics)$#i', '$1.html', $url).'?view=month&action=view&invId='.trim(sanitize_text_field(@$e['UID']['value'])).'&pstat=AC&useInstance=1',//&instStartTime=1504706400000&instDuration=7200000
							'recurrence' => sanitize_text_field(@$e['RRULE']['value']),
							'exceptions' => array(),
						);

						if (!empty($e['EXDATE']))
							foreach ($e['EXDATE'] as $e2)
								foreach ($e2['value'] as $e3)
									$nevent['exceptions'][] = strtotime($e3);
						$nevents[] = $nevent;
					}
				}

				//#debug if ($month_time)
					//#debug echo count($nevents).' EVENTS FOR '.$url.' ON '.date('Y-m', $month_time).'<br>';
								
				foreach ($nevents as $event){

					// set gmts
					foreach (array('start', 'end', 'updated', 'created') as $k){
						if (empty($event[$k]))
							$event[$k.'_gmt'] = $event[$k] = null;
						
						if (isset($event[$k]) && $event[$k] !== null)
							$event[$k] += $offset_all_items;
						if (isset($event[$k.'_gmt']) && $event[$k.'_gmt'] !== null)
							$event[$k.'_gmt'] += $offset_all_items;
					}
					
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
					//echo '+';
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


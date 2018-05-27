<?php


class Okupanel_interface_gcalendar extends Okupanel_interface {
	
	function get_label(){
		return __('Google Calendar (Evil, via API)', 'okupanel');
	}

	function print_config($i){
		?>
		<h3><?= __('Google Calendar setup instructions', 'okupanel') ?>:</h3>
		<div class="okupanel-field">
			<div class="okupanel-field-inner">
				<?= ($i++) ?>. <?= sprintf(__('Create a new project at %s', 'okupanel'), '<a href="https://console.developers.google.com/apis/credentials/oauthclient" target="_blank">'.__('the Google Calendar API Console', 'okupanel').'</a>') ?>
			</div>
		</div>
		<div class="okupanel-field">
			<div class="okupanel-field-inner">
				<?= ($i++) ?>. <?= __('Click "Enable API"', 'okupanel') ?>
			</div>
		</div>
		<div class="okupanel-field">
			<div class="okupanel-field-inner">
				<?= ($i++) ?>. <?= __('Click Oauth Client ID', 'okupanel') ?>
			</div>
		</div>
		<div class="okupanel-field">
			<div class="okupanel-field-inner">
				<?= ($i++) ?>. <?= __('Select "Web", add the following redirect URI and save', 'okupanel') ?>: <br/><input type="text" readonly="readonly" value="<?= esc_attr(admin_url('admin-ajax.php?action=okupanel&target=gcal_auth_cb')) ?>" onclick="jQuery(this).focus().select(); return false;" style="width: 400px; margin: 5px 0 0 14px" />
			</div>
		</div>
		<div class="okupanel-field">
			<div class="okupanel-field-inner">
				<?= ($i++) ?>. <?= __('Copy the App ID and App Secret below', 'okupanel') ?>:
			</div>
		</div>
		<div class="okupanel-field okupanel-settings-field">
			<label><?= __('Google Calendar App ID', 'okupanel') ?>:</label>
			<div class="okupanel-field-inner">
				<div><input type="text" name="okupanel_gcal_app_id" value="<?= esc_attr(get_option('okupanel_gcal_app_id', '')) ?>" /></div>
			</div>
		</div>
		<div class="okupanel-field okupanel-settings-field">
			<label><?= __('Google Calendar App Secret', 'okupanel') ?>:</label>
			<div class="okupanel-field-inner">
				<div><input type="text" name="okupanel_gcal_app_secret" value="<?= esc_attr(get_option('okupanel_gcal_app_secret', 'primary')) ?>" /></div>
			
			</div>
		</div>
		<div class="okupanel-field okupanel-settings-field">
			<label><?= __('Google Calendar ID', 'okupanel') ?>:</label>
			<div class="okupanel-field-inner">
				<div><input type="text" name="okupanel_gcal_id" value="<?= esc_attr(get_option('okupanel_gcal_id', 'primary')) ?>" /></div>
				<div><?= __('This setting is commonly "primary" or the proper account email. Calendar IDs can be found in each calendar\'s settings page.', 'okupanel') ?></div>
			</div>
		</div>
		<div class="okupanel-field">
			<?= ($i++) ?>. <input type="submit" name="okupanel_submit" value="<?= esc_attr(__('Save the settings', 'okupanel')) ?>" />
		</div>
		<div class="okupanel-field">
			<div class="okupanel-field-inner">
				<?= ($i++) ?>. <a href="<?= admin_url('admin-ajax.php?action=okupanel&target=gcal_auth') ?>" target="_blank"><?= __('Authenticate your account with Google Calendar clicking here', 'okupanel') ?></a><?php 
					if ($service = okupanel_gcal_get_service())
						echo ' <span style="color: green">&rarr; '.__('Google Calendar is synchronized!', 'okupanel').'</span>';
					else
						echo ' <span style="color: #777">&rarr; '.__('Google Calendar is not synchronized (yet)', 'okupanel').'</span>';
				?>
			</div>
		</div>
		<?php
		return $i;
	}
	
	function save_config(){
		update_option('okupanel_gcal_app_id', trim(sanitize_text_field(stripslashes(@$_POST['okupanel_gcal_app_id']))));
		update_option('okupanel_gcal_app_secret', trim(sanitize_text_field(stripslashes(@$_POST['okupanel_gcal_app_secret']))));
		update_option('okupanel_gcal_id', trim(sanitize_text_field(stripslashes(@$_POST['okupanel_gcal_id']))));
	}
	
	function fetch_events(){
		if (!($service = okupanel_gcal_get_service()))
			return false;
			
		$cal_id = get_option('okupanel_gcal_id', 'primary');
		
		$cal_events = null;
		try {
			$cal_events = $service->events->listEvents($cal_id, array('orderBy' => 'startTime', 'timeMin' => date('Y-m-d\TH:i:s\Z'), 'singleEvents' => true));

		} catch (Exception $e){
			return false;
		}
		
		if (!$cal_events)
			return false;
			
		// fill recurrence
		foreach ($cal_events as $e)
			if (empty($rec->recurrence) && !empty($e->recurringEventId) && ($rec = $service->events->get($cal_id, $e->recurringEventId)) && $rec->recurrence)
				$e->recurrence = $rec->recurrence;
		
		$events = array();
		$printed = false;
		foreach ($cal_events as $e){
			if (!$e->start || ($e->end->dateTime ? (strtotime($e->end->dateTime) < time()) : (strtotime($e->start->dateTime) < strtotime('-2 hours'))))
				continue;
			
			// for debugging
			if (current_user_can('manage_options') && !empty($_GET['okupanel_debug']))
				echo htmlentities(print_r($e, true)).'<br><br>';
			$printed = true;
				
			$e = array(
				'id' => $e->id,
				'start' => $e->start ? okupanel_parse_time($e->start->dateTime) : null,
				'start_gmt' => $e->start ? okupanel_parse_time($e->start->dateTime, true) : null,
				'end' => $e->end ? okupanel_parse_time($e->end->dateTime) : null,
				'end_gmt' => $e->end ? okupanel_parse_time($e->end->dateTime, true) : null,
				'created' => $e->created ? okupanel_parse_time($e->created) : null,
				'created_gmt' => $e->created ? okupanel_parse_time($e->created, true) : null,
				'updated' => $e->updated ? okupanel_parse_time($e->updated) : null,
				'updated_gmt' => $e->updated ? okupanel_parse_time($e->updated, true) : null,
				'status' => $e->status,
				'summary' => trim(sanitize_text_field($e->summary)),
				'description' => trim(sanitize_text_field($e->description)),
				'location' => $e->location,
				'htmlLink' => $e->htmlLink,
				'recurrence' => $e->recurrence,
			);
			$events[] = $e;
		}
		return $events;
	}
	
}

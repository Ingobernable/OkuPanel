<?php

if (!defined('ABSPATH'))
	exit();


add_action('admin_menu', 'okupanel_add_pages');
function okupanel_add_pages() {
	if (current_user_can('manage_options')){
		add_submenu_page(
			'options-general.php',
			__('OkuPanel Settings', 'okupanel'),
			'OkuPanel',
			'manage_options',
			'okupanel-settings',
			'okupanel_settings_page'
		);
	}
}

function okupanel_settings_page(){
	if (!current_user_can('manage_options'))
		wp_die();

	if (!empty($_POST['okupanel_submit']) && isset($_POST['okupanel_nonce']) && wp_verify_nonce($_POST['okupanel_nonce'], 'okupanel_settings')){

		$interfaces = okupanel_get_interfaces();
		$type = sanitize_text_field(@$_POST['okupanel_cal_type']);
		if ($type == '' || isset($interfaces[$type])){
			update_option('okupanel_cal_type', $type);
			if ($interfaces[$type]!==null) {
				$interfaces[$type]->save_config();
			}
		}

		$allowed = array(
			'class' => array(),
			'id' => array(),
			'style' => array()
		);

		foreach (apply_filters('okupanel_textline_fields_raw', array()) as $k)
			update_option('okupanel_'.$k, trim(wp_kses(stripslashes($_POST['okupanel_'.$k]), array(
			))));

		foreach (apply_filters('okupanel_cb_fields', array()) as $k)
			update_option('okupanel_'.$k, !empty($_POST['okupanel_'.$k]) && $_POST['okupanel_'.$k] !== 'false' ? 1 : 0);
			
		foreach (apply_filters('okupanel_textline_fields', array('intro', 'links_label', 'address')) as $k)
			update_option('okupanel_'.$k, trim(wp_kses(stripslashes($_POST['okupanel_'.$k]), array(
				'strong' => $allowed,
				'span' => $allowed,
				'ul' => $allowed,
				'br' => array(),
				'i' => $allowed,
				'img' => $allowed + array('src' => array()),
			))));

		foreach (apply_filters('okupanel_html_fields_1', array('most_important')) as $k)
			update_option('okupanel_'.$k, trim(wp_kses(stripslashes($_POST['okupanel_'.$k]), array(
				'strong' => $allowed,
				'span' => $allowed,
				'br' => array(),
				'i' => $allowed,
			))));

		update_option('okupanel_page_ids', trim(sanitize_text_field($_POST['okupanel_page_ids'])));

		foreach (apply_filters('okupanel_html_fields_2', array('right_panel', 'bottombar')) as $k)
			update_option('okupanel_'.$k, trim(wp_kses(stripslashes($_POST['okupanel_'.$k]), array(
				'strong' => $allowed,
				'span' => $allowed,
				'a' => $allowed + array(
					'href' => array(),
					'title' => array(),
					'target' => array(),
				),
				'br' => array(),
				'div' => $allowed,
				'ul' => $allowed,
				'li' => $allowed,
				'u' => $allowed,
				'i' => $allowed,
				'img' => $allowed + array('src' => array()),
				'style' => array(),
			))));

		update_option('okupanel_panel_css', wp_strip_all_tags(trim(stripslashes($_POST['okupanel_panel_css']))));
		update_option('okupanel_panel_js', wp_strip_all_tags(trim(stripslashes($_POST['okupanel_panel_js']))));
		
		$mods = array();
		foreach (okupanel_lsdir(OKUPANEL_PATH.'/mods') as $file){ 
			if (preg_match('#^([a-z_0-9]+)\.php$#i', $file, $m))
				$file = $m[1];
			else
				continue;
			if (!empty($_POST['okupanel_mods_'.$file]))
				$mods[] = $file;
		}
		update_option('okupanel_mods', implode(',', $mods));

		do_action('okupanel_save_settings');

		delete_option('okupanel_events_cache');
	}

	echo '<h3>'.__('OkuPanel settings', 'okupanel').'</h3>';

	$i = 1;
	?>
	<script type="text/javascript">
		jQuery(document).ready(function(){
			jQuery('.okupanel-settings-calendar-type').change(function(){
				var fs = jQuery('.okupanel-interface-fields');
				var f = fs.filter('.okupanel-interface-fields-type-'+jQuery(this).val());
				fs.not(f).hide();
				f.show();
			});
		});
	</script>
	<style>
		.okupanel-interface-fields-hidden {
			display: none;
		}
	</style>
	<form action="<?= admin_url('options-general.php?page=okupanel-settings') ?>" method="POST">
		<?php $val = get_option('okupanel_cal_type', ''); ?>
		<div class="okupanel-field">
			<label><?= __('Select your calendar type', 'okupanel') ?></label>
			<div class="okupanel-field-inner">
				<select name="okupanel_cal_type" class="okupanel-settings-calendar-type">
					<option value=""><?= __('Select a calendar type', 'okupanel') ?>..</option>
					<?php
					foreach (okupanel_get_interfaces() as $obj)
						echo '<option value="'.$obj->id.'"'.($obj->id == $val ? ' selected' : '').'>'.$obj->get_label().'</option>';
					?>
				</select>
			</div>
		</div>
		<?php
			foreach (okupanel_get_interfaces('objects') as $obj){
				?>
				<div class="okupanel-field okupanel-interface-fields okupanel-interface-fields-type-<?= $obj->id ?><?php if ($obj->id != $val) echo ' okupanel-interface-fields-hidden'; ?>">
					<?php $obj->print_config(1); ?>
				</div>
				<?php
			}
		?>
		<h3>Panel URLs:</h3>
		<div class="okupanel-field okupanel-settings-field">
			<div class="okupanel-field-inner">
				<div><?= sprintf(__('Your panel is displayed at %s', 'okupanel'), '<a href="'.site_url('okupanel/').'" target="_blank">'.site_url('okupanel/').'</a>') ?></div>
				<div><?= sprintf(__('To use it as an entrance screen (a kiosk), open %s and turn the window fullscreen (commonly F11) or follow the kiosk installation instructions from %s.', 'okupanel'), '<a href="'.site_url('okupanel/?fullscreen=1&moving=1').'" target="_blank">'.site_url('okupanel/?fullscreen=1&moving=1').'</a>', '<a href="https://wordpress.org/okupanel" target="_blank">'.__('the plugin\'s page', 'okupanel').'</a>') ?></div>
				<div><?= sprintf(__('iCal link to be used in calendar clients: %s'), '<a href="'.site_url('okupanel/ics/').'" target="_blank">'.site_url('okupanel/ics/').'</a>' ) ?></div>
			</div>
		</div>
		<br/>

		<h3><?= __('Theming settings', 'okupanel') ?>:</h3>

		<div class="okupanel-field okupanel-settings-field">
			<label><?= __('Panel\'s title', 'okupanel') ?></label>
			<div class="okupanel-field-inner">
				<div><textarea name="okupanel_intro"><?= esc_textarea(get_option('okupanel_intro', 'OkuPanel')) ?></textarea></div>
			</div>
		</div>

		<div class="okupanel-field okupanel-settings-field">
			<label><?= __('Right side\'s HTML', 'okupanel') ?></label>
			<div class="okupanel-field-inner">
				<div><textarea name="okupanel_right_panel"><?= esc_textarea(get_option('okupanel_right_panel', '')) ?></textarea></div>
				<div><?= __('Available shortcodes are <code>[okupanel_line label="Web" url="https://example.com" link_label="Some label"]</code>, <code>[okupanel_separator]</code> and <code>[okupanel_most_important]</code>.', 'okupanel') ?></div>
			</div>
		</div>

		<?php do_action('okupanel_print_extra_textarea_fields_1'); ?>

		<div class="okupanel-field okupanel-settings-field">
			<label><?= __('Bottom bar content', 'okupanel') ?></label>
			<div class="okupanel-field-inner">
				<div><textarea name="okupanel_bottombar"><?= esc_textarea(get_option('okupanel_bottombar', '')) ?></textarea></div>
				<div><?= __('Each (non-empty) line will be considered as one sentence in the bottom bar.', 'okupanel') ?></div>
			</div>
		</div>

		<div class="okupanel-field okupanel-settings-field">
			<label><?= __('Links menu label (for mobiles)', 'okupanel') ?></label>
			<div class="okupanel-field-inner">
				<div><textarea name="okupanel_links_label"><?= esc_textarea(get_option('okupanel_links_label', __('Links', 'okupanel'))) ?></textarea></div>
			</div>
		</div>

		<div class="okupanel-field okupanel-settings-field">
			<label><?= __('Default location', 'okupanel') ?></label>
			<div class="okupanel-field-inner">
				<div><input name="okupanel_address" value="<?= esc_attr(get_option('okupanel_address', '')) ?>" /></div>
			</div>
		</div>

		<?php do_action('okupanel_print_extra_textline_fields'); ?>

		<div class="okupanel-field okupanel-settings-field">
			<label><?= __('Extra CSS', 'okupanel') ?></label>
			<div class="okupanel-field-inner">
				<div><textarea name="okupanel_panel_css"><?= esc_textarea(get_option('okupanel_panel_css', '')) ?></textarea></div>
			</div>
		</div>

		<div class="okupanel-field okupanel-settings-field">
			<label><?= __('Extra Javascript', 'okupanel') ?></label>
			<div class="okupanel-field-inner">
				<div><textarea name="okupanel_panel_js"><?= esc_textarea(get_option('okupanel_panel_js', '')) ?></textarea></div>
			</div>
		</div>

		<?php do_action('okupanel_print_extra_textarea_fields_2'); ?>

		<div class="okupanel-field okupanel-settings-field">
			<label><?= __('Autodetected events', 'okupanel') ?>:</label>
			<div class="okupanel-field-inner">
				<div><textarea name="okupanel_most_important"><?= esc_textarea(get_option('okupanel_most_important', '')) ?></textarea></div>
				<div><?= __('One line per event detection, starting with a regular expression delimited with #\'s, and then the title to use (if not using the event\'s own title). Use [okupanel_most_important] to show detected events in the sidebar.', 'okupanel') ?></div>
			</div>
		</div>

		<?php
			$enabled_mods = explode(',', str_replace(' ', '', get_option('okupanel_mods', '')));
		?>
		<div class="okupanel-field okupanel-settings-field">
			<label><?= __('Enable mods', 'okupanel') ?></label>
			<div class="okupanel-field-inner">
				<div style="margin-bottom: 10px">
					<?php foreach (okupanel_lsdir(OKUPANEL_PATH.'/mods') as $file){ 
						if (preg_match('#^([a-z_0-9]+)\.php$#i', $file, $m))
							$file = $m[1];
						else
							continue;
						?>
						<label for="okupanel_mods_<?= $file ?>"><input type="checkbox" id="okupanel_mods_<?= $file ?>" name="okupanel_mods_<?= $file ?>" <?php if (in_array($file, $enabled_mods)) echo 'checked '; ?>/> <?= ucfirst(str_replace('_', ' ', $file)) ?></label>
					<?php } ?>
				</div>
				<div><?= __('Mods are files dropped in the okupanel/mods folder, containing filters (output modifications) for specific events. See okupanel/mods/hackmeeting2017.php for an example (or enable it and add an event like "[1.99] title").', 'okupanel') ?></div>
			</div>
		</div>

		<div class="okupanel-field okupanel-settings-field">
			<label><?= __('Include styles in pages with ID', 'okupanel') ?>:</label>
			<div class="okupanel-field-inner">
				<div><input type="text" name="okupanel_page_ids" value="<?= esc_attr(get_option('okupanel_page_ids', '')) ?>" /></div>
				<div><?= __('Comma-separated page IDs to queue OkuPanel styles for. Useful when displaying events on a wordpress page with the <code>[okupanel]</code> shortcode.', 'okupanel') ?></div>
			</div>
		</div>

		<?php do_action('okupanel_print_extra_checkbox_fields'); ?>

		<div class="okupanel-field okupanel-settings-field">
			<div class="okupanel-field-inner">
				<div><input type="submit" name="okupanel_submit" value="<?= esc_attr(__('Save settings', 'okupanel')) ?>" /></div>
			</div>
		</div>

		<input type="hidden" name="okupanel_nonce" value="<?= esc_attr(wp_create_nonce('okupanel_settings')) ?>" />
	</form>
	<?php
}

function okupanel_print_panel(){

	$exts = $must_install = array();
	if (!function_exists('curl_init'))
		$exts['php-curl'] = 'cURL';
	if (!function_exists('mb_strtoupper'))
		$exts['php-mbstring'] = 'MultiBytes';
	if ($exts)
		$must_install[] = 'install the '.okupanel_plural($exts).' PHP '.(count($exts) > 1 ? 'extensions' : 'extension').' (sudo apt-get install '.implode(' ', array_keys($exts)).')';


	if (!is_dir(WP_CONTENT_DIR.'/cache'))
		$must_install[] = 'create a writable /wp-content/cache folder';
	else if (!is_writable(WP_CONTENT_DIR.'/cache'))
		$must_install[] = 'make the /wp-content/cache folder writable';

	if ($must_install){
		echo '<div style="color: white; background: red; padding: 20px; font-size: 20px">Please '.okupanel_plural($must_install).' in order for OkuPanel to work!</div>';
		return '';
	}

	if (!($events = okupanel_get_events()))
		return '';

	// format events to lines
	$trs = array();
	$is_first_tr = false;
	$col_span = 0;

	// for debug purpose
	if (current_user_can('manage_options') && !empty($_GET['okupanel_debug_final_events'])){
		echo '<br>FINAL EVENTS:<br>';
		foreach ($events as $e)
			if (preg_match('#.*candelas.*#ius', $e['summary'])){
				echo htmlentities(print_r($e, true));
			}
		//echo htmlentities(print_r($events, true)).'<br><br>';
	}

	$last_start = null;
	$header_after_id = null;
	$last_shown = null;

	usort($events, function($a, $b){
		if ($a['start'] != $b['start'])
			return $a['start'] > $b['start'] ? 1 : -1;
		if ($a['end'] != $b['end'])
			return $a['end'] > $b['end'] ? 1 : -1;
		return $a['summary'] > $b['summary'] ? 1 : -1;
	});

	foreach (array_values($events) as $events_i => $e){
		if ($e['end_gmt'] < time())
			continue;

		$is_featured = okupanel_is_featured($e);

		//if ($e['status'] == 'cancelled' && empty($_GET['show_cancelled']))
		//	continue;

		// day header line
		if (!$last_start || date('Y-m-d', $e['start_gmt']) != date('Y-m-d', $last_start)){
			$is_first_tr = !$is_featured && !$last_start;

			$class = '';
			$class .= $is_first_tr ? ' okupanel-tr-first' : ' okupanel-tr-not-first';

			if (date_i18n('Y-m-d', $e['start_gmt']) == date_i18n('Y-m-d')){
				$tr = '<span title="'.__('Today', 'okupanel').' '.date_i18n(__('l m/d', 'okupanel'), $e['start']).'">'.__('Today', 'okupanel');
				if (date_i18n('H:i') < '08:00')
					$tr .= ' ('.date_i18n('l').')';
				$tr .= '</span>';
				$class .= ' okupanel-today';

			} else if (date_i18n('Y-m-d', $e['start_gmt']) == date_i18n('Y-m-d', strtotime('+1 day')))
				$tr = '<span title="'.__('Tomorrow', 'okupanel').' '.date_i18n(__('l m/d', 'okupanel'), $e['start']).'">'.__('Tomorrow', 'okupanel').'</span>';

			else {
				$diff = $e['start'] - strtotime(date('Y-m-d').' 00:00:00');

				//if ($diff < 7 * DAY_IN_SECONDS)
				//	$tr = date_i18n('l', $e['start']);
				//else
					$tr = date_i18n(__('l m/d', 'okupanel'), $e['start']);
			}
			$trs[] = array('tds' => '', 'okupanel-tr-space-'.($is_first_tr ? 'first' : 'not-first')); // space

			$header_after_id = count($trs);
			$trs[$header_after_id] = array('tds' => $tr, 'class' => $class, 'after' => '');

			$last_start = $e['start_gmt'];
		}

		if ($is_featured){
			if ($header_after_id !== null)
				$trs[$header_after_id]['after'] = '<span class="okupanel-day-header-featured"><i class="fa fa-beer"></i> '.okupanel_lint_featured($is_featured).'</span>';
			continue;
		}

		$tr = array();
		$class = '';
		$started = $starting = false;

		//$tr['bla'] = date('Y-m-d H:i', $e['start']).' -> '.date('Y-m-d H:i', $e['end']).' / '.$e['recurrence'];

		$starting = false;
		if (!$e['start'])
			$tr['start'] = '';
		else {
			$diff = $e['start_gmt'] - time() + (OKUPANEL_TIME_OFFSET * MINUTE_IN_SECONDS);
			$tr['start'] = date_i18n('H:i', $e['start']);

			// started (bold)
			if ($diff < 0){
				$started = true;
				$class .= ' okupanel-started';
			}
			// starting (blinking)
			if (abs($diff) < OKUPANEL_CLIENT_STARTING_UPTO * MINUTE_IN_SECONDS){
				$class .= ' okupanel-starting';
				$starting = true;
			}
		}
		/* for testing propose only *
		if (count($trs) == 2)
			$started = true;
		else if (count($trs) == 3)
			$starting = true;
		*/

		if (empty($e['htmlLink']) && !empty($e['description']) && preg_match_all('#https?://[^\s]+#ius', $e['description'], $matches)){
			$e['htmlLinkFromDesc'] = true;
			$e['htmlLink'] = $matches[0][0];
			if (!preg_match('#^https?://#i', $e['htmlLink']))
				$e['htmlLink'] = 'http://'.$e['htmlLink'];
			$e['htmlLink'] = htmlentities($e['htmlLink']);

		}

		//$tr['icon'] = '<i class="fa fa-'.okupanel_get_icon($e['summary']).' okupanel-fixed-icon"></i>';
		$tr['summary'] = htmlentities(!empty($e['summary']) ? $e['summary'] : $e['description']);

		// allow mods
		$vars = array(
			'tr' => $tr,
			'e' => $e,
			'short_title' => $tr['summary'],
			'hashtag' => null,
			'hashtag_type' => null,
		);
		$vars = apply_filters('okupanel_detect_hashtag', $vars);
		extract($vars);

		if ($e['status'] != 'confirmed')
			$tr['summary'] .= ' <span class="okupanel-ind-custom-status">'.htmlentities(strtoupper($e['status'])).'</span>';

		if ($starting)
			$tr['summary'] .= ' <span class="okupanel-ind-new">'.__('Starting', 'okupanel').'</span>';

		if (!$hashtag){ // disable new and modified tags when a hashtag is present

			if (!empty($e['created']) && $e['created'] > strtotime('-'.OKUPANEL_CLIENT_EVENT_NEW.' days'))
				$tr['summary'] .= ' <span class="okupanel-ind-new">'.__('New', 'okupanel').'</span>';
			else if ($e['updated'] > strtotime(intval(date('N')) == 1 ? '-4 days' : '-2 days'))
				$tr['summary'] .= ' <span class="okupanel-ind-mod">'.__('Modified', 'okupanel').'</span>';
		}

		if ($e['recurrence']){
			$tr['summary'] .= ' <span class="okupanel-ind-recurrence">'.okupanel_human_recurrence($e).'</span>';
		}

		$show_desc = !empty($e['summary']) && !empty($e['description']);

		if ($show_desc)
			$tr['summary'] .= '<i class="fa fa-info-circle"></i>';
		if (!empty($e['htmlLinkFromDesc']) || !empty($e['htmlLink']))
			$tr['summary'] .= '<i class="fa fa-external-link-square"></i>';

		if (preg_match('#.*(fiesta|party|orquesta|'.preg_quote(__('party', 'okupanel'), '#').'|'.preg_quote(__('sound system', 'okupanel'), '#').'|'.preg_quote(__('concert', 'okupanel'), '#').').*#ium', $e['summary']))
			$tr['summary'] .= '<i class="fa fa-music"></i>';

		$play = $started
			? '<i class="fa fa-play okupanel-playing-ind"></i>'
			: (
				$starting
				? '<i class="fa fa-bullhorn okupanel-playing-ind"></i>'
				: ''
			);

		$title = rtrim(preg_replace('#^https?://(?:www\.)?(.*?)$#i', '$1', $e['htmlLink']), '/');

		if ($e['end']){
			$icon = '<i class="fa fa-long-arrow-right okupanel-fixed-icon"></i>';

			$diff = $e['end'] - $e['start'];
			if ($diff < DAY_IN_SECONDS)
				$until = date_i18n('H:i', $e['end']);
			else if ($diff < 7 * DAY_IN_SECONDS)
				$until = date_i18n('D', $e['end']).'. '.date('H:i', $e['end']);
			else
				$until = date_i18n(__('D m/d', 'okupanel'), $e['end']).'. '.date('H:i', $e['end']);

			//$tr['start'] = '<span>'.$tr['start'].'</span><span class="okupanel-ind-until"><i class="fa fa-long-arrow-right okupanel-fixed-icon"></i>'.$until.'</span>';
			//$tr['summary'] .= '<span class="okupanel-ind-until">'.$icon.sprintf(__('Until %s', 'okupanel'), $until).'</span>';
		}

		ob_start();
		?>
		<div class="okupanel-popup-content-intro">
			<div>
				<label><?= __('Beginning', 'okupanel') ?>:</label>
				<span><?= ucfirst(sprintf(__('%s at %s', 'okupanel'), date_i18n(__('l F jS, Y', 'okupanel'), $e['start']), date_i18n('G:i', $e['start']))) ?></span>
			</div>
			<div>
				<label><?= __('Duration', 'okupanel') ?>:</label>
				<span><?php
					echo okupanel_human_time_diff($e['end'] - $e['start'], true);
					echo ' (';
					if (date_i18n('Y-m-d', $e['start']) == date_i18n('Y-m-d', $e['end']))
						echo sprintf(_x('until %s', 'until a time', 'okupanel'), date_i18n('G:i', $e['end']));
					else
						echo sprintf(_x('until %s at %s', 'until a date and time', 'okupanel'), date_i18n(__('l F jS, Y', 'okupanel'), $e['end']), date_i18n('G:i', $e['end']));
					echo ')';
				?></span>
			</div>
			<?php if (($address = get_option('okupanel_address', false)) || !empty($e['location'])){ ?>
				<div>
					<label><?= __('Location', 'okupanel') ?>:</label>
					<span><?php
						if (!empty($e['location'])){
							if (preg_match('#^([B]\.?)$#i', $e['location']))
								echo __('Ground floor', 'okupanel');
							else {
								if (preg_match('#^([B0-9]+\..+)$#i', $e['location']))
									echo __('Room', 'okupanel').' ';
								else if (preg_match('#^([B0-9]+\.?)$#i', $e['location']))
									echo __('Floor', 'okupanel').' ';

								echo htmlentities(stripslashes($e['location']));
							}
						}
						if ($address)
							echo (!empty($e['location']) ? ' - ' : '').$address;
					?></span>
				</div>
			<?php }
			?>
			<div>
				<label><?= __('Frequency', 'okupanel') ?>:</label>
				<span><?= (!empty($e['recurrence']) ? okupanel_human_recurrence($e) : __('Single event', 'okupanel')) ?></span>
			</div>
		</div>
		<?php

		$after = apply_filters('okupanel_after_popup_content', '', $e, $hashtag, $hashtag_type);

		if ($show_desc || $after != ''){ ?>
			<div class="okupanel-popup-content-body">
				<?php
				if ($show_desc)
					echo nl2br(htmlentities(stripslashes(preg_replace('#https?://[^\s]+#ius', '<a href="$0" target="_blank">$0</a>', $e['description']))));
				echo $after;
				?>
			</div>
			<?php
		}
		$content = ob_get_clean();

		$linkTag = 'a class="okupanel-popup-link" href="'.(!empty($e['htmlLink']) ? $e['htmlLink'] : '#').'" target="_blank"';
		$linkTag .= ' title="'.esc_attr(htmlentities(wp_strip_all_tags($e['description']))).'"';

		$summary = $play.'<'.$linkTag;

		$tr_attr = ' data-okupanel-popup-link="'.(!empty($e['htmlLink']) ? esc_attr('(<a href="'.$e['htmlLink'].'" target="_blank">'.$title.'</a>)') : '').'"';

		$tr_attr .= ' data-okupanel-popup-title="'.esc_attr($short_title).'"';
		$tr_attr .= ' data-okupanel-popup-content="'.esc_attr($content).'"';

		$tr['summary'] = $summary.'>'.$tr['summary'].'</a>';

		$tr['duration'] = $e['end'] && $e['end'] != $e['start'] ? okupanel_human_time_diff($e['end'] - $e['start']) : '';

		//$tr['duration'] = '<'.$linkTag.'>'.$tr['duration'].'</a>';

		$tr['location'] = !empty($e['location']) ? $e['location'] : '';
		$title = !empty($e['olocation']) ? ' title="'.esc_attr($e['olocation']).'"' : '';

		if (strlen($tr['location']) > 10)
			$tr['location'] = '...';

		$tr['location'] = '<a'.$title.' '.$linkTag.'>'.$tr['location'].'</a>';

		$tr['start'] .= '<span class="okupanel-tr-mobile-metas">'.$tr['location'].'</span>';

		$class .= $is_first_tr ? ' okupanel-tr-first' : ' okupanel-tr-not-first';

		if ($last_shown && $last_shown == $e['start'])
			$class .= ' okupanel-start-same';
		else {
			$class .= ' okupanel-start-first';
			$last_shown = $e['start'];
		}

		// get next non-featured
		$next_i = $events_i+1;
		while ($next_i < count($events) && okupanel_is_featured($events[$next_i]))
			$next_i++;

		// if no next non-featured or next non-featured is a different time, close the table row
		if (!isset($events[$next_i]) || $events[$next_i]['start'] != $e['start'])
			$class .= ' okupanel-start-last';

		$trs[sanitize_title($e['origin']).'-'.$e['id']] = array('tds' => $tr, 'class' => $class, 'starting' => $starting, 'attr' => $tr_attr);
		$col_span = max($col_span, count($tr));

		//if (count($trs) > 100)
		//	break;
	}
	$trs[] = array('tds' => ''); // space

	do_action('okupanel_before_table');

	// print table
	echo '<table class="okupanel-table" border="0" cellpadding="0" cellspacing="0">';
		echo '<tr class="okupanel-table-headers"><th>';

		//if (!empty($_GET['fullscreen']))
		//	echo '<div class="okupanel-info-bubble"><div></div><i class="fa fa-chevron-down"></i>'.__('Horary', 'okupanel').'</div>';

		echo __('Horary', 'okupanel').'</th><th>'.__('Activity', 'okupanel');

		//echo '<span class="okupanel-ind-until-th">'.__('End', 'okupanel').'</span>

		echo '</th><th>'.__('Duration', 'okupanel').'</th><th>';

		//if (!empty($_GET['fullscreen']))
		//	echo '<div class="okupanel-info-bubble"><div></div><i class="fa fa-chevron-down"></i>'.__('Location', 'okupanel').'</div>';

		echo __('Floor.Room', 'okupanel').'</th></tr>';

		foreach (apply_filters('okupanel_tr', $trs) as $id => $tr){

			$tr += array(
				'class' => '',
				'starting' => false,
				'attr' => '',
			);
			echo '<tr class="okupanel-table-tr okupanel-table-tr-id-'.$id.' '.$tr['class'].'" '.$tr['attr'].'>';
			if (is_string($tr['tds']) || is_integer($tr['tds'])){
				echo '<td class="okupanel-table-td-'.($tr['tds'] == '' ? 'space' : 'center').'" colspan="'.$col_span.'">';
				if ($tr['tds'] != '')
					echo '<span class="okupanel-table-mobile-header okupanel-table-mobile-header-left">'.__('Horary', 'okupanel').'</span>';
				echo '<span class="okupanel-table-header-wrap">'.$tr['tds'].(!empty($tr['after']) ? $tr['after'] : '').'</span>';
				if ($tr['tds'] != '')
					echo '<span class="okupanel-table-mobile-header okupanel-table-mobile-header-right">'.__('Floor.Room', 'okupanel').'</span>';
				echo '</td>';
			} else {
//				echo '<td>'.print_r($tr,  true).'</td>';
				foreach (apply_filters('okupanel_tds', $tr['tds']) as $td_id => $td){
					echo '<td class="'.($tr['starting'] ? 'okupanel-starting-fading ' : '').'okupanel-table-td okupanel-table-td-id-'.$td_id.'">'.$td.'</td>';
				}
			}
			echo '</tr>';
		}
	echo '</table>';

	do_action('okupanel_after_table');
}

// EXPERIMENTAL: just a test putting icons depending on summary strings... but to too busy screen
function okupanel_get_icon($str){
	$icons = array(
		'cine' => 'cc-diners-club',
		'poesía' => 'bullhorn',
		'prensa' => 'rss',
		'perro' => 'paw',
		'sin\s*\-?\s*techo' => 'home',
		'vivienda' => 'home',
		'bici' => 'bicycle',
		'hacklab' => 'terminal',
		'intercambio' => 'exchange',
		'sikuri' => 'music',
		'music' => 'music',
		'concierto' => 'music',
		'asamblea' => 'users',
		'reunion' => 'users',
		'yoga' => 'yelp',
		'kapoeira' => 'flash',
		'boxeo' => 'flash',
	);
	foreach ($icons as $pat => $icon)
		if (preg_match('#('.$pat.')#iu', $str))
			return $icon;

	return 'globe';
}

function okupanel_clean_room($str, &$found = false){
	if ($ret = apply_filters('okupanel_pre_clean_room', false, $str)){
		$found = true;
		return $ret;
	}

	$str = trim(preg_replace('#\s*Calle\s*Gobernador[0-9\s]*(,?\s*Madrid)*(,?\s*Espa[ñn]a)?\s*#ius', '', $str)); // please adapt manually to your address

	$str = trim(preg_replace('#\s*(Madrid\b[/,\.\s]?)\s*#ius', ' ', $str)); // please adapt manually to your address
	$str = trim(preg_replace('#\s*(Espa[ñn]a\b[/,\.\s]?)\s*#ius', ' ', $str)); // please adapt manually to your address

	if (preg_match('#^\s*patio(\s*santorini)?\s*$#is', $str)){ // please adapt "patio...santorini" manually to your yard
		$found = true;
		return __('Yard', 'okupanel');
	}

	if (empty($str))
		return '';

	$parts = preg_split('#(\s*(/|'.preg_quote(__('and', 'okupanel'), '#').'|\+|,)\s*)#i', $str);
	if (count($parts) > 1){
		foreach ($parts as &$p)
			$p = okupanel_clean_room($p);
		/*usort($parts, function($a, $b){
			return floatval($a) == floatval($b) ? $a > $b : floatval($a) > floatval($b);
		});*/

		$found = true;
		return implode('/', $parts);
	}

	$str = rtrim(trim($str), '.');
	$str = preg_replace('#^'.preg_quote(__('ingoberlab', 'okupanel'), '#').'\s*(.*)$#iu', '$1', $str); // please adapt "ingoberlab" manually to your language
	if (preg_match('#^[\d\.]+$#', $str) && strlen($str) <= 4){
		$str = str_replace('.', '', $str);
		$str = $str[0].'.'.substr($str, 1);
	}
	if (strlen($str) < 4)
		$str = strtoupper($str);

	if (preg_match('#^B([0-9])?$#iu', $str, $m)){ // please adapt "B" manually to your language
		$str = 'B.'.(isset($str[1]) ? $str[1] : ''); // please adapt "B" manually to your language
		$found = true;
	}

	if (preg_match('#^([XYZ]+)$#i', $str))
		return '';

	if (preg_match('#^([KC]AFET(A|ERIA))$#iu', remove_accents($str))){ // please adapt "K...ERIA" manually to your language
		$str = __('Kafeta', 'okupanel');
		$found = true;
	}

	/*
	if (preg_match_all('#([0-9\.]+)#iu', $str, $m, PREG_SET_ORDER) && count($m) > 1){
		print_r($m[0]);
		foreach ($m[0] as &$c)
			$c = rtrim(trim($c), '.');
		if (count(array_unique($m[0])) == 1)
			$str = $m[0][0];
	}
	*/
	return apply_filters('okupanel_clean_room', $str);
}



function okupanel_convert_recurrence($e){
	$ee = $e;
	$recs = array();
	if (is_string($e))
		$e = array('#RRULE:'.$e);

	foreach ($e as $r){

		// parse
		$rec = array('FREQ' => null, 'BYDAY' => array(), 'INTERVAL' => 1);

		if (preg_match('#RRULE:([^:])*\bFREQ=([A-Z]+)($|;)#', $r, $m))
			$rec['FREQ'] = $m[2];

		if (preg_match('#RRULE:([^:])*\bBYDAY=([A-Z,]+)($|;)#', $r, $m))
			$rec['BYDAY'] = explode(',', $m[2]);

		if (preg_match('#RRULE:([^:])*\bINTERVAL=([0-9]+)($|;)#', $r, $m))
			$rec['INTERVAL'] = intval($m[2]);

		if (preg_match('#RRULE:([^:])*\bUNTIL=([0-9A-Z]+)($|;)#', $r, $m))
			$rec['UNTIL'] = strtotime($m[2]);

		$recs[] = $rec;
	}
	return $recs;
}

function okupanel_human_recurrence($e){
	$ldays = array('MO', 'TU', 'WE', 'TH', 'FR', 'SA', 'SU');
	$freqdays = array(
		__('Monday', 'okupanel'),
		__('Tuesday', 'okupanel'),
		__('Wednesday', 'okupanel'),
		__('Thursday', 'okupanel'),
		__('Friday', 'okupanel'),
		__('Saturday', 'okupanel'),
		__('Sunday', 'okupanel'),
	);
	$numbers = array(
		__('one', 'okupanel'),
		__('two', 'okupanel'),
		__('three', 'okupanel'),
		__('four', 'okupanel'),
		__('five', 'okupanel'),
		__('six', 'okupanel'),
		__('seven', 'okupanel'),
		__('eight', 'okupanel'),
		__('nine', 'okupanel'),
	);

	$str = array();
	$after = '';
	foreach (okupanel_convert_recurrence($e['recurrence']) as $rec){

		if (!empty($rec['UNTIL']) && $rec['UNTIL'] < time())
			continue;

		// transform

		$num = $rec['INTERVAL'] < count($numbers) ? $numbers[$rec['INTERVAL']-1] : $rec['INTERVAL'];

		if ($rec['FREQ'] == 'WEEKLY' && $rec['BYDAY']){

			$days = array();
			$monday = strtotime('this monday');
			foreach ($rec['BYDAY'] as $d)
				if (($i = array_search($d, $ldays)) !== false)
					$days[] = $freqdays[intval(date_i18n('N', $monday + ($i * DAY_IN_SECONDS)))-1];
				else
					$days[] = $d; // error, print through

			if ($rec['INTERVAL'] == 1)
				$str[] = sprintf(__('Every %s', 'okupanel'), okupanel_plural($days));
			else if (count($days) == 1)
				$str[] = sprintf(__('Every %s weeks', 'okupanel'), $num);
			else
				$str[] = sprintf(__('Every %s weeks on %s', 'okupanel'), $num, okupanel_plural($days));

		} else if ($rec['FREQ'] == 'MONTHLY'){

			if ($rec['INTERVAL'] == 1)
				$str[] = __('Every month', 'okupanel');
			else
				$str[] = sprintf(__('Every %s months', 'okupanel'), $num);
		}

		if (!empty($rec['UNTIL']) && $rec['UNTIL'] < strtotime('+2 months'))
			$after = ' '.sprintf(_x('until %s', 'until a date', 'okupanel'), date_i18n(__('l m/d', 'okupanel'), $rec['UNTIL']));

	}
	return okupanel_plural($str).$after;
}



add_shortcode('okupanel_separator', 'okupanel_separator');
function okupanel_separator($atts = array()){
	return '<div class="okupanel-network-sep'.(!empty($atts) && !empty($atts['last']) ? ' okupanel-network-sep-last' : '').'"></div>';
}

add_shortcode('okupanel_line', 'okupanel_line');
function okupanel_line($atts = array(), $content = ''){
	$atts += array(
		'label' => '',
		'url' => '',
		'link_label' => isset($atts['url']) ? $atts['url'] : '',
		'icon' => null,
	);
	ob_start();
	?>
	<div class="okupanel-network-wrap<?php if (!empty($atts['class'])) echo ' '.$atts['class']; if ($atts['icon']) echo ' okupanel-network-wrap-iconed'; ?>">
		<?php if ($atts['icon']){ ?>
			<i class="fa fa-<?= $atts['icon'] ?> okupanel-network-icon"></i>
		<?php } ?>
		<span class="okupanel-network"><?php echo $atts['label']; if (preg_match('#.*[a-z0-9]$#iu', $atts['label'])) echo ':'; ?> </span>
		<span class="okupanel-network-url">
			<?php
				$link = '<a href="'.esc_attr($atts['url']).'" target="_blank">'.$atts['link_label'].'</a>';
				echo !empty($content) ? sprintf($content, $link) : $link;
			?>
		</span>
	</div>
	<?php
	return ob_get_clean();
}

function okupanel_clock($is_ajax = false){
	return $is_ajax || !empty($_GET['fullscreen']) ? '<span class="okupanel-update-ind" title="'.esc_attr(sprintf(__('updated every %s min', 'okupanel'), OKUPANEL_FULLSCREEN_REFRESH_FREQUENCY)).'"><span class="okupanel-desynced-ind"><i class="fa fa-pause"></i></span><span class="okupanel-clock"></span></span>' : '';
}



function okupanel_add_room(&$e, $location, $summary, $is_location = false){
	if ($location != 'B' || !preg_match('#^B.*#ius', $e['location'])){

		$e['location'] = $is_location || empty($e['location']) || okupanel_clean_room($e['location']) == okupanel_clean_room($location) ? okupanel_clean_room($location) : okupanel_clean_room($e['location']).'/'.okupanel_clean_room($location);
	}

	if (!$is_location)
		$e['summary'] = trim($summary);

	$e['location'] = implode('/', array_unique(explode('/', $e['location'])));
}

function okupanel_lsdir($dir_path){
	$files = array();
	$folders = array();
	$dir = opendir($dir_path);
	while ($file = readdir($dir))
		if ($file != "." && $file != ".." && substr($file, -1) != "~"){ // avoid Unix temp files (ending in ~)
			if (is_file(rtrim($dir_path, '/').'/'.$file))
				$files[] = $file;
			else
				$folders[] = $file;
		}
	closedir($dir);
	return array_merge($folders, $files);
}

function okupanel_get_current_interface($type = 'object'){
	if (current_user_can('manage_options') && !empty($_GET['okupanel_interface_id']))
		$interface_id = sanitize_text_field($_GET['okupanel_interface_id']);
	else {
		$interface_id = get_option('okupanel_cal_type', '');
		if ($interface_id == '')
			return null;
	}
	return $type == 'object' ? okupanel_get_interfaces('objects', $interface_id) : $interface_id;
}

function okupanel_get_interfaces($type = 'objects', $interface_id = null){
	static $ret = null;
	if ($ret === null){
		$ret = array();
		foreach (okupanel_lsdir(OKUPANEL_PATH.'/inc/interfaces') as $interface){
			$id = preg_replace('#^(.*?)(\.php)$#i', '$1', $interface);
			if ($id != $interface){
				$class = 'Okupanel_interface_'.$id;
				require OKUPANEL_PATH.'/inc/interfaces/'.$interface;
				$obj = new $class();
				$obj->id = $id;
				$ret[$obj->id] = $obj;
			}
		}
	}
	return $type == 'objects' ? ($interface_id ? (isset($ret[$interface_id]) ? $ret[$interface_id] : null) : $ret) : array_keys($ret);
}

class Okupanel_interface {
	public $id = null;
}


function okupanel_get_events(){
	$cache = null;
	$events = array();

	// return from cache
	if (($cache = get_option('okupanel_events_cache', false)) && $cache['time'] > strtotime('-'.OKUPANEL_EVENTS_CACHE_DURATION.' minutes') && (!current_user_can('manage_options') || empty($_GET['update'])))

		$events = $cache['events'];

	// must fetch
	else if ($obj = okupanel_get_current_interface()){

		$ret_events = apply_filters('okupanel_events', $obj->fetch_events());

		if ($ret_events){
			$events = array();

			foreach ($ret_events as &$e){
				$e['osummary'] = $e['summary'];
				$e['odescription'] = $e['description'];
				$e['olocation'] = $e['location'];

				// please adapt manually all preg_match's below to your language. we'll try to abstract it to a field in a near future.

				// post treatment
				if (preg_match('#^((SALA|AULA)\s*)?([0-9\.][0-9\.]+(\s*(?:y|/)\s*[0-9\.]+)?)\s*([\-]\s*)?([:]\s*)?(.*?)$#ium', $e['summary'], $m))
					okupanel_add_room($e, $m[3], $m[7]);

				else if (preg_match('#^((SALA)\s*)([A-Z0-9\.]+(\s*y\s*[A-Z0-9\.]+)?)\s*([\-]\s*)?([:]\s*)?(.*?)$#ium', $e['summary'], $m))
					okupanel_add_room($e, $m[3], $m[7]);

				else if (preg_match('#^([KC]AFETA)\s*([-]\s*)?([:]\s*)?(.*?)$#ium', $e['summary'], $m))
					okupanel_add_room($e, $m[1], $m[4]);

				else if (preg_match('#^(([0-9])[aª]\s+PLANTA\s*)([-]\s*)?([:]\s*)?(.*?)$#ium', $e['summary'], $m))
					okupanel_add_room($e, $m[2], $m[5]);

				else if (preg_match('#^\s*\(?\s*(PLANTA\s*BAJA\s*)\s*\)?\s*(\(\s*entrada\s*\))([-]\s*)?([:]\s*)?(.*?)$#ius', $e['summary'], $m))
					okupanel_add_room($e, 'B', $m[5]);

				else
					$e['location'] = okupanel_clean_room($e['location']);

				if (preg_match('#^\s*\(?\s*(PLANTA\s*BAJA\s*)\s*\)?\s*([-]\s*)?([:]\s*)?(.*?)$#ius', $e['summary'], $m))
					okupanel_add_room($e, 'B', $m[4]);

				if (preg_match('#^(.*?)(\s*\(?\s*(sala|aula|planta)\s*([B0-9\.]+)\s*\)\s*)$#ium', $e['summary'], $m))
					okupanel_add_room($e, $m[4], $m[1]);

				if (preg_match('#^(.*?)\s*(patio(\s*santorini)?)\s*$#ium', $e['summary'], $m))
					okupanel_add_room($e, __('Yard', 'okupanel'), $m[1]);

				if (preg_match('#^(\s*patio(\s*santorini)?)\s*\.?\s*-?\s*(.*?)$#ium', $e['summary'], $m))
					okupanel_add_room($e, __('Yard', 'okupanel'), $m[3]);

				if (preg_match('#^(.*?)(\s*\(?\s*(sala|aula|planta)\s*([B0-9\.]+)\s*\)\s*)$#ium', $e['summary'], $m))
					okupanel_add_room($e, $m[4], $m[1]);

				if (preg_match('#^(.*?)(\s*PLANTA\s*BAJA)$#ium', $e['summary'], $m))
					okupanel_add_room($e, 'B', $m[1]);
				else if (preg_match('#^(.*?)(\s*SALA\s*(([B0-9\.]{1,5}(\s*(/|y|\+|,)\s*)?)+))$#ium', $e['summary'], $m))
					okupanel_add_room($e, $m[3], $m[1]);

				if (preg_match('#^(.*?)(\s*PLANTA\s*BAJA)$#ium', $e['location'], $m))
					okupanel_add_room($e, 'B', false, true);
				else if (preg_match('#^(.*?)(\s*SALA\s*(([B0-9\.]{1,5}(\s*(/|y|\+|,)\s*)?)+))$#ium', $e['location'], $m))
					okupanel_add_room($e, $m[3], false, true);

				if (preg_match('#^(.*?)(\s*([0-9]\.[0-9]+\.?)\s*)$#ium', $e['summary'], $m))
					okupanel_add_room($e, $m[3], $m[1]);

				while (preg_match('#^(\s*,?\s*y?\s*(SALA)?\s*([B0-9\.]{2,5}))(.*?)$#ium', $e['summary'], $m))
					okupanel_add_room($e, $m[3], $m[4]);

				if (strlen($e['summary']) >= 5 && (okupanel_strtoupper($e['summary']) == $e['summary'] || okupanel_strtolower($e['summary']) == $e['summary']))
					$e['summary'] = okupanel_strtolower($e['summary']);

				$e['summary'] = okupanel_ucfirst(ltrim($e['summary'], '·'));

				if (current_user_can('manage_options') && !empty($_GET['debug_event_parsing'])) // leave this, useful to add new undetected room patterns
					$e['summary'] = $e['osummary'];

				if (!empty($e['location']) && strlen($e['location']) >= 10){
					if (preg_match('#.*(\b[0-9A-Z]\.[0-9]*)$#', $e['location'], $m))
						$e['location'] = $m[1];
				}

				$e['summary'] = ltrim($e['summary'], '-. ');
				$e['summary'] = rtrim($e['summary'], '-. ');
				$e['summary'] = preg_replace('#\\\\,#', ',', $e['summary']);

				$e = apply_filters('okupanel_event', $e);

				if (preg_match('#([A-Z]+)#iu', $e['summary']) || preg_match('#([A-Z]+)#iu', $e['description'])){
					if ($e['recurrence'])
						okunet_adjust_recurrence($e);

					if ($e['start'] < time() || (!empty($e['end']) && $e['end'] < time()))
						continue;

					if (empty($e['exceptions']) || !in_array($e['start'], $e['exceptions']))
						$events[] = $e;
					foreach (okunet_get_other_recurrences($e) as $other_event)
						$events[] = $other_event;
				}
				//if (!empty($_GET['fullscreen']) && count($events) > 50)
				//	break;
			}
			unset($e);

			if ($events)
				usort($events, function($a, $b){
					return $a['start'] == $b['start'] ? $a['end'] > $b['end'] : $a['start'] > $b['start'];
				});

			// fetch ok
			update_option('okupanel_events_cache', array(
				'events' => $events,
				'time' => time(),
			));
		} else
			$events = $cache && $cache['events'] ? $cache['events'] : array();
	}
	return $events;
}


function okunet_get_other_recurrences($e){
	$events = array();
	if (!empty($e['recurrence'])){

		$max = strtotime('+2 month'); // fill up to..

		foreach (okupanel_convert_recurrence($e['recurrence']) as $rec){

			// transform
			if (in_array($rec['FREQ'], array('WEEKLY', 'MONTHLY')) && $rec['BYDAY']){

				$i = 0;

				$start = $e['start'];
				while ($start < $max){
					$start = strtotime($rec['FREQ'] == 'WEEKLY' ? '+7 days' : '+1 month', $start);

					if (!empty($rec['UNTIL']) && $start >= $rec['UNTIL'])
						break;

					if ($rec['INTERVAL'] == 1 || ($i % $rec['INTERVAL']) == 1){
						if (empty($e['exceptions']) || !in_array($start, $e['exceptions']))
							$events[] = array(
								'id' => $e['id'].' DUP'.$start,
								'start' => $start,
								'start_gmt' => $e['start_gmt'] + $start - $e['start'],
								'end' => $e['end'] + $start - $e['start'],
								'end_gmt' => $e['end_gmt'] + $start - $e['start'],
							) + $e;
					}
					$i++;
				}
			}
		}
	}
	return $events;
}


function okunet_get_other_recurrences2($e){
	$events = array();
	if (!empty($e['recurrence'])){

		$max = strtotime('+1 month'); // fill up to..

		foreach (okupanel_convert_recurrence($e['recurrence']) as $rec){

			// transform
			if ($rec['FREQ'] == 'WEEKLY' && $rec['BYDAY']){

				$start = $e['start'];
				while ($start < $max){
					$start = strtotime('+7 days', $start);

					if (empty($e['exceptions']) || !in_array($start, $e['exceptions']))
						$events[] = array(
							'id' => $e['id'].' DUP'.$start,
							'start' => $start,
							'start_gmt' => $e['start_gmt'] + $start - $e['start'],
							'end' => $e['end'] + $start - $e['start'],
							'end_gmt' => $e['end_gmt'] + $start - $e['start'],
						) + $e;
				}
			}
		}
	}
	return $events;
}

function okunet_adjust_recurrence(&$e){
	//$max_until = null;
	foreach (okupanel_convert_recurrence($e['recurrence']) as $rec){
		/*if (!empty($rec['UNTIL']))
			$max_until = $max_until ? max($rec['UNTIL'], $max_until) : $rec['UNTIL'];
		else
			$max_until = true;*/
		if (!empty($rec['UNTIL']) && $rec['UNTIL'] < time())
			continue;


		// transform
		if ($rec['FREQ'] == 'WEEKLY' && $rec['BYDAY']){

			// calc diff to next event
			if ($e['start'] < time()){
				$weekDay = max(intval(date_i18n('N', $e['start'])) - 1, 0); // 0 for monday, 6 for sunday
				$today = $next = strtotime(date_i18n('Y-m-d')); // today at 00:00

				while (intval(date_i18n('N', $next)) - 1 != $weekDay || (!empty($e['exceptions']) && in_array($next, $e['exceptions']))) 
					$next += DAY_IN_SECONDS;
				
				// adjust "every 2 weeks"
				if (!empty($rec['INTERVAL']) && $rec['INTERVAL'] > 1)
					while (round(($next - strtotime('midnight', $e['start_gmt'])) / (7 * DAY_IN_SECONDS)) % $rec['INTERVAL'] != 0
						&& (empty($e['exceptions']) || !in_array($next, $e['exceptions'])))
						$next += 7 * DAY_IN_SECONDS;

				$diff = $next - strtotime(date('Y-m-d', $e['start']));
//				echo "DIFF: ".($diff / DAY_IN_SECONDS).'<br>';

				$e['start'] += $diff;
				$e['start_gmt'] += $diff;
				$e['end'] += $diff;
				$e['end_gmt'] += $diff;
			}
		}
	}
	/*
	if ($max_until !== true && $max_until !== null && (empty($e['end']) || $max_until < $e['end'])){
		$e['end'] = $max_until;
		//echo $e['summary'].' => '.date('Y-m-d H:i:s', $max_until).'<br>';
	} //else if ($max_until !== true && $max_until !== null){
		//echo 'NO '.$e['summary'].' => '.date('Y-m-d H:i:s', $e['start']).' -> '.date('Y-m-d H:i:s', $e['end']).'<br>';
	//}*/
}

add_shortcode('okupanel_most_important', 'okupanel_most_important');
function okupanel_most_important($atts = array(), $content = ''){
	$html = '';
	if ($events = okupanel_get_events()){

		$sep = '<div class="okupanel-network-sep"></div>';
		$done = array();

		$patterns = array();
		if ($importants = get_option('okupanel_most_important', false))
			foreach (explode("\n", $importants) as $imp){
				$imp = trim($imp);
				if ($imp != '' && preg_match('@^(\s*fa-([a-z0-9-_]+))?\s*(#.*?#[a-z]*)\s*(\s.*)?$@isu', $imp, $m))
					$patterns[$m[3]] = array(
						'original' => $imp,
						'label' => !empty($m[4]) ? trim($m[4]) : null,
						'icon' => !empty($m[2]) ? $m[2] : 'bullhorn',
					);
			}

		if ($patterns)
			foreach ($events as $e){
				if (!empty($e['recurrence']))
					okunet_adjust_recurrence($e);

				if ($e['end_gmt'] < time())
					continue;

				$class = '';
				foreach ($patterns as $pat => $config){

					if (empty($done[$pat]) && preg_match($pat, $e['summary'])){ // please adapt manually to your language
						$done[$pat] = true;

						if ($e['start_gmt'] < time())
							$date = sprintf(_x('Until %s', 'until a time', 'okupanel'), date_i18n('G:i', $e['end']));
						else {

							if (date_i18n('Y-m-d', $e['start_gmt']) == date_i18n('Y-m-d')){
								$date = __('today', 'okupanel');
							} else if (date_i18n('Y-m-d', $e['start_gmt']) == date_i18n('Y-m-d', strtotime('+1 day')))
								$date = __('tomorrow', 'okupanel');
							else {
								$diff = $e['start'] - strtotime(date('Y-m-d').' 00:00:00');
								if ($diff <= 7 * DAY_IN_SECONDS){
									$date = date_i18n('l', $e['start']);
									$date = (function_exists('mb_substr') ? mb_substr($date, 0, 3) : substr($date, 0, 3)).'.';
								} else if ($diff < 10 * DAY_IN_SECONDS){
									$date = date_i18n(__('l', 'okupanel'), $e['start']);
									$date = (function_exists('mb_substr') ? mb_substr($date, 0, 3) : substr($date, 0, 3)).'.';
									$date .= ' '.date_i18n('j', $e['start']);
								} else
									continue;
							}
							$date .= ' '.sprintf(__('at %s', 'okupanel'), date_i18n('G:i', $e['start']));
						}
						$html .= '<div class="okupanel-most-important"><i class="fa fa-'.($e['start_gmt'] < time() ? 'play' : $config['icon']).'"></i><div class="okupanel-most-important-right"><strong>'.($config['label'] ? trim($config['label']) : $e['summary']).'</strong> <br/>'.$date.(!empty($e['location']) ? ' <span class="okupanel-most-important-location">('.okupanel_print_location($e['location']).')</span>' : '').'</div></div>';
						break;
					}
				}
			}
		if (!empty($content))
			$html .= trim($content);
	}

	return '<div class="okupanel-most-importants">'.apply_filters('okupanel_shortcode_most_important', $html).'</div>';
}

function okupanel_print_location($loc){
	if ($loc == __('Kafeta', 'okupanel'))
		return $loc;
	$loc = sprintf(__('room %s', 'okupanel'), $loc);
	return $loc;
}

function okupanel_is_featured($e){
	return preg_match('#^\s*Turno(?:\s*de\s*(?:[ck]afeta|barra))?\s*:?\s*(.+)\s*$#ui', $e['summary'], $m) ? $m[1] : false;
}

// load mods
$mods = explode(',', str_replace(' ', '', get_option('okupanel_mods', '')));
foreach (okupanel_lsdir(OKUPANEL_PATH.'/mods') as $file)
	if (preg_match('#^(.*)\.php$#i', $file, $m) && in_array($m[1], $mods))
		require(OKUPANEL_PATH.'/mods/'.$file);


function okupanel_lint_featured($str){
	$max_length = !empty($_REQUEST['fullscreen']) && $_REQUEST['fullscreen'] !== 'false' ? 65 : 33;
	$intros = array('Colectivo Kafeta: ', 'Col. Kafeta: ');

	$ostr = $str;
	$intro = strlen($intros[0].$str) > $max_length ? $intros[1] : $intros[0];

	if (strlen($intro.$str) > $max_length)
		$str = substr($str, 0, $max_length - strlen($intro)).'..';

	if (okupanel_strtoupper($str) != $str && okupanel_strtolower($str) != $str)
		$ret = $intro.$str;
	else
		$ret = $intro.okupanel_ucfirst(okupanel_strtolower($str));

	return '<span title="'.esc_attr($intros[0].$ostr).'">'.$ret.'</span>';
}


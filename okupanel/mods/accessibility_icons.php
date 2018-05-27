<?php

// add wheelchair symbols depending on the floor

if (!defined('ABSPATH'))
	exit();

// add to panel popups
add_action('okupanel_event_popup_after', 'okupanel_event_popup_after_accessible', 0, 1);
function okupanel_event_popup_after_accessible($e){
	if (okupanel_is_accessible($e)){
		?>
		<div>
			<label><?= __('Accesibilidad', 'okupanel') ?>:</label>
			<span><?= __('Acceso habilitado para personas con movilidad reducida', 'okupanel') ?></span>
		</div>
		<?php
	}
}

// add to panel lines
add_filter('okupanel_panel_tr', 'okupanel_panel_tr_accessible', 0, 2);
function okupanel_panel_tr_accessible($tr, $e){
	if (okupanel_is_accessible($e))
		$tr['summary'] .= '<i class="fa fa-wheelchair" title="'.esc_attr(__('Acceso habilitado para personas con movilidad reducida.', 'okupanel')).'"></i>';
	return $tr;
}

function okupanel_is_accessible($e){
	if (empty($e['location']))
		return false;
		
	$accessible_floors = get_option('okupanel_accessible_floors', array());
	if (!$accessible_floors)
		return false;
		
	if (!($floor = okupanel_get_floor_from_location($e['location'])))
		return false;
	return in_array($floor, $accessible_floors);
}


add_action('okupanel_print_extra_textarea_fields_2', 'okupanel_accessible_fields');
function okupanel_accessible_fields(){
	$floors = array();
	foreach (okupanel_get_events() as $e)
		if (!empty($e['location']) && ($floor = okupanel_get_floor_from_location($e['location']))){
			if (!in_array($floor, $floors))
				$floors[] = $floor;
		}
	$saved = get_option('okupanel_accessible_floors', array());
	foreach ($saved as $s)
		if (!in_array($s, $floors))
			$floors[] = $s;
			
	?>
	<div class="okupanel-field okupanel-settings-field">
		<label><?= __('Accessible floors', 'okupanel') ?></label>
		<div class="okupanel-field-inner">
			<?php 
			if (!$floors)
				echo __('Please load some events first', 'okupanel');

			else {
				usort($floors, function($a, $b){
					if (preg_match('#^([0-9]+)$#i', $a) && preg_match('#^([0-9]+)$#i', $b))
						return intval($a) < intval($b);
					if (preg_match('#^([0-9]+)$#i', $a))
						return -1;
					if (preg_match('#^([0-9]+)$#i', $b))
						return 1;
					return $a < $b;
				});
				foreach ($floors as $floor){ ?>
					<div><label><input type="checkbox" name="okupanel_accessible_floor_<?= $floor ?>" <?php if (in_array($floor, $saved)) echo 'checked '; ?>/><?= sprintf(__('Floor "%s"', 'okupanel'), $floor) ?></label></div>
				<?php 
				} 
			}
			?>
		</div>
	</div>
	<?php
}


add_action('okupanel_save_settings', 'okupanel_save_settings_accessible');
function okupanel_save_settings_accessible(){
	$floors = array();
	foreach ($_POST as $k => $v)
		if (strpos($k, 'okupanel_accessible_floor_') === 0 && $v && $v !== 'false'){
			$floor = substr($k, strlen('okupanel_accessible_floor_'));
			if (preg_match('#^([A-Z]|[0-9]+)$#i', $floor) || $floor == 'KAFETA')
				$floors[] = $floor;
		}
	update_option('okupanel_accessible_floors', $floors);
}

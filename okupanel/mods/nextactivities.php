<?php
// still in development, please do not activate still!!!

// Next activities mods: will display next **permanent** collectives' events



if (!defined('ABSPATH'))
	exit();

add_action('okupanel_print_extra_textline_fields', function(){
	?>
	<div class="okupanel-field okupanel-settings-field">
		<label><?= __('Next permanent events\' widget title', 'okupanel') ?></label>
		<div class="okupanel-field-inner">
			<div><input type="text" name="okupanel_nextactivities" value="<?= esc_attr(get_option('okupanel_nextactivities', '')) ?>" /></div>
		</div>
	</div>
	<div class="okupanel-field okupanel-settings-field">
		<label><?= __('Next permanent events\' widget limit', 'okupanel') ?></label>
		<div class="okupanel-field-inner">
			<div><input type="number" name="okupanel_nextactivities_limit" value="<?= esc_attr(get_option('okupanel_nextactivities_limit', '')) ?>" /></div>
			<div>Leave blank or to zero for no limit</div>
		</div>
	</div>
	<?php
});

add_filter('okupanel_html_fields_2', function($fields){
	$fields[] = 'nextactivities';
	return $fields;
});


add_action('okupanel_save_settings', function($fields){
	$limit = empty(@$_POST['okupanel_nextactivities_limit']) || !is_numeric($_POST['okupanel_nextactivities_limit']) ? 0 : intval($_POST['okupanel_nextactivities_limit']);
	update_option('okupanel_nextactivities_limit', $limit);
});

add_filter('okupanel_shortcode_most_important', function($html){
	
	$activities = array();
	foreach (okupanel_get_events() as $e){
		$weekly = false;
		
		if (!empty($e['recurrence']))
			foreach (okupanel_convert_recurrence($e['recurrence']) as $rec){
				if ($rec['FREQ'] == 'WEEKLY' && $rec['BYDAY']){
					$weekly = true;
					break;
				}
			}
			
		if ($weekly && !isset($activities[$e['id']])){
			$activities[$e['summary']] = $e;
		}
	}
	if (!$activities)
		return '';
		
	$limit = intval(get_option('okupanel_nextactivities_limit', 0));
	if ($limit)
		array_splice($activities, $limit);
		
	ob_start();
	echo '<div class="okupanel-nextactivities">';
	
	if ($title = get_option('okupanel_nextactivities'))
		echo '<h3 class="okupanel-nextactivies-title">'.rtrim($title, ':').':</h3>';
		
	$curDay = null;
	foreach ($activities as $e){
		if (!$curDay || date_i18n('N', $e['start']) != $curDay){
			$curDay = date_i18n('N', $e['start']);
			echo '<span class="okupanel-nextact-day">'.date_i18n(__('l', 'okupanel'), $e['start']).':</span>';
		}
			
		echo '<span><span class="okupanel-nextact-title">';
		$max = 25;
		/*
		if (mb_strlen($e['summary']) > $max){
			$max -= 5;
			while ($e['summary'][$max] != ' ')
				$max--;
			echo mb_substr($e['summary'], 0, $max).'..';
		} else */
			echo $e['summary'];
			
		echo '</span><span class="okupanel-nextact-time">(';
		
		echo date_i18n('H:i', $e['start']);
			
		echo ')</span></span>';
	}
	
	echo '</div>';
	return $html.ob_get_clean();
});

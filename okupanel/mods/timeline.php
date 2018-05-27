<?php
// events timeline with d3

if (!defined('ABSPATH'))
	exit();

add_shortcode('okupanel_timeline', function($atts = array(), $content = ''){
	
	$args = array();
	
	/*
	if (!empty($_GET['start']))
		$args['start'] = $_GET['start'];
	if (!empty($_GET['end']))
		$args['end'] = $_GET['end'];
		
	$ret = file_get_contents($base_url.'.json?'.http_build_query($args));

	$ret = @json_decode($ret);


	foreach ($ret->appt as $e)
		$events[] = array(
			'title' => $e->inv[0]->comp[0]->name,
			'title_fr' => $e->inv[0]->comp[0]->fr,
			'description' => @$e->inv[0]->comp[0]->desc[0]->_content,
			'url' => $e->inv[0]->comp[0]->url,
			'location' => $e->inv[0]->comp[0]->loc,
			'status' => $e->inv[0]->comp[0]->status,
			
			'start' => date('Y-m-d H:i:s', strtotime($e->inv[0]->comp[0]->s[0]->d)),
			'end' => date('Y-m-d H:i:s', strtotime($e->inv[0]->comp[0]->e[0]->d)),
			
			//'original' => $e,
		);
*/
	if (0){
		header('Content-Type: application/json');
		echo json_encode($events, JSON_PRETTY_PRINT);
		die();
	}

	$data = array();
	$events = okupanel_get_all_events(true);
	okupanel_sort_events_by_location_name($events);
	
	foreach ($events as $e){
		
		if (empty($e['location']))
			$e['location'] = __('Undefined location', 'okupanel');
		$lane = $e['location'];
		$lane_key = base64_encode($lane);
		$e = array(
			'type' => 'INTERVAL',
			'label' => $e['summary'],//mb_substr($e['title'], 0, 30),
			'from' => date('Y-m-d H:i:s', $e['start']),
			'to' => date('Y-m-d H:i:s', $e['end']),
		);
		
		/*
		label: 'I\'m a label',
		type: TimelineChart.TYPE.INTERVAL,
		from: new Date([2015, 2, 1]),
		to: new Date([2015, 3, 1])
		* */
		
		if (!isset($data[$lane_key])){
			$fontColor = null;
			$data[$lane_key] = array(
				'label' => $lane, 
				'bgColor' => okupanel_timeline_random_color($fontColor),
				'fontColor' => $fontColor,
				'data' => array(),
			);
		}
		$data[$lane_key]['data'][] = $e;
	}
	
	$locale_config = array(
		"decimal" => ".",
		"thousands" => ",",
		"grouping" => [3],
		"currency" => ["$", ""],
		"dateTime" => "%a %b %e %X %Y",
		"date" => "%m/%d/%Y",
		"time" => "%H:%M:%S",
		"periods" => ["AM", "PM"],
		"days" => [__("Sunday", 'okupanel'), __("Monday", 'okupanel'), __("Tuesday", 'okupanel'), __("Wednesday", 'okupanel'), __("Thursday", 'okupanel'), __("Friday", 'okupanel'), __("Saturday", 'okupanel')],
		"shortDays" => [__("Sun", 'okupanel'), __("Mon", 'okupanel'), __("Tue", 'okupanel'), __("Wed", 'okupanel'), __("Thu", 'okupanel'), __("Fri", 'okupanel'), __("Sat", 'okupanel')],
		"months" => [__("January", 'okupanel'), __("February", 'okupanel'), __("March", 'okupanel'), __("April", 'okupanel'), __("May", 'okupanel'), __("June", 'okupanel'), __("July", 'okupanel'), __("August", 'okupanel'), __("September", 'okupanel'), __("October", 'okupanel'), __("November", 'okupanel'), __("Dicember", 'okupanel')],
		"shortMonths" => [__("Jan", 'okupanel'), __("Feb", 'okupanel'), __("Mar", 'okupanel'), __("Apr", 'okupanel'), __("May", 'okupanel'), __("Jun", 'okupanel'), __("Jul", 'okupanel'), __("Aug", 'okupanel'), __("Sep", 'okupanel'), __("Oct", 'okupanel'), __("Nov", 'okupanel'), __("Dic", 'okupanel')]
	);

	ob_start();
	?>
	<script>
		var okupanel_timeline_data = <?= json_encode($data) ?>;
		var okulec_d3_locale_config = <?= json_encode($locale_config) ?>;
		
		<?php 
		
		/*
			const data = [{
		label: 'Name',
		data: [{
			type: TimelineChart.TYPE.POINT,
			at: new Date([2015, 1, 1])
		}, {
			type: TimelineChart.TYPE.POINT,
			at: new Date([2015, 2, 1])
		}]
		*/
		
		?>
	</script>
	<link rel="stylesheet" href="<?= plugins_url('timeline-includes/d3-timeline-master/dist/timeline-chart.css', __FILE__) ?>">
	<script src="<?= plugins_url('timeline-includes/d3.min.js', __FILE__) ?>"></script>
	<script src="<?= plugins_url('timeline-includes/d3-tip.min.js', __FILE__) ?>"></script>
	<script src="<?= plugins_url('timeline-includes/d3-timeline-master/dist/timeline-chart.js', __FILE__) ?>"></script>
	<script src="<?= plugins_url('timeline-includes/timeline.js', __FILE__) ?>"></script>
	
	<div id="okupanel-timeline" style="height: <?= (count($data) * 20) ?>px"></div>
	<?php
	return ob_get_clean();
});

function okupanel_timeline_random_color(&$fontColor){
	static $colors = null;
	static $keys = null;
	if (!$colors){
		$colors = array(
			'#FFFF00' => '#000000',
			'#000000' => '#FFFF00',
			'#C01AC5' => 'white',
		);
		//shuffle($colors);
		$keys = array_keys($colors);
	}
	
	static $i = 0;
	if ($i % count($colors) == 0)
		$i = 0;
	$color = $keys[$i];
	$fontColor = $colors[$keys[$i]];
	$i++;
	return $color;
}

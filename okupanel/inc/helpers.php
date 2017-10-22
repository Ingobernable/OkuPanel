<?php

if (!defined('ABSPATH'))
	exit();

function okupanel_ucfirst($str){
	return okupanel_strtoupper(okupanel_substr($str, 0, 1)).okupanel_substr($str, 1);
}

function okupanel_strtoupper($str){
	return function_exists('mb_strtoupper') ? mb_strtoupper($str) : strtoupper($str);
}

function okupanel_strtolower($str){
	return function_exists('mb_strtolower') ? mb_strtolower($str) : strtolower($str);
}

function okupanel_substr($str, $start, $length = null){
	return function_exists('mb_substr') ? mb_substr($str, $start, $length) : substr($str, $start, $length);
}

function okupanel_human_time_diff($diff, $longNotation = false){
	$not_hour = $diff % HOUR_IN_SECONDS;
	$h = ($diff - $not_hour) / HOUR_IN_SECONDS;
	$m = ceil($not_hour / MINUTE_IN_SECONDS);
	
	$str = array();
	if (!$longNotation || $h)
		$str[] = sprintf($longNotation ? _n('%s hour', '%s hours', $h, 'okupanel') : __('%sH', 'okupanel'), $h);
	if ($m)
		$str[] = $longNotation ? sprintf(__('%s minutes', 'okupanel'), $m) : $m;
	return $longNotation ? okupanel_plural($str) : implode('', $str);
}

function okupanel_plural($str, $sep = null){
	$last = array_pop($str);
	if (!$str) return $last;
	return implode(', ', $str).($sep ? $sep : ' '.__('and', 'okupanel').' ').$last;
}


function okupanel_is_page($only_panel = false){
	global $wp_query;
	if ($only_panel === false || $only_panel == 'panel'){
		if (is_singular()){
			$ids = explode(',', preg_replace('#(\s+)#', '', get_option('okupanel_page_ids', '')));
			if (in_array(get_the_ID(), $ids))
				return true;
		}
	}
	return ($wp_query->is_main_query() && !empty($wp_query->query_vars['okupanel_action']) && (!$only_panel || $wp_query->query_vars['okupanel_action'] == $only_panel))
		|| ((!$only_panel || $only_panel == 'settings') && is_admin() && !empty($_GET['page']) && in_array($_GET['page'], array('okupanel-settings')));
}


function okupanel_pretty_json($json){
	if (!is_string($json))
		$json = json_encode($json);
		
    $tc = 0;        //tab count
    $r = '';        //result
    $q = false;     //quotes
    $t = "\t";      //tab
    $nl = "\n";     //new line

    for($i=0;$i<strlen($json);$i++){
        $c = $json[$i];
        if($c=='"' && $json[$i-1]!='\\') $q = !$q;
        if($q){
            $r .= $c;
            continue;
        }
        switch($c){
            case '{':
            case '[':
                $r .= $c . $nl . str_repeat($t, ++$tc);
                break;
            case '}':
            case ']':
                $r .= $nl . str_repeat($t, --$tc) . $c;
                break;
            case ',':
                $r .= $c.' ';
                if($json[$i+1]!='{' && $json[$i+1]!='[') $r .= $nl . str_repeat($t, $tc);
                break;
            case ':':
                $r .= $c . ' ';
                break;
            default:
                $r .= $c;
        }
    }
    return stripslashes(str_replace("\\r\\n", "\\\\r\\\\n", str_replace("\t", '<span style="width: 50px; display: inline-block;"></span>', nl2br(htmlentities($r)))));
}


function okupanel_fetch($url, $return_json = false, $type = 'get', $data = array(), $headers = array(), $timeout = 5, $no_ssl_check = false){
	if ($type == 'get' && $data)
		$url .= '?'.http_build_query($data);
	
	$process = curl_init();
	curl_setopt($process, CURLOPT_URL, $url);
	if ($headers)
		curl_setopt($process, CURLOPT_HTTPHEADER, $headers);
	curl_setopt($process, CURLOPT_TIMEOUT, $timeout);
	if ($type == 'post' && $data)
		curl_setopt($process, CURLOPT_POSTFIELDS, $data);
	curl_setopt($process, CURLOPT_RETURNTRANSFER, TRUE);
	
	if ($no_ssl_check){
		curl_setopt($process, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($process, CURLOPT_SSL_VERIFYPEER, 0);
	}

	$ret = curl_exec($process);
	$error = curl_error($process);
	curl_close($process);
	if ($error && !empty($_GET['okupanel_print_errors']) && current_user_can('manage_options')){
		echo '<br>'.$error.'<br>';
		die();
	}
	
	if (!$return_json)
		return $ret;
		
	try {
		$json = json_decode($ret);
	} catch (Exception $e){
		return false;
	}
	return $json;
}

add_shortcode('okupanel', 'okupanel_shortcode');
function okupanel_shortcode(){
	ob_start();
	okupanel_print_popup();
	okupanel_print_panel();
	return ob_get_clean();
}

add_action('wp_head', 'okupanel_print_js_vars', -99999);
function okupanel_print_js_vars(){
	if (!okupanel_is_page())
		return;
	?>
	<script type="text/javascript"> 
		
		// pass variables to JS
		var OKUPANEL = <?= json_encode(array(
			'ajax_url' => admin_url('admin-ajax.php'),
			'autorefresh_frequency' => ((!empty($_GET['fullscreen']) ? OKUPANEL_FULLSCREEN_REFRESH_FREQUENCY : OKUPANEL_CLIENT_REFRESH_FREQUENCY) * MINUTE_IN_SECONDS) * 1000, // MS
			'desynced_error_delay' => (2 * (OKUPANEL_FULLSCREEN_REFRESH_FREQUENCY * MINUTE_IN_SECONDS) + 10) * 1000, // MS
			'simulate_desynced' => !empty($_GET['simulate_desynced']),
			'now' => time(),
		)) ?>;
		
	</script>
	<style>
	
		@font-face {
			font-family: 'led_board';
			src: url('<?= OKUPANEL_URL ?>/assets/font/led_board/led_board-7.ttf') format('truetype'); /* Chrome 4+, Firefox 3.5, Opera 10+, Safari 3—5 */
		}
		@font-face {
			font-family: 'soljik_dambaek';
			src: url('<?= OKUPANEL_URL ?>/assets/font/soljik_dambaek/Soljik-Dambaek.ttf') format('truetype'); /* Chrome 4+, Firefox 3.5, Opera 10+, Safari 3—5 */
		}

		@font-face {
			font-family: 'F25_bank_printer';
			src: url('<?= OKUPANEL_URL ?>/assets/font/F25_bank_printer/F25_Bank_Printer.ttf') format('truetype'); /* Chrome 4+, Firefox 3.5, Opera 10+, Safari 3—5 */
		}

		@font-face {
			font-family: 'nasalization';
			src: url('<?= OKUPANEL_URL ?>/assets/font/nasalization/nasalization-rg.ttf') format('truetype'); /* Chrome 4+, Firefox 3.5, Opera 10+, Safari 3—5 */
		}

		@font-face {
			font-family: 'roboto';
			src: url('<?= OKUPANEL_URL ?>/assets/font/roboto/Roboto-Light.ttf') format('truetype'); /* Chrome 4+, Firefox 3.5, Opera 10+, Safari 3—5 */
		}

	</style>
	<?php
}

function okupanel_print_popup(){
	?>
	<div id="okupanel-popup">
		<div class="okupanel-popup-bg"></div>
		<div class="okupanel-popup-inner">
			<div class="okupanel-popup-close"><i class="fa fa-times"></i></div>
			<div class="okupanel-popup-title">
				<div class="okupanel-popup-title-label"></div>
				<div class="okupanel-popup-link"></div>
			</div>
			<div class="okupanel-popup-content">
			</div>
		</div>
	</div>
	<?php
}


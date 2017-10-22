<?php

// Spanish Hackmeeting 2017 mods
// loads "[1.99] " at beggining of summaries, show it as hashtags and add a link in popups. Also, add a privacy-centric widget in the most_importants widget.

if (!defined('ABSPATH'))
	exit();

add_filter('okupanel_detect_hashtag', function($vars){
	
	if (!empty($vars['e']['summary']) && preg_match('#^\[(1\.([0-9\.]+?))\]\s*(.*)$#iu', $vars['e']['summary'], $m)){
		
		$vars['hashtag_type'] = 'hackmeeting2017';
		$vars['hashtag'] = $m[2];
		$vars['short_title'] = $vars['tr']['summary'] = '<span class="okupanel-summary-hashtag"><span>#HM17N'.$m[2].'</span></span> '.htmlentities(okupanel_ucfirst(trim($m[3])));
	}
	
	return $vars;
});


add_filter('okupanel_after_popup_content', function($after, $e, $hashtag, $hashtag_type){
	
	if ($hashtag && $hashtag_type == 'hackmeeting2017')
		$after .= '<div class="okupanel-hashtag-link"><i class="fa fa-info-circle"></i> Más información sobre el nodo <a href="https://es.hackmeeting.org/hm/index.php?title=2017/propuestas" target="_blank">ahí</a> (nodo 1.'.$hashtag.')</div>';
		
	return $after;
}, 0, 4);

add_filter('okupanel_shortcode_most_important', function($html){
	ob_start();
	?>
	<div class="okupanel-most-important okupanel-most-important-inline">
	<div class="okupanel-most-important-right"><strong>Respeta la privacidad</strong></div>
	<div class="okupanel-signs">
	<span class="okupanel-ban"><i class="fa fa-ban"></i><i class="fa fa-video-camera"></i><span>Videos</span></span>
	<span class="okupanel-ban"><i class="fa fa-ban"></i><i class="fa fa-camera okupanel-icon-big"></i><span>Fotos</span></span>
	<span class="okupanel-ban"><i class="fa fa-ban"></i><i class="fa fa-microphone"></i><span>Sonido</span></span>
	</div>

	<div class="okupanel-most-important-right">Durante todo el Hackmeeting no tomes imágenes o audio sin consentimiento<span class="okupanel-most-important-location"></span></div></div>
	<?php
	$html .= ob_get_clean();
	return $html;
});

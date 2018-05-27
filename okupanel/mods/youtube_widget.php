<?php

if (!defined('ABSPATH'))
	exit();

// @todo: add a textarea field in the settings page and project a random video in the sidebar when on fullscreen mode and space is available.

add_shortcode('okupanel_youtube_widget', 'okupanel_youtube_widget');
function okupanel_youtube_widget($atts = array()){
	$videos = okupanel_youtube_get_videos();
	
	ob_start();
	if (!defined('IS_AJAX') || !IS_AJAX){
		?>
		<style>
		.okupanel-youtube-widget-title {
			padding-bottom: 10px;
		}
		.okupanel-youtube-widget-video iframe {
			margin: 0 !important;
			padding: 0 !important;
		}
		.okupanel-youtube-widget {
			display: none;
		}
		.okupanel-youtube-widget.okupanel-ytw-clone iframe {
			background: black !important;
		}
		.okupanel-youtube-widget .okupanel-youtube-widget-title {
			opacity: 0;
		}
		.okupanel-panel-right > .okupanel-youtube-widget {
			border: 0 !important;
		}
		.okupanel-youtube-widget.okupanel-ytw-clone .okupanel-youtube-widget-title {
			opacity: 1;
		}
		</style>
		<script src="https://www.youtube.com/player_api"></script>
		<script>
			
			// @todo: replace "hl=es" with the current 2-letter language
			function onYouTubePlayerAPIReady(){
				if (OKUPANEL.ytw_inited) 
					return;
				OKUPANEL.ytw_inited = true;
				
				setTimeout(function(){
					okupanel_init_youtube_widgets();
					
					var pending = 0;
					setInterval(function(){
						//console.log('checking init');
						
						if (!pending && OKUPANEL.youtube_widget_playlist_last_end && !jQuery('.okupanel-ytw-playing').length){
							
							pending = Math.max(1, OKUPANEL.youtube_widget_playlist_last_end + OKUPANEL.youtube_widget_playlist_trigger_every - (new Date()).getTime());
							
							//console.log('checking init: pending', pending);
							
							if (pending <= 1){
								//console.log('checking initing');
								okupanel_init_youtube_widgets();
								pending = 0;
								
							} else {
								//console.log('checking initing in', pending);
								setTimeout(function(){
									//console.log('checking initing after', pending);
									okupanel_init_youtube_widgets();
									pending = 0;
								}, pending);
							}
						} 
						
					}, OKUPANEL.youtube_widget_playlist_trigger_every / 3);
					
				}, OKUPANEL.youtube_widget_delay_first_load); // wait 10s to load widgets
			}
			
				
			function okupanel_init_youtube_widgets(){
				if (jQuery('.okupanel-ytw-playing').length)
					return;
					
				jQuery('.okupanel-youtube-widget:not(.okupanel-ytw-playing)').addClass('okupanel-ytw-playing').each(function(){
					(function(t){
						var ref = jQuery('.okupanel-panel-right');
						var video = null;
						var videos = okupanel_shuffle(t.data('okupanel-videos'));
						
						if (!videos.length){
							t.removeClass('okupanel-ytw-playing');
							return;
						}

						jQuery('.okupanel-ytw-clone').remove();
						var clone = t.clone(false).addClass('okupanel-ytw-clone').insertBefore(t).show();
						clone.find('iframe').remove();
						t.prependTo(ref);
						
						var both = t.add(clone);
						var title, holder;
						var video_i = 0;

						function update_videos(new_videos){
							new_videos = okupanel_shuffle(new_videos);
							
							for (var i=0; i<new_videos.length; i++)
								if (jQuery.inArray(new_videos[i], videos) < 0)
									videos.push(new_videos[i]);
							
							for (var i=0; i<videos.length; i++)
								if (jQuery.inArray(videos[i], new_videos) < 0){
									videos.splice(i, 1);
									if (i <= video_i)
										video_i--;
									i--;
								}
								
						}
						
						function had_refresh(){
							return !jQuery('.okupanel-ytw-clone').length;
						}
						
						dim_widget().hide();
						
						jQuery('body').on('okupanel_sidebar_loaded', function(e, data){
							
							// update videos
							if (data.okupanel_extra && data.okupanel_extra.ytw_videos)
								update_videos(data.okupanel_extra.ytw_videos);
								
							if (OKUPANEL.youtube_widget_playing && had_refresh())
								dim_widget();
						});
							
						function startVideo(){
							jQuery('body').addClass('okupanel-ytw-has-playing');
							
							dim_widget();
							both.show();
							
							jQuery('.okupanel-youtube-widget-substitute').hide();
							OKUPANEL.youtube_widget_playing = true;
							
							if (!video){
								var w = holder.width();
								video = new YT.Player(holder[0], {
								  width: w,
								  height: w * 9 / 16,
								  controls: 0,
								  enablejsapi: 1,
								  hl: '<?= substr(get_locale(), 0, 2) ?>',
								  modestbranding: 1,
								  rel: 0,
								  cc_load_policy: 1, // force subtitles
								  showinfo: 0,
								  videoId: videos[video_i],
								  events: {
									onReady: onPlayerReady,
									onStateChange: onPlayerStateChange
								  }
								});
								
							} else
								video.loadVideoById(videos[video_i]);
							
							title.html(OKUPANEL.loading+'..');
							
							var intId = setInterval( function() {
								if (video && typeof video.getPlayerState == 'function'
									&& [ 1, 2, 5 ].indexOf( video.getPlayerState() ) >= 0 ) {
									
									if (OKUPANEL.youtube_widget_mute && OKUPANEL.youtube_widget_mute !== "0" && typeof video.mute == 'function')
										video.mute();

									title.html('<i class="fa fa-play"></i> '+video.getVideoData().title);
									dim_widget();
									
									clearInterval( intId );
								} else
									dim_widget();
							}, 250);
						}
						startVideo();

						// autoplay video
						function onPlayerReady(event) {
							event.target.playVideo();
						}

						function onPlayerStateChange(event) {        
							// when video ends
							if (event.data === 0){
								video_i++;
								if (video_i < videos.length){
									
									if (OKUPANEL.youtube_widget_delay_between){
										hideVideo();
										
										setTimeout(function(){
											startVideo();
										}, OKUPANEL.youtube_widget_delay_between);
									
									} else
										startVideo();
								
								} else {
									// end of playlist
									hideVideo(true);
								}
							}
						}

						function hideVideo(delete_it){
							
							dim_widget(true);
							jQuery('body').removeClass('okupanel-ytw-has-playing');
							
							if (delete_it){
								if (video)
									video.clearVideo();
								delete video;
								holder.html('');
							}
							both.hide();
							
							jQuery('.okupanel-youtube-widget-substitute').show();
							if (delete_it){
								t.removeClass('okupanel-ytw-playing');
								clone.replaceWith(t);
								OKUPANEL.youtube_widget_playlist_last_end = (new Date()).getTime();
							}
							OKUPANEL.youtube_widget_playing = false;
						}
						
						jQuery(window).off('resize.okupanel_ytw_resize').on('resize.okupanel_ytw_resize', function(){
							dim_widget();
						});
						
						function dim_widget(hiding){

							if (had_refresh()){
								t = jQuery('.okupanel-panel-right > .okupanel-youtube-widget');
								
								clone = t.clone(false).removeAttr('style').addClass('okupanel-ytw-clone').removeClass('okupanel-ytw-playing');
								clone.find('iframe').remove();
								
								both = t.add(clone);
								
								jQuery('.okupanel-panel-right-inner .okupanel-youtube-widget').replaceWith(clone.show()).show();
								clone.find('.okupanel-youtube-widget-title').html(t.find('.okupanel-youtube-widget-title').html());
							
								jQuery('.okupanel-youtube-widget-substitute').hide();
							
							} else
								clone = jQuery('.okupanel-ytw-clone');

							title = both.find('.okupanel-youtube-widget-title');
							holder = t.find('.okupanel-youtube-widget-video').children();
							
							if (clone.length){
								var pos = clone.show().position(ref);
								t.css({
									position: 'absolute',
									top: pos ? pos.top : 0,
									left: pos ? pos.left : 0,
									width: clone.outerWidth(),
									'z-index': 999
								});
								clone.find('.okupanel-youtube-widget-video').height(t.find('.okupanel-youtube-widget-video iframe').height());

								if (!OKUPANEL.youtube_widget_playing)
									clone.hide();
							}
							return t;
						}
					})(jQuery(this));
					
					
				});
			}
			function okupanel_shuffle(array) {
			  var currentIndex = array.length, temporaryValue, randomIndex;

			  // While there remain elements to shuffle...
			  while (0 !== currentIndex) {

				// Pick a remaining element...
				randomIndex = Math.floor(Math.random() * currentIndex);
				currentIndex -= 1;

				// And swap it with the current element.
				temporaryValue = array[currentIndex];
				array[currentIndex] = array[randomIndex];
				array[randomIndex] = temporaryValue;
			  }

			  return array;
			}
		</script>
	<?php } ?>
	<div class="okupanel-youtube-widget" data-okupanel-videos="<?= esc_attr(json_encode($videos)) ?>">
		<div class="okupanel-youtube-widget-title"><?= __('Loading', 'okupanel') ?>..</div>
		<div class="okupanel-youtube-widget-video"><div></div></div>
	</div>
	<?php
	
	return ob_get_clean();
}

add_action('okupanel_print_extra_textarea_fields_2', 'okupanel_youtube_widget_fields');
function okupanel_youtube_widget_fields(){
	?>
	<div class="okupanel-field okupanel-settings-field">
		<label><?= __('YouTube videos', 'okupanel') ?></label>
		<div class="okupanel-field-inner">
			<div><textarea name="okupanel_youtube_videos"><?= esc_textarea(get_option('okupanel_youtube_videos', '')) ?></textarea></div>
		</div>
	</div>
	<div class="okupanel-field okupanel-settings-field">
		<label><?= __('Wait before playing the listing', 'okupanel') ?></label>
		<div class="okupanel-field-inner">
			<div><input type="text" name="okupanel_youtube_widget_delay_first_load" value="<?= esc_attr(get_option('okupanel_youtube_widget_delay_first_load', '1 hour')) ?>" /></div>
		</div>
	</div>
	<div class="okupanel-field okupanel-settings-field">
		<label><?= __('Wait between videos', 'okupanel') ?></label>
		<div class="okupanel-field-inner">
			<div><input type="text" name="okupanel_youtube_widget_delay_between" value="<?= esc_attr(get_option('okupanel_youtube_widget_delay_between', '1 hour')) ?>" /></div>
		</div>
	</div>
	<div class="okupanel-field okupanel-settings-field">
		<label><?= __('Repeat playlist every', 'okupanel') ?></label>
		<div class="okupanel-field-inner">
			<div><input type="text" name="okupanel_youtube_widget_playlist_trigger_every" value="<?= esc_attr(get_option('okupanel_youtube_widget_playlist_trigger_every', '1 hour')) ?>" /></div>
		</div>
	</div>
	<div class="okupanel-field okupanel-settings-field">
		<label><?= __('Mute videos', 'okupanel') ?></label>
		<div class="okupanel-field-inner">
			<div><input type="checkbox" name="okupanel_youtube_widget_mute" <?php if (get_option('okupanel_youtube_widget_mute', 1)) echo ' checked'; ?> /></div>
		</div>
	</div>
	<?php
}

add_filter('okupanel_textline_fields', 'okupanel_youtube_widget_fields_save');
function okupanel_youtube_widget_fields_save($fields){
	$fields[] = 'youtube_videos';
	return $fields;
}

add_filter('okupanel_textline_fields_raw', 'okupanel_youtube_widget_okupanel_textline_fields_raw');
function okupanel_youtube_widget_okupanel_textline_fields_raw($fields){
	$fields[] = 'youtube_widget_playlist_trigger_every';
	$fields[] = 'youtube_widget_delay_between';
	$fields[] = 'youtube_widget_delay_first_load';
	
	return $fields;
}

add_filter('okupanel_cb_fields', 'okupanel_youtube_widget_okupanel_cb_fields');
function okupanel_youtube_widget_okupanel_cb_fields($fields){
	$fields[] = 'youtube_widget_mute';
	return $fields;
}

function okupanel_ytw_get_duration($option, $default, $min = null){
	$duration = get_option('okupanel_youtube_widget_'.$option, $default);
	$duration = max(strtotime('+'.$duration, strtotime('midnight')) - strtotime('midnight'), 0);
	if ($min && $duration < $min)
		$duration = $min;
	return $duration * 1000;
}

add_filter('okupanel_main_js_var', function($var){
	$var['youtube_widget_playlist_trigger_every'] = okupanel_ytw_get_duration('playlist_trigger_every', '1 hour');
	$var['youtube_widget_delay_first_load'] = okupanel_ytw_get_duration('delay_first_load', '1 hour');
	$var['youtube_widget_delay_between'] = okupanel_ytw_get_duration('delay_between', '1 hour');
	$var['youtube_widget_mute'] = get_option('okupanel_youtube_widget_mute', 1);
	return $var;
});			

add_filter('okupanel_js_return', function($extra){
	$extra['ytw_videos'] = okupanel_youtube_get_videos();
	return $extra;
});

function okupanel_youtube_get_videos(){
	$videos = array();
	foreach (explode("\n", get_option('okupanel_youtube_videos', '')) as $line){
		if (preg_match_all('#https?://[^/]*\byoutube\.[a-z]+/.*\bv=([^&\#\s]+)#ius', $line, $urls))
			foreach ($urls[1] as $code)
				$videos[] = $code;
	}
	return $videos;
}

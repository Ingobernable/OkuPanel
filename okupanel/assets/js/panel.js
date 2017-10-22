/* OkuPanel JS */

jQuery(document).ready(function(){
	
	var clock = jQuery('.okupanel-clock');
	var last_update = new Date();
	var date_diff = ((new Date()).getTime() / 1000) - OKUPANEL.now;
	
	if (clock.length){
		function clock_update(){
			var d = new Date();

			jQuery('body')[last_update.getTime() < d.getTime() - OKUPANEL.desynced_error_delay || OKUPANEL.simulate_desynced ? 'addClass' : 'removeClass']('okupanel-desynced'); // show as desynced after 2 times the client refresh frequency, +10s

			d.setTime((d.getTime() / 1000 - date_diff) * 1000);
			clock.html(d.getHours()+':'+(d.getMinutes() > 9 ? d.getMinutes() : '0'+d.getMinutes()));
		}
		clock_update();
		setInterval(function(){
			clock_update();
		}, OKUPANEL.simulate_desynced? 3000 : 60000); // every minute
	}
	
	// delays for the animation of the table (in seconds)
	var anim_config = {
		wait_before_start: 5,
		loop_duration: 70,
		animation_speed: 0.01,
		disappear_duration: 5,
		wait_before_reappear: 0,
		reappear_duration: 4,
		reanimate: 20, // looping again and again
	}; 

	var table_bottom = jQuery('.okupanel-table-bottom');
	var table_top = jQuery('.okupanel-table-top');
	var table = table_bottom.find('.okupanel-table-moving');
	
	var positions = {
		add_margin_top: 30, // space between first block and animated ones (in px)
		margin_bottom: 20 // space between the animated blocks and the viewport's bottom (in px)
	};
	var table_inner = table_bottom.find('.okupanel-table-bottom-inner');
	
	function update_positions(resized){
		if (!table_bottom.length)
			return;
			
		if (resized || positions.top_table_height === undefined){
			//table_top.css({overflow: 'auto', height: 'auto'});
			positions.window_height = jQuery(window).height();
			positions.bottom_table_top = table_bottom.find('tr:first').offset().top;
			positions.top_table_top = table_bottom.find('.okupanel-table-tr.okupanel-tr-not-first:first').offset().top;
			positions.top_table_height = positions.top_table_top - positions.bottom_table_top;
		}
		
		// reset bottom table's position
		if (!positions.anim_begin_time || resized || positions.top_table_height === undefined){
			table_inner.css({
				top: -positions.top_table_height,
				height: positions.window_height - positions.bottom_table_top - positions.top_table_height - positions.add_margin_top
			});
			//table_top.css({overflow: 'hidden', height: positions.top_table_height});
		
		// set bottom table's position (during animation frames)
		} else {
			
			var diff = (new Date()).getTime() - positions.anim_begin_time;
			
			var dev = diff < 1000 ? diff * positions.add_margin_top / 1000 : positions.add_margin_top;
			
			table[0].style.top = (-diff * anim_config.animation_speed - dev)+'px';
			
			table_bottom[0].style['margin-top'] = (positions.top_table_height + dev)+'px';
			
			table_inner[0].style.height = (positions.window_height - positions.bottom_table_top - positions.top_table_height - positions.add_margin_top - dev - positions.margin_bottom)+'px';
		}
	}
	
	function reset_table_position(){
		table[0].style.top = 0;
		table_bottom.css({'margin-top': positions.top_table_height});
	}
	
	var now_bottombar = jQuery('.okupanel-bottom-bar-inner').data('okupanel-bottombar');
	
	// auto refresh
	var refreshing = false;
	var new_bottombar = null;
	setInterval(function(){
		if (refreshing)
			return;
		refreshing = true;
		jQuery.post({
			url: OKUPANEL.ajax_url,
			data: {
				action: 'okupanel',
				target: 'table_refresh'
			},
			success: function(data){
				if (data && data.success){
					if (data.reload)
						location.reload();
					else {
						last_update = new Date();
						jQuery('body').removeClass('okupanel-desynced');
						jQuery('.okupanel-table').replaceWith(jQuery(data.html));
						jQuery('.okupanel-panel-right-inner').replaceWith(jQuery(data.right));
						
						if (now_bottombar != data.bottombar){
							now_bottombar = new_bottombar = data.bottombar;
						}
						update_positions(true);
					}
				}
			},
			complete: function(){
				refreshing = false;
			},
			dataType: 'json'
		});
	}, OKUPANEL.autorefresh_frequency);

	if (table_bottom.length){
		table.append(jQuery('.okupanel-panel-left .okupanel-table:first').clone(false));
		jQuery('body').addClass('okupanel-moving-ready');
		
		jQuery(window).on('resize', function(){
			update_positions(true);
		});
		jQuery('body').on('orientationchange', function(){
			update_positions(true);
		});
		update_positions();
		reset_table_position();

		setTimeout(function(){
			var h = table.outerHeight(true);
			var anim = null, anim_begin = null;
			
			var requestAnimFrame = (function(){
			  return window.requestAnimationFrame || 
				window.webkitRequestAnimationFrame || 
				window.mozRequestAnimationFrame    || 
				window.oRequestAnimationFrame      || 
				window.msRequestAnimationFrame     || 
				function( callback, element ){
					window.setTimeout(callback, 1000 / 60);
				};
			})();
			
			function do_animate(){
				positions.anim_begin_time = positions.anim_begin = (new Date()).getTime();
				
				(function update(){
					update_positions(false);
					anim = requestAnimFrame( update, table[0] );
				})();
			}
			
			setInterval(function(){
				if (positions.anim_begin && (new Date()).getTime() - positions.anim_begin > anim_config.loop_duration * 1000){
					positions.anim_begin = false;
					table.stop().animate({opacity: 0}, {
						duration: anim_config.disappear_duration * 1000, 
						complete: function(){
							var windowCancel = window.cancelAnimationFrame || 
								window.webkitCancelAnimationFrame || 
								window.mozCancelAnimationFrame    || 
								window.oCancelAnimationFrame      || 
								window.msCancelAnimationFrame;
								
							windowCancel(anim);
							anim = null;
							
							// TODO: show briefly "mas actividad en ingobernable.net/okupanel", then only, show the table again
							
							setTimeout(function(){
								reset_table_position();
								
								table.stop().animate({opacity: 1}, {
									duration: anim_config.reappear_duration * 1000, 
									complete: function(){
										
										setTimeout(function(){
											do_animate();
										}, anim_config.reanimate * 1000); // reanimate delay
									}
								});
							}, anim_config.wait_before_reappear * 1000); // wait before reappear
						}
					});
				}
			}, 3000); // check to stop the animation (fading out)
			
			do_animate(); // start first animation
			
		}, anim_config.wait_before_start * 1000); // first animation delay
	}
	
	jQuery('body.okupanel-fullscreen').closest('html').css({overflow: 'hidden'});
	jQuery('body:not(.okupanel-fullscreen)').on('click', 'a.okupanel-popup-link', function(e){
		if (jQuery(e.target).attr('href') == '#' || (!e.shiftKey && !e.ctrlKey)){
			var p = jQuery('#okupanel-popup');
			if (p.hasClass('okupanel-popup-open'))
				return false;
			var dataholder = jQuery(this).closest('tr');
			
			p.addClass('okupanel-popup-open');
			p.prependTo(jQuery('body'));
			p.find('.okupanel-popup-title-label').html(dataholder.data('okupanel-popup-title'));
			
			var c = dataholder.data('okupanel-popup-link');
			p.find('.okupanel-popup-link')[c == '' ? 'hide' : 'show']().html(c);
			
			c = dataholder.data('okupanel-popup-content');
			p.find('.okupanel-popup-content')[c == '' ? 'hide' : 'show']().html(c);

			// calculate popup's top offset
			var win_height = jQuery(window).height();
			p.css({opacity: 0}).show();
			var p_height = p.find('.okupanel-popup-inner').height();
			p.hide().css({opacity: 1});
			
			var margin_top = win_height / 6;
			var offset = p_height > win_height ? margin_top : Math.max(((win_height - p_height) / 2) - 30, 50, margin_top);
			p.find('.okupanel-popup-inner').css({'margin-top': jQuery(window).scrollTop() + offset});

			p.fadeIn('fast');
			return false;
		}
	});
	
	var bbar = jQuery('.okupanel-bottom-bar');
	if (bbar.length){
		var clone = null;
		var line_width = null;
		var win_width = null;

		var line_wrap = bbar.children('.okupanel-bottom-bar-inner');
		var line = line_wrap.find('.okupanel-bottom-bar-lines');
		
		line_wrap.css({left: win_width, display: 'inline-block'});
	
		function resize(){
			line_width = line.outerWidth(true);
			win_width = jQuery(window).width();
		}
		
		setTimeout(function(){
			bbar.css({opacity: 0}).show();
			
			resize();
			
			var speed = 60; 
			var duration = line_width * win_width / speed;
			var factor = (win_width + line_width)  / duration;
			
			var anim_bottombar = null;
			var line_left = 0;
			var offset = 0;
			var need_update = false;
			
			clone = line.clone(false).appendTo(line_wrap);
			
			jQuery(window).on('resize orientationchange', function(){
				resize();
			});
			
			function update_bottombar(diff){
				// in a frame
				line_left = win_width - (factor * diff) + offset;
				line_wrap.css({left: line_left});
			}
			
			setTimeout(function(){
				start_bottombar();
				bbar.animate({opacity: 1}, 2000);

				setInterval(function(){
					if (!anim_bottombar)
						return;
						
					if (line_left < -line_width){
						
						while (line_left < -line_width){
							offset += -line_left + clone.offset().left;
							var diff = (new Date()).getTime() - positions.anim_begin_time_bottombar;
							update_bottombar(diff);
						}
						
						if (need_update){
							var nline = clone.clone(false);
							line.replaceWith(nline);
							line = nline;
							need_update = false;
						}
						
						// append new content as second lines
						if (new_bottombar){
							var nline = jQuery(new_bottombar);
							new_bottombar = null;
							
							clone.replaceWith(nline);
							clone = nline;
							need_update = true;
						}
						
						resize();
					}
				}, 3000);
			}, 2000);
			
			function start_bottombar(){
				var requestAnimFrame = (function(){
					return window.requestAnimationFrame || 
					window.webkitRequestAnimationFrame || 
					window.mozRequestAnimationFrame    || 
					window.oRequestAnimationFrame      || 
					window.msRequestAnimationFrame     || 
						function( callback, element ){
							window.setTimeout(callback, 1000 / 60);
						};
				})();
				
				positions.anim_begin_time_bottombar = (new Date()).getTime();
				
				(function update(){
					var diff = (new Date()).getTime() - positions.anim_begin_time_bottombar;
					update_bottombar(diff);
					anim_bottombar = requestAnimFrame( update, table[0] );
				})();
			}
			
			function stop_bottombar(){
							
				var windowCancel = window.cancelAnimationFrame || 
					window.webkitCancelAnimationFrame || 
					window.mozCancelAnimationFrame    || 
					window.oCancelAnimationFrame      || 
					window.msCancelAnimationFrame;
					
				windowCancel(anim_bottombar);
				anim_bottombar = null;
			}
		}, 2000);
	}					
	
	function popup_close(){
		jQuery('#okupanel-popup.okupanel-popup-open').stop().removeClass('okupanel-popup-open').fadeOut('fast');
	}
	
	jQuery('#okupanel-popup').on('click', '.okupanel-popup-close, .okupanel-popup-bg', function(e){
		popup_close();
	});
	
	jQuery('body').on('keydown keypress', function(e){
		if (e.which == 27)
			popup_close();
	});
});

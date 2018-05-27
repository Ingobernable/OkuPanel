
jQuery(document).ready(function(){
	
	var formatted_data = [], t = null;
	for (var i in okupanel_timeline_data){
		var cdata = {
			label: okupanel_timeline_data[i].label,
			data: []
		};
		for (var j=0; j<okupanel_timeline_data[i].data.length; j++){
			
			t = okupanel_timeline_data[i].data[j].from.split(/[- :]/);
			var from = new Date(Date.UTC(t[0], t[1]-1, t[2], t[3], t[4], t[5]));
			
			t = okupanel_timeline_data[i].data[j].to.split(/[- :]/);
			var to = new Date(Date.UTC(t[0], t[1]-1, t[2], t[3], t[4], t[5]));

			cdata.data.push({
				type: TimelineChart.TYPE[okupanel_timeline_data[i].data[j].type],
				label: okupanel_timeline_data[i].data[j].label,
				at: okupanel_timeline_data[i].data[j].label,
				from: from,
				to: to,
				lane: i
			});
		}
		formatted_data.push(cdata);
	}
	//console.log(okupanel_timeline_data, formatted_data);
	
	var element = document.getElementById('okupanel-timeline');
	var timeline = new TimelineChart(element, formatted_data, {
		  tip: function(d) {
			  return d.at || `${d.from}<br>${d.to}`;
		  },
		  chartBgColor: 'white',
		  labelBgColor: function(d) {
			  return okupanel_timeline_data[d.lane].bgColor || 'black';
		  },
		  labelFontColor: function(d) {
			  return okupanel_timeline_data[d.lane].fontColor || 'white';
		  },
	  }).onVizChange(e => console.log(e));
	  
	//d3.select('#okupanel-timeline').selectAll('rect.background').attr('fill', '#555555');
});

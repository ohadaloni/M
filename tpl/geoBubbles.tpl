<div id="{$renderTo}" style="margin: 0 auto"></div>
<script type="text/javascript">
{literal}
	$(function() {
		var data = {/literal} {$dataJson} {literal};
		var mapData = Highcharts.geojson(Highcharts.maps['custom/world']);

		$('{/literal}#{$renderTo}'{literal}).highcharts('Map', {
			chart : {
				borderWidth : 1
			},

			title: {
				text: {/literal}'{$title}'{literal}
			},

			subtitle : {
				text : ''
			},

			legend: {
				enabled: false
			},

			mapNavigation: {
				enabled: true,
				buttonOptions: {
					verticalAlign: 'bottom'
				}
			},

			series : [{
				name: 'Countries',
				mapData: mapData,
				color: '#E0E0E0',
				enableMouseTracking: false
			}, {
				type: 'mapbubble',
				mapData: mapData,
				name: '{/literal}{$title}{literal}',
				joinBy: ['iso-a2', 'countryCode'],
				data: data,
				minSize: 4,
				maxSize: '12%',
				tooltip: {
					pointFormat: '{point.countryCode}: {point.z}'
				}
			}]
		});

	});
{/literal}
</script>
<br />
<br />

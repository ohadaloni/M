
<div id="{$renderTo}" style="margin:30px;min-width: 400px; height: 400px; max-width: 400px; float:left;"></div>
<script type="text/javascript">
{literal}
	/*$(function() {
		    $('{/literal}#{$renderTo}'{literal}).highcharts('Map',
					{/literal}
							{$chartJson}
					{literal}
		);
	});*/
	$(function () {
        // Instanciate the map
        $('{/literal}#{$renderTo}'{literal}).highcharts('Map', {

            chart : {
                borderWidth : 1
            },

            title : {
                text : '{/literal}{$title}'{literal},
				style : 'bold 12px "Lucida Grande", Helvetica, Arial, sans-serif'
            },

            legend: {
                layout: 'horizontal',
                borderWidth: 0,
                backgroundColor: 'rgba(255,255,255,0.85)',
                floating: true,
                verticalAlign: 'top',
                y: 25
            },

            mapNavigation: {
                enabled: true
            },

            colorAxis: {
                min: 1,
                type: 'logarithmic',
                minColor: '#EEEEFF',
                maxColor: '#000022',
                stops: [
                    [0, '#EFEFFF'],
                    [0.67, '#4444FF'],
                    [1, '#000022']
                ]
            },

            series : [{
                animation: {
                    duration: 1000
                },
                data : {/literal}{$data}{literal},
                mapData: Highcharts.maps['countries/us/us-all'],
                joinBy: ['postal-code', 'code'],
                dataLabels: {
                    enabled: true,
                    color: 'white',
                    format: '{point.code}'
                },
                name: 'density',
                tooltip: {
                    pointFormat: '{point.code}: {point.value}'
                }
            }]
        });   
	});
{/literal}
</script>


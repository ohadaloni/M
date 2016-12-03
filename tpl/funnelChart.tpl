<br />
<br />
<div id="{$renderTo}" style="min-width: 410px; max-width: 600px; height: 400px; margin: 0 auto"></div>
<script type="text/javascript">
{literal}
	$(function() {
		    $('{/literal}#{$renderTo}'{literal}).highcharts(
					{/literal}
							{$chartJson}
					{literal}
		);
	});
{/literal}
</script>
<br />
<br />

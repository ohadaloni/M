
<div id="{$renderTo}" style="margin:30px;min-width: 400px; height: 400px; max-width: 400px; float:left;"></div>
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

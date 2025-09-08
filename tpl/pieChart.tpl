<br />
<br />
<div id="{$renderTo}" style="margin: 0 auto"></div>
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

<br />
<br />
<div class="well bs-component">
	<div id="{$renderTo}" style="min-width: 400px; height: 400px; margin: 0 auto"></div>
	<script type="text/javascript">
	{literal}
		$(function() {
			chart = new Highcharts.Chart(
						{/literal}
								{$chartJson}
						{literal}
			);
		});
	{/literal}
	</script>
</div>
<br />
<br />

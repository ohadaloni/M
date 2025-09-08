{if $showCount}
	(<span id="{$numRowsSpanId}"><img border="0" src="{$M}/images/loadingSpinner.gif" /></span>
	Row<span id="{$numRowsSSpanId}"></span>)
{/if}
<table border="0">
	<tr class="mHeaderRow">
		{foreach from=$columns item=col}
			<td>{$col}</td>
		{/foreach}
	</tr>

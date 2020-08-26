{if $sql}
	<a target="_blank"
		href="?className=Mcontroller&action=exportToExcel&sql={$sql|urlencode}&fileName={$exportFileName}"><img
			src="{$M}/images/excel.png" border="0" title="Export to Excel" alt="Export to Excel" /></a>

{/if}
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

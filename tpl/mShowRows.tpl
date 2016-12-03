{if $sql}
	<a class="noHijax" target="_blank"
		href="?className=Mcontroller&action=exportToExcel&sql={$sql|urlencode}&fileName={$exportFileName}"><img
			src="{$M}/images/excel.png" border="0" title="Export to Excel" alt="Export to Excel" /></a>

{/if}
{if $showCount}
	{$rows|@count} Row{if $rows|@count != 1}s{/if}
{/if}
<table border="0">
	<tr class="mHeaderRow">
		{foreach from=$columns item=col}
			<td>{$col}</td>
		{/foreach}
	</tr>
	{foreach from=$rows item=row}
		<tr class="mRow">
			{foreach from=$row item=f}
				<td>{if $f}{$f|htmlspecialchars}{/if}</td>
			{/foreach}
		</tr>
	{/foreach}
</table>

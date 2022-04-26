{$a|@count} Rows
<table border="0">
	{foreach from=$a key=key item=item}
		<tr class="mRow">
				<td>{$key|htmlspecialchars}</td>
				<td>{msuVarDump item=$item}</td>
		</tr>
	{/foreach}
</table>

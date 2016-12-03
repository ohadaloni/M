<table border="0">
	<tr class ="mHeaderRow">
		<td>Table</td>
		<td>Rows</td>
	</tr>
	{foreach from=$tables item=table}
		<tr class ="mRow">
			<td>
					{if $table.hasId}
						<a href="?className={$table.className}&tableName={$table.name}">{$table.name}</a>
					{else}
						{$table.name} (no id)
					{/if}
			</td>
			<td>{$table.rows}</td>
		</tr>
	{/foreach}
</table>

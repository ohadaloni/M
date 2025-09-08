{$rows|@count} Row{if $rows|@count != 1}s{/if}
{if $rows|@count > 0 }
	<table border="0">
		<tr class="mHeaderRow">
			{foreach from=$columns item=col}
				{if $col != "id" && $col != 'createdOn' && $col != 'createdBy' && $col != 'lastChange' && $col != 'lastChangeBy'}
					<td>{$col}</td>
				{/if}
			{/foreach}
			<td colspan="2"></td>
		</tr>
		{foreach from=$rows item=row}
			<tr class="mRow {if $currentRow == $row.id} currentRow{/if}">
				{foreach from=$row key=col item=f}
					{if $col != "id" && $col != 'createdOn' && $col != 'createdBy' && $col != 'lastChange' && $col != 'lastChangeBy'}
						<td>{$f|htmlspecialchars}</td>
					{/if}
				{/foreach}
				<td><a href="?className={$className}&tableName={$tableName}&action=edit&id={$row.id}"><img border="0" src="/images/edit.png" alt="Edit" title="Edit" /></a></td>
				<td><a href="?className={$className}&tableName={$tableName}&action=duplicate&id={$row.id}"><img border="0" src="/images/duplicate.png" alt="Duplicate" title="Duplicate" /></a></td>
			</tr>
		{/foreach}
	</table>
{/if}

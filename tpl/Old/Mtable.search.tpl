<form method="post" class="validateForm">
	<table border="0">
		<tr class="mHeaderRow">
			<td colspan="3">
				Search <a href="/{$tableName}">{$tableName}</a> Record:
			</td>
		</tr>
		{foreach from=$fields item=field}
			{if $field.name != "id" && $field.name != 'createdOn' && $field.name != 'createdBy' && $field.name != 'lastChange' && $field.name != 'lastChangeBy'}
				<tr class="mFormRow">
					<td>{$field.name}</td>
					<td>
						{assign var=fname value=$field.name}
						{assign var=fvalue value=$row.$fname}
						{if $field.type == 'date'}
							<input type="text" class="datepicker" id="{$tableName}-{$field.name}" name="{$field.name}" size="20" value="{$fvalue|msuDateFmt}" />
						{elseif $field.type == 'time'}
							<input type="text" id="{$tableName}-{$field.name}" name="{$field.name}" size="10" value="{$fvalue|htmlspecialchars}" />
						{elseif $field.type == 'datetime'}
							<input type="text" class="datetimepicker" id="{$tableName}-{$field.name}" name="{$field.name}" size="20" value="{$fvalue|msuDateTimePickerFmt}" />
						{elseif $field.type == 'text'}
							<textarea name="{$field.name}" id="{if $databaseName}{$databaseName}-{/if}{$tableName}-{$field.name}" rows="10" cols="80">{$fvalue|htmlspecialchars}</textarea>
						{else}
							<input type="text" name="{$field.name}" id="{if $databaseName}{$databaseName}-{/if}{$tableName}-{$field.name}" {if $field.typeGroup == 'text'}class="autocomplete"{/if} size="80" value="{$fvalue|htmlspecialchars}" />
						{/if}
					</td>
				</tr>
			{/if}
		{/foreach}
		<tr class="mHeaderRow">
			<td colspan="2">
				<input type="hidden" name="className" value="{$className}" />
				<input type="hidden" name="tableName" value="{$tableName}" />
				<input type="hidden" name="action" value="{$submitAction}" />
				<input type="hidden" name="id" value="{$row.id}" />
				<input type="submit" value="{$submitLabel}" />
			</td>
		</tr>
	</table>
</form>
<br />

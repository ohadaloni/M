
<select name="{$varName}" class="selectClass" {if $selectId}id="{$selectId}"{/if}>
		<option value=""></option>
{foreach from=$listRows key=id item=val}
		<option value="{$id}"{if $id == $varValue} selected="selected"{/if}>{$val}</option>
{/foreach}
</select>

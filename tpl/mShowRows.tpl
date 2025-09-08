<table border="0">
	<tr class="mHeaderRow">
		<td>#</td>
		{foreach from=$columns item=col}
			<td>{$col}</td>
		{/foreach}
	</tr>
	{foreach from=$rows key=key item=row}
		{assign var=No value=`$key+1`}
		<tr class="mRow">
			<td>{$No}</td>
			{foreach from=$row item=f}
				<td>{if $f}{$f|htmlspecialchars}{/if}</td>
			{/foreach}
		</tr>
	{/foreach}
</table>

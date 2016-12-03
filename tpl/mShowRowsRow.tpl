		<tr class="mRow">
			{foreach from=$row item=f}
				<td>{if $f}{$f|htmlspecialchars}{/if}</td>
			{/foreach}
		</tr>
